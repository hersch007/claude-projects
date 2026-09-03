<?php
/*
 * Plugin Name: Start Performance — Lifeward Landing
 * Description: Customer-facing landing page for Lifeward featuring the "Rachel" assistant persona — a guided call-now / schedule-callback / send-info / not-interested funnel wired into Core System contacts, leads, tasks, and activity log. Call-center notification is stubbed pending a real endpoint; Salesforce sync is a mock client pending real Connected App credentials.
 * Version:     0.1.1
 * Author:      Start Performance | RH Brashear
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SP_LIFEWARD_VERSION',    '0.1.1' );
define( 'SP_LIFEWARD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SP_LIFEWARD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once SP_LIFEWARD_PLUGIN_DIR . 'includes/class-salesforce-client.php';
require_once SP_LIFEWARD_PLUGIN_DIR . 'includes/call-center.php';
require_once SP_LIFEWARD_PLUGIN_DIR . 'includes/rest-endpoints.php';
require_once SP_LIFEWARD_PLUGIN_DIR . 'includes/settings.php';

// ── Boot ──────────────────────────────────────────────────────────────────────

add_action( 'plugins_loaded', 'sp_lifeward_boot', 20 );

function sp_lifeward_boot() {
    if ( ! function_exists( 'sp_register_view' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Start Performance — Lifeward Landing</strong> requires the Start Performance core plugin.</p></div>';
        } );
        return;
    }
    sp_lifeward_register();
}

// ── Activation + tables ───────────────────────────────────────────────────────
// Only sp_lifeward_sessions is new. Contacts, leads, tasks, notes, and activity all
// live in the Core System's own tables — see includes/rest-endpoints.php.

register_activation_hook( __FILE__, 'sp_lifeward_activate' );
add_action( 'sp_activate', 'sp_lifeward_create_tables' );

function sp_lifeward_activate() {
    sp_lifeward_create_tables();
    sp_lifeward_ensure_public_frontend();
}

// The Core System redirects every front-end URL to /sp-app/ by default (it assumes
// an internal-tool-only site — see the "Redirect all WordPress front-end pages to
// the SP app" block in start-performance.php). Lifeward's whole purpose is a public
// customer-facing landing page, so this plugin needs that redirect turned off. Only
// sets it if unset, so an admin who deliberately disables it later isn't overridden.
function sp_lifeward_ensure_public_frontend() {
    if ( get_option( 'sp_public_frontend', '' ) === '' ) {
        update_option( 'sp_public_frontend', 1 );
    }
}

function sp_lifeward_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_lifeward_sessions (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  token varchar(64) NOT NULL DEFAULT '',
  stage varchar(30) NOT NULL DEFAULT 'greeting',
  contact_name varchar(191) NOT NULL DEFAULT '',
  contact_email varchar(191) NOT NULL DEFAULT '',
  contact_phone varchar(50) NOT NULL DEFAULT '',
  slot_choice datetime DEFAULT NULL,
  contact_id bigint(20) unsigned NOT NULL DEFAULT 0,
  lead_id bigint(20) unsigned NOT NULL DEFAULT 0,
  salesforce_lead_id varchar(64) NOT NULL DEFAULT '',
  ip_hash varchar(64) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY token (token),
  KEY stage (stage)
) $charset;" );
}

// ── Registration ─────────────────────────────────────────────────────────────

function sp_lifeward_register() {
    sp_lifeward_ensure_public_frontend(); // lazy safeguard for installs updated in place, not freshly (re)activated

    sp_register_addon( 'sp-lifeward', array(
        'name'         => 'Lifeward Landing',
        'version'      => SP_LIFEWARD_VERSION,
        'description'  => 'Rachel-led lead capture funnel for the Lifeward site — call now, schedule a callback, request info, or opt out — synced to Contacts, Leads, and Activity.',
        'settings_url' => home_url( '/sp-app/?view=settings#section-lifeward' ),
        'icon'         => '<path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17a5 5 0 110-10 5 5 0 010 10zm0-8a3 3 0 100 6 3 3 0 000-6z"/>',
        'core_slot'    => 'sales-core',
        'plugin_file'  => plugin_basename( __FILE__ ),
    ) );

    sp_register_view( 'lifeward-leads', SP_LIFEWARD_PLUGIN_DIR . 'templates/views/lifeward-leads.php' );
    add_filter( 'sp_nav_items',     'sp_lifeward_nav_items' );
    add_filter( 'sp_allowed_views', 'sp_lifeward_allowed_views' );

    add_action( 'sp_dashboard_before_stats', 'sp_lifeward_dashboard_stats' );
    add_filter( 'sp_intel_kpi_cards',        'sp_lifeward_intel_kpi_cards' );
    add_filter( 'sp_intel_summary_lines',    'sp_lifeward_intel_summary_lines', 20, 4 );

    add_shortcode( 'sp_lifeward_landing', 'sp_lifeward_render_landing' );
    add_action( 'wp_enqueue_scripts', 'sp_lifeward_enqueue_assets' );
}

// ── Frontend shortcode ────────────────────────────────────────────────────────

function sp_lifeward_render_landing( $atts ) {
    ob_start();
    include SP_LIFEWARD_PLUGIN_DIR . 'templates/landing.php';
    return ob_get_clean();
}

function sp_lifeward_enqueue_assets() {
    global $post;
    if ( ! ( $post instanceof WP_Post ) || ! has_shortcode( $post->post_content, 'sp_lifeward_landing' ) ) return;

    wp_enqueue_style( 'sp-lifeward-widget', SP_LIFEWARD_PLUGIN_URL . 'assets/lifeward-widget.css', array(), SP_LIFEWARD_VERSION );
    wp_enqueue_script( 'sp-lifeward-widget', SP_LIFEWARD_PLUGIN_URL . 'assets/lifeward-widget.js', array(), SP_LIFEWARD_VERSION, true );
    wp_localize_script( 'sp-lifeward-widget', 'SP_LIFEWARD', array(
        'restUrl' => esc_url_raw( rest_url( 'sp-lifeward/v1/step' ) ),
        'nonce'   => wp_create_nonce( 'wp_rest' ),
    ) );
}

// ── Sales Core nav bridge (mirrors plugin/fiberco-sp/fiberco-sp.php) ──────────

function sp_lifeward_lead_stats() {
    static $cache = null;
    if ( $cache !== null ) return $cache;
    global $wpdb;
    $t = $wpdb->prefix . 'sp_lifeward_sessions';
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t ) ) !== $t ) {
        return $cache = array( 'total' => 0, 'hot' => 0, 'scheduled' => 0, 'week' => 0, 'has_table' => false );
    }
    $week_cut = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 7 * DAY_IN_SECONDS );
    $cache = array(
        'total'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE lead_id > 0" ),
        'hot'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE stage = 'done' AND contact_phone != '' AND slot_choice IS NULL" ),
        'scheduled' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE slot_choice IS NOT NULL" ),
        'week'      => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE lead_id > 0 AND created_at >= %s", $week_cut ) ),
        'has_table' => true,
    );
    return $cache;
}

function sp_lifeward_nav_items( $items ) {
    $result = array();
    foreach ( $items as $item ) {
        $result[] = $item;
        if ( ! empty( $item['section'] ) && ! empty( $item['section_id'] ) && 'sales-core' === $item['section_id'] ) {
            $result[] = array(
                'view'   => 'lifeward-leads',
                'label'  => 'Lifeward Leads',
                'custom' => true,
                'icon'   => '<path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17a5 5 0 110-10 5 5 0 010 10zm0-8a3 3 0 100 6 3 3 0 000-6z"/>',
            );
        }
    }
    return $result;
}

function sp_lifeward_allowed_views( $views ) {
    $views[] = 'lifeward-leads';
    return $views;
}

function sp_lifeward_dashboard_stats() {
    if ( function_exists( 'sp_is_view_hidden' ) && sp_is_view_hidden( 'lifeward-leads' ) ) return;
    $s = sp_lifeward_lead_stats();
    if ( ! $s['has_table'] || $s['total'] === 0 ) return;
    ?>
    <div class="sp-stats sp-stats-2">
        <div class="sp-stat-card" style="border-top-color:#0057FF">
            <div class="sp-stat-value"><?php echo number_format( $s['total'] ); ?></div>
            <div class="sp-stat-label">Lifeward Leads</div>
        </div>
        <div class="sp-stat-card" style="border-top-color:#f59e0b">
            <div class="sp-stat-value"><?php echo number_format( $s['scheduled'] ); ?></div>
            <div class="sp-stat-label">Callbacks Scheduled</div>
        </div>
    </div>
    <?php
}

function sp_lifeward_intel_kpi_cards( $cards ) {
    $s = sp_lifeward_lead_stats();
    if ( ! $s['has_table'] || $s['total'] === 0 ) return $cards;
    $cards[] = array( 'label' => 'Lifeward Leads', 'value' => number_format( $s['total'] ), 'sub' => $s['week'] . ' this week', 'accent' => 'accent' );
    $cards[] = array( 'label' => 'Callbacks Scheduled', 'value' => number_format( $s['scheduled'] ), 'sub' => 'from the landing page', 'accent' => 'green' );
    return $cards;
}

function sp_lifeward_intel_summary_lines( $lines, $days, $since, $today ) {
    $s = sp_lifeward_lead_stats();
    if ( ! $s['has_table'] || $s['total'] === 0 ) return $lines;
    $lines[] = "- Lifeward landing page: {$s['total']} leads captured, {$s['scheduled']} callbacks scheduled, {$s['week']} in the last {$days} days";
    return $lines;
}

// ── SP-authed CSV export (mirrors plugin/fiberco-sp/fiberco-sp.php) ───────────

add_action( 'init', 'sp_lifeward_maybe_export_csv' );
function sp_lifeward_maybe_export_csv() {
    if ( empty( $_GET['sp_lifeward_export'] ) ) return;
    if ( ! function_exists( 'sp_is_authed' ) || ! sp_is_authed() ) return;

    global $wpdb;
    $sessions_t = $wpdb->prefix . 'sp_lifeward_sessions';
    $leads_t    = $wpdb->prefix . 'sp_leads';
    $contacts_t = $wpdb->prefix . 'sp_contacts';
    $rows = $wpdb->get_results(
        "SELECT sess.created_at, sess.slot_choice, lead.status AS lead_status,
                c.first_name, c.last_name, c.email AS contact_email, c.phone AS contact_phone
         FROM $sessions_t sess
         LEFT JOIN $leads_t lead ON lead.id = sess.lead_id
         LEFT JOIN $contacts_t c ON c.id = sess.contact_id
         WHERE sess.lead_id > 0
         ORDER BY sess.created_at DESC", ARRAY_A );

    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=lifeward-leads-' . gmdate( 'Y-m-d' ) . '.csv' );
    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, array( 'Date', 'First Name', 'Last Name', 'Email', 'Phone', 'Outcome', 'Callback Time' ) );
    foreach ( (array) $rows as $r ) {
        fputcsv( $out, array( $r['created_at'], $r['first_name'], $r['last_name'], $r['contact_email'], $r['contact_phone'], $r['lead_status'], $r['slot_choice'] ) );
    }
    fclose( $out );
    exit;
}
