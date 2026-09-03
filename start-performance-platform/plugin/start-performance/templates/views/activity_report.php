<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! sp_is_admin_member() ) { echo '<p class="sp-empty">Access denied.</p>'; return; }

global $wpdb;

// ── Filters ────────────────────────────────────────────────────────────────────
$filter_member = isset( $_GET['member'] )   ? (int) $_GET['member']                           : 0;
$filter_type   = isset( $_GET['rtype'] )    ? sanitize_key( $_GET['rtype'] )                  : '';
$filter_action = isset( $_GET['raction'] )  ? sanitize_key( $_GET['raction'] )                : '';
$filter_from   = isset( $_GET['from'] )     ? sanitize_text_field( $_GET['from'] )            : date( 'Y-m-d', strtotime( '-30 days' ) );
$filter_to     = isset( $_GET['to'] )       ? sanitize_text_field( $_GET['to'] )              : date( 'Y-m-d' );
$page          = max( 1, isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1 );
$per_page      = 50;
$offset        = ( $page - 1 ) * $per_page;

// ── Build query ────────────────────────────────────────────────────────────────
$where  = array( "a.created_at >= %s", "a.created_at <= %s" );
$params = array( $filter_from . ' 00:00:00', $filter_to . ' 23:59:59' );

if ( $filter_member ) { $where[] = 'a.created_by = %d'; $params[] = $filter_member; }
if ( $filter_type )   { $where[] = 'a.record_type = %s'; $params[] = $filter_type; }
if ( $filter_action ) { $where[] = 'a.action = %s';      $params[] = $filter_action; }

$where_sql = 'WHERE ' . implode( ' AND ', $where );

$total = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sp_activity a $where_sql", $params
) );

$rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT a.*, t.name AS member_name
     FROM {$wpdb->prefix}sp_activity a
     LEFT JOIN {$wpdb->prefix}sp_team t ON t.id = a.created_by
     $where_sql
     ORDER BY a.created_at DESC
     LIMIT %d OFFSET %d",
    array_merge( $params, array( $per_page, $offset ) )
) );

// ── Summary stats (for the date range) ────────────────────────────────────────
$summary = $wpdb->get_results( $wpdb->prepare(
    "SELECT t.name, COUNT(*) AS total
     FROM {$wpdb->prefix}sp_activity a
     LEFT JOIN {$wpdb->prefix}sp_team t ON t.id = a.created_by
     $where_sql
     GROUP BY a.created_by ORDER BY total DESC LIMIT 5",
    $params
) );

// ── Filter options ─────────────────────────────────────────────────────────────
$team_members = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sp_team WHERE status='active' ORDER BY name" );
$record_types = array( 'contact' => 'Contact', 'company' => 'Company', 'lead' => 'Lead', 'ticket' => 'Ticket', 'note' => 'Note', 'task' => 'Task', 'file' => 'File' );
$actions      = $wpdb->get_col( "SELECT DISTINCT action FROM {$wpdb->prefix}sp_activity ORDER BY action" );

$total_pages = ceil( $total / $per_page );

// ── Action labels ──────────────────────────────────────────────────────────────
$action_labels = array(
    'created'       => 'Created',
    'updated'       => 'Updated',
    'deleted'       => 'Deleted',
    'status_change' => 'Status Changed',
    'note_added'    => 'Note Added',
    'task_added'    => 'Task Added',
    'task_done'     => 'Task Completed',
    'file_uploaded' => 'File Uploaded',
    'file_deleted'  => 'File Deleted',
);

function sp_activity_action_label( $action ) {
    global $action_labels;
    return isset( $action_labels[ $action ] ) ? $action_labels[ $action ] : ucwords( str_replace( '_', ' ', $action ) );
}

