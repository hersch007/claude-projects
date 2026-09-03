<?php
/*
 * Plugin Name: Start Performance -- Government Service Core
 * Description: Multi-department public service ticket system with on-call scheduling, SMS/email notifications, and on-duty dashboard for city and municipal governments.
 * Version:     1.2.34
 * Author:      Richard Brashear / Start Performance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'SP_CITY_VERSION' ) ) define( 'SP_CITY_VERSION', '1.2.34' );

// -- City role helpers ---------------------------------------------------------
// Roles:
//   'city_admin'        — full access (settings, branding, delete, assign anyone)
//   'city_supervisor'   — run queue (assign anyone, delete tickets), no settings
//   'city_employee'     — process/close tickets, assign to supervisors only, no delete
//   'call_center_admin' — create + edit tickets, no assign/delete/settings
//   'call_center'       — create tickets only
// Legacy 'city' treated as 'city_admin' for backwards compatibility.

function sp_city_get_member_role( $member_id ) {
    $roles = get_option( 'sp_city_member_roles', array() );
    $role  = isset( $roles[ $member_id ] ) ? $roles[ $member_id ] : 'city_admin';
    // Treat legacy 'city' as 'city_admin'.
    return $role === 'city' ? 'city_admin' : $role;
}

function sp_city_set_member_role( $member_id, $role ) {
    $roles = get_option( 'sp_city_member_roles', array() );
    $roles[ $member_id ] = $role;
    update_option( 'sp_city_member_roles', $roles );
}

function sp_city_current_member_role() {
    $member = function_exists( 'sp_get_current_team_member' ) ? sp_get_current_team_member() : null;
    if ( ! $member ) return 'city';
    return sp_city_get_member_role( $member->id );
}

function sp_city_is_call_center() {
    return sp_city_current_member_role() === 'call_center';
}

function sp_city_is_call_center_admin() {
    return sp_city_current_member_role() === 'call_center_admin';
}

function sp_city_is_city_admin() {
    return sp_city_current_member_role() === 'city_admin';
}

function sp_city_is_city_supervisor() {
    return sp_city_current_member_role() === 'city_supervisor';
}

function sp_city_is_city_employee() {
    return sp_city_current_member_role() === 'city_employee';
}

// Can manage settings and branding.
function sp_city_can_manage_settings() {
    return sp_city_is_city_admin() || sp_city_is_call_center_admin();
}

// Can delete tickets and on-call shifts.
function sp_city_can_delete() {
    return in_array( sp_city_current_member_role(), array( 'city_admin', 'city_supervisor' ), true );
}

// Can assign tickets (city_admin and city_supervisor see everyone; city_employee sees supervisors).
function sp_city_can_assign() {
    return in_array( sp_city_current_member_role(), array( 'city_admin', 'city_supervisor', 'city_employee' ), true );
}

// Call center roles — blocked from editing existing tickets and all assignment.
function sp_city_is_restricted() {
    return in_array( sp_city_current_member_role(), array( 'call_center', 'call_center_admin' ), true );
}

// Remove the SP auth gate on this instance entirely — all pages are public.
add_filter( 'pre_option_sp_public_frontend', '__return_true' );

// Register government-service as a core slot and strip irrelevant slots.
add_action( 'plugins_loaded', function() {
    if ( function_exists( 'sp_register_core_slot' ) ) {
        sp_register_core_slot( 'government-service', array(
            'label'   => 'Government Service',
            'tagline' => 'Multi-department public ticket and on-call management',
            'icon'    => '<path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9"/>',
        ) );
    }
    global $sp_core_slot_registry;
    foreach ( array( 'sales-core', 'service-core', 'operations-core', 'intelligence-core', 'knowledge-core', 'chat-core' ) as $slot ) {
        unset( $sp_core_slot_registry[ $slot ] );
    }
}, 50 );
if ( ! defined( 'SP_CITY_PLUGIN_DIR' ) ) define( 'SP_CITY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Override sidebar branding from saved city settings.
add_filter( 'pre_option_sp_platform_name',  function() {
    $name = get_option( 'sp_city_name', '' );
    return $name ? $name : 'City Core';
} );
add_filter( 'pre_option_sp_brand_icon_url', function() {
    $url = get_option( 'sp_city_logo_url', '' );
    return $url ?: false;
} );
add_filter( 'pre_option_sp_brand_initials', function() {
    $initials = get_option( 'sp_city_initials', '' );
    if ( $initials ) return $initials;
    $name = get_option( 'sp_city_name', 'City Core' );
    $words = explode( ' ', $name );
    return strtoupper( substr( $words[0], 0, 1 ) . ( isset( $words[1] ) ? substr( $words[1], 0, 1 ) : '' ) );
} );

// Hide CRM views from dashboard metrics — makes sp_is_view_hidden() return true
// so the dashboard PHP skips the Contacts/Companies/Tasks/Leads stat cards entirely.
add_filter( 'pre_option_sp_hidden_nav_items', function( $val ) {
    $hidden = is_array( $val ) ? $val : array();
    foreach ( array( 'contacts', 'companies', 'leads', 'tasks', 'sales-estimates' ) as $v ) {
        if ( ! in_array( $v, $hidden, true ) ) $hidden[] = $v;
    }
    return $hidden;
} );

// Inject CSS via sp_app_footer (wp_head doesn't fire in the SP app template).
add_action( 'sp_app_footer', function() {
    $primary   = get_option( 'sp_city_primary_color',   '#1e3a5f' );
    $secondary = get_option( 'sp_city_secondary_color', '#c8a84b' );
    // Derive a darker shade for hover states
    echo '<style>
        :root {
            --sp-accent:        ' . esc_attr( $primary ) . ';
            --sp-accent-dark:   ' . esc_attr( $primary ) . 'cc;
            --sp-accent-light:  ' . esc_attr( $primary ) . '11;
            --city-secondary:   ' . esc_attr( $secondary ) . ';
        }
        .sp-quick-actions{display:none!important}
        .sp-fab-wrap{display:none!important}
        .sp-dash-activity,.sp-dash-grid{display:none!important}
        .sp-card:has(.sp-activity-feed){display:none!important}
        /* Make government-service section label readable like other core sections */
        .sp-nav-group[data-section="government-service"] .sp-nav-section-toggle,
        .sp-nav-group[data-section="government-service"] .sp-nav-section-toggle .sp-nav-sec-label,
        .sp-nav-group[data-section="government-service"] .sp-nav-section-toggle .sp-nav-sec-label span,
        .sp-nav-group[data-section="government-service"] .sp-nav-section-toggle .sp-nav-sec-icon {
            color: rgba(255,255,255,0.85) !important;
            opacity: 1 !important;
        }
    </style>
    <script>
    (function(){
        var toggle = document.querySelector(\'.sp-nav-group[data-section="government-service"] .sp-nav-section-toggle\');
        if (toggle) toggle.classList.add(\'sp-nav-core-active\');
    })();
    </script>';
} );

// -- Sample data seeder --------------------------------------------------------
add_action( 'rest_api_init', function() {
    register_rest_route( 'sp-city/v1', '/seed-sample-data', array(
        'methods'             => 'GET',
        'callback'            => 'sp_city_seed_sample_data',
        'permission_callback' => function() { return current_user_can( 'manage_options' ); },
    ) );
} );

