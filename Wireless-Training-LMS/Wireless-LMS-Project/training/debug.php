<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<p>Step 1: Starting</p>";

require_once __DIR__ . '/includes/auth.php';
echo "<p>Step 2: auth.php loaded</p>";

require_once __DIR__ . '/includes/functions.php';
echo "<p>Step 3: functions.php loaded</p>";

$user = current_user();
echo "<p>Step 4: current_user = " . ($user ? $user['name'] . ' / ' . $user['role'] : 'NOT LOGGED IN') . "</p>";

$courses = get_courses();
echo "<p>Step 5: courses loaded — count: " . count($courses) . "</p>";

foreach ($courses as $c) {
    echo "<p>Course: {$c['title']}</p>";
    $p = course_progress($user['id'] ?? 0, $c['id']);
    echo "<p>Progress: {$p['done']}/{$p['total']}</p>";
}

echo "<p style='color:green'><strong>All done — no errors!</strong></p>";
