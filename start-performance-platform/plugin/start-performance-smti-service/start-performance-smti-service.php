<?php
/*
 * Plugin Name: Start Performance — SMTI Service
 * Description: SMTI service request portal and operations dashboard for the Start Performance Platform
 * Version:     1.2.15
 * Author:      Richard Brashear / Start Performance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SP_SMTI_VERSION',    '1.2.15' );
define( 'SP_SMTI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// ── Boot ──────────────────────────────────────────────────────────────────────

add_action( 'plugins_loaded', 'sp_smti_boot', 20 );

function sp_smti_boot() {
    if ( ! function_exists( 'sp_register_view' ) ) {
        add_action( 'admin_notices', 'sp_smti_dependency_notice' );
        return;
    }
    sp_smti_register();
}

function sp_smti_dependency_notice() {
    echo '<div class="notice notice-error"><p><strong>Start Performance — SMTI Service</strong> requires the Start Performance core plugin.</p></div>';
}

// ── Settings helper ───────────────────────────────────────────────────────────

function sp_smti_get_settings() {
    $o = get_option( 'sp_smti_settings', array() );
    return array(
        'hubspot_token'        => isset( $o['hubspot_token'] )        ? $o['hubspot_token']        : '',
        'allowed_origin'       => isset( $o['allowed_origin'] )       ? $o['allowed_origin']       : '',
        'form_token'           => isset( $o['form_token'] )           ? $o['form_token']           : '',
        'recaptcha_site_key'   => isset( $o['recaptcha_site_key'] )   ? $o['recaptcha_site_key']   : '',
        'recaptcha_secret_key' => isset( $o['recaptcha_secret_key'] ) ? $o['recaptcha_secret_key'] : '',
        'hubdb_table_id'       => isset( $o['hubdb_table_id'] )       ? $o['hubdb_table_id']       : '279292269',
        'ticket_pipeline_id'   => isset( $o['ticket_pipeline_id'] )   ? $o['ticket_pipeline_id']   : '0',
        'ticket_stage_id'      => isset( $o['ticket_stage_id'] )      ? $o['ticket_stage_id']      : '',
    );
}

// ── Registration ──────────────────────────────────────────────────────────────

function sp_smti_register() {
    sp_register_addon( 'sp-smti', array(
        'name'        => 'SMTI Service',
        'version'     => SP_SMTI_VERSION,
        'description' => 'SMTI service request portal — serial number lookup, HubSpot ticket sync, attachment upload, and internal service operations dashboard.',
        'icon'        => '<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>',
        'plugin_file' => plugin_basename( __FILE__ ),
        'core_slot'   => 'service-core',
    ) );

    sp_register_view( 'smti', SP_SMTI_PLUGIN_DIR . 'templates/views/smti.php' );

    add_filter( 'sp_nav_items',     'sp_smti_nav_items', 11 );
    add_filter( 'sp_allowed_views', 'sp_smti_allowed_views' );

    add_action( 'sp_post_handler_smti_stage',  'sp_smti_handle_stage_update' );
    add_action( 'sp_post_handler_smti_note',   'sp_smti_handle_add_note' );
    add_action( 'sp_post_handler_smti_assign', 'sp_smti_handle_assign_owner' );
    add_action( 'sp_post_handler_smti_update', 'sp_smti_handle_ticket_update' );
    add_action( 'sp_post_handler_smti_config', 'sp_smti_save_settings_handler' );

    add_action( 'sp_dashboard_before_stats', 'sp_smti_dashboard_stats' );
    add_action( 'sp_settings_sections',     'sp_smti_settings_section' );

    add_action( 'rest_api_init', 'sp_smti_register_routes' );
    add_action( 'init',          'sp_smti_handle_options_requests' );

    add_filter( 'sp_intel_summary_lines', 'sp_smti_intel_summary_lines', 20, 4 );
    add_filter( 'sp_intel_kpi_cards',     'sp_smti_intel_kpi_cards' );
    add_action( 'sp_intel_after_kpi',     'sp_smti_service_intel_stage_panel' );
}

// ── Service analytics (live HubSpot) ───────────────────────────────────────────
// Single source of truth shared by the KPI cards, the "by stage" panel, the AI
// summary, and the main-dashboard high-priority banner — so every surface agrees
// and we hit HubSpot once per request (static-cached). Returns open workload
// (excludes the pipeline's closed state), a labelled stage breakdown in workflow
// order, and the high-priority / aging / waiting-on-customer follow-up signals.

function sp_smti_service_analytics( $age_days = 30 ) {
    static $cache = array();
    $key = (string) $age_days;
    if ( isset( $cache[ $key ] ) ) return $cache[ $key ];

    $out = array(
        'ok' => false, 'total' => 0, 'open' => 0, 'closed' => 0,
        'by_stage' => array(), 'high_priority_open' => 0,
        'aging_open' => 0, 'aging_days' => (int) $age_days,
        'waiting_customer' => 0, 'oldest_days' => 0,
    );

    $settings = sp_smti_get_settings();
    if ( empty( $settings['hubspot_token'] ) ) { $cache[ $key ] = $out; return $out; }
    $pipeline = trim( $settings['ticket_pipeline_id'] );

    // Stage metadata — label, closed-state, and display order for stable output.
    $stage_label = array(); $stage_closed = array(); $stage_order = array();
    if ( $pipeline !== '' ) {
        $pr = sp_smti_hs_request( 'GET', '/crm/v3/pipelines/tickets/' . rawurlencode( $pipeline ) );
        if ( ! is_wp_error( $pr ) && (int) $pr['status'] === 200 ) {
            $stages = isset( $pr['body']['stages'] ) ? $pr['body']['stages'] : array();
            usort( $stages, 'sp_smti_stage_order_cmp' );
            $i = 0;
            foreach ( $stages as $s ) {
                $sid = (string) $s['id'];
                $stage_label[ $sid ]  = isset( $s['label'] ) ? (string) $s['label'] : $sid;
                $stage_order[ $sid ]  = $i++;
                $stage_closed[ $sid ] = ( isset( $s['metadata']['ticketState'] ) && $s['metadata']['ticketState'] === 'CLOSED' );
            }
        }
    }

    $base = array();
    if ( $pipeline !== '' ) $base[] = array( 'propertyName' => 'hs_pipeline', 'operator' => 'EQ', 'value' => $pipeline );

    $resp = sp_smti_hs_request( 'POST', '/crm/v3/objects/tickets/search', array(
        'filterGroups' => array( array( 'filters' => $base ) ),
        'properties'   => array( 'hs_pipeline_stage', 'hs_ticket_priority', 'createdate' ),
        'sorts'        => array( 'createdate' ),
        'limit'        => 100,
    ) );
    if ( is_wp_error( $resp ) || (int) $resp['status'] !== 200 ) { $cache[ $key ] = $out; return $out; }

    $out['ok']    = true;
    $out['total'] = isset( $resp['body']['total'] ) ? (int) $resp['body']['total'] : 0;
    $tickets      = isset( $resp['body']['results'] ) ? $resp['body']['results'] : array();

    $now = time();
    $stage_counts = array();
    foreach ( $tickets as $t ) {
        $p   = isset( $t['properties'] ) ? $t['properties'] : array();
        $sid = isset( $p['hs_pipeline_stage'] ) ? (string) $p['hs_pipeline_stage'] : '';
        if ( ! empty( $stage_closed[ $sid ] ) ) { $out['closed']++; continue; }
        $out['open']++;
        $stage_counts[ $sid ] = ( isset( $stage_counts[ $sid ] ) ? $stage_counts[ $sid ] : 0 ) + 1;
        $label = isset( $stage_label[ $sid ] ) ? $stage_label[ $sid ] : $sid;
        if ( stripos( $label, 'customer' ) !== false ) $out['waiting_customer']++;
        if ( isset( $p['hs_ticket_priority'] ) && $p['hs_ticket_priority'] === 'HIGH' ) $out['high_priority_open']++;
        if ( ! empty( $p['createdate'] ) ) {
            $age = (int) floor( ( $now - strtotime( $p['createdate'] ) ) / DAY_IN_SECONDS );
            if ( $age > $out['oldest_days'] ) $out['oldest_days'] = $age;
            if ( $age >= (int) $age_days ) $out['aging_open']++;
        }
    }

    $by_stage = array();
    foreach ( $stage_counts as $sid => $cnt ) {
        $by_stage[] = array(
            'label' => isset( $stage_label[ $sid ] ) ? $stage_label[ $sid ] : $sid,
            'count' => $cnt,
            'order' => isset( $stage_order[ $sid ] ) ? $stage_order[ $sid ] : 999,
        );
    }
    usort( $by_stage, 'sp_smti_stage_row_cmp' );
    $out['by_stage'] = $by_stage;

    $cache[ $key ] = $out;
    return $out;
}

// PHP 5.6-safe comparators (avoid the spaceship operator).
function sp_smti_stage_order_cmp( $a, $b ) {
    $ao = isset( $a['displayOrder'] ) ? (int) $a['displayOrder'] : 0;
    $bo = isset( $b['displayOrder'] ) ? (int) $b['displayOrder'] : 0;
    if ( $ao === $bo ) return 0;
    return ( $ao < $bo ) ? -1 : 1;
}
function sp_smti_stage_row_cmp( $a, $b ) {
    if ( $a['order'] === $b['order'] ) return 0;
    return ( $a['order'] < $b['order'] ) ? -1 : 1;
}

// ── Intelligence Core KPI cards (live HubSpot) ─────────────────────────────────
// SMTI Service tickets live in HubSpot, not a local table, so the KPI Dashboard's
// core sp_tickets card is always absent on SMTI. These feed it the real numbers.

function sp_smti_intel_kpi_cards( $cards ) {
    $a = sp_smti_service_analytics();
    if ( empty( $a['ok'] ) ) return $cards;

    $sub = 'live HubSpot pipeline';
    $accent = 'accent';
    if ( $a['aging_open'] > 0 ) { $sub = $a['aging_open'] . ' aging ' . $a['aging_days'] . '+ days'; $accent = 'warn'; }

    $cards[] = array(
        'label'  => 'Service Requests',
        'value'  => number_format( $a['open'] ),
        'sub'    => $sub,
        'accent' => $accent,
    );

    // High Priority — always on the KPI board (0 is meaningful here: "we're clean").
    $hp = (int) $a['high_priority_open'];
    $cards[] = array(
        'label'  => 'High Priority',
        'value'  => number_format( $hp ),
        'sub'    => $hp > 0 ? 'needs immediate attention' : 'none open',
        'accent' => $hp > 0 ? 'danger' : 'green',
    );
    return $cards;
}

// ── Intelligence Core panel: Service Requests by Stage ─────────────────────────

function sp_smti_service_intel_stage_panel() {
    if ( function_exists( 'sp_is_view_hidden' ) && sp_is_view_hidden( 'smti' ) ) return;
    $a = sp_smti_service_analytics();
    if ( empty( $a['ok'] ) || empty( $a['by_stage'] ) ) return;
    ?>
    <div class="sp-intel-section">
        <h3 style="font-size:1.15rem;font-weight:800;color:#0f172a;text-transform:none;letter-spacing:0;margin:0 0 4px;">Service Requests by Stage</h3>
        <p style="margin:0 0 14px;color:#64748b;font-size:.85rem;">Open service tickets by workflow stage — live from HubSpot.</p>
        <?php foreach ( $a['by_stage'] as $row ) :
            $pct     = $a['open'] > 0 ? (int) round( ( $row['count'] / $a['open'] ) * 100 ) : 0;
            $stalled = stripos( $row['label'], 'customer' ) !== false;
        ?>
        <div class="sp-intel-stat-row">
            <span class="sp-intel-stat-label"><?php echo esc_html( $row['label'] ); ?><?php if ( $stalled ) echo ' <span style="color:#b45309;font-weight:600;">(stalled on customer)</span>'; ?></span>
            <span class="sp-intel-stat-value"><?php echo (int) $row['count']; ?> <span style="color:#94a3b8;font-weight:500;">(<?php echo (int) $pct; ?>%)</span></span>
        </div>
        <?php endforeach; ?>
        <?php if ( $a['high_priority_open'] > 0 || $a['aging_open'] > 0 || $a['oldest_days'] > 0 ) : ?>
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid #e2e8f0;display:flex;gap:20px;flex-wrap:wrap;font-size:.85rem;">
            <?php if ( $a['high_priority_open'] > 0 ) : ?><span style="color:#b91c1c;font-weight:700;"><?php echo (int) $a['high_priority_open']; ?> high-priority</span><?php endif; ?>
            <?php if ( $a['aging_open'] > 0 ) : ?><span style="color:#b45309;font-weight:700;"><?php echo (int) $a['aging_open']; ?> aging <?php echo (int) $a['aging_days']; ?>+ days</span><?php endif; ?>
            <?php if ( $a['oldest_days'] > 0 ) : ?><span style="color:#64748b;">oldest open: <?php echo (int) $a['oldest_days']; ?> days</span><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// ── Intelligence Core KPI provider ─────────────────────────────────────────────
// SMTI Service has no local DB tables — ticket data lives in HubSpot. Without this
// hook, Intelligence Core's AI summary has no way to see service-ticket activity
// (its own sp_tickets check is always false here).

function sp_smti_intel_summary_lines( $lines, $days, $since, $today ) {
    $a = sp_smti_service_analytics();
    if ( empty( $a['ok'] ) ) return $lines;

    // New-in-period count (a quick separate search — not part of the open snapshot).
    $settings = sp_smti_get_settings();
    $pipeline = trim( $settings['ticket_pipeline_id'] );
    $base = array();
    if ( $pipeline !== '' ) $base[] = array( 'propertyName' => 'hs_pipeline', 'operator' => 'EQ', 'value' => $pipeline );
    $since_ms  = strtotime( $since ) * 1000;
    $new       = null;
    $new_resp  = sp_smti_hs_request( 'POST', '/crm/v3/objects/tickets/search', array(
        'filterGroups' => array( array( 'filters' => array_merge( $base, array(
            array( 'propertyName' => 'createdate', 'operator' => 'GTE', 'value' => (string) $since_ms ),
        ) ) ) ),
        'limit' => 1,
    ) );
    if ( ! is_wp_error( $new_resp ) && (int) $new_resp['status'] === 200 ) $new = (int) $new_resp['body']['total'];

    $line = "- SMTI service requests: {$a['open']} open";
    if ( $new !== null )                 $line .= ", {$new} new in the last {$days} days";
    if ( $a['high_priority_open'] > 0 )  $line .= ", {$a['high_priority_open']} high-priority";
    $lines[] = $line;

    if ( ! empty( $a['by_stage'] ) ) {
        $parts = array();
        foreach ( $a['by_stage'] as $row ) $parts[] = $row['label'] . ' (' . $row['count'] . ')';
        $lines[] = '- SMTI service requests by workflow stage: ' . implode( ', ', $parts ) . '.';
    }

    $flags = array();
    if ( $a['aging_open'] > 0 )       $flags[] = $a['aging_open'] . ' open ' . $a['aging_days'] . '+ days (oldest ' . $a['oldest_days'] . ' days) — follow-up candidates';
    if ( $a['waiting_customer'] > 0 ) $flags[] = $a['waiting_customer'] . ' stalled waiting on the customer';
    if ( ! empty( $flags ) ) $lines[] = '- SMTI service attention: ' . implode( '; ', $flags ) . '.';

    return $lines;
}

// ── Nav + views ───────────────────────────────────────────────────────────────

function sp_smti_nav_items( $items ) {
    // Replace the standard Tickets item with Service Requests under service-core
    $result = array();
    foreach ( $items as $item ) {
        // Drop the standard tickets nav item — SMTI replaces it
        if ( ! empty( $item['view'] ) && $item['view'] === 'tickets' ) continue;
        $result[] = $item;
        if ( ! empty( $item['section'] ) && ! empty( $item['section_id'] ) && $item['section_id'] === 'service-core' ) {
            $result[] = array(
                'view'   => 'smti',
                'label'  => 'Service Requests',
                'custom' => true,
                'icon'   => '<path fill="currentColor" d="M20 2H4a1 1 0 00-1 1v18l4-4h13a1 1 0 001-1V3a1 1 0 00-1-1zm-9 11H8v-2h3v2zm0-3H8V8h3v2zm5 3h-3v-2h3v2zm0-3h-3V8h3v2z"/>',
            );
        }
    }
    return $result;
}

function sp_smti_allowed_views( $views ) {
    $views[] = 'smti';
    return $views;
}

// ── POST handlers ─────────────────────────────────────────────────────────────

function sp_smti_handle_stage_update( $id ) {
    if ( ! sp_is_admin_member() ) { wp_redirect( home_url( '/sp-app/?view=smti' ) ); exit; }
    $ticket_id = sanitize_text_field( isset( $_POST['ticket_id'] ) ? $_POST['ticket_id'] : '' );
    $stage_id  = sanitize_text_field( isset( $_POST['stage_id'] )  ? $_POST['stage_id']  : '' );
    if ( $ticket_id !== '' && $stage_id !== '' ) {
        sp_smti_hs_request( 'PATCH', '/crm/v3/objects/tickets/' . rawurlencode( $ticket_id ), array(
            'properties' => array( 'hs_pipeline_stage' => $stage_id ),
        ) );
        $stage_map = sp_smti_stage_map();
        $label = isset( $stage_map[ $stage_id ] ) ? $stage_map[ $stage_id ] : $stage_id;
        sp_smti_add_note_to_ticket( $ticket_id, 'Dashboard update: status changed to ' . $label . '.' );
    }
    wp_redirect( home_url( '/sp-app/?view=smti&action=detail&id=' . urlencode( $ticket_id ) . '&saved=1&t=' . time() ) ); exit;
}

function sp_smti_handle_add_note( $id ) {
    $ticket_id = sanitize_text_field( isset( $_POST['ticket_id'] ) ? $_POST['ticket_id'] : '' );
    $note      = sanitize_textarea_field( isset( $_POST['note'] )  ? $_POST['note']      : '' );
    if ( $ticket_id !== '' ) {
        $up = sp_smti_upload_note_files( isset( $_FILES['smti_note_files'] ) ? $_FILES['smti_note_files'] : array() );
        if ( $note !== '' || ! empty( $up['ids'] ) ) {
            $member = sp_get_current_team_member();
            $by     = $member ? $member->name : 'SP Dashboard';
            $body   = 'Note from ' . $by . ":\n" . ( $note !== '' ? $note : '(attachment only)' );
            if ( ! empty( $up['failed'] ) ) $body .= "\n\nUpload failed: " . implode( ', ', $up['failed'] );
            sp_smti_add_note_to_ticket( $ticket_id, $body, '', '', $up['ids'] );
        }
    }
    wp_redirect( home_url( '/sp-app/?view=smti&action=detail&id=' . urlencode( $ticket_id ) . '&saved=1&t=' . time() ) ); exit;
}

function sp_smti_handle_assign_owner( $id ) {
    if ( ! sp_is_admin_member() ) { wp_redirect( home_url( '/sp-app/?view=smti' ) ); exit; }
    $ticket_id = sanitize_text_field( isset( $_POST['ticket_id'] ) ? $_POST['ticket_id'] : '' );
    $owner_id  = preg_replace( '/[^0-9]/', '', isset( $_POST['owner_id'] ) ? $_POST['owner_id'] : '' );
    if ( $ticket_id !== '' && $owner_id !== '' ) {
        sp_smti_hs_request( 'PATCH', '/crm/v3/objects/tickets/' . rawurlencode( $ticket_id ), array(
            'properties' => array( 'hubspot_owner_id' => $owner_id ),
        ) );
        sp_smti_add_note_to_ticket( $ticket_id, 'Dashboard update: assigned to HubSpot owner ID ' . $owner_id . '.' );
    }
    wp_redirect( home_url( '/sp-app/?view=smti&action=detail&id=' . urlencode( $ticket_id ) . '&saved=1&t=' . time() ) ); exit;
}

function sp_smti_handle_ticket_update( $id ) {
    if ( ! function_exists( 'sp_is_admin_member' ) || ! sp_is_admin_member() ) { wp_redirect( home_url( '/sp-app/?view=smti' ) ); exit; }
    $ticket_id       = sanitize_text_field( isset( $_POST['ticket_id'] )       ? $_POST['ticket_id']       : '' );
    $serial_number   = sanitize_text_field( isset( $_POST['serial_number'] )   ? $_POST['serial_number']   : '' );
    $part_id         = sanitize_text_field( isset( $_POST['part_id'] )         ? $_POST['part_id']         : '' );
    $equipment_model = sanitize_text_field( isset( $_POST['equipment_model'] ) ? $_POST['equipment_model'] : '' );
    $company         = sanitize_text_field( isset( $_POST['company'] )         ? $_POST['company']         : '' );
    $location_name   = sanitize_text_field( isset( $_POST['location_name'] )   ? $_POST['location_name']   : '' );

    if ( $ticket_id === '' ) {
        wp_redirect( home_url( '/sp-app/?view=smti' ) ); exit;
    }

    // Fetch the current ticket to get subject + content so we can update the body text
    $current = sp_smti_hs_request( 'GET', '/crm/v3/objects/tickets/' . rawurlencode( $ticket_id ) . '?properties=subject,content' );
    $cur_props   = ( ! is_wp_error( $current ) && isset( $current['body']['properties'] ) ) ? $current['body']['properties'] : array();
    $cur_subject = isset( $cur_props['subject'] ) ? $cur_props['subject'] : '';
    $cur_content = isset( $cur_props['content'] ) ? $cur_props['content'] : '';

    // Update values in the body text by replacing labelled lines
    $replacements = array();
    if ( $serial_number   !== '' ) $replacements['Serial Number']   = $serial_number;
    if ( $part_id         !== '' ) $replacements['Part ID']         = $part_id;
    if ( $equipment_model !== '' ) $replacements['Equipment Model'] = $equipment_model;
    if ( $company         !== '' ) $replacements['Company']         = $company;
    if ( $location_name   !== '' ) $replacements['Location Name']   = $location_name;

    $new_content = $cur_content;
    $changed     = array();
    foreach ( $replacements as $label => $value ) {
        $pattern = '/^(' . preg_quote( $label, '/' ) . '\s*:\s*)(.*)$/mi';
        if ( preg_match( $pattern, $new_content ) ) {
            $new_content = preg_replace( $pattern, '${1}' . $value, $new_content );
        } else {
            $new_content .= "\n" . $label . ': ' . $value;
        }
        $changed[] = $label . ' → ' . $value;
    }

    $props = array( 'content' => $new_content );

    // Also update subject if serial number changed
    if ( $serial_number !== '' && $cur_subject !== '' ) {
        $props['subject'] = preg_replace( '/SN:[^\|]+/', 'SN:' . $serial_number, $cur_subject );
    }

    sp_smti_hs_request( 'PATCH', '/crm/v3/objects/tickets/' . rawurlencode( $ticket_id ), array( 'properties' => $props ) );

    if ( ! empty( $changed ) ) {
        sp_smti_add_note_to_ticket( $ticket_id, 'Tech update — corrected ticket info:' . "\n" . implode( "\n", $changed ) );
    }

    // No delay needed: the detail page re-reads the ticket by ID, and HubSpot's
    // GET-by-id is read-after-write consistent (only the SEARCH index lags, which
    // this path doesn't use). Verified 6/6 on a probe ticket.
    wp_redirect( home_url( '/sp-app/?view=smti&action=detail&id=' . urlencode( $ticket_id ) . '&saved=1&t=' . time() ) ); exit;
}

function sp_smti_save_settings_handler( $id ) {
    if ( ! function_exists( 'sp_is_super_admin' ) || ! sp_is_super_admin() ) { wp_redirect( home_url( '/sp-app/?view=settings' ) ); exit; }
    update_option( 'sp_smti_settings', array(
        'hubspot_token'        => sanitize_text_field( isset( $_POST['sp_smti_hubspot_token'] )        ? $_POST['sp_smti_hubspot_token']        : '' ),
        'allowed_origin'       => esc_url_raw( trim( isset( $_POST['sp_smti_allowed_origin'] )         ? $_POST['sp_smti_allowed_origin']         : '' ) ),
        'form_token'           => sanitize_text_field( isset( $_POST['sp_smti_form_token'] )           ? $_POST['sp_smti_form_token']           : '' ),
        'recaptcha_site_key'   => sanitize_text_field( isset( $_POST['sp_smti_recaptcha_site_key'] )   ? $_POST['sp_smti_recaptcha_site_key']   : '' ),
        'recaptcha_secret_key' => sanitize_text_field( isset( $_POST['sp_smti_recaptcha_secret_key'] ) ? $_POST['sp_smti_recaptcha_secret_key'] : '' ),
        'hubdb_table_id'       => sanitize_text_field( isset( $_POST['sp_smti_hubdb_table_id'] )       ? $_POST['sp_smti_hubdb_table_id']       : '279292269' ),
        'ticket_pipeline_id'   => sanitize_text_field( isset( $_POST['sp_smti_ticket_pipeline_id'] )   ? $_POST['sp_smti_ticket_pipeline_id']   : '0' ),
        'ticket_stage_id'      => sanitize_text_field( isset( $_POST['sp_smti_ticket_stage_id'] )      ? $_POST['sp_smti_ticket_stage_id']      : '' ),
    ) );
    wp_redirect( home_url( '/sp-app/?view=settings&saved=1' ) ); exit;
}

// ── Settings section (injected into SP Settings page) ─────────────────────────

function sp_smti_settings_section() {
    if ( ! function_exists( 'sp_is_super_admin' ) || ! sp_is_super_admin() ) return;
    $s = sp_smti_get_settings();
    ?>
    <div style="margin-top:16px;border:2px solid #f59e0b;border-radius:12px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#451a03,#78350f);padding:12px 20px;display:flex;align-items:center;gap:10px;">
            <svg viewBox="0 0 20 20" fill="#fbbf24" width="16" height="16"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
            <span style="font-size:.8rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#fbbf24;">Super Admin Only — API &amp; Integration Keys</span>
        </div>
        <div class="sp-form-card" style="padding:20px 24px;background:#fff;">
            <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
                <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                <input type="hidden" name="sp_type" value="smti_config">
                <input type="hidden" name="sp_id"   value="0">
                <h2 class="sp-section-heading" style="margin-top:0;">SMTI Service Integration</h2>
                <div class="sp-form-row">
                    <div class="sp-field">
                        <label>HubSpot Private App Token</label>
                        <input type="text" name="sp_smti_hubspot_token" value="<?php echo esc_attr( $s['hubspot_token'] ); ?>" autocomplete="new-password" style="font-family:monospace;font-size:12px">
                    </div>
                    <div class="sp-field">
                        <label>Allowed Origin</label>
                        <input type="url" name="sp_smti_allowed_origin" value="<?php echo esc_attr( $s['allowed_origin'] ); ?>" placeholder="https://smti.co">
                    </div>
                </div>
                <div class="sp-form-row">
                    <div class="sp-field">
                        <label>Form Token (X-SMTI-Form-Token)</label>
                        <input type="text" name="sp_smti_form_token" value="<?php echo esc_attr( $s['form_token'] ); ?>" autocomplete="off">
                        <span class="sp-hint">Must match <code>FORM_TOKEN</code> in the smti.co page JavaScript.</span>
                    </div>
                    <div class="sp-field">
                        <label>HubDB Table ID</label>
                        <input type="text" name="sp_smti_hubdb_table_id" value="<?php echo esc_attr( $s['hubdb_table_id'] ); ?>" placeholder="279292269">
                    </div>
                </div>
                <div class="sp-form-row">
                    <div class="sp-field">
                        <label>Ticket Pipeline ID</label>
                        <input type="text" name="sp_smti_ticket_pipeline_id" value="<?php echo esc_attr( $s['ticket_pipeline_id'] ); ?>" placeholder="0">
                    </div>
                    <div class="sp-field">
                        <label>Default Ticket Stage ID</label>
                        <input type="text" name="sp_smti_ticket_stage_id" value="<?php echo esc_attr( $s['ticket_stage_id'] ); ?>">
                    </div>
                </div>
                <div class="sp-form-row">
                    <div class="sp-field">
                        <label>reCAPTCHA Site Key <span style="font-weight:400;color:#94a3b8">(optional)</span></label>
                        <input type="text" name="sp_smti_recaptcha_site_key" value="<?php echo esc_attr( $s['recaptcha_site_key'] ); ?>" autocomplete="off">
                    </div>
                    <div class="sp-field">
                        <label>reCAPTCHA Secret Key <span style="font-weight:400;color:#94a3b8">(optional)</span></label>
                        <input type="text" name="sp_smti_recaptcha_secret_key" value="<?php echo esc_attr( $s['recaptcha_secret_key'] ); ?>" autocomplete="off" style="font-family:monospace;font-size:12px">
                    </div>
                </div>
                <div class="sp-form-actions">
                    <button type="submit" class="sp-btn sp-btn-primary">Save SMTI Service Settings</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

// ── Dashboard stats ───────────────────────────────────────────────────────────

function sp_smti_dashboard_stats() {
    if ( sp_is_view_hidden( 'smti' ) ) return;
    $a = sp_smti_service_analytics();
    if ( empty( $a['ok'] ) ) return;
    $hp = (int) $a['high_priority_open'];
    ?>
    <?php if ( $hp > 0 ) : ?>
    <a href="<?php echo esc_url( home_url( '/sp-app/?view=smti' ) ); ?>" style="display:flex;align-items:center;gap:10px;text-decoration:none;background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #dc2626;border-radius:10px;padding:12px 16px;margin-bottom:14px;">
        <svg viewBox="0 0 20 20" fill="#dc2626" width="18" height="18" style="flex:0 0 auto;"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        <span style="color:#991b1b;font-weight:700;"><?php echo number_format( $hp ); ?> high-priority service request<?php echo $hp === 1 ? '' : 's'; ?> need attention</span>
        <span style="margin-left:auto;color:#dc2626;font-weight:600;font-size:13px;">Review &rarr;</span>
    </a>
    <?php endif; ?>
    <div class="sp-stats sp-stats-2">
        <div class="sp-stat-card" style="border-top-color:#0ea5e9">
            <div class="sp-stat-value"><?php echo number_format( $a['open'] ); ?></div>
            <div class="sp-stat-label">SMTI Service Requests</div>
        </div>
        <div class="sp-stat-card" style="border-top-color:#6366f1">
            <div class="sp-stat-value">
                <a href="<?php echo esc_url( home_url( '/sp-app/?view=smti' ) ); ?>" style="color:inherit;text-decoration:none;font-size:13px;font-weight:600">View All &rarr;</a>
            </div>
            <div class="sp-stat-label">Open Dashboard</div>
        </div>
    </div>
    <?php
}

// ── CORS ──────────────────────────────────────────────────────────────────────

function sp_smti_send_cors_headers() {
    $settings       = sp_smti_get_settings();
    $allowed_origin = $settings['allowed_origin'];
    if ( empty( $allowed_origin ) ) return;
    $request_origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? trim( $_SERVER['HTTP_ORIGIN'] ) : '';
    if ( $request_origin && rtrim( $request_origin, '/' ) === rtrim( $allowed_origin, '/' ) ) {
        header( 'Access-Control-Allow-Origin: '   . $allowed_origin );
        header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
        header( 'Access-Control-Allow-Headers: Content-Type, Authorization, X-SMTI-Form-Token' );
        header( 'Vary: Origin' );
    }
}

function sp_smti_handle_options_requests() {
    if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || strtoupper( $_SERVER['REQUEST_METHOD'] ) !== 'OPTIONS' ) return;
    if ( empty( $_SERVER['REQUEST_URI'] ) || strpos( $_SERVER['REQUEST_URI'], '/wp-json/sp-smti/v1/' ) === false ) return;
    sp_smti_send_cors_headers();
    status_header( 200 );
    exit;
}

// ── REST routes ───────────────────────────────────────────────────────────────

function sp_smti_register_routes() {
    register_rest_route( 'sp-smti/v1', '/search-equipment', array(
        'methods' => 'GET', 'callback' => 'sp_smti_search_equipment', 'permission_callback' => 'sp_smti_form_permission',
    ) );
    register_rest_route( 'sp-smti/v1', '/hubspot-owners', array(
        'methods' => 'GET', 'callback' => 'sp_smti_get_owners', 'permission_callback' => 'sp_smti_form_permission',
    ) );
    register_rest_route( 'sp-smti/v1', '/create-service-ticket', array(
        'methods' => 'POST', 'callback' => 'sp_smti_create_ticket', 'permission_callback' => 'sp_smti_form_permission',
    ) );
    register_rest_route( 'sp-smti/v1', '/check-duplicate-ticket', array(
        'methods' => 'GET', 'callback' => 'sp_smti_check_duplicate_endpoint', 'permission_callback' => 'sp_smti_form_permission',
    ) );
}

// ── Auth ──────────────────────────────────────────────────────────────────────

function sp_smti_get_header( $key ) {
    if ( function_exists( 'getallheaders' ) ) {
        $headers = getallheaders();
        if ( is_array( $headers ) ) {
            foreach ( $headers as $k => $v ) {
                if ( strtolower( $k ) === strtolower( $key ) ) return $v;
            }
        }
    }
    $server_key = 'HTTP_' . strtoupper( str_replace( '-', '_', $key ) );
    return isset( $_SERVER[ $server_key ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ $server_key ] ) ) : '';
}

function sp_smti_form_permission() {
    $settings = sp_smti_get_settings();
    $expected = trim( $settings['form_token'] );
    $provided = sp_smti_get_header( 'X-SMTI-Form-Token' );
    if ( empty( $expected ) ) {
        return new WP_Error( 'sp_smti_not_configured', 'Form token is not configured.', array( 'status' => 500 ) );
    }
    if ( empty( $provided ) || ! hash_equals( $expected, $provided ) ) {
        return new WP_Error( 'sp_smti_forbidden', 'Invalid or missing form token.', array( 'status' => 403 ) );
    }
    $allowed_origin = $settings['allowed_origin'];
    if ( ! empty( $allowed_origin ) ) {
        $origin  = isset( $_SERVER['HTTP_ORIGIN'] )  ? trim( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) )  : '';
        $referer = isset( $_SERVER['HTTP_REFERER'] ) ? trim( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
        $ok = ( $origin  && stripos( $origin,  $allowed_origin ) === 0 )
           || ( $referer && stripos( $referer, $allowed_origin ) === 0 );
        if ( ! $ok ) {
            return new WP_Error( 'sp_smti_bad_origin', 'Origin not allowed.', array( 'status' => 403 ) );
        }
    }
    return true;
}

// ── HubSpot HTTP helper ───────────────────────────────────────────────────────

function sp_smti_hs_request( $method, $path, $body = null ) {
    $settings = sp_smti_get_settings();
    $token    = $settings['hubspot_token'];
    if ( empty( $token ) ) return new WP_Error( 'sp_smti_missing_token', 'HubSpot token is not configured.' );
    $args = array(
        'method'  => $method,
        'timeout' => 25,
        'headers' => array( 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ),
    );
    if ( ! is_null( $body ) ) $args['body'] = wp_json_encode( $body );
    $response = wp_remote_request( 'https://api.hubapi.com' . $path, $args );
    if ( is_wp_error( $response ) ) return $response;
    return array(
        'status' => wp_remote_retrieve_response_code( $response ),
        'body'   => json_decode( wp_remote_retrieve_body( $response ), true ),
    );
}

// ── Stage map ─────────────────────────────────────────────────────────────────

function sp_smti_stage_map() {
    // Perf: the pipeline's stage list changes only when the pipeline is edited in
    // HubSpot, but this ran on EVERY list load, detail load, and status update
    // (~380ms each) just to translate a stage id into a label. Cache it.
    // Static = once per request; transient = 5 min across requests.
    static $memo = null;
    if ( is_array( $memo ) ) return $memo;

    $settings = sp_smti_get_settings();
    $pipeline = trim( $settings['ticket_pipeline_id'] );
    if ( $pipeline === '' ) return array();

    $ck     = 'sp_smti_stage_map_' . md5( $pipeline );
    $cached = get_transient( $ck );
    if ( is_array( $cached ) && ! empty( $cached ) ) { $memo = $cached; return $memo; }

    $response = sp_smti_hs_request( 'GET', '/crm/v3/pipelines/tickets/' . rawurlencode( $pipeline ) );
    if ( is_wp_error( $response ) || (int) $response['status'] !== 200 ) return array();
    $stages = isset( $response['body']['stages'] ) ? $response['body']['stages'] : array();
    $map = array();
    foreach ( $stages as $stage ) {
        if ( ! empty( $stage['id'] ) ) {
            $map[ (string) $stage['id'] ] = isset( $stage['label'] ) ? (string) $stage['label'] : (string) $stage['id'];
        }
    }
    if ( ! empty( $map ) ) set_transient( $ck, $map, 5 * MINUTE_IN_SECONDS );
    $memo = $map;
    return $memo;
}

// ── Equipment search ──────────────────────────────────────────────────────────

function sp_smti_search_equipment( WP_REST_Request $request ) {
    sp_smti_send_cors_headers();
    $serial   = trim( sanitize_text_field( $request->get_param( 'serial' ) ) );
    if ( empty( $serial ) ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => 'Serial number is required.' ), 400 );
    }
    $settings = sp_smti_get_settings();
    $table_id = trim( $settings['hubdb_table_id'] );
    if ( empty( $table_id ) ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => 'HubDB table ID is not configured.' ), 500 );
    }
    $result = sp_smti_hubdb_search( $table_id, 'serial_number', $serial );
    if ( empty( $result ) ) {
        $normalized = preg_replace( '/[^a-zA-Z0-9]/', '', strtoupper( $serial ) );
        $result = sp_smti_hubdb_search( $table_id, 'serial_number_normalized', $normalized );
    }
    if ( empty( $result ) ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => 'No equipment found for serial number: ' . $serial ), 200 );
    }
    $values    = isset( $result['values'] ) ? $result['values'] : array();
    $equipment = sp_smti_map_hubdb_row( $values, $serial );
    $sn        = isset( $equipment['serial_number'] ) ? $equipment['serial_number'] : $serial;
    $duplicate = sp_smti_find_duplicate_ticket( $sn, '' );
    $resp      = array( 'success' => true, 'equipment' => $equipment );
    if ( ! empty( $duplicate['found'] ) ) {
        $resp['duplicate_detected'] = true;
        $resp['existing_ticket']    = $duplicate;
    }
    return new WP_REST_Response( $resp, 200 );
}

function sp_smti_hubdb_search( $table_id, $column, $value ) {
    $path     = '/cms/v3/hubdb/tables/' . rawurlencode( $table_id ) . '/rows?' . rawurlencode( $column ) . '__eq=' . rawurlencode( $value ) . '&limit=1';
    $response = sp_smti_hs_request( 'GET', $path );
    if ( is_wp_error( $response ) || (int) $response['status'] !== 200 ) return null;
    return ! empty( $response['body']['results'][0] ) ? $response['body']['results'][0] : null;
}

function sp_smti_map_hubdb_row( $values, $serial_fallback ) {
    $keys = array( 'serial_number', 'asset_name', 'part_id', 'part_description', 'order_number', 'invoice_date_ship_date', 'associated_company_name', 'smti_customer_id', 'smti_location_id', 'smti_location_key', 'location_name', 'shipping_address_1', 'shipping_address_2', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country', 'match_status', 'import_notes' );
    $out = array();
    foreach ( $keys as $key ) {
        $out[ $key ] = sp_smti_hubdb_val( $values, $key );
    }
    if ( empty( $out['serial_number'] ) ) $out['serial_number'] = $serial_fallback;
    return $out;
}

function sp_smti_hubdb_val( $values, $key ) {
    if ( ! isset( $values[ $key ] ) ) return '';
    $v = $values[ $key ];
    if ( is_array( $v ) ) return isset( $v['name'] ) ? (string) $v['name'] : implode( ', ', array_column( $v, 'name' ) );
    return (string) $v;
}

// ── Owners ────────────────────────────────────────────────────────────────────

function sp_smti_get_owners( WP_REST_Request $request ) {
    sp_smti_send_cors_headers();
    $response = sp_smti_hs_request( 'GET', '/crm/v3/owners/?limit=100' );
    if ( is_wp_error( $response ) || (int) $response['status'] !== 200 || empty( $response['body']['results'] ) ) {
        return new WP_REST_Response( array( 'success' => true, 'owners' => array() ), 200 );
    }
    $owners = array();
    foreach ( $response['body']['results'] as $owner ) {
        $first = isset( $owner['firstName'] ) ? $owner['firstName'] : '';
        $last  = isset( $owner['lastName'] )  ? $owner['lastName']  : '';
        $email = isset( $owner['email'] )     ? $owner['email']     : '';
        $label = trim( $first . ' ' . $last );
        if ( empty( $label ) ) $label = $email;
        if ( ! empty( $owner['id'] ) ) $owners[] = array( 'id' => (string) $owner['id'], 'label' => $label, 'email' => $email );
    }
    return new WP_REST_Response( array( 'success' => true, 'owners' => $owners ), 200 );
}

function sp_smti_get_owners_list() {
    // Owners change rarely and this runs on every admin detail-view load — cache 10 min.
    $cached = get_transient( 'sp_smti_owners_list' );
    if ( is_array( $cached ) ) return $cached;
    $response = sp_smti_hs_request( 'GET', '/crm/v3/owners/?limit=100&archived=false' );
    if ( is_wp_error( $response ) || (int) $response['status'] !== 200 || empty( $response['body']['results'] ) ) return array();
    $owners = array();
    foreach ( $response['body']['results'] as $owner ) {
        $first = isset( $owner['firstName'] ) ? $owner['firstName'] : '';
        $last  = isset( $owner['lastName'] )  ? $owner['lastName']  : '';
        $email = isset( $owner['email'] )     ? $owner['email']     : '';
        $label = trim( $first . ' ' . $last );
        if ( empty( $label ) ) $label = $email;
        if ( ! empty( $owner['id'] ) ) $owners[] = array( 'id' => (string) $owner['id'], 'label' => $label, 'email' => $email );
    }
    set_transient( 'sp_smti_owners_list', $owners, 10 * MINUTE_IN_SECONDS );
    return $owners;
}

// ── Duplicate detection ───────────────────────────────────────────────────────

function sp_smti_find_duplicate_ticket( $serial_number, $contact_email ) {
    $serial_number = trim( (string) $serial_number );
    $contact_email = sanitize_email( (string) $contact_email );
    if ( $serial_number === '' ) return array( 'found' => false );
    $created_after_ms = ( time() - ( 30 * DAY_IN_SECONDS ) ) * 1000;
    $search_body = array(
        'filterGroups' => array( array( 'filters' => array(
            array( 'propertyName' => 'subject',    'operator' => 'CONTAINS_TOKEN', 'value' => $serial_number ),
            array( 'propertyName' => 'createdate', 'operator' => 'GTE',            'value' => (string) $created_after_ms ),
        ) ) ),
        'properties' => sp_smti_ticket_properties(),
        'sorts'      => array( '-createdate' ),
        'limit'      => 5,
    );
    $response = sp_smti_hs_request( 'POST', '/crm/v3/objects/tickets/search', $search_body );
    if ( is_wp_error( $response ) || (int) $response['status'] !== 200 ) return array( 'found' => false );
    $results = isset( $response['body']['results'] ) ? $response['body']['results'] : array();
    foreach ( $results as $ticket ) {
        $props        = isset( $ticket['properties'] ) ? $ticket['properties'] : array();
        $subject      = isset( $props['subject'] ) ? (string) $props['subject'] : '';
        $content      = isset( $props['content'] ) ? (string) $props['content'] : '';
        $serial_match = stripos( $subject . "\n" . $content, $serial_number ) !== false;
        $email_match  = $contact_email === '' || stripos( $content, $contact_email ) !== false;
        if ( $serial_match && $email_match ) {
            $summary = sp_smti_ticket_summary( $ticket, array() );
            $summary['found'] = true;
            return $summary;
        }
    }
    return array( 'found' => false );
}

function sp_smti_check_duplicate_endpoint( WP_REST_Request $request ) {
    sp_smti_send_cors_headers();
    $serial = trim( sanitize_text_field( (string) $request->get_param( 'serial' ) ) );
    $email  = sanitize_email( (string) ( $request->get_param( 'contact_email' ) ?: $request->get_param( 'email' ) ) );
    if ( $serial === '' ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => 'Serial number is required.' ), 400 );
    }
    $duplicate = sp_smti_find_duplicate_ticket( $serial, $email );
    if ( empty( $duplicate['found'] ) ) {
        return new WP_REST_Response( array( 'success' => true, 'duplicate_detected' => false, 'existing_ticket' => null ), 200 );
    }
    return new WP_REST_Response( array(
        'success'            => true,
        'duplicate_detected' => true,
        'existing_ticket'    => $duplicate,
        'existing_ticket_id' => isset( $duplicate['ticket_id'] ) ? $duplicate['ticket_id'] : '',
    ), 200 );
}

// ── Create ticket ─────────────────────────────────────────────────────────────

function sp_smti_create_ticket( WP_REST_Request $request ) {
    sp_smti_send_cors_headers();
    $settings = sp_smti_get_settings();

    if ( ! empty( $settings['recaptcha_secret_key'] ) ) {
        $captcha_token = (string) ( $request->get_param( 'captcha_token' ) ?: $request->get_param( 'g-recaptcha-response' ) );
        if ( ! sp_smti_verify_recaptcha( $captcha_token, $settings['recaptcha_secret_key'] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'CAPTCHA verification failed.' ), 400 );
        }
    }

    $contact_name      = sanitize_text_field(     (string) $request->get_param( 'contact_name' ) );
    $contact_email     = sanitize_email(          (string) $request->get_param( 'contact_email' ) );
    $contact_phone     = sanitize_text_field(     (string) $request->get_param( 'contact_phone' ) );
    $contact_id        = sanitize_text_field(     (string) $request->get_param( 'contact_id' ) );
    $facility_name     = sanitize_text_field(     (string) $request->get_param( 'facility_name' ) );
    $location_name     = sanitize_text_field(     (string) $request->get_param( 'location_name' ) );
    $serial_number     = sanitize_text_field(     (string) $request->get_param( 'serial_number' ) );
    $equipment_type    = sanitize_text_field(     (string) $request->get_param( 'equipment_type' ) );
    $equipment_model   = sanitize_text_field(     (string) $request->get_param( 'equipment_model' ) );
    $asset_name        = sanitize_text_field(     (string) $request->get_param( 'asset_name' ) );
    $part_id           = sanitize_text_field(     (string) $request->get_param( 'part_id' ) );
    $part_description  = sanitize_text_field(     (string) $request->get_param( 'part_description' ) );
    $order_number      = sanitize_text_field(     (string) $request->get_param( 'order_number' ) );
    $invoice_date      = sanitize_text_field(     (string) $request->get_param( 'invoice_date_ship_date' ) );
    $smti_customer_id  = sanitize_text_field(     (string) $request->get_param( 'smti_customer_id' ) );
    $smti_location_id  = sanitize_text_field(     (string) $request->get_param( 'smti_location_id' ) );
    $smti_location_key = sanitize_text_field(     (string) $request->get_param( 'smti_location_key' ) );
    $address1          = sanitize_text_field(     (string) $request->get_param( 'address1' ) );
    $address2          = sanitize_text_field(     (string) $request->get_param( 'address2' ) );
    $city              = sanitize_text_field(     (string) $request->get_param( 'city' ) );
    $state             = sanitize_text_field(     (string) $request->get_param( 'state' ) );
    $postal_code       = sanitize_text_field(     (string) $request->get_param( 'postal_code' ) );
    $country           = sanitize_text_field(     (string) $request->get_param( 'country' ) );
    $match_status      = sanitize_text_field(     (string) $request->get_param( 'match_status' ) );
    $import_notes      = sanitize_textarea_field( (string) $request->get_param( 'import_notes' ) );
    $error_code        = sanitize_text_field(     (string) $request->get_param( 'error_code' ) );
    $priority          = sanitize_text_field(     (string) $request->get_param( 'priority' ) );
    $assigned_to       = sanitize_text_field(     (string) $request->get_param( 'assigned_to' ) );
    $issue_description = sanitize_textarea_field( (string) $request->get_param( 'issue_description' ) );
    $duplicate_action  = sanitize_text_field(     (string) $request->get_param( 'duplicate_action' ) );
    $override_reason   = sanitize_textarea_field( (string) $request->get_param( 'override_reason' ) );
    $existing_ticket_id = sanitize_text_field(    (string) $request->get_param( 'existing_ticket_id' ) );

    if ( empty( $contact_name ) )      return new WP_REST_Response( array( 'success' => false, 'message' => 'Contact Name is required.' ), 400 );
    if ( empty( $contact_email ) )     return new WP_REST_Response( array( 'success' => false, 'message' => 'Contact Email is required.' ), 400 );
    if ( empty( $part_id ) )           return new WP_REST_Response( array( 'success' => false, 'message' => 'Part ID is required.' ), 400 );
    if ( empty( $part_description ) )  return new WP_REST_Response( array( 'success' => false, 'message' => 'Part Description is required.' ), 400 );
    if ( empty( $issue_description ) ) return new WP_REST_Response( array( 'success' => false, 'message' => 'Issue Description is required.' ), 400 );

    if ( sp_smti_check_cooldown( $serial_number, $contact_email, $issue_description, $duplicate_action ) ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => 'This submission was already received. Please wait a few minutes.', 'cooldown_detected' => true ), 429 );
    }

    $attachment_context = array(
        'contact_name' => $contact_name, 'contact_email' => $contact_email, 'contact_phone' => $contact_phone,
        'facility_name' => $facility_name, 'location_name' => $location_name,
        'address1' => $address1, 'address2' => $address2, 'city' => $city, 'state' => $state, 'postal_code' => $postal_code, 'country' => $country,
        'serial_number' => $serial_number, 'asset_name' => $asset_name, 'model_number' => $equipment_model, 'equipment_model' => $equipment_model,
        'part_id' => $part_id, 'part_description' => $part_description, 'order_number' => $order_number,
        'error_code' => $error_code !== '' ? $error_code : sp_smti_extract_error_code( $issue_description ),
        'issue_description' => $issue_description,
    );
    $company_properties = array(
        'smti_customer_id' => $smti_customer_id, 'smti_location_id' => $smti_location_id,
        'smti_location_key' => $smti_location_key, 'smti_location_name' => $location_name,
        'smti_shipping_address_1' => $address1, 'smti_shipping_address_2' => $address2,
        'smti_shipping_city' => $city, 'smti_shipping_state' => $state,
        'smti_shipping_zip' => $postal_code, 'smti_shipping_country' => $country,
    );

    $duplicate = sp_smti_find_duplicate_ticket( $serial_number, $contact_email );
    if ( ! empty( $duplicate['found'] ) ) {
        $target_id = ! empty( $existing_ticket_id ) ? $existing_ticket_id : ( isset( $duplicate['ticket_id'] ) ? $duplicate['ticket_id'] : '' );
        if ( $duplicate_action === 'update_existing' && $target_id !== '' ) {
            $rc_id = ! empty( $contact_id ) ? $contact_id : sp_smti_find_or_create_contact( $contact_name, $contact_email, $contact_phone, $facility_name, $address1, $city, $state, $postal_code, $country );
            $co_id = sp_smti_find_or_create_company( $facility_name, $smti_customer_id, $company_properties );
            if ( ! empty( $co_id ) ) {
                sp_smti_associate( 'tickets', $target_id, 'companies', $co_id );
                if ( ! empty( $rc_id ) ) sp_smti_associate( 'contacts', $rc_id, 'companies', $co_id );
            }
            $note = "UPDATE TO EXISTING SERVICE TICKET\n" . str_repeat( '-', 40 ) . "\nContact: $contact_name\nEmail: $contact_email\nPhone: $contact_phone\n\nFacility: $facility_name\nLocation: $location_name\nSerial: $serial_number\nPart ID: $part_id\n\nUpdate:\n$issue_description";
            if ( $override_reason !== '' ) $note .= "\n\nReason:\n$override_reason";
            sp_smti_add_note_to_ticket( $target_id, $note, $rc_id, $co_id );
            $files = $request->get_file_params();
            if ( ! empty( $files['smti_attachments'] ) ) sp_smti_handle_attachments( $files['smti_attachments'], $target_id, $rc_id, $attachment_context, $co_id );
            sp_smti_set_cooldown( $serial_number, $contact_email, $issue_description, $duplicate_action );
            return new WP_REST_Response( array( 'success' => true, 'updated_existing' => true, 'message' => 'Existing ticket updated.', 'existing_ticket_id' => $target_id ), 200 );
        }
        if ( $duplicate_action !== 'create_new' ) {
            return new WP_REST_Response( array(
                'success' => false, 'duplicate_detected' => true,
                'message' => 'A recent service ticket exists for this serial number.',
                'existing_ticket_id' => isset( $duplicate['ticket_id'] ) ? $duplicate['ticket_id'] : '',
                'existing_ticket'    => $duplicate,
                'actions'            => array( 'update_existing', 'create_new' ),
            ), 200 );
        }
    }

    $subject_parts  = array_filter( array( $facility_name, $serial_number ? 'SN:' . $serial_number : '', $part_id ) );
    $ticket_subject = 'Service Request' . ( $subject_parts ? ' — ' . implode( ' | ', $subject_parts ) : '' );

    $lines = array(
        'SERVICE TICKET SUBMISSION', str_repeat( '-', 40 ), '',
        'CONTACT INFORMATION',
        'Name:               ' . $contact_name,
        'Email:              ' . $contact_email,
        'Phone:              ' . $contact_phone, '',
        'CUSTOMER / LOCATION',
        'Company:            ' . $facility_name,
        'Location Name:      ' . $location_name,
        'Customer ID:        ' . $smti_customer_id,
        'Location ID:        ' . $smti_location_id,
        'Location Key:       ' . $smti_location_key,
        'Address:            ' . trim( $address1 . ' ' . $address2 ),
        'City / State / Zip: ' . trim( $city . ', ' . $state . ' ' . $postal_code ),
        'Country:            ' . $country, '',
        'EQUIPMENT DETAILS',
        'Serial Number:      ' . $serial_number,
        'Asset Name:         ' . $asset_name,
        'Part ID:            ' . $part_id,
        'Part Description:   ' . $part_description,
        'Order Number:       ' . $order_number,
        'Invoice/Ship Date:  ' . $invoice_date,
        'Equipment Type:     ' . $equipment_type,
        'Equipment Model:    ' . $equipment_model,
        'Error Code / LED:   ' . ( $error_code !== '' ? $error_code : sp_smti_extract_error_code( $issue_description ) ), '',
        'ISSUE DESCRIPTION', $issue_description,
    );
    if ( ! empty( $import_notes ) ) { $lines[] = ''; $lines[] = 'IMPORT NOTES'; $lines[] = $import_notes; }
    if ( $duplicate_action === 'create_new' && ! empty( $duplicate['found'] ) ) {
        $lines[] = ''; $lines[] = 'DUPLICATE OVERRIDE';
        $lines[] = 'Existing Ticket ID: ' . ( isset( $duplicate['ticket_id'] ) ? $duplicate['ticket_id'] : '' );
        if ( $override_reason !== '' ) $lines[] = 'Override Reason: ' . $override_reason;
    }
    $ticket_body = implode( "\n", $lines );

    $priority_map = array( 'low' => 'LOW', 'normal' => 'MEDIUM', 'high' => 'HIGH', 'urgent' => 'HIGH' );
    $hs_priority  = isset( $priority_map[ $priority ] ) ? $priority_map[ $priority ] : 'MEDIUM';
    $ticket_props = array(
        'subject'            => $ticket_subject,
        'content'            => $ticket_body,
        'hs_pipeline'        => $settings['ticket_pipeline_id'],
        'hs_pipeline_stage'  => $settings['ticket_stage_id'],
        'hs_ticket_priority' => $hs_priority,
    );
    if ( ! empty( $assigned_to ) ) $ticket_props['hubspot_owner_id'] = $assigned_to;

    $ticket_resp = sp_smti_hs_request( 'POST', '/crm/v3/objects/tickets', array( 'properties' => $ticket_props ) );
    if ( is_wp_error( $ticket_resp ) ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => $ticket_resp->get_error_message() ), 500 );
    }
    if ( (int) $ticket_resp['status'] !== 201 || empty( $ticket_resp['body']['id'] ) ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => 'HubSpot rejected the ticket.', 'hubspot' => $ticket_resp['body'] ), 500 );
    }

    $ticket_id              = (string) $ticket_resp['body']['id'];
    $service_request_number = 'SR-' . gmdate( 'Y' ) . '-' . str_pad( substr( preg_replace( '/\D/', '', $ticket_id ), -6 ), 6, '0', STR_PAD_LEFT );

    $rc_id = ! empty( $contact_id ) ? $contact_id : sp_smti_find_or_create_contact( $contact_name, $contact_email, $contact_phone, $facility_name, $address1, $city, $state, $postal_code, $country );
    $co_id = sp_smti_find_or_create_company( $facility_name, $smti_customer_id, $company_properties );

    if ( ! empty( $rc_id ) ) {
        sp_smti_hs_request( 'PUT', '/crm/v3/objects/tickets/' . rawurlencode( $ticket_id ) . '/associations/contacts/' . rawurlencode( $rc_id ) . '/ticket_to_contact' );
    }
    if ( ! empty( $co_id ) ) {
        sp_smti_associate( 'tickets', $ticket_id, 'companies', $co_id );
        if ( ! empty( $rc_id ) ) sp_smti_associate( 'contacts', $rc_id, 'companies', $co_id );
    }

    $files = $request->get_file_params();
    sp_smti_handle_attachments( ! empty( $files['smti_attachments'] ) ? $files['smti_attachments'] : array(), $ticket_id, $rc_id, $attachment_context, $co_id );
    sp_smti_set_cooldown( $serial_number, $contact_email, $issue_description, $duplicate_action );

    $stage_map    = sp_smti_stage_map();
    $status_label = isset( $stage_map[ $settings['ticket_stage_id'] ] ) ? $stage_map[ $settings['ticket_stage_id'] ] : 'Awaiting Review';
    $email_sent   = sp_smti_send_confirmation_email( array(
        'contact_name'           => $contact_name,
        'contact_email'          => $contact_email,
        'service_request_number' => $service_request_number,
        'status'                 => $status_label,
        'facility_name'          => $facility_name,
        'equipment'              => $equipment_model !== '' ? $equipment_model : $part_description,
        'serial_number'          => $serial_number,
        'issue_description'      => $issue_description,
    ) );

    return new WP_REST_Response( array(
        'success'                 => true,
        'message'                 => 'Service ticket created successfully.',
        'ticket_id'               => $ticket_id,
        'service_request_number'  => $service_request_number,
        'contact_id'              => $rc_id,
        'company_id'              => $co_id,
        'confirmation_email_sent' => (bool) $email_sent,
    ), 200 );
}

// ── Company / Contact helpers ─────────────────────────────────────────────────

function sp_smti_associate( $from_type, $from_id, $to_type, $to_id ) {
    $from_id = trim( (string) $from_id );
    $to_id   = trim( (string) $to_id );
    if ( $from_id === '' || $to_id === '' ) return false;
    $resp = sp_smti_hs_request( 'PUT', '/crm/v4/objects/' . rawurlencode( $from_type ) . '/' . rawurlencode( $from_id ) . '/associations/default/' . rawurlencode( $to_type ) . '/' . rawurlencode( $to_id ) );
    return ! is_wp_error( $resp ) && (int) $resp['status'] >= 200 && (int) $resp['status'] < 300;
}

function sp_smti_find_or_create_company( $company_name, $smti_customer_id, $properties ) {
    $company_name     = trim( (string) $company_name );
    $smti_customer_id = trim( (string) $smti_customer_id );
    if ( $company_name === '' && $smti_customer_id === '' ) return '';
    if ( $smti_customer_id !== '' ) {
        $s = sp_smti_hs_request( 'POST', '/crm/v3/objects/companies/search', array(
            'filterGroups' => array( array( 'filters' => array( array( 'propertyName' => 'smti_customer_id', 'operator' => 'EQ', 'value' => $smti_customer_id ) ) ) ),
            'properties' => array( 'name' ), 'limit' => 1,
        ) );
        if ( ! is_wp_error( $s ) && (int) $s['status'] === 200 && ! empty( $s['body']['results'][0]['id'] ) ) {
            $cid = (string) $s['body']['results'][0]['id'];
            sp_smti_update_company( $cid, $properties );
            return $cid;
        }
    }
    if ( $company_name !== '' ) {
        $s = sp_smti_hs_request( 'POST', '/crm/v3/objects/companies/search', array(
            'filterGroups' => array( array( 'filters' => array( array( 'propertyName' => 'name', 'operator' => 'EQ', 'value' => $company_name ) ) ) ),
            'properties' => array( 'name' ), 'limit' => 1,
        ) );
        if ( ! is_wp_error( $s ) && (int) $s['status'] === 200 && ! empty( $s['body']['results'][0]['id'] ) ) {
            $cid = (string) $s['body']['results'][0]['id'];
            sp_smti_update_company( $cid, $properties );
            return $cid;
        }
    }
    if ( $company_name === '' ) return '';
    $create_props = array( 'name' => $company_name );
    foreach ( $properties as $k => $v ) { if ( $v !== null && $v !== '' ) $create_props[ $k ] = $v; }
    $c = sp_smti_hs_request( 'POST', '/crm/v3/objects/companies', array( 'properties' => $create_props ) );
    if ( ! is_wp_error( $c ) && (int) $c['status'] === 201 && ! empty( $c['body']['id'] ) ) return (string) $c['body']['id'];
    $c = sp_smti_hs_request( 'POST', '/crm/v3/objects/companies', array( 'properties' => array( 'name' => $company_name ) ) );
    if ( ! is_wp_error( $c ) && (int) $c['status'] === 201 && ! empty( $c['body']['id'] ) ) return (string) $c['body']['id'];
    return '';
}

function sp_smti_update_company( $company_id, $properties ) {
    $company_id = trim( (string) $company_id );
    if ( $company_id === '' || empty( $properties ) ) return false;
    $filtered = array();
    foreach ( $properties as $k => $v ) { if ( $v !== null && $v !== '' ) $filtered[ $k ] = $v; }
    if ( empty( $filtered ) ) return false;
    $r = sp_smti_hs_request( 'PATCH', '/crm/v3/objects/companies/' . rawurlencode( $company_id ), array( 'properties' => $filtered ) );
    return ! is_wp_error( $r ) && (int) $r['status'] >= 200 && (int) $r['status'] < 300;
}

function sp_smti_find_or_create_contact( $name, $email, $phone, $company, $address, $city, $state, $zip, $country ) {
    $s = sp_smti_hs_request( 'POST', '/crm/v3/objects/contacts/search', array(
        'filterGroups' => array( array( 'filters' => array( array( 'propertyName' => 'email', 'operator' => 'EQ', 'value' => $email ) ) ) ),
        'properties' => array( 'email' ), 'limit' => 1,
    ) );
    if ( ! is_wp_error( $s ) && (int) $s['status'] === 200 && ! empty( $s['body']['results'][0]['id'] ) ) return (string) $s['body']['results'][0]['id'];
    $parts     = explode( ' ', trim( $name ), 2 );
    $firstname = $parts[0];
    $lastname  = isset( $parts[1] ) ? $parts[1] : '';
    $props = array();
    $all = array( 'firstname' => $firstname, 'lastname' => $lastname, 'email' => $email, 'phone' => $phone, 'company' => $company, 'address' => $address, 'city' => $city, 'state' => $state, 'zip' => $zip, 'country' => $country );
    foreach ( $all as $k => $v ) { if ( $v !== '' ) $props[ $k ] = $v; }
    $c = sp_smti_hs_request( 'POST', '/crm/v3/objects/contacts', array( 'properties' => $props ) );
    if ( ! is_wp_error( $c ) && (int) $c['status'] === 201 && ! empty( $c['body']['id'] ) ) return (string) $c['body']['id'];
    return '';
}

// ── Notes ─────────────────────────────────────────────────────────────────────

function sp_smti_add_note_to_ticket( $ticket_id, $note_body, $contact_id = '', $company_id = '', $attachment_ids = array() ) {
    $ticket_id = trim( (string) $ticket_id );
    $note_body = trim( (string) $note_body );
    if ( $ticket_id === '' ) return '';
    // Allow attachment-only notes (a photo with no typed text).
    if ( $note_body === '' && empty( $attachment_ids ) ) return '';
    $assoc = array( array( 'to' => array( 'id' => $ticket_id ), 'types' => array( array( 'associationCategory' => 'HUBSPOT_DEFINED', 'associationTypeId' => 228 ) ) ) );
    if ( ! empty( $contact_id ) ) $assoc[] = array( 'to' => array( 'id' => $contact_id ), 'types' => array( array( 'associationCategory' => 'HUBSPOT_DEFINED', 'associationTypeId' => 202 ) ) );
    if ( ! empty( $company_id ) ) $assoc[] = array( 'to' => array( 'id' => $company_id ), 'types' => array( array( 'associationCategory' => 'HUBSPOT_DEFINED', 'associationTypeId' => 190 ) ) );
    $props = array( 'hs_note_body' => nl2br( esc_html( $note_body ) ), 'hs_timestamp' => gmdate( 'c' ) );
    if ( ! empty( $attachment_ids ) ) {
        $props['hs_attachment_ids'] = implode( ';', array_map( 'strval', (array) $attachment_ids ) );
    }
    $r = sp_smti_hs_request( 'POST', '/crm/v3/objects/notes', array(
        'properties'   => $props,
        'associations' => $assoc,
    ) );
    if ( is_wp_error( $r ) || (int) $r['status'] !== 201 || empty( $r['body']['id'] ) ) return '';
    return (string) $r['body']['id'];
}

/**
 * Uploads files chosen on the Add Note form to HubSpot Files and returns their IDs.
 * Images (and PDF) only, max 5 files, 10MB each. Nothing is stored on the WP server —
 * sp_smti_upload_file() streams the PHP temp file straight to HubSpot.
 */
