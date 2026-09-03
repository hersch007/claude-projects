<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Return on-call entries active right now, optionally filtered by dept_id.
 */
function sp_city_get_oncall_now( $dept_id = 0 ) {
    global $wpdb;
    $now = current_time( 'mysql' );
    if ( $dept_id ) {
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT o.*, d.name AS dept_name, d.color AS dept_color, tm.name AS member_name
             FROM {$wpdb->prefix}sp_city_oncall o
             LEFT JOIN {$wpdb->prefix}sp_city_departments d ON d.id = o.dept_id
             LEFT JOIN {$wpdb->prefix}sp_team tm ON tm.id = o.team_member_id
             WHERE o.dept_id = %d AND o.start_datetime <= %s AND o.end_datetime >= %s
             ORDER BY d.sort_order, tm.name",
            $dept_id, $now, $now
        ) );
    }
    return $wpdb->get_results( $wpdb->prepare(
        "SELECT o.*, d.name AS dept_name, d.color AS dept_color, tm.name AS member_name
         FROM {$wpdb->prefix}sp_city_oncall o
         LEFT JOIN {$wpdb->prefix}sp_city_departments d ON d.id = o.dept_id
         LEFT JOIN {$wpdb->prefix}sp_team tm ON tm.id = o.team_member_id
         WHERE o.start_datetime <= %s AND o.end_datetime >= %s
         ORDER BY d.sort_order, tm.name",
        $now, $now
    ) );
}

/**
 * Return upcoming on-call entries (next 72 hours).
 */
function sp_city_get_oncall_upcoming( $hours = 72 ) {
    global $wpdb;
    $now  = current_time( 'mysql' );
    $thru = date( 'Y-m-d H:i:s', strtotime( "+{$hours} hours", current_time( 'timestamp' ) ) );
    return $wpdb->get_results( $wpdb->prepare(
        "SELECT o.*, d.name AS dept_name, d.color AS dept_color, tm.name AS member_name
         FROM {$wpdb->prefix}sp_city_oncall o
         LEFT JOIN {$wpdb->prefix}sp_city_departments d ON d.id = o.dept_id
         LEFT JOIN {$wpdb->prefix}sp_team tm ON tm.id = o.team_member_id
         WHERE o.start_datetime > %s AND o.start_datetime <= %s
         ORDER BY o.start_datetime, d.sort_order",
        $now, $thru
    ) );
}

/**
 * Return all active departments.
 */
function sp_city_get_departments() {
    global $wpdb;
    return $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}sp_city_departments WHERE active = 1 ORDER BY sort_order, name"
    );
}

/**
 * Generate a unique ticket number: CSR-YYYYMMDD-NNNNN
 */
function sp_city_generate_ticket_number( $id ) {
    return 'CSR-' . date( 'Ymd' ) . '-' . str_pad( $id, 5, '0', STR_PAD_LEFT );
}

/**
 * Human-friendly time window label.
 */
function sp_city_format_shift( $start, $end ) {
    $s = strtotime( $start );
    $e = strtotime( $end );
    if ( date( 'Y-m-d', $s ) === date( 'Y-m-d', $e ) ) {
        return date( 'D M j, g:ia', $s ) . ' - ' . date( 'g:ia', $e );
    }
    return date( 'D M j, g:ia', $s ) . ' - ' . date( 'D M j, g:ia', $e );
}
