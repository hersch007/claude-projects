<?php
/**
 * The whole guided flow, as a stage/step state machine. Mirrors the logic from
 * the WordPress version's includes/rest-endpoints.php one-for-one, just backed
 * by SQLite (inc/db.php) instead of WordPress's sp_contacts/sp_leads tables.
 */

function lw_main_buttons() {
    return array(
        array( 'id' => 'call_now',       'label' => 'Please call now' ),
        array( 'id' => 'schedule_call',  'label' => 'Schedule a Call' ),
        array( 'id' => 'send_info',      'label' => "I'm still hesitating, send more info" ),
    );
}

function lw_greeting_text() {
    $settings = lw_get_settings();
    return $settings['greeting'] !== '' ? $settings['greeting'] : lw_settings_defaults()['greeting'];
}

/** Whitelist of products the info-request checkboxes can reference — the server
 *  only ever trusts these ids, never arbitrary client-submitted product text. */
function lw_product_catalog() {
    return array(
        'rewalk'  => array( 'label' => 'ReWalk Personal Exoskeleton', 'url' => 'https://golifeward.com/products/rewalkpersonal-exoskeleton/' ),
        'alterg'  => array( 'label' => 'AlterG Anti-Gravity Systems', 'url' => 'https://golifeward.com/products/alterg-anti-gravity-systems/' ),
        'restore' => array( 'label' => 'ReStore Exo-Suit', 'url' => 'https://golifeward.com/products/restore-exo-suit/' ),
        'myolyn'  => array( 'label' => 'MYOLYN FES Cycling', 'url' => 'https://golifeward.com/products/myolyn-fes-cycling/' ),
    );
}

function lw_product_options() {
    $out = array();
    foreach ( lw_product_catalog() as $id => $p ) {
        $out[] = array( 'id' => $id, 'label' => $p['label'], 'url' => $p['url'] );
    }
    return $out;
}

/** Filters a client-submitted product id list down to known ids, and returns
 *  their display labels for use in notes/email/Salesforce sync. */
function lw_product_labels( $ids ) {
    $catalog = lw_product_catalog();
    $labels  = array();
    foreach ( (array) $ids as $id ) {
        $id = is_string( $id ) ? $id : '';
        if ( isset( $catalog[ $id ] ) ) $labels[] = $catalog[ $id ]['label'];
    }
    return $labels;
}

/** Scans free-form text (e.g. an AI chat transcript) for mentions of a real
 *  product by name and returns the matched catalog ids, in catalog order.
 *  Lets chat-captured leads record product interest the same way the
 *  structured info-request form does, instead of losing it entirely. */
function lw_detect_product_ids_in_text( $text ) {
    $needles = array(
        'rewalk'  => 'rewalk',
        'alterg'  => 'alterg',
        'restore' => 'restore',
        'myolyn'  => 'myolyn',
    );
    $lower = strtolower( (string) $text );
    $found = array();
    foreach ( $needles as $id => $needle ) {
        if ( strpos( $lower, $needle ) !== false ) $found[] = $id;
    }
    return $found;
}

// ── Session storage ──────────────────────────────────────────────────────────

function lw_get_or_create_session( $token, $ip_hash ) {
    $pdo = lw_db();
    if ( $token !== '' ) {
        $stmt = $pdo->prepare( 'SELECT * FROM sessions WHERE token = ? LIMIT 1' );
        $stmt->execute( array( $token ) );
        $row = $stmt->fetch( PDO::FETCH_ASSOC );
        if ( $row ) return $row;
    }

    $token = bin2hex( random_bytes( 20 ) );
    $now   = lw_now();
    $pdo->prepare( 'INSERT INTO sessions (token, stage, ip_hash, created_at, updated_at) VALUES (?, ?, ?, ?, ?)' )
        ->execute( array( $token, 'greeting', $ip_hash, $now, $now ) );

    $stmt = $pdo->prepare( 'SELECT * FROM sessions WHERE id = ?' );
    $stmt->execute( array( $pdo->lastInsertId() ) );
    return $stmt->fetch( PDO::FETCH_ASSOC );
}

