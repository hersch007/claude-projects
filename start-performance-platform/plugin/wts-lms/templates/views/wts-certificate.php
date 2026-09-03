<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$member = wts_lms_actor_id();
$name   = wts_lms_actor_name();
$slug   = isset( $_GET['course'] ) ? sanitize_title( $_GET['course'] ) : '';
$course = wts_lms_get_course( $slug );
if ( ! $course ) { echo '<div class="sp-card"><p>Course not found. <a href="' . esc_url( home_url( '/sp-app/?view=wts-training' ) ) . '">Back to training</a></p></div>'; return; }

$done_at = wts_lms_course_completed_at( $member, $course );
if ( ! $done_at ) {
    echo '<div class="sp-card"><p>You haven\'t completed <strong>' . esc_html( $course['title'] ) . '</strong> yet. Finish every lesson to earn your certificate.</p><p><a class="wts-btn" href="' . esc_url( home_url( '/sp-app/?view=wts-course&course=' . $slug ) ) . '">Back to course</a></p></div>';
    wts_lms_styles();
    return;
}

$date     = date_i18n( 'F j, Y', strtotime( $done_at ) );
$platform = get_option( 'sp_platform_name', 'Wireless Tower Solutions' );
wts_lms_styles();
?>
<style>
.wts-cert-wrap{max-width:820px;margin:0 auto}
.wts-cert{background:#fff;border:3px solid var(--sp-accent,#29A8E0);border-radius:8px;padding:12px}
.wts-cert-inner{border:1px solid #cfe3f4;border-radius:5px;padding:52px 44px;text-align:center}
.wts-cert-kicker{font-size:.82rem;letter-spacing:.3em;text-transform:uppercase;color:var(--sp-accent,#29A8E0);font-weight:700}
.wts-cert-org{color:#1A4F8A;font-weight:600;margin-top:8px;font-size:.95rem;letter-spacing:.02em}
.wts-cert-rule{width:70px;height:3px;background:var(--sp-accent,#29A8E0);margin:18px auto 0;border-radius:2px}
.wts-cert-lead{color:#64748b;margin:30px 0 10px;font-size:.98rem}
.wts-cert-name{font-family:Georgia,'Times New Roman',serif;font-size:2.4rem;color:#1A4F8A;font-weight:700;line-height:1.1}
.wts-cert-name span{border-bottom:2px solid #e2e8f0;padding:0 26px 10px}
.wts-cert-course{font-family:Georgia,'Times New Roman',serif;font-size:1.45rem;color:#0f172a;font-weight:600;margin-top:4px}
.wts-cert-date{color:#64748b;margin-top:30px;font-size:.92rem}
.wts-cert-sig{margin-top:40px;display:inline-block;border-top:1px solid #94a3b8;padding-top:8px;color:#475569;font-size:.86rem;min-width:240px}
.wts-cert-actions{text-align:right;margin-bottom:12px}
@media print{
    body *{visibility:hidden!important}
    .wts-cert-wrap,.wts-cert-wrap *{visibility:visible!important}
    .wts-cert-wrap{position:absolute;left:0;top:0;width:100%;max-width:none}
    .wts-cert{border-width:3px}
    .wts-cert-actions{display:none!important}
}
</style>

<div class="wts-cert-wrap">
    <div class="wts-cert-actions">
        <a class="wts-btn ghost sm" href="<?php echo esc_url( home_url( '/sp-app/?view=wts-course&course=' . $slug ) ); ?>">&larr; Back to course</a>
        <button type="button" class="wts-btn navy sm" onclick="window.print()">Print / Save PDF</button>
    </div>

    <div class="wts-cert">
        <div class="wts-cert-inner">
            <div class="wts-cert-kicker">Certificate of Completion</div>
            <div class="wts-cert-org"><?php echo esc_html( $platform ); ?> &mdash; AMP Training</div>
            <div class="wts-cert-rule"></div>

            <p class="wts-cert-lead">This certifies that</p>
            <div class="wts-cert-name"><span><?php echo esc_html( $name ); ?></span></div>

            <p class="wts-cert-lead">has successfully completed</p>
            <div class="wts-cert-course"><?php echo esc_html( $course['title'] ); ?></div>

            <p class="wts-cert-date">Completed <?php echo esc_html( $date ); ?></p>

            <div class="wts-cert-sig">Wireless Tower Solutions</div>
        </div>
    </div>
</div>
