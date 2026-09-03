<?php
/**
 * Plugin Name: FiberCo AI Chatbot
 * Plugin URI:  https://startadvertising.com
 * Description: AI-powered FiberCo chat assistant with quote intake, lead logging, review dashboard, and CSV export.
 * Version:     1.3.9
 * Author:      Start Performance | Richard Brashear
 * Author URI:  https://startadvertising.com
 * License:     GPL-2.0-or-later
 * Text Domain: fiberco-ai-chatbot
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'FIBERCO_CHATBOT_VERSION', '1.3.9' );

register_activation_hook( __FILE__, 'fiberco_ai_activate_plugin' );
function fiberco_ai_activate_plugin() {
    fiberco_create_chat_log_table();
    fiberco_register_public_dashboard_route();
    fiberco_create_public_dashboard_page();
    flush_rewrite_rules();
}

/**
 * FiberCo AI Chatbot — Functions (v2.7 Confirmation Copy Update)
 * WPCode Plugin: PHP Snippet — Run Everywhere
 */

// ─────────────────────────────────────────────
// 1. CREATE LOG TABLE ON ACTIVATION
// ─────────────────────────────────────────────
function fiberco_create_chat_log_table() {
    global $wpdb;
    $table   = $wpdb->prefix . 'fiberco_chat_logs';
    $charset = $wpdb->get_charset_collate();
    $sql     = "CREATE TABLE IF NOT EXISTS $table (
        id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        session_id      VARCHAR(64)     NOT NULL,
        ip_address      VARCHAR(45)     NOT NULL,
        user_message    TEXT            NOT NULL,
        bot_response    TEXT            NOT NULL,
        escalated       TINYINT(1)      NOT NULL DEFAULT 0,
        failed_attempts TINYINT(1)      NOT NULL DEFAULT 0,
        created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_session (session_id),
        KEY idx_ip      (ip_address),
        KEY idx_created (created_at)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
add_action( 'init', 'fiberco_create_chat_log_table' );

add_action( 'wp_ajax_fiberco_chat',        'fiberco_handle_chat' );
add_action( 'wp_ajax_nopriv_fiberco_chat', 'fiberco_handle_chat' );

add_action( 'wp_ajax_fiberco_chat_nonce',        'fiberco_get_fresh_chat_nonce' );
add_action( 'wp_ajax_nopriv_fiberco_chat_nonce', 'fiberco_get_fresh_chat_nonce' );

function fiberco_get_fresh_chat_nonce() {
    nocache_headers();
    wp_send_json_success( array(
        'nonce'   => wp_create_nonce( 'fiberco_chat_nonce' ),
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
    ) );
}

function fiberco_check_rate_limit( $ip ) {
    $transient_key = 'fiberco_rl_' . md5( $ip );
    $count         = (int) get_transient( $transient_key );
    if ( $count >= 300 ) return false;
    set_transient( $transient_key, $count + 1, HOUR_IN_SECONDS );
    return true;
}

function fiberco_get_history( $session_id ) {
    $key     = 'fiberco_hist_' . md5( $session_id );
    $history = get_transient( $key );
    return is_array( $history ) ? $history : array();
}

function fiberco_save_history( $session_id, $history ) {
    $key = 'fiberco_hist_' . md5( $session_id );
    if ( count( $history ) > 12 ) $history = array_slice( $history, -12 );
    set_transient( $key, $history, 2 * HOUR_IN_SECONDS );
}

function fiberco_get_failed_attempts( $session_id ) {
    return (int) get_transient( 'fiberco_fail_' . md5( $session_id ) );
}

function fiberco_increment_failed_attempts( $session_id ) {
    $key   = 'fiberco_fail_' . md5( $session_id );
    $count = (int) get_transient( $key );
    set_transient( $key, $count + 1, 2 * HOUR_IN_SECONDS );
    return $count + 1;
}

function fiberco_reset_failed_attempts( $session_id ) {
    delete_transient( 'fiberco_fail_' . md5( $session_id ) );
}

function fiberco_request_is_same_origin() {
    $site_host = wp_parse_url( home_url(), PHP_URL_HOST );
    $checks    = array();

    if ( ! empty( $_SERVER['HTTP_ORIGIN'] ) ) {
        $checks[] = wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ), PHP_URL_HOST );
    }
    if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
        $checks[] = wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ), PHP_URL_HOST );
    }

    foreach ( $checks as $host ) {
        if ( $host && strtolower( $host ) === strtolower( $site_host ) ) {
            return true;
        }
    }
    return false;
}

function fiberco_verify_chat_request() {
    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

    if ( $nonce && wp_verify_nonce( $nonce, 'fiberco_chat_nonce' ) ) {
        return true;
    }

    // Public chat pages are often cached, which can stale the nonce.
    // For production reliability, allow same-origin public chat requests through,
    // while rate limiting still protects the endpoint from abuse.
    if ( fiberco_request_is_same_origin() ) {
        return true;
    }

    return false;
}

function fiberco_handle_chat() {
    nocache_headers();
    if ( ! fiberco_verify_chat_request() ) {
        wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
    }
    $ip = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] )
        ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
        : sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
    if ( ! fiberco_check_rate_limit( $ip ) ) {
        wp_send_json_error( array( 'message' => 'Too many messages. Please wait a few minutes.' ), 429 );
    }
    $raw_message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
    $session_id  = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : wp_generate_uuid4();
    if ( empty( $raw_message ) ) {
        wp_send_json_error( array( 'message' => 'No message received.' ), 400 );
    }
    $history         = fiberco_get_history( $session_id );
    $failed_attempts = fiberco_get_failed_attempts( $session_id );
    $result          = fiberco_get_response( $raw_message, $history, $session_id, $failed_attempts );
    $bot_response    = $result['message'];
    $quick_replies   = $result['quick_replies'] ?? array();
    $escalated       = $result['escalated'] ?? false;
    $was_fallback    = $result['was_fallback'] ?? false;
    if ( $was_fallback ) {
        fiberco_increment_failed_attempts( $session_id );
    } else {
        fiberco_reset_failed_attempts( $session_id );
    }
    $history[] = array( 'role' => 'user',     'content' => $raw_message );
    $history[] = array( 'role' => 'assistant', 'content' => wp_strip_all_tags( $bot_response ) );
    fiberco_save_history( $session_id, $history );
    fiberco_log_conversation( $session_id, $ip, $raw_message, $bot_response, $escalated, $was_fallback ? 1 : 0 );
    wp_send_json_success( array(
        'message'       => $bot_response,
        'session_id'    => $session_id,
        'quick_replies' => $quick_replies,
        'escalated'     => $escalated,
    ) );
}

function fiberco_log_conversation( $session_id, $ip, $user_msg, $bot_msg, $escalated = false, $failed_attempts = 0 ) {
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'fiberco_chat_logs',
        array(
            'session_id'      => $session_id,
            'ip_address'      => $ip,
            'user_message'    => $user_msg,
            'bot_response'    => wp_strip_all_tags( $bot_msg ),
            'escalated'       => $escalated ? 1 : 0,
            'failed_attempts' => $failed_attempts,
            'created_at'      => current_time( 'mysql' ),
        ),
        array( '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
    );
}


function fiberco_quote_bridge_button( $label = 'Review and Finish Secure Checkout', $type = 'residential', $plan = '', $address = '', $extra = array() ) {
    $payload = array_merge(
        array(
            'type'          => sanitize_key( $type ),
            'plan'          => sanitize_key( $plan ),
            'address'       => sanitize_text_field( $address ),
            'jumpToPayment' => true,
        ),
        is_array( $extra ) ? $extra : array()
    );
    $payload['name']  = isset( $payload['name'] ) ? sanitize_text_field( $payload['name'] ) : '';
    $payload['email'] = isset( $payload['email'] ) ? sanitize_email( $payload['email'] ) : '';
    $payload['phone'] = isset( $payload['phone'] ) ? sanitize_text_field( $payload['phone'] ) : '';
    $json = esc_attr( wp_json_encode( $payload ) );
    return "<br><br><button type='button' class='fiberco-chat-quote-btn' onclick='if(window.fibercoQuoteStartFromChat){window.fibercoQuoteStartFromChat(JSON.parse(this.dataset.fcq));}else{var p=new URLSearchParams(JSON.parse(this.dataset.fcq));window.location.href=\"https://startwebservicesbackup.com/fiber/?fcq_chat_quote=1&\"+p.toString();}' data-fcq='" . $json . "'>" . esc_html( $label ) . "</button>";
}

function fiberco_normalize_address_for_wizard( $address ) {
    $address = is_scalar( $address ) ? trim( (string) $address ) : '';
    if ( $address === '' ) return '';
    $address = preg_replace( '/\s+/', ' ', $address );
    $address = preg_replace( '/\s*,\s*/', ', ', $address );

    $suffix = '(?:street|st|road|rd|avenue|ave|drive|dr|lane|ln|court|ct|circle|cir|boulevard|blvd|way|place|pl|crossing|xing|parkway|pkwy|terrace|ter|trail|trl)';

    // Normalize both comma and no-comma addresses:
    // 123 Main Street Rock Hill SC 29732
    // 123 Main Street, Rock Hill, SC, 29732
    if ( preg_match( '/^(\d{1,6}\s+.+?\b' . $suffix . '\b)[,\s]+(.+?)[,\s]+([A-Z]{2})[,\s]+(\d{5}(?:-\d{4})?)$/i', $address, $m ) ) {
        $street = trim( $m[1] );
        $city   = trim( preg_replace( '/\s+/', ' ', str_replace( ',', '', $m[2] ) ) );
        $state  = strtoupper( trim( $m[3] ) );
        $zip    = trim( $m[4] );
        return $street . ', ' . $city . ', ' . $state . ' ' . $zip;
    }

    return $address;
}

function fiberco_extract_possible_address( $message ) {
    $message = is_scalar( $message ) ? trim( (string) $message ) : '';
    if ( $message === '' ) return '';

    $suffix = '(?:street|st|road|rd|avenue|ave|drive|dr|lane|ln|court|ct|circle|cir|boulevard|blvd|way|place|pl|crossing|xing|parkway|pkwy|terrace|ter|trail|trl)';

    // Strong full-address pattern. Captures through ZIP, then stops before contact text.
    // Handles:
    // 123 Main Street Rock Hill SC 29732
    // 123 Main Street, Rock Hill, SC, 29732
    // 123 Main Street Rock Hill SC 29732 my name is Richard
    if ( preg_match( '/\b\d{1,6}\s+[a-z0-9.\' -]+?\s+' . $suffix . '\b(?:[\s,]+[a-z.\' -]+)*[\s,]+[a-z]{2}[\s,]+\d{5}(?:-\d{4})?\b/i', $message, $m ) ) {
        return fiberco_normalize_address_for_wizard( trim( preg_replace( '/\s+/', ' ', $m[0] ) ) );
    }

    // Partial street (number + street name). The suffix (St/Rd/Ave...) is now
    // OPTIONAL so "123 Main", "123 Elm Springfield" etc. are captured too.
    if ( preg_match( '/\b\d{1,6}\s+[a-z][a-z0-9.\' -]{2,}/i', $message, $m ) ) {
        return trim( preg_replace( '/\s+/', ' ', $m[0] ) );
    }

    // Loosened demo fallback: any reply containing a ZIP is taken as the address
    // (the bot explicitly asked for it, and availability is always "available").
    if ( fiberco_address_has_zip( $message ) ) {
        return fiberco_normalize_address_for_wizard( trim( preg_replace( '/\s+/', ' ', $message ) ) );
    }

    return '';
}

function fiberco_address_has_zip( $address ) {
    return is_scalar( $address ) && preg_match( '/\b\d{5}(?:-\d{4})?\b/', (string) $address );
}

function fiberco_address_has_state( $address ) {
    return is_scalar( $address ) && preg_match( '/\b[A-Z]{2}\b/i', (string) $address );
}

function fiberco_is_usable_address( $address ) {
    $address = fiberco_normalize_address_for_wizard( $address );
    $address = is_scalar( $address ) ? trim( (string) $address ) : '';
    // Loosened for the demo (availability is always "available"): accept any
    // reasonable address text — a street number, a ZIP, or a street/city name is
    // enough. No longer requires a perfectly formatted street + city + state + ZIP.
    if ( strlen( $address ) < 4 || ! preg_match( '/[a-z]/i', $address ) ) return false;
    return (bool) ( preg_match( '/\d{1,6}\s+[a-z]/i', $address )      // "123 Main"
        || fiberco_address_has_zip( $address )                        // has a ZIP
        || preg_match( '/[a-z]{2,}[\s,]+[a-z]{2,}/i', $address ) );    // "Main St" / "Springfield IL"
}

function fiberco_guess_plan_from_message( $message ) {
    $msg = strtolower( $message );
    if ( preg_match( '/\b(10\s*g|10gig|10 gig|quantum|max|maximum|highest|fastest|top speed|best|power user|everything|premium)\b/', $msg ) ) return 'quantummax';
    if ( preg_match( '/\b(5\s*g|5gig|5 gig|velocity|pro|6\+|heavy|gaming|streaming|work from home|large household)\b/', $msg ) ) return 'velocitypro';
    if ( preg_match( '/\b(1\s*g|1gig|1 gig|everyday gig|gigabit|3-5|family|normal)\b/', $msg ) ) return 'everydaygig';
    if ( preg_match( '/\b(750|stream|work|2-3|couple)\b/', $msg ) ) return 'streamwork';
    if ( preg_match( '/\b(250|essential|starter|basic|1-2|light)\b/', $msg ) ) return 'essentialconnect';
    return '';
}

