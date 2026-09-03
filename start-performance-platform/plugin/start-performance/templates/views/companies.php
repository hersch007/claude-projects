<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$action = sanitize_key( isset( $_GET['action'] ) ? $_GET['action'] : 'list' );
$id     = (int) ( isset( $_GET['id'] ) ? $_GET['id'] : 0 );
$saved  = isset( $_GET['saved'] );

// ── View (detail) ─────────────────────────────────────────────────────────────
if ( $action === 'view' && $id ) {
    $company = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_companies WHERE id = %d", $id ) );
    if ( ! $company ) { echo '<p class="sp-empty">Company not found.</p>'; return; }

    $tab      = sanitize_key( isset( $_GET['tab'] ) ? $_GET['tab'] : 'notes' );
    $team     = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sp_team WHERE status='active' ORDER BY name" );
    $notes    = $wpdb->get_results( $wpdb->prepare( "SELECT n.*, t.name AS author FROM {$wpdb->prefix}sp_notes n LEFT JOIN {$wpdb->prefix}sp_team t ON t.id = n.created_by WHERE n.record_type='company' AND n.record_id=%d ORDER BY n.created_at DESC", $id ) );
    $emails   = $wpdb->get_results( $wpdb->prepare( "SELECT e.*, t.name AS author FROM {$wpdb->prefix}sp_email_log e LEFT JOIN {$wpdb->prefix}sp_team t ON t.id = e.logged_by WHERE e.record_type='company' AND e.record_id=%d ORDER BY e.logged_at DESC", $id ) );
    $tasks    = $wpdb->get_results( $wpdb->prepare( "SELECT tk.*, t.name AS assignee_name FROM {$wpdb->prefix}sp_tasks tk LEFT JOIN {$wpdb->prefix}sp_team t ON t.id = tk.assigned_to WHERE tk.record_type='company' AND tk.record_id=%d ORDER BY tk.status ASC, tk.due_date ASC", $id ) );
    $files    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_attachments WHERE record_type='company' AND record_id=%d ORDER BY created_at DESC", $id ) );
    $activity = $wpdb->get_results( $wpdb->prepare( "SELECT a.*, t.name AS actor FROM {$wpdb->prefix}sp_activity a LEFT JOIN {$wpdb->prefix}sp_team t ON t.id = a.created_by WHERE a.record_type='company' AND a.record_id=%d ORDER BY a.created_at DESC LIMIT 50", $id ) );
    $contacts = $wpdb->get_results( $wpdb->prepare( "SELECT id, first_name, last_name, email FROM {$wpdb->prefix}sp_contacts WHERE company_id=%d ORDER BY first_name", $id ) );

    $open_tasks = count( array_filter( (array) $tasks, function( $t ) { return $t->status === 'open'; } ) );
    ?>
    <div class="sp-page-header">
        <h1><?php echo esc_html( $company->name ); ?></h1>
        <div class="sp-header-actions">
            <?php if ( $saved ) : ?><span class="sp-saved-badge">Saved ✓</span><?php endif; ?>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=companies&action=edit&id=' . $id ) ); ?>" class="sp-btn sp-btn-ghost">Edit</a>
            <a href="<?php echo esc_url( sp_delete_url( 'company', $id ) ); ?>" data-sp-confirm="Delete this company?" class="sp-btn sp-btn-danger">Delete</a>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=companies' ) ); ?>" class="sp-btn sp-btn-ghost">&larr; Companies</a>
        </div>
    </div>

    <div class="sp-record-info-card sp-card">
        <div class="sp-record-fields">
            <div class="sp-record-field"><span class="sp-field-label">Industry</span><span class="sp-field-value"><?php echo esc_html( $company->industry ?: '—' ); ?></span></div>
            <div class="sp-record-field"><span class="sp-field-label">Phone</span><span class="sp-field-value"><?php echo esc_html( $company->phone ?: '—' ); ?></span></div>
            <div class="sp-record-field"><span class="sp-field-label">Website</span><span class="sp-field-value"><?php echo $company->website ? '<a href="'.esc_url($company->website).'" target="_blank">'.esc_html($company->website).'</a>' : '—'; ?></span></div>
            <div class="sp-record-field"><span class="sp-field-label">Contacts</span><span class="sp-field-value"><?php echo count($contacts); ?></span></div>
            <div class="sp-record-field"><span class="sp-field-label">Created</span><span class="sp-field-value sp-muted"><?php echo esc_html( date( 'M j, Y', strtotime( $company->created_at ) ) ); ?></span></div>
        </div>
        <?php if ( $company->address ) : ?>
        <div class="sp-record-base-notes"><strong>Address:</strong> <?php echo nl2br( esc_html( $company->address ) ); ?></div>
        <?php endif; ?>
        <?php if ( $company->notes ) : ?>
        <div class="sp-record-base-notes"><strong>Notes:</strong> <?php echo nl2br( esc_html( $company->notes ) ); ?></div>
        <?php endif; ?>
        <?php if ( ! empty( $contacts ) ) : ?>
        <div class="sp-record-base-notes">
            <strong>Contacts:</strong>
            <?php foreach ( $contacts as $c ) : ?>
                <a href="<?php echo esc_url( home_url( '/sp-app/?view=contacts&action=view&id=' . $c->id ) ); ?>" style="margin-right:12px"><?php echo esc_html( trim( $c->first_name . ' ' . $c->last_name ) ?: $c->email ); ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="sp-panel sp-card" style="margin-top:16px">
        <div class="sp-tab-nav">
            <a href="?view=companies&action=view&id=<?php echo $id; ?>&tab=notes" class="sp-tab<?php echo $tab==='notes'?' active':''; ?>">Notes <span class="sp-tab-count"><?php echo count($notes); ?></span></a>
            <a href="?view=companies&action=view&id=<?php echo $id; ?>&tab=emails" class="sp-tab<?php echo $tab==='emails'?' active':''; ?>">Emails <?php if(count($emails)): ?><span class="sp-tab-count"><?php echo count($emails); ?></span><?php endif; ?></a>
            <a href="?view=companies&action=view&id=<?php echo $id; ?>&tab=tasks" class="sp-tab<?php echo $tab==='tasks'?' active':''; ?>">Tasks <?php if($open_tasks): ?><span class="sp-tab-count sp-tab-count-open"><?php echo $open_tasks; ?></span><?php endif; ?></a>
            <a href="?view=companies&action=view&id=<?php echo $id; ?>&tab=files" class="sp-tab<?php echo $tab==='files'?' active':''; ?>">Files <span class="sp-tab-count"><?php echo count($files); ?></span></a>
            <a href="?view=companies&action=view&id=<?php echo $id; ?>&tab=timeline" class="sp-tab<?php echo $tab==='timeline'?' active':''; ?>">Timeline</a>
        </div>

        <?php if ( $tab === 'notes' ) : ?>
        <div class="sp-tab-pane">
            <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="sp-inline-form">
                <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                <input type="hidden" name="sp_type" value="note">
                <input type="hidden" name="sp_id" value="0">
                <input type="hidden" name="record_type" value="company">
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
            $email_record_type = 'company'; $email_record_id = $id;
            include SP_PLUGIN_DIR . 'templates/views/partials/email_log.php';

        elseif ( $tab === 'tasks' ) : ?>
        <div class="sp-tab-pane">
            <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="sp-inline-form">
                <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                <input type="hidden" name="sp_type" value="task">
                <input type="hidden" name="sp_id" value="0">
                <input type="hidden" name="record_type" value="company">
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
                        <input type="hidden" name="record_type" value="company">
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
                <input type="hidden" name="record_type" value="company">
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
            <?php $action_labels = array( 'created' => 'Company created', 'updated' => 'Company updated', 'note_added' => 'Note added', 'task_created' => 'Task created', 'task_done' => 'Task completed', 'task_open' => 'Task reopened', 'file_attached' => 'File attached' ); ?>
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
    <?php do_action( 'sp_company_view_after', $id ); ?>
    <?php return;
}

