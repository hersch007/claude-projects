<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$is_admin = sp_is_admin_member();
$member   = sp_get_current_team_member();
$my_id    = $member ? (int)$member->id : 0;
$action   = sanitize_key( $_GET['action'] ?? '' );
$view_id  = (int)( $_GET['id'] ?? 0 );

// ── Shared section renderer ───────────────────────────────────────────────────
function sp_wts_client_sections( $row ) {
    $ex = json_decode( $row->exemptions ?? '[]', true ) ?: array();
    $ex_labels = array(
        'emergency_services' => 'Emergency services',
        'exempt_authority'   => 'Exempt from authority',
        'reception_devices'  => 'Reception devices',
        'non_commercial'     => 'Non-commercial uses',
        'amateur_radio'      => 'Amateur radio; meet state laws',
        'wifi_no_towers'     => 'Wi-Fi; no towers',
    );
    $ex_display = implode( ', ', array_map( fn($v) => $ex_labels[$v] ?? $v, $ex ) );

    $sections = array(
        'Legal & Administrative' => array(
            'Ordinance Name'              => $row->ordinance_name,
            'Jurisdiction Name'           => $row->jurisdiction_name,
            'Governing Body (Ordinance)'  => $row->governing_body_ordinance,
            'Governing Body (Appeals)'    => $row->governing_body_appeal,
            'Replaces Existing Ordinance' => $row->replaces_existing,
            'Existing Ordinance Details'  => $row->existing_ordinance_details,
            'Code Designation'            => $row->code_designation,
            'Code Exemptions'             => $ex_display,
            'Additional Exemptions'       => $row->additional_exemptions,
            'Approval Authority'          => $row->approval_authority,
            'Approving Department'        => $row->approving_department,
        ),
        'Approval Authority & Tower Siting' => array(
            'Building Permits Issued By' => $row->building_permits_entity,
            'Siting Priority 1'          => $row->tower_siting_1,
            'Siting Priority 2'          => $row->tower_siting_2,
            'Siting Priority 3'          => $row->tower_siting_3,
            'Siting Priority 4'          => $row->tower_siting_4,
            'Siting Priority 5'          => $row->tower_siting_5,
            'Siting Priority 6'          => $row->tower_siting_6,
            'Siting Priority 7'          => $row->tower_siting_7,
        ),
        'Application Components' => array(
            'Tower Registration Confirmed' => $row->tower_registration_confirmed,
            'Jurisdiction Owned Poles'     => $row->jurisdiction_owned_poles,
        ),
        'Application Fees' => array(
            'Fee -- Colocation / Modification'   => $row->jur_fee_colocation,
            'Fee -- New Tower'                   => $row->jur_fee_new_tower,
            'Fee -- Tower Registration'          => $row->jur_fee_tower_registration,
            'Fee -- Approved Update'             => $row->jur_fee_approved_update,
            'Fee -- SWF New'                     => $row->jur_fee_swf_new,
            'Fee -- SWF Colocation'              => $row->jur_fee_swf_colocation,
            'Fee -- Annual ROW'                  => $row->jur_fee_annual_row,
        ),
        'Insurance & Bonds' => array(
            'Bond -- Colocation or Modification' => $row->ins_colocation,
            'Bond -- Small Wireless Facility'    => $row->ins_small_wireless,
            'Bond -- New Tower'                  => $row->ins_new_tower,
            'CGL Insurance Required'             => $row->ins_cgl,
            'Auto Insurance Required'            => $row->ins_auto,
            'Workers Comp Required'              => $row->ins_workers_comp,
            'Additional Insured Required'        => $row->ins_additional_insured,
        ),
        'Mailing Address' => array(
            'Jurisdiction Name'  => $row->mailing_jur_name,
            'Address'            => $row->mailing_address,
            'City'               => $row->mailing_city,
            'State'              => $row->mailing_state,
            'ZIP'                => $row->mailing_zip,
            'Attention'          => $row->mailing_attention,
        ),
        'Primary Contact' => array(
            'Name'       => $row->primary_name,
            'Position'   => $row->primary_position,
            'Department' => $row->primary_department,
            'Phone'      => $row->primary_phone,
            'Cell'       => $row->primary_cell,
            'Email'      => $row->primary_email,
        ),
        'Secondary Contact' => array(
            'Name'       => $row->secondary_name,
            'Position'   => $row->secondary_position,
            'Department' => $row->secondary_department,
            'Phone'      => $row->secondary_phone,
            'Cell'       => $row->secondary_cell,
            'Email'      => $row->secondary_email,
        ),
        'Additional Contact' => array(
            'Name'       => $row->additional_name,
            'Position'   => $row->additional_position,
            'Department' => $row->additional_department,
            'Phone'      => $row->additional_phone,
            'Cell'       => $row->additional_cell,
            'Email'      => $row->additional_email,
        ),
        'Billing Contact' => array(
            'Same as Primary'     => $row->billing_same_as_primary,
            'Name'                => $row->billing_name,
            'Position'            => $row->billing_position,
            'Phone'               => $row->billing_phone,
            'Receive Invoices by Email' => $row->billing_invoice_email,
            'Mailing Jurisdiction'=> $row->billing_mailing_jur,
            'Mailing Address'     => $row->billing_mailing_address,
            'Mailing City'        => $row->billing_mailing_city,
            'Mailing State'       => $row->billing_mailing_state,
            'Mailing ZIP'         => $row->billing_mailing_zip,
            'Mailing Attention'   => $row->billing_mailing_attention,
            'W-9 Same as Billing' => $row->billing_w9_same,
            'W-9 Contact'         => $row->billing_w9_contact,
        ),
        'E-911 Contact' => array(
            'Name'        => $row->e911_name,
            'Position'    => $row->e911_position,
            'Department'  => $row->e911_department,
            'Phone'       => $row->e911_phone,
            'Email'       => $row->e911_email,
            'Description' => $row->e911_description,
            'Link'        => $row->e911_link,
        ),
    );
    echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">';
    foreach ( $sections as $title => $fields ) {
        $has = array_filter( $fields );
        if ( empty($has) ) continue;
        echo '<div class="sp-card" style="padding:20px;">';
        echo '<h3 style="font-size:.85rem;font-weight:700;color:var(--sp-primary,#2563eb);margin:0 0 14px;">' . esc_html($title) . '</h3>';
        foreach ( $fields as $label => $value ) {
            if ( ! $value ) continue;
            echo '<div style="margin-bottom:10px;">';
            echo '<div style="font-size:.72rem;color:var(--sp-muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em;">' . esc_html($label) . '</div>';
            echo '<div style="font-size:.88rem;">' . esc_html($value) . '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    echo '</div>';
}

// ── Admin: edit jurisdiction contact info ─────────────────────────────────────
if ( $is_admin && $action === 'edit-jurisdiction' && $view_id ) {
    $member = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sp_team WHERE id=%d", $view_id
    ) );
    if ( ! $member ) {
        echo '<div class="sp-notice sp-notice-error">Member not found.</div>';
        return;
    }
    $jur_name = get_option( 'sp_wts_member_jur_' . $view_id, '' );
    ?>
    <div class="sp-view-header">
        <h1>Edit Jurisdiction</h1>
        <a href="<?php echo esc_url( home_url('/sp-app/?view=client-setup') ); ?>" class="sp-btn sp-btn-secondary">Back to List</a>
    </div>
    <?php if ( isset($_GET['error']) && $_GET['error'] === 'noemail' ) : ?>
        <div class="sp-notice sp-notice-error" style="margin-bottom:12px;">Contact email is required.</div>
    <?php elseif ( isset($_GET['error']) && $_GET['error'] === 'exists' ) : ?>
        <div class="sp-notice sp-notice-error" style="margin-bottom:12px;">That email is already in use by another team member.</div>
    <?php endif; ?>
    <div class="sp-card" style="padding:24px;max-width:600px;">
        <form method="post" action="<?php echo esc_url( home_url('/sp-app/') ); ?>">
            <?php wp_nonce_field('sp_form','sp_nonce'); ?>
            <input type="hidden" name="sp_type" value="wts_edit_jurisdiction">
            <input type="hidden" name="sp_id" value="<?php echo (int)$view_id; ?>">
            <div class="sp-field">
                <label>Jurisdiction Name</label>
                <input type="text" name="jurisdiction_name" value="<?php echo esc_attr( $jur_name ); ?>" placeholder="e.g. City of Millbrook">
            </div>
            <div class="sp-form-row">
                <div class="sp-field">
                    <label>Contact Name</label>
                    <input type="text" name="contact_name" value="<?php echo esc_attr( $member->name ); ?>" placeholder="Jane Smith">
                </div>
                <div class="sp-field">
                    <label>Contact Email <span class="sp-required">*</span></label>
                    <input type="email" name="contact_email" value="<?php echo esc_attr( $member->email ); ?>" placeholder="jane@city.gov" required>
                </div>
            </div>
            <button type="submit" class="sp-btn sp-btn-primary">Save Changes</button>
        </form>
    </div>
    <?php
    return;
}