function fiberco_recommend_plan( $type, $usage ) {
    $u = strtolower( $usage );
    if ( $type === 'business' ) {
        if ( preg_match( '/\b(large|heavy|data|many|enterprise|20|25|50|office)\b/', $u ) ) return 'fastgig';
        if ( preg_match( '/\b(video|conference|files|team|10|15)\b/', $u ) ) return 'fast400';
        return 'fast250';
    }
    if ( preg_match( '/\b(10\s*g|best|max|maximum|highest|fastest|top speed|premium|power|creator|many|6\+|heavy gaming|heavy streaming)\b/', $u ) ) return 'quantummax';
    if ( preg_match( '/\b(6\+|large|gaming|streaming|work from home|smart home|heavy)\b/', $u ) ) return 'velocitypro';
    if ( preg_match( '/\b(3-5|3 to 5|family|multiple|normal|everyday)\b/', $u ) ) return 'everydaygig';
    if ( preg_match( '/\b(2-3|2 to 3|stream|work|couple)\b/', $u ) ) return 'streamwork';
    return 'essentialconnect';
}

function fiberco_plan_label( $plan ) {
    $labels = array(
        'quantummax'       => 'Quantum Max 10 Gig',
        'velocitypro'      => 'Velocity Pro 5 Gig',
        'everydaygig'      => 'Everyday Gig',
        'streamwork'       => 'Stream and Work 750',
        'essentialconnect' => 'Essential Connect 250',
        'fastgig'          => 'Fast Gig Business',
        'fast400'          => 'Fast 400 Business',
        'fast250'          => 'Fast 250 Business',
    );
    return $labels[ $plan ] ?? 'recommended plan';
}

function fiberco_parse_service_type( $message ) {
    $message = is_scalar( $message ) ? (string) $message : '';

    // Treat customer language like "home internet service" as residential and do not ask again.
    if ( preg_match( '/\b(home\s+(?:internet|service|wifi|wi-fi|fiber)|for\s+my\s+home|my\s+home|residential|house|personal|family|apartment|condo)\b/i', $message ) ) return 'residential';

    // Avoid classifying "work from home" as business.
    if ( preg_match( '/\b(work\s+from\s+home|wfh)\b/i', $message ) ) return 'residential';

    if ( preg_match( '/\b(business|commercial|office|company|shop|store|small\s+business|business\s+internet)\b/i', $message ) ) return 'business';

    if ( preg_match( '/\b(home|residential|house|apartment|condo)\b/i', $message ) ) return 'residential';
    return '';
}

function fiberco_is_full_address( $address ) {
    return fiberco_is_usable_address( $address );
}

function fiberco_merge_address_parts( $existing, $message ) {
    $existing = trim( (string) $existing );
    $message  = trim( (string) $message );
    if ( $existing && ! fiberco_is_usable_address( $existing ) && fiberco_address_has_zip( $message ) ) {
        return trim( preg_replace( '/\s+/', ' ', $existing . ' ' . $message ) );
    }
    return $message;
}

function fiberco_quote_intake_key( $session_id ) {
    return 'fiberco_quote_intake_' . md5( $session_id ?: 'guest' );
}

function fiberco_get_quote_intake( $session_id ) {
    $state = get_transient( fiberco_quote_intake_key( $session_id ) );
    return is_array( $state ) ? $state : array();
}

function fiberco_save_quote_intake( $session_id, $state ) {
    set_transient( fiberco_quote_intake_key( $session_id ), $state, 2 * HOUR_IN_SECONDS );
}

function fiberco_clear_quote_intake( $session_id ) {
    delete_transient( fiberco_quote_intake_key( $session_id ) );
}


function fiberco_extract_name( $message ) {
    $message = is_scalar( $message ) ? trim( (string) $message ) : '';
    if ( $message === '' ) return '';
    $clean = trim( wp_strip_all_tags( $message ) );

    // Explicit forms: "my name is Richard Brashear", "name is Richard", "I'm Richard".
    // Stop before email/phone/address language so we don't capture "and my".
    if ( preg_match( '/\b(?:my\s+name\s+is|name\s+is|i\s+am|i\'m)\s+([a-z][a-z\' -]{1,60}?)(?=\s+(?:and\s+)?(?:my\s+)?(?:email|e-mail|phone|number|address)\b|\s+at\s+\d|\.|,|$)/i', $clean, $m ) ) {
        $name = trim( preg_replace( '/\s+/', ' ', $m[1] ) );
        $name = preg_replace( '/\s+and\s+my\s*$/i', '', $name );
        $name = preg_replace( '/\s+and\s*$/i', '', $name );
        $name = trim( $name );
        if ( strlen( $name ) >= 2 && ! preg_match( '/\b(want|need|buy|get|internet|service|highest|fastest|speed|email|phone|address)\b/i', $name ) ) return $name;
    }

    return '';
}

