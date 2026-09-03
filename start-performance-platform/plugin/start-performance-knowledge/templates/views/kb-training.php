<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$is_admin = sp_is_admin_member();
$member   = sp_get_current_team_member();
$my_id    = $member ? (int)$member->id : 0;
$action   = sanitize_key( $_GET['action'] ?? '' );

// ── New course form ───────────────────────────────────────────────────────────
if ( $is_admin && $action === 'new' ) { ?>
    <div class="sp-view-header">
        <h1>New Training Course</h1>
        <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-training')); ?>" class="sp-btn sp-btn-secondary">← Back</a>
    </div>
    <div class="sp-card sp-form-card">
        <form method="post" action="<?php echo esc_url(home_url('/sp-app/')); ?>">
            <?php wp_nonce_field('sp_form','sp_nonce'); ?>
            <input type="hidden" name="sp_type" value="kb_course">
            <input type="hidden" name="sp_id"   value="0">
            <div class="sp-field"><label>Course Title <span class="sp-required">*</span></label><input type="text" name="title" required></div>
            <div class="sp-field"><label>Description</label><textarea name="description" rows="3"></textarea></div>
            <div class="sp-form-actions"><button type="submit" class="sp-btn sp-btn-primary">Create Course</button></div>
        </form>
    </div>
    <?php return;
}

// ── Course list ───────────────────────────────────────────────────────────────
$courses = $wpdb->get_results(
    "SELECT c.*,
        COUNT(DISTINCT l.id) AS lesson_count,
        COUNT(DISTINCT comp.lesson_id) AS my_done
     FROM {$wpdb->prefix}sp_kb_courses c
     LEFT JOIN {$wpdb->prefix}sp_kb_lessons l ON l.course_id=c.id
     LEFT JOIN {$wpdb->prefix}sp_kb_completions comp ON comp.course_id=c.id AND comp.member_id={$my_id}
     GROUP BY c.id ORDER BY c.created_at DESC"
);
?>

<div class="sp-view-header">
    <h1>Training</h1>
    <?php if($is_admin): ?>
        <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-training&action=new')); ?>" class="sp-btn sp-btn-primary">+ New Course</a>
    <?php endif; ?>
</div>

<?php if(isset($_GET['deleted'])): ?><div class="sp-notice sp-notice-success">Course deleted.</div><?php endif; ?>

<?php if(empty($courses)): ?>
    <div class="sp-card" style="padding:20px;"><p class="sp-empty">No training courses yet.
        <?php if($is_admin): ?><a href="<?php echo esc_url(home_url('/sp-app/?view=kb-training&action=new')); ?>" class="sp-link"> Create the first one →</a><?php endif; ?>
    </p></div>
<?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
    <?php foreach($courses as $c):
        $total = (int)$c->lesson_count;
        $done  = (int)$c->my_done;
        $pct   = $total > 0 ? round($done/$total*100) : 0;
        $complete = $pct === 100;
    ?>
        <div class="sp-card" style="padding:20px;display:flex;flex-direction:column;gap:12px;">
            <div>
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                    <h3 style="font-size:.95rem;font-weight:700;margin:0;"><?php echo esc_html($c->title); ?></h3>
                    <?php if($complete): ?><span style="color:#16a34a;font-size:.75rem;font-weight:700;white-space:nowrap;">✓ Complete</span><?php endif; ?>
                </div>
                <?php if($c->description): ?><p style="font-size:.82rem;color:var(--sp-muted);margin:6px 0 0;"><?php echo esc_html($c->description); ?></p><?php endif; ?>
            </div>
            <div>
                <div style="display:flex;justify-content:space-between;font-size:.78rem;color:var(--sp-muted);margin-bottom:5px;">
                    <span><?php echo $total; ?> lesson<?php echo $total!==1?'s':''; ?></span>
                    <span><?php echo $done; ?>/<?php echo $total; ?> completed</span>
                </div>
                <div style="background:var(--sp-border,#e5e7eb);border-radius:99px;height:5px;">
                    <div style="width:<?php echo $pct; ?>%;height:5px;border-radius:99px;background:<?php echo $complete?'#16a34a':'var(--sp-primary,#2563eb)'; ?>;"></div>
                </div>
            </div>
            <div style="display:flex;gap:8px;">
                <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-training-course&id='.$c->id)); ?>" class="sp-btn sp-btn-primary sp-btn-sm" style="flex:1;text-align:center;">
                    <?php echo $done > 0 ? 'Continue' : 'Start'; ?>
                </a>
                <?php if($is_admin): ?>
                    <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-training-course&id='.$c->id.'&manage=1')); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">Manage</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