function sp_smti_upload_note_files( $files ) {
    $out = array( 'ids' => array(), 'failed' => array() );
    if ( empty( $files ) || empty( $files['name'] ) ) return $out;

    $allowed = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic', 'pdf' );
    $max     = 10 * 1024 * 1024;

    // Normalize single-file and multi-file shapes into one list.
    $list = array();
    if ( is_array( $files['name'] ) ) {
        foreach ( $files['name'] as $i => $n ) {
            $list[] = array( 'name' => $n, 'tmp' => $files['tmp_name'][ $i ], 'err' => $files['error'][ $i ], 'size' => $files['size'][ $i ], 'type' => isset( $files['type'][ $i ] ) ? $files['type'][ $i ] : '' );
        }
    } else {
        $list[] = array( 'name' => $files['name'], 'tmp' => $files['tmp_name'], 'err' => $files['error'], 'size' => $files['size'], 'type' => isset( $files['type'] ) ? $files['type'] : '' );
    }

    $count = 0;
    foreach ( $list as $f ) {
        if ( empty( $f['name'] ) ) continue;
        if ( ++$count > 5 ) { $out['failed'][] = sanitize_file_name( $f['name'] ) . ' (max 5 files)'; continue; }
        if ( (int) $f['err'] !== UPLOAD_ERR_OK )      { $out['failed'][] = sanitize_file_name( $f['name'] ); continue; }
        if ( (int) $f['size'] > $max )                { $out['failed'][] = sanitize_file_name( $f['name'] ) . ' (over 10MB)'; continue; }
        $ext = strtolower( pathinfo( $f['name'], PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, $allowed, true ) )     { $out['failed'][] = sanitize_file_name( $f['name'] ) . ' (unsupported type)'; continue; }
        if ( ! is_uploaded_file( $f['tmp'] ) )        { $out['failed'][] = sanitize_file_name( $f['name'] ); continue; }

        $r = sp_smti_upload_file( $f['tmp'], $f['name'], $f['type'] );
        if ( ! empty( $r['success'] ) && ! empty( $r['id'] ) ) $out['ids'][] = (string) $r['id'];
        else $out['failed'][] = sanitize_file_name( $f['name'] );
    }
    return $out;
}