function sp_city_seed_sample_data() {
    global $wpdb;

    // Ensure tables exist before seeding
    sp_city_create_tables();

    $errors = array();
    $done   = array();

    // Diagnostic: confirm tables and row counts
    $ticket_table = $wpdb->prefix . 'sp_city_tickets';
    $dept_table   = $wpdb->prefix . 'sp_city_departments';
    $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$ticket_table'" );
    if ( ! $table_exists ) {
        return rest_ensure_response( array( 'error' => "Table $ticket_table does not exist after dbDelta.", 'prefix' => $wpdb->prefix ) );
    }

    $depts = $wpdb->get_results( "SELECT id, name FROM {$dept_table} ORDER BY sort_order", OBJECT_K );
    $dept_ids = array_keys( $depts );
    if ( empty( $dept_ids ) ) {
        return rest_ensure_response( array( 'error' => 'No departments found.', 'prefix' => $wpdb->prefix ) );
    }

    $now     = current_time( 'mysql' );
    $user_id = get_current_user_id();

    // Helper: find dept ID by name
    $find_dept = function( $name ) use ( $depts, $dept_ids ) {
        foreach ( $depts as $id => $d ) {
            if ( $d->name === $name ) return array( $id );
        }
        return array( $dept_ids[0] );
    };

    $tickets = array(
        array(
            'ticket_number' => 'CSR-' . date('Ymd') . '-00001',
            'status'        => 'open',
            'priority'      => 'emergency',
            'address'       => '142 Elm Street',
            'description'   => 'Large water main break flooding the intersection. Water is gushing from the ground and several vehicles are stuck.',
            'reporter_name' => 'Janet Morrison',
            'reporter_email'=> 'janet@example.com',
            'reporter_phone'=> '(803) 555-0142',
            'dept_ids'      => $find_dept( 'Water' ),
        ),
        array(
            'ticket_number' => 'CSR-' . date('Ymd') . '-00002',
            'status'        => 'in_progress',
            'priority'      => 'high',
            'address'       => '88 Oak Avenue',
            'description'   => 'Street light has been out for three nights. The block is very dark and residents are concerned about safety.',
            'reporter_name' => 'Marcus Webb',
            'reporter_email'=> 'marcus.webb@example.com',
            'reporter_phone'=> '(803) 555-0188',
            'dept_ids'      => $find_dept( 'Electric' ),
        ),
        array(
            'ticket_number' => 'CSR-' . date('Ymd') . '-00003',
            'status'        => 'open',
            'priority'      => 'normal',
            'address'       => '310 Maple Drive',
            'description'   => 'Large pothole on the road near the intersection causing vehicles to swerve. Has been there about two weeks.',
            'reporter_name' => 'Donna Simmons',
            'reporter_email'=> '',
            'reporter_phone'=> '(803) 555-0310',
            'dept_ids'      => $find_dept( 'Maintenance' ),
        ),
        array(
            'ticket_number' => 'CSR-' . date('Ymd') . '-00004',
            'status'        => 'open',
            'priority'      => 'normal',
            'address'       => '57 Pine Court',
            'description'   => 'Garbage was not picked up on scheduled collection day (Monday). Bins are overflowing.',
            'reporter_name' => 'Carlos Reyes',
            'reporter_email'=> 'c.reyes@example.com',
            'reporter_phone'=> '',
            'dept_ids'      => $find_dept( 'Garbage' ),
        ),
        array(
            'ticket_number' => 'CSR-' . date('Ymd') . '-00005',
            'status'        => 'resolved',
            'priority'      => 'high',
            'address'       => '221 River Road',
            'description'   => 'Sewer smell very strong near storm drain. Possible backup or broken line underground.',
            'reporter_name' => 'Patricia Hughes',
            'reporter_email'=> 'phughes@example.com',
            'reporter_phone'=> '(803) 555-0221',
            'dept_ids'      => $find_dept( 'Sewer' ),
        ),
    );

    foreach ( $tickets as $t ) {
        $dept_list = $t['dept_ids'];
        unset( $t['dept_ids'] );
        $t['created_at'] = $now;
        $t['updated_at'] = $now;
        $inserted = $wpdb->insert( $wpdb->prefix . 'sp_city_tickets', $t );
        if ( ! $inserted ) {
            $errors[] = "ticket insert failed: " . $wpdb->last_error;
            continue;
        }
        $ticket_id = $wpdb->insert_id;
        foreach ( $dept_list as $did ) {
            $wpdb->insert( $wpdb->prefix . 'sp_city_ticket_depts', array( 'ticket_id' => $ticket_id, 'dept_id' => $did ) );
        }
        $done[] = "ticket: {$t['ticket_number']} ({$t['priority']}, {$t['status']})";
    }

    // Seed sample team members into sp_team, then create on-call shifts
    $team_members = array(
        array( 'name' => 'Tom Bradley',  'email' => 'tom@anytown-sc.gov',    'role' => 'agent' ),
        array( 'name' => 'Sarah Kim',    'email' => 'sarah@anytown-sc.gov',  'role' => 'agent' ),
        array( 'name' => 'Mike Torres',  'email' => 'mike@anytown-sc.gov',   'role' => 'agent' ),
        array( 'name' => 'Linda Park',   'email' => 'linda@anytown-sc.gov',  'role' => 'agent' ),
        array( 'name' => 'James Cooper', 'email' => 'james@anytown-sc.gov',  'role' => 'agent' ),
        array( 'name' => 'Angela Davis', 'email' => 'angela@anytown-sc.gov', 'role' => 'agent' ),
        array( 'name' => 'Robert Mills', 'email' => 'robert@anytown-sc.gov', 'role' => 'agent' ),
    );
    $member_ids = array();
    foreach ( $team_members as $tm ) {
        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sp_team WHERE email = %s", $tm['email'] ) );
        if ( $existing ) {
            $member_ids[ $tm['name'] ] = (int) $existing;
        } else {
            $wpdb->insert( $wpdb->prefix . 'sp_team', array(
                'name'       => $tm['name'],
                'email'      => $tm['email'],
                'role'       => $tm['role'],
                'pin'        => '',
                'status'     => 'active',
                'created_at' => $now,
            ) );
            $member_ids[ $tm['name'] ] = $wpdb->insert_id;
            $done[] = "team: {$tm['name']}";
        }
    }

    $monday = date( 'Y-m-d', strtotime( 'monday this week' ) );
    $shifts = array(
        array( 'dept' => 'Water',       'member' => 'Tom Bradley',  'days' => 0, 'start' => '07:00', 'end' => '19:00' ),
        array( 'dept' => 'Water',       'member' => 'Sarah Kim',    'days' => 0, 'start' => '19:00', 'end_next' => true, 'end' => '07:00' ),
        array( 'dept' => 'Electric',    'member' => 'Mike Torres',  'days' => 0, 'start' => '07:00', 'end' => '19:00' ),
        array( 'dept' => 'Electric',    'member' => 'Linda Park',   'days' => 2, 'start' => '07:00', 'end' => '19:00' ),
        array( 'dept' => 'Maintenance', 'member' => 'James Cooper', 'days' => 0, 'start' => '06:00', 'end' => '18:00' ),
        array( 'dept' => 'Sewer',       'member' => 'Angela Davis', 'days' => 1, 'start' => '07:00', 'end' => '19:00' ),
        array( 'dept' => 'Garbage',     'member' => 'Robert Mills', 'days' => 0, 'start' => '05:00', 'end' => '13:00' ),
        array( 'dept' => 'Water',       'member' => 'Tom Bradley',  'days' => 7, 'start' => '07:00', 'end' => '19:00' ),
        array( 'dept' => 'Electric',    'member' => 'Mike Torres',  'days' => 7, 'start' => '07:00', 'end' => '19:00' ),
        array( 'dept' => 'Maintenance', 'member' => 'James Cooper', 'days' => 7, 'start' => '06:00', 'end' => '18:00' ),
    );

    foreach ( $shifts as $s ) {
        $dept_id   = 0;
        foreach ( $depts as $id => $d ) {
            if ( $d->name === $s['dept'] ) { $dept_id = $id; break; }
        }
        $member_id = isset( $member_ids[ $s['member'] ] ) ? $member_ids[ $s['member'] ] : 0;
        if ( ! $dept_id || ! $member_id ) continue;

        $shift_date  = date( 'Y-m-d', strtotime( $monday . ' +' . $s['days'] . ' days' ) );
        $start_dt    = $shift_date . ' ' . $s['start'] . ':00';
        $end_date    = ! empty( $s['end_next'] )
            ? date( 'Y-m-d', strtotime( $shift_date . ' +1 day' ) )
            : $shift_date;
        $end_dt      = $end_date . ' ' . $s['end'] . ':00';

        $inserted = $wpdb->insert( $wpdb->prefix . 'sp_city_oncall', array(
            'team_member_id' => $member_id,
            'dept_id'        => $dept_id,
            'start_datetime' => $start_dt,
            'end_datetime'   => $end_dt,
            'phone'          => '',
            'email'          => '',
            'notes'          => '',
        ) );
        if ( $inserted ) {
            $done[] = "shift: {$s['member']} ({$s['dept']}) {$shift_date}";
        } else {
            $errors[] = "shift insert failed: " . $wpdb->last_error;
        }
    }

    $final_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_city_tickets" );
    return rest_ensure_response( array(
        'success'       => empty( $errors ),
        'seeded'        => $done,
        'errors'        => $errors,
        'ticket_count'  => $final_count,
        'prefix'        => $wpdb->prefix,
    ) );
}