// ── Admin: single submission ───────────────────────────────────────────────────
if ( $is_admin && $action === 'view' && $view_id ) {
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sp_wts_onboarding WHERE id=%d", $view_id
    ) );
    if ( ! $row ) {
        echo '<div class="sp-notice sp-notice-error">Submission not found.</div>';
        return;
    }
    ?>
    <div class="sp-view-header">
        <h1><?php echo esc_html( $row->jurisdiction_name ?: 'Submission #' . $view_id ); ?></h1>
        <a href="<?php echo esc_url( home_url('/sp-app/?view=client-setup') ); ?>" class="sp-btn sp-btn-secondary">Back to List</a>
    </div>
    <?php sp_wts_client_sections( $row ); ?>
    <p style="font-size:.75rem;color:var(--sp-muted);margin-top:12px;">
        Submitted by <?php echo esc_html( $row->submitter_name ?: 'a team member' ); ?>
        on <?php echo esc_html( date('F j, Y', strtotime($row->submitted_at)) ); ?>
    </p>
    <?php
    return;
}

// ── Admin: submissions list ────────────────────────────────────────────────────
if ( $is_admin && $action !== 'my-form' ) {

    // Status flash messages
    if ( isset($_GET['invited']) )     echo '<div class="sp-notice sp-notice-success" style="margin-bottom:12px;">Invitation sent.</div>';
    if ( isset($_GET['resent']) )     echo '<div class="sp-notice sp-notice-success" style="margin-bottom:12px;">Invitation resent.</div>';
    if ( isset($_GET['approved']) )   echo '<div class="sp-notice sp-notice-success" style="margin-bottom:12px;">Jurisdiction approved — client now has full access.</div>';
    if ( isset($_GET['deleted']) )    echo '<div class="sp-notice sp-notice-success" style="margin-bottom:12px;">Jurisdiction removed.</div>';
    if ( isset($_GET['inactivated']) ) echo '<div class="sp-notice sp-notice-success" style="margin-bottom:12px;">Jurisdiction set to inactive.</div>';
    if ( isset($_GET['reactivated']) ) echo '<div class="sp-notice sp-notice-success" style="margin-bottom:12px;">Jurisdiction reactivated.</div>';
    if ( isset($_GET['saved']) )       echo '<div class="sp-notice sp-notice-success" style="margin-bottom:12px;">Jurisdiction updated.</div>';
    if ( isset($_GET['error']) && $_GET['error'] === 'exists' )  echo '<div class="sp-notice sp-notice-error" style="margin-bottom:12px;">A team member with that email already exists.</div>';
    if ( isset($_GET['error']) && $_GET['error'] === 'noemail' ) echo '<div class="sp-notice sp-notice-error" style="margin-bottom:12px;">Contact email is required.</div>';
    ?>
    <div class="sp-view-header">
        <h1>Client Setup</h1>
        <div style="display:flex;gap:10px;">
            <button type="button" onclick="document.getElementById('wts-add-jur-form').classList.toggle('sp-hidden')" class="sp-btn sp-btn-primary">+ Add New Jurisdiction</button>
            <a href="<?php echo esc_url( home_url('/sp-app/?view=client-setup&action=my-form') ); ?>" class="sp-btn sp-btn-secondary">My Questionnaire</a>
        </div>
    </div>

    <!-- Add New Jurisdiction inline form -->
    <div id="wts-add-jur-form" class="sp-card sp-hidden" style="padding:20px;margin-bottom:20px;">
        <h3 style="font-size:.9rem;font-weight:700;margin:0 0 16px;">New Jurisdiction</h3>
        <form method="post" action="<?php echo esc_url( home_url('/sp-app/') ); ?>">
            <?php wp_nonce_field('sp_form','sp_nonce'); ?>
            <input type="hidden" name="sp_type" value="wts_add_jurisdiction">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end;">
                <div class="sp-field" style="margin:0;">
                    <label>Jurisdiction Name</label>
                    <input type="text" name="jurisdiction_name" placeholder="e.g. City of Millbrook">
                </div>
                <div class="sp-field" style="margin:0;">
                    <label>Contact Name</label>
                    <input type="text" name="contact_name" placeholder="Jane Smith">
                </div>
                <div class="sp-field" style="margin:0;">
                    <label>Contact Email <span class="sp-required">*</span></label>
                    <input type="email" name="contact_email" placeholder="jane@city.gov" required>
                </div>
                <div>
                    <button type="submit" class="sp-btn sp-btn-primary" style="white-space:nowrap;">Send Invite</button>
                </div>
            </div>
            <p style="font-size:.75rem;color:var(--sp-muted);margin:10px 0 0;">An email with a one-time login link will be sent. The link expires in 7 days.</p>
        </form>
    </div>
    <style>.sp-hidden{display:none!important}</style>

    <?php
    // Collect all onboard status options
    $status_opts = $wpdb->get_results(
        "SELECT option_name, option_value FROM {$wpdb->options}
         WHERE option_name LIKE 'sp\_wts\_member\_status\_%'"
    );
    $member_statuses = array(); // member_id => 'invited'|'pending'
    foreach ( $status_opts as $opt ) {
        $mid = (int) str_replace( 'sp_wts_member_status_', '', $opt->option_name );
        if ( $mid ) $member_statuses[ $mid ] = $opt->option_value;
    }

    // Submitted questionnaires
    $submissions = $wpdb->get_results(
        "SELECT o.*, t.name AS team_name, t.email AS team_email
         FROM {$wpdb->prefix}sp_wts_onboarding o
         LEFT JOIN {$wpdb->prefix}sp_team t ON t.id = o.member_id
         ORDER BY o.submitted_at DESC"
    );
    $submitted_ids = array_map( fn($r) => (int)$r->member_id, (array)$submissions );

    // Invited members who haven't submitted yet
    $invited_rows = array();
    if ( ! empty( $member_statuses ) ) {
        $ids_csv = implode(',', array_keys($member_statuses));
        $invited_members = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}sp_team WHERE id IN ($ids_csv)"
        );
        foreach ( $invited_members as $m ) {
            if ( ! in_array( (int)$m->id, $submitted_ids ) ) {
                $invited_rows[] = $m;
            }
        }
    }

    $badge_styles = array(
        'invited'  => 'background:#fef9c3;color:#854d0e;',
        'pending'  => 'background:#fef3c7;color:#92400e;',
        'active'   => 'background:#dcfce7;color:#166534;',
        'inactive' => 'background:#f1f5f9;color:#64748b;',
    );

    if ( empty($submissions) && empty($invited_rows) ): ?>
        <div class="sp-card" style="padding:20px;"><p class="sp-empty">No jurisdictions yet. Click "+ Add New Jurisdiction" to get started.</p></div>
    <?php else: ?>
        <div class="sp-card">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid var(--sp-border,#e5e7eb);">
                        <th style="padding:10px 14px;text-align:left;font-size:.78rem;color:var(--sp-muted);font-weight:600;">Jurisdiction</th>
                        <th style="padding:10px 14px;text-align:left;font-size:.78rem;color:var(--sp-muted);font-weight:600;">Contact</th>
                        <th style="padding:10px 14px;text-align:left;font-size:.78rem;color:var(--sp-muted);font-weight:600;">Status</th>
                        <th style="padding:10px 14px;text-align:left;font-size:.78rem;color:var(--sp-muted);font-weight:600;">Date</th>
                        <th style="width:140px;"></th>
                    </tr>
                </thead>
                <tbody>

                <?php foreach ( $invited_rows as $m ):
                    $jur  = get_option( 'sp_wts_member_jur_' . $m->id, '' );
                    $bs   = $badge_styles['invited'];
                ?>
                    <tr style="border-bottom:1px solid var(--sp-border,#f3f4f6);">
                        <td style="padding:10px 14px;font-size:.88rem;font-weight:600;"><?php echo esc_html( $jur ?: '—' ); ?></td>
                        <td style="padding:10px 14px;font-size:.88rem;color:var(--sp-muted);"><?php echo esc_html( $m->name ); ?><br><span style="font-size:.75rem;"><?php echo esc_html( $m->email ); ?> &nbsp;<span style="color:var(--sp-muted);opacity:.65;">· <?php echo esc_html( $m->name ); ?></span></span></td>
                        <td style="padding:10px 14px;">
                            <span style="<?php echo $bs; ?>font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:99px;">Invited</span>
                        </td>
                        <td style="padding:10px 14px;font-size:.88rem;color:var(--sp-muted);">—</td>
                        <td style="padding:10px 14px;text-align:right;display:flex;gap:6px;justify-content:flex-end;align-items:center;">
                            <a href="<?php echo esc_url( home_url('/sp-app/?view=client-setup&action=edit-jurisdiction&id='.(int)$m->id) ); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">Edit</a>
                            <form method="post" action="<?php echo esc_url( home_url('/sp-app/') ); ?>" style="display:inline;">
                                <?php wp_nonce_field('sp_form','sp_nonce'); ?>
                                <input type="hidden" name="sp_type" value="wts_resend_invite">
                                <input type="hidden" name="sp_id" value="<?php echo (int)$m->id; ?>">
                                <button type="submit" class="sp-btn sp-btn-secondary sp-btn-sm">Resend Invite</button>
                            </form>
                            <form method="post" action="<?php echo esc_url( home_url('/sp-app/') ); ?>" style="display:inline;" onsubmit="return confirm('Remove this jurisdiction invite? This cannot be undone.');">
                                <?php wp_nonce_field('sp_form','sp_nonce'); ?>
                                <input type="hidden" name="sp_type" value="wts_delete_jurisdiction">
                                <input type="hidden" name="sp_id" value="<?php echo (int)$m->id; ?>">
                                <button type="submit" class="sp-btn sp-btn-sm" style="background:#fee2e2;color:#991b1b;border:none;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php foreach ( $submissions as $r ):
                    $st     = $member_statuses[(int)$r->member_id] ?? 'active';
                    $bs     = $badge_styles[ $st ] ?? $badge_styles['active'];
                    $label  = $st === 'pending' ? 'Pending Review' : ( $st === 'inactive' ? 'Inactive' : 'Active' );
                ?>
                    <tr style="border-bottom:1px solid var(--sp-border,#f3f4f6);">
                        <td style="padding:10px 14px;font-size:.88rem;font-weight:600;"><?php echo esc_html( $r->jurisdiction_name ?: '—' ); ?></td>
                        <td style="padding:10px 14px;font-size:.88rem;color:var(--sp-muted);">
                            <?php echo esc_html( $r->submitter_name ?: $r->team_name ?: '—' ); ?>
                            <?php if ( $r->team_email ) : ?>
                            <br><span style="font-size:.75rem;"><?php echo esc_html( $r->team_email ); ?> &nbsp;<span style="color:var(--sp-muted);opacity:.65;">· <?php echo esc_html( $r->team_name ?: '—' ); ?></span></span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:10px 14px;">
                            <span style="<?php echo $bs; ?>font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:99px;"><?php echo esc_html($label); ?></span>
                        </td>
                        <td style="padding:10px 14px;font-size:.88rem;color:var(--sp-muted);"><?php echo esc_html( date('M j, Y', strtotime($r->submitted_at)) ); ?></td>
                        <td style="padding:10px 14px;text-align:right;display:flex;gap:6px;justify-content:flex-end;align-items:center;">
                            <a href="<?php echo esc_url( home_url('/sp-app/?view=client-setup&action=view&id='.$r->id) ); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">View</a>
                            <a href="<?php echo esc_url( home_url('/sp-app/?view=client-setup&action=edit-jurisdiction&id='.(int)$r->member_id) ); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">Edit</a>
                            <?php if ( $st === 'pending' ): ?>
                            <form method="post" action="<?php echo esc_url( home_url('/sp-app/') ); ?>" style="display:inline;">
                                <?php wp_nonce_field('sp_form','sp_nonce'); ?>
                                <input type="hidden" name="sp_type" value="wts_approve_jurisdiction">
                                <input type="hidden" name="sp_id" value="<?php echo (int)$r->member_id; ?>">
                                <button type="submit" class="sp-btn sp-btn-primary sp-btn-sm">Approve</button>
                            </form>
                            <?php elseif ( $st === 'active' ): ?>
                            <form method="post" action="<?php echo esc_url( home_url('/sp-app/') ); ?>" style="display:inline;" onsubmit="return confirm('Set this jurisdiction inactive? They will lose access until reactivated. Are you sure?');">
                                <?php wp_nonce_field('sp_form','sp_nonce'); ?>
                                <input type="hidden" name="sp_type" value="wts_set_inactive">
                                <input type="hidden" name="sp_id" value="<?php echo (int)$r->member_id; ?>">
                                <button type="submit" class="sp-btn sp-btn-sm" style="background:#fef3c7;color:#92400e;border:none;">Make Inactive</button>
                            </form>
                            <?php elseif ( $st === 'inactive' ): ?>
                            <form method="post" action="<?php echo esc_url( home_url('/sp-app/') ); ?>" style="display:inline;">
                                <?php wp_nonce_field('sp_form','sp_nonce'); ?>
                                <input type="hidden" name="sp_type" value="wts_reactivate">
                                <input type="hidden" name="sp_id" value="<?php echo (int)$r->member_id; ?>">
                                <button type="submit" class="sp-btn sp-btn-sm" style="background:#dcfce7;color:#166534;border:none;">Reactivate</button>
                            </form>
                            <?php endif; ?>
                            <form method="post" action="<?php echo esc_url( home_url('/sp-app/') ); ?>" style="display:inline;" onsubmit="return confirm('Remove this jurisdiction? This cannot be undone.');">
                                <?php wp_nonce_field('sp_form','sp_nonce'); ?>
                                <input type="hidden" name="sp_type" value="wts_delete_jurisdiction">
                                <input type="hidden" name="sp_id" value="<?php echo (int)$r->member_id; ?>">
                                <button type="submit" class="sp-btn sp-btn-sm" style="background:#fee2e2;color:#991b1b;border:none;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                </tbody>
            </table>
        </div>
    <?php endif;
    return;
}

