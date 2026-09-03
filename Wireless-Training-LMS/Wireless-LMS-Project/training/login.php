<?php
require_once __DIR__ . '/includes/auth.php';
session_start_safe();

// Already logged in
if (current_user()) {
    header('Location: /wts_documentation/training/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password && login($email, $password)) {
        $user = current_user();
        if ($user['must_change_pw']) {
            header('Location: /wts_documentation/training/change-password.php');
        } elseif ($user['role'] === 'admin') {
            header('Location: /wts_documentation/training/dashboard.php');
        } else {
            header('Location: /wts_documentation/training/dashboard.php');
        }
        exit;
    } else {
        $error = 'Invalid email or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — WTS AMP Training</title>
<link rel="stylesheet" href="/wts_documentation/training/assets/css/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-box">
        <img src="/wts_documentation/training/assets/img/wts-logo.png" alt="Wireless Tower Solutions">
        <h1>AMP Training Portal</h1>
        <p class="sub">Sign in to access your training courses</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required autocomplete="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary btn-full" style="margin-top:.5rem">Sign In</button>
        </form>

        <p style="margin-top:1.5rem;font-size:.8rem;color:#888;">
            Need access? Contact your WTS administrator.
        </p>
    </div>
</div>
</body>
</html>