function fiberco_extract_email( $message ) {
    return preg_match( '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $message, $m ) ? $m[0] : '';
}

function fiberco_extract_phone( $message ) {
    if ( preg_match( '/(?:\+?1[\s.-]?)?(?:\(?\d{3}\)?[\s.-]?)\d{3}[\s.-]?\d{4}/', $message, $m ) ) return $m[0];
    return '';
}


function fiberco_finish_order_response( $session_id, $state ) {
    $button = fiberco_quote_bridge_button(
        'Review and Finish Secure Checkout',
        $state['type'] ?? 'residential',
        $state['plan'] ?? '',
        $state['address'] ?? '',
        array(
            'name'       => $state['name'] ?? '',
            'email'      => $state['email'] ?? '',
            'phone'      => $state['phone'] ?? '',
            'protection' => ! empty( $state['protection'] ),
        )
    );

    $summary  = 'Perfect — I have everything I need.<br><br>';
    if ( ! empty( $state['address'] ) ) $summary .= '📍 ' . esc_html( $state['address'] ) . '<br>';
    if ( ! empty( $state['name'] ) )    $summary .= '👤 ' . esc_html( $state['name'] ) . '<br>';
    if ( ! empty( $state['email'] ) )   $summary .= '✉️ ' . esc_html( $state['email'] ) . '<br>';
    if ( ! empty( $state['phone'] ) )   $summary .= '📞 ' . esc_html( $state['phone'] ) . '<br>';
    if ( ! empty( $state['plan'] ) )    $summary .= '<br>Plan: <strong>' . esc_html( fiberco_plan_label( $state['plan'] ) ) . '</strong>';
    if ( ! empty( $state['protection'] ) ) $summary .= '<br>Priority Care: <strong>Added</strong>';
    $summary .= '<br><br>Your order is ready. Continue to secure checkout to review and finish.';

    fiberco_clear_quote_intake( $session_id );
    return array( 'message' => $summary . $button, 'quick_replies' => array( 'Start over', 'Talk to a person' ), 'was_fallback' => false );
}

function fiberco_plan_card_html( $plan ) {
    $details = array(
        'quantummax'       => array( 'speed' => '10 Gbps',  'price' => '$129.95/mo', 'desc' => 'Max speed for power users.' ),
        'velocitypro'      => array( 'speed' => '5 Gbps',   'price' => '$109.95/mo', 'desc' => 'Best for busy homes, gaming, and work-from-home.' ),
        'everydaygig'      => array( 'speed' => '1 Gbps',   'price' => '$89.95/mo',  'desc' => 'Great all-around family plan.' ),
        'streamwork'       => array( 'speed' => '750 Mbps', 'price' => '$69.95/mo',  'desc' => 'Strong streaming and remote work.' ),
        'essentialconnect' => array( 'speed' => '250 Mbps', 'price' => '$49.95/mo',  'desc' => 'Simple, reliable everyday internet.' ),
        'fastgig'          => array( 'speed' => '1 Gbps',   'price' => '$249.95/mo', 'desc' => 'For larger teams and heavy business use.' ),
        'fast400'          => array( 'speed' => '400 Mbps', 'price' => '$149.95/mo', 'desc' => 'For video meetings and file transfers.' ),
        'fast250'          => array( 'speed' => '250 Mbps', 'price' => '$79.95/mo',  'desc' => 'For small offices and light business use.' ),
    );
    $d = $details[ $plan ] ?? array( 'speed' => '', 'price' => '', 'desc' => '' );
    return "<div class='fiberco-chat-plan-card'>" .
        "<div class='fiberco-chat-plan-title'>⚡ " . esc_html( fiberco_plan_label( $plan ) ) . "</div>" .
        "<div class='fiberco-chat-plan-meta'><strong>" . esc_html( $d['speed'] ) . "</strong> · " . esc_html( $d['price'] ) . "</div>" .
        "<div class='fiberco-chat-plan-desc'>" . esc_html( $d['desc'] ) . "</div>" .
    "</div>";
}

function fiberco_next_faster_plan( $plan ) {
    $map = array(
        'essentialconnect' => 'streamwork',
        'streamwork'       => 'everydaygig',
        'everydaygig'      => 'velocitypro',
        'velocitypro'      => 'quantummax',
        'fast250'          => 'fast400',
        'fast400'          => 'fastgig',
    );
    return $map[ $plan ] ?? $plan;
}

function fiberco_guided_quote_response( $message, $session_id ) {
    $msg = strtolower( trim( is_scalar( $message ) ? (string) $message : '' ) );
    $state = fiberco_get_quote_intake( $session_id );

    $address = fiberco_extract_possible_address( $message );
    $type = fiberco_parse_service_type( $message );
    $plan_in_message = fiberco_guess_plan_from_message( $message );
    $email_in_message = fiberco_extract_email( $message );
    $phone_in_message = fiberco_extract_phone( $message );
    $name_in_message  = fiberco_extract_name( $message );
    $is_quote_intent = preg_match( '/\b(quote|order|available|availability|check address|sign up|new customer|new service|start service|buy|purchase|get started|start quote|internet service|buy it here|set up internet|want internet|get internet|new internet|moved|moving|just moved|new house|new home|gaming internet|gaming|streaming)\b/i', (string) $message );

    if ( empty( $state ) && ! $is_quote_intent ) return null;

    // Support/outage intent takes PRIORITY over the sales flow: a customer reporting
    // a problem should get help, not an upsell. If they raise a service issue (and
    // aren't clearly trying to buy/upgrade), drop out of the guided quote entirely so
    // the support/keyword handler responds — never pitch Priority Care to them.
    if ( ! $is_quote_intent && preg_match( '/\b(down|not working|no internet|no connection|offline|outage|can\'?t connect|cannot connect|no service|internet out|slow|buffering|lag|dropping|disconnect(?:ed|ing)?|not connecting|won\'?t connect|no signal|keeps dropping|troubleshoot)\b/i', $msg ) ) {
        if ( ! empty( $state ) ) { fiberco_clear_quote_intake( $session_id ); }
        return null;
    }

    if ( in_array( $msg, array( 'start over', 'restart', 'reset' ), true ) ) {
        fiberco_clear_quote_intake( $session_id );
        fiberco_save_quote_intake( $session_id, array( 'step' => 'ask_type' ) );
        return array(
            'message'       => 'Sure — is this for a home or business?',
            'quick_replies' => array( 'Home', 'Business' ),
            'was_fallback'  => false,
        );
    }

    // Start a guided order and keep anything the customer already gave us.
    if ( empty( $state ) ) {
        $state = array(
            'step'    => 'ask_type',
            'type'    => $type,
            'address' => fiberco_normalize_address_for_wizard( $address ),
            'plan'    => $plan_in_message,
            'name'    => $name_in_message,
            'email'   => $email_in_message,
            'phone'   => $phone_in_message,
        );

        // If they said they moved / new home, treat as residential unless they clearly said business.
        if ( empty( $state['type'] ) && preg_match( '/\b(moved|moving|new home|new house|apartment|house|home)\b/i', (string) $message ) ) {
            $state['type'] = 'residential';
        }

        if ( ! empty( $state['type'] ) && ! empty( $state['address'] ) && fiberco_is_usable_address( $state['address'] ) ) {
            if ( ! empty( $state['plan'] ) ) {
                $state['step'] = 'confirm_plan';
                fiberco_save_quote_intake( $session_id, $state );
                $known = '<br><br><strong>I have:</strong><br>📍 ' . esc_html( $state['address'] );
                if ( ! empty( $state['name'] ) ) $known .= '<br>👤 ' . esc_html( $state['name'] );
                if ( ! empty( $state['email'] ) ) $known .= '<br>✉️ ' . esc_html( $state['email'] );
                if ( ! empty( $state['phone'] ) ) $known .= '<br>📞 ' . esc_html( $state['phone'] );
                return array(
                    'message'       => 'Perfect — I can build this from what you already gave me.' . $known . '<br><br>Since you asked for this service, I recommend:' . fiberco_plan_card_html( $state['plan'] ),
                    'quick_replies' => array( 'Select this plan', 'Show faster option', 'Start over' ),
                    'was_fallback'  => false,
                );
            }
            $state['step'] = 'ask_usage';
            fiberco_save_quote_intake( $session_id, $state );
            return array(
                'message'       => 'Perfect — I have the full address. How will you mostly use the internet?',
                'quick_replies' => array( '1-2 light use', '3-5 family use', '6+ gaming/streaming' ),
                'was_fallback'  => false,
            );
        }

        if ( ! empty( $state['type'] ) && ! empty( $state['address'] ) && ! fiberco_is_usable_address( $state['address'] ) ) {
            $state['step'] = 'complete_address';
            fiberco_save_quote_intake( $session_id, $state );
            return array(
                'message'       => 'Got it — I have <strong>' . esc_html( $state['address'] ) . '</strong>.<br><br>What city, state, and ZIP should I use?',
                'quick_replies' => array( 'Start over' ),
                'was_fallback'  => false,
            );
        }

        if ( ! empty( $state['address'] ) && ! fiberco_is_usable_address( $state['address'] ) ) {
            $state['step'] = 'ask_type_then_complete_address';
            fiberco_save_quote_intake( $session_id, $state );
            return array(
                'message'       => 'I can start this here. Is it for a home or business?',
                'quick_replies' => array( 'Home', 'Business' ),
                'was_fallback'  => false,
            );
        }

        // If we already know home/business, do not ask again. Move to address collection.
        if ( ! empty( $state['type'] ) ) {
            $state['step'] = 'ask_address';
            fiberco_save_quote_intake( $session_id, $state );
            return array(
                'message'       => ( $state['type'] === 'business' ? 'Great — I can start the business order here.' : 'Great — I can start the home order here.' ) . '<br><br>What address should I check?<br><span class="fiberco-chat-hint">Include city, state, and ZIP.</span>',
                'quick_replies' => array( 'Start over' ),
                'was_fallback'  => false,
            );
        }

        fiberco_save_quote_intake( $session_id, $state );
        return array(
            'message'       => 'Absolutely — I can start this here. Is it for a home or business?',
            'quick_replies' => array( 'Home', 'Business' ),
            'was_fallback'  => false,
        );
    }

    // Always capture new info when the user provides it.
    if ( ! empty( $address ) ) $state['address'] = sanitize_text_field( fiberco_normalize_address_for_wizard( $address ) );
    if ( ! empty( $plan_in_message ) ) $state['plan'] = sanitize_key( $plan_in_message );
    if ( ! empty( $type ) ) $state['type'] = sanitize_key( $type );
    if ( ! empty( $name_in_message ) ) $state['name'] = sanitize_text_field( $name_in_message );
    if ( ! empty( $email_in_message ) && is_email( $email_in_message ) ) $state['email'] = sanitize_email( $email_in_message );
    if ( ! empty( $phone_in_message ) ) $state['phone'] = sanitize_text_field( $phone_in_message );

    if ( ( $state['step'] ?? '' ) === 'ask_type' || ( $state['step'] ?? '' ) === 'ask_type_then_complete_address' ) {
        if ( empty( $state['type'] ) ) {
            if ( preg_match( '/\b(home|house|residential|personal|apartment|condo)\b/i', (string) $message ) ) $state['type'] = 'residential';
            if ( preg_match( '/\b(business|office|company|commercial|store|shop)\b/i', (string) $message ) ) $state['type'] = 'business';
        }
        if ( empty( $state['type'] ) ) {
            fiberco_save_quote_intake( $session_id, $state );
            return array( 'message' => 'Home or business?', 'quick_replies' => array( 'Home', 'Business' ), 'was_fallback' => false );
        }

        if ( ! empty( $state['address'] ) && fiberco_is_usable_address( $state['address'] ) ) {
            if ( ! empty( $state['plan'] ) ) {
                $state['step'] = 'confirm_plan';
                fiberco_save_quote_intake( $session_id, $state );
                return array(
                    'message'       => 'Perfect — I already have the address. I recommend this plan:' . fiberco_plan_card_html( $state['plan'] ),
                    'quick_replies' => array( 'Select this plan', 'Show faster option', 'Start over' ),
                    'was_fallback'  => false,
                );
            }
            $state['step'] = 'ask_usage';
            fiberco_save_quote_intake( $session_id, $state );
            return array(
                'message'       => 'Perfect — I already have the address. How will you mostly use the internet?',
                'quick_replies' => array( '1-2 light use', '3-5 family use', '6+ gaming/streaming' ),
                'was_fallback'  => false,
            );
        }

        if ( ! empty( $state['address'] ) ) {
            $state['step'] = 'complete_address';
            fiberco_save_quote_intake( $session_id, $state );
            return array(
                'message'       => 'Got it — I have <strong>' . esc_html( $state['address'] ) . '</strong>.<br><br>What city, state, and ZIP should I use?',
                'quick_replies' => array( 'Start over' ),
                'was_fallback'  => false,
            );
        }

        $state['step'] = 'ask_address';
        fiberco_save_quote_intake( $session_id, $state );
        return array(
            'message'       => 'Great. What address should I check?<br><span class="fiberco-chat-hint">Include city, state, and ZIP.</span>',
            'quick_replies' => array( 'Start over' ),
            'was_fallback'  => false,
        );
    }

    if ( ( $state['step'] ?? '' ) === 'complete_address' ) {
        $merged = fiberco_merge_address_parts( $state['address'] ?? '', (string) $message );
        if ( ! fiberco_is_usable_address( $merged ) ) {
            fiberco_save_quote_intake( $session_id, $state );
            return array(
                'message'       => 'Please send the city, state, and ZIP. Example: <strong>Rock Hill, SC 29732</strong>',
                'quick_replies' => array( 'Start over' ),
                'was_fallback'  => false,
            );
        }
        $state['address'] = sanitize_text_field( fiberco_normalize_address_for_wizard( $merged ) );
        $state['step'] = ! empty( $state['plan'] ) ? 'confirm_plan' : 'ask_usage';
        fiberco_save_quote_intake( $session_id, $state );
        if ( ! empty( $state['plan'] ) ) {
            return array(
                'message'       => 'Perfect — I have the full address. I recommend this plan:' . fiberco_plan_card_html( $state['plan'] ),
                'quick_replies' => array( 'Select this plan', 'Show faster option', 'Start over' ),
                'was_fallback'  => false,
            );
        }
        return array(
            'message'       => 'Great — I can build the order from here. How will you use it?',
            'quick_replies' => array( '1-2 light use', '3-5 family use', '6+ gaming/streaming' ),
            'was_fallback'  => false,
        );
    }

    if ( ( $state['step'] ?? '' ) === 'ask_address' ) {
        $candidate = $address ?: trim( (string) $message );
        if ( ! fiberco_is_usable_address( $candidate ) ) {
            $state['address'] = sanitize_text_field( fiberco_normalize_address_for_wizard( $candidate ) );
            $state['step'] = 'complete_address';
            fiberco_save_quote_intake( $session_id, $state );
            return array(
                'message'       => 'Got it — I have <strong>' . esc_html( $candidate ) . '</strong>.<br><br>What city, state, and ZIP should I use?',
                'quick_replies' => array( 'Start over' ),
                'was_fallback'  => false,
            );
        }
        $state['address'] = sanitize_text_field( fiberco_normalize_address_for_wizard( $candidate ) );
        $state['step'] = ! empty( $state['plan'] ) ? 'confirm_plan' : 'ask_usage';
        fiberco_save_quote_intake( $session_id, $state );
        if ( ! empty( $state['plan'] ) ) {
            return array(
                'message'       => 'Perfect — I have the address. I recommend this plan:' . fiberco_plan_card_html( $state['plan'] ),
                'quick_replies' => array( 'Select this plan', 'Show faster option', 'Start over' ),
                'was_fallback'  => false,
            );
        }
        return array(
            'message'       => 'Great — I can build the order from here. How will you use it?',
            'quick_replies' => array( '1-2 light use', '3-5 family use', '6+ gaming/streaming' ),
            'was_fallback'  => false,
        );
    }

    if ( ( $state['step'] ?? '' ) === 'ask_usage' ) {
        $plan = ! empty( $state['plan'] ) ? $state['plan'] : fiberco_recommend_plan( $state['type'] ?? 'residential', (string) $message );
        $state['usage'] = sanitize_text_field( $message );
        $state['plan'] = $plan;
        $state['step'] = 'confirm_plan';
        fiberco_save_quote_intake( $session_id, $state );
        return array(
            'message'       => 'I recommend this plan:' . fiberco_plan_card_html( $plan ),
            'quick_replies' => array( 'Select this plan', 'Show faster option', 'Start over' ),
            'was_fallback'  => false,
        );
    }

    if ( ( $state['step'] ?? '' ) === 'confirm_plan' ) {
        if ( preg_match( '/faster|upgrade|more|higher|better/i', (string) $message ) ) {
            $state['plan'] = fiberco_next_faster_plan( $state['plan'] ?? 'essentialconnect' );
            fiberco_save_quote_intake( $session_id, $state );
            return array(
                'message'       => 'Here’s the next step up:' . fiberco_plan_card_html( $state['plan'] ),
                'quick_replies' => array( 'Select this plan', 'Show faster option', 'Start over' ),
                'was_fallback'  => false,
            );
        }
        $state['step'] = 'ask_protection';
        fiberco_save_quote_intake( $session_id, $state );
        return array(
            'message'       => 'Want to add <strong>Priority Care Protection</strong> for $9.99/mo?<br><span class="fiberco-chat-hint">Priority dispatch, equipment replacement, and no service call fees.</span>',
            'quick_replies' => array( 'Add protection', 'No thanks' ),
            'was_fallback'  => false,
        );
    }

    if ( ( $state['step'] ?? '' ) === 'ask_protection' ) {
        $state['protection'] = preg_match( '/add|yes|protect|priority/i', (string) $message ) ? 1 : 0;

        if ( ! empty( $state['name'] ) && ! empty( $state['email'] ) && ! empty( $state['phone'] ) ) {
            return fiberco_finish_order_response( $session_id, $state );
        }

        if ( ! empty( $state['name'] ) && ! empty( $state['email'] ) ) {
            $state['step'] = 'ask_phone';
            fiberco_save_quote_intake( $session_id, $state );
            return array( 'message' => 'Great — I have your name and email.<br><br>📞 What phone number should our installation team use?', 'quick_replies' => array( 'Start over' ), 'was_fallback' => false );
        }
        if ( ! empty( $state['name'] ) ) {
            $state['step'] = 'ask_email';
            fiberco_save_quote_intake( $session_id, $state );
            return array( 'message' => 'Great — I have your name.<br><br>✉️ What email should I use for the confirmation?', 'quick_replies' => array( 'Start over' ), 'was_fallback' => false );
        }
        $state['step'] = 'ask_name';
        fiberco_save_quote_intake( $session_id, $state );
        return array( 'message' => 'Perfect. Who should I put on the order?', 'quick_replies' => array( 'Start over' ), 'was_fallback' => false );
    }

    if ( ( $state['step'] ?? '' ) === 'ask_name' ) {
        if ( ! empty( $state['name'] ) ) {
            if ( ! empty( $state['email'] ) && ! empty( $state['phone'] ) ) {
                return fiberco_finish_order_response( $session_id, $state );
            }
            if ( ! empty( $state['email'] ) ) {
                $state['step'] = 'ask_phone';
                fiberco_save_quote_intake( $session_id, $state );
                return array( 'message' => 'Great — I have your name and email.<br><br>📞 What phone number should our installation team use?', 'quick_replies' => array( 'Start over' ), 'was_fallback' => false );
            }
            $state['step'] = 'ask_email';
            fiberco_save_quote_intake( $session_id, $state );
            return array( 'message' => 'Great — I have your name.<br><br>✉️ What email should I use for the confirmation?', 'quick_replies' => array( 'Start over' ), 'was_fallback' => false );
        }
        $name = $name_in_message ?: trim( wp_strip_all_tags( (string) $message ) );
        if ( strlen( $name ) < 2 || preg_match( '/already\s+gave/i', (string) $message ) ) return array( 'message' => 'I missed the name earlier — what name should I use?', 'quick_replies' => array( 'Start over' ), 'was_fallback' => false );
        $state['name'] = sanitize_text_field( $name );
        if ( ! empty( $state['email'] ) && ! empty( $state['phone'] ) ) {
            fiberco_save_quote_intake( $session_id, $state );
            return fiberco_finish_order_response( $session_id, $state );
        }
        $state['step'] = ! empty( $state['email'] ) ? 'ask_phone' : 'ask_email';
        fiberco_save_quote_intake( $session_id, $state );
        if ( ! empty( $state['email'] ) ) return array( 'message' => 'Perfect — I already have your email.<br><br>📞 What phone number should our installation team use?', 'quick_replies' => array( 'Start over' ), 'was_fallback' => false );
        return array( 'message' => 'Thanks — I have your name.<br><br>✉️ What email should I use for the confirmation?', 'quick_replies' => array( 'Start over' ), 'was_fallback' => false );
    }

    if ( ( $state['step'] ?? '' ) === 'ask_email' ) {
        if ( ! empty( $state['email'] ) && is_email( $state['email'] ) ) {
            if ( ! empty( $state['phone'] ) ) {
                return fiberco_finish_order_response( $session_id, $state );
            }
            $state['step'] = 'ask_phone';
            fiberco_save_quote_intake( $session_id, $state );
            return array( 'message' => 'Perfect — I already have your email: <strong>' . esc_html( $state['email'] ) . '</strong>.<br><br>📞 What phone number should our installation team use?', 'quick_replies' => array( 'Start over' ), 'was_fallback' => false );
        }
        $email = $email_in_message ?: fiberco_extract_email( $message );
        if ( ! is_email( $email ) ) return array( 'message' => 'Please enter a valid email address for the confirmation.', 'quick_replies' => array( 'Start over' ), 'was_fallback' => false );
        $state['email'] = sanitize_email( $email );
        if ( ! empty( $state['phone'] ) ) {
            fiberco_save_quote_intake( $session_id, $state );
            return fiberco_finish_order_response( $session_id, $state );
        }
        $state['step'] = 'ask_phone';
        fiberco_save_quote_intake( $session_id, $state );
        return array( 'message' => 'Great — email saved.<br><br>📞 What phone number should our installation team use?', 'quick_replies' => array( 'Start over' ), 'was_fallback' => false );
    }

    if ( ( $state['step'] ?? '' ) === 'ask_phone' ) {
        if ( ! empty( $state['phone'] ) ) {
            return fiberco_finish_order_response( $session_id, $state );
        }
        $phone = fiberco_extract_phone( $message );
        if ( ! $phone ) return array( 'message' => 'Please enter a valid phone number for installation updates.', 'quick_replies' => array( 'Start over' ), 'was_fallback' => false );
        $state['phone'] = sanitize_text_field( $phone );
        return fiberco_finish_order_response( $session_id, $state );
    }

    fiberco_clear_quote_intake( $session_id );
    return null;
}

function fiberco_get_response( $message, $history = array(), $session_id = '', $failed_attempts = 0 ) {
    try {
        $guided_quote = fiberco_guided_quote_response( $message, $session_id );
        if ( is_array( $guided_quote ) ) return $guided_quote;
    } catch ( Throwable $e ) {
        // Never let intake parsing break the chatbot. Fall through to normal responses.
    }

    if ( $failed_attempts >= 2 ) {
        return array(
            'message'       => "<strong>Let me connect you with a real person.</strong> Our team is better equipped to help.<br><br>📞 <strong>Phone:</strong> 1-800-FIBERCO (Mon–Fri 8am–8pm, Sat 9am–5pm)<br>💬 <strong>Live Chat:</strong> <a href='https://fiberco.com/support' target='_blank'>fiberco.com/support</a>",
            'quick_replies' => array( 'Open live chat', 'Call 1-800-FIBERCO', 'Try a different question' ),
            'escalated'     => true,
            'was_fallback'  => false,
        );
    }
    $api_key = defined( 'FIBERCO_AI_API_KEY' ) ? FIBERCO_AI_API_KEY : get_option( 'fiberco_ai_api_key', '' );
    if ( ! empty( $api_key ) ) {
        return fiberco_claude_api_response( $message, $history, $api_key );
    }
    return fiberco_keyword_response( $message, $failed_attempts );
}

function fiberco_claude_api_response( $message, $history, $api_key ) {

    $system_prompt = 'You are Nova, the friendly AI support assistant for FiberCo, a fiber internet provider. You help with tech support, billing, plan information, and new service quotes.

SUPPORT COMES FIRST — NEVER UPSELL A PROBLEM: If a customer reports an outage, a connection that is down, slow speeds, or any technical/service problem, focus ENTIRELY on helping them troubleshoot or escalating to a technician. Do NOT pitch plans, upgrades, add-ons, or Priority Care Protection to a customer who has a service issue — it comes across as tone-deaf. Only discuss sales when the customer is clearly shopping for or ordering service.

RESIDENTIAL PLANS (all symmetrical, no data caps, no contracts, includes RocketRouter Wi-Fi 7, 24/7 support, SecureShield Protection, custom installation, FiberCo Mobile App):
- Quantum Max 10 Gig: $129.95/mo, 10 Gbps — ultimate speed for power users, Wall-to-Wall Indoor Wi-Fi, FamilyZone parental controls
- Velocity Pro 5 Gig: $109.95/mo, 5 Gbps — premium speed for busy homes, FamilyZone parental controls
- Everyday Gig: $89.95/mo, 1 Gbps — fast fiber for everyday life, Wall-to-Wall Indoor Wi-Fi, FamilyZone parental controls
- Stream and Work 750: $69.95/mo, 750 Mbps — reliable streaming and work speed, device prioritization and FamilyZone controls
- Essential Connect 250: $49.95/mo, 250 Mbps — simple, secure and essential

BUSINESS PLANS (symmetrical speeds, 24/7 support, Wi-Fi 6 router, optional static IP):
- Fast Gig Business: $249.95/mo, 1 Gbps — ideal for heavy use, large teams, data-intensive tasks
- Fast 400 Business: $149.95/mo, 400 Mbps — video conferencing, large file transfers, multiple devices
- Fast 250 Business: $79.95/mo, 250 Mbps — video calls, streaming, small business use

ORDER FLOW:
When someone wants availability, a quote, internet service, or to order, do NOT tell them to go use another page or an internal tool. Say you can start the process here, collect the basics, build the order, and then take them to secure checkout/review to finish. Never collect payment card details in chat.

KEY LINKS (always open in new tab with target="_blank"):
- Quote/availability: https://startwebservicesbackup.com/fiber/
- Account/billing portal: https://fiberco.com/account
- Outage map: https://fiberco.com/outages
- Support/schedule: https://fiberco.com/support
- Phone: 1-800-FIBERCO (Mon-Fri 8am-8pm, Sat 9am-5pm)

RESPONSE RULES:
1. Keep responses very short. Use 1-3 short paragraphs with <br><br> breaks.
2. Prefer buttons/quick replies over long explanations.
3. Never mention internal tool names to the customer.
4. Never say "go to the page" or "use the quote tool." Say "I can start that here" and "finish in secure checkout."
5. Never collect payment card details in chat.
6. Never invent pricing or guarantee final availability at a specific address.
7. Route cancellations and billing disputes to a human agent.
8. Use HTML for formatting: <strong>, <br>, short bullets only if needed.
9. After your response, output a JSON block on its own line in this exact format:
   QUICK_REPLIES:["option1","option2","option3"]
10. Be warm, helpful, and conversational. You represent FiberCo professionally.';

    $messages = array();
    foreach ( $history as $turn ) {
        $messages[] = array( 'role' => $turn['role'], 'content' => $turn['content'] );
    }
    $messages[] = array( 'role' => 'user', 'content' => $message );

    $request_body = wp_json_encode( array(
        'model'      => 'claude-sonnet-4-5-20250929',
        'max_tokens' => 320,
        'system'     => $system_prompt,
        'messages'   => $messages,
    ) );

    $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
        'timeout' => 20,
        'headers' => array(
            'x-api-key'         => $api_key,
            'anthropic-version' => '2023-06-01',
            'Content-Type'      => 'application/json',
        ),
        'body' => $request_body,
    ) );

    if ( is_wp_error( $response ) ) {
        return array(
            'message'       => 'I\'m having trouble connecting. Please call <strong>1-800-FIBERCO</strong> or try again.',
            'was_fallback'  => true,
            'quick_replies' => array( 'Call 1-800-FIBERCO', 'Try again' ),
        );
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = wp_remote_retrieve_body( $response );

    if ( $response_code !== 200 ) {
        return array(
            'message'       => 'I\'m having trouble connecting. Please call <strong>1-800-FIBERCO</strong> or try again.',
            'was_fallback'  => true,
            'quick_replies' => array( 'Call 1-800-FIBERCO', 'Try again' ),
        );
    }

    $data      = json_decode( $response_body, true );
    $full_text = $data['content'][0]['text'] ?? '';

    $quick_replies = array( 'Tech support', 'View plans', 'Billing help' );
    $clean_message = $full_text;

    if ( preg_match( '/QUICK_REPLIES:\s*(\[.*?\])/s', $full_text, $matches ) ) {
        $parsed = json_decode( $matches[1], true );
        if ( is_array( $parsed ) ) $quick_replies = $parsed;
        $clean_message = trim( preg_replace( '/QUICK_REPLIES:\s*\[.*?\]/s', '', $full_text ) );
    }

    return array(
        'message'       => $clean_message,
        'quick_replies' => $quick_replies,
        'was_fallback'  => false,
        'escalated'     => false,
    );
}

