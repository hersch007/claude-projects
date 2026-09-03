<?php
/**
 * Single public endpoint driving the whole funnel: POST {token, step, name?,
 * email?, phone?, slot?} -> {reply, buttons?, fields?, done?}. No auth — this is
 * the public landing page's own backend, same trust level as the page itself.
 */

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/slots.php';
require_once __DIR__ . '/inc/salesforce.php';
require_once __DIR__ . '/inc/call_center.php';
require_once __DIR__ . '/inc/funnel.php';

header( 'Content-Type: application/json; charset=utf-8' );

function lw_send( $data, $status = 200 ) {
    http_response_code( $status );
    echo json_encode( $data );
    exit;
}

if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    lw_send( array( 'ok' => false, 'message' => 'POST only.' ), 405 );
}

$raw  = file_get_contents( 'php://input' );
$body = json_decode( $raw, true );
if ( ! is_array( $body ) ) $body = $_POST;

$ip_hash = lw_hash_ip( lw_client_ip() );

if ( lw_rate_limited( 'step', $ip_hash, 60, gmdate( 'YmdH' ) ) ) {
    lw_send( array( 'ok' => false, 'message' => 'Too many requests. Please try again shortly.' ), 429 );
}

$token = isset( $body['token'] ) ? substr( trim( (string) $body['token'] ), 0, 64 ) : '';
$step  = isset( $body['step'] )  ? preg_replace( '/[^a-z0-9_]/', '', strtolower( (string) $body['step'] ) ) : '';
if ( $step === '' ) lw_send( array( 'ok' => false, 'message' => 'A step is required.' ), 400 );

$products_raw = isset( $body['products'] ) && is_array( $body['products'] ) ? $body['products'] : array();
$products = array();
foreach ( $products_raw as $p ) {
    if ( is_string( $p ) ) $products[] = substr( $p, 0, 40 );
    if ( count( $products ) >= 10 ) break;
}

$in = array(
    'name'     => trim( substr( (string) ( $body['name']    ?? '' ), 0, 191 ) ),
    'email'    => trim( substr( (string) ( $body['email']   ?? '' ), 0, 191 ) ),
    'phone'    => trim( substr( (string) ( $body['phone']   ?? '' ), 0, 50 ) ),
    'slot'     => trim( substr( (string) ( $body['slot']    ?? '' ), 0, 40 ) ),
    'message'  => trim( substr( (string) ( $body['message'] ?? '' ), 0, 1000 ) ),
    'day'      => trim( substr( (string) ( $body['day']     ?? '' ), 0, 10 ) ),
    'time'     => trim( substr( (string) ( $body['time']    ?? '' ), 0, 5 ) ),
    'products' => $products,
);

$terminal_steps = array( 'not_interested', 'submit_call_now', 'submit_schedule', 'submit_specific_time', 'submit_info_request' );
if ( in_array( $step, $terminal_steps, true ) && lw_rate_limited( 'terminal', $ip_hash, 30, gmdate( 'Ymd' ) ) ) {
    lw_send( array( 'ok' => false, 'message' => 'Too many submissions from this network today.' ), 429 );
}

$session = lw_get_or_create_session( $token, $ip_hash );
$out     = lw_transition( $session, $step, $in );

lw_send( array_merge( array( 'ok' => true, 'token' => $session['token'] ), $out ) );