// ── Edit / New form ───────────────────────────────────────────────────────────
if ( $action === 'new' || $action === 'edit' ) {
    $company = null;
    if ( $action === 'edit' && $id ) {
        $company = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_companies WHERE id = %d", $id ) );
        if ( ! $company ) { echo '<p class="sp-empty">Company not found.</p>'; return; }
    }
    ?>
    <div class="sp-page-header">
        <h1><?php echo $action === 'edit' ? 'Edit Company' : 'New Company'; ?></h1>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=companies' ) ); ?>" class="sp-btn sp-btn-ghost">&larr; Back</a>
    </div>
    <div class="sp-card sp-form-card">
        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="company">
            <input type="hidden" name="sp_id" value="<?php echo esc_attr( $id ); ?>">
            <div class="sp-form-row">
                <div class="sp-field"><label>Company Name</label><input type="text" name="name" value="<?php echo esc_attr( $company ? $company->name : '' ); ?>" required></div>
                <div class="sp-field"><label>Industry</label><input type="text" name="industry" value="<?php echo esc_attr( $company ? $company->industry : '' ); ?>"></div>
            </div>
            <div class="sp-form-row">
                <div class="sp-field"><label>Website</label><input type="url" name="website" value="<?php echo esc_attr( $company ? $company->website : '' ); ?>"></div>
                <div class="sp-field"><label>Phone</label><input type="text" name="phone" value="<?php echo esc_attr( $company ? $company->phone : '' ); ?>"></div>
            </div>
            <div class="sp-field"><label>Address</label><textarea name="address" rows="2"><?php echo esc_textarea( $company ? $company->address : '' ); ?></textarea></div>
            <div class="sp-field"><label>Notes</label><textarea name="notes" rows="4"><?php echo esc_textarea( $company ? $company->notes : '' ); ?></textarea></div>
            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary">Save Company</button>
                <a href="<?php echo esc_url( home_url( '/sp-app/?view=companies' ) ); ?>" class="sp-btn sp-btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
    <?php return;
}