function fiberco_keyword_response( $message, $failed_attempts = 0 ) {
    $msg = strtolower( $message );

    if ( preg_match( '/\b(hi|hello|hey|howdy|good morning|good afternoon|good evening|sup|yo)\b/', $msg ) ) {
        return array(
            'message'       => "Hey there! 👋 I’m <strong>Nova</strong>. I can help with service, support, billing, or a new order. What do you need?",
            'quick_replies' => array( 'View residential plans', 'View business plans', 'Start order' ),
            'was_fallback'  => false,
        );
    }

    if ( preg_match( '/\b(thanks|thank you|appreciate|thx|ty)\b/', $msg ) ) {
        return array(
            'message'       => "You're welcome! Is there anything else I can help you with? 😊",
            'quick_replies' => array( 'Tech support', 'Billing help', 'Talk to a person' ),
            'was_fallback'  => false,
        );
    }

    if ( preg_match( '/\b(down|not working|no internet|no connection|offline|outage|can\'?t connect|no service|internet out)\b/', $msg ) ) {
        return array(
            'message'       => "<strong>Sorry to hear your connection is down!</strong> Let's fix that:<br><br><strong>1.</strong> Check your router lights — green = good, red/amber = problem.<br><strong>2.</strong> Unplug your router, wait 30 seconds, plug back in.<br><strong>3.</strong> Check our <a href='https://fiberco.com/outages' target='_blank'>Outage Map</a> for known issues.<br><strong>4.</strong> Still down? Call <strong>1-800-FIBERCO</strong> and we'll send a tech.",
            'quick_replies' => array( 'Check outage map', 'Router light colors', 'Talk to a person' ),
            'was_fallback'  => false,
        );
    }

    if ( preg_match( '/\b(slow|speed|buffering|lagging|lag|mbps|speedtest|loading slow)\b/', $msg ) ) {
        return array(
            'message'       => "<strong>Slow speeds — let's troubleshoot!</strong><br><br><strong>1.</strong> Run a speed test at <a href='https://fast.com' target='_blank'>fast.com</a>.<br><strong>2.</strong> WiFi or wired? Wired is always faster.<br><strong>3.</strong> Restart your router (unplug 30 sec).<br><strong>4.</strong> Still slow? We'll send a tech at no charge.",
            'quick_replies' => array( 'Run a speed test', 'WiFi issues', 'Upgrade my plan' ),
            'was_fallback'  => false,
        );
    }

    if ( preg_match( '/\b(wifi|wi-fi|wireless|signal|password|ssid|keeps dropping|dead zone)\b/', $msg ) ) {
        return array(
            'message'       => "<strong>WiFi troubleshooting:</strong><br><br><strong>1.</strong> Is it all devices or just one?<br><strong>2.</strong> Move closer to the router — does it improve?<br><strong>3.</strong> Restart the router (unplug 30 sec).<br><strong>4.</strong> Dead zones? Our Wall-to-Wall plans include indoor WiFi coverage — ask us!",
            'quick_replies' => array( 'Wall-to-Wall WiFi plans', 'Reset WiFi password', 'Talk to tech support' ),
            'was_fallback'  => false,
        );
    }

    if ( preg_match( '/\b(router|modem|gateway|lights|blinking|reboot|restart router|equipment)\b/', $msg ) ) {
        return array(
            'message'       => "<strong>Router Help:</strong><br><br>• <strong>Restart:</strong> Unplug power, wait 30 sec, plug back in. Allow 2 min to reconnect.<br>• <strong>Lights:</strong> Solid green ✅ | Blinking green ✅ | Red/Amber ❌<br><br>All our plans include a RocketRouter Wi-Fi 7. Need a swap? Call <strong>1-800-FIBERCO</strong>.",
            'quick_replies' => array( 'My lights are red', 'Request equipment swap', 'Talk to a person' ),
            'was_fallback'  => false,
        );
    }


    if ( preg_match( '/\b(quote|order|available|availability|check address|sign up|new customer|new service|start service|buy|purchase|get started)\b/', $msg ) ) {
        $address = fiberco_extract_possible_address( $message );
        $type    = fiberco_parse_service_type( $message );
        $plan    = fiberco_guess_plan_from_message( $message );
        $button  = fiberco_quote_bridge_button( $address ? 'Use this address' : 'Start order', $type, $plan, $address );
        return array(
            'message'       => "Absolutely — I can start this right here. I’ll gather the basics, build the order, and then take you to secure checkout to review and finish." . $button,
            'quick_replies' => array( 'Residential plans', 'Business plans', 'Installation timeline' ),
            'was_fallback'  => false,
        );
    }

    if ( preg_match( '/\b(residential|home|personal|family|plan|plans|price|pricing|cost|how much|package|gig|speed|quantum|velocity|everyday|stream|essential|starter)\b/', $msg ) ) {
        $button = fiberco_quote_bridge_button( 'Start home order', 'residential', fiberco_guess_plan_from_message( $message ), fiberco_extract_possible_address( $message ) );
        return array(
            'message'       => "<strong>Residential plans</strong><br><br>Most homes choose <strong>Everyday Gig</strong> or <strong>Velocity Pro 5 Gig</strong>.<br><br>I can help narrow it down and start your order here." . $button,
            'quick_replies' => array( 'Start order', 'Business plans', 'What is SecureShield?' ),
            'was_fallback'  => false,
        );
    }

    if ( preg_match( '/\b(business|commercial|office|company|fast gig|fast 400|fast 250|static ip|sla|dedicated|enterprise)\b/', $msg ) ) {
        return array(
            'message'       => "<strong>Business plans</strong><br><br>
🚀 <strong>Fast Gig Business</strong> — $249.95/mo | 1 Gbps<br>
⚡ <strong>Fast 400 Business</strong> — $149.95/mo | 400 Mbps<br>
💼 <strong>Fast 250 Business</strong> — $79.95/mo | 250 Mbps<br><br>I can start the business order here.",
            'quick_replies' => array( 'Start business order', 'Static IP details', 'Talk to business team' ),
            'was_fallback'  => false,
        );
    }

    if ( preg_match( '/\b(bill|billing|invoice|charge|statement|due date|autopay|paperless)\b/', $msg ) ) {
        return array(
            'message'       => "<strong>Billing Help:</strong><br><br>• <strong>View your bill:</strong> <a href='https://fiberco.com/account' target='_blank'>fiberco.com/account</a><br>• <strong>Autopay:</strong> Set up in My Account — saves $5/mo!<br>• <strong>Questions?</strong> Call <strong>1-800-FIBERCO</strong> and we'll walk through every line item.",
            'quick_replies' => array( 'Pay my bill', 'Set up autopay', 'Talk to billing team' ),
            'was_fallback'  => false,
        );
    }

    if ( preg_match( '/\b(pay|payment|pay my bill|make a payment|login|log in|my account)\b/', $msg ) ) {
        return array(
            'message'       => "Pay your bill quickly and securely:<br><br><a href='https://fiberco.com/account' target='_blank'><strong>→ Pay My Bill / My Account</strong></a><br><br>Set up <strong>autopay</strong> to save $5/mo and never miss a payment.",
            'quick_replies' => array( 'Set up autopay', 'Billing questions', 'View my plan' ),
            'was_fallback'  => false,
        );
    }

    if ( preg_match( '/\b(cancel|cancellation|disconnect|end service|close account|move|moving|terminate)\b/', $msg ) ) {
        return array(
            'message'       => "We'd hate to lose you! Cancellations need our Customer Care team directly:<br><br>📞 <strong>1-800-FIBERCO</strong> (Mon–Fri 8am–8pm, Sat 9am–5pm)<br>💬 <a href='https://fiberco.com/support' target='_blank'>Live Chat</a><br><br>Moving? We may be able to transfer your service!",
            'quick_replies' => array( 'Transfer my service', 'Talk to a person', 'View retention offers' ),
            'escalated'     => true,
            'was_fallback'  => false,
        );
    }

    if ( preg_match( '/\b(install|installation|schedule|appointment|technician|tech visit)\b/', $msg ) ) {
        return array(
            'message'       => "<strong>Scheduling is easy!</strong><br><br>• <strong>New customers:</strong> I can start the order here and you’ll choose install details before secure checkout.<br>• <strong>Existing customers:</strong> Call <strong>1-800-FIBERCO</strong> or visit <a href='https://fiberco.com/schedule' target='_blank'>fiberco.com/schedule</a>.<br>• Most installs done within <strong>1–3 business days</strong>.",
            'quick_replies' => array( 'Start order', 'Check availability', 'What to expect' ),
            'was_fallback'  => false,
        );
    }

    if ( preg_match( '/\b(agent|human|person|rep|real person|talk to someone|live chat|speak with|operator)\b/', $msg ) ) {
        return array(
            'message'       => "Here's how to reach a <strong>live FiberCo team member</strong>:<br><br>📞 <strong>Phone:</strong> 1-800-FIBERCO (Mon–Fri 8am–8pm, Sat 9am–5pm)<br>💬 <strong>Live Chat:</strong> <a href='https://fiberco.com/support' target='_blank'>fiberco.com/support</a><br><br>Our team typically responds in under 2 minutes on live chat.",
            'quick_replies' => array( 'Open live chat', 'Tech support first', 'Billing questions' ),
            'escalated'     => true,
            'was_fallback'  => false,
        );
    }

    return array(
        'message'       => "I want to make sure I get you the right help! Here's what I can assist with:<br><br>
🏠 <strong>Residential Plans</strong> — 250 Mbps to 10 Gbps starting at $49.95/mo<br>
🏢 <strong>Business Plans</strong> — 250 Mbps to 1 Gbps starting at $79.95/mo<br>
🔧 <strong>Tech Support</strong> — Internet down, slow speeds, WiFi issues<br>
💰 <strong>Billing</strong> — View/pay bill, autopay<br>
🏠 <strong>Get a Quote</strong> — click the Start order button<br>
👤 <strong>Talk to a Person</strong> — 1-800-FIBERCO<br><br>What would you like help with?",
        'quick_replies' => array( 'Residential plans', 'Business plans', 'Start order' ),
        'was_fallback'  => true,
    );
}

