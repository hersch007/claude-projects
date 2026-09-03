<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! sp_is_admin_member() ) { echo '<p class="sp-empty">Access denied.</p>'; return; }

global $wpdb;
$action  = sanitize_key( isset( $_GET['action'] ) ? $_GET['action'] : 'list' );
$id      = (int) ( isset( $_GET['id'] ) ? $_GET['id'] : 0 );
$current = sp_get_current_team_member();
$error   = sanitize_key( isset( $_GET['error'] ) ? $_GET['error'] : '' );

// ── New / Edit form ───────────────────────────────────────────────────────────
if ( $action === 'new' || $action === 'edit' ) {
    $member = null;
    if ( $action === 'edit' && $id ) {
        $member = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_team WHERE id = %d", $id ) );
        if ( ! $member ) { echo '<p class="sp-empty">Team member not found.</p>'; return; }
    }
    ?>
    <div class="sp-page-header">
        <h1><?php echo $action === 'edit' ? 'Edit Team Member' : 'New Team Member'; ?></h1>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=team' ) ); ?>" class="sp-btn sp-btn-ghost">&larr; Back</a>
    </div>
    <div class="sp-card sp-form-card">
        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="team_member">
            <input type="hidden" name="sp_id" value="<?php echo esc_attr( $id ); ?>">

            <div class="sp-form-row">
                <div class="sp-field">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?php echo esc_attr( $member ? $member->name : '' ); ?>" required>
                </div>
                <div class="sp-field">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo esc_attr( $member ? $member->email : '' ); ?>" placeholder="optional">
                </div>
            </div>

            <div class="sp-form-row">
                <div class="sp-field">
                    <label>Role</label>
                    <select name="role">
                        <option value="agent"   <?php selected( $member ? $member->role : 'agent', 'agent'   ); ?>>Agent — can create &amp; edit records</option>
                        <option value="admin"   <?php selected( $member ? $member->role : '', 'admin'         ); ?>>Admin — full access</option>
                        <option value="learner" <?php selected( $member ? $member->role : '', 'learner'       ); ?>>Learner — training &amp; knowledge only</option>
                    </select>
                </div>
                <div class="sp-field">
                    <label>Status</label>
                    <select name="status" <?php echo ( $member && $current && $member->id === $current->id ) ? 'disabled' : ''; ?>>
                        <option value="active"   <?php selected( $member ? $member->status : 'active', 'active' ); ?>>Active</option>
                        <option value="inactive" <?php selected( $member ? $member->status : '', 'inactive' ); ?>>Inactive</option>
                    </select>
                    <?php if ( $member && $current && $member->id === $current->id ) : ?>
                        <input type="hidden" name="status" value="active">
                        <span class="sp-hint">Cannot deactivate yourself.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sp-field" style="max-width:220px">
                <label><?php echo $action === 'edit' ? 'New PIN' : 'PIN'; ?></label>
                <input type="password" name="pin" maxlength="20"
                    placeholder="<?php echo $action === 'edit' ? 'Leave blank to keep current' : 'Enter a PIN'; ?>"
                    <?php echo $action === 'new' ? 'required' : ''; ?>>
                <span class="sp-hint">Letters, numbers, or symbols. Up to 20 characters.</span>
            </div>

            <?php
            $core_options = array();
            if ( function_exists( 'sp_get_core_slots' ) ) {
                foreach ( sp_get_core_slots() as $cid => $cdata ) {
                    $core_options[ $cid ] = $cdata['label'] ?: $cid;
                }
            }
            $saved_access = array();
            if ( $member && ! empty( $member->core_access ) ) {
                $saved_access = json_decode( $member->core_access, true ) ?: array();
            }
            $unrestricted = empty( $saved_access );
            ?>
            <div class="sp-field" style="margin-top:8px;">
                <label>Core Access</label>
                <span class="sp-hint" style="display:block;margin-bottom:10px;">Leave all unchecked to grant access to every core. Check specific cores to restrict this member to only those sections.</span>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;">
                <?php foreach ( $core_options as $cid => $clabel ):
                    $checked = ! $unrestricted && in_array( $cid, $saved_access ); ?>
                    <label style="display:flex;align-items:center;gap:8px;padding:9px 12px;border:1px solid var(--sp-border,#e5e7eb);border-radius:8px;cursor:pointer;font-size:.85rem;">
                        <input type="checkbox" name="core_access[]" value="<?php echo esc_attr($cid); ?>"<?php checked($checked); ?> style="width:15px;height:15px;accent-color:var(--sp-accent);">
                        <?php echo esc_html($clabel); ?>
                    </label>
                <?php endforeach; ?>
                </div>
            </div>

            <?php do_action( 'sp_team_member_form_fields', $member ); ?>

            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary">Save Member</button>
                <a href="<?php echo esc_url( home_url( '/sp-app/?view=team' ) ); ?>" class="sp-btn sp-btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
    <?php
    return;
}

