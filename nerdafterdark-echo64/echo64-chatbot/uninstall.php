<?php
/**
 * Echo-64 Chatbot — Uninstall
 *
 * Runs only when the plugin is deleted from Plugins > Installed Plugins.
 * Drops the conversations table and removes all plugin options.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Drop conversation history table.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}echo64_conversations" );

// Remove all plugin options.
$options = [
    'echo64_api_key',
    'echo64_model',
    'echo64_max_tokens',
    'echo64_history_limit',
    'echo64_float_widget',
    'echo64_system_prompt',
    'echo64_version',
    'echo64_db_version',
];

foreach ( $options as $key ) {
    delete_option( $key );
}

// Remove daily-poem transients (prefixed by session ID — use a wildcard delete).
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_echo64_daily_%'
        OR option_name LIKE '_transient_timeout_echo64_daily_%'"
);
