<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/auth.php';
lw_start_session();

$settings = lw_get_settings();
if ( $settings['admin_password_hash'] !== '' ) {
    header( 'Location: dashboard.php' ); exit;
}

$error = '';
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $pw = (string) ( $_POST['password'] ?? '' );
    $pw2 = (string) ( $_POST['password2'] ?? '' );
    if ( strlen( $pw ) < 8 ) {
        $error = 'Password must be at least 8 characters.';
    } elseif ( $pw !== $pw2 ) {
        $error = "Passwords don't match.";
    } else {
        lw_save_settings( array( 'admin_password_hash' => password_hash( $pw, PASSWORD_DEFAULT ) ) );
        $_SESSION['lw_admin'] = true;
        header( 'Location: dashboard.php' ); exit;
    }
}
?><!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Lifeward Admin — Setup</title>
<link rel="stylesheet" href="admin.css?v=<?php echo @filemtime( __DIR__ . '/admin.css' ); ?>"></head>
<body class="lw-login-page">
<div class="lw-admin-narrow">
    <span class="lw-login-logo-wrap"><img src="lifeward-logo.svg" alt="Lifeward" class="lw-login-logo"></span>
    <h1>Set up admin access</h1>
    <p class="lw-hint">No admin password is set yet. Choose one now — this protects the leads dashboard and settings.</p>
    <?php if ( $error ): ?><div class="lw-alert"><?php echo htmlspecialchars( $error, ENT_QUOTES ); ?></div><?php endif; ?>
    <form method="post">
        <label>Password<input type="password" name="password" required></label>
        <label>Confirm password<input type="password" name="password2" required></label>
        <button type="submit" class="lw-btn-primary">Set password &amp; continue</button>
    </form>
</div>
</body>
</html>
