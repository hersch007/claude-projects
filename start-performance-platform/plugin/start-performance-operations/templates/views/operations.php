<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$is_admin = sp_is_admin_member();
$member   = sp_get_current_team_member();
$my_id    = $member ? (int) $member->id : 0;
$action   = sanitize_key( $_GET['action'] ?? '' );

// ── New / edit workflow form ──────────────────────────────────────────────────

if ( $is_admin && in_array( $action, array( 'new', 'edit' ), true ) ) {
    $wf    = null;
    $steps = array();
    $edit_id = (int) ( $_GET['id'] ?? 0 );
    if ( $action === 'edit' && $edit_id ) {
        $wf    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_op_workflows WHERE id=%d", $edit_id ) );
        $steps = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sp_op_workflow_steps WHERE workflow_id=%d ORDER BY sort_order ASC", $edit_id
        ) );
    }
    $base_url = home_url( '/sp-app/' );
    ?>
    <div class="sp-view-header">
        <h1><?php echo $wf ? 'Edit Workflow' : 'New Workflow'; ?></h1>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=operations' ) ); ?>" class="sp-btn sp-btn-secondary">← Back</a>
    </div>

    <div class="sp-card sp-form-card">
        <form method="post" action="<?php echo esc_url( $base_url ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="op_workflow">
            <input type="hidden" name="sp_id"   value="<?php echo $wf ? (int) $wf->id : 0; ?>">

            <div class="sp-field">
                <label>Workflow Name <span class="sp-required">*</span></label>
                <input type="text" name="name" value="<?php echo esc_attr( $wf->name ?? '' ); ?>" required placeholder="e.g. New Client Onboarding">
            </div>
            <div class="sp-field">
                <label>Description</label>
                <textarea name="description" rows="2" placeholder="Optional — shown when choosing a workflow to start"><?php echo esc_textarea( $wf->description ?? '' ); ?></textarea>
            </div>

            <h3 style="margin:24px 0 12px;font-size:.9rem;font-weight:700;color:var(--sp-text);">Steps</h3>
            <p class="sp-hint" style="margin-bottom:12px;">Each step becomes a task when the workflow is started. Due offset is days from the start date.</p>

            <div id="sp-ops-steps">
                <?php
                $default_steps = empty( $steps ) ? array(
                    array( 'title' => '', 'due_offset_days' => 0, 'assigned_role' => '' ),
                ) : $steps;
                foreach ( $default_steps as $i => $s ) : ?>
                <div class="sp-ops-step" style="display:flex;gap:10px;align-items:center;margin-bottom:8px;">
                    <span class="sp-ops-handle" style="color:var(--sp-muted);cursor:grab;font-size:1.1rem;">⠿</span>
                    <input type="text" name="step_title[]" value="<?php echo esc_attr( is_array($s) ? ($s['title']??'') : $s->title ); ?>"
                           placeholder="Step title" style="flex:3;" required>
                    <input type="number" name="step_offset[]" value="<?php echo is_array($s) ? ($s['due_offset_days']??0) : (int)$s->due_offset_days; ?>"
                           min="0" placeholder="Day +" style="flex:.6;min-width:70px;" title="Due offset in days">
                    <select name="step_role[]" style="flex:1.2;">
                        <option value="">Any role</option>
                        <?php foreach ( array( 'admin', 'manager', 'agent' ) as $role ) :
                            $sel = ( is_array($s) ? ($s['assigned_role']??'') : $s->assigned_role ) === $role ? 'selected' : '';
                        ?>
                            <option value="<?php echo $role; ?>" <?php echo $sel; ?>><?php echo ucfirst($role); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="sp-btn sp-btn-ghost sp-btn-sm sp-ops-remove-step" title="Remove step" style="padding:4px 8px;">✕</button>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="button" id="sp-ops-add-step" class="sp-btn sp-btn-secondary sp-btn-sm" style="margin-bottom:20px;">+ Add Step</button>

            <div class="sp-form-actions">
                <?php if ( $wf ) : ?>
                    <a href="<?php echo esc_url( home_url( '/sp-app/?sp_type=op_workflow&sp_action=delete&sp_id=' . (int)$wf->id . '&sp_nonce=' . wp_create_nonce('sp_form') ) ); ?>"
                       class="sp-btn sp-btn-danger" data-sp-confirm="Delete this workflow template?">Delete</a>
                <?php endif; ?>
                <button type="submit" class="sp-btn sp-btn-primary">Save Workflow</button>
            </div>
        </form>
    </div>

    <script>
    (function(){
        var container = document.getElementById('sp-ops-steps');
        var stepTpl = function() {
            var d = document.createElement('div');
            d.className = 'sp-ops-step';
            d.style.cssText = 'display:flex;gap:10px;align-items:center;margin-bottom:8px;';
            d.innerHTML = '<span style="color:var(--sp-muted);font-size:1.1rem;">⠿</span>'
                + '<input type="text" name="step_title[]" placeholder="Step title" style="flex:3;" required>'
                + '<input type="number" name="step_offset[]" value="0" min="0" placeholder="Day +" style="flex:.6;min-width:70px;" title="Due offset in days">'
                + '<select name="step_role[]" style="flex:1.2;"><option value="">Any role</option><option value="admin">Admin</option><option value="manager">Manager</option><option value="agent">Agent</option></select>'
                + '<button type="button" class="sp-btn sp-btn-ghost sp-btn-sm sp-ops-remove-step" style="padding:4px 8px;">✕</button>';
            return d;
        };
        document.getElementById('sp-ops-add-step').addEventListener('click', function(){
            container.appendChild( stepTpl() );
        });
        container.addEventListener('click', function(e){
            if ( e.target.classList.contains('sp-ops-remove-step') ) {
                var steps = container.querySelectorAll('.sp-ops-step');
                if ( steps.length > 1 ) e.target.closest('.sp-ops-step').remove();
            }
        });
    })();
    </script>
    <?php
    return;
}