// ── Attachments ───────────────────────────────────────────────────────────────

function sp_smti_upload_file( $tmp_name, $original_name, $mime_type ) {
    $settings = sp_smti_get_settings();
    $token    = trim( $settings['hubspot_token'] );
    if ( $token === '' || ! file_exists( $tmp_name ) || ! function_exists( 'curl_init' ) || ! class_exists( 'CURLFile' ) ) {
        return array( 'success' => false );
    }
    $original_name = sanitize_file_name( (string) $original_name );
    if ( empty( $mime_type ) ) $mime_type = function_exists( 'mime_content_type' ) ? mime_content_type( $tmp_name ) : 'application/octet-stream';
    $ch = curl_init();
    curl_setopt_array( $ch, array(
        CURLOPT_URL            => 'https://api.hubapi.com/files/v3/files',
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_HTTPHEADER     => array( 'Authorization: Bearer ' . $token ),
        CURLOPT_POSTFIELDS     => array(
            'file'       => new CURLFile( $tmp_name, $mime_type, $original_name ),
            'folderPath' => '/SMTI-Service-Ticket-Attachments',
            'options'    => wp_json_encode( array( 'access' => 'PRIVATE' ) ),
        ),
    ) );
    $raw       = curl_exec( $ch );
    $http_code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
    curl_close( $ch );
    $decoded = json_decode( (string) $raw, true );
    if ( $http_code >= 200 && $http_code < 300 && ! empty( $decoded['id'] ) ) {
        return array( 'success' => true, 'id' => (string) $decoded['id'], 'name' => $original_name, 'url' => isset( $decoded['url'] ) ? $decoded['url'] : '' );
    }
    return array( 'success' => false );
}

