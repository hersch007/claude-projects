<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$member   = sp_get_current_team_member();
$is_admin = sp_is_admin_member();
$my_id    = $member ? (int) $member->id : 0;

// Filters
$scope    = sanitize_key( isset( $_GET['tm_scope'] ) ? $_GET['tm_scope'] : 'my' );
if ( ! in_array( $scope, array( 'my', 'all' ), true ) ) $scope = 'my';
$assignee = (int) ( isset( $_GET['tm_assignee'] ) ? $_GET['tm_assignee'] : 0 );

$team = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sp_team WHERE status='active' ORDER BY name" );

// Build WHERE
$where = array( "t.status='open'" );
$args  = array();
if ( $scope === 'my' && $my_id ) {
    $where[] = 't.assigned_to=%d';
    $args[]  = $my_id;
} elseif ( $assignee ) {
    $where[] = 't.assigned_to=%d';
    $args[]  = $assignee;
}
$where_sql = implode( ' AND ', $where );

$sql = "SELECT t.*, tm.name AS assignee_name,
        CASE t.record_type
            WHEN 'contact' THEN TRIM(CONCAT(COALESCE(c.first_name,''),' ',COALESCE(c.last_name,'')))
            WHEN 'company' THEN co.name
            WHEN 'lead'    THEN TRIM(CONCAT(COALESCE(lc.first_name,''),' ',COALESCE(lc.last_name,'')))
            ELSE '' END AS record_label
    FROM {$wpdb->prefix}sp_tasks t
    LEFT JOIN {$wpdb->prefix}sp_team tm      ON tm.id = t.assigned_to
    LEFT JOIN {$wpdb->prefix}sp_contacts c   ON ( t.record_type='contact' AND c.id = t.record_id )
    LEFT JOIN {$wpdb->prefix}sp_companies co ON ( t.record_type='company' AND co.id = t.record_id )
    LEFT JOIN {$wpdb->prefix}sp_leads l      ON ( t.record_type='lead'    AND l.id = t.record_id )
    LEFT JOIN {$wpdb->prefix}sp_contacts lc  ON ( t.record_type='lead'    AND lc.id = l.contact_id )
    WHERE $where_sql
    ORDER BY ( t.due_date IS NULL ) ASC, t.due_date ASC, t.created_at ASC";

$tasks = $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ) ) : $wpdb->get_results( $sql );

// Group by due bucket
$today    = current_time( 'Y-m-d' );
$week_end = date( 'Y-m-d', strtotime( $today . ' +7 days' ) );
$groups   = array(
    'overdue' => array( 'label' => 'Overdue',    'color' => '#dc2626', 'items' => array() ),
    'today'   => array( 'label' => 'Today',      'color' => '#f59e0b', 'items' => array() ),
    'week'    => array( 'label' => 'This Week',  'color' => '#3b82f6', 'items' => array() ),
    'later'   => array( 'label' => 'Later',      'color' => '#8b5cf6', 'items' => array() ),
    'nodate'  => array( 'label' => 'No Due Date','color' => '#94a3b8', 'items' => array() ),
);
foreach ( $tasks as $t ) {
    if ( ! $t->due_date ) {
        $groups['nodate']['items'][] = $t;
    } elseif ( $t->due_date < $today ) {
        $groups['overdue']['items'][] = $t;
    } elseif ( $t->due_date === $today ) {
        $groups['today']['items'][] = $t;
    } elseif ( $t->due_date <= $week_end ) {
        $groups['week']['items'][] = $t;
    } else {
        $groups['later']['items'][] = $t;
    }
}

$record_views = array( 'contact' => 'contacts', 'company' => 'companies', 'lead' => 'leads' );

