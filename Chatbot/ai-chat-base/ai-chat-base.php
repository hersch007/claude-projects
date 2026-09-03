<?php
/**
 * Plugin Name: AI Chat Base
 * Plugin URI:  https://startadvertising.com
 * Description: White-label AI chat widget powered by Claude. Configure everything from WP Admin.
 * Version:     1.0.0
 * Author:      Start Performance | Richard Brashear
 * Author URI:  https://startadvertising.com
 * License:     GPL-2.0-or-later
 * Text Domain: ai-chat-base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'AICHAT_VERSION',    '1.0.0' );
define( 'AICHAT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AICHAT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AICHAT_MODEL',      'claude-sonnet-4-6' );
define( 'AICHAT_MAX_TOKENS', 500 );
define( 'AICHAT_MAX_HISTORY', 10 );
define( 'AICHAT_RATE_LIMIT',  30 );

require_once AICHAT_PLUGIN_DIR . 'includes/rate-limit.php';
require_once AICHAT_PLUGIN_DIR . 'includes/lead.php';
require_once AICHAT_PLUGIN_DIR . 'includes/ajax.php';
require_once AICHAT_PLUGIN_DIR . 'includes/settings.php';

/*
|--------------------------------------------------------------------------
| ASSETS
|--------------------------------------------------------------------------
*/

add_action( 'wp_enqueue_scripts', 'aichat_enqueue_assets' );

function aichat_enqueue_assets() {
    wp_enqueue_style(
        'aichat-widget',
        AICHAT_PLUGIN_URL . 'assets/css/widget.css',
        array(),
        AICHAT_VERSION
    );

    // Inject brand colors as CSS variables
    $primary = sanitize_hex_color( get_option( 'aichat_color_primary', '#6559b1' ) );
    $accent  = sanitize_hex_color( get_option( 'aichat_color_accent',  '#a9c33f' ) );
    wp_add_inline_style( 'aichat-widget', ":root{--ac-primary:{$primary};--ac-accent:{$accent};}" );

    wp_enqueue_script(
        'aichat-widget',
        AICHAT_PLUGIN_URL . 'assets/js/widget.js',
        array(),
        AICHAT_VERSION,
        true
    );

    $chips_raw = get_option( 'aichat_chips', 'New Customer,Existing Customer,Ask a Question' );
    $chips     = array_map( 'trim', explode( ',', $chips_raw ) );

    wp_localize_script( 'aichat-widget', 'aichatData', array(
        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'aichat_nonce' ),
        'sessionId' => wp_generate_uuid4(),
        'botName'   => esc_js( get_option( 'aichat_bot_name', 'Ace' ) ),
        'greeting'  => esc_js( get_option( 'aichat_greeting', "Hey, I'm here to help! What can I do for you today?" ) ),
        'chips'     => array_slice( $chips, 0, 3 ),
    ) );
}

/*
|--------------------------------------------------------------------------
| WIDGET HTML
|--------------------------------------------------------------------------
*/

add_action( 'wp_footer', 'aichat_render_widget' );

function aichat_render_widget() {
    $bot_name = esc_html( get_option( 'aichat_bot_name', 'Ace' ) );
    $tagline  = esc_html( get_option( 'aichat_tagline', 'We\'re here to help.' ) );
    $phone    = esc_html( get_option( 'aichat_phone', '' ) );
    $phone_link = preg_replace( '/\D/', '', $phone );
    ?>
    <div id="ac-nudge" style="display:none;" aria-live="polite">
        <button id="ac-nudge-close" aria-label="Dismiss">&times;</button>
        <div id="ac-nudge-text">👋 Have a question? I can help right now.</div>
    </div>

    <div id="ac-widget">
        <button id="ac-launcher" aria-label="Open chat">
            <svg id="ac-launcher-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="26" height="26">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <span id="ac-dot"></span>
        </button>

        <div id="ac-panel" aria-label="Chat window">
            <div id="ac-header">
                <div id="ac-avatar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
                <div>
                    <div id="ac-header-title"><?php echo $bot_name; ?></div>
                    <div id="ac-header-sub"><?php echo $tagline; ?></div>
                </div>
                <button id="ac-close" aria-label="Close chat">&times;</button>
            </div>

            <div id="ac-body"></div>

            <div id="ac-input-wrap">
                <button id="ac-photo-btn" aria-label="Attach photo" title="Send a photo"></button>
                <input type="file" id="ac-photo-input" accept="image/*" style="display:none;">
                <textarea id="ac-input" rows="1" maxlength="600" placeholder="Type your message..."></textarea>
                <button id="ac-send" aria-label="Send">&#10148;</button>
            </div>

            <?php if ( $phone ) : ?>
            <div id="ac-footer">
                <a href="tel:<?php echo esc_attr( $phone_link ); ?>" id="ac-footer-phone"><?php echo $phone; ?></a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