function sp_smti_handle_attachments( $files, $ticket_id, $contact_id, $context, $company_id ) {
    $uploaded = array();
    $failed   = array();
    if ( isset( $files['name'] ) && is_array( $files['name'] ) ) {
        foreach ( $files['name'] as $i => $name ) {
            if ( empty( $name ) || ! isset( $files['error'][ $i ] ) || $files['error'][ $i ] !== UPLOAD_ERR_OK ) continue;
            $r = sp_smti_upload_file( $files['tmp_name'][ $i ], $name, isset( $files['type'][ $i ] ) ? $files['type'][ $i ] : '' );
            if ( ! empty( $r['success'] ) ) $uploaded[] = $r;
            else $failed[] = sanitize_file_name( $name );
        }
    } elseif ( isset( $files['name'] ) && isset( $files['error'] ) && $files['error'] === UPLOAD_ERR_OK ) {
        $r = sp_smti_upload_file( $files['tmp_name'], $files['name'], isset( $files['type'] ) ? $files['type'] : '' );
        if ( ! empty( $r['success'] ) ) $uploaded[] = $r;
    }
    $note_lines = array(
        'SERVICE REQUEST DETAILS / ATTACHMENTS', str_repeat( '-', 40 ),
        'Facility / Company: ' . sp_smti_ctx( $context, 'facility_name' ),
        'Department / Room / Location: ' . sp_smti_ctx( $context, 'location_name' ),
        'Contact: ' . sp_smti_ctx( $context, 'contact_name' ),
        'Email: '   . sp_smti_ctx( $context, 'contact_email' ),
        'Phone: '   . sp_smti_ctx( $context, 'contact_phone' ), '',
        'EQUIPMENT',
        'Serial Number: '    . sp_smti_ctx( $context, 'serial_number' ),
        'Model Number: '     . sp_smti_ctx( $context, 'model_number' ),
        'Asset Name: '       . sp_smti_ctx( $context, 'asset_name' ),
        'Part ID: '          . sp_smti_ctx( $context, 'part_id' ),
        'Part Description: ' . sp_smti_ctx( $context, 'part_description' ),
        'Order Number: '     . sp_smti_ctx( $context, 'order_number' ), '',
        'ERROR / LED LIGHTS', sp_smti_ctx( $context, 'error_code' ), '',
        'ISSUE DESCRIPTION',  sp_smti_ctx( $context, 'issue_description' ), '',
        'ATTACHMENTS',
    );
    if ( empty( $uploaded ) && empty( $failed ) ) { $note_lines[] = 'No attachments submitted.'; }
    $file_ids = array();
    foreach ( $uploaded as $file ) {
        $file_ids[] = (string) $file['id'];
        $line = $file['name'] . ' — HubSpot File ID: ' . $file['id'];
        if ( ! empty( $file['url'] ) ) $line .= ' — ' . $file['url'];
        $note_lines[] = $line;
    }
    if ( ! empty( $failed ) ) { $note_lines[] = 'Upload failed: ' . implode( ', ', $failed ); }
    $note_props = array( 'hs_note_body' => nl2br( esc_html( implode( "\n", $note_lines ) ) ), 'hs_timestamp' => gmdate( 'c' ) );
    if ( ! empty( $file_ids ) ) $note_props['hs_attachment_ids'] = implode( ';', $file_ids );
    $assoc = array( array( 'to' => array( 'id' => $ticket_id ), 'types' => array( array( 'associationCategory' => 'HUBSPOT_DEFINED', 'associationTypeId' => 228 ) ) ) );
    if ( ! empty( $contact_id ) ) $assoc[] = array( 'to' => array( 'id' => $contact_id ), 'types' => array( array( 'associationCategory' => 'HUBSPOT_DEFINED', 'associationTypeId' => 202 ) ) );
    if ( ! empty( $company_id ) ) $assoc[] = array( 'to' => array( 'id' => $company_id ), 'types' => array( array( 'associationCategory' => 'HUBSPOT_DEFINED', 'associationTypeId' => 190 ) ) );
    $r = sp_smti_hs_request( 'POST', '/crm/v3/objects/notes', array( 'properties' => $note_props, 'associations' => $assoc ) );
    if ( is_wp_error( $r ) || (int) $r['status'] !== 201 || empty( $r['body']['id'] ) ) return '';
    return (string) $r['body']['id'];
}

