<?php
/*
 * Plugin Name: Start Performance — Knowledge Core
 * Description: Knowledge base, resource library, and team training for the Start Performance Platform
 * Version:     1.0.11
 * Author:      Richard Brashear / Start Performance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SP_KB_VERSION',    '1.0.11' );
define( 'SP_KB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// ── Boot ──────────────────────────────────────────────────────────────────────

add_action( 'plugins_loaded', 'sp_kb_boot', 20 );

function sp_kb_boot() {
    if ( ! function_exists( 'sp_register_view' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Start Performance — Knowledge Core</strong> requires the Start Performance core plugin.</p></div>';
        } );
        return;
    }
    sp_kb_register();
}

// ── Activation ────────────────────────────────────────────────────────────────

register_activation_hook( __FILE__, 'sp_kb_activate' );
add_action( 'sp_activate', 'sp_kb_create_tables' );

function sp_kb_activate() {
    sp_kb_create_tables();
}

function sp_kb_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Shared categories (type: 'kb' or 'resource')
    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_kb_categories (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  type varchar(20) NOT NULL DEFAULT 'kb',
  name varchar(255) NOT NULL DEFAULT '',
  description text,
  sort_order int(11) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY type (type)
) $charset;" );

    // Knowledge base articles
    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_kb_articles (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  category_id bigint(20) unsigned NOT NULL DEFAULT 0,
  title varchar(255) NOT NULL DEFAULT '',
  content longtext,
  tags varchar(500) NOT NULL DEFAULT '',
  visibility varchar(20) NOT NULL DEFAULT 'team',
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY category_id (category_id),
  KEY visibility (visibility),
  FULLTEXT KEY search_idx (title, content)
) $charset;" );

    // Resource library
    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_kb_resources (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  category_id bigint(20) unsigned NOT NULL DEFAULT 0,
  title varchar(255) NOT NULL DEFAULT '',
  description text,
  resource_type varchar(10) NOT NULL DEFAULT 'file',
  url varchar(500) NOT NULL DEFAULT '',
  filename varchar(255) NOT NULL DEFAULT '',
  filesize bigint(20) unsigned NOT NULL DEFAULT 0,
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY category_id (category_id),
  KEY resource_type (resource_type)
) $charset;" );

    // Training courses
    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_kb_courses (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL DEFAULT '',
  description text,
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id)
) $charset;" );

    // Course lessons
    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_kb_lessons (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  course_id bigint(20) unsigned NOT NULL DEFAULT 0,
  sort_order int(11) NOT NULL DEFAULT 0,
  title varchar(255) NOT NULL DEFAULT '',
  content longtext,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY course_id (course_id)
) $charset;" );

    // Lesson quiz questions
    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_kb_quiz (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  lesson_id bigint(20) unsigned NOT NULL DEFAULT 0,
  sort_order int(11) NOT NULL DEFAULT 0,
  question text NOT NULL,
  answers text NOT NULL,
  correct_index tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  KEY lesson_id (lesson_id)
) $charset;" );

    // Lesson completions per team member
    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_kb_completions (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  course_id bigint(20) unsigned NOT NULL DEFAULT 0,
  lesson_id bigint(20) unsigned NOT NULL DEFAULT 0,
  member_id bigint(20) unsigned NOT NULL DEFAULT 0,
  passed tinyint(1) NOT NULL DEFAULT 1,
  completed_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY member_lesson (member_id, lesson_id),
  KEY course_id (course_id),
  KEY member_id (member_id)
) $charset;" );

}

// ── View count columns (added post-launch) ────────────────────────────────────
function sp_kb_maybe_add_view_counts() {
    global $wpdb;
    $art_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}sp_kb_articles" );
    if ( ! in_array( 'view_count', $art_cols ) ) {
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}sp_kb_articles ADD COLUMN view_count bigint(20) unsigned NOT NULL DEFAULT 0" );
    }
    $res_cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}sp_kb_resources" );
    if ( ! in_array( 'view_count', $res_cols ) ) {
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}sp_kb_resources ADD COLUMN view_count bigint(20) unsigned NOT NULL DEFAULT 0" );
    }
}

function sp_kb_ajax_view_count() {
    global $wpdb;
    if ( ! sp_is_authed() ) wp_send_json_error(); // must be logged in
    $type = sanitize_key( $_POST['kind'] ?? '' );
    $id   = (int) ( $_POST['id'] ?? 0 );
    if ( ! $id ) wp_send_json_error();
    if ( $type === 'resource' ) {
        $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}sp_kb_resources SET view_count = view_count + 1 WHERE id = %d", $id ) );
    }
    wp_send_json_success();
}

// ── Registration ──────────────────────────────────────────────────────────────

function sp_kb_register() {
    sp_kb_create_tables();
    sp_kb_maybe_add_view_counts();
    add_action( 'wp_ajax_sp_kb_view',        'sp_kb_ajax_view_count' );
    add_action( 'wp_ajax_nopriv_sp_kb_view', 'sp_kb_ajax_view_count' );

    sp_register_addon( 'sp-knowledge', array(
        'name'        => 'Knowledge Core',
        'version'     => SP_KB_VERSION,
        'description' => 'Internal knowledge base, resource library, and team training with completion tracking.',
        'icon'        => '<path fill="currentColor" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>',
        'plugin_file' => plugin_basename( __FILE__ ),
        'core_slot'   => 'knowledge-core',
    ) );

    sp_register_view( 'knowledge',          SP_KB_PLUGIN_DIR . 'templates/views/knowledge.php' );
    sp_register_view( 'knowledge-article',  SP_KB_PLUGIN_DIR . 'templates/views/knowledge-article.php' );
    sp_register_view( 'kb-resources',       SP_KB_PLUGIN_DIR . 'templates/views/kb-resources.php' );
    sp_register_view( 'kb-training',        SP_KB_PLUGIN_DIR . 'templates/views/kb-training.php' );
    sp_register_view( 'kb-training-course',  SP_KB_PLUGIN_DIR . 'templates/views/kb-training-course.php' );
    sp_register_view( 'kb-training-report',  SP_KB_PLUGIN_DIR . 'templates/views/kb-training-report.php' );

    add_filter( 'sp_nav_items',     'sp_kb_nav_items' );
    add_filter( 'sp_allowed_views', 'sp_kb_allowed_views' );

    // Knowledge Base handlers
    add_action( 'sp_post_handler_kb_category',  'sp_kb_save_category' );
    add_action( 'sp_delete_handler_kb_category','sp_kb_delete_category' );
    add_action( 'sp_post_handler_kb_article',   'sp_kb_save_article' );
    add_action( 'sp_delete_handler_kb_article', 'sp_kb_delete_article' );

    // Resource handlers
    add_action( 'sp_post_handler_kb_resource',  'sp_kb_save_resource' );
    add_action( 'sp_delete_handler_kb_resource','sp_kb_delete_resource' );

    // Training handlers
    add_action( 'sp_post_handler_kb_course',    'sp_kb_save_course' );
    add_action( 'sp_delete_handler_kb_course',  'sp_kb_delete_course' );
    add_action( 'sp_post_handler_kb_lesson',    'sp_kb_save_lesson' );
    add_action( 'sp_delete_handler_kb_lesson',  'sp_kb_delete_lesson' );
    add_action( 'sp_post_handler_kb_complete',  'sp_kb_mark_complete' );
    add_action( 'sp_post_handler_kb_quiz',       'sp_kb_submit_quiz' );
}

// ── Nav ───────────────────────────────────────────────────────────────────────

function sp_kb_nav_items( $items ) {
    $result = array();
    foreach ( $items as $item ) {
        $result[] = $item;
        if ( ! empty( $item['section'] ) && ! empty( $item['section_id'] ) && $item['section_id'] === 'knowledge-core' ) {
            $result[] = array(
                'view'  => 'knowledge',
                'label' => 'Knowledge Base',
                'icon'  => '<path fill="currentColor" d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>',
            );
            $result[] = array(
                'view'  => 'kb-resources',
                'label' => 'Resources',
                'icon'  => '<path fill="currentColor" d="M7 3a1 1 0 000 2h6a1 1 0 000-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/>',
            );
            $result[] = array(
                'view'  => 'kb-training',
                'label' => 'Training',
                'icon'  => '<path fill="currentColor" d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>',
            );
            if ( sp_is_admin_member() ) {
                $result[] = array(
                    'view'  => 'kb-training-report',
                    'label' => 'Training Report',
                    'icon'  => '<path fill="currentColor" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
                );
            }
        }
    }
    return $result;
}

function sp_kb_allowed_views( $views ) {
    $views[] = 'knowledge';
    $views[] = 'knowledge-article';
    $views[] = 'kb-resources';
    $views[] = 'kb-training';
    $views[] = 'kb-training-course';
    $views[] = 'kb-training-report';
    return $views;
}

// ── Knowledge Base handlers ───────────────────────────────────────────────────

function sp_kb_save_category( $id ) {
    global $wpdb;
    if ( ! sp_is_admin_member() ) { wp_redirect( home_url('/sp-app/?view=knowledge') ); exit; }
    $type = sanitize_key( $_POST['cat_type'] ?? 'kb' );
    $data = array(
        'type'        => $type,
        'name'        => sanitize_text_field( $_POST['name'] ?? '' ),
        'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
        'sort_order'  => (int)( $_POST['sort_order'] ?? 0 ),
    );
    if ( $id ) {
        $wpdb->update( $wpdb->prefix . 'sp_kb_categories', $data, array('id'=>$id) );
    } else {
        $data['created_at'] = current_time('mysql');
        $wpdb->insert( $wpdb->prefix . 'sp_kb_categories', $data );
    }
    $back = $type === 'resource' ? 'kb-resources' : 'knowledge';
    wp_redirect( home_url("/sp-app/?view={$back}&saved=1") ); exit;
}

function sp_kb_delete_category( $id ) {
    global $wpdb;
    if ( ! sp_is_admin_member() ) { wp_redirect( home_url('/sp-app/?view=knowledge') ); exit; }
    $type = $wpdb->get_var( $wpdb->prepare("SELECT type FROM {$wpdb->prefix}sp_kb_categories WHERE id=%d", $id) );
    $wpdb->delete( $wpdb->prefix . 'sp_kb_categories', array('id'=>$id) );
    $back = $type === 'resource' ? 'kb-resources' : 'knowledge';
    wp_redirect( home_url("/sp-app/?view={$back}&deleted=1") ); exit;
}

function sp_kb_save_article( $id ) {
    global $wpdb;
    if ( ! sp_is_admin_member() ) { wp_redirect( home_url('/sp-app/?view=knowledge') ); exit; }
    $member = sp_get_current_team_member();
    $by     = $member ? (int)$member->id : 0;
    $data   = array(
        'category_id' => (int)( $_POST['category_id'] ?? 0 ),
        'title'       => sanitize_text_field( $_POST['title'] ?? '' ),
        'content'     => wp_kses_post( $_POST['content'] ?? '' ),
        'tags'        => sanitize_text_field( $_POST['tags'] ?? '' ),
        'visibility'  => sanitize_key( $_POST['visibility'] ?? 'team' ),
        'updated_at'  => current_time('mysql'),
    );
    if ( $id ) {
        $wpdb->update( $wpdb->prefix . 'sp_kb_articles', $data, array('id'=>$id) );
    } else {
        $data['created_by'] = $by;
        $data['created_at'] = current_time('mysql');
        $wpdb->insert( $wpdb->prefix . 'sp_kb_articles', $data );
        $id = $wpdb->insert_id;
    }
    wp_redirect( home_url("/sp-app/?view=knowledge-article&id={$id}&saved=1") ); exit;
}

function sp_kb_delete_article( $id ) {
    global $wpdb;
    if ( ! sp_is_admin_member() ) { wp_redirect( home_url('/sp-app/?view=knowledge') ); exit; }
    $wpdb->delete( $wpdb->prefix . 'sp_kb_articles', array('id'=>$id) );
    wp_redirect( home_url('/sp-app/?view=knowledge&deleted=1') ); exit;
}

// ── Resource handlers ─────────────────────────────────────────────────────────

function sp_kb_save_resource( $id ) {
    global $wpdb;
    if ( ! sp_is_admin_member() ) { wp_redirect( home_url('/sp-app/?view=kb-resources') ); exit; }
    $member = sp_get_current_team_member();
    $by     = $member ? (int)$member->id : 0;
    $type   = sanitize_key( $_POST['resource_type'] ?? 'link' );
    $url    = '';
    $filename = '';
    $filesize = 0;

    if ( $type === 'file' && ! empty( $_FILES['resource_file']['name'] ) ) {
        $upload = wp_handle_upload( $_FILES['resource_file'], array('test_form'=>false) );
        if ( ! isset($upload['error']) ) {
            $url      = $upload['url'];
            $filename = basename( $upload['file'] );
            $filesize = filesize( $upload['file'] );
        }
    } elseif ( $type === 'link' ) {
        $url = esc_url_raw( $_POST['url'] ?? '' );
    }

    $data = array(
        'category_id'   => (int)( $_POST['category_id'] ?? 0 ),
        'title'         => sanitize_text_field( $_POST['title'] ?? '' ),
        'description'   => sanitize_textarea_field( $_POST['description'] ?? '' ),
        'resource_type' => $type,
    );
    if ( $url )      $data['url']      = $url;
    if ( $filename ) $data['filename'] = $filename;
    if ( $filesize ) $data['filesize'] = $filesize;

    if ( $id ) {
        $wpdb->update( $wpdb->prefix . 'sp_kb_resources', $data, array('id'=>$id) );
    } else {
        $data['created_by'] = $by;
        $data['created_at'] = current_time('mysql');
        $wpdb->insert( $wpdb->prefix . 'sp_kb_resources', $data );
    }
    wp_redirect( home_url('/sp-app/?view=kb-resources&saved=1') ); exit;
}

function sp_kb_delete_resource( $id ) {
    global $wpdb;
    if ( ! sp_is_admin_member() ) { wp_redirect( home_url('/sp-app/?view=kb-resources') ); exit; }
    $wpdb->delete( $wpdb->prefix . 'sp_kb_resources', array('id'=>$id) );
    wp_redirect( home_url('/sp-app/?view=kb-resources&deleted=1') ); exit;
}

// ── Training handlers ─────────────────────────────────────────────────────────

function sp_kb_save_course( $id ) {
    global $wpdb;
    if ( ! sp_is_admin_member() ) { wp_redirect( home_url('/sp-app/?view=kb-training') ); exit; }
    $member = sp_get_current_team_member();
    $by     = $member ? (int)$member->id : 0;
    $data   = array(
        'title'       => sanitize_text_field( $_POST['title'] ?? '' ),
        'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
    );
    if ( $id ) {
        $wpdb->update( $wpdb->prefix . 'sp_kb_courses', $data, array('id'=>$id) );
    } else {
        $data['created_by'] = $by;
        $data['created_at'] = current_time('mysql');
        $wpdb->insert( $wpdb->prefix . 'sp_kb_courses', $data );
        $id = $wpdb->insert_id;
    }
    wp_redirect( home_url("/sp-app/?view=kb-training-course&id={$id}&saved=1") ); exit;
}

function sp_kb_delete_course( $id ) {
    global $wpdb;
    if ( ! sp_is_admin_member() ) { wp_redirect( home_url('/sp-app/?view=kb-training') ); exit; }
    $wpdb->delete( $wpdb->prefix . 'sp_kb_lessons', array('course_id'=>$id) );
    $wpdb->delete( $wpdb->prefix . 'sp_kb_courses', array('id'=>$id) );
    wp_redirect( home_url('/sp-app/?view=kb-training&deleted=1') ); exit;
}

function sp_kb_save_lesson( $id ) {
    global $wpdb;
    if ( ! sp_is_admin_member() ) { wp_redirect( home_url('/sp-app/?view=kb-training') ); exit; }
    $course_id = (int)( $_POST['course_id'] ?? 0 );
    $data = array(
        'course_id'  => $course_id,
        'sort_order' => (int)( $_POST['sort_order'] ?? 0 ),
        'title'      => sanitize_text_field( $_POST['title'] ?? '' ),
        'content'    => wp_kses_post( $_POST['content'] ?? '' ),
    );
    if ( $id ) {
        $wpdb->update( $wpdb->prefix . 'sp_kb_lessons', $data, array('id'=>$id) );
    } else {
        $data['created_at'] = current_time('mysql');
        $wpdb->insert( $wpdb->prefix . 'sp_kb_lessons', $data );
        $id = $wpdb->insert_id;
    }

    // Save quiz questions — replace all for this lesson
    $wpdb->delete( $wpdb->prefix . 'sp_kb_quiz', array('lesson_id'=>$id) );
    $questions = (array)( $_POST['q_question'] ?? array() );
    $answers   = (array)( $_POST['q_answers']  ?? array() );
    $corrects  = (array)( $_POST['q_correct']  ?? array() );
    foreach ( $questions as $i => $q ) {
        $q = sanitize_text_field( $q );
        if ( $q === '' ) continue;
        $ans_raw = sanitize_text_field( $answers[$i] ?? '' );
        $ans_arr = array_values( array_filter( array_map('trim', explode('|', $ans_raw)) ) );
        if ( count($ans_arr) < 2 ) continue;
        $wpdb->insert( $wpdb->prefix . 'sp_kb_quiz', array(
            'lesson_id'     => $id,
            'sort_order'    => $i + 1,
            'question'      => $q,
            'answers'       => json_encode( $ans_arr ),
            'correct_index' => (int)( $corrects[$i] ?? 0 ),
        ) );
    }
    wp_redirect( home_url("/sp-app/?view=kb-training-course&id={$course_id}&lesson_saved=1") ); exit;
}

function sp_kb_delete_lesson( $id ) {
    global $wpdb;
    if ( ! sp_is_admin_member() ) { wp_redirect( home_url('/sp-app/?view=kb-training') ); exit; }
    $course_id = (int)$wpdb->get_var( $wpdb->prepare("SELECT course_id FROM {$wpdb->prefix}sp_kb_lessons WHERE id=%d", $id) );
    $wpdb->delete( $wpdb->prefix . 'sp_kb_quiz',    array('lesson_id'=>$id) );
    $wpdb->delete( $wpdb->prefix . 'sp_kb_lessons', array('id'=>$id) );
    $wpdb->delete( $wpdb->prefix . 'sp_kb_completions', array('lesson_id'=>$id) );
    wp_redirect( home_url("/sp-app/?view=kb-training-course&id={$course_id}") ); exit;
}

function sp_kb_mark_complete( $id ) {
    global $wpdb;
    $lesson_id = (int)( $_POST['lesson_id'] ?? 0 );
    $course_id = (int)( $_POST['course_id'] ?? 0 );
    $member    = sp_get_current_team_member();
    if ( ! $member || ! $lesson_id ) { wp_redirect( home_url('/sp-app/?view=kb-training') ); exit; }
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}sp_kb_completions WHERE member_id=%d AND lesson_id=%d",
        $member->id, $lesson_id
    ) );
    if ( ! $exists ) {
        $wpdb->insert( $wpdb->prefix . 'sp_kb_completions', array(
            'course_id'    => $course_id,
            'lesson_id'    => $lesson_id,
            'member_id'    => (int)$member->id,
            'passed'       => 1,
            'completed_at' => current_time('mysql'),
        ) );
    }
    wp_redirect( home_url("/sp-app/?view=kb-training-course&id={$course_id}&completed=1") ); exit;
}

function sp_kb_submit_quiz( $id ) {
    global $wpdb;
    $lesson_id = (int)( $_POST['lesson_id'] ?? 0 );
    $course_id = (int)( $_POST['course_id'] ?? 0 );
    $member    = sp_get_current_team_member();
    if ( ! $member || ! $lesson_id ) { wp_redirect( home_url('/sp-app/?view=kb-training') ); exit; }

    $questions = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sp_kb_quiz WHERE lesson_id=%d ORDER BY sort_order ASC", $lesson_id
    ) );

    $total   = count( $questions );
    $correct = 0;
    foreach ( $questions as $q ) {
        $submitted = (int)( $_POST['q_' . $q->id] ?? -1 );
        if ( $submitted === (int)$q->correct_index ) $correct++;
    }
    $passed = $total > 0 && ( $correct / $total ) >= 0.7;

    if ( $passed ) {
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}sp_kb_completions WHERE member_id=%d AND lesson_id=%d",
            $member->id, $lesson_id
        ) );
        if ( ! $exists ) {
            $wpdb->insert( $wpdb->prefix . 'sp_kb_completions', array(
                'course_id'    => $course_id,
                'lesson_id'    => $lesson_id,
                'member_id'    => (int)$member->id,
                'passed'       => 1,
                'completed_at' => current_time('mysql'),
            ) );
        }
    }

    $score_pct = $total > 0 ? round($correct/$total*100) : 0;
    wp_redirect( home_url("/sp-app/?view=kb-training-course&id={$course_id}&quiz_result={$score_pct}&quiz_passed=" . ($passed?1:0) . "&lesson={$lesson_id}") ); exit;
}

