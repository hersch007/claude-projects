<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$today    = date( 'Y-m-d' );
$now      = current_time( 'mysql' );
$month_start = date( 'Y-m-01' );
$last_month_start = date( 'Y-m-01', strtotime( '-1 month' ) );
$last_month_end   = date( 'Y-m-t', strtotime( '-1 month' ) );

// ── Core System counts ────────────────────────────────────────────────────────

$total_contacts  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_contacts" );
$new_contacts_mo = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sp_contacts WHERE created_at >= %s", $month_start
) );
$contacts_prev   = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sp_contacts WHERE created_at >= %s AND created_at <= %s",
    $last_month_start, $last_month_end
) );

$total_companies = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_companies" );

// ── Lead pipeline ─────────────────────────────────────────────────────────────

$lead_counts = $wpdb->get_results(
    "SELECT status, COUNT(*) AS cnt FROM {$wpdb->prefix}sp_leads GROUP BY status"
);
$lead_map = array( 'new' => 0, 'contacted' => 0, 'qualified' => 0, 'unqualified' => 0, 'closed' => 0 );
foreach ( $lead_counts as $r ) {
    if ( isset( $lead_map[ $r->status ] ) ) $lead_map[ $r->status ] = (int) $r->cnt;
}
$total_leads     = array_sum( $lead_map );
$active_leads    = $lead_map['new'] + $lead_map['contacted'] + $lead_map['qualified'];
$new_leads_mo    = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sp_leads WHERE created_at >= %s", $month_start
) );

// ── Tasks ─────────────────────────────────────────────────────────────────────

$open_tasks    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tasks WHERE status='open'" );
$overdue_tasks = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tasks WHERE status='open' AND due_date IS NOT NULL AND due_date < %s", $today
) );
$done_tasks_mo = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tasks WHERE status='done' AND created_at >= %s", $month_start
) );

// ── Tickets (optional — sp-tickets plugin) ────────────────────────────────────
// Gated on the addon being genuinely active, not just its table existing — tables
// persist after deactivation. See sp_is_addon_active() in start-performance.php.

$has_tickets   = sp_is_addon_active( 'sp-tickets' ) && $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_tickets'" );
$open_tickets  = 0;
$urgent_tickets = 0;
$resolved_mo   = 0;
if ( $has_tickets ) {
    $open_tickets   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tickets WHERE status IN ('open','in_progress')" );
    $urgent_tickets = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tickets WHERE priority='urgent' AND status IN ('open','in_progress')" );
    $resolved_mo    = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tickets WHERE status IN ('resolved','closed') AND updated_at >= %s", $month_start
    ) );
}

// ── Quotes / Sales pipeline ───────────────────────────────────────────────────
// Custom sales modules (e.g. smti-sales, whose real data lives in HubSpot, not the
// local sp_smti_quotes table) contribute their own KPI cards via the
// `sp_intel_kpi_cards` filter — see the provider-card render in the grid below.
// Intelligence Core no longer reads sp_smti_quotes directly here, so its numbers
// can't drift from the live source the dashboard + AI summary use.

// ── Activity feed (last 15 across all records) ────────────────────────────────

$activity = $wpdb->get_results(
    "SELECT a.*,
        COALESCE( CONCAT(c.first_name,' ',c.last_name), co.name, '' ) AS record_name
     FROM {$wpdb->prefix}sp_activity a
     LEFT JOIN {$wpdb->prefix}sp_contacts  c  ON a.record_type='contact' AND a.record_id=c.id
     LEFT JOIN {$wpdb->prefix}sp_companies co ON a.record_type='company' AND a.record_id=co.id
     ORDER BY a.created_at DESC LIMIT 15"
);

// ── Growth trend: new contacts last 6 months ──────────────────────────────────

$trend_rows = $wpdb->get_results(
    "SELECT DATE_FORMAT(created_at,'%Y-%m') AS mo, COUNT(*) AS cnt
     FROM {$wpdb->prefix}sp_contacts
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY mo ORDER BY mo ASC"
);
$trend_months = array();
for ( $i = 5; $i >= 0; $i-- ) {
    $trend_months[ date( 'Y-m', strtotime( "-$i month" ) ) ] = 0;
}
foreach ( $trend_rows as $r ) {
    if ( isset( $trend_months[ $r->mo ] ) ) $trend_months[ $r->mo ] = (int) $r->cnt;
}

