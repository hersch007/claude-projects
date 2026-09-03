<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_admin();

$pdo = db();

$c1 = $pdo->query("SELECT id FROM courses WHERE slug='amp-fundamentals' LIMIT 1")->fetchColumn();

if (!$c1) {
    die('<p style="color:red">Course 1 not found. Check the database.</p>');
}

$stmt = $pdo->prepare('INSERT IGNORE INTO lessons (course_id, title, slug, content_file, sort_order) VALUES (?,?,?,?,?)');
$stmt->execute([$c1, 'Course 1 Final Exam', 'course1-final-exam', 'course1/lesson-07-course1-exam.md', 7]);
$inserted = $stmt->rowCount();

if ($inserted) {
    echo "<p style='color:green;font-family:sans-serif'>&#10003; Course 1 Final Exam added as Lesson 7.</p>";
} else {
    echo "<p style='color:orange;font-family:sans-serif'>Already exists â€” no change made.</p>";
}

echo "<p style='color:red;font-family:sans-serif'><strong>Delete this file from the server now.</strong></p>";
