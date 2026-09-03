<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$user    = current_user();
$courses = get_courses();
$progress = [];
foreach ($courses as $c) {
    $progress[$c['id']] = course_progress($user['id'], $c['id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Training — WTS AMP</title>
<link rel="stylesheet" href="/wts_documentation/training/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="logo">
        <img src="/wts_documentation/training/assets/img/wts-logo.png" alt="WTS">
        <span>AMP Training Portal</span>
    </div>
    <nav>
        <a href="/wts_documentation/training/dashboard.php">My Courses</a>
        <?php if ($user['role'] === 'admin'): ?>
            <a href="/wts_documentation/training/admin/">Admin</a>
        <?php endif; ?>
        <a href="/wts_documentation/training/logout.php">Sign Out</a>
    </nav>
</header>

<div class="page-wrap">
    <div class="page-heading">
        <h2>Welcome back, <?= htmlspecialchars($user['name']) ?></h2>
        <p>Complete all required courses for your role to receive your AMP certification.</p>
    </div>

    <div class="course-grid">
        <?php foreach ($courses as $c):
            $p = $progress[$c['id']];
        ?>
        <a href="/wts_documentation/training/course.php?slug=<?= urlencode($c['slug']) ?>" class="course-card">
            <h3><?= htmlspecialchars($c['title']) ?></h3>
            <p><?= htmlspecialchars($c['description']) ?></p>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" style="width:<?= $p['pct'] ?>%"></div>
            </div>
            <p style="font-size:.82rem;color:#666;margin-top:.4rem;margin-bottom:0">
                <?= $p['done'] ?> of <?= $p['total'] ?> lessons complete
                <?php if ($p['pct'] === 100): ?>
                    &nbsp;<span class="badge badge-green">✓ Complete</span>
                <?php endif; ?>
            </p>
        </a>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>