// ── Start workflow modal target (action=start) ────────────────────────────────

if ( $action === 'start' ) {
    $workflows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sp_op_workflows ORDER BY name ASC" );
    $contacts  = $wpdb->get_results( "SELECT id, CONCAT(first_name,' ',last_name) AS name FROM {$wpdb->prefix}sp_contacts ORDER BY first_name,last_name LIMIT 200" );
    $companies = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sp_companies ORDER BY name LIMIT 200" );
    ?>
    <div class="sp-view-header">
        <h1>Start a Workflow</h1>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=operations' ) ); ?>" class="sp-btn sp-btn-secondary">← Back</a>
    </div>
    <div class="sp-card sp-form-card">
        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="op_start_workflow">
            <input type="hidden" name="sp_id"   value="0">

            <div class="sp-field">
                <label>Workflow Template <span class="sp-required">*</span></label>
                <select name="workflow_id" required>
                    <option value="">— choose —</option>
                    <?php foreach ( $workflows as $wf ) : ?>
                        <option value="<?php echo (int)$wf->id; ?>"><?php echo esc_html( $wf->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="sp-field">
                <label>Assign To</label>
                <select name="record_type" id="sp-ops-rtype">
                    <option value="contact">Contact</option>
                    <option value="company">Company</option>
                </select>
            </div>

            <div class="sp-field" id="sp-ops-contact-wrap">
                <label>Contact <span class="sp-required">*</span></label>
                <select name="record_id" id="sp-ops-contact-sel">
                    <option value="">— choose —</option>
                    <?php foreach ( $contacts as $c ) : ?>
                        <option value="<?php echo (int)$c->id; ?>"><?php echo esc_html( trim($c->name) ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sp-field" id="sp-ops-company-wrap" style="display:none;">
                <label>Company <span class="sp-required">*</span></label>
                <select name="record_id_co" id="sp-ops-company-sel">
                    <option value="">— choose —</option>
                    <?php foreach ( $companies as $c ) : ?>
                        <option value="<?php echo (int)$c->id; ?>"><?php echo esc_html( $c->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="sp-field">
                <label>Start Date</label>
                <input type="date" name="start_date" value="<?php echo esc_attr( current_time('Y-m-d') ); ?>">
                <span class="sp-hint">Task due dates are calculated from this date using each step's day offset.</span>
            </div>

            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary">Start Workflow</button>
            </div>
        </form>
    </div>
    <script>
    (function(){
        var rtype = document.getElementById('sp-ops-rtype');
        var cw = document.getElementById('sp-ops-contact-wrap');
        var cow = document.getElementById('sp-ops-company-wrap');
        var cs = document.getElementById('sp-ops-contact-sel');
        var cos = document.getElementById('sp-ops-company-sel');
        rtype.addEventListener('change', function(){
            var co = this.value === 'company';
            cw.style.display  = co ? 'none' : '';
            cow.style.display = co ? '' : 'none';
            cs.name  = co ? 'record_id_contact' : 'record_id';
            cos.name = co ? 'record_id' : 'record_id_co';
        });
    })();
    </script>
    <?php
    return;
}

// ── Main list: Templates + Active Instances ───────────────────────────────────

$workflows = $wpdb->get_results(
    "SELECT w.*, COUNT(DISTINCT s.id) AS step_count
     FROM {$wpdb->prefix}sp_op_workflows w
     LEFT JOIN {$wpdb->prefix}sp_op_workflow_steps s ON s.workflow_id = w.id
     GROUP BY w.id ORDER BY w.name ASC"
);

$instances = $wpdb->get_results(
    "SELECT i.*,
        w.name AS workflow_name,
        COALESCE( CONCAT(c.first_name,' ',c.last_name), co.name, '' ) AS record_name,
        COUNT(t.id)                          AS total_tasks,
        SUM( t.status = 'done' )             AS done_tasks,
        SUM( t.status = 'open' AND t.due_date < CURDATE() ) AS overdue_tasks
     FROM {$wpdb->prefix}sp_op_instances i
     JOIN  {$wpdb->prefix}sp_op_workflows w  ON w.id = i.workflow_id
     LEFT JOIN {$wpdb->prefix}sp_contacts  c  ON i.record_type='contact' AND i.record_id=c.id
     LEFT JOIN {$wpdb->prefix}sp_companies co ON i.record_type='company' AND i.record_id=co.id
     LEFT JOIN {$wpdb->prefix}sp_tasks t       ON t.op_instance_id = i.id AND t.status != 'cancelled'
     WHERE i.status='active'
     GROUP BY i.id
     ORDER BY i.started_at DESC"
);

// Batch-load every task for the visible instances (grouped below), so each
// workflow's checklist can render inline without a query per row.
$tasks_by_instance = array();
if ( ! empty( $instances ) ) {
    $instance_ids = wp_list_pluck( $instances, 'id' );
    $placeholders = implode( ',', array_fill( 0, count( $instance_ids ), '%d' ) );
    $task_rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sp_tasks WHERE op_instance_id IN ($placeholders) AND status != 'cancelled' ORDER BY due_date ASC, id ASC",
        $instance_ids
    ) );
    foreach ( $task_rows as $t ) {
        $tasks_by_instance[ (int) $t->op_instance_id ][] = $t;
    }
}
$toggle_nonce = wp_create_nonce( 'sp_toggle_task' );
?>

<div class="sp-view-header">
    <h1>Workflows</h1>
    <div style="display:flex;gap:8px;">
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=operations&action=start' ) ); ?>" class="sp-btn sp-btn-primary">▶ Start Workflow</a>
        <?php if ( $is_admin ) : ?>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=operations&action=new' ) ); ?>" class="sp-btn sp-btn-secondary">+ New Template</a>
        <?php endif; ?>
    </div>
</div>

<?php if ( isset( $_GET['saved'] ) )    : ?><div class="sp-notice sp-notice-success">Workflow saved.</div><?php endif; ?>
<?php if ( isset( $_GET['deleted'] ) )  : ?><div class="sp-notice sp-notice-success">Workflow deleted.</div><?php endif; ?>
<?php if ( isset( $_GET['started'] ) )  : ?><div class="sp-notice sp-notice-success">Workflow started — tasks have been created.</div><?php endif; ?>
<?php if ( isset( $_GET['cancelled'] ) ): ?><div class="sp-notice sp-notice-info">Workflow instance cancelled.</div><?php endif; ?>

<?php // ── Active instances ──────────────────────────────────────────────────── ?>

<?php if ( ! empty( $instances ) ) : ?>
<div class="sp-card sp-table-card" style="margin-bottom:24px;">
    <div class="sp-card-header"><h2>Active Workflows</h2></div>
    <table class="sp-table">
        <thead>
            <tr>
                <th>Workflow</th>
                <th>Assigned To</th>
                <th>Progress</th>
                <th>Started</th>
                <?php if ( $is_admin ) : ?><th></th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $instances as $inst ) :
            $total    = (int) $inst->total_tasks;
            $done     = (int) $inst->done_tasks;
            $overdue  = (int) $inst->overdue_tasks;
            $pct      = $total > 0 ? round( $done / $total * 100 ) : 0;
            $bar_col  = $overdue > 0 ? '#f59e0b' : '#10b981';
            $view_link = $inst->record_type === 'company'
                ? home_url( '/sp-app/?view=companies&action=view&id=' . $inst->record_id )
                : home_url( '/sp-app/?view=contacts&action=view&id=' . $inst->record_id );
        ?>
            <tr>
                <td>
                    <strong><?php echo esc_html( $inst->workflow_name ); ?></strong>
                    <?php if ( $overdue ) : ?>
                        <span class="sp-badge sp-badge-urgent" style="margin-left:6px;"><?php echo $overdue; ?> overdue</span>
                    <?php endif; ?>
                </td>
                <td class="sp-muted">
                    <a href="<?php echo esc_url( $view_link ); ?>" class="sp-link"><?php echo esc_html( $inst->record_name ?: '—' ); ?></a>
                    <span style="font-size:.75rem;opacity:.7;">(<?php echo esc_html( $inst->record_type ); ?>)</span>
                </td>
                <td style="min-width:140px;">
                    <div style="display:flex;align-items:center;gap:8px;" id="sp-ops-progress-<?php echo (int) $inst->id; ?>">
                        <div style="flex:1;background:var(--sp-border,#e5e7eb);border-radius:99px;height:6px;">
                            <div class="sp-ops-progress-bar" style="width:<?php echo $pct; ?>%;height:6px;border-radius:99px;background:<?php echo $bar_col; ?>;transition:width .3s;"></div>
                        </div>
                        <span class="sp-ops-progress-count" style="font-size:.78rem;color:var(--sp-muted);"><?php echo $done; ?>/<?php echo $total; ?></span>
                    </div>
                </td>
                <td class="sp-muted"><?php echo esc_html( date( 'M j, Y', strtotime( $inst->started_at ) ) ); ?></td>
                <?php if ( $is_admin ) : ?>
                <td>
                    <a href="<?php echo esc_url( home_url( '/sp-app/?sp_type=op_cancel_instance&sp_action=delete&sp_id=' . (int)$inst->id . '&sp_nonce=' . wp_create_nonce('sp_form') ) ); ?>"
                       class="sp-btn sp-btn-ghost sp-btn-sm"
                       data-sp-confirm="Cancel this workflow and mark remaining tasks cancelled?"
                       style="color:var(--sp-muted);">Cancel</a>
                </td>
                <?php endif; ?>
            </tr>
            <tr class="sp-ops-checklist-row">
                <td colspan="<?php echo $is_admin ? 5 : 4; ?>" style="padding:0;background:var(--sp-bg,#f9fafb);border-top:none;">
                    <div style="padding:6px 20px 16px;">
                        <?php if ( empty( $tasks_by_instance[ $inst->id ] ) ) : ?>
                            <p class="sp-empty" style="margin:0;font-size:.82rem;">No steps for this workflow.</p>
                        <?php else : foreach ( $tasks_by_instance[ $inst->id ] as $tk ) :
                            $tk_overdue = $tk->status === 'open' && $tk->due_date && strtotime( $tk->due_date ) < strtotime( 'today' );
                        ?>
                            <div class="sp-task-item<?php echo $tk->status === 'done' ? ' sp-task-done' : ''; ?>" id="sp-ops-task-<?php echo (int) $tk->id; ?>" data-instance="<?php echo (int) $inst->id; ?>">
                                <button type="button" class="sp-task-check sp-ops-task-toggle" data-task-id="<?php echo (int) $tk->id; ?>">
                                    <?php echo $tk->status === 'done' ? '✓' : ''; ?>
                                </button>
                                <div class="sp-task-body">
                                    <span class="sp-task-title"><?php echo esc_html( $tk->title ); ?></span>
                                    <span class="sp-task-meta">
                                        <?php if ( $tk->due_date ) : ?>
                                            <span style="<?php echo $tk_overdue ? 'color:#dc2626;font-weight:600;' : ''; ?>">Due <?php echo esc_html( date( 'M j', strtotime( $tk->due_date ) ) ); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
