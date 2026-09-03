<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$sid_enabled = get_option( 'echo64_sid_audio', '0' );
?>
<div class="echo64-chat-wrapper">
<div class="echo64-monitor-frame">
<div class="echo64-chat-container" id="echo64-widget-container" role="region" aria-label="Echo-64 Chat"
     data-sid="<?php echo esc_attr( $sid_enabled ); ?>">

    <!-- ═══════════════════════════════════════════════════
         STATE 1 — Broadcast Splash Screen
         ═══════════════════════════════════════════════════ -->
    <div class="echo64-splash" id="echo64-splash" aria-live="polite">
        <div class="echo64-splash-inner">

            <div class="echo64-splash-header">
                <h1 class="echo64-splash-title">ECHO-64</h1>
                <p class="echo64-splash-subtitle">SCI-FI AFTER MIDNIGHT &bull; 1984</p>
            </div>

            <div class="echo64-splash-body" id="echo64-splash-body">
                <div class="echo64-splash-loading" id="echo64-splash-loading">
                    <div class="echo64-typing">
                        <span></span><span></span><span></span>
                    </div>
                    <p>RECEIVING TRANSMISSION&hellip;</p>
                </div>
            </div>

            <div class="echo64-splash-prompts" id="echo64-splash-prompts" aria-label="Conversation starters"></div>

            <button type="button" class="echo64-open-btn" id="echo64-open-btn" disabled style="display:none" aria-hidden="true"></button>

        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
         STATE 2 — Chat Panel
         ═══════════════════════════════════════════════════ -->
    <div class="echo64-chat-panel" id="echo64-chat-panel" hidden>

        <div class="echo64-chat-header">
            <div class="echo64-header-left">
                <div class="echo64-name-row">
                    <div class="echo64-avatar-wrap">
                        <img src="<?php echo esc_url( ECHO64_PLUGIN_URL . 'assets/images/echo-64-avatar.png' ); ?>"
                             alt="Echo-64" class="echo64-avatar" title="READY." />
                        <span class="echo64-status-dot" aria-hidden="true"></span>
                    </div>
                    <span class="echo64-name">Echo-64</span>
                </div>
                <div class="echo64-badge-slot" aria-live="polite"></div>
            </div>

            <div class="echo64-header-actions">
                <button type="button" class="echo64-amber-btn"
                        title="<?php esc_attr_e( 'Toggle amber phosphor mode', 'echo64-chatbot' ); ?>"
                        aria-label="<?php esc_attr_e( 'Toggle amber phosphor mode', 'echo64-chatbot' ); ?>"
                        aria-pressed="false">
                    <svg width="13" height="13" viewBox="0 0 13 13" fill="none" aria-hidden="true">
                        <circle cx="6.5" cy="6.5" r="5.5" stroke="currentColor" stroke-width="1.3"/>
                        <circle cx="6.5" cy="6.5" r="2.5" fill="currentColor"/>
                    </svg>
                </button>
                <button type="button" class="echo64-clear-btn"
                        title="<?php esc_attr_e( 'New conversation', 'echo64-chatbot' ); ?>"
                        aria-label="<?php esc_attr_e( 'Start new conversation', 'echo64-chatbot' ); ?>">
                    <svg width="13" height="13" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <path d="M13.5 2.5L2.5 13.5M2.5 2.5L13.5 13.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
        </div><!-- /.echo64-chat-header -->

        <div class="echo64-messages" id="echo64-messages"
             role="log" aria-live="polite" aria-atomic="false"></div>

        <div class="echo64-input-area">
            <div class="echo64-input-row">
                <textarea
                    id="echo64-input"
                    class="echo64-input"
                    placeholder="Say anything — sci-fi, cult TV, an unpopular opinion…"
                    rows="1"
                    maxlength="2000"
                    aria-label="<?php esc_attr_e( 'Message Echo-64', 'echo64-chatbot' ); ?>"
                    spellcheck="false"
                ></textarea>
                <button type="button" id="echo64-send" class="echo64-send-btn"
                        aria-label="<?php esc_attr_e( 'Transmit message', 'echo64-chatbot' ); ?>" disabled>
                    <span class="echo64-send-label">TRANSMIT</span>
                </button>
            </div>
            <p class="echo64-footer-note">NERDAFTERDARK.COM &middot; RETRO FUTURES &amp; SARCASTIC TRUTHS</p>
            <p class="echo64-footer-note echo64-input-footer-note" style="display:none" aria-live="polite"></p>
            <div class="e64-kofi-session-bar" style="display:none">
                Echo-64 runs on floppy disks and spite. &nbsp;<a href="#" data-kofi="nerdafterdark">Keep the signal alive &rarr;</a>
            </div>
        </div>

    </div><!-- /.echo64-chat-panel -->

</div><!-- /.echo64-chat-container -->
</div><!-- /.echo64-monitor-frame -->
</div><!-- /.echo64-chat-wrapper -->
