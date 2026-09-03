<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! wts_lms_is_admin() ) { echo '<div class="sp-card"><p>You do not have access to this report.</p></div>'; return; }

$courses  = wts_lms_courses();
$learners = wts_lms_learners();
wts_lms_styles();

// Build the report + roll-up stats
$rows = array();
$sum_pct = 0; $fully_done = 0;
foreach ( $learners as $u ) {
    $td = $tt = 0; $per = array();
    foreach ( $courses as $c ) {
        $p = wts_lms_course_progress( $u->id, $c );
        $per[ $c['slug'] ] = $p;
        $td += $p['done']; $tt += $p['total'];
    }
    $overall = $tt > 0 ? round( $td / $tt * 100 ) : 0;
    $sum_pct += $overall;
    if ( $overall === 100 ) $fully_done++;
    $rows[] = array( 'u' => $u, 'per' => $per, 'overall' => $overall );
}
$avg = count( $rows ) ? round( $sum_pct / count( $rows ) ) : 0;
$export_url = esc_url( home_url( '/sp-app/?wts_lms_export=csv' ) );
?>
<style>
.wts-admin-table{width:100%;border-collapse:collapse;font-size:.9rem}
.wts-admin-table th,.wts-admin-table td{padding:10px 12px;border-bottom:1px solid #eef2f7;text-align:left;vertical-align:top}
.wts-admin-table th{font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b;background:#f8fafc}
.wts-tag{display:inline-block;border-radius:10px;padding:2px 9px;font-size:.72rem;font-weight:600}
.wts-tag.done{background:#def7ec;color:#03543f}
.wts-tag.part{background:var(--sp-accent-bg,rgba(41,168,224,.14));color:#1A4F8A}
.wts-tag.none{background:#f1f5f9;color:#94a3b8}
.wts-stat{background:var(--sp-card-bg,#fff);border:1px solid var(--sp-border,#e5e7eb);border-radius:10px;padding:14px 18px}
.wts-stat .v{font-size:1.6rem;font-weight:700;color:#1A4F8A;line-height:1}
.wts-stat .l{font-size:.75rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-top:4px}
</style>

<div class="sp-page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
    <h1 style="margin:0">Training Report</h1>
    <a href="<?php echo $export_url; ?>" class="wts-btn navy sm">&#8595; Export CSV</a>
</div>
<p style="color:#64748b;font-size:.9rem;margin:0 0 16px">Completion status for all active team members across every AMP course.</p>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:18px">
    <div class="wts-stat"><div class="v"><?php echo count( $rows ); ?></div><div class="l">Learners</div></div>
    <div class="wts-stat"><div class="v"><?php echo $avg; ?>%</div><div class="l">Avg completion</div></div>
    <div class="wts-stat"><div class="v"><?php echo $fully_done; ?></div><div class="l">Fully certified</div></div>
    <div class="wts-stat"><div class="v"><?php echo count( $courses ); ?></div><div class="l">Courses</div></div>
</div>

<div class="sp-card" style="padding:0;overflow-x:auto">
    <table class="wts-admin-table">
        <thead>
            <tr>
                <th>Learner</th>
                <?php foreach ( $courses as $c ) : ?><th><?php echo esc_html( $c['title'] ); ?></th><?php endforeach; ?>
                <th>Overall</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! $rows ) : ?>
            <tr><td colspan="<?php echo count( $courses ) + 2; ?>" style="color:#94a3b8">No active team members yet. Add learners on the <a href="<?php echo esc_url( home_url( '/sp-app/?view=team' ) ); ?>">Team</a> page.</td></tr>
            <?php endif; ?>
            <?php foreach ( $rows as $r ) : ?>
            <tr>
                <td>
                    <strong style="color:#1A4F8A"><?php echo esc_html( $r['u']->name ); ?></strong>
                    <?php if ( $r['u']->email ) : ?><br><span style="font-size:.78rem;color:#94a3b8"><?php echo esc_html( $r['u']->email ); ?></span><?php endif; ?>
                </td>
                <?php foreach ( $courses as $c ) : $p = $r['per'][ $c['slug'] ]; ?>
                <td>
                    <?php if ( $p['pct'] == 100 ) : ?><span class="wts-tag done">&#10003; Complete</span>
                    <?php elseif ( $p['done'] > 0 ) : ?><span class="wts-tag part"><?php echo (int) $p['done']; ?>/<?php echo (int) $p['total']; ?> &middot; <?php echo (int) $p['pct']; ?>%</span>
                    <?php else : ?><span class="wts-tag none">Not started</span><?php endif; ?>
                </td>
                <?php endforeach; ?>
                <td style="min-width:120px">
                    <strong><?php echo (int) $r['overall']; ?>%</strong>
                    <div class="wts-bar" style="margin-top:5px"><div class="wts-bar-fill" style="width:<?php echo (int) $r['overall']; ?>%"></div></div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
