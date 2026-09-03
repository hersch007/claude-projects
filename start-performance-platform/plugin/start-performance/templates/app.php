<?php
if ( ! defined( 'ABSPATH' ) ) exit;

header( 'Cache-Control: no-cache, no-store, must-revalidate' );
header( 'Pragma: no-cache' );
header( 'Expires: 0' );

$view    = sanitize_key( isset( $_GET['view'] ) ? $_GET['view'] : 'dashboard' );
$saved   = isset( $_GET['saved'] );
$allowed = apply_filters( 'sp_allowed_views', array( 'dashboard', 'contacts', 'companies', 'leads', 'tasks', 'activity_report', 'settings', 'addons', 'team', 'import', 'sp-core-upsell', 'profile' ) );
if ( ! in_array( $view, $allowed ) ) $view = 'dashboard';

$view_file  = sp_get_view_file( $view );

// Gate: redirect to dashboard if member lacks access to the core this view belongs to
if ( function_exists( 'sp_get_view_core_map' ) && ! sp_is_super_admin() ) {
    $view_core_map = sp_get_view_core_map();
    if ( isset( $view_core_map[ $view ] ) && ! sp_member_has_core_access( $view_core_map[ $view ] ) ) {
        // Member can SEE this core (as a locked nav teaser) but not open it — send them
        // to that core's upsell screen rather than a blank dashboard bounce.
        wp_redirect( home_url( '/sp-app/?view=sp-core-upsell&core=' . urlencode( $view_core_map[ $view ] ) ) ); exit;
    }
}

$team_member = sp_get_current_team_member();
$is_admin   = sp_is_admin_member();
$logout     = esc_url( home_url( '/sp-app/?sp_logout=1' ) );
$is_super    = sp_is_super_admin();
$is_jur      = $team_member && ! $is_admin && ! $is_super && $team_member->role === 'user';

// Jurisdiction onboarding fires FIRST — even learners who are also WTS contacts
// stay in the onboard flow until they're approved, then fall through below.
if ( $team_member && ! $is_admin && ! $is_super ) {
    $wts_onboard_status = get_option( 'sp_wts_member_status_' . $team_member->id, '' );
    if ( in_array( $wts_onboard_status, array( 'invited', 'pending', 'inactive' ), true ) ) {
        require SP_PLUGIN_DIR . 'templates/onboard.php'; exit;
    }
}

// Jurisdiction contacts (role='user'): force PIN change on first login after approval
if ( $is_jur ) {
    $wts_force_pin = get_option( 'sp_wts_force_pin_' . $team_member->id, '' );
    if ( $wts_force_pin && $view === 'profile' && isset( $_GET['saved'] ) ) {
        delete_option( 'sp_wts_force_pin_' . $team_member->id );
        $wts_force_pin = '';
    }
    if ( $wts_force_pin && $view !== 'profile' ) {
        wp_redirect( home_url( '/sp-app/?view=profile&must_change_pin=1' ) ); exit;
    }
}

// Learner shell — role='learner' members see only the Knowledge/Training views.
if ( $team_member && ! $is_admin && ! $is_super && $team_member->role === 'learner' ) {
    require SP_PLUGIN_DIR . 'templates/learner.php'; exit;
}

// Super admin status always takes precedence in the displayed identity — even if
// this session is also logged in as a regular team member, the sidebar should make
// it unmistakable that elevated (vendor) access is active right now.
if ( $is_super ) {
    $member_name = 'Super Admin';
    $member_role = 'Super Admin';
} else {
    $member_name = $team_member ? $team_member->name : 'User';
    $member_role = $is_jur ? 'Jurisdiction' : ( $team_member ? ucfirst( $team_member->role ) : '' );
}
$member_init = strtoupper( substr( $member_name, 0, 1 ) );

// Reminder badge count for current user
$reminder_count = 0;
if ( $team_member && function_exists( 'sp_get_reminder_count' ) ) {
    $reminder_count = sp_get_reminder_count( $team_member->id );
}

