<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class Echo64_Session {

    private const COOKIE_NAME = 'echo64_sid';
    private const COOKIE_TTL  = 60 * 60 * 24 * 30; // 30 days

    public static function get_session_id(): string {
        if ( ! empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
            $sid = sanitize_text_field( $_COOKIE[ self::COOKIE_NAME ] );
            if ( preg_match( '/^[a-f0-9]{64}$/', $sid ) ) {
                return $sid;
            }
        }

        $sid = bin2hex( random_bytes( 32 ) );

        if ( ! headers_sent() ) {
            setcookie(
                self::COOKIE_NAME,
                $sid,
                [
                    'expires'  => time() + self::COOKIE_TTL,
                    'path'     => '/',
                    'secure'   => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
        }

        return $sid;
    }

    public static function get_history( string $session_id, int $limit = 20 ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'echo64_conversations';
        $limit = max( 2, min( 100, $limit ) );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT role, content FROM (
                    SELECT id, role, content FROM {$table}
                    WHERE session_id = %s
                    ORDER BY id DESC
                    LIMIT %d
                ) sub ORDER BY id ASC",
                $session_id,
                $limit
            ),
            ARRAY_A
        );

        if ( ! is_array( $rows ) ) {
            return [];
        }

        // The API requires the conversation to start with a user turn.
        // Our init flow saves only an assistant turn (the opening poem), so we inject
        // a silent synthetic user turn before it when the first stored row is 'assistant'.
        if ( ! empty( $rows ) && $rows[0]['role'] === 'assistant' ) {
            array_unshift( $rows, [
                'role'    => 'user',
                'content' => '[START]',
            ] );
        }

        return $rows;
    }

    public static function save_message( string $session_id, string $role, string $content ): void {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'echo64_conversations',
            [
                'session_id' => $session_id,
                'user_id'    => get_current_user_id(),
                'role'       => $role,
                'content'    => $content,
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%d', '%s', '%s', '%s' ]
        );
    }

    public static function clear_session( string $session_id ): void {
        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'echo64_conversations',
            [ 'session_id' => $session_id ],
            [ '%s' ]
        );
    }

    public static function is_new_session( string $session_id ): bool {
        global $wpdb;
        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}echo64_conversations WHERE session_id = %s",
                $session_id
            )
        );
        return $count === 0;
    }

    /**
     * Returns the last real user message for this session (max 8 words).
     * Excludes internal trigger messages like [START], [NEW_DAY], etc.
     */
    public static function get_last_user_message( string $session_id ): string {
        global $wpdb;
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT content FROM {$wpdb->prefix}echo64_conversations
                 WHERE session_id = %s
                   AND role = 'user'
                   AND content NOT LIKE '[%]'
                 ORDER BY id DESC
                 LIMIT 1",
                $session_id
            )
        );
        if ( empty( $result ) ) return '';
        // Trim to 8 words so it fits naturally in a greeting template
        $words = explode( ' ', wp_strip_all_tags( $result ) );
        if ( count( $words ) <= 8 ) return $result;
        return implode( ' ', array_slice( $words, 0, 8 ) ) . '…';
    }

    /**
     * Returns true if this session hasn't had a poem generated today (calendar day, site timezone).
     */
    public static function needs_daily_refresh( string $session_id ): bool {
        $key      = 'echo64_daily_' . $session_id;
        $stored   = get_transient( $key );
        $today    = wp_date( 'Y-m-d' );
        return $stored !== $today;
    }

    /**
     * Records that we delivered today's poem for this session.
     */
    public static function mark_daily_refresh( string $session_id ): void {
        $key   = 'echo64_daily_' . $session_id;
        $today = wp_date( 'Y-m-d' );
        // TTL: 48 h — generous so time-zone edge cases don't cause double-poems
        set_transient( $key, $today, 48 * HOUR_IN_SECONDS );
    }

    // ── v1.4.0  Daily Transmission Limit ─────────────────────────────────

    /**
     * Returns how many API messages this session has sent today.
     */
    public static function get_daily_tx_count( string $session_id ): int {
        $key = 'echo64_tx_' . substr( $session_id, 0, 16 ) . '_' . wp_date( 'Y-m-d' );
        return (int) get_transient( $key );
    }

    /**
     * Increments today's count and returns the new value.
     * TTL expires precisely at local midnight so the quota resets cleanly each day.
     */
    public static function increment_daily_tx( string $session_id ): int {
        $key   = 'echo64_tx_' . substr( $session_id, 0, 16 ) . '_' . wp_date( 'Y-m-d' );
        $count = (int) get_transient( $key ) + 1;

        // Seconds until midnight in the site's configured timezone
        $now      = current_time( 'timestamp' );
        $midnight = mktime( 0, 0, 0,
            (int) wp_date( 'n', $now ),
            (int) wp_date( 'j', $now ) + 1,
            (int) wp_date( 'Y', $now )
        );
        $ttl = max( 60, $midnight - $now );

        set_transient( $key, $count, $ttl );
        return $count;
    }
}
