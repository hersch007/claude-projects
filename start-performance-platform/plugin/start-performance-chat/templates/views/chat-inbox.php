<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$enabled_global = (int) get_option( 'sp_chat_enabled_global', 1 );
$enabled_client = (int) get_option( 'sp_chat_enabled', 0 );
$is_super       = function_exists( 'sp_is_super_admin' ) && sp_is_super_admin();
?>
<div class="sp-page-header">
    <h1>Chat Inbox</h1>
</div>

<div class="sp-card sp-form-card" style="margin-top:16px">
    <h2 class="sp-section-heading">Website chat</h2>
    <p style="font-size:14px;color:#475569;line-height:1.7;max-width:640px">
        The live conversation inbox arrives in Phase 2. For now, configure the assistant under
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=settings#section-chat' ) ); ?>">Settings → Chat</a>.
    </p>
    <p style="font-size:13px;color:#64748b;margin-top:12px">
        Status:
        <strong style="color:<?php echo ( $enabled_global && $enabled_client ) ? '#16a34a' : '#dc2626'; ?>">
            <?php echo ( $enabled_global && $enabled_client ) ? 'Enabled' : ( $enabled_global ? 'Off (enable it in Settings → Chat)' : 'Disabled by Start Performance' ); ?>
        </strong>
    </p>
    <?php if ( $is_super ) : ?>
    <p style="font-size:13px;color:#64748b;margin-top:6px">You own the prompt, guardrails, and abuse controls in <a href="<?php echo esc_url( home_url( '/sp-app/?view=settings#section-chat-policy' ) ); ?>">Settings → Chat → Chat Policy</a>.</p>
    <?php endif; ?>
</div>