// ── Member: check for existing submission ─────────────────────────────────────
$existing = $my_id ? $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}sp_wts_onboarding WHERE member_id=%d", $my_id
) ) : null;

// Non-admins cannot edit a submitted questionnaire
if ( $existing && ! $is_admin && $action === 'edit' ) {
    wp_redirect( home_url( '/sp-app/?view=client-setup' ) ); exit;
}

if ( $existing && $action !== 'edit' ) {
    ?>
    <div class="sp-view-header">
        <h1>Client Setup Questionnaire</h1>
        <?php if ( $is_admin ) : ?>
        <a href="<?php echo esc_url( home_url('/sp-app/?view=client-setup&action=edit') ); ?>" class="sp-btn sp-btn-secondary">Edit Responses</a>
        <?php endif; ?>
    </div>
    <?php if ( isset($_GET['submitted']) ): ?>
        <div class="sp-notice sp-notice-success" style="margin-bottom:16px;">Questionnaire saved successfully.</div>
    <?php else: ?>
        <div class="sp-notice sp-notice-success" style="margin-bottom:16px;">Questionnaire completed on <?php echo esc_html( date('F j, Y', strtotime($existing->submitted_at)) ); ?>.</div>
    <?php endif; ?>
    <?php sp_wts_client_sections( $existing ); ?>
    <?php
    return;
}

