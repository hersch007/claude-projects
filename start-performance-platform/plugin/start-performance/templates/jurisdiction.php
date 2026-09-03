<?php
if ( ! defined( 'ABSPATH' ) ) exit;

header( 'Cache-Control: no-cache, no-store, must-revalidate' );
header( 'Pragma: no-cache' );
header( 'Expires: 0' );

$jur_views = array( 'client-setup', 'profile' );
$view = sanitize_key( isset( $_GET['view'] ) ? $_GET['view'] : 'client-setup' );
if ( ! in_array( $view, $jur_views ) ) $view = 'client-setup';

// Force-PIN flag: clear it once they successfully change their PIN
$force_pin = get_option( 'sp_wts_force_pin_' . $team_member->id, '' );
if ( $force_pin && $view === 'profile' && isset( $_GET['saved'] ) ) {
    delete_option( 'sp_wts_force_pin_' . $team_member->id );
    $force_pin = '';
}
// Redirect to profile if PIN change is still required
if ( $force_pin && $view !== 'profile' ) {
    wp_redirect( home_url( '/sp-app/?view=profile&must_change_pin=1' ) ); exit;
}

$view_file = sp_get_view_file( $view );

$sp_app_title      = get_option( 'sp_platform_name', 'Start Performance' );
$sp_favicon        = get_option( 'sp_favicon_url', '' ) ?: get_option( 'sp_brand_icon_url', '' );
$sp_accent         = get_option( 'sp_accent_color', '#CC1F1F' );
$sp_accent_dark    = sp_darken_hex( $sp_accent, 0.15 );
$sp_accent_rgb     = sp_hex_to_rgb( $sp_accent );
$sp_icon_url       = get_option( 'sp_brand_icon_url', '' );
$sp_initials       = get_option( 'sp_brand_initials', '' );
$sp_brand_name     = get_option( 'sp_platform_name', 'Start Performance' );
$sp_sidebar_bg     = get_option( 'sp_sidebar_bg', '' );
$sp_nav_color      = get_option( 'sp_nav_color', '' );
$sp_nav_hover_color= get_option( 'sp_nav_hover_color', '' );
$sp_nav_hover_bg   = get_option( 'sp_nav_hover_bg', '' );
$custom_vars = '';
if ( $sp_sidebar_bg )      $custom_vars .= '--sp-sidebar-bg:'      . esc_attr( $sp_sidebar_bg )      . ';';
if ( $sp_nav_color )       $custom_vars .= '--sp-nav-color:'       . esc_attr( $sp_nav_color )       . ';';
if ( $sp_nav_hover_color ) $custom_vars .= '--sp-nav-hover-color:' . esc_attr( $sp_nav_hover_color ) . ';';
if ( $sp_nav_hover_bg )    $custom_vars .= '--sp-nav-hover-bg:'    . esc_attr( $sp_nav_hover_bg )    . ';';

$member_name = $team_member->name ?? 'User';
$member_init = strtoupper( substr( $member_name, 0, 1 ) );

$nav_items = array(
    array(
        'view'  => 'client-setup',
        'label' => 'My Setup',
        'icon'  => '<path fill="currentColor" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill="currentColor" fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>',
    ),
    array(
        'view'  => 'profile',
        'label' => 'Profile',
        'icon'  => '<path fill="currentColor" fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>',
    ),
);
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
<?php if ( $sp_favicon ) :
    $fav_type = ( '.svg' === strtolower( substr( (string) parse_url( $sp_favicon, PHP_URL_PATH ), -4 ) ) ) ? ' type="image/svg+xml"' : '';
