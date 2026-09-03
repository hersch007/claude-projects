<?php
/*
 * Plugin Name: Start Performance — Tickets
 * Description: Ticket management addon for the Start Performance Platform
 * Version:     1.2.1
 * Author:      Richard Brashear / Start Performance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SP_TICKETS_VERSION',    '1.2.1' );
define( 'SP_TICKETS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// â”€â”€ Dependency check â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Everything is registered inside this function so nothing runs if core is absent.

add_action( 'plugins_loaded', 'sp_tickets_boot', 20 );

function sp_tickets_boot() {
    if ( ! function_exists( 'sp_register_view' ) ) {
        add_action( 'admin_notices', 'sp_tickets_dependency_notice' );
        return;
    }
    sp_tickets_register();
}

function sp_tickets_dependency_notice() {
    echo '<div class="notice notice-error"><p><strong>Start Performance — Tickets</strong> requires the Start Performance core plugin to be installed and active.</p></div>';
}

// â”€â”€ Status / Priority helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function sp_tickets_get_statuses() {
    $saved = get_option( 'sp_ticket_statuses', '' );
    if ( $saved ) {
        $arr = array_values( array_filter( array_map( 'trim', explode( ',', $saved ) ) ) );
        if ( ! empty( $arr ) ) return $arr;
    }
    return array( 'open', 'in_progress', 'resolved', 'closed' );
}

function sp_tickets_get_priorities() {
    $saved = get_option( 'sp_ticket_priorities', '' );
    if ( $saved ) {
        $arr = array_values( array_filter( array_map( 'trim', explode( ',', $saved ) ) ) );
        if ( ! empty( $arr ) ) return $arr;
    }
    return array( 'low', 'normal', 'high', 'urgent' );
}

// â”€â”€ Registration â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function sp_tickets_register() {
    sp_register_addon( 'sp-tickets', array(
        'name'         => 'Tickets',
        'version'      => SP_TICKETS_VERSION,
        'description'  => 'Full ticket management — create, assign, prioritize, and resolve support or task tickets linked to contacts and companies.',
        'settings_url' => '',
        'icon'         => '<path d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>',
        'plugin_file'  => plugin_basename( __FILE__ ),
    ) );

    sp_register_view( 'tickets', SP_TICKETS_PLUGIN_DIR . 'templates/views/tickets.php' );

    add_filter( 'sp_nav_items',     'sp_tickets_nav_items' );
    add_filter( 'sp_allowed_views', 'sp_tickets_allowed_views' );

    add_action( 'sp_post_handler_ticket',        'sp_tickets_post_handler' );
    add_action( 'sp_post_handler_ticket_config', 'sp_tickets_save_config' );
    add_action( 'sp_delete_handler_ticket',      'sp_tickets_delete_handler' );

    add_action( 'sp_dashboard_after_stats', 'sp_tickets_dashboard_stats' );
    add_action( 'sp_dashboard_after_grid',  'sp_tickets_dashboard_grid' );
    add_action( 'sp_settings_sections',     'sp_tickets_settings_section' );
}

// â”€â”€ Activation â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

register_activation_hook( __FILE__, 'sp_tickets_activate' );

function sp_tickets_activate() {
    sp_tickets_create_table();
}

// Also hook into core's activation action in case both plugins are activated together
add_action( 'sp_activate', 'sp_tickets_create_table' );

function sp_tickets_create_table() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_tickets (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL DEFAULT '',
  description text,
  status varchar(50) NOT NULL DEFAULT 'open',
  priority varchar(20) NOT NULL DEFAULT 'normal',
  contact_id bigint(20) unsigned NOT NULL DEFAULT 0,
  company_id bigint(20) unsigned NOT NULL DEFAULT 0,
  assigned_to varchar(100) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY status (status),
  KEY priority (priority),
  KEY contact_id (contact_id)
) $charset;" );
}

// â”€â”€ Nav filter â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function sp_tickets_nav_items( $items ) {
    // Insert Tickets under the service-core section (defined in core nav)
    $result = array();
    foreach ( $items as $item ) {
        $result[] = $item;
        if ( ! empty( $item['section'] ) && ! empty( $item['section_id'] ) && $item['section_id'] === 'service-core' ) {
            $result[] = array(
                'view'  => 'tickets',
                'label' => 'Tickets',
                'icon'  => '<path d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>',
            );
        }
    }
    return $result;
}

// â”€â”€ Allowed views filter â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function sp_tickets_allowed_views( $views ) {
    $views[] = 'tickets';
    return $views;
}

// â”€â”€ POST handler â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function sp_tickets_post_handler( $id ) {
    global $wpdb;
    $data = array(
        'title'       => sanitize_text_field(     isset( $_POST['title'] )       ? $_POST['title']       : '' ),
        'description' => sanitize_textarea_field( isset( $_POST['description'] ) ? $_POST['description'] : '' ),
        'status'      => sanitize_key(            isset( $_POST['status'] )      ? $_POST['status']      : 'open' ),
        'priority'    => sanitize_key(            isset( $_POST['priority'] )    ? $_POST['priority']    : 'normal' ),
        'contact_id'  => (int) ( isset( $_POST['contact_id'] )  ? $_POST['contact_id']  : 0 ),
        'company_id'  => (int) ( isset( $_POST['company_id'] )  ? $_POST['company_id']  : 0 ),
        'assigned_to' => sanitize_text_field(     isset( $_POST['assigned_to'] ) ? $_POST['assigned_to'] : '' ),
        'updated_at'  => current_time( 'mysql' ),
    );
    if ( $id ) {
        $wpdb->update( $wpdb->prefix . 'sp_tickets', $data, array( 'id' => $id ) );
    } else {
        $data['created_at'] = current_time( 'mysql' );
        $wpdb->insert( $wpdb->prefix . 'sp_tickets', $data );
    }
    wp_redirect( home_url( '/sp-app/?view=tickets&saved=1' ) ); exit;
}

// â”€â”€ DELETE handler â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function sp_tickets_delete_handler( $id ) {
    global $wpdb;
    $wpdb->delete( $wpdb->prefix . 'sp_tickets', array( 'id' => $id ) );
    wp_redirect( home_url( '/sp-app/?view=tickets' ) ); exit;
}

// â”€â”€ Dashboard: stat cards â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function sp_tickets_dashboard_stats() {
    if ( sp_is_view_hidden( 'tickets' ) ) return;
    global $wpdb;
    $open   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tickets WHERE status IN ('open','in_progress')" );
    $urgent = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tickets WHERE priority = 'urgent' AND status IN ('open','in_progress')" );
    ?>
    <div class="sp-stats sp-stats-2">
        <div class="sp-stat-card sp-stat-tickets">
            <div class="sp-stat-value"><?php echo number_format( $open ); ?></div>
            <div class="sp-stat-label">Open Tickets</div>
        </div>
        <div class="sp-stat-card sp-stat-urgent">
            <div class="sp-stat-value"><?php echo number_format( $urgent ); ?></div>
            <div class="sp-stat-label">Urgent Tickets</div>
        </div>
    </div>
    <?php
}

// â”€â”€ Dashboard: open tickets table â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function sp_tickets_dashboard_grid() {
    if ( sp_is_view_hidden( 'tickets' ) ) return;
    global $wpdb;
    $rows = $wpdb->get_results(
        "SELECT t.*, c.first_name, c.last_name FROM {$wpdb->prefix}sp_tickets t
         LEFT JOIN {$wpdb->prefix}sp_contacts c ON c.id = t.contact_id
         WHERE t.status IN ('open','in_progress')
         ORDER BY FIELD(t.priority,'urgent','high','normal','low'), t.created_at DESC
         LIMIT 5"
    );
    ?>
    <div class="sp-card sp-table-card" style="margin-top:16px">
        <div class="sp-card-header">
            <h2>Open Tickets</h2>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=tickets&action=new' ) ); ?>" class="sp-btn sp-btn-primary sp-btn-sm">+ Add</a>
        </div>
        <?php if ( empty( $rows ) ) : ?>
            <p class="sp-empty">No open tickets.</p>
        <?php else : ?>
            <table class="sp-table">
                <thead><tr><th>Title</th><th>Contact</th><th>Priority</th><th>Status</th><th>Created</th></tr></thead>
                <tbody>
                <?php foreach ( $rows as $t ) : ?>
                    <tr>
                        <td><a href="<?php echo esc_url( home_url( '/sp-app/?view=tickets&action=edit&id=' . $t->id ) ); ?>" class="sp-link"><?php echo esc_html( $t->title ); ?></a></td>
                        <td class="sp-muted"><?php echo $t->first_name ? esc_html( trim( $t->first_name . ' ' . $t->last_name ) ) : '—'; ?></td>
                        <td><span class="sp-badge sp-badge-priority-<?php echo esc_attr( $t->priority ); ?>"><?php echo esc_html( ucfirst( $t->priority ) ); ?></span></td>
                        <td><span class="sp-badge sp-badge-<?php echo esc_attr( $t->status ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $t->status ) ) ); ?></span></td>
                        <td class="sp-muted"><?php echo esc_html( date( 'M j', strtotime( $t->created_at ) ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

// â”€â”€ Settings helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function sp_tickets_normalize_list( $raw ) {
    $parts  = explode( ',', $raw );
    $result = array();
    foreach ( $parts as $part ) {
        $v = strtolower( trim( $part ) );
        if ( $v !== '' ) $result[] = $v;
    }
    return implode( ',', $result );
}

// â”€â”€ Settings: save config â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function sp_tickets_save_config( $id ) {
    if ( ! sp_is_admin_member() ) {
        wp_redirect( home_url( '/sp-app/?view=settings' ) ); exit;
    }
    $raw_statuses   = sanitize_text_field( isset( $_POST['sp_ticket_statuses'] )   ? $_POST['sp_ticket_statuses']   : '' );
    $raw_priorities = sanitize_text_field( isset( $_POST['sp_ticket_priorities'] ) ? $_POST['sp_ticket_priorities'] : '' );

    // Normalize: lowercase, trim, remove empty
    $statuses   = sp_tickets_normalize_list( $raw_statuses );
    $priorities = sp_tickets_normalize_list( $raw_priorities );

    update_option( 'sp_ticket_statuses',   $statuses );
    update_option( 'sp_ticket_priorities', $priorities );

    wp_redirect( home_url( '/sp-app/?view=settings&saved=1' ) ); exit;
}

// â”€â”€ Settings: render card â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function sp_tickets_settings_section() {
    $statuses   = implode( ', ', sp_tickets_get_statuses() );
    $priorities = implode( ', ', sp_tickets_get_priorities() );
    ?>
    <div class="sp-card sp-form-card" style="margin-top:16px">
        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="ticket_config">
            <input type="hidden" name="sp_id" value="0">

            <h2 class="sp-section-heading">Ticket Settings</h2>

            <div class="sp-field">
                <label>Statuses</label>
                <input type="text" name="sp_ticket_statuses" value="<?php echo esc_attr( $statuses ); ?>">
                <span class="sp-hint">Comma-separated list, e.g. <em>open, in_progress, resolved, closed</em></span>
            </div>

            <div class="sp-field">
                <label>Priorities</label>
                <input type="text" name="sp_ticket_priorities" value="<?php echo esc_attr( $priorities ); ?>">
                <span class="sp-hint">Comma-separated list, e.g. <em>low, normal, high, urgent</em></span>
            </div>

            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary">Save Ticket Settings</button>
            </div>
        </form>
    </div>
    <?php
}
