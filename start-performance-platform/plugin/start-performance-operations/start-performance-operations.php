<?php
/*
 * Plugin Name: Start Performance — Operations Core
 * Description: Workflow templates and onboarding checklists for the Start Performance Platform
 * Version:     1.1.5
 * Author:      Richard Brashear / Start Performance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SP_OPS_VERSION',    '1.1.5' );
define( 'SP_OPS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// ── Boot ──────────────────────────────────────────────────────────────────────

add_action( 'plugins_loaded', 'sp_ops_boot', 20 );

function sp_ops_boot() {
    if ( ! function_exists( 'sp_register_view' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Start Performance — Operations Core</strong> requires the Start Performance core plugin.</p></div>';
        } );
        return;
    }
    sp_ops_register();
}

// ── Activation ────────────────────────────────────────────────────────────────

register_activation_hook( __FILE__, 'sp_ops_activate' );
add_action( 'sp_activate', 'sp_ops_create_tables' );

function sp_ops_activate() {
    sp_ops_create_tables();
}

function sp_ops_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_op_workflows (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL DEFAULT '',
  description text,
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id)
) $charset;" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_op_workflow_steps (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  workflow_id bigint(20) unsigned NOT NULL DEFAULT 0,
  sort_order int(11) NOT NULL DEFAULT 0,
  title varchar(255) NOT NULL DEFAULT '',
  due_offset_days int(11) NOT NULL DEFAULT 0,
  assigned_role varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY  (id),
  KEY workflow_id (workflow_id)
) $charset;" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_op_instances (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  workflow_id bigint(20) unsigned NOT NULL DEFAULT 0,
  record_type varchar(20) NOT NULL DEFAULT '',
  record_id bigint(20) unsigned NOT NULL DEFAULT 0,
  started_by bigint(20) unsigned NOT NULL DEFAULT 0,
  started_at datetime NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'active',
  PRIMARY KEY  (id),
  KEY workflow_id (workflow_id),
  KEY record_type (record_type),
  KEY record_id (record_id),
  KEY status (status)
) $charset;" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_ob_templates (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL DEFAULT '',
  description text,
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id)
) $charset;" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_ob_items (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  template_id bigint(20) unsigned NOT NULL DEFAULT 0,
  sort_order int(11) NOT NULL DEFAULT 0,
  title varchar(255) NOT NULL DEFAULT '',
  description text,
  PRIMARY KEY  (id),
  KEY template_id (template_id)
) $charset;" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_ob_instances (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  template_id bigint(20) unsigned NOT NULL DEFAULT 0,
  record_type varchar(20) NOT NULL DEFAULT '',
  record_id bigint(20) unsigned NOT NULL DEFAULT 0,
  started_by bigint(20) unsigned NOT NULL DEFAULT 0,
  started_at datetime NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'active',
  PRIMARY KEY  (id),
  KEY template_id (template_id),
  KEY record_type (record_type),
  KEY record_id (record_id),
  KEY status (status)
) $charset;" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_ob_progress (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  instance_id bigint(20) unsigned NOT NULL DEFAULT 0,
  item_id bigint(20) unsigned NOT NULL DEFAULT 0,
  checked_by bigint(20) unsigned NOT NULL DEFAULT 0,
  checked_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY instance_item (instance_id, item_id),
  KEY instance_id (instance_id)
) $charset;" );

    // Add instance_id column to sp_tasks if not present (tracks which workflow created a task)
    $cols = $wpdb->get_col( "SHOW COLUMNS FROM {$wpdb->prefix}sp_tasks" );
    if ( ! in_array( 'op_instance_id', $cols ) ) {
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}sp_tasks ADD COLUMN op_instance_id bigint(20) unsigned NOT NULL DEFAULT 0" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}sp_tasks ADD KEY op_instance_id (op_instance_id)" );
    }
}

// ── Registration ──────────────────────────────────────────────────────────────

function sp_ops_register() {
    sp_ops_create_tables(); // ensure tables exist on first load

    sp_register_addon( 'sp-operations', array(
        'name'        => 'Operations Core',
        'version'     => SP_OPS_VERSION,
        'description' => 'Workflow templates and onboarding checklists — assign a workflow to any contact or company and watch tasks auto-create.',
        'icon'        => '<path fill="currentColor" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
        'plugin_file' => plugin_basename( __FILE__ ),
        'core_slot'   => 'operations-core',
    ) );

    sp_register_view( 'operations',           SP_OPS_PLUGIN_DIR . 'templates/views/operations.php' );
    sp_register_view( 'operations-workflow',  SP_OPS_PLUGIN_DIR . 'templates/views/operations-workflow.php' );
    sp_register_view( 'onboarding',           SP_OPS_PLUGIN_DIR . 'templates/views/onboarding.php' );
    sp_register_view( 'onboarding-checklist', SP_OPS_PLUGIN_DIR . 'templates/views/onboarding-checklist.php' );

    add_filter( 'sp_nav_items',     'sp_ops_nav_items' );
    add_filter( 'sp_allowed_views', 'sp_ops_allowed_views' );

    add_action( 'sp_post_handler_op_workflow',        'sp_ops_save_workflow' );
    add_action( 'sp_delete_handler_op_workflow',      'sp_ops_delete_workflow' );
    add_action( 'sp_post_handler_op_start_workflow',  'sp_ops_start_workflow' );
    add_action( 'sp_post_handler_op_cancel_instance', 'sp_ops_cancel_instance' );

    add_action( 'sp_post_handler_ob_template',        'sp_ob_save_template' );
    add_action( 'sp_delete_handler_ob_template',      'sp_ob_delete_template' );
    add_action( 'sp_post_handler_ob_assign',          'sp_ob_assign' );
    add_action( 'sp_post_handler_ob_check',           'sp_ob_check_item' );
    add_action( 'sp_post_handler_ob_uncheck',         'sp_ob_uncheck_item' );
    add_action( 'sp_post_handler_ob_complete',        'sp_ob_complete_instance' );

    add_action( 'sp_contact_view_after', 'sp_ob_record_widget' );
    add_action( 'sp_company_view_after', 'sp_ob_record_widget' );
}

// ── Nav ───────────────────────────────────────────────────────────────────────

function sp_ops_nav_items( $items ) {
    $result = array();
    foreach ( $items as $item ) {
        $result[] = $item;
        if ( ! empty( $item['section'] ) && ! empty( $item['section_id'] ) && $item['section_id'] === 'operations-core' ) {
            $result[] = array(
                'view'  => 'operations',
                'label' => 'Workflows',
                'icon'  => '<path fill="currentColor" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
            );
            $result[] = array(
                'view'  => 'onboarding',
                'label' => 'Onboarding',
                'icon'  => '<path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>',
            );
        }
    }
    return $result;
}

function sp_ops_allowed_views( $views ) {
    $views[] = 'operations';
    $views[] = 'operations-workflow';
    $views[] = 'onboarding';
    $views[] = 'onboarding-checklist';
    return $views;
}

// ── Save workflow template ────────────────────────────────────────────────────

function sp_ops_save_workflow( $id ) {
    global $wpdb;
    if ( ! sp_is_admin_member() ) {
        wp_redirect( home_url( '/sp-app/?view=operations' ) ); exit;
    }

    $name        = sanitize_text_field( $_POST['name'] ?? '' );
    $description = sanitize_textarea_field( $_POST['description'] ?? '' );
    $member      = sp_get_current_team_member();
    $by          = $member ? (int) $member->id : 0;

    if ( $id ) {
        $wpdb->update( $wpdb->prefix . 'sp_op_workflows',
            array( 'name' => $name, 'description' => $description ),
            array( 'id' => $id )
        );
    } else {
        $wpdb->insert( $wpdb->prefix . 'sp_op_workflows', array(
            'name'        => $name,
            'description' => $description,
            'created_by'  => $by,
            'created_at'  => current_time( 'mysql' ),
        ) );
        $id = $wpdb->insert_id;
    }

    // Replace steps
    $wpdb->delete( $wpdb->prefix . 'sp_op_workflow_steps', array( 'workflow_id' => $id ) );

    $titles  = isset( $_POST['step_title'] )      ? (array) $_POST['step_title']      : array();
    $offsets = isset( $_POST['step_offset'] )     ? (array) $_POST['step_offset']     : array();
    $roles   = isset( $_POST['step_role'] )       ? (array) $_POST['step_role']       : array();

    foreach ( $titles as $i => $title ) {
        $title = sanitize_text_field( $title );
        if ( $title === '' ) continue;
        $wpdb->insert( $wpdb->prefix . 'sp_op_workflow_steps', array(
            'workflow_id'     => $id,
            'sort_order'      => $i + 1,
            'title'           => $title,
            'due_offset_days' => (int) ( $offsets[ $i ] ?? 0 ),
            'assigned_role'   => sanitize_key( $roles[ $i ] ?? '' ),
        ) );
    }

    wp_redirect( home_url( '/sp-app/?view=operations-workflow&id=' . $id . '&saved=1' ) ); exit;
}

// ── Delete workflow template ──────────────────────────────────────────────────

function sp_ops_delete_workflow( $id ) {
    global $wpdb;
    if ( ! sp_is_admin_member() ) {
        wp_redirect( home_url( '/sp-app/?view=operations' ) ); exit;
    }
    $wpdb->delete( $wpdb->prefix . 'sp_op_workflow_steps', array( 'workflow_id' => $id ) );
    $wpdb->delete( $wpdb->prefix . 'sp_op_workflows',      array( 'id' => $id ) );
    wp_redirect( home_url( '/sp-app/?view=operations&deleted=1' ) ); exit;
}

// ── Start a workflow instance ─────────────────────────────────────────────────

function sp_ops_start_workflow( $id ) {
    global $wpdb;

    $workflow_id  = (int) ( $_POST['workflow_id']  ?? 0 );
    $record_type  = sanitize_key( $_POST['record_type']  ?? '' );
    $record_id    = (int) ( $_POST['record_id']    ?? 0 );
    $start_date   = sanitize_text_field( $_POST['start_date'] ?? current_time( 'Y-m-d' ) );

    if ( ! $workflow_id || ! $record_type || ! $record_id ) {
        wp_redirect( home_url( '/sp-app/?view=operations&error=missing' ) ); exit;
    }

    $member = sp_get_current_team_member();
    $by     = $member ? (int) $member->id : 0;

    // Create instance
    $wpdb->insert( $wpdb->prefix . 'sp_op_instances', array(
        'workflow_id' => $workflow_id,
        'record_type' => $record_type,
        'record_id'   => $record_id,
        'started_by'  => $by,
        'started_at'  => current_time( 'mysql' ),
        'status'      => 'active',
    ) );
    $instance_id = $wpdb->insert_id;

    // Load team to assign by role
    $team_by_role = array();
    $team = $wpdb->get_results( "SELECT id, role FROM {$wpdb->prefix}sp_team WHERE status='active'" );
    foreach ( $team as $m ) {
        if ( ! isset( $team_by_role[ $m->role ] ) ) $team_by_role[ $m->role ] = (int) $m->id;
    }

    // Create tasks for each step
    $steps = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sp_op_workflow_steps WHERE workflow_id=%d ORDER BY sort_order ASC",
        $workflow_id
    ) );

    $base = strtotime( $start_date );
    foreach ( $steps as $step ) {
        $due = $step->due_offset_days > 0
            ? date( 'Y-m-d', strtotime( "+{$step->due_offset_days} days", $base ) )
            : null;
        $assigned = $step->assigned_role && isset( $team_by_role[ $step->assigned_role ] )
            ? $team_by_role[ $step->assigned_role ]
            : $by;

        $wpdb->insert( $wpdb->prefix . 'sp_tasks', array(
            'record_type'    => $record_type,
            'record_id'      => $record_id,
            'title'          => sanitize_text_field( $step->title ),
            'assigned_to'    => $assigned,
            'due_date'       => $due,
            'status'         => 'open',
            'created_by'     => $by,
            'created_at'     => current_time( 'mysql' ),
            'op_instance_id' => $instance_id,
        ) );
    }

    // Log activity
    if ( function_exists( 'sp_log_activity' ) ) {
        $wf = $wpdb->get_var( $wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}sp_op_workflows WHERE id=%d", $workflow_id
        ) );
        sp_log_activity( $record_type, $record_id, 'workflow_started', $wf, $by );
    }

    wp_redirect( home_url( "/sp-app/?view=operations&started={$instance_id}" ) ); exit;
}

// ── Cancel a workflow instance ────────────────────────────────────────────────

function sp_ops_cancel_instance( $id ) {
    global $wpdb;
    if ( ! sp_is_admin_member() ) {
        wp_redirect( home_url( '/sp-app/?view=operations' ) ); exit;
    }
    $wpdb->update( $wpdb->prefix . 'sp_op_instances',
        array( 'status' => 'cancelled' ),
        array( 'id' => $id )
    );
    // Mark remaining open tasks from this instance as cancelled
    $wpdb->query( $wpdb->prepare(
        "UPDATE {$wpdb->prefix}sp_tasks SET status='cancelled' WHERE op_instance_id=%d AND status='open'",
        $id
    ) );
    wp_redirect( home_url( '/sp-app/?view=operations&cancelled=1' ) ); exit;
}

// ══ ONBOARDING TRACKER ═══════════════════════════════════════════════════════

// ── Save onboarding template ──────────────────────────────────────────────────

function sp_ob_save_template( $id ) {
    global $wpdb;
    if ( ! sp_is_admin_member() ) {
        wp_redirect( home_url( '/sp-app/?view=onboarding' ) ); exit;
    }
    $name        = sanitize_text_field( $_POST['name'] ?? '' );
    $description = sanitize_textarea_field( $_POST['description'] ?? '' );
    $member      = sp_get_current_team_member();
    $by          = $member ? (int) $member->id : 0;

    if ( $id ) {
        $wpdb->update( $wpdb->prefix . 'sp_ob_templates',
            array( 'name' => $name, 'description' => $description ),
            array( 'id' => $id )
        );
    } else {
        $wpdb->insert( $wpdb->prefix . 'sp_ob_templates', array(
            'name'        => $name,
            'description' => $description,
            'created_by'  => $by,
            'created_at'  => current_time( 'mysql' ),
        ) );
        $id = $wpdb->insert_id;
    }

    $wpdb->delete( $wpdb->prefix . 'sp_ob_items', array( 'template_id' => $id ) );
    $titles = (array) ( $_POST['item_title'] ?? array() );
    $descs  = (array) ( $_POST['item_desc']  ?? array() );
    foreach ( $titles as $i => $title ) {
        $title = sanitize_text_field( $title );
        if ( $title === '' ) continue;
        $wpdb->insert( $wpdb->prefix . 'sp_ob_items', array(
            'template_id' => $id,
            'sort_order'  => $i + 1,
            'title'       => $title,
            'description' => sanitize_textarea_field( $descs[ $i ] ?? '' ),
        ) );
    }
    wp_redirect( home_url( '/sp-app/?view=onboarding&saved=1' ) ); exit;
}

// ── Delete onboarding template ────────────────────────────────────────────────

function sp_ob_delete_template( $id ) {
    global $wpdb;
    if ( ! sp_is_admin_member() ) {
        wp_redirect( home_url( '/sp-app/?view=onboarding' ) ); exit;
    }
    $wpdb->delete( $wpdb->prefix . 'sp_ob_items',     array( 'template_id' => $id ) );
    $wpdb->delete( $wpdb->prefix . 'sp_ob_templates', array( 'id' => $id ) );
    wp_redirect( home_url( '/sp-app/?view=onboarding&deleted=1' ) ); exit;
}

// ── Assign checklist to a record ─────────────────────────────────────────────

function sp_ob_assign( $id ) {
    global $wpdb;
    $template_id = (int) ( $_POST['template_id'] ?? 0 );
    $record_type = sanitize_key( $_POST['record_type'] ?? '' );
    $record_id   = (int) ( $_POST['record_id'] ?? 0 );
    if ( ! $template_id || ! $record_type || ! $record_id ) {
        wp_redirect( home_url( '/sp-app/?view=onboarding&error=missing' ) ); exit;
    }
    $member = sp_get_current_team_member();
    $by     = $member ? (int) $member->id : 0;
    $wpdb->insert( $wpdb->prefix . 'sp_ob_instances', array(
        'template_id' => $template_id,
        'record_type' => $record_type,
        'record_id'   => $record_id,
        'started_by'  => $by,
        'started_at'  => current_time( 'mysql' ),
        'status'      => 'active',
    ) );
    $inst_id = $wpdb->insert_id;
    if ( function_exists( 'sp_log_activity' ) ) {
        $tname = $wpdb->get_var( $wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}sp_ob_templates WHERE id=%d", $template_id
        ) );
        sp_log_activity( $record_type, $record_id, 'onboarding_started', $tname, $by );
    }
    $redirect_view = $record_type === 'company' ? 'companies' : 'contacts';
    wp_redirect( home_url( "/sp-app/?view={$redirect_view}&action=view&id={$record_id}&ob_started=1" ) ); exit;
}

// ── Check / uncheck an item ───────────────────────────────────────────────────

function sp_ob_check_item( $id ) {
    global $wpdb;
    $instance_id = (int) ( $_POST['instance_id'] ?? 0 );
    $item_id     = (int) ( $_POST['item_id'] ?? 0 );
    if ( ! $instance_id || ! $item_id ) wp_die( 'Invalid' );
    $member = sp_get_current_team_member();
    $by     = $member ? (int) $member->id : 0;
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}sp_ob_progress WHERE instance_id=%d AND item_id=%d",
        $instance_id, $item_id
    ) );
    if ( ! $exists ) {
        $wpdb->insert( $wpdb->prefix . 'sp_ob_progress', array(
            'instance_id' => $instance_id,
            'item_id'     => $item_id,
            'checked_by'  => $by,
            'checked_at'  => current_time( 'mysql' ),
        ) );
    }
    $redirect = sanitize_text_field( $_POST['redirect'] ?? '' );
    wp_redirect( $redirect ?: home_url( '/sp-app/?view=onboarding' ) ); exit;
}

function sp_ob_uncheck_item( $id ) {
    global $wpdb;
    $instance_id = (int) ( $_POST['instance_id'] ?? 0 );
    $item_id     = (int) ( $_POST['item_id'] ?? 0 );
    if ( ! $instance_id || ! $item_id ) wp_die( 'Invalid' );
    $wpdb->delete( $wpdb->prefix . 'sp_ob_progress',
        array( 'instance_id' => $instance_id, 'item_id' => $item_id )
    );
    $redirect = sanitize_text_field( $_POST['redirect'] ?? '' );
    wp_redirect( $redirect ?: home_url( '/sp-app/?view=onboarding' ) ); exit;
}

// ── Complete an instance ──────────────────────────────────────────────────────

function sp_ob_complete_instance( $id ) {
    global $wpdb;
    $wpdb->update( $wpdb->prefix . 'sp_ob_instances',
        array( 'status' => 'completed' ),
        array( 'id' => $id )
    );
    $redirect = sanitize_text_field( $_POST['redirect'] ?? '' );
    wp_redirect( $redirect ?: home_url( '/sp-app/?view=onboarding' ) ); exit;
}

// ── Record widget (injected on contact + company view pages) ──────────────────

function sp_ob_record_widget( $record_id ) {
    global $wpdb;
    $view        = sanitize_key( $_GET['view'] ?? '' );
    $record_type = ( $view === 'companies' ) ? 'company' : 'contact';

    $instances = $wpdb->get_results( $wpdb->prepare(
        "SELECT i.*, t.name AS template_name,
            COUNT(DISTINCT it.id)          AS total_items,
            COUNT(DISTINCT p.item_id)      AS done_items
         FROM {$wpdb->prefix}sp_ob_instances i
         JOIN  {$wpdb->prefix}sp_ob_templates t  ON t.id = i.template_id
         LEFT JOIN {$wpdb->prefix}sp_ob_items it ON it.template_id = i.template_id
         LEFT JOIN {$wpdb->prefix}sp_ob_progress p ON p.instance_id = i.id
         WHERE i.record_id=%d AND i.record_type=%s AND i.status='active'
         GROUP BY i.id",
        $record_id, $record_type
    ) );

    $templates = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sp_ob_templates ORDER BY name ASC" );

    $redirect = esc_url( home_url( "/sp-app/?view={$view}&action=view&id={$record_id}" ) );
    ?>
    <div class="sp-card" style="margin-top:20px;">
        <div class="sp-card-header">
            <h2>Onboarding</h2>
            <?php if ( ! empty( $templates ) ) : ?>
            <button type="button" class="sp-btn sp-btn-secondary sp-btn-sm" onclick="document.getElementById('sp-ob-assign-<?php echo $record_id; ?>').style.display='block';this.style.display='none';">+ Assign Checklist</button>
            <?php endif; ?>
        </div>

        <div id="sp-ob-assign-<?php echo $record_id; ?>" style="display:none;padding:12px 0 4px;">
            <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                <input type="hidden" name="sp_type"     value="ob_assign">
                <input type="hidden" name="sp_id"       value="0">
                <input type="hidden" name="record_type" value="<?php echo esc_attr( $record_type ); ?>">
                <input type="hidden" name="record_id"   value="<?php echo (int) $record_id; ?>">
                <select name="template_id" required style="flex:2;min-width:180px;">
                    <option value="">— Choose checklist —</option>
                    <?php foreach ( $templates as $t ) : ?>
                        <option value="<?php echo (int)$t->id; ?>"><?php echo esc_html( $t->name ); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="sp-btn sp-btn-primary sp-btn-sm">Start</button>
                <button type="button" class="sp-btn sp-btn-ghost sp-btn-sm" onclick="document.getElementById('sp-ob-assign-<?php echo $record_id; ?>').style.display='none';document.querySelector('[onclick*=\'sp-ob-assign-<?php echo $record_id; ?>\']').style.display='';">Cancel</button>
            </form>
        </div>

        <?php if ( empty( $instances ) && empty( $templates ) ) : ?>
            <p class="sp-empty" style="padding:12px 0;">No onboarding checklists set up yet. <a href="<?php echo esc_url( home_url( '/sp-app/?view=onboarding' ) ); ?>" class="sp-link">Create one →</a></p>
        <?php elseif ( empty( $instances ) ) : ?>
            <p class="sp-empty" style="padding:12px 0;">No active checklists for this <?php echo $record_type; ?>.</p>
        <?php else : ?>
            <?php foreach ( $instances as $inst ) :
                $total = (int) $inst->total_items;
                $done  = (int) $inst->done_items;
                $pct   = $total > 0 ? round( $done / $total * 100 ) : 0;
                $items = $wpdb->get_results( $wpdb->prepare(
                    "SELECT i.*, p.id AS checked_id
                     FROM {$wpdb->prefix}sp_ob_items i
                     LEFT JOIN {$wpdb->prefix}sp_ob_progress p ON p.item_id=i.id AND p.instance_id=%d
                     WHERE i.template_id=%d ORDER BY i.sort_order ASC",
                    $inst->id, $inst->template_id
                ) );
            ?>
            <div style="padding:12px 0;<?php echo count($instances) > 1 ? 'border-top:1px solid var(--sp-border,#e5e7eb);' : ''; ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                    <strong style="font-size:.9rem;"><?php echo esc_html( $inst->template_name ); ?></strong>
                    <span style="font-size:.8rem;color:var(--sp-muted);"><?php echo $done; ?>/<?php echo $total; ?> &nbsp;
                        <strong style="color:<?php echo $pct === 100 ? '#16a34a' : 'var(--sp-primary,#2563eb)'; ?>;"><?php echo $pct; ?>%</strong>
                    </span>
                </div>
                <div style="background:var(--sp-border,#e5e7eb);border-radius:99px;height:6px;margin-bottom:14px;">
                    <div style="width:<?php echo $pct; ?>%;height:6px;border-radius:99px;background:<?php echo $pct === 100 ? '#16a34a' : 'var(--sp-primary,#2563eb)'; ?>;transition:width .3s;"></div>
                </div>
                <?php foreach ( $items as $item ) :
                    $checked = ! empty( $item->checked_id );
                    $type    = $checked ? 'ob_uncheck' : 'ob_check';
                ?>
                <div style="display:flex;align-items:flex-start;gap:10px;padding:6px 0;border-bottom:1px solid var(--sp-border,#f3f4f6);">
                    <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" style="margin:0;padding:0;">
                        <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                        <input type="hidden" name="sp_type"     value="<?php echo $type; ?>">
                        <input type="hidden" name="sp_id"       value="0">
                        <input type="hidden" name="instance_id" value="<?php echo (int)$inst->id; ?>">
                        <input type="hidden" name="item_id"     value="<?php echo (int)$item->id; ?>">
                        <input type="hidden" name="redirect"    value="<?php echo $redirect; ?>">
                        <button type="submit" style="width:20px;height:20px;border-radius:4px;border:2px solid <?php echo $checked ? '#16a34a' : 'var(--sp-border,#d1d5db)'; ?>;background:<?php echo $checked ? '#16a34a' : 'transparent'; ?>;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;flex-shrink:0;margin-top:1px;" title="<?php echo $checked ? 'Uncheck' : 'Check off'; ?>">
                            <?php if ( $checked ) : ?><svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><?php endif; ?>
                        </button>
                    </form>
                    <div style="flex:1;">
                        <div style="font-size:.85rem;<?php echo $checked ? 'text-decoration:line-through;color:var(--sp-muted);' : ''; ?>"><?php echo esc_html( $item->title ); ?></div>
                        <?php if ( $item->description ) : ?><div style="font-size:.75rem;color:var(--sp-muted);"><?php echo esc_html( $item->description ); ?></div><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if ( $pct === 100 ) : ?>
                <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" style="margin-top:10px;">
                    <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                    <input type="hidden" name="sp_type"  value="ob_complete">
                    <input type="hidden" name="sp_id"    value="<?php echo (int)$inst->id; ?>">
                    <input type="hidden" name="redirect" value="<?php echo $redirect; ?>">
                    <button type="submit" class="sp-btn sp-btn-primary sp-btn-sm" style="background:#16a34a;border-color:#16a34a;">✓ Mark Onboarding Complete</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}
