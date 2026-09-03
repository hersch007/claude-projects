<?php
/*
 * Plugin Name: Start Performance
 * Description: Start Performance Platform — core
 * Version:     2.5.49
 * Author:      Richard Brashear / Start Performance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SP_VERSION',    '2.5.49' );
define( 'SP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ── Upgrade / migration ────────────────────────────────────────────────────────

add_action( 'plugins_loaded', 'sp_maybe_migrate' );

// ── From name for all wp_mail() calls ────────────────────────────────────────
add_filter( 'wp_mail_from_name', function( $name ) {
    return get_option( 'sp_platform_name', 'Start Performance' );
} );

function sp_maybe_migrate() {
    global $wpdb;
    // Mark existing installs as setup-complete so the wizard never triggers
    if ( ! get_option( 'sp_setup_complete' ) ) {
        $has_team = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_team" );
        if ( $has_team ) update_option( 'sp_setup_complete', 1 );
    }
    // Add core_access column to sp_team if missing
    $cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}sp_team" );
    if ( ! in_array( 'core_access', $cols ) ) {
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}sp_team ADD COLUMN core_access text NOT NULL DEFAULT '' AFTER status" );
    }
    // Add terms_accepted column to sp_team if missing
    if ( ! in_array( 'terms_accepted', $cols ) ) {
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}sp_team ADD COLUMN terms_accepted tinyint(1) NOT NULL DEFAULT 0 AFTER core_access" );
    }
}

// ── Activation ────────────────────────────────────────────────────────────────

register_activation_hook( __FILE__, 'sp_activate' );
register_deactivation_hook( __FILE__, 'sp_deactivate' );

function sp_activate() {
    sp_create_tables();
    do_action( 'sp_activate' );
    flush_rewrite_rules();
    if ( get_option( 'sp_daily_digest_enabled', 1 ) ) {
        sp_schedule_daily_digest();
    }
}

function sp_deactivate() {
    wp_clear_scheduled_hook( 'sp_daily_digest' );
    flush_rewrite_rules();
}

add_action( 'sp_daily_digest', 'sp_send_daily_digest' );

// Schedules the daily digest at 8:00 AM site time (idempotent). Shared by activation,
// the Settings → Email toggle, and a self-heal on init so the cron exists wherever the
// digest is enabled — including sites where it was never scheduled.
function sp_schedule_daily_digest() {
    if ( wp_next_scheduled( 'sp_daily_digest' ) ) return;
    $site_tz = get_option( 'timezone_string' ) ? get_option( 'timezone_string' ) : 'UTC';
    try {
        $tz   = new DateTimeZone( $site_tz );
        $next = new DateTime( 'tomorrow 08:00:00', $tz );
        wp_schedule_event( $next->getTimestamp(), 'daily', 'sp_daily_digest' );
    } catch ( Exception $e ) {
        wp_schedule_event( time() + 3600, 'daily', 'sp_daily_digest' );
    }
}

// Self-heal: keep the cron in sync with the toggle on every load (cheap check).
add_action( 'init', 'sp_maybe_schedule_daily_digest' );
function sp_maybe_schedule_daily_digest() {
    if ( get_option( 'sp_daily_digest_enabled', 1 ) && ! wp_next_scheduled( 'sp_daily_digest' ) ) {
        sp_schedule_daily_digest();
    }
}

function sp_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_contacts (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  first_name varchar(100) NOT NULL DEFAULT '',
  last_name varchar(100) NOT NULL DEFAULT '',
  email varchar(200) NOT NULL DEFAULT '',
  phone varchar(50) NOT NULL DEFAULT '',
  company_id bigint(20) unsigned NOT NULL DEFAULT 0,
  source varchar(100) NOT NULL DEFAULT '',
  status varchar(50) NOT NULL DEFAULT 'active',
  notes text,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY email (email(100)),
  KEY status (status)
) $charset;" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_companies (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL DEFAULT '',
  industry varchar(100) NOT NULL DEFAULT '',
  website varchar(255) NOT NULL DEFAULT '',
  phone varchar(50) NOT NULL DEFAULT '',
  address text,
  notes text,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY name (name(100))
) $charset;" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_leads (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  contact_id bigint(20) unsigned NOT NULL DEFAULT 0,
  company_id bigint(20) unsigned NOT NULL DEFAULT 0,
  source varchar(100) NOT NULL DEFAULT '',
  status varchar(50) NOT NULL DEFAULT 'new',
  score tinyint(3) unsigned NOT NULL DEFAULT 0,
  notes text,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY status (status),
  KEY contact_id (contact_id)
) $charset;" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_team (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(100) NOT NULL DEFAULT '',
  email varchar(200) NOT NULL DEFAULT '',
  role varchar(20) NOT NULL DEFAULT 'agent',
  pin varchar(255) NOT NULL DEFAULT '',
  status varchar(20) NOT NULL DEFAULT 'active',
  core_access text NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  last_login_at datetime DEFAULT NULL,
  PRIMARY KEY  (id),
  KEY role (role),
  KEY status (status)
) $charset;" );
    // Migration: add last_login_at to existing installs
    $cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}sp_team LIKE 'last_login_at'" );
    if ( empty( $cols ) ) {
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}sp_team ADD COLUMN last_login_at datetime DEFAULT NULL AFTER created_at" );
    }

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_notes (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  record_type varchar(20) NOT NULL DEFAULT '',
  record_id bigint(20) unsigned NOT NULL DEFAULT 0,
  content text NOT NULL,
  reminder_at datetime DEFAULT NULL,
  reminder_sent tinyint(1) NOT NULL DEFAULT 0,
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY record_type (record_type),
  KEY record_id (record_id),
  KEY reminder_at (reminder_at)
) $charset;" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_tasks (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  record_type varchar(20) NOT NULL DEFAULT '',
  record_id bigint(20) unsigned NOT NULL DEFAULT 0,
  title varchar(255) NOT NULL DEFAULT '',
  assigned_to bigint(20) unsigned NOT NULL DEFAULT 0,
  due_date date DEFAULT NULL,
  status varchar(20) NOT NULL DEFAULT 'open',
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY record_type (record_type),
  KEY record_id (record_id),
  KEY status (status),
  KEY assigned_to (assigned_to)
) $charset;" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_attachments (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  record_type varchar(20) NOT NULL DEFAULT '',
  record_id bigint(20) unsigned NOT NULL DEFAULT 0,
  filename varchar(255) NOT NULL DEFAULT '',
  filepath varchar(500) NOT NULL DEFAULT '',
  fileurl varchar(500) NOT NULL DEFAULT '',
  filesize bigint(20) unsigned NOT NULL DEFAULT 0,
  filetype varchar(100) NOT NULL DEFAULT '',
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY record_type (record_type),
  KEY record_id (record_id)
) $charset;" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_activity (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  record_type varchar(20) NOT NULL DEFAULT '',
  record_id bigint(20) unsigned NOT NULL DEFAULT 0,
  action varchar(50) NOT NULL DEFAULT '',
  detail text,
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY record_type (record_type),
  KEY record_id (record_id),
  KEY created_at (created_at)
) $charset;" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_email_log (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  record_type varchar(20) NOT NULL DEFAULT '',
  record_id bigint(20) unsigned NOT NULL DEFAULT 0,
  direction varchar(10) NOT NULL DEFAULT 'sent',
  subject varchar(255) NOT NULL DEFAULT '',
  body text,
  logged_by bigint(20) unsigned NOT NULL DEFAULT 0,
  logged_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY record (record_type, record_id),
  KEY logged_at (logged_at)
) $charset;" );
}

// ── Team migration ─────────────────────────────────────────────────────────────
// On first load after upgrade, seed sp_team with an Admin using the legacy PIN.

add_action( 'init', 'sp_maybe_migrate_team', 5 );

function sp_maybe_migrate_team() {
    global $wpdb;
    $table = $wpdb->prefix . 'sp_team';
    if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) ) return;
    $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
    if ( $count > 0 ) return;
    $existing_pin = get_option( 'sp_pin', '1234' );
    $wpdb->insert( $table, array(
        'name'       => 'Admin',
        'email'      => '',
        'role'       => 'admin',
        'pin'        => wp_hash_password( $existing_pin ),
        'status'     => 'active',
        'created_at' => current_time( 'mysql' ),
    ) );
}

// ── View registry ──────────────────────────────────────────────────────────────

$sp_view_registry = array();

function sp_register_view( $slug, $file_path ) {
    global $sp_view_registry;
    $sp_view_registry[ $slug ] = $file_path;
}

function sp_get_view_file( $slug ) {
    global $sp_view_registry;
    if ( isset( $sp_view_registry[ $slug ] ) && file_exists( $sp_view_registry[ $slug ] ) ) {
        return $sp_view_registry[ $slug ];
    }
    $core = SP_PLUGIN_DIR . 'templates/views/' . $slug . '.php';
    return file_exists( $core ) ? $core : '';
}

// ── Addon registry ─────────────────────────────────────────────────────────────

$sp_addon_registry = array();

function sp_register_addon( $id, $data ) {
    global $sp_addon_registry;
    $sp_addon_registry[ $id ] = array_merge( array(
        'name'         => '',
        'version'      => '',
        'description'  => '',
        'settings_url' => '',
        'icon'         => '',
        'plugin_file'  => '',
        'core_slot'    => '',
    ), $data );
}

function sp_get_addons() {
    global $sp_addon_registry;
    return $sp_addon_registry;
}

// True only if the given addon's plugin actually ran sp_register_addon() this
// request — i.e. it's genuinely active right now. Use this instead of "does the
// table exist" checks for gating dashboard elements: a deactivated plugin's
// tables stick around, so a table-existence check alone can't tell active from
// merely-installed-once, and neither can sp_hidden_nav_items (a separate,
// manually-set toggle unrelated to plugin activation state).
function sp_is_addon_active( $addon_id ) {
    $addons = sp_get_addons();
    return isset( $addons[ $addon_id ] );
}

// Super-admin toggle: is a registered module enabled for display?
// Defaults to enabled (1) so existing sites are unaffected before first save.
function sp_is_module_enabled( $addon_id ) {
    return (bool) get_option( 'sp_module_enabled_' . $addon_id, 1 );
}

// True only if the addon is both active (plugin running) AND enabled by super admin.
// Use this for nav items and dashboard cards.
function sp_is_module_visible( $addon_id ) {
    return sp_is_addon_active( $addon_id ) && sp_is_module_enabled( $addon_id );
}

// Installed-but-inactive SP addon plugins. These never call sp_register_addon()
// (their code doesn't run while inactive), so this reads WP's own plugin list
// directly instead. Used by the Add-ons page to offer an in-app "Activate" button
// so reactivating a Core doesn't require WP admin access.
function sp_get_inactive_addon_plugins() {
    if ( ! function_exists( 'get_plugins' ) ) require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $inactive = array();
    foreach ( get_plugins() as $file => $data ) {
        if ( strpos( $file, 'start-performance-' ) !== 0 ) continue; // our family only, excludes core itself
        if ( is_plugin_active( $file ) ) continue;
        $inactive[ $file ] = $data;
    }
    return $inactive;
}

// ── Core slot registry ─────────────────────────────────────────────────────────

$sp_core_slot_registry = array();

function sp_register_core_slot( $id, $data ) {
    global $sp_core_slot_registry;
    $sp_core_slot_registry[ $id ] = array_merge( array(
        'label'       => '',
        'description' => '',
        'icon'        => '',
        'features'    => array(),
        'learn_more'  => '',
    ), $data );
}

function sp_get_core_slots() {
    global $sp_core_slot_registry;
    return $sp_core_slot_registry;
}

add_action( 'plugins_loaded', 'sp_register_default_core_slots', 1 );

function sp_register_default_core_slots() {
    sp_register_core_slot( 'intelligence-core', array(
        'label'       => 'Intelligence Core',
        'tagline'     => 'Unlock smarter decisions with powerful insights',
        'description' => 'KPI dashboards, form analytics, and business intelligence that turn your data into real performance gains.',
        'icon'        => '<path fill="currentColor" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
        'features'    => array(
            'KPI Dashboard|Real-time visibility into what\'s working',
            'Form Builder & Analytics|Build smarter forms and instantly understand the results',
            'Business Intelligence Reports|Beautiful, actionable reports at your fingertips',
            'Goal Tracking|Set, monitor, and crush your most important targets',
        ),
    ) );
    sp_register_core_slot( 'sales-core', array(
        'label'       => 'Sales Core',
        'tagline'     => 'Close more deals with less effort',
        'description' => 'Professional proposals, estimates, and contracts — built for speed and linked to your contacts.',
        'icon'        => '<path fill="currentColor" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'features'    => array(
            'Estimates|Create professional quotes in minutes and track acceptance',
            'Proposals|Multi-section proposals with scope, timeline, and pricing',
            'Contracts|Send, track, and record signed contracts in one place',
        ),
    ) );
    sp_register_core_slot( 'service-core', array(
        'label'       => 'Service Core',
        'tagline'     => 'Deliver outstanding service every single time',
        'description' => 'Ticket management, service scheduling, and a customer portal that keeps your clients in the loop.',
        'icon'        => '<path fill="currentColor" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>',
        'features'    => array(
            'Ticket Management|Track every service request from open to resolved',
            'Service Scheduling|Keep your team on time and clients informed',
            'Customer Portal|Give clients real-time visibility into their service status',
            'Customer Calculator|Help clients scope and price their own service needs',
        ),
    ) );
    sp_register_core_slot( 'operations-core', array(
        'label'       => 'Operations Core',
        'tagline'     => 'Run a tighter, smarter operation every day',
        'description' => 'Onboarding checklists, workflow automation, and task management that keep your team moving.',
        'icon'        => '<path fill="currentColor" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'features'    => array(
            'Onboarding Checklists|Visual progress tracker tied to every contact and company',
            'Workflow Automation|Build repeatable workflows that create tasks automatically',
            'Workflow Management|See every active workflow and where each one stands',
            'Task Tracking|Keep your whole team on track with linked, due-dated tasks',
        ),
    ) );
    sp_register_core_slot( 'knowledge-core', array(
        'label'       => 'Knowledge Core',
        'tagline'     => 'Build a team that never has to guess',
        'description' => 'Your team\'s single source of truth — SOPs, training, FAQs, and resources all in one place.',
        'icon'        => '<path fill="currentColor" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
        'features'    => array(
            'Knowledge Base|Searchable articles and SOPs your team can actually find',
            'Resource Library|Organize links, files, and docs by category',
            'Training & Testing|Build courses with quizzes and track who\'s completed what',
        ),
    ) );
    sp_register_core_slot( 'chat-core', array(
        'label'       => 'Chat Core',
        'tagline'     => 'Capture leads and support customers in real time',
        'description' => 'Website chat, support intake, and internal team messaging — all inside the platform.',
        'icon'        => '<path fill="currentColor" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>',
        'features'    => array(
            'Website Chat|Capture leads and answer questions before they leave',
            'Support Intake|Turn chat conversations into trackable service tickets',
            'Chat Analytics|See response times, volume, and team performance',
            'Internal Staff Chat|Keep your team connected without leaving the platform',
        ),
    ) );
    // No 'ai-core' slot: AI is a cross-cutting capability, not a standalone/sellable
    // core. It configures under Settings → AI and surfaces in-context (contact
    // summaries, ticket triage) plus Pipeline Insights in Intelligence Core. Keeping
    // it out of the slot registry means it never appears in the nav or the Add-ons
    // upsell — on any site, active or not. See project_ai_not_a_destination.
}

// ── Module visibility filter (strips disabled modules from nav) ───────────────
add_filter( 'sp_nav_items', 'sp_filter_disabled_modules_from_nav', 99 );

function sp_filter_disabled_modules_from_nav( $items ) {
    $addons = sp_get_addons();
    // Build map: core_slot => addon_id
    $slot_map = array();
    foreach ( $addons as $id => $data ) {
        if ( ! empty( $data['core_slot'] ) ) {
            $slot_map[ $data['core_slot'] ] = $id;
        }
    }
    $result       = array();
    $skip_section = false;
    foreach ( $items as $item ) {
        if ( ! empty( $item['section'] ) ) {
            $sid = $item['section_id'] ?? '';
            if ( isset( $slot_map[ $sid ] ) ) {
                $skip_section = ! sp_is_module_enabled( $slot_map[ $sid ] );
            } else {
                $skip_section = false;
            }
            if ( $skip_section ) continue;
        } elseif ( $skip_section ) {
            continue;
        }
        $result[] = $item;
    }
    return $result;
}

// ── Vendor (Super Admin) auth helpers ──────────────────────────────────────────

function sp_set_vendor_cookie() {
    $token = hash_hmac( 'sha256', 'sp_vendor', wp_salt( 'auth' ) );
    setcookie( 'sp_vendor_auth', $token, 0, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
}

function sp_clear_vendor_cookie() {
    setcookie( 'sp_vendor_auth', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
}

function sp_is_super_admin() {
    if ( empty( $_COOKIE['sp_vendor_auth'] ) ) return false;
    $expected = hash_hmac( 'sha256', 'sp_vendor', wp_salt( 'auth' ) );
    return hash_equals( $expected, $_COOKIE['sp_vendor_auth'] );
}

// ── Team auth helpers ──────────────────────────────────────────────────────────

function sp_set_team_cookie( $member_id ) {
    $token = $member_id . '|' . hash_hmac( 'sha256', (string) $member_id, wp_salt( 'auth' ) );
    setcookie( 'sp_team_auth', $token, 0, '/', COOKIE_DOMAIN, is_ssl(), true );
}

function sp_clear_team_cookie() {
    setcookie( 'sp_team_auth', '', time() - 3600, '/', COOKIE_DOMAIN, is_ssl(), true );
}

function sp_section_label( $section_id, $default ) {
    $custom = get_option( 'sp_section_label_' . $section_id, '' );
    return $custom !== '' ? $custom : $default;
}

// Shared dark-card chrome for all AI-generated content cards (Intelligence Core's
// Smart Summary, Dashboard's AI Summary, Pipeline Insights, Contact Summary, Ticket
// Triage) so they look consistent. $action_html is raw HTML (e.g. a Refresh button),
// right-aligned in the header. Call sp_ai_card_end() to close what this opens.
function sp_ai_card_start( $label, $action_html = '' ) {
    ?>
    <div class="sp-ai-dark-card" style="background:linear-gradient(135deg,#0f172a,#1e293b);border-radius:12px;padding:20px 24px;margin-bottom:20px;position:relative;overflow:hidden;">
        <div style="position:absolute;top:0;right:0;width:200px;height:200px;background:radial-gradient(circle,rgba(99,102,241,.15),transparent 70%);pointer-events:none;"></div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;position:relative;z-index:1;">
            <div style="background:rgba(99,102,241,.2);border-radius:8px;padding:6px 8px;display:flex;">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16">
                    <path fill="#818cf8" d="M12 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm6.364 2.636a1 1 0 010 1.414l-.707.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM21 11a1 1 0 110 2h-1a1 1 0 110-2h1zM5.636 4.636a1 1 0 011.414 0l.707.707A1 1 0 116.343 6.757l-.707-.707a1 1 0 010-1.414zM4 11a1 1 0 110 2H3a1 1 0 110-2h1zm14.364 7.364a1 1 0 01-1.414 0l-.707-.707a1 1 0 011.414-1.414l.707.707a1 1 0 010 1.414zM12 18a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.636 19.364a1 1 0 010-1.414l.707-.707a1 1 0 111.414 1.414l-.707.707a1 1 0 01-1.414 0zM12 8a4 4 0 100 8 4 4 0 000-8z"/>
                </svg>
            </div>
            <span style="font-size:.75rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#818cf8;"><?php echo esc_html( $label ); ?></span>
            <?php echo $action_html; ?>
        </div>
        <div style="position:relative;z-index:1;">
    <?php
}

function sp_ai_card_end() {
    echo '</div></div>';
}

// Light "well" for markdown/HTML AI output nested inside sp_ai_card_start()/_end(),
// since that content assumes a light background (dark text colors) — keeps existing
// AI-output rendering untouched while sitting inside the shared dark card chrome.
function sp_ai_content_well_style() {
    return 'background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 16px;';
}

function sp_hex_to_rgb( $hex ) {
    $hex = ltrim( $hex, '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec( substr( $hex, 0, 2 ) );
    $g = hexdec( substr( $hex, 2, 2 ) );
    $b = hexdec( substr( $hex, 4, 2 ) );
    return "$r,$g,$b";
}

function sp_darken_hex( $hex, $amount ) {
    $hex = ltrim( $hex, '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = max( 0, hexdec( substr( $hex, 0, 2 ) ) - (int) round( hexdec( substr( $hex, 0, 2 ) ) * $amount ) );
    $g = max( 0, hexdec( substr( $hex, 2, 2 ) ) - (int) round( hexdec( substr( $hex, 2, 2 ) ) * $amount ) );
    $b = max( 0, hexdec( substr( $hex, 4, 2 ) ) - (int) round( hexdec( substr( $hex, 4, 2 ) ) * $amount ) );
    return sprintf( '#%02x%02x%02x', $r, $g, $b );
}

function sp_get_current_team_member() {
    static $cache = false;
    if ( $cache !== false ) return $cache;
    if ( empty( $_COOKIE['sp_team_auth'] ) ) { $cache = null; return null; }
    $parts = explode( '|', $_COOKIE['sp_team_auth'], 2 );
    if ( count( $parts ) !== 2 ) { $cache = null; return null; }
    list( $id, $hash ) = $parts;
    $id = (int) $id;
    if ( ! hash_equals( hash_hmac( 'sha256', (string) $id, wp_salt( 'auth' ) ), $hash ) ) {
        $cache = null; return null;
    }
    global $wpdb;
    $cache = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sp_team WHERE id = %d AND status = 'active'", $id
    ) );
    return $cache;
}

// Authenticated to use the app at all: a registered team member OR a super admin.
// Super admins authenticate via the sp_vendor_auth cookie and are NOT team-member
// records, so a bare sp_get_current_team_member() check silently locks them out of
// AJAX handlers, REST endpoints, and exports. Use this instead.
function sp_is_authed() {
    return sp_get_current_team_member() !== null || sp_is_super_admin();
}

function sp_is_admin_member() {
    if ( sp_is_super_admin() ) return true;
    $m = sp_get_current_team_member();
    return $m && $m->role === 'admin';
}

// Returns true if the current member can access a given core section.
// Empty core_access = unrestricted (all cores). Super admin always passes.
// 'core-system' and 'dashboard' are always accessible.
function sp_member_has_core_access( $core_id ) {
    if ( sp_is_super_admin() ) return true;
    if ( in_array( $core_id, array( 'core-system', 'dashboard' ), true ) ) return true;
    $m = sp_get_current_team_member();
    if ( ! $m ) return false;
    $access = json_decode( isset( $m->core_access ) ? $m->core_access : '', true );
    if ( empty( $access ) || ! is_array( $access ) ) return true; // no restriction
    return in_array( $core_id, $access, true );
}

// Returns true if a view slug has been hidden via SP Settings → Navigation
// (sp_hidden_nav_items). Nav rendering already respects this (see app.php);
// addon dashboard-widget callbacks should call this too so a hidden module's
// stat card doesn't keep showing after its nav item disappears.
function sp_is_view_hidden( $view_slug ) {
    $hidden = get_option( 'sp_hidden_nav_items', array() );
    if ( ! is_array( $hidden ) ) return false;
    return in_array( $view_slug, $hidden, true );
}

// Build a view→core_id map from the current nav so view gating doesn't need a hardcoded list.
function sp_get_view_core_map() {
    $nav = apply_filters( 'sp_nav_items', array(
        array( 'view' => 'dashboard' ),
        array( 'section' => true, 'section_id' => 'core-system',       'label' => '' ),
        array( 'view' => 'contacts'  ), array( 'view' => 'companies' ),
        array( 'view' => 'tasks'      ),
        array( 'section' => true, 'section_id' => 'intelligence-core', 'label' => '' ),
        array( 'section' => true, 'section_id' => 'sales-core',        'label' => '' ),
        array( 'section' => true, 'section_id' => 'service-core',      'label' => '' ),
        array( 'section' => true, 'section_id' => 'operations-core',   'label' => '' ),
        array( 'section' => true, 'section_id' => 'knowledge-core',    'label' => '' ),
        array( 'section' => true, 'section_id' => 'chat-core',         'label' => '' ),
    ) );
    $map = array();
    $cur = null;
    foreach ( $nav as $item ) {
        if ( ! empty( $item['section'] ) ) {
            $cur = $item['section_id'] ?? null;
        } elseif ( ! empty( $item['view'] ) && $cur ) {
            $map[ $item['view'] ] = $cur;
        }
    }
    return $map;
}

function sp_log_activity( $record_type, $record_id, $action, $detail = '' ) {
    global $wpdb;
    $member = sp_get_current_team_member();
    $wpdb->insert( $wpdb->prefix . 'sp_activity', array(
        'record_type' => $record_type,
        'record_id'   => (int) $record_id,
        'action'      => sanitize_key( $action ),
        'detail'      => $detail,
        'created_by'  => $member ? (int) $member->id : 0,
        'created_at'  => current_time( 'mysql' ),
    ) );
}

function sp_format_filesize( $bytes ) {
    if ( $bytes < 1024 ) return $bytes . ' B';
    if ( $bytes < 1048576 ) return round( $bytes / 1024, 1 ) . ' KB';
    return round( $bytes / 1048576, 1 ) . ' MB';
}

function sp_get_reminder_count( $member_id ) {
    global $wpdb;
    $today = date( 'Y-m-d' );
    $now   = current_time( 'mysql' );
    $tasks = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tasks WHERE assigned_to=%d AND status='open' AND due_date IS NOT NULL AND due_date<=%s",
        $member_id, $today
    ) );
    $notes = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}sp_notes WHERE created_by=%d AND reminder_at IS NOT NULL AND reminder_at<=%s AND reminder_sent=0",
        $member_id, $now
    ) );
    return $tasks + $notes;
}

function sp_my_open_task_count( $member_id ) {
    global $wpdb;
    return (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tasks WHERE assigned_to=%d AND status='open'",
        $member_id
    ) );
}

function sp_send_daily_digest() {
    if ( ! get_option( 'sp_daily_digest_enabled', 1 ) ) return;
    global $wpdb;
    $today = date( 'Y-m-d' );
    $now   = current_time( 'mysql' );
    $members = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sp_team WHERE status='active' AND email != ''" );
    if ( ! $members ) return;

    foreach ( $members as $member ) {
        $overdue = $wpdb->get_results( $wpdb->prepare(
            "SELECT tk.title, tk.due_date, tk.record_type, tk.record_id,
                COALESCE( CONCAT(c.first_name,' ',c.last_name), co.name, '' ) AS record_name
             FROM {$wpdb->prefix}sp_tasks tk
             LEFT JOIN {$wpdb->prefix}sp_contacts c ON tk.record_type='contact' AND tk.record_id=c.id
             LEFT JOIN {$wpdb->prefix}sp_companies co ON tk.record_type='company' AND tk.record_id=co.id
             WHERE tk.assigned_to=%d AND tk.status='open' AND tk.due_date IS NOT NULL AND tk.due_date < %s
             ORDER BY tk.due_date ASC",
            $member->id, $today
        ) );

        $due_today = $wpdb->get_results( $wpdb->prepare(
            "SELECT tk.title, tk.record_type, tk.record_id,
                COALESCE( CONCAT(c.first_name,' ',c.last_name), co.name, '' ) AS record_name
             FROM {$wpdb->prefix}sp_tasks tk
             LEFT JOIN {$wpdb->prefix}sp_contacts c ON tk.record_type='contact' AND tk.record_id=c.id
             LEFT JOIN {$wpdb->prefix}sp_companies co ON tk.record_type='company' AND tk.record_id=co.id
             WHERE tk.assigned_to=%d AND tk.status='open' AND tk.due_date=%s
             ORDER BY tk.created_at ASC",
            $member->id, $today
        ) );

        $note_reminders = $wpdb->get_results( $wpdb->prepare(
            "SELECT n.id, n.content, n.record_type, n.record_id,
                COALESCE( CONCAT(c.first_name,' ',c.last_name), co.name, '' ) AS record_name
             FROM {$wpdb->prefix}sp_notes n
             LEFT JOIN {$wpdb->prefix}sp_contacts c ON n.record_type='contact' AND n.record_id=c.id
             LEFT JOIN {$wpdb->prefix}sp_companies co ON n.record_type='company' AND n.record_id=co.id
             WHERE n.created_by=%d AND n.reminder_at IS NOT NULL AND n.reminder_at<=%s AND n.reminder_sent=0",
            $member->id, $now
        ) );

        if ( empty( $overdue ) && empty( $due_today ) && empty( $note_reminders ) ) continue;

        $app = home_url( '/sp-app/' );
        $rec_link = function( $rt, $rid, $tab ) use ( $app ) {
            if ( $rid && in_array( $rt, array( 'contact', 'company', 'lead' ), true ) ) {
                return $app . '?view=' . $rt . 's&action=view&id=' . (int) $rid . '&tab=' . $tab;
            }
            return $app;
        };

        $sections = array();

        if ( ! empty( $overdue ) ) {
            $items = array();
            foreach ( $overdue as $t ) {
                $rn  = trim( $t->record_name );
                $due = $t->due_date ? 'Was due ' . date( 'M j', strtotime( $t->due_date ) ) : '';
                $sub = trim( ( $rn ? $rn . '  ·  ' : '' ) . $due );
                $items[] = array( 'title' => $t->title, 'sub' => $sub, 'link' => $rec_link( $t->record_type, $t->record_id, 'tasks' ) );
            }
            $sections[] = array( 'label' => 'Overdue', 'color' => '#b91c1c', 'bg' => '#fee2e2', 'items' => $items );
        }

        if ( ! empty( $due_today ) ) {
            $items = array();
            foreach ( $due_today as $t ) {
                $items[] = array( 'title' => $t->title, 'sub' => trim( $t->record_name ), 'link' => $rec_link( $t->record_type, $t->record_id, 'tasks' ) );
            }
            $sections[] = array( 'label' => 'Due Today', 'color' => '#b45309', 'bg' => '#fef3c7', 'items' => $items );
        }

        if ( ! empty( $note_reminders ) ) {
            $items = array();
            foreach ( $note_reminders as $n ) {
                $snippet = substr( $n->content, 0, 100 ) . ( strlen( $n->content ) > 100 ? '…' : '' );
                $items[] = array( 'title' => $snippet, 'sub' => trim( $n->record_name ), 'link' => $rec_link( $n->record_type, $n->record_id, 'notes' ) );
            }
            $sections[] = array( 'label' => 'Note Reminders', 'color' => '#4f46e5', 'bg' => '#e0e7ff', 'items' => $items );
            foreach ( $note_reminders as $n ) {
                $wpdb->update( $wpdb->prefix . 'sp_notes', array( 'reminder_sent' => 1 ), array( 'id' => $n->id ) );
            }
        }

        $subject = 'Daily Digest from ' . get_option( 'sp_platform_name', 'Start Performance' );
        $html    = sp_digest_email_html( $member->name, $sections );
        wp_mail( $member->email, $subject, $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
    }
}

// Branded HTML for the daily digest. Auto-uses the site's platform name, accent
// color, and logo, so each client's email is on-brand. Table + inline-style layout
// for email-client compatibility. $sections: array of
// array('label','color','bg','items'=>array(array('title','sub','link'))).
function sp_digest_email_html( $name, $sections ) {
    $platform = get_option( 'sp_platform_name', 'Start Performance' );
    $accent   = get_option( 'sp_accent_color', '#CC1F1F' );
    if ( ! preg_match( '/^#[0-9a-fA-F]{3,6}$/', $accent ) ) $accent = '#CC1F1F';
    $icon     = get_option( 'sp_brand_icon_url', '' );
    if ( ! $icon ) $icon = get_option( 'sp_logo_url', '' );
    $app_url  = home_url( '/sp-app/' );
    $font     = "Arial,Helvetica,sans-serif";

    $body_sections = '';
    foreach ( $sections as $sec ) {
        if ( empty( $sec['items'] ) ) continue;
        $rows = '';
        foreach ( $sec['items'] as $it ) {
            $rows .= '<tr><td style="padding:11px 0;border-bottom:1px solid #eef2f6;">'
                . '<a href="' . esc_url( $it['link'] ) . '" style="color:#0f172a;text-decoration:none;font-family:' . $font . ';font-size:15px;font-weight:600;line-height:1.45;">' . esc_html( $it['title'] ) . '</a>'
                . ( ! empty( $it['sub'] ) ? '<div style="color:#64748b;font-family:' . $font . ';font-size:13px;margin-top:3px;">' . esc_html( $it['sub'] ) . '</div>' : '' )
                . '</td></tr>';
        }
        $body_sections .= '<tr><td style="padding:24px 32px 0;">'
            . '<span style="display:inline-block;background:' . $sec['bg'] . ';color:' . $sec['color'] . ';font-family:' . $font . ';font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;padding:5px 11px;border-radius:6px;">' . esc_html( $sec['label'] ) . ' (' . count( $sec['items'] ) . ')</span>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:8px;">' . $rows . '</table>'
            . '</td></tr>';
    }

    // Logo on a white chip (like the app sidebar) so dark/black logos stay visible on
    // the dark header, with height fixed + width auto so any aspect ratio isn't squished.
    $brand = $icon
        ? '<span style="display:inline-block;background:#ffffff;border-radius:8px;padding:5px 9px;margin-right:12px;vertical-align:middle;line-height:0;"><img src="' . esc_url( $icon ) . '" alt="' . esc_attr( $platform ) . '" height="26" style="height:26px;width:auto;max-width:150px;display:inline-block;vertical-align:middle;"></span>'
        : '';

    return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;padding:0;background:#f1f5f9;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f1f5f9;padding:24px 12px;">'
        . '<tr><td align="center">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,.08);">'
        . '<tr><td style="background:#0f172a;padding:22px 32px;">'
        . '<span style="font-family:' . $font . ';font-size:18px;font-weight:800;color:#ffffff;vertical-align:middle;">' . $brand . esc_html( $platform ) . '</span>'
        . '<div style="height:3px;width:44px;background:' . $accent . ';border-radius:3px;margin-top:13px;"></div>'
        . '</td></tr>'
        . '<tr><td style="padding:26px 32px 0;">'
        . '<div style="font-family:' . $font . ';font-size:20px;font-weight:800;color:#0f172a;">Hi ' . esc_html( $name ) . ',</div>'
        . '<div style="font-family:' . $font . ';font-size:14px;color:#64748b;margin-top:5px;">Here&rsquo;s what needs your attention today.</div>'
        . '</td></tr>'
        . $body_sections
        . '<tr><td style="padding:28px 32px 8px;">'
        . '<a href="' . esc_url( $app_url ) . '" style="display:inline-block;background:' . $accent . ';color:#ffffff;font-family:' . $font . ';font-size:14px;font-weight:700;text-decoration:none;padding:11px 22px;border-radius:8px;">Open ' . esc_html( $platform ) . ' &rarr;</a>'
        . '</td></tr>'
        . '<tr><td style="padding:22px 32px 30px;">'
        . '<div style="border-top:1px solid #eef2f6;padding-top:16px;font-family:' . $font . ';font-size:12px;color:#94a3b8;line-height:1.6;">'
        . esc_html( $platform ) . '<br><a href="' . esc_url( $app_url ) . '" style="color:#94a3b8;text-decoration:underline;">' . esc_html( $app_url ) . '</a>'
        . '</div></td></tr>'
        . '</table></td></tr></table></body></html>';
}

function sp_current_member_can( $cap ) {
    $m = sp_get_current_team_member();
    if ( ! $m ) return false;
    if ( $m->role === 'admin' ) return true;
    $agent_caps = array( 'edit_records' );
    return in_array( $cap, $agent_caps );
}

// ── URL Routing ────────────────────────────────────────────────────────────────

add_action( 'init', 'sp_route', 1 );

function sp_route() {
    $uri     = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
    $wp_path = trim( parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
    if ( $wp_path ) {
        $uri = trim( substr( $uri, strlen( $wp_path ) ), '/' );
    }

    // The SP app is a dynamic, session-authenticated application — it must NEVER be
    // page-cached by the browser, a CDN, or a host cache. Newfold's endurance-page-
    // cache was sending Cache-Control: max-age=7200 on these routes, so browsers
    // served stale/broken copies (the recurring "screens crapping out"). Force
    // no-store for every SP route, before we render and exit. Also mark the response
    // as non-cacheable to speedycache via DONOTCACHEPAGE.
    if ( in_array( $uri, array( 'sp-app', 'sp-login', 'sp-setup' ), true ) ) {
        if ( ! defined( 'DONOTCACHEPAGE' ) ) define( 'DONOTCACHEPAGE', true );
        nocache_headers();
        header( 'Cache-Control: no-cache, no-store, must-revalidate, max-age=0', true );
        header( 'Pragma: no-cache', true );
        header( 'Expires: 0', true );
    }

    // ── /sp-setup/ ─────────────────────────────────────────────────────────────
    if ( $uri === 'sp-setup' ) {
        // If setup is already done, redirect to login
        if ( get_option( 'sp_setup_complete' ) && ! sp_is_super_admin() ) {
            wp_redirect( home_url( '/sp-login/' ) ); exit;
        }
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && ! empty( $_POST['sp_setup_step'] ) ) {
            sp_handle_setup_post(); exit;
        }
        require SP_PLUGIN_DIR . 'templates/setup.php'; exit;
    }

    // ── /sp-login/ ─────────────────────────────────────────────────────────────
    if ( $uri === 'sp-login' ) {

        // Vendor (super admin) login
        if ( ! empty( $_POST['sp_vendor_login'] ) ) {
            $pin    = isset( $_POST['pin'] ) ? trim( $_POST['pin'] ) : '';
            if ( ! defined( 'SP_SUPER_ADMIN_PIN' ) ) {
                wp_redirect( home_url( '/sp-login/?vendor=1&error=nopin' ) ); exit;
            }
            $stored = (string) SP_SUPER_ADMIN_PIN;
            if ( ! $pin || $pin !== $stored ) {
                wp_redirect( home_url( '/sp-login/?vendor=1&error=pin' ) ); exit;
            }
            $admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
            if ( empty( $admins ) ) { wp_redirect( home_url( '/sp-login/?vendor=1&error=nouser' ) ); exit; }
            wp_set_current_user( $admins[0]->ID );
            wp_set_auth_cookie( $admins[0]->ID, true );
            sp_set_vendor_cookie();
            wp_redirect( home_url( '/sp-app/' ) ); exit;
        }

        // PIN reset — send email
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['sp_type'] ) && $_POST['sp_type'] === 'forgot_pin' ) {
            if ( ! wp_verify_nonce( isset( $_POST['sp_reset_nonce'] ) ? $_POST['sp_reset_nonce'] : '', 'sp_pin_reset' ) ) {
                wp_redirect( home_url( '/sp-login/?mode=forgot&error=invalid' ) ); exit;
            }
            global $wpdb;
            $email  = sanitize_email( isset( $_POST['email'] ) ? $_POST['email'] : '' );
            $member = $email ? $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sp_team WHERE email=%s AND status='active' LIMIT 1", $email
            ) ) : null;
            if ( ! $member ) {
                wp_redirect( home_url( '/sp-login/?mode=forgot&error=noemail' ) ); exit;
            }
            $token    = wp_generate_password( 32, false );
            $sp_name  = get_option( 'sp_platform_name', 'Start Performance' );
            set_transient( 'sp_pin_reset_' . $token, array( 'member_id' => $member->id ), HOUR_IN_SECONDS );
            $reset_url = home_url( '/sp-login/?mode=reset&token=' . $token );
            $subject   = 'Reset your ' . $sp_name . ' PIN';
            $body      = "Hi {$member->name},\n\n"
                       . "We received a request to reset your PIN for {$sp_name}.\n\n"
                       . "Click the link below to set a new PIN. This link expires in 1 hour.\n\n"
                       . $reset_url . "\n\n"
                       . "If you didn't request this, you can ignore this email — your PIN won't change.\n\n"
                       . "— The {$sp_name} Team";
            wp_mail( $email, $subject, $body );
            wp_redirect( home_url( '/sp-login/?mode=forgot&sent=1' ) ); exit;
        }

        // PIN reset — set new PIN
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['sp_type'] ) && $_POST['sp_type'] === 'reset_pin' ) {
            if ( ! wp_verify_nonce( isset( $_POST['sp_reset_nonce'] ) ? $_POST['sp_reset_nonce'] : '', 'sp_pin_reset' ) ) {
                wp_redirect( home_url( '/sp-login/?error=invalid' ) ); exit;
            }
            $token      = sanitize_text_field( isset( $_POST['sp_reset_token'] ) ? $_POST['sp_reset_token'] : '' );
            $new_pin    = isset( $_POST['new_pin'] )     ? trim( $_POST['new_pin'] )     : '';
            $confirm    = isset( $_POST['confirm_pin'] ) ? trim( $_POST['confirm_pin'] ) : '';
            $reset_data = $token ? get_transient( 'sp_pin_reset_' . $token ) : false;
            if ( ! $reset_data ) {
                wp_redirect( home_url( '/sp-login/?mode=reset&token=' . urlencode( $token ) . '&error=tokenbad' ) ); exit;
            }
            if ( strlen( $new_pin ) < 4 ) {
                wp_redirect( home_url( '/sp-login/?mode=reset&token=' . urlencode( $token ) . '&error=pinshort' ) ); exit;
            }
            if ( $new_pin !== $confirm ) {
                wp_redirect( home_url( '/sp-login/?mode=reset&token=' . urlencode( $token ) . '&error=pinmatch' ) ); exit;
            }
            global $wpdb;
            $wpdb->update( $wpdb->prefix . 'sp_team', array( 'pin' => wp_hash_password( $new_pin ) ), array( 'id' => (int) $reset_data['member_id'] ) );
            delete_transient( 'sp_pin_reset_' . $token );
            wp_redirect( home_url( '/sp-login/?reset=1' ) ); exit;
        }

        // Regular team login
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && ! empty( $_POST['sp_login_nonce'] ) ) {
            if ( ! wp_verify_nonce( $_POST['sp_login_nonce'], 'sp_login' ) ) {
                wp_redirect( home_url( '/sp-login/?error=invalid' ) ); exit;
            }
            global $wpdb;
            $team_id = (int) ( isset( $_POST['sp_team_id'] ) ? $_POST['sp_team_id'] : 0 );
            $pin     = isset( $_POST['pin'] ) ? trim( $_POST['pin'] ) : '';
            $member  = $team_id ? $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sp_team WHERE id = %d AND status = 'active'", $team_id
            ) ) : null;
            if ( ! $member || ! wp_check_password( $pin, $member->pin ) ) {
                wp_redirect( home_url( '/sp-login/?error=pin' ) ); exit;
            }
            // Terms acceptance — disabled until login screen links are re-enabled
            // To re-enable: uncomment below and restore checkbox + footer links in templates/login.php
            // if ( ! $member->terms_accepted ) {
            //     if ( empty( $_POST['terms_accepted'] ) ) {
            //         wp_redirect( home_url( '/sp-login/?error=terms&sp_team_id=' . $member->id ) ); exit;
            //     }
            //     $wpdb->update( $wpdb->prefix . 'sp_team', array( 'terms_accepted' => 1 ), array( 'id' => $member->id ) );
            // }
            $admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
            if ( empty( $admins ) ) {
                wp_redirect( home_url( '/sp-login/?error=nouser' ) ); exit;
            }
            wp_set_current_user( $admins[0]->ID );
            wp_set_auth_cookie( $admins[0]->ID, true );
            sp_clear_vendor_cookie();
            sp_set_team_cookie( $member->id );
            $wpdb->update( $wpdb->prefix . 'sp_team', array( 'last_login_at' => current_time( 'mysql' ) ), array( 'id' => $member->id ) );
            wp_redirect( home_url( '/sp-app/' ) ); exit;
        }
        // Jurisdiction invite token (WTS onboarding)
        if ( ! empty( $_GET['sp_invite'] ) ) {
            $token  = sanitize_text_field( $_GET['sp_invite'] );
            $inv    = get_transient( 'sp_wts_invite_' . $token );
            if ( ! $inv || empty( $inv['member_id'] ) ) {
                wp_redirect( home_url( '/sp-login/?error=invite_expired' ) ); exit;
            }
            global $wpdb;
            $member = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sp_team WHERE id = %d AND status = 'active'", (int) $inv['member_id']
            ) );
            if ( ! $member ) {
                wp_redirect( home_url( '/sp-login/?error=invite_invalid' ) ); exit;
            }
            delete_transient( 'sp_wts_invite_' . $token );
            $admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
            if ( empty( $admins ) ) { wp_redirect( home_url( '/sp-login/?error=nouser' ) ); exit; }
            wp_set_current_user( $admins[0]->ID );
            wp_set_auth_cookie( $admins[0]->ID, true );
            sp_clear_vendor_cookie();
            sp_set_team_cookie( $member->id );
            wp_redirect( home_url( '/sp-app/' ) ); exit;
        }

        require SP_PLUGIN_DIR . 'templates/login.php'; exit;
    }

    // ── Public AJAX (no auth required) ────────────────────────────────────────
    if ( ! empty( $_GET['sp_public_ajax'] ) && sanitize_key( $_GET['sp_public_ajax'] ) === 'status_report' ) {
        sp_handle_platform_ajax( 'status_report' ); exit;
    }

    // ── /sp-app/ ───────────────────────────────────────────────────────────────
    if ( $uri === 'sp-app' ) {
        // Redirect to setup wizard on fresh installs
        if ( ! get_option( 'sp_setup_complete' ) && ! sp_is_super_admin() ) {
            wp_redirect( home_url( '/sp-setup/' ) ); exit;
        }
        if ( ! is_user_logged_in() ) {
            wp_redirect( home_url( '/sp-login/' ) ); exit;
        }
        if ( isset( $_GET['sp_logout'] ) ) {
            wp_logout();
            sp_clear_team_cookie();
            sp_clear_vendor_cookie();
            wp_redirect( home_url( '/sp-login/' ) ); exit;
        }
        if ( ! empty( $_GET['sp_ajax'] ) ) {
            sp_handle_platform_ajax( sanitize_key( $_GET['sp_ajax'] ) ); exit;
        }
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            sp_handle_post(); exit;
        }
        if ( isset( $_GET['sp_delete'] ) ) {
            sp_handle_delete(); exit;
        }
        if ( isset( $_GET['sp_export'] ) ) {
            sp_handle_export(); exit;
        }
        if ( ! empty( $_POST['sp_type'] ) && $_POST['sp_type'] === 'import' ) {
            sp_handle_import(); exit;
        }
        require SP_PLUGIN_DIR . 'templates/app.php'; exit;
    }
}

// ── Favicon injection ─────────────────────────────────────────────────────────
add_action( 'wp_head', 'sp_inject_favicon', 1 );
add_action( 'login_head', 'sp_inject_favicon', 1 );
function sp_inject_favicon() {
    $url = get_option( 'sp_favicon_url', '' );
    if ( ! $url ) return;
    $url  = esc_url( $url );
    $ext  = strtolower( substr( (string) parse_url( $url, PHP_URL_PATH ), -4 ) );
    $type = ( '.svg' === $ext ) ? ' type="image/svg+xml"' : ( ( '.ico' === $ext ) ? ' type="image/x-icon"' : ' type="image/png"' );
    echo "<link rel=\"icon\"{$type} href=\"{$url}\">\n";
    echo "<link rel=\"shortcut icon\" href=\"{$url}\">\n";
    echo "<link rel=\"apple-touch-icon\" href=\"{$url}\">\n";
}

// ── WordPress stealth layer ────────────────────────────────────────────────────
// Strip every signal that reveals this is a WordPress site.

// 1. Remove generator meta, API links, RSD/WLW, shortlink, and emoji from <head>
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'rest_output_link_wp_head' );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'wp_resource_hints', 2 );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

// 2. Strip X-Pingback header
add_filter( 'wp_headers', function( $headers ) {
    unset( $headers['X-Pingback'] );
    return $headers;
} );

// 3. Remove ?ver= fingerprints from script/style URLs
add_filter( 'script_loader_src', 'sp_strip_ver_query', 15 );
add_filter( 'style_loader_src',  'sp_strip_ver_query', 15 );
function sp_strip_ver_query( $src ) {
    if ( strpos( $src, 'ver=' ) !== false ) {
        $src = remove_query_arg( 'ver', $src );
    }
    return $src;
}

// 4. Hide admin bar on every page (SP shell has its own chrome)
add_filter( 'show_admin_bar', '__return_false' );

// 5. Redirect wp-login.php to SP login for non-super-admins
add_action( 'login_init', function() {
    $action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'login';
    // Allow logout, lostpassword, resetpass, and WP-CLI through
    $wp_only = array( 'logout', 'lostpassword', 'rp', 'resetpass', 'postpass', 'confirmaction' );
    if ( in_array( $action, $wp_only, true ) ) return;
    // Allow super admins to reach wp-login.php directly via ?sp_admin=1
    if ( isset( $_GET['sp_admin'] ) ) return;
    // Allow the login form POST through — the GET param is lost on submit,
    // but WP still requires valid credentials before granting access.
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) return;
    wp_redirect( home_url( '/sp-login/' ) ); exit;
} );

// Preserve ?sp_admin=1 in the WP login form action so the GET param survives submit.
add_filter( 'login_url', function( $url, $redirect, $force_reauth ) {
    if ( isset( $_GET['sp_admin'] ) ) {
        $url = add_query_arg( 'sp_admin', '1', $url );
    }
    return $url;
}, 10, 3 );

// 6. Disable all WordPress feeds
add_action( 'do_feed',       'sp_disable_feed', 1 );
add_action( 'do_feed_rdf',   'sp_disable_feed', 1 );
add_action( 'do_feed_rss',   'sp_disable_feed', 1 );
add_action( 'do_feed_rss2',  'sp_disable_feed', 1 );
add_action( 'do_feed_atom',  'sp_disable_feed', 1 );
function sp_disable_feed() {
    wp_redirect( home_url( '/sp-login/' ), 301 ); exit;
}

// 7. Disable XML-RPC (prevents wordpress.com-style probing)
add_filter( 'xmlrpc_enabled', '__return_false' );

// ── Block wp-admin ─────────────────────────────────────────────────────────────

add_action( 'admin_init', function() {
    if ( wp_doing_ajax() ) return;
    if ( ! is_user_logged_in() ) {
        wp_redirect( home_url( '/sp-login/' ) ); exit;
    }
} );

// ── Redirect all WordPress front-end pages to the SP app ──────────────────────

add_action( 'template_redirect', function() {
    if ( wp_doing_ajax() ) return;
    // Sites with a PUBLIC marketing front-end (e.g. FiberCo's quote funnel + chat)
    // opt out of the app-only redirect by setting the sp_public_frontend option.
    // The SP app at /sp-app stays gated by sp_route's own auth; everything else is
    // a normal public WordPress site. Internal-tool sites leave this unset (app-only).
    if ( get_option( 'sp_public_frontend', '' ) ) return;
    $uri = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
    $parts = explode( '/', $uri );
    $sp_pages = array( 'sp-app', 'sp-login', 'sp-setup', 'terms', 'privacy', 'dpa' );
    foreach ( $parts as $part ) {
        if ( in_array( $part, $sp_pages, true ) ) return;
    }
    if ( is_user_logged_in() ) {
        wp_redirect( home_url( '/sp-app/' ) ); exit;
    } else {
        wp_redirect( home_url( '/sp-login/' ) ); exit;
    }
} );

// ── POST handler ───────────────────────────────────────────────────────────────

function sp_handle_post() {
    if ( empty( $_POST['sp_nonce'] ) || ! wp_verify_nonce( $_POST['sp_nonce'], 'sp_form' ) ) {
        wp_redirect( home_url( '/sp-app/' ) ); exit;
    }
    global $wpdb;
    $type = isset( $_POST['sp_type'] ) ? sanitize_key( $_POST['sp_type'] ) : '';
    $id   = (int) ( isset( $_POST['sp_id'] ) ? $_POST['sp_id'] : 0 );

    if ( $type === 'contact' ) {
        $data = array(
            'first_name' => sanitize_text_field( isset( $_POST['first_name'] ) ? $_POST['first_name'] : '' ),
            'last_name'  => sanitize_text_field( isset( $_POST['last_name'] )  ? $_POST['last_name']  : '' ),
            'email'      => sanitize_email(      isset( $_POST['email'] )      ? $_POST['email']      : '' ),
            'phone'      => sanitize_text_field( isset( $_POST['phone'] )      ? $_POST['phone']      : '' ),
            'company_id' => (int) ( isset( $_POST['company_id'] ) ? $_POST['company_id'] : 0 ),
            'source'     => sanitize_text_field( isset( $_POST['source'] )     ? $_POST['source']     : '' ),
            'status'     => sanitize_key(        isset( $_POST['status'] )     ? $_POST['status']     : 'active' ),
            'notes'      => sanitize_textarea_field( isset( $_POST['notes'] )  ? $_POST['notes']      : '' ),
        );
        if ( $id ) {
            $wpdb->update( $wpdb->prefix . 'sp_contacts', $data, array( 'id' => $id ) );
            sp_log_activity( 'contact', $id, 'updated', 'Contact updated' );
            wp_redirect( home_url( '/sp-app/?view=contacts&action=view&id=' . $id . '&saved=1' ) ); exit;
        } else {
            if ( $data['email'] ) {
                $dupe = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sp_contacts WHERE email = %s", $data['email'] ) );
                if ( $dupe ) {
                    wp_redirect( home_url( '/sp-app/?view=contacts&action=new&duplicate=' . $dupe ) ); exit;
                }
            }
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $wpdb->prefix . 'sp_contacts', $data );
            $new_id = (int) $wpdb->insert_id;
            sp_log_activity( 'contact', $new_id, 'created', 'Contact created' );
            wp_redirect( home_url( '/sp-app/?view=contacts&action=view&id=' . $new_id . '&saved=1' ) ); exit;
        }
    }

    if ( $type === 'company' ) {
        $data = array(
            'name'     => sanitize_text_field(     isset( $_POST['name'] )     ? $_POST['name']     : '' ),
            'industry' => sanitize_text_field(     isset( $_POST['industry'] ) ? $_POST['industry'] : '' ),
            'website'  => esc_url_raw(             isset( $_POST['website'] )  ? $_POST['website']  : '' ),
            'phone'    => sanitize_text_field(     isset( $_POST['phone'] )    ? $_POST['phone']    : '' ),
            'address'  => sanitize_textarea_field( isset( $_POST['address'] )  ? $_POST['address']  : '' ),
            'notes'    => sanitize_textarea_field( isset( $_POST['notes'] )    ? $_POST['notes']    : '' ),
        );
        if ( $id ) {
            $wpdb->update( $wpdb->prefix . 'sp_companies', $data, array( 'id' => $id ) );
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $wpdb->prefix . 'sp_companies', $data );
        }
        wp_redirect( home_url( '/sp-app/?view=companies&saved=1' ) ); exit;
    }

    if ( $type === 'settings' ) {
        if ( sp_is_admin_member() || sp_is_super_admin() ) {

            // Any admin: Welcome note
            if ( isset( $_POST['sp_welcome_note'] ) ) {
                update_option( 'sp_welcome_note', sanitize_textarea_field( $_POST['sp_welcome_note'] ) );
            }

            // Super admin: Module visibility toggles
            if ( sp_is_super_admin() && isset( $_POST['sp_settings_section'] ) && $_POST['sp_settings_section'] === 'modules' ) {
                $addons = sp_get_addons();
                foreach ( array_keys( $addons ) as $addon_id ) {
                    $enabled = ! empty( $_POST['sp_module_enabled'][ $addon_id ] ) ? 1 : 0;
                    update_option( 'sp_module_enabled_' . $addon_id, $enabled );
                }
                wp_redirect( home_url( '/sp-app/?view=settings&saved=modules#section-modules' ) ); exit;
            }

            // Any admin: Daily digest toggle. Gated on the Email-section flag because an
            // unchecked checkbox isn't POSTed — without this gate, saving any OTHER
            // settings form would read it as "off" and silently disable the digest.
            if ( isset( $_POST['sp_settings_section'] ) && $_POST['sp_settings_section'] === 'email' ) {
                update_option( 'sp_wts_onboarding_email', sanitize_email( $_POST['sp_wts_onboarding_email'] ?? '' ) );
                $digest_on = ! empty( $_POST['sp_daily_digest_enabled'] ) ? 1 : 0;
                update_option( 'sp_daily_digest_enabled', $digest_on );
                if ( $digest_on ) {
                    sp_schedule_daily_digest();
                } else {
                    wp_clear_scheduled_hook( 'sp_daily_digest' );
                }
            }

            // Super admin only: Platform branding + section labels
            if ( sp_is_super_admin() ) {
                update_option( 'sp_platform_name',  sanitize_text_field( isset( $_POST['sp_platform_name'] )  ? $_POST['sp_platform_name']  : 'Start Performance' ) );
                update_option( 'sp_logo_url',        esc_url_raw( isset( $_POST['sp_logo_url'] )        ? $_POST['sp_logo_url']        : '' ) );
                update_option( 'sp_brand_icon_url',  esc_url_raw( isset( $_POST['sp_brand_icon_url'] )  ? $_POST['sp_brand_icon_url']  : '' ) );
                update_option( 'sp_favicon_url',     esc_url_raw( isset( $_POST['sp_favicon_url'] )     ? $_POST['sp_favicon_url']     : '' ) );
                $accent = isset( $_POST['sp_accent_color'] ) ? $_POST['sp_accent_color'] : '#CC1F1F';
                update_option( 'sp_accent_color',   preg_match( '/^#[0-9a-fA-F]{3,6}$/', $accent ) ? $accent : '#CC1F1F' );
                update_option( 'sp_brand_initials', sanitize_text_field( isset( $_POST['sp_brand_initials'] ) ? substr( $_POST['sp_brand_initials'], 0, 3 ) : '' ) );

                $hex_color = function( $key, $default ) {
                    $v = isset( $_POST[ $key ] ) ? trim( $_POST[ $key ] ) : $default;
                    return preg_match( '/^#[0-9a-fA-F]{3,6}$/', $v ) ? $v : $default;
                };
                update_option( 'sp_sidebar_bg',      $hex_color( 'sp_sidebar_bg',      '#0f1729' ) );
                update_option( 'sp_nav_color',       $hex_color( 'sp_nav_color',       '#8b9ab4' ) );
                update_option( 'sp_nav_hover_color', $hex_color( 'sp_nav_hover_color', '#ffffff'  ) );
                // hover bg accepts rgba — sanitize carefully
                $hbg = isset( $_POST['sp_nav_hover_bg_text'] ) ? trim( $_POST['sp_nav_hover_bg_text'] ) : '';
                if ( preg_match( '/^(#[0-9a-fA-F]{3,6}|rgba?\([0-9\s,\.]+\))$/', $hbg ) ) {
                    update_option( 'sp_nav_hover_bg', $hbg );
                }

                $allowed_sections = array( 'core-system', 'chat-core', 'knowledge-core', 'sales-core', 'service-core', 'operations-core', 'intelligence-core' );
                $posted_labels    = isset( $_POST['sp_section_label'] ) && is_array( $_POST['sp_section_label'] ) ? $_POST['sp_section_label'] : array();
                foreach ( $allowed_sections as $sid ) {
                    update_option( 'sp_section_label_' . $sid, sanitize_text_field( isset( $posted_labels[ $sid ] ) ? $posted_labels[ $sid ] : '' ) );
                }
            }

            // Admin + super admin: Nav visibility
            // Base must include all section headers so plugin filters can inject items after them
            $all_nav   = apply_filters( 'sp_nav_items', array(
                array( 'view' => 'dashboard'  ),
                array( 'section' => true, 'section_id' => 'core-system',        'label' => 'Core System'        ),
                array( 'view' => 'contacts'   ),
                array( 'view' => 'companies'  ),
                array( 'view' => 'tasks'      ),
                array( 'section' => true, 'section_id' => 'intelligence-core',  'label' => 'Intelligence Core'  ),
                array( 'section' => true, 'section_id' => 'sales-core',         'label' => 'Sales Core'         ),
                array( 'section' => true, 'section_id' => 'service-core',       'label' => 'Service Core'       ),
                array( 'section' => true, 'section_id' => 'operations-core',    'label' => 'Operations Core'    ),
                array( 'section' => true, 'section_id' => 'knowledge-core',     'label' => 'Knowledge Core'     ),
                array( 'section' => true, 'section_id' => 'chat-core',          'label' => 'Chat Core'          ),
            ) );
            $all_views = array();
            foreach ( $all_nav as $item ) {
                if ( ! empty( $item['view'] ) ) $all_views[] = $item['view'];
            }
            $checked = isset( $_POST['sp_nav_visible'] ) && is_array( $_POST['sp_nav_visible'] )
                ? array_map( 'sanitize_key', $_POST['sp_nav_visible'] )
                : array();
            $hidden = array_values( array_diff( $all_views, $checked ) );
            update_option( 'sp_hidden_nav_items', $hidden );
        }
        wp_redirect( home_url( '/sp-app/?view=settings&saved=1' ) ); exit;
    }

    if ( $type === 'team_member' ) {
        if ( ! sp_is_admin_member() ) {
            wp_redirect( home_url( '/sp-app/' ) ); exit;
        }
        $name   = sanitize_text_field( isset( $_POST['name'] )  ? $_POST['name']  : '' );
        $email  = sanitize_email(      isset( $_POST['email'] ) ? $_POST['email'] : '' );
        $role   = sanitize_key(        isset( $_POST['role'] )  ? $_POST['role']  : 'agent' );
        $role   = in_array( $role, array( 'admin', 'agent' ) ) ? $role : 'agent';
        $status = sanitize_key(        isset( $_POST['status'] ) ? $_POST['status'] : 'active' );
        $status = in_array( $status, array( 'active', 'inactive' ) ) ? $status : 'active';
        $new_pin = isset( $_POST['pin'] ) ? trim( $_POST['pin'] ) : '';
        // Core access — empty array saved as '' means unrestricted
        $valid_cores  = function_exists( 'sp_get_core_slots' ) ? array_keys( sp_get_core_slots() ) : array();
        $posted_cores = isset( $_POST['core_access'] ) && is_array( $_POST['core_access'] )
            ? array_values( array_intersect( array_map( 'sanitize_key', $_POST['core_access'] ), $valid_cores ) )
            : array();
        $core_access_val = empty( $posted_cores ) ? '' : wp_json_encode( $posted_cores );
        $data = array( 'name' => $name, 'email' => $email, 'role' => $role, 'status' => $status, 'core_access' => $core_access_val );
        if ( $id ) {
            if ( $new_pin !== '' ) {
                $data['pin'] = wp_hash_password( $new_pin );
            }
            $wpdb->update( $wpdb->prefix . 'sp_team', $data, array( 'id' => $id ) );
        } else {
            if ( $new_pin === '' ) {
                wp_redirect( home_url( '/sp-app/?view=team&error=nopin' ) ); exit;
            }
            $data['pin']        = wp_hash_password( $new_pin );
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $wpdb->prefix . 'sp_team', $data );
        }
        // Welcome email for new members (addons may suppress via filter to send their own)
        if ( ! $id && $email && apply_filters( 'sp_send_member_welcome_email', true, $name, $email, $new_pin ) ) {
            $sp_name      = get_option( 'sp_platform_name', 'Start Performance' );
            $login_url    = home_url( '/sp-login/' );
            $welcome_note = trim( get_option( 'sp_welcome_note', '' ) );
            $subject      = 'You\'re in — here\'s how to access ' . $sp_name;
            $body         = "Hi {$name},\n\n";
            if ( $welcome_note ) {
                $body .= $welcome_note . "\n\n";
            }
            $body .= "You've been added to {$sp_name}, your team's business operating platform.\n\n"
                   . "Here's how to sign in:\n\n"
                   . "Login URL: {$login_url}\n"
                   . "Your PIN: {$new_pin}\n\n"
                   . "At login, select your name from the dropdown and enter your PIN above.\n\n"
                   . "If you have any questions, reach out to your administrator.\n\n"
                   . "— The {$sp_name} Team";
            wp_mail( $email, $subject, $body );
        }
        $saved_member_id = $id ? $id : $wpdb->insert_id;
        do_action( 'sp_after_team_member_save', $saved_member_id );
        wp_redirect( home_url( '/sp-app/?view=team&saved=1' ) ); exit;
    }

    if ( $type === 'change_pin' ) {
        $member = sp_get_current_team_member();
        if ( ! $member ) { wp_redirect( home_url( '/sp-app/' ) ); exit; }
        $current_pin = isset( $_POST['current_pin'] ) ? trim( $_POST['current_pin'] ) : '';
        $new_pin     = isset( $_POST['new_pin'] )     ? trim( $_POST['new_pin'] )     : '';
        $confirm_pin = isset( $_POST['confirm_pin'] ) ? trim( $_POST['confirm_pin'] ) : '';
        if ( ! wp_check_password( $current_pin, $member->pin ) ) {
            wp_redirect( home_url( '/sp-app/?view=profile&error=wrong_pin' ) ); exit;
        }
        if ( $new_pin === '' ) {
            wp_redirect( home_url( '/sp-app/?view=profile&error=pin_empty' ) ); exit;
        }
        if ( $new_pin !== $confirm_pin ) {
            wp_redirect( home_url( '/sp-app/?view=profile&error=pin_mismatch' ) ); exit;
        }
        $wpdb->update( $wpdb->prefix . 'sp_team', array( 'pin' => wp_hash_password( $new_pin ) ), array( 'id' => $member->id ) );
        wp_redirect( home_url( '/sp-app/?view=profile&saved=1' ) ); exit;
    }

    if ( $type === 'change_name' ) {
        $member   = sp_get_current_team_member();
        if ( ! $member ) { wp_redirect( home_url( '/sp-app/' ) ); exit; }
        $new_name = sanitize_text_field( isset( $_POST['new_name'] ) ? $_POST['new_name'] : '' );
        if ( $new_name === '' ) {
            wp_redirect( home_url( '/sp-app/?view=profile&error=name_empty' ) ); exit;
        }
        $wpdb->update( $wpdb->prefix . 'sp_team', array( 'name' => $new_name ), array( 'id' => $member->id ) );
        wp_redirect( home_url( '/sp-app/?view=profile&name_saved=1' ) ); exit;
    }

    if ( $type === 'note' ) {
        $record_type = sanitize_key( isset( $_POST['record_type'] ) ? $_POST['record_type'] : '' );
        $record_id   = (int) ( isset( $_POST['record_id'] ) ? $_POST['record_id'] : 0 );
        $content     = sanitize_textarea_field( isset( $_POST['content'] ) ? $_POST['content'] : '' );
        $reminder    = ( isset( $_POST['reminder_at'] ) && $_POST['reminder_at'] ) ? sanitize_text_field( $_POST['reminder_at'] ) : null;
        $member      = sp_get_current_team_member();
        if ( $content && $record_type && $record_id ) {
            $wpdb->insert( $wpdb->prefix . 'sp_notes', array(
                'record_type' => $record_type,
                'record_id'   => $record_id,
                'content'     => $content,
                'reminder_at' => $reminder,
                'created_by'  => $member ? (int) $member->id : 0,
                'created_at'  => current_time( 'mysql' ),
            ) );
            sp_log_activity( $record_type, $record_id, 'note_added', substr( $content, 0, 120 ) );
        }
        wp_redirect( home_url( '/sp-app/?view=' . $record_type . 's&action=view&id=' . $record_id . '&tab=notes' ) ); exit;
    }

    if ( $type === 'task' ) {
        $record_type = sanitize_key( isset( $_POST['record_type'] ) ? $_POST['record_type'] : '' );
        $record_id   = (int) ( isset( $_POST['record_id'] ) ? $_POST['record_id'] : 0 );
        $task_id     = (int) ( isset( $_POST['task_id'] ) ? $_POST['task_id'] : 0 );
        $member      = sp_get_current_team_member();
        if ( $task_id ) {
            $task = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_tasks WHERE id = %d", $task_id ) );
            if ( $task ) {
                $new_status = $task->status === 'done' ? 'open' : 'done';
                $wpdb->update( $wpdb->prefix . 'sp_tasks', array( 'status' => $new_status ), array( 'id' => $task_id ) );
                sp_log_activity( $task->record_type, $task->record_id, 'task_' . $new_status, $task->title );
                wp_redirect( home_url( '/sp-app/?view=' . $task->record_type . 's&action=view&id=' . $task->record_id . '&tab=tasks' ) ); exit;
            }
        } else {
            $title       = sanitize_text_field( isset( $_POST['title'] ) ? $_POST['title'] : '' );
            $assigned_to = (int) ( isset( $_POST['assigned_to'] ) ? $_POST['assigned_to'] : 0 );
            $due_date    = ( isset( $_POST['due_date'] ) && $_POST['due_date'] ) ? sanitize_text_field( $_POST['due_date'] ) : null;
            if ( $title && $record_type && $record_id ) {
                $wpdb->insert( $wpdb->prefix . 'sp_tasks', array(
                    'record_type' => $record_type,
                    'record_id'   => $record_id,
                    'title'       => $title,
                    'assigned_to' => $assigned_to,
                    'due_date'    => $due_date,
                    'status'      => 'open',
                    'created_by'  => $member ? (int) $member->id : 0,
                    'created_at'  => current_time( 'mysql' ),
                ) );
                sp_log_activity( $record_type, $record_id, 'task_created', $title );
            }
        }
        if ( $record_type === 'general' || ! $record_id ) {
            wp_redirect( home_url( '/sp-app/?view=dashboard&saved=1' ) ); exit;
        }
        wp_redirect( home_url( '/sp-app/?view=' . $record_type . 's&action=view&id=' . $record_id . '&tab=tasks' ) ); exit;
    }

    if ( $type === 'task_manage' ) {
        $task_id = (int) ( isset( $_POST['task_id'] ) ? $_POST['task_id'] : 0 );
        $op      = sanitize_key( isset( $_POST['op'] ) ? $_POST['op'] : '' );
        $member  = sp_get_current_team_member();
        // Preserve worklist filters on redirect
        $rq = array( 'view' => 'tasks' );
        if ( ! empty( $_POST['tm_scope'] ) )    $rq['tm_scope']    = sanitize_key( $_POST['tm_scope'] );
        if ( ! empty( $_POST['tm_assignee'] ) ) $rq['tm_assignee'] = (int) $_POST['tm_assignee'];
        $redirect = home_url( '/sp-app/?' . http_build_query( $rq ) );

        if ( $op === 'create' ) {
            $title       = sanitize_text_field( isset( $_POST['title'] ) ? $_POST['title'] : '' );
            $assigned_to = (int) ( isset( $_POST['assigned_to'] ) ? $_POST['assigned_to'] : 0 );
            $due_date    = ( isset( $_POST['due_date'] ) && $_POST['due_date'] ) ? sanitize_text_field( $_POST['due_date'] ) : null;
            if ( $title ) {
                $wpdb->insert( $wpdb->prefix . 'sp_tasks', array(
                    'record_type' => 'general',
                    'record_id'   => 0,
                    'title'       => $title,
                    'assigned_to' => $assigned_to,
                    'due_date'    => $due_date,
                    'status'      => 'open',
                    'created_by'  => $member ? (int) $member->id : 0,
                    'created_at'  => current_time( 'mysql' ),
                ) );
                sp_log_activity( 'general', 0, 'task_created', $title );
            }
            wp_redirect( $redirect ); exit;
        }

        if ( $task_id ) {
            $task = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_tasks WHERE id = %d", $task_id ) );
            if ( $task ) {
                if ( $op === 'complete' ) {
                    $wpdb->update( $wpdb->prefix . 'sp_tasks', array( 'status' => 'done' ), array( 'id' => $task_id ) );
                    sp_log_activity( $task->record_type, $task->record_id, 'task_done', $task->title );
                } elseif ( $op === 'reopen' ) {
                    $wpdb->update( $wpdb->prefix . 'sp_tasks', array( 'status' => 'open' ), array( 'id' => $task_id ) );
                    sp_log_activity( $task->record_type, $task->record_id, 'task_open', $task->title );
                } elseif ( $op === 'reschedule' ) {
                    $due = ( isset( $_POST['due_date'] ) && $_POST['due_date'] ) ? sanitize_text_field( $_POST['due_date'] ) : null;
                    $wpdb->update( $wpdb->prefix . 'sp_tasks', array( 'due_date' => $due ), array( 'id' => $task_id ) );
                } elseif ( $op === 'reassign' ) {
                    $assignee = (int) ( isset( $_POST['assigned_to'] ) ? $_POST['assigned_to'] : 0 );
                    $wpdb->update( $wpdb->prefix . 'sp_tasks', array( 'assigned_to' => $assignee ), array( 'id' => $task_id ) );
                } elseif ( $op === 'delete' ) {
                    if ( sp_is_admin_member() || ( $member && (int) $task->created_by === (int) $member->id ) ) {
                        $wpdb->delete( $wpdb->prefix . 'sp_tasks', array( 'id' => $task_id ) );
                    }
                }
            }
        }
        wp_redirect( $redirect ); exit;
    }

    if ( $type === 'attachment' ) {
        $record_type = sanitize_key( isset( $_POST['record_type'] ) ? $_POST['record_type'] : '' );
        $record_id   = (int) ( isset( $_POST['record_id'] ) ? $_POST['record_id'] : 0 );
        $member      = sp_get_current_team_member();
        if ( ! empty( $_FILES['sp_file'] ) && $_FILES['sp_file']['error'] === UPLOAD_ERR_OK ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $upload = wp_handle_upload( $_FILES['sp_file'], array( 'test_form' => false ) );
            if ( $upload && ! isset( $upload['error'] ) ) {
                $wpdb->insert( $wpdb->prefix . 'sp_attachments', array(
                    'record_type' => $record_type,
                    'record_id'   => $record_id,
                    'filename'    => sanitize_file_name( $_FILES['sp_file']['name'] ),
                    'filepath'    => $upload['file'],
                    'fileurl'     => $upload['url'],
                    'filesize'    => (int) $_FILES['sp_file']['size'],
                    'filetype'    => $upload['type'],
                    'created_by'  => $member ? (int) $member->id : 0,
                    'created_at'  => current_time( 'mysql' ),
                ) );
                sp_log_activity( $record_type, $record_id, 'file_attached', sanitize_file_name( $_FILES['sp_file']['name'] ) );
            }
        }
        wp_redirect( home_url( '/sp-app/?view=' . $record_type . 's&action=view&id=' . $record_id . '&tab=files' ) ); exit;
    }

    if ( $type === 'bulk' ) {
        $entity = sanitize_key( isset( $_POST['sp_entity'] ) ? $_POST['sp_entity'] : '' );
        $ba     = sanitize_text_field( isset( $_POST['bulk_action'] ) ? $_POST['bulk_action'] : '' );
        $ids    = array_filter( array_map( 'intval', (array) ( isset( $_POST['ids'] ) ? $_POST['ids'] : array() ) ) );

        $tables   = array( 'contacts' => 'sp_contacts', 'companies' => 'sp_companies', 'leads' => 'sp_leads' );
        $singular = array( 'contacts' => 'contact', 'companies' => 'company', 'leads' => 'lead' );
        $statuses = array(
            'contacts'  => array( 'active', 'inactive' ),
            'leads'     => array( 'new', 'contacted', 'qualified', 'unqualified', 'closed' ),
            'companies' => array(),
        );

        if ( ! isset( $tables[ $entity ] ) || empty( $ids ) || ! $ba ) {
            wp_redirect( home_url( '/sp-app/?view=' . ( isset( $tables[ $entity ] ) ? $entity : 'dashboard' ) ) ); exit;
        }
        $table        = $wpdb->prefix . $tables[ $entity ];
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

        if ( $ba === 'delete' ) {
            if ( ! sp_is_admin_member() ) { wp_redirect( home_url( '/sp-app/?view=' . $entity ) ); exit; }
            $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE id IN ($placeholders)", $ids ) );
            foreach ( $ids as $bid ) sp_log_activity( $singular[ $entity ], $bid, 'deleted', 'Bulk deleted' );
            wp_redirect( home_url( '/sp-app/?view=' . $entity . '&saved=1' ) ); exit;
        }

        if ( strpos( $ba, 'status:' ) === 0 ) {
            $new = sanitize_key( substr( $ba, 7 ) );
            if ( ! in_array( $new, $statuses[ $entity ], true ) ) {
                wp_redirect( home_url( '/sp-app/?view=' . $entity ) ); exit;
            }
            $args = array_merge( array( $new ), $ids );
            $wpdb->query( $wpdb->prepare( "UPDATE $table SET status=%s WHERE id IN ($placeholders)", $args ) );
            foreach ( $ids as $bid ) sp_log_activity( $singular[ $entity ], $bid, 'status_change', 'Bulk set to ' . $new );
            wp_redirect( home_url( '/sp-app/?view=' . $entity . '&saved=1' ) ); exit;
        }

        wp_redirect( home_url( '/sp-app/?view=' . $entity ) ); exit;
    }

    if ( $type === 'email_log' ) {
        $record_type = sanitize_key( isset( $_POST['record_type'] ) ? $_POST['record_type'] : '' );
        $record_id   = (int) ( isset( $_POST['record_id'] ) ? $_POST['record_id'] : 0 );
        $member      = sp_get_current_team_member();
        if ( $record_type && $record_id ) {
            $wpdb->insert( $wpdb->prefix . 'sp_email_log', array(
                'record_type' => $record_type,
                'record_id'   => $record_id,
                'direction'   => in_array( isset( $_POST['direction'] ) ? $_POST['direction'] : '', array( 'sent', 'received' ) ) ? $_POST['direction'] : 'sent',
                'subject'     => sanitize_text_field( isset( $_POST['subject'] ) ? $_POST['subject'] : '' ),
                'body'        => sanitize_textarea_field( isset( $_POST['body'] ) ? $_POST['body'] : '' ),
                'logged_by'   => $member ? (int) $member->id : 0,
                'logged_at'   => current_time( 'mysql' ),
            ) );
            sp_log_activity( $record_type, $record_id, 'email_logged', ( $_POST['direction'] === 'received' ? 'Received: ' : 'Sent: ' ) . sanitize_text_field( isset( $_POST['subject'] ) ? $_POST['subject'] : '' ) );
        }
        wp_redirect( home_url( '/sp-app/?view=' . $record_type . 's&action=view&id=' . $record_id . '&tab=emails' ) ); exit;
    }

    do_action( 'sp_post_handler_' . $type, $id );
    wp_redirect( home_url( '/sp-app/' ) ); exit;
}

// ── DELETE handler ─────────────────────────────────────────────────────────────

function sp_handle_delete() {
    $type  = sanitize_key( isset( $_GET['sp_delete'] ) ? $_GET['sp_delete'] : '' );
    $id    = (int) ( isset( $_GET['id'] ) ? $_GET['id'] : 0 );
    $nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';

    if ( ! wp_verify_nonce( $nonce, 'sp_delete_' . $type . '_' . $id ) ) {
        wp_redirect( home_url( '/sp-app/' ) ); exit;
    }

    // Only admins can delete records
    if ( ! sp_is_admin_member() ) {
        wp_redirect( home_url( '/sp-app/' ) ); exit;
    }

    global $wpdb;
    if ( $type === 'contact' ) {
        $wpdb->delete( $wpdb->prefix . 'sp_contacts', array( 'id' => $id ) );
        wp_redirect( home_url( '/sp-app/?view=contacts' ) ); exit;
    }
    if ( $type === 'company' ) {
        $wpdb->delete( $wpdb->prefix . 'sp_companies', array( 'id' => $id ) );
        wp_redirect( home_url( '/sp-app/?view=companies' ) ); exit;
    }
    if ( $type === 'lead' ) {
        $wpdb->delete( $wpdb->prefix . 'sp_leads', array( 'id' => $id ) );
        wp_redirect( home_url( '/sp-app/?view=leads' ) ); exit;
    }
    if ( $type === 'team_member' ) {
        $current = sp_get_current_team_member();
        if ( $current && $current->id !== $id ) {
            $wpdb->delete( $wpdb->prefix . 'sp_team', array( 'id' => $id ) );
        }
        wp_redirect( home_url( '/sp-app/?view=team' ) ); exit;
    }

    if ( $type === 'note' ) {
        $note = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_notes WHERE id = %d", $id ) );
        if ( $note ) {
            $wpdb->delete( $wpdb->prefix . 'sp_notes', array( 'id' => $id ) );
            wp_redirect( home_url( '/sp-app/?view=' . $note->record_type . 's&action=view&id=' . $note->record_id . '&tab=notes' ) ); exit;
        }
    }
    if ( $type === 'task' ) {
        $task = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_tasks WHERE id = %d", $id ) );
        if ( $task ) {
            $wpdb->delete( $wpdb->prefix . 'sp_tasks', array( 'id' => $id ) );
            wp_redirect( home_url( '/sp-app/?view=' . $task->record_type . 's&action=view&id=' . $task->record_id . '&tab=tasks' ) ); exit;
        }
    }
    if ( $type === 'attachment' ) {
        $att = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_attachments WHERE id = %d", $id ) );
        if ( $att ) {
            if ( $att->filepath && file_exists( $att->filepath ) ) {
                @unlink( $att->filepath );
            }
            $wpdb->delete( $wpdb->prefix . 'sp_attachments', array( 'id' => $id ) );
            wp_redirect( home_url( '/sp-app/?view=' . $att->record_type . 's&action=view&id=' . $att->record_id . '&tab=files' ) ); exit;
        }
    }

    if ( $type === 'email_log' ) {
        $em = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_email_log WHERE id = %d", $id ) );
        if ( $em ) {
            $wpdb->delete( $wpdb->prefix . 'sp_email_log', array( 'id' => $id ) );
            wp_redirect( home_url( '/sp-app/?view=' . $em->record_type . 's&action=view&id=' . $em->record_id . '&tab=emails' ) ); exit;
        }
    }

    do_action( 'sp_delete_handler_' . $type, $id );
    wp_redirect( home_url( '/sp-app/' ) ); exit;
}

// ── Export handler ─────────────────────────────────────────────────────────────

function sp_handle_export() {
    if ( ! sp_is_authed() ) {
        wp_redirect( home_url( '/sp-login/' ) ); exit;
    }
    global $wpdb;
    $type = sanitize_key( isset( $_GET['sp_export'] ) ? $_GET['sp_export'] : '' );

    if ( $type === 'contacts' ) {
        $rows    = $wpdb->get_results( "SELECT first_name, last_name, email, phone, source, status, notes, created_at FROM {$wpdb->prefix}sp_contacts ORDER BY created_at DESC", ARRAY_A );
        $headers = array( 'First Name', 'Last Name', 'Email', 'Phone', 'Source', 'Status', 'Notes', 'Created' );
        $file    = 'contacts-' . date( 'Y-m-d' ) . '.csv';
    } elseif ( $type === 'companies' ) {
        $rows    = $wpdb->get_results( "SELECT name, industry, website, phone, address, notes, created_at FROM {$wpdb->prefix}sp_companies ORDER BY created_at DESC", ARRAY_A );
        $headers = array( 'Name', 'Industry', 'Website', 'Phone', 'Address', 'Notes', 'Created' );
        $file    = 'companies-' . date( 'Y-m-d' ) . '.csv';
    } elseif ( $type === 'leads' ) {
        $rows    = $wpdb->get_results( "SELECT COALESCE(CONCAT(c.first_name,' ',c.last_name),'') AS contact_name, c.email AS contact_email, co.name AS company_name, l.source, l.status, l.score, l.notes, l.created_at FROM {$wpdb->prefix}sp_leads l LEFT JOIN {$wpdb->prefix}sp_contacts c ON c.id=l.contact_id LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id=l.company_id ORDER BY l.created_at DESC", ARRAY_A );
        $headers = array( 'Contact Name', 'Contact Email', 'Company', 'Source', 'Status', 'Score', 'Notes', 'Created' );
        $file    = 'leads-' . date( 'Y-m-d' ) . '.csv';
    } elseif ( $type === 'activity' ) {
        $from    = isset( $_GET['from'] )    ? sanitize_text_field( $_GET['from'] )    : date( 'Y-m-d', strtotime( '-30 days' ) );
        $to      = isset( $_GET['to'] )      ? sanitize_text_field( $_GET['to'] )      : date( 'Y-m-d' );
        $member  = isset( $_GET['member'] )  ? (int) $_GET['member']                   : 0;
        $rtype   = isset( $_GET['rtype'] )   ? sanitize_key( $_GET['rtype'] )          : '';
        $raction = isset( $_GET['raction'] ) ? sanitize_key( $_GET['raction'] )        : '';
        $where  = "WHERE a.created_at >= '{$from} 00:00:00' AND a.created_at <= '{$to} 23:59:59'";
        if ( $member  ) $where .= " AND a.created_by = $member";
        if ( $rtype   ) $where .= " AND a.record_type = '" . esc_sql( $rtype ) . "'";
        if ( $raction ) $where .= " AND a.action = '" . esc_sql( $raction ) . "'";
        $rows    = $wpdb->get_results( "SELECT a.created_at, t.name AS member_name, a.action, a.record_type, a.record_id, a.detail FROM {$wpdb->prefix}sp_activity a LEFT JOIN {$wpdb->prefix}sp_team t ON t.id=a.created_by $where ORDER BY a.created_at DESC", ARRAY_A );
        $headers = array( 'Date', 'Team Member', 'Action', 'Record Type', 'Record ID', 'Detail' );
        $file    = 'activity-' . $from . '-' . $to . '.csv';
    } else {
        wp_redirect( home_url( '/sp-app/' ) ); exit;
    }

    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . $file . '"' );
    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, $headers );
    foreach ( $rows as $row ) {
        fputcsv( $out, array_values( $row ) );
    }
    fclose( $out );
}

// ── Platform AJAX ──────────────────────────────────────────────────────────────

function sp_handle_platform_ajax( $action ) {
    header( 'Content-Type: application/json; charset=utf-8' );

    // ── Public actions (no auth required) ─────────────────────────────────────
    if ( $action === 'status_report' ) {
        // Read versions directly from plugin constants (set at plugins_loaded)
        $v_core  = defined( 'SP_VERSION' )           ? SP_VERSION           : '—';
        $v_sales = defined( 'SP_SALES_VERSION' )     ? SP_SALES_VERSION     : '—';
        $v_intel = defined( 'SP_INTEL_VERSION' )     ? SP_INTEL_VERSION     : ( defined( 'SP_AI_VERSION' ) ? SP_AI_VERSION : '—' );
        $v_svc   = defined( 'SP_TICKETS_VERSION' )   ? SP_TICKETS_VERSION   : '—';
        $v_ops   = defined( 'SP_OPS_VERSION' )       ? SP_OPS_VERSION       : '—';
        $v_know  = defined( 'SP_KNOWLEDGE_VERSION' ) ? SP_KNOWLEDGE_VERSION : '—';

        $today = date( 'Y-m-d' );
        $rows  = array(
            // Core System
            array( 'section' => 'Core System',       'item' => 'Contacts',       'version' => $v_core,  'date' => $today ),
            array( 'section' => 'Core System',       'item' => 'Companies',      'version' => $v_core,  'date' => $today ),
            array( 'section' => 'Core System',       'item' => 'Tasks',          'version' => $v_core,  'date' => $today ),
            // Intelligence Core
            array( 'section' => 'Intelligence Core', 'item' => 'KPI Dashboard',  'version' => $v_intel, 'date' => $today ),
            // Sales Core
            array( 'section' => 'Sales Core',        'item' => 'Leads',          'version' => $v_sales, 'date' => $today ),
            array( 'section' => 'Sales Core',        'item' => 'Estimates',      'version' => $v_sales, 'date' => $today ),
            array( 'section' => 'Sales Core',        'item' => 'Proposals',      'version' => $v_sales, 'date' => $today ),
            array( 'section' => 'Sales Core',        'item' => 'Contracts',      'version' => $v_sales, 'date' => $today ),
            // Service Core
            array( 'section' => 'Service Core',      'item' => 'Tickets',        'version' => $v_svc,   'date' => $today ),
            // Operations Core
            array( 'section' => 'Operations Core',   'item' => 'Workflows',      'version' => $v_ops,   'date' => $today ),
            array( 'section' => 'Operations Core',   'item' => 'Onboarding',     'version' => $v_ops,   'date' => $today ),
            // Knowledge Core
            array( 'section' => 'Knowledge Core',    'item' => 'Knowledge Base', 'version' => $v_know,  'date' => $today ),
            array( 'section' => 'Knowledge Core',    'item' => 'Resources',      'version' => $v_know,  'date' => $today ),
            array( 'section' => 'Knowledge Core',    'item' => 'Training',       'version' => $v_know,  'date' => $today ),
            // AI
            array( 'section' => 'AI',                'item' => 'AI Assistant',   'version' => $v_intel, 'date' => $today ),
            // Admin
            array( 'section' => 'Admin',             'item' => 'Activity Report','version' => $v_core,  'date' => $today ),
            array( 'section' => 'Admin',             'item' => 'Import',         'version' => $v_core,  'date' => $today ),
            array( 'section' => 'Admin',             'item' => 'Team',           'version' => $v_core,  'date' => $today ),
            array( 'section' => 'Admin',             'item' => 'Add-ons',        'version' => $v_core,  'date' => $today ),
            array( 'section' => 'Admin',             'item' => 'Settings',       'version' => $v_core,  'date' => $today ),
        );
        echo json_encode( array( 'success' => true, 'data' => $rows, 'generated' => date( 'c' ) ) ); exit;
    }

    // ── Auth check (all actions below require login) ───────────────────────────
    if ( ! sp_get_current_team_member() && ! sp_is_super_admin() ) {
        echo json_encode( array( 'success' => false, 'data' => 'Not authorized' ) ); exit;
    }

    if ( $action === 'pipeline_move' ) {
        global $wpdb;
        $id     = (int) ( isset( $_POST['lead_id'] ) ? $_POST['lead_id'] : 0 );
        $status = sanitize_key( isset( $_POST['status'] ) ? $_POST['status'] : '' );
        $valid  = array( 'new', 'contacted', 'qualified', 'unqualified', 'closed' );
        if ( ! $id || ! in_array( $status, $valid ) ) {
            echo json_encode( array( 'success' => false, 'data' => 'Invalid data' ) ); exit;
        }
        $old = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$wpdb->prefix}sp_leads WHERE id=%d", $id ) );
        $wpdb->update( $wpdb->prefix . 'sp_leads', array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $id ) );
        sp_log_activity( 'lead', $id, 'status_change', 'Status changed from ' . $old . ' to ' . $status );
        echo json_encode( array( 'success' => true ) ); exit;
    }

    if ( $action === 'search' ) {
        global $wpdb;
        $q = isset( $_GET['q'] ) ? sanitize_text_field( trim( $_GET['q'] ) ) : '';
        if ( strlen( $q ) < 2 ) { echo json_encode( array( 'success' => true, 'data' => array() ) ); exit; }
        $like = '%' . $wpdb->esc_like( $q ) . '%';

        $contacts = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, CONCAT(first_name,' ',last_name) AS label, email AS sub FROM {$wpdb->prefix}sp_contacts
             WHERE first_name LIKE %s OR last_name LIKE %s OR email LIKE %s LIMIT 5",
            $like, $like, $like
        ) );

        $companies = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, name AS label, industry AS sub FROM {$wpdb->prefix}sp_companies WHERE name LIKE %s LIMIT 5",
            $like
        ) );

        $leads = $wpdb->get_results( $wpdb->prepare(
            "SELECT l.id, CONCAT(c.first_name,' ',c.last_name) AS label, l.status AS sub
             FROM {$wpdb->prefix}sp_leads l
             LEFT JOIN {$wpdb->prefix}sp_contacts c ON c.id = l.contact_id
             WHERE c.first_name LIKE %s OR c.last_name LIKE %s OR c.email LIKE %s LIMIT 5",
            $like, $like, $like
        ) );

        $results = array();
        foreach ( $contacts  as $r ) $results[] = array( 'type' => 'contact',  'id' => $r->id, 'label' => trim($r->label), 'sub' => $r->sub );
        foreach ( $companies as $r ) $results[] = array( 'type' => 'company',  'id' => $r->id, 'label' => $r->label,        'sub' => $r->sub );
        foreach ( $leads     as $r ) $results[] = array( 'type' => 'lead',     'id' => $r->id, 'label' => trim($r->label), 'sub' => $r->sub );

        echo json_encode( array( 'success' => true, 'data' => $results ) ); exit;
    }

    echo json_encode( array( 'success' => false, 'data' => 'Unknown action' ) ); exit;
}

// ── Onboarding Setup ───────────────────────────────────────────────────────────

function sp_handle_setup_post() {
    $step = (int) $_POST['sp_setup_step'];

    if ( $step === 1 ) {
        if ( ! wp_verify_nonce( isset( $_POST['sp_setup_nonce'] ) ? $_POST['sp_setup_nonce'] : '', 'sp_setup_1' ) ) {
            wp_redirect( home_url( '/sp-setup/?step=1&error=nonce' ) ); exit;
        }
        $name   = sanitize_text_field( isset( $_POST['sp_platform_name'] ) ? $_POST['sp_platform_name'] : '' );
        $accent = sanitize_hex_color( isset( $_POST['sp_accent_color'] ) ? $_POST['sp_accent_color'] : '' );
        if ( $name ) update_option( 'sp_platform_name', $name );
        if ( $accent ) update_option( 'sp_accent_color', $accent );
        wp_redirect( home_url( '/sp-setup/?step=2' ) ); exit;
    }

    if ( $step === 2 ) {
        if ( ! wp_verify_nonce( isset( $_POST['sp_setup_nonce'] ) ? $_POST['sp_setup_nonce'] : '', 'sp_setup_2' ) ) {
            wp_redirect( home_url( '/sp-setup/?step=2&error=nonce' ) ); exit;
        }
        $logo     = esc_url_raw( isset( $_POST['sp_brand_icon_url'] ) ? $_POST['sp_brand_icon_url'] : '' );
        $initials = sanitize_text_field( isset( $_POST['sp_brand_initials'] ) ? strtoupper( $_POST['sp_brand_initials'] ) : '' );
        update_option( 'sp_brand_icon_url', $logo );
        update_option( 'sp_brand_initials', $initials );
        wp_redirect( home_url( '/sp-setup/?step=3' ) ); exit;
    }

    if ( $step === 3 ) {
        if ( ! wp_verify_nonce( isset( $_POST['sp_setup_nonce'] ) ? $_POST['sp_setup_nonce'] : '', 'sp_setup_3' ) ) {
            wp_redirect( home_url( '/sp-setup/?step=3&error=nonce' ) ); exit;
        }
        $name    = sanitize_text_field( isset( $_POST['admin_name'] ) ? $_POST['admin_name'] : '' );
        $email   = sanitize_email( isset( $_POST['admin_email'] ) ? $_POST['admin_email'] : '' );
        $pin     = isset( $_POST['admin_pin'] ) ? trim( $_POST['admin_pin'] ) : '';
        $confirm = isset( $_POST['admin_pin_confirm'] ) ? trim( $_POST['admin_pin_confirm'] ) : '';

        if ( ! $name ) { wp_redirect( home_url( '/sp-setup/?step=3&error=name_required' ) ); exit; }
        if ( strlen( $pin ) < 4 ) { wp_redirect( home_url( '/sp-setup/?step=3&error=pin_short' ) ); exit; }
        if ( $pin !== $confirm ) { wp_redirect( home_url( '/sp-setup/?step=3&error=pin_mismatch' ) ); exit; }

        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'sp_team', array(
            'name'       => $name,
            'email'      => $email,
            'role'       => 'admin',
            'pin'        => wp_hash_password( $pin ),
            'status'     => 'active',
            'created_at' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' ),
        ) );

        update_option( 'sp_setup_complete', 1 );
        wp_redirect( home_url( '/sp-login/?setup=done' ) ); exit;
    }

    wp_redirect( home_url( '/sp-setup/' ) ); exit;
}

// ── CSV Import ─────────────────────────────────────────────────────────────────

function sp_handle_import() {
    global $wpdb;
    $member = sp_get_current_team_member();
    if ( ! $member ) { wp_redirect( home_url( '/sp-login/' ) ); exit; }
    if ( ! wp_verify_nonce( isset( $_POST['sp_nonce'] ) ? $_POST['sp_nonce'] : '', 'sp_form' ) ) { wp_redirect( home_url( '/sp-app/?view=import&error=nonce' ) ); exit; }

    $type = sanitize_key( isset( $_POST['import_type'] ) ? $_POST['import_type'] : '' );
    if ( ! in_array( $type, array( 'contacts', 'companies', 'leads' ) ) ) { wp_redirect( home_url( '/sp-app/?view=import' ) ); exit; }
    if ( empty( $_FILES['sp_csv']['tmp_name'] ) ) { wp_redirect( home_url( '/sp-app/?view=import&type=' . $type ) ); exit; }

    $handle = fopen( $_FILES['sp_csv']['tmp_name'], 'r' );
    if ( ! $handle ) { wp_redirect( home_url( '/sp-app/?view=import&type=' . $type ) ); exit; }

    $headers = fgetcsv( $handle );
    if ( ! $headers ) { fclose( $handle ); wp_redirect( home_url( '/sp-app/?view=import&type=' . $type ) ); exit; }
    $headers = array_map( 'trim', $headers );
    $col     = array_flip( $headers );

    $imported = 0;
    $skipped  = 0;
    $now      = current_time( 'mysql' );

    while ( ( $row = fgetcsv( $handle ) ) !== false ) {
        $v = function( $key ) use ( $row, $col ) {
            return isset( $col[ $key ], $row[ $col[ $key ] ] ) ? trim( $row[ $col[ $key ] ] ) : '';
        };

        if ( $type === 'contacts' ) {
            $first = $v('first_name'); $last = $v('last_name'); $email = $v('email');
            if ( ! $first && ! $email ) { $skipped++; continue; }
            if ( $email ) {
                $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sp_contacts WHERE email=%s", $email ) );
                if ( $exists ) { $skipped++; continue; }
            }
            $status = $v('status'); if ( ! in_array( $status, array( 'active','inactive','archived' ) ) ) $status = 'active';
            $wpdb->insert( $wpdb->prefix . 'sp_contacts', array(
                'first_name' => $first, 'last_name' => $last, 'email' => $email,
                'phone' => $v('phone'), 'source' => $v('source'), 'status' => $status,
                'notes' => $v('notes'), 'created_at' => $now, 'updated_at' => $now,
            ) );
            sp_log_activity( 'contact', $wpdb->insert_id, 'created', 'Imported via CSV' );
            $imported++;

        } elseif ( $type === 'companies' ) {
            $name = $v('name'); if ( ! $name ) { $skipped++; continue; }
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sp_companies WHERE name=%s", $name ) );
            if ( $exists ) { $skipped++; continue; }
            $wpdb->insert( $wpdb->prefix . 'sp_companies', array(
                'name' => $name, 'industry' => $v('industry'), 'website' => $v('website'),
                'phone' => $v('phone'), 'address' => $v('address'), 'notes' => $v('notes'),
                'created_at' => $now, 'updated_at' => $now,
            ) );
            sp_log_activity( 'company', $wpdb->insert_id, 'created', 'Imported via CSV' );
            $imported++;

        } elseif ( $type === 'leads' ) {
            $contact_id = 0; $company_id = 0;
            $contact_email = $v('contact_email');
            if ( $contact_email ) {
                $contact_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sp_contacts WHERE email=%s", $contact_email ) );
            }
            $company_name = $v('company_name');
            if ( $company_name ) {
                $company_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sp_companies WHERE name=%s", $company_name ) );
            }
            if ( ! $contact_id && ! $company_name ) { $skipped++; continue; }
            $status = $v('status'); if ( ! in_array( $status, array( 'new','contacted','qualified','unqualified','closed' ) ) ) $status = 'new';
            $score  = (int) $v('score'); if ( $score < 0 || $score > 100 ) $score = 0;
            $wpdb->insert( $wpdb->prefix . 'sp_leads', array(
                'contact_id' => $contact_id, 'company_id' => $company_id,
                'source' => $v('source'), 'status' => $status, 'score' => $score,
                'notes' => $v('notes'), 'created_at' => $now, 'updated_at' => $now,
            ) );
            sp_log_activity( 'lead', $wpdb->insert_id, 'created', 'Imported via CSV' );
            $imported++;
        }
    }
    fclose( $handle );
    wp_redirect( home_url( '/sp-app/?view=import&type=' . $type . '&imported=' . $imported . '&skipped=' . $skipped ) ); exit;
}

// ── Helpers ────────────────────────────────────────────────────────────────────

function sp_delete_url( $type, $id ) {
    return wp_nonce_url(
        home_url( '/sp-app/?sp_delete=' . $type . '&id=' . $id ),
        'sp_delete_' . $type . '_' . $id
    );
}

// ── Task toggle (AJAX, no page redirect) ───────────────────────────────────────
// Same status-flip logic as the sp_type=task POST handler, but returns JSON instead
// of redirecting — for places like Operations' workflow checklist where the task
// list is embedded in a page that isn't the task's own contact/company record, so
// redirecting there would navigate the user away from where they're working.

add_action( 'wp_ajax_sp_toggle_task',         'sp_ajax_toggle_task' );
add_action( 'wp_ajax_nopriv_sp_toggle_task',  'sp_ajax_toggle_task' );

function sp_ajax_toggle_task() {
    if ( ! check_ajax_referer( 'sp_toggle_task', 'nonce', false ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }
    if ( ! sp_is_authed() ) {
        wp_send_json_error( 'Not authorized' );
    }
    global $wpdb;
    $task_id = (int) ( isset( $_POST['task_id'] ) ? $_POST['task_id'] : 0 );
    if ( ! $task_id ) wp_send_json_error( 'Missing task_id' );
    $task = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_tasks WHERE id = %d", $task_id ) );
    if ( ! $task ) wp_send_json_error( 'Task not found' );
    $new_status = $task->status === 'done' ? 'open' : 'done';
    $wpdb->update( $wpdb->prefix . 'sp_tasks', array( 'status' => $new_status ), array( 'id' => $task_id ) );
    if ( function_exists( 'sp_log_activity' ) ) {
        sp_log_activity( $task->record_type, $task->record_id, 'task_' . $new_status, $task->title );
    }
    wp_send_json_success( array( 'status' => $new_status ) );
}

// ── Addon activate / deactivate ────────────────────────────────────────────────

add_action( 'wp_ajax_sp_addon_toggle', 'sp_ajax_addon_toggle' );

function sp_ajax_addon_toggle() {
    if ( ! check_ajax_referer( 'sp_addon_toggle', 'nonce', false ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }
    if ( ! function_exists( 'sp_is_super_admin' ) || ! sp_is_super_admin() ) {
        wp_send_json_error( 'Not authorized' );
    }
    if ( ! current_user_can( 'activate_plugins' ) ) {
        wp_send_json_error( 'WordPress permissions required' );
    }

    $plugin_file = isset( $_POST['plugin_file'] ) ? sanitize_text_field( $_POST['plugin_file'] ) : '';
    $action      = isset( $_POST['toggle'] )      ? sanitize_key( $_POST['toggle'] )             : '';

    if ( ! $plugin_file || ! in_array( $action, array( 'activate', 'deactivate' ) ) ) {
        wp_send_json_error( 'Invalid request' );
    }

    require_once ABSPATH . 'wp-admin/includes/plugin.php';

    if ( $action === 'activate' ) {
        $result = activate_plugin( $plugin_file );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }
    } else {
        deactivate_plugins( $plugin_file );
    }

    wp_send_json_success( array( 'action' => $action ) );
}

// ── Demo Data Seeder (Super Admin only) ────────────────────────────────────────

add_action( 'sp_settings_sections', 'sp_demo_seeder_section' );
add_action( 'wp_ajax_sp_seed_demo',  'sp_ajax_seed_demo' );
add_action( 'wp_ajax_sp_clear_demo', 'sp_ajax_clear_demo' );
add_filter( 'sp_settings_anchor_tabs', function( $tabs ) {
    if ( sp_is_super_admin() ) $tabs[] = array( 'id' => 'section-demo', 'label' => 'Demo Data' );
    return $tabs;
} );

function sp_demo_seeder_section() {
    if ( ! sp_is_super_admin() ) return;
    $seeded = get_option( 'sp_demo_seeded', false );
    $nonce  = wp_create_nonce( 'sp_demo_seeder' );
    ?>
    <div id="section-demo" style="margin-top:16px;border:2px solid #f59e0b;border-radius:12px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#451a03,#78350f);padding:12px 20px;display:flex;align-items:center;gap:10px;">
            <svg viewBox="0 0 20 20" fill="#fbbf24" width="16" height="16"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
            <span style="font-size:.8rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#fbbf24;">Super Admin Only — Demo Data</span>
        </div>
        <div class="sp-form-card" style="padding:20px 24px;background:#fff;">
            <h2 class="sp-section-heading" style="margin-top:0;">Demo Data Seeder</h2>
            <p style="font-size:.88rem;color:#64748b;margin:0 0 16px;">Seeds realistic sample data so the platform looks live for demos. Includes contacts, companies, leads, tasks, estimates, proposals, contracts, workflows, KB articles, and a training course.</p>

            <div id="sp-demo-status" style="margin-bottom:16px;">
            <?php if ( $seeded ) : ?>
                <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;font-size:.85rem;color:#15803d;">
                    <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Demo data is seeded and active.
                </div>
            <?php else : ?>
                <div style="padding:10px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:.85rem;color:#64748b;">
                    No demo data seeded yet.
                </div>
            <?php endif; ?>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <?php if ( ! $seeded ) : ?>
                <button type="button" id="sp-seed-btn" class="sp-btn sp-btn-primary" onclick="spRunDemo('seed')">
                    Seed Demo Data
                </button>
                <?php else : ?>
                <button type="button" id="sp-seed-btn" class="sp-btn sp-btn-secondary" onclick="spRunDemo('seed')" style="opacity:.6;" disabled>
                    Already Seeded
                </button>
                <button type="button" id="sp-clear-btn" class="sp-btn" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;" onclick="spRunDemo('clear')">
                    Clear Demo Data
                </button>
                <?php endif; ?>
            </div>

            <div id="sp-demo-log" style="display:none;margin-top:16px;padding:12px 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:.82rem;color:#475569;line-height:1.7;font-family:monospace;white-space:pre-wrap;"></div>
        </div>
    </div>

    <script>
    function spRunDemo(action) {
        var log   = document.getElementById('sp-demo-log');
        var seedBtn  = document.getElementById('sp-seed-btn');
        var clearBtn = document.getElementById('sp-clear-btn');
        log.style.display = 'block';
        log.textContent   = action === 'seed' ? 'Seeding demo data...' : 'Clearing demo data...';
        if (seedBtn)  seedBtn.disabled  = true;
        if (clearBtn) clearBtn.disabled = true;

        var fd = new FormData();
        fd.append('action', action === 'seed' ? 'sp_seed_demo' : 'sp_clear_demo');
        fd.append('nonce',  '<?php echo esc_js( $nonce ); ?>');

        fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>', { method:'POST', body:fd, credentials:'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d.success) {
                    log.textContent = d.data.log;
                    setTimeout(function(){ window.location.reload(); }, 1200);
                } else {
                    log.textContent = 'Error: ' + (d.data || 'Unknown error');
                    if (seedBtn)  seedBtn.disabled  = false;
                    if (clearBtn) clearBtn.disabled = false;
                }
            })
            .catch(function(){ log.textContent = 'Request failed.'; });
    }
    </script>
    <?php
}

function sp_ajax_seed_demo() {
    if ( ! check_ajax_referer( 'sp_demo_seeder', 'nonce', false ) ) wp_send_json_error( 'Invalid nonce' );
    if ( ! sp_is_super_admin() ) wp_send_json_error( 'Not authorized' );
    if ( get_option( 'sp_demo_seeded' ) ) wp_send_json_error( 'Already seeded' );

    global $wpdb;
    $now  = current_time( 'mysql' );
    $ids  = array();
    $log  = array();

    // ── Companies ──────────────────────────────────────────────────────────────
    $companies = array(
        array( 'name' => 'Apex Manufacturing',   'industry' => 'Manufacturing', 'phone' => '312-555-0101', 'address' => '100 Industrial Pkwy, Chicago, IL' ),
        array( 'name' => 'Meridian Logistics',   'industry' => 'Logistics',     'phone' => '214-555-0182', 'address' => '500 Commerce Dr, Dallas, TX' ),
        array( 'name' => 'Crestview Healthcare', 'industry' => 'Healthcare',    'phone' => '404-555-0147', 'address' => '200 Medical Blvd, Atlanta, GA' ),
        array( 'name' => 'Northstar Financial',  'industry' => 'Finance',       'phone' => '212-555-0193', 'address' => '1 Wall St, New York, NY' ),
    );
    $ids['companies'] = array();
    foreach ( $companies as $c ) {
        $wpdb->insert( $wpdb->prefix . 'sp_companies', array_merge( $c, array( 'created_at' => $now ) ) );
        $ids['companies'][] = $wpdb->insert_id;
    }
    $log[] = 'Created ' . count( $ids['companies'] ) . ' companies';

    // ── Contacts ───────────────────────────────────────────────────────────────
    $people = array(
        array( 'first_name' => 'Marcus',   'last_name' => 'Chen',     'email' => 'marcus.chen@apexmfg.example',   'phone' => '312-555-0201', 'notes' => 'VP of Operations. Key decision maker.',   'company_idx' => 0 ),
        array( 'first_name' => 'Sarah',    'last_name' => 'Holloway', 'email' => 'sholloway@meridianlog.example', 'phone' => '214-555-0233', 'notes' => 'Director of Supply Chain.',               'company_idx' => 1 ),
        array( 'first_name' => 'David',    'last_name' => 'Okafor',   'email' => 'dokafor@crestviewhc.example',   'phone' => '404-555-0271', 'notes' => 'CFO. Focuses on ROI.',                   'company_idx' => 2 ),
        array( 'first_name' => 'Jennifer', 'last_name' => 'Marsh',    'email' => 'jmarsh@northstarfin.example',  'phone' => '212-555-0318', 'notes' => 'CEO. Final approver on all contracts.',   'company_idx' => 3 ),
        array( 'first_name' => 'Tom',      'last_name' => 'Vasquez',  'email' => 'tvasquez@apexmfg.example',     'phone' => '312-555-0344', 'notes' => 'Plant Manager. Signed safety contract.',  'company_idx' => 0 ),
        array( 'first_name' => 'Priya',    'last_name' => 'Nair',     'email' => 'pnair@meridianlog.example',    'phone' => '214-555-0367', 'notes' => 'IT Manager.',                             'company_idx' => 1 ),
        array( 'first_name' => 'Rachel',   'last_name' => 'Summers',  'email' => 'rsummers@crestviewhc.example', 'phone' => '404-555-0412', 'notes' => 'HR Director. Inbound lead contact.',      'company_idx' => 2 ),
    );
    $ids['contacts'] = array();
    foreach ( $people as $p ) {
        $wpdb->insert( $wpdb->prefix . 'sp_contacts', array(
            'first_name' => $p['first_name'],
            'last_name'  => $p['last_name'],
            'email'      => $p['email'],
            'phone'      => $p['phone'],
            'notes'      => $p['notes'],
            'company_id' => $ids['companies'][ $p['company_idx'] ],
            'source'     => 'demo',
            'status'     => 'active',
            'created_at' => $now,
        ) );
        $ids['contacts'][] = $wpdb->insert_id;
    }
    $log[] = 'Created ' . count( $ids['contacts'] ) . ' contacts';

    // ── Leads ──────────────────────────────────────────────────────────────────
    $leads = array(
        array( 'status' => 'qualified',   'score' => 85, 'contact_idx' => 0, 'company_idx' => 0, 'source' => 'referral',  'notes' => 'Apex — Process Automation Rollout. Ready to move forward after Q3 budget approval. Est. value $28,500.' ),
        array( 'status' => 'contacted',   'score' => 60, 'contact_idx' => 1, 'company_idx' => 1, 'source' => 'outbound',  'notes' => 'Meridian — Fleet Tracking System. Initial call went well. Sent overview deck. Est. value $14,200.' ),
        array( 'status' => 'new',         'score' => 40, 'contact_idx' => 6, 'company_idx' => 2, 'source' => 'website',   'notes' => 'Crestview — HR Platform Integration. Inbound inquiry from contact form. Est. value $9,800.' ),
        array( 'status' => 'qualified',   'score' => 90, 'contact_idx' => 3, 'company_idx' => 3, 'source' => 'referral',  'notes' => 'Northstar — Compliance Dashboard. Referred by existing client. High urgency. Est. value $41,000.' ),
        array( 'status' => 'closed',      'score' => 100,'contact_idx' => 4, 'company_idx' => 0, 'source' => 'outbound',  'notes' => 'Apex — Safety Training Program. Won. Contract signed. Value $6,500.' ),
        array( 'status' => 'unqualified', 'score' => 10, 'contact_idx' => 5, 'company_idx' => 1, 'source' => 'cold_call', 'notes' => 'Meridian — Vendor Portal. Budget not available this year.' ),
    );
    $ids['leads'] = array();
    foreach ( $leads as $l ) {
        $wpdb->insert( $wpdb->prefix . 'sp_leads', array(
            'status'     => $l['status'],
            'score'      => $l['score'],
            'contact_id' => $ids['contacts'][ $l['contact_idx'] ],
            'company_id' => $ids['companies'][ $l['company_idx'] ],
            'source'     => $l['source'],
            'notes'      => $l['notes'],
            'created_at' => $now,
        ) );
        $ids['leads'][] = $wpdb->insert_id;
    }
    $log[] = 'Created ' . count( $ids['leads'] ) . ' leads';

    // ── Tasks ──────────────────────────────────────────────────────────────────
    $tasks_data = array(
        array( 'title' => 'Send proposal to Marcus Chen',         'record_type' => 'lead',    'record_idx' => 0, 'due_offset' => 3,  'status' => 'open' ),
        array( 'title' => 'Follow up after demo — Northstar',     'record_type' => 'lead',    'record_idx' => 3, 'due_offset' => 1,  'status' => 'open' ),
        array( 'title' => 'Schedule kickoff call — Apex',         'record_type' => 'contact', 'record_idx' => 0, 'due_offset' => 5,  'status' => 'open' ),
        array( 'title' => 'Update contact info — Meridian team',  'record_type' => 'contact', 'record_idx' => 1, 'due_offset' => -2, 'status' => 'open' ),
        array( 'title' => 'Review signed contract — Apex Safety', 'record_type' => 'lead',    'record_idx' => 4, 'due_offset' => 0,  'status' => 'done' ),
        array( 'title' => 'Send onboarding checklist to Crestview','record_type' => 'company', 'record_idx' => 2, 'due_offset' => 7,  'status' => 'open' ),
    );
    $ids['tasks'] = array();
    foreach ( $tasks_data as $t ) {
        $record_id = $t['record_type'] === 'lead' ? $ids['leads'][ $t['record_idx'] ]
                   : ( $t['record_type'] === 'company' ? $ids['companies'][ $t['record_idx'] ] : $ids['contacts'][ $t['record_idx'] ] );
        $due = $t['due_offset'] !== 0 ? date( 'Y-m-d', strtotime( $t['due_offset'] . ' days' ) ) : date( 'Y-m-d' );
        $wpdb->insert( $wpdb->prefix . 'sp_tasks', array(
            'title'       => $t['title'],
            'record_type' => $t['record_type'],
            'record_id'   => $record_id,
            'due_date'    => $due,
            'status'      => $t['status'],
            'created_by'  => 0,
            'created_at'  => $now,
        ) );
        $ids['tasks'][] = $wpdb->insert_id;
    }
    $log[] = 'Created ' . count( $ids['tasks'] ) . ' tasks';

    // ── Estimates (Sales Core) ─────────────────────────────────────────────────
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_estimates'" ) ) {
        $estimates = array(
            array(
                'number' => 'EST-0001', 'title' => 'Process Automation — Phase 1',
                'contact_idx' => 0, 'company_idx' => 0, 'status' => 'Sent',
                'tax_rate' => 8.25, 'discount' => 0,
                'items' => array(
                    array( 'description' => 'Platform setup & configuration',  'qty' => 1,   'unit_price' => 4500 ),
                    array( 'description' => 'Custom workflow development',      'qty' => 40,  'unit_price' => 145  ),
                    array( 'description' => 'Team training (per session)',      'qty' => 4,   'unit_price' => 800  ),
                    array( 'description' => 'Monthly support retainer',        'qty' => 3,   'unit_price' => 650  ),
                ),
            ),
            array(
                'number' => 'EST-0002', 'title' => 'Compliance Dashboard — Full Build',
                'contact_idx' => 3, 'company_idx' => 3, 'status' => 'Draft',
                'tax_rate' => 0, 'discount' => 2000,
                'items' => array(
                    array( 'description' => 'Discovery & requirements workshop', 'qty' => 1,   'unit_price' => 3200 ),
                    array( 'description' => 'Dashboard development',            'qty' => 120, 'unit_price' => 155  ),
                    array( 'description' => 'Data integration (per source)',    'qty' => 5,   'unit_price' => 1800 ),
                    array( 'description' => 'QA & launch support',             'qty' => 1,   'unit_price' => 2500 ),
                ),
            ),
        );
        $ids['estimates'] = array();
        $seq = (int) get_option( 'sp_sales_seq_est', 0 );
        foreach ( $estimates as $e ) {
            $subtotal = 0;
            foreach ( $e['items'] as $li ) $subtotal += $li['qty'] * $li['unit_price'];
            $tax   = round( ( $subtotal - $e['discount'] ) * $e['tax_rate'] / 100, 2 );
            $total = round( $subtotal - $e['discount'] + $tax, 2 );
            $wpdb->insert( $wpdb->prefix . 'sp_estimates', array(
                'number'     => $e['number'],
                'title'      => $e['title'],
                'contact_id' => $ids['contacts'][ $e['contact_idx'] ],
                'company_id' => $ids['companies'][ $e['company_idx'] ],
                'status'     => $e['status'],
                'tax_rate'   => $e['tax_rate'],
                'discount'   => $e['discount'],
                'subtotal'   => $subtotal,
                'total'      => $total,
                'valid_until'=> date( 'Y-m-d', strtotime( '+30 days' ) ),
                'created_by' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ) );
            $est_id = $wpdb->insert_id;
            $ids['estimates'][] = $est_id;
            $sort = 1;
            foreach ( $e['items'] as $li ) {
                $wpdb->insert( $wpdb->prefix . 'sp_estimate_items', array(
                    'estimate_id' => $est_id,
                    'sort_order'  => $sort++,
                    'description' => $li['description'],
                    'qty'         => $li['qty'],
                    'unit_price'  => $li['unit_price'],
                    'line_total'  => round( $li['qty'] * $li['unit_price'], 2 ),
                ) );
            }
            $seq++;
        }
        update_option( 'sp_sales_seq_est', $seq );
        $log[] = 'Created ' . count( $ids['estimates'] ) . ' estimates';

        // ── Proposal ───────────────────────────────────────────────────────────
        $wpdb->insert( $wpdb->prefix . 'sp_proposals', array(
            'number'        => 'PROP-0001',
            'title'         => 'Apex Manufacturing — Process Automation Proposal',
            'contact_id'    => $ids['contacts'][0],
            'company_id'    => $ids['companies'][0],
            'status'        => 'Sent',
            'overview'      => '<p>We propose a phased implementation of the Start Performance platform to automate core operational workflows at Apex Manufacturing, reducing manual overhead by an estimated 30%.</p>',
            'scope'         => '<p>Phase 1 covers intake workflow automation, team task assignment, and real-time KPI tracking. Phase 2 introduces client-facing reporting and advanced integrations.</p>',
            'deliverables'  => '<ul><li>Configured platform environment</li><li>3 automated workflow templates</li><li>Team onboarding sessions (x4)</li><li>Live KPI dashboard</li></ul>',
            'timeline'      => '<p>Estimated 6-week delivery from signed contract. Kickoff within 5 business days.</p>',
            'pricing_notes' => '<p>See attached estimate EST-0001. Net 30 payment terms. 50% due at kickoff.</p>',
            'total_value'   => 17450.00,
            'valid_until'   => date( 'Y-m-d', strtotime( '+30 days' ) ),
            'created_by'    => 0,
            'created_at'    => $now,
            'updated_at'    => $now,
        ) );
        $ids['proposals'] = array( $wpdb->insert_id );
        $log[] = 'Created 1 proposal';

        // ── Contract ───────────────────────────────────────────────────────────
        $wpdb->insert( $wpdb->prefix . 'sp_contracts', array(
            'number'     => 'CTR-0001',
            'title'      => 'Apex Manufacturing — Safety Training Program',
            'contact_id' => $ids['contacts'][4],
            'company_id' => $ids['companies'][0],
            'status'     => 'Active',
            'content'    => '<p>This agreement covers the delivery of the Start Performance Safety Training Program for Apex Manufacturing. Services include platform access, content delivery, and completion tracking for up to 50 team members.</p><p>Term: 12 months from signing date. Auto-renews with 30-day written notice to cancel.</p>',
            'value'      => 6500.00,
            'start_date' => date( 'Y-m-d', strtotime( '-14 days' ) ),
            'end_date'   => date( 'Y-m-d', strtotime( '+351 days' ) ),
            'signed_at'  => date( 'Y-m-d', strtotime( '-14 days' ) ),
            'signed_by_name' => 'Tom Vasquez',
            'created_by' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ) );
        $ids['contracts'] = array( $wpdb->insert_id );
        $log[] = 'Created 1 contract';
    }

    // ── Workflows (Operations Core) ────────────────────────────────────────────
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_op_workflows'" ) ) {
        $workflows = array(
            array(
                'name'        => 'New Client Onboarding',
                'description' => 'Standard workflow for onboarding a new client from signed contract to go-live.',
                'steps'       => array(
                    array( 'title' => 'Send welcome email and platform access',      'offset' => 0,  'role' => 'admin'  ),
                    array( 'title' => 'Schedule kickoff call',                       'offset' => 1,  'role' => 'admin'  ),
                    array( 'title' => 'Complete discovery questionnaire with client','offset' => 3,  'role' => 'admin'  ),
                    array( 'title' => 'Configure platform settings and branding',    'offset' => 5,  'role' => 'admin'  ),
                    array( 'title' => 'Deliver team training session',               'offset' => 10, 'role' => 'admin'  ),
                    array( 'title' => 'Go-live review and sign-off',                 'offset' => 14, 'role' => 'admin'  ),
                ),
            ),
            array(
                'name'        => 'Monthly Account Review',
                'description' => 'Recurring workflow for monthly client check-ins and reporting.',
                'steps'       => array(
                    array( 'title' => 'Pull KPI report from Intelligence Core',      'offset' => 0, 'role' => 'admin' ),
                    array( 'title' => 'Prepare account summary for client',          'offset' => 1, 'role' => 'admin' ),
                    array( 'title' => 'Schedule and conduct review call',            'offset' => 3, 'role' => 'admin' ),
                    array( 'title' => 'Log action items as tasks',                   'offset' => 3, 'role' => 'admin' ),
                ),
            ),
        );
        $ids['workflows'] = array();
        foreach ( $workflows as $wf ) {
            $wpdb->insert( $wpdb->prefix . 'sp_op_workflows', array(
                'name'        => $wf['name'],
                'description' => $wf['description'],
                'created_by'  => 0,
                'created_at'  => $now,
            ) );
            $wf_id = $wpdb->insert_id;
            $ids['workflows'][] = $wf_id;
            $sort = 1;
            foreach ( $wf['steps'] as $step ) {
                $wpdb->insert( $wpdb->prefix . 'sp_op_workflow_steps', array(
                    'workflow_id'     => $wf_id,
                    'sort_order'      => $sort++,
                    'title'           => $step['title'],
                    'due_offset_days' => $step['offset'],
                    'assigned_role'   => $step['role'],
                ) );
            }
        }
        $log[] = 'Created ' . count( $ids['workflows'] ) . ' workflows';

        // ── Onboarding template ────────────────────────────────────────────────
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_ob_templates'" ) ) {
            $wpdb->insert( $wpdb->prefix . 'sp_ob_templates', array(
                'name'        => 'New Client Checklist',
                'description' => 'Checklist assigned to a company when they become a client.',
                'created_by'  => 0,
                'created_at'  => $now,
            ) );
            $tpl_id = $wpdb->insert_id;
            $ids['ob_templates'] = array( $tpl_id );
            $checklist_items = array(
                array( 'title' => 'Signed contract received',             'description' => '' ),
                array( 'title' => 'Platform access credentials sent',     'description' => '' ),
                array( 'title' => 'Kickoff call completed',               'description' => '' ),
                array( 'title' => 'Branding configured (logo, colors)',   'description' => '' ),
                array( 'title' => 'Team members added to platform',       'description' => '' ),
                array( 'title' => 'Training session delivered',           'description' => '' ),
                array( 'title' => 'Go-live confirmed by client',          'description' => '' ),
            );
            $sort = 1;
            foreach ( $checklist_items as $item ) {
                $wpdb->insert( $wpdb->prefix . 'sp_ob_items', array(
                    'template_id' => $tpl_id,
                    'sort_order'  => $sort++,
                    'title'       => $item['title'],
                    'description' => $item['description'],
                ) );
            }
            $log[] = 'Created 1 onboarding checklist template';
        }
    }

    // ── Knowledge Base (Knowledge Core) ───────────────────────────────────────
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_kb_categories'" ) ) {
        // Categories
        $cats = array(
            array( 'type' => 'kb',       'name' => 'Platform Guides',    'sort_order' => 1 ),
            array( 'type' => 'kb',       'name' => 'Sales Playbooks',     'sort_order' => 2 ),
            array( 'type' => 'resource', 'name' => 'Templates',           'sort_order' => 1 ),
        );
        $ids['kb_categories'] = array();
        foreach ( $cats as $cat ) {
            $wpdb->insert( $wpdb->prefix . 'sp_kb_categories', array_merge( $cat, array( 'created_at' => $now ) ) );
            $ids['kb_categories'][] = $wpdb->insert_id;
        }

        // Articles
        $articles = array(
            array(
                'cat_idx'    => 0,
                'title'      => 'Getting Started with Start Performance',
                'visibility' => 'team',
                'tags'       => 'onboarding, setup, getting started',
                'content'    => '<h2>Welcome to Start Performance</h2><p>This guide walks you through the core areas of the platform and how to get the most out of each module.</p><h3>Core System</h3><p>The Core System is your foundation. Start here by adding your key contacts, companies, and leads. Every other module builds on this data.</p><h3>Leads Pipeline</h3><p>Track every opportunity from first contact to closed deal. Update lead status regularly to keep your KPI dashboard accurate.</p><h3>Tasks</h3><p>Tasks can be assigned to any contact, company, or lead. Set due dates and assignees so nothing falls through the cracks.</p>',
            ),
            array(
                'cat_idx'    => 1,
                'title'      => 'Discovery Call Playbook',
                'visibility' => 'team',
                'tags'       => 'sales, discovery, calls',
                'content'    => '<h2>Discovery Call Playbook</h2><p>Use this playbook to run a consistent, effective discovery call with every prospect.</p><h3>Before the Call</h3><ul><li>Research the company (industry, size, recent news)</li><li>Review any prior touchpoints in the platform</li><li>Prepare 3-5 open-ended questions</li></ul><h3>During the Call</h3><ul><li>Confirm their current pain points</li><li>Understand their decision-making process and timeline</li><li>Ask about budget range without being direct</li></ul><h3>After the Call</h3><ul><li>Log notes and update lead status immediately</li><li>Create a follow-up task with a specific due date</li><li>Send a summary email within 2 hours</li></ul>',
            ),
            array(
                'cat_idx'    => 1,
                'title'      => 'Handling Objections — Price',
                'visibility' => 'team',
                'tags'       => 'sales, objections, pricing',
                'content'    => '<h2>Handling Price Objections</h2><p>Price objections are almost always about perceived value, not the actual number. Use these approaches to reframe the conversation.</p><h3>Response Framework</h3><p><strong>"That is more than we budgeted."</strong><br>Acknowledge it, then anchor to ROI: "I understand. Let me show you what clients typically see in the first 90 days in terms of time saved and revenue impact."</p><p><strong>"Can you do it cheaper?"</strong><br>Never just discount. Instead: "Let me look at the scope — are there elements we could phase so we match your budget without cutting the parts that drive the most value?"</p>',
            ),
        );
        $ids['kb_articles'] = array();
        foreach ( $articles as $a ) {
            $wpdb->insert( $wpdb->prefix . 'sp_kb_articles', array(
                'category_id' => $ids['kb_categories'][ $a['cat_idx'] ],
                'title'       => $a['title'],
                'content'     => $a['content'],
                'tags'        => $a['tags'],
                'visibility'  => $a['visibility'],
                'created_by'  => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ) );
            $ids['kb_articles'][] = $wpdb->insert_id;
        }
        $log[] = 'Created ' . count( $ids['kb_articles'] ) . ' KB articles';

        // Resource
        $wpdb->insert( $wpdb->prefix . 'sp_kb_resources', array(
            'category_id'   => $ids['kb_categories'][2],
            'title'         => 'Client Proposal Template (DOCX)',
            'description'   => 'Standard proposal template — fill in client name, scope, and pricing.',
            'resource_type' => 'link',
            'url'           => 'https://docs.google.com/document/d/example',
            'created_by'    => 0,
            'created_at'    => $now,
        ) );
        $ids['kb_resources'] = array( $wpdb->insert_id );
        $log[] = 'Created 1 resource';

        // Training course + lessons
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_kb_courses'" ) ) {
            $wpdb->insert( $wpdb->prefix . 'sp_kb_courses', array(
                'title'       => 'Platform Foundations',
                'description' => 'A short course covering the core concepts every team member should know.',
                'created_by'  => 0,
                'created_at'  => $now,
            ) );
            $course_id = $wpdb->insert_id;
            $ids['kb_courses'] = array( $course_id );

            $lessons = array(
                array(
                    'sort_order' => 1,
                    'title'      => 'Contacts, Companies & Leads',
                    'content'    => '<p>The three pillars of the Core System. Contacts are individual people. Companies are the organizations they belong to. Leads are the specific opportunities you are pursuing.</p><p>Always link a lead to both a contact and a company when possible. This gives the Intelligence Core accurate pipeline data.</p>',
                ),
                array(
                    'sort_order' => 2,
                    'title'      => 'Using Tasks Effectively',
                    'content'    => '<p>Every action item should become a task. Tasks linked to a record (contact, company, or lead) keep the full history in one place.</p><p>Set a due date on every task. The KPI dashboard shows overdue tasks as a key health signal.</p>',
                ),
            );
            $ids['kb_lessons'] = array();
            foreach ( $lessons as $lesson ) {
                $wpdb->insert( $wpdb->prefix . 'sp_kb_lessons', array(
                    'course_id'  => $course_id,
                    'sort_order' => $lesson['sort_order'],
                    'title'      => $lesson['title'],
                    'content'    => $lesson['content'],
                    'created_at' => $now,
                ) );
                $ids['kb_lessons'][] = $wpdb->insert_id;
            }
            $log[] = 'Created 1 training course with ' . count( $lessons ) . ' lessons';
        }
    }

    // Tickets
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_tickets'" ) ) {
        $tickets_data = array(
            array(
                'title'       => 'Platform login not working after password reset',
                'description' => "User reports they completed the password reset flow but are unable to sign in. The system returns an 'Incorrect PIN' error on every attempt.\n\nUser is blocked from the platform. Priority escalated by account manager.",
                'status'      => 'open',
                'priority'    => 'urgent',
                'contact_id'  => isset( $ids['contacts'][3] ) ? $ids['contacts'][3] : 0,
                'company_id'  => isset( $ids['companies'][2] ) ? $ids['companies'][2] : 0,
                'assigned_to' => 'Bud Dark',
                'created_at'  => date( 'Y-m-d H:i:s', strtotime( '-2 days -3 hours' ) ),
            ),
            array(
                'title'       => 'Export to PDF not generating correctly on proposals',
                'description' => "When clicking Print / PDF on a proposal, the generated PDF is missing the sidebar details panel. Only the main content area is printing.\n\nBrowser: Chrome 124. Tested on Firefox — same result.",
                'status'      => 'in_progress',
                'priority'    => 'high',
                'contact_id'  => isset( $ids['contacts'][0] ) ? $ids['contacts'][0] : 0,
                'company_id'  => isset( $ids['companies'][0] ) ? $ids['companies'][0] : 0,
                'assigned_to' => 'Admin',
                'created_at'  => date( 'Y-m-d H:i:s', strtotime( '-4 days -1 hour' ) ),
            ),
            array(
                'title'       => 'Add custom field to contact record for referral source',
                'description' => "Request to add a \"Referral Source\" field to the contact record so the sales team can track where new contacts are coming from (e.g. LinkedIn, trade show, partner referral, inbound web).",
                'status'      => 'open',
                'priority'    => 'normal',
                'contact_id'  => isset( $ids['contacts'][4] ) ? $ids['contacts'][4] : 0,
                'company_id'  => isset( $ids['companies'][3] ) ? $ids['companies'][3] : 0,
                'assigned_to' => 'Admin',
                'created_at'  => date( 'Y-m-d H:i:s', strtotime( '-6 days' ) ),
            ),
            array(
                'title'       => 'Daily digest email arriving 4 hours late',
                'description' => "The morning digest email is set to deliver at 8:00 AM but has been consistently arriving around noon. Checked timezone setting — it is configured correctly.",
                'status'      => 'open',
                'priority'    => 'normal',
                'contact_id'  => isset( $ids['contacts'][5] ) ? $ids['contacts'][5] : 0,
                'company_id'  => isset( $ids['companies'][0] ) ? $ids['companies'][0] : 0,
                'assigned_to' => 'Bud Dark',
                'created_at'  => date( 'Y-m-d H:i:s', strtotime( '-3 days -5 hours' ) ),
            ),
            array(
                'title'       => 'Training: onboard new sales rep to platform',
                'description' => "New team member starting Monday. Needs platform walkthrough covering CRM, Sales Core, tasks, and the knowledge base training modules.",
                'status'      => 'resolved',
                'priority'    => 'normal',
                'contact_id'  => isset( $ids['contacts'][6] ) ? $ids['contacts'][6] : 0,
                'company_id'  => isset( $ids['companies'][2] ) ? $ids['companies'][2] : 0,
                'assigned_to' => 'Admin',
                'created_at'  => date( 'Y-m-d H:i:s', strtotime( '-9 days' ) ),
            ),
            array(
                'title'       => 'Pipeline board not loading — blank screen',
                'description' => "When navigating to Leads → Pipeline view, the board loads with column headers but no cards are visible. The list view works fine and shows all open leads.\n\nConsole error: Uncaught TypeError: Cannot read properties of undefined.",
                'status'      => 'in_progress',
                'priority'    => 'high',
                'contact_id'  => isset( $ids['contacts'][2] ) ? $ids['contacts'][2] : 0,
                'company_id'  => isset( $ids['companies'][1] ) ? $ids['companies'][1] : 0,
                'assigned_to' => 'Bud Dark',
                'created_at'  => date( 'Y-m-d H:i:s', strtotime( '-1 day -2 hours' ) ),
            ),
            array(
                'title'       => 'Request: bulk import contacts from CSV',
                'description' => "Sales team has a list of ~340 contacts from a trade show export they need imported into the CRM. Request for a CSV import tool or one-time import assistance.",
                'status'      => 'open',
                'priority'    => 'normal',
                'contact_id'  => isset( $ids['contacts'][7] ) ? $ids['contacts'][7] : 0,
                'company_id'  => isset( $ids['companies'][1] ) ? $ids['companies'][1] : 0,
                'assigned_to' => 'Admin',
                'created_at'  => date( 'Y-m-d H:i:s', strtotime( '-5 days -4 hours' ) ),
            ),
        );
        $ids['tickets'] = array();
        foreach ( $tickets_data as $ticket ) {
            $wpdb->insert( $wpdb->prefix . 'sp_tickets', array(
                'title'       => $ticket['title'],
                'description' => $ticket['description'],
                'status'      => $ticket['status'],
                'priority'    => $ticket['priority'],
                'contact_id'  => $ticket['contact_id'],
                'company_id'  => $ticket['company_id'],
                'assigned_to' => $ticket['assigned_to'],
                'created_at'  => $ticket['created_at'],
                'updated_at'  => $ticket['created_at'],
            ) );
            $ids['tickets'][] = $wpdb->insert_id;
        }
        $log[] = 'Created ' . count( $ids['tickets'] ) . ' service tickets';
    }

    update_option( 'sp_demo_seeded', true );
    update_option( 'sp_demo_ids',    $ids );

    wp_send_json_success( array( 'log' => implode( "\n", $log ) . "\n\nDone! Reloading..." ) );
}

function sp_ajax_clear_demo() {
    if ( ! check_ajax_referer( 'sp_demo_seeder', 'nonce', false ) ) wp_send_json_error( 'Invalid nonce' );
    if ( ! sp_is_super_admin() ) wp_send_json_error( 'Not authorized' );

    global $wpdb;
    $ids = get_option( 'sp_demo_ids', array() );
    $log = array();

    $map = array(
        'contacts'     => 'sp_contacts',
        'companies'    => 'sp_companies',
        'leads'        => 'sp_leads',
        'tasks'        => 'sp_tasks',
        'estimates'    => 'sp_estimates',
        'proposals'    => 'sp_proposals',
        'contracts'    => 'sp_contracts',
        'workflows'    => 'sp_op_workflows',
        'ob_templates' => 'sp_ob_templates',
        'kb_articles'  => 'sp_kb_articles',
        'kb_resources' => 'sp_kb_resources',
        'kb_courses'   => 'sp_kb_courses',
        'kb_categories'=> 'sp_kb_categories',
        'tickets'      => 'sp_tickets',
    );

    foreach ( $map as $key => $table ) {
        if ( empty( $ids[ $key ] ) ) continue;
        if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}{$table}'" ) ) continue;
        $id_list = implode( ',', array_map( 'intval', $ids[ $key ] ) );
        $deleted = $wpdb->query( "DELETE FROM {$wpdb->prefix}{$table} WHERE id IN ({$id_list})" );
        $log[] = "Deleted {$deleted} from {$table}";
    }

    // Clean up estimate items
    if ( ! empty( $ids['estimates'] ) && $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_estimate_items'" ) ) {
        $id_list = implode( ',', array_map( 'intval', $ids['estimates'] ) );
        $wpdb->query( "DELETE FROM {$wpdb->prefix}sp_estimate_items WHERE estimate_id IN ({$id_list})" );
    }
    // Clean up workflow steps
    if ( ! empty( $ids['workflows'] ) && $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_op_workflow_steps'" ) ) {
        $id_list = implode( ',', array_map( 'intval', $ids['workflows'] ) );
        $wpdb->query( "DELETE FROM {$wpdb->prefix}sp_op_workflow_steps WHERE workflow_id IN ({$id_list})" );
    }
    // Clean up onboarding items
    if ( ! empty( $ids['ob_templates'] ) && $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_ob_items'" ) ) {
        $id_list = implode( ',', array_map( 'intval', $ids['ob_templates'] ) );
        $wpdb->query( "DELETE FROM {$wpdb->prefix}sp_ob_items WHERE template_id IN ({$id_list})" );
    }
    // Clean up KB lessons
    if ( ! empty( $ids['kb_courses'] ) && $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_kb_lessons'" ) ) {
        $id_list = implode( ',', array_map( 'intval', $ids['kb_courses'] ) );
        $wpdb->query( "DELETE FROM {$wpdb->prefix}sp_kb_lessons WHERE course_id IN ({$id_list})" );
    }

    delete_option( 'sp_demo_seeded' );
    delete_option( 'sp_demo_ids' );

    wp_send_json_success( array( 'log' => implode( "\n", $log ) . "\n\nCleared. Reloading..." ) );
}

function sp_pagination( $total, $limit, $paged, $view ) {
    $pages = (int) ceil( $total / $limit );
    if ( $pages <= 1 ) return;
    echo '<div class="sp-pagination">';
    if ( $paged > 1 ) echo '<a href="' . esc_url( home_url( '/sp-app/?view=' . $view . '&paged=' . ( $paged - 1 ) ) ) . '" class="sp-btn sp-btn-ghost sp-btn-sm">&larr; Prev</a>';
    echo '<span>Page ' . $paged . ' of ' . $pages . '</span>';
    if ( $paged < $pages ) echo '<a href="' . esc_url( home_url( '/sp-app/?view=' . $view . '&paged=' . ( $paged + 1 ) ) ) . '" class="sp-btn sp-btn-ghost sp-btn-sm">Next &rarr;</a>';
    echo '</div>';
}
