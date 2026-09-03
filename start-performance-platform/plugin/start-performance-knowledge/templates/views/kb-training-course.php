<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$is_admin  = sp_is_admin_member();
$member    = sp_get_current_team_member();
$my_id     = $member ? (int)$member->id : 0;
$course_id = (int)( $_GET['id'] ?? 0 );
$manage    = $is_admin && isset($_GET['manage']);
$lesson_id = (int)( $_GET['lesson'] ?? 0 );
$action    = sanitize_key( $_GET['action'] ?? '' );

if(!$course_id){ wp_redirect(home_url('/sp-app/?view=kb-training')); exit; }

$course = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sp_kb_courses WHERE id=%d",$course_id));
if(!$course){ echo '<p class="sp-empty">Course not found.</p>'; return; }

// ── Edit course ───────────────────────────────────────────────────────────────
if($is_admin && $action==='edit-course'){?>
    <div class="sp-view-header">
        <h1>Edit Course</h1>
        <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-training-course&id='.$course_id.'&manage=1')); ?>" class="sp-btn sp-btn-secondary">&larr; Back</a>
    </div>
    <div class="sp-card sp-form-card">
        <form method="post" action="<?php echo esc_url(home_url('/sp-app/')); ?>">
            <?php wp_nonce_field('sp_form','sp_nonce'); ?>
            <input type="hidden" name="sp_type" value="kb_course">
            <input type="hidden" name="sp_id"   value="<?php echo $course_id; ?>">
            <div class="sp-field"><label>Title</label><input type="text" name="title" value="<?php echo esc_attr($course->title); ?>" required></div>
            <div class="sp-field"><label>Description</label><textarea name="description" rows="3"><?php echo esc_textarea($course->description); ?></textarea></div>
            <div class="sp-form-actions">
                <a href="<?php echo esc_url(home_url('/sp-app/?sp_type=kb_course&sp_action=delete&sp_id='.$course_id.'&sp_nonce='.wp_create_nonce('sp_form'))); ?>"
                   class="sp-btn sp-btn-danger" data-sp-confirm="Delete this entire course and all lessons?">Delete Course</a>
                <button type="submit" class="sp-btn sp-btn-primary">Save</button>
            </div>
        </form>
    </div>
    <?php return;
}