// Core middle items — addons inject into their section via sp_nav_items filter
// Addons should use 'section_id' to identify which section they belong to
$nav_middle = apply_filters( 'sp_nav_items', array(
    array( 'view' => 'dashboard', 'label' => 'Dashboard', 'icon' => '<rect x="3" y="3" width="7" height="7" rx="1.5" fill="currentColor"/><rect x="14" y="3" width="7" height="7" rx="1.5" fill="currentColor"/><rect x="3" y="14" width="7" height="7" rx="1.5" fill="currentColor"/><rect x="14" y="14" width="7" height="7" rx="1.5" fill="currentColor"/>', 'badge' => $reminder_count ),

    // Divider separating Dashboard from the product-core sections below.
    array( 'divider' => true ),

    array( 'section' => true, 'section_id' => 'intelligence-core','label' => sp_section_label( 'intelligence-core','Intelligence Core' ), 'hero' => true ),
    array( 'section' => true, 'section_id' => 'sales-core',       'label' => sp_section_label( 'sales-core',       'Sales Core'        ) ),
    array( 'section' => true, 'section_id' => 'service-core',     'label' => sp_section_label( 'service-core',     'Service Core'      ) ),
    array( 'section' => true, 'section_id' => 'operations-core',  'label' => sp_section_label( 'operations-core',  'Operations Core'   ) ),
    array( 'section' => true, 'section_id' => 'knowledge-core',   'label' => sp_section_label( 'knowledge-core',   'Knowledge Core'    ) ),
    array( 'section' => true, 'section_id' => 'chat-core',        'label' => sp_section_label( 'chat-core',        'Chat Core'         ) ),

    // Core System (base CRM) sits at the bottom, just above the Admin block.
    array( 'section' => true, 'section_id' => 'core-system',      'label' => sp_section_label( 'core-system',      'Core System'       ) ),
    array( 'view' => 'contacts',  'label' => 'Contacts',  'icon' => '<path fill="currentColor" d="M12 12a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 1114 0H5z"/>' ),
    array( 'view' => 'companies', 'label' => 'Companies', 'icon' => '<path fill="currentColor" d="M4 21V7a2 2 0 012-2h5V3h2v2h5a2 2 0 012 2v14H4zm2-2h4v-3H6v3zm6 0h4v-3h-4v3zm-6-5h4V9H6v5zm6 0h4V9h-4v5z"/>' ),
    array( 'view' => 'tasks',     'label' => 'Tasks',     'icon' => '<path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>', 'badge' => ( function_exists( 'sp_my_open_task_count' ) && $team_member ) ? sp_my_open_task_count( $team_member->id ) : 0 ),

    // Horizontal divider after Core System, separating it from the Admin block below.
    // admin_only so it never dangles for members who don't see the Admin section.
    array( 'divider' => true, 'admin_only' => true ),
) );

// Load hidden nav preference early so upsell injection can account for it
$hidden_nav_early = get_option( 'sp_hidden_nav_items', array() );
if ( ! is_array( $hidden_nav_early ) ) $hidden_nav_early = array();

