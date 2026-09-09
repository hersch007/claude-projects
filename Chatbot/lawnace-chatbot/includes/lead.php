<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clean one field from the [LEAD_CAPTURED:...] tag. Drops the placeholder words the model
 * sometimes leaves when the customer did not provide a value.
 */
function lawnace_lead_clean_value( $key, $value ) {
    $value = trim( (string) $value );
    if ( $value === '' || preg_match( '/^(NAME|EMAIL|PHONE|ADDRESS|STREET ADDRESS.*|n\/a|na|none|not provided|not given|declined|unknown|skip|-+)$/i', $value ) ) {
        return '';
    }
    switch ( $key ) {
        case 'email':
            return sanitize_email( $value );
        case 'phone':
            if ( strlen( preg_replace( '/\D+/', '', $value ) ) < 7 ) {
                return '';
            }
            return sanitize_text_field( $value );
        default:
            return sanitize_text_field( $value );
    }
}

/**
 * Parse the lead tag out of a bot reply.
 * Format: [LEAD_CAPTURED:name=NAME,email=EMAIL,phone=PHONE,address=STREET, CITY, ST ZIP]
 * Address is last so commas inside it are safe. Older two-field tags still parse.
 */
function lawnace_chatbot_parse_lead( $reply ) {
    if ( ! preg_match( '/\[LEAD_CAPTURED:([^\]]*)\]/i', $reply, $m ) ) {
        return null;
    }
    $lead  = array( 'name' => '', 'email' => '', 'phone' => '', 'address' => '' );
    $parts = preg_split( '/,\s*(?=(?:name|email|phone|address)\s*=)/i', $m[1] );
    foreach ( $parts as $part ) {
        if ( strpos( $part, '=' ) === false ) {
            continue;
        }
        list( $key, $value ) = explode( '=', $part, 2 );
        $key = strtolower( trim( $key ) );
        if ( array_key_exists( $key, $lead ) ) {
            $lead[ $key ] = lawnace_lead_clean_value( $key, $value );
        }
    }
    if ( $lead['name'] === '' && $lead['email'] === '' ) {
        return null;
    }
    return $lead;
}

function lawnace_chatbot_strip_lead_tag( $reply ) {
    return trim( preg_replace( '/\[LEAD_CAPTURED:[^\]]*\]/i', '', $reply ) );
}

/** "Name: X | Email: Y | Phone: Z | Address: A" — the text stored in the chat log's lead row. */
function lawnace_lead_row_text( $lead ) {
    $out = array();
    foreach ( array( 'name' => 'Name', 'email' => 'Email', 'phone' => 'Phone', 'address' => 'Address' ) as $key => $label ) {
        if ( ! empty( $lead[ $key ] ) ) {
            $out[] = $label . ': ' . $lead[ $key ];
        }
    }
    return implode( ' | ', $out );
}

/** Reverse of lawnace_lead_row_text(). Tolerates the old two-field rows. */
function lawnace_parse_lead_row( $text ) {
    $lead = array( 'name' => '', 'email' => '', 'phone' => '', 'address' => '' );
    foreach ( explode( ' | ', (string) $text ) as $part ) {
        if ( preg_match( '/^(Name|Email|Phone|Address):\s*(.*)$/i', trim( $part ), $m ) ) {
            $lead[ strtolower( $m[1] ) ] = trim( $m[2] );
        }
    }
    return $lead;
}

/** Fill blanks in $existing with values from $new. Returns array( merged, changed ). */
function lawnace_merge_lead( $existing, $new ) {
    $changed = false;
    foreach ( array( 'name', 'email', 'phone', 'address' ) as $key ) {
        if ( empty( $existing[ $key ] ) && ! empty( $new[ $key ] ) ) {
            $existing[ $key ] = $new[ $key ];
            $changed = true;
        }
    }
    return array( $existing, $changed );
}

function lawnace_lead_tel_href( $phone ) {
    $digits = preg_replace( '/\D+/', '', (string) $phone );
    if ( strlen( $digits ) === 10 ) {
        $digits = '1' . $digits;
    }
    return 'tel:+' . $digits;
}

function lawnace_lead_maps_href( $address ) {
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $address );
}

/** Lead Notification Email setting as a clean list (comma / semicolon / space separated). */
function lawnace_notify_recipients() {
    $raw = (string) get_option( 'lawnace_notify_email', get_option( 'admin_email' ) );
    $out = array();
    foreach ( preg_split( '/[\s,;]+/', $raw ) as $addr ) {
        $addr = sanitize_email( $addr );
        if ( $addr !== '' && is_email( $addr ) ) {
            $out[] = $addr;
        }
    }
    if ( empty( $out ) ) {
        $out[] = get_option( 'admin_email' );
    }
    return array_values( array_unique( $out ) );
}

