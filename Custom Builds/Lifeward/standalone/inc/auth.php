<?php
/**
 * Minimal admin session auth — a single shared password (bcrypt hash stored in
 * data/settings.json), no user table needed for a one-admin standalone app.
 * If no password has been set yet, lw_require_admin() sends you to setup instead
 * of login.
 */

function lw_start_session() {
    if ( session_status() !== PHP_SESSION_ACTIVE ) {
        session_set_cookie_params( array( 'httponly' => true, 'samesite' => 'Lax' ) );
        session_start();
    }
}

function lw_is_admin_logged_in() {
    lw_start_session();
    return ! empty( $_SESSION['lw_admin'] );
}

function lw_require_admin() {
    lw_start_session();
    $settings = lw_get_settings();
    if ( $settings['admin_password_hash'] === '' ) {
        header( 'Location: setup.php' ); exit;
    }
    if ( empty( $_SESSION['lw_admin'] ) ) {
        header( 'Location: login.php' ); exit;
    }
}
