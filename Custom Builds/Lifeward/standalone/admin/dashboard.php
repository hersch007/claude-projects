<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/slots.php';
require_once __DIR__ . '/../inc/funnel.php';
lw_require_admin();

$pdo = lw_db();

$now_utc     = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
$today_start = ( clone $now_utc )->setTime( 0, 0, 0 )->format( 'Y-m-d\TH:i:s\Z' );
$week_start  = ( clone $now_utc )->modify( '-6 days' )->setTime( 0, 0, 0 )->format( 'Y-m-d\TH:i:s\Z' );

$total_leads = (int) $pdo->query( 'SELECT COUNT(*) FROM leads' )->fetchColumn();

$stmt = $pdo->prepare( 'SELECT COUNT(*) FROM leads WHERE created_at >= ?' );
$stmt->execute( array( $today_start ) );
$leads_today = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare( 'SELECT COUNT(*) FROM leads WHERE created_at >= ?' );
$stmt->execute( array( $week_start ) );
$leads_week = (int) $stmt->fetchColumn();

$status_counts = array();
foreach ( $pdo->query( 'SELECT status, COUNT(*) AS c FROM leads GROUP BY status' ) as $row ) {
    $status_counts[ $row['status'] ] = (int) $row['c'];
}

$status_labels = array(
    'hot'            => 'Wants a call',
    'scheduled'      => 'Callback scheduled',
    'info-requested' => 'Info sent',
    'chat-lead'      => 'From AI chat',
    'lost'           => 'Not interested',
);
$max_status_count = $status_counts ? max( $status_counts ) : 0;

$chat_sessions_total = (int) $pdo->query( 'SELECT COUNT(DISTINCT session_id) FROM chat_messages' )->fetchColumn();
$chat_messages_total = (int) $pdo->query( 'SELECT COUNT(*) FROM chat_messages' )->fetchColumn();
$chat_leads          = isset( $status_counts['chat-lead'] ) ? $status_counts['chat-lead'] : 0;
$chat_conversion_pct = $chat_sessions_total > 0 ? round( $chat_leads / $chat_sessions_total * 100 ) : 0;

// Business-hours split for "call now" (hot) requests, computed per lead in
// its own Eastern local time — not just whether it's business hours *now*.
$in_hours = 0; $after_hours = 0;
foreach ( $pdo->query( "SELECT created_at FROM leads WHERE status = 'hot'" ) as $r ) {
    $dt = new DateTime( $r['created_at'], new DateTimeZone( 'UTC' ) );
    $dt->setTimezone( new DateTimeZone( 'America/New_York' ) );
    if ( lw_is_business_hours_at( $dt ) ) { $in_hours++; } else { $after_hours++; }
}
$hot_total = $in_hours + $after_hours;

// Most-requested product — scan lead notes for each catalog product's label.
$product_counts = array();
foreach ( lw_product_catalog() as $p ) $product_counts[ $p['label'] ] = 0;
$notes = $pdo->query( "SELECT notes FROM leads WHERE notes != ''" )->fetchAll( PDO::FETCH_COLUMN );
foreach ( $notes as $note ) {
    foreach ( $product_counts as $label => $count ) {
        if ( stripos( $note, $label ) !== false ) $product_counts[ $label ]++;
    }
}
arsort( $product_counts );
$max_product_count = $product_counts ? max( $product_counts ) : 0;

