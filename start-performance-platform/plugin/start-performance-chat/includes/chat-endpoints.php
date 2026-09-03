<?php
/**
 * Public, UN-authenticated chat endpoints (REST namespace: sp-chat/v1).
 * Called cross-origin by the embedded widget on the client's separate public site.
 * Gate order on every call: enabled -> valid key -> allowed origin -> not IP-banned ->
 * rate limit -> input moderation -> compose layered prompt + KB grounding -> AI -> output
 * moderation. No staff cookies or admin actions are ever reachable here.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Route registration ──────────────────────────────────────────────────────────

add_action( 'rest_api_init', 'sp_chat_register_routes' );

function sp_chat_register_routes() {
    $public = array( 'permission_callback' => '__return_true', 'methods' => 'POST' );
    register_rest_route( 'sp-chat/v1', '/start',    array_merge( $public, array( 'callback' => 'sp_chat_ep_start' ) ) );
    register_rest_route( 'sp-chat/v1', '/send',     array_merge( $public, array( 'callback' => 'sp_chat_ep_send' ) ) );
    register_rest_route( 'sp-chat/v1', '/escalate', array_merge( $public, array( 'callback' => 'sp_chat_ep_escalate' ) ) );
}

// ── CORS (only for our namespace, only for allowed origins) ──────────────────────

add_filter( 'rest_pre_serve_request', 'sp_chat_cors', 10, 4 );

function sp_chat_cors( $served, $result, $request, $server ) {
    if ( strpos( $request->get_route(), '/sp-chat/v1' ) !== 0 ) return $served;
    $origin = get_http_origin();
    if ( $origin && sp_chat_origin_allowed( $origin ) ) {
        header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
        header( 'Access-Control-Allow-Methods: POST, OPTIONS' );
        header( 'Access-Control-Allow-Headers: Content-Type' );
        header( 'Vary: Origin' );
    }
    return $served;
}

// ── Shared gate ──────────────────────────────────────────────────────────────────

function sp_chat_gate( $request ) {
    if ( ! (int) get_option( 'sp_chat_enabled_global', 1 ) || ! (int) get_option( 'sp_chat_enabled', 0 ) ) {
        return sp_chat_err( 'disabled', 'Chat is not available.', 403 );
    }
    if ( ! sp_chat_key_valid( sp_chat_req( $request, 'key' ) ) ) {
        return sp_chat_err( 'bad_key', 'Invalid site key.', 403 );
    }
    $origin = get_http_origin();
    if ( $origin && ! sp_chat_origin_allowed( $origin ) ) {
        return sp_chat_err( 'bad_origin', 'This site is not authorized to use this chat.', 403 );
    }
    if ( sp_chat_ip_is_blocked( sp_chat_client_ip() ) ) {
        return sp_chat_err( 'blocked', 'Too many requests. Please try again later.', 429 );
    }
    return null;
}

// ── Endpoints ────────────────────────────────────────────────────────────────────

function sp_chat_ep_start( $request ) {
    $gate = sp_chat_gate( $request ); if ( $gate ) return $gate;
    $ip     = sp_chat_client_ip();
    $limits = sp_chat_get_limits();

    if ( sp_chat_rl_sessions_today( $ip ) >= $limits['max_sessions_day'] ) {
        return sp_chat_err( 'rate', 'Too many chats from your network today.', 429 );
    }

    // Mark "installed" — the widget successfully loaded and started a session somewhere.
    // Drives the install-status banner in Settings → Chat.
    update_option( 'sp_chat_last_seen', time() );

    global $wpdb;
    $token = sp_chat_gen_token();
    $now   = current_time( 'mysql' );
    $wpdb->insert( $wpdb->prefix . 'sp_chat_sessions', array(
        'token'      => $token,
        'status'     => 'active',
        'ip_hash'    => sp_chat_hash_ip( $ip ),
        'created_at' => $now,
        'updated_at' => $now,
    ) );

    return sp_chat_ok( array(
        'token' => $token,
        'reply' => sp_chat_greeting(),
        'chips' => sp_chat_get_quick_buttons(),
    ) );
}

function sp_chat_ep_send( $request ) {
    $gate = sp_chat_gate( $request ); if ( $gate ) return $gate;
    $ip      = sp_chat_client_ip();
    $limits  = sp_chat_get_limits();
    $token   = sp_chat_req( $request, 'token' );
    $message = trim( (string) sp_chat_req( $request, 'message' ) );

    $session = sp_chat_session_by_token( $token );
    if ( ! $session )                                return sp_chat_err( 'no_session', 'Chat session not found.', 404 );
    if ( $message === '' )                           return sp_chat_err( 'empty', 'Message is required.', 400 );
    if ( mb_strlen( $message ) > $limits['max_input_chars'] ) return sp_chat_err( 'too_long', 'Message is too long.', 400 );
    if ( sp_chat_session_msg_count( $session->id ) >= $limits['max_msgs_session'] ) {
        return sp_chat_err( 'limit', 'This chat has reached its message limit.', 429 );
    }

    // Input moderation — flags feed the auto-ban counter; injection is handled by the
    // layered prompt so we still answer, but repeat offenders get banned.
    $flags = sp_chat_screen_input( $message );
    foreach ( $flags as $f ) sp_chat_register_flag( $ip, $f );

    // Grounding + layered prompt (vendor policy wraps client content + KB).
    $kb      = sp_chat_kb_retrieve( $message, 4 );
    $content = (string) get_option( 'sp_chat_client_content', '' );
    $persona = (string) get_option( 'sp_chat_persona', '' );
    if ( $persona !== '' ) $content .= "\nPreferred tone: " . $persona;
    $system  = sp_chat_compose_system_prompt( $content, $kb );

    $messages   = sp_chat_history( $session->id, 12 );
    $messages[] = array( 'role' => 'user', 'content' => $message );

    $ai = sp_chat_ai_complete( $system, $messages );
    if ( is_wp_error( $ai ) ) {
        $reply = "Sorry — I'm having trouble right now. Would you like me to connect you with the team?";
    } else {
        $screen = sp_chat_screen_output( $ai );
        $reply  = $screen[1];
    }

    // Persist the exchange.
    sp_chat_insert_msg( $session->id, 'user', $message, '', $flags ? 1 : 0 );
    sp_chat_insert_msg( $session->id, 'assistant', $reply, wp_json_encode( array( 'grounded' => $kb !== '' ) ), 0 );
    global $wpdb;
    $wpdb->update( $wpdb->prefix . 'sp_chat_sessions', array( 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $session->id ) );

    return sp_chat_ok( array( 'reply' => $reply, 'grounded' => $kb !== '' ) );
}

function sp_chat_ep_escalate( $request ) {
    $gate = sp_chat_gate( $request ); if ( $gate ) return $gate;
    $token = sp_chat_req( $request, 'token' );
    $session = sp_chat_session_by_token( $token );
    if ( ! $session ) return sp_chat_err( 'no_session', 'Chat session not found.', 404 );

    $name  = sanitize_text_field( (string) sp_chat_req( $request, 'name' ) );
    $email = sanitize_email(      (string) sp_chat_req( $request, 'email' ) );
    $note  = sanitize_textarea_field( (string) sp_chat_req( $request, 'note' ) );

    global $wpdb;
    $wpdb->update( $wpdb->prefix . 'sp_chat_sessions', array(
        'status'        => 'escalated',
        'visitor_name'  => $name,
        'visitor_email' => $email,
        'updated_at'    => current_time( 'mysql' ),
    ), array( 'id' => $session->id ) );
    sp_chat_insert_msg( $session->id, 'system-note', 'Escalated to a human. ' . ( $email ? "Contact: $email" : '' ) . ( $note ? " Note: $note" : '' ), '', 0 );

    // Service addons (core tickets / SMTI HubSpot) hook this to create a real ticket.
    do_action( 'sp_chat_escalate', $session, array( 'name' => $name, 'email' => $email, 'note' => $note ) );

    return sp_chat_ok( array( 'reply' => "Thanks — I've passed this to the team. They'll follow up" . ( $email ? " at $email." : "." ) ) );
}

// Default escalation handler: safe capture only (no ticket-schema guessing). A Service
// addon can hook sp_chat_escalate at a later priority to create a real ticket.
add_action( 'sp_chat_escalate', 'sp_chat_default_escalation', 20, 2 );
function sp_chat_default_escalation( $session, $payload ) {
    // Placeholder for lead/ticket creation (Phase 2). Contact is already stored on the
    // session; nothing schema-specific is done here so it can't break on any install.
}

// ── AI call (reuses the AI addon's key; supports system + multi-turn) ─────────────

function sp_chat_ai_complete( $system, $messages ) {
    if ( ! function_exists( 'sp_ai_get_api_key' ) ) return new WP_Error( 'no_ai', 'AI is not configured.' );
    $api_key = sp_ai_get_api_key();
    if ( $api_key === '' ) return new WP_Error( 'no_ai', 'AI is not configured.' );

    $model = (string) get_option( 'sp_chat_model', '' );
    if ( $model === '' && function_exists( 'sp_ai_get_model' ) ) $model = sp_ai_get_model();
    if ( $model === '' ) $model = 'gpt-4o-mini';

    $is_anthropic = strpos( $model, 'claude' ) !== false;

    if ( $is_anthropic ) {
        $url     = 'https://api.anthropic.com/v1/messages';
        $headers = array( 'x-api-key' => $api_key, 'anthropic-version' => '2023-06-01', 'content-type' => 'application/json' );
        $body    = wp_json_encode( array( 'model' => $model, 'max_tokens' => 600, 'system' => $system, 'messages' => $messages ) );
    } else {
        $url     = 'https://api.openai.com/v1/chat/completions';
        $headers = array( 'Authorization' => 'Bearer ' . $api_key, 'Content-Type' => 'application/json' );
        $with_system = array_merge( array( array( 'role' => 'system', 'content' => $system ) ), $messages );
        $body    = wp_json_encode( array( 'model' => $model, 'max_tokens' => 600, 'messages' => $with_system ) );
    }

    $resp = wp_remote_post( $url, array( 'headers' => $headers, 'body' => $body, 'timeout' => 30 ) );
    if ( is_wp_error( $resp ) ) return $resp;
    $code = wp_remote_retrieve_response_code( $resp );
    $json = json_decode( wp_remote_retrieve_body( $resp ), true );
    if ( $code !== 200 ) {
        $msg = isset( $json['error']['message'] ) ? $json['error']['message'] : "AI error ($code)";
        return new WP_Error( 'ai_api', $msg );
    }
    if ( $is_anthropic ) {
        return isset( $json['content'][0]['text'] ) ? trim( $json['content'][0]['text'] ) : new WP_Error( 'ai_api', 'Empty response.' );
    }
    return isset( $json['choices'][0]['message']['content'] ) ? trim( $json['choices'][0]['message']['content'] ) : new WP_Error( 'ai_api', 'Empty response.' );
}

// ── Grounding: retrieve from Knowledge Core (public-visibility articles only) ─────

function sp_chat_kb_retrieve( $query, $k ) {
    global $wpdb;
    $tbl = $wpdb->prefix . 'sp_kb_articles';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$tbl'" ) !== $tbl ) return '';

    $vis   = (string) get_option( 'sp_chat_kb_visibility', 'public' );
    $words = array_filter( preg_split( '/\W+/', strtolower( (string) $query ) ), function( $w ) { return strlen( $w ) > 3; } );
    if ( ! $words ) return '';

    $likes = array(); $args = array( $vis );
    foreach ( array_slice( array_values( $words ), 0, 6 ) as $w ) {
        $likes[] = '(title LIKE %s OR content LIKE %s)';
        $like    = '%' . $wpdb->esc_like( $w ) . '%';
        $args[]  = $like; $args[] = $like;
    }
    $args[] = (int) $k;
    $sql  = "SELECT title, content FROM $tbl WHERE visibility = %s AND ( " . implode( ' OR ', $likes ) . " ) LIMIT %d";
    $rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ) );

    $out = '';
    foreach ( (array) $rows as $r ) {
        $out .= "# " . $r->title . "\n" . wp_strip_all_tags( $r->content ) . "\n\n";
    }
    $out = trim( $out );
    return function_exists( 'mb_substr' ) ? mb_substr( $out, 0, 4000 ) : substr( $out, 0, 4000 );
}

// ── Moderation ───────────────────────────────────────────────────────────────────

function sp_chat_screen_input( $text ) {
    $flags = array();
    if ( preg_match( '/\b(ignore|disregard|forget)\b.{0,40}\b(previous|prior|above|earlier|all)\b.{0,25}\b(instruction|instructions|rules|prompt)/i', $text )
      || preg_match( '/system\s*prompt|reveal.{0,25}(prompt|instructions)|you are now|pretend (to be|you)/i', $text ) ) {
        $flags[] = 'injection';
    }
    if ( sp_chat_has_profanity( $text ) ) $flags[] = 'profanity';
    return $flags;
}

function sp_chat_screen_output( $text ) {
    // Block system-prompt leakage.
    if ( strpos( $text, 'SAFETY RULES' ) !== false || strpos( $text, '=== ' ) !== false ) {
        return array( true, "I'm sorry, I can't share that. How can I help you with our products or services?" );
    }
    return array( false, $text );
}

function sp_chat_has_profanity( $text ) {
    $list = apply_filters( 'sp_chat_profanity_list', array( 'fuck', 'shit', 'bitch', 'asshole', 'cunt', 'bastard' ) );
    $lc   = strtolower( $text );
    foreach ( $list as $w ) {
        if ( preg_match( '/\b' . preg_quote( $w, '/' ) . '\b/i', $lc ) ) return true;
    }
    return false;
}

// ── Rate limiting + auto-ban ─────────────────────────────────────────────────────

function sp_chat_rl_sessions_today( $ip ) {
    global $wpdb;
    $since = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 86400 );
    return (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}sp_chat_sessions WHERE ip_hash = %s AND created_at > %s",
        sp_chat_hash_ip( $ip ), $since
    ) );
}

function sp_chat_session_msg_count( $sid ) {
    global $wpdb;
    return (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}sp_chat_messages WHERE session_id = %d AND role = 'user'", $sid
    ) );
}

function sp_chat_register_flag( $ip, $reason ) {
    $limits = sp_chat_get_limits();
    $hash   = sp_chat_hash_ip( $ip );
    $key    = 'sp_chat_fl_' . $hash;
    $n      = (int) get_transient( $key ) + 1;
    set_transient( $key, $n, $limits['autoban_window_min'] * 60 );

    if ( $n >= $limits['autoban_threshold'] ) {
        global $wpdb;
        $existing = $wpdb->get_row( $wpdb->prepare( "SELECT id, strikes FROM {$wpdb->prefix}sp_chat_ip_blocks WHERE ip_hash = %s", $hash ) );
        $strikes  = $existing ? ( (int) $existing->strikes + 1 ) : 1;
        $mins     = array( 15, 60, 1440 );
        $cool     = $mins[ min( $strikes - 1, 2 ) ];
        $wpdb->replace( $wpdb->prefix . 'sp_chat_ip_blocks', array(
            'ip_hash'    => $hash,
            'reason'     => 'auto: ' . $reason,
            'source'     => 'auto',
            'strikes'    => $strikes,
            'created_by' => 0,
            'expires_at' => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + $cool * 60 ),
            'created_at' => current_time( 'mysql' ),
        ) );
        delete_transient( $key );
    }
}

// ── Small helpers ────────────────────────────────────────────────────────────────

function sp_chat_req( $request, $field ) {
    $v = $request->get_param( $field );
    return $v === null ? '' : $v;
}

function sp_chat_ok( $data ) {
    return new WP_REST_Response( array_merge( array( 'ok' => true ), $data ), 200 );
}

function sp_chat_err( $code, $message, $status ) {
    return new WP_REST_Response( array( 'ok' => false, 'code' => $code, 'message' => $message ), $status );
}

function sp_chat_client_ip() {
    return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

function sp_chat_gen_token() {
    return wp_generate_password( 40, false );
}

function sp_chat_key_valid( $key ) {
    $expected = sp_chat_get_public_key();
    return $expected !== '' && is_string( $key ) && hash_equals( $expected, $key );
}

function sp_chat_origin_allowed( $origin ) {
    $domains = sp_chat_allowed_domains();
    if ( empty( $domains ) ) return true; // no allowlist set = open (dev). Set domains to lock down.
    $host = strtolower( (string) wp_parse_url( $origin, PHP_URL_HOST ) );
    foreach ( $domains as $d ) {
        $d = strtolower( trim( $d ) );
        if ( $d === '' ) continue;
        if ( $host === $d || substr( $host, - ( strlen( $d ) + 1 ) ) === '.' . $d ) return true;
    }
    return false;
}

function sp_chat_allowed_domains() {
    $raw = (string) get_option( 'sp_chat_allowed_domains', '' );
    $parts = array_filter( array_map( 'trim', preg_split( '/[\s,]+/', $raw ) ), 'strlen' );
    return array_values( $parts );
}

function sp_chat_session_by_token( $token ) {
    if ( ! is_string( $token ) || $token === '' ) return null;
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sp_chat_sessions WHERE token = %s AND status != 'closed' LIMIT 1", $token
    ) );
}

function sp_chat_insert_msg( $sid, $role, $body, $kb_refs, $flagged ) {
    global $wpdb;
    $wpdb->insert( $wpdb->prefix . 'sp_chat_messages', array(
        'session_id' => (int) $sid,
        'role'       => $role,
        'body'       => $body,
        'kb_refs'    => (string) $kb_refs,
        'flagged'    => (int) $flagged,
        'created_at' => current_time( 'mysql' ),
    ) );
}

function sp_chat_history( $sid, $limit ) {
    global $wpdb;
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT role, body FROM {$wpdb->prefix}sp_chat_messages WHERE session_id = %d AND role IN ('user','assistant') ORDER BY id DESC LIMIT %d",
        (int) $sid, (int) $limit
    ) );
    $rows = array_reverse( (array) $rows );
    $out  = array();
    foreach ( $rows as $r ) $out[] = array( 'role' => $r->role, 'content' => $r->body );
    return $out;
}

function sp_chat_greeting() {
    $g = (string) get_option( 'sp_chat_greeting', '' );
    return $g !== '' ? $g : "Hi! How can I help you today?";
}
