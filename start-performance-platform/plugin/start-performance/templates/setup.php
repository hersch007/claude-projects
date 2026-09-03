<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$step        = (int) ( isset( $_GET['step'] ) ? $_GET['step'] : 1 );
$step        = max( 1, min( 3, $step ) );
$error       = sanitize_text_field( isset( $_GET['error'] ) ? $_GET['error'] : '' );
$sp_accent   = get_option( 'sp_accent_color', '#CC1F1F' );
$sp_accent_d = sp_darken_hex( $sp_accent, 0.15 );

$step_labels = array( 1 => 'Platform', 2 => 'Brand', 3 => 'Admin Account' );
$error_msgs  = array(
    'pin_mismatch' => 'PINs do not match. Please try again.',
    'pin_short'    => 'PIN must be at least 4 digits.',
    'name_required'=> 'Admin name is required.',
    'nonce'        => 'Security check failed. Please try again.',
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Platform Setup — Start Performance</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
:root{--accent:<?php echo esc_attr($sp_accent); ?>;--accent-dark:<?php echo esc_attr($sp_accent_d); ?>}
.wrap{width:100%;max-width:520px}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:40px;box-shadow:0 4px 24px rgba(0,0,0,.06)}
.logo-mark{width:52px;height:52px;border-radius:12px;background:var(--accent);display:flex;align-items:center;justify-content:center;margin-bottom:20px}
.logo-mark svg{width:30px;height:30px}
.step-bar{display:flex;gap:6px;margin-bottom:32px}
.step-pip{flex:1;height:4px;border-radius:2px;background:#e2e8f0}
.step-pip.done{background:var(--accent)}
.step-pip.active{background:var(--accent);opacity:.5}
.step-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:6px}
h1{font-size:22px;font-weight:800;color:#1e293b;margin-bottom:6px}
.subtitle{font-size:14px;color:#64748b;margin-bottom:28px;line-height:1.5}
.field{margin-bottom:18px}
.field label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin-bottom:6px}
.field input[type=text],.field input[type=url],.field input[type=password],.field input[type=email]{width:100%;background:#f8fafc;border:2px solid #e2e8f0;border-radius:10px;padding:12px 14px;font-size:15px;color:#1e293b;outline:none;transition:border-color .2s}
.field input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(204,31,31,.08)}
.color-row{display:flex;align-items:center;gap:10px}
.color-row input[type=color]{width:48px;height:44px;border:2px solid #e2e8f0;border-radius:10px;cursor:pointer;padding:2px;background:#f8fafc}
.color-row input[type=text]{flex:1}
.hint{font-size:12px;color:#94a3b8;margin-top:5px}
.preview{margin:20px 0;padding:14px 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;display:flex;align-items:center;gap:10px}
.preview-icon{width:32px;height:32px;border-radius:7px;background:var(--accent);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:12px;font-weight:800;color:#fff;overflow:hidden}
.preview-icon img{width:32px;height:32px;object-fit:cover}
.preview-name{font-size:14px;font-weight:700;color:#1e293b}
.preview-sub{font-size:11px;color:#94a3b8}
.pin-wrap{position:relative}
.pin-wrap input{letter-spacing:6px;font-size:22px;font-weight:700;text-align:center;padding:14px}
.pin-toggle{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;font-size:12px}
.actions{display:flex;gap:10px;margin-top:28px}
.btn{flex:1;padding:14px;border-radius:10px;font-size:15px;font-weight:700;border:none;cursor:pointer;transition:background .2s;text-align:center;text-decoration:none}
.btn-primary{background:var(--accent);color:#fff}
.btn-primary:hover{background:var(--accent-dark)}
.btn-ghost{background:#f1f5f9;color:#475569}
.btn-ghost:hover{background:#e2e8f0}
.error{background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:11px 14px;font-size:13px;color:#dc2626;margin-bottom:20px}
.skip{text-align:center;margin-top:16px;font-size:12px;color:#94a3b8}
.skip a{color:#94a3b8;text-decoration:underline}
</style>
</head>
<body>
<div class="wrap">
<div class="card">

    <!-- Logo mark -->
    <div class="logo-mark">
        <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="18" cy="18" r="3.5" fill="white"/>
            <circle cx="18" cy="5" r="2.5" fill="white"/>
            <circle cx="18" cy="31" r="2.5" fill="white"/>
            <circle cx="5" cy="18" r="2.5" fill="white"/>
            <circle cx="31" cy="18" r="2.5" fill="white"/>
            <circle cx="8" cy="8" r="2.5" fill="white"/>
            <circle cx="28" cy="8" r="2.5" fill="white"/>
            <circle cx="8" cy="28" r="2.5" fill="white"/>
            <circle cx="28" cy="28" r="2.5" fill="white"/>
            <line x1="18" y1="14.5" x2="18" y2="7.5" stroke="white" stroke-width="1.8"/>
            <line x1="18" y1="21.5" x2="18" y2="28.5" stroke="white" stroke-width="1.8"/>
            <line x1="14.5" y1="18" x2="7.5" y2="18" stroke="white" stroke-width="1.8"/>
            <line x1="21.5" y1="18" x2="28.5" y2="18" stroke="white" stroke-width="1.8"/>
            <line x1="15.5" y1="15.5" x2="10" y2="10" stroke="white" stroke-width="1.8"/>
            <line x1="20.5" y1="15.5" x2="26" y2="10" stroke="white" stroke-width="1.8"/>
            <line x1="15.5" y1="20.5" x2="10" y2="26" stroke="white" stroke-width="1.8"/>
            <line x1="20.5" y1="20.5" x2="26" y2="26" stroke="white" stroke-width="1.8"/>
        </svg>
    </div>

    <!-- Step bar -->
    <div class="step-bar">
        <?php for ( $i = 1; $i <= 3; $i++ ) : ?>
            <div class="step-pip <?php echo $i < $step ? 'done' : ( $i === $step ? 'active' : '' ); ?>"></div>
        <?php endfor; ?>
    </div>
    <div class="step-label">Step <?php echo $step; ?> of 3 — <?php echo esc_html( $step_labels[ $step ] ); ?></div>

    <?php if ( $error && isset( $error_msgs[ $error ] ) ) : ?>
        <div class="error"><?php echo esc_html( $error_msgs[ $error ] ); ?></div>
    <?php endif; ?>

    <?php if ( $step === 1 ) : ?>
    <!-- ── Step 1: Platform ──────────────────────────────────────────────────── -->
    <h1>Welcome to Start Performance</h1>
    <p class="subtitle">Let's get your platform set up. This takes about two minutes.</p>

    <form method="post" action="<?php echo esc_url( home_url( '/sp-setup/' ) ); ?>">
        <?php wp_nonce_field( 'sp_setup_1', 'sp_setup_nonce' ); ?>
        <input type="hidden" name="sp_setup_step" value="1">

        <div class="field">
            <label>Platform Name</label>
            <input type="text" name="sp_platform_name" value="<?php echo esc_attr( get_option( 'sp_platform_name', '' ) ); ?>" placeholder="e.g. Acme Business Platform" required>
            <span class="hint">Shown in the browser title, login page, and sidebar.</span>
        </div>

        <div class="field">
            <label>Accent Color</label>
            <div class="color-row">
                <input type="color" id="color_pick" name="sp_accent_color" value="<?php echo esc_attr( get_option( 'sp_accent_color', '#CC1F1F' ) ); ?>">
                <input type="text" id="color_hex" value="<?php echo esc_attr( get_option( 'sp_accent_color', '#CC1F1F' ) ); ?>" maxlength="7" placeholder="#CC1F1F" oninput="syncColor(this.value,'pick')">
            </div>
            <span class="hint">Used for buttons, active nav items, and the brand icon.</span>
        </div>

        <div class="actions">
            <button type="submit" class="btn btn-primary">Next &rarr;</button>
        </div>
    </form>

    <?php elseif ( $step === 2 ) : ?>
    <!-- ── Step 2: Brand ─────────────────────────────────────────────────────── -->
    <?php
    $sp_name     = get_option( 'sp_platform_name', 'Your Platform' );
    $sp_icon_url = get_option( 'sp_brand_icon_url', '' );
    $sp_initials = get_option( 'sp_brand_initials', '' );
    ?>
    <h1>Add Your Brand</h1>
    <p class="subtitle">Upload your logo to the WordPress Media Library and paste the URL, or use short initials as a fallback.</p>

    <form method="post" action="<?php echo esc_url( home_url( '/sp-setup/' ) ); ?>">
        <?php wp_nonce_field( 'sp_setup_2', 'sp_setup_nonce' ); ?>
        <input type="hidden" name="sp_setup_step" value="2">

        <div class="field">
            <label>Logo Image URL <span style="font-weight:400;text-transform:none;font-size:11px">(optional)</span></label>
            <input type="url" name="sp_brand_icon_url" id="logo_url" value="<?php echo esc_attr( $sp_icon_url ); ?>" placeholder="https://… paste from Media Library">
            <span class="hint">Square image recommended. PNG or SVG. Leave blank to use initials.</span>
        </div>

        <div class="field">
            <label>Brand Initials <span style="font-weight:400;text-transform:none;font-size:11px">(if no image)</span></label>
            <input type="text" name="sp_brand_initials" id="brand_init" value="<?php echo esc_attr( $sp_initials ); ?>" maxlength="3" placeholder="e.g. SP" style="max-width:80px">
        </div>

        <!-- Live preview -->
        <div class="preview" id="brand_preview">
            <div class="preview-icon" id="preview_icon">
                <?php if ( $sp_icon_url ) : ?>
                    <img src="<?php echo esc_url( $sp_icon_url ); ?>" alt="">
                <?php else : ?>
                    <span id="preview_init"><?php echo esc_html( strtoupper( $sp_initials ?: substr( $sp_name, 0, 2 ) ) ); ?></span>
                <?php endif; ?>
            </div>
            <div>
                <div class="preview-name" id="preview_name"><?php echo esc_html( $sp_name ); ?></div>
                <div class="preview-sub">Sidebar preview</div>
            </div>
        </div>

        <div class="actions">
            <a href="<?php echo esc_url( home_url( '/sp-setup/?step=1' ) ); ?>" class="btn btn-ghost">&larr; Back</a>
            <button type="submit" class="btn btn-primary">Next &rarr;</button>
        </div>
    </form>

    <?php elseif ( $step === 3 ) : ?>
    <!-- ── Step 3: Admin Account ──────────────────────────────────────────────── -->
    <h1>Create Your Admin Account</h1>
    <p class="subtitle">This is the first team member who will manage the platform. You can add more from the Team section after setup.</p>

    <form method="post" action="<?php echo esc_url( home_url( '/sp-setup/' ) ); ?>">
        <?php wp_nonce_field( 'sp_setup_3', 'sp_setup_nonce' ); ?>
        <input type="hidden" name="sp_setup_step" value="3">

        <div class="field">
            <label>Full Name</label>
            <input type="text" name="admin_name" placeholder="e.g. Jane Smith" required>
        </div>

        <div class="field">
            <label>Email <span style="font-weight:400;text-transform:none;font-size:11px">(optional, for digest emails)</span></label>
            <input type="email" name="admin_email" placeholder="jane@yourbusiness.com">
        </div>

        <div class="field">
            <label>Login PIN</label>
            <div class="pin-wrap">
                <input type="password" name="admin_pin" id="pin1" inputmode="numeric" pattern="[0-9]*" maxlength="8" placeholder="••••" required>
                <button type="button" class="pin-toggle" onclick="togglePin('pin1',this)">Show</button>
            </div>
            <span class="hint">4–8 digit numeric PIN. This is what you'll enter on the login screen.</span>
        </div>

        <div class="field">
            <label>Confirm PIN</label>
            <div class="pin-wrap">
                <input type="password" name="admin_pin_confirm" id="pin2" inputmode="numeric" pattern="[0-9]*" maxlength="8" placeholder="••••" required>
                <button type="button" class="pin-toggle" onclick="togglePin('pin2',this)">Show</button>
            </div>
        </div>

        <div class="actions">
            <a href="<?php echo esc_url( home_url( '/sp-setup/?step=2' ) ); ?>" class="btn btn-ghost">&larr; Back</a>
            <button type="submit" class="btn btn-primary">Finish Setup &rarr;</button>
        </div>
    </form>
    <?php endif; ?>

    <div class="skip">
        <?php if ( sp_is_super_admin() ) : ?>
            <a href="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">Skip setup and go to the platform &rarr;</a>
        <?php else : ?>
            Already set up? <a href="<?php echo esc_url( home_url( '/sp-login/' ) ); ?>">Sign in</a>
        <?php endif; ?>
    </div>

</div><!-- .card -->
</div><!-- .wrap -->

<script>
function syncColor(val, target) {
    if (/^#[0-9a-fA-F]{6}$/.test(val)) {
        if (target === 'pick') document.getElementById('color_pick').value = val;
        else document.getElementById('color_hex').value = val;
        document.documentElement.style.setProperty('--accent', val);
    }
}
var pick = document.getElementById('color_pick');
var hex  = document.getElementById('color_hex');
if (pick) {
    pick.addEventListener('input', function() {
        if (hex) hex.value = this.value;
        document.querySelector('[name=sp_accent_color]').value = this.value;
        document.documentElement.style.setProperty('--accent', this.value);
    });
}

function togglePin(id, btn) {
    var el = document.getElementById(id);
    if (el.type === 'password') { el.type = 'text'; btn.textContent = 'Hide'; }
    else { el.type = 'password'; btn.textContent = 'Show'; }
}

// Brand preview live update
var logoInput = document.getElementById('logo_url');
var initInput = document.getElementById('brand_init');
var previewIcon = document.getElementById('preview_icon');
if (logoInput) {
    logoInput.addEventListener('input', function() {
        if (!previewIcon) return;
        if (this.value) {
            previewIcon.innerHTML = '<img src="'+this.value+'" style="width:32px;height:32px;object-fit:cover">';
        } else {
            var init = initInput ? initInput.value.toUpperCase() : '';
            previewIcon.innerHTML = '<span>'+init+'</span>';
        }
    });
}
if (initInput) {
    initInput.addEventListener('input', function() {
        var span = document.getElementById('preview_init');
        if (span) span.textContent = this.value.toUpperCase();
    });
}
</script>
</body>
</html>
