<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
|--------------------------------------------------------------------------
| AUTH HELPERS
|--------------------------------------------------------------------------
*/

function lawnace_client_dash_cookie_value() {
    $pin = get_option( 'lawnace_client_pin', '' );
    if ( empty( $pin ) ) return '';
    return hash( 'sha256', $pin . wp_salt( 'auth' ) . 'lawnace-client-v1' );
}

function lawnace_client_dash_is_authed() {
    $expected = lawnace_client_dash_cookie_value();
    if ( empty( $expected ) ) return false;
    $cookie = isset( $_COOKIE['lawnace_client_auth'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['lawnace_client_auth'] ) ) : '';
    return ! empty( $cookie ) && hash_equals( $expected, $cookie );
}

/*
|--------------------------------------------------------------------------
| REWRITE RULE
|--------------------------------------------------------------------------
*/

add_action( 'init', 'lawnace_register_client_dashboard_rewrite' );

function lawnace_register_client_dashboard_rewrite() {
    add_rewrite_rule( '^la-team/?$', 'index.php?lawnace_client_dashboard=1', 'top' );
}

add_filter( 'query_vars', 'lawnace_client_dashboard_query_vars' );

function lawnace_client_dashboard_query_vars( $vars ) {
    $vars[] = 'lawnace_client_dashboard';
    return $vars;
}

/*
|--------------------------------------------------------------------------
| TEMPLATE REDIRECT
|--------------------------------------------------------------------------
*/

add_action( 'template_redirect', 'lawnace_maybe_render_client_dashboard' );

function lawnace_maybe_render_client_dashboard() {
    if ( ! get_query_var( 'lawnace_client_dashboard' ) ) return;

    $pin = get_option( 'lawnace_client_pin', '' );

    if ( isset( $_GET['logout'] ) ) {
        setcookie( 'lawnace_client_auth', '', [
            'expires'  => time() - 3600,
            'path'     => COOKIEPATH,
            'domain'   => COOKIE_DOMAIN,
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ] );
        wp_redirect( home_url( '/la-team/' ) );
        exit;
    }

    if ( isset( $_POST['lawnace_client_pin'] ) ) {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'lawnace_client_dash_login' ) ) {
            lawnace_render_client_login( 'Invalid request. Please try again.' );
            exit;
        }
        $submitted = sanitize_text_field( wp_unslash( $_POST['lawnace_client_pin'] ) );
        if ( ! empty( $pin ) && hash_equals( $pin, $submitted ) ) {
            setcookie( 'lawnace_client_auth', lawnace_client_dash_cookie_value(), [
                'expires'  => time() + 8 * HOUR_IN_SECONDS,
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ] );
            wp_redirect( home_url( '/la-team/' ) );
            exit;
        } else {
            lawnace_render_client_login( 'Incorrect password. Please try again.' );
            exit;
        }
    }

    if ( empty( $pin ) ) {
        lawnace_render_client_not_configured();
        exit;
    }

    if ( ! lawnace_client_dash_is_authed() ) {
        lawnace_render_client_login( false );
        exit;
    }

    lawnace_render_client_dashboard_page();
    exit;
}

/*
|--------------------------------------------------------------------------
| WEEKLY SUMMARY AJAX
|--------------------------------------------------------------------------
*/

add_action( 'wp_ajax_lawnace_client_weekly_summary',        'lawnace_client_weekly_summary_handler' );
add_action( 'wp_ajax_nopriv_lawnace_client_weekly_summary', 'lawnace_client_weekly_summary_handler' );

function lawnace_client_weekly_summary_handler() {
    check_ajax_referer( 'lawnace_client_summary_nonce', 'nonce' );

    if ( ! lawnace_client_dash_is_authed() ) {
        wp_send_json_error( 'Not authorized.' );
    }

    global $wpdb;
    $table   = $wpdb->prefix . 'lawnace_chat_logs';
    $api_key = get_option( 'lawnace_anthropic_key', '' );

    if ( empty( $api_key ) ) {
        wp_send_json_error( 'API key not configured.' );
    }

    $range = isset( $_POST['range'] ) ? sanitize_text_field( wp_unslash( $_POST['range'] ) ) : 'month';
    switch ( $range ) {
        case 'week': $since = date( 'Y-m-d 00:00:00', strtotime( '-7 days' ) );  $range_label = 'Last 7 Days';  break;
        case 'all':  $since = '2000-01-01 00:00:00';                               $range_label = 'All Time';     break;
        default:     $since = date( 'Y-m-d 00:00:00', strtotime( '-30 days' ) ); $range_label = 'Last 30 Days'; $range = 'month';
    }

    $messages = $wpdb->get_col( $wpdb->prepare(
        "SELECT message FROM {$table} WHERE role = 'user' AND created_at >= %s ORDER BY created_at DESC LIMIT 200",
        $since
    ) );

    $total_sessions = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(DISTINCT session_id) FROM {$table} WHERE created_at >= %s", $since
    ) );
    $lead_sessions = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(DISTINCT session_id) FROM {$table} WHERE role = 'lead' AND created_at >= %s", $since
    ) );

    $zip_counts = [];
    $complaint_count = 0;
    foreach ( $messages as $msg ) {
        if ( preg_match_all( '/\b(\d{5})\b/', $msg, $matches ) ) {
            foreach ( $matches[1] as $zip ) {
                $zip_counts[ $zip ] = ( $zip_counts[ $zip ] ?? 0 ) + 1;
            }
        }
        if ( preg_match( '/rude|complaint|unhappy|not\s+happy|disappointed|problem|issue|didn\'t show|no show/i', $msg ) ) {
            $complaint_count++;
        }
    }
    arsort( $zip_counts );
    $top_zip = ! empty( $zip_counts ) ? array_key_first( $zip_counts ) : null;

    $competitor_counts = [];
    foreach ( $messages as $msg ) {
        foreach ( lawnace_detect_competitors( $msg ) as $name ) {
            $competitor_counts[ $name ] = ( $competitor_counts[ $name ] ?? 0 ) + 1;
        }
    }
    arsort( $competitor_counts );

    $topic_counts = [];
    foreach ( $messages as $msg ) {
        foreach ( lawnace_categorize_message( $msg ) as $t ) {
            $topic_counts[ $t ] = ( $topic_counts[ $t ] ?? 0 ) + 1;
        }
    }
    arsort( $topic_counts );

    $topic_summary = '';
    foreach ( array_slice( $topic_counts, 0, 6, true ) as $topic => $count ) {
        $topic_summary .= "- {$topic}: {$count} mentions\n";
    }

    $comp_summary = '';
    foreach ( $competitor_counts as $name => $count ) {
        $comp_summary .= "- {$name}: mentioned {$count} time(s)\n";
    }
    if ( empty( $comp_summary ) ) $comp_summary = "None mentioned.\n";

    $message_sample = implode( "\n---\n", array_slice( $messages, 0, 100 ) );

    $prompt = "You are writing a chat data brief for the Lawn Ace sales and service team. Lawn Ace is a locally owned lawn care company in Augusta, GA. The report covers: {$range_label}.

DATA:
- Total chat sessions: {$total_sessions}
- Leads captured: {$lead_sessions}
- Complaints or service concerns: {$complaint_count}
" . ( $top_zip ? "- Most active ZIP: {$top_zip}\n" : '' ) . "
TOP TOPICS:
{$topic_summary}
COMPETITORS MENTIONED:
{$comp_summary}
SAMPLE MESSAGES:
---
{$message_sample}
---

Return ONLY a raw JSON object — no markdown, no code fences, no preamble. Exactly these four keys. Values are plain text, no HTML, no bullet points, no em dashes, 1-2 tight sentences each:
{
  \"volume\": \"One sentence on total sessions and leads captured with specific numbers.\",
  \"topics\": \"One sentence on what customers are asking about most. Name the top 2-3 topics.\",
  \"geography\": \"One sentence on where customers are coming from based on ZIP codes or city mentions.\",
  \"flags\": \"One sentence flagging complaints, competitor mentions, or anything the team should act on. If nothing to flag, return an empty string.\"
}

Return ONLY the JSON. No preamble, no explanation.";

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
                'max_tokens' => 600,
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

    $raw    = trim( $body['content'][0]['text'] );
    $clean  = preg_replace( '/^```(?:json)?\s*/i', '', $raw );
    $clean  = preg_replace( '/\s*```$/', '', $clean );
    $parsed = json_decode( trim( $clean ), true );

    if ( ! is_array( $parsed ) ) {
        // Fallback: treat as plain text volume section
        $parsed = [ 'volume' => $raw, 'topics' => '', 'geography' => '', 'flags' => '' ];
    }

    $summary = [
        'sections'  => $parsed,
        'text'      => $raw,
        'generated' => current_time( 'mysql' ),
        'sessions'  => $total_sessions,
        'range'     => $range_label,
    ];

    update_option( 'lawnace_client_summary_cache', $summary );
    wp_send_json_success( $summary );
}

/*
|--------------------------------------------------------------------------
| TASK COMPLETION AJAX
|--------------------------------------------------------------------------
*/

add_action( 'wp_ajax_lawnace_task_toggle',        'lawnace_task_toggle_handler' );
add_action( 'wp_ajax_nopriv_lawnace_task_toggle', 'lawnace_task_toggle_handler' );

function lawnace_task_toggle_handler() {
    check_ajax_referer( 'lawnace_client_summary_nonce', 'nonce' );
    if ( ! lawnace_client_dash_is_authed() ) wp_send_json_error( 'Not authorized.' );

    $session_id = sanitize_text_field( wp_unslash( $_POST['session_id'] ?? '' ) );
    $completed  = (bool) ( $_POST['completed'] ?? false );

    if ( empty( $session_id ) ) wp_send_json_error( 'No session ID.' );

    $done = get_option( 'lawnace_completed_tasks', [] );
    if ( ! is_array( $done ) ) $done = [];

    if ( $completed ) {
        $done[ $session_id ] = current_time( 'mysql' );
    } else {
        unset( $done[ $session_id ] );
    }

    update_option( 'lawnace_completed_tasks', $done );
    wp_send_json_success( [ 'completed' => $completed, 'session_id' => $session_id ] );
}

/*
|--------------------------------------------------------------------------
| LOGIN PAGE
|--------------------------------------------------------------------------
*/

