<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$pdo     = db();
$courses = get_courses();
$users   = $pdo->query('SELECT * FROM users WHERE role = "user" AND active = 1 ORDER BY name')->fetchAll();

// Per-user per-course completion
$report = [];
foreach ($users as $u) {
    $row = ['name' => $u['name'], 'email' => $u['email'], 'courses' => []];
    foreach ($courses as $c) {
        $row['courses'][$c['id']] = course_progress($u['id'], $c['id']);
    }
    $report[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Progress Report — WTS AMP Admin</title>
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
        <a href="/wts_documentation/training/logout.php">Sign Out</a>
    </nav>
</header>

<div class="page-wrap">
    <div class="page-heading" style="display:flex;justify-content:space-between;align-items:center">
        <div>
            <h2>Training Progress Report</h2>
            <p>Completion status for all active users across all courses.</p>
        </div>
        <a href="?export=csv" class="btn btn-navy btn-sm">Export CSV</a>
    </div>

    <div class="card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <?php foreach ($courses as $c): ?>
                        <th><?= h($c['title']) ?></th>
                    <?php endforeach; ?>
                    <th>Overall</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report as $r):
                    $total_done  = array_sum(array_column($r['courses'], 'done'));
                    $total_total = array_sum(array_column($r['courses'], 'total'));
                    $overall_pct = $total_total > 0 ? round($total_done / $total_total * 100) : 0;
                ?>
                <tr>
                    <td>
                        <?= h($r['name']) ?>
                        <br><span style="font-size:.8rem;color:#888"><?= h($r['email']) ?></span>
                    </td>
                    <?php foreach ($courses as $c):
                        $p = $r['courses'][$c['id']];
                    ?>
                    <td>
                        <?php if ($p['pct'] === 100): ?>
                            <span class="badge badge-green">✓ Complete</span>
                        <?php elseif ($p['done'] > 0): ?>
                            <span class="badge badge-blue"><?= $p['done'] ?>/<?= $p['total'] ?> (<?= $p['pct'] ?>%)</span>
                        <?php else: ?>
                            <span class="badge badge-gray">Not started</span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                    <td>
                        <strong><?= $overall_pct ?>%</strong>
                        <div class="progress-bar-wrap" style="margin-top:.3rem">
                            <div class="progress-bar-fill" style="width:<?= $overall_pct ?>%"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="wts-lms-progress-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    $headers = ['Name', 'Email'];
    foreach ($courses as $c) $headers[] = $c['title'];
    $headers[] = 'Overall %';
    fputcsv($out, $headers);
    foreach ($report as $r) {
        $row = [$r['name'], $r['email']];
        $total_done = $total_total = 0;
        foreach ($courses as $c) {
            $p = $r['courses'][$c['id']];
            $row[] = $p['pct'] . '%';
            $total_done  += $p['done'];
            $total_total += $p['total'];
        }
        $row[] = ($total_total > 0 ? round($total_done / $total_total * 100) : 0) . '%';
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}
?>
</body>
</html>