// helpers
function sp_intel_delta( $curr, $prev ) {
    if ( $prev == 0 ) return $curr > 0 ? '+' . $curr : '';
    $d = $curr - $prev;
    return ( $d >= 0 ? '+' : '' ) . $d;
}
function sp_intel_trend_class( $curr, $prev ) {
    return $curr >= $prev ? 'sp-intel-up' : 'sp-intel-down';
}
?>

<div class="sp-page-header" style="align-items:center;">
    <div>
        <h1>Intelligence Core</h1>
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
        <span style="font-size:.8rem;color:#64748b;font-weight:500;">Data window:</span>
        <div style="display:flex;gap:4px;" id="sp-intel-days-selector">
            <?php foreach ( array( 7, 30, 90 ) as $d ) : ?>
                <button type="button" data-days="<?php echo $d; ?>"
                    class="sp-intel-day-btn <?php echo $d === 30 ? 'active' : ''; ?>"
                    style="padding:5px 12px;border-radius:6px;border:1px solid #e2e8f0;background:<?php echo $d === 30 ? 'var(--sp-accent,#2563eb)' : '#fff'; ?>;color:<?php echo $d === 30 ? '#fff' : '#374151'; ?>;font-size:.8rem;font-weight:600;cursor:pointer;">
                    <?php echo $d; ?>d
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- AI Smart Summary Card -->
<?php $has_api_key = (bool) get_option( 'sp_anthropic_api_key', '' ); ?>
<div id="sp-intel-ai-card" style="background:linear-gradient(135deg,#0f172a,#1e293b);border-radius:12px;padding:20px 24px;margin-bottom:24px;position:relative;overflow:hidden;">
    <div style="position:absolute;top:0;right:0;width:200px;height:200px;background:radial-gradient(circle,rgba(99,102,241,.15),transparent 70%);pointer-events:none;"></div>
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
        <div style="background:rgba(99,102,241,.2);border-radius:8px;padding:6px 8px;display:flex;">
            <svg viewBox="0 0 24 24" fill="none" width="16" height="16">
                <path fill="#818cf8" d="M12 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm6.364 2.636a1 1 0 010 1.414l-.707.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM21 11a1 1 0 110 2h-1a1 1 0 110-2h1zM5.636 4.636a1 1 0 011.414 0l.707.707A1 1 0 116.343 6.757l-.707-.707a1 1 0 010-1.414zM4 11a1 1 0 110 2H3a1 1 0 110-2h1zm14.364 7.364a1 1 0 01-1.414 0l-.707-.707a1 1 0 011.414-1.414l.707.707a1 1 0 010 1.414zM12 18a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.636 19.364a1 1 0 010-1.414l.707-.707a1 1 0 111.414 1.414l-.707.707a1 1 0 01-1.414 0zM12 8a4 4 0 100 8 4 4 0 000-8z"/>
            </svg>
        </div>
        <span style="font-size:.75rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#818cf8;"><?php echo esc_html( get_option( 'sp_platform_name', 'Start Performance' ) ); ?> Smart Summary</span>
        <?php if ( $has_api_key ) : ?>
        <button type="button" id="sp-intel-ai-refresh" title="Refresh insights" style="margin-left:auto;background:rgba(255,255,255,.08);border:none;border-radius:6px;padding:4px 8px;cursor:pointer;color:#94a3b8;font-size:.75rem;">↻ Refresh</button>
        <?php endif; ?>
    </div>
    <div id="sp-intel-ai-content">
        <?php if ( ! $has_api_key ) : ?>
            <p style="color:#64748b;font-size:.88rem;margin:0;">Add your Anthropic API key in <a href="<?php echo esc_url( home_url('/sp-app/?view=settings') ); ?>" style="color:#818cf8;">Settings</a> to enable AI-powered insights.</p>
        <?php else : ?>
            <p id="sp-intel-ai-text" style="color:#e2e8f0;font-size:.95rem;line-height:1.6;margin:0;">
                <span style="color:#475569;font-style:italic;">Loading insights…</span>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php if ( $has_api_key ) : ?>
