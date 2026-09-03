<?php
/**
 * Free-form chat endpoint: POST {token, message} -> {reply}. Shares the exact
 * same session/token as api.php's guided funnel (inc/funnel.php) — a lead
 * captured through chat and a lead captured through the buttons are the same
 * underlying record if the visitor used both.
 */

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/slots.php';
require_once __DIR__ . '/inc/salesforce.php';
require_once __DIR__ . '/inc/call_center.php';
require_once __DIR__ . '/inc/funnel.php';
require_once __DIR__ . '/inc/ai.php';
require_once __DIR__ . '/inc/chat.php';

header( 'Content-Type: application/json; charset=utf-8' );

function lw_chat_send( $data, $status = 200 ) {
    http_response_code( $status );
    echo json_encode( $data );
    exit;
}

if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    lw_chat_send( array( 'ok' => false, 'message' => 'POST only.' ), 405 );
}

if ( ! lw_ai_configured() ) {
    lw_chat_send( array( 'ok' => false, 'message' => "Chat isn't set up yet — please use the buttons above, or check back soon." ), 503 );
}

$raw  = file_get_contents( 'php://input' );
$body = json_decode( $raw, true );
if ( ! is_array( $body ) ) $body = $_POST;

$ip_hash = lw_hash_ip( lw_client_ip() );
if ( lw_rate_limited( 'chat', $ip_hash, 30, gmdate( 'YmdH' ) ) ) {
    lw_chat_send( array( 'ok' => false, 'message' => 'Too many messages — please try again in a bit.' ), 429 );
}

$token   = isset( $body['token'] ) ? substr( trim( (string) $body['token'] ), 0, 64 ) : '';
$message = isset( $body['message'] ) ? trim( (string) $body['message'] ) : '';

if ( $message === '' ) {
    lw_chat_send( array( 'ok' => false, 'message' => 'A message is required.' ), 400 );
}
$msg_len = function_exists( 'mb_strlen' ) ? mb_strlen( $message ) : strlen( $message );
if ( $msg_len > 2000 ) {
    lw_chat_send( array( 'ok' => false, 'message' => 'That message is too long.' ), 400 );
}

$session = lw_get_or_create_session( $token, $ip_hash );

$msg_count_stmt = lw_db()->prepare( 'SELECT COUNT(*) FROM chat_messages WHERE session_id = ? AND role = ?' );
$msg_count_stmt->execute( array( $session['id'], 'user' ) );
if ( (int) $msg_count_stmt->fetchColumn() >= 40 ) {
    lw_chat_send( array( 'ok' => false, 'message' => "This chat has reached its limit — please use the buttons above to reach our team." ), 429 );
}

lw_chat_screen_input( $message ); // flags reserved for future auto-moderation; input is still answered, guarded by the system prompt

$history   = lw_chat_history( $session['id'], 12 );
$history[] = array( 'role' => 'user', 'content' => $message );

$ai = lw_ai_complete( lw_chat_system_prompt(), $history );

lw_chat_insert_message( $session['id'], 'user', $message );

if ( is_array( $ai ) && isset( $ai['error'] ) ) {
    $reply = "Sorry — I'm having trouble right now. Would you like to use the buttons above to reach our team instead?";
    lw_chat_insert_message( $session['id'], 'assistant', $reply );
    lw_chat_send( array( 'ok' => true, 'token' => $session['token'], 'reply' => $reply ) );
}

$lead = lw_chat_parse_lead_tag( $ai );
if ( $lead ) {
    $transcript_text = implode( ' ', array_column( $history, 'content' ) ) . ' ' . $ai;
    $session = lw_chat_handle_lead( $session, $lead, $transcript_text );
    $ai = lw_chat_strip_lead_tag( $ai );
}

$button_ids = lw_chat_parse_buttons_tag( $ai );
if ( $button_ids ) {
    $ai = lw_chat_strip_buttons_tag( $ai );
}

$reply = lw_chat_screen_output( $ai );
lw_chat_insert_message( $session['id'], 'assistant', $reply );

$out = array( 'ok' => true, 'token' => $session['token'], 'reply' => $reply );
if ( $button_ids ) $out['buttons'] = lw_chat_button_defs( $button_ids );

lw_chat_send( $out );
