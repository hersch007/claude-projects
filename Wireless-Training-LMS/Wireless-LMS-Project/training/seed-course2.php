<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_admin();

$pdo = db();

// Insert Course 2 if it doesn't exist
$pdo->exec("INSERT IGNORE INTO courses (title, slug, description, sort_order) VALUES
    ('AMP for Jurisdiction Reviewers', 'amp-jurisdiction', 'Training for government and jurisdiction staff who review and approve wireless facility applications in AMP.', 2)
");

$c2 = $pdo->query("SELECT id FROM courses WHERE slug='amp-jurisdiction' LIMIT 1")->fetchColumn();

if (!$c2) {
    die('<p style="color:red">Could not find or create Course 2. Check the database.</p>');
}

$lessons = [
    ['Authority and Responsibility',          'authority-responsibility', 'course2/lesson-01-authority.md',            1],
    ['Dashboard and Workload Management',     'dashboard-workload',      'course2/lesson-02-dashboard-workload.md',   2],
    ['Reviewing the PIF',                     'reviewing-pif',           'course2/lesson-03-reviewing-pif.md',        3],
    ['Reviewing Components',                  'reviewing-components',    'course2/lesson-04-reviewing-components.md', 4],
    ['The Shot Clock',                        'shot-clock',              'course2/lesson-05-shot-clock.md',           5],
    ['Making Decisions',                      'making-decisions',        'course2/lesson-06-making-decisions.md',     6],
    ['Communication, Inspection and Closure', 'communication-closure',   'course2/lesson-07-communication.md',        7],
];

$stmt = $pdo->prepare('INSERT IGNORE INTO lessons (course_id, title, slug, content_file, sort_order) VALUES (?,?,?,?,?)');
$inserted = 0;
foreach ($lessons as $l) {
    $stmt->execute([$c2, ...$l]);
    $inserted += $stmt->rowCount();
}

echo "<p style='color:green;font-family:sans-serif'>&#10003; Course 2 seeded. " . $inserted . " lesson(s) inserted.</p>";
echo "<p style='font-family:sans-serif'>Course 2 ID: <strong>" . $c2 . "</strong></p>";
echo "<p style='color:red;font-family:sans-serif'><strong>Delete this file from the server now.</strong></p>";
