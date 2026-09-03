<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/slots.php';
require_once __DIR__ . '/../inc/funnel.php';
lw_require_admin();

$pdo = lw_db();

if ( isset( $_GET['export'] ) ) {
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=lifeward-leads-' . gmdate( 'Y-m-d' ) . '.csv' );
    $all = $pdo->query( 'SELECT * FROM leads ORDER BY created_at DESC' )->fetchAll( PDO::FETCH_ASSOC );
    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, array( 'Date', 'Name', 'Email', 'Phone', 'Status', 'Score', 'Callback Time (UTC)', 'Salesforce ID', 'Notes' ) );
    foreach ( $all as $r ) {
        fputcsv( $out, array( $r['created_at'], $r['name'], $r['email'], $r['phone'], $r['status'], $r['score'], $r['slot_choice_utc'], $r['salesforce_id'], $r['notes'] ) );
    }
    fclose( $out );
    exit;
}

$status_labels = array(
    'hot'            => array( 'Wants a call',       '#fef3c7', '#92400e' ),
    'scheduled'      => array( 'Callback scheduled', '#dbeafe', '#1e40af' ),
    'info-requested' => array( 'Info sent',           '#e0f2fe', '#075985' ),
    'lost'           => array( 'Not interested',      '#f1f5f9', '#64748b' ),
    'chat-lead'      => array( 'From AI chat',        '#f3e8ff', '#6b21a8' ),
);

// ── Dashboard drill-through filters (?status=, ?range=, ?product=, ?hours=) ──
$where  = array();
$params = array();
$active_filters = array();

$status_filter = isset( $_GET['status'] ) ? trim( (string) $_GET['status'] ) : '';
if ( isset( $status_labels[ $status_filter ] ) ) {
    $where[]          = 'status = ?';
    $params[]         = $status_filter;
    $active_filters[] = $status_labels[ $status_filter ][0];
}

$range_filter = isset( $_GET['range'] ) ? trim( (string) $_GET['range'] ) : '';
if ( $range_filter === 'today' || $range_filter === 'week' ) {
    $now_utc = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
    $start   = $range_filter === 'today'
        ? ( clone $now_utc )->setTime( 0, 0, 0 )
        : ( clone $now_utc )->modify( '-6 days' )->setTime( 0, 0, 0 );
    $where[]          = 'created_at >= ?';
    $params[]         = $start->format( 'Y-m-d\TH:i:s\Z' );
    $active_filters[] = $range_filter === 'today' ? 'Today' : 'This week';
}

$product_labels_valid = array_column( lw_product_catalog(), 'label' );
$product_filter = isset( $_GET['product'] ) ? trim( (string) $_GET['product'] ) : '';
if ( in_array( $product_filter, $product_labels_valid, true ) ) {
    $where[]          = 'notes LIKE ?';
    $params[]         = '%' . $product_filter . '%';
    $active_filters[] = $product_filter;
}

$sql = 'SELECT * FROM leads';
if ( $where ) $sql .= ' WHERE ' . implode( ' AND ', $where );
$sql .= ' ORDER BY created_at DESC LIMIT 300';
$stmt = $pdo->prepare( $sql );
$stmt->execute( $params );
$rows = $stmt->fetchAll( PDO::FETCH_ASSOC );

// The business-hours split isn't a stored column — it's derived per lead from
// created_at the same way the dashboard computes it — so it's filtered here
// in PHP after the SQL fetch rather than in the query itself.
$hours_filter = isset( $_GET['hours'] ) ? trim( (string) $_GET['hours'] ) : '';
if ( $hours_filter === 'business' || $hours_filter === 'after' ) {
    $rows = array_values( array_filter( $rows, function ( $r ) use ( $hours_filter ) {
        $dt = new DateTime( $r['created_at'], new DateTimeZone( 'UTC' ) );
        $dt->setTimezone( new DateTimeZone( 'America/New_York' ) );
        $in_hours = lw_is_business_hours_at( $dt );
        return $hours_filter === 'business' ? $in_hours : ! $in_hours;
    } ) );
    $active_filters[] = $hours_filter === 'business' ? 'Business hours' : 'After hours';
}

$total = count( $rows );
$scheduled = count( array_filter( $rows, function( $r ) { return $r['status'] === 'scheduled'; } ) );
?><!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Lifeward Admin — Leads</title>
<link rel="stylesheet" href="admin.css?v=<?php echo @filemtime( __DIR__ . '/admin.css' ); ?>"></head>
<body>
<div class="lw-admin-wrap">
    <?php include __DIR__ . '/_nav.php'; ?>

    <div class="lw-admin-header">
        <h1>Leads</h1>
        <?php if ( $total > 0 ): ?><a href="?export=1" class="lw-btn-primary">&#11015; Export CSV</a><?php endif; ?>
    </div>

    <?php if ( $active_filters ): ?>
        <div class="lw-hint" style="display:flex;align-items:center;gap:10px;">
            <span>Filtered by: <strong><?php echo htmlspecialchars( implode( ' + ', $active_filters ), ENT_QUOTES ); ?></strong></span>
            <a href="index.php" style="color:var(--lw-teal);font-weight:700;text-decoration:none;">Clear</a>
        </div>
    <?php endif; ?>

    <div class="lw-stats">
        <div class="lw-stat"><div class="lw-stat-value"><?php echo (int) $total; ?></div><div class="lw-stat-label">Total Leads</div></div>
        <div class="lw-stat"><div class="lw-stat-value"><?php echo (int) $scheduled; ?></div><div class="lw-stat-label">Callbacks Scheduled</div></div>
    </div>

    <?php if ( empty( $rows ) ): ?>
        <div class="lw-empty">No leads yet — they'll show up here as visitors go through Rachel's flow.</div>
    <?php else: ?>
        <table class="lw-table">
            <thead><tr><th>Date</th><th>Name</th><th>Contact</th><th>Outcome</th><th>Callback Time</th></tr></thead>
            <tbody>
            <?php foreach ( $rows as $r ):
                $label = isset( $status_labels[ $r['status'] ] ) ? $status_labels[ $r['status'] ] : array( $r['status'], '#f1f5f9', '#64748b' );
                $slot  = '—';
                if ( $r['slot_choice_utc'] ) {
                    $slot_dt = new DateTime( $r['slot_choice_utc'], new DateTimeZone( 'UTC' ) );
                    $slot_dt->setTimezone( new DateTimeZone( 'America/New_York' ) );
                    $slot = $slot_dt->format( 'D, M j \a\t g:i A' ) . ' ET';
                }
            ?>
                <tr>
                    <td><?php echo htmlspecialchars( date( 'M j, g:i A', strtotime( $r['created_at'] ) ), ENT_QUOTES ); ?></td>
                    <td><?php echo htmlspecialchars( $r['name'] ?: '—', ENT_QUOTES ); ?></td>
                    <td><?php echo htmlspecialchars( $r['email'] ?: '—', ENT_QUOTES ); ?><br><span class="lw-muted"><?php echo htmlspecialchars( $r['phone'], ENT_QUOTES ); ?></span></td>
                    <td><span class="lw-badge" style="background:<?php echo $label[1]; ?>;color:<?php echo $label[2]; ?>"><?php echo htmlspecialchars( $label[0], ENT_QUOTES ); ?></span></td>
                    <td><?php echo htmlspecialchars( $slot, ENT_QUOTES ); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
