<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$member = wts_lms_actor_id();
$slug   = isset( $_GET['course'] ) ? sanitize_title( $_GET['course'] ) : '';
$course = wts_lms_get_course( $slug );
if ( ! $course ) { echo '<div class="sp-card"><p>Course not found. <a href="' . esc_url( home_url( '/sp-app/?view=wts-training' ) ) . '">Back to training</a></p></div>'; return; }
$p = wts_lms_course_progress( $member, $course );
wts_lms_styles();
?>
<div class="sp-page-header" style="display:flex;align-items:center;gap:12px">
    <a href="<?php echo esc_url( home_url( '/sp-app/?view=wts-training' ) ); ?>" class="wts-btn ghost sm">&larr; My Courses</a>
    <h1 style="margin:0"><?php echo esc_html( $course['title'] ); ?></h1>
</div>

<div class="sp-card" style="padding:18px 22px">
    <p style="color:#64748b;margin:0 0 12px"><?php echo esc_html( $course['desc'] ); ?></p>
    <div class="wts-bar"><div class="wts-bar-fill" style="width:<?php echo (int) $p['pct']; ?>%"></div></div>
    <p class="wts-meta"><?php echo (int) $p['done']; ?> of <?php echo (int) $p['total']; ?> lessons complete</p>
</div>

<?php foreach ( $course['lessons'] as $i => $l ) : $done = wts_lms_is_complete( $member, $l['slug'] ); ?>
<a class="sp-card wts-lesson-row" style="<?php echo $done ? 'border-left:4px solid #16a34a' : ''; ?>" href="<?php echo esc_url( home_url( '/sp-app/?view=wts-lesson&lesson=' . $l['slug'] ) ); ?>">
    <div class="n"><?php echo $done ? '<span style="color:#16a34a">&#10003;</span>' : ( $i + 1 ); ?></div>
    <div>
        <strong><?php echo esc_html( $l['title'] ); ?></strong>
        <?php if ( $done ) : ?><br><span style="font-size:.8rem;color:#16a34a">Completed</span><?php endif; ?>
    </div>
    <div style="margin-left:auto;color:#cbd5e1">&rsaquo;</div>
</a>
<?php endforeach; ?>

<?php if ( $p['pct'] == 100 ) : ?>
<div class="sp-card" style="text-align:center;background:#f0fdf4;border:1px solid #86efac">
    &#127881; You have completed <strong><?php echo esc_html( $course['title'] ); ?></strong>!
    <div style="margin-top:12px"><a class="wts-btn" href="<?php echo esc_url( home_url( '/sp-app/?view=wts-certificate&course=' . $course['slug'] ) ); ?>">View Certificate</a></div>
</div>
<?php endif; ?>
