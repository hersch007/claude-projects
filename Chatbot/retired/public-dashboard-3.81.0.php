<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
|--------------------------------------------------------------------------
| AUTH HELPERS
|--------------------------------------------------------------------------
*/

function lawnace_public_dash_cookie_value() {
    $pin = get_option( 'lawnace_dashboard_pin', '' );
    if ( empty( $pin ) ) return '';
    return hash( 'sha256', $pin . wp_salt( 'auth' ) . 'lawnace-dash-v1' );
}

function lawnace_public_dash_is_authed() {
    $expected = lawnace_public_dash_cookie_value();
    if ( empty( $expected ) ) return false;
    $cookie = isset( $_COOKIE['lawnace_dash_auth'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['lawnace_dash_auth'] ) ) : '';
    return ! empty( $cookie ) && hash_equals( $expected, $cookie );
}

/*
|--------------------------------------------------------------------------
| REWRITE RULE
|--------------------------------------------------------------------------
*/

add_action( 'init', 'lawnace_register_dashboard_rewrite' );

function lawnace_register_dashboard_rewrite() {
    add_rewrite_rule( '^ace-dashboard/?$', 'index.php?lawnace_dashboard=1', 'top' );
}

add_filter( 'query_vars', 'lawnace_dashboard_query_vars' );

function lawnace_dashboard_query_vars( $vars ) {
    $vars[] = 'lawnace_dashboard';
    return $vars;
}

/*
|--------------------------------------------------------------------------
| TEMPLATE REDIRECT
|--------------------------------------------------------------------------
*/

add_action( 'template_redirect', 'lawnace_maybe_render_public_dashboard' );

function lawnace_maybe_render_public_dashboard() {
    if ( ! get_query_var( 'lawnace_dashboard' ) ) return;

    $pin = get_option( 'lawnace_dashboard_pin', '' );

    // Handle logout
    if ( isset( $_GET['logout'] ) ) {
        setcookie( 'lawnace_dash_auth', '', [
            'expires'  => time() - 3600,
            'path'     => COOKIEPATH,
            'domain'   => COOKIE_DOMAIN,
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ] );
        wp_redirect( home_url( '/ace-dashboard/' ) );
        exit;
    }

    // Handle login form submit
    if ( isset( $_POST['lawnace_pin'] ) ) {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'lawnace_dash_login' ) ) {
            lawnace_render_public_login( 'Invalid request. Please try again.' );
            exit;
        }
        $submitted = sanitize_text_field( wp_unslash( $_POST['lawnace_pin'] ) );
        if ( ! empty( $pin ) && hash_equals( $pin, $submitted ) ) {
            setcookie( 'lawnace_dash_auth', lawnace_public_dash_cookie_value(), [
                'expires'  => time() + 8 * HOUR_IN_SECONDS,
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ] );
            wp_redirect( home_url( '/ace-dashboard/' ) );
            exit;
        } else {
            lawnace_render_public_login( 'Incorrect PIN. Please try again.' );
            exit;
        }
    }

    if ( empty( $pin ) ) {
        lawnace_render_public_not_configured();
        exit;
    }

    if ( ! lawnace_public_dash_is_authed() ) {
        lawnace_render_public_login( false );
        exit;
    }

    lawnace_render_public_dashboard_page();
    exit;
}

/*
|--------------------------------------------------------------------------
| PUBLIC INSIGHTS AJAX
|--------------------------------------------------------------------------
*/

add_action( 'wp_ajax_lawnace_public_insights',        'lawnace_public_insights_handler' );
add_action( 'wp_ajax_nopriv_lawnace_public_insights', 'lawnace_public_insights_handler' );

function lawnace_public_insights_handler() {
    check_ajax_referer( 'lawnace_public_insights_nonce', 'nonce' );

    if ( ! lawnace_public_dash_is_authed() ) {
        wp_send_json_error( 'Not authorized.' );
    }

    global $wpdb;
    $table   = $wpdb->prefix . 'lawnace_chat_logs';
    $api_key = get_option( 'lawnace_anthropic_key', '' );

    if ( empty( $api_key ) ) {
        wp_send_json_error( 'API key not configured.' );
    }

    $messages       = $wpdb->get_col( "SELECT message FROM {$table} WHERE role = 'user' ORDER BY created_at DESC LIMIT 200" );
    $total_sessions = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM {$table}" );
    $lead_sessions  = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM {$table} WHERE role = 'lead'" );
    $conversion     = $total_sessions > 0 ? round( ( $lead_sessions / $total_sessions ) * 100, 1 ) : 0;

    $topic_counts = [];
    foreach ( $messages as $msg ) {
        foreach ( lawnace_categorize_message( $msg ) as $t ) {
            $topic_counts[ $t ] = ( $topic_counts[ $t ] ?? 0 ) + 1;
        }
    }
    arsort( $topic_counts );
    $topic_summary = '';
    foreach ( $topic_counts as $topic => $count ) {
        $topic_summary .= "- {$topic}: {$count} mentions\n";
    }

    $message_sample = implode( "\n---\n", array_slice( $messages, 0, 100 ) );

    $prompt = "You are a lawn care business analyst reviewing chat data for Lawn Ace, a locally owned lawn care company in Augusta, GA serving the CSRA area.

Services: Lawn Fertilization, Weed Control, Mosquito Control, Tree & Shrub Care, Core Aeration, Fire Ant Control, Insect Control, Grub Control.

CHAT STATISTICS:
- Total sessions: {$total_sessions}
- Leads captured: {$lead_sessions}
- Lead conversion rate: {$conversion}%

TOPIC BREAKDOWN:
{$topic_summary}

SAMPLE OF RECENT CUSTOMER MESSAGES:
---
{$message_sample}
---

Provide a focused business analysis. Do NOT include a title or introductory header — go straight into the sections. Format each section header exactly as: ## N. SECTION NAME (nothing else on that line).

## 1. LEAD CONVERSION OPPORTUNITIES
## 2. WHAT CUSTOMERS ARE REALLY ASKING ABOUT
## 3. CONTENT & BLOG RECOMMENDATIONS (suggest actual titles)
## 4. CHATBOT IMPROVEMENTS
## 5. SEASONAL / TIMING OBSERVATIONS
## 6. TOP 3 ACTION ITEMS

Be specific, direct, and actionable. Write for a small business owner. Keep each section concise. Use **bold** sparingly to highlight the single most important number, finding, or action item in each section — not every sentence, just the thing they must not miss.";

    $response = wp_remote_post(
        'https://api.anthropic.com/v1/messages',
        [
            'timeout' => 60,
            'headers' => [
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ],
            'body' => wp_json_encode( [
                'model'      => LAWNACE_MODEL,
                'max_tokens' => 1500,
                'messages'   => [ [ 'role' => 'user', 'content' => $prompt ] ],
            ] ),
        ]
    );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( 'API connection failed.' );
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $body['content'][0]['text'] ) ) {
        wp_send_json_error( 'No response from API.' );
    }

    $insights = [
        'text'      => $body['content'][0]['text'],
        'generated' => current_time( 'mysql' ),
        'sessions'  => $total_sessions,
    ];

    update_option( 'lawnace_insights_cache', $insights );
    wp_send_json_success( $insights );
}

