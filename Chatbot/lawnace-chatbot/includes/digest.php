<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
|--------------------------------------------------------------------------
| MORNING DIGEST — daily 8:00 AM email to the Lawn Ace team
|--------------------------------------------------------------------------
| Covers the last 24 hours: new leads (with phone + address), pricing
| questions, service issues, drop-offs, and the open task count.
| Scheduled with WP-Cron in the site's timezone. Settings live on the
| plugin's Settings page (Lawnie → Settings).
*/

define( 'LAWNACE_DIGEST_HOOK', 'lawnace_morning_digest' );
define( 'LAWNACE_DIGEST_HOUR', 8 );

/** Recipient list: the Digest Recipients setting, falling back to the Lead Notification Email. */
function lawnace_digest_recipients() {
    $raw = trim( (string) get_option( 'lawnace_digest_emails', '' ) );
    if ( $raw === '' ) {
        $raw = (string) get_option( 'lawnace_notify_email', get_option( 'admin_email' ) );
    }
    $out = array();
    foreach ( preg_split( '/[\s,;]+/', $raw ) as $addr ) {
        $addr = sanitize_email( $addr );
        if ( $addr !== '' && is_email( $addr ) ) {
            $out[] = $addr;
        }
    }
    return array_values( array_unique( $out ) );
}

/** Unix timestamp of the next 8:00 AM in the site's timezone. */
function lawnace_digest_next_run() {
    $tz  = wp_timezone();
    $now = new DateTime( 'now', $tz );
    $run = new DateTime( 'today ' . LAWNACE_DIGEST_HOUR . ':00', $tz );
    if ( $run <= $now ) {
        $run->modify( '+1 day' );
    }
    return $run->getTimestamp();
}

function lawnace_digest_schedule() {
    if ( ! wp_next_scheduled( LAWNACE_DIGEST_HOOK ) ) {
        wp_schedule_event( lawnace_digest_next_run(), 'daily', LAWNACE_DIGEST_HOOK );
    }
}
add_action( 'init', 'lawnace_digest_schedule' );

function lawnace_digest_unschedule() {
    wp_clear_scheduled_hook( LAWNACE_DIGEST_HOOK );
}

add_action( LAWNACE_DIGEST_HOOK, 'lawnace_send_morning_digest' );

/**
 * Build and send the digest. $force = true bypasses the enabled switch (used by "Send test now").
 * Returns true on send, false if disabled / no recipients / mail failure.
 */
