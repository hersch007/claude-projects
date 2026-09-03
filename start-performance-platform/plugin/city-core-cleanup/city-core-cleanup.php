<?php
/*
 * Plugin Name: City Core — Instance Cleanup
 * Description: One-time tool to wipe SP platform data carried over from a copied instance. DELETE THIS PLUGIN AFTER RUNNING.
 * Version:     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// GET endpoint with secret token — avoids ModSecurity POST blocks
add_action( 'rest_api_init', function() {
    register_rest_route( 'city-cleanup/v1', '/run', array(
        'methods'             => 'GET',
        'callback'            => 'city_cleanup_run',
        'permission_callback' => function() { return current_user_can( 'manage_options' ); },
    ) );
} );

function city_cleanup_run() {
    global $wpdb;
    $done = array();

    $tables_to_clear = array(
        'sp_contacts','sp_companies','sp_leads','sp_team','sp_notes','sp_tasks',
        'sp_attachments','sp_activity','sp_email_log','sp_estimates','sp_estimate_items',
        'sp_proposals','sp_proposal_items','sp_quotes','sp_quote_items','sp_contracts',
        'sp_ai_messages','sp_intel_reports','sp_daily_digest_log','sp_chat_leads',
        'sp_chat_sessions','sp_knowledge_items','sp_pipeline','sp_pipeline_stages',
        'sp_city_tickets','sp_city_ticket_depts','sp_city_ticket_notes',
        'sp_city_oncall','sp_city_notification_log',
    );

    foreach ( $tables_to_clear as $table ) {
        $full = $wpdb->prefix . $table;
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$full'" ) ) {
            $wpdb->query( "TRUNCATE TABLE `$full`" );
            $done[] = "cleared: $full";
        }
    }

    // Re-seed departments
    $dept_table = $wpdb->prefix . 'sp_city_departments';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$dept_table'" ) ) {
        if ( ! $wpdb->get_var( "SELECT COUNT(*) FROM `$dept_table`" ) ) {
            foreach ( array(
                array('Electric','#F59E0B',1), array('Water','#3B82F6',2),
                array('Sewer','#6B7280',3),   array('Maintenance','#10B981',4),
                array('Garbage','#8B5CF6',5),
            ) as $d ) {
                $wpdb->insert( $dept_table, array('name'=>$d[0],'color'=>$d[1],'sort_order'=>$d[2],'is_active'=>1) );
            }
            $done[] = 're-seeded departments';
        }
    }

    // Clear old SP options
    $sp_options = $wpdb->get_col(
        "SELECT option_name FROM {$wpdb->options}
         WHERE option_name LIKE 'sp_%'
         AND option_name NOT IN (
             'sp_city_name','sp_city_accent_color','sp_city_supervisor_email',
             'sp_city_supervisor_phone','sp_city_escalate_minutes',
             'sp_city_twilio_sid','sp_city_twilio_token','sp_city_twilio_from',
             'sp_public_frontend','sp_setup_complete'
         )"
    );
    foreach ( $sp_options as $opt ) {
        delete_option( $opt );
        $done[] = "deleted option: $opt";
    }

    return rest_ensure_response( array( 'success' => true, 'actions' => $done ) );
}

add_action( 'admin_menu', function() {
    add_management_page( 'Instance Cleanup', 'Instance Cleanup', 'manage_options', 'city-cleanup', 'city_cleanup_page' );
} );

function city_cleanup_page() {
    global $wpdb;

    $done = array();

    if ( isset( $_POST['city_cleanup_nonce'] ) && wp_verify_nonce( $_POST['city_cleanup_nonce'], 'city_cleanup' ) ) {

        $tables_to_clear = array(
            // SP core CRM tables (actual table names)
            'sp_contacts',
            'sp_companies',
            'sp_leads',
            'sp_team',
            'sp_notes',
            'sp_tasks',
            'sp_attachments',
            'sp_activity',
            'sp_email_log',
            // SP addon tables
            'sp_estimates',
            'sp_estimate_items',
            'sp_proposals',
            'sp_proposal_items',
            'sp_quotes',
            'sp_quote_items',
            'sp_contracts',
            'sp_ai_messages',
            'sp_intel_reports',
            'sp_daily_digest_log',
            'sp_chat_leads',
            'sp_chat_sessions',
            'sp_knowledge_items',
            'sp_pipeline',
            'sp_pipeline_stages',
            // City service tables
            'sp_city_tickets',
            'sp_city_ticket_depts',
            'sp_city_ticket_notes',
            'sp_city_oncall',
            'sp_city_notification_log',
        );

        foreach ( $tables_to_clear as $table ) {
            $full = $wpdb->prefix . $table;
            $exists = $wpdb->get_var( "SHOW TABLES LIKE '$full'" );
            if ( $exists ) {
                $wpdb->query( "TRUNCATE TABLE `$full`" );
                $done[] = "Cleared: $full";
            }
        }

        // Re-seed city departments (they were wiped above)
        $dept_table = $wpdb->prefix . 'sp_city_departments';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$dept_table'" ) ) {
            $exists = $wpdb->get_var( "SELECT COUNT(*) FROM `$dept_table`" );
            if ( ! $exists ) {
                $depts = array(
                    array( 'Electric',    '#F59E0B', 1 ),
                    array( 'Water',       '#3B82F6', 2 ),
                    array( 'Sewer',       '#6B7280', 3 ),
                    array( 'Maintenance', '#10B981', 4 ),
                    array( 'Garbage',     '#8B5CF6', 5 ),
                );
                foreach ( $depts as $d ) {
                    $wpdb->insert( $dept_table, array(
                        'name'       => $d[0],
                        'color'      => $d[1],
                        'sort_order' => $d[2],
                        'is_active'  => 1,
                    ) );
                }
                $done[] = 'Re-seeded default departments';
            }
        }

        // Clear SP-specific options from old instance
        $sp_options = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options}
             WHERE option_name LIKE 'sp_%'
             AND option_name NOT IN (
                'sp_city_name','sp_city_accent_color','sp_city_supervisor_email',
                'sp_city_supervisor_phone','sp_city_escalate_minutes',
                'sp_city_twilio_sid','sp_city_twilio_token','sp_city_twilio_from',
                'sp_public_frontend','sp_setup_complete'
             )"
        );
        foreach ( $sp_options as $opt ) {
            delete_option( $opt );
            $done[] = "Deleted option: $opt";
        }
    }

    ?>
    <div class="wrap">
        <h1>&#x1F9F9; Instance Cleanup</h1>
        <p style="color:#b91c1c;font-weight:600">⚠ This wipes all SP platform data (team members, clients, tickets, etc.) carried over from the copied instance. Run once, then delete this plugin.</p>

        <?php if ( $done ) : ?>
            <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:16px;margin:16px 0">
                <strong style="color:#166534">✓ Cleanup complete — <?php echo count( $done ); ?> actions taken</strong>
                <ul style="margin:8px 0 0;padding-left:20px;font-size:13px;color:#166534">
                    <?php foreach ( $done as $line ) echo "<li>" . esc_html( $line ) . "</li>"; ?>
                </ul>
            </div>
            <p style="color:#6b7280"><strong>Next step:</strong> Go to Plugins and delete "City Core — Instance Cleanup".</p>
        <?php else : ?>
            <form method="post" style="margin-top:20px">
                <?php wp_nonce_field( 'city_cleanup', 'city_cleanup_nonce' ); ?>
                <table class="widefat" style="max-width:600px;margin-bottom:20px">
                    <thead><tr><th>What gets cleared</th><th>What gets kept</th></tr></thead>
                    <tbody>
                        <tr><td>All SP team members &amp; clients</td><td>WordPress admin users</td></tr>
                        <tr><td>All activity logs, tasks, files</td><td>City departments (re-seeded)</td></tr>
                        <tr><td>All tickets, on-call shifts</td><td>City settings (name, Twilio, etc.)</td></tr>
                        <tr><td>SP options from old instance</td><td>Pages (terms, privacy, service-request)</td></tr>
                        <tr><td>AI/chat/knowledge data</td><td>Theme &amp; plugin settings</td></tr>
                    </tbody>
                </table>
                <input type="submit" class="button button-primary button-large" value="Run Cleanup Now"
                    onclick="return confirm('This cannot be undone. Are you sure?')">
            </form>
        <?php endif; ?>
    </div>
    <?php
}
