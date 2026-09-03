<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$member    = sp_get_current_team_member();
$member_id = $member ? (int) $member->id : 0;
$today     = date( 'Y-m-d' );
$now       = current_time( 'mysql' );
$is_admin  = sp_is_admin_member();

// ── Greeting ───────────────────────────────────────────────────────────────────
$hour         = (int) date( 'G' );
$greeting     = $hour < 12 ? 'Good morning' : ( $hour < 17 ? 'Good afternoon' : 'Good evening' );
$first_name   = $member ? trim( explode( ' ', $member->name )[0] ) : '';

// ── Jurisdiction contact dashboard ─────────────────────────────────────────────
$is_jur_dash = $member && ! $is_admin && ! sp_is_super_admin() && $member->role === 'user';
if ( $is_jur_dash ) {
    // Check whether they have submitted a questionnaire
    $wts_submitted = false;
    $wts_submitted_date = '';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_wts_onboarding'" ) ) {
        $wts_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT submitted_at FROM {$wpdb->prefix}sp_wts_onboarding WHERE member_id=%d ORDER BY id DESC LIMIT 1",
            $member_id
        ) );
        if ( $wts_row ) {
            $wts_submitted      = true;
            $wts_submitted_date = date( 'F j, Y', strtotime( $wts_row->submitted_at ) );
        }
    }
    ?>
    <div class="sp-greeting-bar">
        <div>
            <div class="sp-greeting-text"><?php echo esc_html( $greeting . ( $first_name ? ', ' . $first_name : '' ) ); ?>.</div>
            <div class="sp-greeting-sub">Welcome to the WTS Jurisdiction Portal.</div>
        </div>
        <div class="sp-greeting-date"><?php echo date( 'l, F j' ); ?></div>
    </div>

    <div class="sp-quick-actions">
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=client-setup' ) ); ?>" class="sp-qa-btn sp-qa-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 2a1 1 0 000 2h6a1 1 0 000-2H9z"/><path d="M4 5a2 2 0 012-2 3 3 0 003 3h6a3 3 0 003-3 2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5z"/></svg>
            My Client Setup
        </a>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=knowledge' ) ); ?>" class="sp-qa-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
            Knowledge Base
        </a>
        <a href="https://wirelesstowersolutions.com/Sites/" target="_blank" rel="noopener noreferrer" class="sp-qa-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            AMP Login
        </a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:4px;">

        <!-- Setup status -->
        <div class="sp-card" style="padding:22px 24px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                <div style="width:40px;height:40px;border-radius:10px;background:<?php echo $wts_submitted ? '#dcfce7' : '#fef3c7'; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <?php if ( $wts_submitted ) : ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round"><polyline points="20,6 9,17 4,12"/></svg>
                    <?php else : ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?php endif; ?>
                </div>
                <div>
                    <div style="font-weight:700;font-size:.95rem;color:#1e293b;">Client Setup Questionnaire</div>
                    <div style="font-size:.78rem;color:<?php echo $wts_submitted ? '#16a34a' : '#d97706'; ?>;font-weight:600;margin-top:2px;">
                        <?php echo $wts_submitted ? 'Submitted' . ( $wts_submitted_date ? ' · ' . esc_html( $wts_submitted_date ) : '' ) : 'Action Required'; ?>
                    </div>
                </div>
            </div>
            <p style="font-size:.83rem;color:#64748b;margin:0 0 14px;">
                <?php echo $wts_submitted
                    ? 'Your questionnaire has been received. WTS will be in touch if any additional information is needed.'
                    : 'Please complete your Client Setup Questionnaire so WTS can review your jurisdiction details.'; ?>
            </p>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=client-setup' ) ); ?>" class="sp-btn <?php echo $wts_submitted ? 'sp-btn-secondary' : 'sp-btn-primary'; ?> sp-btn-sm">
                <?php echo $wts_submitted ? 'View Submission' : 'Complete Now →'; ?>
            </a>
        </div>

        <!-- Resources -->
        <div class="sp-card" style="padding:22px 24px;">
            <div style="font-weight:700;font-size:.95rem;color:#1e293b;margin-bottom:14px;">Resources</div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <a href="<?php echo esc_url( home_url( '/sp-app/?view=knowledge' ) ); ?>" style="display:flex;align-items:center;gap:10px;text-decoration:none;padding:10px 12px;border-radius:8px;border:1px solid #e2e8f0;background:#f8fafc;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
                    <div>
                        <div style="font-size:.85rem;font-weight:600;color:#1e293b;">Knowledge Base</div>
                        <div style="font-size:.75rem;color:#64748b;">Articles, guides, and resources</div>
                    </div>
                </a>
                <a href="https://wirelesstowersolutions.com/Sites/" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;gap:10px;text-decoration:none;padding:10px 12px;border-radius:8px;border:1px solid #e2e8f0;background:#f8fafc;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--sp-accent,#CC1F1F)" stroke-width="2" stroke-linecap="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15,3 21,3 21,9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    <div>
                        <div style="font-size:.85rem;font-weight:600;color:#1e293b;">AMP Login</div>
                        <div style="font-size:.75rem;color:#64748b;">Wireless Tower Solutions portal</div>
                    </div>
                </a>
                <a href="<?php echo esc_url( home_url( '/sp-app/?view=profile' ) ); ?>" style="display:flex;align-items:center;gap:10px;text-decoration:none;padding:10px 12px;border-radius:8px;border:1px solid #e2e8f0;background:#f8fafc;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <div>
                        <div style="font-size:.85rem;font-weight:600;color:#1e293b;">My Profile</div>
                        <div style="font-size:.75rem;color:#64748b;">Update your name and PIN</div>
                    </div>
                </a>
            </div>
        </div>

    </div>

    <style>
    .sp-greeting-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding:20px 24px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;border-left:4px solid var(--sp-accent,#CC1F1F)}
    .sp-greeting-text{font-size:1.35rem;font-weight:800;color:#1e293b;line-height:1.2}
    .sp-greeting-sub{font-size:.83rem;color:#64748b;margin-top:4px}
    .sp-greeting-date{font-size:.82rem;color:#94a3b8;font-weight:600;white-space:nowrap}
    .sp-quick-actions{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px}
    .sp-qa-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;font-size:.83rem;font-weight:600;text-decoration:none;background:#fff;border:1.5px solid #e2e8f0;color:#475569;transition:all .15s;white-space:nowrap}
    .sp-qa-btn:hover{border-color:var(--sp-accent,#CC1F1F);color:var(--sp-accent,#CC1F1F);background:#fef2f2}
    .sp-qa-primary{background:var(--sp-accent,#CC1F1F);color:#fff;border-color:var(--sp-accent,#CC1F1F)}
    .sp-qa-primary:hover{background:var(--sp-accent-dark,#b01818);color:#fff;border-color:var(--sp-accent-dark,#b01818)}
    </style>
    <?php
    return;
}

// ── Stat counts ────────────────────────────────────────────────────────────────
$contacts  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_contacts" );
$companies = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_companies" );
$leads     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_leads" );

// Overdue tasks (assigned to me)
$overdue_count = $member_id ? (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tasks WHERE assigned_to=%d AND status='open' AND due_date IS NOT NULL AND due_date < %s",
    $member_id, $today
) ) : 0;

// Open tasks due today or overdue
$due_tasks = $member_id ? $wpdb->get_results( $wpdb->prepare(
    "SELECT tk.*, COALESCE( CONCAT(c.first_name,' ',c.last_name), co.name, l2.id, '' ) AS record_name,
     tk.record_type AS rtype, tk.record_id AS rid
     FROM {$wpdb->prefix}sp_tasks tk
     LEFT JOIN {$wpdb->prefix}sp_contacts c  ON tk.record_type='contact'  AND tk.record_id=c.id
     LEFT JOIN {$wpdb->prefix}sp_companies co ON tk.record_type='company'  AND tk.record_id=co.id
     LEFT JOIN {$wpdb->prefix}sp_leads l2     ON tk.record_type='lead'     AND tk.record_id=l2.id
     WHERE tk.assigned_to=%d AND tk.status='open' AND tk.due_date IS NOT NULL AND tk.due_date<=%s
     ORDER BY tk.due_date ASC LIMIT 10",
    $member_id, $today
) ) : array();

// Note reminders
$due_notes = $member_id ? $wpdb->get_results( $wpdb->prepare(
    "SELECT n.*, COALESCE( CONCAT(c.first_name,' ',c.last_name), co.name, '' ) AS record_name
     FROM {$wpdb->prefix}sp_notes n
     LEFT JOIN {$wpdb->prefix}sp_contacts c  ON n.record_type='contact' AND n.record_id=c.id
     LEFT JOIN {$wpdb->prefix}sp_companies co ON n.record_type='company' AND n.record_id=co.id
     WHERE n.created_by=%d AND n.reminder_at IS NOT NULL AND n.reminder_at<=%s AND n.reminder_sent=0
     ORDER BY n.reminder_at ASC LIMIT 5",
    $member_id, $now
) ) : array();

// ── Pipeline funnel ────────────────────────────────────────────────────────────
$pipeline_data = $wpdb->get_results(
    "SELECT status, COUNT(*) AS cnt FROM {$wpdb->prefix}sp_leads GROUP BY status"
);
$pipeline_map = array( 'new' => 0, 'contacted' => 0, 'qualified' => 0, 'unqualified' => 0, 'closed' => 0 );
foreach ( $pipeline_data as $row ) {
    if ( isset( $pipeline_map[ $row->status ] ) ) $pipeline_map[ $row->status ] = (int) $row->cnt;
}
$pipeline_max = max( array_values( $pipeline_map ) ) ?: 1;
$pipeline_colors = array(
    'new'         => '#3b82f6',
    'contacted'   => '#f59e0b',
    'qualified'   => '#8b5cf6',
    'unqualified' => '#ef4444',
    'closed'      => '#10b981',
);

// ── Recent activity feed ───────────────────────────────────────────────────────
$recent_activity = $wpdb->get_results(
    "SELECT a.*, t.name AS actor,
     COALESCE( CONCAT(c.first_name,' ',c.last_name), co.name, '' ) AS record_name
     FROM {$wpdb->prefix}sp_activity a
     LEFT JOIN {$wpdb->prefix}sp_team t ON t.id = a.created_by
     LEFT JOIN {$wpdb->prefix}sp_contacts c  ON a.record_type='contact' AND a.record_id=c.id
     LEFT JOIN {$wpdb->prefix}sp_companies co ON a.record_type='company' AND a.record_id=co.id
     ORDER BY a.created_at DESC LIMIT 12"
);

// ── Recent contacts ────────────────────────────────────────────────────────────
$recent_contacts = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}sp_contacts ORDER BY created_at DESC LIMIT 5"
);

// ── Revenue (Sales Core) ───────────────────────────────────────────────────────
// Gated on the addon actually being active, not just its tables existing — tables
// persist after deactivation, so a SHOW TABLES check alone can't tell "active" from
// "was installed once." See sp_is_addon_active() in start-performance.php.
$pipeline_value   = null;
$pipeline_won_mo  = null;
if ( sp_is_addon_active( 'sp-sales' ) && $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_estimates'" ) ) {
    $pipeline_value  = (float) $wpdb->get_var( "SELECT SUM(total)       FROM {$wpdb->prefix}sp_estimates WHERE status NOT IN ('Rejected','Expired')" );
    $pipeline_value += (float) $wpdb->get_var( "SELECT SUM(total_value) FROM {$wpdb->prefix}sp_proposals WHERE status NOT IN ('Rejected','Expired')" );
    $pipeline_value += (float) $wpdb->get_var( "SELECT SUM(value)       FROM {$wpdb->prefix}sp_contracts WHERE status IN ('Active')" );
    $pipeline_won_mo = (float) $wpdb->get_var( $wpdb->prepare(
        "SELECT SUM(value) FROM {$wpdb->prefix}sp_contracts WHERE status='Active' AND start_date >= %s",
        date('Y-m-01')
    ) );
}
// Note: smti-sales' own dashboard widget (sp_smti_sales_dashboard_stats) shows the
// real HubSpot-live pipeline value now — this quick-stat no longer overrides it from
// the local sp_smti_quotes table, which only ever held a subset of real deals.

// ── Status line for greeting ───────────────────────────────────────────────────
$tasks_today = $member_id ? (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tasks WHERE assigned_to=%d AND status='open' AND due_date IS NOT NULL AND due_date<=%s",
    $member_id, $today
) ) : 0;
$leads_active = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_leads WHERE status IN ('new','contacted','qualified')" );
$status_parts = array();
if ( $tasks_today  ) $status_parts[] = $tasks_today  . ' task'  . ( $tasks_today  !== 1 ? 's' : '' ) . ' due today';
if ( $leads_active ) $status_parts[] = $leads_active . ' active lead' . ( $leads_active !== 1 ? 's' : '' );
$status_line = $status_parts ? implode( ' · ', $status_parts ) : 'Everything is up to date.';

// ── Activity this week (admin only) ───────────────────────────────────────────
$week_start  = date( 'Y-m-d', strtotime( 'monday this week' ) );
$week_counts = $is_admin ? $wpdb->get_results( $wpdb->prepare(
    "SELECT t.name, COUNT(*) AS total
     FROM {$wpdb->prefix}sp_activity a
     LEFT JOIN {$wpdb->prefix}sp_team t ON t.id = a.created_by
     WHERE a.created_at >= %s AND a.created_by > 0
     GROUP BY a.created_by ORDER BY total DESC LIMIT 5",
    $week_start . ' 00:00:00'
) ) : array();

$action_labels = array(
    'created'       => 'created',
    'updated'       => 'updated',
    'deleted'       => 'deleted',
    'status_change' => 'changed status on',
    'note_added'    => 'added a note to',
    'task_added'    => 'added a task to',
    'task_done'     => 'completed a task on',
    'file_uploaded' => 'uploaded a file to',
);

// ── Core-section visibility (auto-hide for quotes-only clients) ─────────────────
// Mirror the KPI Dashboard's per-metric enable / auto-hide-when-empty logic so a
// client whose core CRM is empty (e.g. SMTI, Fruth — real data lives in HubSpot /
// sqs_quotes) doesn't show a row of zero cards, an empty pipeline funnel, or empty
// activity/contacts. Falls back to always-visible when Intelligence Core is absent
// (so a plain core install is unchanged). SP has data, so nothing hides there.
$sp_metric_on = function( $key ) {
    return function_exists( 'sp_intel_core_kpi_enabled' ) ? sp_intel_core_kpi_enabled( $key ) : true;
};
$has_sales   = sp_is_addon_active( 'sp-sales' ) || sp_is_addon_active( 'sp-smti-sales' );
$has_tickets = sp_is_addon_active( 'sp-tickets' );
$has_ops     = sp_is_addon_active( 'sp-operations' );
$has_kb      = sp_is_addon_active( 'sp-knowledge' );

$show_contacts_card  = ! sp_is_view_hidden( 'contacts' )  && $sp_metric_on( 'contacts' );
$show_companies_card = ! sp_is_view_hidden( 'companies' ) && $sp_metric_on( 'companies' );
$show_leads_card     = $has_sales && ! sp_is_view_hidden( 'leads' ) && $sp_metric_on( 'leads' );
$show_tasks_card     = ! sp_is_view_hidden( 'tasks' )     && $sp_metric_on( 'tasks' );
$show_pipeline_card  = $has_sales && $pipeline_value !== null && ! sp_is_view_hidden( 'sales-estimates' );
$show_core_metrics   = $show_contacts_card || $show_companies_card || $show_leads_card || $show_tasks_card || $show_pipeline_card;

$show_pipeline_funnel = $has_sales && $sp_metric_on( 'leads' ) && array_sum( $pipeline_map ) > 0;
$show_activity_card   = ! empty( $recent_activity );
$show_contacts_list   = ! empty( $recent_contacts );
$show_team_card       = ( $is_admin && ! empty( $week_counts ) );
$show_dash_grid       = $show_pipeline_funnel || $show_activity_card || $show_contacts_list || $show_team_card;
?>

<!-- ── Greeting ──────────────────────────────────────────────────────────────── -->
<div class="sp-greeting-bar">
    <div>
        <div class="sp-greeting-text"><?php echo esc_html( $greeting . ( $first_name ? ', ' . $first_name : '' ) ); ?>.</div>
        <div class="sp-greeting-sub"><?php echo esc_html( $status_line ); ?></div>
    </div>
    <div class="sp-greeting-date"><?php echo date( 'l, F j' ); ?></div>
</div>

<!-- ── Quick actions ─────────────────────────────────────────────────────────── -->
<div class="sp-quick-actions">
    <?php if ( $has_sales ) : ?>
    <a href="<?php echo esc_url( home_url( '/sp-app/?view=leads&action=new' ) ); ?>" class="sp-qa-btn sp-qa-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 5v14M5 12h14"/></svg>
        New Lead
    </a>
    <?php endif; ?>
    <a href="<?php echo esc_url( home_url( '/sp-app/?view=contacts&action=new' ) ); ?>" class="sp-qa-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        New Contact
    </a>
    <a href="<?php echo esc_url( home_url( '/sp-app/?view=companies&action=new' ) ); ?>" class="sp-qa-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
        New Company
    </a>
    <?php if ( sp_is_addon_active( 'sp-sales' ) ) : ?>
    <a href="<?php echo esc_url( home_url( '/sp-app/?view=sales-estimates&action=new' ) ); ?>" class="sp-qa-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        New Estimate
    </a>
    <?php elseif ( sp_is_addon_active( 'sp-smti-sales' ) ) : ?>
    <a href="<?php echo esc_url( home_url( '/sp-app/?view=smti-sales&action=new' ) ); ?>" class="sp-qa-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
        New Quote
    </a>
    <?php endif; ?>
    <?php if ( sp_is_addon_active( 'sp-tickets' ) ) : ?>
    <a href="<?php echo esc_url( home_url( '/sp-app/?view=tickets&action=new' ) ); ?>" class="sp-qa-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
        New Ticket
    </a>
    <?php endif; ?>
</div>

<?php if ( has_action( 'sp_dashboard_before_stats' ) ) : ?>
<div style="font-size:1.35rem;font-weight:800;text-transform:uppercase;letter-spacing:.03em;color:#1e293b;margin:4px 0 10px;">Custom Metrics</div>
<?php do_action( 'sp_dashboard_before_stats' ); ?>
<?php endif; ?>

<?php if ( $show_core_metrics ) : ?>
<div style="font-size:1.35rem;font-weight:800;text-transform:uppercase;letter-spacing:.03em;color:#1e293b;margin:24px 0 10px;">Core Metrics</div>

<!-- ── Stat row ──────────────────────────────────────────────────────────────── -->
<div class="sp-stats" style="margin-bottom:20px">
    <?php if ( $show_contacts_card ) : ?>
    <a href="<?php echo esc_url( home_url( '/sp-app/?view=contacts' ) ); ?>" class="sp-stat-card sp-stat-contacts sp-stat-link">
        <div class="sp-stat-value"><?php echo number_format( $contacts ); ?></div>
        <div class="sp-stat-label">Contacts</div>
    </a>
    <?php endif; ?>
    <?php if ( $show_companies_card ) : ?>
    <a href="<?php echo esc_url( home_url( '/sp-app/?view=companies' ) ); ?>" class="sp-stat-card sp-stat-companies sp-stat-link">
        <div class="sp-stat-value"><?php echo number_format( $companies ); ?></div>
        <div class="sp-stat-label">Companies</div>
    </a>
    <?php endif; ?>
    <?php if ( $show_leads_card ) : ?>
    <a href="<?php echo esc_url( home_url( '/sp-app/?view=leads' ) ); ?>" class="sp-stat-card sp-stat-leads sp-stat-link">
        <div class="sp-stat-value"><?php echo number_format( $leads ); ?></div>
        <div class="sp-stat-label">Total Leads</div>
    </a>
    <?php endif; ?>
    <?php if ( $show_tasks_card ) : ?>
    <a href="<?php echo esc_url( home_url( '/sp-app/?view=leads&leads_tab=pipeline' ) ); ?>" class="sp-stat-card sp-stat-link" style="border-top-color:<?php echo $overdue_count ? '#ef4444' : '#94a3b8'; ?>">
        <div class="sp-stat-value" style="color:<?php echo $overdue_count ? '#ef4444' : '#1e293b'; ?>"><?php echo number_format( $overdue_count ); ?></div>
        <div class="sp-stat-label">Overdue Tasks</div>
    </a>
    <?php endif; ?>
    <?php if ( $show_pipeline_card ) : ?>
    <div class="sp-stat-card" style="border-top-color:#10b981">
        <div class="sp-stat-value" style="color:#059669">$<?php echo $pipeline_value >= 1000 ? number_format($pipeline_value/1000,1).'k' : number_format($pipeline_value,0); ?></div>
        <div class="sp-stat-label">Pipeline Value</div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php do_action( 'sp_dashboard_after_stats' ); ?>

<!-- ── Reminders ─────────────────────────────────────────────────────────────── -->
<?php if ( ! empty( $due_tasks ) || ! empty( $due_notes ) ) : ?>
<div class="sp-card sp-reminders-card" style="margin-bottom:20px">
    <div class="sp-card-header">
        <h2>Your Reminders</h2>
        <span class="sp-count"><?php echo count($due_tasks) + count($due_notes); ?></span>
    </div>
    <?php foreach ( $due_tasks as $tk ) :
        $overdue = $tk->due_date < $today;
        $rtype   = rtrim( $tk->rtype, 's' );
        $link    = home_url( '/sp-app/?view=' . $rtype . 's&action=view&id=' . $tk->rid . '&tab=tasks' );
    ?>
    <div class="sp-reminder-item">
        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" style="display:contents">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="task">
            <input type="hidden" name="sp_id" value="0">
            <input type="hidden" name="record_type" value="<?php echo esc_attr($tk->rtype); ?>">
            <input type="hidden" name="record_id" value="<?php echo esc_attr($tk->rid); ?>">
            <input type="hidden" name="task_id" value="<?php echo esc_attr($tk->id); ?>">
            <button type="submit" class="sp-task-check sp-reminder-check" title="Mark done"></button>
        </form>
        <div class="sp-reminder-body">
            <a href="<?php echo esc_url($link); ?>" class="sp-reminder-title"><?php echo esc_html($tk->title); ?></a>
            <?php if ( trim($tk->record_name) ) : ?><span class="sp-reminder-record"><?php echo esc_html( trim($tk->record_name) ); ?></span><?php endif; ?>
        </div>
        <span class="sp-reminder-due <?php echo $overdue ? 'sp-overdue' : ''; ?>">
            <?php echo $overdue ? 'Overdue · ' . esc_html( date('M j', strtotime($tk->due_date)) ) : 'Today'; ?>
        </span>
    </div>
    <?php endforeach; ?>
    <?php foreach ( $due_notes as $n ) :
        $link = home_url( '/sp-app/?view=' . $n->record_type . 's&action=view&id=' . $n->record_id . '&tab=notes' );
    ?>
    <div class="sp-reminder-item">
        <div class="sp-reminder-note-icon">📝</div>
        <div class="sp-reminder-body">
            <a href="<?php echo esc_url($link); ?>" class="sp-reminder-title"><?php echo esc_html( substr($n->content,0,80).(strlen($n->content)>80?'…':'') ); ?></a>
            <?php if ( trim($n->record_name) ) : ?><span class="sp-reminder-record"><?php echo esc_html( trim($n->record_name) ); ?></span><?php endif; ?>
        </div>
        <span class="sp-reminder-due"><?php echo esc_html( date('M j g:ia', strtotime($n->reminder_at)) ); ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Main grid ──────────────────────────────────────────────────────────────── -->
<?php if ( $show_dash_grid ) : ?>
<div class="sp-dash-grid">

    <?php if ( $show_pipeline_funnel ) : ?>
    <!-- Pipeline funnel -->
    <div class="sp-card sp-dash-funnel">
        <div class="sp-card-header">
            <h2>Pipeline</h2>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=leads&leads_tab=pipeline' ) ); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">View Board</a>
        </div>
        <div style="padding:16px 20px">
        <?php foreach ( $pipeline_map as $status => $cnt ) :
            $pct   = round( $cnt / $pipeline_max * 100 );
            $color = $pipeline_colors[ $status ];
        ?>
        <div class="sp-funnel-row">
            <span class="sp-funnel-label"><?php echo esc_html( ucfirst($status) ); ?></span>
            <div class="sp-funnel-track">
                <div class="sp-funnel-bar" style="width:<?php echo max(4,$pct); ?>%;background:<?php echo esc_attr($color); ?>"></div>
            </div>
            <span class="sp-funnel-count"><?php echo $cnt; ?></span>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( $show_activity_card ) : ?>
    <!-- Recent activity feed -->
    <div class="sp-card sp-dash-activity">
        <div class="sp-card-header">
            <h2>Recent Activity</h2>
        </div>
        <?php if ( empty( $recent_activity ) ) : ?>
            <p class="sp-empty">No activity yet.</p>
        <?php else : ?>
        <div class="sp-activity-feed">
            <?php foreach ( $recent_activity as $evt ) :
                $verb      = isset( $action_labels[ $evt->action ] ) ? $action_labels[ $evt->action ] : $evt->action;
                $rname     = trim( $evt->record_name );
                $ago       = human_time_diff( strtotime( $evt->created_at ), current_time('timestamp') );
                $actor     = $evt->actor ?: 'System';
            ?>
            <div class="sp-feed-item">
                <div class="sp-feed-dot" style="background:<?php echo esc_attr( $pipeline_colors[ array_key_exists($evt->record_type, $pipeline_colors) ? $evt->record_type : 'new' ] ?? '#94a3b8' ); ?>"></div>
                <div class="sp-feed-body">
                    <span class="sp-feed-actor"><?php echo esc_html($actor); ?></span>
                    <span class="sp-feed-verb"> <?php echo esc_html($verb); ?> </span>
                    <?php if ( $rname ) : ?>
                        <span class="sp-feed-record"><?php echo esc_html($rname); ?></span>
                    <?php else : ?>
                        <span class="sp-feed-record"><?php echo esc_html( ucfirst($evt->record_type) . ' #' . $evt->record_id ); ?></span>
                    <?php endif; ?>
                    <span class="sp-feed-time"><?php echo esc_html($ago); ?> ago</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ( $show_contacts_list ) : ?>
    <!-- Recent contacts -->
    <div class="sp-card">
        <div class="sp-card-header">
            <h2>Recent Contacts</h2>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=contacts&action=new' ) ); ?>" class="sp-btn sp-btn-primary sp-btn-sm">+ Add</a>
        </div>
        <?php if ( empty( $recent_contacts ) ) : ?>
            <p class="sp-empty">No contacts yet.</p>
        <?php else : ?>
        <table class="sp-table">
            <tbody>
            <?php foreach ( $recent_contacts as $c ) : ?>
                <tr>
                    <td><a href="<?php echo esc_url( home_url('/sp-app/?view=contacts&action=view&id='.$c->id) ); ?>" class="sp-link"><?php echo esc_html( trim($c->first_name.' '.$c->last_name) ); ?></a></td>
                    <td class="sp-muted" style="font-size:12px"><?php echo esc_html( $c->email ); ?></td>
                    <td><span class="sp-badge sp-badge-<?php echo esc_attr($c->status); ?>"><?php echo esc_html( ucfirst($c->status) ); ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Team activity this week (admin only) -->
    <?php if ( $is_admin && ! empty( $week_counts ) ) : ?>
    <div class="sp-card">
        <div class="sp-card-header">
            <h2>Team This Week</h2>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=activity_report' ) ); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">Full Report</a>
        </div>
        <div style="padding:16px 20px">
        <?php
        $week_max = max( array_column( (array)$week_counts, 'total' ) ) ?: 1;
        foreach ( $week_counts as $wc ) : $pct = round( $wc->total / $week_max * 100 ); ?>
        <div class="sp-funnel-row" style="margin-bottom:10px">
            <span class="sp-funnel-label"><?php echo esc_html( $wc->name ?: 'Unknown' ); ?></span>
            <div class="sp-funnel-track">
                <div class="sp-funnel-bar" style="width:<?php echo max(4,$pct); ?>%;background:var(--sp-accent,#CC1F1F)"></div>
            </div>
            <span class="sp-funnel-count"><?php echo esc_html($wc->total); ?></span>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php endif; ?>

<?php do_action( 'sp_dashboard_after_grid' ); ?>

<style>
/* Greeting */
.sp-greeting-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding:20px 24px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;border-left:4px solid var(--sp-accent,#CC1F1F)}
.sp-greeting-text{font-size:1.35rem;font-weight:800;color:#1e293b;line-height:1.2}
.sp-greeting-sub{font-size:.83rem;color:#64748b;margin-top:4px}
.sp-greeting-date{font-size:.82rem;color:#94a3b8;font-weight:600;white-space:nowrap}
/* Quick actions */
.sp-quick-actions{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px}
.sp-qa-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;font-size:.83rem;font-weight:600;text-decoration:none;background:#fff;border:1.5px solid #e2e8f0;color:#475569;transition:all .15s;white-space:nowrap}
.sp-qa-btn:hover{border-color:var(--sp-accent,#CC1F1F);color:var(--sp-accent,#CC1F1F);background:#fef2f2}
.sp-qa-primary{background:var(--sp-accent,#CC1F1F);color:#fff;border-color:var(--sp-accent,#CC1F1F)}
.sp-qa-primary:hover{background:var(--sp-accent-dark,#b01818);color:#fff;border-color:var(--sp-accent-dark,#b01818)}
/* AI card */
.sp-ai-loading{display:flex;align-items:center;padding:4px 0}
.sp-ai-dot{width:7px;height:7px;background:var(--sp-accent,#CC1F1F);border-radius:50%;margin-right:4px;animation:sp-ai-pulse 1.2s ease-in-out infinite}
.sp-ai-dot:nth-child(2){animation-delay:.2s}
.sp-ai-dot:nth-child(3){animation-delay:.4s}
@keyframes sp-ai-pulse{0%,80%,100%{opacity:.25;transform:scale(.8)}40%{opacity:1;transform:scale(1)}}
.sp-ai-result{font-size:.87rem;color:#334155;line-height:1.7;white-space:pre-wrap}
/* Stat link */
.sp-stat-link{text-decoration:none;display:block;transition:box-shadow .15s,transform .1s}
.sp-stat-link:hover{box-shadow:0 4px 16px rgba(0,0,0,.08);transform:translateY(-1px)}
.sp-dash-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.sp-dash-funnel,.sp-dash-activity{grid-column:span 1}
.sp-funnel-row{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.sp-funnel-row:last-child{margin-bottom:0}
.sp-funnel-label{font-size:12px;font-weight:600;color:#475569;width:90px;flex-shrink:0}
.sp-funnel-track{flex:1;height:10px;background:#f1f5f9;border-radius:5px;overflow:hidden}
.sp-funnel-bar{height:100%;border-radius:5px;transition:width .4s ease}
.sp-funnel-count{font-size:12px;font-weight:700;color:#64748b;width:24px;text-align:right;flex-shrink:0}
.sp-activity-feed{padding:8px 0;max-height:320px;overflow-y:auto}
.sp-feed-item{display:flex;align-items:flex-start;gap:10px;padding:8px 20px}
.sp-feed-item:hover{background:#fafafa}
.sp-feed-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:5px}
.sp-feed-body{flex:1;font-size:12px;color:#64748b;line-height:1.5}
.sp-feed-actor{font-weight:700;color:#1e293b}
.sp-feed-verb{color:#64748b}
.sp-feed-record{font-weight:600;color:#475569}
.sp-feed-time{display:block;font-size:11px;color:#94a3b8;margin-top:1px}
@media(max-width:768px){
  .sp-dash-grid{grid-template-columns:1fr}
  .sp-dash-funnel,.sp-dash-activity{grid-column:span 1}
}
</style>