// ── 11-step form ──────────────────────────────────────────────────────────────
$d    = $existing;
$v    = fn( $key, $default = '' ) => $d ? ( $d->$key ?? $default ) : $default;
$spid = $existing ? (int)$existing->id : 0;
$ex_checked = json_decode( $v('exemptions', '[]'), true ) ?: array();
$exemption_options = array(
    'emergency_services' => 'Emergency services',
    'exempt_authority'   => 'Exempt from authority',
    'reception_devices'  => 'Reception devices',
    'non_commercial'     => 'Non-commercial uses',
    'amateur_radio'      => 'Amateur radio; meet state laws',
    'wifi_no_towers'     => 'Wi-Fi; no towers',
);
?>
<div class="sp-view-header">
    <h1>Client Setup Questionnaire</h1>
    <?php if ( $existing ): ?>
        <a href="<?php echo esc_url( home_url('/sp-app/?view=client-setup') ); ?>" class="sp-btn sp-btn-secondary">Cancel</a>
    <?php endif; ?>
</div>

<div style="max-width:760px;">

<div style="height:8px;background:var(--sp-border,#e5e7eb);border-radius:99px;margin:0 0 24px;overflow:hidden;">
    <div id="wts-progress" style="height:100%;width:9.09%;background:var(--sp-primary,#2563eb);border-radius:99px;transition:width 0.3s ease;"></div>
</div>

