<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_ajax_lawnace_chat',        'lawnace_chatbot_handler' );
add_action( 'wp_ajax_nopriv_lawnace_chat', 'lawnace_chatbot_handler' );

function lawnace_chatbot_handler() {
    check_ajax_referer( 'lawnace_chat_nonce', 'nonce' );

    if ( ! lawnace_chatbot_check_rate_limit() ) {
        wp_send_json_error( 'Too many requests. Please call 706-364-2338.', 429 );
    }

    $api_key = get_option( 'lawnace_anthropic_key', '' );

    if ( empty( $api_key ) ) {
        wp_send_json_error( 'Chat assistant is not configured.', 503 );
    }

    $session_id = isset( $_POST['session_id'] )
        ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) )
        : wp_generate_uuid4();

    $raw_messages = isset( $_POST['messages'] )
        ? wp_unslash( $_POST['messages'] )
        : '';

    $messages = json_decode( $raw_messages, true );

    if ( ! is_array( $messages ) || empty( $messages ) ) {
        wp_send_json_error( 'Invalid message format.', 400 );
    }

    $clean_messages = array();

    foreach ( $messages as $msg ) {
        if ( empty( $msg['role'] ) || empty( $msg['content'] ) ) {
            continue;
        }

        $role = sanitize_text_field( $msg['role'] );

        if ( ! in_array( $role, array( 'user', 'assistant' ), true ) ) {
            continue;
        }

        $clean_messages[] = array(
            'role'    => $role,
            'content' => sanitize_textarea_field( $msg['content'] ),
        );
    }

    if ( count( $clean_messages ) > LAWNACE_MAX_HISTORY ) {
        $clean_messages = array_slice( $clean_messages, -LAWNACE_MAX_HISTORY );
    }

    // Handle photo upload — replace last user message with vision content block
    $image_data = isset( $_POST['image_data'] ) ? sanitize_text_field( wp_unslash( $_POST['image_data'] ) ) : '';
    $image_type = isset( $_POST['image_type'] ) ? sanitize_text_field( wp_unslash( $_POST['image_type'] ) ) : 'image/jpeg';

    $allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
    if ( ! in_array( $image_type, $allowed_types, true ) ) {
        $image_data = '';
    }

    if ( ! empty( $image_data ) ) {
        // Find and replace the last user message with a vision content block
        for ( $i = count( $clean_messages ) - 1; $i >= 0; $i-- ) {
            if ( $clean_messages[ $i ]['role'] === 'user' ) {
                $text_content = $clean_messages[ $i ]['content'];
                // Strip the "[Photo attached]" prefix from the history text
                $text_content = preg_replace( '/^\[Photo.*?\]\s*/i', '', $text_content );

                $clean_messages[ $i ]['content'] = array(
                    array(
                        'type'   => 'image',
                        'source' => array(
                            'type'       => 'base64',
                            'media_type' => $image_type,
                            'data'       => $image_data,
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'text' => $text_content ?: 'I sent a photo of my lawn. What do you see and what should I do about it?',
                    ),
                );
                break;
            }
        }
    }

    $response = wp_remote_post(
        'https://api.anthropic.com/v1/messages',
        array(
            'timeout' => 45,
            'headers' => array(
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ),
            'body' => wp_json_encode(
                array(
                    'model'      => LAWNACE_MODEL,
                    'max_tokens' => LAWNACE_MAX_TOKENS,
                    'system'     => lawnace_chatbot_system_prompt(),
                    'messages'   => $clean_messages,
                )
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( 'Connection issue. Please try again.', 502 );
    }

    $code = wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $code !== 200 || empty( $body['content'][0]['text'] ) ) {
        error_log( 'LawnAce chatbot bad response: ' . wp_json_encode( $body ) );
        wp_send_json_error( 'Assistant error. Please try again.', 502 );
    }

    $reply = $body['content'][0]['text'];
    $lead  = lawnace_chatbot_parse_lead( $reply );
    $log_new_lead = false;

    if ( $lead ) {
        $reply = lawnace_chatbot_strip_lead_tag( $reply );

        // If this session already has a lead row (customer gave phone/address after the first tag),
        // merge the new fields into it instead of creating a duplicate lead.
        global $wpdb;
        $existing_lead_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, message FROM {$wpdb->prefix}lawnace_chat_logs WHERE session_id = %s AND role = 'lead' ORDER BY id DESC LIMIT 1",
            $session_id
        ) );

        if ( $existing_lead_row ) {
            list( $merged_lead, $lead_changed ) = lawnace_merge_lead( lawnace_parse_lead_row( $existing_lead_row->message ), $lead );
            if ( $lead_changed ) {
                $wpdb->update(
                    $wpdb->prefix . 'lawnace_chat_logs',
                    array( 'message' => lawnace_lead_row_text( $merged_lead ) ),
                    array( 'id' => (int) $existing_lead_row->id )
                );
                lawnace_chatbot_notify_lead( $merged_lead, $clean_messages, true );
            }
        } else {
            $log_new_lead = true;
            lawnace_chatbot_notify_lead( $lead, $clean_messages );
        }
    }

    $last_user_message = '';

    foreach ( array_reverse( $clean_messages ) as $msg ) {
        if ( $msg['role'] === 'user' ) {
            $last_user_message = $msg['content'];
            break;
        }
    }

    if ( ! empty( $last_user_message ) ) {
        lawnace_chatbot_log_message( $session_id, 'user', $last_user_message );
    }

    lawnace_chatbot_log_message( $session_id, 'assistant', $reply );

    if ( $lead && $log_new_lead ) {
        lawnace_chatbot_log_message( $session_id, 'lead', lawnace_lead_row_text( $lead ) );
    }

    wp_send_json_success(
        array(
            'reply'      => $reply,
            'lead'       => $lead ? true : false,
            'session_id' => $session_id,
        )
    );
}

/*
|--------------------------------------------------------------------------
| RATING HANDLER
|--------------------------------------------------------------------------
*/

add_action( 'wp_ajax_lawnace_rate',        'lawnace_rating_handler' );
add_action( 'wp_ajax_nopriv_lawnace_rate', 'lawnace_rating_handler' );

function lawnace_rating_handler() {
    check_ajax_referer( 'lawnace_chat_nonce', 'nonce' );

    global $wpdb;

    $session_id = sanitize_text_field( wp_unslash( $_POST['session_id'] ?? '' ) );
    $rating     = (int) ( $_POST['rating'] ?? 0 );

    if ( ! in_array( $rating, array( 1, -1 ), true ) || empty( $session_id ) ) {
        wp_send_json_error( 'Invalid rating.' );
    }

    $table   = $wpdb->prefix . 'lawnace_chat_logs';
    $charset = $wpdb->get_charset_collate();

    // Ensure ratings table exists
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}lawnace_ratings (
        id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        session_id VARCHAR(64)     NOT NULL,
        rating     TINYINT         NOT NULL,
        created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY session_id (session_id)
    ) {$charset};" );

    $wpdb->insert(
        $wpdb->prefix . 'lawnace_ratings',
        array(
            'session_id' => $session_id,
            'rating'     => $rating,
            'created_at' => current_time( 'mysql' ),
        )
    );

    wp_send_json_success();
}