function lawnace_chatbot_notify_lead( $lead, $messages, $is_update = false ) {
    if ( empty( $lead['email'] ) && empty( $lead['name'] ) ) {
        return;
    }

    $notify_email = lawnace_notify_recipients();
    $name         = esc_html( $lead['name'] !== '' ? $lead['name'] : 'Website visitor' );
    $email        = esc_html( $lead['email'] ?? '' );
    $phone        = esc_html( $lead['phone'] ?? '' );
    $address      = esc_html( $lead['address'] ?? '' );
    $subject      = ( $is_update ? 'Updated Lead Info: ' : 'New Lawn Ace Lead: ' ) . ( $lead['name'] !== '' ? $lead['name'] : $lead['email'] );
    $heading      = $is_update ? '🌿 Lead Updated — Lawn Ace' : '🌿 New Lead — Lawn Ace';
    $time         = current_time( 'F j, Y \a\t g:i a' );

    // Lead detail rows (only the fields we actually have)
    $detail_rows = '
                            <tr>
                                <td style="font-size:13px;color:#666;width:80px;padding-bottom:10px;">Name</td>
                                <td style="font-size:14px;font-weight:700;color:#1f2a1f;padding-bottom:10px;">' . $name . '</td>
                            </tr>';
    if ( $phone !== '' ) {
        $detail_rows .= '
                            <tr>
                                <td style="font-size:13px;color:#666;padding-bottom:10px;">Phone</td>
                                <td style="font-size:14px;font-weight:700;color:#1f2a1f;padding-bottom:10px;"><a href="' . esc_attr( lawnace_lead_tel_href( $lead['phone'] ) ) . '" style="color:#6559b1;">' . $phone . '</a></td>
                            </tr>';
    }
    if ( $email !== '' ) {
        $detail_rows .= '
                            <tr>
                                <td style="font-size:13px;color:#666;padding-bottom:10px;">Email</td>
                                <td style="font-size:14px;color:#1f2a1f;padding-bottom:10px;"><a href="mailto:' . $email . '" style="color:#6559b1;">' . $email . '</a></td>
                            </tr>';
    }
    if ( $address !== '' ) {
        $detail_rows .= '
                            <tr>
                                <td style="font-size:13px;color:#666;padding-bottom:10px;">Address</td>
                                <td style="font-size:14px;color:#1f2a1f;padding-bottom:10px;"><a href="' . esc_url( lawnace_lead_maps_href( $lead['address'] ) ) . '" style="color:#6559b1;">' . $address . '</a></td>
                            </tr>';
    }
    $missing = array();
    if ( $phone === '' )   { $missing[] = 'phone'; }
    if ( $address === '' ) { $missing[] = 'street address'; }
    if ( $missing ) {
        $detail_rows .= '
                            <tr>
                                <td colspan="2" style="font-size:12px;color:#b45309;padding-top:4px;">Customer did not provide: ' . esc_html( implode( ' or ', $missing ) ) . '. Check the conversation below.</td>
                            </tr>';
    }

    // Primary action button: call if we have a phone, otherwise email
    if ( $phone !== '' ) {
        $primary_cta = '<a href="' . esc_attr( lawnace_lead_tel_href( $lead['phone'] ) ) . '" style="display:inline-block;background:#a9c33f;color:#fff;font-size:14px;font-weight:700;padding:12px 28px;border-radius:6px;text-decoration:none;">Call ' . $name . '</a>';
    } elseif ( $email !== '' ) {
        $primary_cta = '<a href="mailto:' . $email . '" style="display:inline-block;background:#a9c33f;color:#fff;font-size:14px;font-weight:700;padding:12px 28px;border-radius:6px;text-decoration:none;">Reply to ' . $name . '</a>';
    } else {
        $primary_cta = '';
    }

    // Build conversation HTML
    $convo_html = '';
    foreach ( $messages as $msg ) {
        $role        = $msg['role'] === 'user' ? 'Customer' : 'Ace';
        $bg          = $msg['role'] === 'user' ? '#f0eeff' : '#f6f7f4';
        $align       = $msg['role'] === 'user' ? 'right' : 'left';
        $label_color = $msg['role'] === 'user' ? '#6559b1' : '#2f7d22';
        $content     = nl2br( esc_html( $msg['content'] ) );

        $convo_html .= '
        <div style="margin-bottom:12px;text-align:' . $align . ';">
            <div style="display:inline-block;max-width:80%;background:' . $bg . ';border-radius:10px;padding:10px 13px;text-align:left;">
                <div style="font-size:10px;font-weight:700;color:' . $label_color . ';text-transform:uppercase;margin-bottom:4px;">' . $role . '</div>
                <div style="font-size:13px;color:#1f2a1f;line-height:1.5;">' . $content . '</div>
            </div>
        </div>';
    }

    $html = '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">

    <!-- Header -->
    <tr>
        <td style="background:linear-gradient(135deg,#6559b1,#4a3f99);padding:28px 32px;text-align:center;">
            <div style="font-size:22px;font-weight:700;color:#fff;letter-spacing:-.5px;">' . $heading . '</div>
            <div style="font-size:13px;color:rgba(255,255,255,.8);margin-top:6px;">' . $time . '</div>
        </td>
    </tr>

    <!-- Lead info -->
    <tr>
        <td style="padding:28px 32px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9ff;border-radius:8px;border:1px solid #e8e5ff;">
                <tr>
                    <td style="padding:20px 24px;">
                        <div style="font-size:11px;font-weight:700;color:#6559b1;text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px;">Lead Details</div>
                        <table width="100%" cellpadding="0" cellspacing="0">' . $detail_rows . '
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- CTA -->
    <tr>
        <td style="padding:20px 32px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center">
                        ' . $primary_cta . '
                        &nbsp;&nbsp;
                        <a href="tel:7063642338" style="display:inline-block;background:#6559b1;color:#fff;font-size:14px;font-weight:700;padding:12px 28px;border-radius:6px;text-decoration:none;">Call Office</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Conversation -->
    <tr>
        <td style="padding:0 32px 28px;">
            <div style="font-size:11px;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px;border-top:1px solid #eee;padding-top:20px;">Full Conversation</div>
            ' . $convo_html . '
        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style="background:#f8f9f6;padding:16px 32px;text-align:center;border-top:1px solid #eee;">
            <div style="font-size:11px;color:#999;">Lawn Ace AI Chatbot &mdash; lawnace.com &mdash; 706-364-2338</div>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>';

    wp_mail(
        $notify_email,
        $subject,
        $html,
        array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Lawn Ace Chatbot <' . get_option( 'admin_email' ) . '>',
        )
    );
}
