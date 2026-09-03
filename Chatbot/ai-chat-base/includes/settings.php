<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'aichat_add_settings_page' );

function aichat_add_settings_page() {
    add_options_page(
        'AI Chat Settings',
        'AI Chat',
        'manage_options',
        'ai-chat-base',
        'aichat_render_settings_page'
    );
}

add_action( 'admin_init', 'aichat_register_settings' );

function aichat_register_settings() {
    $fields = [
        'aichat_api_key',
        'aichat_notify_email',
        'aichat_business_name',
        'aichat_bot_name',
        'aichat_phone',
        'aichat_tagline',
        'aichat_color_primary',
        'aichat_color_accent',
        'aichat_greeting',
        'aichat_chips',
        'aichat_system_prompt',
    ];
    foreach ( $fields as $field ) {
        register_setting( 'aichat_settings', $field, [ 'sanitize_callback' => 'sanitize_textarea_field' ] );
    }
}

function aichat_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    ?>
    <div class="wrap">
        <h1>AI Chat Settings</h1>

        <?php if ( isset( $_GET['settings-updated'] ) ) : ?>
            <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields( 'aichat_settings' ); ?>

            <h2>API & Notifications</h2>
            <table class="form-table">
                <tr>
                    <th><label for="aichat_api_key">Anthropic API Key</label></th>
                    <td><input type="password" id="aichat_api_key" name="aichat_api_key" value="<?php echo esc_attr( get_option( 'aichat_api_key' ) ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="aichat_notify_email">Lead Notification Email</label></th>
                    <td><input type="email" id="aichat_notify_email" name="aichat_notify_email" value="<?php echo esc_attr( get_option( 'aichat_notify_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text"></td>
                </tr>
            </table>

            <h2>Business Info</h2>
            <table class="form-table">
                <tr>
                    <th><label for="aichat_business_name">Business Name</label></th>
                    <td><input type="text" id="aichat_business_name" name="aichat_business_name" value="<?php echo esc_attr( get_option( 'aichat_business_name', '' ) ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="aichat_phone">Phone Number</label></th>
                    <td><input type="text" id="aichat_phone" name="aichat_phone" value="<?php echo esc_attr( get_option( 'aichat_phone', '' ) ); ?>" class="regular-text" placeholder="555-867-5309"></td>
                </tr>
            </table>

            <h2>Widget Appearance</h2>
            <table class="form-table">
                <tr>
                    <th><label for="aichat_bot_name">Bot Name</label></th>
                    <td><input type="text" id="aichat_bot_name" name="aichat_bot_name" value="<?php echo esc_attr( get_option( 'aichat_bot_name', 'Ace' ) ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="aichat_tagline">Header Tagline</label></th>
                    <td><input type="text" id="aichat_tagline" name="aichat_tagline" value="<?php echo esc_attr( get_option( 'aichat_tagline', "We're here to help." ) ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="aichat_color_primary">Primary Color</label></th>
                    <td><input type="color" id="aichat_color_primary" name="aichat_color_primary" value="<?php echo esc_attr( get_option( 'aichat_color_primary', '#6559b1' ) ); ?>"></td>
                </tr>
                <tr>
                    <th><label for="aichat_color_accent">Accent Color</label></th>
                    <td><input type="color" id="aichat_color_accent" name="aichat_color_accent" value="<?php echo esc_attr( get_option( 'aichat_color_accent', '#a9c33f' ) ); ?>"></td>
                </tr>
            </table>

            <h2>Conversation</h2>
            <table class="form-table">
                <tr>
                    <th><label for="aichat_greeting">Opening Message</label></th>
                    <td><textarea id="aichat_greeting" name="aichat_greeting" class="large-text" rows="2"><?php echo esc_textarea( get_option( 'aichat_greeting', "Hey! I'm here to help. What can I do for you today?" ) ); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="aichat_chips">Starter Buttons</label></th>
                    <td>
                        <input type="text" id="aichat_chips" name="aichat_chips" value="<?php echo esc_attr( get_option( 'aichat_chips', 'New Customer,Existing Customer,Ask a Question' ) ); ?>" class="large-text">
                        <p class="description">Comma-separated. Max 3 buttons.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="aichat_system_prompt">System Prompt</label></th>
                    <td>
                        <textarea id="aichat_system_prompt" name="aichat_system_prompt" class="large-text code" rows="20"><?php echo esc_textarea( get_option( 'aichat_system_prompt', aichat_default_prompt() ) ); ?></textarea>
                        <p class="description">This is the AI's full instruction set. Edit carefully.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button( 'Save Settings' ); ?>
        </form>
    </div>
    <?php
}

function aichat_default_prompt() {
    $business = get_option( 'aichat_business_name', 'our business' );
    $bot      = get_option( 'aichat_bot_name', 'Ace' );
    $phone    = get_option( 'aichat_phone', '' );

    return <<<PROMPT
You are {$bot}, a friendly and knowledgeable AI assistant for {$business}.

Your job is to answer questions, help customers understand our services, and capture their contact information when they are ready to move forward.

---

TONE

Warm, confident, and local. You are not a call center. You are a helpful neighbor who knows this business well.
Never robotic. Never corporate. Keep responses short — 2 to 3 sentences, then one question.

---

LEAD CAPTURE

When a customer is ready for follow-up or asks for pricing, a quote, or to get started, collect:
1. Name
2. Email
3. Phone number

Once all three are collected, output this tag on its own line at the end of your response:
[LEAD_CAPTURED:name=FIRSTNAME,email=EMAIL]

Do not ask for all three at once. Collect one per message.

---

CLOSING AFTER LEAD CAPTURE

Close warmly. Confirm what happens next. Do not just say "someone will reach out."

Example: You are all set, Sarah. Our team will follow up with you shortly. If you want to move faster, call us at {$phone}.

---

SERVICES

[FILL IN — describe your services, pricing, and what makes you different]

---

OBJECTIONS

[FILL IN — top objections customers raise and how to handle them]

---

WHAT YOU DO NOT DO

- Do not make up prices or policies you are not sure about
- Do not promise specific timelines you cannot guarantee
- Do not disparage competitors by name
PROMPT;
}