<script>
(function(){
    var ajaxUrl = '<?php echo esc_js( admin_url('admin-ajax.php') ); ?>';
    var currentDays = 30;
    var loading = false;

    function formatInsight( raw ) {
        var lines = raw.split('\n');
        var html = '';
        lines.forEach(function(line){
            line = line.trim();
            if ( !line ) return;
            // **Label:** → styled heading
            line = line.replace(/^\*\*(.+?)\*\*:?\s*/, function(m, label){
                return '<span style="display:block;font-size:.7rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#94a3b8;margin-top:14px;margin-bottom:4px;">' + label + '</span>';
            });
            // bullet • or -
            if ( /^[•\-]/.test(line) ) {
                line = '<span style="display:block;padding-left:12px;margin-bottom:3px;color:#e2e8f0;">· ' + line.replace(/^[•\-]\s*/, '') + '</span>';
            } else {
                line = '<span style="display:block;margin-bottom:2px;">' + line + '</span>';
            }
            html += line;
        });
        return html;
    }

    function loadInsights( days ) {
        if ( loading ) return;
        loading = true;
        var text = document.getElementById('sp-intel-ai-text');
        if ( text ) text.innerHTML = '<span style="color:#475569;font-style:italic;">Analyzing your data…</span>';

        var fd = new FormData();
        fd.append('action', 'sp_intel_ai_insights');
        fd.append('days', days);

        fetch( ajaxUrl, { method:'POST', body: fd })
            .then(function(r){ return r.json(); })
            .then(function(d){
                loading = false;
                if ( d.success && d.data && d.data.insight ) {
                    if ( text ) text.innerHTML = formatInsight( d.data.insight );
                } else {
                    if ( text ) text.innerHTML = '<span style="color:#ef4444;">Could not load insights. Check your API key in Settings.</span>';
                }
            })
            .catch(function(){
                loading = false;
                if ( text ) text.innerHTML = '<span style="color:#ef4444;">Connection error. Please try again.</span>';
            });
    }

    // Day selector
    var btns = document.querySelectorAll('.sp-intel-day-btn');
    btns.forEach(function(btn){
        btn.addEventListener('click', function(){
            btns.forEach(function(b){
                b.style.background = '#fff';
                b.style.color = '#374151';
                b.classList.remove('active');
            });
            btn.style.background = 'var(--sp-accent,#2563eb)';
            btn.style.color = '#fff';
            btn.classList.add('active');
            currentDays = parseInt(btn.getAttribute('data-days'));
            loadInsights(currentDays);
        });
    });

    // Refresh button
    var refresh = document.getElementById('sp-intel-ai-refresh');
    if ( refresh ) refresh.addEventListener('click', function(){ loadInsights(currentDays); });

    // Auto-load on page open
    loadInsights(30);
})();
</script>
<?php endif; ?>

<?php
// AI addon (and any future AI surface) can render here, directly under the AI Smart
// Summary — keeps all "AI analyzes your business" content in Intelligence Core.
do_action( 'sp_intel_after_ai_summary' );
?>

