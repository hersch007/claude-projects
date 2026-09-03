<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function aichat_check_rate_limit() {
    $ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
    $key = 'aichat_rl_' . md5( $ip );
    $count = (int) get_transient( $key );
    if ( $count >= AICHAT_RATE_LIMIT ) {
        return false;
    }
    set_transient( $key, $count + 1, HOUR_IN_SECONDS );
    return true;
}