(function(){
    var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    var nonce   = <?php echo wp_json_encode( $toggle_nonce ); ?>;
    document.querySelectorAll('.sp-ops-task-toggle').forEach(function(btn){
        btn.addEventListener('click', function(){
            var item = btn.closest('.sp-task-item');
            var instanceId = item.getAttribute('data-instance');
            btn.disabled = true;
            var fd = new FormData();
            fd.append('action', 'sp_toggle_task');
            fd.append('task_id', btn.dataset.taskId);
            fd.append('nonce', nonce);
            fetch(ajaxUrl, { method: 'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    btn.disabled = false;
                    if ( ! data.success ) { alert('Could not update task.'); return; }
                    var isDone = data.data.status === 'done';
                    item.classList.toggle('sp-task-done', isDone);
                    btn.textContent = isDone ? '✓' : '';

                    // Update this instance's progress bar/count without a page reload
                    var progress = document.getElementById('sp-ops-progress-' + instanceId);
                    if ( progress ) {
                        var countEl = progress.querySelector('.sp-ops-progress-count');
                        var barEl   = progress.querySelector('.sp-ops-progress-bar');
                        var parts   = countEl.textContent.split('/');
                        var total   = parseInt(parts[1], 10) || 0;
                        var done    = 0;
                        document.querySelectorAll('.sp-task-item[data-instance="' + instanceId + '"]').forEach(function(t){
                            if ( t.classList.contains('sp-task-done') ) done++;
                        });
                        countEl.textContent = done + '/' + total;
                        if ( barEl && total > 0 ) barEl.style.width = Math.round(done / total * 100) + '%';
                    }
                })
                .catch(function(){
                    btn.disabled = false;
                    alert('Request failed.');
                });
        });
    });
})();
</script>
<?php endif; ?>

