<?php
/**
 * Callback slot generation/validation — Mon–Fri, 9am–5pm Eastern, timezone-safe
 * regardless of what timezone the PHP server itself runs in.
 */

function lw_business_hours() {
    return range( 9, 16 ); // hour a slot can *start* at; last slot 4–5pm
}

function lw_generate_slots( $count = 12 ) {
    $tz  = new DateTimeZone( 'America/New_York' );
    $now = new DateTime( 'now', $tz );
    $slots = array();
    $hours = lw_business_hours();

    $cursor = clone $now;
    $cursor->setTime( 0, 0, 0 );
    $days_checked = 0;

    while ( count( $slots ) < $count && $days_checked < 30 ) {
        $dow = (int) $cursor->format( 'N' ); // 1 Mon .. 7 Sun
        if ( $dow >= 1 && $dow <= 5 ) {
            foreach ( $hours as $h ) {
                $slot = clone $cursor;
                $slot->setTime( $h, 0, 0 );
                if ( $slot > $now ) {
                    $slots[] = array( 'value' => $slot->format( DATE_ATOM ), 'label' => lw_format_slot_label( $slot->format( DATE_ATOM ) ) );
                    if ( count( $slots ) >= $count ) break;
                }
            }
        }
        $cursor->modify( '+1 day' );
        $days_checked++;
    }
    return $slots;
}

function lw_slot_datetime( $iso ) {
    if ( $iso === '' ) return null;
    try {
        $dt = new DateTime( $iso );
        $dt->setTimezone( new DateTimeZone( 'America/New_York' ) );
        return $dt;
    } catch ( Exception $e ) {
        return null;
    }
}

function lw_slot_is_valid( $iso ) {
    $dt = lw_slot_datetime( $iso );
    if ( ! $dt ) return false;
    $now = new DateTime( 'now', new DateTimeZone( 'America/New_York' ) );
    if ( $dt <= $now ) return false;
    $dow = (int) $dt->format( 'N' );
    if ( $dow < 1 || $dow > 5 ) return false;
    if ( (int) $dt->format( 'i' ) !== 0 ) return false;
    return in_array( (int) $dt->format( 'G' ), lw_business_hours(), true );
}

/** Like lw_slot_is_valid() but for the freeform "specific day/time" form, which
 *  lets a visitor type any minute — only the dropdown-based schedule_form needs
 *  to land on the hour. */
function lw_specific_time_is_valid( $iso ) {
    $dt = lw_slot_datetime( $iso );
    if ( ! $dt ) return false;
    $now = new DateTime( 'now', new DateTimeZone( 'America/New_York' ) );
    if ( $dt <= $now ) return false;
    $dow = (int) $dt->format( 'N' );
    if ( $dow < 1 || $dow > 5 ) return false;
    $hour = (int) $dt->format( 'G' );
    return $hour >= 9 && $hour <= 16;
}

function lw_slot_to_utc( $iso ) {
    $dt = lw_slot_datetime( $iso );
    if ( ! $dt ) return null;
    $dt->setTimezone( new DateTimeZone( 'UTC' ) );
    return $dt->format( 'Y-m-d\TH:i:s\Z' );
}

function lw_format_slot_label( $iso ) {
    $dt = lw_slot_datetime( $iso );
    return $dt ? $dt->format( 'D, M j \a\t g:i A' ) . ' ET' : $iso;
}

/** True if the given Eastern-time DateTime falls Mon–Fri, 9am–5pm. */
function lw_is_business_hours_at( $dt ) {
    $dow = (int) $dt->format( 'N' );
    if ( $dow < 1 || $dow > 5 ) return false;
    $hour = (int) $dt->format( 'G' );
    return $hour >= 9 && $hour < 17;
}

/** True if it's currently Mon–Fri, 9am–5pm Eastern — drives the "call back in 15
 *  minutes" vs. "next business day" confirmation message. */
function lw_is_business_hours_now() {
    return lw_is_business_hours_at( new DateTime( 'now', new DateTimeZone( 'America/New_York' ) ) );
}

/** Combines a raw date input + time input pair (assumed to be entered in
 *  Eastern, since that's the only availability window we quote) into the same
 *  ISO-8601 shape lw_generate_slots() produces, so lw_slot_is_valid()/
 *  lw_slot_to_utc()/lw_format_slot_label() all work unchanged on it. */
function lw_specific_slot_iso( $day, $time ) {
    if ( $day === '' || $time === '' ) return '';
    if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $day ) || ! preg_match( '/^\d{2}:\d{2}$/', $time ) ) return '';
    try {
        $dt = new DateTime( "{$day}T{$time}:00", new DateTimeZone( 'America/New_York' ) );
        return $dt->format( DATE_ATOM );
    } catch ( Exception $e ) {
        return '';
    }
}