// ── Add / Edit lesson ─────────────────────────────────────────────────────────
if($is_admin && in_array($action,array('new-lesson','edit-lesson'),true)){
    $lesson = null;
    $quiz   = array();
    $edit_lid = (int)($_GET['lid']??0);
    if($action==='edit-lesson' && $edit_lid){
        $lesson = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sp_kb_lessons WHERE id=%d",$edit_lid));
        $quiz   = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sp_kb_quiz WHERE lesson_id=%d ORDER BY sort_order ASC",$edit_lid));
    }
    $lesson_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sp_kb_lessons WHERE course_id=%d",$course_id));
    ?>
    <div class="sp-view-header">
        <h1><?php echo $lesson?'Edit Lesson':'New Lesson'; ?></h1>
        <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-training-course&id='.$course_id.'&manage=1')); ?>" class="sp-btn sp-btn-secondary">&larr; Back</a>
    </div>
    <div class="sp-card sp-form-card">
        <form method="post" action="<?php echo esc_url(home_url('/sp-app/')); ?>">
            <?php wp_nonce_field('sp_form','sp_nonce'); ?>
            <input type="hidden" name="sp_type"   value="kb_lesson">
            <input type="hidden" name="sp_id"     value="<?php echo $lesson?(int)$lesson->id:0; ?>">
            <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
            <div class="sp-form-row">
                <div class="sp-field" style="flex:3;"><label>Lesson Title <span class="sp-required">*</span></label><input type="text" name="title" value="<?php echo esc_attr($lesson->title??''); ?>" required></div>
                <div class="sp-field"><label>Order</label><input type="number" name="sort_order" value="<?php echo $lesson?(int)$lesson->sort_order:$lesson_count+1; ?>" min="1"></div>
            </div>
            <div class="sp-field">
                <label>Content <span class="sp-required">*</span></label>
                <div class="sp-editor-toolbar">
                    <button type="button" onclick="spFmtL('bold')" title="Bold"><b>B</b></button>
                    <button type="button" onclick="spFmtL('italic')" title="Italic"><i>I</i></button>
                    <button type="button" onclick="spFmtL('underline')" title="Underline"><u>U</u></button>
                    <span class="sp-toolbar-sep"></span>
                    <button type="button" onclick="spBlockL('h2')">H2</button>
                    <button type="button" onclick="spBlockL('h3')">H3</button>
                    <button type="button" onclick="spBlockL('p')">P</button>
                    <span class="sp-toolbar-sep"></span>
                    <button type="button" onclick="spFmtL('insertUnorderedList')">&#8226; List</button>
                    <button type="button" onclick="spFmtL('insertOrderedList')">1. List</button>
                    <span class="sp-toolbar-sep"></span>
                    <button type="button" onclick="spLinkL()">Link</button>
                    <button type="button" onclick="spFmtL('removeFormat')">Clear</button>
                </div>
                <div id="lesson-editor" contenteditable="true" class="sp-rich-editor"><?php echo $lesson ? wp_kses_post($lesson->content) : ''; ?></div>
                <textarea name="content" id="lesson-content-hidden" style="display:none;" required><?php echo esc_textarea($lesson->content??''); ?></textarea>
            </div>

            <h3 style="margin:24px 0 8px;font-size:.9rem;font-weight:700;">Quiz Questions <span style="font-weight:400;color:var(--sp-muted);font-size:.82rem;">(optional — pass score is 70%)</span></h3>
            <p class="sp-hint" style="margin-bottom:12px;">Separate answer options with a pipe <code>|</code> character. Example: <em>Yes|No|Maybe</em></p>
            <div id="sp-quiz-rows">
            <?php
            $default_q = empty($quiz) ? array() : $quiz;
            foreach($default_q as $i=>$q):
                $ans_arr = json_decode($q->answers,true) ?: array();
            ?>
            <div class="sp-quiz-row" style="background:var(--sp-bg,#f9fafb);border-radius:8px;padding:14px;margin-bottom:10px;">
                <div class="sp-field" style="margin-bottom:8px;"><label style="font-size:.8rem;">Question</label><input type="text" name="q_question[]" value="<?php echo esc_attr($q->question); ?>" required></div>
                <div class="sp-form-row" style="margin-bottom:0;">
                    <div class="sp-field"><label style="font-size:.8rem;">Answers (pipe-separated)</label><input type="text" name="q_answers[]" value="<?php echo esc_attr(implode('|',$ans_arr)); ?>" placeholder="Option A|Option B|Option C"></div>
                    <div class="sp-field" style="flex:.5;"><label style="font-size:.8rem;">Correct (0-based)</label><input type="number" name="q_correct[]" value="<?php echo (int)$q->correct_index; ?>" min="0" max="9"></div>
                    <div style="padding-top:22px;"><button type="button" class="sp-btn sp-btn-ghost sp-btn-sm sp-quiz-remove">✕</button></div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <button type="button" id="sp-quiz-add" class="sp-btn sp-btn-secondary sp-btn-sm" style="margin-bottom:20px;">+ Add Question</button>

            <div class="sp-form-actions">
                <?php if($lesson): ?>
                    <a href="<?php echo esc_url(home_url('/sp-app/?sp_type=kb_lesson&sp_action=delete&sp_id='.(int)$lesson->id.'&sp_nonce='.wp_create_nonce('sp_form'))); ?>"
                       class="sp-btn sp-btn-danger" data-sp-confirm="Delete this lesson?">Delete Lesson</a>
                <?php endif; ?>
                <button type="submit" class="sp-btn sp-btn-primary">Save Lesson</button>
            </div>
        </form>
    </div>
    <script>
    (function(){
        var c=document.getElementById('sp-quiz-rows');
        document.getElementById('sp-quiz-add').addEventListener('click',function(){
            var d=document.createElement('div');
            d.className='sp-quiz-row';
            d.style.cssText='background:var(--sp-bg,#f9fafb);border-radius:8px;padding:14px;margin-bottom:10px;';
            d.innerHTML='<div class="sp-field" style="margin-bottom:8px;"><label style="font-size:.8rem;">Question</label><input type="text" name="q_question[]" required></div>'
                +'<div class="sp-form-row" style="margin-bottom:0;"><div class="sp-field"><label style="font-size:.8rem;">Answers (pipe-separated)</label><input type="text" name="q_answers[]" placeholder="Option A|Option B|Option C"></div>'
                +'<div class="sp-field" style="flex:.5;"><label style="font-size:.8rem;">Correct (0-based)</label><input type="number" name="q_correct[]" value="0" min="0" max="9"></div>'
                +'<div style="padding-top:22px;"><button type="button" class="sp-btn sp-btn-ghost sp-btn-sm sp-quiz-remove">✕</button></div></div>';
            c.appendChild(d);
        });
        c.addEventListener('click',function(e){ if(e.target.classList.contains('sp-quiz-remove')) e.target.closest('.sp-quiz-row').remove(); });
    })();
    </script>
    <script>
    (function(){
        var ed = document.getElementById('lesson-editor');
        var hidden = document.getElementById('lesson-content-hidden');
        if(!ed) return;
        function spFmtL(cmd){ document.execCommand(cmd,false,null); ed.focus(); }
        function spBlockL(tag){ ed.focus(); document.execCommand('formatBlock',false,tag); }
        function spLinkL(){ var u=prompt('URL:'); if(u){ document.execCommand('createLink',false,u); } ed.focus(); }
        window.spFmtL=spFmtL; window.spBlockL=spBlockL; window.spLinkL=spLinkL;
        ed.closest('form').addEventListener('submit',function(){ hidden.value=ed.innerHTML; hidden.removeAttribute('required'); });
    })();
    </script>
    <style>
    .sp-editor-toolbar{display:flex;flex-wrap:wrap;gap:2px;padding:6px 8px;background:var(--sp-bg,#f9fafb);border:1px solid var(--sp-border,#e5e7eb);border-bottom:none;border-radius:8px 8px 0 0;}
    .sp-editor-toolbar button{background:none;border:1px solid transparent;border-radius:4px;padding:3px 9px;font-size:.82rem;cursor:pointer;color:var(--sp-text,#1e293b);}
    .sp-editor-toolbar button:hover{background:#fff;border-color:var(--sp-border,#e5e7eb);}
    .sp-toolbar-sep{width:1px;background:var(--sp-border,#e5e7eb);margin:2px 4px;}
    .sp-rich-editor{min-height:260px;padding:16px;border:1px solid var(--sp-border,#e5e7eb);border-radius:0 0 8px 8px;background:#fff;font-size:.9rem;line-height:1.7;outline:none;overflow-y:auto;}
    .sp-rich-editor:focus{border-color:var(--sp-primary,#2563eb);}
    .sp-rich-editor h2{font-size:1.1rem;font-weight:700;margin:.7em 0 .3em;}
    .sp-rich-editor h3{font-size:.95rem;font-weight:700;margin:.7em 0 .3em;}
    .sp-rich-editor ul,.sp-rich-editor ol{padding-left:1.4em;margin:.5em 0;}
    </style>
    <?php return;
}

// ── Course detail view ────────────────────────────────────────────────────────
$lessons = $wpdb->get_results($wpdb->prepare(
    "SELECT l.*,
        comp.id AS my_completion
     FROM {$wpdb->prefix}sp_kb_lessons l
     LEFT JOIN {$wpdb->prefix}sp_kb_completions comp ON comp.lesson_id=l.id AND comp.member_id={$my_id}
     WHERE l.course_id=%d ORDER BY l.sort_order ASC, l.created_at ASC",
    $course_id
));

$total_lessons = count($lessons);
$done_count    = count(array_filter($lessons,fn($l)=>$l->my_completion));
$pct           = $total_lessons>0 ? round($done_count/$total_lessons*100) : 0;

// Which lesson to show
$active_lesson = null;
if($lesson_id){
    foreach($lessons as $l){ if($l->id===$lesson_id){ $active_lesson=$l; break; } }
}
if(!$active_lesson && !$manage){
    // Show first incomplete lesson
    foreach($lessons as $l){ if(!$l->my_completion){ $active_lesson=$l; break; } }
    if(!$active_lesson && !empty($lessons)) $active_lesson=$lessons[0];
}

$quiz_result = isset($_GET['quiz_result']) ? (int)$_GET['quiz_result'] : null;
$quiz_passed = isset($_GET['quiz_passed']) ? (int)$_GET['quiz_passed'] : null;
?>

<div class="sp-view-header">
    <h1><?php echo esc_html($course->title); ?></h1>
    <div style="display:flex;gap:8px;">
        <?php if($is_admin): ?>
            <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-training-course&id='.$course_id.($manage?'':' &manage=1'))); ?>"
               class="sp-btn sp-btn-secondary"><?php echo $manage?'&larr; Back to Course':'Manage'; ?></a>
        <?php endif; ?>
        <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-training')); ?>" class="sp-btn sp-btn-ghost">&larr; Training</a>
    </div>
</div>

<?php if(isset($_GET['saved']) || isset($_GET['lesson_saved'])): ?><div class="sp-notice sp-notice-success">Saved.</div><?php endif; ?>
<?php if(isset($_GET['completed'])): ?><div class="sp-notice sp-notice-success">Lesson marked complete!</div><?php endif; ?>
<?php if($quiz_result!==null): ?>
    <div class="sp-notice <?php echo $quiz_passed?'sp-notice-success':'sp-notice-warning'; ?>">
        <?php echo $quiz_passed?"Quiz passed — <?php echo $quiz_result; ?>% correct. Lesson marked complete!":"Quiz score: <?php echo $quiz_result; ?>%. You need 70% to pass — try again."; ?>
    </div>
<?php endif; ?>

<?php if($is_admin && $manage): ?>
    <?php // ── Manage mode ── ?>
    <div style="display:flex;gap:8px;margin-bottom:18px;align-items:center;">
        <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-training-course&id='.$course_id.'&action=edit-course')); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">Edit Course Details</a>
        <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-training-course&id='.$course_id.'&action=new-lesson')); ?>" class="sp-btn sp-btn-primary sp-btn-sm">+ Add Lesson</a>
    </div>

    <?php // Team progress table
    $team_progress = $wpdb->get_results($wpdb->prepare(
        "SELECT t.id, t.name,
            COUNT(DISTINCT comp.lesson_id) AS done
         FROM {$wpdb->prefix}sp_team t
         LEFT JOIN {$wpdb->prefix}sp_kb_completions comp ON comp.member_id=t.id AND comp.course_id=%d
         WHERE t.status='active'
         GROUP BY t.id ORDER BY t.name ASC",
        $course_id
    ));
    ?>
    <div class="sp-card sp-table-card" style="margin-bottom:18px;">
        <div class="sp-card-header"><h2>Team Progress</h2></div>
        <?php if(empty($team_progress)): ?>
            <p class="sp-empty">No team members yet.</p>
        <?php else: ?>
        <table class="sp-table">
            <thead><tr><th>Member</th><th>Progress</th></tr></thead>
            <tbody>
            <?php foreach($team_progress as $tm):
                $tp=$total_lessons>0?round((int)$tm->done/$total_lessons*100):0;
            ?>
                <tr>
                    <td><?php echo esc_html($tm->name); ?></td>
                    <td style="min-width:160px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;background:var(--sp-border,#e5e7eb);border-radius:99px;height:5px;">
                                <div style="width:<?php echo $tp; ?>%;height:5px;border-radius:99px;background:<?php echo $tp===100?'#16a34a':'var(--sp-primary,#2563eb)'; ?>;"></div>
                            </div>
                            <span style="font-size:.78rem;color:var(--sp-muted);"><?php echo $tm->done; ?>/<?php echo $total_lessons; ?></span>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="sp-card sp-table-card">
        <div class="sp-card-header"><h2>Lessons</h2></div>
        <?php if(empty($lessons)): ?>
            <p class="sp-empty">No lessons yet. <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-training-course&id='.$course_id.'&action=new-lesson')); ?>" class="sp-link">Add one →</a></p>
        <?php else: ?>
        <table class="sp-table">
            <thead><tr><th>#</th><th>Title</th><th>Quiz</th><th></th></tr></thead>
            <tbody>
            <?php foreach($lessons as $li=>$l):
                $qc=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sp_kb_quiz WHERE lesson_id=%d",$l->id));
            ?>
                <tr>
                    <td class="sp-muted"><?php echo $li+1; ?></td>
                    <td><?php echo esc_html($l->title); ?></td>
                    <td><?php echo $qc?'<span class="sp-badge">'.$qc.' question'.($qc!==1?'s':'').'</span>':'<span style="color:var(--sp-muted);font-size:.8rem;">None</span>'; ?></td>
                    <td style="text-align:right;">
                        <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-training-course&id='.$course_id.'&action=edit-lesson&lid='.$l->id.'&manage=1')); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

<?php else: ?>
    <?php // ── Learner mode ── ?>
    <div style="margin-bottom:12px;">
        <div style="display:flex;justify-content:space-between;font-size:.8rem;color:var(--sp-muted);margin-bottom:5px;">
            <span>Your progress</span><span><?php echo $done_count; ?>/<?php echo $total_lessons; ?> lessons</span>
        </div>
        <div style="background:var(--sp-border,#e5e7eb);border-radius:99px;height:6px;">
            <div style="width:<?php echo $pct; ?>%;height:6px;border-radius:99px;background:<?php echo $pct===100?'#16a34a':'var(--sp-primary,#2563eb)'; ?>;transition:width .3s;"></div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:220px 1fr;gap:20px;align-items:start;">

        <div class="sp-card" style="padding:12px;">
            <?php foreach($lessons as $li=>$l): $done=$l->my_completion; ?>
            <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-training-course&id='.$course_id.'&lesson='.$l->id)); ?>"
               style="display:flex;align-items:center;gap:10px;padding:8px;border-radius:7px;text-decoration:none;margin-bottom:2px;<?php echo ($active_lesson&&$active_lesson->id===$l->id)?'background:var(--sp-primary,#2563eb);color:#fff;':'color:var(--sp-text);'; ?>">
                <div style="width:20px;height:20px;border-radius:50%;border:2px solid <?php echo $done?'#16a34a':'currentColor'; ?>;background:<?php echo $done?'#16a34a':'transparent'; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <?php if($done): ?><svg width="10" height="10" viewBox="0 0 24 24" fill="none"><path stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><?php else: ?><span style="font-size:.7rem;"><?php echo $li+1; ?></span><?php endif; ?>
                </div>
                <span style="font-size:.83rem;line-height:1.3;"><?php echo esc_html($l->title); ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if($active_lesson): ?>
        <div>
            <div class="sp-card" style="padding:28px 32px;margin-bottom:16px;">
                <h2 style="margin:0 0 16px;font-size:1.1rem;"><?php echo esc_html($active_lesson->title); ?></h2>
                <div class="sp-kb-content" style="line-height:1.7;font-size:.92rem;">
                    <?php echo wp_kses_post($active_lesson->content); ?>
                </div>
            </div>

            <?php
            // Quiz or mark-complete
            $quiz_qs = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sp_kb_quiz WHERE lesson_id=%d ORDER BY sort_order ASC",
                $active_lesson->id
            ));
            $already_done = (bool)$active_lesson->my_completion;
            ?>

            <?php if($already_done): ?>
                <div style="display:flex;align-items:center;gap:10px;padding:14px 20px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;color:#16a34a;font-weight:600;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Lesson complete
                </div>

            <?php elseif(!empty($quiz_qs)): ?>
                <div class="sp-card" style="padding:22px;">
                    <h3 style="font-size:.9rem;font-weight:700;margin:0 0 16px;">Quiz — Answer all questions to complete this lesson</h3>
                    <form method="post" action="<?php echo esc_url(home_url('/sp-app/')); ?>">
                        <?php wp_nonce_field('sp_form','sp_nonce'); ?>
                        <input type="hidden" name="sp_type"   value="kb_quiz">
                        <input type="hidden" name="sp_id"     value="0">
                        <input type="hidden" name="lesson_id" value="<?php echo (int)$active_lesson->id; ?>">
                        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                        <?php foreach($quiz_qs as $qi=>$q):
                            $answers=json_decode($q->answers,true)?:array();
                        ?>
                        <div style="margin-bottom:18px;">
                            <p style="font-weight:600;font-size:.88rem;margin:0 0 8px;"><?php echo ($qi+1).'. '.esc_html($q->question); ?></p>
                            <?php foreach($answers as $ai=>$ans): ?>
                            <label style="display:flex;align-items:center;gap:8px;padding:6px 0;font-size:.85rem;cursor:pointer;">
                                <input type="radio" name="q_<?php echo $q->id; ?>" value="<?php echo $ai; ?>" required>
                                <?php echo esc_html($ans); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                        <button type="submit" class="sp-btn sp-btn-primary">Submit Quiz</button>
                    </form>
                </div>

            <?php else: ?>
                <form method="post" action="<?php echo esc_url(home_url('/sp-app/')); ?>">
                    <?php wp_nonce_field('sp_form','sp_nonce'); ?>
                    <input type="hidden" name="sp_type"   value="kb_complete">
                    <input type="hidden" name="sp_id"     value="0">
                    <input type="hidden" name="lesson_id" value="<?php echo (int)$active_lesson->id; ?>">
                    <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                    <button type="submit" class="sp-btn sp-btn-primary">✓ Mark Lesson Complete</button>
                </form>
            <?php endif; ?>
        </div>
        <?php elseif(empty($lessons)): ?>
            <div class="sp-card" style="padding:20px;"><p class="sp-empty">No lessons added yet.</p></div>
        <?php endif; ?>

    </div>
<?php endif; ?>

<style>
.sp-kb-content h1,.sp-kb-content h2,.sp-kb-content h3{font-weight:700;margin:1.2em 0 .4em;}
.sp-kb-content h2{font-size:1.1rem;}.sp-kb-content h3{font-size:.95rem;}
.sp-kb-content ul,.sp-kb-content ol{padding-left:1.4em;margin:.6em 0;}
.sp-kb-content li{margin:.3em 0;}.sp-kb-content p{margin:.6em 0;}
.sp-kb-content a{color:var(--sp-primary,#2563eb);}
.sp-kb-content code{background:var(--sp-bg,#f9fafb);padding:2px 5px;border-radius:4px;font-size:.88em;font-family:monospace;}
.sp-kb-content blockquote{border-left:3px solid var(--sp-primary,#2563eb);margin:0;padding:8px 16px;color:var(--sp-muted);}
</style>
