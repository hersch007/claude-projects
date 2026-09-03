<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$action = sanitize_key( isset( $_GET['action'] ) ? $_GET['action'] : 'list' );
$id     = (int) ( isset( $_GET['id'] ) ? $_GET['id'] : 0 );
$saved  = isset( $_GET['saved'] );

// ── View (detail) ─────────────────────────────────────────────────────────────
if ( $action === 'view' && $id ) {
    $contact = $wpdb->get_row( $wpdb->prepare(
        "SELECT c.*, co.name AS company_name FROM {$wpdb->prefix}sp_contacts c LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id = c.company_id WHERE c.id = %d", $id
    ) );
    if ( ! $contact ) { echo '<p class="sp-empty">Contact not found.</p>'; return; }

    $tab        = sanitize_key( isset( $_GET['tab'] ) ? $_GET['tab'] : 'notes' );
    $team       = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sp_team WHERE status='active' ORDER BY name" );
    $notes      = $wpdb->get_results( $wpdb->prepare( "SELECT n.*, t.name AS author FROM {$wpdb->prefix}sp_notes n LEFT JOIN {$wpdb->prefix}sp_team t ON t.id = n.created_by WHERE n.record_type='contact' AND n.record_id=%d ORDER BY n.created_at DESC", $id ) );
    $emails     = $wpdb->get_results( $wpdb->prepare( "SELECT e.*, t.name AS author FROM {$wpdb->prefix}sp_email_log e LEFT JOIN {$wpdb->prefix}sp_team t ON t.id = e.logged_by WHERE e.record_type='contact' AND e.record_id=%d ORDER BY e.logged_at DESC", $id ) );
    $tasks      = $wpdb->get_results( $wpdb->prepare( "SELECT tk.*, t.name AS assignee_name FROM {$wpdb->prefix}sp_tasks tk LEFT JOIN {$wpdb->prefix}sp_team t ON t.id = tk.assigned_to WHERE tk.record_type='contact' AND tk.record_id=%d ORDER BY tk.status ASC, tk.due_date ASC", $id ) );
    $files      = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_attachments WHERE record_type='contact' AND record_id=%d ORDER BY created_at DESC", $id ) );
    $activity   = $wpdb->get_results( $wpdb->prepare( "SELECT a.*, t.name AS actor FROM {$wpdb->prefix}sp_activity a LEFT JOIN {$wpdb->prefix}sp_team t ON t.id = a.created_by WHERE a.record_type='contact' AND a.record_id=%d ORDER BY a.created_at DESC LIMIT 50", $id ) );

    $open_tasks = count( array_filter( (array) $tasks, function( $t ) { return $t->status === 'open'; } ) );
    ?>
    <div class="sp-page-header">
        <h1><?php echo esc_html( trim( $contact->first_name . ' ' . $contact->last_name ) ); ?></h1>
        <div class="sp-header-actions">
            <?php if ( $saved ) : ?><span class="sp-saved-badge">Saved ✓</span><?php endif; ?>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=contacts&action=edit&id=' . $id ) ); ?>" class="sp-btn sp-btn-ghost">Edit</a>
            <a href="<?php echo esc_url( sp_delete_url( 'contact', $id ) ); ?>" data-sp-confirm="Delete this contact?" class="sp-btn sp-btn-danger">Delete</a>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=contacts' ) ); ?>" class="sp-btn sp-btn-ghost">&larr; Contacts</a>
        </div>
    </div>

    <div class="sp-record-info-card sp-card">
        <div class="sp-record-fields">
            <div class="sp-record-field"><span class="sp-field-label">Email</span><span class="sp-field-value"><?php echo $contact->email ? '<a href="mailto:'.esc_attr($contact->email).'">'.esc_html($contact->email).'</a>' : '—'; ?></span></div>
            <div class="sp-record-field"><span class="sp-field-label">Phone</span><span class="sp-field-value"><?php echo esc_html( $contact->phone ?: '—' ); ?></span></div>
            <div class="sp-record-field"><span class="sp-field-label">Company</span><span class="sp-field-value"><?php echo esc_html( $contact->company_name ?: '—' ); ?></span></div>
            <div class="sp-record-field"><span class="sp-field-label">Source</span><span class="sp-field-value"><?php echo esc_html( $contact->source ?: '—' ); ?></span></div>
            <div class="sp-record-field"><span class="sp-field-label">Status</span><span class="sp-field-value"><span class="sp-badge sp-badge-<?php echo esc_attr($contact->status); ?>"><?php echo esc_html( ucfirst($contact->status) ); ?></span></span></div>
            <div class="sp-record-field"><span class="sp-field-label">Created</span><span class="sp-field-value sp-muted"><?php echo esc_html( date( 'M j, Y', strtotime( $contact->created_at ) ) ); ?></span></div>
        </div>
        <?php if ( $contact->notes ) : ?>
        <div class="sp-record-base-notes"><strong>Notes:</strong> <?php echo nl2br( esc_html( $contact->notes ) ); ?></div>
        <?php endif; ?>
    </div>

    <div class="sp-panel sp-card" style="margin-top:16px">
        <div class="sp-tab-nav">
            <a href="?view=contacts&action=view&id=<?php echo $id; ?>&tab=notes" class="sp-tab<?php echo $tab==='notes'?' active':''; ?>">Notes <span class="sp-tab-count"><?php echo count($notes); ?></span></a>
            <a href="?view=contacts&action=view&id=<?php echo $id; ?>&tab=emails" class="sp-tab<?php echo $tab==='emails'?' active':''; ?>">Emails <?php if(count($emails)): ?><span class="sp-tab-count"><?php echo count($emails); ?></span><?php endif; ?></a>
            <a href="?view=contacts&action=view&id=<?php echo $id; ?>&tab=tasks" class="sp-tab<?php echo $tab==='tasks'?' active':''; ?>">Tasks <?php if($open_tasks): ?><span class="sp-tab-count sp-tab-count-open"><?php echo $open_tasks; ?></span><?php endif; ?></a>
            <a href="?view=contacts&action=view&id=<?php echo $id; ?>&tab=files" class="sp-tab<?php echo $tab==='files'?' active':''; ?>">Files <span class="sp-tab-count"><?php echo count($files); ?></span></a>
            <a href="?view=contacts&action=view&id=<?php echo $id; ?>&tab=timeline" class="sp-tab<?php echo $tab==='timeline'?' active':''; ?>">Timeline</a>
        </div>

        <?php if ( $tab === 'notes' ) : ?>
        <div class="sp-tab-pane">
            <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="sp-inline-form">
                <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                <input type="hidden" name="sp_type" value="note">
                <input type="hidden" name="sp_id" value="0">
                <input type="hidden" name="record_type" value="contact">
                <input type="hidden" name="record_id" value="<?php echo $id; ?>">
                <textarea name="content" rows="3" placeholder="Add a note…" required></textarea>
                <div class="sp-inline-form-footer">
                    <div class="sp-field-inline">
                        <label>Reminder</label>
                        <input type="datetime-local" name="reminder_at">
                    </div>
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
            $email_record_type = 'contact'; $email_record_id = $id;
            include SP_PLUGIN_DIR . 'templates/views/partials/email_log.php';
        ?>

        <?php elseif ( $tab === 'tasks' ) : ?>
        <div class="sp-tab-pane">
            <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="sp-inline-form">
                <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                <input type="hidden" name="sp_type" value="task">
                <input type="hidden" name="sp_id" value="0">
                <input type="hidden" name="record_type" value="contact">
                <input type="hidden" name="record_id" value="<?php echo $id; ?>">
                <input type="text" name="title" placeholder="Task title…" required style="flex:1">
                <div class="sp-inline-form-footer">
                    <div class="sp-field-inline">
                        <label>Assign to</label>
                        <select name="assigned_to">
                            <option value="0">— Anyone —</option>
                            <?php foreach ( $team as $m ) : ?><option value="<?php echo $m->id; ?>"><?php echo esc_html($m->name); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="sp-field-inline">
                        <label>Due</label>
                        <input type="date" name="due_date">
                    </div>
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
                        <input type="hidden" name="record_type" value="contact">
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
                <input type="hidden" name="record_type" value="contact">
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
            <?php
            $action_labels = array(
                'created'      => 'Contact created',
                'updated'      => 'Contact updated',
                'note_added'   => 'Note added',
                'task_created' => 'Task created',
                'task_done'    => 'Task completed',
                'task_open'    => 'Task reopened',
                'file_attached'=> 'File attached',
            );
            ?>
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
    <?php do_action( 'sp_contact_view_after', $id ); ?>
    <?php
    return;
}

