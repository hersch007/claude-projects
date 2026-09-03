<?php
/**
 * [sp_lifeward_landing] shortcode template — Rachel's speech bubble + the three
 * main buttons, plus empty containers the widget JS fills in as the funnel
 * progresses. All copy after the first click comes from the server (see
 * includes/rest-endpoints.php) so this file only needs the opening state.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$greeting = function_exists( 'sp_lifeward_greeting_text' ) ? sp_lifeward_greeting_text() : "Hi, I'm Rachel. What can I help you with today?";
?>
<div class="sp-lifeward-app" id="sp-lifeward-app">
    <div class="sp-lifeward-avatar" aria-hidden="true">
        <svg viewBox="0 0 64 64" width="64" height="64">
            <circle cx="32" cy="32" r="32" fill="#e0f2fe"/>
            <circle cx="32" cy="26" r="12" fill="#fbcfe8"/>
            <path d="M14 54c1-10 8-16 18-16s17 6 18 16" fill="#38bdf8"/>
            <circle cx="27" cy="25" r="1.6" fill="#0f172a"/>
            <circle cx="37" cy="25" r="1.6" fill="#0f172a"/>
            <path d="M27 31c2 2 8 2 10 0" stroke="#0f172a" stroke-width="1.4" fill="none" stroke-linecap="round"/>
            <path d="M22 46c0 4 4 6 10 6s10-2 10-6" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round"/>
            <circle cx="32" cy="50" r="2" fill="#fff"/>
        </svg>
    </div>

    <div class="sp-lifeward-name">Rachel</div>
    <div class="sp-lifeward-role">Here to help</div>

    <div class="sp-lifeward-bubble" id="sp-lifeward-bubble"><?php echo esc_html( $greeting ); ?></div>

    <div class="sp-lifeward-buttons" id="sp-lifeward-buttons">
        <button type="button" class="sp-lifeward-btn sp-lifeward-btn-primary" data-step="call_now">Yes, I'm interested. Please call now.</button>
        <button type="button" class="sp-lifeward-btn" data-step="send_info">Yes, send me more information.</button>
        <button type="button" class="sp-lifeward-btn sp-lifeward-btn-quiet" data-step="not_interested">No, I'm not interested.</button>
    </div>

    <div class="sp-lifeward-fields" id="sp-lifeward-fields"></div>
    <div class="sp-lifeward-error" id="sp-lifeward-error" role="alert"></div>
</div>
