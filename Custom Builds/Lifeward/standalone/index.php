<?php
require_once __DIR__ . '/inc/config.php';
$settings = lw_get_settings();
$greeting = $settings['greeting'] !== '' ? $settings['greeting'] : lw_settings_defaults()['greeting'];
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lifeward</title>
<link rel="stylesheet" href="assets/style.css?v=<?php echo @filemtime( __DIR__ . '/assets/style.css' ); ?>">
</head>
<body>
<div class="lw-split">
    <div class="lw-hero" role="img" aria-label="A Lifeward patient using a ReStore exoskeleton">
        <div class="lw-hero-ready">Yes, I'm Ready!</div>
        <div class="lw-hero-tagline">Redefine Possible</div>
    </div>

    <div class="lw-panel">
        <div class="lw-app" id="lw-app">
            <img class="lw-logo" src="assets/brand/logo.svg" alt="Lifeward">

            <h1 class="lw-headline">Take Back Your Independence</h1>
            <p class="lw-subheadline">Choose Your Preference</p>

            <div class="lw-bubble lw-hidden" id="lw-bubble"><?php echo htmlspecialchars( $greeting, ENT_QUOTES, 'UTF-8' ); ?></div>
            <div class="lw-bubble-by lw-hidden">&mdash; Rachel, AI Assistant</div>

            <div class="lw-buttons" id="lw-buttons">
                <button type="button" class="lw-btn lw-btn-teal" data-step="call_now">Please call now</button>
                <button type="button" class="lw-btn lw-btn-teal" data-step="schedule_call">Schedule a Call</button>
                <button type="button" class="lw-btn lw-btn-teal" data-step="send_info">I'm still hesitating, send more info</button>
            </div>

            <div class="lw-fields" id="lw-fields"></div>
            <div class="lw-error" id="lw-error" role="alert"></div>

            <button type="button" class="lw-chat-open" id="lw-chat-open">Still have questions? Ask Rachel &rarr;</button>

            <div class="lw-disclosure">Rachel is an AI assistant, not a licensed clinician. For medical advice, please speak with a healthcare provider.</div>
        </div>
    </div>
</div>

<div class="lw-modal-overlay lw-hidden" id="lw-modal-overlay">
    <div class="lw-modal">
        <button type="button" class="lw-modal-close" id="lw-modal-close" aria-label="Close">&times;</button>
        <div class="lw-modal-badge">Demo Note &mdash; Internal Only</div>
        <h2 class="lw-modal-title">What This Button Will Do</h2>
        <p class="lw-modal-text">In production, this prompts the Start agents to initiate a call.</p>
        <p class="lw-modal-text lw-modal-note">If it's after hours (outside Monday&ndash;Friday, 9am&ndash;5pm Eastern), the system schedules a callback instead of calling right away.</p>
        <button type="button" class="lw-btn lw-btn-primary" id="lw-modal-next" style="justify-content:center">Next</button>
    </div>
</div>

<div class="lw-modal-overlay lw-hidden" id="lw-callmade-overlay">
    <div class="lw-modal">
        <div class="lw-modal-badge">Demo Note &mdash; Internal Only</div>
        <h2 class="lw-modal-title">Call Made</h2>
        <p class="lw-modal-text">Simulating the CRM + RingCentral dial-out with this tracked lead info:</p>
        <div class="lw-modal-track">
            <div><strong>Name:</strong> <span id="lw-callmade-name"></span></div>
            <div><strong>Phone:</strong> <span id="lw-callmade-phone"></span></div>
            <div id="lw-callmade-message-row"><strong>Note:</strong> <span id="lw-callmade-message"></span></div>
            <div><strong>Time:</strong> <span id="lw-callmade-time"></span></div>
        </div>
        <button type="button" class="lw-btn lw-btn-primary" id="lw-callmade-done" style="justify-content:center">Done</button>
    </div>
</div>

<div class="lw-modal-overlay lw-hidden" id="lw-scheduled-overlay">
    <div class="lw-modal">
        <div class="lw-modal-badge">Demo Note &mdash; Internal Only</div>
        <h2 class="lw-modal-title">Callback Scheduled</h2>
        <p class="lw-modal-text">Simulating the CRM + RingCentral scheduled callback with this tracked lead info:</p>
        <div class="lw-modal-track">
            <div><strong>Name:</strong> <span id="lw-scheduled-name"></span></div>
            <div><strong>Phone:</strong> <span id="lw-scheduled-phone"></span></div>
            <div><strong>Callback time:</strong> <span id="lw-scheduled-when"></span></div>
            <div id="lw-scheduled-message-row"><strong>Note:</strong> <span id="lw-scheduled-message"></span></div>
        </div>
        <p class="lw-modal-text lw-modal-note">Scheduled callback slots are only offered during call center hours (Monday&ndash;Friday, 9am&ndash;5pm Eastern) &mdash; that's why times outside that window aren't selectable.</p>
        <button type="button" class="lw-btn lw-btn-primary" id="lw-scheduled-done" style="justify-content:center">Done</button>
    </div>
</div>

<div class="lw-modal-overlay lw-hidden" id="lw-infosent-overlay">
    <div class="lw-modal">
        <div class="lw-modal-badge">Demo Note &mdash; Internal Only</div>
        <h2 class="lw-modal-title">Info Sent</h2>
        <p class="lw-modal-text">Simulating the CRM sync with this tracked lead info (the info email was sent for real):</p>
        <div class="lw-modal-track">
            <div><strong>Name:</strong> <span id="lw-infosent-name"></span></div>
            <div><strong>Email:</strong> <span id="lw-infosent-email"></span></div>
            <div id="lw-infosent-products-row"><strong>Products:</strong> <span id="lw-infosent-products"></span></div>
        </div>
        <button type="button" class="lw-btn lw-btn-primary" id="lw-infosent-done" style="justify-content:center">Done</button>
    </div>
</div>

<div class="lw-chat-launcher" id="lw-chat-launcher" aria-label="Open chat with Rachel" role="button" tabindex="0">
    <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
</div>

<div class="lw-chat-panel" id="lw-chat-panel" hidden>
    <div class="lw-chat-header">
        <div class="lw-chat-header-title">Chat with Rachel, AI-Specialist</div>
        <button type="button" class="lw-chat-close" id="lw-chat-close" aria-label="Close chat">&times;</button>
    </div>
    <div class="lw-chat-thread" id="lw-chat-thread">
        <div class="lw-chat-msg lw-chat-msg-assistant">Hi! Ask me anything about Lifeward's products, or use the buttons on the page to reach our team directly.</div>
    </div>
    <div class="lw-chat-inputrow">
        <input type="text" id="lw-chat-input" placeholder="Type a message&hellip;" maxlength="2000">
        <button type="button" id="lw-chat-send" aria-label="Send">&rarr;</button>
    </div>
</div>

<script src="assets/app.js?v=<?php echo @filemtime( __DIR__ . '/assets/app.js' ); ?>"></script>
<script src="assets/chat-widget.js?v=<?php echo @filemtime( __DIR__ . '/assets/chat-widget.js' ); ?>"></script>
</body>
</html>
