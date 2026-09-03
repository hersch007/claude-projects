<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$sp_app_title  = get_option( 'sp_platform_name', 'Start Performance' );
$sp_favicon    = get_option( 'sp_favicon_url', '' ) ?: get_option( 'sp_brand_icon_url', '' );
$sp_accent     = get_option( 'sp_accent_color', '#CC1F1F' );
$sp_accent_rgb = function_exists( 'sp_hex_to_rgb' ) ? sp_hex_to_rgb( $sp_accent ) : '204,31,31';
$sp_icon_url   = get_option( 'sp_brand_icon_url', '' );
$sp_brand_name = get_option( 'sp_platform_name', 'Start Performance' );
$logout_url    = esc_url( home_url( '/sp-app/?sp_logout=1' ) );
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<title><?php echo esc_html( $sp_app_title ); ?></title>
<?php if ( $sp_favicon ) : ?>
<link rel="icon" href="<?php echo esc_url( $sp_favicon ); ?>">
<link rel="apple-touch-icon" href="<?php echo esc_url( $sp_favicon ); ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?php echo esc_url( SP_PLUGIN_URL . 'assets/css/app.css?v=' . SP_VERSION ); ?>">
<style>
:root {
    --sp-accent: <?php echo esc_attr( $sp_accent ); ?>;
    --sp-accent-bg: rgba(<?php echo esc_attr( $sp_accent_rgb ); ?>, .18);
}
body { background: var(--sp-bg-alt, #f8fafc); min-height: 100vh; margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
[class*="cookieadmin"], [id*="cookieadmin"], #CybotCookiebotDialog, .CookieConsent { display: none !important; }
.sp-ob-wrap { max-width: 820px; margin: 0 auto; padding: 28px 20px 60px; }
.sp-ob-topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; padding-bottom: 16px; border-bottom: 1px solid var(--sp-border, #e5e7eb); }
.sp-ob-brand { display: flex; align-items: center; gap: 10px; }
.sp-ob-brand img { height: 34px; object-fit: contain; }
.sp-ob-brand-text { font-size: 1rem; font-weight: 700; color: var(--sp-text, #1e293b); }
.sp-ob-signout { font-size: .8rem; color: var(--sp-muted, #64748b); text-decoration: none; }
.sp-ob-signout:hover { color: var(--sp-text, #1e293b); }
</style>
</head>
<body>
<div class="sp-ob-wrap">
    <div class="sp-ob-topbar">
        <div class="sp-ob-brand">
            <?php if ( $sp_icon_url ) : ?>
                <img src="<?php echo esc_url( $sp_icon_url ); ?>" alt="<?php echo esc_attr( $sp_brand_name ); ?>">
            <?php endif; ?>
            <span class="sp-ob-brand-text"><?php echo esc_html( $sp_brand_name ); ?></span>
        </div>
        <div style="display:flex;gap:20px;align-items:center;">
            <?php if ( ! empty( $is_admin ) || ! empty( $is_super ) ) : ?>
                <a href="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="sp-ob-signout">&#8592; Admin Dashboard</a>
            <?php endif; ?>
            <a href="<?php echo $logout_url; ?>" class="sp-ob-signout">Sign out</a>
        </div>
    </div>

    <?php if ( $wts_onboard_status === 'inactive' ) : ?>
    <div style="background:#f1f5f9;color:#475569;border-left:4px solid #94a3b8;padding:16px 20px;border-radius:6px;margin-bottom:24px;">
        <strong>Account Inactive.</strong> Your jurisdiction access has been deactivated. Please contact your WTS representative for assistance.
    </div>
    <?php else : ?>

    <?php if ( $wts_onboard_status === 'pending' && ! isset( $_GET['action'] ) ) : ?>
    <div class="sp-notice sp-notice-success" style="margin-bottom:24px;">
        <strong>Questionnaire submitted.</strong> WTS is reviewing your information and will be in touch soon.
        You can still review or update your responses below.
    </div>
    <?php endif; ?>

    <?php
    $view_file = sp_get_view_file( 'client-setup' );
    if ( $view_file && file_exists( $view_file ) ) {
        require $view_file;
    }
    ?>

    <?php endif; ?>
</div>
<?php wp_footer(); ?>
</body>
</html>
