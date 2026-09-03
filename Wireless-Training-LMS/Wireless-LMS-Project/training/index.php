<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user()) {
    header('Location: /wts_documentation/training/dashboard.php');
} else {
    header('Location: /wts_documentation/training/login.php');
}
exit;







