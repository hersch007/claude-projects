<?php
/*
 * Plugin Name: Start Performance — Intelligence Core
 * Description: KPI dashboard and business intelligence for the Start Performance Platform
 * Version:     1.2.5
 * Author:      Richard Brashear / Start Performance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SP_INTEL_VERSION',    '1.2.5' );
define( 'SP_INTEL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

add_action( 'plugins_loaded', 'sp_intel_boot', 20 );

function sp_intel_boot() {
    if ( ! function_exists( 'sp_register_view' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Start Performance — Intelligence Core</strong> requires the Start Performance core plugin.</p></div>';
        } );
        return;
    }
    sp_intel_register();
}

function sp_intel_register() {
    sp_register_addon( 'sp-intelligence', array(
        'name'        => 'Intelligence Core',
        'version'     => SP_INTEL_VERSION,
        'description' => 'KPI dashboard, business performance metrics, and pipeline insights across all active modules.',
        'icon'        => '<path fill="currentColor" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
        'plugin_file' => plugin_basename( __FILE__ ),
        'core_slot'   => 'intelligence-core',
    ) );

    sp_register_view( 'intelligence', SP_INTEL_PLUGIN_DIR . 'templates/views/intelligence.php' );

    add_filter( 'sp_nav_items',        'sp_intel_nav_items' );
    add_filter( 'sp_allowed_views',    'sp_intel_allowed_views' );
    add_action( 'sp_settings_sections','sp_intel_settings_section' );
    add_action( 'sp_post_handler_intel_settings', 'sp_intel_save_settings' );

    add_action( 'wp_ajax_sp_intel_ai_insights', 'sp_intel_ajax_ai_insights' );
    add_action( 'wp_ajax_nopriv_sp_intel_ai_insights', 'sp_intel_ajax_ai_insights' );

    add_filter( 'sp_intel_summary_lines', 'sp_intel_default_summary_lines', 10, 4 );
}

function sp_intel_nav_items( $items ) {
    $result = array();
    foreach ( $items as $item ) {
        $result[] = $item;
        if ( ! empty( $item['section'] ) && ! empty( $item['section_id'] ) && $item['section_id'] === 'intelligence-core' ) {
            $result[] = array(
                'view'  => 'intelligence',
                'label' => 'KPI Dashboard',
                'icon'  => '<path fill="currentColor" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
            );
        }
    }
    return $result;
}

function sp_intel_allowed_views( $views ) {
    $views[] = 'intelligence';
    return $views;
}

// ── Core KPI metric selection (per-site, super-admin controlled) ──────────────
// Which built-in CRM metrics appear on the KPI Dashboard AND feed the AI Summary.
//
// Two modes:
//  • AUTO (option unset — the default): hide any core metric whose table has no
//    data, show the rest. A brand-new client whose CRM lives in an external system
//    (e.g. HubSpot) just works — empty metrics never clutter the board or skew the
//    AI, and a metric appears automatically the moment it has data.
//  • EXPLICIT (option is an array): the super admin picked exactly which metrics to
//    show, regardless of data. Their choice always wins.

function sp_intel_core_kpi_keys() {
    return array(
        'contacts'  => 'Contacts',
        'companies' => 'Companies',
        'leads'     => 'Active Leads',
        'tasks'     => 'Tasks',
    );
}

// True if the metric's underlying table has at least one row (auto-mode signal).
function sp_intel_core_kpi_has_data( $key ) {
    static $cache = array();
    if ( isset( $cache[ $key ] ) ) return $cache[ $key ];
    global $wpdb;
    $map = array( 'contacts' => 'sp_contacts', 'companies' => 'sp_companies', 'leads' => 'sp_leads', 'tasks' => 'sp_tasks' );
    if ( ! isset( $map[ $key ] ) ) return true;
    $table = $wpdb->prefix . $map[ $key ];
    $has = false;
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) ) {
        $has = ( $wpdb->get_var( "SELECT 1 FROM $table LIMIT 1" ) !== null );
    }
    $cache[ $key ] = $has;
    return $has;
}

// True if the super admin has explicitly configured the metric selection (EXPLICIT
// mode) vs. leaving it on AUTO (option never saved).
function sp_intel_core_kpi_is_auto() {
    return ! is_array( get_option( 'sp_intel_core_kpis', null ) );
}

function sp_intel_core_kpi_enabled( $key ) {
    $opt = get_option( 'sp_intel_core_kpis', null );
    if ( is_array( $opt ) ) {
        return in_array( $key, $opt, true ); // EXPLICIT: super admin's choice wins
    }
    return sp_intel_core_kpi_has_data( $key );  // AUTO: show only metrics that have data
}

// ── Settings section (Super Admin only) ───────────────────────────────────────

function sp_intel_settings_section() {
    if ( ! function_exists( 'sp_is_super_admin' ) || ! sp_is_super_admin() ) return;
    $api_key = get_option( 'sp_anthropic_api_key', '' );
    ?>
    <div style="margin-top:16px;border:2px solid #f59e0b;border-radius:12px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#451a03,#78350f);padding:12px 20px;display:flex;align-items:center;gap:10px;">
            <svg viewBox="0 0 20 20" fill="#fbbf24" width="16" height="16"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
            <span style="font-size:.8rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#fbbf24;">Super Admin Only — AI &amp; Intelligence Keys</span>
        </div>
        <div class="sp-form-card" style="padding:20px 24px;background:#fff;">
            <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
                <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                <input type="hidden" name="sp_type" value="intel_settings">
                <input type="hidden" name="sp_id"   value="0">
                <h2 class="sp-section-heading" style="margin-top:0;">Intelligence Core — AI Settings</h2>
                <div class="sp-form-row">
                    <div class="sp-field">
                        <label>Anthropic API Key — KPI Dashboard AI Summary</label>
                        <input type="password" name="sp_anthropic_api_key" value="<?php echo esc_attr( $api_key ); ?>" autocomplete="new-password" style="font-family:monospace;font-size:12px;max-width:480px;">
                        <span class="sp-hint">Powers the <strong>AI Business Summary</strong> on the KPI Dashboard. Use an Anthropic key (<code>sk-ant-…</code>) from console.anthropic.com. If left blank, the summary falls back to the <strong>AI Assistant</strong> key (Settings → AI Integration) when that key is an Anthropic one.</span>
                    </div>
                </div>
                <div class="sp-field" style="margin-top:18px;">
                    <label>KPI Dashboard — Core Metrics</label>
                    <span class="sp-hint" style="display:block;margin-bottom:10px;">Which built-in CRM metrics show on the KPI Dashboard and feed the AI Summary. Custom-module metrics (Quotes, Service Requests) always show when their module is active.</span>
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;font-weight:600;text-transform:none;letter-spacing:0;color:#0f172a;">
                        <input type="checkbox" name="sp_intel_core_kpis_auto" value="1" id="sp-intel-kpi-auto" <?php checked( sp_intel_core_kpi_is_auto() ); ?>>
                        Auto — hide any metric that has no data <span style="font-weight:400;color:#64748b;">(recommended)</span>
                    </label>
                    <div id="sp-intel-kpi-manual" style="padding-left:26px;">
                        <span class="sp-hint" style="display:block;margin-bottom:8px;">Manual override — used only when Auto is off. Check exactly which metrics to show, regardless of data.</span>
                        <?php
                        $manual_opt = get_option( 'sp_intel_core_kpis', null );
                        foreach ( sp_intel_core_kpi_keys() as $ck => $clabel ) :
                            $box_checked = is_array( $manual_opt ) ? in_array( $ck, $manual_opt, true ) : sp_intel_core_kpi_has_data( $ck );
                        ?>
                            <label style="display:inline-flex;align-items:center;gap:6px;margin:0 18px 8px 0;font-weight:400;text-transform:none;letter-spacing:0;color:#334155;">
                                <input type="checkbox" class="sp-intel-kpi-box" name="sp_intel_core_kpis[]" value="<?php echo esc_attr( $ck ); ?>" <?php checked( $box_checked ); ?>>
                                <?php echo esc_html( $clabel ); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <script>
                    (function(){
                        var auto = document.getElementById('sp-intel-kpi-auto');
                        var wrap = document.getElementById('sp-intel-kpi-manual');
                        if ( ! auto || ! wrap ) return;
                        var boxes = wrap.querySelectorAll('.sp-intel-kpi-box');
                        function sync(){ boxes.forEach(function(b){ b.disabled = auto.checked; }); wrap.style.opacity = auto.checked ? '.5' : '1'; }
                        auto.addEventListener('change', sync); sync();
                    })();
                    </script>
                </div>
                <div class="sp-form-actions">
                    <button type="submit" class="sp-btn sp-btn-primary">Save AI Settings</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

function sp_intel_save_settings( $id ) {
    if ( ! function_exists( 'sp_is_super_admin' ) || ! sp_is_super_admin() ) {
        wp_redirect( home_url( '/sp-app/?view=settings' ) ); exit;
    }
    $key = isset( $_POST['sp_anthropic_api_key'] ) ? sanitize_text_field( trim( $_POST['sp_anthropic_api_key'] ) ) : '';
    if ( $key ) update_option( 'sp_anthropic_api_key', $key );

    // Core KPI metric selection. AUTO mode = delete the option (fall back to
    // hide-when-empty). EXPLICIT mode = save the array of checked metrics. Checkboxes
    // only POST checked values, so an absent field in explicit mode = "all unchecked".
    if ( ! empty( $_POST['sp_intel_core_kpis_auto'] ) ) {
        delete_option( 'sp_intel_core_kpis' );
    } else {
        $allowed = array_keys( sp_intel_core_kpi_keys() );
        $sel = ( isset( $_POST['sp_intel_core_kpis'] ) && is_array( $_POST['sp_intel_core_kpis'] ) )
            ? array_values( array_intersect( $allowed, array_map( 'sanitize_key', $_POST['sp_intel_core_kpis'] ) ) )
            : array();
        update_option( 'sp_intel_core_kpis', $sel );
    }

    wp_redirect( home_url( '/sp-app/?view=settings&saved=1' ) ); exit;
}

// ── AI key resolver ───────────────────────────────────────────────────────────
// The KPI AI Summary uses its own Anthropic key (sp_anthropic_api_key). When that
// is empty it falls back to the AI module's key (sp_ai_api_key) so a site only
// needs one Anthropic key configured for the summary to work — avoids the
// two-fields-look-alike trap on the Settings page. Primary always wins when set,
// so sites that deliberately run separate keys per feature are unaffected.
function sp_intel_get_api_key() {
    $key = trim( (string) get_option( 'sp_anthropic_api_key', '' ) );
    if ( $key !== '' ) return $key;
    return trim( (string) get_option( 'sp_ai_api_key', '' ) );
}

// ── AI Insights AJAX handler ──────────────────────────────────────────────────

function sp_intel_ajax_ai_insights() {
    if ( ! function_exists( 'sp_get_current_team_member' ) ) wp_send_json_error( 'not_auth' );
    $member = sp_get_current_team_member();
    $is_super = function_exists( 'sp_is_super_admin' ) && sp_is_super_admin();
    if ( ! $member && ! $is_super ) wp_send_json_error( 'not_auth' );

    $api_key = sp_intel_get_api_key();
    if ( ! $api_key ) wp_send_json_error( 'no_key' );

    $days = (int) ( isset( $_POST['days'] ) ? $_POST['days'] : 30 );
    if ( ! in_array( $days, array( 7, 30, 90 ) ) ) $days = 30;

    global $wpdb;
    $since = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
    $today = date( 'Y-m-d' );

    // Compile data snapshot
    $data = array();
    $data['period_days']   = $days;
    $data['contacts_total']= (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_contacts" );
    $data['contacts_new']  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_contacts WHERE created_at >= %s", $since ) );
    $data['leads_active']  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_leads WHERE status IN ('new','contacted','qualified')" );
    $data['leads_new']     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_leads WHERE created_at >= %s", $since ) );
    $data['leads_closed']  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_leads WHERE status='closed' AND updated_at >= %s", $since ) );
    $data['tasks_open']    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tasks WHERE status='open'" );
    $data['tasks_overdue'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tasks WHERE status='open' AND due_date IS NOT NULL AND due_date < %s", $today ) );
    $data['tasks_done']    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tasks WHERE status='done' AND created_at >= %s", $since ) );

    $platform_name = get_option( 'sp_platform_name', 'the business' );

    // Only include the core CRM baseline metrics that are enabled for this site, so
    // the AI doesn't reason off empty local zeros when the real data lives elsewhere
    // (e.g. SMTI → HubSpot). Custom-module lines come from sp_intel_summary_lines.
    $summary_lines = array( "Business performance data for {$platform_name} over the last {$days} days:" );
    if ( sp_intel_core_kpi_enabled( 'contacts' ) ) {
        $summary_lines[] = "- Contacts: {$data['contacts_total']} total, {$data['contacts_new']} new this period";
    }
    if ( sp_intel_core_kpi_enabled( 'leads' ) ) {
        $summary_lines[] = "- Active leads: {$data['leads_active']}, {$data['leads_new']} new, {$data['leads_closed']} closed";
    }
    if ( sp_intel_core_kpi_enabled( 'tasks' ) ) {
        $summary_lines[] = "- Tasks: {$data['tasks_open']} open, {$data['tasks_overdue']} overdue, {$data['tasks_done']} completed";
    }

    // Modules contribute their own KPI bullets — see sp_intel_default_summary_lines()
    // for core Tickets/Operations/Knowledge/Sales, and smti-sales/smti-service for
    // their own schemas (sp_smti_quotes / HubSpot).
    $summary_lines = apply_filters( 'sp_intel_summary_lines', $summary_lines, $days, $since, $today );

    $prompt = implode( "\n", $summary_lines );
    $prompt .= "\n\nAnalyze this business data and respond in this exact format (use plain hyphens for bullets, no special characters):\n\n**Status:** One sentence overall health assessment.\n\n**What's working:**\n- one positive signal per line. Max 2 lines.\n\n**Needs attention:**\n- one risk or gap per line, with the specific number and what it means for the business. Max 3 lines.\n\n**Next move:** One sentence — the single highest-leverage action right now.\n\nBe direct, specific, and sharp. No fluff.";

    $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
        'timeout' => 20,
        'headers' => array(
            'x-api-key'         => $api_key,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ),
        'body' => wp_json_encode( array(
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 500,
            'messages'   => array(
                array( 'role' => 'user', 'content' => $prompt ),
            ),
        ) ),
    ) );

    if ( is_wp_error( $response ) ) wp_send_json_error( 'api_error' );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    $text = $body['content'][0]['text'] ?? '';
    if ( ! $text ) wp_send_json_error( 'empty_response' );

    wp_send_json_success( array( 'insight' => $text ) );
}

// ── Default summary lines (core Tickets / Operations / Knowledge / Sales) ─────
// Registered as a low-priority (10) hook on 'sp_intel_summary_lines' so it still
// applies to installs running the core modules. SMTI and other client-specific
// modules register their own callbacks on the same filter instead of being
// special-cased here (see start-performance-smti-sales.php / start-performance-smti-service.php).

function sp_intel_default_summary_lines( $lines, $days, $since, $today ) {
    global $wpdb;

    if ( sp_is_addon_active( 'sp-tickets' ) && $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_tickets'" ) ) {
        $service_open     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tickets WHERE status IN ('open','in_progress')" );
        $service_urgent   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tickets WHERE priority='urgent' AND status IN ('open','in_progress')" );
        $service_resolved = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tickets WHERE status IN ('resolved','closed') AND updated_at >= %s", $since ) );
        $lines[] = "- Service tickets: {$service_open} open, {$service_urgent} urgent, {$service_resolved} resolved this period";
    }

    // Operations Core data
    if ( sp_is_addon_active( 'sp-operations' ) && $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_op_workflows'" ) ) {
        $workflows_total   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_op_workflows WHERE status='active'" );
        $workflows_running = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_op_instances WHERE status='active'" );
        $workflows_done    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_op_instances WHERE status='completed' AND updated_at >= %s", $since ) );
        $onboarding_active = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_ob_instances WHERE status='active'" );
        $onboarding_done   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_ob_instances WHERE status='completed' AND updated_at >= %s", $since ) );
        $lines[] = "- Operations: {$workflows_total} active workflows, {$workflows_running} running instances, {$workflows_done} completed this period, {$onboarding_active} onboardings in progress, {$onboarding_done} completed";
    }

    // Knowledge Core data
    if ( sp_is_addon_active( 'sp-knowledge' ) && $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_kb_articles'" ) ) {
        $kb_articles        = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_kb_articles WHERE status='published'" );
        $kb_courses         = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_kb_courses WHERE status='published'" );
        $kb_completions     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_kb_completions WHERE completed_at >= %s", $since ) );
        $kb_members_trained = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT member_id) FROM {$wpdb->prefix}sp_kb_completions WHERE completed_at >= %s", $since ) );
        $lines[] = "- Knowledge base: {$kb_articles} published articles, {$kb_courses} courses, {$kb_completions} lesson completions by {$kb_members_trained} team members this period";
    }

    // Sales Core data (estimates, proposals, contracts)
    if ( sp_is_addon_active( 'sp-sales' ) && $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_estimates'" ) ) {
        $estimates_open   = (int)   $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_estimates WHERE status IN ('draft','sent')" );
        $estimates_value  = (float) $wpdb->get_var( "SELECT SUM(total) FROM {$wpdb->prefix}sp_estimates WHERE status IN ('draft','sent')" );
        $proposals_open   = (int)   $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_proposals WHERE status IN ('draft','sent')" );
        $contracts_active = (int)   $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_contracts WHERE status='active'" );
        $lines[] = "- Sales pipeline: {$estimates_open} open estimates (\${$estimates_value}), {$proposals_open} proposals out, {$contracts_active} active contracts";
    }

    return $lines;
}