function sp_smti_ctx( $context, $key ) {
    if ( ! is_array( $context ) || ! isset( $context[ $key ] ) ) return '';
    return trim( (string) $context[ $key ] );
}

// ── Cooldown ──────────────────────────────────────────────────────────────────

function sp_smti_cooldown_key( $serial, $email, $issue, $action ) {
    return 'sp_smti_submit_' . md5( strtolower( trim( $serial ) ) . '|' . strtolower( trim( $email ) ) . '|' . trim( $issue ) . '|' . trim( $action ) );
}
function sp_smti_check_cooldown( $serial, $email, $issue, $action ) {
    return (bool) get_transient( sp_smti_cooldown_key( $serial, $email, $issue, $action ) );
}
function sp_smti_set_cooldown( $serial, $email, $issue, $action ) {
    set_transient( sp_smti_cooldown_key( $serial, $email, $issue, $action ), 1, 5 * MINUTE_IN_SECONDS );
}

// ── reCAPTCHA ─────────────────────────────────────────────────────────────────

function sp_smti_verify_recaptcha( $token, $secret ) {
    $token  = trim( (string) $token );
    $secret = trim( (string) $secret );
    if ( $secret === '' ) return true;
    if ( $token === '' )  return false;
    $r = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
        'timeout' => 15,
        'body'    => array( 'secret' => $secret, 'response' => $token, 'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' ),
    ) );
    if ( is_wp_error( $r ) ) return false;
    $body = json_decode( wp_remote_retrieve_body( $r ), true );
    return ! empty( $body['success'] );
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function sp_smti_extract_error_code( $issue ) {
    if ( preg_match( '/Error Code \/ Error Lights:\s*(.+)/i', (string) $issue, $m ) ) return trim( $m[1] );
    return '';
}

