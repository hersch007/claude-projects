<?php
/*
 * Weekly Service Request Report — emailed to city admins and supervisors.
 *
 * Runs on WP-Cron (hook sp_ai_city_weekly_report) at the configured day/hour in
 * site time. Each run generates a FRESH AI Queue Analysis via
 * sp_ai_city_generate_analysis() (which also refreshes the dashboard cache) and
 * mails it, with a short stats strip, to every active team member whose city
 * role is city_admin or city_supervisor, plus any extra addresses configured.
 *
 * Settings live in their own card under Settings → "Weekly Report" and are
 * editable by the vendor (super admin) or a city admin. "Send now" mails a
 * report immediately so the setup can be verified.
 *
 * Options: sp_ai_city_weekly_enabled (1/0, default 1), sp_ai_city_weekly_day
 * (0=Sun..6=Sat, default 1), sp_ai_city_weekly_hour (0-23, default 7),
 * sp_ai_city_weekly_extra (extra recipient emails), sp_ai_city_weekly_last (log).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function sp_ai_city_weekly_register() {
    add_action( 'sp_ai_city_weekly_report',           'sp_ai_city_weekly_cron' );
    add_action( 'init',                                'sp_ai_city_weekly_self_heal' );
    add_filter( 'sp_settings_anchor_tabs',             'sp_ai_city_weekly_settings_tab' );
    add_action( 'sp_settings_sections',                'sp_ai_city_weekly_settings_section', 12 );
    add_action( 'sp_post_handler_ai_city_weekly',      'sp_ai_city_weekly_save_settings' );
    add_action( 'wp_ajax_sp_ai_city_weekly_send_now',  'sp_ai_ajax_city_weekly_send_now' );
}

// ── Access ────────────────────────────────────────────────────────────────────

// Vendor (super admin) or a city admin may configure and send the report.
function sp_ai_city_weekly_can_manage() {
    if ( function_exists( 'sp_is_super_admin' ) && sp_is_super_admin() ) return true;
    return function_exists( 'sp_city_is_city_admin' ) && sp_city_is_city_admin()
        && function_exists( 'sp_get_current_team_member' ) && sp_get_current_team_member();
}

// ── Schedule ──────────────────────────────────────────────────────────────────

function sp_ai_city_weekly_enabled() {
    return (bool) get_option( 'sp_ai_city_weekly_enabled', 1 );
}

function sp_ai_city_weekly_day_names() {
    return array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
}

// Next occurrence of the configured weekday/hour in the site's timezone.
function sp_ai_city_weekly_next_timestamp() {
    $day  = max( 0, min( 6,  (int) get_option( 'sp_ai_city_weekly_day',  1 ) ) );
    $hour = max( 0, min( 23, (int) get_option( 'sp_ai_city_weekly_hour', 7 ) ) );
    $tz   = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
    $now  = new DateTime( 'now', $tz );
    $next = clone $now;
    $next->setTime( $hour, 0, 0 );
    $guard = 0;
    while ( ( (int) $next->format( 'w' ) !== $day || $next <= $now ) && $guard++ < 8 ) {
        $next->modify( '+1 day' );
        $next->setTime( $hour, 0, 0 );
    }
    return $next->getTimestamp();
}

// (Re)schedule from current settings. Clears any existing event first so a
// changed day/hour takes effect immediately.
function sp_ai_city_weekly_schedule() {
    wp_clear_scheduled_hook( 'sp_ai_city_weekly_report' );
    if ( ! sp_ai_city_weekly_enabled() ) return;
    wp_schedule_event( sp_ai_city_weekly_next_timestamp(), 'weekly', 'sp_ai_city_weekly_report' );
}

// Cheap per-load check: keep the cron present whenever the report is enabled.
function sp_ai_city_weekly_self_heal() {
    if ( sp_ai_city_weekly_enabled() && ! wp_next_scheduled( 'sp_ai_city_weekly_report' ) ) {
        sp_ai_city_weekly_schedule();
    }
}

function sp_ai_city_weekly_cron() {
    sp_ai_city_send_weekly_report( false );
}

// ── Recipients ────────────────────────────────────────────────────────────────

// Active team members with a city role of admin or supervisor (Government
// Service Core treats a member with no role entry as city_admin, so those are
// included), plus any extra addresses from settings. De-duplicated by email.
function sp_ai_city_weekly_recipients() {
    global $wpdb;
    $out  = array();
    $seen = array();

    $members = $wpdb->get_results(
        "SELECT id, name, email FROM {$wpdb->prefix}sp_team WHERE status = 'active' AND email != '' ORDER BY name"
    );
    foreach ( $members as $m ) {
        $role = function_exists( 'sp_city_get_member_role' ) ? sp_city_get_member_role( $m->id ) : 'city_admin';
        if ( ! in_array( $role, array( 'city_admin', 'city_supervisor' ), true ) ) continue;
        $email = strtolower( trim( $m->email ) );
        if ( ! is_email( $email ) || isset( $seen[ $email ] ) ) continue;
        $seen[ $email ] = true;
        $out[] = array( 'name' => $m->name, 'email' => $email, 'role' => $role );
    }

    $extra = (string) get_option( 'sp_ai_city_weekly_extra', '' );
    foreach ( preg_split( '/[\s,;]+/', $extra ) as $email ) {
        $email = strtolower( trim( $email ) );
        if ( ! $email || ! is_email( $email ) || isset( $seen[ $email ] ) ) continue;
        $seen[ $email ] = true;
        $out[] = array( 'name' => $email, 'email' => $email, 'role' => 'extra' );
    }
    return $out;
}

// ── Send ──────────────────────────────────────────────────────────────────────

// Generates a fresh analysis and emails it. Returns array(sent, failed,
// recipients) or WP_Error. $manual = true bypasses the enabled toggle ("Send now").
function sp_ai_city_send_weekly_report( $manual = false ) {
    if ( ! $manual && ! sp_ai_city_weekly_enabled() ) {
        return new WP_Error( 'sp_ai_weekly', 'Weekly report is disabled.' );
    }
    if ( ! sp_ai_is_configured() ) {
        return new WP_Error( 'sp_ai_weekly', 'AI is not configured — add an API key under Settings → AI Integration.' );
    }
    $recipients = sp_ai_city_weekly_recipients();
    if ( empty( $recipients ) ) {
        return new WP_Error( 'sp_ai_weekly', 'No recipients: no active team member has the City Admin or City Supervisor role with an email address.' );
    }

    $text = sp_ai_city_generate_analysis();
    if ( is_wp_error( $text ) ) return $text;

    $city    = get_option( 'sp_city_name', '' ) ?: 'City';
    $subject = $city . ' — Weekly Service Request Report — ' . date_i18n( 'M j, Y' );
    $html    = sp_ai_city_weekly_email_html( $text, $city );
    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    $sent   = 0;
    $failed = array();
    foreach ( $recipients as $r ) {
        if ( wp_mail( $r['email'], $subject, $html, $headers ) ) {
            $sent++;
        } else {
            $failed[] = $r['email'];
        }
    }

    update_option( 'sp_ai_city_weekly_last', array(
        'time'   => time(),
        'sent'   => $sent,
        'failed' => $failed,
        'manual' => (bool) $manual,
    ), false );

    return array( 'sent' => $sent, 'failed' => $failed, 'recipients' => $recipients );
}

// ── Email body ────────────────────────────────────────────────────────────────

// Headline numbers for the strip at the top of the email.
function sp_ai_city_weekly_stats() {
    global $wpdb;
    $p    = $wpdb->prefix;
    $open = "status NOT IN ('resolved','closed')";
    return array(
        'Open requests'          => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}sp_city_tickets WHERE $open" ),
        'Emergency / high open'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}sp_city_tickets WHERE $open AND priority IN ('emergency','high')" ),
        'Unacknowledged > 24h'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}sp_city_tickets WHERE $open AND acknowledged_at IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)" ),
        'New this week'          => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}sp_city_tickets WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" ),
        'Resolved this week'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}sp_city_tickets WHERE resolved_at IS NOT NULL AND resolved_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" ),
    );
}

// Email-safe markdown: inline styles only, no SVG icons (most mail clients
// strip them). Mirrors the subset sp_ai_render_markdown() understands.
function sp_ai_city_weekly_markdown( $text ) {
    $html  = '';
    $in_ul = false;
    foreach ( explode( "\n", $text ) as $line ) {
        $t = trim( $line );
        if ( preg_match( '/^# (.+)/', $t, $m ) ) {
            if ( $in_ul ) { $html .= '</ul>'; $in_ul = false; }
            $html .= '<h2 style="font-size:16px;font-weight:700;color:#0f172a;margin:22px 0 6px;padding-bottom:4px;border-bottom:1px solid #e2e8f0">' . esc_html( $m[1] ) . '</h2>';
        } elseif ( preg_match( '/^## (.+)/', $t, $m ) ) {
            if ( $in_ul ) { $html .= '</ul>'; $in_ul = false; }
            $html .= '<h3 style="font-size:14px;font-weight:700;color:#1e293b;margin:14px 0 4px">' . esc_html( $m[1] ) . '</h3>';
        } elseif ( preg_match( '/^[-*] (.+)/', $t, $m ) ) {
            if ( ! $in_ul ) { $html .= '<ul style="margin:4px 0 10px 20px;padding:0">'; $in_ul = true; }
            $html .= '<li style="margin:0 0 4px;color:#374151">' . sp_ai_inline_md( $m[1] ) . '</li>';
        } elseif ( preg_match( '/^([-*_]\s?){3,}$/', $t ) ) {
            if ( $in_ul ) { $html .= '</ul>'; $in_ul = false; } // horizontal rule: drop
        } elseif ( $t === '' ) {
            if ( $in_ul ) { $html .= '</ul>'; $in_ul = false; }
        } else {
            if ( $in_ul ) { $html .= '</ul>'; $in_ul = false; }
            $html .= '<p style="margin:0 0 10px;color:#374151">' . sp_ai_inline_md( $t ) . '</p>';
        }
    }
    if ( $in_ul ) $html .= '</ul>';
    return $html;
}

function sp_ai_city_weekly_email_html( $analysis_text, $city ) {
    $stats    = sp_ai_city_weekly_stats();
    $dash_url = home_url( '/sp-app/' );
    $days     = sp_ai_city_weekly_day_names();
    $day      = $days[ max( 0, min( 6, (int) get_option( 'sp_ai_city_weekly_day', 1 ) ) ) ];
    $hour     = (int) get_option( 'sp_ai_city_weekly_hour', 7 );
    $when     = $day . 's at ' . date( 'g:i A', mktime( $hour, 0, 0 ) );
    $range    = date_i18n( 'M j', strtotime( '-7 days' ) ) . ' – ' . date_i18n( 'M j, Y' );

    $cells = '';
    foreach ( $stats as $label => $value ) {
        $color  = ( $label === 'Emergency / high open' || $label === 'Unacknowledged > 24h' ) && $value > 0 ? '#dc2626' : '#0f172a';
        $cells .= '<td align="center" valign="top" style="padding:10px 6px;border-right:1px solid #e2e8f0">'
            . '<div style="font-size:22px;font-weight:800;color:' . $color . ';line-height:1.1">' . (int) $value . '</div>'
            . '<div style="font-size:10px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#64748b;margin-top:4px">' . esc_html( $label ) . '</div>'
            . '</td>';
    }

    ob_start();
    ?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo esc_html( $city ); ?> Weekly Service Request Report</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 12px">
<tr><td align="center">
<table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0">
    <tr><td style="background:#0f172a;padding:22px 28px">
        <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#818cf8">Weekly Service Request Report</div>
        <div style="font-size:22px;font-weight:800;color:#ffffff;margin-top:4px"><?php echo esc_html( $city ); ?></div>
        <div style="font-size:13px;color:#94a3b8;margin-top:4px"><?php echo esc_html( $range ); ?></div>
    </td></tr>
    <tr><td style="padding:0 20px">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:8px;margin:18px 0 6px;background:#f8fafc">
            <tr><?php echo $cells; ?></tr>
        </table>
    </td></tr>
    <tr><td style="padding:12px 28px 8px;font-size:14px;line-height:1.7;color:#374151">
        <?php echo sp_ai_city_weekly_markdown( $analysis_text ); ?>
    </td></tr>
    <tr><td align="center" style="padding:8px 28px 26px">
        <a href="<?php echo esc_url( $dash_url ); ?>" style="display:inline-block;background:#4f46e5;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:12px 22px;border-radius:8px">Open the Dashboard</a>
    </td></tr>
    <tr><td style="padding:14px 28px;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:11px;line-height:1.6;color:#94a3b8">
        Generated automatically by the <?php echo esc_html( $city ); ?> service system, <?php echo esc_html( $when ); ?>. Sent to city admins and supervisors. Change the schedule or recipients under Settings &rarr; Weekly Report. AI-written summary of live ticket data; verify before acting on specifics.
    </td></tr>
</table>
</td></tr>
</table>
</body></html>
    <?php
    return ob_get_clean();
}

// ── Settings ──────────────────────────────────────────────────────────────────

function sp_ai_city_weekly_settings_tab( $tabs ) {
    if ( sp_ai_city_weekly_can_manage() ) {
        $tabs[] = array( 'id' => 'section-ai-weekly', 'label' => 'Weekly Report' );
    }
    return $tabs;
}

function sp_ai_city_weekly_save_settings( $id ) {
    if ( ! sp_ai_city_weekly_can_manage() ) return;
    update_option( 'sp_ai_city_weekly_enabled', empty( $_POST['sp_ai_city_weekly_enabled'] ) ? 0 : 1 );
    update_option( 'sp_ai_city_weekly_day',     max( 0, min( 6,  (int) ( isset( $_POST['sp_ai_city_weekly_day'] )  ? $_POST['sp_ai_city_weekly_day']  : 1 ) ) ) );
    update_option( 'sp_ai_city_weekly_hour',    max( 0, min( 23, (int) ( isset( $_POST['sp_ai_city_weekly_hour'] ) ? $_POST['sp_ai_city_weekly_hour'] : 7 ) ) ) );
    $extra = isset( $_POST['sp_ai_city_weekly_extra'] ) ? sanitize_textarea_field( wp_unslash( $_POST['sp_ai_city_weekly_extra'] ) ) : '';
    update_option( 'sp_ai_city_weekly_extra', $extra );
    sp_ai_city_weekly_schedule();
    wp_redirect( home_url( '/sp-app/?view=settings&saved=1#section-ai-weekly' ) ); exit;
}

function sp_ai_city_weekly_settings_section() {
    if ( ! sp_ai_city_weekly_can_manage() ) return;
    $enabled    = sp_ai_city_weekly_enabled();
    $day        = max( 0, min( 6,  (int) get_option( 'sp_ai_city_weekly_day',  1 ) ) );
    $hour       = max( 0, min( 23, (int) get_option( 'sp_ai_city_weekly_hour', 7 ) ) );
    $extra      = (string) get_option( 'sp_ai_city_weekly_extra', '' );
    $last       = get_option( 'sp_ai_city_weekly_last' );
    $recipients = sp_ai_city_weekly_recipients();
    $next       = wp_next_scheduled( 'sp_ai_city_weekly_report' );
    $nonce      = wp_create_nonce( 'sp_ai_city_weekly_send' );
    $configured = sp_ai_is_configured();
    $role_labels = array( 'city_admin' => 'City Admin', 'city_supervisor' => 'City Supervisor', 'extra' => 'Extra' );
    ?>
    <div id="section-ai-weekly" class="sp-card sp-form-card sp-settings-section" style="margin-top:16px">
        <h2 class="sp-section-heading">Weekly Service Request Report</h2>
        <p class="sp-hint" style="margin:0 0 14px">Emails the AI Queue Analysis, with headline numbers, to every active team member with the <strong>City Admin</strong> or <strong>City Supervisor</strong> role. Each send generates a fresh analysis and also refreshes the dashboard card.</p>
        <?php if ( ! $configured ) : ?>
        <p class="sp-hint" style="color:#b45309;margin:0 0 14px"><strong>AI is not configured.</strong> Add an API key in the AI Integration section above or nothing will be sent.</p>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="ai_city_weekly">
            <input type="hidden" name="sp_id" value="0">

            <div class="sp-field">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="sp_ai_city_weekly_enabled" value="1" <?php checked( $enabled ); ?>> Send the weekly report automatically
                </label>
            </div>

            <div class="sp-form-row" style="margin-top:12px">
                <div class="sp-field" style="max-width:200px">
                    <label>Day</label>
                    <select name="sp_ai_city_weekly_day">
                        <?php foreach ( sp_ai_city_weekly_day_names() as $i => $name ) : ?>
                            <option value="<?php echo $i; ?>" <?php selected( $day, $i ); ?>><?php echo esc_html( $name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sp-field" style="max-width:160px">
                    <label>Time (site time)</label>
                    <select name="sp_ai_city_weekly_hour">
                        <?php for ( $h = 0; $h < 24; $h++ ) : ?>
                            <option value="<?php echo $h; ?>" <?php selected( $hour, $h ); ?>><?php echo date( 'g:00 A', mktime( $h, 0, 0 ) ); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="sp-field" style="max-width:480px;margin-top:12px">
                <label>Extra recipients <span style="font-weight:400;color:var(--sp-muted)">(optional, one per line or comma-separated)</span></label>
                <textarea name="sp_ai_city_weekly_extra" rows="2" placeholder="mayor@example.gov"><?php echo esc_textarea( $extra ); ?></textarea>
            </div>

            <div class="sp-form-actions" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <button type="submit" class="sp-btn sp-btn-primary">Save Weekly Report Settings</button>
                <button type="button" id="sp-ai-weekly-send" class="sp-btn sp-btn-ghost" <?php disabled( ! $configured || empty( $recipients ) ); ?>
                    data-nonce="<?php echo esc_attr( $nonce ); ?>" data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">Send now</button>
                <span id="sp-ai-weekly-send-status" style="font-size:12px;color:var(--sp-muted)"></span>
            </div>
        </form>

        <div style="margin-top:18px;padding-top:14px;border-top:1px solid var(--sp-border);font-size:13px">
            <div style="margin-bottom:8px">
                <strong>Next send:</strong>
                <?php if ( $enabled && $next ) : ?>
                    <?php echo esc_html( wp_date( 'l, M j \a\t g:i A', $next ) ); ?>
                <?php elseif ( $enabled ) : ?>
                    scheduling on next page load
                <?php else : ?>
                    off
                <?php endif; ?>
                <?php if ( $last && ! empty( $last['time'] ) ) : ?>
                    &nbsp;&middot;&nbsp; <strong>Last send:</strong> <?php echo esc_html( human_time_diff( $last['time'] ) ); ?> ago,
                    <?php echo (int) $last['sent']; ?> delivered<?php if ( ! empty( $last['failed'] ) ) echo ', <span style="color:#dc2626">' . count( $last['failed'] ) . ' failed</span>'; ?><?php echo ! empty( $last['manual'] ) ? ' (manual)' : ''; ?>
                <?php endif; ?>
            </div>
            <div><strong>Recipients (<?php echo count( $recipients ); ?>):</strong>
                <?php if ( $recipients ) : ?>
                    <?php $bits = array(); foreach ( $recipients as $r ) $bits[] = esc_html( $r['name'] ) . ' <span style="color:var(--sp-muted)">(' . esc_html( $r['email'] ) . ', ' . esc_html( $role_labels[ $r['role'] ] ) . ')</span>'; echo implode( ' &middot; ', $bits ); ?>
                <?php else : ?>
                    <span style="color:#dc2626">none — give at least one active team member the City Admin or City Supervisor role and an email address.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
    (function(){
        var btn = document.getElementById('sp-ai-weekly-send'), st = document.getElementById('sp-ai-weekly-send-status');
        if (!btn) return;
        btn.addEventListener('click', function(){
            if (!confirm('Generate a fresh analysis and email it to all recipients now?')) return;
            btn.disabled = true; st.textContent = 'Generating and sending… (about 20 seconds)';
            var fd = new FormData(); fd.append('action','sp_ai_city_weekly_send_now'); fd.append('nonce', btn.dataset.nonce);
            fetch(btn.dataset.ajax, { method:'POST', body:fd, credentials:'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(d){
                    btn.disabled = false;
                    st.textContent = d.success ? d.data.message : ('Error: ' + (d.data || 'unknown'));
                    st.style.color = d.success ? '#059669' : '#dc2626';
                })
                .catch(function(){ btn.disabled = false; st.textContent = 'Request failed.'; st.style.color = '#dc2626'; });
        });
    })();
    </script>
    <?php
}

function sp_ai_ajax_city_weekly_send_now() {
    if ( ! check_ajax_referer( 'sp_ai_city_weekly_send', 'nonce', false ) ) wp_send_json_error( 'Invalid nonce' );
    if ( ! sp_ai_city_weekly_can_manage() ) wp_send_json_error( 'Not authorized' );
    $result = sp_ai_city_send_weekly_report( true );
    if ( is_wp_error( $result ) ) wp_send_json_error( $result->get_error_message() );
    $msg = 'Sent to ' . (int) $result['sent'] . ' recipient' . ( $result['sent'] === 1 ? '' : 's' ) . '.';
    if ( ! empty( $result['failed'] ) ) $msg .= ' Failed: ' . implode( ', ', $result['failed'] ) . '.';
    wp_send_json_success( array( 'message' => $msg ) );
}
