<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/auth.php';
lw_start_session();

$settings = lw_get_settings();
if ( $settings['admin_password_hash'] === '' ) {
    header( 'Location: setup.php' ); exit;
}
if ( ! empty( $_SESSION['lw_admin'] ) ) {
    header( 'Location: dashboard.php' ); exit;
}

$error = '';
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $pw = (string) ( $_POST['password'] ?? '' );
    if ( password_verify( $pw, $settings['admin_password_hash'] ) ) {
        $_SESSION['lw_admin'] = true;
        header( 'Location: dashboard.php' ); exit;
    }
    $error = 'Incorrect password.';
}
?><!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Lifeward Admin — Login</title>
<link rel="stylesheet" href="admin.css?v=<?php echo @filemtime( __DIR__ . '/admin.css' ); ?>"></head>
<body class="lw-login-page">
<div class="lw-admin-narrow">
    <span class="lw-login-logo-wrap"><img src="lifeward-logo.svg" alt="Lifeward" class="lw-login-logo"></span>
    <h1>Lifeward Admin</h1>
    <?php if ( $error ): ?><div class="lw-alert"><?php echo htmlspecialchars( $error, ENT_QUOTES ); ?></div><?php endif; ?>
    <form method="post">
        <label>Password<input type="password" name="password" required autofocus></label>
        <button type="submit" class="lw-btn-primary">Log in</button>
    </form>
</div>
</body>
</html>
