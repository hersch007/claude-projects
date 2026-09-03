<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$is_vendor  = ! empty( $_GET['vendor'] );
$error      = sanitize_key( isset( $_GET['error'] ) ? $_GET['error'] : '' );

$mode        = sanitize_key( isset( $_GET['mode'] ) ? $_GET['mode'] : '' );
$token       = sanitize_text_field( isset( $_GET['token'] ) ? $_GET['token'] : '' );
$error_msg   = '';
$success_msg = '';
if ( $error === 'pin' )        $error_msg = 'Incorrect PIN. Please try again.';
if ( $error === 'nopin' )      $error_msg = 'Admin access is not enabled for this site. Please contact support.';
if ( $error === 'invalid' )    $error_msg = 'Invalid request. Please try again.';
if ( $error === 'noemail' )    $error_msg = 'No account found with that email address.';
if ( $error === 'tokenbad' )   $error_msg = 'This reset link has expired or already been used. Please request a new one.';
if ( $error === 'pinmatch' )   $error_msg = 'PINs do not match. Please try again.';
if ( $error === 'pinshort' )   $error_msg = 'PIN must be at least 4 characters.';
if ( isset( $_GET['sent'] ) )  $success_msg = 'Check your email — we sent you a reset link.';
if ( isset( $_GET['reset'] ) ) $success_msg = 'PIN updated. You can now sign in.';
if ( isset( $_GET['setup'] ) && $_GET['setup'] === 'done' ) $success_msg = 'Setup complete! Sign in with your new account.';
if ( $error === 'terms' )      $error_msg = 'You must agree to the Terms of Service and Privacy Policy to continue.';

$prefill_team_id = (int) ( isset( $_GET['sp_team_id'] ) ? $_GET['sp_team_id'] : 0 );

$team_members = $wpdb->get_results(
    "SELECT id, name FROM {$wpdb->prefix}sp_team WHERE status = 'active' ORDER BY name"
);
$sp_accent      = get_option( 'sp_accent_color', '#CC1F1F' );
$sp_accent_dark = sp_darken_hex( $sp_accent, 0.15 );
$sp_icon_url    = get_option( 'sp_brand_icon_url', '' );
$sp_initials    = get_option( 'sp_brand_initials', '' );
$sp_name        = get_option( 'sp_platform_name', 'Start Performance' );
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — <?php echo esc_html( $sp_name ); ?></title>
<?php $sp_favicon = get_option( 'sp_favicon_url', '' ); if ( $sp_favicon ) :
	$sp_fav_type = ( '.svg' === strtolower( substr( (string) parse_url( $sp_favicon, PHP_URL_PATH ), -4 ) ) ) ? ' type="image/svg+xml"' : ' type="image/png"'; ?>
