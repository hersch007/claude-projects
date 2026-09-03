<?php
/**
 * Call-center notification — STUB pending a real endpoint.
 *
 * Wire it up: /admin/settings.php -> "Call Center Webhook URL". Once set,
 * lw_notify_call_center() POSTs the lead payload there as JSON and this file
 * needs no further changes.
 *
 * Until a webhook URL is configured, "immediately notify our call center" still
 * does something real: it emails the fallback address set in the same settings
 * panel, and always logs the attempt to the activity table either way.
 */

function lw_notify_call_center( $lead_id, $payload ) {
    $settings    = lw_get_settings();
    $webhook_url = trim( (string) $settings['call_center_webhook_url'] );
    $result = array( 'channel' => '', 'ok' => false, 'detail' => '' );

    if ( $webhook_url !== '' ) {
        $result = lw_post_json( $webhook_url, $payload );
        $result['channel'] = 'webhook';
    } else {
        $fallback_email = trim( (string) $settings['call_center_fallback_email'] );
        if ( $fallback_email !== '' ) {
            $subject = 'Lifeward: caller wants to talk right now';
            $note    = trim( (string) ( $payload['message'] ?? '' ) );
            $body    = "A visitor on the Lifeward landing page asked to speak with someone immediately.\n\n"
                     . "Name: {$payload['name']}\nPhone: {$payload['phone']}\nEmail: {$payload['email']}\n"
                     . ( $note !== '' ? "Note from visitor: $note\n" : '' )
                     . 'Requested at: ' . gmdate( 'Y-m-d H:i:s' ) . " UTC\n\n"
                     . "No call-center webhook is configured yet — set one at /admin/settings.php to notify the dialer/ticketing system directly instead of this email.";
            $sent = mail( $fallback_email, $subject, $body );
            $result = array( 'channel' => 'email', 'ok' => (bool) $sent, 'detail' => $fallback_email );
        } else {
            $result = array( 'channel' => 'none', 'ok' => false, 'detail' => 'No webhook URL or fallback email configured.' );
        }
    }

    if ( $lead_id > 0 ) {
        lw_log_activity( $lead_id, 'call_center_notify', array( 'payload' => $payload, 'result' => $result ) );
    }

    return $result;
}

/** Minimal dependency-free JSON POST (cURL if available, stream context fallback). */
function lw_post_json( $url, $payload ) {
    $body = json_encode( $payload );

    if ( function_exists( 'curl_init' ) ) {
        $ch = curl_init( $url );
        curl_setopt_array( $ch, array(
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => array( 'Content-Type: application/json' ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ) );
        curl_exec( $ch );
        $code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $err  = curl_error( $ch );
        curl_close( $ch );
        if ( $err ) return array( 'ok' => false, 'detail' => $err );
        return array( 'ok' => $code >= 200 && $code < 300, 'detail' => "HTTP $code" );
    }

    $context = stream_context_create( array( 'http' => array(
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\n",
        'content' => $body,
        'timeout' => 10,
        'ignore_errors' => true,
    ) ) );
    $result = @file_get_contents( $url, false, $context );
    $status_line = isset( $http_response_header[0] ) ? $http_response_header[0] : '';
    return array( 'ok' => $result !== false && strpos( $status_line, '2' ) !== false, 'detail' => $status_line ?: 'request failed' );
}
