<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function aichat_parse_lead( $reply ) {
    if ( preg_match( '/\[LEAD_CAPTURED:name=([^,\]]+),email=([^\]]+)\]/i', $reply, $m ) ) {
        return [
            'name'  => sanitize_text_field( trim( $m[1] ) ),
            'email' => sanitize_email( trim( $m[2] ) ),
        ];
    }
    return null;
}

function aichat_strip_lead_tag( $reply ) {
    return trim( preg_replace( '/\[LEAD_CAPTURED:[^\]]+\]/i', '', $reply ) );
}

function aichat_notify_lead( $lead, $messages ) {
    if ( empty( $lead['email'] ) ) return;

    $notify  = get_option( 'aichat_notify_email', get_option( 'admin_email' ) );
    $biz     = get_option( 'aichat_business_name', get_bloginfo( 'name' ) );
    $primary = get_option( 'aichat_color_primary', '#6559b1' );
    $name    = esc_html( $lead['name'] );
    $email   = esc_html( $lead['email'] );
    $time    = current_time( 'F j, Y \a\t g:i a' );

    $convo_html = '';
    foreach ( $messages as $msg ) {
        if ( is_array( $msg['content'] ) ) continue; // skip vision blocks
        $role    = $msg['role'] === 'user' ? 'Customer' : 'Bot';
        $bg      = $msg['role'] === 'user' ? '#f0eeff' : '#f6f7f4';
        $align   = $msg['role'] === 'user' ? 'right' : 'left';
        $color   = $msg['role'] === 'user' ? $primary : '#2f7d22';
        $content = nl2br( esc_html( $msg['content'] ) );

        $convo_html .= '
        <div style="margin-bottom:12px;text-align:' . $align . ';">
            <div style="display:inline-block;max-width:80%;background:' . $bg . ';border-radius:10px;padding:10px 13px;text-align:left;">
                <div style="font-size:10px;font-weight:700;color:' . $color . ';text-transform:uppercase;margin-bottom:4px;">' . $role . '</div>
                <div style="font-size:13px;color:#1f2a1f;line-height:1.5;">' . $content . '</div>
            </div>
        </div>';
    }

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">
    <tr>
        <td style="background:' . $primary . ';padding:28px 32px;text-align:center;">
            <div style="font-size:22px;font-weight:700;color:#fff;">New Lead — ' . esc_html( $biz ) . '</div>
            <div style="font-size:13px;color:rgba(255,255,255,.8);margin-top:6px;">' . $time . '</div>
        </td>
    </tr>
    <tr>
        <td style="padding:28px 32px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9ff;border-radius:8px;border:1px solid #e8e5ff;">
                <tr><td style="padding:20px 24px;">
                    <div style="font-size:11px;font-weight:700;color:' . $primary . ';text-transform:uppercase;margin-bottom:14px;">Lead Details</div>
                    <table><tr>
                        <td style="font-size:13px;color:#666;width:80px;padding-bottom:8px;">Name</td>
                        <td style="font-size:14px;font-weight:700;color:#1f2a1f;padding-bottom:8px;">' . $name . '</td>
                    </tr><tr>
                        <td style="font-size:13px;color:#666;">Email</td>
                        <td style="font-size:14px;color:#1f2a1f;"><a href="mailto:' . $email . '" style="color:' . $primary . ';">' . $email . '</a></td>
                    </tr></table>
                </td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:20px 32px;">
            <table width="100%"><tr><td align="center">
                <a href="mailto:' . $email . '" style="display:inline-block;background:' . $primary . ';color:#fff;font-size:14px;font-weight:700;padding:12px 28px;border-radius:6px;text-decoration:none;">Reply to ' . $name . '</a>
            </td></tr></table>
        </td>
    </tr>
    <tr>
        <td style="padding:0 32px 28px;">
            <div style="font-size:11px;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px;border-top:1px solid #eee;padding-top:20px;">Full Conversation</div>
            ' . $convo_html . '
        </td>
    </tr>
    <tr>
        <td style="background:#f8f9f6;padding:16px 32px;text-align:center;border-top:1px solid #eee;">
            <div style="font-size:11px;color:#999;">' . esc_html( $biz ) . ' AI Chat</div>
        </td>
    </tr>
</table>
</td></tr></table></body></html>';

    wp_mail(
        $notify,
        'New Lead: ' . $lead['name'],
        $html,
        [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $biz . ' Chat <' . get_option( 'admin_email' ) . '>',
        ]
    );
}
