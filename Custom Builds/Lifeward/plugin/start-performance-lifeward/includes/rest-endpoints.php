<?php
/**
 * Public, unauthenticated funnel endpoint (REST namespace: sp-lifeward/v1).
 * One route, /step, drives the whole guided flow: the client always POSTs
 * {token, step, name?, email?, phone?, slot?} and the server returns the next
 * {reply, buttons, fields}. All persona copy and branching lives here so the
 * frontend widget stays a thin renderer — see assets/lifeward-widget.js.
 *
 * Terminal actions (call now, schedule, send info, not interested) each: find-or-
 * create the visitor in sp_contacts, create-or-update a row in sp_leads, log to
 * sp_activity, and call the Salesforce mock client. Scheduling also creates an
 * sp_tasks row (day-level due date, for the team's task board) and an sp_notes
 * row with reminder_at set to the exact requested time.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', 'sp_lifeward_register_routes' );

function sp_lifeward_register_routes() {
    register_rest_route( 'sp-lifeward/v1', '/step', array(
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => 'sp_lifeward_ep_step',
    ) );
}

// ── Endpoint ─────────────────────────────────────────────────────────────────

function sp_lifeward_ep_step( $request ) {
    $ip_hash = sp_lifeward_hash_ip( sp_lifeward_client_ip() );

    if ( sp_lifeward_rate_limited( $ip_hash ) ) {
        return sp_lifeward_err( 'rate', 'Too many requests. Please try again shortly.', 429 );
    }

    $token = sanitize_text_field( (string) $request->get_param( 'token' ) );
    $step  = sanitize_key( (string) $request->get_param( 'step' ) );
    if ( $step === '' ) return sp_lifeward_err( 'bad_step', 'A step is required.', 400 );

    $input = array(
        'name'  => sanitize_text_field( (string) $request->get_param( 'name' ) ),
        'email' => sanitize_email( (string) $request->get_param( 'email' ) ),
        'phone' => sanitize_text_field( (string) $request->get_param( 'phone' ) ),
        'slot'  => sanitize_text_field( (string) $request->get_param( 'slot' ) ),
    );

    $terminal_steps = array( 'not_interested', 'submit_call_now', 'submit_schedule', 'submit_email' );
    if ( in_array( $step, $terminal_steps, true ) && sp_lifeward_terminal_limited( $ip_hash ) ) {
        return sp_lifeward_err( 'rate', 'Too many submissions from this network today.', 429 );
    }

    $session = sp_lifeward_get_or_create_session( $token, $ip_hash );
    $out     = sp_lifeward_transition( $session, $step, $input );

    return sp_lifeward_ok( array_merge( array( 'token' => $session->token ), $out ) );
}

// ── State machine ────────────────────────────────────────────────────────────

function sp_lifeward_transition( $session, $step, $in ) {
    $stage = $session->stage;

    if ( $stage === 'greeting' ) {
        if ( $step === 'call_now' ) {
            sp_lifeward_save_session( $session, array( 'stage' => 'call_now_when' ) );
            return array(
                'reply'   => "Of course — I'd love to get you connected. Would you like to talk right now, or would a scheduled callback work better for you?",
                'buttons' => array(
                    array( 'id' => 'call_now_immediate', 'label' => 'Talk right now' ),
                    array( 'id' => 'call_now_schedule',  'label' => 'Schedule a callback' ),
                ),
            );
        }
        if ( $step === 'send_info' ) {
            sp_lifeward_save_session( $session, array( 'stage' => 'awaiting_email' ) );
            return array(
                'reply'  => "Happy to send that your way. What's the best email to reach you at?",
                'fields' => array( 'type' => 'email_form' ),
            );
        }
        if ( $step === 'not_interested' ) {
            $contact_id = sp_lifeward_find_or_create_contact( '', '', '' );
            $lead_id    = sp_lifeward_upsert_lead( $session, $contact_id, 'lost', 0, 'Declined on landing page.' );
            SP_Lifeward_Salesforce_Client::sync_lead( $lead_id, array( 'name' => '', 'email' => '', 'phone' => '' ), 'lost' );
            sp_lifeward_save_session( $session, array( 'stage' => 'done', 'lead_id' => $lead_id, 'contact_id' => $contact_id ) );
            return array( 'reply' => 'Thank you so much for letting me know — I really appreciate your time today. Take care!', 'done' => true );
        }
    }

    if ( $stage === 'call_now_when' ) {
        if ( $step === 'call_now_immediate' ) {
            sp_lifeward_save_session( $session, array( 'stage' => 'awaiting_contact_call' ) );
            return array(
                'reply'  => 'Great — just need a couple quick details so our team can reach you right away.',
                'fields' => array( 'type' => 'contact_form', 'collect' => array( 'name', 'phone' ) ),
            );
        }
        if ( $step === 'call_now_schedule' ) {
            sp_lifeward_save_session( $session, array( 'stage' => 'awaiting_schedule' ) );
            return array(
                'reply'  => "No problem at all — let's find a time that works for you. We're available Monday through Friday, 9am to 5pm Eastern.",
                'fields' => array( 'type' => 'schedule_form', 'slots' => sp_lifeward_generate_slots() ),
            );
        }
    }

    if ( $stage === 'awaiting_contact_call' && $step === 'submit_call_now' ) {
        if ( $in['name'] === '' || $in['phone'] === '' ) {
            return array(
                'reply'  => 'Just need both your name and a phone number so our team can reach you.',
                'fields' => array( 'type' => 'contact_form', 'collect' => array( 'name', 'phone' ) ),
            );
        }
        $contact_id = sp_lifeward_find_or_create_contact( $in['name'], $in['email'], $in['phone'] );
        $lead_id    = sp_lifeward_upsert_lead( $session, $contact_id, 'hot', 90, 'Requested an immediate call from the landing page.' );
        SP_Lifeward_Salesforce_Client::sync_lead( $lead_id, $in, 'hot' );
        sp_lifeward_notify_call_center( $lead_id, $in );
        sp_lifeward_save_session( $session, array(
            'stage' => 'done', 'lead_id' => $lead_id, 'contact_id' => $contact_id,
            'contact_name' => $in['name'], 'contact_email' => $in['email'], 'contact_phone' => $in['phone'],
        ) );
        return array( 'reply' => "Perfect, {$in['name']} — I've let our team know. Someone will be calling you at {$in['phone']} shortly. Thank you for reaching out!", 'done' => true );
    }

    if ( $stage === 'awaiting_schedule' && $step === 'submit_schedule' ) {
        if ( $in['name'] === '' || $in['phone'] === '' || ! sp_lifeward_slot_is_valid( $in['slot'] ) ) {
            return array(
                'reply'  => $in['slot'] !== '' && ! sp_lifeward_slot_is_valid( $in['slot'] )
                    ? "That time doesn't look available anymore — please pick another."
                    : 'Just need your name, phone number, and a time that works.',
                'fields' => array( 'type' => 'schedule_form', 'slots' => sp_lifeward_generate_slots() ),
            );
        }
        $contact_id = sp_lifeward_find_or_create_contact( $in['name'], $in['email'], $in['phone'] );
        $lead_id    = sp_lifeward_upsert_lead( $session, $contact_id, 'scheduled', 80, 'Requested a callback for ' . $in['slot'] . '.' );
        SP_Lifeward_Salesforce_Client::sync_lead( $lead_id, $in, 'scheduled', array( 'slot' => $in['slot'] ) );
        sp_lifeward_create_callback_task( $lead_id, $in );
        sp_lifeward_save_session( $session, array(
            'stage' => 'done', 'lead_id' => $lead_id, 'contact_id' => $contact_id,
            'contact_name' => $in['name'], 'contact_email' => $in['email'], 'contact_phone' => $in['phone'],
            'slot_choice' => sp_lifeward_slot_to_mysql( $in['slot'] ),
        ) );
        $label = sp_lifeward_format_slot_label( $in['slot'] );
        return array( 'reply' => "Wonderful — I have you down for {$label}. We'll call {$in['phone']} then. Talk soon, {$in['name']}!", 'done' => true );
    }

    if ( $stage === 'awaiting_email' && $step === 'submit_email' ) {
        if ( ! is_email( $in['email'] ) ) {
            return array( 'reply' => "Hmm, that email doesn't look quite right — could you double check it for me?", 'fields' => array( 'type' => 'email_form' ) );
        }
        $contact_id = sp_lifeward_find_or_create_contact( $in['name'], $in['email'], '' );
        $lead_id    = sp_lifeward_upsert_lead( $session, $contact_id, 'info-requested', 50, 'Requested the info package by email.' );
        SP_Lifeward_Salesforce_Client::sync_lead( $lead_id, $in, 'info-requested' );
        sp_lifeward_send_info_package( $lead_id, $in['email'] );
        sp_lifeward_save_session( $session, array( 'stage' => 'done', 'lead_id' => $lead_id, 'contact_id' => $contact_id, 'contact_email' => $in['email'] ) );
        return array( 'reply' => 'All set — check your inbox! If anything comes up in the meantime, we\'re here.', 'done' => true );
    }

    // Stage/step mismatch (stale tab, double-submit, back button) — re-serve the current prompt.
    return sp_lifeward_current_prompt( $session );
}

function sp_lifeward_current_prompt( $session ) {
    $prompts = array(
        'greeting' => array( 'reply' => sp_lifeward_greeting_text(), 'buttons' => sp_lifeward_main_buttons() ),
        'call_now_when' => array( 'reply' => 'Would you like to talk right now, or schedule a callback?', 'buttons' => array(
            array( 'id' => 'call_now_immediate', 'label' => 'Talk right now' ),
            array( 'id' => 'call_now_schedule',  'label' => 'Schedule a callback' ),
        ) ),
        'awaiting_contact_call' => array( 'reply' => 'Just need your name and phone number.', 'fields' => array( 'type' => 'contact_form', 'collect' => array( 'name', 'phone' ) ) ),
        'awaiting_schedule'     => array( 'reply' => 'Pick a time that works for you.', 'fields' => array( 'type' => 'schedule_form', 'slots' => sp_lifeward_generate_slots() ) ),
        'awaiting_email'        => array( 'reply' => "What's the best email to reach you at?", 'fields' => array( 'type' => 'email_form' ) ),
        'done'                  => array( 'reply' => "We're all set — thanks again!", 'done' => true ),
    );
    return isset( $prompts[ $session->stage ] ) ? $prompts[ $session->stage ] : $prompts['greeting'];
}

function sp_lifeward_greeting_text() {
    $g = (string) get_option( 'sp_lifeward_greeting', '' );
    return $g !== '' ? $g : "Hi, I'm Rachel, your Lifeward assistant — here to help you understand your options with the same warmth and care you'd expect from someone who's spent years in patient care. What can I help you with today?";
}

function sp_lifeward_main_buttons() {
    return array(
        array( 'id' => 'call_now',       'label' => "Yes, I'm interested. Please call now." ),
        array( 'id' => 'send_info',      'label' => 'Yes, send me more information.' ),
        array( 'id' => 'not_interested', 'label' => "No, I'm not interested." ),
    );
}

// ── Session storage ──────────────────────────────────────────────────────────

function sp_lifeward_get_or_create_session( $token, $ip_hash ) {
    global $wpdb;
    $t = $wpdb->prefix . 'sp_lifeward_sessions';

    if ( $token !== '' ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE token = %s LIMIT 1", $token ) );
        if ( $row ) return $row;
    }

    $token = wp_generate_password( 40, false );
    $now   = current_time( 'mysql' );
    $wpdb->insert( $t, array(
        'token' => $token, 'stage' => 'greeting', 'ip_hash' => $ip_hash,
        'created_at' => $now, 'updated_at' => $now,
    ) );
    return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE id = %d", $wpdb->insert_id ) );
}

function sp_lifeward_save_session( $session, $fields ) {
    global $wpdb;
    $fields['updated_at'] = current_time( 'mysql' );
    $wpdb->update( $wpdb->prefix . 'sp_lifeward_sessions', $fields, array( 'id' => $session->id ) );
    foreach ( $fields as $k => $v ) $session->$k = $v;
    return $session;
}

// ── Core System writes (contacts / leads / tasks / notes / activity) ─────────

function sp_lifeward_find_or_create_contact( $name, $email, $phone ) {
    global $wpdb;
    $t = $wpdb->prefix . 'sp_contacts';
    $existing = null;
    if ( $email !== '' ) $existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE email = %s ORDER BY id DESC LIMIT 1", $email ) );
    if ( ! $existing && $phone !== '' ) $existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE phone = %s ORDER BY id DESC LIMIT 1", $phone ) );

    if ( $existing ) {
        $update = array();
        if ( $existing->email === '' && $email !== '' ) $update['email'] = $email;
        if ( $existing->phone === '' && $phone !== '' ) $update['phone'] = $phone;
        if ( $update ) $wpdb->update( $t, $update, array( 'id' => $existing->id ) );
        return (int) $existing->id;
    }

    $name  = trim( $name );
    $parts = $name !== '' ? explode( ' ', $name, 2 ) : array( 'Website', 'Visitor' );
    $wpdb->insert( $t, array(
        'first_name'  => isset( $parts[0] ) && $parts[0] !== '' ? $parts[0] : 'Website',
        'last_name'   => isset( $parts[1] ) ? $parts[1] : 'Visitor',
        'email'       => $email,
        'phone'       => $phone,
        'company_id'  => 0,
        'source'      => 'lifeward-landing',
        'status'      => 'active',
        'notes'       => '',
        'created_at'  => current_time( 'mysql' ),
    ) );
    return (int) $wpdb->insert_id;
}

function sp_lifeward_score_defaults() {
    return array( 'hot' => 90, 'scheduled' => 80, 'info-requested' => 50, 'lost' => 0 );
}

function sp_lifeward_upsert_lead( $session, $contact_id, $status, $score, $note ) {
    global $wpdb;
    $t = $wpdb->prefix . 'sp_leads';

    if ( (int) $session->lead_id > 0 ) {
        $wpdb->update( $t, array( 'status' => $status, 'score' => (int) $score, 'notes' => $note ), array( 'id' => $session->lead_id ) );
        $lead_id = (int) $session->lead_id;
    } else {
        $wpdb->insert( $t, array(
            'contact_id' => $contact_id, 'company_id' => 0, 'source' => 'lifeward-landing',
            'status' => $status, 'score' => (int) $score, 'notes' => $note, 'created_at' => current_time( 'mysql' ),
        ) );
        $lead_id = (int) $wpdb->insert_id;
    }

    $wpdb->insert( $wpdb->prefix . 'sp_activity', array(
        'record_type' => 'lead', 'record_id' => $lead_id, 'action' => 'lifeward_' . $status,
        'detail' => $note, 'created_by' => 0, 'created_at' => current_time( 'mysql' ),
    ) );

    return $lead_id;
}

function sp_lifeward_create_callback_task( $lead_id, $in ) {
    global $wpdb;
    $dt = sp_lifeward_slot_datetime( $in['slot'] );
    if ( ! $dt ) return;

    $wpdb->insert( $wpdb->prefix . 'sp_tasks', array(
        'record_type' => 'lead', 'record_id' => $lead_id,
        'title'       => 'Callback: ' . ( $in['name'] !== '' ? $in['name'] : 'Lifeward lead' ) . ' (' . $in['phone'] . ')',
        'assigned_to' => 0, 'due_date' => $dt->format( 'Y-m-d' ), 'status' => 'open',
        'created_by'  => 0, 'created_at' => current_time( 'mysql' ),
    ) );

    $wpdb->insert( $wpdb->prefix . 'sp_notes', array(
        'record_type' => 'lead', 'record_id' => $lead_id,
        'content'     => 'Callback requested for ' . sp_lifeward_format_slot_label( $in['slot'] ) . '.',
        'reminder_at' => $dt->setTimezone( wp_timezone() )->format( 'Y-m-d H:i:s' ),
        'reminder_sent' => 0, 'created_by' => 0, 'created_at' => current_time( 'mysql' ),
    ) );
}

function sp_lifeward_send_info_package( $lead_id, $email ) {
    $subject = (string) get_option( 'sp_lifeward_info_subject', 'Your Lifeward information package' );
    $body    = (string) get_option( 'sp_lifeward_info_body', "Thanks for your interest in Lifeward!\n\nWe've attached our information package. If you have any questions, just reply to this email.\n\n— The Lifeward Team" );
    $sent    = wp_mail( $email, $subject, $body );

    global $wpdb;
    $wpdb->insert( $wpdb->prefix . 'sp_email_log', array(
        'record_type' => 'lead', 'record_id' => $lead_id, 'direction' => 'sent',
        'subject' => $subject, 'body' => $body, 'logged_by' => 0, 'logged_at' => current_time( 'mysql' ),
    ) );
    return $sent;
}

// ── Slot generation (Mon–Fri, 9am–5pm Eastern) ────────────────────────────────

function sp_lifeward_business_hours() {
    return range( 9, 16 ); // hour a callback slot can *start* at; last slot 4–5pm
}

function sp_lifeward_generate_slots( $count = 12 ) {
    $tz  = new DateTimeZone( 'America/New_York' );
    $now = new DateTime( 'now', $tz );
    $slots = array();
    $hours = sp_lifeward_business_hours();

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
                    $slots[] = array( 'value' => $slot->format( DATE_ATOM ), 'label' => sp_lifeward_format_slot_label( $slot->format( DATE_ATOM ) ) );
                    if ( count( $slots ) >= $count ) break;
                }
            }
        }
        $cursor->modify( '+1 day' );
        $days_checked++;
    }
    return $slots;
}

function sp_lifeward_slot_datetime( $iso ) {
    if ( $iso === '' ) return null;
    try {
        $dt = new DateTime( $iso );
        $dt->setTimezone( new DateTimeZone( 'America/New_York' ) );
        return $dt;
    } catch ( Exception $e ) {
        return null;
    }
}

function sp_lifeward_slot_is_valid( $iso ) {
    $dt = sp_lifeward_slot_datetime( $iso );
    if ( ! $dt ) return false;
    $now = new DateTime( 'now', new DateTimeZone( 'America/New_York' ) );
    if ( $dt <= $now ) return false;
    $dow = (int) $dt->format( 'N' );
    if ( $dow < 1 || $dow > 5 ) return false;
    if ( (int) $dt->format( 'i' ) !== 0 ) return false;
    return in_array( (int) $dt->format( 'G' ), sp_lifeward_business_hours(), true );
}

function sp_lifeward_slot_to_mysql( $iso ) {
    $dt = sp_lifeward_slot_datetime( $iso );
    return $dt ? $dt->format( 'Y-m-d H:i:s' ) : null;
}

function sp_lifeward_format_slot_label( $iso ) {
    $dt = sp_lifeward_slot_datetime( $iso );
    return $dt ? $dt->format( 'D, M j \a\t g:i A' ) . ' ET' : $iso;
}

// ── Rate limiting ────────────────────────────────────────────────────────────

function sp_lifeward_client_ip() {
    return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

function sp_lifeward_hash_ip( $ip ) {
    return hash( 'sha256', $ip . wp_salt() );
}

function sp_lifeward_rate_limited( $ip_hash ) {
    $key = 'sp_lifeward_rl_' . $ip_hash;
    $n   = (int) get_transient( $key ) + 1;
    set_transient( $key, $n, HOUR_IN_SECONDS );
    return $n > 60;
}

function sp_lifeward_terminal_limited( $ip_hash ) {
    $key = 'sp_lifeward_term_' . $ip_hash;
    $n   = (int) get_transient( $key ) + 1;
    set_transient( $key, $n, DAY_IN_SECONDS );
    return $n > 5;
}

// ── Response helpers ─────────────────────────────────────────────────────────

function sp_lifeward_ok( $data ) {
    return new WP_REST_Response( array_merge( array( 'ok' => true ), $data ), 200 );
}

function sp_lifeward_err( $code, $message, $status ) {
    return new WP_REST_Response( array( 'ok' => false, 'code' => $code, 'message' => $message ), $status );
}