<form method="post" action="<?php echo esc_url( home_url('/sp-app/') ); ?>" id="wts-onb-form">
    <?php wp_nonce_field('sp_form','sp_nonce'); ?>
    <input type="hidden" name="sp_type" value="client_setup">
    <input type="hidden" name="sp_id"   value="<?php echo $spid; ?>">

    <!-- STEP 1: Introduction -->
    <div id="wts-step-1" class="wts-onb-step">
        <div class="sp-card" style="padding:24px;">
            <h3 style="font-size:1rem;font-weight:700;margin:0 0 16px;">Step 1 of 11 &mdash; Introduction</h3>
            <div style="line-height:1.75;font-size:.88rem;color:var(--sp-text);">
                <p><strong>Wireless Tower Solutions' effectiveness is dependent on an online application process.</strong> For that process to work best for you and WTS, it is important that each client define some critical variables. The following questionnaire should be completed to define your jurisdiction's preferences, ordinance criteria and personnel that will be involved in the process.</p>
                <p>Your WTS representative will work with you to complete this on-boarding process, answer questions, and help insure everything works smoothly from the start. Should personnel change, updates to the administrative side of the on-line systems are relatively easy to do. Other aspects, such as the ordinance and resolutions, once implemented, and its on-line application process are more difficult to change.</p>
                <p>The three sections include questions geared to 1) your ordinance, 2) the people that work with the approval process, and 3) the people involved with the billing process.</p>
                <p><strong>Our suggested process:</strong></p>
                <ol style="margin:0 0 8px 20px;">
                    <li>We complete this questionnaire together. We keep it to the basics for simplicity and clarity.</li>
                    <li>Based on this questionnaire's results, WTS will provide you with a DRAFT Ordinance that reflects the goals of the Jurisdiction as they relate to wireless facilities.</li>
                    <li>All involved staff and legal counsel use the proposed ordinance for review and discussion to obtain the final form for adoption by the appropriate governing body.</li>
                </ol>
            </div>
            <div style="text-align:center;margin-top:24px;">
                <button type="button" onclick="wtsNext(1)" class="sp-btn sp-btn-primary" style="padding:12px 40px;font-size:1rem;">I Agree &mdash; Let's Get Started</button>
            </div>
        </div>
    </div>

    <!-- STEP 2: Legal & Administrative -->
    <div id="wts-step-2" class="wts-onb-step" style="display:none;">
        <div class="sp-card" style="padding:24px;">
            <h3 style="font-size:1rem;font-weight:700;margin:0 0 16px;">Step 2 of 11 &mdash; Legal & Administrative Items</h3>
            <div class="sp-field"><label>Ordinance Name</label><input type="text" name="ordinance_name" value="<?php echo esc_attr($v('ordinance_name','Ordinance Regulating the Siting and Permitting of Wireless Telecommunications Facilities')); ?>"></div>
            <div class="sp-field"><label>Legal Name of Jurisdiction <span class="sp-required">*</span></label><input type="text" name="jurisdiction_name" value="<?php echo esc_attr($v('jurisdiction_name')); ?>" required></div>
            <div class="sp-field"><label>Governing Body (approves ordinance)</label><input type="text" name="governing_body_ordinance" value="<?php echo esc_attr($v('governing_body_ordinance')); ?>"></div>
            <div class="sp-field"><label>Governing Body (appeals)</label><input type="text" name="governing_body_appeal" value="<?php echo esc_attr($v('governing_body_appeal')); ?>"></div>
            <div class="sp-field">
                <label>Does this replace an existing ordinance?</label>
                <div style="display:flex;gap:20px;margin-top:6px;">
                    <label style="font-weight:normal;display:flex;gap:6px;align-items:center;"><input type="radio" name="replaces_existing" value="No" <?php checked($v('replaces_existing','No'),'No'); ?>> No</label>
                    <label style="font-weight:normal;display:flex;gap:6px;align-items:center;"><input type="radio" name="replaces_existing" value="Yes" <?php checked($v('replaces_existing','No'),'Yes'); ?>> Yes</label>
                </div>
            </div>
            <div class="sp-field"><label>If yes, details</label><input type="text" name="existing_ordinance_details" value="<?php echo esc_attr($v('existing_ordinance_details')); ?>"></div>
            <div class="sp-field"><label>Code Designation</label><input type="text" name="code_designation" value="<?php echo esc_attr($v('code_designation')); ?>"></div>
            <div class="sp-field">
                <label>Code Exemptions</label>
                <div style="display:flex;flex-direction:column;gap:8px;margin-top:2px;">
                <?php foreach ( $exemption_options as $val => $lbl ): ?>
                    <label style="display:flex;align-items:center;gap:10px;font-size:.88rem;font-weight:500;text-transform:none;letter-spacing:0;color:var(--sp-text,#1e293b);cursor:pointer;">
                        <input type="checkbox" name="exemptions[]" value="<?php echo esc_attr($val); ?>" <?php checked( in_array($val,$ex_checked), true ); ?> style="width:16px;height:16px;min-width:16px;border-radius:4px;border:1.5px solid #94a3b8;padding:0;cursor:pointer;accent-color:var(--sp-primary,#2563eb);">
                        <?php echo esc_html($lbl); ?>
                    </label>
                <?php endforeach; ?>
                </div>
            </div>
            <div class="sp-field"><label>Additional Exemptions</label><textarea name="additional_exemptions" rows="3"><?php echo esc_textarea($v('additional_exemptions')); ?></textarea></div>
            <div class="sp-field"><label>Approval Authority <span style="font-size:.75rem;font-weight:400;color:var(--sp-muted);">(title / board / committee)</span></label><input type="text" name="approval_authority" value="<?php echo esc_attr($v('approval_authority')); ?>"></div>
            <div class="sp-field"><label>Approving Department</label><input type="text" name="approving_department" value="<?php echo esc_attr($v('approving_department')); ?>"></div>
            <div style="display:flex;justify-content:space-between;margin-top:8px;">
                <button type="button" onclick="wtsPrev(2)" class="sp-btn sp-btn-secondary">Back</button>
                <button type="button" onclick="wtsNext(2)" class="sp-btn sp-btn-primary">Next</button>
            </div>
        </div>
    </div>

    <!-- STEP 3: Approval Authority & Tower Siting -->
    <div id="wts-step-3" class="wts-onb-step" style="display:none;">
        <div class="sp-card" style="padding:24px;">
            <h3 style="font-size:1rem;font-weight:700;margin:0 0 16px;">Step 3 of 11 &mdash; Tower Siting Priorities</h3>
            <div class="sp-field"><label>Building Permits Issued By</label><input type="text" name="building_permits_entity" value="<?php echo esc_attr($v('building_permits_entity')); ?>"></div>
            <div class="sp-field">
                <label>Tower Siting Priorities <span style="font-size:.75rem;font-weight:400;color:var(--sp-muted);">(1 = highest priority)</span></label>
                <?php for ( $i = 1; $i <= 7; $i++ ): ?>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                        <span style="font-size:.8rem;color:var(--sp-muted);width:18px;flex-shrink:0;text-align:right;"><?php echo $i; ?>.</span>
                        <input type="text" name="tower_siting_<?php echo $i; ?>" value="<?php echo esc_attr($v('tower_siting_'.$i)); ?>" style="flex:1;">
                    </div>
                <?php endfor; ?>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:8px;">
                <button type="button" onclick="wtsPrev(3)" class="sp-btn sp-btn-secondary">Back</button>
                <button type="button" onclick="wtsNext(3)" class="sp-btn sp-btn-primary">Next</button>
            </div>
        </div>
    </div>

    <!-- STEP 4: Application Components -->
    <div id="wts-step-4" class="wts-onb-step" style="display:none;">
        <div class="sp-card" style="padding:24px;">
            <h3 style="font-size:1rem;font-weight:700;margin:0 0 16px;">Step 4 of 11 &mdash; Application Components</h3>
            <div class="sp-field">
                <label>Tower Registration Component Confirmed?</label>
                <div style="display:flex;gap:20px;margin-top:6px;">
                    <label style="font-weight:normal;display:flex;gap:6px;align-items:center;"><input type="radio" name="tower_registration_confirmed" value="Yes" <?php checked($v('tower_registration_confirmed','Yes'),'Yes'); ?>> Yes</label>
                    <label style="font-weight:normal;display:flex;gap:6px;align-items:center;"><input type="radio" name="tower_registration_confirmed" value="No" <?php checked($v('tower_registration_confirmed','Yes'),'No'); ?>> No</label>
                </div>
            </div>
            <div class="sp-field">
                <label>Jurisdiction Owned Poles / Special Structures?</label>
                <div style="display:flex;gap:20px;margin-top:6px;">
                    <label style="font-weight:normal;display:flex;gap:6px;align-items:center;"><input type="radio" name="jurisdiction_owned_poles" value="Yes" <?php checked($v('jurisdiction_owned_poles','No'),'Yes'); ?>> Yes</label>
                    <label style="font-weight:normal;display:flex;gap:6px;align-items:center;"><input type="radio" name="jurisdiction_owned_poles" value="No" <?php checked($v('jurisdiction_owned_poles','No'),'No'); ?>> No</label>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:8px;">
                <button type="button" onclick="wtsPrev(4)" class="sp-btn sp-btn-secondary">Back</button>
                <button type="button" onclick="wtsNext(4)" class="sp-btn sp-btn-primary">Next</button>
            </div>
        </div>
    </div>

    <!-- STEP 5: Application Fees -->
    <div id="wts-step-5" class="wts-onb-step" style="display:none;">
        <div class="sp-card" style="padding:24px;">
            <h3 style="font-size:1rem;font-weight:700;margin:0 0 16px;">Step 5 of 11 &mdash; Application Fees</h3>
            <div class="sp-field"><label>Fee &mdash; Colocation / Modification / Eligible Facility</label><input type="text" name="jur_fee_colocation" value="<?php echo esc_attr($v('jur_fee_colocation','$2,500 - $3,600')); ?>"></div>
            <div class="sp-field"><label>Fee &mdash; New Tower</label><input type="text" name="jur_fee_new_tower" value="<?php echo esc_attr($v('jur_fee_new_tower','$5,000 - $7,000')); ?>"></div>
            <div class="sp-field"><label>Fee &mdash; Tower Registration</label><input type="text" name="jur_fee_tower_registration" value="<?php echo esc_attr($v('jur_fee_tower_registration')); ?>"></div>
            <div class="sp-field"><label>Fee &mdash; Approved Update</label><input type="text" name="jur_fee_approved_update" value="<?php echo esc_attr($v('jur_fee_approved_update')); ?>"></div>
            <div class="sp-field"><label>Fee &mdash; Small Wireless Facility (New)</label><input type="text" name="jur_fee_swf_new" value="<?php echo esc_attr($v('jur_fee_swf_new')); ?>"></div>
            <div class="sp-field"><label>Fee &mdash; Small Wireless Facility (Colocation)</label><input type="text" name="jur_fee_swf_colocation" value="<?php echo esc_attr($v('jur_fee_swf_colocation')); ?>"></div>
            <div class="sp-field"><label>Fee &mdash; Annual ROW</label><input type="text" name="jur_fee_annual_row" value="<?php echo esc_attr($v('jur_fee_annual_row')); ?>"></div>
            <div style="display:flex;justify-content:space-between;margin-top:8px;">
                <button type="button" onclick="wtsPrev(5)" class="sp-btn sp-btn-secondary">Back</button>
                <button type="button" onclick="wtsNext(5)" class="sp-btn sp-btn-primary">Next</button>
            </div>
        </div>
    </div>

    <!-- STEP 6: Insurance & Bonds + Mailing Address -->
    <div id="wts-step-6" class="wts-onb-step" style="display:none;">
        <div class="sp-card" style="padding:24px;">
            <h3 style="font-size:1rem;font-weight:700;margin:0 0 16px;">Step 6 of 11 &mdash; Insurance, Bonds & Mailing Address</h3>
            <h4 style="font-size:.88rem;font-weight:700;margin:0 0 12px;color:var(--sp-muted);">Bonds</h4>
            <div class="sp-field"><label>Bond &mdash; Colocation or Modification</label><input type="text" name="ins_colocation" value="<?php echo esc_attr($v('ins_colocation','$25,000')); ?>"></div>
            <div class="sp-field"><label>Bond &mdash; Small Wireless Facility</label><input type="text" name="ins_small_wireless" value="<?php echo esc_attr($v('ins_small_wireless','$15,000')); ?>"></div>
            <div class="sp-field"><label>Bond &mdash; New Tower</label><input type="text" name="ins_new_tower" value="<?php echo esc_attr($v('ins_new_tower','$75,000')); ?>"></div>
            <h4 style="font-size:.88rem;font-weight:700;margin:16px 0 12px;color:var(--sp-muted);">Insurance Requirements</h4>
            <div class="sp-field"><label>CGL (Commercial General Liability)</label><input type="text" name="ins_cgl" value="<?php echo esc_attr($v('ins_cgl')); ?>" placeholder="e.g. $1,000,000"></div>
            <div class="sp-field"><label>Auto Liability</label><input type="text" name="ins_auto" value="<?php echo esc_attr($v('ins_auto')); ?>" placeholder="e.g. $1,000,000"></div>
            <div class="sp-field"><label>Workers' Compensation</label><input type="text" name="ins_workers_comp" value="<?php echo esc_attr($v('ins_workers_comp')); ?>" placeholder="e.g. Statutory"></div>
            <div class="sp-field">
                <label>Additional Insured Required?</label>
                <div style="display:flex;gap:20px;margin-top:6px;">
                    <label style="font-weight:normal;display:flex;gap:6px;align-items:center;"><input type="radio" name="ins_additional_insured" value="Yes" <?php checked($v('ins_additional_insured','Yes'),'Yes'); ?>> Yes</label>
                    <label style="font-weight:normal;display:flex;gap:6px;align-items:center;"><input type="radio" name="ins_additional_insured" value="No" <?php checked($v('ins_additional_insured','Yes'),'No'); ?>> No</label>
                </div>
            </div>
            <h4 style="font-size:.88rem;font-weight:700;margin:16px 0 12px;color:var(--sp-muted);">Official Mailing Address</h4>
            <div class="sp-field"><label>Jurisdiction Name (for mail)</label><input type="text" name="mailing_jur_name" value="<?php echo esc_attr($v('mailing_jur_name')); ?>"></div>
            <div class="sp-field"><label>Street Address</label><input type="text" name="mailing_address" value="<?php echo esc_attr($v('mailing_address')); ?>"></div>
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px;">
                <div class="sp-field" style="margin:0;"><label>City</label><input type="text" name="mailing_city" value="<?php echo esc_attr($v('mailing_city')); ?>"></div>
                <div class="sp-field" style="margin:0;"><label>State</label><input type="text" name="mailing_state" value="<?php echo esc_attr($v('mailing_state')); ?>"></div>
                <div class="sp-field" style="margin:0;"><label>ZIP</label><input type="text" name="mailing_zip" value="<?php echo esc_attr($v('mailing_zip')); ?>"></div>
            </div>
            <div class="sp-field"><label>Attention</label><input type="text" name="mailing_attention" value="<?php echo esc_attr($v('mailing_attention')); ?>"></div>
            <div style="display:flex;justify-content:space-between;margin-top:16px;">
                <button type="button" onclick="wtsPrev(6)" class="sp-btn sp-btn-secondary">Back</button>
                <button type="button" onclick="wtsNext(6)" class="sp-btn sp-btn-primary">Next</button>
            </div>
        </div>
    </div>

    <!-- STEP 7: Primary Contact -->
    <div id="wts-step-7" class="wts-onb-step" style="display:none;">
        <div class="sp-card" style="padding:24px;">
            <h3 style="font-size:1rem;font-weight:700;margin:0 0 16px;">Step 7 of 11 &mdash; Primary Contact</h3>
            <p style="font-size:.83rem;color:var(--sp-muted);margin:0 0 16px;">The primary contact handles day-to-day wireless application processing.</p>
            <div class="sp-field"><label>Name <span class="sp-required">*</span></label><input type="text" name="primary_name" value="<?php echo esc_attr($v('primary_name')); ?>" required></div>
            <div class="sp-field"><label>Title / Position</label><input type="text" name="primary_position" value="<?php echo esc_attr($v('primary_position')); ?>"></div>
            <div class="sp-field"><label>Department</label><input type="text" name="primary_department" value="<?php echo esc_attr($v('primary_department')); ?>"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="sp-field" style="margin:0;"><label>Phone</label><input type="text" name="primary_phone" value="<?php echo esc_attr($v('primary_phone')); ?>"></div>
                <div class="sp-field" style="margin:0;"><label>Cell</label><input type="text" name="primary_cell" value="<?php echo esc_attr($v('primary_cell')); ?>"></div>
            </div>
            <div class="sp-field"><label>Email <span class="sp-required">*</span></label><input type="email" name="primary_email" value="<?php echo esc_attr($v('primary_email')); ?>" required></div>
            <div style="display:flex;justify-content:space-between;margin-top:8px;">
                <button type="button" onclick="wtsPrev(7)" class="sp-btn sp-btn-secondary">Back</button>
                <button type="button" onclick="wtsNext(7)" class="sp-btn sp-btn-primary">Next</button>
            </div>
        </div>
    </div>

    <!-- STEP 8: Secondary & Additional Contacts -->
    <div id="wts-step-8" class="wts-onb-step" style="display:none;">
        <div class="sp-card" style="padding:24px;">
            <h3 style="font-size:1rem;font-weight:700;margin:0 0 16px;">Step 8 of 11 &mdash; Secondary & Additional Contacts</h3>
            <h4 style="font-size:.88rem;font-weight:700;margin:0 0 12px;color:var(--sp-muted);">Secondary Contact</h4>
            <div class="sp-field"><label>Name</label><input type="text" name="secondary_name" value="<?php echo esc_attr($v('secondary_name')); ?>"></div>
            <div class="sp-field"><label>Title / Position</label><input type="text" name="secondary_position" value="<?php echo esc_attr($v('secondary_position')); ?>"></div>
            <div class="sp-field"><label>Department</label><input type="text" name="secondary_department" value="<?php echo esc_attr($v('secondary_department')); ?>"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="sp-field" style="margin:0;"><label>Phone</label><input type="text" name="secondary_phone" value="<?php echo esc_attr($v('secondary_phone')); ?>"></div>
                <div class="sp-field" style="margin:0;"><label>Cell</label><input type="text" name="secondary_cell" value="<?php echo esc_attr($v('secondary_cell')); ?>"></div>
            </div>
            <div class="sp-field"><label>Email</label><input type="email" name="secondary_email" value="<?php echo esc_attr($v('secondary_email')); ?>"></div>
            <h4 style="font-size:.88rem;font-weight:700;margin:16px 0 12px;color:var(--sp-muted);">Additional Contact <span style="font-size:.75rem;font-weight:400;">(optional)</span></h4>
            <div class="sp-field"><label>Name</label><input type="text" name="additional_name" value="<?php echo esc_attr($v('additional_name')); ?>"></div>
            <div class="sp-field"><label>Title / Position</label><input type="text" name="additional_position" value="<?php echo esc_attr($v('additional_position')); ?>"></div>
            <div class="sp-field"><label>Department</label><input type="text" name="additional_department" value="<?php echo esc_attr($v('additional_department')); ?>"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="sp-field" style="margin:0;"><label>Phone</label><input type="text" name="additional_phone" value="<?php echo esc_attr($v('additional_phone')); ?>"></div>
                <div class="sp-field" style="margin:0;"><label>Cell</label><input type="text" name="additional_cell" value="<?php echo esc_attr($v('additional_cell')); ?>"></div>
            </div>
            <div class="sp-field"><label>Email</label><input type="email" name="additional_email" value="<?php echo esc_attr($v('additional_email')); ?>"></div>
            <div style="display:flex;justify-content:space-between;margin-top:8px;">
                <button type="button" onclick="wtsPrev(8)" class="sp-btn sp-btn-secondary">Back</button>
                <button type="button" onclick="wtsNext(8)" class="sp-btn sp-btn-primary">Next</button>
            </div>
        </div>
    </div>

    <!-- STEP 9: Billing Contact -->
    <div id="wts-step-9" class="wts-onb-step" style="display:none;">
        <div class="sp-card" style="padding:24px;">
            <h3 style="font-size:1rem;font-weight:700;margin:0 0 16px;">Step 9 of 11 &mdash; Billing Contact</h3>
            <div class="sp-field">
                <label>Same as Primary Contact?</label>
                <div style="display:flex;gap:20px;margin-top:6px;">
                    <label style="font-weight:normal;display:flex;gap:6px;align-items:center;"><input type="radio" name="billing_same_as_primary" value="Yes" <?php checked($v('billing_same_as_primary','Yes'),'Yes'); ?>> Yes</label>
                    <label style="font-weight:normal;display:flex;gap:6px;align-items:center;"><input type="radio" name="billing_same_as_primary" value="No" <?php checked($v('billing_same_as_primary','Yes'),'No'); ?>> No</label>
                </div>
            </div>
            <div class="sp-field"><label>Name</label><input type="text" name="billing_name" value="<?php echo esc_attr($v('billing_name')); ?>"></div>
            <div class="sp-field"><label>Title / Position</label><input type="text" name="billing_position" value="<?php echo esc_attr($v('billing_position')); ?>"></div>
            <div class="sp-field"><label>Phone</label><input type="text" name="billing_phone" value="<?php echo esc_attr($v('billing_phone')); ?>"></div>
            <div class="sp-field">
                <label>Receive Invoices by Email?</label>
                <div style="display:flex;gap:20px;margin-top:6px;">
                    <label style="font-weight:normal;display:flex;gap:6px;align-items:center;"><input type="radio" name="billing_invoice_email" value="Yes" <?php checked($v('billing_invoice_email','Yes'),'Yes'); ?>> Yes</label>
                    <label style="font-weight:normal;display:flex;gap:6px;align-items:center;"><input type="radio" name="billing_invoice_email" value="No" <?php checked($v('billing_invoice_email','Yes'),'No'); ?>> No</label>
                </div>
            </div>
            <h4 style="font-size:.88rem;font-weight:700;margin:16px 0 12px;color:var(--sp-muted);">Billing Mailing Address <span style="font-size:.75rem;font-weight:400;">(if different from official mailing)</span></h4>
            <div class="sp-field"><label>Jurisdiction Name (for mail)</label><input type="text" name="billing_mailing_jur" value="<?php echo esc_attr($v('billing_mailing_jur')); ?>"></div>
            <div class="sp-field"><label>Street Address</label><input type="text" name="billing_mailing_address" value="<?php echo esc_attr($v('billing_mailing_address')); ?>"></div>
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px;">
                <div class="sp-field" style="margin:0;"><label>City</label><input type="text" name="billing_mailing_city" value="<?php echo esc_attr($v('billing_mailing_city')); ?>"></div>
                <div class="sp-field" style="margin:0;"><label>State</label><input type="text" name="billing_mailing_state" value="<?php echo esc_attr($v('billing_mailing_state')); ?>"></div>
                <div class="sp-field" style="margin:0;"><label>ZIP</label><input type="text" name="billing_mailing_zip" value="<?php echo esc_attr($v('billing_mailing_zip')); ?>"></div>
            </div>
            <div class="sp-field"><label>Attention</label><input type="text" name="billing_mailing_attention" value="<?php echo esc_attr($v('billing_mailing_attention')); ?>"></div>
            <h4 style="font-size:.88rem;font-weight:700;margin:16px 0 12px;color:var(--sp-muted);">W-9 Information</h4>
            <div class="sp-field">
                <label>W-9 Same as Billing Contact?</label>
                <div style="display:flex;gap:20px;margin-top:6px;">
                    <label style="font-weight:normal;display:flex;gap:6px;align-items:center;"><input type="radio" name="billing_w9_same" value="Yes" <?php checked($v('billing_w9_same','Yes'),'Yes'); ?>> Yes</label>
                    <label style="font-weight:normal;display:flex;gap:6px;align-items:center;"><input type="radio" name="billing_w9_same" value="No" <?php checked($v('billing_w9_same','Yes'),'No'); ?>> No</label>
                </div>
            </div>
            <div class="sp-field"><label>W-9 Contact <span style="font-size:.75rem;font-weight:400;color:var(--sp-muted);">(if different)</span></label><input type="text" name="billing_w9_contact" value="<?php echo esc_attr($v('billing_w9_contact')); ?>"></div>
            <div style="display:flex;justify-content:space-between;margin-top:8px;">
                <button type="button" onclick="wtsPrev(9)" class="sp-btn sp-btn-secondary">Back</button>
                <button type="button" onclick="wtsNext(9)" class="sp-btn sp-btn-primary">Next</button>
            </div>
        </div>
    </div>

    <!-- STEP 10: E-911 Contact -->
    <div id="wts-step-10" class="wts-onb-step" style="display:none;">
        <div class="sp-card" style="padding:24px;">
            <h3 style="font-size:1rem;font-weight:700;margin:0 0 16px;">Step 10 of 11 &mdash; E-911 Contact</h3>
            <div class="sp-field"><label>Name</label><input type="text" name="e911_name" value="<?php echo esc_attr($v('e911_name')); ?>"></div>
            <div class="sp-field"><label>Title / Position</label><input type="text" name="e911_position" value="<?php echo esc_attr($v('e911_position')); ?>"></div>
            <div class="sp-field"><label>Department</label><input type="text" name="e911_department" value="<?php echo esc_attr($v('e911_department')); ?>"></div>
            <div class="sp-field"><label>Phone</label><input type="text" name="e911_phone" value="<?php echo esc_attr($v('e911_phone')); ?>"></div>
            <div class="sp-field"><label>Email</label><input type="email" name="e911_email" value="<?php echo esc_attr($v('e911_email')); ?>"></div>
            <div class="sp-field"><label>Description <span style="font-size:.75rem;font-weight:400;color:var(--sp-muted);">(notes / context)</span></label><textarea name="e911_description" rows="3"><?php echo esc_textarea($v('e911_description')); ?></textarea></div>
            <div class="sp-field"><label>E-911 System Link / URL</label><input type="url" name="e911_link" value="<?php echo esc_attr($v('e911_link')); ?>" placeholder="https://"></div>
            <div style="display:flex;justify-content:space-between;margin-top:8px;">
                <button type="button" onclick="wtsPrev(10)" class="sp-btn sp-btn-secondary">Back</button>
                <button type="button" onclick="wtsNext(10)" class="sp-btn sp-btn-primary">Review &amp; Submit</button>
            </div>
        </div>
    </div>

    <!-- STEP 11: Review -->
    <div id="wts-step-11" class="wts-onb-step" style="display:none;">
        <div class="sp-card" style="padding:24px;">
            <h3 style="font-size:1rem;font-weight:700;margin:0 0 16px;">Step 11 of 11 &mdash; Review Your Answers</h3>
            <div id="wts-review-body" style="background:var(--sp-bg-alt,#f9fafb);border:1px solid var(--sp-border,#e5e7eb);border-radius:8px;padding:20px;margin-bottom:20px;font-size:.88rem;line-height:1.7;"></div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <button type="button" onclick="wtsPrev(11)" class="sp-btn sp-btn-secondary">Edit Previous Step</button>
                <button type="submit" class="sp-btn sp-btn-primary" style="padding:12px 36px;">Submit Questionnaire</button>
            </div>
        </div>
    </div>