// Inject locked upsell teasers + gate by member core access, in one pass. A core-slot
// section shows a locked "upsell" teaser (in place of its real items) whenever it has
// no accessible items — either its addon is inactive OR the current member isn't granted
// that core. So a member SEES every core they lack (a "see but can't open" upsell hook)
// while the real view stays blocked by the access gate above. core-system/dashboard are
// always accessible (see sp_member_has_core_access), so only core slots are affected.
if ( function_exists( 'sp_get_core_slots' ) ) {
    $core_slots = sp_get_core_slots();
    $sp_has_access = function( $sid ) {
        return ! $sid || ! function_exists( 'sp_member_has_core_access' ) || sp_member_has_core_access( $sid );
    };

    // First pass: a section counts as "has items" only if it has a visible item the
    // current member can actually access.
    $sections_with_items = array();
    $cur_sid = null;
    foreach ( $nav_middle as $item ) {
        if ( ! empty( $item['section'] ) ) {
            $cur_sid = ! empty( $item['section_id'] ) ? $item['section_id'] : null;
        } elseif ( $cur_sid && ! empty( $item['view'] ) && ! in_array( $item['view'], $hidden_nav_early ) && $sp_has_access( $cur_sid ) ) {
            $sections_with_items[] = $cur_sid;
        }
    }

    // Second pass: keep section headers; drop the real items of any section the member
    // can't access; inject a locked teaser after each core-slot header with no accessible
    // items. A restricted section that isn't a core slot (nothing to upsell) is hidden.
    $nav_middle_injected = array();
    $drop_items_for = null;
    foreach ( $nav_middle as $item ) {
        if ( ! empty( $item['section'] ) ) {
            $sid        = ! empty( $item['section_id'] ) ? $item['section_id'] : '';
            $has_access = $sp_has_access( $sid );
            $is_slot    = $sid && isset( $core_slots[ $sid ] );
            $empty_slot = $is_slot && ! in_array( $sid, $sections_with_items );

            // Upsell teasers are for the BUYER: only admins see a locked "unlock this" item.
            // For non-admin members, a core they can't use is hidden entirely — a focused menu,
            // not marketing clutter aimed at someone who can't act on it.
            if ( $empty_slot && ! $is_admin ) { $drop_items_for = $sid; continue; }

            if ( ! $has_access && ! $is_slot ) { $drop_items_for = $sid; continue; }
            $drop_items_for = $has_access ? null : $sid;
            $nav_middle_injected[] = $item;

            if ( $empty_slot ) {
                $slot = $core_slots[ $sid ];
                $nav_middle_injected[] = array(
                    'view'   => 'sp-core-upsell',
                    'core'   => $sid,
                    'label'  => $slot['label'],
                    'icon'   => $slot['icon'],
                    'locked' => true,
                );
            }
        } elseif ( ! empty( $item['divider'] ) ) {
            $nav_middle_injected[] = $item;
        } else {
            if ( $drop_items_for !== null ) continue;
            $nav_middle_injected[] = $item;
        }
    }
    $nav_middle = $nav_middle_injected;
}

// Bottom items — always pinned last, admin-only
$nav_bottom = array(
    array( 'section' => true, 'label' => 'Admin', 'view' => '', 'icon' => '', 'admin_only' => true ),
    array( 'view' => 'activity_report', 'label' => 'Activity Report', 'icon' => '<path fill="currentColor" d="M9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4zm2 2H5V5h14v14zm0-16H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2z"/>', 'admin_only' => true ),
    array( 'view' => 'import',   'label' => 'Import',   'icon' => '<path fill="currentColor" d="M12 2a1 1 0 011 1v10.586l2.293-2.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L11 13.586V3a1 1 0 011-1zM3 17a1 1 0 011 1v1h16v-1a1 1 0 112 0v2a1 1 0 01-1 1H3a1 1 0 01-1-1v-2a1 1 0 011-1z"/>', 'admin_only' => true ),
    array( 'view' => 'team',     'label' => 'Team',     'icon' => '<path fill="currentColor" d="M16 11a4 4 0 10-8 0 4 4 0 008 0zm-4 6c-4.418 0-8 1.79-8 4v1h16v-1c0-2.21-3.582-4-8-4zm6-10a3 3 0 015.83 1c0 1.657-1.343 3-3 3a3 3 0 01-2.83-2zm3 7c1.657 0 3 .895 3 2v.5h-3.5c.323-.607.5-1.28.5-2v-.5zm-16 0a3 3 0 01-3-3A3 3 0 016 7.83V8a3 3 0 01-3 3zm0 2v.5c0 .72.177 1.393.5 2H2v-.5c0-1.105 1.343-2 3-2z"/>', 'admin_only' => true ),
    array( 'view' => 'addons',   'label' => 'Add-ons',  'icon' => '<path fill="currentColor" d="M11 3a1 1 0 10-2 0v1H7a2 2 0 00-2 2v2H4a1 1 0 100 2h1v6H4a1 1 0 100 2h1v2a2 2 0 002 2h10a2 2 0 002-2v-2h1a1 1 0 100-2h-1V10h1a1 1 0 100-2h-1V6a2 2 0 00-2-2h-2V3a1 1 0 10-2 0v1h-2V3z"/>', 'admin_only' => true ),
    array( 'view' => 'settings', 'label' => 'Settings', 'icon' => '<path fill="currentColor" fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>', 'admin_only' => true ),
);