/*
|--------------------------------------------------------------------------
| LOGIN PAGE
|--------------------------------------------------------------------------
*/

function lawnace_render_public_login( $error = false ) {
    $site_name = get_bloginfo( 'name' );
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Login &mdash; <?php echo esc_html( $site_name ); ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0f1a0f;min-height:100vh;display:flex;align-items:center;justify-content:center;}
.login-wrap{width:100%;max-width:380px;padding:20px;}
.login-card{background:#fff;border-radius:16px;padding:40px 36px;box-shadow:0 20px 60px rgba(0,0,0,.4);}
.login-logo{text-align:center;margin-bottom:28px;}
.login-logo svg{width:56px;height:56px;}
.login-title{font-size:22px;font-weight:700;color:#1a2e1a;text-align:center;margin-bottom:6px;}
.login-sub{font-size:13px;color:#888;text-align:center;margin-bottom:28px;}
.login-label{display:block;font-size:12px;font-weight:600;color:#444;margin-bottom:6px;letter-spacing:.5px;text-transform:uppercase;}
.login-input{width:100%;padding:12px 16px;border:2px solid #e0e0e0;border-radius:8px;font-size:18px;letter-spacing:6px;text-align:center;outline:none;transition:border-color .2s;}
.login-input:focus{border-color:#6559b1;}
.login-error{background:#fff0f0;border:1px solid #f5c2c2;color:#c0392b;border-radius:6px;padding:10px 14px;font-size:13px;margin-top:14px;text-align:center;}
.login-btn{width:100%;margin-top:18px;padding:13px;background:#6559b1;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;transition:background .2s;}
.login-btn:hover{background:#4a3f99;}
.login-footer{text-align:center;margin-top:20px;font-size:11px;color:#aaa;}
</style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-logo">
            <svg viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="56" height="56" rx="14" fill="#6559b1"/>
                <path d="M10 38 L46 10 L38 48 L26 32 Z" fill="white" fill-opacity=".25" stroke="white" stroke-width="2.5" stroke-linejoin="round"/>
                <path d="M26 32 L38 48 L32 40 Z" fill="white" fill-opacity=".6" stroke="white" stroke-width="2" stroke-linejoin="round"/>
                <line x1="10" y1="38" x2="26" y2="32" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="login-title">Ace Analytics</div>
        <div class="login-sub">Enter your dashboard PIN to continue</div>

        <form method="post" action="<?php echo esc_url( home_url( '/ace-dashboard/' ) ); ?>">
            <?php wp_nonce_field( 'lawnace_dash_login' ); ?>
            <label class="login-label" for="lawnace_pin">Dashboard PIN</label>
            <input class="login-input" type="password" id="lawnace_pin" name="lawnace_pin"
                   autocomplete="current-password" autofocus required maxlength="20"
                   placeholder="••••••">
            <?php if ( $error ) : ?>
                <div class="login-error"><?php echo esc_html( $error ); ?></div>
            <?php endif; ?>
            <button class="login-btn" type="submit">Enter Dashboard</button>
        </form>
    </div>
    <div class="login-footer">Lawn Ace &mdash; <?php echo esc_html( $site_name ); ?></div>
</div>
</body>
</html>
    <?php
}

/*
|--------------------------------------------------------------------------
| NOT CONFIGURED PAGE
|--------------------------------------------------------------------------
*/

function lawnace_render_public_not_configured() {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard Not Configured</title>
<style>
body{font-family:-apple-system,sans-serif;background:#0f1a0f;min-height:100vh;display:flex;align-items:center;justify-content:center;color:#fff;}
.card{background:#1a2e1a;border:1px solid #2a4a2a;border-radius:12px;padding:40px;max-width:420px;text-align:center;}
h2{margin-bottom:12px;font-size:20px;}
p{color:#aaa;font-size:14px;line-height:1.6;}
a{color:#a9c33f;}
</style>
</head>
<body>
<div class="card">
    <h2>Dashboard PIN Not Set</h2>
    <p>A dashboard PIN has not been configured yet. Please visit <a href="<?php echo esc_url( admin_url( 'admin.php?page=lawnace-settings' ) ); ?>">LawnAce Settings</a> in the WordPress admin to set one.</p>
</div>
</body>
</html>
    <?php
}

/*
|--------------------------------------------------------------------------
| PUBLIC DASHBOARD PAGE
|--------------------------------------------------------------------------
*/

function lawnace_render_public_dashboard_page() {
    global $wpdb;
    lawnace_chatbot_ensure_log_table();
    $table     = $wpdb->prefix . 'lawnace_chat_logs';
    $site_name = get_bloginfo( 'name' );

    // Session detail view
    if ( ! empty( $_GET['session'] ) ) {
        lawnace_render_public_session_detail( sanitize_text_field( wp_unslash( $_GET['session'] ) ), $table, $site_name );
        return;
    }

    // Date range
    $range = isset( $_GET['range'] ) ? sanitize_text_field( wp_unslash( $_GET['range'] ) ) : 'month';
    switch ( $range ) {
        case 'week':
            $since = date( 'Y-m-d 00:00:00', strtotime( '-7 days' ) );
            $label = 'Last 7 Days';
            break;
        case 'all':
            $since = '2000-01-01 00:00:00';
            $label = 'All Time';
            break;
        default:
            $since = date( 'Y-m-d 00:00:00', strtotime( '-30 days' ) );
            $label = 'Last 30 Days';
            $range = 'month';
    }

    // ── Stats ──────────────────────────────────────────────────────────

    $total_sessions = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(DISTINCT session_id) FROM {$table} WHERE created_at >= %s", $since
    ) );
    $lead_sessions = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(DISTINCT session_id) FROM {$table} WHERE role = 'lead' AND created_at >= %s", $since
    ) );
    $conversion     = $total_sessions > 0 ? round( ( $lead_sessions / $total_sessions ) * 100, 1 ) : 0;
    $total_messages = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE role = 'user' AND created_at >= %s", $since
    ) );

    // ── Topic breakdown ────────────────────────────────────────────────

    $user_message_rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT session_id, message, created_at FROM {$table} WHERE role = 'user' AND created_at >= %s ORDER BY created_at DESC", $since
    ) );
    $user_messages = wp_list_pluck( $user_message_rows, 'message' );

    $topic_counts = [];
    foreach ( $user_messages as $msg ) {
        foreach ( lawnace_categorize_message( $msg ) as $t ) {
            $topic_counts[ $t ] = ( $topic_counts[ $t ] ?? 0 ) + 1;
        }
    }
    arsort( $topic_counts );
    $total_topic_hits = array_sum( $topic_counts ) ?: 1;

    // ── Peak hours ─────────────────────────────────────────────────────

    $hour_rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT HOUR(created_at) AS hr, COUNT(*) AS cnt
         FROM {$table} WHERE role = 'user' AND created_at >= %s
         GROUP BY hr ORDER BY hr ASC", $since
    ) );
    $hours = array_fill( 0, 24, 0 );
    foreach ( $hour_rows as $r ) $hours[ (int) $r->hr ] = (int) $r->cnt;
    $max_hour = max( $hours ) ?: 1;

    // ── ZIP codes ─────────────────────────────────────────────────────

    $zip_counts = [];
    foreach ( $user_messages as $msg ) {
        if ( preg_match_all( '/\b(\d{5})\b/', $msg, $matches ) ) {
            foreach ( $matches[1] as $zip ) {
                $zip_counts[ $zip ] = ( $zip_counts[ $zip ] ?? 0 ) + 1;
            }
        }
    }
    arsort( $zip_counts );
    $zip_counts = array_slice( $zip_counts, 0, 10, true );

    // ── Competitor mentions ────────────────────────────────────────────

    $competitor_counts   = [];
    $competitor_sessions = [];
    foreach ( $user_message_rows as $row ) {
        foreach ( lawnace_detect_competitors( $row->message ) as $name ) {
            $competitor_counts[ $name ] = ( $competitor_counts[ $name ] ?? 0 ) + 1;
            if ( count( $competitor_sessions[ $name ] ?? [] ) < 50 ) {
                $competitor_sessions[ $name ][] = [
                    'session_id' => $row->session_id,
                    'message'    => mb_substr( $row->message, 0, 300 ),
                    'created_at' => $row->created_at,
                ];
            }
        }
    }
    arsort( $competitor_counts );

    // ── Recent leads ───────────────────────────────────────────────────

    $recent_leads = $wpdb->get_results( $wpdb->prepare(
        "SELECT session_id, message, created_at FROM {$table}
         WHERE role = 'lead' AND created_at >= %s
         ORDER BY created_at DESC LIMIT 10", $since
    ) );

    // ── Frustrated sessions ────────────────────────────────────────────

    $frustrated = $wpdb->get_results( $wpdb->prepare(
        "SELECT session_id, COUNT(*) AS msg_count, MIN(created_at) AS started
         FROM {$table} WHERE role = 'user' AND created_at >= %s
         GROUP BY session_id HAVING msg_count <= 2
         ORDER BY started DESC LIMIT 20", $since
    ) );

    // ── Ratings ────────────────────────────────────────────────────────

    $ratings_table = $wpdb->prefix . 'lawnace_ratings';
    $ratings_exist = $wpdb->get_var( "SHOW TABLES LIKE '{$ratings_table}'" ) === $ratings_table;
    $thumbs_up = $thumbs_down = 0;
    if ( $ratings_exist ) {
        $thumbs_up   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$ratings_table} WHERE rating = 1  AND created_at >= %s", $since ) );
        $thumbs_down = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$ratings_table} WHERE rating = -1 AND created_at >= %s", $since ) );
    }
    $total_ratings = $thumbs_up + $thumbs_down;
    $approval_rate = $total_ratings > 0 ? round( ( $thumbs_up / $total_ratings ) * 100 ) : 0;

    // ── Sessions list ──────────────────────────────────────────────────

    $sessions = $wpdb->get_results( $wpdb->prepare(
        "SELECT session_id, MIN(created_at) AS started, COUNT(*) AS messages,
                MAX(CASE WHEN role = 'lead' THEN 1 ELSE 0 END) AS has_lead
         FROM {$table} WHERE created_at >= %s
         GROUP BY session_id ORDER BY started DESC LIMIT 200", $since
    ) );

    $base_url        = home_url( '/ace-dashboard/' );
    $logout_url      = add_query_arg( 'logout', '1', $base_url );
    $cached_insights = get_option( 'lawnace_insights_cache', null );
    $insights_nonce  = wp_create_nonce( 'lawnace_public_insights_nonce' );
    $ajax_url        = admin_url( 'admin-ajax.php' );

    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ace Analytics &mdash; <?php echo esc_html( $site_name ); ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f5f5f7;color:#1d2327;font-size:14px;}

/* ── Header ── */
.la-header{background:#1a2e1a;color:#fff;padding:0 24px;display:flex;align-items:center;justify-content:space-between;height:60px;position:sticky;top:0;z-index:100;box-shadow:0 2px 8px rgba(0,0,0,.3);}
.la-header-brand{display:flex;align-items:center;gap:10px;font-size:17px;font-weight:700;letter-spacing:-.3px;}
.la-header-brand svg{flex-shrink:0;}
.la-header-right{display:flex;align-items:center;gap:16px;}
.la-header-right a{color:#a9c33f;font-size:12px;text-decoration:none;font-weight:600;padding:6px 12px;border:1px solid #a9c33f;border-radius:6px;transition:all .2s;}
.la-header-right a:hover{background:#a9c33f;color:#1a2e1a;}
.la-version{font-size:11px;color:#688;background:#0f1a0f;padding:3px 8px;border-radius:4px;}

/* ── Layout ── */
.la-main{max-width:1140px;margin:0 auto;padding:24px 20px;}

/* ── Date range ── */
.la-range-bar{display:flex;align-items:center;gap:8px;margin-bottom:24px;flex-wrap:wrap;}
.la-range-bar strong{color:#555;font-size:12px;margin-right:4px;}
.la-range-btn{text-decoration:none;padding:5px 14px;border-radius:20px;border:1px solid #d0d0d0;background:#fff;color:#444;font-size:12px;font-weight:600;transition:all .15s;}
.la-range-btn:hover{border-color:#6559b1;color:#6559b1;}
.la-range-btn.active{background:#6559b1;border-color:#6559b1;color:#fff;}
.la-range-label{font-size:12px;color:#888;margin-left:6px;}

/* ── Stat cards ── */
.la-stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px;}
.la-stat{background:#fff;border:1px solid #e8e8e8;border-radius:10px;padding:16px 20px;box-shadow:0 1px 4px rgba(0,0,0,.06);}
.la-stat-num{font-size:30px;font-weight:800;color:#6559b1;line-height:1.1;}
.la-stat-label{font-size:11px;color:#888;margin-top:5px;font-weight:500;text-transform:uppercase;letter-spacing:.4px;}

/* ── Panels ── */
.la-panels{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:20px;}
@media(max-width:720px){.la-panels{grid-template-columns:1fr;}}
.la-panel{background:#fff;border:1px solid #e8e8e8;border-radius:10px;padding:18px 20px;box-shadow:0 1px 4px rgba(0,0,0,.06);}
.la-panel-full{grid-column:1/-1;}
.la-panel h3{margin:0 0 14px;font-size:13px;font-weight:700;color:#1d2327;text-transform:uppercase;letter-spacing:.5px;}
.la-panel-sub{font-size:11px;color:#aaa;margin-top:-10px;margin-bottom:12px;}

/* ── Bar charts ── */
.la-bar-row{display:flex;align-items:center;gap:8px;margin-bottom:8px;font-size:12px;}
.la-bar-label{width:160px;flex-shrink:0;color:#333;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.la-bar-track{flex:1;background:#f0f0f0;border-radius:4px;height:13px;overflow:hidden;}
.la-bar-fill{height:100%;background:#6559b1;border-radius:4px;transition:width .4s;}
.la-bar-fill.green{background:#a9c33f;}
.la-bar-pct{width:36px;text-align:right;color:#888;font-size:11px;}

/* ── Hour chart ── */
.la-hour-grid{display:grid;grid-template-columns:repeat(24,1fr);gap:2px;}
.la-hour-cell{text-align:center;}
.la-hour-bar-wrap{height:48px;display:flex;align-items:flex-end;justify-content:center;}
.la-hour-bar{width:100%;background:#a9c33f;border-radius:2px 2px 0 0;min-height:2px;opacity:.85;}
.la-hour-lbl{font-size:8px;color:#aaa;margin-top:2px;}

/* ── Tables ── */
.la-table{width:100%;border-collapse:collapse;font-size:12px;}
.la-table th{text-align:left;padding:7px 10px;border-bottom:2px solid #eee;color:#888;font-size:11px;text-transform:uppercase;letter-spacing:.4px;font-weight:600;}
.la-table td{padding:8px 10px;border-bottom:1px solid #f5f5f5;color:#333;vertical-align:top;}
.la-table tr:last-child td{border-bottom:none;}
.la-table tr:hover td{background:#fafafa;}
.la-table a{color:#6559b1;text-decoration:none;font-weight:600;}
.la-table a:hover{text-decoration:underline;}
.la-lead-badge{color:#2e7d32;font-weight:700;font-size:11px;}
code{background:#f0f0f0;padding:2px 6px;border-radius:3px;font-size:11px;color:#555;}

/* ── Insights panel ── */
.la-insights-panel{border-left:4px solid #6559b1;}
.la-insights-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;}
.la-insights-meta{font-size:11px;color:#aaa;margin-top:3px;}
.la-insights-btn{padding:8px 18px;background:#6559b1;color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:700;cursor:pointer;transition:background .2s;}
.la-insights-btn:hover{background:#4a3f99;}
.la-insights-btn:disabled{opacity:.6;cursor:not-allowed;}
.la-insights-loading{display:none;color:#6559b1;font-size:13px;padding:10px 0;}
.la-insights-output{font-size:13px;line-height:1.75;color:#1d2327;white-space:pre-wrap;}

/* ── ZIP grid ── */
.la-zip-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;}

/* ── Approval colors ── */
.approval-good{color:#2e7d32;font-weight:800;}
.approval-mid {color:#e65100;font-weight:800;}
.approval-bad {color:#c62828;font-weight:800;}

/* ── Back link ── */
.la-back{display:inline-flex;align-items:center;gap:5px;color:#6559b1;text-decoration:none;font-size:13px;font-weight:600;margin-bottom:18px;}
.la-back:hover{text-decoration:underline;}

/* ── Empty state ── */
.la-empty{color:#aaa;font-size:13px;padding:10px 0;}

/* ── Responsive ── */
@media(max-width:500px){
    .la-hour-grid{grid-template-columns:repeat(12,1fr);}
    .la-hour-lbl{display:none;}
    .la-stat-num{font-size:24px;}
}
</style>
</head>
<body>

<!-- Header -->
<header class="la-header">
    <div class="la-header-brand">
        <svg width="28" height="28" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M10 38 L46 10 L38 48 L26 32 Z" fill="white" fill-opacity=".25" stroke="white" stroke-width="2.5" stroke-linejoin="round"/>
            <path d="M26 32 L38 48 L32 40 Z" fill="white" fill-opacity=".6" stroke="white" stroke-width="2" stroke-linejoin="round"/>
            <line x1="10" y1="38" x2="26" y2="32" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
        🌿 Ace Analytics
    </div>
    <div class="la-header-right">
        <span class="la-version">v<?php echo esc_html( LAWNACE_VERSION ); ?></span>
        <a href="<?php echo esc_url( $logout_url ); ?>">Sign Out</a>
    </div>
</header>

<main class="la-main">

    <!-- Date Range -->
    <div class="la-range-bar">
        <strong>Range:</strong>
        <?php foreach ( [ 'week' => 'Last 7 Days', 'month' => 'Last 30 Days', 'all' => 'All Time' ] as $key => $lbl ) : ?>
        <a href="<?php echo esc_url( add_query_arg( 'range', $key, $base_url ) ); ?>"
           class="la-range-btn <?php echo $range === $key ? 'active' : ''; ?>">
            <?php echo esc_html( $lbl ); ?>
        </a>
        <?php endforeach; ?>
        <span class="la-range-label">Showing: <strong><?php echo esc_html( $label ); ?></strong></span>
    </div>

    <!-- Stat Cards -->
    <div class="la-stat-grid">
        <div class="la-stat">
            <div class="la-stat-num"><?php echo esc_html( $total_sessions ); ?></div>
            <div class="la-stat-label">Chat Sessions</div>
        </div>
        <div class="la-stat">
            <div class="la-stat-num"><?php echo esc_html( $lead_sessions ); ?></div>
            <div class="la-stat-label">Leads Captured</div>
        </div>
        <div class="la-stat">
            <div class="la-stat-num"><?php echo esc_html( $conversion ); ?>%</div>
            <div class="la-stat-label">Conversion Rate</div>
        </div>
        <div class="la-stat">
            <div class="la-stat-num"><?php echo esc_html( $total_messages ); ?></div>
            <div class="la-stat-label">Customer Messages</div>
        </div>
        <div class="la-stat">
            <div class="la-stat-num"><?php echo count( $frustrated ); ?></div>
            <div class="la-stat-label">Drop-off Sessions</div>
        </div>
        <div class="la-stat">
            <div class="la-stat-num <?php echo $total_ratings > 0 ? ( $approval_rate >= 70 ? 'approval-good' : ( $approval_rate >= 40 ? 'approval-mid' : 'approval-bad' ) ) : ''; ?>">
                <?php echo $total_ratings > 0 ? $approval_rate . '%' : '—'; ?>
            </div>
            <div class="la-stat-label">👍 Approval (<?php echo esc_html( $thumbs_up ); ?>↑ <?php echo esc_html( $thumbs_down ); ?>↓)</div>
        </div>
    </div>

    <!-- ZIP + Competitors -->
    <div class="la-panels" style="margin-bottom:20px;">

        <!-- ZIP Codes -->
        <div class="la-panel">
            <h3>📍 ZIP Codes Mentioned</h3>
            <p class="la-panel-sub">Top ZIP codes detected in customer messages</p>
            <?php if ( empty( $zip_counts ) ) : ?>
                <p class="la-empty">No ZIP codes detected yet.</p>
            <?php else : ?>
                <?php $max_zip = max( $zip_counts ) ?: 1; ?>
                <?php foreach ( $zip_counts as $zip => $cnt ) :
                    $pct = round( ( $cnt / $max_zip ) * 100 );
                ?>
                    <div class="la-bar-row">
                        <span class="la-bar-label" style="width:58px;font-weight:700;"><?php echo esc_html( $zip ); ?></span>
                        <div class="la-bar-track"><div class="la-bar-fill green" style="width:<?php echo esc_attr( $pct ); ?>%"></div></div>
                        <span class="la-bar-pct"><?php echo esc_html( $cnt ); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Competitors -->
        <div class="la-panel">
            <h3>🥊 Competitors Mentioned</h3>
            <p class="la-panel-sub">Click any row to see the messages</p>
            <?php if ( empty( $competitor_counts ) ) : ?>
                <p class="la-empty">No competitors detected yet — great sign!</p>
            <?php else : ?>
                <?php $max_comp = max( $competitor_counts ) ?: 1; ?>
                <?php foreach ( $competitor_counts as $comp => $cnt ) :
                    $pct      = round( ( $cnt / $max_comp ) * 100 );
                    $data_key = esc_attr( $comp );
                ?>
                    <div class="la-bar-row la-comp-row" data-comp="<?php echo $data_key; ?>" title="Click to see messages">
                        <span class="la-bar-label" style="color:#d63638;font-weight:700;"><?php echo esc_html( $comp ); ?></span>
                        <div class="la-bar-track"><div class="la-bar-fill" style="width:<?php echo esc_attr( $pct ); ?>%;background:#d63638;"></div></div>
                        <span class="la-bar-pct"><?php echo esc_html( $cnt ); ?> <span style="color:#d63638;font-size:10px;">▶</span></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- Competitor Modal -->
    <div id="la-comp-modal" style="display:none;">
        <div id="la-comp-backdrop"></div>
        <div id="la-comp-drawer">
            <div id="la-comp-header">
                <div>
                    <div id="la-comp-title" style="font-size:16px;font-weight:700;color:#1d2327;"></div>
                    <div id="la-comp-sub" style="font-size:12px;color:#aaa;margin-top:3px;"></div>
                </div>
                <button id="la-comp-close">&times;</button>
            </div>
            <div id="la-comp-body"></div>
        </div>
    </div>

    <style>
    .la-comp-row{cursor:pointer;border-radius:6px;padding:4px 6px;margin:0 -6px;transition:background .15s;}
    .la-comp-row:hover{background:#fff5f5;}
    #la-comp-modal{position:fixed;inset:0;z-index:999;display:flex;align-items:flex-end;justify-content:center;}
    #la-comp-backdrop{position:absolute;inset:0;background:rgba(0,0,0,.45);}
    #la-comp-drawer{position:relative;background:#fff;border-radius:16px 16px 0 0;width:100%;max-width:780px;max-height:75vh;display:flex;flex-direction:column;box-shadow:0 -8px 32px rgba(0,0,0,.2);animation:slideUp .22s ease;}
    @keyframes slideUp{from{transform:translateY(60px);opacity:0}to{transform:translateY(0);opacity:1}}
    #la-comp-header{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #eee;flex-shrink:0;}
    #la-comp-close{background:none;border:none;font-size:24px;cursor:pointer;color:#aaa;line-height:1;padding:0 4px;}
    #la-comp-close:hover{color:#333;}
    #la-comp-body{overflow-y:auto;padding:16px 22px;flex:1;}
    .la-comp-msg{border:1px solid #f0f0f0;border-radius:8px;padding:12px 14px;margin-bottom:10px;background:#fafafa;}
    .la-comp-msg-text{font-size:13px;color:#333;line-height:1.6;margin-bottom:8px;}
    .la-comp-msg-meta{display:flex;align-items:center;justify-content:space-between;font-size:11px;color:#aaa;}
    .la-comp-msg-meta a{color:#6559b1;font-weight:600;text-decoration:none;}
    .la-comp-msg-meta a:hover{text-decoration:underline;}
    </style>

    <script>
    (function(){
        var compData = <?php echo wp_json_encode( $competitor_sessions ); ?>;
        var baseUrl  = <?php echo wp_json_encode( $base_url ); ?>;
        var range    = <?php echo wp_json_encode( $range ); ?>;

        var modal    = document.getElementById('la-comp-modal');
        var backdrop = document.getElementById('la-comp-backdrop');
        var title    = document.getElementById('la-comp-title');
        var sub      = document.getElementById('la-comp-sub');
        var body     = document.getElementById('la-comp-body');
        var closeBtn = document.getElementById('la-comp-close');

        function openModal(comp) {
            var rows = compData[comp] || [];
            title.textContent = '🥊 ' + comp;
            sub.textContent   = rows.length + ' message' + (rows.length !== 1 ? 's' : '') + ' mentioning this competitor';
            body.innerHTML    = '';
            rows.forEach(function(r) {
                var sessionUrl = baseUrl + '?session=' + encodeURIComponent(r.session_id) + '&range=' + range;
                var div = document.createElement('div');
                div.className = 'la-comp-msg';
                div.innerHTML =
                    '<div class="la-comp-msg-text">' + escHtml(r.message) + '</div>' +
                    '<div class="la-comp-msg-meta">' +
                        '<span>' + escHtml(r.created_at) + '</span>' +
                        '<a href="' + escHtml(sessionUrl) + '">View full session →</a>' +
                    '</div>';
                body.appendChild(div);
            });
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        function escHtml(s) {
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        document.querySelectorAll('.la-comp-row').forEach(function(row) {
            row.addEventListener('click', function(){ openModal(this.dataset.comp); });
        });
        closeBtn.addEventListener('click', closeModal);
        backdrop.addEventListener('click', closeModal);
        document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeModal(); });
    })();
    </script>

    <!-- Row 1: Topics + Hours -->
    <div class="la-panels">

        <div class="la-panel">
            <h3>📊 Conversation Topics</h3>
            <?php if ( empty( $topic_counts ) ) : ?>
                <p class="la-empty">No data yet.</p>
            <?php else : ?>
                <?php foreach ( $topic_counts as $topic => $count ) :
                    $pct = round( ( $count / $total_topic_hits ) * 100 );
                ?>
                <div class="la-bar-row">
                    <span class="la-bar-label" title="<?php echo esc_attr( $topic ); ?>"><?php echo esc_html( $topic ); ?></span>
                    <div class="la-bar-track"><div class="la-bar-fill" style="width:<?php echo esc_attr( $pct ); ?>%"></div></div>
                    <span class="la-bar-pct"><?php echo esc_html( $pct ); ?>%</span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="la-panel">
            <h3>🕐 Peak Chat Hours</h3>
            <div class="la-hour-grid">
                <?php for ( $i = 0; $i < 24; $i++ ) :
                    $h   = $hours[ $i ];
                    $pct = round( ( $h / $max_hour ) * 100 );
                    $lbl = $i === 0 ? '12a' : ( $i < 12 ? $i . 'a' : ( $i === 12 ? '12p' : ( $i - 12 ) . 'p' ) );
                ?>
                <div class="la-hour-cell">
                    <div class="la-hour-bar-wrap">
                        <div class="la-hour-bar" style="height:<?php echo max( 2, $pct ); ?>%"
                             title="<?php echo esc_attr( $h . ' messages at ' . $lbl ); ?>"></div>
                    </div>
                    <div class="la-hour-lbl"><?php echo esc_html( $lbl ); ?></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

    </div>

    <!-- Ace Intelligence -->
    <div class="la-panel la-insights-panel" style="margin-bottom:20px;">
        <style>
        .la-iheader{font-size:11px;font-weight:800;color:#6559b1;text-transform:uppercase;letter-spacing:.7px;margin:22px 0 8px;padding-bottom:5px;border-bottom:2px solid #6559b1;}
        .la-iheader:first-child{margin-top:0;}
        .la-iline{font-size:13px;line-height:1.75;color:#1d2327;}
        .la-ibullet{font-size:13px;line-height:1.75;color:#1d2327;padding-left:16px;position:relative;}
        .la-ibullet::before{content:'•';position:absolute;left:4px;color:#a9c33f;font-weight:700;}
        .la-igap{height:5px;}
        </style>
        <div class="la-insights-header">
            <div>
                <h3 style="margin-bottom:4px;">✨ Ace Intelligence</h3>
                <div class="la-insights-meta">
                    <?php if ( $cached_insights ) : ?>
                        Last generated: <?php echo esc_html( $cached_insights['generated'] ); ?> &mdash; based on <?php echo esc_html( $cached_insights['sessions'] ); ?> sessions
                    <?php else : ?>
                        No analysis generated yet.
                    <?php endif; ?>
                </div>
            </div>
            <button id="la-insights-btn" class="la-insights-btn"
                data-nonce="<?php echo esc_attr( $insights_nonce ); ?>"
                data-ajax="<?php echo esc_url( $ajax_url ); ?>">
                <?php echo $cached_insights ? '🔄 Refresh Analysis' : '✨ Generate'; ?>
            </button>
        </div>
        <div id="la-insights-loading" class="la-insights-loading">⏳ Analyzing your chat data — this takes about 15 seconds...</div>
        <div id="la-insights-output"
             data-raw="<?php echo $cached_insights ? esc_attr( $cached_insights['text'] ) : ''; ?>">
        </div>
    </div>

    <!-- Row 2: Leads + Drop-offs -->
    <div class="la-panels">

        <div class="la-panel">
            <h3>🎯 Recent Leads</h3>
            <?php if ( empty( $recent_leads ) ) : ?>
                <p class="la-empty">No leads captured yet.</p>
            <?php else : ?>
                <table class="la-table">
                    <thead><tr><th>Info</th><th>Time</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ( $recent_leads as $lead ) : ?>
                    <tr>
                        <td><?php echo esc_html( $lead->message ); ?></td>
                        <td style="white-space:nowrap;color:#888"><?php echo esc_html( $lead->created_at ); ?></td>
                        <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $lead->session_id, 'range' => $range ], $base_url ) ); ?>">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="la-panel">
            <h3>⚠️ Drop-off Sessions</h3>
            <p class="la-panel-sub">Sessions with 2 or fewer customer messages</p>
            <?php if ( empty( $frustrated ) ) : ?>
                <p class="la-empty">None found.</p>
            <?php else : ?>
                <table class="la-table">
                    <thead><tr><th>Session</th><th>Msgs</th><th>Started</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ( $frustrated as $s ) : ?>
                    <tr>
                        <td><code><?php echo esc_html( substr( $s->session_id, 0, 12 ) ); ?>&hellip;</code></td>
                        <td><?php echo esc_html( $s->msg_count ); ?></td>
                        <td style="color:#888"><?php echo esc_html( $s->started ); ?></td>
                        <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $s->session_id, 'range' => $range ], $base_url ) ); ?>">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>

    <!-- All Sessions -->
    <div class="la-panel" style="margin-bottom:20px;">
        <h3>💬 All Sessions</h3>
        <?php if ( empty( $sessions ) ) : ?>
            <p class="la-empty">No sessions in this period.</p>
        <?php else : ?>
            <table class="la-table">
                <thead><tr>
                    <th>Session</th><th>Started</th><th>Messages</th><th>Lead</th><th></th>
                </tr></thead>
                <tbody>
                <?php foreach ( $sessions as $s ) : ?>
                <tr>
                    <td><code><?php echo esc_html( substr( $s->session_id, 0, 18 ) ); ?>&hellip;</code></td>
                    <td style="color:#888;white-space:nowrap"><?php echo esc_html( $s->started ); ?></td>
                    <td><?php echo esc_html( $s->messages ); ?></td>
                    <td><?php echo $s->has_lead ? '<span class="la-lead-badge">✓ Lead</span>' : '<span style="color:#ccc">—</span>'; ?></td>
                    <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $s->session_id, 'range' => $range ], $base_url ) ); ?>">View</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</main>

<script>
(function(){
    var btn     = document.getElementById('la-insights-btn');
    var loading = document.getElementById('la-insights-loading');
    var output  = document.getElementById('la-insights-output');
    if (!btn) return;

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function formatInsights(text) {
        if (!text) return '';
        function applyBold(s) {
            return escHtml(s).replace(/\*\*(.+?)\*\*/g, '<strong style="color:#1d2327;">$1</strong>');
        }
        var html = '';
        text.split('\n').forEach(function(line) {
            var t = line.trim();
            if (!t) { html += '<div class="la-igap"></div>'; return; }
            if (/^#+(Lawn Ace|Business Intelligence)/i.test(t) || /^---+$/.test(t)) return;
            if (/^#{1,3}\s*\d+\.\s+[A-Z]/.test(t) || /^\d+\.\s+[A-Z][A-Z\s\/&']+$/.test(t)) {
                html += '<div class="la-iheader">' + escHtml(t.replace(/^#+\s*/, '')) + '</div>';
            } else if (/^[-•*]\s/.test(t)) {
                html += '<div class="la-ibullet">' + applyBold(t.replace(/^[-•*]\s+/, '')) + '</div>';
            } else {
                html += '<div class="la-iline">' + applyBold(line) + '</div>';
            }
        });
        return html;
    }

    // Render cached content on load
    var raw = output.dataset.raw;
    if (raw) output.innerHTML = formatInsights(raw);

    btn.addEventListener('click', function(){
        btn.disabled = true;
        btn.textContent = 'Analyzing...';
        loading.style.display = 'block';
        output.innerHTML = '';

        var fd = new FormData();
        fd.append('action', 'lawnace_public_insights');
        fd.append('nonce', btn.dataset.nonce);

        fetch(btn.dataset.ajax, {method:'POST', credentials:'same-origin', body:fd})
        .then(function(r){ return r.json(); })
        .then(function(res){
            loading.style.display = 'none';
            if (res.success && res.data && res.data.text) {
                output.innerHTML = formatInsights(res.data.text);
                btn.textContent = '🔄 Refresh Analysis';
            } else {
                output.innerHTML = '<div class="la-iline" style="color:#c62828;">Error: ' + escHtml(res.data || 'Unknown error.') + '</div>';
                btn.textContent = '✨ Try Again';
            }
            btn.disabled = false;
        })
        .catch(function(){
            loading.style.display = 'none';
            output.innerHTML = '<div class="la-iline" style="color:#c62828;">Request failed. Please try again.</div>';
            btn.textContent = '✨ Try Again';
            btn.disabled = false;
        });
    });
})();
</script>

</body>
</html>
    <?php
}

/*
|--------------------------------------------------------------------------
| PUBLIC SESSION DETAIL
|--------------------------------------------------------------------------
*/

function lawnace_render_public_session_detail( $session_id, $table, $site_name ) {
    global $wpdb;
    $range    = isset( $_GET['range'] ) ? sanitize_text_field( wp_unslash( $_GET['range'] ) ) : 'month';
    $back_url = esc_url( add_query_arg( 'range', $range, remove_query_arg( 'session' ) ) );

    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT role, message, created_at FROM {$table} WHERE session_id = %s ORDER BY created_at ASC",
        $session_id
    ) );
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Session Detail &mdash; <?php echo esc_html( $site_name ); ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f5f5f7;color:#1d2327;font-size:14px;}
.la-header{background:#1a2e1a;color:#fff;padding:0 24px;display:flex;align-items:center;height:60px;}
.la-header-brand{font-size:17px;font-weight:700;}
.la-main{max-width:900px;margin:0 auto;padding:24px 20px;}
.la-back{display:inline-flex;align-items:center;gap:5px;color:#6559b1;text-decoration:none;font-size:13px;font-weight:600;margin-bottom:18px;}
.la-back:hover{text-decoration:underline;}
h1{font-size:20px;margin-bottom:6px;}
.session-id{font-size:12px;color:#aaa;margin-bottom:20px;}
code{background:#f0f0f0;padding:2px 6px;border-radius:3px;font-size:11px;}
.la-table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);}
.la-table th{text-align:left;padding:10px 14px;background:#f8f8f8;border-bottom:2px solid #eee;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:#888;font-weight:600;}
.la-table td{padding:10px 14px;border-bottom:1px solid #f0f0f0;vertical-align:top;font-size:13px;line-height:1.5;}
.la-table tr:last-child td{border-bottom:none;}
.role-user{color:#1d2327;font-weight:700;}
.role-assistant{color:#4a3f99;font-weight:700;}
.role-lead{color:#2e7d32;font-weight:700;}
</style>
</head>
<body>
<header class="la-header">
    <div class="la-header-brand">🌿 Ace Analytics — Session Detail</div>
</header>
<main class="la-main">
    <a class="la-back" href="<?php echo $back_url; ?>">&larr; Back to Dashboard</a>
    <h1>Chat Session</h1>
    <div class="session-id">ID: <code><?php echo esc_html( $session_id ); ?></code></div>
    <table class="la-table">
        <thead><tr>
            <th style="width:80px">Role</th>
            <th>Message</th>
            <th style="width:150px">Time</th>
        </tr></thead>
        <tbody>
        <?php foreach ( $rows as $row ) :
            $role_class = 'role-' . esc_attr( $row->role );
        ?>
        <tr>
            <td><span class="<?php echo $role_class; ?>"><?php echo esc_html( ucfirst( $row->role ) ); ?></span></td>
            <td style="white-space:pre-wrap"><?php echo esc_html( $row->message ); ?></td>
            <td style="color:#aaa;font-size:12px"><?php echo esc_html( $row->created_at ); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</body>
</html>
    <?php
}
