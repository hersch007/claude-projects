<?php
require_once __DIR__ . '/db.php';

function get_courses(): array {
    return db()->query('SELECT * FROM courses WHERE active = 1 ORDER BY sort_order')->fetchAll();
}

function get_lessons(int $course_id): array {
    $stmt = db()->prepare('SELECT * FROM lessons WHERE course_id = ? AND active = 1 ORDER BY sort_order');
    $stmt->execute([$course_id]);
    return $stmt->fetchAll();
}

function get_lesson(string $slug): ?array {
    $stmt = db()->prepare('SELECT l.*, c.title AS course_title, c.slug AS course_slug FROM lessons l JOIN courses c ON c.id = l.course_id WHERE l.slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function is_lesson_complete(int $user_id, int $lesson_id): bool {
    $stmt = db()->prepare('SELECT 1 FROM progress WHERE user_id = ? AND lesson_id = ?');
    $stmt->execute([$user_id, $lesson_id]);
    return (bool)$stmt->fetch();
}

function mark_lesson_complete(int $user_id, int $lesson_id): void {
    $stmt = db()->prepare('INSERT IGNORE INTO progress (user_id, lesson_id, completed_at) VALUES (?, ?, NOW())');
    $stmt->execute([$user_id, $lesson_id]);
}

function course_progress(int $user_id, int $course_id): array {
    $stmt = db()->prepare('SELECT COUNT(*) FROM lessons WHERE course_id = ? AND active = 1');
    $stmt->execute([$course_id]);
    $total = (int)$stmt->fetchColumn();

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM progress p
         JOIN lessons l ON l.id = p.lesson_id
         WHERE p.user_id = ? AND l.course_id = ?'
    );
    $stmt->execute([$user_id, $course_id]);
    $done = (int)$stmt->fetchColumn();

    return ['total' => $total, 'done' => $done, 'pct' => $total > 0 ? round($done / $total * 100) : 0];
}

function save_quiz_result(int $user_id, int $lesson_id, int $score, int $total): void {
    $passed = $score >= ceil($total * 0.8) ? 1 : 0;
    $stmt = db()->prepare(
        'INSERT INTO quiz_results (user_id, lesson_id, score, total, passed, taken_at)
         VALUES (?, ?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE score = ?, total = ?, passed = ?, taken_at = NOW()'
    );
    $stmt->execute([$user_id, $lesson_id, $score, $total, $passed, $score, $total, $passed]);
    if ($passed) {
        mark_lesson_complete($user_id, $lesson_id);
    }
}

function get_quiz_result(int $user_id, int $lesson_id): ?array {
    $stmt = db()->prepare('SELECT * FROM quiz_results WHERE user_id = ? AND lesson_id = ? LIMIT 1');
    $stmt->execute([$user_id, $lesson_id]);
    return $stmt->fetch() ?: null;
}

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
