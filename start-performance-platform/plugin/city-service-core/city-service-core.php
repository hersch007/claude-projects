<?php
/*
 * Plugin Name: Start Performance -- City Service Core
 * Description: Multi-department public ticket system with on-call scheduling and SMS/email notifications for city governments.
 * Version:     1.1.0
 * Author:      Richard Brashear / Start Performance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SP_CITY_VERSION',    '1.1.0' );

// Remove the SP auth gate on this instance entirely — all pages are public.
add_filter( 'pre_option_sp_public_frontend', '__return_true' );
define( 'SP_CITY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// -- Settings REST endpoint ----------------------------------------------------
add_action( 'rest_api_init', function() {
    register_rest_route( 'sp-city/v1', '/settings', array(
        'methods'             => 'GET',
        'callback'            => 'sp_city_rest_settings',
        'permission_callback' => function() { return current_user_can( 'manage_options' ); },
    ) );
} );

function sp_city_rest_settings( $req ) {
    $writeable = array(
        'city_name', 'supervisor_email', 'supervisor_phone',
        'escalate_minutes', 'accent_color',
        'twilio_sid', 'twilio_token', 'twilio_from',
    );
    $updated = array();
    foreach ( $writeable as $key ) {
        if ( $req->get_param( $key ) !== null ) {
            update_option( 'sp_city_' . $key, sanitize_text_field( $req->get_param( $key ) ) );
            $updated[ $key ] = get_option( 'sp_city_' . $key );
        }
    }
    $current = array();
    foreach ( $writeable as $key ) {
        $current[ $key ] = get_option( 'sp_city_' . $key, '' );
    }
    return array( 'updated' => $updated, 'current' => $current );
}

require_once SP_CITY_PLUGIN_DIR . 'includes/db.php';
require_once SP_CITY_PLUGIN_DIR . 'includes/oncall-helpers.php';
require_once SP_CITY_PLUGIN_DIR . 'includes/notifications.php';

// -- Boot ----------------------------------------------------------------------

add_action( 'plugins_loaded', 'sp_city_boot', 20 );

function sp_city_boot() {
    if ( ! function_exists( 'sp_register_view' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Start Performance -- City Service Core</strong> requires the Start Performance core plugin.</p></div>';
        } );
        return;
    }
    sp_city_register();
}

// -- Activation ----------------------------------------------------------------

register_activation_hook( __FILE__, 'sp_city_activate' );
add_action( 'sp_activate', 'sp_city_create_tables' );

function sp_city_activate() {
    sp_city_create_tables();
}

// -- Registration --------------------------------------------------------------

function sp_city_register() {
    sp_register_addon( 'city-service', array(
        'name'        => 'City Service',
        'version'     => SP_CITY_VERSION,
        'description' => 'Multi-department public ticket system with on-call scheduling, SMS/email notifications, and on-duty dashboard for city service operations.',
        'icon'        => '<path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9"/>',
        'plugin_file' => plugin_basename( __FILE__ ),
    ) );

    sp_register_view( 'city-tickets',  SP_CITY_PLUGIN_DIR . 'templates/views/city-tickets.php' );
    sp_register_view( 'city-oncall',   SP_CITY_PLUGIN_DIR . 'templates/views/city-oncall.php' );
    sp_register_view( 'city-onduty',   SP_CITY_PLUGIN_DIR . 'templates/views/city-onduty.php' );

    add_filter( 'sp_nav_items',     'sp_city_nav_items' );
    add_filter( 'sp_allowed_views', 'sp_city_allowed_views' );

    add_action( 'sp_post_handler_city_ticket',       'sp_city_save_ticket' );
    add_action( 'sp_post_handler_city_ticket_ack',   'sp_city_acknowledge_ticket' );
    add_action( 'sp_post_handler_city_ticket_note',  'sp_city_save_note' );
    add_action( 'sp_post_handler_city_oncall',       'sp_city_save_oncall' );
    add_action( 'sp_post_handler_city_dept',         'sp_city_save_dept' );
    add_action( 'sp_post_handler_city_config',       'sp_city_save_config' );
    add_action( 'sp_delete_handler_city_ticket',     'sp_city_delete_ticket' );
    add_action( 'sp_delete_handler_city_oncall',     'sp_city_delete_oncall' );

    add_action( 'sp_dashboard_after_stats', 'sp_city_dashboard_stats' );
    add_action( 'sp_dashboard_after_grid',  'sp_city_dashboard_grid' );
    add_action( 'sp_settings_sections',     'sp_city_settings_section' );

    // Public shortcode: [city_service_form]
    add_shortcode( 'city_service_form', 'sp_city_public_form_shortcode' );
    add_action( 'admin_post_nopriv_sp_city_submit', 'sp_city_handle_public_submit' );
    add_action( 'admin_post_sp_city_submit',        'sp_city_handle_public_submit' );

    // Standalone page template for /service-request/
    add_filter( 'template_include', 'sp_city_service_request_template' );
}

function sp_city_service_request_template( $template ) {
    $uri   = trim( parse_url( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH ), '/' );
    $parts = explode( '/', $uri );
    if ( in_array( 'service-request', $parts, true ) ) {
        return SP_CITY_PLUGIN_DIR . 'templates/public/service-request.php';
    }
    return $template;
}

function sp_city_bypass_gate() {
    $uri   = trim( parse_url( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH ), '/' );
    $parts = explode( '/', $uri );
    if ( in_array( 'service-request', $parts, true ) ) {
        // pre_option fires before the DB read — guaranteed to intercept
        add_filter( 'pre_option_sp_public_frontend', '__return_true' );
    }
}

// -- Nav -----------------------------------------------------------------------

function sp_city_nav_items( $items ) {
    $section_added = false;
    $result        = array();
    foreach ( $items as $item ) {
        $result[] = $item;
    }

    $result[] = array(
        'section'    => true,
        'section_id' => 'city-service',
        'label'      => 'City Service',
        'icon'       => '<path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9"/>',
    );
    $result[] = array(
        'view'  => 'city-tickets',
        'label' => 'Service Tickets',
        'icon'  => '<path d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>',
    );
    $result[] = array(
        'view'  => 'city-onduty',
        'label' => 'On-Duty Board',
        'icon'  => '<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    );
    $result[] = array(
        'view'  => 'city-oncall',
        'label' => 'On-Call Schedule',
        'icon'  => '<path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
    );

    return $result;
}

function sp_city_allowed_views( $views ) {
    $views[] = 'city-tickets';
    $views[] = 'city-oncall';
    $views[] = 'city-onduty';
    return $views;
}

// -- Ticket save ---------------------------------------------------------------

function sp_city_save_ticket( $id ) {
    global $wpdb;

    $priority = sanitize_key( isset( $_POST['priority'] ) ? $_POST['priority'] : 'normal' );
    $status   = sanitize_key( isset( $_POST['status'] )   ? $_POST['status']   : 'open' );

    $data = array(
        'source'         => sanitize_key(            isset( $_POST['source'] )          ? $_POST['source']          : 'staff' ),
        'reporter_name'  => sanitize_text_field(     isset( $_POST['reporter_name'] )   ? $_POST['reporter_name']   : '' ),
        'reporter_email' => sanitize_email(          isset( $_POST['reporter_email'] )  ? $_POST['reporter_email']  : '' ),
        'reporter_phone' => sanitize_text_field(     isset( $_POST['reporter_phone'] )  ? $_POST['reporter_phone']  : '' ),
        'address'        => sanitize_textarea_field( isset( $_POST['address'] )         ? $_POST['address']         : '' ),
        'description'    => sanitize_textarea_field( isset( $_POST['description'] )     ? $_POST['description']     : '' ),
        'priority'       => $priority,
        'status'         => $status,
        'updated_at'     => current_time( 'mysql' ),
    );

    if ( $id ) {
        $wpdb->update( $wpdb->prefix . 'sp_city_tickets', $data, array( 'id' => $id ) );

        // Sync dept rows if provided
        $dept_ids = isset( $_POST['dept_ids'] ) && is_array( $_POST['dept_ids'] )
            ? array_map( 'intval', $_POST['dept_ids'] ) : array();
        if ( $dept_ids ) {
            // Remove unchecked, add new
            $existing = $wpdb->get_col( $wpdb->prepare(
                "SELECT dept_id FROM {$wpdb->prefix}sp_city_ticket_depts WHERE ticket_id = %d", $id
            ) );
            foreach ( array_diff( $existing, $dept_ids ) as $remove ) {
                $wpdb->delete( $wpdb->prefix . 'sp_city_ticket_depts', array( 'ticket_id' => $id, 'dept_id' => $remove ) );
            }
            foreach ( array_diff( $dept_ids, $existing ) as $add ) {
                $wpdb->insert( $wpdb->prefix . 'sp_city_ticket_depts', array(
                    'ticket_id' => $id,
                    'dept_id'   => $add,
                    'status'    => 'open',
                ) );
            }
        }
        wp_redirect( home_url( '/sp-app/?view=city-tickets&action=edit&id=' . $id . '&saved=1' ) ); exit;
    }

    // New ticket
    $data['created_at'] = current_time( 'mysql' );
    $wpdb->insert( $wpdb->prefix . 'sp_city_tickets', $data );
    $new_id = (int) $wpdb->insert_id;

    // Generate ticket number
    $ticket_number = sp_city_generate_ticket_number( $new_id );
    $wpdb->update( $wpdb->prefix . 'sp_city_tickets', array( 'ticket_number' => $ticket_number ), array( 'id' => $new_id ) );

    // Link departments
    $dept_ids = isset( $_POST['dept_ids'] ) && is_array( $_POST['dept_ids'] )
        ? array_map( 'intval', $_POST['dept_ids'] ) : array();
    foreach ( $dept_ids as $dept_id ) {
        $wpdb->insert( $wpdb->prefix . 'sp_city_ticket_depts', array(
            'ticket_id' => $new_id,
            'dept_id'   => $dept_id,
            'status'    => 'open',
        ) );
    }

    // Notify on-call staff
    if ( $dept_ids ) {
        $ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_city_tickets WHERE id = %d", $new_id ) );
        sp_city_notify_oncall( $new_id, $dept_ids );
    }

    wp_redirect( home_url( '/sp-app/?view=city-tickets&action=edit&id=' . $new_id . '&saved=1' ) ); exit;
}

// -- Acknowledge ticket (dept-level) -------------------------------------------

function sp_city_acknowledge_ticket( $id ) {
    global $wpdb;
    $ticket_id = (int) ( isset( $_POST['ticket_id'] ) ? $_POST['ticket_id'] : 0 );
    $dept_id   = (int) ( isset( $_POST['dept_id'] )   ? $_POST['dept_id']   : 0 );
    $member    = function_exists( 'sp_get_current_team_member' ) ? sp_get_current_team_member() : null;
    $by        = $member ? $member->name : 'Staff';
    $now       = current_time( 'mysql' );

    if ( $dept_id ) {
        $wpdb->update( $wpdb->prefix . 'sp_city_ticket_depts', array(
            'status'          => 'acknowledged',
            'acknowledged_by' => $by,
            'acknowledged_at' => $now,
        ), array( 'ticket_id' => $ticket_id, 'dept_id' => $dept_id ) );
    }

    // If all depts acknowledged, mark ticket acknowledged
    $open_depts = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}sp_city_ticket_depts WHERE ticket_id = %d AND status = 'open'",
        $ticket_id
    ) );
    if ( $open_depts === 0 ) {
        $wpdb->update( $wpdb->prefix . 'sp_city_tickets', array(
            'acknowledged_at' => $now,
            'acknowledged_by' => $by,
            'status'          => 'in_progress',
            'updated_at'      => $now,
        ), array( 'id' => $ticket_id ) );
    }

    // Add auto-note
    $dept = $wpdb->get_row( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}sp_city_departments WHERE id = %d", $dept_id ) );
    $wpdb->insert( $wpdb->prefix . 'sp_city_ticket_notes', array(
        'ticket_id'  => $ticket_id,
        'note'       => $by . ' acknowledged ' . ( $dept ? $dept->name : 'department' ),
        'created_by' => $by,
        'created_at' => $now,
        'is_public'  => 0,
    ) );

    wp_redirect( home_url( '/sp-app/?view=city-tickets&action=edit&id=' . $ticket_id . '&acked=1' ) ); exit;
}

// -- Notes ---------------------------------------------------------------------

function sp_city_save_note( $id ) {
    global $wpdb;
    $ticket_id = (int) ( isset( $_POST['ticket_id'] ) ? $_POST['ticket_id'] : 0 );
    $note      = sanitize_textarea_field( isset( $_POST['note'] ) ? $_POST['note'] : '' );
    $is_public = isset( $_POST['is_public'] ) ? 1 : 0;
    $member    = function_exists( 'sp_get_current_team_member' ) ? sp_get_current_team_member() : null;
    $by        = $member ? $member->name : 'Staff';

    if ( $ticket_id && $note ) {
        $wpdb->insert( $wpdb->prefix . 'sp_city_ticket_notes', array(
            'ticket_id'  => $ticket_id,
            'note'       => $note,
            'created_by' => $by,
            'created_at' => current_time( 'mysql' ),
            'is_public'  => $is_public,
        ) );

        // Also update ticket status if provided
        $new_status = sanitize_key( isset( $_POST['status'] ) ? $_POST['status'] : '' );
        if ( $new_status ) {
            $upd = array( 'status' => $new_status, 'updated_at' => current_time( 'mysql' ) );
            if ( $new_status === 'resolved' ) {
                $upd['resolved_at'] = current_time( 'mysql' );
            }
            $wpdb->update( $wpdb->prefix . 'sp_city_tickets', $upd, array( 'id' => $ticket_id ) );
        }
    }
    wp_redirect( home_url( '/sp-app/?view=city-tickets&action=edit&id=' . $ticket_id . '&noted=1' ) ); exit;
}

// -- On-call save --------------------------------------------------------------

function sp_city_save_oncall( $id ) {
    global $wpdb;

    $data = array(
        'team_member_id' => (int)                    ( isset( $_POST['team_member_id'] ) ? $_POST['team_member_id'] : 0 ),
        'dept_id'        => (int)                    ( isset( $_POST['dept_id'] )        ? $_POST['dept_id']        : 0 ),
        'start_datetime' => sanitize_text_field(     isset( $_POST['start_datetime'] )   ? $_POST['start_datetime'] : '' ),
        'end_datetime'   => sanitize_text_field(     isset( $_POST['end_datetime'] )     ? $_POST['end_datetime']   : '' ),
        'phone'          => sanitize_text_field(     isset( $_POST['phone'] )            ? $_POST['phone']          : '' ),
        'email'          => sanitize_email(          isset( $_POST['email'] )            ? $_POST['email']          : '' ),
        'notes'          => sanitize_text_field(     isset( $_POST['notes'] )            ? $_POST['notes']          : '' ),
    );

    if ( $id ) {
        $wpdb->update( $wpdb->prefix . 'sp_city_oncall', $data, array( 'id' => $id ) );
    } else {
        $wpdb->insert( $wpdb->prefix . 'sp_city_oncall', $data );
    }
    wp_redirect( home_url( '/sp-app/?view=city-oncall&saved=1' ) ); exit;
}

// -- Department save -----------------------------------------------------------

function sp_city_save_dept( $id ) {
    if ( ! sp_is_admin_member() ) {
        wp_redirect( home_url( '/sp-app/?view=settings' ) ); exit;
    }
    global $wpdb;
    $data = array(
        'name'       => sanitize_text_field( isset( $_POST['dept_name'] )  ? $_POST['dept_name']  : '' ),
        'color'      => sanitize_hex_color(  isset( $_POST['dept_color'] ) ? $_POST['dept_color'] : '#3B82F6' ),
        'active'     => isset( $_POST['dept_active'] ) ? 1 : 0,
        'sort_order' => (int) ( isset( $_POST['dept_sort'] ) ? $_POST['dept_sort'] : 0 ),
    );
    if ( $id ) {
        $wpdb->update( $wpdb->prefix . 'sp_city_departments', $data, array( 'id' => $id ) );
    } else {
        $slug = sanitize_key( isset( $_POST['dept_name'] ) ? $_POST['dept_name'] : 'dept' );
        $data['slug'] = $slug;
        $wpdb->insert( $wpdb->prefix . 'sp_city_departments', $data );
    }
    wp_redirect( home_url( '/sp-app/?view=settings&saved=1' ) ); exit;
}

// -- Config save ---------------------------------------------------------------

function sp_city_save_config( $id ) {
    if ( ! sp_is_admin_member() ) {
        wp_redirect( home_url( '/sp-app/?view=settings' ) ); exit;
    }
    $fields = array(
        'sp_city_name'              => 'sanitize_text_field',
        'sp_city_supervisor_email'  => 'sanitize_email',
        'sp_city_supervisor_phone'  => 'sanitize_text_field',
        'sp_city_twilio_sid'        => 'sanitize_text_field',
        'sp_city_twilio_token'      => 'sanitize_text_field',
        'sp_city_twilio_from'       => 'sanitize_text_field',
        'sp_city_escalate_minutes'  => 'intval',
    );
    foreach ( $fields as $key => $fn ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_option( $key, call_user_func( $fn, $_POST[ $key ] ) );
        }
    }
    wp_redirect( home_url( '/sp-app/?view=settings&saved=1' ) ); exit;
}

// -- Delete handlers -----------------------------------------------------------

function sp_city_delete_ticket( $id ) {
    global $wpdb;
    $wpdb->delete( $wpdb->prefix . 'sp_city_tickets',          array( 'id'        => $id ) );
    $wpdb->delete( $wpdb->prefix . 'sp_city_ticket_depts',     array( 'ticket_id' => $id ) );
    $wpdb->delete( $wpdb->prefix . 'sp_city_ticket_notes',     array( 'ticket_id' => $id ) );
    $wpdb->delete( $wpdb->prefix . 'sp_city_notification_log', array( 'ticket_id' => $id ) );
    wp_redirect( home_url( '/sp-app/?view=city-tickets' ) ); exit;
}

function sp_city_delete_oncall( $id ) {
    global $wpdb;
    $wpdb->delete( $wpdb->prefix . 'sp_city_oncall', array( 'id' => $id ) );
    wp_redirect( home_url( '/sp-app/?view=city-oncall' ) ); exit;
}

// -- Dashboard hooks -----------------------------------------------------------

function sp_city_dashboard_stats() {
    global $wpdb;
    $open      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_city_tickets WHERE status IN ('open','in_progress')" );
    $emergency = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_city_tickets WHERE priority = 'emergency' AND status NOT IN ('resolved','closed')" );
    $oncall    = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}sp_city_oncall WHERE start_datetime <= %s AND end_datetime >= %s",
        current_time( 'mysql' ), current_time( 'mysql' )
    ) );
    ?>
    <div class="sp-stats sp-stats-3">
        <div class="sp-stat-card">
            <div class="sp-stat-value"><?php echo number_format( $open ); ?></div>
            <div class="sp-stat-label">Open Tickets</div>
        </div>
        <div class="sp-stat-card">
            <div class="sp-stat-value" style="color:var(--sp-danger)"><?php echo number_format( $emergency ); ?></div>
            <div class="sp-stat-label">Emergency</div>
        </div>
        <div class="sp-stat-card">
            <div class="sp-stat-value" style="color:var(--sp-success)"><?php echo number_format( $oncall ); ?></div>
            <div class="sp-stat-label">On Duty Now</div>
        </div>
    </div>
    <?php
}

function sp_city_dashboard_grid() {
    global $wpdb;
    $now     = current_time( 'mysql' );
    $tickets = $wpdb->get_results( $wpdb->prepare(
        "SELECT t.*, GROUP_CONCAT(d.name ORDER BY d.sort_order SEPARATOR ', ') AS depts
         FROM {$wpdb->prefix}sp_city_tickets t
         LEFT JOIN {$wpdb->prefix}sp_city_ticket_depts td ON td.ticket_id = t.id
         LEFT JOIN {$wpdb->prefix}sp_city_departments d ON d.id = td.dept_id
         WHERE t.status NOT IN ('resolved','closed')
         GROUP BY t.id
         ORDER BY FIELD(t.priority,'emergency','high','normal','low'), t.created_at DESC
         LIMIT 6"
    ) );
    $shifts  = sp_city_get_oncall_now();
    ?>
    <div class="sp-card sp-table-card" style="margin-top:16px">
        <div class="sp-card-header">
            <h2>Open Service Tickets</h2>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=city-tickets&action=new' ) ); ?>" class="sp-btn sp-btn-primary sp-btn-sm">+ New</a>
        </div>
        <?php if ( empty( $tickets ) ) : ?>
            <p class="sp-empty">No open tickets.</p>
        <?php else : ?>
            <table class="sp-table">
                <thead><tr><th>Ticket #</th><th>Address</th><th>Departments</th><th>Priority</th><th>Status</th><th>Submitted</th></tr></thead>
                <tbody>
                <?php foreach ( $tickets as $t ) : ?>
                <tr>
                    <td><a href="<?php echo esc_url( home_url( '/sp-app/?view=city-tickets&action=edit&id=' . $t->id ) ); ?>" class="sp-link"><?php echo esc_html( $t->ticket_number ); ?></a></td>
                    <td class="sp-muted"><?php echo esc_html( $t->address ); ?></td>
                    <td class="sp-muted"><?php echo esc_html( $t->depts ?: '—' ); ?></td>
                    <td><span class="sp-badge sp-badge-priority-<?php echo esc_attr( $t->priority ); ?>"><?php echo esc_html( ucfirst( $t->priority ) ); ?></span></td>
                    <td><span class="sp-badge sp-badge-<?php echo esc_attr( $t->status ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $t->status ) ) ); ?></span></td>
                    <td class="sp-muted"><?php echo esc_html( date( 'M j g:ia', strtotime( $t->created_at ) ) ); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if ( ! empty( $shifts ) ) : ?>
    <div class="sp-card" style="margin-top:16px">
        <div class="sp-card-header"><h2>On Duty Right Now</h2><a href="<?php echo esc_url( home_url( '/sp-app/?view=city-onduty' ) ); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">Full Board</a></div>
        <div style="display:flex;flex-wrap:wrap;gap:10px;padding:12px 16px">
        <?php foreach ( $shifts as $s ) : ?>
            <div style="background:<?php echo esc_attr( $s->dept_color ); ?>22;border:1px solid <?php echo esc_attr( $s->dept_color ); ?>;border-radius:8px;padding:10px 14px;min-width:160px">
                <div style="font-size:11px;font-weight:700;color:<?php echo esc_attr( $s->dept_color ); ?>;text-transform:uppercase;letter-spacing:.05em"><?php echo esc_html( $s->dept_name ); ?></div>
                <div style="font-weight:600;margin-top:2px"><?php echo esc_html( $s->member_name ?: 'Unknown' ); ?></div>
                <?php if ( $s->phone ) : ?><div style="font-size:12px;color:var(--sp-muted)"><?php echo esc_html( $s->phone ); ?></div><?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif;
}

// -- Settings ------------------------------------------------------------------

function sp_city_settings_section() {
    global $wpdb;
    $depts = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sp_city_departments ORDER BY sort_order, name" );
    ?>
    <div class="sp-card sp-form-card" style="margin-top:24px">
        <h2 class="sp-section-heading">City Service Core</h2>
        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="city_config">
            <input type="hidden" name="sp_id" value="0">

            <div class="sp-form-row">
                <div class="sp-field">
                    <label>City Name</label>
                    <input type="text" name="sp_city_name" value="<?php echo esc_attr( get_option( 'sp_city_name', '' ) ); ?>" placeholder="City of Springfield">
                </div>
                <div class="sp-field">
                    <label>Escalation After (minutes)</label>
                    <input type="number" name="sp_city_escalate_minutes" value="<?php echo esc_attr( get_option( 'sp_city_escalate_minutes', 30 ) ); ?>" min="5" max="480">
                    <span class="sp-hint">Alert supervisor if ticket unacknowledged after this many minutes.</span>
                </div>
            </div>
            <div class="sp-form-row">
                <div class="sp-field">
                    <label>Supervisor Email</label>
                    <input type="email" name="sp_city_supervisor_email" value="<?php echo esc_attr( get_option( 'sp_city_supervisor_email', '' ) ); ?>">
                </div>
                <div class="sp-field">
                    <label>Supervisor Phone (SMS)</label>
                    <input type="text" name="sp_city_supervisor_phone" value="<?php echo esc_attr( get_option( 'sp_city_supervisor_phone', '' ) ); ?>" placeholder="+15555550100">
                </div>
            </div>

            <h3 style="font-size:14px;font-weight:600;margin:20px 0 8px">Twilio SMS Settings</h3>
            <div class="sp-form-row">
                <div class="sp-field">
                    <label>Account SID</label>
                    <input type="text" name="sp_city_twilio_sid" value="<?php echo esc_attr( get_option( 'sp_city_twilio_sid', '' ) ); ?>" placeholder="ACxxxxxxxx">
                </div>
                <div class="sp-field">
                    <label>Auth Token</label>
                    <input type="password" name="sp_city_twilio_token" value="<?php echo esc_attr( get_option( 'sp_city_twilio_token', '' ) ); ?>">
                </div>
            </div>
            <div class="sp-field" style="max-width:260px">
                <label>From Phone Number</label>
                <input type="text" name="sp_city_twilio_from" value="<?php echo esc_attr( get_option( 'sp_city_twilio_from', '' ) ); ?>" placeholder="+15555550000">
                <span class="sp-hint">Must be a Twilio-verified number in E.164 format.</span>
            </div>

            <div class="sp-form-actions"><button type="submit" class="sp-btn sp-btn-primary">Save City Settings</button></div>
        </form>
    </div>

    <!-- Departments -->
    <div class="sp-card" style="margin-top:16px">
        <div class="sp-card-header">
            <h2>Departments</h2>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=city-oncall&action=add-dept' ) ); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">+ Add</a>
        </div>
        <table class="sp-table">
            <thead><tr><th>Name</th><th>Color</th><th>Active</th><th></th></tr></thead>
            <tbody>
            <?php foreach ( $depts as $d ) : ?>
            <tr>
                <td><?php echo esc_html( $d->name ); ?></td>
                <td><span style="display:inline-block;width:16px;height:16px;background:<?php echo esc_attr( $d->color ); ?>;border-radius:3px;vertical-align:middle"></span> <?php echo esc_html( $d->color ); ?></td>
                <td><?php echo $d->active ? '<span class="sp-badge sp-badge-open">Active</span>' : '<span class="sp-badge">Inactive</span>'; ?></td>
                <td class="sp-actions"><a href="<?php echo esc_url( home_url( '/sp-app/?view=city-oncall&action=edit-dept&id=' . $d->id ) ); ?>">Edit</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// -- Public form shortcode -----------------------------------------------------

function sp_city_public_form_shortcode( $atts ) {
    $city_name = get_option( 'sp_city_name', get_bloginfo( 'name' ) );
    $depts     = sp_city_get_departments();

    // Show confirmation if just submitted
    if ( isset( $_GET['city_ticket'] ) ) {
        $num = sanitize_text_field( $_GET['city_ticket'] );
        ob_start(); ?>
        <div style="max-width:600px;margin:0 auto;padding:32px 24px;font-family:system-ui,sans-serif">
            <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:32px;text-align:center">
                <div style="font-size:48px;margin-bottom:12px">&#10003;</div>
                <h2 style="color:#166534;margin:0 0 8px">Request Submitted</h2>
                <p style="color:#15803d;margin:0 0 16px">Your service request has been received and our team has been notified.</p>
                <div style="background:#fff;border-radius:8px;padding:16px;display:inline-block">
                    <div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.05em">Your Ticket Number</div>
                    <div style="font-size:28px;font-weight:700;color:#111827;letter-spacing:.05em"><?php echo esc_html( $num ); ?></div>
                </div>
                <p style="color:#4b5563;margin:16px 0 0;font-size:14px">Save this number to reference your request. A confirmation email has been sent if you provided one.</p>
            </div>
        </div>
        <?php return ob_get_clean();
    }

    ob_start();
    $action_url = admin_url( 'admin-post.php' );
    $return_url = get_permalink();
    ?>
    <div style="max-width:620px;margin:0 auto;font-family:system-ui,sans-serif">
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08)">
            <div style="background:var(--sp-accent,#1e3a5f);padding:24px 28px">
                <h2 style="color:#fff;margin:0;font-size:20px;font-weight:700"><?php echo esc_html( $city_name ); ?> — Service Request</h2>
                <p style="color:rgba(255,255,255,.75);margin:6px 0 0;font-size:14px">Report a utility or maintenance issue to the appropriate department.</p>
            </div>
            <form method="post" action="<?php echo esc_url( $action_url ); ?>" style="padding:28px">
                <?php wp_nonce_field( 'sp_city_public', 'sp_city_nonce' ); ?>
                <input type="hidden" name="action" value="sp_city_submit">
                <input type="hidden" name="return_url" value="<?php echo esc_url( $return_url ); ?>">

                <fieldset style="border:none;padding:0;margin:0 0 20px">
                    <legend style="font-weight:600;font-size:15px;margin-bottom:12px;color:#111827">Which department(s) does this involve?</legend>
                    <div style="display:flex;flex-wrap:wrap;gap:10px">
                    <?php foreach ( $depts as $d ) : ?>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;background:#f9fafb;border:1.5px solid #e5e7eb;border-radius:8px;padding:10px 14px;font-size:14px;font-weight:500">
                            <input type="checkbox" name="dept_ids[]" value="<?php echo esc_attr( $d->id ); ?>" style="width:16px;height:16px;accent-color:<?php echo esc_attr( $d->color ); ?>">
                            <span style="display:inline-block;width:10px;height:10px;background:<?php echo esc_attr( $d->color ); ?>;border-radius:50%"></span>
                            <?php echo esc_html( $d->name ); ?>
                        </label>
                    <?php endforeach; ?>
                    </div>
                </fieldset>

                <?php
                $field_style = 'display:flex;flex-direction:column;gap:5px;margin-bottom:16px';
                $label_style = 'font-size:13px;font-weight:600;color:#374151';
                $input_style = 'border:1.5px solid #d1d5db;border-radius:7px;padding:10px 12px;font-size:14px;width:100%;box-sizing:border-box';
                ?>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px">
                    <div style="<?php echo $field_style; ?>">
                        <label style="<?php echo $label_style; ?>">Priority</label>
                        <select name="priority" style="<?php echo $input_style; ?>">
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="emergency">Emergency / Urgent</option>
                        </select>
                    </div>
                </div>

                <div style="<?php echo $field_style; ?>">
                    <label style="<?php echo $label_style; ?>">Address or Location of Issue <span style="color:#ef4444">*</span></label>
                    <input type="text" name="address" required style="<?php echo $input_style; ?>" placeholder="123 Main Street">
                </div>

                <div style="<?php echo $field_style; ?>">
                    <label style="<?php echo $label_style; ?>">Description <span style="color:#ef4444">*</span></label>
                    <textarea name="description" required rows="4" style="<?php echo $input_style; ?>;resize:vertical" placeholder="Please describe the issue..."></textarea>
                </div>

                <div style="border-top:1px solid #f3f4f6;padding-top:16px;margin:20px 0 16px">
                    <p style="font-size:13px;font-weight:600;color:#374151;margin:0 0 12px">Your Contact Information (optional)</p>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                        <div style="<?php echo $field_style; ?> margin-bottom:0"><label style="<?php echo $label_style; ?>">Name</label><input type="text" name="reporter_name" style="<?php echo $input_style; ?>"></div>
                        <div style="<?php echo $field_style; ?> margin-bottom:0"><label style="<?php echo $label_style; ?>">Phone</label><input type="tel" name="reporter_phone" style="<?php echo $input_style; ?>"></div>
                    </div>
                    <div style="<?php echo $field_style; ?> margin-top:14px margin-bottom:0">
                        <label style="<?php echo $label_style; ?>">Email (for confirmation)</label>
                        <input type="email" name="reporter_email" style="<?php echo $input_style; ?>">
                    </div>
                </div>

                <button type="submit" style="background:var(--sp-accent,#1e3a5f);color:#fff;border:none;border-radius:8px;padding:13px 28px;font-size:15px;font-weight:600;cursor:pointer;width:100%;margin-top:8px">
                    Submit Service Request
                </button>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// -- Public form handler -------------------------------------------------------

function sp_city_handle_public_submit() {
    if ( ! isset( $_POST['sp_city_nonce'] ) || ! wp_verify_nonce( $_POST['sp_city_nonce'], 'sp_city_public' ) ) {
        wp_die( 'Security check failed. Please go back and try again.' );
    }
    global $wpdb;

    $priority = sanitize_key( isset( $_POST['priority'] ) ? $_POST['priority'] : 'normal' );
    $address  = sanitize_textarea_field( isset( $_POST['address'] )     ? $_POST['address']     : '' );
    $desc     = sanitize_textarea_field( isset( $_POST['description'] ) ? $_POST['description'] : '' );

    if ( ! $address || ! $desc ) {
        wp_die( 'Please fill in all required fields.' );
    }

    $data = array(
        'source'         => 'public',
        'reporter_name'  => sanitize_text_field( isset( $_POST['reporter_name'] )  ? $_POST['reporter_name']  : '' ),
        'reporter_email' => sanitize_email(      isset( $_POST['reporter_email'] ) ? $_POST['reporter_email'] : '' ),
        'reporter_phone' => sanitize_text_field( isset( $_POST['reporter_phone'] ) ? $_POST['reporter_phone'] : '' ),
        'address'        => $address,
        'description'    => $desc,
        'priority'       => $priority,
        'status'         => 'open',
        'created_at'     => current_time( 'mysql' ),
        'updated_at'     => current_time( 'mysql' ),
    );

    $wpdb->insert( $wpdb->prefix . 'sp_city_tickets', $data );
    $new_id = (int) $wpdb->insert_id;

    $ticket_number = sp_city_generate_ticket_number( $new_id );
    $wpdb->update( $wpdb->prefix . 'sp_city_tickets', array( 'ticket_number' => $ticket_number ), array( 'id' => $new_id ) );

    $dept_ids = isset( $_POST['dept_ids'] ) && is_array( $_POST['dept_ids'] )
        ? array_map( 'intval', $_POST['dept_ids'] ) : array();
    foreach ( $dept_ids as $dept_id ) {
        $wpdb->insert( $wpdb->prefix . 'sp_city_ticket_depts', array(
            'ticket_id' => $new_id,
            'dept_id'   => $dept_id,
            'status'    => 'open',
        ) );
    }

    $ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_city_tickets WHERE id = %d", $new_id ) );

    if ( $dept_ids ) {
        sp_city_notify_oncall( $new_id, $dept_ids );
    }
    if ( $ticket->reporter_email ) {
        sp_city_notify_reporter( $ticket );
    }

    $return = esc_url_raw( isset( $_POST['return_url'] ) ? $_POST['return_url'] : home_url() );
    wp_redirect( add_query_arg( 'city_ticket', $ticket_number, $return ) );
    exit;
}
