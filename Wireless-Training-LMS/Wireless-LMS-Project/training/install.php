<?php
/**
 * WTS LMS Installer — run once, then delete this file from the server.
 * Visit: wirelesstowersolutions.com/wts_documentation/training/install.php
 */
require_once __DIR__ . '/includes/db.php';

$pdo = db();

$tables = [

'users' => "CREATE TABLE IF NOT EXISTS users (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(120) NOT NULL,
    email          VARCHAR(180) NOT NULL UNIQUE,
    password       VARCHAR(255) NOT NULL,
    role           ENUM('admin','user') NOT NULL DEFAULT 'user',
    active         TINYINT(1) NOT NULL DEFAULT 1,
    must_change_pw TINYINT(1) NOT NULL DEFAULT 1,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

'courses' => "CREATE TABLE IF NOT EXISTS courses (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200) NOT NULL,
    slug        VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    sort_order  INT NOT NULL DEFAULT 0,
    active      TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

'lessons' => "CREATE TABLE IF NOT EXISTS lessons (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id   INT UNSIGNED NOT NULL,
    title       VARCHAR(200) NOT NULL,
    slug        VARCHAR(200) NOT NULL UNIQUE,
    content_file VARCHAR(300) NOT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    active      TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (course_id) REFERENCES courses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

'progress' => "CREATE TABLE IF NOT EXISTS progress (
    user_id      INT UNSIGNED NOT NULL,
    lesson_id    INT UNSIGNED NOT NULL,
    completed_at DATETIME NOT NULL,
    PRIMARY KEY (user_id, lesson_id),
    FOREIGN KEY (user_id)   REFERENCES users(id),
    FOREIGN KEY (lesson_id) REFERENCES lessons(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

'quiz_results' => "CREATE TABLE IF NOT EXISTS quiz_results (
    user_id    INT UNSIGNED NOT NULL,
    lesson_id  INT UNSIGNED NOT NULL,
    score      INT NOT NULL,
    total      INT NOT NULL,
    passed     TINYINT(1) NOT NULL DEFAULT 0,
    taken_at   DATETIME NOT NULL,
    PRIMARY KEY (user_id, lesson_id),
    FOREIGN KEY (user_id)   REFERENCES users(id),
    FOREIGN KEY (lesson_id) REFERENCES lessons(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

];

$errors = [];
foreach ($tables as $name => $sql) {
    try {
        $pdo->exec($sql);
        echo "<p style='color:green'>✓ Table <strong>$name</strong> created.</p>";
    } catch (PDOException $e) {
        $errors[] = $name;
        echo "<p style='color:red'>✗ Table <strong>$name</strong> failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Seed first admin account
$adminEmail = 'richard@grouprb.com';
$adminPass  = password_hash('WTS-Admin-2026!', PASSWORD_BCRYPT);
try {
    $stmt = $pdo->prepare('INSERT IGNORE INTO users (name, email, password, role, must_change_pw) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute(['Richard Brashear', $adminEmail, $adminPass, 'admin', 0]);
    echo "<p style='color:green'>✓ Admin account seeded: <strong>$adminEmail</strong> / WTS-Admin-2026!</p>";
} catch (PDOException $e) {
    echo "<p style='color:orange'>Admin account already exists or failed.</p>";
}

// Seed Course 1
try {
    $pdo->exec("INSERT IGNORE INTO courses (title, slug, description, sort_order) VALUES
        ('AMP Fundamentals', 'amp-fundamentals', 'Core training for all AMP users — prerequisite for all role-specific courses.', 1),
        ('AMP for Jurisdiction Reviewers', 'amp-jurisdiction', 'Training for government/jurisdiction staff who review and approve applications.', 2)
    ");
    echo "<p style='color:green'>✓ Courses seeded.</p>";
} catch (PDOException $e) {
    echo "<p style='color:orange'>Courses already seeded.</p>";
}

// Seed Course 1 lessons
$c1 = $pdo->query("SELECT id FROM courses WHERE slug='amp-fundamentals' LIMIT 1")->fetchColumn();
if ($c1) {
    $lessons1 = [
        ['What Is AMP and Why It Exists',         'what-is-amp',           'course1/lesson-01-what-is-amp.md',           1],
        ['User Roles and Responsibilities',        'user-roles',            'course1/lesson-02-user-roles.md',            2],
        ['Projects, Dashboard and Status',         'dashboard-status',      'course1/lesson-03-dashboard-status.md',      3],
        ['Project Information Form (PIF)',         'pif',                   'course1/lesson-04-pif.md',                   4],
        ['Component Types (NWF, TWF, NCM, ATF)',  'component-types',       'course1/lesson-05-component-types.md',       5],
        ['Security, Compliance & Official Record','security-compliance',   'course1/lesson-06-security-compliance.md',   6],
    ];
    $stmt = $pdo->prepare('INSERT IGNORE INTO lessons (course_id, title, slug, content_file, sort_order) VALUES (?,?,?,?,?)');
    foreach ($lessons1 as $l) {
        $stmt->execute([$c1, ...$l]);
    }
    echo "<p style='color:green'>✓ Course 1 lessons seeded.</p>";
}

// Seed Course 2 lessons
$c2 = $pdo->query("SELECT id FROM courses WHERE slug='amp-jurisdiction' LIMIT 1")->fetchColumn();
if ($c2) {
    $lessons2 = [
        ['Authority and Responsibility',           'authority-responsibility','course2/lesson-01-authority.md',            1],
        ['Dashboard and Workload Management',      'dashboard-workload',     'course2/lesson-02-dashboard-workload.md',   2],
        ['Reviewing the PIF',                      'reviewing-pif',          'course2/lesson-03-reviewing-pif.md',        3],
        ['Reviewing Components',                   'reviewing-components',   'course2/lesson-04-reviewing-components.md', 4],
        ['Shot Clock',                             'shot-clock',             'course2/lesson-05-shot-clock.md',           5],
        ['Making Decisions',                       'making-decisions',       'course2/lesson-06-making-decisions.md',     6],
        ['Communication, Inspection & Closure',   'communication-closure',  'course2/lesson-07-communication.md',        7],
    ];
    $stmt = $pdo->prepare('INSERT IGNORE INTO lessons (course_id, title, slug, content_file, sort_order) VALUES (?,?,?,?,?)');
    foreach ($lessons2 as $l) {
        $stmt->execute([$c2, ...$l]);
    }
    echo "<p style='color:green'>✓ Course 2 lessons seeded.</p>";
}

if (empty($errors)) {
    echo "<hr><p><strong>Installation complete.</strong> Delete this file from the server now.</p>";
} else {
    echo "<hr><p style='color:red'><strong>Some tables failed. Check your DB credentials in includes/db.php.</strong></p>";
}