?>
<link rel="icon"<?php echo $fav_type; ?> href="<?php echo esc_url( $sp_favicon ); ?>">
<link rel="apple-touch-icon" href="<?php echo esc_url( $sp_favicon ); ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?php echo esc_url( SP_PLUGIN_URL . 'assets/css/app.css?v=' . SP_VERSION ); ?>">
<style>:root{--sp-accent:<?php echo esc_attr( $sp_accent ); ?>;--sp-accent-dark:<?php echo esc_attr( $sp_accent_dark ); ?>;--sp-accent-bg:rgba(<?php echo esc_attr( $sp_accent_rgb ); ?>,.18);<?php echo $custom_vars; ?>}
[class*="cookieadmin"],[id*="cookieadmin"],#CybotCookiebotDialog,.CookieConsent{display:none!important}</style>
</head>
<body>
<div class="sp-overlay" id="sp-overlay"></div>
<div class="sp-layout">

    <aside class="sp-sidebar" id="sp-sidebar">
        <div class="sp-brand">
            <?php if ( $sp_icon_url ) : ?>
                <img src="<?php echo esc_url( $sp_icon_url ); ?>" class="sp-brand-logo" alt="<?php echo esc_attr( $sp_brand_name ); ?>">
            <?php elseif ( $sp_initials ) : ?>
                <div class="sp-brand-icon">
                    <span style="font-size:<?php echo strlen( $sp_initials ) > 2 ? '10' : '13'; ?>px;font-weight:800;color:#fff;letter-spacing:-.5px;line-height:1"><?php echo esc_html( strtoupper( $sp_initials ) ); ?></span>
                </div>
            <?php else : ?>
                <div class="sp-brand-icon">
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
                        <line x1="18" y1="14.5" x2="18" y2="7.5"  stroke="white" stroke-width="1.8"/>
                        <line x1="18" y1="21.5" x2="18" y2="28.5" stroke="white" stroke-width="1.8"/>
                        <line x1="14.5" y1="18" x2="7.5"  y2="18" stroke="white" stroke-width="1.8"/>
                        <line x1="21.5" y1="18" x2="28.5" y2="18" stroke="white" stroke-width="1.8"/>
                        <line x1="15.5" y1="15.5" x2="10" y2="10" stroke="white" stroke-width="1.8"/>
                        <line x1="20.5" y1="15.5" x2="26" y2="10" stroke="white" stroke-width="1.8"/>
                        <line x1="15.5" y1="20.5" x2="10" y2="26" stroke="white" stroke-width="1.8"/>
                        <line x1="20.5" y1="20.5" x2="26" y2="26" stroke="white" stroke-width="1.8"/>
                    </svg>
                </div>
            <?php endif; ?>
            <span class="sp-brand-name" style="margin-top:2px"><?php echo esc_html( $sp_brand_name ); ?></span>
        </div>

        <nav class="sp-nav">
            <?php foreach ( $nav_items as $item ) :
                $is_active = $view === $item['view'] ? ' active' : '';
                $href = esc_url( home_url( '/sp-app/?view=' . $item['view'] ) );
            ?>
            <a href="<?php echo $href; ?>" class="sp-nav-item<?php echo $is_active; ?>">
                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <?php echo $item['icon']; ?>
                </svg>
                <?php echo esc_html( $item['label'] ); ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <div class="sp-sidebar-footer">
            <div class="sp-user">
                <div class="sp-avatar"><?php echo esc_html( $member_init ); ?></div>
                <div class="sp-user-info">
                    <div class="sp-user-name"><?php echo esc_html( $member_name ); ?></div>
                    <div class="sp-user-role">Jurisdiction</div>
                </div>
                <a href="<?php echo $logout; ?>" class="sp-logout" title="Sign out">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                        <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </a>
            </div>
        </div>
    </aside>

    <main class="sp-main">
        <div class="sp-mobile-bar">
            <button class="sp-menu-toggle" id="sp-menu-toggle" aria-label="Open menu">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
            <span style="font-size:15px;font-weight:700;color:#1e293b"><?php echo esc_html( $view === 'profile' ? 'Profile' : 'My Setup' ); ?></span>
        </div>
        <?php if ( $view === 'profile' && isset( $_GET['must_change_pin'] ) ) : ?>
        <div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:14px 18px;border-radius:6px;margin-bottom:20px;font-size:.88rem;color:#92400e;">
            <strong>Action required:</strong> Please set a permanent PIN before continuing. Enter your temporary PIN in the "Current PIN" field below.
        </div>
        <?php endif; ?>
        <?php if ( $view_file ) require $view_file; ?>
    </main>

</div>
<?php wp_footer(); ?>
</body>
</html>
