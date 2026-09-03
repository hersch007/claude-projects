<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$action = sanitize_key( isset( $_GET['action'] ) ? $_GET['action'] : 'list' );
$id     = (int) ( isset( $_GET['id'] ) ? $_GET['id'] : 0 );

$statuses   = function_exists( 'sp_tickets_get_statuses' )   ? sp_tickets_get_statuses()   : array( 'open', 'in_progress', 'resolved', 'closed' );
$priorities = function_exists( 'sp_tickets_get_priorities' ) ? sp_tickets_get_priorities() : array( 'low', 'normal', 'high', 'urgent' );

// ── Edit / New form ───────────────────────────────────────────────────────────
if ( $action === 'new' || $action === 'edit' ) {
    $ticket      = null;
    $contacts    = $wpdb->get_results( "SELECT id, first_name, last_name FROM {$wpdb->prefix}sp_contacts ORDER BY first_name, last_name" );
    $companies   = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sp_companies ORDER BY name" );
    $team_members = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sp_team WHERE status = 'active' ORDER BY name" );
    if ( $action === 'edit' && $id ) {
        $ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_tickets WHERE id = %d", $id ) );
        if ( ! $ticket ) { echo '<p class="sp-empty">Ticket not found.</p>'; return; }
    }
    ?>
    <div class="sp-page-header">
        <h1><?php echo $action === 'edit' ? 'Edit Ticket' : 'New Ticket'; ?></h1>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=tickets' ) ); ?>" class="sp-btn sp-btn-ghost">&larr; Back</a>
    </div>
    <div class="sp-card sp-form-card">
        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="ticket">
            <input type="hidden" name="sp_id" value="<?php echo esc_attr( $id ); ?>">

            <div class="sp-field"><label>Title</label><input type="text" name="title" value="<?php echo esc_attr( $ticket ? $ticket->title : '' ); ?>" required placeholder="Brief description of the issue"></div>

            <div class="sp-form-row">
                <div class="sp-field">
                    <label>Status</label>
                    <select name="status">
                        <?php foreach ( $statuses as $s ) : ?>
                            <option value="<?php echo $s; ?>" <?php selected( $ticket ? $ticket->status : 'open', $s ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $s ) ) ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sp-field">
                    <label>Priority</label>
                    <select name="priority">
                        <?php foreach ( $priorities as $p ) : ?>
                            <option value="<?php echo $p; ?>" <?php selected( $ticket ? $ticket->priority : 'normal', $p ); ?>><?php echo esc_html( ucfirst( $p ) ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="sp-form-row">
                <div class="sp-field">
                    <label>Contact</label>
                    <select name="contact_id">
                        <option value="0">— None —</option>
                        <?php foreach ( $contacts as $c ) : ?>
                            <option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $ticket ? $ticket->contact_id : 0, $c->id ); ?>><?php echo esc_html( trim( $c->first_name . ' ' . $c->last_name ) ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sp-field">
                    <label>Company</label>
                    <select name="company_id">
                        <option value="0">— None —</option>
                        <?php foreach ( $companies as $co ) : ?>
                            <option value="<?php echo esc_attr( $co->id ); ?>" <?php selected( $ticket ? $ticket->company_id : 0, $co->id ); ?>><?php echo esc_html( $co->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="sp-field">
                <label>Assigned To</label>
                <select name="assigned_to">
                    <option value="">— Unassigned —</option>
                    <?php foreach ( $team_members as $tm ) : ?>
                        <option value="<?php echo esc_attr( $tm->name ); ?>" <?php selected( $ticket ? $ticket->assigned_to : '', $tm->name ); ?>><?php echo esc_html( $tm->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="sp-field"><label>Description</label><textarea name="description" rows="6"><?php echo esc_textarea( $ticket ? $ticket->description : '' ); ?></textarea></div>

            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary">Save Ticket</button>
                <a href="<?php echo esc_url( home_url( '/sp-app/?view=tickets' ) ); ?>" class="sp-btn sp-btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
    <?php if ( $action === 'edit' && $id ) do_action( 'sp_ticket_edit_after', $id ); ?>
    <?php
    return;
}

// ── List ──────────────────────────────────────────────────────────────────────
$filter_status   = sanitize_key( isset( $_GET['status'] )   ? $_GET['status']   : '' );
$filter_priority = sanitize_key( isset( $_GET['priority'] ) ? $_GET['priority'] : '' );
$search          = sanitize_text_field( isset( $_GET['s'] ) ? $_GET['s'] : '' );
$paged           = max( 1, (int) ( isset( $_GET['paged'] ) ? $_GET['paged'] : 1 ) );
$limit           = 25;
$offset          = ( $paged - 1 ) * $limit;

$where  = array( '1=1' );
$params = array();

if ( $filter_status ) {
    $where[]  = 'status = %s';
    $params[] = $filter_status;
}
if ( $filter_priority ) {
    $where[]  = 'priority = %s';
    $params[] = $filter_priority;
}
if ( $search ) {
    $like     = '%' . $wpdb->esc_like( $search ) . '%';
    $where[]  = 'title LIKE %s';
    $params[] = $like;
}

$where_sql = implode( ' AND ', $where );

if ( $params ) {
    $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tickets WHERE $where_sql", $params ) );
    $rows  = $wpdb->get_results( $wpdb->prepare(
        "SELECT t.*, c.first_name, c.last_name, co.name AS company_name
         FROM {$wpdb->prefix}sp_tickets t
         LEFT JOIN {$wpdb->prefix}sp_contacts c ON c.id = t.contact_id
         LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id = t.company_id
         WHERE $where_sql
         ORDER BY FIELD(t.priority,'urgent','high','normal','low'), t.created_at DESC
         LIMIT %d OFFSET %d",
        array_merge( $params, array( $limit, $offset ) )
    ) );
} else {
    $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tickets" );
    $rows  = $wpdb->get_results( $wpdb->prepare(
        "SELECT t.*, c.first_name, c.last_name, co.name AS company_name
         FROM {$wpdb->prefix}sp_tickets t
         LEFT JOIN {$wpdb->prefix}sp_contacts c ON c.id = t.contact_id
         LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id = t.company_id
         ORDER BY FIELD(t.priority,'urgent','high','normal','low'), t.created_at DESC
         LIMIT %d OFFSET %d",
        $limit, $offset
    ) );
}
?>
<div class="sp-page-header">
    <h1>Tickets <span class="sp-count"><?php echo number_format( $total ); ?></span></h1>
    <div class="sp-header-actions">
        <form method="get" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="sp-search-form">
            <input type="hidden" name="view" value="tickets">
            <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search...">
            <select name="status" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach ( $statuses as $s ) : ?>
                    <option value="<?php echo $s; ?>" <?php selected( $filter_status, $s ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $s ) ) ); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="priority" onchange="this.form.submit()">
                <option value="">All Priorities</option>
                <?php foreach ( array_reverse( $priorities ) as $p ) : ?>
                    <option value="<?php echo $p; ?>" <?php selected( $filter_priority, $p ); ?>><?php echo esc_html( ucfirst( $p ) ); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=tickets&action=new' ) ); ?>" class="sp-btn sp-btn-primary">+ New Ticket</a>
    </div>