function sp_activity_record_link( $type, $id ) {
    $map = array(
        'contact' => array( 'view' => 'contacts',  'param' => 'action=view&id=' ),
        'company' => array( 'view' => 'companies', 'param' => 'action=view&id=' ),
        'lead'    => array( 'view' => 'leads',     'param' => 'action=view&id=' ),
    );
    if ( ! isset( $map[ $type ] ) ) return ucfirst( $type ) . ' #' . $id;
    $v = $map[ $type ];
    return '<a class="sp-link" href="' . esc_url( home_url( '/sp-app/?view=' . $v['view'] . '&' . $v['param'] . $id ) ) . '">' . ucfirst( $type ) . ' #' . $id . '</a>';
}

$base_url = home_url( '/sp-app/?view=activity_report' );
function sp_report_url( $extra ) {
    global $filter_member, $filter_type, $filter_action, $filter_from, $filter_to, $base_url;
    $p = array_merge( array(
        'member' => $filter_member, 'rtype' => $filter_type,
        'raction' => $filter_action, 'from' => $filter_from, 'to' => $filter_to,
    ), $extra );
    $q = '';
    foreach ( $p as $k => $v ) { if ( $v ) $q .= '&' . $k . '=' . urlencode( $v ); }
    return $base_url . $q;
}
?>
<div class="sp-page-header">
    <h1>Team Activity Report</h1>
    <div class="sp-header-actions">
        <a href="<?php echo esc_url( home_url( '/sp-app/?sp_export=activity&from=' . $filter_from . '&to=' . $filter_to . ( $filter_member ? '&member=' . $filter_member : '' ) . ( $filter_type ? '&rtype=' . $filter_type : '' ) . ( $filter_action ? '&raction=' . $filter_action : '' ) ) ); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">Export CSV</a>
    </div>
</div>

