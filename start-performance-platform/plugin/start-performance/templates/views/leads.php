<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$action = sanitize_key( isset( $_GET['action'] ) ? $_GET['action'] : 'list' );
$id     = (int) ( isset( $_GET['id'] ) ? $_GET['id'] : 0 );
$saved  = isset( $_GET['saved'] );

// ── View (detail) ─────────────────────────────────────────────────────────────
if ( $action === 'view' && $id ) {
    $lead = $wpdb->get_row( $wpdb->prepare(
        "SELECT l.*, c.first_name, c.last_name, c.email AS contact_email, co.name AS company_name
         FROM {$wpdb->prefix}sp_leads l
         LEFT JOIN {$wpdb->prefix}sp_contacts c ON c.id = l.contact_id
         LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id = l.company_id
         WHERE l.id = %d", $id
    ) );
    if ( ! $lead ) { echo '<p class="sp-empty">Lead not found.</p>'; return; }

    $tab        = sanitize_key( isset( $_GET['tab'] ) ? $_GET['tab'] : 'notes' );
    $team       = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sp_team WHERE status='active' ORDER BY name" );
    $notes      = $wpdb->get_results( $wpdb->prepare( "SELECT n.*, t.name AS author FROM {$wpdb->prefix}sp_notes n LEFT JOIN {$wpdb->prefix}sp_team t ON t.id = n.created_by WHERE n.record_type='lead' AND n.record_id=%d ORDER BY n.created_at DESC", $id ) );
    $emails     = $wpdb->get_results( $wpdb->prepare( "SELECT e.*, t.name AS author FROM {$wpdb->prefix}sp_email_log e LEFT JOIN {$wpdb->prefix}sp_team t ON t.id = e.logged_by WHERE e.record_type='lead' AND e.record_id=%d ORDER BY e.logged_at DESC", $id ) );
    $tasks      = $wpdb->get_results( $wpdb->prepare( "SELECT tk.*, t.name AS assignee_name FROM {$wpdb->prefix}sp_tasks tk LEFT JOIN {$wpdb->prefix}sp_team t ON t.id = tk.assigned_to WHERE tk.record_type='lead' AND tk.record_id=%d ORDER BY tk.status ASC, tk.due_date ASC", $id ) );
    $files      = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_attachments WHERE record_type='lead' AND record_id=%d ORDER BY created_at DESC", $id ) );
    $activity   = $wpdb->get_results( $wpdb->prepare( "SELECT a.*, t.name AS actor FROM {$wpdb->prefix}sp_activity a LEFT JOIN {$wpdb->prefix}sp_team t ON t.id = a.created_by WHERE a.record_type='lead' AND a.record_id=%d ORDER BY a.created_at DESC LIMIT 50", $id ) );

    $open_tasks  = count( array_filter( (array) $tasks, function( $t ) { return $t->status === 'open'; } ) );
    $lead_name   = trim( $lead->first_name . ' ' . $lead->last_name ) ?: ( $lead->contact_email ?: 'Lead #' . $id );
    ?>
    <div class="sp-page-header">
        <h1><?php echo esc_html( $lead_name ); ?></h1>
        <div class="sp-header-actions">
            <?php if ( $saved ) : ?><span class="sp-saved-badge">Saved ✓</span><?php endif; ?>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=leads&action=edit&id=' . $id ) ); ?>" class="sp-btn sp-btn-ghost">Edit</a>
            <a href="<?php echo esc_url( sp_delete_url( 'lead', $id ) ); ?>" data-sp-confirm="Delete this lead?" class="sp-btn sp-btn-danger">Delete</a>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=leads' ) ); ?>" class="sp-btn sp-btn-ghost">&larr; Leads</a>
        </div>
    </div>

    <div class="sp-record-info-card sp-card">
        <div class="sp-record-fields">
            <?php if ( $lead->contact_id ) : ?>
            <div class="sp-record-field"><span class="sp-field-label">Contact</span><span class="sp-field-value"><a href="<?php echo esc_url( home_url( '/sp-app/?view=contacts&action=view&id=' . $lead->contact_id ) ); ?>"><?php echo esc_html( $lead_name ); ?></a></span></div>
            <?php endif; ?>
            <div class="sp-record-field"><span class="sp-field-label">Company</span><span class="sp-field-value"><?php echo $lead->company_id ? '<a href="'.esc_url(home_url('/sp-app/?view=companies&action=view&id='.$lead->company_id)).'">'.esc_html($lead->company_name).'</a>' : '—'; ?></span></div>
            <div class="sp-record-field"><span class="sp-field-label">Source</span><span class="sp-field-value"><?php echo esc_html( $lead->source ?: '—' ); ?></span></div>
            <div class="sp-record-field"><span class="sp-field-label">Status</span><span class="sp-field-value"><span class="sp-badge sp-badge-<?php echo esc_attr($lead->status); ?>"><?php echo esc_html( ucfirst($lead->status) ); ?></span></span></div>
            <div class="sp-record-field"><span class="sp-field-label">Score</span><span class="sp-field-value"><?php echo esc_html( $lead->score ); ?>/100</span></div>
            <div class="sp-record-field"><span class="sp-field-label">Created</span><span class="sp-field-value sp-muted"><?php echo esc_html( date( 'M j, Y', strtotime( $lead->created_at ) ) ); ?></span></div>
        </div>
        <?php if ( $lead->notes ) : ?>
        <div class="sp-record-base-notes"><strong>Notes:</strong> <?php echo nl2br( esc_html( $lead->notes ) ); ?></div>
        <?php endif; ?>
    </div>

    <div class="sp-panel sp-card" style="margin-top:16px">
        <div class="sp-tab-nav">
            <a href="?view=leads&action=view&id=<?php echo $id; ?>&tab=notes" class="sp-tab<?php echo $tab==='notes'?' active':''; ?>">Notes <span class="sp-tab-count"><?php echo count($notes); ?></span></a>
            <a href="?view=leads&action=view&id=<?php echo $id; ?>&tab=emails" class="sp-tab<?php echo $tab==='emails'?' active':''; ?>">Emails <?php if(count($emails)): ?><span class="sp-tab-count"><?php echo count($emails); ?></span><?php endif; ?></a>
            <a href="?view=leads&action=view&id=<?php echo $id; ?>&tab=tasks" class="sp-tab<?php echo $tab==='tasks'?' active':''; ?>">Tasks <?php if($open_tasks): ?><span class="sp-tab-count sp-tab-count-open"><?php echo $open_tasks; ?></span><?php endif; ?></a>
            <a href="?view=leads&action=view&id=<?php echo $id; ?>&tab=files" class="sp-tab<?php echo $tab==='files'?' active':''; ?>">Files <span class="sp-tab-count"><?php echo count($files); ?></span></a>
            <a href="?view=leads&action=view&id=<?php echo $id; ?>&tab=timeline" class="sp-tab<?php echo $tab==='timeline'?' active':''; ?>">Timeline</a>
        </div>

        <?php if ( $tab === 'notes' ) : ?>
        <div class="sp-tab-pane">
            <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="sp-inline-form">
                <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                <input type="hidden" name="sp_type" value="note">
                <input type="hidden" name="sp_id" value="0">
                <input type="hidden" name="record_type" value="lead">
                <input type="hidden" name="record_id" value="<?php echo $id; ?>">
                <textarea name="content" rows="3" placeholder="Add a note…" required></textarea>
                <div class="sp-inline-form-footer">
                    <div class="sp-field-inline"><label>Reminder</label><input type="datetime-local" name="reminder_at"></div>
                    <button type="submit" class="sp-btn sp-btn-primary sp-btn-sm">Add Note</button>
                </div>
            </form>
            <?php if ( empty( $notes ) ) : ?>
                <p class="sp-empty">No notes yet.</p>
            <?php else : foreach ( $notes as $note ) : ?>
                <div class="sp-note-item">
                    <div class="sp-note-body"><?php echo nl2br( esc_html( $note->content ) ); ?></div>
                    <div class="sp-note-meta">
                        <?php echo esc_html( $note->author ?: 'System' ); ?> &middot; <?php echo esc_html( date( 'M j, Y g:ia', strtotime( $note->created_at ) ) ); ?>
                        <?php if ( $note->reminder_at ) : ?> &middot; <span class="sp-reminder">⏰ <?php echo esc_html( date( 'M j g:ia', strtotime( $note->reminder_at ) ) ); ?></span><?php endif; ?>
                        <a href="<?php echo esc_url( sp_delete_url( 'note', $note->id ) ); ?>" data-sp-confirm="Delete note?" class="sp-note-delete">×</a>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>

        <?php elseif ( $tab === 'emails' ) :
            $email_record_type = 'lead'; $email_record_id = $id;
            include SP_PLUGIN_DIR . 'templates/views/partials/email_log.php';

        elseif ( $tab === 'tasks' ) : ?>
        <div class="sp-tab-pane">
            <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="sp-inline-form">
                <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                <input type="hidden" name="sp_type" value="task">
                <input type="hidden" name="sp_id" value="0">
                <input type="hidden" name="record_type" value="lead">
                <input type="hidden" name="record_id" value="<?php echo $id; ?>">
                <input type="text" name="title" placeholder="Task title…" required style="flex:1">
                <div class="sp-inline-form-footer">
                    <div class="sp-field-inline"><label>Assign to</label>
                        <select name="assigned_to"><option value="0">— Anyone —</option>
                        <?php foreach ( $team as $m ) : ?><option value="<?php echo $m->id; ?>"><?php echo esc_html($m->name); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="sp-field-inline"><label>Due</label><input type="date" name="due_date"></div>
                    <button type="submit" class="sp-btn sp-btn-primary sp-btn-sm">Add Task</button>
                </div>
            </form>
            <?php if ( empty( $tasks ) ) : ?>
                <p class="sp-empty">No tasks yet.</p>
            <?php else : foreach ( $tasks as $tk ) : ?>
                <div class="sp-task-item<?php echo $tk->status==='done'?' sp-task-done':''; ?>">
                    <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" style="display:inline">
                        <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                        <input type="hidden" name="sp_type" value="task">
                        <input type="hidden" name="sp_id" value="0">
                        <input type="hidden" name="record_type" value="lead">
                        <input type="hidden" name="record_id" value="<?php echo $id; ?>">
                        <input type="hidden" name="task_id" value="<?php echo $tk->id; ?>">
                        <button type="submit" class="sp-task-check"><?php echo $tk->status==='done' ? '✓' : ''; ?></button>
                    </form>
                    <div class="sp-task-body">
                        <span class="sp-task-title"><?php echo esc_html( $tk->title ); ?></span>
                        <span class="sp-task-meta">
                            <?php if ( $tk->assignee_name ) echo esc_html( $tk->assignee_name ) . ' &middot; '; ?>
                            <?php if ( $tk->due_date ) echo 'Due ' . esc_html( date( 'M j', strtotime( $tk->due_date ) ) ); ?>
                        </span>
                    </div>
                    <a href="<?php echo esc_url( sp_delete_url( 'task', $tk->id ) ); ?>" data-sp-confirm="Delete task?" class="sp-task-delete">×</a>
                </div>
            <?php endforeach; endif; ?>
        </div>

        <?php elseif ( $tab === 'files' ) : ?>
        <div class="sp-tab-pane">
            <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" enctype="multipart/form-data" class="sp-inline-form">
                <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                <input type="hidden" name="sp_type" value="attachment">
                <input type="hidden" name="sp_id" value="0">
                <input type="hidden" name="record_type" value="lead">
                <input type="hidden" name="record_id" value="<?php echo $id; ?>">
                <div class="sp-inline-form-footer">
                    <input type="file" name="sp_file" required>
                    <button type="submit" class="sp-btn sp-btn-primary sp-btn-sm">Upload</button>
                </div>
            </form>
            <?php if ( empty( $files ) ) : ?>
                <p class="sp-empty">No files attached.</p>
            <?php else : foreach ( $files as $f ) : ?>
                <div class="sp-file-item">
                    <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path fill="currentColor" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z"/></svg>
                    <div class="sp-file-body">
                        <a href="<?php echo esc_url( $f->fileurl ); ?>" target="_blank" class="sp-file-name"><?php echo esc_html( $f->filename ); ?></a>
                        <span class="sp-file-meta"><?php echo esc_html( sp_format_filesize( $f->filesize ) ); ?> &middot; <?php echo esc_html( date( 'M j, Y', strtotime( $f->created_at ) ) ); ?></span>
                    </div>
                    <a href="<?php echo esc_url( sp_delete_url( 'attachment', $f->id ) ); ?>" data-sp-confirm="Delete file?" class="sp-task-delete">×</a>
                </div>
            <?php endforeach; endif; ?>
        </div>

        <?php elseif ( $tab === 'timeline' ) : ?>
        <div class="sp-tab-pane">
            <?php $action_labels = array( 'created' => 'Lead created', 'updated' => 'Lead updated', 'note_added' => 'Note added', 'task_created' => 'Task created', 'task_done' => 'Task completed', 'task_open' => 'Task reopened', 'file_attached' => 'File attached' ); ?>
            <?php if ( empty( $activity ) ) : ?>
                <p class="sp-empty">No activity yet.</p>
            <?php else : foreach ( $activity as $evt ) : ?>
                <div class="sp-timeline-item">
                    <div class="sp-timeline-dot"></div>
                    <div class="sp-timeline-body">
                        <span class="sp-timeline-action"><?php echo esc_html( isset($action_labels[$evt->action]) ? $action_labels[$evt->action] : $evt->action ); ?></span>
                        <?php if ( $evt->detail ) : ?><span class="sp-timeline-detail"> — <?php echo esc_html( $evt->detail ); ?></span><?php endif; ?>
                        <span class="sp-timeline-meta"><?php echo esc_html( $evt->actor ?: 'System' ); ?> &middot; <?php echo esc_html( date( 'M j, Y g:ia', strtotime( $evt->created_at ) ) ); ?></span>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php return;
}

