<?php
/*
 * Plugin Name: Start Performance - SMTI Sales
 * Description: Sales quote builder for SMTI — distributor quotes, HubSpot deal sync, print output.
 * Version:     1.3.10
 * Author:      Richard Brashear / Start Performance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SP_SMTI_SALES_VERSION',    '1.3.10' );
define( 'SP_SMTI_SALES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// ── Boot ──────────────────────────────────────────────────────────────────────

add_action( 'plugins_loaded', 'sp_smti_sales_boot', 25 );

function sp_smti_sales_boot() {
    if ( ! function_exists( 'sp_register_view' ) ) {
        add_action( 'admin_notices', 'sp_smti_sales_dependency_notice' );
        return;
    }
    sp_smti_sales_register();
}

function sp_smti_sales_dependency_notice() {
    echo '<div class="notice notice-error"><p><strong>Start Performance — SMTI Sales</strong> requires the Start Performance core plugin.</p></div>';
}

// ── Registration ──────────────────────────────────────────────────────────────

function sp_smti_sales_register() {
    sp_register_addon( 'sp-smti-sales', array(
        'name'        => 'SMTI Sales',
        'version'     => SP_SMTI_SALES_VERSION,
        'description' => 'Distributor quote builder with HubSpot deal sync and print output.',
        'icon'        => '<path fill="currentColor" fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a1 1 0 01.707.293l4 4A1 1 0 0119 7v13a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm9 1.414V7h1.586L13 5.414zM7 11a1 1 0 011-1h6a1 1 0 110 2H8a1 1 0 01-1-1zm1 3a1 1 0 100 2h4a1 1 0 100-2H8z" clip-rule="evenodd"/>',
        'plugin_file' => plugin_basename( __FILE__ ),
        'core_slot'   => 'sales-core',
    ) );

    sp_register_view( 'smti-sales', SP_SMTI_SALES_PLUGIN_DIR . 'templates/views/smti-sales.php' );

    add_filter( 'sp_nav_items',     'sp_smti_sales_nav_items', 12 );
    add_filter( 'sp_allowed_views', 'sp_smti_sales_allowed_views' );
    add_action( 'sp_settings_sections', 'sp_smti_sales_settings_section' );
    add_action( 'sp_post_handler_smti_sales_settings',     'sp_smti_sales_save_settings' );
    add_action( 'sp_post_handler_smti_sales_api_settings', 'sp_smti_sales_save_api_settings' );

    // AJAX handlers (work for non-WP-logged-in SP users)
    add_action( 'wp_ajax_nopriv_sp_smti_sales_search',      'sp_smti_sales_ajax_search' );
    add_action( 'wp_ajax_sp_smti_sales_search',             'sp_smti_sales_ajax_search' );
    add_action( 'wp_ajax_nopriv_sp_smti_sales_contact',     'sp_smti_sales_ajax_contact' );
    add_action( 'wp_ajax_sp_smti_sales_contact',            'sp_smti_sales_ajax_contact' );
    add_action( 'wp_ajax_nopriv_sp_smti_sales_save_quote',  'sp_smti_sales_ajax_save_quote' );
    add_action( 'wp_ajax_sp_smti_sales_save_quote',         'sp_smti_sales_ajax_save_quote' );
    add_action( 'wp_ajax_nopriv_sp_smti_sales_load_quote',  'sp_smti_sales_ajax_load_quote' );
    add_action( 'wp_ajax_sp_smti_sales_load_quote',         'sp_smti_sales_ajax_load_quote' );
    add_action( 'wp_ajax_nopriv_sp_smti_sales_revisions',   'sp_smti_sales_ajax_revisions' );
    add_action( 'wp_ajax_sp_smti_sales_revisions',          'sp_smti_sales_ajax_revisions' );
    add_action( 'wp_ajax_nopriv_sp_smti_sales_compare',     'sp_smti_sales_ajax_compare' );
    add_action( 'wp_ajax_sp_smti_sales_compare',            'sp_smti_sales_ajax_compare' );
    add_action( 'wp_ajax_nopriv_sp_smti_sales_sync',        'sp_smti_sales_ajax_sync' );
    add_action( 'wp_ajax_sp_smti_sales_sync',               'sp_smti_sales_ajax_sync' );
    add_action( 'wp_ajax_nopriv_sp_smti_sales_quotes_list', 'sp_smti_sales_ajax_quotes_list' );
    add_action( 'wp_ajax_sp_smti_sales_quotes_list',        'sp_smti_sales_ajax_quotes_list' );
    add_action( 'wp_ajax_nopriv_sp_smti_sales_pipeline_deals', 'sp_smti_sales_ajax_pipeline_deals' );
    add_action( 'wp_ajax_sp_smti_sales_pipeline_deals',        'sp_smti_sales_ajax_pipeline_deals' );
    add_action( 'rest_api_init', 'sp_smti_sales_register_routes' );

    add_filter( 'sp_intel_summary_lines',   'sp_smti_sales_intel_summary_lines', 20, 4 );
    add_filter( 'sp_intel_kpi_cards',       'sp_smti_sales_intel_kpi_cards' );
    add_action( 'sp_intel_after_kpi',       'sp_smti_sales_intel_distributor_panel' );
    add_action( 'sp_dashboard_before_stats', 'sp_smti_sales_dashboard_stats' );
}

// ── Distributor scorecard (live HubSpot) ───────────────────────────────────────
// Aggregates open HubSpot deals by the smt_distributor_name property so the KPI
// Dashboard can show which distributors are driving the most quote volume/value.

function sp_smti_sales_get_distributor_scorecard() {
    $pat = get_option( 'sp_smti_sales_hs_pat', '' );
    if ( ! $pat ) return null;
    $pipeline = get_option( 'sp_smti_sales_pipeline_id', 'default' );
    $resp = sp_smti_sales_hs_call( 'POST', '/crm/v3/objects/deals/search', array(
        'filterGroups' => array( array( 'filters' => array(
            array( 'propertyName' => 'pipeline',  'operator' => 'EQ',     'value'  => $pipeline ),
            array( 'propertyName' => 'dealstage', 'operator' => 'NOT_IN', 'values' => array( 'closedwon', 'closedlost' ) ),
        ) ) ),
        'properties' => array( 'amount', 'smt_distributor_name' ),
        'limit'      => 100,
    ) );
    if ( is_wp_error( $resp ) || (int) $resp['code'] !== 200 ) return null;

    $by = array();
    foreach ( ( isset( $resp['data']['results'] ) ? $resp['data']['results'] : array() ) as $d ) {
        $p    = isset( $d['properties'] ) ? $d['properties'] : array();
        $name = isset( $p['smt_distributor_name'] ) ? trim( (string) $p['smt_distributor_name'] ) : '';
        if ( $name === '' ) $name = 'Unassigned';
        $amt  = (float) ( isset( $p['amount'] ) ? $p['amount'] : 0 );
        if ( ! isset( $by[ $name ] ) ) $by[ $name ] = array( 'count' => 0, 'value' => 0.0 );
        $by[ $name ]['count']++;
        $by[ $name ]['value'] += $amt;
    }
    // Sort by open pipeline value, descending (PHP 5.6-safe comparison).
    uasort( $by, function( $a, $b ) {
        if ( $a['value'] == $b['value'] ) return 0;
        return ( $a['value'] < $b['value'] ) ? 1 : -1;
    } );
    return $by;
}

function sp_smti_sales_intel_distributor_panel() {
    if ( function_exists( 'sp_is_view_hidden' ) && sp_is_view_hidden( 'smti-sales' ) ) return;
    $board = sp_smti_sales_get_distributor_scorecard();
    if ( empty( $board ) ) return;
    $rank = 0;
    ?>
    <div class="sp-intel-section" style="margin-bottom:24px;">
        <h3 style="font-size:1.15rem;font-weight:800;letter-spacing:.01em;color:#0f172a;text-transform:none;margin-bottom:16px;">Top Distributors — Open Pipeline</h3>
        <?php foreach ( $board as $name => $s ) : $rank++; if ( $rank > 12 ) break; ?>
            <div class="sp-intel-stat-row">
                <span style="display:flex;align-items:center;gap:10px;">
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:var(--sp-primary,#2563eb);color:#fff;font-size:.72rem;font-weight:700;flex-shrink:0;"><?php echo $rank; ?></span>
                    <?php echo esc_html( $name ); ?>
                </span>
                <span style="font-weight:600;white-space:nowrap;">
                    <?php echo (int) $s['count']; ?> <span style="color:var(--sp-muted,#6b7280);font-weight:400;">quote<?php echo (int) $s['count'] !== 1 ? 's' : ''; ?></span>
                    &nbsp;·&nbsp; $<?php echo number_format( $s['value'], 0 ); ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

// Open quotes that have sat in the pipeline longer than $age_days, grouped by
// distributor — fed to the AI summary so it can recommend specific follow-ups
// ("Distributor X has N quotes aging past 30 days worth $Y — initiate follow-up").
function sp_smti_sales_get_aging_by_distributor( $age_days = 30 ) {
    $pat = get_option( 'sp_smti_sales_hs_pat', '' );
    if ( ! $pat ) return null;
    $pipeline  = get_option( 'sp_smti_sales_pipeline_id', 'default' );
    $cutoff_ms = ( time() - (int) $age_days * DAY_IN_SECONDS ) * 1000;
    $resp = sp_smti_sales_hs_call( 'POST', '/crm/v3/objects/deals/search', array(
        'filterGroups' => array( array( 'filters' => array(
            array( 'propertyName' => 'pipeline',   'operator' => 'EQ',     'value'  => $pipeline ),
            array( 'propertyName' => 'dealstage',  'operator' => 'NOT_IN', 'values' => array( 'closedwon', 'closedlost' ) ),
            array( 'propertyName' => 'createdate', 'operator' => 'LTE',    'value'  => (string) $cutoff_ms ),
        ) ) ),
        'properties' => array( 'amount', 'smt_distributor_name' ),
        'limit'      => 100,
    ) );
    if ( is_wp_error( $resp ) || (int) $resp['code'] !== 200 ) return null;
    $by = array();
    foreach ( ( isset( $resp['data']['results'] ) ? $resp['data']['results'] : array() ) as $d ) {
        $p    = isset( $d['properties'] ) ? $d['properties'] : array();
        $name = isset( $p['smt_distributor_name'] ) ? trim( (string) $p['smt_distributor_name'] ) : '';
        if ( $name === '' ) $name = 'Unassigned';
        $amt  = (float) ( isset( $p['amount'] ) ? $p['amount'] : 0 );
        if ( ! isset( $by[ $name ] ) ) $by[ $name ] = array( 'count' => 0, 'value' => 0.0 );
        $by[ $name ]['count']++;
        $by[ $name ]['value'] += $amt;
    }
    uasort( $by, function( $a, $b ) {
        if ( $a['value'] == $b['value'] ) return 0;
        return ( $a['value'] < $b['value'] ) ? 1 : -1;
    } );
    return $by;
}

// ── Intelligence Core KPI cards (live HubSpot) ─────────────────────────────────
// Feeds the KPI Dashboard the SAME live-HubSpot pipeline numbers the main dashboard
// widget and AI summary use — so the KPI page can't drift from reality the way the
// old local-sp_smti_quotes reads did.

function sp_smti_sales_intel_kpi_cards( $cards ) {
    $open = sp_smti_sales_get_open_pipeline();
    if ( $open === null ) return $cards;
    $cards[] = array(
        'label'  => 'Open Quotes',
        'value'  => number_format( $open['count'] ),
        'sub'    => 'live HubSpot pipeline',
        'accent' => 'accent',
    );
    $cards[] = array(
        'label'  => 'Pipeline Value',
        'value'  => '$' . number_format( $open['value'], 0 ),
        'sub'    => 'open deals, live HubSpot',
        'accent' => 'green',
    );
    return $cards;
}

// ── HubSpot pipeline data ──────────────────────────────────────────────────────
// The local sp_smti_quotes table only holds quotes built through this tool's own
// Quote Builder — it is not a mirror of HubSpot's actual deal pipeline (other deals
// get created directly in HubSpot or via the legacy SMTI system). Both the dashboard
// card and the AI summary read live from HubSpot instead so the numbers match reality.

function sp_smti_sales_get_open_pipeline() {
    $pat = get_option( 'sp_smti_sales_hs_pat', '' );
    if ( ! $pat ) return null;
    $pipeline = get_option( 'sp_smti_sales_pipeline_id', 'default' );
    $resp = sp_smti_sales_hs_call( 'POST', '/crm/v3/objects/deals/search', array(
        'filterGroups' => array( array( 'filters' => array(
            array( 'propertyName' => 'pipeline',  'operator' => 'EQ',     'value'  => $pipeline ),
            array( 'propertyName' => 'dealstage', 'operator' => 'NOT_IN', 'values' => array( 'closedwon', 'closedlost' ) ),
        ) ) ),
        'properties' => array( 'amount' ),
        'limit'      => 100,
    ) );
    if ( is_wp_error( $resp ) || (int) $resp['code'] !== 200 ) return null;
    $count = isset( $resp['data']['total'] ) ? (int) $resp['data']['total'] : 0;
    $value = 0.0;
    foreach ( ( isset( $resp['data']['results'] ) ? $resp['data']['results'] : array() ) as $d ) {
        $value += (float) ( isset( $d['properties']['amount'] ) ? $d['properties']['amount'] : 0 );
    }
    return array( 'count' => $count, 'value' => $value );
}

// Same open-pipeline filter as sp_smti_sales_get_open_pipeline(), but returns the
// individual deals (name, amount, stage, created date) for the dashboard modal
// instead of just the aggregate count/value.
function sp_smti_sales_get_open_pipeline_deals() {
    $pat = get_option( 'sp_smti_sales_hs_pat', '' );
    if ( ! $pat ) return null;
    $pipeline = get_option( 'sp_smti_sales_pipeline_id', 'default' );
    $resp = sp_smti_sales_hs_call( 'POST', '/crm/v3/objects/deals/search', array(
        'filterGroups' => array( array( 'filters' => array(
            array( 'propertyName' => 'pipeline',  'operator' => 'EQ',     'value'  => $pipeline ),
            array( 'propertyName' => 'dealstage', 'operator' => 'NOT_IN', 'values' => array( 'closedwon', 'closedlost' ) ),
        ) ) ),
        'properties' => array( 'dealname', 'amount', 'dealstage', 'createdate' ),
        'sorts'      => array( array( 'propertyName' => 'amount', 'direction' => 'DESCENDING' ) ),
        'limit'      => 100,
    ) );
    if ( is_wp_error( $resp ) || (int) $resp['code'] !== 200 ) return null;

    $stage_labels = sp_smti_sales_stage_labels( $pipeline );
    $deals = array();
    foreach ( ( isset( $resp['data']['results'] ) ? $resp['data']['results'] : array() ) as $d ) {
        $p = isset( $d['properties'] ) ? $d['properties'] : array();
        $stage_id = isset( $p['dealstage'] ) ? $p['dealstage'] : '';
        $deals[] = array(
            'name'    => isset( $p['dealname'] ) ? $p['dealname'] : '(untitled deal)',
            'amount'  => (float) ( isset( $p['amount'] ) ? $p['amount'] : 0 ),
            'stage'   => isset( $stage_labels[ $stage_id ] ) ? $stage_labels[ $stage_id ] : $stage_id,
            'created' => isset( $p['createdate'] ) ? substr( $p['createdate'], 0, 10 ) : '',
        );
    }
    return $deals;
}

function sp_smti_sales_stage_labels( $pipeline ) {
    $resp = sp_smti_sales_hs_call( 'GET', '/crm/v3/pipelines/deals/' . rawurlencode( $pipeline ) );
    if ( is_wp_error( $resp ) || (int) $resp['code'] !== 200 ) return array();
    $map = array();
    foreach ( ( isset( $resp['data']['stages'] ) ? $resp['data']['stages'] : array() ) as $stage ) {
        if ( ! empty( $stage['id'] ) ) {
            $map[ (string) $stage['id'] ] = isset( $stage['label'] ) ? (string) $stage['label'] : (string) $stage['id'];
        }
    }
    return $map;
}

// ── Dashboard widget ───────────────────────────────────────────────────────────

function sp_smti_sales_dashboard_stats() {
    if ( sp_is_view_hidden( 'smti-sales' ) ) return;
    $open = sp_smti_sales_get_open_pipeline();
    if ( $open === null ) return;
    $nonce = wp_create_nonce( 'sp_smti_sales_pipeline_deals' );
    ?>
    <div class="sp-stats sp-stats-2">
        <div class="sp-stat-card" id="sp-smti-sales-open-quotes-card" style="border-top-color:#0ea5e9;cursor:pointer" title="Click to view open quotes">
            <div class="sp-stat-value"><?php echo number_format( $open['count'] ); ?></div>
            <div class="sp-stat-label">Open Quotes (HubSpot)</div>
        </div>
        <div class="sp-stat-card" style="border-top-color:#10b981">
            <div class="sp-stat-value">$<?php echo number_format( $open['value'] ); ?></div>
            <div class="sp-stat-label">Open Pipeline Value</div>
        </div>
    </div>

    <div id="sp-smti-sales-pipeline-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:9999;align-items:center;justify-content:center;padding:24px">
        <div style="background:#fff;border-radius:14px;max-width:720px;width:100%;max-height:80vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3)">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #e2e8f0">
                <h2 style="margin:0;font-size:16px;font-weight:700;color:#1e293b">Open Quotes — HubSpot Pipeline</h2>
                <button type="button" id="sp-smti-sales-pipeline-close" style="background:none;border:none;font-size:20px;line-height:1;cursor:pointer;color:#94a3b8">&times;</button>
            </div>
            <div style="overflow-y:auto;padding:0 22px">
                <table class="sp-table" style="width:100%">
                    <thead>
                        <tr><th>Deal</th><th>Amount</th><th>Stage</th><th>Created</th></tr>
                    </thead>
                    <tbody id="sp-smti-sales-pipeline-tbody">
                        <tr><td colspan="4" class="sp-muted" style="padding:20px 0">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
            <div style="padding:14px 22px;border-top:1px solid #e2e8f0;text-align:right">
                <button type="button" id="sp-smti-sales-pipeline-close-btn" class="sp-btn sp-btn-ghost sp-btn-sm">Close</button>
            </div>
        </div>
    </div>

    <script>
    (function(){
        var card   = document.getElementById('sp-smti-sales-open-quotes-card');
        var modal  = document.getElementById('sp-smti-sales-pipeline-modal');
        var tbody  = document.getElementById('sp-smti-sales-pipeline-tbody');
        if ( ! card || ! modal ) return;
        var loaded = false;

        function esc(s){ return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){ return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]; }); }
        function closeModal(){ modal.style.display = 'none'; }
        document.getElementById('sp-smti-sales-pipeline-close').addEventListener('click', closeModal);
        document.getElementById('sp-smti-sales-pipeline-close-btn').addEventListener('click', closeModal);
        modal.addEventListener('click', function(e){ if ( e.target === modal ) closeModal(); });

        card.addEventListener('click', function(){
            modal.style.display = 'flex';
            if ( loaded ) return;
            loaded = true;
            var fd = new FormData();
            fd.append('action', 'sp_smti_sales_pipeline_deals');
            fd.append('nonce', <?php echo wp_json_encode( $nonce ); ?>);
            fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, { method:'POST', body:fd })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    if ( ! data.success || ! data.data.deals || ! data.data.deals.length ) {
                        tbody.innerHTML = '<tr><td colspan="4" class="sp-muted" style="padding:20px 0">No open quotes found.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = data.data.deals.map(function(d){
                        return '<tr><td>' + esc(d.name) + '</td><td>$' + Number(d.amount).toLocaleString() + '</td><td>' + esc(d.stage) + '</td><td>' + esc(d.created) + '</td></tr>';
                    }).join('');
                })
                .catch(function(){
                    loaded = false;
                    tbody.innerHTML = '<tr><td colspan="4" class="sp-muted" style="padding:20px 0">Failed to load. Try again.</td></tr>';
                });
        });
    })();
    </script>
    <?php
}

// ── AJAX: pipeline deal list (for the dashboard modal) ─────────────────────────

function sp_smti_sales_ajax_pipeline_deals() {
    if ( ! check_ajax_referer( 'sp_smti_sales_pipeline_deals', 'nonce', false ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }
    sp_smti_sales_auth_check();
    $deals = sp_smti_sales_get_open_pipeline_deals();
    if ( $deals === null ) {
        wp_send_json_error( 'Could not reach HubSpot' );
    }
    wp_send_json_success( array( 'deals' => $deals ) );
}

// ── Intelligence Core KPI provider ─────────────────────────────────────────────

function sp_smti_sales_intel_summary_lines( $lines, $days, $since, $today ) {
    $open = sp_smti_sales_get_open_pipeline();
    if ( $open === null ) return $lines;

    $pipeline = get_option( 'sp_smti_sales_pipeline_id', 'default' );
    $since_ms = strtotime( $since ) * 1000;

    $won_count = null; $won_value = 0.0;
    $won_resp = sp_smti_sales_hs_call( 'POST', '/crm/v3/objects/deals/search', array(
        'filterGroups' => array( array( 'filters' => array(
            array( 'propertyName' => 'pipeline',  'operator' => 'EQ',  'value' => $pipeline ),
            array( 'propertyName' => 'dealstage', 'operator' => 'EQ',  'value' => 'closedwon' ),
            array( 'propertyName' => 'closedate', 'operator' => 'GTE', 'value' => (string) $since_ms ),
        ) ) ),
        'properties' => array( 'amount' ),
        'limit'      => 100,
    ) );
    if ( ! is_wp_error( $won_resp ) && (int) $won_resp['code'] === 200 ) {
        $won_count = isset( $won_resp['data']['total'] ) ? (int) $won_resp['data']['total'] : 0;
        foreach ( ( isset( $won_resp['data']['results'] ) ? $won_resp['data']['results'] : array() ) as $d ) {
            $won_value += (float) ( isset( $d['properties']['amount'] ) ? $d['properties']['amount'] : 0 );
        }
    }

    $new_count = null;
    $new_resp = sp_smti_sales_hs_call( 'POST', '/crm/v3/objects/deals/search', array(
        'filterGroups' => array( array( 'filters' => array(
            array( 'propertyName' => 'pipeline',   'operator' => 'EQ',  'value' => $pipeline ),
            array( 'propertyName' => 'createdate', 'operator' => 'GTE', 'value' => (string) $since_ms ),
        ) ) ),
        'limit' => 1,
    ) );
    if ( ! is_wp_error( $new_resp ) && (int) $new_resp['code'] === 200 && isset( $new_resp['data']['total'] ) ) {
        $new_count = (int) $new_resp['data']['total'];
    }

    $line = "- Quotes (HubSpot pipeline): {$open['count']} open (\$" . number_format( $open['value'], 2 ) . ')';
    if ( $new_count !== null ) $line .= ", {$new_count} new in the last {$days} days";
    if ( $won_count !== null ) $line .= ", {$won_count} won (\$" . number_format( $won_value, 2 ) . ')';
    $lines[] = $line;

    // Aging pipeline by distributor — gives the AI concrete follow-up targets.
    $aging = sp_smti_sales_get_aging_by_distributor( 30 );
    if ( ! empty( $aging ) ) {
        $parts = array(); $n = 0;
        foreach ( $aging as $dname => $a ) {
            $parts[] = $dname . ' (' . $a['count'] . ' quote' . ( $a['count'] != 1 ? 's' : '' ) . ', $' . number_format( $a['value'], 0 ) . ')';
            if ( ++$n >= 6 ) break;
        }
        $lines[] = "- Aging open quotes still unclosed after 30+ days, by distributor (follow-up candidates): " . implode( '; ', $parts );
    }

    return $lines;
}

// ── Activation ────────────────────────────────────────────────────────────────

register_activation_hook( __FILE__, 'sp_smti_sales_activate' );
add_action( 'sp_activate', 'sp_smti_sales_create_tables' );

function sp_smti_sales_activate() {
    sp_smti_sales_create_tables();
}

function sp_smti_sales_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_smti_products (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  product_code varchar(50) NOT NULL DEFAULT '',
  product_title varchar(255) NOT NULL DEFAULT '',
  category varchar(100) NOT NULL DEFAULT '',
  product_description text,
  unit_price decimal(10,4) NOT NULL DEFAULT 0.0000,
  dealer_price decimal(10,4) NOT NULL DEFAULT 0.0000,
  location varchar(10) NOT NULL DEFAULT 'us',
  active tinyint(1) NOT NULL DEFAULT 1,
  allow_discount tinyint(1) NOT NULL DEFAULT 1,
  max_discount_percent int(11) NOT NULL DEFAULT 10,
  collateral_file varchar(500) NOT NULL DEFAULT '',
  sort_order int(11) NOT NULL DEFAULT 0,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY product_code (product_code),
  KEY location (location)
) $charset;" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_smti_distributors (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  slug varchar(100) NOT NULL DEFAULT '',
  name varchar(255) NOT NULL DEFAULT '',
  company varchar(255) NOT NULL DEFAULT '',
  title varchar(255) NOT NULL DEFAULT '',
  phone varchar(50) NOT NULL DEFAULT '',
  email varchar(100) NOT NULL DEFAULT '',
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY slug (slug)
) $charset;" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_smti_quote_revisions (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  deal_id varchar(50) NOT NULL DEFAULT '',
  quote_number varchar(50) NOT NULL DEFAULT '',
  revision_number int(11) NOT NULL DEFAULT 1,
  quote_status varchar(50) NOT NULL DEFAULT 'Draft',
  created_by varchar(100) NOT NULL DEFAULT '',
  quote_json longtext,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY deal_id (deal_id),
  KEY quote_number (quote_number)
) $charset;" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_smti_quotes (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  quote_number varchar(50) NOT NULL DEFAULT '',
  deal_id varchar(50) NOT NULL DEFAULT '',
  contact_id varchar(50) NOT NULL DEFAULT '',
  customer_name varchar(255) NOT NULL DEFAULT '',
  company_name varchar(255) NOT NULL DEFAULT '',
  pricing_region varchar(10) NOT NULL DEFAULT 'us',
  subtotal decimal(12,2) NOT NULL DEFAULT 0.00,
  discount_amount decimal(12,2) NOT NULL DEFAULT 0.00,
  freight_amount decimal(12,2) NOT NULL DEFAULT 0.00,
  total decimal(12,2) NOT NULL DEFAULT 0.00,
  status varchar(30) NOT NULL DEFAULT 'Draft',
  created_by varchar(100) NOT NULL DEFAULT '',
  quote_json longtext,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY quote_number (quote_number),
  KEY deal_id (deal_id),
  KEY status (status),
  KEY updated_at (updated_at)
) $charset;" );
}

// ── REST API routes ───────────────────────────────────────────────────────────

function sp_smti_sales_register_routes() {
    $ns = 'smti-sales/v1';
    $routes = array(
        array( 'search-contacts',         'GET',  'sp_smti_sales_rest_search_contacts' ),
        array( 'get-contact-details',     'GET',  'sp_smti_sales_rest_get_contact'     ),
        array( 'create-deal',             'POST', 'sp_smti_sales_rest_create_deal'     ),
        array( 'get-deal-quote',          'GET',  'sp_smti_sales_rest_get_deal_quote'  ),
        array( 'get-quote-revisions',     'GET',  'sp_smti_sales_rest_get_revisions'   ),
        array( 'get-quote-revision',      'GET',  'sp_smti_sales_rest_get_revision'    ),
        array( 'get-quote-revision-compare','GET',   'sp_smti_sales_rest_compare_revisions'),
        array( 'delete-local-quote',        'DELETE', 'sp_smti_sales_rest_delete_local'    ),
    );
    foreach ( $routes as $r ) {
        register_rest_route( $ns, '/' . $r[0], array(
            'methods'             => $r[1],
            'callback'            => $r[2],
            'permission_callback' => '__return_true',
        ) );
    }
}

// Authorized for the quote-builder REST endpoints: a registered team member OR a
// super admin. Super admins authenticate via the sp_vendor_auth cookie and are NOT
// team-member records, so the plain team-member check 403'd them on every call
// (search-contacts, create-deal, revisions…). Mirrors sp_ai_authed().
function sp_smti_sales_rest_authed() {
    if ( function_exists( 'sp_get_current_team_member' ) && sp_get_current_team_member() ) return true;
    return function_exists( 'sp_is_super_admin' ) && sp_is_super_admin();
}

function sp_smti_sales_hs_call( $method, $path, $body = null ) {
    $pat = get_option( 'sp_smti_sales_hs_pat', '' );
    if ( ! $pat ) {
        return new WP_Error( 'no_pat', 'HubSpot Private App Token not set. Add it in SP Settings → SMTI Sales Settings.' );
    }
    $args = array(
        'method'  => $method,
        'timeout' => 20,
        'headers' => array(
            'Authorization' => 'Bearer ' . $pat,
            'Content-Type'  => 'application/json',
        ),
    );
    if ( $body !== null ) {
        $args['body'] = json_encode( $body );
    }
    $resp = wp_remote_request( 'https://api.hubapi.com' . $path, $args );
    if ( is_wp_error( $resp ) ) {
        return $resp;
    }
    return array(
        'code' => wp_remote_retrieve_response_code( $resp ),
        'data' => json_decode( wp_remote_retrieve_body( $resp ), true ),
    );
}

function sp_smti_sales_generate_quote_number() {
    $ym  = date( 'Ym' );
    $key = 'sp_smti_sales_qctr_' . $ym;
    $n   = (int) get_option( $key, 0 ) + 1;
    update_option( $key, $n, false );
    return 'SMTI-' . $ym . '-' . str_pad( $n, 4, '0', STR_PAD_LEFT );
}

function sp_smti_sales_calc_totals( $data ) {
    $sub = 0; $disc = 0; $freight = 0;
    if ( ! is_array( $data ) || ! isset( $data['items'] ) ) {
        return array( 'subtotal' => 0, 'discount' => 0, 'freight' => 0, 'total' => 0 );
    }
    foreach ( $data['items'] as $item ) {
        if ( ! empty( $item['is_freight'] ) ) {
            $freight += (float) ( isset( $item['unit_price'] ) ? $item['unit_price'] : 0 );
            continue;
        }
        $qty = (int) ( isset( $item['quantity'] ) ? $item['quantity'] : 1 );
        $up  = (float) ( isset( $item['unit_price'] ) ? $item['unit_price'] : 0 );
        $da  = (float) ( isset( $item['item_discount_amount'] ) ? $item['item_discount_amount'] : 0 );
        $sub  += $qty * $up;
        $disc += $da;
    }
    return array(
        'subtotal' => $sub,
        'discount' => $disc,
        'freight'  => $freight,
        'total'    => ( $sub - $disc ) + $freight,
    );
}

// search-contacts
function sp_smti_sales_rest_search_contacts( $request ) {
    if ( ! sp_smti_sales_rest_authed() ) {
        return new WP_REST_Response( array( 'results' => array() ), 403 );
    }
    $q = sanitize_text_field( $request->get_param( 'q' ) );
    if ( ! $q || strlen( $q ) < 2 ) {
        return rest_ensure_response( array( 'results' => array() ) );
    }

    $props = array( 'firstname', 'lastname', 'email', 'phone', 'company', 'address', 'city', 'state', 'zip' );

    // Run full-text query search (name, email, phone, company)
    $r1 = sp_smti_sales_hs_call( 'POST', '/crm/v3/objects/contacts/search', array(
        'query'      => $q,
        'limit'      => 10,
        'properties' => $props,
    ) );

    // Run address CONTAINS_TOKEN search in parallel via a second call
    $r2 = sp_smti_sales_hs_call( 'POST', '/crm/v3/objects/contacts/search', array(
        'filterGroups' => array(
            array( 'filters' => array( array( 'propertyName' => 'address', 'operator' => 'CONTAINS_TOKEN', 'value' => $q ) ) ),
            array( 'filters' => array( array( 'propertyName' => 'city',    'operator' => 'CONTAINS_TOKEN', 'value' => $q ) ) ),
        ),
        'limit'      => 10,
        'properties' => $props,
    ) );

    // Merge and deduplicate by contact ID
    $seen = array();
    $out  = array();

    $raw = array();
    if ( ! is_wp_error( $r1 ) && isset( $r1['data']['results'] ) ) {
        $raw = array_merge( $raw, $r1['data']['results'] );
    }
    if ( ! is_wp_error( $r2 ) && isset( $r2['data']['results'] ) ) {
        $raw = array_merge( $raw, $r2['data']['results'] );
    }

    foreach ( $raw as $c ) {
        $cid = $c['id'];
        if ( isset( $seen[ $cid ] ) ) continue;
        $seen[ $cid ] = true;
        $p     = isset( $c['properties'] ) ? $c['properties'] : array();
        $first = isset( $p['firstname'] ) ? $p['firstname'] : '';
        $last  = isset( $p['lastname'] )  ? $p['lastname']  : '';
        $email = isset( $p['email'] )     ? $p['email']     : '';
        $co    = isset( $p['company'] )   ? $p['company']   : '';
        $addr  = isset( $p['address'] )   ? trim( $p['address'] )   : '';
        $city  = isset( $p['city'] )      ? trim( $p['city'] )      : '';
        $state = isset( $p['state'] )     ? trim( $p['state'] )     : '';
        $zip   = isset( $p['zip'] )       ? trim( $p['zip'] )       : '';
        $name  = trim( $first . ' ' . $last );

        // Build address line: "115 Dave Lyle Blvd S — Rock Hill, SC 29732"
        $city_state_zip = trim( $city . ( $state ? ', ' . $state : '' ) . ( $zip ? ' ' . $zip : '' ) );
        if ( $addr && $city_state_zip ) {
            $label = $addr . ' — ' . $city_state_zip;
        } elseif ( $addr ) {
            $label = $addr;
        } else {
            $label = ( $name ? $name : $email ) . ( $co ? ' — ' . $co : '' );
        }

        $out[] = array(
            'contact_id'      => $cid,
            'label'           => $label ? $label : 'Unknown',
            'name'            => $name,
            'email'           => $email,
            'company'         => $co,
            'account_name'    => $co,
            'address'         => $addr,
            'city'            => $city,
            'state'           => $state,
            'zip'             => $zip,
            'city_state_zip'  => $city_state_zip,
        );
    }
    return rest_ensure_response( array( 'results' => $out ) );
}

// get-contact-details
function sp_smti_sales_rest_get_contact( $request ) {
    if ( ! sp_smti_sales_rest_authed() ) {
        return new WP_REST_Response( array( 'found' => false ), 403 );
    }
    $id = sanitize_text_field( $request->get_param( 'contact_id' ) );
    if ( ! $id ) {
        return rest_ensure_response( array( 'found' => false, 'message' => 'No contact ID' ) );
    }
    $props  = 'firstname,lastname,email,phone,mobilephone,company,address,city,state,zip,country';
    $result = sp_smti_sales_hs_call( 'GET', '/crm/v3/objects/contacts/' . rawurlencode( $id ) . '?properties=' . $props );
    if ( is_wp_error( $result ) || (int) $result['code'] !== 200 ) {
        return rest_ensure_response( array( 'found' => false, 'message' => 'Contact not found' ) );
    }
    $p = isset( $result['data']['properties'] ) ? $result['data']['properties'] : array();
    $g = function( $k ) use ( $p ) { return isset( $p[ $k ] ) ? (string) $p[ $k ] : ''; };
    return rest_ensure_response( array(
        'found'               => true,
        'contact_id'          => $id,
        'customer_first_name' => $g( 'firstname' ),
        'customer_last_name'  => $g( 'lastname' ),
        'customer_email'      => $g( 'email' ),
        'customer_phone'      => $g( 'phone' ) ? $g( 'phone' ) : $g( 'mobilephone' ),
        'account_name'        => $g( 'company' ),
        'company_name'        => $g( 'company' ),
        'address1'            => $g( 'address' ),
        'city'                => $g( 'city' ),
        'state'               => $g( 'state' ),
        'postal_code'         => $g( 'zip' ),
        'country'             => $g( 'country' ),
    ) );
}

// create-deal
function sp_smti_sales_rest_create_deal( $request ) {
    if ( ! sp_smti_sales_rest_authed() ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => 'Not authorized' ), 403 );
    }
    global $wpdb;
    $body = $request->get_json_params();
    if ( ! $body ) {
        return rest_ensure_response( array( 'success' => false, 'message' => 'No data received' ) );
    }
    $g = function( $k, $d = '' ) use ( $body ) { return isset( $body[ $k ] ) ? $body[ $k ] : $d; };
    $current_deal_id = trim( (string) $g( 'current_deal_id' ) );
    $contact_id      = trim( (string) $g( 'contact_id' ) );
    $email           = sanitize_email( (string) $g( 'customer_email' ) );
    $account_name    = sanitize_text_field( (string) $g( 'account_name' ) );
    $was_update      = false;
    $contact_created = false;
    $contact_was_found_by_email = false;
    $company_created = false;
    $company_was_found_by_name  = false;

    // Build contact props from form data (used for both create and update)
    $contact_props = array_filter( array(
        'firstname' => sanitize_text_field( (string) $g( 'customer_first_name' ) ),
        'lastname'  => sanitize_text_field( (string) $g( 'customer_last_name' ) ),
        'email'     => $email,
        'phone'     => sanitize_text_field( (string) $g( 'customer_phone' ) ),
        'company'   => $account_name,
        'address'   => sanitize_text_field( (string) $g( 'address1' ) ),
        'city'      => sanitize_text_field( (string) $g( 'city' ) ),
        'state'     => sanitize_text_field( (string) $g( 'state' ) ),
        'zip'       => sanitize_text_field( (string) $g( 'postal_code' ) ),
        'country'   => sanitize_text_field( (string) $g( 'country' ) ),
    ) );

    // Find or create contact
    if ( ! $contact_id && $email ) {
        $sr = sp_smti_sales_hs_call( 'POST', '/crm/v3/objects/contacts/search', array(
            'filterGroups' => array( array( 'filters' => array( array(
                'propertyName' => 'email', 'operator' => 'EQ', 'value' => $email,
            ) ) ) ),
            'limit' => 1, 'properties' => array( 'email' ),
        ) );
        if ( ! is_wp_error( $sr ) && (int) $sr['code'] === 200 && ! empty( $sr['data']['results'][0]['id'] ) ) {
            $contact_id = $sr['data']['results'][0]['id'];
            $contact_was_found_by_email = true;
        }
    }
    if ( $contact_id ) {
        // Update existing contact with current form data
        sp_smti_sales_hs_call( 'PATCH', '/crm/v3/objects/contacts/' . rawurlencode( $contact_id ), array( 'properties' => $contact_props ) );
    } else {
        // Create new contact
        $cr = sp_smti_sales_hs_call( 'POST', '/crm/v3/objects/contacts', array( 'properties' => $contact_props ) );
        if ( ! is_wp_error( $cr ) && in_array( (int) $cr['code'], array( 200, 201 ) ) && ! empty( $cr['data']['id'] ) ) {
            $contact_id = $cr['data']['id'];
            $contact_created = true;
        }
    }

    // Find or create company
    $company_id = '';
    if ( $account_name ) {
        $cs = sp_smti_sales_hs_call( 'POST', '/crm/v3/objects/companies/search', array(
            'filterGroups' => array( array( 'filters' => array( array(
                'propertyName' => 'name', 'operator' => 'EQ', 'value' => $account_name,
            ) ) ) ),
            'limit' => 1,
        ) );
        if ( ! is_wp_error( $cs ) && (int) $cs['code'] === 200 && ! empty( $cs['data']['results'][0]['id'] ) ) {
            $company_id = $cs['data']['results'][0]['id'];
            $company_was_found_by_name = true;
        }
        if ( ! $company_id ) {
            $cc = sp_smti_sales_hs_call( 'POST', '/crm/v3/objects/companies', array(
                'properties' => array( 'name' => $account_name ),
            ) );
            if ( ! is_wp_error( $cc ) && in_array( (int) $cc['code'], array( 200, 201 ) ) && ! empty( $cc['data']['id'] ) ) {
                $company_id = $cc['data']['id'];
                $company_created = true;
            }
        }
    }

    // Build line item summary for deal properties
    $products    = isset( $body['products'] ) && is_array( $body['products'] ) ? $body['products'] : array();
    $line_items  = array();
    foreach ( $products as $p ) {
        $qty   = isset( $p['qty'] )   ? (int) $p['qty']        : 1;
        $code  = isset( $p['code'] )  ? $p['code']             : '';
        $title = isset( $p['title'] ) ? $p['title']            : '';
        $price = isset( $p['price'] ) ? number_format( (float) $p['price'], 2 ) : '0.00';
        $line_items[] = $qty . 'x ' . $code . ' ' . $title . ' @ $' . $price;
    }
    $distributor_name    = sanitize_text_field( (string) $g( 'distributor_company' ) );
    $distributor_rep     = trim( sanitize_text_field( (string) $g( 'distributor_first_name' ) ) . ' ' . sanitize_text_field( (string) $g( 'distributor_last_name' ) ) );
    $pricing_region      = strtoupper( sanitize_key( (string) $g( 'pricing_region', 'us' ) ) );
    $subtotal            = (float) $g( 'subtotal', 0 );
    $discount_amount     = (float) $g( 'discount_amount', 0 );
    $freight_amount      = (float) $g( 'freight_amount', 0 );
    $total               = (float) $g( 'total', 0 );
    $notes_field         = sanitize_textarea_field( (string) $g( 'notes' ) );
    $shipping_method     = sanitize_text_field( (string) $g( 'shipping_method' ) );
    $discount_reason     = sanitize_text_field( (string) $g( 'discount_reason' ) );
    $customer_name       = sanitize_text_field( (string) $g( 'customer_name' ) );
    $customer_email      = sanitize_email( (string) $g( 'customer_email' ) );
    $customer_phone      = sanitize_text_field( (string) $g( 'customer_phone' ) );
    $mailing_address     = sanitize_text_field( (string) $g( 'address1' ) );
    $city                = sanitize_text_field( (string) $g( 'city' ) );
    $state               = sanitize_text_field( (string) $g( 'state' ) );
    $postal_code         = sanitize_text_field( (string) $g( 'postal_code' ) );

    // Create or update deal
    $deal_name       = sanitize_text_field( (string) $g( 'deal_name', $account_name . ' - SMTI Quote' ) );
    $hs_pipeline_id  = get_option( 'sp_smti_sales_pipeline_id',    'default' );
    $hs_stage_id     = get_option( 'sp_smti_sales_deal_stage_id',  'qualifiedtobuy' );
    $deal_props = array(
        'dealname'   => $deal_name,
        'dealstage'  => $hs_stage_id,
        'pipeline'   => $hs_pipeline_id,
        'amount'     => $total,
    );
    $deal_id = $current_deal_id;
    if ( $deal_id ) {
        $upd = sp_smti_sales_hs_call( 'PATCH', '/crm/v3/objects/deals/' . rawurlencode( $deal_id ), array( 'properties' => $deal_props ) );
        if ( ! is_wp_error( $upd ) && (int) $upd['code'] === 404 ) {
            // The linked HubSpot deal was deleted (local quote points at a dead deal).
            // Don't silently report success against a non-existent record — recover by
            // creating a fresh deal below so the quote still lands in HubSpot.
            $deal_id = '';
        } elseif ( is_wp_error( $upd ) || ! in_array( (int) $upd['code'], array( 200, 201 ) ) ) {
            $emsg = is_wp_error( $upd ) ? $upd->get_error_message() : ( 'HubSpot returned HTTP ' . (int) $upd['code'] );
            return rest_ensure_response( array( 'success' => false, 'message' => 'Failed to update the HubSpot deal (' . $emsg . '). Nothing was saved.' ) );
        } else {
            $was_update = true;
        }
    }
    if ( ! $deal_id ) {
        $dr = sp_smti_sales_hs_call( 'POST', '/crm/v3/objects/deals', array( 'properties' => $deal_props ) );
        if ( is_wp_error( $dr ) || ! in_array( (int) $dr['code'], array( 200, 201 ) ) || empty( $dr['data']['id'] ) ) {
            return rest_ensure_response( array( 'success' => false, 'message' => 'Failed to create HubSpot deal.' ) );
        }
        $deal_id = $dr['data']['id'];
    }

    // Associate contact & company using v4 default associations
    if ( $contact_id ) {
        sp_smti_sales_hs_call( 'PUT', '/crm/v3/objects/deals/' . rawurlencode( $deal_id ) . '/associations/contacts/' . rawurlencode( $contact_id ) . '/3', null );
    }
    if ( $company_id ) {
        sp_smti_sales_hs_call( 'PUT', '/crm/v3/objects/deals/' . rawurlencode( $deal_id ) . '/associations/companies/' . rawurlencode( $company_id ) . '/5', null );
    }

    // Write a clean readable note on the deal (and contact)
    $member      = function_exists( 'sp_get_current_team_member' ) ? sp_get_current_team_member() : null;
    $created_by  = $member ? $member->name : 'SP Platform';
    $addr_line   = trim( $mailing_address . ( $city ? ', ' . $city : '' ) . ( $state ? ', ' . $state : '' ) . ( $postal_code ? ' ' . $postal_code : '' ) );

    // Ensure tables exist (in case plugin was updated without reactivation)
    sp_smti_sales_create_tables();

    // Quote number & revision
    $rev_table = $wpdb->prefix . 'sp_smti_quote_revisions';
    $rev_count = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$rev_table} WHERE deal_id = %s", $deal_id
    ) );
    $revision_number = $rev_count + 1;
    if ( $revision_number === 1 ) {
        $quote_number = sp_smti_sales_generate_quote_number();
    } else {
        $first_qn = $wpdb->get_var( $wpdb->prepare(
            "SELECT quote_number FROM {$rev_table} WHERE deal_id = %s ORDER BY revision_number ASC LIMIT 1", $deal_id
        ) );
        $base     = preg_replace( '/-R\d+$/', '', (string) $first_qn );
        $quote_number = $base . '-R' . $revision_number;
    }

    // Push quote number + distributor back to the deal now that we have them
    $deal_name_with_qn = $deal_name . ' [' . $quote_number . ']';
    $deal_update_props = array(
        'dealname'          => $deal_name_with_qn,
        'smti_quote_number' => $quote_number,
    );
    if ( $distributor_name ) {
        $deal_update_props['smt_distributor_name'] = $distributor_name;
        $deal_update_props['description']          = 'Distributor: ' . $distributor_name . ( $distributor_rep ? ' / ' . $distributor_rep : '' );
    }
    sp_smti_sales_hs_call( 'PATCH', '/crm/v3/objects/deals/' . rawurlencode( $deal_id ), array( 'properties' => $deal_update_props ) );

    // Post a clean readable note to HubSpot (deal + contact + company)
    $is_revision  = $revision_number > 1;
    $note_header  = $is_revision
        ? '===== SMTI QUOTE REVISION ' . $revision_number . ' ====='
        : '===== SMTI SALES QUOTE =====';

    $note_lines = array(
        $note_header,
        '  Quote #:     ' . $quote_number,
        '  Deal ID:     ' . $deal_id,
        ( $is_revision ? '  Revision:    ' . $revision_number . ' of this deal' : '' ),
        '',
        'CUSTOMER',
        '  Name:        ' . $customer_name,
        '  Email:       ' . $customer_email,
        '  Phone:       ' . $customer_phone,
        '  Account:     ' . $account_name,
        ( $addr_line ? '  Address:     ' . $addr_line : '' ),
        '',
        'DISTRIBUTOR',
        '  Company:     ' . $distributor_name,
        '  Rep:         ' . $distributor_rep,
        ( $shipping_method ? '  Shipping:    ' . $shipping_method : '' ),
        '',
        'PRODUCTS',
    );
    foreach ( $line_items as $li ) {
        $note_lines[] = '  ' . $li;
    }
    $note_lines[] = '';
    $note_lines[] = 'PRICING (' . $pricing_region . ')';
    $note_lines[] = '  Subtotal:    $' . number_format( $subtotal, 2 );
    if ( $discount_amount > 0 ) {
        $note_lines[] = '  Discount:    -$' . number_format( $discount_amount, 2 ) . ( $discount_reason ? ' (' . $discount_reason . ')' : '' );
    }
    if ( $freight_amount > 0 ) {
        $note_lines[] = '  Freight:     $' . number_format( $freight_amount, 2 );
    }
    $note_lines[] = '  TOTAL:       $' . number_format( $total, 2 );
    if ( $notes_field ) {
        $note_lines[] = '';
        $note_lines[] = 'NOTES';
        $note_lines[] = '  ' . str_replace( "\n", "\n  ", $notes_field );
    }
    $note_lines[] = '';
    $note_lines[] = 'Saved by: ' . $created_by . ' via SP Platform';
    $note_lines[] = '============================';

    $note_body = implode( "\n", array_filter( $note_lines, function( $l ) { return $l !== ''; } ) );
    $note_body = preg_replace( '/\n(CUSTOMER|DISTRIBUTOR|PRODUCTS|PRICING|NOTES|Saved by|=====)/', "\n\n$1", $note_body );

    $note_assoc = array();
    if ( $deal_id ) {
        $note_assoc[] = array( 'to' => array( 'id' => $deal_id ),    'types' => array( array( 'associationCategory' => 'HUBSPOT_DEFINED', 'associationTypeId' => 214 ) ) );
    }
    if ( $contact_id ) {
        $note_assoc[] = array( 'to' => array( 'id' => $contact_id ), 'types' => array( array( 'associationCategory' => 'HUBSPOT_DEFINED', 'associationTypeId' => 202 ) ) );
    }
    if ( $company_id ) {
        $note_assoc[] = array( 'to' => array( 'id' => $company_id ), 'types' => array( array( 'associationCategory' => 'HUBSPOT_DEFINED', 'associationTypeId' => 190 ) ) );
    }
    sp_smti_sales_hs_call( 'POST', '/crm/v3/objects/notes', array(
        'properties'   => array(
            'hs_note_body' => nl2br( esc_html( $note_body ) ),
            'hs_timestamp' => gmdate( 'c' ),
        ),
        'associations' => $note_assoc,
    ) );

    // Store revision locally
    $payload    = $body;
    $payload['deal_id']         = $deal_id;
    $payload['quote_number']    = $quote_number;
    $payload['revision_number'] = $revision_number;

    $wpdb->insert( $rev_table, array(
        'deal_id'         => $deal_id,
        'quote_number'    => $quote_number,
        'revision_number' => $revision_number,
        'quote_status'    => 'Quote Submitted',
        'created_by'      => $created_by,
        'quote_json'      => json_encode( $payload ),
        'created_at'      => current_time( 'mysql' ),
    ) );

    // Update summary row
    $summary = array(
        'quote_number'    => $quote_number,
        'deal_id'         => $deal_id,
        'contact_id'      => $contact_id,
        'customer_name'   => sanitize_text_field( (string) $g( 'customer_name' ) ),
        'company_name'    => $account_name,
        'pricing_region'  => sanitize_key( (string) $g( 'pricing_region', 'us' ) ),
        'subtotal'        => (float) $g( 'subtotal', 0 ),
        'discount_amount' => (float) $g( 'discount_amount', 0 ),
        'freight_amount'  => (float) $g( 'freight_amount', 0 ),
        'total'           => (float) $g( 'total', 0 ),
        'status'          => 'submitted',
        'created_by'      => $created_by,
        'quote_json'      => json_encode( $payload ),
        'updated_at'      => current_time( 'mysql' ),
    );
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}sp_smti_quotes WHERE deal_id = %s", $deal_id
    ) );
    if ( $exists ) {
        $wpdb->update( $wpdb->prefix . 'sp_smti_quotes', $summary, array( 'deal_id' => $deal_id ) );
    } else {
        $summary['created_at'] = current_time( 'mysql' );
        $wpdb->insert( $wpdb->prefix . 'sp_smti_quotes', $summary );
    }

    return rest_ensure_response( array(
        'success'                    => true,
        'deal_id'                    => $deal_id,
        'contact_id'                 => $contact_id,
        'quote_number'               => $quote_number,
        'revision_number'            => $revision_number,
        'was_update'                 => $was_update,
        'contact_created'            => $contact_created,
        'contact_was_found_by_email' => $contact_was_found_by_email,
        'company_created'            => $company_created,
        'company_was_found_by_name'  => $company_was_found_by_name,
        'deal_stage_label'           => 'Quote Submitted',
    ) );
}

// get-deal-quote
function sp_smti_sales_rest_get_deal_quote( $request ) {
    if ( ! sp_smti_sales_rest_authed() ) {
        return new WP_REST_Response( array( 'success' => false ), 403 );
    }
    global $wpdb;
    $deal_id      = sanitize_text_field( $request->get_param( 'deal_id' ) );
    $quote_number = sanitize_text_field( $request->get_param( 'quote_number' ) );

    if ( ! $deal_id && ! $quote_number ) {
        return rest_ensure_response( array( 'success' => false, 'message' => 'Provide a deal ID or quote number.' ) );
    }

    // Look up by quote number first if provided
    if ( $quote_number && ! $deal_id ) {
        $base_qn  = preg_replace( '/-R\d+$/', '', $quote_number );
        $deal_id  = $wpdb->get_var( $wpdb->prepare(
            "SELECT deal_id FROM {$wpdb->prefix}sp_smti_quote_revisions WHERE quote_number LIKE %s ORDER BY revision_number DESC LIMIT 1",
            $base_qn . '%'
        ) );
        if ( ! $deal_id ) {
            return rest_ensure_response( array( 'success' => false, 'message' => 'No quote found for number: ' . $quote_number ) );
        }
    }

    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sp_smti_quote_revisions WHERE deal_id = %s ORDER BY revision_number DESC LIMIT 1",
        $deal_id
    ), ARRAY_A );
    if ( ! $row ) {
        return rest_ensure_response( array( 'success' => false, 'message' => 'No quote found for deal ID: ' . $deal_id ) );
    }
    return rest_ensure_response( array(
        'success'      => true,
        'deal_id'      => $deal_id,
        'quote_number' => $row['quote_number'],
        'quote_json'   => json_decode( $row['quote_json'], true ),
    ) );
}

// delete-local-quote
function sp_smti_sales_rest_delete_local( $request ) {
    if ( ! sp_smti_sales_rest_authed() || ! sp_is_admin_member() ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => 'Not authorized' ), 403 );
    }
    global $wpdb;
    $deal_id = sanitize_text_field( $request->get_param( 'deal_id' ) );
    if ( ! $deal_id ) {
        return rest_ensure_response( array( 'success' => false, 'message' => 'No deal ID' ) );
    }
    $wpdb->delete( $wpdb->prefix . 'sp_smti_quote_revisions', array( 'deal_id' => $deal_id ) );
    $wpdb->delete( $wpdb->prefix . 'sp_smti_quotes',          array( 'deal_id' => $deal_id ) );
    return rest_ensure_response( array( 'success' => true ) );
}

// get-quote-revisions
function sp_smti_sales_rest_get_revisions( $request ) {
    if ( ! sp_smti_sales_rest_authed() ) {
        return new WP_REST_Response( array( 'success' => false ), 403 );
    }
    global $wpdb;
    $deal_id = sanitize_text_field( $request->get_param( 'deal_id' ) );
    if ( ! $deal_id ) {
        return rest_ensure_response( array( 'success' => false, 'message' => 'No deal ID' ) );
    }
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT quote_number, revision_number, quote_status, created_at, quote_json FROM {$wpdb->prefix}sp_smti_quote_revisions WHERE deal_id = %s ORDER BY revision_number DESC",
        $deal_id
    ), ARRAY_A );
    $out = array();
    foreach ( $rows as $i => $row ) {
        $qj         = json_decode( $row['quote_json'], true );
        $line_count = ( is_array( $qj ) && isset( $qj['items'] ) ) ? count( $qj['items'] ) : 0;
        $out[]      = array(
            'quote_number'        => $row['quote_number'],
            'revision_number'     => (int) $row['revision_number'],
            'quote_status'        => $row['quote_status'],
            'saved_at'            => strtotime( $row['created_at'] ) * 1000,
            'line_count'          => $line_count,
            'is_current_revision' => $i === 0 ? 'true' : 'false',
        );
    }
    return rest_ensure_response( array( 'success' => true, 'revisions' => $out ) );
}

// get-quote-revision
function sp_smti_sales_rest_get_revision( $request ) {
    if ( ! sp_smti_sales_rest_authed() ) {
        return new WP_REST_Response( array( 'success' => false ), 403 );
    }
    global $wpdb;
    $deal_id      = sanitize_text_field( $request->get_param( 'deal_id' ) );
    $quote_number = sanitize_text_field( $request->get_param( 'quote_number' ) );
    if ( ! $deal_id || ! $quote_number ) {
        return rest_ensure_response( array( 'success' => false, 'message' => 'Missing parameters' ) );
    }
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sp_smti_quote_revisions WHERE deal_id = %s AND quote_number = %s LIMIT 1",
        $deal_id, $quote_number
    ), ARRAY_A );
    if ( ! $row ) {
        return rest_ensure_response( array( 'success' => false, 'message' => 'Revision not found' ) );
    }
    return rest_ensure_response( array(
        'success'      => true,
        'quote_number' => $row['quote_number'],
        'deal_id'      => $deal_id,
        'quote_json'   => json_decode( $row['quote_json'], true ),
    ) );
}

// get-quote-revision-compare
function sp_smti_sales_rest_compare_revisions( $request ) {
    if ( ! sp_smti_sales_rest_authed() ) {
        return new WP_REST_Response( array( 'success' => false ), 403 );
    }
    global $wpdb;
    $deal_id  = sanitize_text_field( $request->get_param( 'deal_id' ) );
    $from_num = sanitize_text_field( $request->get_param( 'from_quote_number' ) );
    $to_num   = sanitize_text_field( $request->get_param( 'to_quote_number' ) );
    if ( ! $deal_id || ! $from_num || ! $to_num ) {
        return rest_ensure_response( array( 'success' => false, 'message' => 'Missing parameters' ) );
    }
    $get = function( $qn ) use ( $wpdb, $deal_id ) {
        return $wpdb->get_var( $wpdb->prepare(
            "SELECT quote_json FROM {$wpdb->prefix}sp_smti_quote_revisions WHERE deal_id = %s AND quote_number = %s",
            $deal_id, $qn
        ) );
    };
    $from_json = $get( $from_num );
    $to_json   = $get( $to_num );
    if ( ! $from_json || ! $to_json ) {
        return rest_ensure_response( array( 'success' => false, 'message' => 'One or both revisions not found' ) );
    }
    $from_data = json_decode( $from_json, true );
    $to_data   = json_decode( $to_json,   true );
    $fi_map = array();
    $ti_map = array();
    if ( isset( $from_data['items'] ) ) {
        foreach ( $from_data['items'] as $item ) { $fi_map[ $item['product_code'] ] = $item; }
    }
    if ( isset( $to_data['items'] ) ) {
        foreach ( $to_data['items'] as $item ) { $ti_map[ $item['product_code'] ] = $item; }
    }
    $codes = array_unique( array_merge( array_keys( $fi_map ), array_keys( $ti_map ) ) );
    $diffs = array(); $added = 0; $removed = 0; $changed = 0; $unchanged = 0;
    foreach ( $codes as $code ) {
        $fi = isset( $fi_map[ $code ] ) ? $fi_map[ $code ] : null;
        $ti = isset( $ti_map[ $code ] ) ? $ti_map[ $code ] : null;
        if ( $fi && ! $ti ) { $type = 'removed'; $removed++; $ch = array(); }
        elseif ( ! $fi && $ti ) { $type = 'added';   $added++;   $ch = array(); }
        else {
            $ch = array();
            foreach ( array( 'quantity', 'unit_price', 'item_discount_type', 'item_discount_value' ) as $f ) {
                if ( (string) $fi[ $f ] !== (string) $ti[ $f ] ) { $ch[] = $f; }
            }
            if ( $ch ) { $type = 'changed'; $changed++; } else { $type = 'unchanged'; $unchanged++; }
        }
        $diffs[] = array( 'key' => $code, 'change_type' => $type, 'changes' => $ch, 'from' => $fi, 'to' => $ti );
    }
    return rest_ensure_response( array(
        'success'           => true,
        'from_quote_number' => $from_num,
        'to_quote_number'   => $to_num,
        'summary'           => array(
            'from'   => sp_smti_sales_calc_totals( $from_data ),
            'to'     => sp_smti_sales_calc_totals( $to_data ),
            'counts' => array( 'added' => $added, 'removed' => $removed, 'changed' => $changed, 'unchanged' => $unchanged ),
        ),
        'diffs' => $diffs,
    ) );
}

// ── Nav filter ────────────────────────────────────────────────────────────────

function sp_smti_sales_nav_items( $items ) {
    // Insert under the sales-core section (defined in core nav)
    $result = array();
    foreach ( $items as $item ) {
        $result[] = $item;
        if ( ! empty( $item['section'] ) && ! empty( $item['section_id'] ) && $item['section_id'] === 'sales-core' ) {
            $result[] = array(
                'view'   => 'smti-sales',
                'label'  => 'Quotes',
                'custom' => true,
                'icon'   => '<path fill="currentColor" d="M7 3a1 1 0 000 2h10a1 1 0 100-2H7zM4 7a1 1 0 011-1h14a1 1 0 011 1v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7zm3 4a1 1 0 000 2h2a1 1 0 100-2H7zm0 4a1 1 0 000 2h2a1 1 0 100-2H7zm6-4a1 1 0 000 2h2a1 1 0 100-2h-2zm0 4a1 1 0 000 2h2a1 1 0 100-2h-2z"/>',
            );
        }
    }
    return $result;
}

// ── Allowed views ─────────────────────────────────────────────────────────────

function sp_smti_sales_allowed_views( $views ) {
    $views[] = 'smti-sales';
    return $views;
}

// ── Settings section ──────────────────────────────────────────────────────────

function sp_smti_sales_settings_section() {
    $logo_url = get_option( 'sp_smti_sales_logo_url', '' );
    $company  = get_option( 'sp_smti_sales_company',  'Sport Medical Technology Inc' );
    $address  = get_option( 'sp_smti_sales_address',  "49 Natcon Dr\nShirley, NY 11967\nUnited States of America" );
    $terms    = get_option( 'sp_smti_sales_terms',    'This Quotation is valid for 30 days. All prices and costs indicated herein are paid by the customer to Sport Medical Technology Inc. with no allowable deductions for banking or other charges.' );
    ?>
    <div class="sp-card sp-form-card" style="margin-top:16px">
        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="smti_sales_settings">
            <input type="hidden" name="sp_id" value="0">
            <h2 class="sp-section-heading">SMTI Sales Settings</h2>
            <div class="sp-field">
                <label>Company Name (on quotes)</label>
                <input type="text" name="sp_smti_sales_company" value="<?php echo esc_attr( $company ); ?>">
            </div>
            <div class="sp-field">
                <label>Company Address (on quotes)</label>
                <textarea name="sp_smti_sales_address" rows="3"><?php echo esc_textarea( $address ); ?></textarea>
            </div>
            <div class="sp-field">
                <label>Logo URL</label>
                <input type="text" name="sp_smti_sales_logo_url" value="<?php echo esc_attr( $logo_url ); ?>">
            </div>
            <div class="sp-field">
                <label>Quote Terms</label>
                <textarea name="sp_smti_sales_terms" rows="4"><?php echo esc_textarea( $terms ); ?></textarea>
            </div>
            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary">Save Sales Settings</button>
            </div>
        </form>
    </div>

    <?php if ( ! function_exists( 'sp_is_super_admin' ) || ! sp_is_super_admin() ) return; ?>

    <?php
    $api_base    = get_option( 'sp_smti_sales_api_base',      'https://startwebservicesbackup.com/smti/wp-json/smti/v1' );
    $api_token   = get_option( 'sp_smti_sales_api_token',     '' );
    $hs_pat      = get_option( 'sp_smti_sales_hs_pat',        '' );
    $products_id = get_option( 'sp_smti_sales_products_id',   '213697055' );
    $distrib_id  = get_option( 'sp_smti_sales_distrib_id',    '224700762' );
    $pipeline_id = get_option( 'sp_smti_sales_pipeline_id',   'default' );
    $stage_id    = get_option( 'sp_smti_sales_deal_stage_id', 'qualifiedtobuy' );
    ?>
    <div style="margin-top:16px;border:2px solid #f59e0b;border-radius:12px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#451a03,#78350f);padding:12px 20px;display:flex;align-items:center;gap:10px;">
            <svg viewBox="0 0 20 20" fill="#fbbf24" width="16" height="16"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
            <span style="font-size:.8rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#fbbf24;">Super Admin Only — API &amp; Integration Keys</span>
        </div>
        <div class="sp-form-card" style="padding:20px 24px;background:#fff;">
            <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
                <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                <input type="hidden" name="sp_type" value="smti_sales_api_settings">
                <input type="hidden" name="sp_id" value="0">
                <div class="sp-field">
                    <label>Sales API Base URL</label>
                    <input type="text" name="sp_smti_sales_api_base" value="<?php echo esc_attr( $api_base ); ?>" style="font-family:monospace;font-size:12px;">
                    <span class="sp-hint">URL of the SMTI WordPress API (old install)</span>
                </div>
                <div class="sp-field">
                    <label>Sales API Token</label>
                    <input type="text" name="sp_smti_sales_api_token" value="<?php echo esc_attr( $api_token ); ?>" autocomplete="new-password" style="font-family:monospace;font-size:12px;">
                </div>
                <div class="sp-field">
                    <label>HubSpot Private App Token (for product sync)</label>
                    <input type="text" name="sp_smti_sales_hs_pat" value="<?php echo esc_attr( $hs_pat ); ?>" autocomplete="new-password" style="font-family:monospace;font-size:12px;">
                    <span class="sp-hint">Found in HubSpot → Settings → Integrations → Private Apps.</span>
                </div>
                <div class="sp-form-row">
                    <div class="sp-field">
                        <label>HubSpot Deal Pipeline ID</label>
                        <input type="text" name="sp_smti_sales_pipeline_id" value="<?php echo esc_attr( $pipeline_id ); ?>" placeholder="default">
                        <span class="sp-hint">HubSpot → Settings → Deals → Pipelines. Use <code>default</code> for the default pipeline.</span>
                    </div>
                    <div class="sp-field">
                        <label>Deal Stage ID</label>
                        <input type="text" name="sp_smti_sales_deal_stage_id" value="<?php echo esc_attr( $stage_id ); ?>" placeholder="qualifiedtobuy">
                        <span class="sp-hint">Stage ID from HubSpot pipeline settings.</span>
                    </div>
                </div>
                <div class="sp-form-row">
                    <div class="sp-field">
                        <label>Products HubDB Table ID</label>
                        <input type="text" name="sp_smti_sales_products_id" value="<?php echo esc_attr( $products_id ); ?>">
                    </div>
                    <div class="sp-field">
                        <label>Distributors HubDB Table ID</label>
                        <input type="text" name="sp_smti_sales_distrib_id" value="<?php echo esc_attr( $distrib_id ); ?>">
                    </div>
                </div>
                <div class="sp-form-actions">
                    <button type="submit" class="sp-btn sp-btn-primary">Save API Settings</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

function sp_smti_sales_save_settings( $id ) {
    if ( ! sp_is_admin_member() ) {
        wp_redirect( home_url( '/sp-app/?view=settings' ) ); exit;
    }
    $fields = array(
        'sp_smti_sales_company'  => 'sanitize_text_field',
        'sp_smti_sales_address'  => 'sanitize_textarea_field',
        'sp_smti_sales_logo_url' => 'esc_url_raw',
        'sp_smti_sales_terms'    => 'sanitize_textarea_field',
    );
    foreach ( $fields as $key => $sanitizer ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_option( $key, call_user_func( $sanitizer, wp_unslash( $_POST[ $key ] ) ) );
        }
    }
    wp_redirect( home_url( '/sp-app/?view=settings&saved=1' ) ); exit;
}

function sp_smti_sales_save_api_settings( $id ) {
    if ( ! function_exists( 'sp_is_super_admin' ) || ! sp_is_super_admin() ) {
        wp_redirect( home_url( '/sp-app/?view=settings' ) ); exit;
    }
    $fields = array(
        'sp_smti_sales_api_base'       => 'esc_url_raw',
        'sp_smti_sales_api_token'      => 'sanitize_text_field',
        'sp_smti_sales_hs_pat'         => 'sanitize_text_field',
        'sp_smti_sales_products_id'    => 'sanitize_text_field',
        'sp_smti_sales_distrib_id'     => 'sanitize_text_field',
        'sp_smti_sales_pipeline_id'    => 'sanitize_text_field',
        'sp_smti_sales_deal_stage_id'  => 'sanitize_text_field',
    );
    foreach ( $fields as $key => $sanitizer ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_option( $key, call_user_func( $sanitizer, wp_unslash( $_POST[ $key ] ) ) );
        }
    }
    wp_redirect( home_url( '/sp-app/?view=settings&saved=1' ) ); exit;
}

// ── AJAX: Auth check ──────────────────────────────────────────────────────────

function sp_smti_sales_auth_check() {
    if ( ! sp_smti_sales_rest_authed() ) {
        wp_send_json_error( array( 'message' => 'Not authenticated' ) );
        exit;
    }
}

// ── AJAX: Customer search ─────────────────────────────────────────────────────

function sp_smti_sales_ajax_search() {
    sp_smti_sales_auth_check();
    $q = sanitize_text_field( isset( $_POST['q'] ) ? wp_unslash( $_POST['q'] ) : '' );
    if ( strlen( $q ) < 2 ) {
        wp_send_json_success( array( 'results' => array() ) );
    }
    $response = sp_smti_sales_api_request( 'GET', '/search-contacts?q=' . urlencode( $q ) );
    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
    }
    wp_send_json_success( $response );
}

// ── AJAX: Contact details ─────────────────────────────────────────────────────

function sp_smti_sales_ajax_contact() {
    sp_smti_sales_auth_check();
    $contact_id = sanitize_text_field( isset( $_POST['contact_id'] ) ? wp_unslash( $_POST['contact_id'] ) : '' );
    if ( ! $contact_id ) {
        wp_send_json_error( array( 'message' => 'No contact ID' ) );
    }
    $response = sp_smti_sales_api_request( 'GET', '/get-contact-details?contact_id=' . urlencode( $contact_id ) );
    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
    }
    wp_send_json_success( $response );
}

// ── AJAX: Save quote ──────────────────────────────────────────────────────────

function sp_smti_sales_ajax_save_quote() {
    global $wpdb;
    sp_smti_sales_auth_check();

    $raw = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
    $payload = json_decode( $raw, true );
    if ( ! is_array( $payload ) ) {
        wp_send_json_error( array( 'message' => 'Invalid payload' ) );
    }

    $response = sp_smti_sales_api_request( 'POST', '/create-deal', $payload );
    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
    }
    if ( empty( $response['success'] ) ) {
        wp_send_json_error( $response );
    }

    // Save locally
    $member    = sp_get_current_team_member();
    $deal_id   = isset( $response['deal_id'] )     ? sanitize_text_field( $response['deal_id'] )     : '';
    $qnum      = isset( $response['quote_number'] ) ? sanitize_text_field( $response['quote_number'] ) : '';
    $contact   = isset( $payload['contact_id'] )    ? sanitize_text_field( $payload['contact_id'] )    : '';
    $cname     = isset( $payload['customer_name'] )  ? sanitize_text_field( $payload['customer_name'] ) : '';
    $company   = isset( $payload['account_name'] )   ? sanitize_text_field( $payload['account_name'] )  : '';
    $region    = isset( $payload['pricing_region'] )  ? sanitize_key( $payload['pricing_region'] )       : 'us';
    $sub       = isset( $payload['subtotal'] )        ? floatval( $payload['subtotal'] )                : 0;
    $disc      = isset( $payload['discount_amount'] ) ? floatval( $payload['discount_amount'] )         : 0;
    $freight   = isset( $payload['freight_amount'] )  ? floatval( $payload['freight_amount'] )          : 0;
    $total     = isset( $payload['total'] )           ? floatval( $payload['total'] )                   : 0;
    $json_col  = wp_json_encode( $payload );

    $existing = $deal_id ? $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}sp_smti_quotes WHERE deal_id = %s LIMIT 1", $deal_id
    ) ) : null;

    if ( $existing ) {
        $wpdb->update( $wpdb->prefix . 'sp_smti_quotes', array(
            'quote_number'    => $qnum,
            'customer_name'   => $cname,
            'company_name'    => $company,
            'pricing_region'  => $region,
            'subtotal'        => $sub,
            'discount_amount' => $disc,
            'freight_amount'  => $freight,
            'total'           => $total,
            'status'          => 'Draft',
            'quote_json'      => $json_col,
            'updated_at'      => current_time( 'mysql' ),
        ), array( 'id' => $existing ) );
    } else {
        $wpdb->insert( $wpdb->prefix . 'sp_smti_quotes', array(
            'quote_number'    => $qnum,
            'deal_id'         => $deal_id,
            'contact_id'      => $contact,
            'customer_name'   => $cname,
            'company_name'    => $company,
            'pricing_region'  => $region,
            'subtotal'        => $sub,
            'discount_amount' => $disc,
            'freight_amount'  => $freight,
            'total'           => $total,
            'status'          => 'Draft',
            'created_by'      => $member ? $member->name : '',
            'quote_json'      => $json_col,
            'created_at'      => current_time( 'mysql' ),
            'updated_at'      => current_time( 'mysql' ),
        ) );
    }

    wp_send_json_success( $response );
}

// ── AJAX: Load quote ──────────────────────────────────────────────────────────

function sp_smti_sales_ajax_load_quote() {
    sp_smti_sales_auth_check();
    $deal_id = sanitize_text_field( isset( $_POST['deal_id'] ) ? wp_unslash( $_POST['deal_id'] ) : '' );
    if ( ! $deal_id ) {
        wp_send_json_error( array( 'message' => 'No deal ID' ) );
    }
    $response = sp_smti_sales_api_request( 'GET', '/get-deal-quote?deal_id=' . urlencode( $deal_id ) );
    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
    }
    wp_send_json_success( $response );
}

// ── AJAX: Revisions ───────────────────────────────────────────────────────────

function sp_smti_sales_ajax_revisions() {
    sp_smti_sales_auth_check();
    $deal_id = sanitize_text_field( isset( $_POST['deal_id'] ) ? wp_unslash( $_POST['deal_id'] ) : '' );
    if ( ! $deal_id ) { wp_send_json_error( array( 'message' => 'No deal ID' ) ); }
    $response = sp_smti_sales_api_request( 'GET', '/get-quote-revisions?deal_id=' . urlencode( $deal_id ) );
    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
    }
    wp_send_json_success( $response );
}

// ── AJAX: Compare revisions ───────────────────────────────────────────────────

function sp_smti_sales_ajax_compare() {
    sp_smti_sales_auth_check();
    $deal_id = sanitize_text_field( isset( $_POST['deal_id'] ) ? wp_unslash( $_POST['deal_id'] ) : '' );
    $from    = sanitize_text_field( isset( $_POST['from'] )    ? wp_unslash( $_POST['from'] )    : '' );
    $to      = sanitize_text_field( isset( $_POST['to'] )      ? wp_unslash( $_POST['to'] )      : '' );
    if ( ! $deal_id || ! $from || ! $to ) { wp_send_json_error( array( 'message' => 'Missing params' ) ); }
    $response = sp_smti_sales_api_request( 'GET',
        '/get-quote-revision-compare?deal_id=' . urlencode( $deal_id ) .
        '&from_quote_number=' . urlencode( $from ) .
        '&to_quote_number=' . urlencode( $to )
    );
    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => $response->get_error_message() ) );
    }
    wp_send_json_success( $response );
}

// ── AJAX: Sync products & distributors from HubDB ────────────────────────────

function sp_smti_sales_ajax_sync() {
    global $wpdb;
    sp_smti_sales_auth_check();
    if ( ! sp_is_admin_member() ) {
        wp_send_json_error( array( 'message' => 'Admin only' ) );
    }

    $products_id = get_option( 'sp_smti_sales_products_id', '213697055' );
    $distrib_id  = get_option( 'sp_smti_sales_distrib_id',  '224700762' );

    $products = sp_smti_sales_fetch_hubdb( $products_id );
    $distribs  = sp_smti_sales_fetch_hubdb( $distrib_id );

    if ( is_wp_error( $products ) ) {
        wp_send_json_error( array( 'message' => 'Products fetch failed: ' . $products->get_error_message() ) );
    }
    if ( is_wp_error( $distribs ) ) {
        wp_send_json_error( array( 'message' => 'Distributors fetch failed: ' . $distribs->get_error_message() ) );
    }

    // Safety guard: the sync is a destructive wipe-and-reimport. A fetch can succeed
    // (HTTP 200) yet return zero rows — HubDB table momentarily emptied, a wrong-but-
    // valid table ID, or an API hiccup. Applying that would wipe the whole catalog and
    // blank the Quote Builder. Refuse to proceed on empty results and keep existing data.
    if ( empty( $products ) ) {
        wp_send_json_error( array( 'message' => 'Sync aborted: HubSpot returned 0 products. Existing catalog kept unchanged. Check the Products HubDB table and try again.' ) );
    }
    if ( empty( $distribs ) ) {
        wp_send_json_error( array( 'message' => 'Sync aborted: HubSpot returned 0 distributors. Nothing was changed. Check the Distributors HubDB table and try again.' ) );
    }

    // Clear and re-insert products
    $wpdb->query( "DELETE FROM {$wpdb->prefix}sp_smti_products" );
    $now = current_time( 'mysql' );
    $p_count = 0;
    foreach ( $products as $row ) {
        $v = isset( $row['values'] ) ? $row['values'] : $row;
        $active_raw = isset( $v['active'] ) ? $v['active'] : true;
        $active = sp_smti_sales_parse_active( $active_raw );
        $wpdb->insert( $wpdb->prefix . 'sp_smti_products', array(
            'product_code'       => sanitize_text_field( isset( $v['product_code'] ) ? $v['product_code'] : '' ),
            'product_title'      => sanitize_text_field( isset( $v['product_title'] ) ? $v['product_title'] : '' ),
            'category'           => sanitize_text_field( isset( $v['category'] ) ? $v['category'] : '' ),
            'product_description'=> wp_kses_post( isset( $v['product_description'] ) ? $v['product_description'] : '' ),
            'unit_price'         => floatval( isset( $v['unit_price'] ) ? $v['unit_price'] : 0 ),
            'dealer_price'       => floatval( isset( $v['dealer_price'] ) ? $v['dealer_price'] : 0 ),
            'location'           => sanitize_key( isset( $v['location'] ) ? $v['location'] : 'us' ),
            'active'             => $active ? 1 : 0,
            'allow_discount'     => sp_smti_sales_parse_active( isset( $v['allow_discount'] ) ? $v['allow_discount'] : true ) ? 1 : 0,
            'max_discount_percent'=> intval( isset( $v['max_discount_percent'] ) ? $v['max_discount_percent'] : 10 ),
            'collateral_file'    => esc_url_raw( isset( $v['collateral_file']['url'] ) ? $v['collateral_file']['url'] : ( isset( $v['collateral_file'] ) && is_string( $v['collateral_file'] ) ? $v['collateral_file'] : '' ) ),
            'sort_order'         => intval( isset( $v['sort_order'] ) ? $v['sort_order'] : 0 ),
            'updated_at'         => $now,
        ) );
        $p_count++;
    }

    // Clear and re-insert distributors
    $wpdb->query( "DELETE FROM {$wpdb->prefix}sp_smti_distributors" );
    $d_count = 0;
    foreach ( $distribs as $row ) {
        $v = isset( $row['values'] ) ? $row['values'] : $row;
        $wpdb->insert( $wpdb->prefix . 'sp_smti_distributors', array(
            'slug'       => sanitize_key( isset( $v['slug'] ) ? $v['slug'] : '' ),
            'name'       => sanitize_text_field( isset( $v['name'] ) ? $v['name'] : '' ),
            'company'    => sanitize_text_field( isset( $v['company'] ) ? $v['company'] : '' ),
            'title'      => sanitize_text_field( isset( $v['title'] ) ? $v['title'] : '' ),
            'phone'      => sanitize_text_field( isset( $v['phone'] ) ? $v['phone'] : '' ),
            'email'      => sanitize_email( isset( $v['email'] ) ? $v['email'] : '' ),
            'updated_at' => $now,
        ) );
        $d_count++;
    }

    update_option( 'sp_smti_sales_last_sync', $now );
    wp_send_json_success( array(
        'products'     => $p_count,
        'distributors' => $d_count,
        'synced_at'    => $now,
    ) );
}

// ── AJAX: Saved quotes list ───────────────────────────────────────────────────

function sp_smti_sales_ajax_quotes_list() {
    global $wpdb;
    sp_smti_sales_auth_check();
    $rows = $wpdb->get_results(
        "SELECT id, quote_number, deal_id, customer_name, company_name, pricing_region, total, status, created_by, created_at
         FROM {$wpdb->prefix}sp_smti_quotes
         ORDER BY updated_at DESC LIMIT 100",
        ARRAY_A
    );
    $rows = sp_smti_sales_hide_orphaned_quotes( $rows );
    wp_send_json_success( array( 'quotes' => $rows ? $rows : array() ) );
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function sp_smti_sales_api_request( $method, $path, $body = null ) {
    $base  = rtrim( get_option( 'sp_smti_sales_api_base', 'https://startwebservicesbackup.com/smti/wp-json/smti/v1' ), '/' );
    $token = get_option( 'sp_smti_sales_api_token', '' );

    $args = array(
        'method'  => strtoupper( $method ),
        'headers' => array(
            'X-SMTI-Token' => $token,
            'Content-Type' => 'application/json',
        ),
        'timeout' => 20,
    );
    if ( $body !== null ) {
        $args['body'] = wp_json_encode( $body );
    }

    $response = wp_remote_request( $base . $path, $args );
    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $decoded = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( $decoded === null ) {
        return new WP_Error( 'json_error', 'Invalid JSON from SMTI API' );
    }
    return $decoded;
}

function sp_smti_sales_fetch_hubdb( $table_id ) {
    $token = get_option( 'sp_smti_sales_hs_pat', '' );
    if ( ! $token ) {
        return new WP_Error( 'hubdb_no_token', 'HubSpot Private App Token not set. Add it in Settings → SMTI Sales Settings.' );
    }
    $url = 'https://api.hubapi.com/cms/v3/hubdb/tables/' . rawurlencode( $table_id ) . '/rows?limit=1000';

    $args = array(
        'timeout' => 20,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
        ),
    );

    $response = wp_remote_get( $url, $args );
    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code( $response );
    if ( $code !== 200 ) {
        return new WP_Error( 'hubdb_error', 'HubDB returned HTTP ' . $code );
    }

    $body    = json_decode( wp_remote_retrieve_body( $response ), true );
    $results = isset( $body['results'] ) ? $body['results'] : array();
    return $results;
}

function sp_smti_sales_parse_active( $value ) {
    if ( $value === true || $value === 1 || $value === '1' ) return true;
    if ( $value === false || $value === 0 || $value === '0' ) return false;
    if ( is_array( $value ) ) {
        foreach ( $value as $item ) {
            if ( sp_smti_sales_parse_active( $item ) ) return true;
        }
        return false;
    }
    if ( is_object( $value ) ) {
        $check = isset( $value->name ) ? $value->name : ( isset( $value->value ) ? $value->value : '' );
        return sp_smti_sales_parse_active( $check );
    }
    $raw = strtolower( trim( (string) $value ) );
    return in_array( $raw, array( 'yes', 'true', '1' ), true );
}

function sp_smti_sales_get_products() {
    global $wpdb;
    return $wpdb->get_results(
        "SELECT product_code, product_title, category, product_description,
                unit_price, dealer_price, location, active, allow_discount,
                max_discount_percent, collateral_file
         FROM {$wpdb->prefix}sp_smti_products
         WHERE active = 1
         ORDER BY sort_order ASC, product_code ASC",
        ARRAY_A
    );
}

function sp_smti_sales_get_distributors() {
    global $wpdb;
    return $wpdb->get_results(
        "SELECT slug, name, company, title, phone, email
         FROM {$wpdb->prefix}sp_smti_distributors
         ORDER BY name ASC",
        ARRAY_A
    );
}

function sp_smti_sales_get_recent_quotes( $limit = 10 ) {
    global $wpdb;
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, quote_number, deal_id, customer_name, company_name, total, status, created_at
         FROM {$wpdb->prefix}sp_smti_quotes
         ORDER BY updated_at DESC LIMIT %d",
        $limit
    ), ARRAY_A );
    return sp_smti_sales_hide_orphaned_quotes( $rows );
}

// Given rows from sp_smti_quotes, drop any whose HubSpot deal has been deleted so
// orphaned local records don't clutter the quote lists. Uses ONE HubSpot batch read
// for all deal_ids (not one call per quote). Fails OPEN: if the HubSpot call errors,
// times out, or the token isn't set, every row is returned unchanged — this filter
// must never blank the list or block page render on an API hiccup.
function sp_smti_sales_hide_orphaned_quotes( $rows ) {
    if ( empty( $rows ) ) return $rows;
    $ids = array();
    foreach ( $rows as $r ) { if ( ! empty( $r['deal_id'] ) ) $ids[ (string) $r['deal_id'] ] = true; }
    if ( empty( $ids ) ) return $rows;

    $existing = sp_smti_sales_hs_existing_deal_ids( array_keys( $ids ) );
    if ( $existing === null ) return $rows; // fail open

    $out = array();
    foreach ( $rows as $r ) {
        if ( empty( $r['deal_id'] ) || isset( $existing[ (string) $r['deal_id'] ] ) ) $out[] = $r;
    }
    return $out;
}

// Returns a map [deal_id => true] of the deal IDs that still exist in HubSpot, via a
// single batch read (max 100 inputs). Returns null on any failure so callers can fail
// open. Short timeout so it never stalls a page render.
function sp_smti_sales_hs_existing_deal_ids( $ids ) {
    $ids = array_values( array_unique( array_filter( array_map( 'strval', $ids ) ) ) );
    if ( empty( $ids ) ) return array();
    $ids = array_slice( $ids, 0, 100 ); // HubSpot batch read cap

    $pat = get_option( 'sp_smti_sales_hs_pat', '' );
    if ( ! $pat ) return null;

    $inputs = array();
    foreach ( $ids as $id ) { $inputs[] = array( 'id' => $id ); }

    $resp = wp_remote_post( 'https://api.hubapi.com/crm/v3/objects/deals/batch/read', array(
        'timeout' => 8,
        'headers' => array( 'Authorization' => 'Bearer ' . $pat, 'Content-Type' => 'application/json' ),
        'body'    => wp_json_encode( array( 'properties' => array( 'hs_object_id' ), 'inputs' => $inputs ) ),
    ) );
    if ( is_wp_error( $resp ) ) return null;
    $code = (int) wp_remote_retrieve_response_code( $resp );
    // 200 = all found; 207 = partial (some missing). Both return results[] for the found deals.
    if ( $code !== 200 && $code !== 207 ) return null;

    $data    = json_decode( wp_remote_retrieve_body( $resp ), true );
    $results = isset( $data['results'] ) ? $data['results'] : array();
    $existing = array();
    foreach ( $results as $d ) {
        if ( ! empty( $d['id'] ) ) $existing[ (string) $d['id'] ] = true;
    }
    return $existing;
}