$nav_raw    = array_merge( $nav_middle, $nav_bottom );
$hidden_nav = get_option( 'sp_hidden_nav_items', array() );
if ( ! is_array( $hidden_nav ) ) $hidden_nav = array();

// Remove hidden views; also remove section headers that have no visible items beneath them
$nav            = array();
$pending_section = null;
foreach ( $nav_raw as $item ) {
    if ( ! empty( $item['section'] ) ) {
        $pending_section = $item;
        continue;
    }
    if ( ! empty( $item['view'] ) && in_array( $item['view'], $hidden_nav ) ) continue;
    if ( $pending_section !== null ) {
        $nav[]           = $pending_section;
        $pending_section = null;
    }
    $nav[] = $item;
}

// Jurisdiction contacts (role='user'): hide core-system section and its children
if ( $is_jur ) {
    $jur_nav = array(); $skip_core_sys = false;
    foreach ( $nav as $n ) {
        if ( ! empty( $n['section'] ) ) {
            $skip_core_sys = ( ( $n['section_id'] ?? '' ) === 'core-system' );
            if ( ! $skip_core_sys ) $jur_nav[] = $n;
        } elseif ( ! $skip_core_sys ) {
            $jur_nav[] = $n;
        }
    }
    $nav = $jur_nav;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<?php
$sp_app_title = get_option( 'sp_platform_name', 'Start Performance' );
$sp_favicon   = get_option( 'sp_favicon_url', '' );
if ( ! $sp_favicon ) { $sp_favicon = get_option( 'sp_brand_icon_url', '' ); }
?>
<title><?php echo esc_html( $sp_app_title ); ?></title>
<?php if ( $sp_favicon ) :
	$sp_fav_type = ( '.svg' === strtolower( substr( (string) parse_url( $sp_favicon, PHP_URL_PATH ), -4 ) ) ) ? ' type="image/svg+xml"' : '';
?>
<link rel="icon"<?php echo $sp_fav_type; ?> href="<?php echo esc_url( $sp_favicon ); ?>">
<link rel="apple-touch-icon" href="<?php echo esc_url( $sp_favicon ); ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?php echo esc_url( SP_PLUGIN_URL . 'assets/css/app.css?v=' . SP_VERSION ); ?>">
<?php
$sp_accent         = get_option( 'sp_accent_color',      '#CC1F1F' );
$sp_accent_dark    = sp_darken_hex( $sp_accent, 0.15 );
$sp_accent_rgb     = sp_hex_to_rgb( $sp_accent );
$sp_sidebar_bg     = get_option( 'sp_sidebar_bg',        '' );
$sp_nav_color      = get_option( 'sp_nav_color',         '' );
$sp_nav_hover_color= get_option( 'sp_nav_hover_color',   '' );
$sp_nav_hover_bg   = get_option( 'sp_nav_hover_bg',      '' );
$custom_vars = '';
if ( $sp_sidebar_bg )      $custom_vars .= '--sp-sidebar-bg:' . esc_attr( $sp_sidebar_bg ) . ';';
if ( $sp_nav_color )       $custom_vars .= '--sp-nav-color:' . esc_attr( $sp_nav_color ) . ';';
if ( $sp_nav_hover_color ) $custom_vars .= '--sp-nav-hover-color:' . esc_attr( $sp_nav_hover_color ) . ';';
if ( $sp_nav_hover_bg )    $custom_vars .= '--sp-nav-hover-bg:' . esc_attr( $sp_nav_hover_bg ) . ';';
?>
<style>:root{--sp-accent:<?php echo esc_attr( $sp_accent ); ?>;--sp-accent-dark:<?php echo esc_attr( $sp_accent_dark ); ?>;--sp-accent-bg:rgba(<?php echo esc_attr( $sp_accent_rgb ); ?>,.18);<?php echo $custom_vars; ?>}
.sp-nav-item-locked{opacity:.4;font-style:italic}.sp-nav-item-locked:hover{opacity:.65}
[class*="cookieadmin"],[id*="cookieadmin"],#CybotCookiebotDialog,.CookieConsent{display:none!important}</style>
</head>
<body>
<div class="sp-overlay" id="sp-overlay"></div>
<div class="sp-layout">

    <aside class="sp-sidebar" id="sp-sidebar">
        <div class="sp-brand">
            <?php
            $sp_icon_url   = get_option( 'sp_brand_icon_url', '' );
            $sp_initials   = get_option( 'sp_brand_initials', '' );
            $sp_brand_name = get_option( 'sp_platform_name', 'Start Performance' );
            if ( $sp_icon_url ) : ?>
                <img src="<?php echo esc_url( $sp_icon_url ); ?>" class="sp-brand-logo" alt="<?php echo esc_attr( $sp_brand_name ); ?>">
            <?php else : ?>
            <div class="sp-brand-icon">
                <?php if ( $sp_initials ) : ?>
                    <span style="font-size:<?php echo strlen( $sp_initials ) > 2 ? '10' : '13'; ?>px;font-weight:800;color:#fff;letter-spacing:-.5px;line-height:1"><?php echo esc_html( strtoupper( $sp_initials ) ); ?></span>
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
            <?php endif; ?>
            <span class="sp-brand-name" style="margin-top:2px"><?php echo esc_html( $sp_brand_name ); ?></span>
        </div>

        <button id="sp-search-trigger" class="sp-search-trigger" title="Search" aria-label="Search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        </button>

        <!-- Search overlay -->
        <div id="sp-search-overlay" class="sp-search-overlay" style="display:none;" aria-modal="true" role="dialog">
            <div class="sp-search-modal">
                <div class="sp-search-modal-inner">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" width="16" height="16" style="flex-shrink:0"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    <input type="text" id="sp-global-search" class="sp-search-input" placeholder="Search contacts, companies, leads…" autocomplete="off">
                    <kbd class="sp-search-esc">Esc</kbd>
                </div>
                <div id="sp-search-results" class="sp-search-results" style="display:none"></div>
            </div>
        </div>

        <?php
        // Find which section contains the active view (for auto-open)
        $active_section_key  = null;
        $current_section_key = null;
        foreach ( $nav as $item ) {
            if ( ! empty( $item['section'] ) ) {
                $current_section_key = ! empty( $item['section_id'] ) ? $item['section_id'] : sanitize_title( $item['label'] );
            } elseif ( ! empty( $item['view'] ) && $item['view'] === $view ) {
                $active_section_key = $current_section_key;
            }
        }

        // Section-header icons, keyed by section key. Core slots supply their own icon
        // (rendered stroked, so section headers read as outline vs. the filled item rows);
        // the two non-slot sections get a hand-set icon.
        $sp_sec_icons = array();
        if ( function_exists( 'sp_get_core_slots' ) ) {
            foreach ( sp_get_core_slots() as $sid => $slot ) {
                if ( ! empty( $slot['icon'] ) ) $sp_sec_icons[ $sid ] = $slot['icon'];
            }
        }
        $sp_sec_icons['core-system'] = '<circle cx="9" cy="7" r="3.2"/><path d="M3.5 20v-1.5a4 4 0 014-4h3a4 4 0 014 4V20"/><path d="M16 4.2a3.4 3.4 0 010 6.3"/><path d="M20.5 20v-1.5a4 4 0 00-2.7-3.8"/>';
        $sp_sec_icons['admin']       = '<path d="M12 3l7 3v5c0 4.4-3 7.4-7 8.9-4-1.5-7-4.5-7-8.9V6l7-3z"/>';
        // Core-slot section ids — an ACTIVE one (collapsible, not locked) gets brightened so
        // owned modules pop above the structural sections and the dimmed locked upsells.
        $sp_core_slot_ids = function_exists( 'sp_get_core_slots' ) ? array_keys( sp_get_core_slots() ) : array();
        ?>
        <nav class="sp-nav" data-active-section="<?php echo esc_attr( $active_section_key ); ?>">
            <?php
            // Pre-pass: find sections where every child item is locked
            $all_locked_sections = array();
            $cur_sec = null; $sec_all_locked = true;
            foreach ( $nav as $_item ) {
                if ( ! empty( $_item['section'] ) ) {
                    if ( $cur_sec !== null ) $all_locked_sections[ $cur_sec ] = $sec_all_locked;
                    $cur_sec = ! empty( $_item['section_id'] ) ? $_item['section_id'] : sanitize_title( $_item['label'] );
                    $sec_all_locked = true;
                } elseif ( ! empty( $_item['view'] ) && $cur_sec !== null ) {
                    if ( empty( $_item['locked'] ) ) $sec_all_locked = false;
                }
            }
            if ( $cur_sec !== null ) $all_locked_sections[ $cur_sec ] = $sec_all_locked;

            $in_group = false;
            $suppress_upsell_for = null;
            foreach ( $nav as $item ) {
                if ( ! empty( $item['admin_only'] ) && ! $is_admin ) continue;
                if ( ! empty( $item['divider'] ) ) {
                    if ( $in_group ) { echo '</div></div>'; $in_group = false; }
                    echo '<hr class="sp-nav-divider">';
                    continue;
                }
                if ( ! empty( $item['section'] ) ) {
                    if ( $in_group ) { echo '</div></div>'; $in_group = false; }
                    $sk = ! empty( $item['section_id'] ) ? $item['section_id'] : sanitize_title( $item['label'] );

                    // Section icon (shared by the collapsible header and the promoted upsell link).
                    $sec_icon_svg = '';
                    if ( isset( $sp_sec_icons[ $sk ] ) ) {
                        $sec_icon_raw = str_replace( ' fill="currentColor"', '', $sp_sec_icons[ $sk ] );
                        $sec_icon_svg = '<svg class="sp-nav-sec-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" width="16" height="16" aria-hidden="true">' . $sec_icon_raw . '</svg>';
                    }

                    // A core with no accessible items (inactive / not granted to this member) is
                    // promoted to a single clickable "marketing" menu item at the CORE level — the
                    // section header itself links to the upsell — instead of a collapsible header
                    // plus a redundant locked child.
                    if ( ! empty( $all_locked_sections[ $sk ] ) ) {
                        $up_href   = home_url( '/sp-app/?view=sp-core-upsell&core=' . urlencode( $sk ) );
                        $up_active = ( $view === 'sp-core-upsell' && isset( $_GET['core'] ) && $_GET['core'] === $sk ) ? ' active' : '';
                        // Standalone link — NOT wrapped in .sp-nav-group, so the collapse JS never
                        // tries to find a (nonexistent) items container inside it.
                        echo '<a href="' . esc_url( $up_href ) . '" class="sp-nav-section-upsell' . $up_active . '">';
                        echo '<span class="sp-nav-sec-label">' . $sec_icon_svg . '<span>' . esc_html( $item['label'] ) . '</span></span>';
                        echo '<svg class="sp-nav-lock" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="11" height="11" style="flex-shrink:0"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>';
                        echo '</a>';
                        $suppress_upsell_for = $sk;
                        continue;
                    }

                    $is_hero  = ! empty( $item['hero'] );
                    // Hero (Intelligence Core) shows the filled red highlight + accent bar ONLY when
                    // it's the section you're actually in — otherwise it read as permanently "active"
                    // and competed with the real active-item indicator. The AI chip + auto-open stay
                    // on always so it's still visually featured as the AI-powered draw.
                    $hero_active = $is_hero && ( $sk === $active_section_key );
                    $hero_cls = $hero_active ? ' sp-nav-hero' : '';
                    $hero_att = $is_hero ? ' data-nav-default-open="1"' : '';
                    // Owned core sections get the "pop" brightening unless the hero highlight is showing
                    // (so Intelligence Core still pops like the other cores when it isn't the active one).
                    $active_core_cls = ( ! $hero_active && in_array( $sk, $sp_core_slot_ids, true ) ) ? ' sp-nav-core-active' : '';
                    echo '<div class="sp-nav-group' . $hero_cls . '" data-section="' . esc_attr( $sk ) . '"' . $hero_att . '>';
                    echo '<button type="button" class="sp-nav-section-toggle' . $active_core_cls . '" data-section="' . esc_attr( $sk ) . '">';
                    echo '<span class="sp-nav-sec-label">' . $sec_icon_svg . '<span>' . esc_html( $item['label'] ) . '</span></span>';
                    if ( $is_hero ) echo '<span class="sp-nav-ai-chip">AI</span>';
                    echo '<svg class="sp-nav-chevron" viewBox="0 0 24 24" fill="none" width="12" height="12"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                    echo '</button>';
                    echo '<div class="sp-nav-group-items">';
                    $in_group = true;
                } else {
                    // Skip the locked upsell child already promoted to its section header above.
                    if ( ! empty( $item['locked'] ) && ! empty( $item['core'] ) && $suppress_upsell_for === $item['core'] ) {
                        continue;
                    }
                    // External URL item — opens in a new tab (e.g. AMP Login).
                    if ( ! empty( $item['url'] ) ) {
                        $ext_icon   = ! empty( $item['icon'] ) ? $item['icon'] : '';
                        $ext_target = ! empty( $item['target'] ) ? ' target="' . esc_attr( $item['target'] ) . '" rel="noopener noreferrer"' : '';
                        $ext_style  = ! empty( $item['item_style'] ) ? ' style="' . esc_attr( $item['item_style'] ) . '"' : '';
                        echo '<a href="' . esc_url( $item['url'] ) . '" class="sp-nav-item"' . $ext_target . $ext_style . '>';
                        echo '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
                        if ( strpos( $ext_icon, '<path' ) !== false || strpos( $ext_icon, '<rect' ) !== false ) { echo $ext_icon; }
                        echo '</svg>';
                        echo esc_html( $item['label'] );
                        echo '</a>';
                        continue;
                    }
                    $icon = ! empty( $item['icon'] ) ? $item['icon'] : '';
                    // Build href — locked items include a ?core= param
                    $href = ! empty( $item['core'] )
                        ? home_url( '/sp-app/?view=' . $item['view'] . '&core=' . urlencode( $item['core'] ) )
                        : home_url( '/sp-app/?view=' . $item['view'] );
                    // Active check — locked items also match on core param
                    $is_active = ( ! empty( $item['view'] ) && $item['view'] === $view
                        && ( empty( $item['core'] ) || ( isset( $_GET['core'] ) && $_GET['core'] === $item['core'] ) )
                    ) ? ' active' : '';
                    $locked_class = ! empty( $item['locked'] ) ? ' sp-nav-item-locked' : '';
                    echo '<a href="' . esc_url( $href ) . '" class="sp-nav-item' . $is_active . $locked_class . '">';
                    echo '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
                    if ( strpos( $icon, '<path' ) !== false || strpos( $icon, '<rect' ) !== false ) {
                        echo $icon;
                    } else {
                        echo '<path fill="currentColor" d="' . esc_attr( $icon ) . '"/>';
                    }
                    echo '</svg>';
                    echo esc_html( $item['label'] );
                    if ( ! empty( $item['locked'] ) ) {
                        echo '<svg class="sp-nav-lock" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="11" height="11" style="margin-left:auto;opacity:1;flex-shrink:0;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>';
                    }
                    if ( ! empty( $item['addon'] ) ) { echo '<span class="sp-nav-addon">+</span>'; }
                    if ( ! empty( $item['badge'] ) ) { echo '<span class="sp-nav-badge">' . (int) $item['badge'] . '</span>'; }
                    echo '</a>';
                }
            }
            if ( $in_group ) { echo '</div></div>'; }
            ?>
        </nav>

        <div class="sp-sidebar-footer">
            <div class="sp-user">
                <?php if ( $team_member ) : ?>
                <a href="<?php echo esc_url( home_url( '/sp-app/?view=profile' ) ); ?>" title="My Profile" style="text-decoration:none;flex-shrink:0;">
                    <div class="sp-avatar" style="cursor:pointer;" title="My Profile"><?php echo esc_html( $member_init ); ?></div>
                </a>
                <?php else : ?>
                <div class="sp-avatar"><?php echo esc_html( $member_init ); ?></div>
                <?php endif; ?>
                <div class="sp-user-info">
                    <div class="sp-user-name"><?php echo esc_html( $member_name ); ?></div>
                    <div class="sp-user-role"><?php echo esc_html( $member_role ); ?></div>
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
            <span style="font-size:15px;font-weight:700;color:#1e293b"><?php echo esc_html( ucfirst( $view ) ); ?></span>
        </div>
        <?php if ( $saved ) : ?>
            <div class="sp-toast">Saved successfully.</div>
        <?php endif; ?>
        <?php if ( $is_jur && $view === 'profile' && isset( $_GET['must_change_pin'] ) ) : ?>
        <div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:14px 18px;border-radius:6px;margin-bottom:20px;font-size:.88rem;color:#92400e;">
            <strong>Action required:</strong> Please set a permanent PIN before continuing. Enter your temporary PIN in the "Current PIN" field below.
        </div>
        <?php endif; ?>
        <?php if ( $view_file ) require $view_file; ?>
    </main>

</div>

<?php if ( ! $is_jur ) : ?>
<!-- Quick-add FAB -->
<div class="sp-fab-wrap" id="sp-fab-wrap">
    <div class="sp-fab-menu" id="sp-fab-menu">
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=contacts&action=new' ) ); ?>" class="sp-fab-item">
            <span class="sp-fab-item-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M12 12a4 4 0 100-8 4 4 0 000 8z"/><path d="M20 21a8 8 0 10-16 0"/></svg>
            </span>
            <span class="sp-fab-item-label">Contact</span>
        </a>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=leads&action=new' ) ); ?>" class="sp-fab-item">
            <span class="sp-fab-item-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
            </span>
            <span class="sp-fab-item-label">Lead</span>
        </a>
        <button type="button" class="sp-fab-item" id="sp-fab-task-btn">
            <span class="sp-fab-item-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
            </span>
            <span class="sp-fab-item-label">Task</span>
        </button>
    </div>
    <button type="button" class="sp-fab-btn" id="sp-fab-btn" aria-label="Quick add">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="20" height="20"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    </button>
</div>
<?php endif; ?>

<!-- Quick-task modal -->
<div class="sp-modal-overlay" id="sp-task-modal" style="display:none">
    <div class="sp-modal">
        <div class="sp-modal-header">
            <h3>Quick Add Task</h3>
            <button type="button" class="sp-modal-close" id="sp-task-modal-close">×</button>
        </div>
        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="sp-modal-body">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="task">
            <input type="hidden" name="sp_id" value="0">
            <input type="hidden" name="record_type" value="general">
            <input type="hidden" name="record_id" value="0">
            <div class="sp-field">
                <label>Task</label>
                <input type="text" name="title" placeholder="What needs to be done?" required autofocus>
            </div>
            <div class="sp-form-row">
                <div class="sp-field">
                    <label>Due Date</label>
                    <input type="date" name="due_date">
                </div>
                <div class="sp-field">
                    <label>Assign To</label>
                    <select name="assigned_to">
                        <option value="0">— Anyone —</option>
                        <?php
                        global $wpdb;
                        $qt = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sp_team WHERE status='active' ORDER BY name" );
                        foreach ( $qt as $m ) echo '<option value="' . esc_attr($m->id) . '">' . esc_html($m->name) . '</option>';
                        ?>
                    </select>
                </div>
            </div>
            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary">Add Task</button>
                <button type="button" class="sp-btn sp-btn-ghost" id="sp-task-modal-cancel">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo esc_url( SP_PLUGIN_URL . 'assets/js/app.js?v=' . SP_VERSION ); ?>"></script>
<?php do_action( 'sp_app_footer' ); ?>
</body>
</html>
