<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

// Summary stats
$pdo        = db();
$user_count = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "user"')->fetchColumn();
$courses    = get_courses();

// Recent completions
$recent = $pdo->query(
    'SELECT u.name, u.email, l.title AS lesson, c.title AS course, p.completed_at
     FROM progress p
     JOIN users   u ON u.id = p.user_id
     JOIN lessons l ON l.id = p.lesson_id
     JOIN courses c ON c.id = l.course_id
     ORDER BY p.completed_at DESC LIMIT 20'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin — WTS AMP Training</title>
<link rel="stylesheet" href="/wts_documentation/training/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="logo">
        <img src="/wts_documentation/training/assets/img/wts-logo.png" alt="WTS">
        <span>AMP Training — Admin</span>
    </div>
    <nav>
        <a href="/wts_documentation/training/admin/">Dashboard</a>
        <a href="/wts_documentation/training/admin/users.php">Users</a>
        <a href="/wts_documentation/training/admin/progress.php">Progress</a>
        <a href="/wts_documentation/training/dashboard.php">My Training</a>
        <a href="/wts_documentation/training/logout.php">Sign Out</a>
    </nav>
</header>

<div class="page-wrap">
    <div class="page-heading">
        <h2>Admin Dashboard</h2>
        <p>Manage users and track training completion.</p>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem">
        <div class="card" style="text-align:center">
            <div style="font-size:2.5rem;font-weight:700;color:var(--navy)"><?= $user_count ?></div>
            <div style="color:#666;font-size:.9rem">Enrolled Users</div>
        </div>
        <?php foreach ($courses as $c):
            $total_lessons = count(get_lessons($c['id']));
        ?>
        <div class="card" style="text-align:center">
            <div style="font-size:2.5rem;font-weight:700;color:var(--cyan)"><?= $total_lessons ?></div>
            <div style="color:#666;font-size:.9rem"><?= htmlspecialchars($c['title']) ?> Lessons</div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
            <h3 style="color:var(--navy)">Recent Completions</h3>
            <a href="/wts_documentation/training/admin/users.php" class="btn btn-primary btn-sm">+ Add User</a>
        </div>
        <?php if ($recent): ?>
        <table class="admin-table">
            <thead>
                <tr><th>User</th><th>Course</th><th>Lesson</th><th>Completed</th></tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $r): ?>
                <tr>
                    <td><?= h($r['name']) ?><br><span style="font-size:.8rem;color:#888"><?= h($r['email']) ?></span></td>
                    <td><?= h($r['course']) ?></td>
                    <td><?= h($r['lesson']) ?></td>
                    <td><?= date('M j, Y g:ia', strtotime($r['completed_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="color:#888">No completions yet.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>







