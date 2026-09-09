<?php
/*
 * Government Service Core integration.
 *
 * Adds the AI Queue Analysis card to the dashboard, and (via city-weekly.php)
 * a weekly emailed report to city admins and supervisors, for instances running
 * Start Performance — Government Service Core (City of Clinton style installs).
 * Loaded from sp_ai_register() only when SP_CITY_VERSION is defined. Hangs on
 * the core dashboard hook sp_dashboard_after_stats, after the city stat cards.
 *
 * Government Service Core 1.2.35+ also fires sp_city_ticket_edit_after and
 * sp_city_tickets_after_list in its ticket views; neither is used today.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function sp_ai_city_register() {
    // Priority 20: render after Government Service Core's own stat cards (priority 10).
    add_action( 'sp_dashboard_after_stats',           'sp_ai_city_dashboard_analysis_ui', 20 );
    add_action( 'wp_ajax_sp_ai_city_ticket_analysis', 'sp_ai_ajax_city_ticket_analysis' );

    // Weekly emailed report to city admins and supervisors (own settings card + cron).
    require_once SP_AI_PLUGIN_DIR . 'includes/city-weekly.php';
    sp_ai_city_weekly_register();
}

// ── Access ────────────────────────────────────────────────────────────────────

// Queue analysis is a supervisor tool: city admins and city supervisors (plus
// super admins, who resolve to city_admin) see it. Employees and call-center
// roles do not — enforced for both the dashboard card and the AJAX handler.
function sp_ai_city_can_view_analysis() {
    if ( ! sp_ai_authed() ) return false;
    if ( function_exists( 'sp_is_super_admin' ) && sp_is_super_admin() ) return true;
    $admin = function_exists( 'sp_city_is_city_admin' )      && sp_city_is_city_admin();
    $super = function_exists( 'sp_city_is_city_supervisor' ) && sp_city_is_city_supervisor();
    return $admin || $super;
}

// ── Shared assets ─────────────────────────────────────────────────────────────

// Markdown output styling + client-side renderer, printed once per page so the
// analysis card formats its AJAX response the same way the server-side
// sp_ai_render_markdown() does.
function sp_ai_city_md_assets() {
    static $done = false;
    if ( $done ) return;
    $done = true;
    ?>
    <style>
    .sp-ai-md-out h2{font-size:16px;font-weight:700;color:#0f172a;margin:20px 0 4px;display:flex;align-items:center;gap:7px}
    .sp-ai-md-out h2:first-child{margin-top:0}
    .sp-ai-md-out h3{font-size:14px;font-weight:700;color:#1e293b;margin:14px 0 3px;display:flex;align-items:center;gap:6px}
    .sp-ai-md-out p{margin:0 0 10px;color:#374151}
    .sp-ai-md-out strong{font-weight:600;color:#0f172a}
    .sp-ai-md-out ul{margin:4px 0 10px 18px;padding:0}
    .sp-ai-md-out li{margin-bottom:3px}
    .sp-ai-city-btn{background:rgba(255,255,255,.08);border:none;border-radius:6px;padding:6px 14px;cursor:pointer;color:#e2e8f0;font-size:.8rem}
    .sp-ai-city-btn:disabled{opacity:.6;cursor:default}
    </style>
    <script>
    if ( typeof spAiRenderMd === 'undefined' ) {
        window.spAiRenderMd = (function(){
            var ih2 = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15" style="flex-shrink:0;color:#6366f1"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>';
            var ih3 = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" style="flex-shrink:0;color:#94a3b8"><path d="M9 5l7 7-7 7"/></svg>';
            function esc(s){ return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
            function inline(t){ return esc(t).replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>').replace(/\*(.+?)\*/g,'<em>$1</em>'); }
            return function(md){
                var lines = String(md).split('\n'), html = '', inUl = false;
                lines.forEach(function(line){
                    if (/^# /.test(line))       { if (inUl){html+='</ul>';inUl=false;} html += '<h2>'+ih2+esc(line.replace(/^# /,''))+'</h2>'; }
                    else if (/^## /.test(line)) { if (inUl){html+='</ul>';inUl=false;} html += '<h3>'+ih3+esc(line.replace(/^## /,''))+'</h3>'; }
                    else if (/^[-*] /.test(line)) { if (!inUl){html+='<ul>';inUl=true;} html += '<li>'+inline(line.replace(/^[-*] /,''))+'</li>'; }
                    else if (/^([-*_]\s?){3,}$/.test(line.trim())) { if (inUl){html+='</ul>';inUl=false;} } // horizontal rule: drop
                    else if (line.trim()==='')  { if (inUl){html+='</ul>';inUl=false;} }
                    else                        { if (inUl){html+='</ul>';inUl=false;} html += '<p>'+inline(line)+'</p>'; }
                });
                if (inUl) html += '</ul>';
                return html;
            };
        })();
    }
    // Wire a "generate" button to an AJAX action and render the markdown result.
    function spAiCityBind(btnId, outId, statusId, metaId, ajaxAction, doneLabel, extra){
        var btn = document.getElementById(btnId), out = document.getElementById(outId);
        var status = document.getElementById(statusId), meta = document.getElementById(metaId);
        if (!btn) return;
        btn.addEventListener('click', function(){
            btn.disabled = true; status.style.display = 'inline';
            var fd = new FormData();
            fd.append('action', ajaxAction);
            fd.append('nonce',  btn.dataset.nonce);
            if (extra) { Object.keys(extra).forEach(function(k){ fd.append(k, extra[k]); }); }
            fetch(btn.dataset.ajax, { method:'POST', body:fd, credentials:'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    status.style.display = 'none'; btn.disabled = false; btn.textContent = doneLabel;
                    if (data.success) {
                        out.innerHTML = spAiRenderMd(data.data.text);
                        out.style.display = 'block';
                        if (meta) meta.textContent = 'Generated just now';
                    } else {
                        out.textContent = 'Error: ' + (data.data || 'Unknown error');
                        out.style.display = 'block';
                    }
                })
                .catch(function(){
                    status.style.display = 'none'; btn.disabled = false;
                    out.textContent = 'Request failed.'; out.style.display = 'block';
                });
        });
    }
    </script>
    <?php
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function sp_ai_city_source_label( $source ) {
    $labels = array( 'call_center' => 'Call Center', 'staff' => 'City Direct', 'public' => 'Public (online form)' );
    return isset( $labels[ $source ] ) ? $labels[ $source ] : ucfirst( $source );
}

