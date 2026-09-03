<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$settings  = sp_smti_get_settings();
$action    = sanitize_key( isset( $_GET['action'] ) ? $_GET['action'] : 'list' );
$ticket_id = sanitize_text_field( isset( $_GET['id'] ) ? $_GET['id'] : '' );

// ── Not configured notice ─────────────────────────────────────────────────────
if ( empty( $settings['hubspot_token'] ) ) {
    ?>
    <div class="sp-page-header"><h1>Service Requests</h1></div>
    <div class="sp-card sp-form-card">
        <p style="color:#64748b;margin:0">HubSpot is not configured. Go to <a href="<?php echo esc_url( home_url( '/sp-app/?view=settings' ) ); ?>" class="sp-link">Settings</a> and add your HubSpot Private App Token.</p>
    </div>
    <?php
    return;
}

// ── Detail view ───────────────────────────────────────────────────────────────
if ( $action === 'detail' && $ticket_id !== '' ) {

    $stage_map = sp_smti_stage_map();

    // Phase 3 perf: fetch the ticket AND its associated contact/company/note IDs in
    // ONE call (associations param), instead of the ticket + three separate v4
    // association lookups.
    $props_qs = rawurlencode( implode( ',', sp_smti_ticket_properties() ) );
    $response = sp_smti_hs_request( 'GET', '/crm/v3/objects/tickets/' . rawurlencode( $ticket_id ) . '?properties=' . $props_qs . '&associations=contacts,notes' );

    if ( is_wp_error( $response ) || (int) $response['status'] !== 200 || empty( $response['body'] ) ) {
        echo '<div class="sp-page-header"><h1>Service Requests</h1><a href="' . esc_url( home_url( '/sp-app/?view=smti' ) ) . '" class="sp-btn sp-btn-ghost">&larr; Back</a></div>';
        echo '<p class="sp-empty">Ticket not found or HubSpot error.</p>';
        return;
    }

    $ticket  = sp_smti_ticket_summary( $response['body'], $stage_map );
    $content = $ticket['content'];

    // Pull associated IDs from the inline associations on the ticket response.
    $assoc_ids = function( $body, $type ) {
        $out = array();
        if ( ! empty( $body['associations'][ $type ]['results'] ) ) {
            foreach ( $body['associations'][ $type ]['results'] as $row ) {
                if ( isset( $row['id'] ) ) $out[] = (string) $row['id'];
            }
        }
        return array_values( array_unique( $out ) );
    };
    $contact_ids = array_slice( $assoc_ids( $response['body'], 'contacts' ), 0, 3 );
    $note_ids    = array_slice( $assoc_ids( $response['body'], 'notes' ),    0, 10 );

    // Phase 3 perf: batch-read each object type in a single call instead of one GET
    // per record (was up to ~16 serial calls; now at most 3).
    $batch_read = function( $type, $ids, $props ) {
        if ( empty( $ids ) ) return array();
        $inputs = array();
        foreach ( $ids as $id ) $inputs[] = array( 'id' => (string) $id );
        $r = sp_smti_hs_request( 'POST', '/crm/v3/objects/' . $type . '/batch/read', array( 'properties' => $props, 'inputs' => $inputs ) );
        if ( is_wp_error( $r ) || (int) $r['status'] !== 200 || empty( $r['body']['results'] ) ) return array();
        return $r['body']['results'];
    };
    // Note: associated companies were fetched here but never rendered — dropped
    // (the ticket's own Company/Facility field already covers it, and the extra
    // batch/read cost ~600ms on every ticket open).
    $contacts = $batch_read( 'contacts', $contact_ids, array( 'firstname', 'lastname', 'email', 'phone' ) );
    $notes    = $batch_read( 'notes',    $note_ids,    array( 'hs_note_body', 'hs_timestamp', 'hs_attachment_ids' ) );

    usort( $notes, function( $a, $b ) {
        $ta = isset( $a['properties']['hs_timestamp'] ) ? $a['properties']['hs_timestamp'] : '';
        $tb = isset( $b['properties']['hs_timestamp'] ) ? $b['properties']['hs_timestamp'] : '';
        return strcmp( $tb, $ta );
    } );

    // Contact card data: prefer the linked HubSpot contact record(s), filling any
    // gaps from the ticket's own submitted fields. Falls back entirely to the ticket
    // fields when no CRM contact is associated, so we never show nothing.
    $contact_cards = array();
    foreach ( $contacts as $c ) {
        $p    = isset( $c['properties'] ) ? $c['properties'] : array();
        $name = trim( ( isset( $p['firstname'] ) ? $p['firstname'] : '' ) . ' ' . ( isset( $p['lastname'] ) ? $p['lastname'] : '' ) );
        $contact_cards[] = array(
            'name'  => $name !== '' ? $name : $ticket['contact_name'],
            'email' => ! empty( $p['email'] ) ? $p['email'] : $ticket['contact_email'],
            'phone' => ! empty( $p['phone'] ) ? $p['phone'] : $ticket['contact_phone'],
        );
    }
    if ( empty( $contact_cards ) && ( $ticket['contact_name'] || $ticket['contact_email'] || $ticket['contact_phone'] ) ) {
        $contact_cards[] = array(
            'name'  => $ticket['contact_name'],
            'email' => $ticket['contact_email'],
            'phone' => $ticket['contact_phone'],
        );
    }

    // ── Attachments (fixes: customer-attached images were never shown) ──────────
    // Uploaded files live as HubSpot Files referenced via hs_attachment_ids on the
    // ticket's notes. They're PRIVATE, so we fetch a temporary signed URL per file.
    $attach_ids  = array();   // file id => true (dedupe across notes)
    $note_attach = array();   // note id  => array of file ids (for per-note thumbnails)
    foreach ( $notes as $note ) {
        $nid = isset( $note['id'] ) ? (string) $note['id'] : '';
        $ap  = isset( $note['properties'] ) ? $note['properties'] : array();
        if ( ! empty( $ap['hs_attachment_ids'] ) ) {
            foreach ( preg_split( '/[;,\s]+/', (string) $ap['hs_attachment_ids'] ) as $fid ) {
                $fid = trim( $fid );
                if ( $fid === '' ) continue;
                $attach_ids[ $fid ] = true;
                if ( $nid !== '' ) $note_attach[ $nid ][] = $fid;
            }
        }
    }
    $files = array();   // file id => array('name','img','url')
    foreach ( array_slice( array_keys( $attach_ids ), 0, 12 ) as $fid ) {
        // Phase 3 perf: one call per file. The signed-url endpoint already returns the
        // display URL plus name/extension/type, so the separate file-metadata GET the
        // old code made was redundant (halves the attachment calls).
        $signed = sp_smti_hs_request( 'GET', '/files/v3/files/' . rawurlencode( $fid ) . '/signed-url' );
        if ( is_wp_error( $signed ) || (int) $signed['status'] !== 200 || empty( $signed['body']['url'] ) ) continue;
        $fb  = $signed['body'];
        $ext = strtolower( isset( $fb['extension'] ) ? $fb['extension'] : '' );
        $img = ( isset( $fb['type'] ) && $fb['type'] === 'IMG' ) || in_array( $ext, array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic' ), true );
        $files[ $fid ] = array(
            'name' => ( isset( $fb['name'] ) ? $fb['name'] : ( 'File ' . $fid ) ) . ( $ext ? '.' . $ext : '' ),
            'img'  => $img,
            'url'  => $fb['url'],
        );
    }
    $attachments = array_values( $files );

    $owners  = sp_smti_is_admin_member_check() ? sp_smti_get_owners_list() : array();
    $is_adm  = sp_smti_is_admin_member_check();
    $created = $ticket['createdate']       ? date( 'M j, Y g:i a', strtotime( $ticket['createdate'] ) )       : '—';
    $updated = $ticket['lastmodifieddate'] ? date( 'M j, Y g:i a', strtotime( $ticket['lastmodifieddate'] ) ) : '—';
    ?>
    <div class="sp-page-header">
        <h1><?php echo esc_html( $ticket['service_request_number'] ? $ticket['service_request_number'] : 'Service Request' ); ?></h1>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=smti' ) ); ?>" class="sp-btn sp-btn-ghost">&larr; All Requests</a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:start">

        <div>
            <!-- Ticket info -->
            <div class="sp-card sp-form-card">
                <h2 class="sp-section-heading">Ticket Details</h2>
                <p style="font-size:15px;font-weight:600;margin:0 0 12px"><?php echo esc_html( $ticket['subject'] ); ?></p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 24px;font-size:13px;color:#64748b">
                    <div><strong style="color:#1e293b">Status</strong><br>
                        <?php if ( $ticket['status'] ) : ?><span class="sp-badge" style="background:#f1f5f9;color:#334155"><?php echo esc_html( $ticket['status'] ); ?></span><?php else : ?>—<?php endif; ?>
                    </div>
                    <div><strong style="color:#1e293b">Serial Number</strong><br><?php echo esc_html( $ticket['serial_number'] ? $ticket['serial_number'] : '—' ); ?></div>
                    <div><strong style="color:#1e293b">Company / Facility</strong><br><?php echo esc_html( $ticket['company'] ? $ticket['company'] : '—' ); ?></div>
                    <div><strong style="color:#1e293b">Location</strong><br><?php echo esc_html( $ticket['location_name'] ? $ticket['location_name'] : '—' ); ?></div>
                    <div><strong style="color:#1e293b">Part ID</strong><br><?php echo esc_html( $ticket['part_id'] ? $ticket['part_id'] : '—' ); ?></div>
                    <div><strong style="color:#1e293b">Equipment Model</strong><br><?php echo esc_html( $ticket['equipment_model'] ? $ticket['equipment_model'] : '—' ); ?></div>
                    <div><strong style="color:#1e293b">Created</strong><br><?php echo esc_html( $created ); ?></div>
                    <div><strong style="color:#1e293b">Last Updated</strong><br><?php echo esc_html( $updated ); ?></div>
                </div>

                <?php if ( $content ) : ?>
                <details style="margin-top:16px">
                    <summary style="cursor:pointer;font-size:13px;font-weight:600;color:#64748b">Full Ticket Body</summary>
                    <pre style="margin-top:8px;padding:12px;background:#f8fafc;border-radius:6px;font-size:12px;white-space:pre-wrap;overflow:auto;max-height:300px;color:#334155"><?php echo esc_html( $content ); ?></pre>
                </details>
                <?php endif; ?>
            </div>

            <!-- Attachments -->
            <?php if ( ! empty( $attachments ) ) : ?>
            <div class="sp-card sp-form-card" style="margin-top:16px">
                <h2 class="sp-section-heading">Attachments</h2>
                <div style="display:flex;flex-wrap:wrap;gap:14px">
                    <?php foreach ( $attachments as $att ) : ?>
                        <?php if ( $att['img'] && $att['url'] ) : ?>
                            <a href="<?php echo esc_url( $att['url'] ); ?>" target="_blank" rel="noopener" style="text-decoration:none;display:block">
                                <img src="<?php echo esc_url( $att['url'] ); ?>" alt="<?php echo esc_attr( $att['name'] ); ?>" loading="lazy" style="width:150px;height:150px;object-fit:cover;border-radius:10px;border:1px solid #e2e8f0;display:block">
                                <div style="font-size:11px;color:#64748b;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:5px"><?php echo esc_html( $att['name'] ); ?></div>
                            </a>
                        <?php elseif ( $att['url'] ) : ?>
                            <a href="<?php echo esc_url( $att['url'] ); ?>" target="_blank" rel="noopener" class="sp-btn sp-btn-ghost sp-btn-sm" style="align-self:flex-start">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                                <?php echo esc_html( $att['name'] ); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Notes -->
            <div class="sp-card sp-form-card" style="margin-top:16px">
                <h2 class="sp-section-heading">Notes &amp; Activity</h2>

                <!-- Add note form (kept at top for quick access) -->
                <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                    <input type="hidden" name="sp_type"   value="smti_note">
                    <input type="hidden" name="sp_id"     value="0">
                    <input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket_id ); ?>">
                    <div class="sp-field">
                        <label>Add Internal Note</label>
                        <textarea name="note" rows="3" placeholder="Enter note..."></textarea>
                    </div>
                    <div class="sp-field" style="margin-top:10px">
                        <label style="font-size:12px">Attach Photos <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#94a3b8">(optional — images or PDF, up to 5, 10MB each)</span></label>
                        <input type="file" name="smti_note_files[]" multiple accept="image/*,.pdf" id="smtiNoteFiles" style="font-size:13px;padding:8px;border:1px dashed #cbd5e1;border-radius:8px;background:#f8fafc;width:100%;box-sizing:border-box;cursor:pointer">
                        <span class="sp-hint" id="smtiNoteFilesHint" style="display:none"></span>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;margin-top:12px">
                        <button type="submit" id="smtiNoteBtn" class="sp-btn sp-btn-primary sp-btn-sm smti-cta" disabled>Add Note</button>
                        <span class="smti-unsaved" id="smtiNoteUnsaved">Unsaved &mdash; click to save</span>
                    </div>
                </form>
                <script>
                (function(){
                    var input = document.getElementById('smtiNoteFiles');
                    var hint  = document.getElementById('smtiNoteFilesHint');
                    if (!input || !hint) return;
                    input.addEventListener('change', function(){
                        if (!input.files.length) { hint.style.display = 'none'; return; }
                        var names = [];
                        for (var i = 0; i < input.files.length; i++) names.push(input.files[i].name);
                        hint.textContent = input.files.length + ' file' + (input.files.length > 1 ? 's' : '') + ' selected: ' + names.join(', ');
                        hint.style.display = 'block';
                    });
                })();
                </script>

                <?php if ( ! empty( $notes ) ) : ?>
                <div style="border-top:1px solid #e2e8f0;margin:18px 0 0;padding-top:16px;display:flex;flex-direction:column;gap:12px">
                    <?php foreach ( $notes as $note ) :
                        $np        = isset( $note['properties'] ) ? $note['properties'] : array();
                        $note_body = isset( $np['hs_note_body'] ) ? $np['hs_note_body'] : '';
                        $note_ts   = isset( $np['hs_timestamp'] ) ? date( 'M j, Y \a\t g:i a', strtotime( $np['hs_timestamp'] ) ) : '';
                        $nid       = isset( $note['id'] ) ? (string) $note['id'] : '';
                        $natt      = ( $nid !== '' && ! empty( $note_attach[ $nid ] ) ) ? $note_attach[ $nid ] : array();
                        ?>
                        <div style="background:#f1f5f9;border:1px solid #dbe3ec;border-radius:10px;padding:14px 16px">
                            <?php if ( $note_ts ) : ?><div style="font-size:12px;font-weight:700;color:#475569;margin-bottom:7px"><?php echo esc_html( $note_ts ); ?></div><?php endif; ?>
                            <div style="font-size:15px;color:#0f172a;line-height:1.75;word-break:break-word"><?php echo wp_kses( $note_body, array( 'br' => array(), 'p' => array(), 'strong' => array(), 'b' => array(), 'em' => array(), 'i' => array(), 'ul' => array(), 'ol' => array(), 'li' => array(), 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ); ?></div>
                            <?php if ( $natt ) : ?>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:11px">
                                <?php foreach ( $natt as $fid ) : if ( empty( $files[ $fid ] ) ) continue; $f = $files[ $fid ]; ?>
                                    <?php if ( $f['img'] ) : ?>
                                    <a href="<?php echo esc_url( $f['url'] ); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr( $f['name'] ); ?>">
                                        <img src="<?php echo esc_url( $f['url'] ); ?>" alt="<?php echo esc_attr( $f['name'] ); ?>" loading="lazy" style="width:84px;height:84px;object-fit:cover;border-radius:8px;border:1px solid #cbd5e1;display:block">
                                    </a>
                                    <?php else : ?>
                                    <a href="<?php echo esc_url( $f['url'] ); ?>" target="_blank" rel="noopener" style="font-size:12px;color:#0ea5e9;text-decoration:none;background:#fff;border:1px solid #dbe3ec;border-radius:6px;padding:6px 10px"><?php echo esc_html( $f['name'] ); ?></a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                    <p class="sp-empty" style="margin:16px 0 0">No notes yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right sidebar: contact + actions -->
        <div>
            <?php if ( ! empty( $contact_cards ) ) : ?>
            <div class="sp-card sp-form-card">
                <h2 class="sp-section-heading">Contact</h2>
                <?php foreach ( $contact_cards as $ci => $cc ) : ?>
                <div style="<?php echo $ci ? 'margin-top:14px;padding-top:14px;border-top:1px solid #eef2f6;' : ''; ?>">
                    <?php if ( $cc['name'] ) : ?>
                    <div style="font-size:14px;font-weight:700;color:#0f172a;margin-bottom:7px"><?php echo esc_html( $cc['name'] ); ?></div>
                    <?php endif; ?>
                    <?php if ( $cc['email'] ) : ?>
                    <div style="margin-bottom:5px;display:flex;align-items:flex-start;gap:7px">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" style="flex:0 0 auto;margin-top:2px"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6l-10 7L2 6"/></svg>
                        <a href="mailto:<?php echo esc_attr( $cc['email'] ); ?>" style="font-size:13px;color:#0ea5e9;text-decoration:none;word-break:break-all"><?php echo esc_html( $cc['email'] ); ?></a>
                    </div>
                    <?php endif; ?>
                    <?php if ( $cc['phone'] ) : ?>
                    <div style="display:flex;align-items:center;gap:7px">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" style="flex:0 0 auto"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6A19.79 19.79 0 012.12 4.18 2 2 0 014.11 2h3a2 2 0 012 1.72c.13.96.36 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0122 16.92z"/></svg>
                        <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $cc['phone'] ) ); ?>" style="font-size:13px;color:#0ea5e9;text-decoration:none"><?php echo esc_html( $cc['phone'] ); ?></a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ( $is_adm && ! empty( $stage_map ) ) : ?>
            <div class="sp-card sp-form-card"<?php echo ! empty( $contact_cards ) ? ' style="margin-top:16px"' : ''; ?>>
                <h2 class="sp-section-heading">Update Status</h2>
                <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
                    <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                    <input type="hidden" name="sp_type"   value="smti_stage">
                    <input type="hidden" name="sp_id"     value="0">
                    <input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket_id ); ?>">
                    <div class="sp-field">
                        <select name="stage_id" id="smtiStageSel" data-current="<?php echo esc_attr( $ticket['stage_id'] ); ?>">
                            <?php foreach ( $stage_map as $sid => $slabel ) : ?>
                                <option value="<?php echo esc_attr( $sid ); ?>" <?php selected( $ticket['stage_id'], $sid ); ?>><?php echo esc_html( $slabel ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" id="smtiStageBtn" class="sp-btn sp-btn-primary smti-cta" style="width:100%" disabled>Update Status</button>
                    <div class="smti-unsaved" id="smtiStageUnsaved" style="margin-top:8px;text-align:center">Unsaved &mdash; click to save</div>
                </form>
            </div>
            <?php endif; ?>

            <?php if ( $is_adm && ! empty( $owners ) ) : ?>
            <div class="sp-card sp-form-card" style="margin-top:16px">
                <h2 class="sp-section-heading">Assign Technician</h2>
                <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
                    <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                    <input type="hidden" name="sp_type"   value="smti_assign">
                    <input type="hidden" name="sp_id"     value="0">
                    <input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket_id ); ?>">
                    <div class="sp-field">
                        <select name="owner_id">
                            <option value="">— Select owner —</option>
                            <?php foreach ( $owners as $owner ) : ?>
                                <option value="<?php echo esc_attr( $owner['id'] ); ?>"><?php echo esc_html( $owner['label'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="sp-btn sp-btn-ghost" style="width:100%">Assign</button>
                </form>
            </div>
            <?php endif; ?>

            <div class="sp-card sp-form-card" style="margin-top:16px">
                <h2 class="sp-section-heading">Edit Ticket Info</h2>
                <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" id="smtiEditForm">
                    <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
                    <input type="hidden" name="sp_type"   value="smti_update">
                    <input type="hidden" name="sp_id"     value="0">
                    <input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket_id ); ?>">
                    <div class="sp-field">
                        <label style="font-size:12px">Serial Number</label>
                        <input type="text" name="serial_number" value="<?php echo esc_attr( $ticket['serial_number'] ); ?>" placeholder="e.g. SN-12345">
                    </div>
                    <div class="sp-field">
                        <label style="font-size:12px">Part ID</label>
                        <input type="text" name="part_id" value="<?php echo esc_attr( $ticket['part_id'] ); ?>" placeholder="e.g. 12345-A">
                    </div>
                    <div class="sp-field">
                        <label style="font-size:12px">Equipment Model</label>
                        <input type="text" name="equipment_model" value="<?php echo esc_attr( $ticket['equipment_model'] ); ?>" placeholder="e.g. Treadmill Pro X">
                    </div>
                    <div class="sp-field">
                        <label style="font-size:12px">Company / Facility</label>
                        <input type="text" name="company" value="<?php echo esc_attr( $ticket['company'] ); ?>">
                    </div>
                    <div class="sp-field">
                        <label style="font-size:12px">Location</label>
                        <input type="text" name="location_name" value="<?php echo esc_attr( $ticket['location_name'] ); ?>">
                    </div>
                    <button type="submit" id="smtiEditBtn" class="sp-btn sp-btn-primary smti-cta" style="width:100%" disabled>Save to HubSpot</button>
                    <div class="smti-unsaved" id="smtiEditUnsaved" style="margin-top:8px;text-align:center">Unsaved &mdash; click to save</div>
                </form>
            </div>

            <div class="sp-card sp-form-card" style="margin-top:16px">
                <h2 class="sp-section-heading">Ticket ID</h2>
                <p style="font-size:13px;color:#64748b;margin:0;word-break:break-all"><?php echo esc_html( $ticket_id ); ?></p>
            </div>
        </div>
    </div>

    <style>
    /* Save buttons stay pale + inert until the form actually has something to save,
       then go solid with an attention ring. The ring is deliberately a neutral blue
       so it reads as "click me" regardless of the site's accent color. */
    .smti-cta:disabled{background:#e2e8f0 !important;color:#94a3b8 !important;border-color:#e2e8f0 !important;cursor:not-allowed !important;box-shadow:none !important;}
    .smti-cta:not(:disabled){cursor:pointer;box-shadow:0 0 0 3px rgba(14,165,233,.30);}
    .smti-unsaved{display:none;font-size:11.5px;font-weight:700;color:#0369a1;}
    .smti-unsaved.on{display:block;}
    </style>
    <script>
    (function(){
        function wire(btn, hint, isDirty, els){
            if (!btn) return;
            function sync(){
                var d = !!isDirty();
                btn.disabled = !d;
                if (hint) hint.classList.toggle('on', d);
            }
            els.forEach(function(el){ if (el) { el.addEventListener('input', sync); el.addEventListener('change', sync); } });
            sync();
        }

        // Add Note — dirty when there's text or at least one file.
        var nTxt = document.querySelector('textarea[name="note"]');
        var nFil = document.getElementById('smtiNoteFiles');
        wire(document.getElementById('smtiNoteBtn'), document.getElementById('smtiNoteUnsaved'),
            function(){ return (nTxt && nTxt.value.trim() !== '') || (nFil && nFil.files.length > 0); },
            [nTxt, nFil]);

        // Update Status — dirty only when a different stage is selected.
        var sel = document.getElementById('smtiStageSel');
        wire(document.getElementById('smtiStageBtn'), document.getElementById('smtiStageUnsaved'),
            function(){ return sel && sel.value !== sel.getAttribute('data-current'); }, [sel]);

        // Edit Ticket Info — dirty when any field differs from what loaded.
        var form = document.getElementById('smtiEditForm');
        if (form) {
            var flds = [].slice.call(form.querySelectorAll('input[type="text"]'));
            flds.forEach(function(f){ f.setAttribute('data-orig', f.value); });
            wire(document.getElementById('smtiEditBtn'), document.getElementById('smtiEditUnsaved'),
                function(){ return flds.some(function(f){ return f.value !== f.getAttribute('data-orig'); }); }, flds);
        }
    })();
    </script>
    <?php
    return;
}

// ── List view ─────────────────────────────────────────────────────────────────

$search    = sanitize_text_field( isset( $_GET['q'] ) ? $_GET['q'] : '' );
$searching = ( $search !== '' );
$limit     = 50;
$stage_map = sp_smti_stage_map();
$pipeline  = trim( $settings['ticket_pipeline_id'] );

// Status buckets (the customer's four tabs), mapped from HubSpot stage labels so the
// pipeline can change without code edits. "Waiting on Parts" stays empty (count 0)
// until a matching stage exists in the pipeline.
$sp_buckets = array(
    'open'     => array( 'label' => 'Open',             'color' => '#3b82f6', 'ids' => array() ),
    'review'   => array( 'label' => 'Awaiting Review',  'color' => '#f59e0b', 'ids' => array() ),
    'parts'    => array( 'label' => 'Waiting on Parts', 'color' => '#8b5cf6', 'ids' => array() ),
    'resolved' => array( 'label' => 'Resolved',         'color' => '#10b981', 'ids' => array() ),
);
foreach ( $stage_map as $sid => $slabel ) {
    $s = strtolower( $slabel );
    if ( strpos( $s, 'resolved' ) !== false || strpos( $s, 'closed' ) !== false ) {
        $sp_buckets['resolved']['ids'][] = $sid;
        continue;
    }
    // Open = every active stage (the full working set). Awaiting Review and Waiting
    // on Parts are drill-down subsets that also live inside Open.
    $sp_buckets['open']['ids'][] = $sid;
    if ( strpos( $s, 'awaiting' ) !== false || strpos( $s, 'review' ) !== false ) $sp_buckets['review']['ids'][] = $sid;
    if ( strpos( $s, 'part' ) !== false )                                          $sp_buckets['parts']['ids'][] = $sid;
}

// Active tab — defaults to Open. Ignored while searching (search spans all statuses).
$bucket = sanitize_key( isset( $_GET['status'] ) ? $_GET['status'] : 'open' );
if ( ! isset( $sp_buckets[ $bucket ] ) ) $bucket = 'open';
$active_ids = $sp_buckets[ $bucket ]['ids'];

// Optional exact-stage filter (the "All statuses" dropdown) overrides the tab bucket.
$stage_filter = sanitize_text_field( isset( $_GET['stage'] ) ? $_GET['stage'] : '' );
$stage_valid  = ( $stage_filter !== '' && isset( $stage_map[ $stage_filter ] ) );

// Which tab to highlight: the bucket holding the picked stage (prefer a named subset
// over the catch-all Open), otherwise the active tab.
$hl_bucket = $bucket;
if ( $stage_valid ) {
    foreach ( array( 'resolved', 'review', 'parts', 'open' ) as $bk ) {
        if ( in_array( $stage_filter, $sp_buckets[ $bk ]['ids'], true ) ) { $hl_bucket = $bk; break; }
    }
}

$bucket_empty = ( ! $searching && ! $stage_valid && empty( $active_ids ) );

$base_filter = array();
if ( $pipeline !== '' && $pipeline !== '0' ) {
    $base_filter[] = array( 'propertyName' => 'hs_pipeline', 'operator' => 'EQ', 'value' => $pipeline );
}

$tickets = array();
$total   = 0;
$sp_smti_debug = '';

if ( ! $bucket_empty ) {
    $search_body = array(
        'properties' => sp_smti_ticket_properties(),
        'sorts'      => array( '-createdate' ),
        'limit'      => $limit,
    );
    if ( $searching ) {
        // Precise multi-field search — Request #, Serial, Company, Contact, Subject.
        // HubSpot ORs filterGroups together; CONTAINS_TOKEN with a trailing wildcard
        // matches token prefixes (e.g. "Start" → "Start Corporation", "462" → 4629…).
        // Contact names live in the ticket body, so we search `content` for those
        // (the dedicated smti_contact_name property is often empty).
        $needle = $search . '*';
        $fields = array( 'smti_service_request_number', 'smti_serial_number', 'smti_facility_name', 'content', 'subject' );
        $groups = array();
        foreach ( $fields as $fp ) {
            $g = $base_filter;
            $g[] = array( 'propertyName' => $fp, 'operator' => 'CONTAINS_TOKEN', 'value' => $needle );
            $groups[] = array( 'filters' => $g );
        }
        $search_body['filterGroups'] = $groups;
    } else {
        $filters = $base_filter;
        if ( $stage_valid ) {
            $filters[] = array( 'propertyName' => 'hs_pipeline_stage', 'operator' => 'EQ', 'value' => $stage_filter );
        } elseif ( ! empty( $active_ids ) ) {
            $filters[] = array( 'propertyName' => 'hs_pipeline_stage', 'operator' => 'IN', 'values' => array_values( $active_ids ) );
        }
        if ( ! empty( $filters ) ) $search_body['filterGroups'] = array( array( 'filters' => $filters ) );
    }

    $response = sp_smti_hs_request( 'POST', '/crm/v3/objects/tickets/search', $search_body );
    if ( is_wp_error( $response ) ) {
        $sp_smti_debug = 'WP_Error: ' . $response->get_error_message();
    } elseif ( (int) $response['status'] !== 200 ) {
        $sp_smti_debug = 'HTTP ' . $response['status'] . ': ' . ( isset( $response['body']['message'] ) ? $response['body']['message'] : wp_json_encode( $response['body'] ) );
    } else {
        $total   = isset( $response['body']['total'] ) ? (int) $response['body']['total'] : 0;
        $results = isset( $response['body']['results'] ) ? $response['body']['results'] : array();
        foreach ( $results as $t ) $tickets[] = sp_smti_ticket_summary( $t, $stage_map );
    }
}

// Per-bucket totals for the tab badges (cached 60s to keep the extra searches cheap).
$sp_counts = get_transient( 'sp_smti_bucket_counts_v2_' . md5( $pipeline ) );
if ( ! is_array( $sp_counts ) ) {
    $sp_counts = array();
    foreach ( $sp_buckets as $bk => $b ) {
        if ( empty( $b['ids'] ) ) { $sp_counts[ $bk ] = 0; continue; }
        $cf   = $base_filter;
        $cf[] = array( 'propertyName' => 'hs_pipeline_stage', 'operator' => 'IN', 'values' => array_values( $b['ids'] ) );
        $cr   = sp_smti_hs_request( 'POST', '/crm/v3/objects/tickets/search', array( 'filterGroups' => array( array( 'filters' => $cf ) ), 'limit' => 1 ) );
        $sp_counts[ $bk ] = ( ! is_wp_error( $cr ) && (int) $cr['status'] === 200 && isset( $cr['body']['total'] ) ) ? (int) $cr['body']['total'] : 0;
    }
    set_transient( 'sp_smti_bucket_counts_v2_' . md5( $pipeline ), $sp_counts, 60 );
}
?>
<div class="sp-page-header">
    <h1>Service Requests <span class="sp-count"><?php echo number_format( $total ); ?></span></h1>
    <div class="sp-header-actions">
        <form method="get" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" style="margin:0">
            <input type="hidden" name="view" value="smti">
            <select name="stage" aria-label="Filter by status" onchange="this.form.submit()" style="height:36px">
                <option value="">All statuses</option>
                <?php foreach ( $stage_map as $sid => $slabel ) : ?>
                    <option value="<?php echo esc_attr( $sid ); ?>" <?php selected( $stage_valid ? $stage_filter : '', $sid ); ?>><?php echo esc_html( $slabel ); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <form method="get" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="sp-search-form">
            <input type="hidden" name="view" value="smti">
            <input type="search" name="q" value="<?php echo esc_attr( $search ); ?>" placeholder="Search request #, serial, contact, company…" style="min-width:260px">
            <button type="submit" class="sp-btn sp-btn-ghost sp-btn-sm">Search</button>
            <?php if ( $searching ) : ?><a href="<?php echo esc_url( home_url( '/sp-app/?view=smti' ) ); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">Clear</a><?php endif; ?>
        </form>
    </div>
</div>

<?php if ( ! $sp_smti_debug ) : ?>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px">
    <?php foreach ( $sp_buckets as $bk => $b ) :
        $is_active = ( ! $searching && $bk === $hl_bucket );
        $href      = home_url( '/sp-app/?view=smti&status=' . $bk );
    ?>
    <a href="<?php echo esc_url( $href ); ?>" class="sp-card" style="margin:0;padding:16px 20px;border-top:4px solid <?php echo $b['color']; ?>;text-decoration:none;display:block;transition:box-shadow .12s<?php echo $is_active ? ';box-shadow:0 0 0 2px ' . $b['color'] : ''; ?>">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:<?php echo $is_active ? $b['color'] : '#94a3b8'; ?>"><?php echo esc_html( $b['label'] ); ?></div>
        <div style="font-size:32px;font-weight:800;color:#1e293b;line-height:1;margin-top:6px"><?php echo (int) ( isset( $sp_counts[ $bk ] ) ? $sp_counts[ $bk ] : 0 ); ?></div>
    </a>
    <?php endforeach; ?>
</div>
<?php if ( $searching ) : ?>
<p style="font-size:13px;color:#64748b;margin:-4px 0 14px">Search results across all statuses for &ldquo;<?php echo esc_html( $search ); ?>&rdquo;.</p>
<?php endif; ?>
<?php endif; ?>

<div class="sp-card sp-table-card">
    <table class="sp-table">
        <thead>
            <tr><th>Request #</th><th>Subject</th><th>Company</th><th>Serial #</th><th>Contact</th><th>Status</th><th>Created</th></tr>
        </thead>
        <tbody>
        <?php if ( $sp_smti_debug ) : ?>
            <tr><td colspan="7" style="color:#dc2626;padding:16px;font-family:monospace;font-size:12px"><?php echo esc_html( $sp_smti_debug ); ?></td></tr>
        <?php elseif ( empty( $tickets ) ) : ?>
            <tr><td colspan="7" class="sp-empty">No service requests found<?php echo $search ? ' matching "' . esc_html( $search ) . '"' : ''; ?>.</td></tr>
        <?php else : foreach ( $tickets as $t ) :
            $created = $t['createdate'] ? date( 'M j, Y', strtotime( $t['createdate'] ) ) : '—';
            ?>
            <tr>
                <td><a href="<?php echo esc_url( home_url( '/sp-app/?view=smti&action=detail&id=' . $t['ticket_id'] ) ); ?>" class="sp-link"><?php echo esc_html( $t['service_request_number'] ? $t['service_request_number'] : $t['ticket_id'] ); ?></a></td>
                <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo esc_html( $t['subject'] ); ?></td>
                <td class="sp-muted"><?php echo esc_html( $t['company'] ? $t['company'] : '—' ); ?></td>
                <td class="sp-muted"><?php echo esc_html( $t['serial_number'] ? $t['serial_number'] : '—' ); ?></td>
                <td class="sp-muted"><?php echo esc_html( $t['contact_name'] ? $t['contact_name'] : '—' ); ?></td>
                <td><?php if ( $t['status'] ) : ?><span class="sp-badge" style="background:#f1f5f9;color:#334155"><?php echo esc_html( $t['status'] ); ?></span><?php else : ?>—<?php endif; ?></td>
                <td class="sp-muted"><?php echo esc_html( $created ); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php if ( $total > $limit ) : ?>
        <p style="font-size:12px;color:#94a3b8;padding:12px 16px;margin:0">Showing <?php echo count( $tickets ); ?> of <?php echo number_format( $total ); ?> results. Use search or status filter to narrow down.</p>
    <?php endif; ?>
</div>
<?php

function sp_smti_is_admin_member_check() {
    return function_exists( 'sp_is_admin_member' ) && sp_is_admin_member();
}
