<?php
/**
 * SP app view: Lifeward Leads — every visitor who completed the Rachel funnel,
 * rendered inside the Start Performance app shell (mirrors
 * plugin/fiberco-sp/templates/fiberco-leads.php).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$sessions_t = $wpdb->prefix . 'sp_lifeward_sessions';
$leads_t    = $wpdb->prefix . 'sp_leads';
$contacts_t = $wpdb->prefix . 'sp_contacts';
$has_table  = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $sessions_t ) ) === $sessions_t );
$s = function_exists( 'sp_lifeward_lead_stats' ) ? sp_lifeward_lead_stats() : array( 'total' => 0, 'hot' => 0, 'scheduled' => 0, 'week' => 0 );

$rows = $has_table
    ? $wpdb->get_results(
        "SELECT sess.id, sess.stage, sess.slot_choice, sess.created_at,
                lead.status AS lead_status, lead.score,
                c.first_name, c.last_name, c.email AS contact_email, c.phone AS contact_phone
         FROM $sessions_t sess
         LEFT JOIN $leads_t lead ON lead.id = sess.lead_id
         LEFT JOIN $contacts_t c ON c.id = sess.contact_id
         WHERE sess.lead_id > 0
         ORDER BY sess.created_at DESC
         LIMIT 300", ARRAY_A )
    : array();

$export_url = esc_url( home_url( '/?sp_lifeward_export=1' ) );

$status_colors = array(
    'hot'            => array( '#fef3c7', '#92400e', 'Wants a call' ),
    'scheduled'      => array( '#dbeafe', '#1e40af', 'Callback scheduled' ),
    'info-requested' => array( '#e0f2fe', '#075985', 'Info sent' ),
    'lost'           => array( '#f1f5f9', '#64748b', 'Not interested' ),
);
?>
<div class="sp-view-header" style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
    <div>
        <h1 style="margin:0 0 4px;font-size:1.6rem;font-weight:800;color:var(--sp-text,#0f172a);">Lifeward Leads</h1>
        <p style="margin:0;color:var(--sp-muted,#64748b);font-size:.95rem;">Visitors captured through Rachel on the Lifeward landing page.</p>
    </div>
    <?php if ( ! empty( $rows ) ) : ?>
    <a href="<?php echo $export_url; ?>" class="sp-btn" style="display:inline-flex;align-items:center;gap:8px;background:var(--sp-accent,#0057FF);color:#fff;border:none;border-radius:10px;padding:11px 18px;font-weight:700;font-size:.9rem;text-decoration:none;white-space:nowrap;">&#11015; Export CSV</a>
    <?php endif; ?>
</div>

<div class="sp-stats sp-stats-3" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:22px;">
    <div class="sp-stat-card" style="border-top:3px solid #0057FF;background:#fff;border-radius:14px;padding:18px 20px;box-shadow:0 1px 6px rgba(15,23,42,.06);">
        <div class="sp-stat-value" style="font-size:1.9rem;font-weight:800;color:#0f172a;line-height:1;"><?php echo number_format( (int) $s['total'] ); ?></div>
        <div class="sp-stat-label" style="margin-top:6px;color:#64748b;font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Total Leads</div>
    </div>
    <div class="sp-stat-card" style="border-top:3px solid #1e40af;background:#fff;border-radius:14px;padding:18px 20px;box-shadow:0 1px 6px rgba(15,23,42,.06);">
        <div class="sp-stat-value" style="font-size:1.9rem;font-weight:800;color:#0f172a;line-height:1;"><?php echo number_format( (int) $s['scheduled'] ); ?></div>
        <div class="sp-stat-label" style="margin-top:6px;color:#64748b;font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Callbacks Scheduled</div>
    </div>
    <div class="sp-stat-card" style="border-top:3px solid #10b981;background:#fff;border-radius:14px;padding:18px 20px;box-shadow:0 1px 6px rgba(15,23,42,.06);">
        <div class="sp-stat-value" style="font-size:1.9rem;font-weight:800;color:#0f172a;line-height:1;"><?php echo number_format( (int) $s['week'] ); ?></div>
        <div class="sp-stat-label" style="margin-top:6px;color:#64748b;font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">This Week</div>
    </div>
</div>

<?php if ( empty( $rows ) ) : ?>
    <div style="background:#fff;border:1px solid #e5e8f0;border-radius:14px;padding:48px 24px;text-align:center;color:#64748b;">
        <div style="font-size:38px;margin-bottom:10px;">&#128100;</div>
        <div style="font-size:1.05rem;font-weight:700;color:#0f172a;margin-bottom:4px;">No leads yet</div>
        <div style="font-size:.9rem;">Visitors who click through Rachel's flow on the Lifeward landing page will show up here.</div>
    </div>
<?php else : ?>
    <div style="background:#fff;border:1px solid #e5e8f0;border-radius:14px;overflow:hidden;box-shadow:0 1px 6px rgba(15,23,42,.06);">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:.86rem;min-width:820px;">
                <thead>
                    <tr style="background:#f8fafc;text-align:left;">
                        <th style="padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;font-weight:700;white-space:nowrap;">Date</th>
                        <th style="padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;font-weight:700;">Name</th>
                        <th style="padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;font-weight:700;">Contact</th>
                        <th style="padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;font-weight:700;">Outcome</th>
                        <th style="padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;font-weight:700;">Callback Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $rows as $r ) :
                        $dt     = $r['created_at'] ? date_i18n( 'M j, g:i A', strtotime( $r['created_at'] ) ) : '—';
                        $name   = trim( ( $r['first_name'] ?? '' ) . ' ' . ( $r['last_name'] ?? '' ) );
                        $status = $r['lead_status'] ?: 'new';
                        $swatch = isset( $status_colors[ $status ] ) ? $status_colors[ $status ] : array( '#f1f5f9', '#64748b', $status );
                        $slot   = $r['slot_choice'] ? date_i18n( 'D, M j \a\t g:i A', strtotime( $r['slot_choice'] ) ) . ' ET' : '—';
                    ?>
                    <tr style="border-top:1px solid #f1f5f9;">
                        <td style="padding:12px 16px;color:#475569;white-space:nowrap;vertical-align:top;"><?php echo esc_html( $dt ); ?></td>
                        <td style="padding:12px 16px;color:#0f172a;vertical-align:top;"><?php echo esc_html( $name !== '' ? $name : '—' ); ?></td>
                        <td style="padding:12px 16px;color:#475569;vertical-align:top;">
                            <?php echo esc_html( $r['contact_email'] ?: '—' ); ?><br>
                            <span style="color:#94a3b8;"><?php echo esc_html( $r['contact_phone'] ?: '' ); ?></span>
                        </td>
                        <td style="padding:12px 16px;vertical-align:top;white-space:nowrap;">
                            <span style="display:inline-block;background:<?php echo esc_attr( $swatch[0] ); ?>;color:<?php echo esc_attr( $swatch[1] ); ?>;border-radius:20px;padding:3px 10px;font-size:.72rem;font-weight:800;"><?php echo esc_html( $swatch[2] ); ?></span>
                        </td>
                        <td style="padding:12px 16px;color:#475569;vertical-align:top;white-space:nowrap;"><?php echo esc_html( $slot ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <p style="margin:12px 2px 0;color:#94a3b8;font-size:.78rem;">Showing the most recent <?php echo count( $rows ); ?> leads. Use Export CSV for the full log.</p>
<?php endif; ?>