function sp_smti_ticket_properties() {
    return array( 'subject', 'content', 'createdate', 'hs_lastmodifieddate', 'hs_pipeline', 'hs_pipeline_stage', 'smti_service_request_number', 'smti_contact_name', 'smti_contact_email', 'smti_contact_phone', 'smti_facility_name', 'smti_location_name', 'smti_serial_number', 'smti_equipment_type', 'smti_equipment_model', 'smti_issue_description', 'smti_part_id', 'smti_order_number', 'smti_customer_id' );
}

function sp_smti_body_value( $content, $label ) {
    $label = preg_quote( (string) $label, '/' );
    if ( preg_match( '/^' . $label . '\s*:\s*(.*)$/mi', (string) $content, $m ) ) return trim( (string) $m[1] );
    return '';
}

function sp_smti_prop( $props, $keys, $fallback = '' ) {
    foreach ( (array) $keys as $key ) {
        if ( isset( $props[ $key ] ) && trim( (string) $props[ $key ] ) !== '' ) return trim( (string) $props[ $key ] );
    }
    return $fallback;
}

function sp_smti_ticket_summary( $ticket, $stage_map ) {
    $props    = isset( $ticket['properties'] ) ? $ticket['properties'] : array();
    $content  = isset( $props['content'] ) ? (string) $props['content'] : '';
    $stage_id = sp_smti_prop( $props, array( 'hs_pipeline_stage' ) );
    return array(
        'ticket_id'              => isset( $ticket['id'] ) ? (string) $ticket['id'] : '',
        'subject'                => sp_smti_prop( $props, array( 'subject' ) ),
        'service_request_number' => sp_smti_prop( $props, array( 'smti_service_request_number' ) ),
        'stage_id'               => $stage_id,
        'status'                 => isset( $stage_map[ $stage_id ] ) ? $stage_map[ $stage_id ] : $stage_id,
        'createdate'             => sp_smti_prop( $props, array( 'createdate' ) ),
        'lastmodifieddate'       => sp_smti_prop( $props, array( 'hs_lastmodifieddate' ) ),
        'company'                => sp_smti_prop( $props, array( 'smti_facility_name' ),  sp_smti_body_value( $content, 'Company' ) ),
        'contact_name'           => sp_smti_prop( $props, array( 'smti_contact_name' ),   sp_smti_body_value( $content, 'Name' ) ),
        'contact_email'          => sp_smti_prop( $props, array( 'smti_contact_email' ),  sp_smti_body_value( $content, 'Email' ) ),
        'contact_phone'          => sp_smti_prop( $props, array( 'smti_contact_phone' ),  sp_smti_body_value( $content, 'Phone' ) ),
        'location_name'          => sp_smti_prop( $props, array( 'smti_location_name' ),  sp_smti_body_value( $content, 'Location Name' ) ),
        'serial_number'          => sp_smti_prop( $props, array( 'smti_serial_number' ),  sp_smti_body_value( $content, 'Serial Number' ) ),
        'part_id'                => sp_smti_prop( $props, array( 'smti_part_id', 'smti_equipment_type' ), sp_smti_body_value( $content, 'Part ID' ) ),
        'equipment_model'        => sp_smti_prop( $props, array( 'smti_equipment_model' ), sp_smti_body_value( $content, 'Equipment Model' ) ),
        'issue_summary'          => wp_trim_words( wp_strip_all_tags( sp_smti_prop( $props, array( 'smti_issue_description' ) ) ), 20, '...' ),
        'content'                => $content,
    );
}

