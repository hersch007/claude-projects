<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Fire notifications for a new ticket: find who is on-call for each selected
 * department and send email + SMS.
 */
function sp_city_notify_oncall( $ticket_id, $dept_ids ) {
    global $wpdb;

    $ticket = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sp_city_tickets WHERE id = %d", $ticket_id
    ) );
    if ( ! $ticket ) return;

    $city_name     = get_option( 'sp_city_name', get_bloginfo( 'name' ) );
    $escalate_mins = (int) get_option( 'sp_city_escalate_minutes', 30 );
    $view_url      = home_url( '/sp-app/?view=city-tickets&action=edit&id=' . $ticket_id );

    foreach ( $dept_ids as $dept_id ) {
        $dept = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sp_city_departments WHERE id = %d", $dept_id
        ) );
        if ( ! $dept ) continue;

        $oncall = sp_city_get_oncall_now( $dept_id );

        if ( empty( $oncall ) ) {
            // No one on call — notify supervisor fallback
            sp_city_notify_supervisor_no_oncall( $ticket, $dept );
            continue;
        }

        foreach ( $oncall as $shift ) {
            $recipient_name  = $shift->member_name ?: 'On-Call Staff';
            $recipient_email = $shift->email ?: ( $shift->member_email ?? '' );
            $recipient_phone = $shift->phone;

            $subject = "[{$city_name}] NEW " . strtoupper( $ticket->priority ) . " TICKET #{$ticket->ticket_number} — {$dept->name}";

            $body = "Hello {$recipient_name},\n\n"
                . "A new service ticket has been submitted that requires your attention.\n\n"
                . "Ticket #: {$ticket->ticket_number}\n"
                . "Department: {$dept->name}\n"
                . "Priority: " . strtoupper( $ticket->priority ) . "\n"
                . "Address: {$ticket->address}\n"
                . "Reporter: {$ticket->reporter_name}"
                . ( $ticket->reporter_phone ? " ({$ticket->reporter_phone})" : "" ) . "\n\n"
                . "Description:\n{$ticket->description}\n\n"
                . "View & acknowledge: {$view_url}\n\n"
                . "Please acknowledge within {$escalate_mins} minutes or the ticket will be escalated.\n\n"
                . "— {$city_name} Service System";

            // Email
            if ( $recipient_email ) {
                $sent = wp_mail( $recipient_email, $subject, $body );
                sp_city_log_notification( $ticket_id, $dept->name, $recipient_name, $recipient_email, '', 'email', $body, $sent ? 'sent' : 'failed' );
            }

            // SMS via Twilio
            if ( $recipient_phone ) {
                $sms = "ALERT [{$city_name}] Ticket #{$ticket->ticket_number} ({$dept->name} / " . strtoupper( $ticket->priority ) . "): {$ticket->address}. Log in to acknowledge.";
                sp_city_send_sms( $recipient_phone, $sms, $ticket_id, $dept->name, $recipient_name );
            }
        }
    }

    // Schedule escalation check
    if ( $escalate_mins > 0 ) {
        wp_schedule_single_event(
            time() + ( $escalate_mins * 60 ),
            'sp_city_escalation_check',
            array( $ticket_id )
        );
    }
}

/**
 * Escalation: called by WP-Cron if ticket is still unacknowledged.
 */
function sp_city_escalation_check( $ticket_id ) {
    global $wpdb;
    $ticket = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sp_city_tickets WHERE id = %d AND acknowledged_at IS NULL", $ticket_id
    ) );
    if ( ! $ticket ) return; // already acknowledged

    $supervisor_email = get_option( 'sp_city_supervisor_email', '' );
    $city_name        = get_option( 'sp_city_name', get_bloginfo( 'name' ) );
    $view_url         = home_url( '/sp-app/?view=city-tickets&action=edit&id=' . $ticket_id );

    if ( $supervisor_email ) {
        $subject = "[ESCALATION] Ticket #{$ticket->ticket_number} not acknowledged — {$city_name}";
        $body    = "This ticket has not been acknowledged and requires immediate attention.\n\n"
            . "Ticket #: {$ticket->ticket_number}\n"
            . "Priority: " . strtoupper( $ticket->priority ) . "\n"
            . "Address: {$ticket->address}\n"
            . "Submitted: {$ticket->created_at}\n\n"
            . "View ticket: {$view_url}\n\n"
            . "— {$city_name} Service System";
        wp_mail( $supervisor_email, $subject, $body );
    }

    // SMS supervisor
    $supervisor_phone = get_option( 'sp_city_supervisor_phone', '' );
    if ( $supervisor_phone ) {
        $sms = "ESCALATION [{$city_name}]: Ticket #{$ticket->ticket_number} ({$ticket->priority}) at {$ticket->address} has not been acknowledged. Check system.";
        sp_city_send_sms( $supervisor_phone, $sms, $ticket_id, 'Escalation', 'Supervisor' );
    }
}
add_action( 'sp_city_escalation_check', 'sp_city_escalation_check' );

/**
 * Send confirmation email to the public reporter.
 */
function sp_city_notify_reporter( $ticket ) {
    if ( ! $ticket->reporter_email ) return;

    $city_name = get_option( 'sp_city_name', get_bloginfo( 'name' ) );
    $subject   = "[{$city_name}] Service Request Received — #{$ticket->ticket_number}";
    $body      = "Hello {$ticket->reporter_name},\n\n"
        . "We have received your service request and our team has been notified.\n\n"
        . "Your ticket number is: {$ticket->ticket_number}\n"
        . "Please save this number to reference your request.\n\n"
        . "Address reported: {$ticket->address}\n\n"
        . "We will contact you if additional information is needed.\n\n"
        . "Thank you,\n{$city_name} Public Services";
    wp_mail( $ticket->reporter_email, $subject, $body );
}