function lawnace_send_morning_digest( $force = false ) {
    if ( ! $force && get_option( 'lawnace_digest_enabled', '1' ) !== '1' ) {
        return false;
    }
    $recipients = lawnace_digest_recipients();
    if ( empty( $recipients ) ) {
        return false;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'lawnace_chat_logs';

    // Window: last 24 hours, site-local (log rows are stored with current_time('mysql'))
    $now_local = current_time( 'timestamp' );
    $start     = date( 'Y-m-d H:i:s', $now_local - DAY_IN_SECONDS );
    $end       = date( 'Y-m-d H:i:s', $now_local );

    $src = lawnace_client_task_sources( $start, $end );

    $conversations = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(DISTINCT session_id) FROM {$table} WHERE role = 'user' AND created_at >= %s AND created_at <= %s",
        $start, $end
    ) );

    $referred = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(DISTINCT session_id) FROM {$table} WHERE role = 'assistant' AND created_at >= %s AND created_at <= %s
         AND (message LIKE '%%call the office%%' OR message LIKE '%%give the office a call%%' OR message LIKE '%%706-364-2338%%')",
        $start, $end
    ) );

    // Open tasks across the dashboard's full 30-day window
    $all = lawnace_client_task_sources( date( 'Y-m-d 00:00:00', strtotime( '-30 days' ) ), date( 'Y-m-d 23:59:59' ) );
    $completed = get_option( 'lawnace_completed_tasks', [] );
    if ( ! is_array( $completed ) ) {
        $completed = [];
    }
    $all_sids = array_merge(
        array_column( $all['leads'], 'session_id' ),
        array_column( $all['pricing'], 'session_id' ),
        array_column( $all['service'], 'session_id' ),
        array_column( $all['dropoffs'], 'session_id' )
    );
    $open_tasks = count( $all_sids ) - count( array_intersect( $all_sids, array_keys( $completed ) ) );

    $data = array(
        'date_label'    => date_i18n( 'l, F j', $now_local ),
        'conversations' => $conversations,
        'leads'         => $src['leads'],
        'pricing'       => $src['pricing'],
        'service'       => $src['service'],
        'dropoffs'      => count( $src['dropoffs'] ),
        'referred'      => $referred,
        'open_tasks'    => $open_tasks,
    );

    $lead_n    = count( $src['leads'] );
    $service_n = count( $src['service'] );
    $subject   = sprintf(
        'Lawn Ace Morning Brief — %s: %d new lead%s, %d service issue%s',
        date_i18n( 'D M j', $now_local ),
        $lead_n, $lead_n === 1 ? '' : 's',
        $service_n, $service_n === 1 ? '' : 's'
    );

    $sent = wp_mail(
        $recipients,
        $subject,
        lawnace_digest_html( $data ),
        array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Lawnie at Lawn Ace <' . get_option( 'admin_email' ) . '>',
        )
    );

    update_option( 'lawnace_digest_last_sent', array(
        'time'  => current_time( 'mysql' ),
        'ok'    => (bool) $sent,
        'to'    => $recipients,
        'leads' => $lead_n,
        'test'  => (bool) $force,
    ), false );

    return (bool) $sent;
}

/** Session link into the team dashboard (login required, cookie persists). */
function lawnace_digest_chat_link( $session_id ) {
    return esc_url( add_query_arg( array( 'session' => $session_id, 'tab' => 'tasks' ), home_url( '/la-team/' ) ) );
}

function lawnace_digest_time( $mysql_datetime ) {
    $ts = strtotime( $mysql_datetime );
    return $ts ? date_i18n( 'D g:i a', $ts ) : esc_html( $mysql_datetime );
}

