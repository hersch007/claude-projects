<?php
/**
 * SP View: Quote Settings — admin-only pricing rate-table editor.
 *
 * Renders the ported "Data Editor" (rate tables, formula costs, defaults, product
 * codes). Editing is restricted to Start Performance admin members. Saving reuses
 * the engine's schema + JSON validation, gated + nonce-checked here.
 *
 * @package start-performance-quotes
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$sp_quotes_is_admin = function_exists( 'sp_is_admin_member' ) && sp_is_admin_member();

if ( ! $sp_quotes_is_admin ) {
    echo '<div class="sp-card"><h2>Quote Settings</h2><p class="sp-empty">Quote pricing tables can only be edited by administrators.</p></div>';
    return;
}

if ( ! function_exists( 'sqs_pricing_calculator_render_frontend_editor' ) ) {
    echo '<div class="sp-card"><p class="sp-empty">Quote engine failed to load.</p></div>';
    return;
}

// ── Handle "Save All Tables" POST (admin-gated, nonce-checked) ────────────────
$sp_quotes_msg = '';
if ( isset( $_POST['sqs_frontend_action'] ) && 'save_all' === $_POST['sqs_frontend_action'] ) {
    if ( ! isset( $_POST['sqs_frontend_nonce'] )
        || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sqs_frontend_nonce'] ) ), 'sqs_frontend_save' ) ) {
        $sp_quotes_msg = 'Save failed because the page security check expired. Refresh and try again.';
    } else {
        global $wpdb;
        $sp_quotes_table = sqs_pricing_calculator_table_name();
        $sp_quotes_err   = '';
        if ( isset( $_POST['sqs_data'] ) && is_array( $_POST['sqs_data'] ) ) {
            foreach ( $_POST['sqs_data'] as $sp_key => $sp_json ) {
                $sp_key     = sqs_pricing_calculator_clean_data_key( $sp_key );
                $sp_json    = wp_unslash( $sp_json );
                $sp_decoded = json_decode( $sp_json, true );
                if ( json_last_error() !== JSON_ERROR_NONE ) {
                    $sp_quotes_err = 'One or more tables were not saved because a value could not be read.';
                    break;
                }
                $wpdb->update(
                    $sp_quotes_table,
                    array(
                        'data_json'  => wp_json_encode( $sp_decoded, JSON_PRETTY_PRINT ),
                        'updated_at' => current_time( 'mysql' ),
                    ),
                    array( 'data_key' => $sp_key )
                );
            }
        }
        $sp_quotes_msg = $sp_quotes_err ? $sp_quotes_err : 'Tables saved.';
    }
}

// App-bar CSS (the editor uses the shared .sqs-app-bar chrome).
if ( function_exists( 'sqs_app_bar_styles' ) ) {
    sqs_app_bar_styles();
}

// Render the editor UI (reads current values from the DB, emits a fresh nonce).
sqs_pricing_calculator_render_frontend_editor( $sp_quotes_msg );
