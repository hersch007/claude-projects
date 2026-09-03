<?php
/**
 * One-time environment self-test — visit /check.php after uploading to confirm
 * this host can actually run the app, before wiring up the real page. Safe to
 * delete once everything shows OK.
 */
header( 'Content-Type: text/plain; charset=utf-8' );

function check( $label, $ok, $detail = '' ) {
    echo ( $ok ? '[OK]   ' : '[FAIL] ' ) . $label . ( $detail ? " ($detail)" : '' ) . "\n";
}

check( 'PHP version >= 7.4', version_compare( PHP_VERSION, '7.4.0', '>=' ), PHP_VERSION );
check( 'pdo_sqlite extension', extension_loaded( 'pdo_sqlite' ) );
check( 'json extension', function_exists( 'json_encode' ) );
check( 'mail() available', function_exists( 'mail' ) );
check( 'cURL available (optional — has a fallback)', function_exists( 'curl_init' ) );
check( 'mbstring available (optional — has a fallback)', function_exists( 'mb_strlen' ) );

$dataDir = __DIR__ . '/data';
if ( ! is_dir( $dataDir ) ) @mkdir( $dataDir, 0775, true );
check( 'data/ directory exists', is_dir( $dataDir ) );
check( 'data/ directory writable', is_writable( $dataDir ), $dataDir );

try {
    require_once __DIR__ . '/inc/db.php';
    lw_db();
    check( 'SQLite database opens + migrates', true );
} catch ( Exception $e ) {
    check( 'SQLite database opens + migrates', false, $e->getMessage() );
}

echo "\nIf everything above is [OK], visit /admin/setup.php to set an admin password, then open / to see the landing page.\n";