function lawnace_render_client_login( $error = false ) {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lawn Ace Dashboard</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,'Segoe UI',Arial,sans-serif;background:#12103a;min-height:100vh;display:flex;align-items:center;justify-content:center;}
.wrap{width:100%;max-width:380px;padding:20px;}
.card{background:#fff;border-radius:16px;padding:40px 36px;box-shadow:0 24px 64px rgba(0,0,0,.5);}
.logo{text-align:center;margin-bottom:28px;}
.logo-mark{width:52px;height:52px;background:linear-gradient(135deg,#5b4fbd,#8b7de0);border-radius:14px;display:inline-flex;align-items:center;justify-content:center;}
.title{font-size:21px;font-weight:700;color:#12103a;text-align:center;margin-bottom:6px;}
.sub{font-size:13px;color:#999;text-align:center;margin-bottom:28px;}
label{display:block;font-size:11px;font-weight:700;color:#555;margin-bottom:6px;letter-spacing:.6px;text-transform:uppercase;}
input[type=password]{width:100%;padding:12px 16px;border:2px solid #e8e8e8;border-radius:8px;font-size:16px;outline:none;transition:border-color .2s;}
input[type=password]:focus{border-color:#5b4fbd;}
.error{background:#fff0f0;border:1px solid #f5c2c2;color:#c0392b;border-radius:6px;padding:10px 14px;font-size:13px;margin-top:14px;text-align:center;}
.btn{width:100%;margin-top:18px;padding:13px;background:#5b4fbd;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;transition:background .2s;}
.btn:hover{background:#4a3f99;}
.foot{text-align:center;margin-top:20px;font-size:11px;color:#666;}
</style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="logo">
            <div class="logo-mark">
                <img src="<?php echo esc_url( LAWNACE_PLUGIN_URL . 'assets/images/lawnie-icon.png' ); ?>" width="32" height="32" alt="Lawnie" style="border-radius:50%;">
            </div>
        </div>
        <div class="title">Lawn Ace Dashboard</div>
        <div class="sub">Sales &amp; Service Team Portal</div>
        <form method="post" action="<?php echo esc_url( home_url( '/la-team/' ) ); ?>">
            <?php wp_nonce_field( 'lawnace_client_dash_login' ); ?>
            <label for="lawnace_client_pin">Password</label>
            <input type="password" id="lawnace_client_pin" name="lawnace_client_pin"
                   autocomplete="current-password" autofocus required maxlength="40"
                   placeholder="Enter your password">
            <?php if ( $error ) : ?>
                <div class="error"><?php echo esc_html( $error ); ?></div>
            <?php endif; ?>
            <button class="btn" type="submit">Sign In</button>
        </form>
    </div>
    <div class="foot">Lawn Ace &mdash; Augusta &amp; CSRA</div>
</div>
</body>
</html>
    <?php
}

/*
|--------------------------------------------------------------------------
| NOT CONFIGURED
|--------------------------------------------------------------------------
*/

function lawnace_render_client_not_configured() {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard Not Configured</title>
<style>
body{font-family:system-ui,sans-serif;background:#12103a;min-height:100vh;display:flex;align-items:center;justify-content:center;color:#fff;}
.card{background:#1e1a4a;border:1px solid #3b3294;border-radius:12px;padding:40px;max-width:420px;text-align:center;}
h2{margin-bottom:12px;font-size:20px;}
p{color:#aaa;font-size:14px;line-height:1.6;}
</style>
</head>
<body>
<div class="card">
    <h2>Dashboard Not Configured</h2>
    <p>A client dashboard password has not been set yet. Please contact your administrator.</p>
</div>
</body>
</html>
    <?php
}

/*
|--------------------------------------------------------------------------
| MAIN DASHBOARD PAGE
|--------------------------------------------------------------------------
*/

function lawnace_render_task_mini_card( $open, $completed, $total, $base_url, $range ) {
    $tasks_url = esc_url( add_query_arg( [ 'tab' => 'tasks', 'range' => $range ], $base_url ) );
    ?>
    <div class="la-panel la-task-mini" style="margin-top:20px;">
        <h3>Last 30 Days — Tasks</h3>
        <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;">
            <div style="text-align:center;">
                <div style="font-size:26px;font-weight:800;color:<?php echo $open > 0 ? '#d97706' : '#16a34a'; ?>;"><?php echo esc_html( $open ); ?></div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#777;margin-top:2px;">Open</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:26px;font-weight:800;color:#16a34a;"><?php echo esc_html( $completed ); ?></div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#777;margin-top:2px;">Done</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:26px;font-weight:800;color:#555;"><?php echo esc_html( $total ); ?></div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#777;margin-top:2px;">Total</div>
            </div>
            <a href="<?php echo $tasks_url; ?>" style="margin-left:auto;padding:8px 18px;background:#5b4fbd;color:#fff;text-decoration:none;border-radius:8px;font-size:12px;font-weight:700;">
                <?php echo $open > 0 ? 'View ' . esc_html( $open ) . ' Open Tasks &rarr;' : 'View Tasks &rarr;'; ?>
            </a>
        </div>
    </div>
    <?php
}

function lawnace_render_client_dashboard_page() {
    global $wpdb;
    lawnace_chatbot_ensure_log_table();
    $table = $wpdb->prefix . 'lawnace_chat_logs';

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

    // ── Core stats ─────────────────────────────────────────────────────

    $total_sessions = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(DISTINCT session_id) FROM {$table} WHERE created_at >= %s", $since
    ) );
    $lead_sessions = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(DISTINCT session_id) FROM {$table} WHERE role = 'lead' AND created_at >= %s", $since
    ) );
    $conversion = $total_sessions > 0 ? round( ( $lead_sessions / $total_sessions ) * 100, 1 ) : 0;
    $total_messages = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE role = 'user' AND created_at >= %s", $since
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

    // ── User messages ──────────────────────────────────────────────────

    $user_message_rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT session_id, message, created_at FROM {$table} WHERE role = 'user' AND created_at >= %s ORDER BY created_at DESC", $since
    ) );
    $user_messages = wp_list_pluck( $user_message_rows, 'message' );

    // ── Topics ─────────────────────────────────────────────────────────

    $topic_counts = [];
    foreach ( $user_messages as $msg ) {
        foreach ( lawnace_categorize_message( $msg ) as $t ) {
            $topic_counts[ $t ] = ( $topic_counts[ $t ] ?? 0 ) + 1;
        }
    }
    arsort( $topic_counts );
    $total_topic_hits = array_sum( $topic_counts ) ?: 1;

    // ── Competitors ────────────────────────────────────────────────────

    $competitor_counts = [];
    foreach ( $user_message_rows as $row ) {
        foreach ( lawnace_detect_competitors( $row->message ) as $name ) {
            $competitor_counts[ $name ] = ( $competitor_counts[ $name ] ?? 0 ) + 1;
        }
    }
    arsort( $competitor_counts );

    // ── ZIP codes ──────────────────────────────────────────────────────

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

    // ── Service types requested ────────────────────────────────────────

    $service_keywords = [
        'Fertilization'      => ['fertili', 'fert ', 'feeding', 'lawn food'],
        'Weed Control'       => ['weed', 'dandelion', 'crabgrass', 'broadleaf'],
        'Aeration'           => ['aerat', 'core aerat', 'plug aerat'],
        'Mosquito Control'   => ['mosquito', 'mosquitoes', 'bug spray', 'pest control'],
        'Overseeding'        => ['overseed', 'seed', 'grass seed', 'reseed'],
        'Lawn Mowing'        => ['mow', 'mowing', 'cut the grass', 'cutting'],
        'Shrub/Tree Care'    => ['shrub', 'bush', 'tree trim', 'hedge', 'pruning'],
        'Irrigation'         => ['irrigat', 'sprinkler', 'watering system'],
        'Soil/pH Treatment'  => ['soil', 'lime', 'ph ', 'acidic', 'alkaline'],
        'Grub Control'       => ['grub', 'grubs', 'white grub'],
        'Fire Ant Control'   => ['fire ant', 'fire ants', 'ant control', 'ant problem'],
    ];
    $service_counts = [];
    foreach ( $user_messages as $msg ) {
        $msg_lower = strtolower( $msg );
        foreach ( $service_keywords as $service => $keywords ) {
            foreach ( $keywords as $kw ) {
                if ( strpos( $msg_lower, $kw ) !== false ) {
                    $service_counts[ $service ] = ( $service_counts[ $service ] ?? 0 ) + 1;
                    break;
                }
            }
        }
    }
    arsort( $service_counts );

    // ── Quote / pricing requests ───────────────────────────────────────

    $quote_sessions = [];
    $quote_keywords = ['price', 'cost', 'how much', 'quote', 'estimate', 'pricing', 'charge', 'fee', 'rates', 'affordable', 'cheap'];
    foreach ( $user_message_rows as $row ) {
        $msg_lower = strtolower( $row->message );
        foreach ( $quote_keywords as $kw ) {
            if ( strpos( $msg_lower, $kw ) !== false ) {
                $quote_sessions[ $row->session_id ] = true;
                break;
            }
        }
    }
    $quote_count = count( $quote_sessions );

    // ── Time-to-lead ──────────────────────────────────────────────────

    $lead_session_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT DISTINCT session_id FROM {$table} WHERE role = 'lead' AND created_at >= %s", $since
    ) );
    $time_to_lead_vals = [];
    if ( ! empty( $lead_session_ids ) ) {
        $placeholders = implode( ',', array_fill( 0, count( $lead_session_ids ), '%s' ) );
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT session_id, role, created_at FROM {$table}
             WHERE session_id IN ({$placeholders}) AND created_at >= %s
             ORDER BY session_id, created_at",
            array_merge( $lead_session_ids, [ $since ] )
        ) );
        $session_data = [];
        foreach ( $rows as $row ) {
            $session_data[ $row->session_id ][] = $row;
        }
        foreach ( $session_data as $sid => $entries ) {
            $first_user = null;
            $lead_time  = null;
            $user_count = 0;
            foreach ( $entries as $e ) {
                if ( $e->role === 'user' ) {
                    if ( $first_user === null ) $first_user = strtotime( $e->created_at );
                    $user_count++;
                }
                if ( $e->role === 'lead' && $first_user !== null ) {
                    $lead_time = $user_count;
                    break;
                }
            }
            if ( $lead_time !== null ) $time_to_lead_vals[] = $lead_time;
        }
    }
    $avg_time_to_lead = ! empty( $time_to_lead_vals ) ? round( array_sum( $time_to_lead_vals ) / count( $time_to_lead_vals ), 1 ) : null;

    // ── Leads ──────────────────────────────────────────────────────────

    $recent_leads = $wpdb->get_results( $wpdb->prepare(
        "SELECT session_id, message, created_at FROM {$table}
         WHERE role = 'lead' AND created_at >= %s
         ORDER BY created_at DESC LIMIT 50", $since
    ) );

    // ── Sessions ───────────────────────────────────────────────────────

    $sessions = $wpdb->get_results( $wpdb->prepare(
        "SELECT session_id, MIN(created_at) AS started, COUNT(*) AS messages,
                MAX(CASE WHEN role = 'lead' THEN 1 ELSE 0 END) AS has_lead
         FROM {$table} WHERE created_at >= %s
         GROUP BY session_id ORDER BY started DESC LIMIT 200", $since
    ) );

    // ── Drop-offs ──────────────────────────────────────────────────────

    $dropoffs = $wpdb->get_results( $wpdb->prepare(
        "SELECT session_id, COUNT(*) AS msg_count, MIN(created_at) AS started
         FROM {$table} WHERE role = 'user' AND created_at >= %s
         GROUP BY session_id HAVING msg_count <= 2
         ORDER BY started DESC LIMIT 20", $since
    ) );

    // ── Complaints ─────────────────────────────────────────────────────
    // Note: %% in LIKE patterns — wpdb->prepare() treats single % as printf specifier

    $complaint_sessions = $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT session_id, MIN(created_at) AS started
         FROM {$table}
         WHERE role = 'user' AND created_at >= %s
         AND (message LIKE '%%rude%%' OR message LIKE '%%complaint%%' OR message LIKE '%%unhappy%%'
              OR message LIKE '%%not happy%%' OR message LIKE '%%disappointed%%'
              OR message LIKE '%%didn''t show%%' OR message LIKE '%%no show%%')
         GROUP BY session_id ORDER BY started DESC LIMIT 20", $since
    ) ) ?: [];

    // ── No-show mentions ───────────────────────────────────────────────

    $noshow_sessions = $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT session_id, MIN(created_at) AS started
         FROM {$table}
         WHERE role = 'user' AND created_at >= %s
         AND (message LIKE '%%didn''t show%%' OR message LIKE '%%no show%%'
              OR message LIKE '%%never came%%' OR message LIKE '%%never showed%%'
              OR message LIKE '%%missed appointment%%' OR message LIKE '%%no one came%%'
              OR message LIKE '%%did not show%%' OR message LIKE '%%not show up%%')
         GROUP BY session_id ORDER BY started DESC LIMIT 20", $since
    ) ) ?: [];

    // ── Cancellation intent ────────────────────────────────────────────

    $cancel_sessions = $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT session_id, MIN(created_at) AS started
         FROM {$table}
         WHERE role = 'user' AND created_at >= %s
         AND (message LIKE '%%cancel%%' OR message LIKE '%%stop service%%'
              OR message LIKE '%%not renewing%%' OR message LIKE '%%discontinue%%'
              OR message LIKE '%%switch%%' OR message LIKE '%%going with someone else%%'
              OR message LIKE '%%ending my%%' OR message LIKE '%%quit%%')
         GROUP BY session_id ORDER BY started DESC LIMIT 20", $since
    ) ) ?: [];

    // ── Unanswered / escalated ─────────────────────────────────────────

    $clover_unsure = $wpdb->get_results( $wpdb->prepare(
        "SELECT session_id, message, created_at FROM {$table}
         WHERE role = 'assistant' AND created_at >= %s
         AND (message LIKE '%%not sure%%' OR message LIKE '%%don''t know%%'
              OR message LIKE '%%can''t answer%%' OR message LIKE '%%give us a call%%'
              OR message LIKE '%%706-364-2338%%' OR message LIKE '%%reach out to our team%%'
              OR message LIKE '%%best to call%%')
         ORDER BY created_at DESC LIMIT 20", $since
    ) ) ?: [];

    // ── Upsell signals ─────────────────────────────────────────────────

    $upsell_sessions = $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT session_id, MIN(created_at) AS started
         FROM {$table} WHERE role = 'user' AND created_at >= %s
         AND (message LIKE '%%aerat%%' OR message LIKE '%%seed%%' OR message LIKE '%%overseed%%'
              OR message LIKE '%%pest control%%' OR message LIKE '%%mosquito%%'
              OR message LIKE '%%flea%%' OR message LIKE '%%tick%%'
              OR message LIKE '%%fire ant%%' OR message LIKE '%%grub%%'
              OR message LIKE '%%tree%%' OR message LIKE '%%shrub%%'
              OR message LIKE '%%mulch%%' OR message LIKE '%%irrigation%%')
         GROUP BY session_id ORDER BY started DESC LIMIT 30", $since
    ) ) ?: [];

    // ── Service area misses ────────────────────────────────────────────

    $area_miss_sessions = $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT session_id, MIN(created_at) AS started, message
         FROM {$table} WHERE role = 'user' AND created_at >= %s
         AND (message LIKE '%%do you service%%' OR message LIKE '%%do you cover%%'
              OR message LIKE '%%do you come to%%' OR message LIKE '%%are you in%%'
              OR message LIKE '%%available in%%' OR message LIKE '%%come to%%'
              OR message LIKE '%%serve my area%%' OR message LIKE '%%my zip%%')
         GROUP BY session_id ORDER BY started DESC LIMIT 20", $since
    ) ) ?: [];

    // ── Seasonal treatment demand ──────────────────────────────────────

    $seasonal_counts = [];
    $seasonal_keywords = [
        'Pre-Emergent'    => ['pre-emergent','pre emergent','prevent weeds','weed prevention'],
        'Grub Control'    => ['grub','grubs','grub control','white grub'],
        'Fire Ant'        => ['fire ant','fire ants','ant mound','ant treatment'],
        'Overseeding'     => ['overseed','over seed','overseeding','seed my lawn','lawn seed'],
        'Mosquito'        => ['mosquito','mosquitoes','mosquito treatment','mosquito control'],
        'Aeration'        => ['aerati','core aeration','plug aeration'],
        'Winterization'   => ['winterize','winterization','dormant','fall treatment'],
        'Fungus/Disease'  => ['fungus','disease','brown patch','dollar spot','rust'],
    ];
    foreach ( $user_message_rows as $row ) {
        $msg_lower = strtolower( $row->message );
        foreach ( $seasonal_keywords as $label => $terms ) {
            foreach ( $terms as $term ) {
                if ( strpos( $msg_lower, $term ) !== false ) {
                    $seasonal_counts[ $label ] = ( $seasonal_counts[ $label ] ?? 0 ) + 1;
                    break;
                }
            }
        }
    }
    arsort( $seasonal_counts );

    // ── Competitor mentions ────────────────────────────────────────────
    // (already exists as $competitor_counts — skip, it's on Insights tab)

    // ── Returning customer inquiries ───────────────────────────────────

    $returning_sessions = $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT session_id, MIN(created_at) AS started
         FROM {$table} WHERE role = 'user' AND created_at >= %s
         AND (message LIKE '%%current customer%%' OR message LIKE '%%already use%%'
              OR message LIKE '%%already a customer%%' OR message LIKE '%%my account%%'
              OR message LIKE '%%my service%%' OR message LIKE '%%my yard%%' AND message LIKE '%%already%%'
              OR message LIKE '%%existing customer%%' OR message LIKE '%%been a customer%%'
              OR message LIKE '%%you guys already%%' OR message LIKE '%%you already service%%')
         GROUP BY session_id ORDER BY started DESC LIMIT 20", $since
    ) ) ?: [];

    // ── Re-treatment requests ──────────────────────────────────────────

    $retreatment_sessions = $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT session_id, MIN(created_at) AS started
         FROM {$table} WHERE role = 'user' AND created_at >= %s
         AND (message LIKE '%%re-treat%%' OR message LIKE '%%retreatment%%' OR message LIKE '%%re treat%%'
              OR message LIKE '%%still have weeds%%' OR message LIKE '%%weeds came back%%'
              OR message LIKE '%%didn''t work%%' OR message LIKE '%%not working%%'
              OR message LIKE '%%still seeing%%' OR message LIKE '%%treatment didn%%'
              OR message LIKE '%%come back out%%' OR message LIKE '%%redo%%'
              OR message LIKE '%%results aren%%' OR message LIKE '%%no improvement%%')
         GROUP BY session_id ORDER BY started DESC LIMIT 20", $since
    ) ) ?: [];

    // ── Pet / child safety questions ───────────────────────────────────

    $safety_sessions = $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT session_id, MIN(created_at) AS started
         FROM {$table} WHERE role = 'user' AND created_at >= %s
         AND (message LIKE '%%pet%%' OR message LIKE '%%dog%%' OR message LIKE '%%cat%%'
              OR message LIKE '%%child%%' OR message LIKE '%%kid%%' OR message LIKE '%%baby%%'
              OR message LIKE '%%toddler%%' OR message LIKE '%%safe%%')
         GROUP BY session_id ORDER BY started DESC LIMIT 20", $since
    ) ) ?: [];

    // ── Billing / invoice questions ────────────────────────────────────

    $billing_sessions = $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT session_id, MIN(created_at) AS started
         FROM {$table} WHERE role = 'user' AND created_at >= %s
         AND (message LIKE '%%invoice%%' OR message LIKE '%%bill%%' OR message LIKE '%%charge%%'
              OR message LIKE '%%payment%%' OR message LIKE '%%double charge%%' OR message LIKE '%%overcharged%%'
              OR message LIKE '%%my card%%' OR message LIKE '%%owe%%' OR message LIKE '%%receipt%%'
              OR message LIKE '%%how much do i owe%%' OR message LIKE '%%refund%%')
         GROUP BY session_id ORDER BY started DESC LIMIT 20", $since
    ) ) ?: [];

    // ── Weather holds / reschedule requests ───────────────────────────

    $weather_sessions = $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT session_id, MIN(created_at) AS started
         FROM {$table} WHERE role = 'user' AND created_at >= %s
         AND (message LIKE '%%rain%%' OR message LIKE '%%weather%%' OR message LIKE '%%reschedule%%'
              OR message LIKE '%%postpone%%' OR message LIKE '%%hold off%%' OR message LIKE '%%delay%%'
              OR message LIKE '%%too wet%%' OR message LIKE '%%storm%%')
         GROUP BY session_id ORDER BY started DESC LIMIT 20", $since
    ) ) ?: [];

    // ── Account changes ────────────────────────────────────────────────

    $account_change_sessions = $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT session_id, MIN(created_at) AS started
         FROM {$table} WHERE role = 'user' AND created_at >= %s
         AND (message LIKE '%%pause%%' OR message LIKE '%%put on hold%%' OR message LIKE '%%skip%%'
              OR message LIKE '%%change my plan%%' OR message LIKE '%%upgrade%%' OR message LIKE '%%downgrade%%'
              OR message LIKE '%%switch my%%' OR message LIKE '%%change frequency%%'
              OR message LIKE '%%new address%%' OR message LIKE '%%moved%%' OR message LIKE '%%new home%%')
         GROUP BY session_id ORDER BY started DESC LIMIT 20", $since
    ) ) ?: [];

    // ── Sentiment trend (frustrated vs positive) ───────────────────────

    $frustrated_sessions = [];
    $positive_sessions   = [];
    $frustrated_keywords = ['frustrated', 'angry', 'annoyed', 'terrible', 'awful', 'worst', 'hate', 'ridiculous', 'unacceptable', 'disgusted', 'furious'];
    $positive_keywords   = ['great', 'awesome', 'love', 'excellent', 'amazing', 'thank you', 'thanks', 'perfect', 'happy', 'pleased', 'impressed', 'wonderful'];
    foreach ( $user_message_rows as $row ) {
        $msg_lower = strtolower( $row->message );
        foreach ( $frustrated_keywords as $kw ) {
            if ( strpos( $msg_lower, $kw ) !== false ) { $frustrated_sessions[ $row->session_id ] = true; break; }
        }
        foreach ( $positive_keywords as $kw ) {
            if ( strpos( $msg_lower, $kw ) !== false ) { $positive_sessions[ $row->session_id ] = true; break; }
        }
    }
    $frustrated_count = count( $frustrated_sessions );
    $positive_count   = count( $positive_sessions );

    // ── Topic trend: this period vs previous equal period ──────────────

    $period_days = ( $range === 'week' ) ? 7 : ( $range === 'all' ? 0 : 30 );
    $prev_topic_counts = [];
    if ( $period_days > 0 ) {
        $prev_since = date( 'Y-m-d 00:00:00', strtotime( "-{$period_days} days", strtotime( $since ) ) );
        $prev_msgs  = $wpdb->get_col( $wpdb->prepare(
            "SELECT message FROM {$table} WHERE role = 'user' AND created_at >= %s AND created_at < %s",
            $prev_since, $since
        ) );
        foreach ( $prev_msgs as $msg ) {
            foreach ( lawnace_categorize_message( $msg ) as $t ) {
                $prev_topic_counts[ $t ] = ( $prev_topic_counts[ $t ] ?? 0 ) + 1;
            }
        }
    }

    // ── Tasks – last 30 days ───────────────────────────────────────────

    $yesterday_start = date( 'Y-m-d 00:00:00', strtotime( '-30 days' ) );
    $yesterday_end   = date( 'Y-m-d 23:59:59' ); // through right now

    $completed_tasks = get_option( 'lawnace_completed_tasks', [] );
    if ( ! is_array( $completed_tasks ) ) $completed_tasks = [];

    // Sales tasks: sessions from yesterday that captured a lead or asked about pricing
    $yesterday_leads = $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT session_id, MIN(created_at) AS started
         FROM {$table} WHERE role = 'lead' AND created_at >= %s AND created_at <= %s
         GROUP BY session_id ORDER BY started DESC",
        $yesterday_start, $yesterday_end
    ) ) ?: [];

    $yesterday_pricing_sessions = [];
    $pricing_rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT session_id, MIN(created_at) AS started
         FROM {$table} WHERE role = 'user' AND created_at >= %s AND created_at <= %s
         AND (message LIKE '%%price%%' OR message LIKE '%%cost%%' OR message LIKE '%%how much%%'
              OR message LIKE '%%quote%%' OR message LIKE '%%estimate%%')
         GROUP BY session_id ORDER BY started DESC",
        $yesterday_start, $yesterday_end
    ) ) ?: [];
    // Remove sessions already captured as leads
    $lead_sids = array_column( $yesterday_leads, 'session_id' );
    foreach ( $pricing_rows as $r ) {
        if ( ! in_array( $r->session_id, $lead_sids, true ) ) {
            $yesterday_pricing_sessions[] = $r;
        }
    }

    // Service tasks: complaints, no-shows, cancellations, drop-offs from yesterday
    $yesterday_service_tasks = $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT session_id, MIN(created_at) AS started
         FROM {$table} WHERE role = 'user' AND created_at >= %s AND created_at <= %s
         AND (message LIKE '%%complaint%%' OR message LIKE '%%rude%%' OR message LIKE '%%unhappy%%'
              OR message LIKE '%%cancel%%' OR message LIKE '%%no show%%' OR message LIKE '%%didn''t show%%'
              OR message LIKE '%%never came%%' OR message LIKE '%%stop service%%')
         GROUP BY session_id ORDER BY started DESC",
        $yesterday_start, $yesterday_end
    ) ) ?: [];

    $yesterday_dropoffs = $wpdb->get_results( $wpdb->prepare(
        "SELECT session_id, COUNT(*) AS msg_count, MIN(created_at) AS started
         FROM {$table} WHERE role = 'user' AND created_at >= %s AND created_at <= %s
         GROUP BY session_id HAVING msg_count <= 2 ORDER BY started DESC",
        $yesterday_start, $yesterday_end
    ) ) ?: [];
    // Remove overlap with service tasks
    $service_sids = array_column( $yesterday_service_tasks, 'session_id' );
    $yesterday_dropoffs = array_filter( $yesterday_dropoffs, function( $r ) use ( $service_sids ) {
        return ! in_array( $r->session_id, $service_sids, true );
    } );

    // Task counts for summary cards
    $all_task_sessions = array_merge(
        array_column( $yesterday_leads, 'session_id' ),
        array_column( $yesterday_pricing_sessions, 'session_id' ),
        array_column( $yesterday_service_tasks, 'session_id' ),
        array_column( array_values( $yesterday_dropoffs ), 'session_id' )
    );
    $total_tasks    = count( $all_task_sessions );
    $completed_count = count( array_intersect( $all_task_sessions, array_keys( $completed_tasks ) ) );
    $open_count     = $total_tasks - $completed_count;

    $base_url       = home_url( '/la-team/' );
    $active_tab     = in_array( $_GET['tab'] ?? '', [ 'tasks', 'service', 'insights' ], true ) ? $_GET['tab'] : 'sales';
    $logout_url     = add_query_arg( 'logout', '1', $base_url );
    $summary_nonce  = wp_create_nonce( 'lawnace_client_summary_nonce' );
    $ajax_url       = admin_url( 'admin-ajax.php' );
    $cached_summary = get_option( 'lawnace_client_summary_cache', null );

    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lawn Ace Dashboard</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,'Segoe UI',Arial,sans-serif;background:#e8e9ef;color:#1a1a2e;font-size:14px;}

