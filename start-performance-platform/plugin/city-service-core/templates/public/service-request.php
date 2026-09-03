<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$city_name = get_option( 'sp_city_name', get_bloginfo( 'name' ) );
$accent    = get_option( 'sp_city_accent_color', '#1B3A6B' );
$year      = date( 'Y' );

// Darken accent for hover
function sp_city_darken( $hex, $pct = 15 ) {
    $hex = ltrim( $hex, '#' );
    list( $r, $g, $b ) = array_map( 'hexdec', str_split( $hex, 2 ) );
    $r = max( 0, $r - round( $r * $pct / 100 ) );
    $g = max( 0, $g - round( $g * $pct / 100 ) );
    $b = max( 0, $b - round( $b * $pct / 100 ) );
    return sprintf( '#%02x%02x%02x', $r, $g, $b );
}
$accent_dark = sp_city_darken( $accent );
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Submit a Service Request — <?php echo esc_html( $city_name ); ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: #f0f4f8;
    color: #1a202c;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* ── Header ── */
.sp-header {
    background: <?php echo esc_attr( $accent ); ?>;
    padding: 0 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,.18);
}
.sp-header-inner {
    max-width: 760px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 64px;
}
.sp-header-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}
.sp-header-icon {
    width: 36px;
    height: 36px;
    background: rgba(255,255,255,.15);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.sp-header-icon svg { width: 20px; height: 20px; stroke: #fff; fill: none; }
.sp-header-city {
    font-size: 17px;
    font-weight: 700;
    color: #fff;
    line-height: 1.2;
}
.sp-header-sub {
    font-size: 11px;
    color: rgba(255,255,255,.65);
    font-weight: 400;
    display: block;
}
.sp-header-badge {
    font-size: 11px;
    color: rgba(255,255,255,.7);
    background: rgba(255,255,255,.12);
    border-radius: 20px;
    padding: 4px 12px;
    border: 1px solid rgba(255,255,255,.2);
}

/* ── Hero strip ── */
.sp-hero {
    background: <?php echo esc_attr( $accent ); ?>;
    padding: 28px 24px 36px;
    text-align: center;
    border-bottom: 4px solid <?php echo esc_attr( $accent_dark ); ?>;
}
.sp-hero h1 {
    color: #fff;
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 6px;
}
.sp-hero p {
    color: rgba(255,255,255,.8);
    font-size: 15px;
}

/* ── Main content ── */
.sp-main {
    flex: 1;
    max-width: 760px;
    width: 100%;
    margin: -16px auto 0;
    padding: 0 16px 48px;
}

/* ── Info strip ── */
.sp-info-strip {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 24px;
}
.sp-info-card {
    background: #fff;
    border-radius: 10px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,.07);
}
.sp-info-card svg { width: 20px; height: 20px; stroke: <?php echo esc_attr( $accent ); ?>; fill: none; flex-shrink: 0; }
.sp-info-card-text { font-size: 12px; }
.sp-info-card-text strong { display: block; font-size: 13px; color: #111; margin-bottom: 1px; }
.sp-info-card-text span { color: #6b7280; }

/* ── Card ── */
.sp-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.08);
    overflow: hidden;
}
.sp-card-header {
    background: <?php echo esc_attr( $accent ); ?>;
    padding: 20px 28px;
}
.sp-card-header h2 { color: #fff; font-size: 18px; font-weight: 700; margin-bottom: 2px; }
.sp-card-header p  { color: rgba(255,255,255,.75); font-size: 13px; }
.sp-card-body { padding: 28px; }

/* ── Form ── */
.sp-field { margin-bottom: 18px; }
.sp-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}
.sp-label .req { color: #ef4444; }
.sp-input, .sp-select, .sp-textarea {
    width: 100%;
    border: 1.5px solid #d1d5db;
    border-radius: 8px;
    padding: 10px 13px;
    font-size: 14px;
    font-family: inherit;
    color: #111;
    background: #fff;
    transition: border-color .15s;
    outline: none;
}
.sp-input:focus, .sp-select:focus, .sp-textarea:focus {
    border-color: <?php echo esc_attr( $accent ); ?>;
    box-shadow: 0 0 0 3px <?php echo esc_attr( $accent ); ?>22;
}
.sp-textarea { resize: vertical; min-height: 100px; }
.sp-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

/* ── Dept pills ── */
.sp-dept-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
.sp-dept-label {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: 1.5px solid #e5e7eb;
    border-radius: 8px;
    background: #f9fafb;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: border-color .15s, background .15s;
    user-select: none;
}
.sp-dept-label:hover { border-color: <?php echo esc_attr( $accent ); ?>; background: #fff; }
.sp-dept-label input { position: absolute; opacity: 0; width: 0; height: 0; }
.sp-dept-label.checked { border-color: <?php echo esc_attr( $accent ); ?>; background: #fff; }
.sp-dept-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.sp-dept-check { width: 16px; height: 16px; border: 1.5px solid #d1d5db; border-radius: 4px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .15s; }
.sp-dept-label.checked .sp-dept-check { background: <?php echo esc_attr( $accent ); ?>; border-color: <?php echo esc_attr( $accent ); ?>; }
.sp-dept-label.checked .sp-dept-check::after { content: ""; display: block; width: 8px; height: 5px; border-left: 2px solid #fff; border-bottom: 2px solid #fff; transform: rotate(-45deg) translate(1px,-1px); }

/* ── Priority selector ── */
.sp-priority-grid { display: flex; gap: 10px; }
.sp-priority-opt { flex: 1; }
.sp-priority-opt input { position: absolute; opacity: 0; }
.sp-priority-opt label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 12px 8px;
    border: 1.5px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-align: center;
    transition: all .15s;
}
.sp-priority-opt input:checked + label { color: #111; }
.sp-priority-opt.normal  input:checked + label { border-color: #3b82f6; background: #eff6ff; color: #1d4ed8; }
.sp-priority-opt.high    input:checked + label { border-color: #f59e0b; background: #fffbeb; color: #92400e; }
.sp-priority-opt.emergency input:checked + label { border-color: #ef4444; background: #fef2f2; color: #b91c1c; }
.sp-priority-dot { width: 10px; height: 10px; border-radius: 50%; }
.normal    .sp-priority-dot { background: #3b82f6; }
.high      .sp-priority-dot { background: #f59e0b; }
.emergency .sp-priority-dot { background: #ef4444; }

/* ── Divider ── */
.sp-divider { border: none; border-top: 1px solid #f0f0f0; margin: 22px 0; }

/* ── Contact section ── */
.sp-contact-header {
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #9ca3af;
    margin-bottom: 14px;
}

/* ── Submit button ── */
.sp-submit {
    width: 100%;
    background: <?php echo esc_attr( $accent ); ?>;
    color: #fff;
    border: none;
    border-radius: 9px;
    padding: 14px 24px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s;
    margin-top: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.sp-submit:hover { background: <?php echo esc_attr( $accent_dark ); ?>; }
.sp-submit svg { width: 18px; height: 18px; stroke: #fff; fill: none; }

/* ── Success card ── */
.sp-success {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.08);
    padding: 52px 32px;
    text-align: center;
}
.sp-success-icon { font-size: 56px; margin-bottom: 16px; }
.sp-success h2 { font-size: 24px; color: #166534; margin-bottom: 8px; }
.sp-success p { color: #4b5563; font-size: 15px; margin-bottom: 24px; }
.sp-ticket-badge {
    display: inline-block;
    background: #f0fdf4;
    border: 1.5px solid #86efac;
    border-radius: 10px;
    padding: 16px 32px;
    margin-bottom: 20px;
}
.sp-ticket-badge .tk-label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 4px; }
.sp-ticket-badge .tk-num { font-size: 30px; font-weight: 800; color: #111; letter-spacing: .04em; }
.sp-back-link { color: <?php echo esc_attr( $accent ); ?>; font-weight: 600; text-decoration: none; font-size: 14px; }
.sp-back-link:hover { text-decoration: underline; }

/* ── Footer ── */
.sp-footer {
    background: #1a202c;
    color: rgba(255,255,255,.5);
    text-align: center;
    padding: 18px 24px;
    font-size: 12px;
}
.sp-footer a { color: rgba(255,255,255,.5); text-decoration: none; }

@media (max-width: 600px) {
    .sp-info-strip { grid-template-columns: 1fr; }
    .sp-grid-2 { grid-template-columns: 1fr; }
    .sp-priority-grid { flex-direction: column; }
    .sp-hero h1 { font-size: 21px; }
    .sp-card-body { padding: 20px; }
}
</style>
<?php wp_head(); ?>
</head>
<body>

<!-- Header -->
<header class="sp-header">
    <div class="sp-header-inner">
        <a class="sp-header-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
            <div class="sp-header-icon">
                <svg viewBox="0 0 24 24" stroke-width="2"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9"/></svg>
            </div>
            <div>
                <span class="sp-header-city"><?php echo esc_html( $city_name ); ?></span>
                <span class="sp-header-sub">Public Services</span>
            </div>
        </a>
        <span class="sp-header-badge">Service Request Portal</span>
    </div>
</header>

<!-- Hero -->
<div class="sp-hero">
    <h1>Submit a Service Request</h1>
    <p>Report utility or maintenance issues directly to the right department.</p>
</div>

<!-- Main -->
<main class="sp-main">

    <!-- Info strip -->
    <div class="sp-info-strip">
        <div class="sp-info-card">
            <svg viewBox="0 0 24 24" stroke-width="2"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="sp-info-card-text">
                <strong>Fast Response</strong>
                <span>On-call staff notified immediately</span>
            </div>
        </div>
        <div class="sp-info-card">
            <svg viewBox="0 0 24 24" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <div class="sp-info-card-text">
                <strong>Ticket Number</strong>
                <span>Track your request anytime</span>
            </div>
        </div>
        <div class="sp-info-card">
            <svg viewBox="0 0 24 24" stroke-width="2"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <div class="sp-info-card-text">
                <strong>Email Confirmation</strong>
                <span>Confirmation sent automatically</span>
            </div>
        </div>
    </div>

    <?php
    // ── Success state ────────────────────────────────────────────────────────
    if ( isset( $_GET['city_ticket'] ) ) :
        $num = sanitize_text_field( $_GET['city_ticket'] );
    ?>
    <div class="sp-success">
        <div class="sp-success-icon">&#10003;</div>
        <h2>Request Submitted!</h2>
        <p>Your service request has been received and our on-call team has been notified.</p>
        <div class="sp-ticket-badge">
            <div class="tk-label">Your Ticket Number</div>
            <div class="tk-num"><?php echo esc_html( $num ); ?></div>
        </div>
        <p style="font-size:13px;color:#9ca3af;margin-bottom:20px">Save this number to reference your request.<br>A confirmation email has been sent if you provided one.</p>
        <a class="sp-back-link" href="<?php echo esc_url( get_permalink() ); ?>">&#8592; Submit another request</a>
    </div>

    <?php else : ?>

    <!-- Form card -->
    <div class="sp-card">
        <div class="sp-card-header">
            <h2>Service Request Form</h2>
            <p>All requests are routed to the correct department automatically.</p>
        </div>
        <div class="sp-card-body">
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="sp-city-form">
                <?php wp_nonce_field( 'sp_city_public', 'sp_city_nonce' ); ?>
                <input type="hidden" name="action" value="sp_city_submit">
                <input type="hidden" name="return_url" value="<?php echo esc_url( get_permalink() ); ?>">

                <!-- Departments -->
                <div class="sp-field">
                    <label class="sp-label">Which department(s) does this involve? <span class="req">*</span></label>
                    <div class="sp-dept-grid" id="sp-dept-grid">
                    <?php foreach ( sp_city_get_departments() as $d ) : ?>
                        <div class="sp-dept-label" data-id="<?php echo esc_attr( $d->id ); ?>">
                            <input type="checkbox" name="dept_ids[]" value="<?php echo esc_attr( $d->id ); ?>">
                            <span class="sp-dept-dot" style="background:<?php echo esc_attr( $d->color ); ?>"></span>
                            <?php echo esc_html( $d->name ); ?>
                            <span class="sp-dept-check"></span>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>

                <!-- Priority -->
                <div class="sp-field">
                    <label class="sp-label">Priority Level</label>
                    <div class="sp-priority-grid">
                        <div class="sp-priority-opt normal">
                            <input type="radio" name="priority" id="pri-normal" value="normal" checked>
                            <label for="pri-normal">
                                <span class="sp-priority-dot"></span>
                                Normal
                                <span style="font-weight:400;color:inherit;opacity:.7">Routine issue</span>
                            </label>
                        </div>
                        <div class="sp-priority-opt high">
                            <input type="radio" name="priority" id="pri-high" value="high">
                            <label for="pri-high">
                                <span class="sp-priority-dot"></span>
                                High
                                <span style="font-weight:400;color:inherit;opacity:.7">Needs prompt attention</span>
                            </label>
                        </div>
                        <div class="sp-priority-opt emergency">
                            <input type="radio" name="priority" id="pri-emergency" value="emergency">
                            <label for="pri-emergency">
                                <span class="sp-priority-dot"></span>
                                Emergency
                                <span style="font-weight:400;color:inherit;opacity:.7">Urgent / safety risk</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Location -->
                <div class="sp-field">
                    <label class="sp-label" for="sp-address">Address or Location of Issue <span class="req">*</span></label>
                    <input id="sp-address" class="sp-input" type="text" name="address" required placeholder="e.g. 123 Main Street or Intersection of Oak & 5th">
                </div>

                <!-- Description -->
                <div class="sp-field">
                    <label class="sp-label" for="sp-desc">Description of Issue <span class="req">*</span></label>
                    <textarea id="sp-desc" class="sp-textarea" name="description" required placeholder="Please describe what you're seeing — the more detail, the better."></textarea>
                </div>

                <hr class="sp-divider">

                <!-- Contact -->
                <div class="sp-contact-header">Your Contact Info <span style="font-weight:400;text-transform:none;letter-spacing:0">(optional — helps us follow up)</span></div>
                <div class="sp-grid-2">
                    <div class="sp-field" style="margin-bottom:0">
                        <label class="sp-label" for="sp-name">Full Name</label>
                        <input id="sp-name" class="sp-input" type="text" name="reporter_name">
                    </div>
                    <div class="sp-field" style="margin-bottom:0">
                        <label class="sp-label" for="sp-phone">Phone Number</label>
                        <input id="sp-phone" class="sp-input" type="tel" name="reporter_phone">
                    </div>
                </div>
                <div class="sp-field" style="margin-top:14px">
                    <label class="sp-label" for="sp-email">Email Address <span style="font-weight:400;color:#9ca3af">(for confirmation)</span></label>
                    <input id="sp-email" class="sp-input" type="email" name="reporter_email">
                </div>

                <button type="submit" class="sp-submit">
                    <svg viewBox="0 0 24 24" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    Submit Service Request
                </button>
            </form>
        </div>
    </div>

    <?php endif; ?>
</main>

<!-- Footer -->
<footer class="sp-footer">
    &copy; <?php echo $year; ?> <?php echo esc_html( $city_name ); ?> &nbsp;&middot;&nbsp;
    Powered by <a href="https://startperformance.com" target="_blank">Start Performance</a>
    &nbsp;&middot;&nbsp; <a href="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">Staff Login</a>
</footer>

<script>
// Dept pill toggle
document.querySelectorAll('.sp-dept-label').forEach(function(lbl) {
    lbl.addEventListener('click', function() {
        var cb = this.querySelector('input[type=checkbox]');
        cb.checked = !cb.checked;
        this.classList.toggle('checked', cb.checked);
    });
});
// Priority radio styling already handled by CSS :checked
</script>

<?php wp_footer(); ?>
</body>
</html>