<!-- Filters -->
<form method="get" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="sp-report-filters">
    <input type="hidden" name="view" value="activity_report">
    <div class="sp-report-filter-row">
        <div class="sp-field-inline">
            <label>From</label>
            <input type="date" name="from" value="<?php echo esc_attr( $filter_from ); ?>">
        </div>
        <div class="sp-field-inline">
            <label>To</label>
            <input type="date" name="to" value="<?php echo esc_attr( $filter_to ); ?>">
        </div>
        <div class="sp-field-inline">
            <label>Team Member</label>
            <select name="member">
                <option value="">All Members</option>
                <?php foreach ( $team_members as $m ) : ?>
                    <option value="<?php echo esc_attr( $m->id ); ?>" <?php selected( $filter_member, $m->id ); ?>><?php echo esc_html( $m->name ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sp-field-inline">
            <label>Record Type</label>
            <select name="rtype">
                <option value="">All Types</option>
                <?php foreach ( $record_types as $k => $v ) : ?>
                    <option value="<?php echo esc_attr( $k ); ?>" <?php selected( $filter_type, $k ); ?>><?php echo esc_html( $v ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sp-field-inline">
            <label>Action</label>
            <select name="raction">
                <option value="">All Actions</option>
                <?php foreach ( $actions as $a ) : ?>
                    <option value="<?php echo esc_attr( $a ); ?>" <?php selected( $filter_action, $a ); ?>><?php echo esc_html( sp_activity_action_label( $a ) ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="sp-btn sp-btn-primary sp-btn-sm">Filter</button>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=activity_report' ) ); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">Reset</a>
    </div>
</form>

<!-- Summary cards -->
<?php if ( ! empty( $summary ) ) : ?>
<div class="sp-report-summary">
    <div class="sp-report-summary-label">Top activity <?php echo esc_html( $filter_from ); ?> – <?php echo esc_html( $filter_to ); ?></div>
    <div class="sp-report-summary-bars">
        <?php
        $max = max( array_column( (array) $summary, 'total' ) );
        foreach ( $summary as $s ) :
            $pct = $max ? round( $s->total / $max * 100 ) : 0;
            $name = $s->member_name ?: 'System / Import';
        ?>
        <div class="sp-report-bar-row">
            <span class="sp-report-bar-name"><?php echo esc_html( $name ); ?></span>
            <div class="sp-report-bar-track">
                <div class="sp-report-bar-fill" style="width:<?php echo $pct; ?>%"></div>
            </div>
            <span class="sp-report-bar-count"><?php echo esc_html( $s->total ); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Results table -->
<div class="sp-card sp-table-card" style="margin-top:16px">
    <div class="sp-card-header">
        <h2><?php echo number_format( $total ); ?> activities</h2>
        <?php if ( $total_pages > 1 ) : ?>
            <span style="font-size:12px;color:#94a3b8">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
        <?php endif; ?>
    </div>
    <?php if ( empty( $rows ) ) : ?>
        <p class="sp-empty">No activity found for the selected filters.</p>
    <?php else : ?>
    <table class="sp-table">
        <thead>
            <tr>
                <th>When</th>
                <th>Who</th>
                <th>Action</th>
                <th>Record</th>
                <th>Detail</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $rows as $row ) : ?>
            <tr>
                <td style="white-space:nowrap;color:#94a3b8;font-size:12px">
                    <?php echo esc_html( date( 'M j, g:ia', strtotime( $row->created_at ) ) ); ?>
                </td>
                <td>
                    <?php if ( $row->member_name ) : ?>
                        <span class="sp-report-who"><?php echo esc_html( $row->member_name ); ?></span>
                    <?php else : ?>
                        <span style="color:#94a3b8;font-size:12px">System</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="sp-report-action sp-report-action-<?php echo esc_attr( $row->action ); ?>">
                        <?php echo esc_html( sp_activity_action_label( $row->action ) ); ?>
                    </span>
                </td>
                <td><?php echo sp_activity_record_link( $row->record_type, $row->record_id ); ?></td>
                <td style="font-size:12px;color:#64748b;max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    <?php echo esc_html( $row->detail ); ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ( $total_pages > 1 ) : ?>
    <div class="sp-pagination">
        <?php if ( $page > 1 ) : ?>
            <a href="<?php echo esc_url( sp_report_url( array( 'paged' => $page - 1 ) ) ); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">&larr; Prev</a>
        <?php endif; ?>
        <span><?php echo $page; ?> / <?php echo $total_pages; ?></span>
        <?php if ( $page < $total_pages ) : ?>
            <a href="<?php echo esc_url( sp_report_url( array( 'paged' => $page + 1 ) ) ); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">Next &rarr;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.sp-report-filters{margin-bottom:20px}
.sp-report-filter-row{display:flex;flex-wrap:wrap;align-items:flex-end;gap:10px;padding:14px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:10px}
.sp-report-summary{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;margin-bottom:4px}
.sp-report-summary-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:12px}
.sp-report-summary-bars{display:flex;flex-direction:column;gap:8px}
.sp-report-bar-row{display:flex;align-items:center;gap:10px}
.sp-report-bar-name{font-size:13px;font-weight:500;color:#1e293b;width:140px;flex-shrink:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sp-report-bar-track{flex:1;height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden}
.sp-report-bar-fill{height:100%;background:var(--sp-accent,#CC1F1F);border-radius:4px;transition:width .3s}
.sp-report-bar-count{font-size:12px;font-weight:700;color:#64748b;width:30px;text-align:right;flex-shrink:0}
.sp-report-who{font-size:13px;font-weight:600;color:#1e293b}
.sp-report-action{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;background:#f1f5f9;color:#475569}
.sp-report-action-created{background:#dcfce7;color:#15803d}
.sp-report-action-updated{background:#dbeafe;color:#1d4ed8}
.sp-report-action-deleted{background:#fee2e2;color:#dc2626}
.sp-report-action-status_change{background:#fef3c7;color:#b45309}
.sp-report-action-note_added{background:#ede9fe;color:#7c3aed}
.sp-report-action-task_done{background:#dcfce7;color:#15803d}
.sp-report-action-file_uploaded{background:#e0f2fe;color:#0369a1}
@media(max-width:768px){
  .sp-report-filter-row{gap:8px}
  .sp-report-bar-name{width:100px}
}
</style>
