<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare('SELECT * FROM courses WHERE slug = ? AND active = 1 LIMIT 1');
$stmt->execute([$slug]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: /wts_documentation/training/dashboard.php');
    exit;
}

$user    = current_user();
$lessons = get_lessons($course['id']);
$p       = course_progress($user['id'], $course['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($course['title']) ?> — WTS AMP Training</title>
<link rel="stylesheet" href="/wts_documentation/training/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="logo">
        <img src="/wts_documentation/training/assets/img/wts-logo.png" alt="WTS">
        <span>AMP Training Portal</span>
    </div>
    <nav>
        <a href="/wts_documentation/training/dashboard.php">← My Courses</a>
        <a href="/wts_documentation/training/logout.php">Sign Out</a>
    </nav>
</header>

<div class="page-wrap">
    <div class="card">
        <h2 style="color:var(--navy);margin-bottom:.4rem"><?= htmlspecialchars($course['title']) ?></h2>
        <p style="color:#666;margin-bottom:1rem"><?= htmlspecialchars($course['description']) ?></p>
        <div class="progress-bar-wrap">
            <div class="progress-bar-fill" style="width:<?= $p['pct'] ?>%"></div>
        </div>
        <p style="font-size:.82rem;color:#666;margin-top:.4rem"><?= $p['done'] ?> of <?= $p['total'] ?> lessons complete</p>
    </div>

    <?php foreach ($lessons as $i => $lesson):
        $done = is_lesson_complete($user['id'], $lesson['id']);
    ?>
    <a href="/wts_documentation/training/lesson.php?slug=<?= urlencode($lesson['slug']) ?>" style="text-decoration:none">
        <div class="card" style="display:flex;align-items:center;gap:1rem;padding:1.1rem 1.5rem;<?= $done ? 'border-left:4px solid var(--green)' : '' ?>">
            <div style="font-size:1.4rem;width:36px;text-align:center">
                <?= $done ? '<span style="color:var(--green)">✓</span>' : '<span style="color:#ccc">' . ($i+1) . '</span>' ?>
            </div>
            <div>
                <strong style="color:var(--navy)"><?= htmlspecialchars($lesson['title']) ?></strong>
                <?php if ($done): ?>
                    <br><span style="font-size:.8rem;color:var(--green)">Completed</span>
                <?php endif; ?>
            </div>
            <div style="margin-left:auto;color:#ccc">›</div>
        </div>
    </a>
    <?php endforeach; ?>

    <?php if ($p['pct'] === 100): ?>
    <div class="alert alert-success" style="text-align:center;font-size:1rem">
        🎉 You have completed <strong><?= htmlspecialchars($course['title']) ?></strong>!
        <br><a href="/wts_documentation/training/certificate.php?course=<?= urlencode($course['slug']) ?>" class="btn btn-success btn-sm" style="margin-top:.75rem">View Certificate</a>
    </div>
    <?php endif; ?>
</div>

</body>
</html>







