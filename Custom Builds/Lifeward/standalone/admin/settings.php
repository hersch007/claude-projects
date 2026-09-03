<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/auth.php';
lw_require_admin();

$saved = false;
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    lw_save_settings( array(
        'greeting'                   => trim( (string) ( $_POST['greeting'] ?? '' ) ),
        'call_center_webhook_url'    => trim( (string) ( $_POST['call_center_webhook_url'] ?? '' ) ),
        'call_center_fallback_email' => trim( (string) ( $_POST['call_center_fallback_email'] ?? '' ) ),
        'info_subject'               => trim( (string) ( $_POST['info_subject'] ?? '' ) ),
        'info_body'                  => (string) ( $_POST['info_body'] ?? '' ),
        'anthropic_api_key'          => trim( (string) ( $_POST['anthropic_api_key'] ?? '' ) ),
        'ai_model'                   => trim( (string) ( $_POST['ai_model'] ?? '' ) ),
    ) );
    $saved = true;
}

$s = lw_get_settings();
?><!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Lifeward Admin — Settings</title>
<link rel="stylesheet" href="admin.css?v=<?php echo @filemtime( __DIR__ . '/admin.css' ); ?>"></head>
<body>
<div class="lw-admin-wrap">
    <?php include __DIR__ . '/_nav.php'; ?>
    <h1>Settings</h1>
    <p class="lw-hint">Salesforce sync is a mock (see ../inc/salesforce.php) — nothing here activates it; it exists so the funnel has something real to hit once Salesforce credentials are available.</p>
    <?php if ( $saved ): ?><div class="lw-alert lw-alert-ok">Saved.</div><?php endif; ?>

    <form method="post" class="lw-form">
        <label>Rachel's greeting
            <textarea name="greeting" rows="3"><?php echo htmlspecialchars( $s['greeting'], ENT_QUOTES ); ?></textarea>
        </label>

        <label>Call-center webhook URL
            <input type="url" name="call_center_webhook_url" value="<?php echo htmlspecialchars( $s['call_center_webhook_url'], ENT_QUOTES ); ?>" placeholder="https://your-call-center-system.example.com/webhook">
            <span class="lw-hint-small">When a visitor asks to talk right now, we POST their name/phone/email here as JSON. Leave blank to fall back to email below.</span>
        </label>

        <label>Fallback notification email
            <input type="email" name="call_center_fallback_email" value="<?php echo htmlspecialchars( $s['call_center_fallback_email'], ENT_QUOTES ); ?>">
        </label>

        <label>Info package email — subject
            <input type="text" name="info_subject" value="<?php echo htmlspecialchars( $s['info_subject'], ENT_QUOTES ); ?>">
        </label>

        <label>Info package email — body
            <textarea name="info_body" rows="6"><?php echo htmlspecialchars( $s['info_body'], ENT_QUOTES ); ?></textarea>
        </label>

        <button type="submit" class="lw-btn-primary">Save Settings</button>
    </form>

    <form method="post" class="lw-form" style="margin-top:20px">
        <h2 style="margin:0 0 4px;font-size:1.1rem">AI Chat (Anthropic)</h2>
        <p class="lw-hint-small" style="margin:0 0 4px">The floating "ask me anything" chat is disabled until a key is set here. Get one at <a href="https://console.anthropic.com/" target="_blank" rel="noopener">console.anthropic.com</a>. The persona and safety rules live in <code>inc/chat.php</code>.</p>

        <label>Anthropic API key
            <input type="password" name="anthropic_api_key" value="<?php echo htmlspecialchars( $s['anthropic_api_key'], ENT_QUOTES ); ?>" placeholder="sk-ant-...">
        </label>

        <label>Model
            <input type="text" name="ai_model" value="<?php echo htmlspecialchars( $s['ai_model'], ENT_QUOTES ); ?>">
        </label>

        <!-- Repeat the other fields as hidden so this second form doesn't blank them out -->
        <input type="hidden" name="greeting" value="<?php echo htmlspecialchars( $s['greeting'], ENT_QUOTES ); ?>">
        <input type="hidden" name="call_center_webhook_url" value="<?php echo htmlspecialchars( $s['call_center_webhook_url'], ENT_QUOTES ); ?>">
        <input type="hidden" name="call_center_fallback_email" value="<?php echo htmlspecialchars( $s['call_center_fallback_email'], ENT_QUOTES ); ?>">
        <input type="hidden" name="info_subject" value="<?php echo htmlspecialchars( $s['info_subject'], ENT_QUOTES ); ?>">
        <input type="hidden" name="info_body" value="<?php echo htmlspecialchars( $s['info_body'], ENT_QUOTES ); ?>">

        <button type="submit" class="lw-btn-primary">Save AI Settings</button>
    </form>
</div>
</body>
</html>
