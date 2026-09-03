<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$member = wts_lms_actor_id();
$slug   = isset( $_GET['lesson'] ) ? sanitize_title( $_GET['lesson'] ) : '';
$lesson = wts_lms_get_lesson( $slug );
if ( ! $lesson ) { echo '<div class="sp-card"><p>Lesson not found. <a href="' . esc_url( home_url( '/sp-app/?view=wts-training' ) ) . '">Back to training</a></p></div>'; return; }

$is_complete = wts_lms_is_complete( $member, $slug );
$quiz_result = wts_lms_get_quiz_result( $member, $slug );
$content     = wts_lms_render_markdown( $lesson['file'] );
$quiz        = wts_lms_quiz_data( $lesson['file'] );

$ans_key      = 'wts_lms_ans_' . $member . '_' . md5( $slug );
$last_answers = get_transient( $ans_key );
if ( ! is_array( $last_answers ) ) $last_answers = array();
if ( $last_answers ) delete_transient( $ans_key );

$post_url  = esc_url( home_url( '/sp-app/' ) );
$course_url = esc_url( home_url( '/sp-app/?view=wts-course&course=' . $lesson['course_slug'] ) );

// Reusable "mark incomplete" form
if ( ! function_exists( 'wts_lms_incomplete_btn' ) ) :
function wts_lms_incomplete_btn( $post_url, $slug ) {
    ob_start(); ?>
    <form method="post" action="<?php echo $post_url; ?>" style="margin:0" onsubmit="return confirm('Remove completion and clear the quiz for this lesson?')">
        <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
        <input type="hidden" name="sp_type" value="wts_lms"><input type="hidden" name="sp_id" value="0">
        <input type="hidden" name="wts_action" value="mark_incomplete"><input type="hidden" name="lesson" value="<?php echo esc_attr( $slug ); ?>">
        <button type="submit" class="wts-btn ghost sm">Mark Incomplete</button>
    </form>
    <?php return ob_get_clean();
}
endif;
wts_lms_styles();
?>
<div class="sp-page-header" style="display:flex;align-items:center;gap:12px">
    <a href="<?php echo $course_url; ?>" class="wts-btn ghost sm">&larr; <?php echo esc_html( $lesson['course_title'] ); ?></a>
    <h1 style="margin:0;font-size:1.2rem"><?php echo esc_html( $lesson['title'] ); ?></h1>
</div>

