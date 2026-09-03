<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_ajax_nopriv_aichat_message', 'aichat_handle_message' );
add_action( 'wp_ajax_aichat_message',        'aichat_handle_message' );

function aichat_handle_message() {
    if ( ! check_ajax_referer( 'aichat_nonce', 'nonce', false ) ) {
        wp_send_json_error( 'Invalid request', 403 );
    }

    if ( ! aichat_check_rate_limit() ) {
        wp_send_json_error( 'Too many messages. Please wait a moment.', 429 );
    }

    $api_key = get_option( 'aichat_api_key', '' );
    if ( empty( $api_key ) ) {
        wp_send_json_error( 'Chat is not configured yet.', 500 );
    }

    // Build history
    $raw      = isset( $_POST['history'] ) ? $_POST['history'] : '[]';
    $history  = json_decode( wp_unslash( $raw ), true );
    if ( ! is_array( $history ) ) $history = [];

    $messages = [];
    foreach ( $history as $entry ) {
        $role    = $entry['role'] === 'user' ? 'user' : 'assistant';
        $content = sanitize_textarea_field( $entry['content'] ?? '' );
        if ( $content ) {
            $messages[] = [ 'role' => $role, 'content' => $content ];
        }
    }

    // Photo upload
    $has_photo  = ! empty( $_POST['image_data'] ) && ! empty( $_POST['image_type'] );
    $image_data = $has_photo ? sanitize_text_field( $_POST['image_data'] ) : '';
    $image_type = $has_photo ? sanitize_text_field( $_POST['image_type'] ) : '';

    if ( $has_photo && ! empty( $messages ) ) {
        $last = end( $messages );
        if ( $last['role'] === 'user' ) {
            array_pop( $messages );
            $messages[] = [
                'role'    => 'user',
                'content' => [
                    [ 'type' => 'image', 'source' => [
                        'type'       => 'base64',
                        'media_type' => $image_type,
                        'data'       => $image_data,
                    ]],
                    [ 'type' => 'text', 'text' => $last['content'] ],
                ],
            ];
        }
    }

    $messages = array_slice( $messages, - AICHAT_MAX_HISTORY );

    $system_prompt = get_option( 'aichat_system_prompt', '' );

    $body = wp_json_encode( [
        'model'      => AICHAT_MODEL,
        'max_tokens' => AICHAT_MAX_TOKENS,
        'system'     => $system_prompt,
        'messages'   => $messages,
    ] );

    $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
        'timeout' => 45,
        'headers' => [
            'x-api-key'         => $api_key,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ],
        'body' => $body,
    ] );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( 'Connection error. Please try again.', 500 );
    }

    $data  = json_decode( wp_remote_retrieve_body( $response ), true );
    $reply = $data['content'][0]['text'] ?? '';

    if ( empty( $reply ) ) {
        wp_send_json_error( 'No response received. Please try again.', 500 );
    }

    // Lead capture
    $lead = aichat_parse_lead( $reply );
    if ( $lead ) {
        $all_messages = $messages;
        $all_messages[] = [ 'role' => 'assistant', 'content' => aichat_strip_lead_tag( $reply ) ];
        aichat_notify_lead( $lead, $all_messages );
        $reply = aichat_strip_lead_tag( $reply );
    }

    wp_send_json_success( [ 'reply' => $reply ] );
}