// ── Edit / New form ───────────────────────────────────────────────────────────
if ( $action === 'new' || $action === 'edit' ) {
    $lead      = null;
    $contacts  = $wpdb->get_results( "SELECT id, first_name, last_name, email FROM {$wpdb->prefix}sp_contacts ORDER BY first_name, last_name" );
    $companies = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sp_companies ORDER BY name" );
    if ( $action === 'edit' && $id ) {
        $lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_leads WHERE id = %d", $id ) );
        if ( ! $lead ) { echo '<p class="sp-empty">Lead not found.</p>'; return; }
    }
    $statuses = array( 'new', 'contacted', 'qualified', 'unqualified', 'closed' );
    ?>
    <div class="sp-page-header">
        <h1><?php echo $action === 'edit' ? 'Edit Lead' : 'New Lead'; ?></h1>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=leads' ) ); ?>" class="sp-btn sp-btn-ghost">&larr; Back</a>
    </div>
    <div class="sp-card sp-form-card">
        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="lead">
            <input type="hidden" name="sp_id" value="<?php echo esc_attr( $id ); ?>">
            <div class="sp-form-row">
                <div class="sp-field">
                    <label>Contact</label>
                    <select name="contact_id">
                        <option value="0">— Select Contact —</option>
                        <?php foreach ( $contacts as $c ) :
                            $label = trim( $c->first_name . ' ' . $c->last_name ) ?: $c->email; ?>
                            <option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $lead ? $lead->contact_id : 0, $c->id ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sp-field">
                    <label>Company</label>
                    <select name="company_id">
                        <option value="0">— None —</option>
                        <?php foreach ( $companies as $co ) : ?>
                            <option value="<?php echo esc_attr( $co->id ); ?>" <?php selected( $lead ? $lead->company_id : 0, $co->id ); ?>><?php echo esc_html( $co->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="sp-form-row">
                <div class="sp-field"><label>Source</label><input type="text" name="source" value="<?php echo esc_attr( $lead ? $lead->source : '' ); ?>" placeholder="website, referral..."></div>
                <div class="sp-field">
                    <label>Status</label>
                    <select name="status">
                        <?php foreach ( $statuses as $s ) : ?>
                            <option value="<?php echo $s; ?>" <?php selected( $lead ? $lead->status : 'new', $s ); ?>><?php echo ucfirst( $s ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="sp-form-row">
                <div class="sp-field"><label>Score (0–100)</label><input type="number" name="score" min="0" max="100" value="<?php echo esc_attr( $lead ? $lead->score : 0 ); ?>"></div>
            </div>
            <div class="sp-field"><label>Notes</label><textarea name="notes" rows="4"><?php echo esc_textarea( $lead ? $lead->notes : '' ); ?></textarea></div>
            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary">Save Lead</button>
                <a href="<?php echo esc_url( home_url( '/sp-app/?view=leads' ) ); ?>" class="sp-btn sp-btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
    <?php return;
}

// ── List ──────────────────────────────────────────────────────────────────────
$status_filter = sanitize_key( isset( $_GET['status'] ) ? $_GET['status'] : '' );
$paged  = max( 1, (int) ( isset( $_GET['paged'] ) ? $_GET['paged'] : 1 ) );
$limit  = 20; $offset = ( $paged - 1 ) * $limit;

if ( $status_filter ) {
    $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_leads WHERE status = %s", $status_filter ) );
    $rows  = $wpdb->get_results( $wpdb->prepare( "SELECT l.*, c.first_name, c.last_name, co.name AS company_name FROM {$wpdb->prefix}sp_leads l LEFT JOIN {$wpdb->prefix}sp_contacts c ON c.id = l.contact_id LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id = l.company_id WHERE l.status = %s ORDER BY l.created_at DESC LIMIT %d OFFSET %d", $status_filter, $limit, $offset ) );
} else {
    $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_leads" );
    $rows  = $wpdb->get_results( $wpdb->prepare( "SELECT l.*, c.first_name, c.last_name, co.name AS company_name FROM {$wpdb->prefix}sp_leads l LEFT JOIN {$wpdb->prefix}sp_contacts c ON c.id = l.contact_id LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id = l.company_id ORDER BY l.created_at DESC LIMIT %d OFFSET %d", $limit, $offset ) );
}
$statuses = array( '', 'new', 'contacted', 'qualified', 'unqualified', 'closed' );
?>
<?php
$leads_tab = sanitize_key( isset( $_GET['leads_tab'] ) ? $_GET['leads_tab'] : 'list' );
?>
<div class="sp-page-header">
    <h1>Leads <span class="sp-count"><?php echo number_format( $total ); ?></span></h1>
    <div class="sp-header-actions">
        <?php if ( $leads_tab === 'list' ) : ?>
        <form method="get" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="sp-search-form">
            <input type="hidden" name="view" value="leads">
            <select name="status" onchange="this.form.submit()">
                <?php foreach ( $statuses as $s ) : ?>
                    <option value="<?php echo $s; ?>" <?php selected( $status_filter, $s ); ?>><?php echo $s ? ucfirst( $s ) : 'All Statuses'; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="<?php echo esc_url( home_url( '/sp-app/?sp_export=leads' ) ); ?>" class="sp-btn sp-btn-ghost">Export CSV</a>
        <?php endif; ?>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=leads&action=new' ) ); ?>" class="sp-btn sp-btn-primary">+ New Lead</a>
    </div>
</div>

<div style="display:flex;gap:8px;margin-bottom:20px">
    <a href="<?php echo esc_url( home_url( '/sp-app/?view=leads' ) ); ?>"
       class="sp-btn <?php echo $leads_tab === 'list' ? 'sp-btn-primary' : 'sp-btn-ghost'; ?>">List</a>
    <a href="<?php echo esc_url( home_url( '/sp-app/?view=leads&leads_tab=pipeline' ) ); ?>"
       class="sp-btn <?php echo $leads_tab === 'pipeline' ? 'sp-btn-primary' : 'sp-btn-ghost'; ?>">Pipeline</a>
</div>

<?php if ( $leads_tab === 'pipeline' ) :
    // ── Inline Pipeline Board ─────────────────────────────────────────────────
    $pipe_statuses = array(
        'new'         => array( 'label' => 'New',         'color' => '#3b82f6', 'bg' => '#dbeafe' ),
        'contacted'   => array( 'label' => 'Contacted',   'color' => '#f59e0b', 'bg' => '#fef3c7' ),
        'qualified'   => array( 'label' => 'Qualified',   'color' => '#8b5cf6', 'bg' => '#ede9fe' ),
        'unqualified' => array( 'label' => 'Unqualified', 'color' => '#ef4444', 'bg' => '#fee2e2' ),
        'closed'      => array( 'label' => 'Closed',      'color' => '#10b981', 'bg' => '#d1fae5' ),
    );
    $all_leads = $wpdb->get_results(
        "SELECT l.*, CONCAT(COALESCE(c.first_name,''),' ',COALESCE(c.last_name,'')) AS contact_name,
                c.email AS contact_email, co.name AS company_name
         FROM {$wpdb->prefix}sp_leads l
         LEFT JOIN {$wpdb->prefix}sp_contacts c  ON c.id  = l.contact_id
         LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id = l.company_id
         ORDER BY l.score DESC, l.created_at DESC"
    );
    $by_status = array();
    foreach ( $pipe_statuses as $key => $_ ) $by_status[ $key ] = array();
    foreach ( $all_leads as $lead ) {
        $s = isset( $by_status[ $lead->status ] ) ? $lead->status : 'new';
        $by_status[ $s ][] = $lead;
    }
    ?>
    <div class="sp-pipeline-wrap">
    <?php foreach ( $pipe_statuses as $key => $meta ) :
        $col_leads = $by_status[ $key ];
        $count     = count( $col_leads );
        $avg_score = $count ? round( array_sum( array_column( $col_leads, 'score' ) ) / $count ) : 0;
    ?>
    <div class="sp-pipeline-col" data-status="<?php echo esc_attr( $key ); ?>">
        <div class="sp-pipeline-col-header" style="border-top:3px solid <?php echo esc_attr( $meta['color'] ); ?>">
            <span class="sp-pipeline-col-label"><?php echo esc_html( $meta['label'] ); ?></span>
            <span class="sp-pipeline-col-count" style="background:<?php echo esc_attr( $meta['bg'] ); ?>;color:<?php echo esc_attr( $meta['color'] ); ?>"><?php echo $count; ?></span>
            <?php if ( $count ) : ?><span class="sp-pipeline-avg">avg <?php echo $avg_score; ?></span><?php endif; ?>
        </div>
        <div class="sp-pipeline-cards" id="col-<?php echo esc_attr( $key ); ?>">
            <?php if ( empty( $col_leads ) ) : ?>
                <div class="sp-pipeline-empty">No leads</div>
            <?php endif; ?>
            <?php foreach ( $col_leads as $pl ) :
                $name = trim( $pl->contact_name ) ?: $pl->company_name ?: 'Unnamed Lead';
                $sub  = trim( $pl->contact_name ) && $pl->company_name ? $pl->company_name : $pl->contact_email;
                $age  = human_time_diff( strtotime( $pl->created_at ), current_time( 'timestamp' ) );
            ?>
            <div class="sp-pipeline-card" draggable="true" data-id="<?php echo esc_attr( $pl->id ); ?>" data-status="<?php echo esc_attr( $pl->status ); ?>">
                <div class="sp-pipeline-card-name">
                    <a href="<?php echo esc_url( home_url( '/sp-app/?view=leads&action=view&id=' . $pl->id ) ); ?>"><?php echo esc_html( $name ); ?></a>
                </div>
                <?php if ( $sub ) : ?><div class="sp-pipeline-card-sub"><?php echo esc_html( $sub ); ?></div><?php endif; ?>
                <div class="sp-pipeline-card-footer">
                    <?php if ( $pl->source ) : ?><span class="sp-pipeline-source"><?php echo esc_html( $pl->source ); ?></span><?php endif; ?>
                    <span class="sp-pipeline-age"><?php echo esc_html( $age ); ?> ago</span>
                    <?php if ( $pl->score ) : ?><span class="sp-pipeline-score"><?php echo esc_html( $pl->score ); ?></span><?php endif; ?>
                </div>
                <div class="sp-pipeline-move">
                    <select class="sp-pipeline-move-select" data-id="<?php echo esc_attr( $pl->id ); ?>">
                        <?php foreach ( $pipe_statuses as $s_key => $s_meta ) : ?>
                            <option value="<?php echo esc_attr( $s_key ); ?>" <?php selected( $pl->status, $s_key ); ?>>Move to: <?php echo esc_html( $s_meta['label'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <div id="sp-pipeline-toast" class="sp-pipeline-toast" style="display:none"></div>
    <?php include SP_PLUGIN_DIR . 'templates/views/pipeline_scripts.php'; ?>
    <?php return;
endif; ?>
<form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" id="sp-bulk-form" class="sp-bulk-form">
    <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
    <input type="hidden" name="sp_type" value="bulk">
    <input type="hidden" name="sp_id" value="0">
    <input type="hidden" name="sp_entity" value="leads">
    <?php $bulk_entity = 'leads'; $bulk_statuses = array( 'new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified', 'unqualified' => 'Unqualified', 'closed' => 'Closed' ); include SP_PLUGIN_DIR . 'templates/views/partials/bulk_bar.php'; ?>
<div class="sp-card sp-table-card">
    <table class="sp-table">
        <thead><tr><th class="sp-check-col"><input type="checkbox" id="sp-check-all"></th><th>Contact</th><th>Company</th><th>Source</th><th>Status</th><th>Score</th><th>Created</th><th></th></tr></thead>
        <tbody>
        <?php if ( empty( $rows ) ) : ?>
            <tr><td colspan="8" class="sp-empty">No leads found.</td></tr>
        <?php else : foreach ( $rows as $row ) :
            $name = trim( $row->first_name . ' ' . $row->last_name ) ?: '(no contact)'; ?>
            <tr>
                <td class="sp-check-col"><input type="checkbox" class="sp-row-check" name="ids[]" value="<?php echo esc_attr( $row->id ); ?>"></td>
                <td><a href="<?php echo esc_url( home_url( '/sp-app/?view=leads&action=view&id=' . $row->id ) ); ?>" class="sp-link"><?php echo esc_html( $name ); ?></a></td>
                <td class="sp-muted"><?php echo esc_html( $row->company_name ? $row->company_name : '—' ); ?></td>
                <td class="sp-muted"><?php echo esc_html( $row->source ? $row->source : '—' ); ?></td>
                <td><span class="sp-badge sp-badge-<?php echo esc_attr( $row->status ); ?>"><?php echo esc_html( ucfirst( $row->status ) ); ?></span></td>
                <td><?php echo esc_html( $row->score ); ?></td>
                <td class="sp-muted"><?php echo esc_html( date( 'M j, Y', strtotime( $row->created_at ) ) ); ?></td>
                <td class="sp-actions">
                    <a href="<?php echo esc_url( home_url( '/sp-app/?view=leads&action=view&id=' . $row->id ) ); ?>">View</a>
                    <a href="<?php echo esc_url( home_url( '/sp-app/?view=leads&action=edit&id=' . $row->id ) ); ?>">Edit</a>
                    <a href="<?php echo esc_url( sp_delete_url( 'lead', $row->id ) ); ?>" data-sp-confirm="Delete this lead?" class="sp-danger">Delete</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php if ( function_exists( 'sp_pagination' ) ) sp_pagination( $total, $limit, $paged, 'leads' ); ?>
</div>
</form>
