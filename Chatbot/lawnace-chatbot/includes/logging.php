<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lawnace_chatbot_ensure_log_table() {
    global $wpdb;

    $table   = $wpdb->prefix . 'lawnace_chat_logs';
    $charset = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE {$table} (
        id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        session_id VARCHAR(64)     NOT NULL,
        ip_address VARCHAR(45)     NOT NULL,
        role       VARCHAR(20)     NOT NULL,
        message    LONGTEXT        NOT NULL,
        created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY session_id (session_id),
        KEY created_at (created_at),
        KEY role (role)
    ) {$charset};";

    dbDelta( $sql );
}

function lawnace_chatbot_log_message( $session_id, $role, $message ) {
    global $wpdb;

    lawnace_chatbot_ensure_log_table();

    $wpdb->insert(
        $wpdb->prefix . 'lawnace_chat_logs',
        array(
            'session_id' => sanitize_text_field( $session_id ),
            'ip_address' => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ),
            'role'       => sanitize_text_field( $role ),
            'message'    => sanitize_textarea_field( $message ),
            'created_at' => current_time( 'mysql' ),
        )
    );
}