// ── Edit / New form ───────────────────────────────────────────────────────────
if ( $action === 'new' || $action === 'edit' ) {
    $contact   = null;
    $companies = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sp_companies ORDER BY name" );
    $duplicate = (int) ( isset( $_GET['duplicate'] ) ? $_GET['duplicate'] : 0 );
    if ( $action === 'edit' && $id ) {
        $contact = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_contacts WHERE id = %d", $id ) );
        if ( ! $contact ) { echo '<p class="sp-empty">Contact not found.</p>'; return; }
    }
    ?>
    <div class="sp-page-header">
        <h1><?php echo $action === 'edit' ? 'Edit Contact' : 'New Contact'; ?></h1>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=contacts' ) ); ?>" class="sp-btn sp-btn-ghost">&larr; Back</a>
    </div>
    <?php if ( $duplicate ) : ?>
    <div class="sp-alert sp-alert-warning" style="margin-bottom:16px">
        A contact with this email already exists. <a href="<?php echo esc_url( home_url( '/sp-app/?view=contacts&action=view&id=' . $duplicate ) ); ?>">View existing contact &rarr;</a>
    </div>
    <?php endif; ?>
    <div class="sp-card sp-form-card">
        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="contact">
            <input type="hidden" name="sp_id" value="<?php echo esc_attr( $id ); ?>">
            <div class="sp-form-row">
                <div class="sp-field"><label>First Name</label><input type="text" name="first_name" value="<?php echo esc_attr( $contact ? $contact->first_name : '' ); ?>" required></div>
                <div class="sp-field"><label>Last Name</label><input type="text" name="last_name" value="<?php echo esc_attr( $contact ? $contact->last_name : '' ); ?>"></div>
            </div>
            <div class="sp-form-row">
                <div class="sp-field"><label>Email</label><input type="email" name="email" value="<?php echo esc_attr( $contact ? $contact->email : '' ); ?>"></div>
                <div class="sp-field"><label>Phone</label><input type="text" name="phone" value="<?php echo esc_attr( $contact ? $contact->phone : '' ); ?>"></div>
            </div>
            <div class="sp-form-row">
                <div class="sp-field">
                    <label>Company</label>
                    <select name="company_id">
                        <option value="0">— None —</option>
                        <?php foreach ( $companies as $co ) : ?>
                            <option value="<?php echo esc_attr( $co->id ); ?>" <?php selected( $contact ? $contact->company_id : 0, $co->id ); ?>><?php echo esc_html( $co->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sp-field">
                    <label>Source</label>
                    <input type="text" name="source" value="<?php echo esc_attr( $contact ? $contact->source : '' ); ?>" placeholder="website, referral…">
                </div>
            </div>
            <div class="sp-form-row">
                <div class="sp-field">
                    <label>Status</label>
                    <select name="status">
                        <?php foreach ( array( 'active', 'inactive', 'archived' ) as $s ) : ?>
                            <option value="<?php echo $s; ?>" <?php selected( $contact ? $contact->status : 'active', $s ); ?>><?php echo ucfirst( $s ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="sp-field"><label>Notes</label><textarea name="notes" rows="4"><?php echo esc_textarea( $contact ? $contact->notes : '' ); ?></textarea></div>
            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary">Save Contact</button>
                <a href="<?php echo esc_url( home_url( '/sp-app/?view=contacts' ) ); ?>" class="sp-btn sp-btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
    <?php if ( $action === 'edit' && $id ) do_action( 'sp_contact_edit_after', $id ); ?>
    <?php
    return;
}

// ── List ──────────────────────────────────────────────────────────────────────
$search = sanitize_text_field( isset( $_GET['s'] ) ? $_GET['s'] : '' );
$paged  = max( 1, (int) ( isset( $_GET['paged'] ) ? $_GET['paged'] : 1 ) );
$limit  = 20;
$offset = ( $paged - 1 ) * $limit;

if ( $search ) {
    $like  = '%' . $wpdb->esc_like( $search ) . '%';
    $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_contacts WHERE first_name LIKE %s OR last_name LIKE %s OR email LIKE %s", $like, $like, $like ) );
    $rows  = $wpdb->get_results( $wpdb->prepare( "SELECT c.*, co.name AS company_name FROM {$wpdb->prefix}sp_contacts c LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id = c.company_id WHERE c.first_name LIKE %s OR c.last_name LIKE %s OR c.email LIKE %s ORDER BY c.created_at DESC LIMIT %d OFFSET %d", $like, $like, $like, $limit, $offset ) );
} else {
    $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_contacts" );
    $rows  = $wpdb->get_results( $wpdb->prepare( "SELECT c.*, co.name AS company_name FROM {$wpdb->prefix}sp_contacts c LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id = c.company_id ORDER BY c.created_at DESC LIMIT %d OFFSET %d", $limit, $offset ) );
}
?>
<div class="sp-page-header">
    <h1>Contacts <span class="sp-count"><?php echo number_format( $total ); ?></span></h1>
    <div class="sp-header-actions">
        <form method="get" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="sp-search-form">
            <input type="hidden" name="view" value="contacts">
            <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search…">
        </form>
        <a href="<?php echo esc_url( home_url( '/sp-app/?sp_export=contacts' ) ); ?>" class="sp-btn sp-btn-ghost">Export CSV</a>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=contacts&action=new' ) ); ?>" class="sp-btn sp-btn-primary">+ New Contact</a>
    </div>
</div>

<form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" id="sp-bulk-form" class="sp-bulk-form">
    <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
    <input type="hidden" name="sp_type" value="bulk">
    <input type="hidden" name="sp_id" value="0">
    <input type="hidden" name="sp_entity" value="contacts">
    <?php $bulk_entity = 'contacts'; $bulk_statuses = array( 'active' => 'Active', 'inactive' => 'Inactive' ); include SP_PLUGIN_DIR . 'templates/views/partials/bulk_bar.php'; ?>
<div class="sp-card sp-table-card">
    <table class="sp-table">
        <thead>
            <tr><th class="sp-check-col"><input type="checkbox" id="sp-check-all"></th><th>Name</th><th>Email</th><th>Phone</th><th>Company</th><th>Status</th><th>Created</th><th></th></tr>
        </thead>
        <tbody>
        <?php if ( empty( $rows ) ) : ?>
            <tr><td colspan="8" class="sp-empty">No contacts found.</td></tr>
        <?php else : foreach ( $rows as $row ) : ?>
            <tr>
                <td class="sp-check-col"><input type="checkbox" class="sp-row-check" name="ids[]" value="<?php echo esc_attr( $row->id ); ?>"></td>
                <td><a href="<?php echo esc_url( home_url( '/sp-app/?view=contacts&action=view&id=' . $row->id ) ); ?>" class="sp-link"><?php echo esc_html( trim( $row->first_name . ' ' . $row->last_name ) ); ?></a></td>
                <td class="sp-muted"><?php echo esc_html( $row->email ); ?></td>
                <td class="sp-muted"><?php echo esc_html( $row->phone ); ?></td>
                <td><?php echo esc_html( $row->company_name ? $row->company_name : '—' ); ?></td>
                <td><span class="sp-badge sp-badge-<?php echo esc_attr( $row->status ); ?>"><?php echo esc_html( ucfirst( $row->status ) ); ?></span></td>
                <td class="sp-muted"><?php echo esc_html( date( 'M j, Y', strtotime( $row->created_at ) ) ); ?></td>
                <td class="sp-actions">
                    <a href="<?php echo esc_url( home_url( '/sp-app/?view=contacts&action=view&id=' . $row->id ) ); ?>">View</a>
                    <a href="<?php echo esc_url( home_url( '/sp-app/?view=contacts&action=edit&id=' . $row->id ) ); ?>">Edit</a>
                    <a href="<?php echo esc_url( sp_delete_url( 'contact', $row->id ) ); ?>" data-sp-confirm="Delete this contact?" class="sp-danger">Delete</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php sp_pagination( $total, $limit, $paged, 'contacts' ); ?>
</div>
</form>
