<?php
/**
 * Anthropic Claude client — same API LAWN ACE uses (api.anthropic.com/v1/messages),
 * just called with plain cURL instead of WordPress's wp_remote_post(). No SDK
 * dependency, no Composer.
 */

function lw_ai_configured() {
    $settings = lw_get_settings();
    return trim( (string) $settings['anthropic_api_key'] ) !== '';
}

/**
 * @param string $system   System prompt.
 * @param array  $messages array of ['role' => 'user'|'assistant', 'content' => '...']
 * @return string|WP_Error-like array — either the reply text, or array('error' => '...')
 */
function lw_ai_complete( $system, $messages ) {
    $settings = lw_get_settings();
    $api_key  = trim( (string) $settings['anthropic_api_key'] );
    if ( $api_key === '' ) {
        return array( 'error' => 'AI chat is not configured yet.' );
    }
    $model = trim( (string) $settings['ai_model'] ) ?: 'claude-sonnet-5';

    $body = json_encode( array(
        'model'      => $model,
        'max_tokens' => 700,
        'system'     => $system,
        'messages'   => $messages,
    ) );

    $headers = array(
        'x-api-key: ' . $api_key,
        'anthropic-version: 2023-06-01',
        'content-type: application/json',
    );

    if ( function_exists( 'curl_init' ) ) {
        $ch = curl_init( 'https://api.anthropic.com/v1/messages' );
        curl_setopt_array( $ch, array(
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ) );
        $raw  = curl_exec( $ch );
        $err  = curl_error( $ch );
        $code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );
        if ( $err ) { error_log( 'Lifeward AI cURL error: ' . $err ); return array( 'error' => $err ); }
    } else {
        $context = stream_context_create( array( 'http' => array(
            'method'  => 'POST',
            'header'  => implode( "\r\n", $headers ) . "\r\n",
            'content' => $body,
            'timeout' => 30,
            'ignore_errors' => true,
        ) ) );
        $raw = @file_get_contents( 'https://api.anthropic.com/v1/messages', false, $context );
        $status_line = isset( $http_response_header[0] ) ? $http_response_header[0] : '';
        preg_match( '/\s(\d{3})\s/', $status_line, $m );
        $code = isset( $m[1] ) ? (int) $m[1] : 0;
        if ( $raw === false ) return array( 'error' => 'Request failed.' );
    }

    $json = json_decode( (string) $raw, true );
    if ( $code !== 200 ) {
        $msg = isset( $json['error']['message'] ) ? $json['error']['message'] : "AI error ($code)";
        error_log( "Lifeward AI error ($code) model=$model: $msg" );
        return array( 'error' => $msg );
    }
    // Claude may return a "thinking" block before the actual "text" block (extended
    // thinking) — scan for the first real text block rather than assuming content[0].
    if ( isset( $json['content'] ) && is_array( $json['content'] ) ) {
        foreach ( $json['content'] as $block ) {
            if ( isset( $block['type'], $block['text'] ) && $block['type'] === 'text' && trim( $block['text'] ) !== '' ) {
                return trim( $block['text'] );
            }
        }
    }
    error_log( 'Lifeward AI: no text block in response: ' . substr( (string) $raw, 0, 500 ) );
    return array( 'error' => 'Empty response from AI.' );
}