<link rel="icon"<?php echo $sp_fav_type; ?> href="<?php echo esc_url( $sp_favicon ); ?>">
<link rel="shortcut icon" href="<?php echo esc_url( $sp_favicon ); ?>">
<?php endif; ?>
<style>:root{--sp-accent:<?php echo esc_attr( $sp_accent ); ?>;--sp-accent-dark:<?php echo esc_attr( $sp_accent_dark ); ?>}</style>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:40px;width:100%;max-width:380px;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.06)}
.logo-mark{width:60px;height:60px;border-radius:14px;background:var(--sp-accent,#CC1F1F);display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px}
.logo-mark svg{width:36px;height:36px}
.logo-name{font-size:20px;font-weight:800;color:#1e293b;margin-bottom:4px}
.logo-tag{font-size:13px;color:#94a3b8;margin-bottom:36px}
h1{font-size:18px;font-weight:700;color:#1e293b;margin-bottom:4px}
p{font-size:14px;color:#94a3b8;margin-bottom:24px}
.field{margin-bottom:14px;text-align:left}
.field label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;margin-bottom:6px}
select{width:100%;background:#f8fafc;border:2px solid #e2e8f0;border-radius:10px;padding:14px 16px;font-size:15px;color:#1e293b;outline:none;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2394a3b8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;cursor:pointer;transition:border-color .2s}
select:focus{border-color:var(--sp-accent,#CC1F1F);box-shadow:0 0 0 3px rgba(204,31,31,.1)}
.pin-input{width:100%;background:#f8fafc;border:2px solid #e2e8f0;border-radius:10px;padding:16px;font-size:28px;font-weight:700;color:#1e293b;text-align:center;letter-spacing:12px;outline:none;margin-bottom:16px;transition:border-color .2s}
.pin-input:focus{border-color:var(--sp-accent,#CC1F1F);box-shadow:0 0 0 3px rgba(204,31,31,.1)}
button{width:100%;background:var(--sp-accent,#CC1F1F);color:#fff;font-size:15px;font-weight:700;border:none;border-radius:8px;padding:14px;cursor:pointer;transition:background .2s}
button:hover{background:var(--sp-accent-dark,#b01818)}
.error{background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px 14px;font-size:13px;color:#dc2626;margin-bottom:20px;text-align:left}
.footer{margin-top:24px;font-size:12px;color:#cbd5e1}
.back-link{display:block;margin-top:16px;font-size:13px;color:#94a3b8;text-decoration:none}
.back-link:hover{color:#64748b}
.forgot-link{display:block;margin-top:12px;font-size:13px;color:#94a3b8;text-decoration:none;cursor:pointer}
.forgot-link:hover{color:#64748b}
.success{background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:12px 14px;font-size:13px;color:#166534;margin-bottom:20px;text-align:left}
input[type=email],input[type=text]{width:100%;background:#f8fafc;border:2px solid #e2e8f0;border-radius:10px;padding:14px 16px;font-size:15px;color:#1e293b;outline:none;transition:border-color .2s;margin-bottom:16px}
input[type=email]:focus,input[type=text]:focus{border-color:var(--sp-accent,#CC1F1F);box-shadow:0 0 0 3px rgba(204,31,31,.1)}
.terms-wrap{display:flex;align-items:flex-start;gap:10px;text-align:left;margin-bottom:16px;padding:12px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px}
.terms-wrap input[type=checkbox]{margin-top:2px;width:16px;height:16px;min-width:16px;accent-color:var(--sp-accent,#CC1F1F);cursor:pointer}
.terms-wrap label{font-size:12px;color:#64748b;line-height:1.5;cursor:pointer}
.terms-wrap a{color:var(--sp-accent,#CC1F1F);text-decoration:none}
.terms-wrap a:hover{text-decoration:underline}
.footer-links{margin-top:16px;font-size:11px;color:#cbd5e1}
.footer-links a{color:#94a3b8;text-decoration:none}
.footer-links a:hover{color:#64748b}
</style>
</head>
<body>
<div class="card">
    <div class="logo-mark" <?php if ( $sp_icon_url ) echo 'style="background:transparent;border-radius:0;box-shadow:none;"'; ?>>
        <?php if ( $sp_icon_url ) : ?>
            <img src="<?php echo esc_url( $sp_icon_url ); ?>" style="width:60px;height:60px;object-fit:contain;" alt="">
        <?php elseif ( $sp_initials ) : ?>
            <span style="font-size:<?php echo strlen($sp_initials)>2?'14':'18'; ?>px;font-weight:800;color:#fff;letter-spacing:-.5px;line-height:1"><?php echo esc_html( strtoupper( $sp_initials ) ); ?></span>
        <?php else : ?>
        <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="18" cy="18" r="3.5" fill="white"/>
            <circle cx="18" cy="5"  r="2.5" fill="white"/>
            <circle cx="18" cy="31" r="2.5" fill="white"/>
            <circle cx="5"  cy="18" r="2.5" fill="white"/>
            <circle cx="31" cy="18" r="2.5" fill="white"/>
            <circle cx="8"  cy="8"  r="2.5" fill="white"/>
            <circle cx="28" cy="8"  r="2.5" fill="white"/>
            <circle cx="8"  cy="28" r="2.5" fill="white"/>
            <circle cx="28" cy="28" r="2.5" fill="white"/>
            <line x1="18" y1="14.5" x2="18" y2="7.5"   stroke="white" stroke-width="1.8"/>
            <line x1="18" y1="21.5" x2="18" y2="28.5"  stroke="white" stroke-width="1.8"/>
            <line x1="14.5" y1="18" x2="7.5"  y2="18"  stroke="white" stroke-width="1.8"/>
            <line x1="21.5" y1="18" x2="28.5" y2="18"  stroke="white" stroke-width="1.8"/>
            <line x1="15.5" y1="15.5" x2="10" y2="10"  stroke="white" stroke-width="1.8"/>
            <line x1="20.5" y1="15.5" x2="26" y2="10"  stroke="white" stroke-width="1.8"/>
            <line x1="15.5" y1="20.5" x2="10" y2="26"  stroke="white" stroke-width="1.8"/>
            <line x1="20.5" y1="20.5" x2="26" y2="26"  stroke="white" stroke-width="1.8"/>
        </svg>
        <?php endif; ?>
    </div>
    <div class="logo-name"><?php echo esc_html( $sp_name ); ?></div>
    <div class="logo-tag">Business Operating Platform</div>

    <?php if ( $is_vendor ) : ?>
    <h1>Platform Access</h1>
    <p>Enter your vendor PIN to continue</p>

    <?php if ( $error_msg ) : ?><div class="error"><?php echo esc_html( $error_msg ); ?></div><?php endif; ?>

    <form method="post" action="<?php echo esc_url( home_url( '/sp-login/?vendor=1' ) ); ?>">
        <input type="hidden" name="sp_vendor_login" value="1">
        <input type="password" name="pin" class="pin-input" maxlength="20" placeholder="••••••••" autofocus required>
        <button type="submit">Access Platform &rarr;</button>
    </form>

    <?php elseif ( $mode === 'forgot' ) : ?>
    <h1>Reset your PIN</h1>
    <p>Enter your email address and we'll send you a reset link</p>

    <?php if ( $error_msg ) : ?><div class="error"><?php echo esc_html( $error_msg ); ?></div><?php endif; ?>
    <?php if ( $success_msg ) : ?><div class="success"><?php echo esc_html( $success_msg ); ?></div><?php endif; ?>

    <?php if ( ! isset( $_GET['sent'] ) ) : ?>
    <form method="post" action="<?php echo esc_url( home_url( '/sp-login/' ) ); ?>">
        <?php wp_nonce_field( 'sp_pin_reset', 'sp_reset_nonce' ); ?>
        <input type="hidden" name="sp_type" value="forgot_pin">
        <input type="email" name="email" placeholder="your@email.com" autofocus required>
        <button type="submit">Send Reset Link &rarr;</button>
    </form>
    <?php endif; ?>
    <a href="<?php echo esc_url( home_url( '/sp-login/' ) ); ?>" class="back-link">← Back to sign in</a>

    <?php elseif ( $mode === 'reset' && $token ) : ?>
    <?php
    $reset_data = get_transient( 'sp_pin_reset_' . $token );
    if ( ! $reset_data ) {
        echo '<div class="error">This reset link has expired or already been used. <a href="' . esc_url( home_url( '/sp-login/?mode=forgot' ) ) . '" style="color:#dc2626;">Request a new one</a>.</div>';
    } else {
    ?>
    <h1>Set a new PIN</h1>
    <p>Choose a new PIN for your account</p>

    <?php if ( $error_msg ) : ?><div class="error"><?php echo esc_html( $error_msg ); ?></div><?php endif; ?>

    <form method="post" action="<?php echo esc_url( home_url( '/sp-login/' ) ); ?>">
        <?php wp_nonce_field( 'sp_pin_reset', 'sp_reset_nonce' ); ?>
        <input type="hidden" name="sp_type" value="reset_pin">
        <input type="hidden" name="sp_reset_token" value="<?php echo esc_attr( $token ); ?>">
        <div class="field"><label>New PIN</label></div>
        <input type="password" name="new_pin" class="pin-input" maxlength="20" placeholder="••••" autofocus required>
        <div class="field"><label>Confirm PIN</label></div>
        <input type="password" name="confirm_pin" class="pin-input" maxlength="20" placeholder="••••" required>
        <button type="submit">Set New PIN &rarr;</button>
    </form>
    <?php } ?>

    <?php else : ?>
    <h1>Welcome back</h1>
    <p>Select your name and enter your PIN</p>

    <?php if ( $success_msg ) : ?><div class="success"><?php echo esc_html( $success_msg ); ?></div><?php endif; ?>
    <?php if ( $error_msg ) : ?><div class="error"><?php echo esc_html( $error_msg ); ?></div><?php endif; ?>

    <form method="post" action="<?php echo esc_url( home_url( '/sp-login/' ) ); ?>">
        <?php wp_nonce_field( 'sp_login', 'sp_login_nonce' ); ?>
        <div class="field">
            <label>Who are you?</label>
            <select name="sp_team_id" required>
                <option value="">Select your name...</option>
                <?php foreach ( $team_members as $m ) : ?>
                    <option value="<?php echo esc_attr( $m->id ); ?>"><?php echo esc_html( $m->name ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="password" name="pin" class="pin-input" maxlength="20" placeholder="••••" autofocus required>
        <button type="submit">Sign In &rarr;</button>
    </form>
    <a href="<?php echo esc_url( home_url( '/sp-login/?mode=forgot' ) ); ?>" class="forgot-link">Forgot your PIN?</a>
    <?php endif; ?>

    <div class="footer">Start Performance v<?php echo esc_html( SP_VERSION ); ?></div>
</div>
<script>try { localStorage.removeItem('sp_nav_open'); } catch(e) {}</script>
</body>
</html>