function lawnace_digest_html( $d ) {
    $dash_url = esc_url( add_query_arg( 'tab', 'tasks', home_url( '/la-team/' ) ) );
    $purple   = '#5b4fbd';
    $green    = '#a9c33f';

    // ── Stat tiles ──
    $tiles = array(
        array( 'Conversations',  $d['conversations'], $purple ),
        array( 'New Leads',      count( $d['leads'] ), '#16a34a' ),
        array( 'Service Issues', count( $d['service'] ), count( $d['service'] ) ? '#dc2626' : '#16a34a' ),
        array( 'Open Tasks',     $d['open_tasks'], $d['open_tasks'] ? '#d97706' : '#16a34a' ),
    );
    $tiles_html = '';
    foreach ( $tiles as $t ) {
        $tiles_html .= '
            <td width="25%" style="padding:6px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f8fc;border:1px solid #e8e5ff;border-radius:8px;">
                    <tr><td style="padding:14px 8px;text-align:center;">
                        <div style="font-size:26px;font-weight:800;color:' . $t[2] . ';line-height:1.1;">' . (int) $t[1] . '</div>
                        <div style="font-size:10px;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.4px;margin-top:5px;">' . $t[0] . '</div>
                    </td></tr>
                </table>
            </td>';
    }

    // ── New leads ──
    $leads_html = '';
    if ( empty( $d['leads'] ) ) {
        $leads_html = '<div style="font-size:13px;color:#999;padding:8px 0;">No new leads in the last 24 hours.</div>';
    } else {
        foreach ( $d['leads'] as $row ) {
            $ld    = lawnace_parse_lead_row( $row->lead_text );
            $name  = $ld['name'] !== '' ? esc_html( $ld['name'] ) : 'Website visitor';
            $lines = array();
            if ( $ld['phone'] !== '' ) {
                $lines[] = '📞 <a href="' . esc_attr( lawnace_lead_tel_href( $ld['phone'] ) ) . '" style="color:' . $purple . ';font-weight:700;text-decoration:none;">' . esc_html( $ld['phone'] ) . '</a>';
            }
            if ( $ld['email'] !== '' ) {
                $lines[] = '✉️ <a href="mailto:' . esc_attr( $ld['email'] ) . '" style="color:' . $purple . ';text-decoration:none;">' . esc_html( $ld['email'] ) . '</a>';
            }
            if ( $ld['address'] !== '' ) {
                $lines[] = '📍 <a href="' . esc_url( lawnace_lead_maps_href( $ld['address'] ) ) . '" style="color:#333;text-decoration:none;">' . esc_html( $ld['address'] ) . '</a>';
            }
            $missing = array();
            if ( $ld['phone'] === '' )   { $missing[] = 'phone'; }
            if ( $ld['address'] === '' ) { $missing[] = 'address'; }
            $missing_html = $missing ? '<div style="font-size:11px;color:#b45309;margin-top:4px;">No ' . esc_html( implode( ' or ', $missing ) ) . ' given — check the chat.</div>' : '';

            $leads_html .= '
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e0ddf5;border-left:4px solid #16a34a;border-radius:8px;margin-bottom:10px;background:#fff;">
                <tr><td style="padding:12px 14px;">
                    <div style="font-size:15px;font-weight:800;color:#1a1a2e;">' . $name . ' <span style="font-size:11px;font-weight:400;color:#999;">&middot; ' . lawnace_digest_time( $row->started ) . '</span></div>
                    <div style="font-size:13px;color:#333;line-height:1.7;margin-top:4px;">' . implode( '<br>', $lines ) . '</div>
                    ' . $missing_html . '
                    <div style="margin-top:8px;"><a href="' . lawnace_digest_chat_link( $row->session_id ) . '" style="font-size:12px;font-weight:700;color:' . $purple . ';text-decoration:none;">View conversation &rarr;</a></div>
                </td></tr>
            </table>';
        }
    }

    // ── Simple session lists (pricing, service) ──
    $list = function( $rows, $empty_text, $accent ) use ( $purple ) {
        if ( empty( $rows ) ) {
            return '<div style="font-size:13px;color:#999;padding:8px 0;">' . $empty_text . '</div>';
        }
        $out  = '<table width="100%" cellpadding="0" cellspacing="0">';
        $i    = 0;
        foreach ( $rows as $row ) {
            if ( ++$i > 12 ) {
                $out .= '<tr><td colspan="2" style="font-size:12px;color:#999;padding:6px 0;">+ ' . ( count( $rows ) - 12 ) . ' more on the dashboard</td></tr>';
                break;
            }
            $out .= '<tr>
                <td style="font-size:13px;color:#333;padding:7px 0;border-bottom:1px solid #f0f0f8;"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' . $accent . ';margin-right:8px;"></span>' . lawnace_digest_time( $row->started ) . '</td>
                <td align="right" style="padding:7px 0;border-bottom:1px solid #f0f0f8;"><a href="' . lawnace_digest_chat_link( $row->session_id ) . '" style="font-size:12px;font-weight:700;color:' . $purple . ';text-decoration:none;">View chat &rarr;</a></td>
            </tr>';
        }
        return $out . '</table>';
    };
    $pricing_html = $list( $d['pricing'], 'No unanswered pricing questions.', '#d97706' );
    $service_html = $list( $d['service'], 'No complaints, no-shows, or cancellations. 🎉', '#dc2626' );

    $section = function( $title, $body, $sub = '' ) use ( $purple ) {
        return '
    <tr><td style="padding:0 32px 22px;">
        <div style="font-size:12px;font-weight:800;color:#12103a;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid ' . $purple . ';padding-bottom:6px;margin-bottom:10px;">' . $title . '</div>
        ' . ( $sub ? '<div style="font-size:11px;color:#888;margin:-4px 0 10px;">' . $sub . '</div>' : '' ) . '
        ' . $body . '
    </td></tr>';
    };

    $dropoff_note = $d['dropoffs']
        ? (int) $d['dropoffs'] . ' customer' . ( $d['dropoffs'] === 1 ? '' : 's' ) . ' left after 2 or fewer messages'
        : 'No drop-offs';
    $referred_note = $d['referred']
        ? (int) $d['referred'] . ' conversation' . ( $d['referred'] === 1 ? '' : 's' ) . ' where Lawnie referred the customer to call the office'
        : 'Lawnie handled every question without referring anyone to the office';

    return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#e8e9ef;font-family:-apple-system,Segoe UI,Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#e8e9ef;padding:24px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">

    <tr>
        <td style="background:linear-gradient(135deg,#12103a,#3b3294,#5b4fbd);background-color:#3b3294;padding:26px 32px;">
            ' . lawnace_email_logo_html( 56 ) . '
            <div style="font-size:12px;color:rgba(255,255,255,.7);text-transform:uppercase;letter-spacing:.6px;font-weight:700;">☀️ Morning Brief from Lawnie</div>
            <div style="font-size:22px;font-weight:800;color:#fff;margin-top:4px;letter-spacing:-.3px;">Good morning, Lawn Ace</div>
            <div style="font-size:13px;color:rgba(255,255,255,.85);margin-top:6px;">' . esc_html( $d['date_label'] ) . ' &middot; What Lawnie handled in the last 24 hours</div>
        </td>
    </tr>

    <tr><td style="padding:20px 26px 8px;">
        <table width="100%" cellpadding="0" cellspacing="0"><tr>' . $tiles_html . '</tr></table>
    </td></tr>

    <tr><td style="padding:8px 32px 22px;text-align:center;">
        <a href="' . $dash_url . '" style="display:inline-block;background:' . $green . ';color:#fff;font-size:14px;font-weight:800;padding:12px 30px;border-radius:8px;text-decoration:none;">Open the Team Dashboard &rarr;</a>
    </td></tr>

    ' . $section( '🟢 New Leads — call these today', $leads_html ) . '
    ' . $section( '🟠 Pricing Questions — no contact info captured', $pricing_html, 'Worth a look: these customers asked about cost but Lawnie could not get their info.' ) . '
    ' . $section( '🔴 Service Issues', $service_html, 'Complaints, no-shows, and cancellation signals from the last 24 hours.' ) . '
    ' . $section( '📋 Also worth knowing', '
        <div style="font-size:13px;color:#333;line-height:1.8;">
            &bull; ' . esc_html( $dropoff_note ) . '<br>
            &bull; ' . esc_html( $referred_note ) . '<br>
            &bull; <strong>' . (int) $d['open_tasks'] . '</strong> open task' . ( $d['open_tasks'] === 1 ? '' : 's' ) . ' on the dashboard across the last 30 days
        </div>' ) . '

    <tr>
        <td style="background:#f8f9f6;padding:14px 32px;text-align:center;border-top:1px solid #eee;">
            <div style="font-size:11px;color:#999;line-height:1.6;">Sent every morning at ' . LAWNACE_DIGEST_HOUR . ':00 AM by Lawnie, the Lawn Ace chatbot.</div>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>';
}

/*
|--------------------------------------------------------------------------
| ADMIN: "Send test digest now" button handler
|--------------------------------------------------------------------------
*/
add_action( 'admin_post_lawnace_send_test_digest', 'lawnace_handle_test_digest' );

function lawnace_handle_test_digest() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    check_admin_referer( 'lawnace_send_test_digest' );

    $ok = lawnace_send_morning_digest( true );

    wp_redirect( add_query_arg( 'lawnace_digest', $ok ? 'sent' : 'failed', admin_url( 'admin.php?page=lawnace-settings' ) ) );
    exit;
}