/* Header */
.la-header{background:linear-gradient(135deg,#12103a,#3b3294,#5b4fbd);color:#fff;padding:0 28px;display:flex;align-items:center;justify-content:space-between;height:62px;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(18,16,58,.4);}
.la-brand{display:flex;align-items:center;gap:10px;font-size:17px;font-weight:700;letter-spacing:-.2px;}
.la-header-right{display:flex;align-items:center;gap:16px;}
.la-header-right a{color:rgba(255,255,255,.75);font-size:12px;text-decoration:none;font-weight:600;padding:6px 14px;border:1px solid rgba(255,255,255,.3);border-radius:6px;transition:all .2s;}
.la-header-right a:hover{background:rgba(255,255,255,.15);color:#fff;}
.la-version{font-size:11px;color:rgba(255,255,255,.4);background:rgba(0,0,0,.2);padding:3px 8px;border-radius:4px;}

/* Layout */
.la-main{max-width:1160px;margin:0 auto;padding:28px 20px;}

/* Tabs */
.la-tabs{display:flex;gap:4px;margin-bottom:24px;background:#fff;padding:5px;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.08);width:fit-content;}
.la-tab{display:block;padding:9px 24px;border-radius:8px;border:none;background:none;font-size:13px;font-weight:600;color:#888;cursor:pointer;transition:all .2s;text-decoration:none;}
.la-tab:hover{color:#5b4fbd;}
.la-tab.active{background:#5b4fbd;color:#fff;box-shadow:0 2px 8px rgba(91,79,189,.35);}
.la-tab-panel{display:none !important;}
.la-tab-panel.active{display:block !important;}

/* Range bar */
.la-range-bar{display:flex;align-items:center;gap:8px;margin-bottom:22px;flex-wrap:wrap;}
.la-range-bar strong{color:#666;font-size:12px;margin-right:4px;}
.la-range-btn{text-decoration:none;padding:5px 14px;border-radius:20px;border:1px solid #d8d8e8;background:#fff;color:#555;font-size:12px;font-weight:600;transition:all .15s;}
.la-range-btn:hover{border-color:#5b4fbd;color:#5b4fbd;}
.la-range-btn.active{background:#5b4fbd;border-color:#5b4fbd;color:#fff;}
.la-range-label{font-size:12px;color:#999;margin-left:4px;}

/* Stat grid */
.la-stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:22px;}
.la-stat{background:#fff;border:1px solid #cccce0;border-radius:10px;padding:18px 20px;box-shadow:0 2px 8px rgba(0,0,0,.08);}
.la-stat-num{font-size:30px;font-weight:800;color:#5b4fbd;line-height:1.1;}
.la-stat-num.green{color:#16a34a;}
.la-stat-num.amber{color:#d97706;}
.la-stat-num.red{color:#dc2626;}
.la-stat-label{font-size:11px;color:#555;margin-top:5px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;}

/* Panels */
.la-panels{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:20px;}
@media(max-width:720px){.la-panels{grid-template-columns:1fr;}}
.la-panel{background:#fff;border:1px solid #cccce0;border-radius:10px;padding:20px 22px;box-shadow:0 2px 8px rgba(0,0,0,.08);}
.la-panel h3{margin:0 0 6px;font-size:13px;font-weight:800;color:#12103a;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #5b4fbd;padding-bottom:8px;}
.la-panel-sub{font-size:11px;color:#777;margin-top:6px;margin-bottom:14px;}

/* Bar chart */
.la-bar-row{display:flex;align-items:center;gap:8px;margin-bottom:8px;font-size:12px;}
.la-bar-label{width:170px;flex-shrink:0;color:#333;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.la-bar-track{flex:1;background:#f0f0f8;border-radius:4px;height:13px;overflow:hidden;}
.la-bar-fill{height:100%;background:#5b4fbd;border-radius:4px;}
.la-bar-fill.green{background:#16a34a;}
.la-bar-fill.red{background:#dc2626;}
.la-bar-pct{width:36px;text-align:right;color:#999;font-size:11px;}

/* Table */
.la-table{width:100%;border-collapse:collapse;font-size:12px;}
.la-table th{text-align:left;padding:7px 10px;border-bottom:2px solid #dddded;color:#444;font-size:11px;text-transform:uppercase;letter-spacing:.4px;font-weight:700;}
.la-table td{padding:8px 10px;border-bottom:1px solid #f5f5fa;color:#333;vertical-align:top;}
.la-table tr:last-child td{border-bottom:none;}
.la-table tr:hover td{background:#fafafe;}
.la-table a{color:#5b4fbd;text-decoration:none;font-weight:600;}
.la-table a:hover{text-decoration:underline;}
.la-lead-badge{color:#16a34a;font-weight:700;font-size:11px;}
.la-flag-badge{color:#dc2626;font-weight:700;font-size:11px;}
code{background:#f0f0f8;padding:2px 6px;border-radius:3px;font-size:11px;color:#666;}

/* Alert panel */
.la-alert-panel{border-left:4px solid #dc2626;}

/* Summary panel */
.la-summary-panel{border-left:4px solid #5b4fbd;}
.la-summary-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;}
.la-summary-meta{font-size:11px;color:#bbb;margin-top:3px;}
.la-summary-btn{padding:9px 20px;background:#5b4fbd;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;transition:background .2s;}
.la-summary-btn:hover{background:#4a3f99;}
.la-summary-btn:disabled{opacity:.6;cursor:not-allowed;}
.la-summary-text{font-size:14px;line-height:1.8;color:#1a1a2e;background:#f8f8fc;border-radius:8px;padding:18px 20px;}
.la-brief-row{display:flex;align-items:baseline;gap:14px;padding:10px 0;border-bottom:1px solid #ebebf5;}
.la-brief-row:last-child{border-bottom:none;}
.la-brief-label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:#5b4fbd;min-width:80px;flex-shrink:0;}
.la-brief-text{font-size:13px;color:#1a1a2e;line-height:1.6;}
.la-brief-flag .la-brief-label{color:#dc2626;}
.la-brief-flag .la-brief-text{font-weight:600;}

/* Empty */
.la-empty{color:#bbb;font-size:13px;padding:10px 0;}

/* Tasks tab */
.la-tab-badge{display:inline-block;background:#dc2626;color:#fff;font-size:10px;font-weight:800;border-radius:10px;padding:1px 6px;margin-left:5px;vertical-align:middle;line-height:1.4;}
.la-task-group{margin-bottom:18px;}
.la-task-group h3{font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:#5b4fbd;margin:0 0 10px;}
.la-task-row{display:flex;align-items:center;gap:12px;padding:10px 14px;background:#f9f9ff;border:1px solid #e0ddf5;border-radius:8px;margin-bottom:6px;transition:background .15s;}
.la-task-row:hover{background:#f0eeff;}
.la-task-done{opacity:.6;}
.la-task-check{flex-shrink:0;display:flex;align-items:center;cursor:pointer;}
.la-task-check input[type=checkbox]{width:18px;height:18px;accent-color:#5b4fbd;cursor:pointer;}
.la-task-info{flex:1;min-width:0;}
.la-task-type{display:block;font-size:12px;font-weight:700;color:#333;}
.la-task-type-sales{color:#16a34a;}
.la-task-type-service{color:#d97706;}
.la-task-time{display:block;font-size:11px;color:#888;margin-top:2px;}
.la-task-view{flex-shrink:0;font-size:12px;font-weight:700;color:#5b4fbd;text-decoration:none;white-space:nowrap;}
.la-task-view:hover{text-decoration:underline;}
.la-task-done .la-task-type,.la-task-done .la-task-time{text-decoration:line-through;color:#aaa;}
.la-task-done-stamp{flex-shrink:0;font-size:10px;color:#16a34a;font-weight:700;white-space:nowrap;}
.la-task-mini h3{font-size:14px;font-weight:800;color:#1a1a2e;margin:0 0 12px;padding-bottom:8px;border-bottom:2px solid #5b4fbd;}
</style>
</head>
<body>

<header class="la-header">
    <div class="la-brand">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="-42 -42 84 84" width="26" height="26">
            <g fill="white" transform="rotate(45)">
                <path d="M0,3 C-3,-8 -17,-19 -17,-29 C-17,-38 -7,-43 0,-36 C7,-43 17,-38 17,-29 C17,-19 3,-8 0,3Z"/>
                <path d="M0,3 C-3,-8 -17,-19 -17,-29 C-17,-38 -7,-43 0,-36 C7,-43 17,-38 17,-29 C17,-19 3,-8 0,3Z" transform="rotate(90)"/>
                <path d="M0,3 C-3,-8 -17,-19 -17,-29 C-17,-38 -7,-43 0,-36 C7,-43 17,-38 17,-29 C17,-19 3,-8 0,3Z" transform="rotate(180)"/>
                <path d="M0,3 C-3,-8 -17,-19 -17,-29 C-17,-38 -7,-43 0,-36 C7,-43 17,-38 17,-29 C17,-19 3,-8 0,3Z" transform="rotate(270)"/>
            </g>
        </svg>
        Lawn Ace Dashboard
    </div>
    <div class="la-header-right">
        <span class="la-version">v<?php echo esc_html( LAWNACE_VERSION ); ?></span>
        <a href="<?php echo esc_url( $logout_url ); ?>">Sign Out</a>
    </div>
</header>

<main class="la-main">

    <!-- Tabs -->
    <div class="la-tabs">
        <a class="la-tab <?php echo $active_tab === 'tasks'    ? 'active' : ''; ?>" href="<?php echo esc_url( add_query_arg( [ 'tab' => 'tasks',    'range' => $range ], $base_url ) ); ?>">
            Tasks<?php if ( $open_count > 0 ) : ?><span class="la-tab-badge"><?php echo esc_html( $open_count ); ?></span><?php endif; ?>
        </a>
        <a class="la-tab <?php echo $active_tab === 'sales'    ? 'active' : ''; ?>" href="<?php echo esc_url( add_query_arg( [ 'tab' => 'sales',    'range' => $range ], $base_url ) ); ?>">Sales</a>
        <a class="la-tab <?php echo $active_tab === 'service'  ? 'active' : ''; ?>" href="<?php echo esc_url( add_query_arg( [ 'tab' => 'service',  'range' => $range ], $base_url ) ); ?>">Service</a>
        <a class="la-tab <?php echo $active_tab === 'insights' ? 'active' : ''; ?>" href="<?php echo esc_url( add_query_arg( [ 'tab' => 'insights', 'range' => $range ], $base_url ) ); ?>">Insights</a>
    </div>

    <!-- Range bar (shared) -->
    <div class="la-range-bar">
        <strong>Range:</strong>
        <?php foreach ( [ 'week' => 'Last 7 Days', 'month' => 'Last 30 Days', 'all' => 'All Time' ] as $key => $lbl ) : ?>
        <a href="<?php echo esc_url( add_query_arg( [ 'tab' => $active_tab, 'range' => $key ], $base_url ) ); ?>"
           class="la-range-btn <?php echo $range === $key ? 'active' : ''; ?>">
            <?php echo esc_html( $lbl ); ?>
        </a>
        <?php endforeach; ?>
        <span class="la-range-label">Showing: <strong><?php echo esc_html( $label ); ?></strong></span>
    </div>

    <!-- ================================================================
         TASKS TAB
    ================================================================ -->
    <div id="tab-tasks" class="la-tab-panel <?php echo $active_tab === 'tasks' ? 'active' : ''; ?>">

        <!-- Task summary stats -->
        <div class="la-stat-grid" style="margin-bottom:24px;">
            <div class="la-stat">
                <div class="la-stat-num"><?php echo esc_html( $total_tasks ); ?></div>
                <div class="la-stat-label">Last 30 Days — Tasks</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num <?php echo $open_count > 0 ? 'amber' : 'green'; ?>"><?php echo esc_html( $open_count ); ?></div>
                <div class="la-stat-label">Open</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num green"><?php echo esc_html( $completed_count ); ?></div>
                <div class="la-stat-label">Completed</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num <?php echo $total_tasks > 0 ? ( round( ($completed_count/$total_tasks)*100 ) >= 80 ? 'green' : 'amber' ) : ''; ?>">
                    <?php echo $total_tasks > 0 ? round( ($completed_count/$total_tasks)*100 ) . '%' : 'N/A'; ?>
                </div>
                <div class="la-stat-label">Completion Rate</div>
            </div>
        </div>

        <?php
        $task_nonce = wp_create_nonce( 'lawnace_client_summary_nonce' );

        // Helper to render a task group
        function lawnace_render_task_group( $title, $type, $rows, $completed_tasks, $base_url, $range, $task_nonce ) {
            if ( empty( $rows ) ) return;
            echo '<div class="la-panel la-task-group" style="margin-bottom:18px;">';
            echo '<h3>' . esc_html( $title ) . '</h3>';
            foreach ( $rows as $row ) {
                $sid  = $row->session_id;
                $done = isset( $completed_tasks[ $sid ] );
                $view_url = esc_url( add_query_arg( [ 'session' => $sid, 'tab' => 'tasks', 'range' => $range ], home_url( '/la-team/' ) ) );
                echo '<div class="la-task-row' . ( $done ? ' la-task-done' : '' ) . '" data-sid="' . esc_attr( $sid ) . '">';
                echo '<label class="la-task-check"><input type="checkbox" class="la-task-cb"' . ( $done ? ' checked' : '' ) . ' data-sid="' . esc_attr( $sid ) . '" data-nonce="' . esc_attr( $task_nonce ) . '"><span class="la-checkmark"></span></label>';
                echo '<div class="la-task-info">';
                echo '<span class="la-task-type la-task-type-' . esc_attr( $type ) . '">' . esc_html( $title ) . '</span>';
                echo '<span class="la-task-time">' . esc_html( $row->started ) . '</span>';
                echo '</div>';
                echo '<a class="la-task-view" href="' . $view_url . '">View Chat &rarr;</a>';
                if ( $done && isset( $completed_tasks[ $sid ] ) ) {
                    echo '<span class="la-task-done-stamp">Done ' . esc_html( substr( $completed_tasks[ $sid ], 0, 16 ) ) . '</span>';
                }
                echo '</div>';
            }
            echo '</div>';
        }
        ?>

        <!-- Sales tasks -->
        <?php lawnace_render_task_group( 'New Lead — Follow Up', 'sales', $yesterday_leads, $completed_tasks, $base_url, $range, $task_nonce ); ?>
        <?php lawnace_render_task_group( 'Pricing Question — Follow Up', 'sales', $yesterday_pricing_sessions, $completed_tasks, $base_url, $range, $task_nonce ); ?>

        <!-- Service tasks -->
        <?php lawnace_render_task_group( 'Complaint / Service Issue', 'service', $yesterday_service_tasks, $completed_tasks, $base_url, $range, $task_nonce ); ?>
        <?php lawnace_render_task_group( 'Drop-off — Re-engage', 'service', array_values( $yesterday_dropoffs ), $completed_tasks, $base_url, $range, $task_nonce ); ?>

        <?php if ( $total_tasks === 0 ) : ?>
        <div class="la-panel" style="text-align:center;padding:40px;">
            <div style="font-size:32px;margin-bottom:10px;">&#10003;</div>
            <div style="font-weight:700;color:#16a34a;font-size:15px;">No tasks from yesterday</div>
            <div style="color:#999;font-size:13px;margin-top:6px;">Check back tomorrow morning.</div>
        </div>
        <?php endif; ?>

    </div>

    <!-- ================================================================
         SALES TAB
    ================================================================ -->
    <div id="tab-sales" class="la-tab-panel <?php echo $active_tab === 'sales' ? 'active' : ''; ?>">

        <div class="la-stat-grid">
            <div class="la-stat">
                <div class="la-stat-num"><?php echo esc_html( $lead_sessions ); ?></div>
                <div class="la-stat-label">Leads Captured</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num"><?php echo esc_html( $total_sessions ); ?></div>
                <div class="la-stat-label">Total Conversations</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num <?php echo $conversion >= 20 ? 'green' : ( $conversion >= 10 ? 'amber' : '' ); ?>">
                    <?php echo esc_html( $conversion ); ?>%
                </div>
                <div class="la-stat-label">Conversion Rate</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num"><?php echo esc_html( $quote_count ); ?></div>
                <div class="la-stat-label">Pricing Questions</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num <?php echo $avg_time_to_lead !== null ? ( $avg_time_to_lead <= 4 ? 'green' : ( $avg_time_to_lead <= 7 ? 'amber' : 'red' ) ) : ''; ?>">
                    <?php echo $avg_time_to_lead !== null ? esc_html( $avg_time_to_lead ) : 'N/A'; ?>
                </div>
                <div class="la-stat-label">Avg Messages to Lead</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num"><?php echo esc_html( $total_messages ); ?></div>
                <div class="la-stat-label">Customer Messages</div>
            </div>
        </div>

        <!-- Leads table -->
        <div class="la-panel" style="margin-bottom:20px;">
            <h3>Recent Leads</h3>
            <?php if ( empty( $recent_leads ) ) : ?>
                <p class="la-empty">No leads captured in this period yet.</p>
            <?php else : ?>
                <table class="la-table">
                    <thead><tr><th>Contact Info</th><th>Captured</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ( $recent_leads as $lead ) : ?>
                    <tr>
                        <td><?php echo esc_html( $lead->message ); ?></td>
                        <td style="white-space:nowrap;color:#999"><?php echo esc_html( $lead->created_at ); ?></td>
                        <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $lead->session_id, 'range' => $range ], $base_url ) ); ?>">View chat</a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Service types + ZIP side by side -->
        <div class="la-panels">
            <div class="la-panel">
                <h3>Services Customers Are Requesting</h3>
                <p class="la-panel-sub">Detected from conversation keywords</p>
                <?php if ( empty( $service_counts ) ) : ?>
                    <p class="la-empty">No service requests detected yet.</p>
                <?php else : ?>
                    <?php $max_svc = max( $service_counts ) ?: 1; ?>
                    <?php foreach ( $service_counts as $svc => $cnt ) :
                        $pct = round( ( $cnt / $max_svc ) * 100 );
                    ?>
                    <div class="la-bar-row">
                        <span class="la-bar-label"><?php echo esc_html( $svc ); ?></span>
                        <div class="la-bar-track"><div class="la-bar-fill green" style="width:<?php echo esc_attr( $pct ); ?>%"></div></div>
                        <span class="la-bar-pct"><?php echo esc_html( $cnt ); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="la-panel">
                <h3>Top ZIP Codes</h3>
                <p class="la-panel-sub">Where customers are located based on their messages</p>
                <?php if ( empty( $zip_counts ) ) : ?>
                    <p class="la-empty">No ZIP codes detected yet.</p>
                <?php else : ?>
                    <?php $max_zip = max( $zip_counts ) ?: 1; ?>
                    <?php foreach ( $zip_counts as $zip => $cnt ) :
                        $pct = round( ( $cnt / $max_zip ) * 100 );
                    ?>
                    <div class="la-bar-row">
                        <span class="la-bar-label" style="width:60px;font-weight:700;"><?php echo esc_html( $zip ); ?></span>
                        <div class="la-bar-track"><div class="la-bar-fill" style="width:<?php echo esc_attr( $pct ); ?>%"></div></div>
                        <span class="la-bar-pct"><?php echo esc_html( $cnt ); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upsell signals + Seasonal demand side by side -->
        <div class="la-panels" style="margin-bottom:20px;">
            <div class="la-panel">
                <h3>Upsell Opportunities</h3>
                <p class="la-panel-sub">Customers asking about services beyond standard lawn care</p>
                <?php if ( empty( $upsell_sessions ) ) : ?>
                    <p class="la-empty">No upsell signals detected in this period.</p>
                <?php else : ?>
                    <table class="la-table">
                        <thead><tr><th>Session</th><th>Started</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ( $upsell_sessions as $s ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( substr( $s->session_id, 0, 14 ) ); ?>&hellip;</code></td>
                            <td style="color:#999;white-space:nowrap;font-size:11px"><?php echo esc_html( $s->started ); ?></td>
                            <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $s->session_id, 'range' => $range ], $base_url ) ); ?>">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="la-panel">
                <h3>Seasonal Treatment Demand</h3>
                <p class="la-panel-sub">Treatment types customers are asking about right now</p>
                <?php if ( empty( $seasonal_counts ) ) : ?>
                    <p class="la-empty">No seasonal demand signals detected yet.</p>
                <?php else : ?>
                    <?php $max_seas = max( $seasonal_counts ) ?: 1; ?>
                    <?php foreach ( $seasonal_counts as $label => $cnt ) :
                        $pct = round( ( $cnt / $max_seas ) * 100 );
                    ?>
                    <div class="la-bar-row">
                        <span class="la-bar-label"><?php echo esc_html( $label ); ?></span>
                        <div class="la-bar-track"><div class="la-bar-fill green" style="width:<?php echo esc_attr( $pct ); ?>%"></div></div>
                        <span class="la-bar-pct"><?php echo esc_html( $cnt ); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Service area misses + Returning customers side by side -->
        <div class="la-panels" style="margin-bottom:20px;">
            <div class="la-panel">
                <h3>Service Area Inquiries</h3>
                <p class="la-panel-sub">Customers checking if we cover their location — sorted by ZIP, potential expansion signals</p>
                <?php if ( empty( $area_miss_sessions ) ) : ?>
                    <p class="la-empty">No area inquiries detected in this period.</p>
                <?php else :
                    // Extract location as the customer typed it — ZIP first, then city phrase from message
                    foreach ( $area_miss_sessions as $s ) {
                        $msg = $s->message ?? '';
                        // Try ZIP first
                        preg_match( '/\b(\d{5})\b/', $msg, $zm );
                        $s->zip      = $zm[1] ?? '';
                        $s->location = $s->zip;
                        // If no ZIP, pull the city/area the customer actually named
                        if ( ! $s->location ) {
                            // Match phrases like "in Evans", "to Grovetown", "service North Augusta", "near Martinez"
                            if ( preg_match( '/(?:in|to|near|around|service|serve|cover|out to|come to)\s+([A-Z][A-Za-z\s]{2,25})(?:[?,.]|$)/i', $msg, $cm ) ) {
                                $s->location = trim( $cm[1] );
                            }
                        }
                    }
                    // Sort by zip (blank last), then by started
                    usort( $area_miss_sessions, function( $a, $b ) {
                        if ( $a->zip === '' && $b->zip !== '' ) return 1;
                        if ( $b->zip === '' && $a->zip !== '' ) return -1;
                        return strcmp( $a->zip, $b->zip ) ?: strcmp( $a->started, $b->started );
                    } );
                ?>
                    <table class="la-table">
                        <thead><tr><th>City / ZIP</th><th>Session</th><th>Started</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ( $area_miss_sessions as $s ) : ?>
                        <tr>
                            <td style="font-weight:700;color:#5b4fbd;white-space:nowrap;">
                                <?php echo $s->location ? esc_html( $s->location ) : '<span style="color:#ccc">Unknown</span>'; ?>
                            </td>
                            <td><code><?php echo esc_html( substr( $s->session_id, 0, 14 ) ); ?>&hellip;</code></td>
                            <td style="color:#999;white-space:nowrap;font-size:11px"><?php echo esc_html( $s->started ); ?></td>
                            <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $s->session_id, 'range' => $range ], $base_url ) ); ?>">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="la-panel">
                <h3>Returning Customer Inquiries</h3>
                <p class="la-panel-sub">Existing customers reaching out — different follow-up needed</p>
                <?php if ( empty( $returning_sessions ) ) : ?>
                    <p class="la-empty">No returning customer chats detected in this period.</p>
                <?php else : ?>
                    <table class="la-table">
                        <thead><tr><th>Session</th><th>Started</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ( $returning_sessions as $s ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( substr( $s->session_id, 0, 14 ) ); ?>&hellip;</code></td>
                            <td style="color:#999;white-space:nowrap;font-size:11px"><?php echo esc_html( $s->started ); ?></td>
                            <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $s->session_id, 'range' => $range ], $base_url ) ); ?>">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stat cards for new sales metrics -->
        <div class="la-stat-grid" style="margin-bottom:20px;">
            <div class="la-stat">
                <div class="la-stat-num <?php echo count( $upsell_sessions ) > 0 ? 'green' : ''; ?>"><?php echo count( $upsell_sessions ); ?></div>
                <div class="la-stat-label">Upsell Signals</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num"><?php echo count( $area_miss_sessions ); ?></div>
                <div class="la-stat-label">Area Inquiries</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num"><?php echo count( $returning_sessions ); ?></div>
                <div class="la-stat-label">Returning Customers</div>
            </div>
        </div>

        <?php lawnace_render_task_mini_card( $open_count, $completed_count, $total_tasks, $base_url, $range ); ?>

    </div>

    <!-- ================================================================
         SERVICE TAB
    ================================================================ -->
    <div id="tab-service" class="la-tab-panel <?php echo $active_tab === 'service' ? 'active' : ''; ?>">

        <div class="la-stat-grid">
            <div class="la-stat">
                <div class="la-stat-num <?php echo $total_ratings > 0 ? ( $approval_rate >= 70 ? 'green' : ( $approval_rate >= 40 ? 'amber' : 'red' ) ) : ''; ?>">
                    <?php echo $total_ratings > 0 ? esc_html( $approval_rate ) . '%' : 'No data'; ?>
                </div>
                <div class="la-stat-label">Customer Approval (<?php echo esc_html( $thumbs_up ); ?> up / <?php echo esc_html( $thumbs_down ); ?> down)</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num <?php echo count( $complaint_sessions ) > 0 ? 'amber' : 'green'; ?>">
                    <?php echo count( $complaint_sessions ); ?>
                </div>
                <div class="la-stat-label">Flagged Complaints</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num <?php echo count( $noshow_sessions ) > 0 ? 'red' : 'green'; ?>">
                    <?php echo count( $noshow_sessions ); ?>
                </div>
                <div class="la-stat-label">No-Show Reports</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num <?php echo count( $cancel_sessions ) > 0 ? 'red' : 'green'; ?>">
                    <?php echo count( $cancel_sessions ); ?>
                </div>
                <div class="la-stat-label">Cancellation Intent</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num <?php echo count( $clover_unsure ) > 5 ? 'amber' : ''; ?>">
                    <?php echo count( $clover_unsure ); ?>
                </div>
                <div class="la-stat-label">Referred to Office</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num"><?php echo count( $dropoffs ); ?></div>
                <div class="la-stat-label">Drop-off Sessions</div>
            </div>
        </div>

        <!-- High-priority alerts row -->
        <?php
        $urgent_map = [];
        foreach ( $noshow_sessions as $s ) $urgent_map[ $s->session_id ] = [ 'session' => $s, 'type' => 'No-Show' ];
        foreach ( $cancel_sessions as $s ) {
            if ( isset( $urgent_map[ $s->session_id ] ) ) {
                $urgent_map[ $s->session_id ]['type'] = 'No-Show + Cancel';
            } else {
                $urgent_map[ $s->session_id ] = [ 'session' => $s, 'type' => 'Cancel Intent' ];
            }
        }
        if ( ! empty( $urgent_map ) ) : ?>
        <div class="la-panel la-alert-panel" style="margin-bottom:20px;">
            <h3>Needs Attention</h3>
            <p class="la-panel-sub">No-show reports and cancellation signals — follow up with these customers</p>
            <table class="la-table">
                <thead><tr><th>Type</th><th>Session</th><th>Started</th><th></th></tr></thead>
                <tbody>
                <?php foreach ( $urgent_map as $sid => $item ) : ?>
                <tr>
                    <td><span class="la-flag-badge"><?php echo esc_html( $item['type'] ); ?></span></td>
                    <td><code><?php echo esc_html( substr( $sid, 0, 14 ) ); ?>&hellip;</code></td>
                    <td style="color:#999;white-space:nowrap"><?php echo esc_html( $item['session']->started ); ?></td>
                    <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $sid, 'range' => $range ], $base_url ) ); ?>">Review</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Complaints + Lawnie escalations -->
        <div class="la-panels" style="margin-bottom:20px;">
            <div class="la-panel">
                <h3>Flagged Complaints</h3>
                <p class="la-panel-sub">Customers who mentioned rude service or dissatisfaction</p>
                <?php if ( empty( $complaint_sessions ) ) : ?>
                    <p class="la-empty">No flagged conversations in this period.</p>
                <?php else : ?>
                    <table class="la-table">
                        <thead><tr><th>Session</th><th>Started</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ( $complaint_sessions as $s ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( substr( $s->session_id, 0, 14 ) ); ?>&hellip;</code></td>
                            <td style="color:#999;white-space:nowrap"><?php echo esc_html( $s->started ); ?></td>
                            <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $s->session_id, 'range' => $range ], $base_url ) ); ?>">Review</a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="la-panel">
                <h3>Referred to Office</h3>
                <p class="la-panel-sub">Conversations where Lawnie couldn't answer and referred the customer to call the office directly</p>
                <?php if ( empty( $clover_unsure ) ) : ?>
                    <p class="la-empty">No escalations detected in this period.</p>
                <?php else : ?>
                    <table class="la-table">
                        <thead><tr><th>Session</th><th>Time</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ( $clover_unsure as $s ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( substr( $s->session_id, 0, 14 ) ); ?>&hellip;</code></td>
                            <td style="color:#999;white-space:nowrap;font-size:11px"><?php echo esc_html( $s->created_at ); ?></td>
                            <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $s->session_id, 'range' => $range ], $base_url ) ); ?>">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Drop-offs + All sessions -->
        <div class="la-panels">
            <div class="la-panel">
                <h3>Drop-off Sessions</h3>
                <p class="la-panel-sub">Customers who left after 2 or fewer messages</p>
                <?php if ( empty( $dropoffs ) ) : ?>
                    <p class="la-empty">None found.</p>
                <?php else : ?>
                    <table class="la-table">
                        <thead><tr><th>Session</th><th>Msgs</th><th>Started</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ( $dropoffs as $s ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( substr( $s->session_id, 0, 12 ) ); ?>&hellip;</code></td>
                            <td><?php echo esc_html( $s->msg_count ); ?></td>
                            <td style="color:#999;white-space:nowrap;font-size:11px"><?php echo esc_html( $s->started ); ?></td>
                            <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $s->session_id, 'range' => $range ], $base_url ) ); ?>">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="la-panel">
                <h3>All Conversations</h3>
                <p class="la-panel-sub">Most recent 200 sessions</p>
                <?php if ( empty( $sessions ) ) : ?>
                    <p class="la-empty">No sessions in this period.</p>
                <?php else : ?>
                    <table class="la-table">
                        <thead><tr><th>Started</th><th>Msgs</th><th>Lead</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ( $sessions as $s ) : ?>
                        <tr>
                            <td style="color:#999;white-space:nowrap;font-size:11px"><?php echo esc_html( $s->started ); ?></td>
                            <td><?php echo esc_html( $s->messages ); ?></td>
                            <td><?php echo $s->has_lead ? '<span class="la-lead-badge">Yes</span>' : '<span style="color:#ddd">No</span>'; ?></td>
                            <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $s->session_id, 'range' => $range ], $base_url ) ); ?>">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Re-treatment + Pet/child safety side by side -->
        <div class="la-panels" style="margin-bottom:20px;">
            <div class="la-panel la-alert-panel">
                <h3>Re-Treatment Requests</h3>
                <p class="la-panel-sub">Customers saying results weren't satisfactory — churn &amp; liability risk</p>
                <?php if ( empty( $retreatment_sessions ) ) : ?>
                    <p class="la-empty">No re-treatment requests in this period.</p>
                <?php else : ?>
                    <table class="la-table">
                        <thead><tr><th>Session</th><th>Started</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ( $retreatment_sessions as $s ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( substr( $s->session_id, 0, 14 ) ); ?>&hellip;</code></td>
                            <td style="color:#999;white-space:nowrap;font-size:11px"><?php echo esc_html( $s->started ); ?></td>
                            <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $s->session_id, 'range' => $range ], $base_url ) ); ?>">Review</a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="la-panel">
                <h3>Pet &amp; Child Safety Questions</h3>
                <p class="la-panel-sub">Customers asking if treatments are safe — call these back personally</p>
                <?php if ( empty( $safety_sessions ) ) : ?>
                    <p class="la-empty">No safety questions detected in this period.</p>
                <?php else : ?>
                    <table class="la-table">
                        <thead><tr><th>Session</th><th>Started</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ( $safety_sessions as $s ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( substr( $s->session_id, 0, 14 ) ); ?>&hellip;</code></td>
                            <td style="color:#999;white-space:nowrap;font-size:11px"><?php echo esc_html( $s->started ); ?></td>
                            <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $s->session_id, 'range' => $range ], $base_url ) ); ?>">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Billing + Weather holds side by side -->
        <div class="la-panels" style="margin-bottom:20px;">
            <div class="la-panel">
                <h3>Billing &amp; Invoice Questions</h3>
                <p class="la-panel-sub">Payment confusion or disputes needing follow-up</p>
                <?php if ( empty( $billing_sessions ) ) : ?>
                    <p class="la-empty">No billing questions detected in this period.</p>
                <?php else : ?>
                    <table class="la-table">
                        <thead><tr><th>Session</th><th>Started</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ( $billing_sessions as $s ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( substr( $s->session_id, 0, 14 ) ); ?>&hellip;</code></td>
                            <td style="color:#999;white-space:nowrap;font-size:11px"><?php echo esc_html( $s->started ); ?></td>
                            <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $s->session_id, 'range' => $range ], $base_url ) ); ?>">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="la-panel">
                <h3>Weather Holds &amp; Reschedules</h3>
                <p class="la-panel-sub">Customers asking to pause or reschedule due to rain or weather</p>
                <?php if ( empty( $weather_sessions ) ) : ?>
                    <p class="la-empty">No weather holds detected in this period.</p>
                <?php else : ?>
                    <table class="la-table">
                        <thead><tr><th>Session</th><th>Started</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ( $weather_sessions as $s ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( substr( $s->session_id, 0, 14 ) ); ?>&hellip;</code></td>
                            <td style="color:#999;white-space:nowrap;font-size:11px"><?php echo esc_html( $s->started ); ?></td>
                            <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $s->session_id, 'range' => $range ], $base_url ) ); ?>">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Account changes panel full width -->
        <div class="la-panel" style="margin-bottom:20px;">
            <h3>Account Changes Requested</h3>
            <p class="la-panel-sub">Customers asking to pause, upgrade, downgrade, or update their service</p>
            <?php if ( empty( $account_change_sessions ) ) : ?>
                <p class="la-empty">No account change requests detected in this period.</p>
            <?php else : ?>
                <table class="la-table">
                    <thead><tr><th>Session</th><th>Started</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ( $account_change_sessions as $s ) : ?>
                    <tr>
                        <td><code><?php echo esc_html( substr( $s->session_id, 0, 14 ) ); ?>&hellip;</code></td>
                        <td style="color:#999;white-space:nowrap;font-size:11px"><?php echo esc_html( $s->started ); ?></td>
                        <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $s->session_id, 'range' => $range ], $base_url ) ); ?>">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Stat cards for new service metrics -->
        <div class="la-stat-grid" style="margin-bottom:20px;">
            <div class="la-stat">
                <div class="la-stat-num <?php echo count( $retreatment_sessions ) > 0 ? 'red' : 'green'; ?>"><?php echo count( $retreatment_sessions ); ?></div>
                <div class="la-stat-label">Re-Treatment Requests</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num <?php echo count( $safety_sessions ) > 0 ? 'amber' : ''; ?>"><?php echo count( $safety_sessions ); ?></div>
                <div class="la-stat-label">Safety Questions</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num <?php echo count( $billing_sessions ) > 0 ? 'amber' : ''; ?>"><?php echo count( $billing_sessions ); ?></div>
                <div class="la-stat-label">Billing Questions</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num"><?php echo count( $weather_sessions ); ?></div>
                <div class="la-stat-label">Weather Holds</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num"><?php echo count( $account_change_sessions ); ?></div>
                <div class="la-stat-label">Account Changes</div>
            </div>
        </div>

        <?php lawnace_render_task_mini_card( $open_count, $completed_count, $total_tasks, $base_url, $range ); ?>

    </div>

    <!-- ================================================================
         INSIGHTS TAB
    ================================================================ -->
    <div id="tab-insights" class="la-tab-panel <?php echo $active_tab === 'insights' ? 'active' : ''; ?>">

        <!-- Weekly AI Summary -->
        <div class="la-panel la-summary-panel" style="margin-bottom:22px;">
            <div class="la-summary-header">
                <div>
                    <h3 style="margin-bottom:4px;">This Week in the Chat</h3>
                    <div class="la-summary-meta">
                        <?php if ( $cached_summary ) : ?>
                            Generated <?php echo esc_html( $cached_summary['generated'] ); ?> &middot; <?php echo esc_html( $cached_summary['range'] ?? 'Last 30 Days' ); ?>
                        <?php else : ?>
                            No summary generated yet. Use the range selector above then click Generate.
                        <?php endif; ?>
                    </div>
                </div>
                <button id="la-summary-btn" class="la-summary-btn"
                    data-nonce="<?php echo esc_attr( $summary_nonce ); ?>"
                    data-ajax="<?php echo esc_url( $ajax_url ); ?>"
                    data-range="<?php echo esc_attr( $range ); ?>">
                    <?php echo $cached_summary ? 'Refresh Summary' : 'Generate Summary'; ?>
                </button>
            </div>
            <div id="la-summary-loading" style="display:none;color:#5b4fbd;font-size:13px;padding:10px 0;">
                Analyzing conversations, give it about 15 seconds...
            </div>
            <div id="la-summary-output">
                <?php if ( $cached_summary && ! empty( $cached_summary['sections'] ) ) :
                    $s = $cached_summary['sections'];
                    $labels = [ 'volume' => 'Volume', 'topics' => 'Top Topics', 'geography' => 'Geography', 'flags' => 'Flags' ];
                    foreach ( $labels as $key => $label ) :
                        if ( empty( $s[ $key ] ) ) continue; ?>
                    <div class="la-brief-row <?php echo $key === 'flags' ? 'la-brief-flag' : ''; ?>">
                        <span class="la-brief-label"><?php echo esc_html( $label ); ?></span>
                        <span class="la-brief-text"><?php echo esc_html( $s[ $key ] ); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php elseif ( $cached_summary ) : ?>
                    <div class="la-summary-text"><?php echo esc_html( $cached_summary['text'] ); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sentiment + Competitor side by side -->
        <div class="la-panels" style="margin-bottom:20px;">
            <div class="la-panel">
                <h3>Customer Sentiment</h3>
                <p class="la-panel-sub">Positive and frustrated signals detected across conversations</p>
                <?php
                $sent_total = $positive_count + $frustrated_count;
                ?>
                <div style="display:flex;gap:16px;margin-bottom:18px;">
                    <div style="flex:1;text-align:center;padding:14px;background:#f0fdf4;border-radius:8px;">
                        <div style="font-size:28px;font-weight:800;color:#16a34a;"><?php echo esc_html( $positive_count ); ?></div>
                        <div style="font-size:11px;color:#666;font-weight:600;margin-top:4px;text-transform:uppercase;letter-spacing:.4px;">Positive Sessions</div>
                    </div>
                    <div style="flex:1;text-align:center;padding:14px;background:#fff5f5;border-radius:8px;">
                        <div style="font-size:28px;font-weight:800;color:#dc2626;"><?php echo esc_html( $frustrated_count ); ?></div>
                        <div style="font-size:11px;color:#666;font-weight:600;margin-top:4px;text-transform:uppercase;letter-spacing:.4px;">Frustrated Sessions</div>
                    </div>
                </div>
                <?php if ( $sent_total > 0 ) :
                    $pos_pct  = round( ( $positive_count   / $sent_total ) * 100 );
                    $frus_pct = round( ( $frustrated_count / $sent_total ) * 100 );
                ?>
                <div class="la-bar-row">
                    <span class="la-bar-label">Positive</span>
                    <div class="la-bar-track"><div class="la-bar-fill green" style="width:<?php echo esc_attr( $pos_pct ); ?>%"></div></div>
                    <span class="la-bar-pct"><?php echo esc_html( $pos_pct ); ?>%</span>
                </div>
                <div class="la-bar-row">
                    <span class="la-bar-label">Frustrated</span>
                    <div class="la-bar-track"><div class="la-bar-fill red" style="width:<?php echo esc_attr( $frus_pct ); ?>%"></div></div>
                    <span class="la-bar-pct"><?php echo esc_html( $frus_pct ); ?>%</span>
                </div>
                <?php else : ?>
                    <p class="la-empty">Not enough data to measure sentiment yet.</p>
                <?php endif; ?>
            </div>

            <div class="la-panel">
                <h3>Competitors Mentioned</h3>
                <p class="la-panel-sub">Competitor names detected in customer messages</p>
                <?php if ( empty( $competitor_counts ) ) : ?>
                    <p class="la-empty">No competitors mentioned in this period.</p>
                <?php else : ?>
                    <?php $max_comp = max( $competitor_counts ) ?: 1; ?>
                    <?php foreach ( $competitor_counts as $comp => $cnt ) :
                        $pct = round( ( $cnt / $max_comp ) * 100 );
                    ?>
                    <div class="la-bar-row">
                        <span class="la-bar-label" style="color:#dc2626;font-weight:700;"><?php echo esc_html( $comp ); ?></span>
                        <div class="la-bar-track"><div class="la-bar-fill red" style="width:<?php echo esc_attr( $pct ); ?>%"></div></div>
                        <span class="la-bar-pct"><?php echo esc_html( $cnt ); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Topic trend -->
        <?php if ( $period_days > 0 && ( ! empty( $topic_counts ) || ! empty( $prev_topic_counts ) ) ) :
            $all_topics = array_unique( array_merge( array_keys( $topic_counts ), array_keys( $prev_topic_counts ) ) );
            $trend_rows = [];
            foreach ( $all_topics as $t ) {
                $now  = $topic_counts[ $t ]      ?? 0;
                $prev = $prev_topic_counts[ $t ] ?? 0;
                $diff = $now - $prev;
                $trend_rows[ $t ] = [ 'now' => $now, 'prev' => $prev, 'diff' => $diff ];
            }
            uasort( $trend_rows, function( $a, $b ) { return $b['now'] - $a['now']; } );
        ?>
        <div class="la-panel" style="margin-bottom:20px;">
            <h3>Topic Trends</h3>
            <p class="la-panel-sub">This period vs. previous equal period — arrows show change</p>
            <table class="la-table">
                <thead><tr><th>Topic</th><th>This Period</th><th>Previous</th><th>Change</th></tr></thead>
                <tbody>
                <?php foreach ( $trend_rows as $topic => $row ) : ?>
                <tr>
                    <td><?php echo esc_html( $topic ); ?></td>
                    <td style="font-weight:700;"><?php echo esc_html( $row['now'] ); ?></td>
                    <td style="color:#999;"><?php echo esc_html( $row['prev'] ); ?></td>
                    <td>
                        <?php if ( $row['diff'] > 0 ) : ?>
                            <span style="color:#16a34a;font-weight:700;">+<?php echo esc_html( $row['diff'] ); ?> &uarr;</span>
                        <?php elseif ( $row['diff'] < 0 ) : ?>
                            <span style="color:#dc2626;font-weight:700;"><?php echo esc_html( $row['diff'] ); ?> &darr;</span>
                        <?php else : ?>
                            <span style="color:#bbb;">&mdash;</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>

</main>

<script>
(function(){
    // Weekly summary
    var btn     = document.getElementById('la-summary-btn');
    var loading = document.getElementById('la-summary-loading');
    var output  = document.getElementById('la-summary-output');
    if (!btn) return;

    btn.addEventListener('click', function(){
        btn.disabled = true;
        btn.textContent = 'Analyzing...';
        loading.style.display = 'block';
        output.innerHTML = '';

        var fd = new FormData();
        fd.append('action', 'lawnace_client_weekly_summary');
        fd.append('nonce', btn.dataset.nonce);
        fd.append('range', btn.dataset.range || 'month');

        fetch(btn.dataset.ajax, {method:'POST', credentials:'same-origin', body:fd})
        .then(function(r){ return r.json(); })
        .then(function(res){
            loading.style.display = 'none';
            if (res.success && res.data && res.data.sections) {
                var labels = {volume:'Volume', topics:'Top Topics', geography:'Geography', flags:'Flags'};
                var html = '';
                for (var key in labels) {
                    var val = res.data.sections[key];
                    if (!val) continue;
                    var flagClass = key === 'flags' ? ' la-brief-flag' : '';
                    html += '<div class="la-brief-row' + flagClass + '"><span class="la-brief-label">' + labels[key] + '</span><span class="la-brief-text">' + escHtml(val) + '</span></div>';
                }
                output.innerHTML = html;
                btn.textContent = 'Refresh Summary';
            } else {
                output.innerHTML = '<p style="color:#dc2626;font-size:13px;">Error: ' + escHtml(res.data || 'Something went wrong.') + '</p>';
                btn.textContent = 'Try Again';
            }
            btn.disabled = false;
        })
        .catch(function(){
            loading.style.display = 'none';
            output.innerHTML = '<p style="color:#dc2626;font-size:13px;">Request failed. Please try again.</p>';
            btn.textContent = 'Try Again';
            btn.disabled = false;
        });
    });

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // Task checkbox AJAX
    document.querySelectorAll('.la-task-cb').forEach(function(cb) {
        cb.addEventListener('change', function() {
            var sid    = this.dataset.sid;
            var nonce  = this.dataset.nonce;
            var done   = this.checked;
            var row    = this.closest('.la-task-row');
            if (row) row.classList.toggle('la-task-done', done);
            var fd = new FormData();
            fd.append('action', 'lawnace_task_toggle');
            fd.append('nonce', nonce);
            fd.append('session_id', sid);
            fd.append('completed', done ? '1' : '0');
            fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                method: 'POST',
                credentials: 'same-origin',
                body: fd
            }).then(function(r){ return r.json(); }).then(function(res) {
                if (!res.success) { cb.checked = !done; if (row) row.classList.toggle('la-task-done', !done); }
            }).catch(function() { cb.checked = !done; if (row) row.classList.toggle('la-task-done', !done); });
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
| SESSION DETAIL
|--------------------------------------------------------------------------
*/

add_action( 'template_redirect', 'lawnace_maybe_render_client_session', 9 );

function lawnace_maybe_render_client_session() {
    if ( ! get_query_var( 'lawnace_client_dashboard' ) ) return;
    if ( ! lawnace_client_dash_is_authed() ) return;
    if ( empty( $_GET['session'] ) ) return;

    global $wpdb;
    $table      = $wpdb->prefix . 'lawnace_chat_logs';
    $session_id = sanitize_text_field( wp_unslash( $_GET['session'] ) );
    $range      = isset( $_GET['range'] ) ? sanitize_text_field( wp_unslash( $_GET['range'] ) ) : 'month';
    $back_tab   = in_array( $_GET['tab'] ?? '', [ 'service', 'tasks', 'insights' ], true ) ? $_GET['tab'] : 'sales';
    $back_url   = esc_url( add_query_arg( [ 'tab' => $back_tab, 'range' => $range ], home_url( '/la-team/' ) ) );

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
<title>Conversation Detail &mdash; Lawn Ace Dashboard</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,'Segoe UI',Arial,sans-serif;background:#f4f5f8;color:#1a1a2e;font-size:14px;}
.la-header{background:linear-gradient(135deg,#12103a,#3b3294,#5b4fbd);color:#fff;padding:0 28px;display:flex;align-items:center;height:62px;}
.la-brand{font-size:16px;font-weight:700;}
.la-main{max-width:900px;margin:0 auto;padding:28px 20px;}
.la-back{display:inline-flex;align-items:center;gap:5px;color:#5b4fbd;text-decoration:none;font-size:13px;font-weight:600;margin-bottom:20px;}
.la-back:hover{text-decoration:underline;}
h1{font-size:20px;margin-bottom:6px;}
.session-meta{font-size:12px;color:#999;margin-bottom:22px;}
code{background:#f0f0f8;padding:2px 6px;border-radius:3px;font-size:11px;}
.la-table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);}
.la-table th{text-align:left;padding:10px 14px;background:#f8f8fc;border-bottom:2px solid #eee;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:#999;font-weight:600;}
.la-table td{padding:10px 14px;border-bottom:1px solid #f0f0f8;vertical-align:top;font-size:13px;line-height:1.5;}
.la-table tr:last-child td{border-bottom:none;}
.role-user{color:#1a1a2e;font-weight:700;}
.role-assistant{color:#5b4fbd;font-weight:700;}
.role-lead{color:#16a34a;font-weight:700;}
</style>
</head>
<body>
<header class="la-header">
    <div class="la-brand">Lawn Ace Dashboard &mdash; Conversation Detail</div>
</header>
<main class="la-main">
    <a class="la-back" href="<?php echo $back_url; ?>">&larr; Back to Dashboard</a>
    <h1>Conversation Transcript</h1>
    <div class="session-meta">Session ID: <code><?php echo esc_html( $session_id ); ?></code></div>
    <table class="la-table">
        <thead><tr>
            <th style="width:90px">Speaker</th>
            <th>Message</th>
            <th style="width:150px">Time</th>
        </tr></thead>
        <tbody>
        <?php foreach ( $rows as $row ) : ?>
        <tr>
            <td><span class="role-<?php echo esc_attr( $row->role ); ?>"><?php echo esc_html( ucfirst( $row->role ) ); ?></span></td>
            <td style="white-space:pre-wrap"><?php echo esc_html( $row->message ); ?></td>
            <td style="color:#bbb;font-size:11px"><?php echo esc_html( $row->created_at ); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</body>
</html>
    <?php
    exit;
}