</form>
</div>

<script>
(function(){
    var cur = 1, tot = 11;

    function prog() {
        var bar = document.getElementById('wts-progress');
        if (bar) bar.style.width = Math.round(cur/tot*100) + '%';
    }

    function show(n) {
        document.querySelectorAll('.wts-onb-step').forEach(function(s){ s.style.display='none'; });
        var t = document.getElementById('wts-step-'+n);
        if (t) t.style.display = 'block';
        cur = n;
        prog();
        window.scrollTo({top:0,behavior:'smooth'});
    }

    window.wtsNext = function(from) {
        if (from === 10) { buildReview(); show(11); }
        else show(from+1);
    };
    window.wtsPrev = function(from) { show(from-1); };

    function buildReview() {
        var form = document.getElementById('wts-onb-form');
        var out  = {};

        form.querySelectorAll('input[type=text],input[type=email],input[type=url],textarea').forEach(function(el){
            if (!el.name || el.name === 'sp_id') return;
            out[el.name] = el.value;
        });

        var radioSeen = {};
        form.querySelectorAll('input[type=radio]').forEach(function(el){
            if (!radioSeen[el.name]) { out[el.name] = ''; radioSeen[el.name] = true; }
            if (el.checked) out[el.name] = el.value;
        });

        var cbGroups = {};
        form.querySelectorAll('input[type=checkbox]').forEach(function(el){
            var g = el.name;
            if (!cbGroups[g]) cbGroups[g] = [];
            if (el.checked) {
                var lbl = el.parentElement ? el.parentElement.textContent.trim() : el.value;
                cbGroups[g].push(lbl);
            }
        });
        Object.assign(out, cbGroups);

        var groups = [
            { title: 'Legal & Administrative', keys: [
                ['ordinance_name','Ordinance Name'],['jurisdiction_name','Jurisdiction'],
                ['governing_body_ordinance','Governing Body (Ordinance)'],
                ['governing_body_appeal','Governing Body (Appeals)'],
                ['replaces_existing','Replaces Existing'],['existing_ordinance_details','Existing Ordinance Details'],
                ['code_designation','Code Designation'],['exemptions[]','Code Exemptions'],
                ['additional_exemptions','Additional Exemptions'],
                ['approval_authority','Approval Authority'],['approving_department','Approving Department']
            ]},
            { title: 'Tower Siting', keys: [
                ['building_permits_entity','Building Permits Issued By'],
                ['tower_siting_1','Priority 1'],['tower_siting_2','Priority 2'],
                ['tower_siting_3','Priority 3'],['tower_siting_4','Priority 4'],
                ['tower_siting_5','Priority 5'],['tower_siting_6','Priority 6'],['tower_siting_7','Priority 7']
            ]},
            { title: 'Application Components', keys: [
                ['tower_registration_confirmed','Tower Registration Confirmed'],
                ['jurisdiction_owned_poles','Jurisdiction Owned Poles']
            ]},
            { title: 'Application Fees', keys: [
                ['jur_fee_colocation','Fee – Colocation/Modification'],
                ['jur_fee_new_tower','Fee – New Tower'],
                ['jur_fee_tower_registration','Fee – Tower Registration'],
                ['jur_fee_approved_update','Fee – Approved Update'],
                ['jur_fee_swf_new','Fee – SWF New'],
                ['jur_fee_swf_colocation','Fee – SWF Colocation'],
                ['jur_fee_annual_row','Fee – Annual ROW']
            ]},
            { title: 'Insurance & Bonds', keys: [
                ['ins_colocation','Bond – Colocation/Modification'],
                ['ins_small_wireless','Bond – Small Wireless'],
                ['ins_new_tower','Bond – New Tower'],
                ['ins_cgl','CGL Insurance'],['ins_auto','Auto Insurance'],
                ['ins_workers_comp','Workers\' Comp'],
                ['ins_additional_insured','Additional Insured Required']
            ]},
            { title: 'Mailing Address', keys: [
                ['mailing_jur_name','Jurisdiction'],['mailing_address','Address'],
                ['mailing_city','City'],['mailing_state','State'],['mailing_zip','ZIP'],
                ['mailing_attention','Attention']
            ]},
            { title: 'Primary Contact', keys: [
                ['primary_name','Name'],['primary_position','Position'],
                ['primary_department','Department'],['primary_phone','Phone'],
                ['primary_cell','Cell'],['primary_email','Email']
            ]},
            { title: 'Secondary Contact', keys: [
                ['secondary_name','Name'],['secondary_position','Position'],
                ['secondary_department','Department'],['secondary_phone','Phone'],
                ['secondary_cell','Cell'],['secondary_email','Email']
            ]},
            { title: 'Additional Contact', keys: [
                ['additional_name','Name'],['additional_position','Position'],
                ['additional_department','Department'],['additional_phone','Phone'],
                ['additional_cell','Cell'],['additional_email','Email']
            ]},
            { title: 'Billing Contact', keys: [
                ['billing_same_as_primary','Same as Primary'],['billing_name','Name'],
                ['billing_position','Position'],['billing_phone','Phone'],
                ['billing_invoice_email','Invoice by Email'],
                ['billing_mailing_jur','Mailing Jurisdiction'],
                ['billing_mailing_address','Mailing Address'],
                ['billing_mailing_city','Mailing City'],['billing_mailing_state','Mailing State'],
                ['billing_mailing_zip','Mailing ZIP'],['billing_mailing_attention','Mailing Attention'],
                ['billing_w9_same','W-9 Same as Billing'],['billing_w9_contact','W-9 Contact']
            ]},
            { title: 'E-911 Contact', keys: [
                ['e911_name','Name'],['e911_position','Position'],
                ['e911_department','Department'],['e911_phone','Phone'],
                ['e911_email','Email'],['e911_description','Description'],['e911_link','Link']
            ]}
        ];

        var html = '';
        groups.forEach(function(g){
            var rows = '';
            g.keys.forEach(function(pair){
                var k = pair[0], label = pair[1];
                var val = out[k];
                if (!val || (Array.isArray(val) && !val.length)) return;
                var display = Array.isArray(val) ? val.join(', ') : val;
                rows += '<dt style="font-weight:600;padding:3px 0;font-size:.82rem;color:var(--sp-muted);">'+label+':</dt>';
                rows += '<dd style="margin:0;padding:3px 0;font-size:.82rem;">'+display+'</dd>';
            });
            if (!rows) return;
            html += '<div style="margin-bottom:16px;">';
            html += '<div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--sp-primary,#2563eb);margin-bottom:6px;">'+g.title+'</div>';
            html += '<dl style="display:grid;grid-template-columns:minmax(160px,1fr) 2fr;gap:3px 16px;">'+rows+'</dl>';
            html += '</div>';
        });

        document.getElementById('wts-review-body').innerHTML = html || '<p style="color:var(--sp-muted);">No responses entered yet.</p>';
    }

    document.addEventListener('DOMContentLoaded', function(){ show(1); });
})();
</script>