<style>
.sp-intel-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:14px; margin-bottom:24px; }
.sp-intel-card { background:var(--sp-card-bg,#fff); border:1px solid var(--sp-border,#e5e7eb); border-radius:10px; padding:18px 20px; }
.sp-intel-card .sp-ic-label { font-size:.72rem; font-weight:600; color:var(--sp-muted,#6b7280); text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px; }
.sp-intel-card .sp-ic-value { font-size:2rem; font-weight:700; color:var(--sp-text,#111); line-height:1; }
.sp-intel-card .sp-ic-sub { font-size:.78rem; color:var(--sp-muted,#6b7280); margin-top:5px; }
.sp-intel-up   { color:#16a34a; }
.sp-intel-down { color:#dc2626; }
.sp-intel-accent { border-left:3px solid var(--sp-primary,#2563eb); }
.sp-intel-warn  { border-left:3px solid #f59e0b; }
.sp-intel-danger{ border-left:3px solid #dc2626; }
.sp-intel-green { border-left:3px solid #16a34a; }

.sp-intel-row { display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:24px; }
.sp-intel-row .sp-intel-section:only-child { grid-column:1 / -1; }
@media(max-width:700px){ .sp-intel-row{ grid-template-columns:1fr; } }

.sp-intel-section { background:var(--sp-card-bg,#fff); border:1px solid var(--sp-border,#e5e7eb); border-radius:10px; padding:20px; }
.sp-intel-section h3 { font-size:.85rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--sp-muted,#6b7280); margin:0 0 14px; }

.sp-intel-funnel { display:flex; gap:8px; align-items:flex-end; height:90px; }
.sp-intel-funnel-bar { flex:1; border-radius:4px 4px 0 0; min-height:6px; position:relative; }
.sp-intel-funnel-bar span { position:absolute; bottom:-20px; left:50%; transform:translateX(-50%); font-size:.68rem; color:var(--sp-muted,#6b7280); white-space:nowrap; }
.sp-intel-funnel-bar em { position:absolute; top:-20px; left:50%; transform:translateX(-50%); font-size:.75rem; font-weight:700; font-style:normal; }
.sp-intel-funnel-wrap { padding-bottom:24px; padding-top:24px; }

.sp-intel-trend { display:flex; gap:4px; align-items:flex-end; height:60px; margin-top:8px; }
.sp-intel-trend-bar { flex:1; border-radius:3px 3px 0 0; background:var(--sp-primary,#2563eb); opacity:.7; min-height:3px; }

.sp-intel-stat-row { display:flex; justify-content:space-between; align-items:center; padding:7px 0; border-bottom:1px solid var(--sp-border,#e5e7eb); font-size:.85rem; }
.sp-intel-stat-row:last-child { border-bottom:none; }
.sp-intel-stat-row .sp-ic-badge { display:inline-block; padding:2px 8px; border-radius:99px; font-size:.72rem; font-weight:600; background:var(--sp-border,#e5e7eb); }

.sp-intel-activity { list-style:none; margin:0; padding:0; }
.sp-intel-activity li { font-size:.82rem; padding:6px 0; border-bottom:1px solid var(--sp-border,#e5e7eb); color:var(--sp-text,#111); }
.sp-intel-activity li:last-child { border-bottom:none; }
.sp-intel-activity li .sp-ia-time { color:var(--sp-muted,#6b7280); font-size:.75rem; float:right; }
.sp-intel-activity li .sp-ia-who  { font-weight:600; }
</style>

<?php
// ── KPI stat cards ────────────────────────────────────────────────────────────
$contact_delta = sp_intel_delta( $new_contacts_mo, $contacts_prev );
$contact_cls   = sp_intel_trend_class( $new_contacts_mo, $contacts_prev );
?>

<div class="sp-intel-grid">

    <?php if ( sp_intel_core_kpi_enabled( 'contacts' ) ) : ?>
    <div class="sp-intel-card sp-intel-accent">
        <div class="sp-ic-label">Total Contacts</div>
        <div class="sp-ic-value"><?php echo number_format( $total_contacts ); ?></div>
        <div class="sp-ic-sub"><?php echo $new_contacts_mo; ?> added this month
            <?php if ( $contact_delta ) : ?>
                <span class="<?php echo $contact_cls; ?>">(<?php echo $contact_delta; ?> vs last)</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( sp_intel_core_kpi_enabled( 'companies' ) ) : ?>
    <div class="sp-intel-card sp-intel-accent">
        <div class="sp-ic-label">Companies</div>
        <div class="sp-ic-value"><?php echo number_format( $total_companies ); ?></div>
        <div class="sp-ic-sub">&nbsp;</div>
    </div>
    <?php endif; ?>

    <?php if ( sp_intel_core_kpi_enabled( 'leads' ) ) : ?>
    <div class="sp-intel-card sp-intel-accent">
        <div class="sp-ic-label">Active Leads</div>
        <div class="sp-ic-value"><?php echo number_format( $active_leads ); ?></div>
        <div class="sp-ic-sub"><?php echo $new_leads_mo; ?> new this month</div>
    </div>
    <?php endif; ?>

    <?php if ( sp_intel_core_kpi_enabled( 'tasks' ) ) : ?>
    <div class="sp-intel-card <?php echo $overdue_tasks > 0 ? 'sp-intel-danger' : 'sp-intel-accent'; ?>">
        <div class="sp-ic-label">Open Tasks</div>
        <div class="sp-ic-value"><?php echo number_format( $open_tasks ); ?></div>
        <div class="sp-ic-sub">
            <?php if ( $overdue_tasks > 0 ) : ?>
                <span class="sp-intel-down"><?php echo $overdue_tasks; ?> overdue</span>
            <?php else : ?>
                <?php echo $done_tasks_mo; ?> completed this month
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( $has_tickets ) : ?>
    <div class="sp-intel-card <?php echo $urgent_tickets > 0 ? 'sp-intel-warn' : 'sp-intel-accent'; ?>">
        <div class="sp-ic-label">Open Tickets</div>
        <div class="sp-ic-value"><?php echo number_format( $open_tickets ); ?></div>
        <div class="sp-ic-sub">
            <?php if ( $urgent_tickets > 0 ) : ?>
                <span class="sp-intel-down"><?php echo $urgent_tickets; ?> urgent</span>
            <?php else : ?>
                <?php echo $resolved_mo; ?> resolved this month
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // Module-provided KPI cards (custom modules feed their own live-source cards
    // here instead of Intelligence Core hardcoding table reads). Each card is:
    // array( 'label'=>, 'value'=>(preformatted string), 'sub'=>, 'accent'=>accent|warn|danger|green )
    $provider_cards = apply_filters( 'sp_intel_kpi_cards', array(), array( 'month_start' => $month_start ) );
    foreach ( (array) $provider_cards as $card ) :
        if ( empty( $card['label'] ) ) continue;
        $accent = isset( $card['accent'] ) ? preg_replace( '/[^a-z]/', '', strtolower( $card['accent'] ) ) : 'accent';
        if ( ! in_array( $accent, array( 'accent', 'warn', 'danger', 'green' ), true ) ) $accent = 'accent';
    ?>
    <div class="sp-intel-card sp-intel-<?php echo $accent; ?>">
        <div class="sp-ic-label"><?php echo esc_html( $card['label'] ); ?></div>
        <div class="sp-ic-value"><?php echo esc_html( isset( $card['value'] ) ? $card['value'] : '' ); ?></div>
        <div class="sp-ic-sub"><?php echo esc_html( isset( $card['sub'] ) ? $card['sub'] : '' ); ?></div>
    </div>
    <?php endforeach; ?>

</div>

<?php
// ── Lead funnel + Contact trend ───────────────────────────────────────────────
$funnel_max = max( 1, max( array_values( $lead_map ) ) );
$funnel_colors = array(
    'new'         => '#3b82f6',
    'contacted'   => '#8b5cf6',
    'qualified'   => '#10b981',
    'unqualified' => '#f59e0b',
    'closed'      => '#6b7280',
);
$trend_max = max( 1, max( array_values( $trend_months ) ) );
$show_leads    = sp_intel_core_kpi_enabled( 'leads' );
$show_contacts = sp_intel_core_kpi_enabled( 'contacts' );
?>

<?php if ( $show_leads || $show_contacts ) : ?>
<div class="sp-intel-row">

    <?php if ( $show_leads ) : ?>
    <div class="sp-intel-section">
        <h3>Lead Pipeline</h3>
        <div class="sp-intel-funnel-wrap">
            <div class="sp-intel-funnel">
                <?php foreach ( $lead_map as $status => $cnt ) :
                    $pct = max( 5, round( $cnt / $funnel_max * 100 ) );
                    $color = $funnel_colors[ $status ] ?? '#6b7280';
                ?>
                    <div class="sp-intel-funnel-bar" style="height:<?php echo $pct; ?>%;background:<?php echo $color; ?>;">
                        <em><?php echo $cnt; ?></em>
                        <span><?php echo ucfirst( $status ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( $show_contacts ) : ?>
    <div class="sp-intel-section">
        <h3>New Contacts — Last 6 Months</h3>
        <div class="sp-intel-trend">
            <?php foreach ( $trend_months as $mo => $cnt ) :
                $pct = max( 4, round( $cnt / $trend_max * 100 ) );
            ?>
                <div title="<?php echo esc_attr( $mo . ': ' . $cnt ); ?>" class="sp-intel-trend-bar" style="height:<?php echo $pct; ?>%;"></div>
            <?php endforeach; ?>
        </div>
        <div style="font-size:.72rem;color:var(--sp-muted,#6b7280);margin-top:28px;display:flex;gap:4px;">
            <?php foreach ( $trend_months as $mo => $cnt ) : ?>
                <div style="flex:1;text-align:center;"><?php echo date( 'M', strtotime( $mo . '-01' ) ); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php endif; ?>

<div class="sp-intel-row">

    <?php if ( $show_leads ) : ?>
    <div class="sp-intel-section">
        <h3>Lead Status Breakdown</h3>
        <?php foreach ( $lead_map as $status => $cnt ) :
            $color = $funnel_colors[ $status ] ?? '#6b7280';
        ?>
            <div class="sp-intel-stat-row">
                <span style="display:flex;align-items:center;gap:8px;">
                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?php echo $color; ?>;"></span>
                    <?php echo ucfirst( $status ); ?>
                </span>
                <span style="font-weight:600;"><?php echo $cnt; ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="sp-intel-section">
        <h3>Recent Activity</h3>
        <?php if ( empty( $activity ) ) : ?>
            <p style="color:var(--sp-muted,#6b7280);font-size:.85rem;">No activity recorded yet.</p>
        <?php else : ?>
            <ul class="sp-intel-activity">
                <?php foreach ( $activity as $a ) :
                    $age = human_time_diff( strtotime( $a->created_at ), current_time( 'timestamp' ) );
                    $who = trim( $a->record_name );
                ?>
                    <li>
                        <span class="sp-ia-time"><?php echo esc_html( $age ); ?> ago</span>
                        <?php if ( $who ) : ?><span class="sp-ia-who"><?php echo esc_html( $who ); ?></span> — <?php endif; ?>
                        <?php echo esc_html( ucfirst( str_replace( '_', ' ', $a->action ) ) ); ?>
                        <?php if ( $a->detail ) : ?><span style="color:var(--sp-muted,#6b7280);"> · <?php echo esc_html( $a->detail ); ?></span><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

</div>

<?php if ( $has_tickets ) : ?>
<div class="sp-intel-section" style="margin-bottom:24px;">
    <h3>Ticket Health</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;">
        <?php
        $ticket_stats = $wpdb->get_results(
            "SELECT status, priority, COUNT(*) AS cnt FROM {$wpdb->prefix}sp_tickets GROUP BY status, priority"
        );
        $t_by_status = array();
        $t_by_priority = array();
        foreach ( $ticket_stats as $r ) {
            $t_by_status[ $r->status ]     = ( $t_by_status[ $r->status ]     ?? 0 ) + (int)$r->cnt;
            $t_by_priority[ $r->priority ] = ( $t_by_priority[ $r->priority ] ?? 0 ) + (int)$r->cnt;
        }
        $status_colors = array( 'open' => '#3b82f6', 'in_progress' => '#8b5cf6', 'resolved' => '#10b981', 'closed' => '#6b7280' );
        foreach ( $t_by_status as $s => $c ) :
            $col = $status_colors[ $s ] ?? '#6b7280';
        ?>
            <div style="background:var(--sp-bg,#f9fafb);border-radius:8px;padding:12px 14px;border-left:3px solid <?php echo $col; ?>;">
                <div style="font-size:1.4rem;font-weight:700;"><?php echo $c; ?></div>
                <div style="font-size:.75rem;color:var(--sp-muted,#6b7280);margin-top:2px;"><?php echo ucwords( str_replace( '_', ' ', $s ) ); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php
// Modules can append their own detail panels below the KPI grid (e.g. smti-sales'
// live-HubSpot "Top Distributors" scorecard). Uses the same .sp-intel-section
// styling defined on this page.
do_action( 'sp_intel_after_kpi' );
?>
