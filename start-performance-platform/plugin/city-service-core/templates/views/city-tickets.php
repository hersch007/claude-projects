<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$action = sanitize_key( isset( $_GET['action'] ) ? $_GET['action'] : 'list' );
$id     = (int) ( isset( $_GET['id'] ) ? $_GET['id'] : 0 );
$depts  = sp_city_get_departments();

// ── Detail / Edit ─────────────────────────────────────────────────────────────
if ( $action === 'edit' && $id ) {
    $ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_city_tickets WHERE id = %d", $id ) );
    if ( ! $ticket ) { echo '<p class="sp-empty">Ticket not found.</p>'; return; }

    $ticket_dept_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT dept_id FROM {$wpdb->prefix}sp_city_ticket_depts WHERE ticket_id = %d", $id
    ) );
    $ticket_depts = $wpdb->get_results( $wpdb->prepare(
        "SELECT td.*, d.name AS dept_name, d.color AS dept_color, tm.name AS ack_member_name
         FROM {$wpdb->prefix}sp_city_ticket_depts td
         LEFT JOIN {$wpdb->prefix}sp_city_departments d ON d.id = td.dept_id
         LEFT JOIN {$wpdb->prefix}sp_team tm ON tm.name = td.acknowledged_by
         WHERE td.ticket_id = %d ORDER BY d.sort_order", $id
    ) );
    $notes = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sp_city_ticket_notes WHERE ticket_id = %d ORDER BY created_at ASC", $id
    ) );
    $notif_log = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sp_city_notification_log WHERE ticket_id = %d ORDER BY sent_at DESC LIMIT 20", $id
    ) );
    $member  = function_exists( 'sp_get_current_team_member' ) ? sp_get_current_team_member() : null;
    $is_admin = function_exists( 'sp_is_admin_member' ) && sp_is_admin_member();
    $statuses = array( 'open', 'acknowledged', 'in_progress', 'resolved', 'closed' );

    $priority_colors = array(
        'emergency' => '#DC2626', 'high' => '#EA580C', 'normal' => '#2563EB', 'low' => '#6B7280'
    );
    $pcolor = isset( $priority_colors[ $ticket->priority ] ) ? $priority_colors[ $ticket->priority ] : '#6B7280';
    ?>
    <div class="sp-page-header">
        <div>
            <h1 style="display:flex;align-items:center;gap:10px">
                <?php echo esc_html( $ticket->ticket_number ); ?>
                <span class="sp-badge sp-badge-priority-<?php echo esc_attr( $ticket->priority ); ?>"><?php echo esc_html( ucfirst( $ticket->priority ) ); ?></span>
                <span class="sp-badge sp-badge-<?php echo esc_attr( $ticket->status ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $ticket->status ) ) ); ?></span>
            </h1>
            <div style="font-size:13px;color:var(--sp-muted);margin-top:4px">
                Submitted <?php echo esc_html( date( 'D M j Y, g:ia', strtotime( $ticket->created_at ) ) ); ?>
                <?php if ( $ticket->source === 'public' ) echo ' &mdash; <strong>Public submission</strong>'; ?>
            </div>
        </div>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=city-tickets' ) ); ?>" class="sp-btn sp-btn-ghost">&larr; Back</a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start">

        <!-- LEFT: ticket details + notes -->
        <div>
            <div class="sp-card sp-form-card">
                <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
                    <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                    <input type="hidden" name="sp_type" value="city_ticket">
                    <input type="hidden" name="sp_id" value="<?php echo $id; ?>">

                    <div class="sp-field">
                        <label>Address / Location</label>
                        <input type="text" name="address" value="<?php echo esc_attr( $ticket->address ); ?>" required>
                    </div>

                    <div class="sp-form-row">
                        <div class="sp-field">
                            <label>Priority</label>
                            <select name="priority">
                                <?php foreach ( array( 'low', 'normal', 'high', 'emergency' ) as $p ) : ?>
                                    <option value="<?php echo $p; ?>" <?php selected( $ticket->priority, $p ); ?>><?php echo ucfirst( $p ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="sp-field">
                            <label>Status</label>
                            <select name="status">
                                <?php foreach ( $statuses as $s ) : ?>
                                    <option value="<?php echo $s; ?>" <?php selected( $ticket->status, $s ); ?>><?php echo ucwords( str_replace( '_', ' ', $s ) ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="sp-field">
                        <label>Departments</label>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px">
                        <?php foreach ( $depts as $d ) : ?>
                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px">
                                <input type="checkbox" name="dept_ids[]" value="<?php echo esc_attr( $d->id ); ?>" <?php checked( in_array( $d->id, $ticket_dept_ids ) ); ?>>
                                <span style="display:inline-block;width:8px;height:8px;background:<?php echo esc_attr( $d->color ); ?>;border-radius:50%"></span>
                                <?php echo esc_html( $d->name ); ?>
                            </label>
                        <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="sp-field">
                        <label>Description</label>
                        <textarea name="description" rows="5"><?php echo esc_textarea( $ticket->description ); ?></textarea>
                    </div>

                    <div class="sp-form-actions">
                        <button type="submit" class="sp-btn sp-btn-primary">Save Changes</button>
                        <?php if ( $is_admin ) : ?>
                            <a href="<?php echo esc_url( sp_delete_url( 'city_ticket', $id ) ); ?>" data-sp-confirm="Permanently delete this ticket?" class="sp-btn sp-btn-ghost sp-danger">Delete</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Dept acknowledgements -->
            <?php if ( ! empty( $ticket_depts ) ) : ?>
            <div class="sp-card" style="margin-top:12px">
                <div class="sp-card-header"><h2>Department Status</h2></div>
                <table class="sp-table">
                    <thead><tr><th>Department</th><th>Status</th><th>Acknowledged By</th><th>Time</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ( $ticket_depts as $td ) : ?>
                    <tr>
                        <td>
                            <span style="display:inline-block;width:8px;height:8px;background:<?php echo esc_attr( $td->dept_color ); ?>;border-radius:50%;margin-right:6px"></span>
                            <?php echo esc_html( $td->dept_name ); ?>
                        </td>
                        <td><span class="sp-badge sp-badge-<?php echo esc_attr( $td->status ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $td->status ) ) ); ?></span></td>
                        <td class="sp-muted"><?php echo esc_html( $td->acknowledged_by ?: '—' ); ?></td>
                        <td class="sp-muted"><?php echo $td->acknowledged_at ? esc_html( date( 'M j g:ia', strtotime( $td->acknowledged_at ) ) ) : '—'; ?></td>
                        <td>
                        <?php if ( $td->status === 'open' ) : ?>
                            <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" style="display:inline">
                                <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                                <input type="hidden" name="sp_type" value="city_ticket_ack">
                                <input type="hidden" name="sp_id" value="0">
                                <input type="hidden" name="ticket_id" value="<?php echo $id; ?>">
                                <input type="hidden" name="dept_id" value="<?php echo esc_attr( $td->dept_id ); ?>">
                                <button type="submit" class="sp-btn sp-btn-sm sp-btn-primary">Acknowledge</button>
                            </form>
                        <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Notes / timeline -->
            <div class="sp-card" style="margin-top:12px">
                <div class="sp-card-header"><h2>Notes & Updates</h2></div>
                <?php if ( ! empty( $notes ) ) : ?>
                <div style="padding:0 16px">
                <?php foreach ( $notes as $n ) : ?>
                    <div style="border-bottom:1px solid var(--sp-border);padding:12px 0;display:flex;gap:12px">
                        <div style="flex:1">
                            <div style="font-size:12px;color:var(--sp-muted);margin-bottom:4px">
                                <strong><?php echo esc_html( $n->created_by ); ?></strong> &mdash;
                                <?php echo esc_html( date( 'M j Y, g:ia', strtotime( $n->created_at ) ) ); ?>
                                <?php if ( $n->is_public ) echo '<span class="sp-badge" style="margin-left:6px">Public</span>'; ?>
                            </div>
                            <div><?php echo nl2br( esc_html( $n->note ) ); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
                <?php else : ?>
                    <p class="sp-empty">No notes yet.</p>
                <?php endif; ?>

                <div style="padding:16px;border-top:1px solid var(--sp-border)">
                    <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
                        <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                        <input type="hidden" name="sp_type" value="city_ticket_note">
                        <input type="hidden" name="sp_id" value="0">
                        <input type="hidden" name="ticket_id" value="<?php echo $id; ?>">
                        <div class="sp-field"><label>Add Note</label><textarea name="note" rows="3" placeholder="Add a note or status update..."></textarea></div>
                        <div class="sp-form-row" style="align-items:center">
                            <div class="sp-field" style="flex:0 0 auto">
                                <label>Update Status</label>
                                <select name="status">
                                    <option value="">— No change —</option>
                                    <?php foreach ( $statuses as $s ) : ?>
                                        <option value="<?php echo $s; ?>"><?php echo ucwords( str_replace( '_', ' ', $s ) ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="flex:0 0 auto;padding-top:20px">
                                <label style="display:flex;align-items:center;gap:6px;font-size:13px">
                                    <input type="checkbox" name="is_public" value="1"> Visible to public
                                </label>
                            </div>
                            <div style="flex:1;padding-top:20px;text-align:right">
                                <button type="submit" class="sp-btn sp-btn-primary">Add Note</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT: reporter info + notification log -->
        <div>
            <div class="sp-card">
                <div class="sp-card-header"><h2>Reporter</h2></div>
                <div style="padding:16px">
                    <?php if ( $ticket->reporter_name ) : ?>
                        <div style="margin-bottom:10px"><div style="font-size:11px;color:var(--sp-muted);text-transform:uppercase;letter-spacing:.04em">Name</div><div style="font-weight:600"><?php echo esc_html( $ticket->reporter_name ); ?></div></div>
                    <?php endif; ?>
                    <?php if ( $ticket->reporter_phone ) : ?>
                        <div style="margin-bottom:10px"><div style="font-size:11px;color:var(--sp-muted);text-transform:uppercase;letter-spacing:.04em">Phone</div><div><?php echo esc_html( $ticket->reporter_phone ); ?></div></div>
                    <?php endif; ?>
                    <?php if ( $ticket->reporter_email ) : ?>
                        <div style="margin-bottom:10px"><div style="font-size:11px;color:var(--sp-muted);text-transform:uppercase;letter-spacing:.04em">Email</div><div><?php echo esc_html( $ticket->reporter_email ); ?></div></div>
                    <?php endif; ?>
                    <?php if ( ! $ticket->reporter_name && ! $ticket->reporter_phone && ! $ticket->reporter_email ) : ?>
                        <p class="sp-empty" style="margin:0">Anonymous submission</p>
                    <?php endif; ?>
                    <?php if ( $ticket->acknowledged_at ) : ?>
                        <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--sp-border)">
                            <div style="font-size:11px;color:var(--sp-muted);text-transform:uppercase;letter-spacing:.04em">Acknowledged By</div>
                            <div style="font-weight:600"><?php echo esc_html( $ticket->acknowledged_by ); ?></div>
                            <div style="font-size:12px;color:var(--sp-muted)"><?php echo esc_html( date( 'M j Y, g:ia', strtotime( $ticket->acknowledged_at ) ) ); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ( ! empty( $notif_log ) ) : ?>
            <div class="sp-card" style="margin-top:12px">
                <div class="sp-card-header"><h2>Notification Log</h2></div>
                <table class="sp-table" style="font-size:12px">
                    <thead><tr><th>To</th><th>Via</th><th>Status</th><th>Time</th></tr></thead>
                    <tbody>
                    <?php foreach ( $notif_log as $n ) : ?>
                    <tr>
                        <td><?php echo esc_html( $n->recipient_name ); ?><br><span style="color:var(--sp-muted)"><?php echo esc_html( $n->dept_name ); ?></span></td>
                        <td><?php echo esc_html( strtoupper( $n->channel ) ); ?></td>
                        <td><span class="sp-badge sp-badge-<?php echo $n->status === 'sent' ? 'open' : 'closed'; ?>"><?php echo esc_html( $n->status ); ?></span></td>
                        <td class="sp-muted"><?php echo esc_html( date( 'M j g:ia', strtotime( $n->sent_at ) ) ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php return;
}

// ── New Ticket Form ────────────────────────────────────────────────────────────
if ( $action === 'new' ) {
    ?>
    <div class="sp-page-header">
        <h1>New Service Ticket</h1>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=city-tickets' ) ); ?>" class="sp-btn sp-btn-ghost">&larr; Back</a>
    </div>
    <div class="sp-card sp-form-card">
        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="city_ticket">
            <input type="hidden" name="sp_id" value="0">
            <input type="hidden" name="source" value="staff">

            <div class="sp-field">
                <label>Departments <span style="color:#ef4444">*</span></label>
                <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:4px">
                <?php foreach ( $depts as $d ) : ?>
                    <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px;font-weight:500">
                        <input type="checkbox" name="dept_ids[]" value="<?php echo esc_attr( $d->id ); ?>">
                        <span style="display:inline-block;width:9px;height:9px;background:<?php echo esc_attr( $d->color ); ?>;border-radius:50%"></span>
                        <?php echo esc_html( $d->name ); ?>
                    </label>
                <?php endforeach; ?>
                </div>
            </div>

            <div class="sp-form-row">
                <div class="sp-field">
                    <label>Priority</label>
                    <select name="priority">
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="emergency">Emergency</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="sp-field">
                    <label>Status</label>
                    <select name="status">
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                    </select>
                </div>
            </div>

            <div class="sp-field"><label>Address / Location <span style="color:#ef4444">*</span></label><input type="text" name="address" required placeholder="123 Main Street"></div>
            <div class="sp-field"><label>Description <span style="color:#ef4444">*</span></label><textarea name="description" rows="5" required></textarea></div>

            <h3 style="font-size:13px;font-weight:600;margin:20px 0 8px;color:var(--sp-muted)">Reporter (optional)</h3>
            <div class="sp-form-row">
                <div class="sp-field"><label>Name</label><input type="text" name="reporter_name"></div>
                <div class="sp-field"><label>Phone</label><input type="tel" name="reporter_phone"></div>
            </div>
            <div class="sp-field" style="max-width:320px"><label>Email</label><input type="email" name="reporter_email"></div>

            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary">Create Ticket &amp; Notify On-Call</button>
            </div>
        </form>
    </div>
    <?php return;
}

// ── List ──────────────────────────────────────────────────────────────────────
$filter_status   = sanitize_key( isset( $_GET['status'] )   ? $_GET['status']   : '' );
$filter_priority = sanitize_key( isset( $_GET['priority'] ) ? $_GET['priority'] : '' );
$filter_dept     = (int) ( isset( $_GET['dept_id'] ) ? $_GET['dept_id'] : 0 );
$search          = sanitize_text_field( isset( $_GET['s'] ) ? $_GET['s'] : '' );
$paged           = max( 1, (int) ( isset( $_GET['paged'] ) ? $_GET['paged'] : 1 ) );
$limit           = 25;
$offset          = ( $paged - 1 ) * $limit;

$join  = '';
$where = array( '1=1' );
$params = array();

if ( $filter_dept ) {
    $join      = "INNER JOIN {$wpdb->prefix}sp_city_ticket_depts td2 ON td2.ticket_id = t.id AND td2.dept_id = %d";
    $params[]  = $filter_dept;
}
if ( $filter_status )   { $where[] = 't.status = %s';   $params[] = $filter_status; }
if ( $filter_priority ) { $where[] = 't.priority = %s'; $params[] = $filter_priority; }
if ( $search ) {
    $like     = '%' . $wpdb->esc_like( $search ) . '%';
    $where[]  = '(t.ticket_number LIKE %s OR t.address LIKE %s OR t.reporter_name LIKE %s)';
    $params[] = $like; $params[] = $like; $params[] = $like;
}

$where_sql = implode( ' AND ', $where );
$base_sql  = "FROM {$wpdb->prefix}sp_city_tickets t $join
              LEFT JOIN {$wpdb->prefix}sp_city_ticket_depts tdx ON tdx.ticket_id = t.id
              LEFT JOIN {$wpdb->prefix}sp_city_departments d ON d.id = tdx.dept_id
              WHERE $where_sql GROUP BY t.id";

if ( $params ) {
    $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT t.id) FROM {$wpdb->prefix}sp_city_tickets t $join WHERE $where_sql", $params ) );
    $rows  = $wpdb->get_results( $wpdb->prepare(
        "SELECT t.*, GROUP_CONCAT(d.name ORDER BY d.sort_order SEPARATOR ', ') AS depts $base_sql
         ORDER BY FIELD(t.priority,'emergency','high','normal','low'), t.created_at DESC
         LIMIT %d OFFSET %d",
        array_merge( $params, array( $limit, $offset ) )
    ) );
} else {
    $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_city_tickets" );
    $rows  = $wpdb->get_results( $wpdb->prepare(
        "SELECT t.*, GROUP_CONCAT(d.name ORDER BY d.sort_order SEPARATOR ', ') AS depts $base_sql
         ORDER BY FIELD(t.priority,'emergency','high','normal','low'), t.created_at DESC
         LIMIT %d OFFSET %d",
        $limit, $offset
    ) );
}
?>
<div class="sp-page-header">
    <h1>Service Tickets <span class="sp-count"><?php echo number_format( $total ); ?></span></h1>
    <div class="sp-header-actions">
        <form method="get" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="sp-search-form">
            <input type="hidden" name="view" value="city-tickets">
            <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search ticket # or address...">
            <select name="dept_id" onchange="this.form.submit()">
                <option value="">All Departments</option>
                <?php foreach ( $depts as $d ) : ?>
                    <option value="<?php echo $d->id; ?>" <?php selected( $filter_dept, $d->id ); ?>><?php echo esc_html( $d->name ); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach ( array( 'open', 'acknowledged', 'in_progress', 'resolved', 'closed' ) as $s ) : ?>
                    <option value="<?php echo $s; ?>" <?php selected( $filter_status, $s ); ?>><?php echo ucwords( str_replace( '_', ' ', $s ) ); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="priority" onchange="this.form.submit()">
                <option value="">All Priorities</option>
                <?php foreach ( array( 'emergency', 'high', 'normal', 'low' ) as $p ) : ?>
                    <option value="<?php echo $p; ?>" <?php selected( $filter_priority, $p ); ?>><?php echo ucfirst( $p ); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=city-tickets&action=new' ) ); ?>" class="sp-btn sp-btn-primary">+ New Ticket</a>
    </div>
</div>

<div class="sp-card sp-table-card">
    <table class="sp-table">
        <thead>
            <tr><th>Ticket #</th><th>Address</th><th>Departments</th><th>Priority</th><th>Status</th><th>Reporter</th><th>Submitted</th><th></th></tr>
        </thead>
        <tbody>
        <?php if ( empty( $rows ) ) : ?>
            <tr><td colspan="8" class="sp-empty">No tickets found.</td></tr>
        <?php else : foreach ( $rows as $row ) :
            $priority_colors = array( 'emergency' => '#DC2626', 'high' => '#EA580C', 'normal' => '#2563EB', 'low' => '#6B7280' );
            $pcolor = isset( $priority_colors[ $row->priority ] ) ? $priority_colors[ $row->priority ] : '#6B7280';
        ?>
            <tr>
                <td><a href="<?php echo esc_url( home_url( '/sp-app/?view=city-tickets&action=edit&id=' . $row->id ) ); ?>" class="sp-link"><?php echo esc_html( $row->ticket_number ); ?></a></td>
                <td><?php echo esc_html( $row->address ); ?></td>
                <td class="sp-muted" style="font-size:12px"><?php echo esc_html( $row->depts ?: '—' ); ?></td>
                <td><span class="sp-badge sp-badge-priority-<?php echo esc_attr( $row->priority ); ?>"><?php echo esc_html( ucfirst( $row->priority ) ); ?></span></td>
                <td><span class="sp-badge sp-badge-<?php echo esc_attr( $row->status ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $row->status ) ) ); ?></span></td>
                <td class="sp-muted"><?php echo esc_html( $row->reporter_name ?: 'Anonymous' ); ?></td>
                <td class="sp-muted"><?php echo esc_html( date( 'M j, g:ia', strtotime( $row->created_at ) ) ); ?></td>
                <td class="sp-actions">
                    <a href="<?php echo esc_url( home_url( '/sp-app/?view=city-tickets&action=edit&id=' . $row->id ) ); ?>">View</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php if ( function_exists( 'sp_pagination' ) ) sp_pagination( $total, $limit, $paged, 'city-tickets' ); ?>
</div>