// ─────────────────────────────────────────────
// 10. INJECT NONCE + AJAX URL INTO HEAD
// ─────────────────────────────────────────────
function fiberco_inject_chat_config() {
    if ( ! headers_sent() ) { nocache_headers(); }
    echo '<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
<meta http-equiv="Pragma" content="no-cache">
<style>
/* FiberCo chat readability polish */
.fiberco-chat-body,.fiberco-chat-messages,.fc-chat-messages,.chat-messages,#fiberco-chat-messages{
    max-height:520px !important;
    overflow-y:auto !important;
    scroll-behavior:smooth;
    padding-right:6px;
    scrollbar-width:thin;
}
.fiberco-chat-body::-webkit-scrollbar,.fiberco-chat-messages::-webkit-scrollbar,.fc-chat-messages::-webkit-scrollbar{width:7px}
.fiberco-chat-body::-webkit-scrollbar-thumb,.fiberco-chat-messages::-webkit-scrollbar-thumb,.fc-chat-messages::-webkit-scrollbar-thumb{background:#c7d2fe;border-radius:999px}
.fiberco-chat-message,.fc-chat-message,.fiberco-chat-bubble,.fc-chat-bubble{
    line-height:1.55 !important;
    margin-bottom:12px !important;
    max-width:86% !important;
    white-space:normal !important;
}
.fiberco-chat-message p,.fc-chat-message p,.fiberco-chat-bubble p,.fc-chat-bubble p{margin:0 0 10px !important}
.fiberco-chat-input-wrap,.fc-chat-input-wrap{position:sticky;bottom:0;background:#fff;z-index:2}
.fiberco-chat-plan-card{margin:10px 0;padding:13px 15px;border:1px solid #dbeafe;border-radius:14px;background:#f8fbff;box-shadow:0 4px 14px rgba(0,87,255,.08)}
.fiberco-chat-plan-title{font-weight:800;color:#111827;margin-bottom:5px}
.fiberco-chat-plan-meta{font-size:13px;color:#0057ff;margin-bottom:5px}
.fiberco-chat-plan-desc,.fiberco-chat-hint{font-size:12.5px;color:#6b7280;line-height:1.45}
.fiberco-chat-quote-btn{display:inline-flex;align-items:center;justify-content:center;margin-top:10px;background:#0057ff;color:#fff;border:0;border-radius:999px;padding:12px 18px;font-weight:800;font-size:14px;cursor:pointer;box-shadow:0 6px 18px rgba(0,87,255,.28)}
.fiberco-chat-quote-btn:hover{background:#0041cc;transform:translateY(-1px)}

/* v1.2.6 send button + Nova polish */
#fiberco-chat-send,.fiberco-chat-send,.fc-chat-send,.chat-send,button[aria-label="Send"],button[title="Send"]{
    display:inline-flex !important;align-items:center !important;justify-content:center !important;
    min-width:56px !important;width:56px !important;height:56px !important;border-radius:50% !important;
    background:#05aeca !important;color:#fff !important;border:0 !important;font-size:0 !important;line-height:1 !important;
    box-shadow:0 10px 24px rgba(0,87,255,.22) !important;cursor:pointer !important;
}
#fiberco-chat-send::before,.fiberco-chat-send::before,.fc-chat-send::before,.chat-send::before,button[aria-label="Send"]::before,button[title="Send"]::before{
    content:"➜" !important;font-size:24px !important;font-weight:900 !important;color:#fff !important;display:block !important;
}
#fiberco-chat-send svg,.fiberco-chat-send svg,.fc-chat-send svg,.chat-send svg{display:block !important;width:22px !important;height:22px !important;fill:#fff !important;stroke:#fff !important}
</style><script>
window.fibercoChatConfig = {
    ajaxUrl: ' . json_encode( admin_url( 'admin-ajax.php' ) ) . ',
    nonceUrl: ' . json_encode( admin_url( 'admin-ajax.php?action=fiberco_chat_nonce' ) ) . ',
    nonce:   ' . json_encode( wp_create_nonce( 'fiberco_chat_nonce' ) ) . '
};

(function(){
  function fibercoNovaPolish(){
    try{
      document.querySelectorAll("body *").forEach(function(el){
        if(el.children && el.children.length) return;
        if(el.textContent && el.textContent.indexOf("Finn") !== -1){
          el.textContent = el.textContent.replace(/Finn/g,"Nova");
        }
      });
      var send = document.querySelector("#fiberco-chat-send,.fiberco-chat-send,.fc-chat-send,.chat-send,button[aria-label=\"Send\"],button[title=\"Send\"]");
      if(send && !send.querySelector("svg") && !send.textContent.trim()) send.innerHTML = "➜";
    }catch(e){}
  }
  document.addEventListener("DOMContentLoaded", fibercoNovaPolish);
  window.setInterval(fibercoNovaPolish, 1500);
})();

window.fibercoQuoteStartFromChat = window.fibercoQuoteStartFromChat || function(options){
    options = options || {};
    var quoteUrl = "https://startwebservicesbackup.com/fiber/";
    var address = (options.address || "").trim();
    var type = (options.type || "residential").toLowerCase();
    var plan = (options.plan || "").trim();
    var wrap = document.getElementById("fcq-wrap");
    if(!wrap){
        var params = new URLSearchParams();
        params.set("fcq_chat_quote", "1");
        if(address) params.set("address", address);
        if(type) params.set("type", type);
        if(plan) params.set("plan", plan);
        window.location.href = quoteUrl + "?" + params.toString();
        return;
    }
    if(typeof fcqGoTo === "function") fcqGoTo(1);
    var street = document.getElementById("fcq-street");
    var biz = document.getElementById("fcq-is-business");
    if(biz){
        biz.checked = (type === "business");
        if(typeof fcqSetBizType === "function") fcqSetBizType(biz.checked);
    }
    if(street){
        if(address) street.value = address;
        street.focus();
    }
    var target = document.querySelector(".fcq-progress-wrap") || wrap;
    if(target) window.scrollTo({top:target.getBoundingClientRect().top + window.pageYOffset - 24, behavior:"smooth"});
    var hasFullAddr = address.length > 8 && /\d{5}/.test(address) && /\b[A-Z]{2}\b/i.test(address);
    if(hasFullAddr && typeof fcqCheckAddress === "function"){
        fcqCheckAddress();
        window.setTimeout(function(){
            if(typeof fcqGoTo === "function") fcqGoTo(2);
            if(plan && typeof fcqSelectPlan === "function") fcqSelectPlan(plan);
        }, 1800);
    } else if(plan){
        window.setTimeout(function(){
            if(typeof fcqGoTo === "function") fcqGoTo(2);
            if(typeof fcqSelectPlan === "function") fcqSelectPlan(plan);
        }, 300);
    }
};
</script>';
}
add_action( 'wp_head', 'fiberco_inject_chat_config', 5 );

// ─────────────────────────────────────────────
// 11. ADMIN LOG VIEWER
// ─────────────────────────────────────────────
function fiberco_admin_menu() {
    add_menu_page(
        'FiberCo AI Dashboard',
        'FiberCo AI',
        'manage_options',
        'fiberco-chat-logs',
        'fiberco_render_log_page',
        'dashicons-networking',
        26
    );
    add_submenu_page(
        'fiberco-chat-logs',
        'FiberCo AI Dashboard',
        'Dashboard',
        'manage_options',
        'fiberco-chat-logs',
        'fiberco_render_log_page'
    );
    add_submenu_page(
        'fiberco-chat-logs',
        'FiberCo AI Settings',
        'Settings',
        'manage_options',
        'fiberco-ai-settings',
        'fiberco_render_settings_page'
    );
}
add_action( 'admin_menu', 'fiberco_admin_menu' );

function fiberco_handle_csv_export() {
    if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'fiberco-chat-logs' ) return;
    if ( ! isset( $_GET['fiberco_export_csv'] ) ) return;
    if ( ! check_admin_referer( 'fiberco_export_csv_action' ) ) wp_die( 'Security check failed.' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Access denied.' );
    global $wpdb;
    $logs     = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}fiberco_chat_logs ORDER BY created_at DESC", ARRAY_A );
    $filename = 'fiberco-chat-logs-' . gmdate( 'Y-m-d' ) . '.csv';
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=' . $filename );
    header( 'Pragma: no-cache' );
    $output = fopen( 'php://output', 'w' );
    fputcsv( $output, array( 'ID', 'Session ID', 'IP Address', 'User Message', 'Bot Response', 'Escalated', 'Date/Time' ) );
    foreach ( $logs as $log ) {
        fputcsv( $output, array( $log['id'], $log['session_id'], $log['ip_address'], $log['user_message'], $log['bot_response'], $log['escalated'] ? 'Yes' : 'No', $log['created_at'] ) );
    }
    fclose( $output );
    exit;
}
add_action( 'admin_init', 'fiberco_handle_csv_export' );

function fiberco_render_log_page() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Access denied.' );
    global $wpdb;
    $table = $wpdb->prefix . 'fiberco_chat_logs';
    if ( isset( $_POST['fiberco_delete_logs'] ) && check_admin_referer( 'fiberco_delete_logs_action' ) ) {
        $wpdb->query( "TRUNCATE TABLE $table" );
        echo '<div class="notice notice-success"><p>All logs deleted.</p></div>';
    }
    $per_page  = 50;
    $page      = max( 1, isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1 );
    $offset    = ( $page - 1 ) * $per_page;
    $total     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
    $escalated = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE escalated = 1" );
    $today     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE DATE(created_at) = %s", gmdate( 'Y-m-d' ) ) );
    $logs      = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset ) );
    $export_url = wp_nonce_url( add_query_arg( array( 'page' => 'fiberco-chat-logs', 'fiberco_export_csv' => '1' ), admin_url( 'tools.php' ) ), 'fiberco_export_csv_action' );
    ?>
    <div class="wrap">
        <h1>FiberCo Chat Logs</h1>
        <div style="display:flex;gap:16px;margin:16px 0;">
            <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 24px;text-align:center;">
                <div style="font-size:28px;font-weight:600;color:#0073aa;"><?php echo esc_html( $total ); ?></div>
                <div style="font-size:12px;color:#666;">Total conversations</div>
            </div>
            <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 24px;text-align:center;">
                <div style="font-size:28px;font-weight:600;color:#e65054;"><?php echo esc_html( $escalated ); ?></div>
                <div style="font-size:12px;color:#666;">Escalated to human</div>
            </div>
            <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:16px 24px;text-align:center;">
                <div style="font-size:28px;font-weight:600;color:#00a32a;"><?php echo esc_html( $today ); ?></div>
                <div style="font-size:12px;color:#666;">Today's chats</div>
            </div>
        </div>
        <div style="display:flex;gap:8px;margin-bottom:16px;">
            <a href="<?php echo esc_url( $export_url ); ?>" class="button button-primary">⬇ Export CSV</a>
            <form method="post" style="display:inline;">
                <?php wp_nonce_field( 'fiberco_delete_logs_action' ); ?>
                <input type="hidden" name="fiberco_delete_logs" value="1">
                <input type="submit" class="button button-secondary" value="🗑 Delete All Logs" onclick="return confirm('Delete all logs?')">
            </form>
        </div>
        <?php if ( empty( $logs ) ) : ?>
            <p>No chat logs yet.</p>
        <?php else : ?>
            <table class="widefat striped">
                <thead><tr><th>ID</th><th>Session</th><th>IP</th><th>User Message</th><th>Bot Response</th><th>Status</th><th>Escalated</th><th>Date/Time</th></tr></thead>
                <tbody>
                    <?php foreach ( $logs as $log ) : ?>
                    <tr>
                        <td><?php echo esc_html( $log->id ); ?></td>
                        <td style="font-size:11px;"><?php echo esc_html( substr( $log->session_id, 0, 8 ) ); ?>…</td>
                        <td><?php echo esc_html( $log->ip_address ); ?></td>
                        <td><?php echo esc_html( $log->user_message ); ?></td>
                        <td style="max-width:300px;white-space:normal;"><?php echo esc_html( $log->bot_response ); ?></td>
                        <td><?php echo fiberco_review_status_label( $log->user_message . " " . $log->bot_response ); ?></td>
                        <td><?php echo $log->escalated ? '<span style="background:#fcf0f1;color:#c02b2b;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;">Escalated</span>' : '<span style="color:#999;font-size:11px;">—</span>'; ?></td>
                        <td><?php echo esc_html( $log->created_at ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
            $total_pages = ceil( $total / $per_page );
            if ( $total_pages > 1 ) {
                echo '<div class="tablenav"><div class="tablenav-pages">';
                echo paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $page, 'total' => $total_pages ) );
                echo '</div></div>';
            }
            ?>
        <?php endif; ?>
    </div>
    <?php
}

