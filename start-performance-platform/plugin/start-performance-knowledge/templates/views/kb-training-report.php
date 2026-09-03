<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! sp_is_admin_member() ) { wp_redirect( home_url('/sp-app/?view=kb-training') ); exit; }
global $wpdb;

$filter_course = (int)( $_GET['course'] ?? 0 );

$courses = $wpdb->get_results(
    "SELECT c.*, COUNT(DISTINCT l.id) AS lesson_count
     FROM {$wpdb->prefix}sp_kb_courses c
     LEFT JOIN {$wpdb->prefix}sp_kb_lessons l ON l.course_id=c.id
     GROUP BY c.id ORDER BY c.title ASC"
);

$team = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}sp_team WHERE status='active' ORDER BY name ASC"
);
?>

<div class="sp-view-header">
    <h1>Training Report</h1>
    <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-training')); ?>" class="sp-btn sp-btn-secondary">&larr; Training</a>
</div>

<?php if(empty($courses)): ?>
    <div class="sp-card" style="padding:20px;"><p class="sp-empty">No courses created yet.</p></div>
    <?php return;
endif; ?>

<?php if(count($courses) > 1): ?>
<div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;flex-wrap:wrap;">
    <span style="font-size:.85rem;font-weight:600;">Filter by course:</span>
    <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-training-report')); ?>"
       class="sp-btn sp-btn-sm <?php echo !$filter_course ? 'sp-btn-primary' : 'sp-btn-ghost'; ?>">All Courses</a>
    <?php foreach($courses as $c): ?>
        <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-training-report&course='.$c->id)); ?>"
           class="sp-btn sp-btn-sm <?php echo $filter_course===$c->id ? 'sp-btn-primary' : 'sp-btn-ghost'; ?>">
            <?php echo esc_html($c->title); ?>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$show_courses = $filter_course
    ? array_filter($courses, fn($c) => $c->id === $filter_course)
    : $courses;

foreach($show_courses as $course):
    $total = (int)$course->lesson_count;
    if($total === 0) continue;

    $lessons = $wpdb->get_results($wpdb->prepare(
        "SELECT id, title FROM {$wpdb->prefix}sp_kb_lessons WHERE course_id=%d ORDER BY sort_order ASC, created_at ASC",
        $course->id
    ));

    // All completions for this course
    $completions = $wpdb->get_results($wpdb->prepare(
        "SELECT member_id, lesson_id, completed_at, passed FROM {$wpdb->prefix}sp_kb_completions WHERE course_id=%d",
        $course->id
    ));
    $comp_map = array();
    foreach($completions as $comp){
        $comp_map[$comp->member_id][$comp->lesson_id] = $comp;
    }
?>
<div class="sp-card sp-table-card" style="margin-bottom:24px;overflow-x:auto;">
    <div class="sp-card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <h2><?php echo esc_html($course->title); ?></h2>
        <span style="font-size:.78rem;color:var(--sp-muted);"><?php echo $total; ?> lesson<?php echo $total!==1?'s':''; ?></span>
    </div>
    <table class="sp-table" style="min-width:600px;">
        <thead>
            <tr>
                <th style="min-width:140px;">Team Member</th>
                <th style="width:90px;">Progress</th>
                <?php foreach($lessons as $li=>$l): ?>
                    <th style="font-size:.72rem;font-weight:600;max-width:100px;white-space:normal;text-align:center;" title="<?php echo esc_attr($l->title); ?>">
                        <?php echo esc_html(mb_strimwidth($l->title, 0, 22, '...')); ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach($team as $m):
            $member_comp = $comp_map[$m->id] ?? array();
            $done = count($member_comp);
            $pct  = $total > 0 ? round($done/$total*100) : 0;
        ?>
            <tr>
                <td style="font-weight:600;"><?php echo esc_html($m->name); ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <div style="flex:1;background:var(--sp-border,#e5e7eb);border-radius:99px;height:5px;min-width:50px;">
                            <div style="width:<?php echo $pct; ?>%;height:5px;border-radius:99px;background:<?php echo $pct===100?'#16a34a':'var(--sp-primary,#2563eb)'; ?>;"></div>
                        </div>
                        <span style="font-size:.72rem;color:var(--sp-muted);white-space:nowrap;"><?php echo $pct; ?>%</span>
                    </div>
                </td>
                <?php foreach($lessons as $l):
                    $c = $member_comp[$l->id] ?? null;
                ?>
                    <td style="text-align:center;">
                        <?php if($c): ?>
                            <span title="Completed <?php echo esc_attr(date('M j, Y', strtotime($c->completed_at))); ?>"
                                  style="color:#16a34a;font-size:1rem;">&#10003;</span>
                        <?php else: ?>
                            <span style="color:var(--sp-border,#e5e7eb);font-size:1rem;">&#8212;</span>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endforeach; ?>