// ── List ──────────────────────────────────────────────────────────────────────
$search = sanitize_text_field( isset( $_GET['s'] ) ? $_GET['s'] : '' );
$paged  = max( 1, (int) ( isset( $_GET['paged'] ) ? $_GET['paged'] : 1 ) );
$limit  = 20; $offset = ( $paged - 1 ) * $limit;

if ( $search ) {
    $like  = '%' . $wpdb->esc_like( $search ) . '%';
    $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_companies WHERE name LIKE %s OR industry LIKE %s", $like, $like ) );
    $rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_companies WHERE name LIKE %s OR industry LIKE %s ORDER BY name LIMIT %d OFFSET %d", $like, $like, $limit, $offset ) );
} else {
    $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_companies" );
    $rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_companies ORDER BY name LIMIT %d OFFSET %d", $limit, $offset ) );
}
?>
<div class="sp-page-header">
    <h1>Companies <span class="sp-count"><?php echo number_format( $total ); ?></span></h1>
    <div class="sp-header-actions">
        <form method="get" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="sp-search-form">
            <input type="hidden" name="view" value="companies">
            <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search...">
        </form>
        <a href="<?php echo esc_url( home_url( '/sp-app/?sp_export=companies' ) ); ?>" class="sp-btn sp-btn-ghost">Export CSV</a>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=companies&action=new' ) ); ?>" class="sp-btn sp-btn-primary">+ New Company</a>
    </div>
</div>
<form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" id="sp-bulk-form" class="sp-bulk-form">
    <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
    <input type="hidden" name="sp_type" value="bulk">
    <input type="hidden" name="sp_id" value="0">
    <input type="hidden" name="sp_entity" value="companies">
    <?php $bulk_entity = 'companies'; $bulk_statuses = array(); include SP_PLUGIN_DIR . 'templates/views/partials/bulk_bar.php'; ?>
<div class="sp-card sp-table-card">
    <table class="sp-table">
        <thead><tr><th class="sp-check-col"><input type="checkbox" id="sp-check-all"></th><th>Name</th><th>Industry</th><th>Phone</th><th>Website</th><th>Created</th><th></th></tr></thead>
        <tbody>
        <?php if ( empty( $rows ) ) : ?>
            <tr><td colspan="7" class="sp-empty">No companies found.</td></tr>
        <?php else : foreach ( $rows as $row ) : ?>
            <tr>
                <td class="sp-check-col"><input type="checkbox" class="sp-row-check" name="ids[]" value="<?php echo esc_attr( $row->id ); ?>"></td>
                <td><a href="<?php echo esc_url( home_url( '/sp-app/?view=companies&action=view&id=' . $row->id ) ); ?>" class="sp-link"><?php echo esc_html( $row->name ); ?></a></td>
                <td class="sp-muted"><?php echo esc_html( $row->industry ? $row->industry : '—' ); ?></td>
                <td class="sp-muted"><?php echo esc_html( $row->phone ? $row->phone : '—' ); ?></td>
                <td><?php echo $row->website ? '<a href="' . esc_url( $row->website ) . '" target="_blank" class="sp-link">' . esc_html( $row->website ) . '</a>' : '—'; ?></td>
                <td class="sp-muted"><?php echo esc_html( date( 'M j, Y', strtotime( $row->created_at ) ) ); ?></td>
                <td class="sp-actions">
                    <a href="<?php echo esc_url( home_url( '/sp-app/?view=companies&action=view&id=' . $row->id ) ); ?>">View</a>
                    <a href="<?php echo esc_url( home_url( '/sp-app/?view=companies&action=edit&id=' . $row->id ) ); ?>">Edit</a>
                    <a href="<?php echo esc_url( sp_delete_url( 'company', $row->id ) ); ?>" data-sp-confirm="Delete this company?" class="sp-danger">Delete</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php sp_pagination( $total, $limit, $paged, 'companies' ); ?>
</div>
</form>