function lw_save_session( $session, $fields ) {
    $fields['updated_at'] = lw_now();
    $sets = array(); $args = array();
    foreach ( $fields as $k => $v ) { $sets[] = "$k = ?"; $args[] = $v; }
    $args[] = $session['id'];
    lw_db()->prepare( 'UPDATE sessions SET ' . implode( ', ', $sets ) . ' WHERE id = ?' )->execute( $args );
    return array_merge( $session, $fields );
}

// ── Lead storage ─────────────────────────────────────────────────────────────

function lw_upsert_lead( $session, $contact, $status, $score, $note, $slot_utc = null ) {
    $pdo = lw_db();
    $now = lw_now();

    if ( (int) $session['lead_id'] > 0 ) {
        $pdo->prepare( 'UPDATE leads SET name=?, email=?, phone=?, status=?, score=?, notes=?, slot_choice_utc=?, updated_at=? WHERE id=?' )
            ->execute( array( $contact['name'], $contact['email'], $contact['phone'], $status, $score, $note, $slot_utc, $now, $session['lead_id'] ) );
        $lead_id = (int) $session['lead_id'];
    } else {
        $pdo->prepare( 'INSERT INTO leads (name, email, phone, status, score, notes, slot_choice_utc, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?)' )
            ->execute( array( $contact['name'], $contact['email'], $contact['phone'], $status, $score, $note, $slot_utc, $now, $now ) );
        $lead_id = (int) $pdo->lastInsertId();
    }

    lw_log_activity( $lead_id, 'lifeward_' . $status, $note );
    return $lead_id;
}

// ── State machine ────────────────────────────────────────────────────────────

function lw_call_now_when_buttons() {
    return array(
        array( 'id' => 'call_now_immediate', 'label' => 'Talk right now' ),
        array( 'id' => 'call_now_schedule',  'label' => 'Schedule a callback' ),
        array( 'id' => 'call_now_specific',  'label' => 'Call me back at a specific time' ),
    );
}