// ─────────────────────────────────────────────
// 12. PLUGIN SETTINGS + REVIEW HELPERS
// ─────────────────────────────────────────────
add_action( 'admin_init', 'fiberco_register_plugin_settings' );
function fiberco_register_plugin_settings() {
    register_setting( 'fiberco_ai_settings_group', 'fiberco_ai_api_key', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ) );
    register_setting( 'fiberco_ai_settings_group', 'fiberco_notify_email', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_email',
        'default' => get_option( 'admin_email' ),
    ) );
    register_setting( 'fiberco_ai_settings_group', 'fiberco_dashboard_pin', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ) );
}

function fiberco_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    ?>
    <div class="wrap">
        <h1>FiberCo AI Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'fiberco_ai_settings_group' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="fiberco_ai_api_key">Anthropic API Key</label></th>
                    <td>
                        <input type="password" id="fiberco_ai_api_key" name="fiberco_ai_api_key" value="<?php echo esc_attr( get_option( 'fiberco_ai_api_key', '' ) ); ?>" class="regular-text" autocomplete="off" />
                        <p class="description">Optional. If blank, the chatbot uses the built-in keyword/guided intake flow.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="fiberco_notify_email">Lead Notification Email</label></th>
                    <td>
                        <input type="email" id="fiberco_notify_email" name="fiberco_notify_email" value="<?php echo esc_attr( get_option( 'fiberco_notify_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text" />
                        <p class="description">Used for future quote-ready lead notifications.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="fiberco_dashboard_pin">Public Dashboard PIN</label></th>
                    <td>
                        <input type="text" id="fiberco_dashboard_pin" name="fiberco_dashboard_pin" value="<?php echo esc_attr( get_option( 'fiberco_dashboard_pin', '' ) ); ?>" class="regular-text" maxlength="20" />
                        <p class="description">Used for the standalone public dashboard at /dashboard/.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Save Settings' ); ?>
        </form>
        <hr>
        <h2>Installation Notes</h2>
        <p>Deactivate the older FiberCo WPCode chatbot snippet before activating this plugin to avoid duplicate function names.</p>
        <p>Keep the Quote Wizard snippet/plugin active if you want chat-to-checkout handoff.</p>
    </div>
    <?php
}

function fiberco_review_status_label( $text ) {
    $t = strtolower( wp_strip_all_tags( (string) $text ) );
    if ( strpos( $t, 'review and finish secure checkout' ) !== false || strpos( $t, 'secure checkout' ) !== false || strpos( $t, 'built your order' ) !== false ) {
        return '<span style="background:#dcfce7;color:#166534;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;">Quote Ready</span>';
    }
    if ( strpos( $t, 'talk to a person' ) !== false || strpos( $t, 'live fiberco team member' ) !== false || strpos( $t, 'escalated' ) !== false ) {
        return '<span style="background:#fee2e2;color:#991b1b;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;">Needs Human</span>';
    }
    if ( strpos( $t, 'address' ) !== false || strpos( $t, 'recommend' ) !== false || strpos( $t, 'plan' ) !== false ) {
        return '<span style="background:#dbeafe;color:#1e40af;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;">Sales Active</span>';
    }
    return '<span style="background:#f3f4f6;color:#374151;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;">General</span>';
}




// ─────────────────────────────────────────────
// 13. TRUE FRONTEND DASHBOARD ROUTE
// ─────────────────────────────────────────────
add_action( 'init', 'fiberco_register_public_dashboard_route' );
function fiberco_register_public_dashboard_route() {
    // Root dashboard support: /dashboard/
    add_rewrite_rule( '^dashboard/?$', 'index.php?fiberco_public_dashboard=1', 'top' );

    // FiberCo child dashboard support: /FiberCo/dashboard/ or /fiberco/dashboard/
    // WordPress rewrite paths are normally lowercase slugs. This route prevents
    // some themes/plugins from redirecting the child dashboard request back to /FiberCo/.
    add_rewrite_rule( '^fiberco/dashboard/?$', 'index.php?fiberco_public_dashboard=1', 'top' );
}

function fiberco_find_fiberco_parent_page() {
    if ( ! function_exists( 'get_page_by_path' ) ) return null;

    $parent = get_page_by_path( 'fiberco' );
    if ( $parent && isset( $parent->ID ) ) return $parent;

    $parent = get_page_by_path( 'FiberCo' );
    if ( $parent && isset( $parent->ID ) ) return $parent;

    // Fallback: find a page with matching title, regardless of case/slug.
    $pages = get_pages( array( 'post_status' => 'publish', 'number' => 100 ) );
    foreach ( $pages as $page ) {
        if ( strtolower( trim( $page->post_title ) ) === 'fiberco' || strtolower( trim( $page->post_name ) ) === 'fiberco' ) {
            return $page;
        }
    }

    return null;
}

function fiberco_create_public_dashboard_page() {
    if ( ! function_exists( 'get_page_by_path' ) ) return;

    // Keep root /dashboard/ available as a backup URL.
    $existing = get_page_by_path( 'dashboard' );
    if ( $existing && isset( $existing->ID ) ) {
        if ( strpos( (string) $existing->post_content, '[fiberco_ai_dashboard]' ) === false ) {
            wp_update_post( array(
                'ID'           => $existing->ID,
                'post_content' => '[fiberco_ai_dashboard]',
                'post_status'  => 'publish',
            ) );
        }
    } else {
        wp_insert_post( array(
            'post_title'   => 'Dashboard',
            'post_name'    => 'dashboard',
            'post_content' => '[fiberco_ai_dashboard]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => 1,
        ) );
    }

    // Create the requested child page: /FiberCo/dashboard/
    $parent = fiberco_find_fiberco_parent_page();
    if ( $parent && isset( $parent->ID ) ) {
        $child = get_page_by_path( 'fiberco/dashboard' );
        if ( $child && isset( $child->ID ) ) {
            wp_update_post( array(
                'ID'           => $child->ID,
                'post_parent'  => (int) $parent->ID,
                'post_title'   => 'Dashboard',
                'post_name'    => 'dashboard',
                'post_content' => '[fiberco_ai_dashboard]',
                'post_status'  => 'publish',
            ) );
        } else {
            wp_insert_post( array(
                'post_title'   => 'Dashboard',
                'post_name'    => 'dashboard',
                'post_content' => '[fiberco_ai_dashboard]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_parent'  => (int) $parent->ID,
                'post_author'  => 1,
            ) );
        }
    }
}

add_filter( 'query_vars', 'fiberco_public_dashboard_query_vars' );
function fiberco_public_dashboard_query_vars( $vars ) {
    $vars[] = 'fiberco_public_dashboard';
    return $vars;
}

function fiberco_is_frontend_dashboard_request() {
    $path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $path = parse_url( $path, PHP_URL_PATH );
    if ( ! is_string( $path ) ) return false;
    $path = trim( strtolower( $path ), '/' );

    // Supports both the site-root dashboard and the requested FiberCo child route.
    return ( $path === 'dashboard' || $path === 'fiberco/dashboard' );
}

add_action( 'template_redirect', 'fiberco_render_public_dashboard_route', 0 );
function fiberco_render_public_dashboard_route() {
    if ( ! get_query_var( 'fiberco_public_dashboard' ) && ! fiberco_is_frontend_dashboard_request() ) {
        return;
    }

    // Build content before output so the PIN cookie can be set correctly.
    $dashboard_content = fiberco_render_public_dashboard_shortcode();

    status_header( 200 );
    nocache_headers();
    header( 'X-Robots-Tag: noindex, nofollow', true );
    header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ), true );

    // Direct standalone render: no theme wp_head/wp_footer. Some themes show a full-page
    // preloader/spinner on shortcode pages, so the dashboard must render independently.
    echo '<!doctype html><html ' . get_language_attributes() . '><head><meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '"><meta name="viewport" content="width=device-width, initial-scale=1"><title>FiberCo AI Dashboard</title></head><body class="fiberco-standalone-dashboard">';
    echo $dashboard_content;
    echo '</body></html>';
    exit;
}

add_action( 'admin_init', 'fiberco_flush_dashboard_rewrites_if_needed' );
function fiberco_flush_dashboard_rewrites_if_needed() {
    if ( get_option( 'fiberco_dashboard_rewrite_version' ) !== FIBERCO_CHATBOT_VERSION ) {
        fiberco_register_public_dashboard_route();
        fiberco_create_public_dashboard_page();
        flush_rewrite_rules();
        update_option( 'fiberco_dashboard_rewrite_version', FIBERCO_CHATBOT_VERSION );
    }
}

// ─────────────────────────────────────────────
// 14. PUBLIC PIN DASHBOARD RENDERING
// ─────────────────────────────────────────────
add_shortcode( 'fiberco_ai_dashboard', 'fiberco_render_public_dashboard_shortcode' );

function fiberco_ai_maybe_create_dashboard_page() {
    fiberco_create_public_dashboard_page();
}

function fiberco_ai_dashboard_url() {
    $child = get_page_by_path( 'fiberco/dashboard' );
    if ( $child && isset( $child->ID ) ) return get_permalink( $child );

    $page = get_page_by_path( 'dashboard' );
    return $page ? get_permalink( $page ) : home_url( '/dashboard/' );
}

