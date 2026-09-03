<?php
if ( ! defined( 'ABSPATH' ) ) exit;

header( 'Cache-Control: no-cache, no-store, must-revalidate' );
header( 'Pragma: no-cache' );
header( 'Expires: 0' );

$learner_views = array( 'kb-training', 'kb-training-course', 'knowledge', 'knowledge-article', 'kb-resources' );
$view = sanitize_key( isset( $_GET['view'] ) ? $_GET['view'] : 'kb-training' );
if ( ! in_array( $view, $learner_views ) ) $view = 'kb-training';
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
if ( $sp_sidebar_bg )      $custom_vars .= '--sp-sidebar-bg:'       . esc_attr( $sp_sidebar_bg )      . ';';
if ( $sp_nav_color )       $custom_vars .= '--sp-nav-color:'        . esc_attr( $sp_nav_color )       . ';';
if ( $sp_nav_hover_color ) $custom_vars .= '--sp-nav-hover-color:'  . esc_attr( $sp_nav_hover_color ) . ';';
if ( $sp_nav_hover_bg )    $custom_vars .= '--sp-nav-hover-bg:'     . esc_attr( $sp_nav_hover_bg )    . ';';

$member_name = $team_member ? $team_member->name : 'User';
$member_init = strtoupper( substr( $member_name, 0, 1 ) );

$nav_items = array(
    array(
        'view'  => 'kb-training',
        'label' => 'Training',
        'icon'  => '<path fill="currentColor" d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>',
    ),
    array(
        'view'  => 'knowledge',
        'label' => 'Knowledge Base',
        'icon'  => '<path fill="currentColor" d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>',
    ),
    array(
        'view'  => 'kb-resources',
        'label' => 'Resources',
        'icon'  => '<path fill="currentColor" d="M7 3a1 1 0 000 2h6a1 1 0 000-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/>',
    ),
);

// Active nav item: training detail and course pages both highlight Training
$active_nav = $view;
if ( $view === 'kb-training-course' ) $active_nav = 'kb-training';
if ( $view === 'knowledge-article'  ) $active_nav = 'knowledge';
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
<style>:root{--sp-accent:<?php echo esc_attr( $sp_accent ); ?>;--sp-accent-dark:<?php echo esc_attr( $sp_accent_dark ); ?>;--sp-accent-bg:rgba(<?php echo esc_attr( $sp_accent_rgb ); ?>,.18);<?php echo $custom_vars; ?>}</style>
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
                $is_active = $active_nav === $item['view'] ? ' active' : '';
                $href = esc_url( home_url( '/sp-app/?view=' . $item['view'] ) );
            ?>
            <a href="<?php echo $href; ?>" class="sp-nav-item<?php echo $is_active; ?>">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
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
                    <div class="sp-user-role">Learner</div>
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
            <span style="font-size:15px;font-weight:700;color:#1e293b"><?php echo esc_html( ucfirst( str_replace( array('kb-', '-'), array('', ' '), $view ) ) ); ?></span>
        </div>
        <?php if ( $view_file ) require $view_file; ?>
    </main>

</div>
<?php wp_footer(); ?>
</body>
</html>