// Recent activity, newest first, for the live-feed ticker.
$activity_rows = $pdo->query( "
    SELECT a.action, a.created_at, l.name
    FROM activity a LEFT JOIN leads l ON l.id = a.lead_id
    WHERE a.action LIKE 'lifeward_%'
    ORDER BY a.id DESC LIMIT 15
" )->fetchAll( PDO::FETCH_ASSOC );

function lw_dashboard_activity_line( $row ) {
    $name = $row['name'] !== '' ? $row['name'] : 'Someone';
    switch ( $row['action'] ) {
        case 'lifeward_hot':            return htmlspecialchars( "$name asked for an immediate call", ENT_QUOTES );
        case 'lifeward_scheduled':       return htmlspecialchars( "$name scheduled a callback", ENT_QUOTES );
        case 'lifeward_info-requested':  return htmlspecialchars( "$name requested more info", ENT_QUOTES );
        case 'lifeward_chat-lead':       return htmlspecialchars( "$name became a lead via the AI chat", ENT_QUOTES );
        default:                         return htmlspecialchars( "$name — " . $row['action'], ENT_QUOTES );
    }
}
?><!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Lifeward Admin — Dashboard</title>
<link rel="stylesheet" href="admin.css?v=<?php echo @filemtime( __DIR__ . '/admin.css' ); ?>"></head>
<body>
<div class="lw-admin-wrap">
    <?php include __DIR__ . '/_nav.php'; ?>

    <div class="lw-admin-header">
        <h1>Dashboard</h1>
    </div>

    <div class="lw-stats">
        <a class="lw-stat lw-stat-link" href="index.php?range=today"><div class="lw-stat-value"><?php echo $leads_today; ?></div><div class="lw-stat-label">Leads Today</div></a>
        <a class="lw-stat lw-stat-link" href="index.php?range=week"><div class="lw-stat-value"><?php echo $leads_week; ?></div><div class="lw-stat-label">Leads This Week</div></a>
        <a class="lw-stat lw-stat-link" href="index.php"><div class="lw-stat-value"><?php echo $total_leads; ?></div><div class="lw-stat-label">Total Leads</div></a>
        <a class="lw-stat lw-stat-link" href="chats.php"><div class="lw-stat-value"><?php echo $chat_sessions_total; ?></div><div class="lw-stat-label">Chat Sessions</div></a>
        <a class="lw-stat lw-stat-link" href="chats.php"><div class="lw-stat-value"><?php echo $chat_conversion_pct; ?>%</div><div class="lw-stat-label">Chat &rarr; Lead Rate</div></a>
    </div>

    <div class="lw-panels">
        <div class="lw-panel-card">
            <h2>Leads by Outcome</h2>
            <?php if ( empty( $status_counts ) ): ?>
                <div class="lw-muted">No leads yet.</div>
            <?php else: foreach ( $status_labels as $key => $label ):
                $count   = isset( $status_counts[ $key ] ) ? $status_counts[ $key ] : 0;
                $pct     = $max_status_count > 0 ? round( $count / $max_status_count * 100 ) : 0;
                $is_chat = $key === 'chat-lead';
            ?>
                <?php $outcome_href = $is_chat ? 'chats.php' : ( 'index.php?status=' . urlencode( $key ) ); ?>
                <a href="<?php echo htmlspecialchars( $outcome_href, ENT_QUOTES ); ?>" class="lw-bar-row lw-bar-row-link">
                    <div class="lw-bar-label"><?php echo htmlspecialchars( $label, ENT_QUOTES ); ?></div>
                    <div class="lw-bar-track"><div class="lw-bar-fill" style="width:<?php echo $pct; ?>%"></div></div>
                    <div class="lw-bar-count"><?php echo $count; ?></div>
                </a>
            <?php endforeach; endif; ?>
        </div>

        <div class="lw-panel-card">
            <h2>"Call Now" Timing</h2>
            <?php if ( $hot_total === 0 ): ?>
                <div class="lw-muted">No call-now requests yet.</div>
            <?php else: ?>
                <a href="index.php?status=hot&amp;hours=business" class="lw-bar-row lw-bar-row-link">
                    <div class="lw-bar-label">During business hours</div>
                    <div class="lw-bar-track"><div class="lw-bar-fill" style="width:<?php echo round( $in_hours / $hot_total * 100 ); ?>%"></div></div>
                    <div class="lw-bar-count"><?php echo $in_hours; ?></div>
                </a>
                <a href="index.php?status=hot&amp;hours=after" class="lw-bar-row lw-bar-row-link">
                    <div class="lw-bar-label">After hours</div>
                    <div class="lw-bar-track"><div class="lw-bar-fill" style="width:<?php echo round( $after_hours / $hot_total * 100 ); ?>%"></div></div>
                    <div class="lw-bar-count"><?php echo $after_hours; ?></div>
                </a>
            <?php endif; ?>
            <p class="lw-hint-small" style="margin-top:14px;margin-bottom:0;">Mon&ndash;Fri, 9am&ndash;5pm Eastern, based on when each request came in.</p>
        </div>

        <div class="lw-panel-card">
            <h2>Products Mentioned</h2>
            <?php if ( $max_product_count === 0 ): ?>
                <div class="lw-muted">No product interest recorded yet.</div>
            <?php else: foreach ( $product_counts as $label => $count ):
                $pct = $max_product_count > 0 ? round( $count / $max_product_count * 100 ) : 0;
            ?>
                <a href="index.php?product=<?php echo urlencode( $label ); ?>" class="lw-bar-row lw-bar-row-link">
                    <div class="lw-bar-label"><?php echo htmlspecialchars( $label, ENT_QUOTES ); ?></div>
                    <div class="lw-bar-track"><div class="lw-bar-fill" style="width:<?php echo $pct; ?>%"></div></div>
                    <div class="lw-bar-count"><?php echo $count; ?></div>
                </a>
            <?php endforeach; endif; ?>
            <p class="lw-hint-small" style="margin-top:14px;margin-bottom:0;"><?php echo $chat_messages_total; ?> total chat messages across <?php echo $chat_sessions_total; ?> session<?php echo $chat_sessions_total === 1 ? '' : 's'; ?>.</p>
        </div>

        <div class="lw-panel-card">
            <h2>Recent Activity</h2>
            <?php if ( empty( $activity_rows ) ): ?>
                <div class="lw-muted">Nothing yet — activity shows up here as visitors go through Rachel's flow.</div>
            <?php else: ?>
                <ul class="lw-ticker">
                    <?php foreach ( $activity_rows as $row ): ?>
                        <li>
                            <span><?php echo lw_dashboard_activity_line( $row ); ?></span>
                            <time><?php echo htmlspecialchars( date( 'M j, g:i A', strtotime( $row['created_at'] ) ), ENT_QUOTES ); ?></time>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
