<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>PHP is working</h2>";

// Test DB
require_once __DIR__ . '/includes/db.php';
try {
    $pdo = db();
    echo "<p style='color:green'>✓ Database connected</p>";

    $users = $pdo->query('SELECT id, name, email, role, active FROM users')->fetchAll();
    echo "<h3>Users in database:</h3><ul>";
    foreach ($users as $u) {
        echo "<li>{$u['name']} — {$u['email']} — {$u['role']} — active:{$u['active']}</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ DB Error: " . $e->getMessage() . "</p>";
}

// Test session
session_start();
$_SESSION['test'] = 'works';
echo "<p style='color:green'>✓ Session: " . ($_SESSION['test'] ?? 'failed') . "</p>";
