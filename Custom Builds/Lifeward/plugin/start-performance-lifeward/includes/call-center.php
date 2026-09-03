<?php
/**
 * Call-center notification — STUB pending a real endpoint.
 *
 * Wire it up: Settings → Lifeward → "Call Center Webhook URL" (see
 * includes/settings.php). Once that option is set, sp_lifeward_notify_call_center()
 * POSTs the lead payload there as JSON and this file needs no further changes.
 *
 * Until a webhook URL is configured, "immediately notify our call center" still
 * does something real: it emails the fallback address set in the same settings
 * panel, and always logs the attempt to the Core System's sp_activity table so
 * it's visible on the lead record either way.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function sp_lifeward_notify_call_center( $lead_id, $payload ) {
    $webhook_url = trim( (string) get_option( 'sp_lifeward_call_center_webhook_url', '' ) );
    $result = array( 'channel' => '', 'ok' => false, 'detail' => '' );

    if ( $webhook_url !== '' ) {
        $resp = wp_remote_post( $webhook_url, array(
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( $payload ),
            'timeout' => 10,
        ) );
        if ( is_wp_error( $resp ) ) {
            $result = array( 'channel' => 'webhook', 'ok' => false, 'detail' => $resp->get_error_message() );
        } else {
            $code = wp_remote_retrieve_response_code( $resp );
            $result = array( 'channel' => 'webhook', 'ok' => $code >= 200 && $code < 300, 'detail' => "HTTP $code" );
        }
    } else {
        $fallback_email = trim( (string) get_option( 'sp_lifeward_call_center_fallback_email', get_option( 'admin_email' ) ) );
        if ( $fallback_email !== '' ) {
            $subject = 'Lifeward: caller wants to talk right now';
            $body    = "A visitor on the Lifeward landing page asked to speak with someone immediately.\n\n"
                     . "Name: {$payload['name']}\nPhone: {$payload['phone']}\nEmail: {$payload['email']}\n"
                     . "Requested at: " . current_time( 'mysql' ) . "\n\n"
                     . "No call-center webhook is configured yet — set one under Settings \xe2\x86\x92 Lifeward to notify the dialer/ticketing system directly instead of this email.";
            $sent = wp_mail( $fallback_email, $subject, $body );
            $result = array( 'channel' => 'email', 'ok' => (bool) $sent, 'detail' => $fallback_email );
        } else {
            $result = array( 'channel' => 'none', 'ok' => false, 'detail' => 'No webhook URL or fallback email configured.' );
        }
    }

    if ( $lead_id > 0 ) {
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'sp_activity', array(
            'record_type' => 'lead',
            'record_id'   => (int) $lead_id,
            'action'      => 'call_center_notify',
            'detail'      => wp_json_encode( array( 'payload' => $payload, 'result' => $result ) ),
            'created_by'  => 0,
            'created_at'  => current_time( 'mysql' ),
        ) );
    }

    return $result;
}
