<?php
/*
 * Plugin Name: Start Performance — Fruth Quotes
 * Description: Custom quote module for Fruth — database-backed pricing calculator (tubing, in-line BSB, zipper) with versioned quotes and printable quotations. Client-specific add-on for the Start Performance Platform.
 * Version:     1.0.0
 * Author:      Richard Brashear / Start Performance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SP_FRUTH_QUOTES_VERSION',    '1.0.0' );
define( 'SP_FRUTH_QUOTES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// ── Load the ported Quote Builder engine + print module ───────────────────────
// Loaded at file-scope (like any plugin) so their own WordPress hooks (admin-ajax
// handlers, the sqs_* extension points) register against the normal request
// lifecycle. The engine defines SQS_VERSION, which the print module requires.
// The engine keeps its original sqs_ internals, wp_sqs_* tables and AJAX actions
// — that is the faithful port and the HubSpot-feeding data. Only this wrapper
// (the module's SP identity) is Fruth-branded.
require_once SP_FRUTH_QUOTES_PLUGIN_DIR . 'includes/quote-engine.php';
require_once SP_FRUTH_QUOTES_PLUGIN_DIR . 'includes/quote-print.php';

// ── Dependency check + registration ───────────────────────────────────────────
// Everything SP-specific is registered inside this boot so nothing runs if core
// is absent (mirrors the SMTI custom-module pattern).

add_action( 'plugins_loaded', 'sp_fruth_quotes_boot', 20 );

function sp_fruth_quotes_boot() {
    if ( ! function_exists( 'sp_register_view' ) ) {
        add_action( 'admin_notices', 'sp_fruth_quotes_dependency_notice' );
        return;
    }
    sp_fruth_quotes_register();
}

function sp_fruth_quotes_dependency_notice() {
    echo '<div class="notice notice-error"><p><strong>Start Performance — Fruth Quotes</strong> requires the Start Performance core plugin to be installed and active.</p></div>';
}

// SVG path for the nav icon (document / quote).
function sp_fruth_quotes_icon() {
    return '<path fill="currentColor" d="M6 2a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6H6zm7 1.5L18.5 9H13V3.5zM8 12h8v2H8v-2zm0 4h8v2H8v-2zm0-8h3v2H8V8z"/>';
}

// ── Registration ──────────────────────────────────────────────────────────────

function sp_fruth_quotes_register() {
    sp_register_addon( 'sp-fruth-quotes', array(
        'name'        => 'Fruth Quotes',
        'version'     => SP_FRUTH_QUOTES_VERSION,
        'description' => 'Custom quote builder for Fruth — database-backed pricing for tubing, in-line BSB and zipper products, with versioned quotes (Draft/Final/Archived) and printable quotations.',
        'settings_url'=> home_url( '/sp-app/?view=fruth_quote_settings' ),
        'icon'        => sp_fruth_quotes_icon(),
        'plugin_file' => plugin_basename( __FILE__ ),
    ) );

    // The calculator view and the admin-only pricing-table editor view.
    // View slugs are Fruth-namespaced so they never collide with a first-party module.
    sp_register_view( 'fruth_quotes',         SP_FRUTH_QUOTES_PLUGIN_DIR . 'templates/views/quotes.php' );
    sp_register_view( 'fruth_quote_settings', SP_FRUTH_QUOTES_PLUGIN_DIR . 'templates/views/quote_settings.php' );

    add_filter( 'sp_nav_items',     'sp_fruth_quotes_nav_items' );
    add_filter( 'sp_allowed_views', 'sp_fruth_quotes_allowed_views' );

    // Optional dashboard surface.
    add_action( 'sp_dashboard_after_grid', 'sp_fruth_quotes_dashboard_grid' );
}

// ── Activation: create tables (delegates to the engine's schema) ──────────────

register_activation_hook( __FILE__, 'sp_fruth_quotes_activate' );
add_action( 'sp_activate', 'sp_fruth_quotes_activate' ); // also runs if both are activated together

function sp_fruth_quotes_activate() {
    // Engine owns the schema + default seed (wp_sqs_pricing_data, wp_sqs_quotes,
    // wp_sqs_quote_items). Faithful port keeps the sqs_ table names.
    if ( function_exists( 'sqs_pricing_calculator_activate' ) ) {
        sqs_pricing_calculator_activate();
    }
}

// ── Nav: inject "Quotes" into the existing Sales Core section ──────────────────

function sp_fruth_quotes_nav_items( $items ) {
    $quote_item = array(
        'view'  => 'fruth_quotes',
        'label' => 'Quotes',
        'icon'  => sp_fruth_quotes_icon(),
        'addon' => true,
    );

    // Prefer to drop it directly under the core "sales-core" section header.
    $out    = array();
    $placed = false;
    foreach ( $items as $item ) {
        $out[] = $item;
        if ( ! $placed && ! empty( $item['section'] ) && isset( $item['section_id'] ) && 'sales-core' === $item['section_id'] ) {
            $out[]  = $quote_item;
            $placed = true;
        }
    }

    // Fallback: no sales-core section present — append our own section + item.
    if ( ! $placed ) {
        $out[] = array( 'section' => true, 'section_id' => 'sales-core', 'label' => 'Sales' );
        $out[] = $quote_item;
    }

    return $out;
}

function sp_fruth_quotes_allowed_views( $views ) {
    $views[] = 'fruth_quotes';
    $views[] = 'fruth_quote_settings';
    return $views;
}

// ── Dashboard: recent quotes ──────────────────────────────────────────────────

function sp_fruth_quotes_dashboard_grid() {
    global $wpdb;
    if ( ! function_exists( 'sqs_pricing_calculator_quotes_table_name' ) ) return;
    $table = sqs_pricing_calculator_quotes_table_name();
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) return;

    $rows = $wpdb->get_results(
        "SELECT quote_number, revision_label, status, product_type, company_name, total_sales, updated_at
         FROM $table ORDER BY updated_at DESC LIMIT 5"
    );
    ?>
    <div class="sp-card sp-table-card" style="margin-top:16px">
        <div class="sp-card-header">
            <h2>Recent Quotes</h2>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=fruth_quotes' ) ); ?>" class="sp-btn sp-btn-primary sp-btn-sm">Open Quote Builder</a>
        </div>
        <?php if ( empty( $rows ) ) : ?>
            <p class="sp-empty">No quotes saved yet.</p>
        <?php else : ?>
            <table class="sp-table">
                <thead><tr><th>Quote #</th><th>Account</th><th>Product</th><th>Status</th><th>Total</th><th>Updated</th></tr></thead>
                <tbody>
                <?php foreach ( $rows as $q ) : ?>
                    <tr>
                        <td><?php echo esc_html( trim( $q->quote_number . ' ' . $q->revision_label ) ); ?></td>
                        <td class="sp-muted"><?php echo $q->company_name ? esc_html( $q->company_name ) : '—'; ?></td>
                        <td class="sp-muted"><?php echo esc_html( ucfirst( $q->product_type ) ); ?></td>
                        <td><span class="sp-badge sp-badge-<?php echo esc_attr( strtolower( $q->status ) ); ?>"><?php echo esc_html( $q->status ); ?></span></td>
                        <td><?php echo esc_html( '$' . number_format( (float) $q->total_sales, 2 ) ); ?></td>
                        <td class="sp-muted"><?php echo esc_html( date( 'M j', strtotime( $q->updated_at ) ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
