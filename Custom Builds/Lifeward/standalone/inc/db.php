<?php
/**
 * PDO/SQLite connection + schema. No WordPress, no external DB server required —
 * just the pdo_sqlite PHP extension, which is enabled by default on almost all
 * shared PHP hosting (see ../check.php to confirm on your specific host).
 */

function lw_db() {
    static $pdo = null;
    if ( $pdo !== null ) return $pdo;

    $dir = __DIR__ . '/../data';
    if ( ! is_dir( $dir ) ) mkdir( $dir, 0775, true );
    $path = $dir . '/lifeward.sqlite';

    $pdo = new PDO( 'sqlite:' . $path );
    $pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $pdo->exec( 'PRAGMA foreign_keys = ON' );

    lw_db_migrate( $pdo );
    return $pdo;
}

function lw_db_migrate( $pdo ) {
    $pdo->exec( "CREATE TABLE IF NOT EXISTS leads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL DEFAULT '',
        email TEXT NOT NULL DEFAULT '',
        phone TEXT NOT NULL DEFAULT '',
        status TEXT NOT NULL DEFAULT 'new',
        score INTEGER NOT NULL DEFAULT 0,
        notes TEXT NOT NULL DEFAULT '',
        slot_choice_utc TEXT,
        salesforce_id TEXT NOT NULL DEFAULT '',
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL
    )" );

    $pdo->exec( "CREATE TABLE IF NOT EXISTS sessions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        token TEXT NOT NULL UNIQUE,
        stage TEXT NOT NULL DEFAULT 'greeting',
        name TEXT NOT NULL DEFAULT '',
        email TEXT NOT NULL DEFAULT '',
        phone TEXT NOT NULL DEFAULT '',
        lead_id INTEGER NOT NULL DEFAULT 0,
        ip_hash TEXT NOT NULL DEFAULT '',
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL
    )" );

    $pdo->exec( "CREATE TABLE IF NOT EXISTS activity (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lead_id INTEGER NOT NULL DEFAULT 0,
        action TEXT NOT NULL,
        detail TEXT NOT NULL DEFAULT '',
        created_at TEXT NOT NULL
    )" );

    $pdo->exec( "CREATE TABLE IF NOT EXISTS chat_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id INTEGER NOT NULL DEFAULT 0,
        role TEXT NOT NULL DEFAULT 'user',
        body TEXT NOT NULL DEFAULT '',
        created_at TEXT NOT NULL
    )" );

    $pdo->exec( "CREATE TABLE IF NOT EXISTS rate_limits (
        ip_hash TEXT NOT NULL,
        kind TEXT NOT NULL,
        window_key TEXT NOT NULL,
        count INTEGER NOT NULL DEFAULT 0,
        PRIMARY KEY (ip_hash, kind, window_key)
    )" );

    $pdo->exec( 'CREATE INDEX IF NOT EXISTS idx_sessions_token ON sessions(token)' );
    $pdo->exec( 'CREATE INDEX IF NOT EXISTS idx_activity_lead ON activity(lead_id)' );
    $pdo->exec( 'CREATE INDEX IF NOT EXISTS idx_chat_messages_session ON chat_messages(session_id)' );
}

function lw_client_ip() {
    return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

function lw_hash_ip( $ip ) {
    return hash( 'sha256', $ip . '|lifeward-standalone' );
}

/**
 * True if this IP has exceeded $max hits for $kind within the current window.
 * Plain select-then-write instead of an SQLite UPSERT — some hosts bundle an
 * SQLite build older than 3.24 (no ON CONFLICT support), and this is a
 * low-concurrency rate limiter, not a ledger, so the small race window between
 * the SELECT and the INSERT/UPDATE (two simultaneous requests from the exact
 * same IP in the same window) is an acceptable trade-off for portability.
 */
function lw_rate_limited( $kind, $ip_hash, $max, $window_key ) {
    $pdo = lw_db();
    $stmt = $pdo->prepare( 'SELECT count FROM rate_limits WHERE ip_hash = ? AND kind = ? AND window_key = ?' );
    $stmt->execute( array( $ip_hash, $kind, $window_key ) );
    $existing = $stmt->fetchColumn();

    if ( $existing === false ) {
        $pdo->prepare( 'INSERT INTO rate_limits (ip_hash, kind, window_key, count) VALUES (?, ?, ?, 1)' )
            ->execute( array( $ip_hash, $kind, $window_key ) );
        $count = 1;
    } else {
        $count = (int) $existing + 1;
        $pdo->prepare( 'UPDATE rate_limits SET count = ? WHERE ip_hash = ? AND kind = ? AND window_key = ?' )
            ->execute( array( $count, $ip_hash, $kind, $window_key ) );
    }

    return $count > $max;
}

function lw_now() {
    return gmdate( 'Y-m-d\TH:i:s\Z' );
}

function lw_log_activity( $lead_id, $action, $detail ) {
    $stmt = lw_db()->prepare( 'INSERT INTO activity (lead_id, action, detail, created_at) VALUES (?, ?, ?, ?)' );
    $stmt->execute( array( (int) $lead_id, $action, is_string( $detail ) ? $detail : json_encode( $detail ), lw_now() ) );
}