// Helper to render a row
function sp_tm_row( $t, $team, $record_views, $scope, $assignee, $my_id ) {
    $label = trim( $t->record_label );
    $rec_view = isset( $record_views[ $t->record_type ] ) ? $record_views[ $t->record_type ] : '';
    $rec_url  = $rec_view ? home_url( '/sp-app/?view=' . $rec_view . '&action=view&id=' . $t->record_id ) : '';
    $action   = esc_url( home_url( '/sp-app/' ) );
    $hidden   = wp_nonce_field( 'sp_form', 'sp_nonce', true, false )
        . '<input type="hidden" name="sp_type" value="task_manage">'
        . '<input type="hidden" name="sp_id" value="0">'
        . '<input type="hidden" name="task_id" value="' . esc_attr( $t->id ) . '">'
        . '<input type="hidden" name="tm_scope" value="' . esc_attr( $scope ) . '">'
        . '<input type="hidden" name="tm_assignee" value="' . esc_attr( $assignee ) . '">';
    ?>
    <div class="sp-tm-row">
        <form method="post" action="<?php echo $action; ?>" class="sp-tm-check-form">
            <?php echo $hidden; ?>
            <input type="hidden" name="op" value="complete">
            <button type="submit" class="sp-tm-check" title="Mark complete"></button>
        </form>
        <div class="sp-tm-main">
            <div class="sp-tm-title"><?php echo esc_html( $t->title ); ?></div>
            <div class="sp-tm-meta">
                <?php if ( $label && $rec_url ) : ?>
                    <a href="<?php echo esc_url( $rec_url ); ?>" class="sp-tm-record sp-tm-record-<?php echo esc_attr( $t->record_type ); ?>"><?php echo esc_html( ucfirst( $t->record_type ) ); ?>: <?php echo esc_html( $label ); ?></a>
                <?php elseif ( $t->record_type === 'general' ) : ?>
                    <span class="sp-tm-record sp-tm-record-general">General</span>
                <?php endif; ?>
            </div>
        </div>
        <form method="post" action="<?php echo $action; ?>" class="sp-tm-inline">
            <?php echo $hidden; ?>
            <input type="hidden" name="op" value="reassign">
            <select name="assigned_to" onchange="this.form.submit()" class="sp-tm-select" title="Assignee">
                <option value="0">— Anyone —</option>
                <?php foreach ( $team as $m ) : ?>
                    <option value="<?php echo esc_attr( $m->id ); ?>" <?php selected( $t->assigned_to, $m->id ); ?>><?php echo esc_html( $m->name ); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <form method="post" action="<?php echo $action; ?>" class="sp-tm-inline">
            <?php echo $hidden; ?>
            <input type="hidden" name="op" value="reschedule">
            <input type="date" name="due_date" value="<?php echo esc_attr( $t->due_date ); ?>" onchange="this.form.submit()" class="sp-tm-date" title="Due date">
        </form>
        <form method="post" action="<?php echo $action; ?>" class="sp-tm-inline">
            <?php echo $hidden; ?>
            <input type="hidden" name="op" value="delete">
            <button type="button" class="sp-tm-del" title="Delete" data-sp-confirm="Delete this task?" data-sp-confirm-title="Delete Task" data-sp-confirm-btn="Delete" data-sp-confirm-class="sp-btn-danger">&times;</button>
        </form>
    </div>
    <?php
}

$total_open = count( $tasks );
?>
<div class="sp-page-header">
    <h1>Tasks <span class="sp-count"><?php echo number_format( $total_open ); ?></span></h1>
    <div class="sp-header-actions">
        <div class="sp-tm-toggle">
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=tasks&tm_scope=my' ) ); ?>" class="sp-btn <?php echo $scope === 'my' ? 'sp-btn-primary' : 'sp-btn-ghost'; ?>">My Tasks</a>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=tasks&tm_scope=all' ) ); ?>" class="sp-btn <?php echo $scope === 'all' ? 'sp-btn-primary' : 'sp-btn-ghost'; ?>">All Tasks</a>
        </div>
        <?php if ( $scope === 'all' ) : ?>
        <form method="get" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="sp-search-form">
            <input type="hidden" name="view" value="tasks">
            <input type="hidden" name="tm_scope" value="all">
            <select name="tm_assignee" onchange="this.form.submit()">
                <option value="0">All Assignees</option>
                <?php foreach ( $team as $m ) : ?>
                    <option value="<?php echo esc_attr( $m->id ); ?>" <?php selected( $assignee, $m->id ); ?>><?php echo esc_html( $m->name ); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="sp-card sp-tm-add-card">
    <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="sp-tm-add-form">
        <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
        <input type="hidden" name="sp_type" value="task_manage">
        <input type="hidden" name="sp_id" value="0">
        <input type="hidden" name="op" value="create">
        <input type="hidden" name="tm_scope" value="<?php echo esc_attr( $scope ); ?>">
        <input type="hidden" name="tm_assignee" value="<?php echo esc_attr( $assignee ); ?>">
        <input type="text" name="title" placeholder="Add a task…" required class="sp-tm-add-title">
        <select name="assigned_to" class="sp-tm-select">
            <option value="<?php echo esc_attr( $my_id ); ?>">— Me —</option>
            <option value="0">Anyone</option>
            <?php foreach ( $team as $m ) : if ( (int) $m->id === $my_id ) continue; ?>
                <option value="<?php echo esc_attr( $m->id ); ?>"><?php echo esc_html( $m->name ); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="due_date" class="sp-tm-date" title="Due date">
        <button type="submit" class="sp-btn sp-btn-primary sp-btn-sm">Add Task</button>
    </form>
