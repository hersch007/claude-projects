<?php
/**
 * SP View: Quotes — hosts the ported Quote Builder calculator.
 *
 * Access is already gated by Start Performance (allowed_views + team auth) before
 * this template renders. Any authenticated team member may build/save quotes; the
 * AJAX save/load/get handlers re-check via sqs_pricing_calculator_user_can_quote_ajax().
 *
 * @package start-performance-quotes
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'sqs_pricing_calculator_render' ) ) {
    echo '<div class="sp-card"><p class="sp-empty">Quote engine failed to load. Ensure the Start Performance — Quotes plugin files are intact.</p></div>';
    return;
}

// The app-bar/toolbar/load-modal CSS and the print module CSS+JS were previously
// emitted via wp_head in the standalone plugin. The SP app renders its own document
// shell, so we emit them here instead, right before the calculator markup.
if ( function_exists( 'sqs_app_bar_styles' ) ) {
    sqs_app_bar_styles();
}
if ( function_exists( 'sqsp_head_assets' ) ) {
    sqsp_head_assets(); // print styles + JS + the sqs:update listener
}

// Render the full calculator. Its internal do_action( 'sqs_bottom_stack_extra' ),
// 'sqs_toolbar_extra_buttons' and 'sqs_quote_info_extra_fields' hooks fire the
// print module's injections (print card, Print button, extra quote-info fields).
sqs_pricing_calculator_render();
