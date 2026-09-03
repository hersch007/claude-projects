<?php
// Base URL path Ã¢â‚¬â€ change this if the folder name changes
define('BASE', '/wts_documentation');

// Database configuration Ã¢â‚¬â€ update these values for the live server
define('DB_HOST', 'localhost');
define('DB_NAME', 'start6zs_wts_lms');
define('DB_USER', 'start6zs_wts_lms');
define('DB_PASS', 'WTS@Lms2026!');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            die('Database connection failed. Please contact your administrator.');
        }
    }
    return $pdo;
}