function fiberco_render_public_dashboard_shortcode() {
    $pin = trim( (string) get_option( 'fiberco_dashboard_pin', '' ) );
    if ( $pin === '' ) {
        return '<div class="fiberco-dash-login"><h2>FiberCo AI Dashboard</h2><p>Please set a Public Dashboard PIN in WP Admin → FiberCo AI → Settings.</p></div>';
    }

    $session_key = 'fiberco_dash_ok_' . md5( $pin . AUTH_SALT );
    $authed = ! empty( $_COOKIE[ $session_key ] );
    $error = '';

    if ( isset( $_POST['fiberco_dash_pin'] ) ) {
        $submitted = sanitize_text_field( wp_unslash( $_POST['fiberco_dash_pin'] ) );
        if ( hash_equals( $pin, $submitted ) ) {
            setcookie( $session_key, '1', time() + DAY_IN_SECONDS, '/', COOKIE_DOMAIN, is_ssl(), true );
            $_COOKIE[ $session_key ] = '1';
            wp_safe_redirect( remove_query_arg( array( 'fiberco_signout' ) ) );
            exit;
        } else {
            $error = 'Incorrect PIN. Please try again.';
        }
    }

    if ( isset( $_GET['fiberco_signout'] ) ) {
        setcookie( $session_key, '', time() - HOUR_IN_SECONDS, '/', COOKIE_DOMAIN, is_ssl(), true );
        unset( $_COOKIE[ $session_key ] );
        $authed = false;
    }

    if ( ! $authed ) {
        ob_start(); ?>
        <style>
        .fiberco-dash-login{max-width:440px;margin:80px auto;padding:34px;border-radius:20px;background:#fff;box-shadow:0 16px 50px rgba(15,23,42,.12);font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;text-align:center;color:#101827}.fiberco-dash-login h2{margin:0 0 10px;font-size:28px}.fiberco-dash-login p{color:#667085}.fiberco-dash-login input{width:100%;padding:15px 16px;border-radius:12px;border:1px solid #d0d5dd;font-size:18px;text-align:center;letter-spacing:4px}.fiberco-dash-login button{margin-top:16px;width:100%;border:0;border-radius:12px;background:#0057ff;color:#fff;padding:15px 18px;font-weight:800;font-size:15px;cursor:pointer}.fiberco-dash-error{background:#fee2e2;color:#991b1b;border-radius:10px;padding:10px 12px;margin:14px 0}
        </style>
        <div class="fiberco-dash-login">
            <h2>FiberCo AI Dashboard</h2>
            <p>Enter your dashboard PIN to view chatbot analytics.</p>
            <?php if ( $error ) : ?><div class="fiberco-dash-error"><?php echo esc_html( $error ); ?></div><?php endif; ?>
            <form method="post">
                <input type="password" name="fiberco_dash_pin" placeholder="PIN" autocomplete="off" />
                <button type="submit">View Dashboard</button>
            </form>
        </div>
        <?php return ob_get_clean();
    }

    return fiberco_render_public_dashboard_html();
}

function fiberco_dash_get_logs_for_range( $range ) {
    global $wpdb;
    $table = $wpdb->prefix . 'fiberco_chat_logs';
    $where = '1=1';
    if ( $range === '7' ) {
        $where = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ( $range === '30' ) {
        $where = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }
    return $wpdb->get_results( "SELECT * FROM $table WHERE $where ORDER BY created_at DESC" );
}

function fiberco_dash_count_matches( $logs, $patterns ) {
    $count = 0;
    foreach ( $logs as $log ) {
        $txt = strtolower( wp_strip_all_tags( $log->user_message . ' ' . $log->bot_response ) );
        foreach ( $patterns as $pat ) {
            if ( preg_match( $pat, $txt ) ) { $count++; break; }
        }
    }
    return $count;
}

function fiberco_dash_category_maps() {
    return array(
        'needs' => array(
            'Gaming Internet' => '/gaming|gamer|xbox|playstation|ps5|latency|\blag\b|ping/i',
            'Moving / New Service' => '/moved|moving|new service|new customer|start service|buy internet|sign up|get connected|set up internet|need internet/i',
            'Streaming / Smart Home' => '/streaming|netflix|hulu|youtube|smart home|4k|tv/i',
            'Work From Home' => '/work from home|wfh|remote work|zoom|video calls|teams meeting/i',
            'Better Wi-Fi Coverage' => '/wifi|wi-fi|coverage|dead zone|whole home|wall-to-wall|mesh/i',
            'Faster Speeds' => '/faster|speed|slow|upgrade|gig|gbps|mbps/i',
            'Business Internet' => '/business|office|company|commercial|static ip|small business|shop|store/i',
            'Lower Bill / Pricing' => '/price|pricing|cost|cheap|lower bill|affordable|how much/i',
            'Installation Questions' => '/install|installation|technician|appointment|schedule/i',
        ),
        'technical' => array(
            'Slow Speeds' => '/slow|speed|buffering|loading slow|speedtest/i',
            'Wi-Fi Problems' => '/wifi|wi-fi|wireless|signal|dead zone|coverage/i',
            'Outage / No Internet' => '/outage|down|offline|no internet|no connection|internet out|not working/i',
            'Gaming Lag' => '/\blag\b|latency|ping|gaming/i',
            'Router / Equipment' => '/router|modem|gateway|equipment|lights|reboot|restart/i',
            'Billing Help' => '/bill|billing|invoice|payment|autopay|charge/i',
            'Install / Technician' => '/install|technician|appointment|schedule|service call/i',
        ),
        'topic' => array(
            'New Service / Quote' => '/quote|buy|internet|service|moved|new customer|address|start order/i',
            'Gaming / Speed' => '/gaming|streaming|speed|\blag\b|latency|gbps|mbps/i',
            'Business Internet' => '/business|office|static ip|company|commercial|small business/i',
            'Billing' => '/bill|billing|payment|invoice|autopay/i',
            'Support / Outage' => '/support|outage|down|not working|router|wifi/i',
        ),
        'plan' => array(
            'Essential Connect 250' => '/essential connect|250\s*mbps|starter/i',
            'Everyday Gig' => '/everyday gig|1\s*gbps|gaming/i',
            'Velocity Pro 5 Gig' => '/velocity pro|5\s*gbps|5 gig/i',
            'Quantum Max 10 Gig' => '/quantum max|10\s*gbps|10 gig/i',
            'Stream and Work 750' => '/stream (and|&) work|750\s*mbps/i',
        ),
        'competitor' => array(
            'Spectrum' => '/spectrum/i',
            'AT&T' => '/at&t|att\b/i',
            'Xfinity' => '/xfinity|comcast/i',
            'Comporium' => '/comporium/i',
            'T-Mobile' => '/t-mobile|tmobile/i',
            'Verizon' => '/verizon/i',
            'Windstream' => '/windstream/i',
        ),
    );
}

function fiberco_dash_top_items( $logs, $type = 'zip' ) {
    $items = array();

    // IMPORTANT: Dashboard counts and drilldowns must use the same session-level matching logic.
    // For needs/technical/topics, count CUSTOMER messages only so Nova's repeated recommendations do not inflate totals.
    $maps = fiberco_dash_category_maps();

    if ( in_array( $type, array( 'needs', 'technical', 'topic' ), true ) && isset( $maps[ $type ] ) ) {
        $sessions = array();
        foreach ( $logs as $log ) {
            if ( empty( $log->session_id ) ) continue;
            if ( ! isset( $sessions[ $log->session_id ] ) ) $sessions[ $log->session_id ] = '';
            $sessions[ $log->session_id ] .= ' ' . wp_strip_all_tags( $log->user_message );
        }
        foreach ( $sessions as $customer_text ) {
            foreach ( $maps[ $type ] as $label => $regex ) {
                if ( preg_match( $regex, $customer_text ) ) {
                    $items[ $label ] = ( $items[ $label ] ?? 0 ) + 1;
                }
            }
        }
        arsort( $items );
        return array_slice( $items, 0, 8, true );
    }

    // Less-used legacy groups may still look at the full transcript.
    foreach ( $logs as $log ) {
        $txt = wp_strip_all_tags( $log->user_message . ' ' . $log->bot_response );
        if ( $type === 'zip' && preg_match_all( '/\b\d{5}\b/', $txt, $m ) ) {
            foreach ( $m[0] as $zip ) $items[$zip] = ($items[$zip] ?? 0) + 1;
        } elseif ( isset( $maps[ $type ] ) ) {
            foreach ( $maps[ $type ] as $label => $regex ) {
                if ( preg_match( $regex, $txt ) ) $items[$label] = ($items[$label] ?? 0) + 1;
            }
        }
    }
    arsort( $items );
    return array_slice( $items, 0, 8, true );
}

function fiberco_render_bar_list( $items, $color = '#0057ff', $type = '' ) {
    if ( empty( $items ) ) return '<div class="fc-empty">No data yet.</div>';
    $max = max( $items );
    $html = '';
    foreach ( $items as $label => $count ) {
        $pct = $max ? max( 8, round( $count / $max * 100 ) ) : 0;
        $base_url = remove_query_arg( array( 'session', 'filter_label', 'filter_type', 'fiberco_signout' ) );
        $url = add_query_arg( array( 'filter_type' => $type, 'filter_label' => $label ), $base_url );
        $html .= '<a class="fc-bar-row fc-drill-link" data-filter-type="' . esc_attr( $type ) . '" data-filter-label="' . esc_attr( $label ) . '" href="' . esc_url( $url ) . '"><div class="fc-bar-label">' . esc_html( $label ) . '</div><div class="fc-bar-track"><div class="fc-bar-fill" style="width:' . esc_attr( $pct ) . '%;background:' . esc_attr( $color ) . '"></div></div><div class="fc-bar-count">' . esc_html( $count ) . ' ▶</div></a>';
    }
    return $html;
}

function fiberco_dash_match_regex_for_label( $label, $type = '' ) {
    $label_l = strtolower( trim( $label ) );
    $maps = fiberco_dash_category_maps();

    if ( $type && isset( $maps[ $type ] ) ) {
        foreach ( $maps[ $type ] as $map_label => $regex ) {
            if ( strtolower( $map_label ) === $label_l ) return $regex;
        }
    }

    foreach ( $maps as $group ) {
        foreach ( $group as $map_label => $regex ) {
            if ( strtolower( $map_label ) === $label_l ) return $regex;
        }
    }

    return '';
}

function fiberco_dash_log_matches_label( $log, $label, $type = '' ) {
    $regex = fiberco_dash_match_regex_for_label( $label, $type );

    // Drilldowns should be driven by what the CUSTOMER said, not repeated plan/support text Nova may include.
    $customer_text = strtolower( wp_strip_all_tags( $log->user_message ) );
    $all_text      = strtolower( wp_strip_all_tags( $log->user_message . ' ' . $log->bot_response ) );

    if ( $regex ) {
        if ( in_array( $type, array( 'needs', 'technical', 'topic' ), true ) ) {
            return (bool) preg_match( $regex, $customer_text );
        }
        return (bool) preg_match( $regex, $all_text );
    }

    $label_l = strtolower( $label );
    foreach ( preg_split( '/\s+/', preg_replace('/[^a-z0-9]+/i', ' ', $label_l ) ) as $word ) {
        if ( strlen( $word ) >= 4 && strpos( $customer_text, $word ) !== false ) return true;
    }
    return false;
}

function fiberco_dash_session_matches_label( $session_logs, $label, $type = '' ) {
    foreach ( $session_logs as $log ) {
        if ( fiberco_dash_log_matches_label( $log, $label, $type ) ) return true;
    }
    return false;
}

function fiberco_dash_render_session_transcript( $logs, $session_id ) {
    $session_logs = array_values( array_filter( $logs, function( $log ) use ( $session_id ) { return $log->session_id === $session_id; } ) );
    if ( empty( $session_logs ) ) return '';
    usort( $session_logs, function( $a, $b ) { return strcmp( $a->created_at, $b->created_at ); } );
    $all_text = '';
    foreach ( $session_logs as $log ) $all_text .= ' ' . $log->user_message . ' ' . $log->bot_response;
    $status = fiberco_review_status_label( $all_text );
    $html = '<div class="fc-panel fc-leads fc-transcript"><h3>💬 Full Conversation Transcript</h3><div class="fc-sub">Session ' . esc_html( substr( $session_id, 0, 12 ) ) . '… &nbsp; ' . $status . '</div>';
    foreach ( $session_logs as $log ) {
        $html .= '<div class="fc-chat-pair"><div class="fc-chat-meta">' . esc_html( mysql2date( 'M j, g:i A', $log->created_at ) ) . '</div><div class="fc-user-msg"><strong>Customer:</strong><br>' . esc_html( $log->user_message ) . '</div><div class="fc-bot-msg"><strong>Nova:</strong><br>' . wp_kses_post( wpautop( wp_strip_all_tags( $log->bot_response ) ) ) . '</div></div>';
    }
    $html .= '<p><a class="fc-pill" href="' . esc_url( remove_query_arg( 'session' ) ) . '">← Back to dashboard</a></p></div>';
    return $html;
}

function fiberco_dash_render_filtered_conversations( $logs, $label, $type = '' ) {
    if ( ! $label ) return '';

    // Group logs by session first so each drilldown returns the correct conversations, not the first repeated rows.
    $sessions = array();
    foreach ( $logs as $log ) {
        if ( empty( $log->session_id ) ) continue;
        if ( ! isset( $sessions[ $log->session_id ] ) ) $sessions[ $log->session_id ] = array();
        $sessions[ $log->session_id ][] = $log;
    }

    $matches = array();
    foreach ( $sessions as $sid => $session_logs ) {
        if ( fiberco_dash_session_matches_label( $session_logs, $label, $type ) ) {
            usort( $session_logs, function( $a, $b ) { return strcmp( $a->created_at, $b->created_at ); } );
            $matches[ $sid ] = $session_logs;
        }
    }

    if ( empty( $matches ) ) {
        return '<div class="fc-panel fc-leads"><h3>🔎 Conversations: ' . esc_html( $label ) . '</h3><div class="fc-empty">No matching conversations found.</div></div>';
    }

    $html = '<div class="fc-panel fc-leads"><h3>🔎 Conversations: ' . esc_html( $label ) . '</h3><div class="fc-sub">Showing conversations for this specific bar. Click any row to open the full transcript.</div><table class="fc-table"><thead><tr><th>Date</th><th>Session</th><th>Customer Message</th><th>Status</th><th></th></tr></thead><tbody>';

    foreach ( $matches as $sid => $session_logs ) {
        $first = $session_logs[0];
        $last  = end( $session_logs );
        $summary_text = '';
        foreach ( $session_logs as $l ) $summary_text .= ' ' . $l->user_message . ' ' . $l->bot_response;
        $status = fiberco_review_status_label( $summary_text );
        $url = add_query_arg( array( 'session' => $sid ) );
        $html .= '<tr><td>' . esc_html( mysql2date( 'M j, g:i A', $last->created_at ) ) . '</td><td>' . esc_html( substr( $sid, 0, 8 ) ) . '…</td><td>' . esc_html( wp_trim_words( $first->user_message, 24 ) ) . '</td><td>' . $status . '</td><td><a class="fc-open" href="' . esc_url( $url ) . '">Open Transcript</a></td></tr>';
    }
    $html .= '</tbody></table></div>';
    return $html;
}


function fiberco_dash_revenue_opportunity( $logs ) {
    $prices = array(
        'quantum max' => 129.95,
        '10 gig' => 129.95,
        'velocity pro' => 109.95,
        '5 gig' => 109.95,
        'everyday gig' => 89.95,
        '1 gbps' => 89.95,
        'stream and work' => 69.95,
        '750' => 69.95,
        'essential connect' => 49.95,
        '250 mbps' => 49.95,
        'fast gig business' => 249.95,
        'fast 400 business' => 149.95,
        'fast 250 business' => 79.95,
    );
    $sessions = array();
    foreach ( $logs as $log ) {
        $sid = $log->session_id;
        if ( ! isset( $sessions[ $sid ] ) ) {
            $sessions[ $sid ] = array( 'text' => '', 'price' => 0, 'hot' => false, 'upsell' => 0 );
        }
        $sessions[ $sid ]['text'] .= ' ' . strtolower( $log->user_message . ' ' . $log->bot_response );
    }
    $monthly = 0; $hot = 0; $upsells = 0;
    foreach ( $sessions as $sid => $data ) {
        $text = $data['text'];
        $best = 0;
        foreach ( $prices as $needle => $price ) {
            if ( strpos( $text, $needle ) !== false ) $best = max( $best, $price );
        }
        if ( $best <= 0 && preg_match( '/quote|buy|order|checkout|new service|internet|gaming|moving|moved/i', $text ) ) {
            $best = 89.95;
        }
        if ( preg_match( '/priority care|protection|mesh|wifi pod|upgrade|faster/i', $text ) ) {
            $best += 9.99;
            $upsells++;
        }
        if ( preg_match( '/secure checkout|built your order|review and finish|complete payment|email|phone/i', $text ) ) {
            $hot++;
            $monthly += $best;
        } elseif ( $best > 0 ) {
            $monthly += ( $best * 0.35 );
        }
    }
    return array(
        'monthly' => round( $monthly ),
        'annual' => round( $monthly * 12 ),
        'hot' => $hot,
        'upsells' => $upsells,
    );
}

function fiberco_dash_ai_insights_summary( $logs ) {
    $needs = fiberco_dash_top_items( $logs, 'needs' );
    $tech  = fiberco_dash_top_items( $logs, 'technical' );
    $topics = fiberco_dash_top_items( $logs, 'topic' );
    $rev = fiberco_dash_revenue_opportunity( $logs );
    $top_need = empty( $needs ) ? 'new service interest' : key( $needs );
    $top_tech = empty( $tech ) ? 'no dominant technical issue yet' : key( $tech );
    $top_topic = empty( $topics ) ? 'general questions' : key( $topics );
    $sessions = array();
    foreach ( $logs as $log ) $sessions[ $log->session_id ] = true;
    $session_count = count( $sessions );
    if ( $session_count === 0 ) {
        return '<div class="fc-insight-empty">No conversation data yet. Once chats begin, Nova will summarize sales intent, support issues, and revenue opportunities here.</div>';
    }
    $html  = '<div class="fc-insight-grid">';
    $html .= '<div class="fc-insight"><strong>Primary demand:</strong><br>Customers are most often showing interest in <span>' . esc_html( $top_need ) . '</span>.</div>';
    $html .= '<div class="fc-insight"><strong>Top conversation theme:</strong><br>The most common discussion area is <span>' . esc_html( $top_topic ) . '</span>.</div>';
    $html .= '<div class="fc-insight"><strong>Support signal:</strong><br>The leading technical pattern is <span>' . esc_html( $top_tech ) . '</span>.</div>';
    $html .= '<div class="fc-insight"><strong>Revenue signal:</strong><br>Estimated pipeline opportunity is <span>$' . esc_html( number_format_i18n( $rev['monthly'] ) ) . '/mo</span> across active buying conversations.</div>';
    $html .= '</div>';
    return $html;
}

function fiberco_render_public_dashboard_html() {
    $range = isset( $_GET['range'] ) ? sanitize_text_field( wp_unslash( $_GET['range'] ) ) : 'all';
    if ( ! in_array( $range, array( '7', '30', 'all' ), true ) ) $range = 'all';
    $logs = fiberco_dash_get_logs_for_range( $range );
    $sessions = array(); $hours = array();
    foreach ( $logs as $log ) {
        $sessions[ $log->session_id ] = true;
        $h = date( 'g A', strtotime( $log->created_at ) );
        $hours[$h] = ($hours[$h] ?? 0) + 1;
    }
    arsort( $hours );
    $session_count = count( $sessions );
    $messages = count( $logs );
    $leads = fiberco_dash_count_matches( $logs, array( '/email/i','/phone/i','/secure checkout/i','/built your order/i','/review and finish/i' ) );
    $quote_ready = fiberco_dash_count_matches( $logs, array( '/secure checkout/i','/built your order/i','/review and finish/i' ) );
    $dropoffs = max( 0, $session_count - $quote_ready );
    $conversion = $session_count ? round( ( $leads / $session_count ) * 100, 1 ) : 0;
    $top_hour = empty( $hours ) ? '—' : key( $hours );
    $revenue = fiberco_dash_revenue_opportunity( $logs );
    $url_base = remove_query_arg( array( 'range', 'fiberco_signout', 'session', 'filter_label', 'filter_type' ) );
    ob_start(); ?>
    <style>
    html,body.fiberco-standalone-dashboard{margin:0!important;padding:0!important;background:#f3f5f9}.fiberco-dash{font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f3f5f9;color:#101827;margin:0;padding-bottom:60px;min-height:100vh}.fc-top{height:70px;background:#071b3d;color:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 36px;box-shadow:0 6px 20px rgba(15,23,42,.16)}.fc-brand{display:flex;align-items:center;gap:14px;font-size:24px;font-weight:900}.fc-brand-icon{width:34px;height:34px;border-radius:11px;background:#0057ff;display:grid;place-items:center}.fc-version{font-size:13px;background:#041226;color:#93c5fd;border-radius:8px;padding:7px 10px;margin-right:14px}.fc-signout{border:1px solid rgba(255,255,255,.35);border-radius:10px;color:#fff;text-decoration:none;padding:10px 16px;font-weight:800}.fc-body{max-width:1780px;margin:0 auto;padding:36px 52px}.fc-range{display:flex;align-items:center;gap:12px;margin-bottom:34px}.fc-range strong{margin-right:6px}.fc-pill{background:#fff;border:1px solid #dde3ee;border-radius:999px;padding:12px 24px;text-decoration:none;color:#344054;font-weight:800}.fc-pill.active{background:#0057ff;color:#fff;border-color:#0057ff}.fc-muted{color:#7b8494;margin-left:8px}.fc-cards{display:grid;grid-template-columns:repeat(6,1fr);gap:22px;margin-bottom:34px}.fc-card{background:#fff;border-radius:14px;padding:28px 32px;box-shadow:0 4px 16px rgba(15,23,42,.08);border:1px solid #e5eaf2}.fc-num{font-size:48px;font-weight:950;color:#0057ff;line-height:1}.fc-label{font-size:15px;text-transform:uppercase;letter-spacing:.7px;color:#6b7280;font-weight:900;margin-top:8px}.fc-grid{display:grid;grid-template-columns:1fr 1fr;gap:28px}.fc-panel{background:#fff;border-radius:16px;padding:30px 34px;box-shadow:0 4px 16px rgba(15,23,42,.08);border:1px solid #e5eaf2;min-height:280px}.fc-revenue{background:linear-gradient(135deg,#052e16,#0f766e);color:#fff}.fc-revenue h3,.fc-revenue .fc-sub{color:#fff}.fc-rev-num{font-size:50px;font-weight:950;line-height:1;margin:16px 0 4px}.fc-rev-label{opacity:.8;font-weight:800;text-transform:uppercase;letter-spacing:.7px}.fc-rev-row{display:flex;justify-content:space-between;border-top:1px solid rgba(255,255,255,.18);padding:14px 0;font-weight:900}.fc-insights{grid-column:1/-1;background:linear-gradient(135deg,#fff,#eff6ff)}.fc-insight-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}.fc-insight{background:#fff;border:1px solid #dbeafe;border-radius:14px;padding:20px;line-height:1.55;color:#344054}.fc-insight span{color:#0057ff;font-weight:950}.fc-insight-empty{background:#fff;border:1px dashed #bfdbfe;border-radius:14px;padding:22px;color:#64748b}.fc-panel h3{font-size:24px;margin:0 0 8px}.fc-sub{color:#9aa3af;margin-bottom:22px}.fc-bar-row{display:grid;grid-template-columns:170px 1fr 48px;gap:16px;align-items:center;margin:16px 0;text-decoration:none}.fc-drill-link:hover .fc-bar-label{color:#0057ff}.fc-open{font-weight:900;color:#0057ff;text-decoration:none}.fc-transcript{background:#fff}.fc-chat-pair{border:1px solid #e5eaf2;border-radius:14px;padding:18px;margin:16px 0;background:#f8fafc}.fc-chat-meta{font-size:12px;font-weight:900;color:#667085;text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px}.fc-user-msg{background:#eff6ff;border-left:4px solid #0057ff;border-radius:10px;padding:14px;margin-bottom:10px;line-height:1.55}.fc-bot-msg{background:#fff;border-left:4px solid #10b981;border-radius:10px;padding:14px;line-height:1.55}.fc-bot-msg p{margin:0 0 8px}.fc-bar-label{font-weight:900;color:#1d2939}.fc-bar-track{height:20px;background:#eef1f5;border-radius:999px;overflow:hidden}.fc-bar-fill{height:100%;border-radius:999px}.fc-bar-count{text-align:right;color:#667085;font-weight:800}.fc-empty{color:#98a2b3;padding:24px 0}.fc-leads{grid-column:1/-1}.fc-table{width:100%;border-collapse:collapse}.fc-table th{text-align:left;color:#667085;text-transform:uppercase;letter-spacing:.6px;font-size:12px;border-bottom:1px solid #e5eaf2;padding:12px}.fc-table td{border-bottom:1px solid #eef1f5;padding:14px 12px;vertical-align:top}.fc-badge{display:inline-block;border-radius:999px;background:#dbeafe;color:#1e40af;padding:5px 10px;font-size:12px;font-weight:900}.fc-hot{background:#dcfce7;color:#166534}.fc-human{background:#fee2e2;color:#991b1b}@media(max-width:1200px){.fc-cards{grid-template-columns:repeat(3,1fr)}.fc-grid{grid-template-columns:1fr}.fc-insight-grid{grid-template-columns:1fr 1fr}}@media(max-width:700px){.fc-body{padding:24px 16px}.fc-cards{grid-template-columns:1fr}.fc-top{padding:0 16px}.fc-range{flex-wrap:wrap}.fc-bar-row{grid-template-columns:1fr}.fc-bar-count{text-align:left}.fc-insight-grid{grid-template-columns:1fr}}
    </style>
    <div class="fiberco-dash">
        <div class="fc-top"><div class="fc-brand"><span class="fc-brand-icon">⚡</span> FiberCo AI Analytics</div><div><span class="fc-version">v<?php echo esc_html( FIBERCO_CHATBOT_VERSION ); ?></span><a class="fc-signout" href="<?php echo esc_url( add_query_arg( 'fiberco_signout', '1' ) ); ?>">Sign Out</a></div></div>
        <div class="fc-body">
            <div class="fc-range"><strong>Range:</strong><a class="fc-pill <?php echo $range==='7'?'active':''; ?>" href="<?php echo esc_url( add_query_arg( 'range', '7', $url_base ) ); ?>">Last 7 Days</a><a class="fc-pill <?php echo $range==='30'?'active':''; ?>" href="<?php echo esc_url( add_query_arg( 'range', '30', $url_base ) ); ?>">Last 30 Days</a><a class="fc-pill <?php echo $range==='all'?'active':''; ?>" href="<?php echo esc_url( add_query_arg( 'range', 'all', $url_base ) ); ?>">All Time</a><span class="fc-muted">Showing: <strong><?php echo esc_html( $range === 'all' ? 'All Time' : 'Last ' . $range . ' Days' ); ?></strong></span></div>
            <div class="fc-cards">
                <div class="fc-card"><div class="fc-num"><?php echo esc_html( $session_count ); ?></div><div class="fc-label">Chat Sessions</div></div>
                <div class="fc-card"><div class="fc-num"><?php echo esc_html( $leads ); ?></div><div class="fc-label">Leads Captured</div></div>
                <div class="fc-card"><div class="fc-num"><?php echo esc_html( $conversion ); ?>%</div><div class="fc-label">Conversion Rate</div></div>
                <div class="fc-card"><div class="fc-num"><?php echo esc_html( $messages ); ?></div><div class="fc-label">Customer Messages</div></div>
                <div class="fc-card"><div class="fc-num"><?php echo esc_html( $dropoffs ); ?></div><div class="fc-label">Drop-Off Sessions</div></div>
                <div class="fc-card"><div class="fc-num" style="color:#15803d"><?php echo esc_html( $top_hour ); ?></div><div class="fc-label">Peak Chat Hour</div></div>
            </div>
            <div class="fc-grid">
                <?php if ( isset( $_GET['session'] ) ) echo fiberco_dash_render_session_transcript( $logs, sanitize_text_field( wp_unslash( $_GET['session'] ) ) ); ?>
                <?php if ( isset( $_GET['filter_label'] ) && ! isset( $_GET['session'] ) ) echo fiberco_dash_render_filtered_conversations( $logs, sanitize_text_field( wp_unslash( $_GET['filter_label'] ) ), isset( $_GET['filter_type'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_type'] ) ) : '' ); ?>
                <div class="fc-panel"><h3>📊 Conversation Topics</h3><div class="fc-sub">What customers are asking about</div><?php echo fiberco_render_bar_list( fiberco_dash_top_items( $logs, 'topic' ), '#7c3aed', 'topic' ); ?></div>
                <div class="fc-panel"><h3>🎯 Top Customer Needs</h3><div class="fc-sub">Sales intent and service needs detected in chat</div><?php echo fiberco_render_bar_list( fiberco_dash_top_items( $logs, 'needs' ), '#16a34a', 'needs' ); ?></div>
                <div class="fc-panel"><h3>🔧 Top Technical Issues</h3><div class="fc-sub">Support issues and troubleshooting patterns</div><?php echo fiberco_render_bar_list( fiberco_dash_top_items( $logs, 'technical' ), '#f97316', 'technical' ); ?></div>
                <div class="fc-panel fc-revenue"><h3>💵 Revenue Opportunity</h3><div class="fc-sub">Estimated monthly pipeline from quote-ready and buying-intent chats</div><div class="fc-rev-num">$<?php echo esc_html( number_format_i18n( $revenue['monthly'] ) ); ?>/mo</div><div class="fc-rev-label">$<?php echo esc_html( number_format_i18n( $revenue['annual'] ) ); ?> annualized</div><div style="margin-top:22px"><div class="fc-rev-row"><span>Hot buying conversations</span><span><?php echo esc_html( $revenue['hot'] ); ?></span></div><div class="fc-rev-row"><span>Upsell signals</span><span><?php echo esc_html( $revenue['upsells'] ); ?></span></div></div></div>
                <div class="fc-panel fc-insights"><h3>✨ AI Insights Summary</h3><div class="fc-sub">Executive-level readout of what customers are asking for and where revenue/support opportunities are emerging</div><?php echo fiberco_dash_ai_insights_summary( $logs ); ?></div>
                <div class="fc-panel fc-leads"><h3>🔥 Recent Quote Activity</h3><div class="fc-sub">Latest sessions with sales, support, and checkout signals</div>
                    <table class="fc-table"><thead><tr><th>Date</th><th>Session</th><th>Customer Message</th><th>Status</th><th></th></tr></thead><tbody>
                    <?php foreach ( array_slice( $logs, 0, 12 ) as $log ) : $txt = $log->user_message . ' ' . $log->bot_response; $status = fiberco_review_status_label( $txt ); ?>
                    <tr><td><?php echo esc_html( mysql2date( 'M j, g:i A', $log->created_at ) ); ?></td><td><?php echo esc_html( substr( $log->session_id, 0, 8 ) ); ?>…</td><td><?php echo esc_html( wp_trim_words( $log->user_message, 22 ) ); ?></td><td><?php echo $status; ?></td><td><a class="fc-open" href="<?php echo esc_url( add_query_arg( 'session', $log->session_id ) ); ?>">Open Full Transcript</a></td></tr>
                    <?php endforeach; ?>
                    </tbody></table>
                </div>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}

/* ── Front-end chat widget (launcher + window). Outputs the migrated WPCode footer
 * widget on the PUBLIC site only — never inside the SP app. Pairs with the
 * window.fibercoChatConfig injected in wp_head and the fiberco_chat AJAX handler. ── */
add_action( 'wp_footer', 'fiberco_render_chat_widget', 50 );
function fiberco_render_chat_widget() {
	if ( is_admin() ) { return; }
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	if ( preg_match( '#/(sp-app|sp-login|sp-setup)(/|$|\?)#', $uri ) ) { return; }
	$f = plugin_dir_path( __FILE__ ) . 'chat-widget.html';
	if ( file_exists( $f ) ) { echo "\n"; readfile( $f ); }
}