// ── Confirmation email ────────────────────────────────────────────────────────

function sp_smti_send_confirmation_email( $ctx ) {
    $email = sanitize_email( isset( $ctx['contact_email'] ) ? $ctx['contact_email'] : '' );
    if ( ! is_email( $email ) ) return false;
    $name       = isset( $ctx['contact_name'] )           ? trim( $ctx['contact_name'] )           : '';
    $request_no = isset( $ctx['service_request_number'] ) ? trim( $ctx['service_request_number'] ) : '';
    $status     = isset( $ctx['status'] )                 ? trim( $ctx['status'] )                 : 'Awaiting Review';
    $facility   = isset( $ctx['facility_name'] )          ? trim( $ctx['facility_name'] )          : '';
    $equipment  = isset( $ctx['equipment'] )              ? trim( $ctx['equipment'] )              : '';
    $serial     = isset( $ctx['serial_number'] )          ? trim( $ctx['serial_number'] )          : '';
    $issue_raw  = isset( $ctx['issue_description'] )      ? trim( $ctx['issue_description'] )      : '';
    $subject_no = $request_no !== '' ? $request_no : 'Your Ticket';
    $issue      = sp_smti_clean_issue_for_email( $issue_raw );
    $body = implode( "\n", array(
        'Hello ' . ( $name !== '' ? $name : 'there' ) . ',', '',
        'Thank you for contacting SMTI.', '',
        'We have received your service request and created Ticket #' . $subject_no . '.', '',
        'Current Status:', ( $status !== '' ? $status : 'Awaiting Review' ), '',
        'Facility:', ( $facility !== '' ? $facility : 'Not provided' ), '',
        'Equipment:', ( $equipment !== '' ? $equipment : 'Not provided' ), '',
        'Serial Number:', ( $serial !== '' ? $serial : 'Not provided' ), '',
        'Issue Summary:', ( $issue !== '' ? $issue : 'Not provided' ), '',
        'An SMTI service representative will review your request and contact you if additional information is needed.', '',
        'Please do not reply to this email.', '',
        'Thank you,', '', 'SMTI Service Team', 'support@smti.co',
    ) );
    return wp_mail( $email, 'SMTI Service Request Received - Ticket #' . $subject_no, $body, array( 'From: SMTI Service Team <noreply@smti.co>', 'Content-Type: text/plain; charset=UTF-8' ) );
}

function sp_smti_clean_issue_for_email( $issue ) {
    $issue = trim( (string) $issue );
    if ( $issue === '' ) return '';
    $lines = preg_split( "/\r\n|\n|\r/", $issue );
    $clean = array();
    foreach ( $lines as $line ) {
        $line = trim( (string) $line );
        if ( $line === '' ) continue;
        if ( stripos( $line, 'SERIAL NUMBER STATUS:' ) === 0 ) continue;
        if ( strtoupper( $line ) === 'DESCRIPTION' || strtoupper( $line ) === 'PROBLEM DESCRIPTION' ) continue;
        $clean[] = $line;
    }
    $summary = trim( implode( "\n", $clean ) );
    return $summary !== '' ? $summary : $issue;
}