function lw_transition( $session, $step, $in ) {
    $stage = $session['stage'];

    // The 3 main buttons only ever come from a freshly loaded page, which
    // always renders them regardless of what stage a stale session token is
    // sitting in (abandoned mid-flow, or already 'done'). Treat them as a
    // restart into that specific path rather than falling through to
    // whatever stage the old session was stuck on.
    if ( $stage !== 'greeting' && in_array( $step, array( 'call_now', 'schedule_call', 'send_info' ), true ) ) {
        $session = lw_save_session( $session, array( 'stage' => 'greeting' ) );
        $stage   = 'greeting';
    }

    if ( $stage === 'greeting' ) {
        if ( $step === 'call_now' ) {
            $session = lw_save_session( $session, array( 'stage' => 'call_now_when' ) );
            return array(
                'reply'   => "Of course — I'd love to get you connected. Would you like to talk right now, schedule a callback, or pick a specific day and time?",
                'buttons' => lw_call_now_when_buttons(),
            );
        }
        if ( $step === 'schedule_call' ) {
            lw_save_session( $session, array( 'stage' => 'awaiting_specific_time' ) );
            return array( 'reply' => "Great — let's find a day and time that works. We're available Monday through Friday, 9am to 5pm Eastern.", 'fields' => array( 'type' => 'specific_time_form' ) );
        }
        if ( $step === 'send_info' ) {
            lw_save_session( $session, array( 'stage' => 'awaiting_info' ) );
            return array(
                'reply'  => "Happy to send that your way. What's your name, the best email to reach you at, and which products are you curious about?",
                'fields' => array( 'type' => 'info_form', 'products' => lw_product_options() ),
            );
        }
    }

    if ( $stage === 'call_now_when' ) {
        if ( $step === 'call_now_immediate' ) {
            lw_save_session( $session, array( 'stage' => 'awaiting_contact_call' ) );
            return array( 'reply' => 'Great — just need a couple quick details so our team can reach you right away.', 'fields' => array( 'type' => 'contact_form' ) );
        }
        if ( $step === 'call_now_schedule' ) {
            lw_save_session( $session, array( 'stage' => 'awaiting_schedule' ) );
            return array( 'reply' => "No problem at all — let's find a time that works for you. We're available Monday through Friday, 9am to 5pm Eastern.", 'fields' => array( 'type' => 'schedule_form', 'slots' => lw_generate_slots() ) );
        }
        if ( $step === 'call_now_specific' ) {
            lw_save_session( $session, array( 'stage' => 'awaiting_specific_time' ) );
            return array( 'reply' => "Sure thing — pick whatever day and time works best. We're available Monday through Friday, 9am to 5pm Eastern.", 'fields' => array( 'type' => 'specific_time_form' ) );
        }
    }

    if ( $stage === 'awaiting_contact_call' && $step === 'submit_call_now' ) {
        if ( $in['name'] === '' || $in['phone'] === '' ) {
            return array( 'reply' => 'Just need both your name and a phone number so our team can reach you.', 'fields' => array( 'type' => 'contact_form' ) );
        }
        $note = 'Requested an immediate call from the landing page.';
        if ( $in['message'] !== '' ) $note .= ' Note for the rep: ' . $in['message'];
        $lead_id = lw_upsert_lead( $session, $in, 'hot', 90, $note );
        lw_salesforce_sync_lead( $lead_id, $in, 'hot', array( 'message' => $in['message'] ) );
        lw_notify_call_center( $lead_id, $in );
        lw_save_session( $session, array( 'stage' => 'done', 'lead_id' => $lead_id, 'name' => $in['name'], 'email' => $in['email'], 'phone' => $in['phone'] ) );
        $when = lw_is_business_hours_now()
            ? "Someone will be calling you at {$in['phone']} within about 15 minutes."
            : "We're outside our call hours right now (Mon–Fri, 9am–5pm Eastern), so someone will call you at {$in['phone']} the next business day.";
        return array( 'reply' => "Perfect, {$in['name']} — I've let our team know. $when Thank you for reaching out!", 'done' => true );
    }

    if ( $stage === 'awaiting_schedule' && $step === 'submit_schedule' ) {
        if ( $in['name'] === '' || $in['phone'] === '' || ! lw_slot_is_valid( $in['slot'] ) ) {
            return array(
                'reply'  => ( $in['slot'] !== '' && ! lw_slot_is_valid( $in['slot'] ) ) ? "That time doesn't look available anymore — please pick another." : 'Just need your name, phone number, and a time that works.',
                'fields' => array( 'type' => 'schedule_form', 'slots' => lw_generate_slots() ),
            );
        }
        $lead_id = lw_upsert_lead( $session, $in, 'scheduled', 80, 'Requested a callback for ' . $in['slot'] . '.', lw_slot_to_utc( $in['slot'] ) );
        lw_salesforce_sync_lead( $lead_id, $in, 'scheduled', array( 'slot' => $in['slot'] ) );
        lw_save_session( $session, array( 'stage' => 'done', 'lead_id' => $lead_id, 'name' => $in['name'], 'email' => $in['email'], 'phone' => $in['phone'] ) );
        $label = lw_format_slot_label( $in['slot'] );
        return array( 'reply' => "Wonderful — I have you down for {$label}. We'll call {$in['phone']} then. Talk soon, {$in['name']}!", 'done' => true );
    }

    if ( $stage === 'awaiting_specific_time' && $step === 'submit_specific_time' ) {
        $iso = lw_specific_slot_iso( $in['day'], $in['time'] );
        if ( $in['name'] === '' || $in['phone'] === '' || ! lw_specific_time_is_valid( $iso ) ) {
            return array(
                'reply'  => ( $in['day'] !== '' && $in['time'] !== '' && ! lw_specific_time_is_valid( $iso ) )
                    ? "That falls outside Monday–Friday, 9am–5pm Eastern — please pick another day and time."
                    : 'Just need your name, phone number, and a day and time that works.',
                'fields' => array( 'type' => 'specific_time_form' ),
            );
        }
        $note = 'Requested a callback for ' . $iso . '.';
        if ( $in['message'] !== '' ) $note .= ' Note for the rep: ' . $in['message'];
        $lead_id = lw_upsert_lead( $session, $in, 'scheduled', 80, $note, lw_slot_to_utc( $iso ) );
        lw_salesforce_sync_lead( $lead_id, $in, 'scheduled', array( 'slot' => $iso, 'message' => $in['message'] ) );
        lw_save_session( $session, array( 'stage' => 'done', 'lead_id' => $lead_id, 'name' => $in['name'], 'email' => $in['email'], 'phone' => $in['phone'] ) );
        $label = lw_format_slot_label( $iso );
        return array( 'reply' => "Wonderful — I have you down for {$label}. We'll call {$in['phone']} then. Talk soon, {$in['name']}!", 'done' => true );
    }

    if ( $stage === 'awaiting_info' && $step === 'submit_info_request' ) {
        if ( $in['name'] === '' || ! filter_var( $in['email'], FILTER_VALIDATE_EMAIL ) ) {
            return array( 'reply' => "Just need your name and a valid email — could you double check those for me?", 'fields' => array( 'type' => 'info_form', 'products' => lw_product_options() ) );
        }
        $labels = lw_product_labels( $in['products'] );
        $note   = $labels ? ( 'Requested info on: ' . implode( ', ', $labels ) . '.' ) : 'Requested the info package (no specific product selected).';
        $lead_id = lw_upsert_lead( $session, $in, 'info-requested', 50, $note );
        lw_salesforce_sync_lead( $lead_id, $in, 'info-requested', array( 'products' => $labels ) );
        lw_send_info_package( $lead_id, $in['email'], $labels );
        lw_save_session( $session, array( 'stage' => 'done', 'lead_id' => $lead_id, 'name' => $in['name'], 'email' => $in['email'] ) );
        return array( 'reply' => "All set, {$in['name']} — check your inbox! If anything comes up in the meantime, we're here.", 'done' => true );
    }

    return lw_current_prompt( $session );
}

