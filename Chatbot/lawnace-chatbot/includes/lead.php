<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lawnace_chatbot_parse_lead( $reply ) {
    if ( preg_match( '/\[LEAD_CAPTURED:name=([^,\]]+),email=([^\]]+)\]/i', $reply, $m ) ) {
        return array(
            'name'  => sanitize_text_field( trim( $m[1] ) ),
            'email' => sanitize_email( trim( $m[2] ) ),
        );
    }
    return null;
}

function lawnace_chatbot_strip_lead_tag( $reply ) {
    return trim( preg_replace( '/\[LEAD_CAPTURED:[^\]]+\]/i', '', $reply ) );
}

function lawnace_chatbot_notify_lead( $lead, $messages ) {
    if ( empty( $lead['email'] ) ) {
        return;
    }

    $notify_email = get_option( 'lawnace_notify_email', get_option( 'admin_email' ) );
    $name         = esc_html( $lead['name'] );
    $email        = esc_html( $lead['email'] );
    $subject      = 'New Lawn Ace Lead: ' . $lead['name'];
    $time         = current_time( 'F j, Y \a\t g:i a' );

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
            <div style="font-size:22px;font-weight:700;color:#fff;letter-spacing:-.5px;">🌿 New Lead — Lawn Ace</div>
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
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="font-size:13px;color:#666;width:80px;padding-bottom:10px;">Name</td>
                                <td style="font-size:14px;font-weight:700;color:#1f2a1f;padding-bottom:10px;">' . $name . '</td>
                            </tr>
                            <tr>
                                <td style="font-size:13px;color:#666;padding-bottom:10px;">Email</td>
                                <td style="font-size:14px;color:#1f2a1f;padding-bottom:10px;"><a href="mailto:' . $email . '" style="color:#6559b1;">' . $email . '</a></td>
                            </tr>
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
                        <a href="mailto:' . $email . '" style="display:inline-block;background:#a9c33f;color:#fff;font-size:14px;font-weight:700;padding:12px 28px;border-radius:6px;text-decoration:none;">Reply to ' . $name . '</a>
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
