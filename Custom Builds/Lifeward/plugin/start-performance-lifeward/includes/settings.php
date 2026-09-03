<?php
/**
 * Settings → Lifeward tab: call-center webhook, fallback notify email, and the
 * info-package email content. Mirrors the tab/section/save-handler pattern used
 * by Chat Core (see sp_chat_settings_tab / sp_chat_settings_section in
 * plugin/start-performance-chat/start-performance-chat.php).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'plugins_loaded', function() {
    if ( ! function_exists( 'sp_register_view' ) ) return;
    add_filter( 'sp_settings_anchor_tabs', 'sp_lifeward_settings_tab' );
    add_action( 'sp_settings_sections',    'sp_lifeward_settings_section' );
    add_action( 'sp_post_handler_lifeward', 'sp_lifeward_save_settings' );
}, 21 );

function sp_lifeward_settings_tab( $tabs ) {
    $tabs[] = array( 'id' => 'section-lifeward', 'label' => 'Lifeward' );
    return $tabs;
}

function sp_lifeward_settings_section() {
    $greeting  = get_option( 'sp_lifeward_greeting', "Hi, I'm Rachel, your Lifeward assistant — here to help you understand your options with the same warmth and care you'd expect from someone who's spent years in patient care. What can I help you with today?" );
    $webhook   = get_option( 'sp_lifeward_call_center_webhook_url', '' );
    $fallback  = get_option( 'sp_lifeward_call_center_fallback_email', get_option( 'admin_email' ) );
    $subject   = get_option( 'sp_lifeward_info_subject', 'Your Lifeward information package' );
    $body      = get_option( 'sp_lifeward_info_body', "Thanks for your interest in Lifeward!\n\nWe've attached our information package. If you have any questions, just reply to this email.\n\n— The Lifeward Team" );
    ?>
    <div id="section-lifeward" class="sp-card sp-form-card sp-settings-section" style="margin-top:16px">
        <h2 class="sp-section-heading">Lifeward Landing Page</h2>
        <p style="font-size:13px;color:#64748b;margin-bottom:16px">Controls for the Rachel-led lead funnel on the Lifeward site. Salesforce sync is currently a mock (see includes/class-salesforce-client.php) — nothing here activates it; it exists purely so the funnel has something real to hit once Connected App credentials are available.</p>

        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="lifeward">
            <input type="hidden" name="sp_id" value="0">

            <div class="sp-field" style="max-width:640px">
                <label>Rachel's greeting</label>
                <textarea name="sp_lifeward_greeting" rows="3"><?php echo esc_textarea( $greeting ); ?></textarea>
                <span class="sp-hint">Shown when the landing page first loads, before any button is clicked.</span>
            </div>

            <div class="sp-field" style="max-width:520px;margin-top:16px">
                <label>Call-center webhook URL</label>
                <input type="url" name="sp_lifeward_call_center_webhook_url" value="<?php echo esc_attr( $webhook ); ?>" placeholder="https://your-call-center-system.example.com/webhook">
                <span class="sp-hint">When a visitor asks to talk right now, we POST their name/phone/email here as JSON. Leave blank to fall back to email notification below.</span>
            </div>

            <div class="sp-field" style="max-width:520px;margin-top:16px">
                <label>Fallback notification email</label>
                <input type="email" name="sp_lifeward_call_center_fallback_email" value="<?php echo esc_attr( $fallback ); ?>">
                <span class="sp-hint">Used only when no webhook URL is set above.</span>
            </div>

            <div class="sp-field" style="max-width:640px;margin-top:16px">
                <label>Info package email — subject</label>
                <input type="text" name="sp_lifeward_info_subject" value="<?php echo esc_attr( $subject ); ?>">
            </div>

            <div class="sp-field" style="max-width:640px;margin-top:16px">
                <label>Info package email — body</label>
                <textarea name="sp_lifeward_info_body" rows="6"><?php echo esc_textarea( $body ); ?></textarea>
                <span class="sp-hint">Sent when a visitor asks for more information. Plain text for now — attach a PDF or link as needed.</span>
            </div>

            <button type="submit" class="sp-btn sp-btn-primary" style="margin-top:16px">Save Lifeward Settings</button>
        </form>
    </div>
    <?php
}

function sp_lifeward_save_settings( $id ) {
    if ( ! function_exists( 'sp_is_authed' ) || ! sp_is_authed() ) return;

    update_option( 'sp_lifeward_greeting', sanitize_textarea_field( isset( $_POST['sp_lifeward_greeting'] ) ? $_POST['sp_lifeward_greeting'] : '' ) );
    update_option( 'sp_lifeward_call_center_webhook_url',   esc_url_raw(   isset( $_POST['sp_lifeward_call_center_webhook_url'] )   ? $_POST['sp_lifeward_call_center_webhook_url']   : '' ) );
    update_option( 'sp_lifeward_call_center_fallback_email', sanitize_email( isset( $_POST['sp_lifeward_call_center_fallback_email'] ) ? $_POST['sp_lifeward_call_center_fallback_email'] : '' ) );
    update_option( 'sp_lifeward_info_subject', sanitize_text_field( isset( $_POST['sp_lifeward_info_subject'] ) ? $_POST['sp_lifeward_info_subject'] : '' ) );
    update_option( 'sp_lifeward_info_body',    sanitize_textarea_field( isset( $_POST['sp_lifeward_info_body'] ) ? $_POST['sp_lifeward_info_body'] : '' ) );

    wp_redirect( home_url( '/sp-app/?view=settings&saved=1#section-lifeward' ) ); exit;
}
