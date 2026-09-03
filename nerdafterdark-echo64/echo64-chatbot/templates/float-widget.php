<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="e64-fw">

    <div class="e64-fw-bar">
        <img src="<?php echo esc_url( ECHO64_PLUGIN_URL . 'assets/images/echo-64-avatar.png' ); ?>"
             class="e64-fw-avatar" alt="Echo-64" />
        <div class="e64-fw-info">
            <span class="e64-fw-name">ECHO-64</span>
            <span class="e64-fw-stat" id="e64-fw-stat">CONNECTING&hellip;</span>
        </div>
        <button type="button" class="e64-fw-reset echo64-clear-btn"
                title="<?php esc_attr_e( 'New conversation', 'echo64-chatbot' ); ?>"
                aria-label="<?php esc_attr_e( 'Start new conversation', 'echo64-chatbot' ); ?>">
            <svg width="13" height="13" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M13.5 8A5.5 5.5 0 1 1 8 2.5M13.5 2.5v3.5h-3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <button type="button" class="e64-fw-close"
                title="<?php esc_attr_e( 'Close', 'echo64-chatbot' ); ?>"
                aria-label="<?php esc_attr_e( 'Close chat', 'echo64-chatbot' ); ?>">
            <svg width="11" height="11" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M13.5 2.5L2.5 13.5M2.5 2.5L13.5 13.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
    </div>

    <div class="echo64-messages e64-fw-messages" id="echo64-messages"
         role="log" aria-live="polite" aria-atomic="false"></div>

    <div class="e64-fw-foot">
        <div class="e64-fw-input-row">
            <textarea
                id="echo64-input"
                class="echo64-input e64-fw-input"
                placeholder="Transmit on encrypted channel&hellip;"
                rows="1"
                maxlength="2000"
                aria-label="<?php esc_attr_e( 'Message Echo-64', 'echo64-chatbot' ); ?>"
                spellcheck="false"
            ></textarea>
            <button type="button" id="echo64-send" class="echo64-send-btn e64-fw-send" disabled
                    aria-label="<?php esc_attr_e( 'Transmit message', 'echo64-chatbot' ); ?>">
                TRANSMIT
            </button>
        </div>
    </div>

</div>
