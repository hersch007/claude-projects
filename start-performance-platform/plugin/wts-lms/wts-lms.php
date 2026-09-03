<?php
/*
 * Plugin Name: WTS — AMP Training (LMS)
 * Description: Wireless Tower Solutions AMP training courses, embedded natively in the Start Performance app under Knowledge Core. File-based lessons + quizzes, SP-authenticated learners, progress tracking.
 * Version:     0.4.2
 * Author:      Wireless Tower Solutions / Start Performance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WTS_LMS_VERSION', '0.4.2' );
define( 'WTS_LMS_DIR', plugin_dir_path( __FILE__ ) );
define( 'WTS_LMS_URL', plugin_dir_url( __FILE__ ) );

// ── Boot ──────────────────────────────────────────────────────────────────────

add_action( 'plugins_loaded', 'wts_lms_boot', 20 );

function wts_lms_boot() {
    if ( ! function_exists( 'sp_register_view' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>WTS — AMP Training</strong> requires the Start Performance core plugin.</p></div>';
        } );
        return;
    }
    wts_lms_register();
}

// ── Activation + tables ─────────────────────────────────────────────────────────

register_activation_hook( __FILE__, 'wts_lms_activate' );
add_action( 'sp_activate', 'wts_lms_create_tables' );

function wts_lms_activate() { wts_lms_create_tables(); }

function wts_lms_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta( "CREATE TABLE {$wpdb->prefix}wts_lms_progress (
  member_id bigint(20) NOT NULL DEFAULT 0,
  lesson_slug varchar(191) NOT NULL DEFAULT '',
  completed_at datetime NOT NULL,
  PRIMARY KEY  (member_id, lesson_slug)
) $charset;" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}wts_lms_quiz_results (
  member_id bigint(20) NOT NULL DEFAULT 0,
  lesson_slug varchar(191) NOT NULL DEFAULT '',
  score int(11) NOT NULL DEFAULT 0,
  total int(11) NOT NULL DEFAULT 0,
  passed tinyint(1) NOT NULL DEFAULT 0,
  taken_at datetime NOT NULL,
  PRIMARY KEY  (member_id, lesson_slug)
) $charset;" );
}

// ── Registration ────────────────────────────────────────────────────────────────

function wts_lms_register() {
    sp_register_addon( 'wts-lms', array(
        'name'        => 'AMP Training',
        'version'     => WTS_LMS_VERSION,
        'description' => 'AMP training courses with lessons, quizzes, and progress tracking.',
        'core_slot'   => 'knowledge-core',
        'plugin_file' => plugin_basename( __FILE__ ),
    ) );

    sp_register_view( 'wts-training',       WTS_LMS_DIR . 'templates/views/wts-training.php' );
    sp_register_view( 'wts-course',         WTS_LMS_DIR . 'templates/views/wts-course.php' );
    sp_register_view( 'wts-lesson',         WTS_LMS_DIR . 'templates/views/wts-lesson.php' );
    sp_register_view( 'wts-training-admin', WTS_LMS_DIR . 'templates/views/wts-training-admin.php' );
    sp_register_view( 'wts-certificate',    WTS_LMS_DIR . 'templates/views/wts-certificate.php' );

    // Priority 20 so it runs AFTER Knowledge Core (priority 10) has added its own nav items —
    // that lets us replace its built-in "Training" item in place.
    add_filter( 'sp_nav_items',     'wts_lms_nav_items', 20 );
    add_filter( 'sp_allowed_views', 'wts_lms_allowed_views' );
    add_action( 'sp_post_handler_wts_lms', 'wts_lms_handle_post' );
    add_action( 'init', 'wts_lms_maybe_export_csv' );
}

// The two AMP Training nav items (learner view + admin report).
function wts_lms_training_nav() {
    return array(
        array(
            'view'  => 'wts-training',
            'label' => 'AMP Training',
            'icon'  => '<path fill="currentColor" d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>',
        ),
        array(
            'view'       => 'wts-training-admin',
            'label'      => 'Training Report',
            'icon'       => '<path fill="currentColor" d="M9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4zm2 2H5V5h14v14zm0-16H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2z"/>',
            'admin_only' => true,
        ),
    );
}

// Replace Knowledge Core's built-in "Training" (kb-training) with AMP Training, in place.
// This is a custom module replacing one core feature (training) while leaving the rest of
// Knowledge Core (KB, Resources) intact. Falls back to appending after the knowledge-core
// header if the built-in Training isn't present.
function wts_lms_nav_items( $items ) {
    $ours    = wts_lms_training_nav();
    $out     = array();
    $swapped = false;
    foreach ( $items as $item ) {
        if ( ! empty( $item['view'] ) && $item['view'] === 'kb-training' ) {
            foreach ( $ours as $o ) $out[] = $o; // drop kb-training, insert ours in its slot
            $swapped = true;
            continue;
        }
        if ( ! empty( $item['view'] ) && $item['view'] === 'kb-training-report' ) {
            continue; // WTS has its own superior training report
        }
        $out[] = $item;
    }
    if ( ! $swapped ) {
        $final = array();
        foreach ( $out as $item ) {
            $final[] = $item;
            if ( ! $swapped && ! empty( $item['section'] ) && ! empty( $item['section_id'] ) && 'knowledge-core' === $item['section_id'] ) {
                foreach ( $ours as $o ) $final[] = $o;
                $swapped = true;
            }
        }
        $out = $final;
    }
    return $out;
}

function wts_lms_allowed_views( $views ) {
    $views[] = 'wts-training';
    $views[] = 'wts-course';
    $views[] = 'wts-lesson';
    $views[] = 'wts-training-admin';
    $views[] = 'wts-certificate';
    return $views;
}

// Learner display name for the certificate.
function wts_lms_actor_name() {
    if ( function_exists( 'sp_get_current_team_member' ) ) {
        $m = sp_get_current_team_member();
        if ( $m ) return $m->name;
    }
    return 'Preview User';
}

// The date a course was fully completed (latest lesson completion), or null if not complete.
function wts_lms_course_completed_at( $member, $course ) {
    if ( wts_lms_course_progress( $member, $course )['pct'] != 100 ) return null;
    global $wpdb;
    $slugs = array();
    foreach ( $course['lessons'] as $l ) $slugs[] = $l['slug'];
    $place = implode( ',', array_fill( 0, count( $slugs ), '%s' ) );
    $args  = array_merge( array( $member ), $slugs );
    return $wpdb->get_var( $wpdb->prepare(
        "SELECT MAX(completed_at) FROM {$wpdb->prefix}wts_lms_progress WHERE member_id = %d AND lesson_slug IN ($place)",
        $args
    ) );
}

// Active team members = the learner roster for the progress report.
function wts_lms_learners() {
    global $wpdb;
    return $wpdb->get_results( "SELECT id, name, email, role FROM {$wpdb->prefix}sp_team WHERE status = 'active' ORDER BY name" );
}

// CSV export — must run before any app HTML is sent, so it's hooked on init.
function wts_lms_maybe_export_csv() {
    if ( ! isset( $_GET['wts_lms_export'] ) || $_GET['wts_lms_export'] !== 'csv' ) return;
    if ( ! function_exists( 'sp_is_authed' ) || ! sp_is_authed() || ! wts_lms_is_admin() ) return;

    $courses  = wts_lms_courses();
    $learners = wts_lms_learners();
    nocache_headers();
    header( 'Content-Type: text/csv; charset=UTF-8' );
    header( 'Content-Disposition: attachment; filename="wts-amp-training-progress-' . date( 'Y-m-d' ) . '.csv"' );
    $out = fopen( 'php://output', 'w' );

    $head = array( 'Name', 'Email' );
    foreach ( $courses as $c ) $head[] = $c['title'] . ' %';
    $head[] = 'Overall %';
    fputcsv( $out, $head );

    foreach ( $learners as $u ) {
        $row = array( $u->name, $u->email );
        $td = $tt = 0;
        foreach ( $courses as $c ) {
            $p = wts_lms_course_progress( $u->id, $c );
            $row[] = $p['pct'] . '%';
            $td += $p['done']; $tt += $p['total'];
        }
        $row[] = ( $tt > 0 ? round( $td / $tt * 100 ) : 0 ) . '%';
        fputcsv( $out, $row );
    }
    fclose( $out );
    exit;
}

// ── Course manifest (file-based; mirrors the original installer seed) ─────────────

function wts_lms_courses() {
    return array(
        array(
            'slug'  => 'amp-fundamentals',
            'title' => 'AMP Fundamentals',
            'desc'  => 'Core training for all AMP users — prerequisite for all role-specific courses.',
            'lessons' => array(
                array( 'slug' => 'what-is-amp',        'title' => 'What Is AMP and Why It Exists',         'file' => 'course1/lesson-01-what-is-amp.md' ),
                array( 'slug' => 'user-roles',         'title' => 'User Roles and Responsibilities',        'file' => 'course1/lesson-02-user-roles.md' ),
                array( 'slug' => 'dashboard-status',   'title' => 'Projects, Dashboard and Status',         'file' => 'course1/lesson-03-dashboard-status.md' ),
                array( 'slug' => 'pif',                'title' => 'Project Information Form (PIF)',          'file' => 'course1/lesson-04-pif.md' ),
                array( 'slug' => 'component-types',    'title' => 'Component Types (NWF, TWF, NCM, ATF)',    'file' => 'course1/lesson-05-component-types.md' ),
                array( 'slug' => 'security-compliance','title' => 'Security, Compliance & Official Record', 'file' => 'course1/lesson-06-security-compliance.md' ),
                array( 'slug' => 'course1-exam',       'title' => 'Course 1 Final Exam',                    'file' => 'course1/lesson-07-course1-exam.md' ),
            ),
        ),
        array(
            'slug'  => 'amp-jurisdiction',
            'title' => 'AMP for Jurisdiction Reviewers',
            'desc'  => 'Training for government/jurisdiction staff who review and approve applications.',
            'lessons' => array(
                array( 'slug' => 'authority-responsibility', 'title' => 'Authority and Responsibility',          'file' => 'course2/lesson-01-authority.md' ),
                array( 'slug' => 'dashboard-workload',       'title' => 'Dashboard and Workload Management',      'file' => 'course2/lesson-02-dashboard-workload.md' ),
                array( 'slug' => 'reviewing-pif',            'title' => 'Reviewing the PIF',                      'file' => 'course2/lesson-03-reviewing-pif.md' ),
                array( 'slug' => 'reviewing-components',     'title' => 'Reviewing Components',                   'file' => 'course2/lesson-04-reviewing-components.md' ),
                array( 'slug' => 'shot-clock',               'title' => 'Shot Clock',                             'file' => 'course2/lesson-05-shot-clock.md' ),
                array( 'slug' => 'making-decisions',         'title' => 'Making Decisions',                       'file' => 'course2/lesson-06-making-decisions.md' ),
                array( 'slug' => 'communication-closure',    'title' => 'Communication, Inspection & Closure',    'file' => 'course2/lesson-07-communication.md' ),
            ),
        ),
    );
}

function wts_lms_get_course( $slug ) {
    foreach ( wts_lms_courses() as $c ) if ( $c['slug'] === $slug ) return $c;
    return null;
}

// Returns lesson + its course context (course_slug, course_title, index, prev, next) or null.
function wts_lms_get_lesson( $slug ) {
    foreach ( wts_lms_courses() as $c ) {
        foreach ( $c['lessons'] as $i => $l ) {
            if ( $l['slug'] === $slug ) {
                return array_merge( $l, array(
                    'course_slug'  => $c['slug'],
                    'course_title' => $c['title'],
                    'index'        => $i,
                    'prev'         => isset( $c['lessons'][ $i - 1 ] ) ? $c['lessons'][ $i - 1 ] : null,
                    'next'         => isset( $c['lessons'][ $i + 1 ] ) ? $c['lessons'][ $i + 1 ] : null,
                    'course_lessons' => $c['lessons'],
                ) );
            }
        }
    }
    return null;
}

// ── Actor (SP-authenticated learner) ─────────────────────────────────────────────

function wts_lms_actor_id() {
    if ( function_exists( 'sp_get_current_team_member' ) ) {
        $m = sp_get_current_team_member();
        if ( $m ) return (int) $m->id;
    }
    if ( function_exists( 'sp_is_super_admin' ) && sp_is_super_admin() ) return 0; // super-admin preview
    return -1;
}

function wts_lms_is_admin() {
    return ( function_exists( 'sp_is_admin_member' ) && sp_is_admin_member() )
        || ( function_exists( 'sp_is_super_admin' ) && sp_is_super_admin() );
}

// ── Progress + quiz storage ──────────────────────────────────────────────────────

function wts_lms_is_complete( $member_id, $slug ) {
    global $wpdb;
    return (bool) $wpdb->get_var( $wpdb->prepare(
        "SELECT 1 FROM {$wpdb->prefix}wts_lms_progress WHERE member_id = %d AND lesson_slug = %s", $member_id, $slug
    ) );
}

function wts_lms_mark_complete( $member_id, $slug ) {
    global $wpdb;
    $wpdb->query( $wpdb->prepare(
        "INSERT IGNORE INTO {$wpdb->prefix}wts_lms_progress (member_id, lesson_slug, completed_at) VALUES (%d, %s, %s)",
        $member_id, $slug, current_time( 'mysql' )
    ) );
}

function wts_lms_unmark( $member_id, $slug ) {
    global $wpdb;
    $wpdb->delete( $wpdb->prefix . 'wts_lms_progress',     array( 'member_id' => $member_id, 'lesson_slug' => $slug ) );
    $wpdb->delete( $wpdb->prefix . 'wts_lms_quiz_results', array( 'member_id' => $member_id, 'lesson_slug' => $slug ) );
}

function wts_lms_get_quiz_result( $member_id, $slug ) {
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wts_lms_quiz_results WHERE member_id = %d AND lesson_slug = %s", $member_id, $slug
    ), ARRAY_A );
}

// Pass threshold: 80% (matches the original LMS). Passing auto-completes the lesson.
function wts_lms_save_quiz_result( $member_id, $slug, $score, $total ) {
    global $wpdb;
    $passed = ( $total > 0 && $score >= ceil( $total * 0.8 ) ) ? 1 : 0;
    $wpdb->query( $wpdb->prepare(
        "INSERT INTO {$wpdb->prefix}wts_lms_quiz_results (member_id, lesson_slug, score, total, passed, taken_at)
         VALUES (%d, %s, %d, %d, %d, %s)
         ON DUPLICATE KEY UPDATE score = %d, total = %d, passed = %d, taken_at = %s",
        $member_id, $slug, $score, $total, $passed, current_time( 'mysql' ),
        $score, $total, $passed, current_time( 'mysql' )
    ) );
    if ( $passed ) wts_lms_mark_complete( $member_id, $slug );
    return $passed;
}

function wts_lms_course_progress( $member_id, $course ) {
    $total = count( $course['lessons'] );
    $done  = 0;
    foreach ( $course['lessons'] as $l ) if ( wts_lms_is_complete( $member_id, $l['slug'] ) ) $done++;
    return array( 'total' => $total, 'done' => $done, 'pct' => $total > 0 ? round( $done / $total * 100 ) : 0 );
}

// ── Content readers ──────────────────────────────────────────────────────────────

function wts_lms_render_markdown( $file ) {
    $path = WTS_LMS_DIR . 'content/' . $file;
    $raw  = file_exists( $path ) ? file_get_contents( $path ) : "# Content coming soon";
    require_once WTS_LMS_DIR . 'includes/Parsedown.php';
    $pd = new Parsedown();
    $pd->setSafeMode( true );
    return $pd->text( $raw );
}

function wts_lms_quiz_data( $file ) {
    $qf = WTS_LMS_DIR . 'content/' . str_replace( '.md', '-quiz.json', $file );
    if ( ! file_exists( $qf ) ) return null;
    $d = json_decode( file_get_contents( $qf ), true );
    return ( is_array( $d ) && ! empty( $d['questions'] ) ) ? $d : null;
}

// ── POST handler (quiz submit / mark complete / mark incomplete) ─────────────────

function wts_lms_handle_post( $id ) {
    if ( ! function_exists( 'sp_is_authed' ) || ! sp_is_authed() ) return;
    $member = wts_lms_actor_id();
    $action = isset( $_POST['wts_action'] ) ? sanitize_key( $_POST['wts_action'] ) : '';
    $slug   = isset( $_POST['lesson'] ) ? sanitize_title( $_POST['lesson'] ) : '';
    $lesson = wts_lms_get_lesson( $slug );
    if ( ! $lesson ) { wp_redirect( home_url( '/sp-app/?view=wts-training' ) ); exit; }

    $back = home_url( '/sp-app/?view=wts-lesson&lesson=' . urlencode( $slug ) );

    if ( $action === 'quiz_submit' ) {
        $quiz = wts_lms_quiz_data( $lesson['file'] );
        if ( $quiz ) {
            $score = 0; $answers = array();
            foreach ( $quiz['questions'] as $qi => $q ) {
                $ans = isset( $_POST[ 'q' . $qi ] ) ? (string) $_POST[ 'q' . $qi ] : '';
                $answers[ $qi ] = $ans;
                if ( $ans === $q['answer'] ) $score++;
            }
            wts_lms_save_quiz_result( $member, $slug, $score, count( $quiz['questions'] ) );
            set_transient( 'wts_lms_ans_' . $member . '_' . md5( $slug ), $answers, 600 );
        }
        wp_redirect( $back . '#quiz' ); exit;
    }

    if ( $action === 'mark_complete' ) {
        wts_lms_mark_complete( $member, $slug );
        wp_redirect( $back . '&done=1' ); exit;
    }

    if ( $action === 'mark_incomplete' ) {
        wts_lms_unmark( $member, $slug );
        delete_transient( 'wts_lms_ans_' . $member . '_' . md5( $slug ) );
        wp_redirect( $back ); exit;
    }

    wp_redirect( $back ); exit;
}

// ── Shared styles (WTS blue: accent = --sp-accent #29A8E0, navy headings #1A4F8A) ──

function wts_lms_styles() {
    static $done = false; if ( $done ) return; $done = true;
    ?>
    <style>
    .wts-course-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;margin-top:16px}
    .wts-course-card{display:block;text-decoration:none;padding:20px 22px}
    .wts-course-card h3{color:#1A4F8A;margin:0 0 6px;font-size:1.05rem}
    .wts-course-card p{color:#64748b;font-size:.88rem;margin:0 0 14px}
    .wts-bar{height:8px;background:#e5e7eb;border-radius:6px;overflow:hidden}
    .wts-bar-fill{height:100%;background:var(--sp-accent,#29A8E0)}
    .wts-meta{font-size:.8rem;color:#64748b;margin:8px 0 0}
    .wts-badge{background:#def7ec;color:#03543f;border-radius:10px;padding:2px 8px;font-size:.72rem;margin-left:6px}
    .wts-lesson-row{display:flex;align-items:center;gap:14px;padding:14px 18px;margin-bottom:8px;text-decoration:none}
    .wts-lesson-row .n{width:30px;text-align:center;font-size:1.1rem;color:#cbd5e1}
    .wts-lesson-row strong{color:#1A4F8A}
    .wts-lesson-layout{display:grid;grid-template-columns:230px 1fr;gap:20px;align-items:start}
    @media(max-width:820px){.wts-lesson-layout{grid-template-columns:1fr}}
    .wts-sidenav h4{color:#1A4F8A;font-size:.9rem;margin:0 0 10px}
    .wts-sidenav a{display:block;padding:8px 10px;border-radius:6px;font-size:.85rem;color:#475569;text-decoration:none;margin-bottom:2px}
    .wts-sidenav a.active{background:var(--sp-accent-bg,rgba(41,168,224,.14));color:#1A4F8A;font-weight:600}
    .wts-sidenav a.done::before{content:"\2713 ";color:#16a34a}
    .wts-content{line-height:1.7;color:#1e293b;padding:1.5rem 2.25rem}
    @media(max-width:600px){.wts-content{padding:1.25rem 1.25rem}}
    .wts-content h1,.wts-content h2,.wts-content h3,.wts-content p,.wts-content ul,.wts-content ol,.wts-content blockquote{max-width:70ch}
    .wts-content h1{color:#1A4F8A;font-size:1.5rem;line-height:1.25;margin:0 0 1rem}
    .wts-content h2{color:#1A4F8A;font-size:1.2rem;line-height:1.3;margin:2.4rem 0 .8rem}
    .wts-content h3{color:#1A4F8A;font-size:1.05rem;margin:2rem 0 .5rem}
    .wts-content h1:first-child,.wts-content h2:first-child,.wts-content h3:first-child{margin-top:0}
    .wts-content p{margin:0 0 1.05rem}
    .wts-content ul,.wts-content ol{margin:0 0 1.05rem}
    .wts-content hr{border:none;height:0;margin:1.75rem 0;background:none}
    .wts-content code{background:#f1f5f9;padding:1px 5px;border-radius:4px}
    .wts-content table{border-collapse:collapse;width:100%;margin:1rem 0}
    .wts-content th,.wts-content td{border:1px solid #e5e7eb;padding:8px 10px;text-align:left;font-size:.9rem}
    .wts-quiz{padding:1.5rem 2.25rem}
    @media(max-width:600px){.wts-quiz{padding:1.25rem 1.25rem}}
    .wts-q{margin-bottom:1.5rem}
    .wts-q .qq{font-weight:600;color:#0f172a;margin:0 0 8px}
    .wts-opt{display:flex;gap:10px;align-items:flex-start;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:8px;cursor:pointer}
    .wts-opt:hover{border-color:var(--sp-accent,#29A8E0)}
    .wts-opt.correct{background:#def7ec;border-color:#84e1bc}
    .wts-opt.wrong{background:#fde8e8;border-color:#f8b4b4}
    .wts-expl{margin-top:8px;padding:10px 12px;background:#e9f7fd;border-left:4px solid var(--sp-accent,#29A8E0);border-radius:0 6px 6px 0;font-size:.88rem}
    .wts-result{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-weight:600}
    .wts-result.pass{background:#def7ec;color:#03543f}
    .wts-result.fail{background:#fde8e8;color:#9b1c1c}
    .wts-btn{display:inline-block;background:var(--sp-accent,#29A8E0);color:#fff;border:none;border-radius:8px;padding:10px 18px;font-size:.9rem;font-weight:600;cursor:pointer;text-decoration:none}
    .wts-btn.navy{background:#1A4F8A}
    .wts-btn.ghost{background:#fff;color:#1A4F8A;border:1px solid #cbd5e1}
    .wts-btn.sm{padding:6px 12px;font-size:.8rem}
    </style>
    <?php
}
