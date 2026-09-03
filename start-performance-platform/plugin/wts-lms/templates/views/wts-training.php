<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$member  = wts_lms_actor_id();
$courses = wts_lms_courses();
wts_lms_styles();
?>
<div class="sp-page-header">
    <h1>AMP Training</h1>
</div>
<p style="color:#64748b;font-size:.92rem;margin:0 0 4px">Complete all required courses for your role to receive your AMP certification.</p>

<div class="wts-course-grid">
    <?php foreach ( $courses as $c ) : $p = wts_lms_course_progress( $member, $c ); ?>
    <a class="sp-card wts-course-card" href="<?php echo esc_url( home_url( '/sp-app/?view=wts-course&course=' . $c['slug'] ) ); ?>">
        <h3><?php echo esc_html( $c['title'] ); ?></h3>
        <p><?php echo esc_html( $c['desc'] ); ?></p>
        <div class="wts-bar"><div class="wts-bar-fill" style="width:<?php echo (int) $p['pct']; ?>%"></div></div>
        <p class="wts-meta">
            <?php echo (int) $p['done']; ?> of <?php echo (int) $p['total']; ?> lessons complete
            <?php if ( $p['pct'] == 100 ) : ?><span class="wts-badge">&#10003; Complete</span><?php endif; ?>
        </p>
    </a>
    <?php endforeach; ?>
</div>
