<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Prevent Endurance/Newfold page cache from caching this form page
header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
header( 'Pragma: no-cache' );
header( 'Expires: 0' );

$city_name = get_option( 'sp_city_name', get_bloginfo( 'name' ) );
$accent    = get_option( 'sp_city_primary_color', '#1e3a5f' );
$year      = date( 'Y' );

function sp_city_darken( $hex, $pct = 15 ) {
    $hex = ltrim( $hex, '#' );
    list( $r, $g, $b ) = array_map( 'hexdec', str_split( $hex, 2 ) );
    $r = max( 0, $r - round( $r * $pct / 100 ) );
    $g = max( 0, $g - round( $g * $pct / 100 ) );
    $b = max( 0, $b - round( $b * $pct / 100 ) );
    return sprintf( '#%02x%02x%02x', $r, $g, $b );
}
$accent_dark = sp_city_darken( $accent );

$initial_tab = isset( $_GET['track'] ) ? 'track' : 'submit';
$success_ticket = isset( $_GET['city_ticket'] ) ? sanitize_text_field( $_GET['city_ticket'] ) : '';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Service Request — <?php echo esc_html( $city_name ); ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: #f1f5f9;
    color: #1a202c;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* ── Header ── */
.sp-header {
    background: <?php echo esc_attr( $accent ); ?>;
    padding: 0 24px;
    box-shadow: 0 2px 16px rgba(0,0,0,.22);
}
.sp-header-inner {
    max-width: 700px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 68px;
}
.sp-header-city {
    font-size: 18px;
    font-weight: 700;
    color: #fff;
    letter-spacing: -.2px;
}

/* ── Hero scene ── */
.sp-hero-scene {
    width: 100%;
    height: 200px;
    overflow: hidden;
    position: relative;
    background: url('https://images.unsplash.com/photo-1761839259488-2bdeeae794f5?w=1400&h=400&fit=crop&crop=center&q=85') center/cover no-repeat;
}

/* ── Main ── */
.sp-main {
    flex: 1;
    max-width: 700px;
    width: 100%;
    margin: -12px auto 0;
    padding: 0 16px 56px;
    position: relative;
    z-index: 1;
}