</div>

<?php if ( $total_open === 0 ) : ?>
    <div class="sp-card" style="padding:48px 24px;text-align:center">
        <p class="sp-empty" style="margin:0">🎉 No open tasks<?php echo $scope === 'my' ? ' assigned to you' : ''; ?>. You're all caught up.</p>
    </div>
<?php else : ?>
    <?php foreach ( $groups as $key => $group ) :
        if ( empty( $group['items'] ) ) continue; ?>
        <div class="sp-tm-group">
            <div class="sp-tm-group-header">
                <span class="sp-tm-group-dot" style="background:<?php echo esc_attr( $group['color'] ); ?>"></span>
                <span class="sp-tm-group-label"><?php echo esc_html( $group['label'] ); ?></span>
                <span class="sp-tm-group-count"><?php echo count( $group['items'] ); ?></span>
            </div>
            <div class="sp-card sp-tm-list">
                <?php foreach ( $group['items'] as $t ) sp_tm_row( $t, $team, $record_views, $scope, $assignee, $my_id ); ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<style>
.sp-tm-toggle{display:flex;gap:6px}
.sp-tm-add-card{padding:12px 16px;margin-bottom:20px}
.sp-tm-add-form{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.sp-tm-add-title{flex:1;min-width:200px;border:1px solid #e2e8f0;border-radius:7px;padding:9px 12px;font-size:14px;outline:none}
.sp-tm-add-title:focus{border-color:var(--sp-accent,#CC1F1F)}
.sp-tm-add-form .sp-tm-select,.sp-tm-add-form .sp-tm-date{padding:8px 10px;font-size:13px}
.sp-tm-group{margin-bottom:22px}
.sp-tm-group-header{display:flex;align-items:center;gap:8px;margin-bottom:8px;padding-left:2px}
.sp-tm-group-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0}
.sp-tm-group-label{font-size:13px;font-weight:700;color:#1e293b;text-transform:uppercase;letter-spacing:.04em}
.sp-tm-group-count{font-size:11px;font-weight:700;color:#94a3b8;background:#f1f5f9;border-radius:20px;padding:2px 8px}
.sp-tm-list{padding:4px 0}
.sp-tm-row{display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #f1f5f9}
.sp-tm-row:last-child{border-bottom:none}
.sp-tm-check-form{flex-shrink:0;line-height:0}
.sp-tm-check{width:20px;height:20px;border-radius:50%;border:2px solid #cbd5e1;background:#fff;cursor:pointer;padding:0;transition:border-color .15s,background .15s}
.sp-tm-check:hover{border-color:#15803d;background:#f0fdf4}
.sp-tm-main{flex:1;min-width:0}
.sp-tm-title{font-size:14px;font-weight:600;color:#1e293b;margin-bottom:2px}
.sp-tm-meta{display:flex;gap:8px;align-items:center}
.sp-tm-record{font-size:12px;text-decoration:none;color:#64748b;background:#f1f5f9;padding:2px 8px;border-radius:5px}
.sp-tm-record:hover{background:#e2e8f0;color:#1e293b}
.sp-tm-record-general{color:#94a3b8}
.sp-tm-inline{flex-shrink:0;line-height:0}
.sp-tm-select,.sp-tm-date{border:1px solid #e2e8f0;border-radius:6px;padding:5px 8px;font-size:12px;color:#64748b;background:#f8fafc;cursor:pointer;outline:none;max-width:130px}
.sp-tm-select:focus,.sp-tm-date:focus{border-color:var(--sp-accent,#CC1F1F)}
.sp-tm-del{background:none;border:none;color:#cbd5e1;font-size:20px;cursor:pointer;line-height:1;padding:0 4px}
.sp-tm-del:hover{color:#dc2626}
@media(max-width:768px){
    .sp-tm-row{flex-wrap:wrap}
    .sp-tm-main{flex:1 1 100%;order:-1}
    .sp-tm-select,.sp-tm-date{max-width:none}
}
</style>