// ── Queue Analysis card (dashboard) ───────────────────────────────────────────

// Renders under the city stat cards on the dashboard for city admins and
// supervisors. Shows the cached analysis immediately; if it is missing or older
// than a day, the page auto-requests a fresh one in the background so the
// morning view is current without anyone clicking. Manual refresh stays.
function sp_ai_city_dashboard_analysis_ui() {
    if ( ! sp_ai_is_configured() || ! sp_ai_city_can_view_analysis() ) return;
    $nonce     = wp_create_nonce( 'sp_ai_city_ticket_analysis' );
    $cached    = get_option( 'sp_ai_city_analysis_cache' );
    $text      = $cached ? $cached['text'] : '';
    $gen_at    = $cached ? (int) $cached['generated_at'] : 0;
    $stale     = ( time() - $gen_at ) > DAY_IN_SECONDS;
    $gen_label = $gen_at ? 'Generated ' . human_time_diff( $gen_at ) . ' ago' : '';
    $action_html = '<span id="sp-ai-ca-meta" style="margin-left:auto;font-size:11px;color:#94a3b8">' . esc_html( $gen_label ) . '</span>'
        . '<span id="sp-ai-ca-status" style="font-size:12px;color:#94a3b8;display:none">Analyzing…</span>'
        . '<button type="button" id="sp-ai-ca-btn" class="sp-ai-city-btn" style="padding:4px 10px;font-size:.75rem"'
        . ' data-nonce="' . esc_attr( $nonce ) . '" data-ajax="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '">'
        . ( $text ? 'Refresh' : 'Analyze Queue' ) . '</button>';
    $well = sp_ai_content_well_style();

    sp_ai_city_md_assets();
    echo '<div style="margin-bottom:20px">';
    sp_ai_card_start( 'AI Queue Analysis', $action_html );
    ?>
        <div id="sp-ai-ca-output" class="sp-ai-md-out" style="<?php echo $text ? '' : 'display:none;'; ?><?php echo $well; ?>font-size:14px;line-height:1.75;color:#1e293b"><?php echo $text ? sp_ai_render_markdown( $text ) : ''; ?></div>
        <div style="font-size:12px;color:#94a3b8;margin-top:<?php echo $text ? '10px' : '0'; ?>">Whole service-request queue: backlog, unacknowledged and aging requests, department workload, response times and repeat addresses. Refreshes automatically once a day.</div>
    <?php
    sp_ai_card_end();
    echo '</div>';
    ?>
    <script>
    spAiCityBind('sp-ai-ca-btn', 'sp-ai-ca-output', 'sp-ai-ca-status', 'sp-ai-ca-meta', 'sp_ai_city_ticket_analysis', 'Refresh', null);
    <?php if ( $stale ) : ?>
    // Daily auto-refresh: kick off a fresh analysis in the background on load.
    (function(){ var b = document.getElementById('sp-ai-ca-btn'); if (b) b.click(); })();
    <?php endif; ?>
    </script>
    <?php
}

