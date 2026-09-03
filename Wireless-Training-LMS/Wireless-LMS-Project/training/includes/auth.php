<?php
// Start session immediately before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function session_start_safe(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function current_user(): ?array {
    session_start_safe();
    return $_SESSION['lms_user'] ?? null;
}

function require_login(): void {
    if (!current_user()) {
        header('Location: /wts_documentation/training/login.php');
        exit;
    }
}

function require_admin(): void {
    $user = current_user();
    if (!$user || $user['role'] !== 'admin') {
        header('Location: /wts_documentation/training/dashboard.php');
        exit;
    }
}

function login(string $email, string $password): bool {
    session_start_safe();

    // Rate limit: max 10 attempts per IP per 15 minutes
    $ip_key = 'login_attempts_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $attempts = $_SESSION[$ip_key] ?? ['count' => 0, 'until' => 0];

    if ($attempts['until'] > time()) {
        return false; // still locked out
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND active = 1 LIMIT 1');
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        unset($_SESSION[$ip_key]);
        session_regenerate_id(true);
        $_SESSION['lms_user'] = [
            'id'             => $user['id'],
            'name'           => $user['name'],
            'email'          => $user['email'],
            'role'           => $user['role'],
            'must_change_pw' => (bool)$user['must_change_pw'],
        ];
        return true;
    }

    // Increment failed attempts
    $attempts['count']++;
    if ($attempts['count'] >= 10) {
        $attempts['until'] = time() + 900; // lock for 15 min
        $attempts['count'] = 0;
    }
    $_SESSION[$ip_key] = $attempts;
    return false;
}

function csrf_token(): string {
    session_start_safe();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): void {
    session_start_safe();
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid request. Please go back and try again.');
    }
}

function logout(): void {
    session_start_safe();
    session_destroy();
    header('Location: /wts_documentation/training/login.php');
    exit;
}