<?php // ── Workflow templates ────────────────────────────────────────────────── ?>

<div class="sp-card sp-table-card">
    <div class="sp-card-header">
        <h2>Workflow Templates</h2>
    </div>
    <?php if ( empty( $workflows ) ) : ?>
        <p class="sp-empty">No workflow templates yet.
            <?php if ( $is_admin ) : ?>
                <a href="<?php echo esc_url( home_url( '/sp-app/?view=operations&action=new' ) ); ?>" class="sp-link">Create your first one →</a>
            <?php endif; ?>
        </p>
    <?php else : ?>
        <table class="sp-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Steps</th>
                    <th>Description</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $workflows as $wf ) : ?>
                <tr>
                    <td><strong><?php echo esc_html( $wf->name ); ?></strong></td>
                    <td><span class="sp-badge"><?php echo (int)$wf->step_count; ?> step<?php echo $wf->step_count != 1 ? 's' : ''; ?></span></td>
                    <td class="sp-muted"><?php echo esc_html( $wf->description ?: '—' ); ?></td>
                    <td style="text-align:right;white-space:nowrap;">
                        <a href="<?php echo esc_url( home_url( '/sp-app/?view=operations&action=start&wf=' . (int)$wf->id ) ); ?>"
                           class="sp-btn sp-btn-primary sp-btn-sm">▶ Start</a>
                        <?php if ( $is_admin ) : ?>
                            <a href="<?php echo esc_url( home_url( '/sp-app/?view=operations&action=edit&id=' . (int)$wf->id ) ); ?>"
                               class="sp-btn sp-btn-secondary sp-btn-sm">Edit</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