// -- Public ticket status lookup (no auth — returns only non-PII fields) ------
add_action( 'rest_api_init', function() {
    register_rest_route( 'sp-city/v1', '/ticket-status', array(
        'methods'             => 'GET',
        'callback'            => 'sp_city_rest_ticket_status',
        'permission_callback' => '__return_true',
    ) );
} );

function sp_city_rest_ticket_status( $req ) {
    global $wpdb;
    $number = sanitize_text_field( $req->get_param( 'number' ) );
    if ( ! $number ) {
        return rest_ensure_response( array( 'found' => false ) );
    }

    $ticket = $wpdb->get_row( $wpdb->prepare(
        "SELECT id, ticket_number, status, priority, address, created_at, updated_at, acknowledged_at
         FROM {$wpdb->prefix}sp_city_tickets WHERE ticket_number = %s LIMIT 1",
        $number
    ) );

    if ( ! $ticket ) {
        return rest_ensure_response( array( 'found' => false ) );
    }

    $depts = $wpdb->get_col( $wpdb->prepare(
        "SELECT d.name FROM {$wpdb->prefix}sp_city_ticket_depts td
         JOIN {$wpdb->prefix}sp_city_departments d ON d.id = td.dept_id
         WHERE td.ticket_id = %d ORDER BY d.sort_order",
        $ticket->id
    ) );

    $fmt = function( $dt ) {
        return $dt ? date( 'M j, Y g:i a', strtotime( $dt ) ) : null;
    };

    return rest_ensure_response( array(
        'found'              => true,
        'ticket_number'      => $ticket->ticket_number,
        'status'             => $ticket->status,
        'priority'           => $ticket->priority,
        'address'            => $ticket->address,
        'departments'        => implode( ', ', $depts ),
        'created_at_fmt'     => $fmt( $ticket->created_at ),
        'acknowledged_at_fmt'=> $fmt( $ticket->acknowledged_at ),
        'updated_at_fmt'     => $fmt( $ticket->updated_at ),
    ) );
}

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

add_action( 'rest_api_init', function() {
    register_rest_route( 'sp-city/v1', '/address-check', array(
        'methods'             => 'GET',
        'callback'            => 'sp_city_rest_address_check',
        'permission_callback' => function() { return sp_is_authed(); },
    ) );
} );

function sp_city_rest_address_check( $req ) {
    global $wpdb;
    $q = sanitize_text_field( $req->get_param( 'q' ) );
    if ( strlen( $q ) < 4 ) return rest_ensure_response( array() );
    $like  = '%' . $wpdb->esc_like( $q ) . '%';
    $rows  = $wpdb->get_results( $wpdb->prepare(
        "SELECT t.id, t.ticket_number, t.address, t.status, t.priority,
                GROUP_CONCAT(d.name ORDER BY d.sort_order SEPARATOR ', ') AS depts
         FROM {$wpdb->prefix}sp_city_tickets t
         LEFT JOIN {$wpdb->prefix}sp_city_ticket_depts td ON td.ticket_id = t.id
         LEFT JOIN {$wpdb->prefix}sp_city_departments d ON d.id = td.dept_id
         WHERE t.address LIKE %s AND t.status NOT IN ('closed','resolved','cancelled')
         GROUP BY t.id
         ORDER BY t.created_at DESC
         LIMIT 5",
        $like
    ) );
    return rest_ensure_response( $rows );
}

require_once SP_CITY_PLUGIN_DIR . 'includes/db.php';
require_once SP_CITY_PLUGIN_DIR . 'includes/oncall-helpers.php';
require_once SP_CITY_PLUGIN_DIR . 'includes/notifications.php';

// -- Boot ----------------------------------------------------------------------

add_action( 'plugins_loaded', 'sp_city_boot', 20 );

