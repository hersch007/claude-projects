<?php
// Bridge file for Start Performance's sp_register_view() -- see
// sp-views/fruth-quotes.php for the general pattern. This one is
// admin-only (Data Editor access), matching the agreed role split:
// Admin gets Sales + Data Editor + can manage users; Agent gets Sales only.
// Gated here even though the nav item is also hidden from agents in
// sqs_pricing_calculator_sp_nav_items() -- nav visibility is never the real
// authorization boundary in this plugin (same discipline used everywhere
// else), so an agent guessing this URL directly must still be blocked here.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
sqs_pricing_calculator_activate();

if ( ! function_exists( 'sp_is_admin_member' ) || ! sp_is_admin_member() ) {
    echo '<p>You do not have access to this page.</p>';
    return;
}

if ( isset( $_POST['sqs_frontend_action'] ) && 'save_all' === $_POST['sqs_frontend_action'] ) {
    sqs_pricing_calculator_frontend_handle_save_all();
}

$message = sqs_pricing_calculator_frontend_login_message();
sqs_pricing_calculator_frontend_clear_message();
sqs_pricing_calculator_render_frontend_editor( $message, true );