// ── List ──────────────────────────────────────────────────────────────────────
$members = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sp_team ORDER BY role ASC, name ASC" );
?>
<div class="sp-page-header">
    <h1>Team <span class="sp-count"><?php echo count( $members ); ?></span></h1>
    <a href="<?php echo esc_url( home_url( '/sp-app/?view=team&action=new' ) ); ?>" class="sp-btn sp-btn-primary">+ New Member</a>
</div>

<?php if ( $error === 'nopin' ) : ?>
    <div class="sp-toast" style="background:#fee2e2;border-color:#fca5a5;color:#dc2626">A PIN is required for new team members.</div>
<?php endif; ?>

<div class="sp-card sp-table-card">
    <table class="sp-table">
        <?php
        $core_labels = array();
        if ( function_exists( 'sp_get_core_slots' ) ) {
            foreach ( sp_get_core_slots() as $cid => $cdata ) {
                // Shorten label for chip display — strip " Core" suffix
                $core_labels[ $cid ] = preg_replace( '/ Core$/i', '', $cdata['label'] ?: $cid );
            }
        }
        ?>
        <thead>
            <tr><th>Name</th><th>Email</th><th>Role</th><th>Core Access</th><th>Status</th><th>Member Since</th><th>Last Login</th><th></th></tr>
        </thead>
        <tbody>
        <?php if ( empty( $members ) ) : ?>
            <tr><td colspan="7" class="sp-empty">No team members found.</td></tr>
        <?php else : foreach ( $members as $m ) :
            $m_access = ! empty( $m->core_access ) ? json_decode( $m->core_access, true ) : array();
        ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div class="sp-avatar" style="width:28px;height:28px;font-size:11px;flex-shrink:0"><?php echo esc_html( strtoupper( substr( $m->name, 0, 1 ) ) ); ?></div>
                        <a href="<?php echo esc_url( home_url( '/sp-app/?view=team&action=edit&id=' . $m->id ) ); ?>" class="sp-link"><?php echo esc_html( $m->name ); ?></a>
                        <?php if ( $current && $m->id === $current->id ) : ?><span class="sp-badge sp-badge-new" style="font-size:10px">You</span><?php endif; ?>
                    </div>
                </td>
                <td class="sp-muted"><?php echo $m->email ? esc_html( $m->email ) : '—'; ?></td>
                <td><span class="sp-badge <?php echo $m->role === 'admin' ? 'sp-badge-active' : 'sp-badge-contacted'; ?>"><?php echo esc_html( ucfirst( $m->role ) ); ?></span></td>
                <td>
                <?php if ( empty( $m_access ) ) : ?>
                    <span style="font-size:.75rem;color:#16a34a;font-weight:600;">All cores</span>
                <?php else : ?>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                    <?php foreach ( $m_access as $cid ) : ?>
                        <span style="font-size:.68rem;font-weight:700;background:#ede9fe;color:#7c3aed;border-radius:4px;padding:2px 6px;"><?php echo esc_html( $core_labels[$cid] ?? $cid ); ?></span>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                </td>
                <td><span class="sp-badge <?php echo $m->status === 'active' ? 'sp-badge-active' : 'sp-badge-inactive'; ?>"><?php echo esc_html( ucfirst( $m->status ) ); ?></span></td>
                <td class="sp-muted"><?php echo esc_html( date( 'M j, Y', strtotime( $m->created_at ) ) ); ?></td>
                <td class="sp-muted"><?php echo $m->last_login_at ? esc_html( date( 'M j, Y g:ia', strtotime( $m->last_login_at ) ) ) : '<span style="color:#d1d5db">Never</span>'; ?></td>
                <td class="sp-actions">
                    <a href="<?php echo esc_url( home_url( '/sp-app/?view=team&action=edit&id=' . $m->id ) ); ?>">Edit</a>
                    <?php if ( ! $current || $m->id !== $current->id ) : ?>
                        <a href="<?php echo esc_url( sp_delete_url( 'team_member', $m->id ) ); ?>" data-sp-confirm="Remove <?php echo esc_js( $m->name ); ?>?" class="sp-danger">Remove</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