function sp_city_boot() {
    // Run dbDelta on every version change so new columns are added to existing installs.
    if ( get_option( 'sp_city_db_version' ) !== SP_CITY_VERSION ) {
        sp_city_create_tables();
        update_option( 'sp_city_db_version', SP_CITY_VERSION );
    }
    if ( ! function_exists( 'sp_register_view' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Start Performance -- Government Service Core</strong> requires the Start Performance core plugin.</p></div>';
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
    sp_register_addon( 'government-service', array(
        'name'        => 'Government Service',
        'version'     => SP_CITY_VERSION,
        'description' => 'Multi-department public ticket system with on-call scheduling, SMS/email notifications, and on-duty dashboard for city service operations.',
        'icon'        => '<path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9"/>',
        'plugin_file' => plugin_basename( __FILE__ ),
        'core_slot'   => 'government-service',
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
    add_action( 'sp_post_handler_city_team_import',  'sp_city_handle_team_import' );
    add_action( 'sp_delete_handler_city_ticket',     'sp_city_delete_ticket' );
    add_action( 'sp_delete_handler_city_oncall',     'sp_city_delete_oncall' );

    add_action( 'sp_dashboard_before_stats', 'sp_city_dashboard_onduty' );
    add_action( 'sp_dashboard_after_stats',  'sp_city_dashboard_stats' );
    add_action( 'sp_dashboard_after_grid',   'sp_city_dashboard_grid' );
    add_action( 'sp_settings_sections',          'sp_city_settings_section' );
    add_action( 'sp_team_member_form_fields',    'sp_city_team_role_field' );
    add_action( 'sp_after_team_member_save',     'sp_city_save_team_role_from_form' );
    add_filter( 'sp_send_member_welcome_email',  'sp_city_suppress_core_welcome', 10, 4 );

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
    $gov_items = array(
        array(
            'section'    => true,
            'section_id' => 'government-service',
            'label'      => get_option( 'sp_city_name', 'City Services' ),
            'icon'       => '<path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9"/>',
        ),
        array(
            'view'  => 'city-tickets',
            'label' => 'Service Tickets',
            'icon'  => '<path d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>',
        ),
        array(
            'view'  => 'city-onduty',
            'label' => 'On-Duty Board',
            'icon'  => '<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        ),
        array(
            'view'  => 'city-oncall',
            'label' => 'On-Call Schedule',
            'icon'  => '<path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
        ),
    );

    // Inject Government Service immediately after Dashboard; strip Core System section.
    $result      = array();
    $injected    = false;
    $skip_section = false;
    foreach ( $items as $item ) {
        // Track section boundaries to drop Core System and its children.
        if ( ! empty( $item['section'] ) ) {
            $sid          = isset( $item['section_id'] ) ? $item['section_id'] : '';
            $skip_section = ( $sid === 'core-system' );
            if ( $skip_section ) continue;
        } elseif ( $skip_section ) {
            continue;
        }

        $result[] = $item;

        if ( ! $injected && ! empty( $item['view'] ) && $item['view'] === 'dashboard' ) {
            foreach ( $gov_items as $g ) {
                $result[] = $g;
            }
            $injected = true;
        }
    }
    if ( ! $injected ) {
        foreach ( $gov_items as $g ) {
            $result[] = $g;
        }
    }
    return $result;
}

function sp_city_allowed_views( $views ) {
    $views[] = 'city-tickets';
    $views[] = 'city-oncall';
    $views[] = 'city-onduty';
    return $views;
}

// -- Auto-assign on-call -------------------------------------------------------

function sp_city_auto_assign_oncall( $ticket_id, $dept_ids ) {
    if ( empty( $dept_ids ) ) return;
    global $wpdb;

    $safe_ids = implode( ',', array_map( 'intval', $dept_ids ) );
    $primary_dept_id = (int) $wpdb->get_var(
        "SELECT id FROM {$wpdb->prefix}sp_city_departments
         WHERE id IN ({$safe_ids}) ORDER BY sort_order ASC LIMIT 1"
    );
    if ( ! $primary_dept_id ) return;

    $oncall = sp_city_get_oncall_now( $primary_dept_id );
    if ( empty( $oncall ) ) return; // no one on call — supervisor fallback already fired

    $shift = $oncall[0];
    if ( ! $shift->team_member_id ) return;

    $wpdb->update(
        $wpdb->prefix . 'sp_city_tickets',
        array( 'assigned_to' => (int) $shift->team_member_id, 'updated_at' => current_time( 'mysql' ) ),
        array( 'id' => $ticket_id )
    );
    // On-call notification already told this person about the ticket; no second email.
}

// -- Ticket save ---------------------------------------------------------------

function sp_city_save_ticket( $id ) {
    global $wpdb;

    // Basic call center agents can create new tickets but cannot edit existing ones.
    // Call center admins can edit. City role has full access.
    if ( $id && sp_city_is_call_center() ) {
        wp_redirect( home_url( '/sp-app/?view=city-tickets&error=access' ) ); exit;
    }

    $priority        = sanitize_key( isset( $_POST['priority'] ) ? $_POST['priority'] : 'normal' );
    $is_cc_role      = sp_city_is_call_center() || sp_city_is_call_center_admin();
    $is_cc_basic     = sp_city_is_call_center(); // basic call center only — no assign, locked to open
    $status          = $is_cc_role ? 'open' : sanitize_key( isset( $_POST['status'] ) ? $_POST['status'] : 'open' );
    $reporter_name   = sanitize_text_field( isset( $_POST['reporter_name'] )  ? $_POST['reporter_name']  : '' );
    $reporter_phone  = sanitize_text_field( isset( $_POST['reporter_phone'] ) ? $_POST['reporter_phone'] : '' );

    // Reporter name and phone are required on all new tickets.
    if ( ! $id && ( ! $reporter_name || ! $reporter_phone ) ) {
        wp_redirect( home_url( '/sp-app/?view=city-tickets&action=new&error=reporter_required' ) ); exit;
    }

    $data = array(
        'source'         => $is_cc_basic ? 'call_center' : sanitize_key( isset( $_POST['source'] ) ? $_POST['source'] : 'staff' ),
        'reporter_name'  => $reporter_name,
        'reporter_email' => sanitize_email(          isset( $_POST['reporter_email'] )  ? $_POST['reporter_email']  : '' ),
        'reporter_phone' => $reporter_phone,
        'address'        => sanitize_textarea_field( isset( $_POST['address'] )         ? $_POST['address']         : '' ),
        'description'    => sanitize_textarea_field( isset( $_POST['description'] )     ? $_POST['description']     : '' ),
        'priority'       => $priority,
        'status'         => $status,
        'assigned_to'    => $is_cc_basic ? 0 : (int) ( isset( $_POST['assigned_to'] ) ? $_POST['assigned_to'] : 0 ),
        'updated_at'     => current_time( 'mysql' ),
    );

    if ( $id ) {
        // Capture old assigned_to before update so we can detect a change.
        $old_assigned = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT assigned_to FROM {$wpdb->prefix}sp_city_tickets WHERE id = %d", $id
        ) );

        $wpdb->update( $wpdb->prefix . 'sp_city_tickets', $data, array( 'id' => $id ) );

        // Notify the newly assigned person if the assignment changed.
        $new_assigned = (int) $data['assigned_to'];
        if ( $new_assigned && $new_assigned !== $old_assigned ) {
            sp_city_notify_assigned( $id, $new_assigned );
        }

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

    // Notify on-call staff for each department.
    if ( $dept_ids ) {
        sp_city_notify_oncall( $new_id, $dept_ids );
        // Auto-assign to on-call rep for primary dept (only if not manually assigned).
        if ( empty( $data['assigned_to'] ) ) {
            sp_city_auto_assign_oncall( $new_id, $dept_ids );
        }
    }

    // Notify the directly assigned person (separate from on-call).
    if ( ! empty( $data['assigned_to'] ) ) {
        sp_city_notify_assigned( $new_id, (int) $data['assigned_to'] );
    }

    wp_redirect( home_url( '/sp-app/?view=city-tickets&action=edit&id=' . $new_id . '&saved=1' ) ); exit;
}

// -- Acknowledge ticket (dept-level) -------------------------------------------

function sp_city_acknowledge_ticket( $id ) {
    // Only basic call center agents are blocked; admins and city can acknowledge.
    if ( sp_city_is_call_center() ) {
        wp_redirect( home_url( '/sp-app/?view=city-tickets&error=access' ) ); exit;
    }
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
    if ( sp_city_is_call_center() ) {
        wp_redirect( home_url( '/sp-app/?view=city-tickets&error=access' ) ); exit;
    }
    // Call center admin can add notes but cannot change status to resolved/closed.
    if ( sp_city_is_call_center_admin() ) {
        $new_status = sanitize_key( isset( $_POST['status'] ) ? $_POST['status'] : '' );
        if ( in_array( $new_status, array( 'resolved', 'closed' ), true ) ) {
            unset( $_POST['status'] );
        }
    }
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
    if ( ! sp_is_authed() || ! sp_city_can_manage_settings() ) {
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
    if ( ! sp_is_authed() || ! sp_city_can_manage_settings() ) {
        wp_redirect( home_url( '/sp-app/?view=settings' ) ); exit;
    }
    $fields = array(
        'sp_city_name'              => 'sanitize_text_field',
        'sp_city_initials'          => 'sanitize_text_field',
        'sp_city_logo_url'          => 'esc_url_raw',
        'sp_city_supervisor_email'  => 'sanitize_email',
        'sp_city_supervisor_phone'  => 'sanitize_text_field',
        'sp_city_twilio_sid'        => 'sanitize_text_field',
        'sp_city_twilio_token'      => 'sanitize_text_field',
        'sp_city_twilio_from'       => 'sanitize_text_field',
        'sp_city_escalate_minutes'  => 'intval',
    );
    // Color fields — sanitize as hex
    foreach ( array( 'sp_city_primary_color', 'sp_city_secondary_color' ) as $color_key ) {
        // Accept from either the color picker or the hex text input
        $hex_val = isset( $_POST[ $color_key . '_hex' ] ) ? sanitize_text_field( $_POST[ $color_key . '_hex' ] ) : '';
        $col_val = isset( $_POST[ $color_key ] ) ? sanitize_text_field( $_POST[ $color_key ] ) : '';
        $val = $hex_val ?: $col_val;
        if ( $val && preg_match( '/^#[0-9a-fA-F]{3,6}$/', $val ) ) {
            update_option( $color_key, $val );
        }
    }
    foreach ( $fields as $key => $fn ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_option( $key, call_user_func( $fn, $_POST[ $key ] ) );
        }
    }
    // Save team roles if submitted from the roles table
    if ( ! empty( $_POST['sp_city_save_roles'] ) && isset( $_POST['sp_city_role'] ) && is_array( $_POST['sp_city_role'] ) ) {
        $roles = array();
        foreach ( $_POST['sp_city_role'] as $member_id => $role ) {
            $mid = (int) $member_id;
            $r   = in_array( $role, array( 'city_admin', 'city_supervisor', 'city_employee', 'call_center_admin', 'call_center' ), true ) ? $role : 'city_admin';
            if ( $mid ) $roles[ $mid ] = $r;
        }
        update_option( 'sp_city_member_roles', $roles );
    }
    wp_redirect( home_url( '/sp-app/?view=settings&saved=1' ) ); exit;
}

// -- Delete handlers -----------------------------------------------------------

function sp_city_delete_ticket( $id ) {
    if ( ! sp_city_can_delete() ) {
        wp_redirect( home_url( '/sp-app/?view=city-tickets&error=access' ) ); exit;
    }
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

function sp_city_dashboard_onduty() {
    $shifts = sp_city_get_oncall_now();
    if ( empty( $shifts ) ) return;
    ?>
    <div class="sp-card" style="margin-bottom:16px">
        <div class="sp-card-header">
            <h2>On Duty Right Now</h2>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=city-onduty' ) ); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">Full Board</a>
        </div>
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
    <?php
}

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
    $depts = sp_city_get_departments();

    $all_tickets = $wpdb->get_results(
        "SELECT t.*,
                GROUP_CONCAT(DISTINCT d.name ORDER BY d.sort_order SEPARATOR ', ') AS depts,
                GROUP_CONCAT(DISTINCT CAST(d.id AS CHAR) ORDER BY d.sort_order SEPARATOR ',') AS dept_ids_str,
                tm.name AS assigned_name
         FROM {$wpdb->prefix}sp_city_tickets t
         LEFT JOIN {$wpdb->prefix}sp_city_ticket_depts td ON td.ticket_id = t.id
         LEFT JOIN {$wpdb->prefix}sp_city_departments d ON d.id = td.dept_id
         LEFT JOIN {$wpdb->prefix}sp_team tm ON tm.id = t.assigned_to
         WHERE t.status NOT IN ('resolved','closed')
         GROUP BY t.id
         ORDER BY FIELD(t.priority,'emergency','high','normal','low'), t.created_at ASC"
    );

    // Group by dept
    $by_dept = array();
    foreach ( $depts as $d ) $by_dept[ $d->id ] = array();
    foreach ( $all_tickets as $t ) {
        foreach ( array_filter( array_map( 'intval', explode( ',', $t->dept_ids_str ?: '' ) ) ) as $did ) {
            if ( isset( $by_dept[ $did ] ) ) $by_dept[ $did ][] = $t;
        }
    }

    $uid = 'ctq';
    ?>
    <div class="sp-card sp-table-card" style="margin-top:16px">
        <div class="sp-card-header">
            <h2>Service Ticket Queue</h2>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=city-tickets&action=new' ) ); ?>" class="sp-btn sp-btn-primary sp-btn-sm">+ New</a>
        </div>

        <div style="display:flex;gap:0;border-bottom:1px solid var(--sp-border);padding:0 16px;overflow-x:auto;flex-shrink:0">
            <button onclick="cityQueueTab('all')" id="<?php echo esc_attr("{$uid}_tab_all"); ?>"
                    style="padding:10px 16px;font-size:13px;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid var(--sp-accent);color:var(--sp-accent);white-space:nowrap;margin-bottom:-1px">
                All <span style="font-weight:400;opacity:.65">(<?php echo count( $all_tickets ); ?>)</span>
            </button>
            <?php foreach ( $depts as $d ) :
                $cnt = count( $by_dept[ $d->id ] ); ?>
            <button onclick="cityQueueTab('<?php echo esc_js( $d->id ); ?>')" id="<?php echo esc_attr("{$uid}_tab_{$d->id}"); ?>"
                    style="padding:10px 16px;font-size:13px;font-weight:500;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;color:var(--sp-muted);white-space:nowrap;margin-bottom:-1px">
                <span style="flex-shrink:0;display:inline-block;width:8px;height:8px;background:<?php echo esc_attr( $d->color ); ?>;border-radius:50%;margin-right:4px;vertical-align:middle"></span>
                <?php echo esc_html( $d->name ); ?>
                <?php if ( $cnt > 0 ) : ?><span style="font-weight:400;opacity:.65">(<?php echo $cnt; ?>)</span><?php endif; ?>
            </button>
            <?php endforeach; ?>
        </div>

        <div id="<?php echo esc_attr("{$uid}_panel_all"); ?>">
            <?php sp_city_queue_table( $all_tickets, true ); ?>
        </div>
        <?php foreach ( $depts as $d ) : ?>
        <div id="<?php echo esc_attr("{$uid}_panel_{$d->id}"); ?>" style="display:none">
            <?php sp_city_queue_table( $by_dept[ $d->id ], false ); ?>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
    function cityQueueTab(tab) {
        document.querySelectorAll('[id^="ctq_panel_"]').forEach(function(el){ el.style.display='none'; });
        document.querySelectorAll('[id^="ctq_tab_"]').forEach(function(el){
            el.style.borderBottomColor='transparent';
            el.style.color='var(--sp-muted)';
            el.style.fontWeight='500';
        });
        var panel = document.getElementById('ctq_panel_'+tab);
        if(panel) panel.style.display='';
        var btn = document.getElementById('ctq_tab_'+tab);
        if(btn){ btn.style.borderBottomColor='var(--sp-accent)'; btn.style.color='var(--sp-accent)'; btn.style.fontWeight='600'; }
    }
    </script>
    <?php
}

function sp_city_queue_table( $tickets, $show_depts = true ) {
    if ( empty( $tickets ) ) : ?>
        <p class="sp-empty" style="padding:20px 16px">No open tickets.</p>
    <?php return; endif; ?>
    <table class="sp-table">
        <thead>
            <tr>
                <th>Ticket #</th>
                <th>Address</th>
                <?php if ( $show_depts ) : ?><th>Departments</th><?php endif; ?>
                <th>Priority</th>
                <th>Status</th>
                <th>Assigned To</th>
                <th>Submitted</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $tickets as $t ) : ?>
        <tr>
            <td><a href="<?php echo esc_url( home_url( '/sp-app/?view=city-tickets&action=edit&id=' . $t->id ) ); ?>" class="sp-link"><?php echo esc_html( $t->ticket_number ); ?></a></td>
            <td class="sp-muted"><?php echo esc_html( $t->address ); ?></td>
            <?php if ( $show_depts ) : ?><td class="sp-muted"><?php echo esc_html( $t->depts ?: '—' ); ?></td><?php endif; ?>
            <td><span class="sp-badge sp-badge-priority-<?php echo esc_attr( $t->priority ); ?>"><?php echo esc_html( ucfirst( $t->priority ) ); ?></span></td>
            <td><span class="sp-badge sp-badge-<?php echo esc_attr( $t->status ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $t->status ) ) ); ?></span></td>
            <td class="sp-muted"><?php echo esc_html( $t->assigned_name ?: '—' ); ?></td>
            <td class="sp-muted"><?php echo esc_html( date( 'M j g:ia', strtotime( $t->created_at ) ) ); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

// -- Team role field injected into core team member form -----------------------

function sp_city_team_role_field( $member ) {
    if ( ! sp_city_is_city_admin() && ! sp_city_is_call_center_admin() ) return;
    $member_id    = $member ? (int) $member->id : 0;
    $current_role = $member_id ? sp_city_get_member_role( $member_id ) : '';
    $roles = array(
        ''                 => '— Select Role —',
        'city_admin'       => 'City Admin',
        'city_supervisor'  => 'City Supervisor',
        'city_employee'    => 'City Employee',
        'call_center_admin'=> 'Call Center Admin',
        'call_center'      => 'Call Center',
    );
    ?>
    <div class="sp-form-group" style="margin-top:16px;padding-top:16px;border-top:1px solid var(--sp-border,#e5e7eb)">
        <label class="sp-label">City Role <span style="color:var(--sp-danger,#ef4444)">*</span></label>
        <select name="sp_city_member_role" style="max-width:260px" required>
            <?php foreach ( $roles as $val => $label ) : ?>
            <option value="<?php echo esc_attr( $val ); ?>"<?php selected( $current_role, $val ); ?>><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
        <span class="sp-hint" style="display:block;margin-top:4px">Controls what this person can see and do in the City Service system.</span>
    </div>
    <?php
}

function sp_city_save_team_role_from_form( $member_id ) {
    if ( ! sp_city_is_city_admin() && ! sp_city_is_call_center_admin() ) return;
    if ( ! isset( $_POST['sp_city_member_role'] ) ) return;
    $role = sanitize_key( $_POST['sp_city_member_role'] );
    $valid = array( '', 'city_admin', 'city_supervisor', 'city_employee', 'call_center_admin', 'call_center' );
    if ( ! in_array( $role, $valid, true ) ) return;
    $roles = get_option( 'sp_city_member_roles', array() );
    if ( $role === '' ) {
        unset( $roles[ $member_id ] );
    } else {
        $roles[ $member_id ] = $role;
    }
    update_option( 'sp_city_member_roles', $roles );

    // Send city-branded welcome email for new members
    $is_new = empty( $_POST['sp_id'] ) || ! (int) $_POST['sp_id'];
    if ( $is_new ) {
        sp_city_send_welcome_email( $member_id, sanitize_text_field( $_POST['pin'] ?? '' ), $role );
    }
}

function sp_city_suppress_core_welcome( $send, $name, $email, $pin ) {
    // Always suppress core welcome email — city sends its own branded version
    return false;
}

function sp_city_send_welcome_email( $member_id, $plain_pin, $city_role ) {
    global $wpdb;
    $member = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sp_team WHERE id = %d", $member_id
    ) );
    if ( ! $member || ! $member->email ) return;

    $role_labels = array(
        'city_admin'        => 'City Admin',
        'city_supervisor'   => 'City Supervisor',
        'city_employee'     => 'City Employee',
        'call_center_admin' => 'Call Center Admin',
        'call_center'       => 'Call Center Rep',
    );
    $role_label = isset( $role_labels[ $city_role ] ) ? $role_labels[ $city_role ] : 'Team Member';

    $city_name = get_option( 'sp_city_name', get_bloginfo( 'name' ) );
    $login_url = home_url( '/sp-login/' );

    $subject = "City of {$city_name} — Service Portal Access";
    $body    = "Hi {$member->name},\n\n"
             . "You've been added to the City of {$city_name}'s service management system. "
             . "This is where we track and route all public service requests across Water/Sewer, Electric, Streets/Sanitation, and Utility Billing.\n\n"
             . "YOUR LOGIN\n"
             . "----------\n"
             . "URL:   {$login_url}\n"
             . "Name:  {$member->name}\n"
             . "PIN:   {$plain_pin}\n\n"
             . "At the login page, select your name from the dropdown and enter your PIN above. "
             . "Keep your PIN confidential — contact your administrator if you need it reset.\n\n"
             . "YOUR ROLE: {$role_label}\n"
             . "----------\n";

    $role_desc = array(
        'city_admin'        => "You have full access: settings, all tickets, assignments, and team management.",
        'city_supervisor'   => "You can manage the full ticket queue, assign to anyone, and close or delete tickets.",
        'city_employee'     => "You can view and process tickets assigned to you or your department.",
        'call_center_admin' => "You can create and manage tickets, add notes, and assign to any team member.",
        'call_center'       => "You can create new service tickets on behalf of residents calling in.",
    );
    if ( isset( $role_desc[ $city_role ] ) ) {
        $body .= $role_desc[ $city_role ] . "\n\n";
    }

    $body .= "If you have questions about using the system, reach out to your administrator.\n\n"
           . "— City of {$city_name} Service Operations";

    wp_mail( $member->email, $subject, $body );
}

// -- Bulk team import ----------------------------------------------------------

function sp_city_handle_team_import() {
    if ( ! sp_city_is_city_admin() ) {
        wp_redirect( home_url( '/sp-app/' ) ); exit;
    }
    global $wpdb;

    if ( empty( $_FILES['sp_city_csv']['tmp_name'] ) ) {
        wp_redirect( home_url( '/sp-app/?view=settings&error=no_file' ) ); exit;
    }

    $valid_roles = array( 'city_admin', 'city_supervisor', 'city_employee', 'call_center_admin', 'call_center' );
    $results     = array();
    $roles_map   = get_option( 'sp_city_member_roles', array() );

    $fh = fopen( $_FILES['sp_city_csv']['tmp_name'], 'r' );
    if ( ! $fh ) {
        wp_redirect( home_url( '/sp-app/?view=settings&error=bad_file' ) ); exit;
    }

    $header = fgetcsv( $fh ); // skip header row

    while ( ( $row = fgetcsv( $fh ) ) !== false ) {
        if ( count( $row ) < 4 ) continue;
        list( $name, $email, $pin, $role ) = array_map( 'trim', $row );

        $name  = sanitize_text_field( $name );
        $email = sanitize_email( $email );
        $pin   = sanitize_text_field( $pin );
        $role  = sanitize_key( $role );

        if ( ! $name || ! $email || ! $pin ) {
            $results[] = array( 'name' => $name ?: '(blank)', 'email' => $email, 'ok' => false, 'msg' => 'Missing name, email, or PIN — skipped' );
            continue;
        }
        if ( ! in_array( $role, $valid_roles, true ) ) {
            $results[] = array( 'name' => $name, 'email' => $email, 'ok' => false, 'msg' => "Invalid role \"{$role}\" — skipped" );
            continue;
        }
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}sp_team WHERE email = %s", $email
        ) );
        if ( $exists ) {
            $results[] = array( 'name' => $name, 'email' => $email, 'ok' => false, 'msg' => 'Email already exists — skipped' );
            continue;
        }

        $wpdb->insert( $wpdb->prefix . 'sp_team', array(
            'name'       => $name,
            'email'      => $email,
            'pin'        => wp_hash_password( $pin ),
            'role'       => 'agent',
            'status'     => 'active',
            'created_at' => current_time( 'mysql' ),
        ) );
        $member_id = (int) $wpdb->insert_id;

        $roles_map[ $member_id ] = $role;
        sp_city_send_welcome_email( $member_id, $pin, $role );

        $results[] = array( 'name' => $name, 'email' => $email, 'ok' => true, 'msg' => 'Created & welcome email sent' );
    }
    fclose( $fh );

    update_option( 'sp_city_member_roles', $roles_map );
    set_transient( 'sp_city_import_results_' . get_current_user_id(), $results, 60 );
    wp_redirect( home_url( '/sp-app/?view=settings&saved=1' ) ); exit;
}