<div class="wts-lesson-layout">
    <aside class="sp-card wts-sidenav">
        <h4><?php echo esc_html( $lesson['course_title'] ); ?></h4>
        <?php foreach ( $lesson['course_lessons'] as $l ) :
            $done = wts_lms_is_complete( $member, $l['slug'] );
            $cls  = ( $l['slug'] === $slug ? 'active ' : '' ) . ( $done ? 'done' : '' );
        ?>
        <a class="<?php echo trim( $cls ); ?>" href="<?php echo esc_url( home_url( '/sp-app/?view=wts-lesson&lesson=' . $l['slug'] ) ); ?>"><?php echo esc_html( $l['title'] ); ?></a>
        <?php endforeach; ?>
    </aside>

    <main>
        <?php if ( isset( $_GET['done'] ) ) : ?>
        <div class="wts-result pass">&#10003; Lesson marked complete.</div>
        <?php endif; ?>

        <?php if ( $is_complete ) : ?>
        <div class="wts-result pass" style="display:flex;align-items:center;justify-content:space-between">
            <span>&#10003; You have completed this lesson.</span>
            <?php echo wts_lms_incomplete_btn( $post_url, $slug ); ?>
        </div>
        <?php endif; ?>

        <div class="sp-card wts-content"><?php echo $content; ?></div>

        <?php if ( $quiz ) : ?>
        <div class="sp-card wts-quiz" id="quiz" style="margin-top:16px">
            <h2 style="color:#1A4F8A;margin-top:0">Checkpoint Quiz</h2>

            <?php if ( $quiz_result ) : ?>
                <div class="wts-result <?php echo $quiz_result['passed'] ? 'pass' : 'fail'; ?>">
                    <?php if ( $quiz_result['passed'] ) : ?>
                        &#10003; Passed &mdash; <?php echo (int) $quiz_result['score']; ?>/<?php echo (int) $quiz_result['total']; ?> correct. This lesson is complete.
                    <?php else : ?>
                        &#10007; Not yet passing &mdash; <?php echo (int) $quiz_result['score']; ?>/<?php echo (int) $quiz_result['total']; ?> correct. You need 80% or higher.
                    <?php endif; ?>
                </div>

                <?php foreach ( $quiz['questions'] as $qi => $q ) :
                    $submitted = isset( $last_answers[ $qi ] ) ? $last_answers[ $qi ] : null;
                    $correct   = $q['answer'];
                ?>
                <div class="wts-q">
                    <p class="qq"><?php echo ( $qi + 1 ) . '. ' . esc_html( $q['question'] ); ?></p>
                    <?php foreach ( $q['options'] as $key => $label ) :
                        $cls = '';
                        if ( $key === $correct ) $cls = 'correct';
                        elseif ( $submitted === $key ) $cls = 'wrong';
                    ?>
                    <div class="wts-opt <?php echo $cls; ?>">
                        <span style="font-weight:700;min-width:18px"><?php echo esc_html( $key ); ?>)</span>
                        <span><?php echo esc_html( $label ); ?></span>
                        <?php if ( $key === $correct ) : ?><span style="margin-left:auto;color:#16a34a;font-weight:700">&#10003;</span>
                        <?php elseif ( $submitted === $key ) : ?><span style="margin-left:auto;color:#dc2626;font-weight:700">&#10007;</span><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if ( ! empty( $q['explanation'] ) ) : ?>
                    <div class="wts-expl"><strong>Why <?php echo esc_html( $correct ); ?> is correct:</strong> <?php echo esc_html( $q['explanation'] ); ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <?php if ( ! $quiz_result['passed'] ) : ?>
                <form method="post" action="<?php echo $post_url; ?>" style="margin-top:8px">
                    <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                    <input type="hidden" name="sp_type" value="wts_lms"><input type="hidden" name="sp_id" value="0">
                    <input type="hidden" name="wts_action" value="mark_incomplete"><input type="hidden" name="lesson" value="<?php echo esc_attr( $slug ); ?>">
                    <button type="submit" class="wts-btn navy">&#8634; Try Again</button>
                </form>
                <?php endif; ?>

            <?php else : ?>
                <form method="post" action="<?php echo $post_url; ?>">
                    <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                    <input type="hidden" name="sp_type" value="wts_lms"><input type="hidden" name="sp_id" value="0">
                    <input type="hidden" name="wts_action" value="quiz_submit"><input type="hidden" name="lesson" value="<?php echo esc_attr( $slug ); ?>">
                    <?php foreach ( $quiz['questions'] as $qi => $q ) : ?>
                    <div class="wts-q">
                        <p class="qq"><?php echo ( $qi + 1 ) . '. ' . esc_html( $q['question'] ); ?></p>
                        <?php foreach ( $q['options'] as $key => $label ) : ?>
                        <label class="wts-opt">
                            <input type="radio" name="q<?php echo (int) $qi; ?>" value="<?php echo esc_attr( $key ); ?>" required style="margin-top:3px">
                            <span><strong><?php echo esc_html( $key ); ?>)</strong> <?php echo esc_html( $label ); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                    <button type="submit" class="wts-btn">Submit Quiz</button>
                </form>
            <?php endif; ?>
        </div>

        <?php elseif ( ! $is_complete ) : ?>
        <form method="post" action="<?php echo $post_url; ?>" style="margin-top:16px">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="wts_lms"><input type="hidden" name="sp_id" value="0">
            <input type="hidden" name="wts_action" value="mark_complete"><input type="hidden" name="lesson" value="<?php echo esc_attr( $slug ); ?>">
            <button type="submit" class="wts-btn">&#10003; Mark Lesson Complete</button>
        </form>
        <?php endif; ?>

        <div style="display:flex;justify-content:space-between;gap:12px;margin-top:18px">
            <?php if ( $lesson['prev'] ) : ?>
            <a class="wts-btn navy" href="<?php echo esc_url( home_url( '/sp-app/?view=wts-lesson&lesson=' . $lesson['prev']['slug'] ) ); ?>">&larr; Previous</a>
            <?php else : ?><span></span><?php endif; ?>
            <?php if ( $lesson['next'] ) : ?>
            <a class="wts-btn" href="<?php echo esc_url( home_url( '/sp-app/?view=wts-lesson&lesson=' . $lesson['next']['slug'] ) ); ?>">Next &rarr;</a>
            <?php else : ?>
            <a class="wts-btn ghost" href="<?php echo $course_url; ?>">&larr; Back to Course</a>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php
// Auto-scroll to quiz results after submit
if ( $quiz_result ) echo '<script>var q=document.getElementById("quiz");if(q&&location.hash==="#quiz")q.scrollIntoView();</script>';