/* ── Tab switcher ── */
.sp-tabs {
    display: flex;
    background: #fff;
    border-radius: 14px 14px 0 0;
    overflow: hidden;
    box-shadow: 0 -1px 0 rgba(0,0,0,.06);
}
.sp-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 15px 20px;
    font-size: 14px;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    border: none;
    background: none;
    border-bottom: 3px solid transparent;
    transition: color .15s, border-color .15s;
    letter-spacing: -.1px;
}
.sp-tab svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2; flex-shrink: 0; }
.sp-tab:hover { color: #374151; }
.sp-tab.active {
    color: <?php echo esc_attr( $accent ); ?>;
    border-bottom-color: <?php echo esc_attr( $accent ); ?>;
}

/* ── Info pills ── */
.sp-pills {
    display: flex;
    gap: 8px;
    padding: 0 24px 20px;
    background: #fff;
    flex-wrap: wrap;
}
.sp-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #4b5563;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 8px 14px;
    line-height: 1.4;
}
.sp-pill svg { width: 13px; height: 13px; stroke: <?php echo esc_attr( $accent ); ?>; fill: none; stroke-width: 2; }
.sp-pill strong { color: #111; }

/* ── Panel card ── */
.sp-panel {
    background: #fff;
    border-radius: 0 0 14px 14px;
    box-shadow: 0 4px 24px rgba(0,0,0,.09);
    overflow: hidden;
}
.sp-panel-body { padding: 28px 28px 32px; }
.sp-panel-section { display: none; }
.sp-panel-section.active { display: block; }

/* ── Form elements ── */
.sp-field { margin-bottom: 20px; }
.sp-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}
.sp-label .opt { font-weight: 400; color: #9ca3af; font-size: 12px; }
.sp-req { color: #ef4444; }
.sp-input, .sp-select, .sp-textarea {
    width: 100%;
    border: 1.5px solid #e2e8f0;
    border-radius: 9px;
    padding: 10px 13px;
    font-size: 14px;
    font-family: inherit;
    color: #111;
    background: #fafafa;
    transition: border-color .15s, box-shadow .15s, background .15s;
    outline: none;
    appearance: none;
}
.sp-input:focus, .sp-select:focus, .sp-textarea:focus {
    border-color: <?php echo esc_attr( $accent ); ?>;
    box-shadow: 0 0 0 3px <?php echo esc_attr( $accent ); ?>20;
    background: #fff;
}
.sp-select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%239ca3af' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px; }
.sp-textarea { resize: vertical; min-height: 100px; }
.sp-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

/* ── Dept pills ── */
.sp-dept-wrap { display: flex; flex-wrap: wrap; gap: 8px; }
.sp-dept-opt { position: relative; }
.sp-dept-opt input { position: absolute; opacity: 0; width: 0; height: 0; }
.sp-dept-opt label {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 8px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 20px;
    background: #fafafa;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    transition: all .15s;
    user-select: none;
    white-space: nowrap;
}
.sp-dept-opt label:hover { border-color: <?php echo esc_attr( $accent ); ?>60; background: #fff; }
.sp-dept-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
.sp-dept-opt input:checked + label {
    border-color: <?php echo esc_attr( $accent ); ?>;
    background: <?php echo esc_attr( $accent ); ?>0d;
    color: <?php echo esc_attr( $accent ); ?>;
    font-weight: 600;
}

/* ── Priority ── */
.sp-pri-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; }
.sp-pri-opt { position: relative; }
.sp-pri-opt input { position: absolute; opacity: 0; width: 0; height: 0; }
.sp-pri-opt label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    padding: 12px 8px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    text-align: center;
    transition: all .15s;
    background: #fafafa;
}
.sp-pri-opt label:hover { background: #fff; border-color: #cbd5e1; }
.sp-pri-label { font-size: 13px; font-weight: 700; color: #374151; }
.sp-pri-sub   { font-size: 11px; color: #9ca3af; font-weight: 400; }
.sp-pri-dot   { width: 10px; height: 10px; border-radius: 50%; }
.normal-dot   { background: #3b82f6; }
.high-dot     { background: #f59e0b; }
.emergency-dot{ background: #ef4444; }
.sp-pri-opt.normal   input:checked + label { border-color: #3b82f6; background: #eff6ff; }
.sp-pri-opt.high     input:checked + label { border-color: #f59e0b; background: #fffbeb; }
.sp-pri-opt.emergency input:checked + label { border-color: #ef4444; background: #fef2f2; }
.sp-pri-opt.normal   input:checked + label .sp-pri-label { color: #1d4ed8; }
.sp-pri-opt.high     input:checked + label .sp-pri-label { color: #92400e; }
.sp-pri-opt.emergency input:checked + label .sp-pri-label { color: #b91c1c; }

/* ── Divider ── */
.sp-divider { border: none; border-top: 1px solid #f1f5f9; margin: 22px 0; }
.sp-section-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #9ca3af;
    margin-bottom: 14px;
}

/* ── Submit button ── */
.sp-submit {
    width: 100%;
    background: <?php echo esc_attr( $accent ); ?>;
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 14px 24px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s, transform .1s, box-shadow .15s;
    margin-top: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    letter-spacing: -.1px;
    box-shadow: 0 2px 8px <?php echo esc_attr( $accent ); ?>40;
}
.sp-submit:hover  { background: <?php echo esc_attr( $accent_dark ); ?>; box-shadow: 0 4px 14px <?php echo esc_attr( $accent ); ?>50; }
.sp-submit:active { transform: scale(.99); }
.sp-submit svg { width: 17px; height: 17px; stroke: #fff; fill: none; stroke-width: 2.5; }

/* ── Success card ── */
.sp-success {
    padding: 44px 28px 36px;
    text-align: center;
    animation: fadeUp .35s ease;
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
.sp-success-icon {
    width: 64px;
    height: 64px;
    background: #f0fdf4;
    border: 2px solid #86efac;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}
.sp-success-icon svg { width: 30px; height: 30px; stroke: #16a34a; fill: none; stroke-width: 2.5; }
.sp-success h2 { font-size: 22px; font-weight: 800; color: #166534; margin-bottom: 6px; }
.sp-success p  { color: #4b5563; font-size: 14px; line-height: 1.6; margin-bottom: 20px; }
.sp-ticket-badge {
    display: inline-block;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 36px;
    margin-bottom: 20px;
}
.sp-ticket-badge .tk-label { font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: .07em; margin-bottom: 4px; }
.sp-ticket-badge .tk-num   { font-size: 32px; font-weight: 900; color: #111; letter-spacing: .05em; }
.sp-track-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: <?php echo esc_attr( $accent ); ?>;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    margin-bottom: 16px;
    text-decoration: none;
}
.sp-track-link:hover { text-decoration: underline; }
.sp-track-link svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; }
.sp-back-link {
    display: block;
    color: #9ca3af;
    font-size: 13px;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    margin-top: 12px;
    text-decoration: underline;
    text-underline-offset: 2px;
}
.sp-back-link:hover { color: #6b7280; }

/* ── Track section ── */
.sp-track-box { text-align: center; padding: 8px 0 24px; }
.sp-track-box h2 { font-size: 18px; font-weight: 700; color: #111; margin-bottom: 6px; }
.sp-track-box p  { font-size: 14px; color: #6b7280; margin-bottom: 24px; }
.sp-track-form { display: flex; gap: 10px; max-width: 420px; margin: 0 auto; }
.sp-track-form .sp-input { font-size: 15px; letter-spacing: .03em; text-transform: uppercase; }
.sp-track-btn {
    background: <?php echo esc_attr( $accent ); ?>;
    color: #fff;
    border: none;
    border-radius: 9px;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: background .15s;
    flex-shrink: 0;
}
.sp-track-btn:hover { background: <?php echo esc_attr( $accent_dark ); ?>; }
.sp-track-result { margin-top: 24px; text-align: left; animation: fadeUp .3s ease; }
.sp-track-card {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px 22px;
}
.sp-track-card-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px; }
.sp-track-num  { font-size: 20px; font-weight: 800; color: #111; }
.sp-track-when { font-size: 12px; color: #9ca3af; margin-top: 2px; }
.sp-track-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; }
.sp-track-item-label { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #9ca3af; font-weight: 700; margin-bottom: 3px; }
.sp-track-item-val   { font-size: 14px; font-weight: 600; color: #111; }
.sp-track-address    { grid-column: 1 / -1; }
.sp-track-timeline   { margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
.sp-tl-row { display: flex; align-items: center; gap: 10px; padding: 5px 0; }
.sp-tl-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.sp-tl-text { font-size: 13px; color: #374151; }
.sp-tl-time { font-size: 12px; color: #9ca3af; margin-left: auto; }
.sp-track-error {
    text-align: center;
    padding: 24px;
    color: #6b7280;
    font-size: 14px;
}
.sp-track-error strong { display: block; color: #374151; font-size: 16px; margin-bottom: 6px; }

/* ── Status badge ── */
.sp-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 20px;
    letter-spacing: .02em;
}
.sp-badge-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.badge-open        { background: #eff6ff; color: #1d4ed8; }
.badge-open .sp-badge-dot        { background: #3b82f6; }
.badge-in_progress { background: #fffbeb; color: #92400e; }
.badge-in_progress .sp-badge-dot { background: #f59e0b; }
.badge-resolved    { background: #f0fdf4; color: #166534; }
.badge-resolved .sp-badge-dot    { background: #22c55e; }
.badge-closed      { background: #f3f4f6; color: #374151; }
.badge-closed .sp-badge-dot      { background: #9ca3af; }
.badge-emergency   { background: #fef2f2; color: #b91c1c; }
.badge-emergency .sp-badge-dot   { background: #ef4444; }
.badge-high        { background: #fffbeb; color: #92400e; }
.badge-high .sp-badge-dot        { background: #f59e0b; }
.badge-normal      { background: #f0fdf4; color: #166534; }
.badge-normal .sp-badge-dot      { background: #22c55e; }

/* ── Footer ── */
.sp-footer {
    background: #0f172a;
    color: rgba(255,255,255,.4);
    text-align: center;
    padding: 18px 24px;
    font-size: 12px;
    margin-top: auto;
}
.sp-footer a { color: rgba(255,255,255,.4); text-decoration: none; }
.sp-footer a:hover { color: rgba(255,255,255,.7); }

/* ── Photo upload ── */
.sp-drop-zone {
    border: 2px dashed #e2e8f0;
    border-radius: 10px;
    background: #fafafa;
    padding: 24px 20px;
    text-align: center;
    cursor: pointer;
    transition: border-color .15s, background .15s;
}
.sp-drop-zone:hover, .sp-drop-zone.dragover {
    border-color: <?php echo esc_attr( $accent ); ?>;
    background: <?php echo esc_attr( $accent ); ?>08;
}
.sp-drop-icon { width: 32px; height: 32px; color: #9ca3af; margin: 0 auto 8px; display: block; }
.sp-drop-text { font-size: 14px; font-weight: 600; color: #374151; }
.sp-drop-sub  { font-size: 12px; color: #9ca3af; margin-top: 3px; }
.sp-photo-previews { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
.sp-photo-thumb {
    position: relative;
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    border: 1.5px solid #e2e8f0;
    flex-shrink: 0;
}
.sp-photo-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.sp-photo-remove {
    position: absolute;
    top: 3px;
    right: 3px;
    width: 18px;
    height: 18px;
    background: rgba(0,0,0,.55);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border: none;
    padding: 0;
    line-height: 1;
    font-size: 11px;
    color: #fff;
    font-weight: 700;
}

/* ── Loading spinner ── */
.sp-spinner { display: inline-block; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 540px) {
    .sp-grid-2   { grid-template-columns: 1fr; }
    .sp-pri-grid { grid-template-columns: 1fr; }
    .sp-banner h1 { font-size: 22px; }
    .sp-panel-body { padding: 20px 18px 24px; }
    .sp-pills { padding: 0 18px 16px; }
    .sp-track-form { flex-direction: column; }
    .sp-track-meta { grid-template-columns: 1fr; }
}
</style>
<?php wp_head(); ?>
</head>
<body>

<!-- Header -->
<header class="sp-header">
    <div class="sp-header-inner" style="justify-content:center">
        <span class="sp-header-city" style="font-size:17px">City of Clinton Utility Service Request Portal</span>
    </div>
</header>

<!-- Hero -->
<div class="sp-hero-scene"></div>

<!-- Main -->
<main class="sp-main">

    <!-- Tabs -->
    <div class="sp-tabs">
        <button class="sp-tab <?php echo $initial_tab === 'submit' && ! $success_ticket ? 'active' : ''; ?>" id="tab-submit" onclick="switchTab('submit')">
            <svg viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Submit a Request
        </button>
        <button class="sp-tab <?php echo $initial_tab === 'track' || $success_ticket ? 'active' : ''; ?>" id="tab-track" onclick="switchTab('track')">
            <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            Track Your Request
        </button>
    </div>

    <!-- Info pills -->
    <div class="sp-pills" style="justify-content:center">
        <span class="sp-pill">
            <svg viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span><strong>Fast Response</strong><br><span style="color:#6b7280">On-call staff notified</span></span>
        </span>
        <span class="sp-pill">
            <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span><strong>Ticket Number</strong><br><span style="color:#6b7280">Track anytime</span></span>
        </span>
        <span class="sp-pill">
            <svg viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span><strong>Email Confirmation</strong><br><span style="color:#6b7280">Sent automatically</span></span>
        </span>
    </div>

    <!-- Panel -->
    <div class="sp-panel">
        <div class="sp-panel-body">

            <!-- ── Submit section ── -->
            <div class="sp-panel-section <?php echo ! $success_ticket && $initial_tab !== 'track' ? 'active' : ''; ?>" id="section-submit">

            <?php if ( $success_ticket ) : ?>
                <!-- Success state -->
                <div class="sp-success">
                    <div class="sp-success-icon">
                        <svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h2>Request Submitted!</h2>
                    <p>Your service request has been received and our on-call team has been notified.<br>Save your ticket number below to check on your request anytime.</p>
                    <div class="sp-ticket-badge">
                        <div class="tk-label">Your Ticket Number</div>
                        <div class="tk-num"><?php echo esc_html( $success_ticket ); ?></div>
                    </div>
                    <br>
                    <button class="sp-track-link" onclick="switchTab('track', '<?php echo esc_js( $success_ticket ); ?>')">
                        <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        Track this ticket →
                    </button>
                    <button class="sp-back-link" onclick="resetForm()">Submit another request</button>
                </div>

            <?php else : ?>
                <!-- Form -->
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="sp-city-form" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'sp_city_public', 'sp_city_nonce' ); ?>
                    <input type="hidden" name="action" value="sp_city_submit">
                    <input type="hidden" name="return_url" value="<?php echo esc_url( get_permalink() ); ?>">

                    <!-- Department -->
                    <div class="sp-field">
                        <label class="sp-label" style="text-align:center;display:block">Which department(s) does this involve? <span class="sp-req">*</span></label>
                        <div style="text-align:center;font-size:12px;color:#9ca3af;margin-bottom:10px">Select all that apply</div>
                        <div class="sp-dept-wrap" style="justify-content:center">
                        <?php foreach ( sp_city_get_departments() as $d ) : ?>
                            <div class="sp-dept-opt">
                                <input type="checkbox" name="dept_ids[]" value="<?php echo esc_attr( $d->id ); ?>" id="dept-<?php echo esc_attr( $d->id ); ?>">
                                <label for="dept-<?php echo esc_attr( $d->id ); ?>">
                                    <span class="sp-dept-dot" style="background:<?php echo esc_attr( $d->color ); ?>"></span>
                                    <?php echo esc_html( $d->name ); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Priority -->
                    <div class="sp-field">
                        <label class="sp-label">Priority Level</label>
                        <div class="sp-pri-grid">
                            <div class="sp-pri-opt normal">
                                <input type="radio" name="priority" id="pri-normal" value="normal" checked>
                                <label for="pri-normal">
                                    <span class="sp-pri-dot normal-dot"></span>
                                    <span class="sp-pri-label">Normal</span>
                                    <span class="sp-pri-sub">Routine issue</span>
                                </label>
                            </div>
                            <div class="sp-pri-opt high">
                                <input type="radio" name="priority" id="pri-high" value="high">
                                <label for="pri-high">
                                    <span class="sp-pri-dot high-dot"></span>
                                    <span class="sp-pri-label">High</span>
                                    <span class="sp-pri-sub">Needs prompt attention</span>
                                </label>
                            </div>
                            <div class="sp-pri-opt emergency">
                                <input type="radio" name="priority" id="pri-emergency" value="emergency">
                                <label for="pri-emergency">
                                    <span class="sp-pri-dot emergency-dot"></span>
                                    <span class="sp-pri-label">Emergency</span>
                                    <span class="sp-pri-sub">Urgent / safety risk</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="sp-field">
                        <label class="sp-label" for="sp-address">Address or Location of Issue <span class="sp-req">*</span></label>
                        <input id="sp-address" class="sp-input" type="text" name="address" required placeholder="e.g. 123 Main Street or Intersection of Oak & 5th">
                    </div>

                    <!-- Description -->
                    <div class="sp-field">
                        <label class="sp-label" for="sp-desc">Description of Issue <span class="sp-req">*</span></label>
                        <textarea id="sp-desc" class="sp-textarea" name="description" required placeholder="Describe what you're seeing — the more detail, the better."></textarea>
                    </div>

                    <!-- Photos -->
                    <div class="sp-field">
                        <label class="sp-label">Photos <span class="opt">(optional — up to 3, 8 MB each)</span></label>
                        <div class="sp-drop-zone" id="sp-drop-zone" onclick="document.getElementById('sp-photos').click()">
                            <svg class="sp-drop-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <div class="sp-drop-text">Tap to add photos</div>
                            <div class="sp-drop-sub">JPG, PNG, WEBP or GIF</div>
                            <input type="file" id="sp-photos" name="city_photos[]" accept="image/*" multiple style="display:none" onchange="previewPhotos(this)">
                        </div>
                        <div class="sp-photo-previews" id="sp-photo-previews"></div>
                    </div>

                    <hr class="sp-divider">
                    <div class="sp-section-label">Your Contact Info <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:12px">(optional — helps us follow up)</span></div>

                    <div class="sp-grid-2">
                        <div class="sp-field" style="margin-bottom:0">
                            <label class="sp-label" for="sp-name">Full Name</label>
                            <input id="sp-name" class="sp-input" type="text" name="reporter_name" placeholder="Jane Smith">
                        </div>
                        <div class="sp-field" style="margin-bottom:0">
                            <label class="sp-label" for="sp-phone">Phone Number</label>
                            <input id="sp-phone" class="sp-input" type="tel" name="reporter_phone" placeholder="(555) 555-0100">
                        </div>
                    </div>
                    <div class="sp-field" style="margin-top:14px">
                        <label class="sp-label" for="sp-email">Email Address <span class="opt">(for confirmation)</span></label>
                        <input id="sp-email" class="sp-input" type="email" name="reporter_email" placeholder="you@example.com">
                    </div>

                    <button type="submit" class="sp-submit" id="sp-submit-btn">
                        <svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        Submit Service Request
                    </button>
                </form>
            <?php endif; ?>

            </div><!-- /section-submit -->

            <!-- ── Track section ── -->
            <div class="sp-panel-section <?php echo $initial_tab === 'track' || $success_ticket ? 'active' : ''; ?>" id="section-track">
                <div class="sp-track-box">
                    <h2>Track Your Request</h2>
                    <p>Enter your ticket number to check the current status of your service request.</p>
                    <div class="sp-track-form">
                        <input type="text" class="sp-input" id="track-input" placeholder="e.g. CSR-20260812-00001" maxlength="40">
                        <button class="sp-track-btn" id="track-btn" onclick="lookupTicket()">Look Up</button>
                    </div>
                    <div id="track-result" class="sp-track-result" style="display:none"></div>
                </div>
            </div><!-- /section-track -->

        </div><!-- /panel-body -->
    </div><!-- /panel -->

</main>

<!-- Footer -->
<footer class="sp-footer">
    &copy; <?php echo $year; ?> <?php echo esc_html( $city_name ); ?>
    &nbsp;&middot;&nbsp; Powered by <a href="https://startperformance.com" target="_blank">Start Performance</a>
    &nbsp;&middot;&nbsp; <a href="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">Staff Login</a>
</footer>

<script>
var REST_ROOT = '<?php echo esc_js( rest_url( 'sp-city/v1' ) ); ?>';

function switchTab(tab, prefill) {
    document.getElementById('tab-submit').classList.toggle('active', tab === 'submit');
    document.getElementById('tab-track').classList.toggle('active', tab === 'track');
    document.getElementById('section-submit').classList.toggle('active', tab === 'submit');
    document.getElementById('section-track').classList.toggle('active', tab === 'track');
    if (tab === 'track' && prefill) {
        document.getElementById('track-input').value = prefill;
        lookupTicket();
    }
}

function resetForm() {
    window.location.href = window.location.pathname;
}

function lookupTicket() {
    var input = document.getElementById('track-input');
    var num   = input.value.trim().toUpperCase();
    var result = document.getElementById('track-result');
    var btn   = document.getElementById('track-btn');

    if (!num) { input.focus(); return; }

    btn.innerHTML = '<span class="sp-spinner"></span>';
    btn.disabled = true;
    result.style.display = 'none';

    fetch(REST_ROOT + '/ticket-status?number=' + encodeURIComponent(num))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.innerHTML = 'Look Up';
            btn.disabled = false;
            result.style.display = 'block';
            if (data.found) {
                result.innerHTML = renderTicket(data);
            } else {
                result.innerHTML = '<div class="sp-track-error"><strong>Ticket not found</strong>No record matches "' + escHtml(num) + '". Please check the number and try again.</div>';
            }
        })
        .catch(function() {
            btn.innerHTML = 'Look Up';
            btn.disabled = false;
            result.style.display = 'block';
            result.innerHTML = '<div class="sp-track-error"><strong>Connection error</strong>Could not reach the server. Please try again.</div>';
        });
}

function renderTicket(d) {
    var statusLabels = { open: 'Open', in_progress: 'In Progress', resolved: 'Resolved', closed: 'Closed' };
    var priLabels    = { normal: 'Normal', high: 'High', emergency: 'Emergency' };

    var tl = '<div class="sp-tl-row"><span class="sp-tl-dot" style="background:#3b82f6"></span><span class="sp-tl-text">Request submitted</span><span class="sp-tl-time">' + escHtml(d.created_at_fmt) + '</span></div>';
    if (d.acknowledged_at_fmt) {
        tl += '<div class="sp-tl-row"><span class="sp-tl-dot" style="background:#f59e0b"></span><span class="sp-tl-text">Acknowledged by staff</span><span class="sp-tl-time">' + escHtml(d.acknowledged_at_fmt) + '</span></div>';
    }
    if (d.status === 'resolved' || d.status === 'closed') {
        tl += '<div class="sp-tl-row"><span class="sp-tl-dot" style="background:#22c55e"></span><span class="sp-tl-text">Issue resolved</span><span class="sp-tl-time">' + escHtml(d.updated_at_fmt) + '</span></div>';
    }

    return '<div class="sp-track-card">' +
        '<div class="sp-track-card-header">' +
            '<div><div class="sp-track-num">' + escHtml(d.ticket_number) + '</div><div class="sp-track-when">Submitted ' + escHtml(d.created_at_fmt) + '</div></div>' +
            '<span class="sp-badge badge-' + escHtml(d.status) + '"><span class="sp-badge-dot"></span>' + escHtml(statusLabels[d.status] || d.status) + '</span>' +
        '</div>' +
        '<div class="sp-track-meta">' +
            '<div><div class="sp-track-item-label">Priority</div><div><span class="sp-badge badge-' + escHtml(d.priority) + '"><span class="sp-badge-dot"></span>' + escHtml(priLabels[d.priority] || d.priority) + '</span></div></div>' +
            '<div><div class="sp-track-item-label">Department(s)</div><div class="sp-track-item-val">' + escHtml(d.departments || '—') + '</div></div>' +
            '<div class="sp-track-address"><div class="sp-track-item-label">Location</div><div class="sp-track-item-val">' + escHtml(d.address) + '</div></div>' +
        '</div>' +
        '<div class="sp-track-timeline">' + tl + '</div>' +
    '</div>';
}

function escHtml(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Allow Enter key in track input
document.getElementById('track-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') lookupTicket();
});

// Photo preview
var selectedFiles = [];
function previewPhotos(input) {
    var newFiles = Array.from(input.files).slice(0, 3 - selectedFiles.length);
    newFiles.forEach(function(f) { if (selectedFiles.length < 3) selectedFiles.push(f); });
    renderPreviews();
    input.value = '';
}
function renderPreviews() {
    var wrap = document.getElementById('sp-photo-previews');
    var zone = document.getElementById('sp-drop-zone');
    wrap.innerHTML = '';
    selectedFiles.forEach(function(f, i) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var div = document.createElement('div');
            div.className = 'sp-photo-thumb';
            div.innerHTML = '<img src="' + e.target.result + '"><button type="button" class="sp-photo-remove" onclick="removePhoto(' + i + ')">✕</button>';
            wrap.appendChild(div);
        };
        reader.readAsDataURL(f);
    });
    // Rebuild hidden file input with selected files via DataTransfer
    syncFileInput();
    zone.querySelector('.sp-drop-text').textContent = selectedFiles.length >= 3 ? '3 photos added (max)' : 'Tap to add photos';
}
function removePhoto(i) {
    selectedFiles.splice(i, 1);
    renderPreviews();
}
function syncFileInput() {
    var input = document.getElementById('sp-photos');
    try {
        var dt = new DataTransfer();
        selectedFiles.forEach(function(f) { dt.items.add(f); });
        input.files = dt.files;
    } catch(e) {}
}

// Drag-and-drop
(function() {
    var zone = document.getElementById('sp-drop-zone');
    if (!zone) return;
    zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', function() { zone.classList.remove('dragover'); });
    zone.addEventListener('drop', function(e) {
        e.preventDefault();
        zone.classList.remove('dragover');
        var files = Array.from(e.dataTransfer.files).filter(function(f) { return f.type.startsWith('image/'); }).slice(0, 3 - selectedFiles.length);
        files.forEach(function(f) { if (selectedFiles.length < 3) selectedFiles.push(f); });
        renderPreviews();
    });
})();

// Submit button loading state
document.getElementById('sp-city-form') && document.getElementById('sp-city-form').addEventListener('submit', function() {
    var btn = document.getElementById('sp-submit-btn');
    btn.innerHTML = '<span class="sp-spinner"></span> Submitting...';
    btn.disabled = true;
});
</script>

<?php wp_footer(); ?>
</body>
</html>
