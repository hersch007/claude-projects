<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$member  = sp_get_current_team_member();
if ( ! $member ) { echo '<p class="sp-empty">Not signed in.</p>'; return; }

$error   = sanitize_key( isset( $_GET['error'] )  ? $_GET['error']  : '' );
$saved   = isset( $_GET['saved'] );

$error_msg = '';
if ( $error === 'wrong_pin' )    $error_msg = 'Current PIN is incorrect. Please try again.';
if ( $error === 'pin_mismatch' ) $error_msg = 'New PINs do not match. Please try again.';
if ( $error === 'pin_empty' )    $error_msg = 'New PIN cannot be empty.';
if ( $error === 'name_empty' )   $error_msg = 'Name cannot be empty.';

$name_saved = isset( $_GET['name_saved'] );
?>
<div class="sp-page-header">
    <h1>My Profile</h1>
</div>

<div style="max-width:520px;display:flex;flex-direction:column;gap:16px;">

    <!-- Identity card -->
    <div class="sp-card" style="padding:24px;display:flex;align-items:center;gap:18px;">
        <div class="sp-avatar" style="width:52px;height:52px;font-size:20px;flex-shrink:0;"><?php echo esc_html( strtoupper( substr( $member->name, 0, 1 ) ) ); ?></div>
        <div>
            <div style="font-size:1.1rem;font-weight:700;color:#1e293b;"><?php echo esc_html( $member->name ); ?></div>
            <div style="font-size:.85rem;color:#64748b;margin-top:2px;"><?php echo esc_html( ucfirst( $member->role ) ); ?></div>
            <?php if ( $member->email ) : ?>
                <div style="font-size:.82rem;color:#94a3b8;margin-top:2px;"><?php echo esc_html( $member->email ); ?></div>
            <?php endif; ?>
            <?php if ( ! empty( $member->last_login_at ) ) : ?>
                <div style="font-size:.78rem;color:#94a3b8;margin-top:4px;">Last login: <?php echo esc_html( date( 'M j, Y g:ia', strtotime( $member->last_login_at ) ) ); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Change Name -->
    <div class="sp-card sp-form-card">
        <h2 class="sp-section-heading" style="margin-top:0;">Change Name</h2>

        <?php if ( $name_saved ) : ?>
            <div class="sp-toast" style="background:#f0fdf4;border-color:#86efac;color:#166534;margin-bottom:16px;">Name updated successfully.</div>
        <?php endif; ?>
        <?php if ( $error === 'name_empty' ) : ?>
            <div class="sp-toast" style="background:#fee2e2;border-color:#fca5a5;color:#dc2626;margin-bottom:16px;"><?php echo esc_html( $error_msg ); ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="change_name">
            <input type="hidden" name="sp_id"   value="0">
            <div class="sp-field">
                <label>Full Name</label>
                <input type="text" name="new_name" value="<?php echo esc_attr( $member->name ); ?>" required maxlength="100" style="max-width:320px;">
            </div>
            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary">Update Name</button>
            </div>
        </form>
    </div>

    <!-- Change PIN -->
    <div class="sp-card sp-form-card">
        <h2 class="sp-section-heading" style="margin-top:0;">Change PIN</h2>

        <?php if ( $saved ) : ?>
            <div class="sp-toast" style="background:#f0fdf4;border-color:#86efac;color:#166534;margin-bottom:16px;">PIN updated successfully.</div>
        <?php endif; ?>
        <?php if ( $error_msg ) : ?>
            <div class="sp-toast" style="background:#fee2e2;border-color:#fca5a5;color:#dc2626;margin-bottom:16px;"><?php echo esc_html( $error_msg ); ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="change_pin">
            <input type="hidden" name="sp_id"   value="0">

            <div class="sp-field">
                <label>Current PIN</label>
                <input type="password" name="current_pin" maxlength="20" required autocomplete="current-password" style="max-width:260px;">
            </div>
            <div class="sp-field">
                <label>New PIN</label>
                <input type="password" name="new_pin" maxlength="20" required autocomplete="new-password" style="max-width:260px;">
                <span class="sp-hint">Letters, numbers, or symbols. Up to 20 characters.</span>
            </div>
            <div class="sp-field">
                <label>Confirm New PIN</label>
                <input type="password" name="confirm_pin" maxlength="20" required autocomplete="new-password" style="max-width:260px;">
            </div>

            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary">Update PIN</button>
            </div>
        </form>
    </div>

</div>
