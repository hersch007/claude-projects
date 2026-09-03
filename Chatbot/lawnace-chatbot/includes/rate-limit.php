<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lawnace_chatbot_check_rate_limit() {
    $ip    = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? 'unknown' );
    $key   = 'lawnace_rate_' . md5( $ip );
    $count = (int) get_transient( $key );

    if ( $count >= LAWNACE_RATE_LIMIT ) {
        return false;
    }

    set_transient( $key, $count + 1, HOUR_IN_SECONDS );

    return true;
}