/**
 * Fallback when no one is on-call for a department.
 */
function sp_city_notify_supervisor_no_oncall( $ticket, $dept ) {
    $supervisor_email = get_option( 'sp_city_supervisor_email', '' );
    if ( ! $supervisor_email ) return;
    $city_name = get_option( 'sp_city_name', get_bloginfo( 'name' ) );
    $subject   = "[{$city_name}] Ticket #{$ticket->ticket_number} — No one on call for {$dept->name}";
    $body      = "A ticket was submitted for {$dept->name} but no one is currently scheduled on call.\n\n"
        . "Ticket #: {$ticket->ticket_number}\n"
        . "Priority: " . strtoupper( $ticket->priority ) . "\n"
        . "Address: {$ticket->address}\n\n"
        . "Please assign someone immediately.";
    wp_mail( $supervisor_email, $subject, $body );
}

/**
 * Send SMS via Twilio REST API.
 */
function sp_city_send_sms( $to_phone, $message, $ticket_id, $dept_name, $recipient_name ) {
    $account_sid  = get_option( 'sp_city_twilio_sid', '' );
    $auth_token   = get_option( 'sp_city_twilio_token', '' );
    $from_number  = get_option( 'sp_city_twilio_from', '' );

    if ( ! $account_sid || ! $auth_token || ! $from_number ) {
        sp_city_log_notification( $ticket_id, $dept_name, $recipient_name, '', $to_phone, 'sms', $message, 'skipped_no_config' );
        return;
    }

    // Normalize phone to E.164
    $to = preg_replace( '/[^0-9+]/', '', $to_phone );
    if ( substr( $to, 0, 1 ) !== '+' ) {
        $to = '+1' . $to; // default to US
    }

    $response = wp_remote_post(
        "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json",
        array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( "{$account_sid}:{$auth_token}" ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body'    => http_build_query( array(
                'To'   => $to,
                'From' => $from_number,
                'Body' => $message,
            ) ),
            'timeout' => 15,
        )
    );

    $status = ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 201 )
        ? 'sent' : 'failed';
    sp_city_log_notification( $ticket_id, $dept_name, $recipient_name, '', $to_phone, 'sms', $message, $status );
}

/**
 * Notify the team member directly assigned to a ticket.
 * Fires on new ticket creation or when the assigned person changes on edit.
 */
function sp_city_notify_assigned( $ticket_id, $member_id ) {
    if ( ! $member_id ) return;

    global $wpdb;

    $ticket = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sp_city_tickets WHERE id = %d", $ticket_id
    ) );
    if ( ! $ticket ) return;

    $member = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sp_team WHERE id = %d", $member_id
    ) );
    if ( ! $member ) return;

    $city_name = get_option( 'sp_city_name', get_bloginfo( 'name' ) );
    $view_url  = home_url( '/sp-app/?view=city-tickets&action=edit&id=' . $ticket_id );

    $depts = $wpdb->get_col( $wpdb->prepare(
        "SELECT d.name FROM {$wpdb->prefix}sp_city_ticket_depts td
         JOIN {$wpdb->prefix}sp_city_departments d ON d.id = td.dept_id
         WHERE td.ticket_id = %d ORDER BY d.sort_order",
        $ticket_id
    ) );
    $dept_str = $depts ? implode( ', ', $depts ) : 'General';

    $subject = "[{$city_name}] You've been assigned Ticket #{$ticket->ticket_number}";
    $body    = "Hello {$member->name},\n\n"
        . "You have been assigned a service ticket that requires your attention.\n\n"
        . "Ticket #:    {$ticket->ticket_number}\n"
        . "Department:  {$dept_str}\n"
        . "Priority:    " . strtoupper( $ticket->priority ) . "\n"
        . "Address:     {$ticket->address}\n"
        . "Reporter:    {$ticket->reporter_name}"
        . ( $ticket->reporter_phone ? " ({$ticket->reporter_phone})" : '' ) . "\n\n"
        . "Description:\n{$ticket->description}\n\n"
        . "View ticket: {$view_url}\n\n"
        . "— {$city_name} Service System";

    if ( $member->email ) {
        $sent = wp_mail( $member->email, $subject, $body );
        sp_city_log_notification( $ticket_id, $dept_str, $member->name, $member->email, '', 'email', $body, $sent ? 'sent' : 'failed' );
    }

    if ( ! empty( $member->phone ) ) {
        $sms = "ASSIGNED [{$city_name}] Ticket #{$ticket->ticket_number} ({$dept_str} / " . strtoupper( $ticket->priority ) . "): {$ticket->address}. View: {$view_url}";
        sp_city_send_sms( $member->phone, $sms, $ticket_id, $dept_str, $member->name );
    }
}

/**
 * Write a row to the notification log.
 */
function sp_city_log_notification( $ticket_id, $dept_name, $recipient_name, $email, $phone, $channel, $message, $status ) {
    global $wpdb;
    $wpdb->insert( $wpdb->prefix . 'sp_city_notification_log', array(
        'ticket_id'       => $ticket_id,
        'dept_name'       => $dept_name,
        'recipient_name'  => $recipient_name,
        'recipient_email' => $email,
        'recipient_phone' => $phone,
        'channel'         => $channel,
        'message'         => $message,
        'sent_at'         => current_time( 'mysql' ),
        'status'          => $status,
    ) );
}