</div>

<div class="sp-card sp-table-card">
    <table class="sp-table">
        <thead>
            <tr><th>Title</th><th>Contact</th><th>Company</th><th>Priority</th><th>Status</th><th>Assigned</th><th>Created</th><th></th></tr>
        </thead>
        <tbody>
        <?php if ( empty( $rows ) ) : ?>
            <tr><td colspan="8" class="sp-empty">No tickets found.</td></tr>
        <?php else : foreach ( $rows as $row ) : ?>
            <tr>
                <td><a href="<?php echo esc_url( home_url( '/sp-app/?view=tickets&action=edit&id=' . $row->id ) ); ?>" class="sp-link"><?php echo esc_html( $row->title ); ?></a></td>
                <td class="sp-muted"><?php echo $row->first_name ? esc_html( trim( $row->first_name . ' ' . $row->last_name ) ) : '—'; ?></td>
                <td class="sp-muted"><?php echo $row->company_name ? esc_html( $row->company_name ) : '—'; ?></td>
                <td><span class="sp-badge sp-badge-priority-<?php echo esc_attr( $row->priority ); ?>"><?php echo esc_html( ucfirst( $row->priority ) ); ?></span></td>
                <td><span class="sp-badge sp-badge-<?php echo esc_attr( $row->status ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $row->status ) ) ); ?></span></td>
                <td class="sp-muted"><?php echo esc_html( $row->assigned_to ? $row->assigned_to : '—' ); ?></td>
                <td class="sp-muted"><?php echo esc_html( date( 'M j, Y', strtotime( $row->created_at ) ) ); ?></td>
                <td class="sp-actions">
                    <a href="<?php echo esc_url( home_url( '/sp-app/?view=tickets&action=edit&id=' . $row->id ) ); ?>">Edit</a>
                    <?php if ( function_exists( 'sp_is_admin_member' ) && sp_is_admin_member() ) : ?>
                        <a href="<?php echo esc_url( sp_delete_url( 'ticket', $row->id ) ); ?>" data-sp-confirm="Delete this ticket?" class="sp-danger">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php if ( function_exists( 'sp_pagination' ) ) sp_pagination( $total, $limit, $paged, 'tickets' ); ?>
</div>
<?php do_action( 'sp_tickets_after_list' ); ?>
