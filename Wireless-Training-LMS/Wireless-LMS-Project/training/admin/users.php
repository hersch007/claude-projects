<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$pdo   = db();
$error = $success = '';

// CSRF check on all POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_verify();

// Add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name  = trim($_POST['name']  ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $role  = $_POST['role'] === 'admin' ? 'admin' : 'user';
    $temp  = $_POST['temp_password'] ?? '';

    if (!$name || !$email || !$temp) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        $hash = password_hash($temp, PASSWORD_BCRYPT);
        try {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, must_change_pw) VALUES (?,?,?,?,1)');
            $stmt->execute([$name, $email, $hash, $role]);
            $success = "User $name ($email) added. Temp password: $temp";
        } catch (PDOException $e) {
            $error = 'Email already exists.';
        }
    }
}

// Toggle active
if (isset($_GET['toggle'])) {
    $stmt = $pdo->prepare('UPDATE users SET active = 1 - active WHERE id = ? AND role != "admin"');
    $stmt->execute([(int)$_GET['toggle']]);
    header('Location: /wts_documentation/training/admin/users.php');
    exit;
}

// Reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_pw'])) {
    $uid      = (int)$_POST['user_id'];
    $new_pass = $_POST['new_temp_pass'] ?? '';
    if ($new_pass && $uid) {
        $hash = password_hash($new_pass, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE users SET password = ?, must_change_pw = 1 WHERE id = ?')->execute([$hash, $uid]);
        $success = "Password reset. New temp password: $new_pass";
    }
}

$users = $pdo->query('SELECT * FROM users ORDER BY role, name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Users — WTS AMP Admin</title>
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
    <div class="page-heading">
        <h2>User Management</h2>
        <p>Add and manage training users. Users set their own password on first login.</p>
    </div>

    <?php if ($error):   ?><div class="alert alert-error"><?=   h($error)   ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>

    <!-- Add User Form -->
    <div class="card" style="margin-bottom:2rem">
        <h3 style="color:var(--navy);margin-bottom:1rem">Add New User</h3>
        <form method="POST" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:.75rem;align-items:end">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="form-group" style="margin:0">
                <label>Full Name</label>
                <input type="text" name="name" required placeholder="Jane Smith">
            </div>
            <div class="form-group" style="margin:0">
                <label>Email</label>
                <input type="email" name="email" required placeholder="jane@example.com">
            </div>
            <div class="form-group" style="margin:0">
                <label>Temp Password</label>
                <input type="text" name="temp_password" required placeholder="WTS-Temp-2026!">
            </div>
            <div class="form-group" style="margin:0">
                <label>Role</label>
                <select name="role" style="width:100%;padding:.6rem .85rem;border:1px solid var(--border);border-radius:5px">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
        </form>
    </div>

    <!-- User List -->
    <div class="card">
        <h3 style="color:var(--navy);margin-bottom:1rem">All Users (<?= count($users) ?>)</h3>
        <table class="admin-table">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= h($u['name']) ?></td>
                    <td><?= h($u['email']) ?></td>
                    <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-blue' : 'badge-gray' ?>"><?= h($u['role']) ?></span></td>
                    <td>
                        <?php if ($u['active']): ?>
                            <span class="badge badge-green">Active</span>
                        <?php else: ?>
                            <span class="badge badge-red">Inactive</span>
                        <?php endif; ?>
                        <?php if ($u['must_change_pw']): ?>
                            <span class="badge badge-gray" style="margin-left:.3rem">Pending PW</span>
                        <?php endif; ?>
                    </td>
                    <td style="display:flex;gap:.4rem;flex-wrap:wrap">
                        <?php if ($u['role'] !== 'admin'): ?>
                            <a href="?toggle=<?= $u['id'] ?>" class="btn btn-sm <?= $u['active'] ? 'btn-danger' : 'btn-success' ?>"
                               onclick="return confirm('<?= $u['active'] ? 'Deactivate' : 'Activate' ?> this user?')">
                                <?= $u['active'] ? 'Deactivate' : 'Activate' ?>
                            </a>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-navy" onclick="showReset(<?= $u['id'] ?>, '<?= h($u['name']) ?>')">Reset PW</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Reset PW Modal -->
<div id="reset-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:100;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:8px;padding:2rem;width:380px">
        <h3 style="color:var(--navy);margin-bottom:1rem">Reset Password</h3>
        <p id="reset-name" style="margin-bottom:1rem;color:#666"></p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="user_id" id="reset-uid">
            <div class="form-group">
                <label>New Temporary Password</label>
                <input type="text" name="new_temp_pass" required placeholder="WTS-Temp-2026!">
            </div>
            <div style="display:flex;gap:.5rem;justify-content:flex-end">
                <button type="button" class="btn btn-navy btn-sm" onclick="hideReset()">Cancel</button>
                <button type="submit" name="reset_pw" class="btn btn-primary btn-sm">Reset</button>
            </div>
        </form>
    </div>
</div>

<script>
function showReset(id, name) {
    document.getElementById('reset-uid').value = id;
    document.getElementById('reset-name').textContent = 'User: ' + name;
    document.getElementById('reset-modal').style.display = 'flex';
}
function hideReset() {
    document.getElementById('reset-modal').style.display = 'none';
}
</script>

</body>
</html>







