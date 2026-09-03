<?php
/**
 * Settings storage — a JSON file in data/ instead of a database table, so the
 * admin panel can read/write it with zero schema migrations. Not web-accessible
 * (see data/.htaccess).
 */

function lw_settings_path() {
    return __DIR__ . '/../data/settings.json';
}

function lw_settings_defaults() {
    return array(
        'greeting'  => "Happy to answer any questions before you decide — or pick an option below.",
        'call_center_webhook_url'   => '',
        'call_center_fallback_email'=> '',
        'info_subject' => 'Your Lifeward information package',
        'info_body'    => "Thanks for your interest in Lifeward!\n\nWe've attached our information package. If you have any questions, just reply to this email.\n\n— The Lifeward Team",
        'admin_password_hash' => '', // set on first visit to /admin/
        'anthropic_api_key' => '', // chat is disabled until this is set — see inc/ai.php
        'ai_model' => 'claude-sonnet-5',
    );
}

function lw_get_settings() {
    $defaults = lw_settings_defaults();
    $path = lw_settings_path();
    if ( ! file_exists( $path ) ) return $defaults;
    $stored = json_decode( (string) file_get_contents( $path ), true );
    if ( ! is_array( $stored ) ) return $defaults;
    return array_merge( $defaults, $stored );
}

function lw_save_settings( $settings ) {
    $dir = dirname( lw_settings_path() );
    if ( ! is_dir( $dir ) ) mkdir( $dir, 0775, true );
    $current = lw_get_settings();
    $merged  = array_merge( $current, $settings );
    file_put_contents( lw_settings_path(), json_encode( $merged, JSON_PRETTY_PRINT ) );
    return $merged;
}
