<?php
/**
 * Plugin Name: LawnAce Chatbot
 * Plugin URI:  https://startadvertising.com
 * Description: AI-powered lawn care chat widget for LawnAce, powered by Claude.
 * Version:     3.90.0
 * Author:      Start Performance | Richard Brashear
 * Author URI:  https://startadvertising.com
 * License:     GPL-2.0-or-later
 * Text Domain: lawnace-chatbot
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LAWNACE_VERSION',    '3.90.0' );
define( 'LAWNACE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LAWNACE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'LAWNACE_MODEL',       'claude-sonnet-4-6' );
define( 'LAWNACE_MAX_TOKENS',  700 );
define( 'LAWNACE_MAX_HISTORY', 12 );
define( 'LAWNACE_RATE_LIMIT',  40 );

require_once LAWNACE_PLUGIN_DIR . 'includes/logging.php';
require_once LAWNACE_PLUGIN_DIR . 'includes/rate-limit.php';
require_once LAWNACE_PLUGIN_DIR . 'includes/system-prompt.php';
require_once LAWNACE_PLUGIN_DIR . 'includes/lead.php';
require_once LAWNACE_PLUGIN_DIR . 'includes/ajax.php';
require_once LAWNACE_PLUGIN_DIR . 'includes/admin.php';
require_once LAWNACE_PLUGIN_DIR . 'includes/client-dashboard.php';
require_once LAWNACE_PLUGIN_DIR . 'includes/digest.php';

register_activation_hook( __FILE__, 'lawnace_activate' );
register_deactivation_hook( __FILE__, 'lawnace_deactivate' );

function lawnace_activate() {
    lawnace_chatbot_ensure_log_table();
    lawnace_register_client_dashboard_rewrite();
    flush_rewrite_rules();
    lawnace_digest_schedule();
}

function lawnace_deactivate() {
    lawnace_digest_unschedule();
}

// Flush rewrite rules whenever the plugin version changes (i.e. after every zip upload)
add_action( 'init', 'lawnace_maybe_flush_rewrites', 20 );

function lawnace_maybe_flush_rewrites() {
    if ( get_option( 'lawnace_flushed_version' ) !== LAWNACE_VERSION ) {
        lawnace_register_client_dashboard_rewrite();
        flush_rewrite_rules();
        update_option( 'lawnace_flushed_version', LAWNACE_VERSION );
    }
}

/*
|--------------------------------------------------------------------------
| FRONTEND ASSETS + WIDGET HTML
|--------------------------------------------------------------------------
*/

add_action( 'wp_enqueue_scripts', 'lawnace_enqueue_assets' );

function lawnace_enqueue_assets() {
    wp_enqueue_style(
        'lawnace-widget',
        LAWNACE_PLUGIN_URL . 'assets/css/widget.css',
        array(),
        LAWNACE_VERSION
    );

    wp_enqueue_script(
        'lawnace-widget',
        LAWNACE_PLUGIN_URL . 'assets/js/widget.js',
        array(),
        LAWNACE_VERSION,
        true
    );

    wp_localize_script(
        'lawnace-widget',
        'lawnaceChat',
        array(
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'lawnace_chat_nonce' ),
            'sessionId' => wp_generate_uuid4(),
        )
    );
}

add_action( 'wp_footer', 'lawnace_render_widget' );

function lawnace_render_widget() {
    ?>
    <div id="la-nudge" style="display:none;" aria-live="polite">
        <button id="la-nudge-close" aria-label="Dismiss">&times;</button>
        <div id="la-nudge-text">🌿 Have a lawn question? I'm Lawnie — ask me anything.</div>
    </div>

    <div id="lawnace-ai-widget">
        <button id="la-launcher" aria-label="Open Lawn Ace AI chat">
            <span id="la-launcher-icon">
                <img src="<?php echo esc_url( LAWNACE_PLUGIN_URL . 'assets/images/lawnie-icon.png' ); ?>" width="62" height="62" alt="" aria-hidden="true" style="display:block;border-radius:50%;">
            </span>
            <span id="la-dot"></span>
        </button>

        <div id="la-panel" aria-label="Lawn Ace AI chat window">
            <div id="la-header">
                <div id="la-avatar">
                    <img src="<?php echo esc_url( LAWNACE_PLUGIN_URL . 'assets/images/lawnie-icon.png' ); ?>" width="32" height="32" alt="" aria-hidden="true" style="display:block;border-radius:50%;">
                </div>
                <div>
                    <div id="la-header-title">Lawnie &mdash; Your Lawn Expert</div>
                    <div id="la-header-sub"><span id="la-online-dot"></span>Online now &middot; Augusta &amp; CSRA</div>
                </div>
                <button id="la-close" aria-label="Close chat">&times;</button>
            </div>

            <div id="la-body"></div>

            <div id="la-input-wrap">
                <button id="la-photo-btn" aria-label="Attach photo" title="Send a photo of your lawn"></button>
                <input type="file" id="la-photo-input" accept="image/*" style="display:none;">
                <textarea id="la-input" rows="1" maxlength="600" placeholder="Type your message..."></textarea>
                <button id="la-send" aria-label="Send message">&#10148;</button>
            </div>

            <div id="la-footer">Local. Trusted. Here to help. &middot; <a href="tel:7063642338" id="la-footer-phone">706-364-2338</a></div>
            <?php $la_privacy_url = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : ''; ?>
            <div id="la-disclaimer">Lawnie is an AI assistant. Answers are general information, not a quote or professional advice, and you act on them at your own risk. Chats are saved so our team can follow up.<?php if ( ! empty( $la_privacy_url ) ) : ?> <a href="<?php echo esc_url( $la_privacy_url ); ?>" target="_blank" rel="noopener">Privacy</a><?php endif; ?></div>
        </div>
    </div>
    <?php
}