// -- Settings ------------------------------------------------------------------

function sp_city_settings_section() {
    global $wpdb;
    $depts = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sp_city_departments ORDER BY sort_order, name" );
    if ( ! sp_city_is_city_admin() ) {
        // Call center admin sees Team Roles only — skip to that section below.
        $team_members_cc = $wpdb->get_results( "SELECT id, name, email FROM {$wpdb->prefix}sp_team WHERE status = 'active' ORDER BY name" );
        if ( $team_members_cc ) : ?>
        <div class="sp-card sp-form-card" style="margin-top:24px">
            <h2 class="sp-section-heading">Team Roles</h2>
            <p style="font-size:13px;color:var(--sp-muted);margin:0 0 16px">
                <strong>City Admin</strong> — full access: settings, branding, delete, assign anyone.<br>
                <strong>City Supervisor</strong> — run the queue: assign anyone, delete tickets; no settings.<br>
                <strong>City Employee</strong> — process &amp; close tickets; can assign to supervisors only; no delete.<br>
                <strong>Call Center Admin</strong> — create &amp; edit tickets, add notes, acknowledge; can assign to anyone; no settings.<br>
                <strong>Call Center</strong> — create new tickets only; no assign.
            </p>
            <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
                <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                <input type="hidden" name="sp_type" value="city_config">
                <input type="hidden" name="sp_id" value="0">
                <input type="hidden" name="sp_city_save_roles" value="1">
                <table class="sp-table">
                    <thead><tr><th>Name</th><th>Email</th><th>City Role</th></tr></thead>
                    <tbody>
                    <?php foreach ( $team_members_cc as $tm ) :
                        $current_role = sp_city_get_member_role( $tm->id ); ?>
                    <tr>
                        <td style="font-weight:500"><?php echo esc_html( $tm->name ); ?></td>
                        <td class="sp-muted"><?php echo esc_html( $tm->email ); ?></td>
                        <td>
                            <select name="sp_city_role[<?php echo esc_attr( $tm->id ); ?>]" style="width:200px">
                                <option value="city_admin"        <?php selected( $current_role, 'city_admin' ); ?>>City Admin</option>
                                <option value="city_supervisor"   <?php selected( $current_role, 'city_supervisor' ); ?>>City Supervisor</option>
                                <option value="city_employee"     <?php selected( $current_role, 'city_employee' ); ?>>City Employee</option>
                                <option value="call_center_admin" <?php selected( $current_role, 'call_center_admin' ); ?>>Call Center Admin</option>
                                <option value="call_center"       <?php selected( $current_role, 'call_center' ); ?>>Call Center</option>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="sp-form-actions" style="padding-top:12px">
                    <button type="submit" class="sp-btn sp-btn-primary">Save Team Roles</button>
                </div>
            </form>
        </div>
        <?php endif;
        return;
    }
    ?>
    <div class="sp-card sp-form-card" style="margin-top:24px">
        <h2 class="sp-section-heading">CityCore — Instance Settings</h2>
        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="city_config">
            <input type="hidden" name="sp_id" value="0">

            <h3 style="font-size:14px;font-weight:600;margin:0 0 12px">Branding</h3>
            <div class="sp-form-row">
                <div class="sp-field">
                    <label>City / Organization Name</label>
                    <input type="text" name="sp_city_name" value="<?php echo esc_attr( get_option( 'sp_city_name', '' ) ); ?>" placeholder="City of Clinton">
                    <span class="sp-hint">Shown in sidebar, nav, and public portal.</span>
                </div>
                <div class="sp-field">
                    <label>Initials (2 letters)</label>
                    <input type="text" name="sp_city_initials" value="<?php echo esc_attr( get_option( 'sp_city_initials', '' ) ); ?>" placeholder="CC" maxlength="3">
                    <span class="sp-hint">Sidebar avatar. Leave blank to auto-derive from name.</span>
                </div>
            </div>
            <div class="sp-form-row">
                <div class="sp-field">
                    <label>Primary Color</label>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <input type="color" name="sp_city_primary_color" value="<?php echo esc_attr( get_option( 'sp_city_primary_color', '#1e3a5f' ) ); ?>" style="width:48px;height:36px;padding:2px;border:1px solid #d1d5db;border-radius:6px;cursor:pointer;">
                        <input type="text" name="sp_city_primary_color_hex" value="<?php echo esc_attr( get_option( 'sp_city_primary_color', '#1e3a5f' ) ); ?>" placeholder="#1e3a5f" style="width:100px;" oninput="this.previousElementSibling.value=this.value">
                    </div>
                    <span class="sp-hint">Sidebar, header bars, buttons, active states.</span>
                </div>
                <div class="sp-field">
                    <label>Secondary Color</label>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <input type="color" name="sp_city_secondary_color" value="<?php echo esc_attr( get_option( 'sp_city_secondary_color', '#c8a84b' ) ); ?>" style="width:48px;height:36px;padding:2px;border:1px solid #d1d5db;border-radius:6px;cursor:pointer;">
                        <input type="text" name="sp_city_secondary_color_hex" value="<?php echo esc_attr( get_option( 'sp_city_secondary_color', '#c8a84b' ) ); ?>" placeholder="#c8a84b" style="width:100px;" oninput="this.previousElementSibling.value=this.value">
                    </div>
                    <span class="sp-hint">Accent elements and department tags.</span>
                </div>
            </div>
            <div class="sp-form-row">
                <div class="sp-field">
                    <label>Logo URL</label>
                    <input type="url" name="sp_city_logo_url" value="<?php echo esc_attr( get_option( 'sp_city_logo_url', '' ) ); ?>" placeholder="https://...">
                    <span class="sp-hint">Upload your logo to the WordPress Media Library first, then paste the direct image URL here. Replaces the initials avatar in the sidebar. Leave blank to keep initials.</span>
                </div>
            </div>

            <h3 style="font-size:14px;font-weight:600;margin:20px 0 12px">Operations</h3>
            <div class="sp-form-row">
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
                    <span class="sp-hint">Receives escalation alerts and fallback notices when no one is on-call for a department.</span>
                </div>
                <div class="sp-field">
                    <label>Supervisor Phone (SMS)</label>
                    <input type="text" name="sp_city_supervisor_phone" value="<?php echo esc_attr( get_option( 'sp_city_supervisor_phone', '' ) ); ?>" placeholder="+15555550100">
                    <span class="sp-hint">SMS is not active for Clinton — this field is not in use.</span>
                </div>
            </div>

            <h3 style="font-size:14px;font-weight:600;margin:20px 0 8px">Twilio SMS Settings</h3>
            <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:6px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#92400e">
                <strong>SMS is not active for Clinton.</strong> Leave these fields blank. They can be filled in later if SMS notifications are enabled.
            </div>
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

    <!-- Team Roles -->
    <?php
    $team_members = $wpdb->get_results( "SELECT id, name, email FROM {$wpdb->prefix}sp_team WHERE status = 'active' ORDER BY name" );
    if ( $team_members ) : ?>
    <div class="sp-card sp-form-card" style="margin-top:16px">
        <h2 class="sp-section-heading">Team Roles</h2>
        <p style="font-size:13px;color:var(--sp-muted);margin:0 0 16px">
            <strong>City Admin</strong> — full access: settings, branding, delete, assign anyone.<br>
            <strong>City Supervisor</strong> — run the queue: assign anyone, delete tickets; no settings.<br>
            <strong>City Employee</strong> — process &amp; close tickets; can assign to supervisors only; no delete.<br>
            <strong>Call Center Admin</strong> — create &amp; edit tickets, add notes, acknowledge; no assign, no delete, no settings.<br>
            <strong>Call Center</strong> — create new tickets only; no assign.
        </p>
        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="city_config">
            <input type="hidden" name="sp_id" value="0">
            <input type="hidden" name="sp_city_save_roles" value="1">
            <table class="sp-table">
                <thead><tr><th>Name</th><th>Email</th><th>City Role</th></tr></thead>
                <tbody>
                <?php foreach ( $team_members as $tm ) :
                    $current_role = sp_city_get_member_role( $tm->id ); ?>
                <tr>
                    <td style="font-weight:500"><?php echo esc_html( $tm->name ); ?></td>
                    <td class="sp-muted"><?php echo esc_html( $tm->email ); ?></td>
                    <td>
                        <select name="sp_city_role[<?php echo esc_attr( $tm->id ); ?>]" style="width:200px">
                            <option value="city_admin"        <?php selected( $current_role, 'city_admin' ); ?>>City Admin</option>
                            <option value="city_supervisor"   <?php selected( $current_role, 'city_supervisor' ); ?>>City Supervisor</option>
                            <option value="city_employee"     <?php selected( $current_role, 'city_employee' ); ?>>City Employee</option>
                            <option value="call_center_admin" <?php selected( $current_role, 'call_center_admin' ); ?>>Call Center Admin</option>
                            <option value="call_center"       <?php selected( $current_role, 'call_center' ); ?>>Call Center</option>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="sp-form-actions" style="padding-top:12px">
                <button type="submit" class="sp-btn sp-btn-primary">Save Team Roles</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if ( sp_city_is_city_admin() ) :
        $import_results = get_transient( 'sp_city_import_results_' . get_current_user_id() );
        delete_transient( 'sp_city_import_results_' . get_current_user_id() );
    ?>
    <div class="sp-card sp-form-card" style="margin-top:24px">
        <h2 class="sp-section-heading">Bulk Import Team Members</h2>
        <p style="font-size:13px;color:var(--sp-muted);margin:0 0 16px">
            Upload a CSV file to create multiple team members at once. Each member will receive the city welcome email.<br>
            <strong>Required columns:</strong> Name, Email, PIN, Role &nbsp;|&nbsp;
            <strong>Valid roles:</strong> city_admin, city_supervisor, city_employee, call_center_admin, call_center
        </p>
        <div style="background:var(--sp-surface,#f9fafb);border:1px solid var(--sp-border,#e5e7eb);border-radius:8px;padding:12px 16px;font-family:monospace;font-size:12px;margin-bottom:16px;color:var(--sp-muted)">
            Name,Email,PIN,Role<br>
            Jane Smith,jane@example.com,4821,city_employee<br>
            Bob Jones,bob@example.com,7734,call_center
        </div>

        <?php if ( $import_results ) : ?>
        <div style="margin-bottom:20px;padding:14px 16px;background:var(--sp-surface,#f9fafb);border:1px solid var(--sp-border);border-radius:8px">
            <strong style="display:block;margin-bottom:10px">Import Results</strong>
            <table class="sp-table" style="font-size:13px">
                <thead><tr><th>Name</th><th>Email</th><th>Result</th></tr></thead>
                <tbody>
                <?php foreach ( $import_results as $r ) : ?>
                <tr>
                    <td><?php echo esc_html( $r['name'] ); ?></td>
                    <td><?php echo esc_html( $r['email'] ); ?></td>
                    <td style="color:<?php echo $r['ok'] ? 'var(--sp-success,#16a34a)' : 'var(--sp-danger,#ef4444)'; ?>">
                        <?php echo esc_html( $r['msg'] ); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" enctype="multipart/form-data">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="city_team_import">
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                <input type="file" name="sp_city_csv" accept=".csv" required style="font-size:13px">
                <button type="submit" class="sp-btn sp-btn-primary">Import Members</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
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

    // Handle photo uploads (up to 3, images only, max 8 MB each)
    $photo_ids = array();
    if ( ! empty( $_FILES['city_photos']['name'][0] ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $allowed_mime = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
        $count = min( 3, count( $_FILES['city_photos']['name'] ) );
        for ( $i = 0; $i < $count; $i++ ) {
            if ( empty( $_FILES['city_photos']['name'][ $i ] ) ) continue;
            $file = array(
                'name'     => $_FILES['city_photos']['name'][ $i ],
                'type'     => $_FILES['city_photos']['type'][ $i ],
                'tmp_name' => $_FILES['city_photos']['tmp_name'][ $i ],
                'error'    => $_FILES['city_photos']['error'][ $i ],
                'size'     => $_FILES['city_photos']['size'][ $i ],
            );
            if ( $file['error'] || $file['size'] > 8 * 1024 * 1024 ) continue;
            if ( ! in_array( $file['type'], $allowed_mime, true ) ) continue;
            $uploaded = wp_handle_upload( $file, array( 'test_form' => false ) );
            if ( isset( $uploaded['file'] ) ) {
                $att_id = wp_insert_attachment( array(
                    'post_mime_type' => $uploaded['type'],
                    'post_title'     => 'Service Request ' . $ticket_number,
                    'post_status'    => 'inherit',
                ), $uploaded['file'] );
                if ( $att_id ) {
                    wp_update_attachment_metadata( $att_id, wp_generate_attachment_metadata( $att_id, $uploaded['file'] ) );
                    $photo_ids[] = $att_id;
                }
            }
        }
        if ( $photo_ids ) {
            $wpdb->update( $wpdb->prefix . 'sp_city_tickets', array( 'photo_ids' => implode( ',', $photo_ids ) ), array( 'id' => $new_id ) );
        }
    }

    $ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_city_tickets WHERE id = %d", $new_id ) );

    if ( $dept_ids ) {
        sp_city_notify_oncall( $new_id, $dept_ids );
        sp_city_auto_assign_oncall( $new_id, $dept_ids );
    }
    if ( $ticket->reporter_email ) {
        sp_city_notify_reporter( $ticket );
    }

    $return = esc_url_raw( isset( $_POST['return_url'] ) ? $_POST['return_url'] : home_url() );
    wp_redirect( add_query_arg( 'city_ticket', $ticket_number, $return ) );
    exit;
}