function lw_current_prompt( $session ) {
    $prompts = array(
        'greeting'              => array( 'reply' => lw_greeting_text(), 'buttons' => lw_main_buttons() ),
        'call_now_when'         => array( 'reply' => 'Would you like to talk right now, schedule a callback, or pick a specific day and time?', 'buttons' => lw_call_now_when_buttons() ),
        'awaiting_contact_call' => array( 'reply' => 'Just need your name and phone number.', 'fields' => array( 'type' => 'contact_form' ) ),
        'awaiting_schedule'     => array( 'reply' => 'Pick a time that works for you.', 'fields' => array( 'type' => 'schedule_form', 'slots' => lw_generate_slots() ) ),
        'awaiting_specific_time'=> array( 'reply' => 'Pick a day and time that works for you.', 'fields' => array( 'type' => 'specific_time_form' ) ),
        'awaiting_info'         => array( 'reply' => "What's your name, the best email to reach you at, and which products are you curious about?", 'fields' => array( 'type' => 'info_form', 'products' => lw_product_options() ) ),
        'done'                  => array( 'reply' => "We're all set — thanks again!", 'done' => true ),
    );
    return isset( $prompts[ $session['stage'] ] ) ? $prompts[ $session['stage'] ] : $prompts['greeting'];
}

function lw_send_info_package( $lead_id, $email, $product_labels = array() ) {
    $settings = lw_get_settings();
    $body = $settings['info_body'];
    if ( $product_labels ) {
        $body .= "\n\nYou asked about: " . implode( ', ', $product_labels ) . '.';
    }
    $sent = mail( $email, $settings['info_subject'], $body );
    lw_log_activity( $lead_id, 'info_email_sent', array( 'to' => $email, 'products' => $product_labels, 'ok' => (bool) $sent ) );
    return $sent;
}