// ── Queue Analysis AJAX ───────────────────────────────────────────────────────

function sp_ai_ajax_city_ticket_analysis() {
    if ( ! check_ajax_referer( 'sp_ai_city_ticket_analysis', 'nonce', false ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }
    if ( ! sp_ai_city_can_view_analysis() ) {
        wp_send_json_error( 'Not authorized' );
    }
    if ( ! sp_ai_is_configured() ) {
        wp_send_json_error( 'AI is not configured — add an API key under Settings → AI Integration.' );
    }
    $result = sp_ai_city_generate_analysis();
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }
    wp_send_json_success( array( 'text' => $result ) );
}

// ── Analysis generator ────────────────────────────────────────────────────────

// Pulls the queue snapshot, asks the model for the analysis, caches the result
// for the dashboard card, and returns the text (or WP_Error). Shared by the
// dashboard button/auto-refresh and the weekly email so both use one source.
function sp_ai_city_generate_analysis() {
    global $wpdb;
    $p    = $wpdb->prefix;
    $open = "status NOT IN ('resolved','closed')";

    $by_status = $wpdb->get_results(
        "SELECT status, COUNT(*) AS count FROM {$p}sp_city_tickets GROUP BY status ORDER BY count DESC"
    );
    $by_priority = $wpdb->get_results(
        "SELECT priority, COUNT(*) AS count FROM {$p}sp_city_tickets
         WHERE $open GROUP BY priority ORDER BY FIELD(priority,'emergency','high','normal','low')"
    );
    $by_dept = $wpdb->get_results(
        "SELECT d.name, COUNT(DISTINCT td.ticket_id) AS count
         FROM {$p}sp_city_ticket_depts td
         INNER JOIN {$p}sp_city_tickets tk ON tk.id = td.ticket_id
         LEFT JOIN {$p}sp_city_departments d ON d.id = td.dept_id
         WHERE tk.$open
         GROUP BY td.dept_id ORDER BY count DESC"
    );
    $dept_unacked = $wpdb->get_results(
        "SELECT d.name, COUNT(*) AS count
         FROM {$p}sp_city_ticket_depts td
         INNER JOIN {$p}sp_city_tickets tk ON tk.id = td.ticket_id
         LEFT JOIN {$p}sp_city_departments d ON d.id = td.dept_id
         WHERE tk.$open AND td.acknowledged_at IS NULL
         GROUP BY td.dept_id ORDER BY count DESC"
    );
    $recent = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$p}sp_city_tickets WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    $prior_30 = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$p}sp_city_tickets
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    $no_dept = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$p}sp_city_tickets tk
         WHERE tk.$open AND NOT EXISTS (SELECT 1 FROM {$p}sp_city_ticket_depts td WHERE td.ticket_id = tk.id)"
    );
    $unassigned = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$p}sp_city_tickets WHERE $open AND assigned_to = 0"
    );
    $unacked_24h = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$p}sp_city_tickets
         WHERE $open AND acknowledged_at IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    );
    $open_emergency = $wpdb->get_results(
        "SELECT tk.ticket_number, tk.address, tk.status, tk.created_at, tk.acknowledged_at, tm.name AS assigned_name
         FROM {$p}sp_city_tickets tk
         LEFT JOIN {$p}sp_team tm ON tm.id = tk.assigned_to
         WHERE tk.$open AND tk.priority IN ('emergency','high')
         ORDER BY FIELD(tk.priority,'emergency','high'), tk.created_at ASC LIMIT 8"
    );
    $oldest = $wpdb->get_results(
        "SELECT tk.ticket_number, tk.address, tk.priority, tk.status, DATEDIFF(NOW(), tk.created_at) AS age_days,
                tm.name AS assigned_name,
                (SELECT GROUP_CONCAT(d.name SEPARATOR ', ') FROM {$p}sp_city_ticket_depts td
                   LEFT JOIN {$p}sp_city_departments d ON d.id = td.dept_id WHERE td.ticket_id = tk.id) AS depts
         FROM {$p}sp_city_tickets tk
         LEFT JOIN {$p}sp_team tm ON tm.id = tk.assigned_to
         WHERE tk.$open
         ORDER BY tk.created_at ASC LIMIT 6"
    );
    $ack_hours = $wpdb->get_var(
        "SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, acknowledged_at)) / 60
         FROM {$p}sp_city_tickets
         WHERE acknowledged_at IS NOT NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    $resolve_days = $wpdb->get_var(
        "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) / 24
         FROM {$p}sp_city_tickets
         WHERE resolved_at IS NOT NULL AND resolved_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    $resolved_30 = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$p}sp_city_tickets WHERE resolved_at IS NOT NULL AND resolved_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    $repeat_addr = $wpdb->get_results(
        "SELECT address, COUNT(*) AS count
         FROM {$p}sp_city_tickets
         WHERE address != '' AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
         GROUP BY address HAVING count > 1 ORDER BY count DESC LIMIT 5"
    );
    $by_source = $wpdb->get_results(
        "SELECT source, COUNT(*) AS count FROM {$p}sp_city_tickets
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY source ORDER BY count DESC"
    );

    $city  = get_option( 'sp_city_name', '' );
    $lines = array( ( $city ? $city : 'City' ) . ' service-request queue snapshot as of ' . date( 'F j, Y g:ia' ) . ':', '' );

    $lines[] = 'Requests by status (all time):';
    foreach ( $by_status as $r ) $lines[] = '  ' . str_replace( '_', ' ', $r->status ) . ": {$r->count}";

    if ( $by_priority ) {
        $lines[] = '';
        $lines[] = 'Open requests by priority:';
        foreach ( $by_priority as $r ) $lines[] = "  {$r->priority}: {$r->count}";
    }

    $lines[] = '';
    $lines[] = 'Open requests by department:';
    if ( $by_dept ) {
        foreach ( $by_dept as $r ) $lines[] = '  ' . ( $r->name ? $r->name : 'Unknown' ) . ": {$r->count}";
    } else {
        $lines[] = '  (none)';
    }
    if ( $no_dept ) $lines[] = "  Open requests with NO department assigned: $no_dept";

    if ( $dept_unacked ) {
        $lines[] = '';
        $lines[] = 'Open department assignments not yet acknowledged, by department:';
        foreach ( $dept_unacked as $r ) $lines[] = '  ' . ( $r->name ? $r->name : 'Unknown' ) . ": {$r->count}";
    }

    $lines[] = '';
    $lines[] = "New requests in last 30 days: $recent (previous 30 days: $prior_30)";
    $lines[] = "Resolved in last 30 days: $resolved_30";
    $lines[] = "Open requests unassigned to a staff member: $unassigned";
    $lines[] = "Open requests unacknowledged for more than 24 hours: $unacked_24h";
    $lines[] = 'Average time to acknowledge (last 30 days): ' . ( $ack_hours !== null ? round( (float) $ack_hours, 1 ) . ' hours' : 'no data' );
    $lines[] = 'Average time to resolve (last 30 days): ' . ( $resolve_days !== null ? round( (float) $resolve_days, 1 ) . ' days' : 'no data' );

    if ( $by_source ) {
        $lines[] = '';
        $lines[] = 'Request source, last 30 days:';
        foreach ( $by_source as $r ) $lines[] = '  ' . sp_ai_city_source_label( $r->source ) . ": {$r->count}";
    }

    if ( $open_emergency ) {
        $lines[] = '';
        $lines[] = 'Open emergency / high priority requests:';
        foreach ( $open_emergency as $t ) {
            $ack      = $t->acknowledged_at ? 'acknowledged' : 'NOT acknowledged';
            $assigned = $t->assigned_name ? "assigned to {$t->assigned_name}" : 'unassigned';
            $lines[]  = "  - {$t->ticket_number} at {$t->address} — " . str_replace( '_', ' ', $t->status ) . ", $ack, $assigned, opened " . human_time_diff( strtotime( $t->created_at ) ) . ' ago';
        }
    }

    if ( $oldest ) {
        $lines[] = '';
        $lines[] = 'Oldest unresolved requests:';
        foreach ( $oldest as $t ) {
            $assigned = $t->assigned_name ? "assigned to {$t->assigned_name}" : 'unassigned';
            $depts    = $t->depts ? $t->depts : 'no department';
            $lines[]  = "  - {$t->ticket_number} [{$t->priority}] at {$t->address} — {$t->age_days} days old, " . str_replace( '_', ' ', $t->status ) . ", $depts, $assigned";
        }
    }

    if ( $repeat_addr ) {
        $lines[] = '';
        $lines[] = 'Addresses with repeat requests in the last 90 days:';
        foreach ( $repeat_addr as $r ) $lines[] = "  - {$r->address}: {$r->count} requests";
    }

    $context = implode( "\n", $lines );

    $prompt = "You are an operations analyst for a city public works and utility service department. "
        . "Based on the service-request queue data below, write a concise analysis with these sections (use '# ' headings):\n"
        . "# Queue Health — overall state in 2-3 sentences, including the 30-day trend\n"
        . "# Needs Attention Now — emergency/high priority, unacknowledged, unassigned or aging requests that a supervisor should act on today\n"
        . "# Department Workload — which departments carry the load or are falling behind on acknowledgements\n"
        . "# Response Performance — time to acknowledge and resolve, and whether it is acceptable for a municipal service desk\n"
        . "# Patterns — repeat addresses, source mix, anything unusual\n"
        . "# Recommended Actions — 2-3 specific, practical actions for supervisors this week\n\n"
        . "Be direct and actionable. Refer to requests by ticket number. Do not invent data that is not provided. Keep the whole analysis under 500 words: short paragraphs, no filler, every section present.\n\n"
        . $context;

    $result = sp_ai_call_api( $prompt, 2500 );
    if ( is_wp_error( $result ) ) return $result;

    update_option( 'sp_ai_city_analysis_cache', array(
        'text'         => $result,
        'generated_at' => time(),
    ), false );

    return $result;
}
