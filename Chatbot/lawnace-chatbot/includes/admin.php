<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
|--------------------------------------------------------------------------
| MENU REGISTRATION
|--------------------------------------------------------------------------
*/

add_action( 'admin_menu', 'lawnace_register_admin_menu' );

function lawnace_register_admin_menu() {

    // Custom grass SVG icon for the top-level menu item
    $svg = 'data:image/svg+xml;base64,' . base64_encode(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none">' .
        '<path d="M4 44 L60 6 L46 60 L30 38 Z" fill="#a7aaad" fill-opacity="0.3" stroke="#a7aaad" stroke-width="4" stroke-linejoin="round" stroke-linecap="round"/>' .
        '<path d="M30 38 L46 60 L37 46 Z" fill="#a7aaad" fill-opacity="0.6" stroke="#a7aaad" stroke-width="3" stroke-linejoin="round"/>' .
        '<line x1="4" y1="44" x2="30" y2="38" stroke="#a7aaad" stroke-width="3" stroke-linecap="round"/>' .
        '</svg>'
    );

    add_menu_page(
        'Lawnie Dashboard',
        'Lawnie',
        'manage_options',
        'lawnace-chat-logs',
        'lawnace_render_dashboard',
        $svg,
        25
    );

    add_submenu_page(
        'lawnace-chat-logs',
        'Lawnie Dashboard',
        'Dashboard',
        'manage_options',
        'lawnace-chat-logs',
        'lawnace_render_dashboard'
    );

    add_submenu_page(
        'lawnace-chat-logs',
        'Lawnie Settings',
        'Settings',
        'manage_options',
        'lawnace-settings',
        'lawnace_render_settings_page'
    );
}

/*
|--------------------------------------------------------------------------
| SETTINGS PAGE
|--------------------------------------------------------------------------
*/

add_action( 'admin_init', 'lawnace_register_settings' );

function lawnace_register_settings() {
    register_setting( 'lawnace_settings_group', 'lawnace_anthropic_key', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ] );
    register_setting( 'lawnace_settings_group', 'lawnace_notify_email', [
        'type'              => 'string',
        'sanitize_callback' => 'lawnace_sanitize_email_list',
        'default'           => get_option( 'admin_email' ),
    ] );
    register_setting( 'lawnace_settings_group', 'lawnace_client_pin', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ] );
    register_setting( 'lawnace_settings_group', 'lawnace_digest_enabled', [
        'type'              => 'string',
        'sanitize_callback' => function ( $v ) { return $v ? '1' : '0'; },
        'default'           => '1',
    ] );
    register_setting( 'lawnace_settings_group', 'lawnace_digest_emails', [
        'type'              => 'string',
        'sanitize_callback' => 'lawnace_sanitize_email_list',
        'default'           => '',
    ] );
    register_setting( 'lawnace_settings_group', 'lawnace_team_members', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => 'Kyle, Tammi, Tim',
    ] );
}

/** Accepts "a@x.com, b@y.com" (commas, semicolons, or spaces) and stores a clean comma list. */
function lawnace_sanitize_email_list( $raw ) {
    $out = [];
    foreach ( preg_split( '/[\s,;]+/', (string) $raw ) as $addr ) {
        $addr = sanitize_email( $addr );
        if ( $addr !== '' && is_email( $addr ) ) $out[] = $addr;
    }
    return implode( ', ', array_unique( $out ) );
}

function lawnace_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    ?>
    <div class="wrap">
        <h1><img src="<?php echo esc_url( LAWNACE_PLUGIN_URL . 'assets/images/lawnie-icon.png' ); ?>" alt="" style="width:34px;height:34px;border-radius:50%;vertical-align:middle;margin-right:8px;">Lawnie Settings</h1>
        <?php settings_errors( 'lawnace_settings_group' ); ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'lawnace_settings_group' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="lawnace_anthropic_key">Anthropic API Key</label></th>
                    <td>
                        <input type="password" id="lawnace_anthropic_key" name="lawnace_anthropic_key"
                            value="<?php echo esc_attr( get_option( 'lawnace_anthropic_key', '' ) ); ?>"
                            class="regular-text" autocomplete="off" />
                        <p class="description">Your Anthropic API key. Stored securely in the database.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="lawnace_notify_email">Lead Notification Email</label></th>
                    <td>
                        <input type="text" id="lawnace_notify_email" name="lawnace_notify_email"
                            value="<?php echo esc_attr( get_option( 'lawnace_notify_email', get_option( 'admin_email' ) ) ); ?>"
                            class="regular-text" placeholder="kyle@lawnace.com, tammi@lawnace.com" />
                        <p class="description">Receives new lead notifications from the chatbot. Comma-separated for more than one person.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="lawnace_client_pin">Client Dashboard Password</label></th>
                    <td>
                        <input type="text" id="lawnace_client_pin" name="lawnace_client_pin"
                            value="<?php echo esc_attr( get_option( 'lawnace_client_pin', '' ) ); ?>"
                            class="regular-text" autocomplete="off" maxlength="40"
                            placeholder="e.g. LawnAce2026!" />
                        <p class="description">
                            Password for the Lawn Ace client dashboard (Sales &amp; Service team — no WordPress login needed).<br>
                            <?php
                            $client_pin = get_option( 'lawnace_client_pin', '' );
                            $client_url = home_url( '/la-team/' );
                            if ( ! empty( $client_pin ) ) :
                            ?>
                            URL: <strong><a href="<?php echo esc_url( $client_url ); ?>" target="_blank"><?php echo esc_url( $client_url ); ?></a></strong>
                            — share this with Kyle's team.
                            <?php else : ?>
                            Set a password above to enable the client dashboard.
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>Morning Digest Email</th>
                    <td>
                        <label>
                            <input type="checkbox" name="lawnace_digest_enabled" value="1" <?php checked( get_option( 'lawnace_digest_enabled', '1' ), '1' ); ?> />
                            Send a daily <?php echo esc_html( LAWNACE_DIGEST_HOUR ); ?>:00 AM brief to the team
                        </label>
                        <p class="description">
                            New leads (with phone &amp; address), pricing questions, service issues, drop-offs, and the open task count from the last 24 hours.<br>
                            <?php
                            $next = wp_next_scheduled( LAWNACE_DIGEST_HOOK );
                            $last = get_option( 'lawnace_digest_last_sent', null );
                            if ( $next ) {
                                echo 'Next send: <strong>' . esc_html( wp_date( 'D M j, g:i a', $next ) ) . '</strong> (' . esc_html( wp_timezone_string() ) . ').';
                            } else {
                                echo 'Not scheduled yet — save settings or reload this page.';
                            }
                            if ( is_array( $last ) && ! empty( $last['time'] ) ) {
                                echo ' Last sent: <strong>' . esc_html( $last['time'] ) . '</strong>'
                                    . ( empty( $last['ok'] ) ? ' <span style="color:#c0392b;">(mail failed)</span>' : '' )
                                    . ( ! empty( $last['test'] ) ? ' (test)' : '' ) . '.';
                            }
                            ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="lawnace_digest_emails">Digest Recipients</label></th>
                    <td>
                        <input type="text" id="lawnace_digest_emails" name="lawnace_digest_emails"
                            value="<?php echo esc_attr( get_option( 'lawnace_digest_emails', '' ) ); ?>"
                            class="regular-text" placeholder="kyle@lawnace.com, tammi@lawnace.com" />
                        <p class="description">Comma-separated. Leave blank to send to the Lead Notification Email above.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="lawnace_team_members">Team Members</label></th>
                    <td>
                        <input type="text" id="lawnace_team_members" name="lawnace_team_members"
                            value="<?php echo esc_attr( get_option( 'lawnace_team_members', 'Kyle, Tammi, Tim' ) ); ?>"
                            class="regular-text" placeholder="Kyle, Tammi, Tim" />
                        <p class="description">Names that appear in the "Assigned to" dropdown on the team dashboard. Comma-separated.</p>
                    </td>
                </tr>
            </table>
            <table class="form-table" role="presentation">
                <tr>
                    <th>Current Model</th>
                    <td><code><?php echo esc_html( LAWNACE_MODEL ); ?></code></td>
                </tr>
                <tr>
                    <th>Rate Limit</th>
                    <td><code><?php echo esc_html( LAWNACE_RATE_LIMIT ); ?> requests / IP / hour</code></td>
                </tr>
            </table>
            <?php submit_button( 'Save Settings' ); ?>
        </form>

        <?php if ( isset( $_GET['lawnace_digest'] ) ) : ?>
            <?php if ( $_GET['lawnace_digest'] === 'sent' ) : ?>
                <div class="notice notice-success"><p>✅ Test digest sent to <?php echo esc_html( implode( ', ', lawnace_digest_recipients() ) ); ?>.</p></div>
            <?php else : ?>
                <div class="notice notice-error"><p>❌ Digest was not sent. Check that at least one recipient is set and that the site can send mail.</p></div>
            <?php endif; ?>
        <?php endif; ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:-8px;margin-bottom:8px;">
            <input type="hidden" name="action" value="lawnace_send_test_digest">
            <?php wp_nonce_field( 'lawnace_send_test_digest' ); ?>
            <?php submit_button( 'Send Test Digest Now', 'secondary', 'submit', false ); ?>
            <span class="description" style="margin-left:8px;">Sends the real last-24-hours brief to the recipients above, right now. Save settings first if you changed them.</span>
        </form>

        <hr>
        <h2>Update Plugin</h2>
        <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:6px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#555;">
            ⚠️ <strong>Always update here</strong> — not via Plugins → Add New. Using the WordPress uploader installs a second copy and causes a conflict. This form overwrites the existing plugin in place.
        </div>

        <?php if ( isset( $_GET['lawnace_updated'] ) ) : ?>
            <?php if ( $_GET['lawnace_updated'] === 'success' ) : ?>
                <div class="notice notice-success"><p>✅ Plugin updated successfully to version <?php echo esc_html( LAWNACE_VERSION ); ?>.</p></div>
            <?php else : ?>
                <div class="notice notice-error"><p>❌ Update failed. Please try again or check the error log.</p></div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="lawnace_update_plugin">
            <?php wp_nonce_field( 'lawnace_update_plugin' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="lawnace_zip">New Plugin ZIP</label></th>
                    <td>
                        <input type="file" name="lawnace_zip" id="lawnace_zip" accept=".zip" required />
                        <p class="description">Select <code>lawnace-chatbot.zip</code> from your computer.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Upload &amp; Update Now', 'secondary' ); ?>
        </form>
    </div>
    <?php
}

/*
|--------------------------------------------------------------------------
| COMPETITOR KEYWORDS
|--------------------------------------------------------------------------
*/

function lawnace_get_competitors() {
    return [
        'TruGreen'                    => [ 'trugreen', 'tru green', 'tru-green', 'truegreen', 'true green' ],
        'MTM / Matthews Turf'         => [ 'mtm', 'matthews turf', 'matthew turf', 'matthews lawn' ],
        'Fairway Lawns'               => [ 'fairway lawn', 'fairway lawns', 'fairway' ],
        "Kathleen's Lawn & Shrub"     => [ "kathleen's", 'kathleens lawn', 'kathleen lawn', 'kathleen shrub' ],
        'Lawn Doctor'                 => [ 'lawn doctor' ],
        'Spring-Green'                => [ 'spring green', 'spring-green' ],
        'Mosquito Joe'                => [ 'mosquito joe' ],
        'BioTech'                     => [ 'biotech', 'bio tech', 'bio-tech' ],
        'Terminix'                    => [ 'terminix' ],
        'Orkin'                       => [ 'orkin' ],
        'Scotts / Miracle-Gro'        => [ 'scotts', 'miracle-gro', 'miracle gro' ],
        'HomeAdvisor / Angi'          => [ 'homeadvisor', 'home advisor', 'angi', 'angie' ],
        'Other Company'               => [ 'other company', 'another company', 'competitor', 'someone else', 'different company' ],
    ];
}

function lawnace_detect_competitors( $text ) {
    $text  = strtolower( $text );
    $found = [];
    foreach ( lawnace_get_competitors() as $name => $keywords ) {
        foreach ( $keywords as $kw ) {
            if ( strpos( $text, $kw ) !== false ) {
                $found[] = $name;
                break;
            }
        }
    }
    return $found;
}

/*
|--------------------------------------------------------------------------
| TOPIC KEYWORDS
|--------------------------------------------------------------------------
*/

function lawnace_get_topics() {
    return [
        'Brown Patches / Yellowing' => [ 'brown', 'yellow', 'patch', 'dead spot', 'dying', 'bare spot', 'discolor' ],
        'Weeds'                     => [ 'weed', 'crabgrass', 'nutsedge', 'clover', 'dollar weed', 'dandelion', 'broadleaf', 'spurge' ],
        'Mosquitoes'                => [ 'mosquito', 'mosquitoes', 'mosquito control' ],
        'Fertilization'             => [ 'fertiliz', 'fertil', 'green up', 'thin', 'color', 'pale' ],
        'Core Aeration'             => [ 'aerati', 'core aeration', 'plug', 'thatch', 'compacted' ],
        'Fire Ants'                 => [ 'fire ant', 'mound', 'ant mound', 'fire ants' ],
        'Insects / Grubs'           => [ 'grub', 'insect', 'chinch', 'armyworm', 'bug', 'larvae', 'sod webworm' ],
        'Tree & Shrub'              => [ 'tree', 'shrub', 'bush', 'hedge', 'plant' ],
        'Pricing / Quote'           => [ 'quote', 'price', 'pricing', 'cost', 'estimate', 'how much', 'charge', 'fee' ],
        'Billing / Account'         => [ 'bill', 'payment', 'invoice', 'account', 'login', 'portal', 'pay', 'balance' ],
        'Service Issue'             => [ 'still have', 'not working', 'complaint', 'problem', 'issue', 'came back', 'didn\'t work' ],
        'Grass Type'                => [ 'bermuda', 'zoysia', 'fescue', 'centipede', 'st. augustine', 'grass type', 'what kind of grass' ],
    ];
}

function lawnace_categorize_message( $text ) {
    $text   = strtolower( $text );
    $topics = lawnace_get_topics();
    $found  = [];

    foreach ( $topics as $topic => $keywords ) {
        foreach ( $keywords as $kw ) {
            if ( strpos( $text, $kw ) !== false ) {
                $found[] = $topic;
                break;
            }
        }
    }

    return $found ?: [ 'General' ];
}

/*
|--------------------------------------------------------------------------
| SELF-UPDATE HANDLER
|--------------------------------------------------------------------------
*/

add_action( 'admin_post_lawnace_update_plugin', 'lawnace_handle_plugin_update' );

function lawnace_handle_plugin_update() {
    if ( ! current_user_can( 'update_plugins' ) ) {
        wp_die( 'Unauthorized' );
    }

    check_admin_referer( 'lawnace_update_plugin' );

    $redirect = admin_url( 'admin.php?page=lawnace-settings' );

    if ( empty( $_FILES['lawnace_zip']['tmp_name'] ) ) {
        wp_redirect( add_query_arg( 'lawnace_updated', 'error', $redirect ) );
        exit;
    }

    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';

    // Move upload to a temp file WP can use
    $tmp = wp_tempnam( 'lawnace-chatbot.zip' );
    move_uploaded_file( $_FILES['lawnace_zip']['tmp_name'], $tmp );

    $upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );
    $result   = $upgrader->install( $tmp, [ 'overwrite_package' => true ] );

    @unlink( $tmp );

    if ( is_wp_error( $result ) || $result === false ) {
        wp_redirect( add_query_arg( 'lawnace_updated', 'error', $redirect ) );
    } else {
        // Re-activate plugin after update
        activate_plugin( 'lawnace-chatbot/lawnace-chatbot.php' );
        wp_redirect( add_query_arg( 'lawnace_updated', 'success', $redirect ) );
    }

    exit;
}

/*
|--------------------------------------------------------------------------
| CLEAR LOGS
|--------------------------------------------------------------------------
*/

add_action( 'admin_post_lawnace_clear_logs', 'lawnace_handle_clear_logs' );

function lawnace_handle_clear_logs() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
    check_admin_referer( 'lawnace_clear_logs' );

    global $wpdb;
    $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}lawnace_chat_logs" );
    delete_option( 'lawnace_insights_cache' );

    wp_redirect( add_query_arg( 'cleared', '1', admin_url( 'admin.php?page=lawnace-chat-logs' ) ) );
    exit;
}

/*
|--------------------------------------------------------------------------
| AI INSIGHTS
|--------------------------------------------------------------------------
*/

add_action( 'wp_ajax_lawnace_generate_insights', 'lawnace_ajax_generate_insights' );

function lawnace_ajax_generate_insights() {
    check_ajax_referer( 'lawnace_insights_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

    global $wpdb;
    $table   = $wpdb->prefix . 'lawnace_chat_logs';
    $api_key = get_option( 'lawnace_anthropic_key', '' );

    if ( empty( $api_key ) ) {
        wp_send_json_error( 'API key not configured.' );
    }

    // Pull last 200 user messages
    $messages = $wpdb->get_col(
        "SELECT message FROM {$table} WHERE role = 'user' ORDER BY created_at DESC LIMIT 200"
    );

    // Pull stats
    $total_sessions = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM {$table}" );
    $lead_sessions  = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM {$table} WHERE role = 'lead'" );
    $conversion     = $total_sessions > 0 ? round( ( $lead_sessions / $total_sessions ) * 100, 1 ) : 0;

    // Topic breakdown
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

    $prompt = "You are a lawn care business analyst reviewing chat data for Lawn Ace, a locally owned lawn care company in Augusta, GA serving the CSRA area (Augusta, Evans, Martinez, Grovetown, North Augusta, Aiken, and surrounding communities).

Services offered: Lawn Fertilization, Weed Control, Mosquito Control, Tree & Shrub Care, Core Aeration, Fire Ant Control, Insect Control, Grub Control.

CHAT STATISTICS (all time):
- Total sessions: {$total_sessions}
- Leads captured: {$lead_sessions}
- Lead conversion rate: {$conversion}%

TOPIC BREAKDOWN (from {$total_sessions} sessions):
{$topic_summary}

SAMPLE OF RECENT CUSTOMER MESSAGES (last 100):
---
{$message_sample}
---

Based on this real customer chat data, provide a focused business analysis. Do NOT include a title or introductory header — go straight into the sections. Format each section header exactly as: ## N. SECTION NAME (nothing else on that line).

## 1. LEAD CONVERSION OPPORTUNITIES
Based on the {$conversion}% conversion rate and the topics discussed, what specific changes could increase leads?

## 2. WHAT CUSTOMERS ARE REALLY ASKING ABOUT
Identify the most common specific questions and concerns hiding inside the 'General' category. What topics keep coming up that aren't being captured?

## 3. CONTENT & BLOG RECOMMENDATIONS
Specific blog post or FAQ topics Lawn Ace should create based on what customers are asking. Be specific — suggest actual titles.

## 4. CHATBOT IMPROVEMENTS
What topics or questions is the chatbot likely struggling with? What knowledge gaps should be added to Lawnie's training?

## 5. SEASONAL / TIMING OBSERVATIONS
Any patterns in what people are asking about that suggest timing opportunities for proactive outreach or promotions?

## 6. TOP 3 ACTION ITEMS
The three highest-impact things Lawn Ace should do right now based on this data.

Be specific, direct, and actionable. Write for a small business owner, not a data analyst. Keep each section concise. Use **bold** sparingly to highlight the single most important number, finding, or action item in each section — not every sentence, just the thing they must not miss.";

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
        wp_send_json_error( 'API connection failed: ' . $response->get_error_message() );
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
| CSV EXPORT
|--------------------------------------------------------------------------
*/

add_action( 'admin_post_lawnace_export_csv', 'lawnace_export_csv' );

function lawnace_export_csv() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
    check_admin_referer( 'lawnace_export_csv' );

    global $wpdb;
    $table = $wpdb->prefix . 'lawnace_chat_logs';

    $rows = $wpdb->get_results( "SELECT session_id, ip_address, role, message, created_at FROM {$table} ORDER BY created_at DESC", ARRAY_A );

    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="lawnace-chat-logs-' . date( 'Y-m-d' ) . '.csv"' );

    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, [ 'Session ID', 'IP Address', 'Role', 'Message', 'Created At' ] );

    foreach ( $rows as $row ) {
        fputcsv( $out, $row );
    }

    fclose( $out );
    exit;
}

/*
|--------------------------------------------------------------------------
| DASHBOARD
|--------------------------------------------------------------------------
*/

function lawnace_render_dashboard() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    global $wpdb;
    lawnace_chatbot_ensure_log_table();
    $table = $wpdb->prefix . 'lawnace_chat_logs';

    // Session detail view
    if ( ! empty( $_GET['session'] ) ) {
        lawnace_render_session_detail( sanitize_text_field( $_GET['session'] ), $table );
        return;
    }

    // Date range
    $range = isset( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : 'month';
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

    $conversion = $total_sessions > 0 ? round( ( $lead_sessions / $total_sessions ) * 100, 1 ) : 0;

    $total_messages = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE role = 'user' AND created_at >= %s", $since
    ) );

    // ── Topic breakdown ────────────────────────────────────────────────

    $user_message_rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT session_id, message, created_at FROM {$table} WHERE role = 'user' AND created_at >= %s ORDER BY created_at DESC", $since
    ) );
    $user_messages     = wp_list_pluck( $user_message_rows, 'message' );
    $all_user_messages = $user_messages; // alias used by ZIP section below

    $topic_counts = [];
    foreach ( $user_messages as $msg ) {
        $topics = lawnace_categorize_message( $msg );
        foreach ( $topics as $t ) {
            $topic_counts[ $t ] = ( $topic_counts[ $t ] ?? 0 ) + 1;
        }
    }
    arsort( $topic_counts );
    $total_topic_hits = array_sum( $topic_counts ) ?: 1;

    // ── Peak hours ─────────────────────────────────────────────────────

    $hour_rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT HOUR(created_at) AS hr, COUNT(*) AS cnt
         FROM {$table}
         WHERE role = 'user' AND created_at >= %s
         GROUP BY hr ORDER BY hr ASC", $since
    ) );
    $hours = array_fill( 0, 24, 0 );
    foreach ( $hour_rows as $r ) $hours[ (int) $r->hr ] = (int) $r->cnt;
    $max_hour = max( $hours ) ?: 1;

    // ── ZIP code breakdown ─────────────────────────────────────────────
    // $all_user_messages already set above

    $zip_counts = [];
    foreach ( $all_user_messages as $msg ) {
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

    // ── Frustrated sessions (≤ 2 user messages) ────────────────────────

    $frustrated = $wpdb->get_results( $wpdb->prepare(
        "SELECT session_id, COUNT(*) AS msg_count, MIN(created_at) AS started
         FROM {$table}
         WHERE role = 'user' AND created_at >= %s
         GROUP BY session_id
         HAVING msg_count <= 2
         ORDER BY started DESC LIMIT 20", $since
    ) );

    // ── Ratings ────────────────────────────────────────────────────────
    $ratings_table = $wpdb->prefix . 'lawnace_ratings';
    $ratings_exist = $wpdb->get_var( "SHOW TABLES LIKE '{$ratings_table}'" ) === $ratings_table;

    $thumbs_up   = 0;
    $thumbs_down = 0;
    if ( $ratings_exist ) {
        $thumbs_up   = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$ratings_table} WHERE rating = 1 AND created_at >= %s", $since
        ) );
        $thumbs_down = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$ratings_table} WHERE rating = -1 AND created_at >= %s", $since
        ) );
    }
    $total_ratings  = $thumbs_up + $thumbs_down;
    $approval_rate  = $total_ratings > 0 ? round( ( $thumbs_up / $total_ratings ) * 100 ) : 0;

    $base_url   = admin_url( 'admin.php?page=lawnace-chat-logs' );
    $export_url = wp_nonce_url( admin_url( 'admin-post.php?action=lawnace_export_csv' ), 'lawnace_export_csv' );
    $cached_insights = get_option( 'lawnace_insights_cache', null );

    // ── Render ─────────────────────────────────────────────────────────
    ?>
    <style>
    .la-dash { max-width: 1100px; }
    .la-dash h1 { display:flex; align-items:center; gap:12px; }
    .la-dash .la-actions { display:flex; gap:10px; align-items:center; margin-bottom:20px; flex-wrap:wrap; }
    .la-dash .la-range-btn { text-decoration:none; padding:5px 14px; border-radius:4px; border:1px solid #c3c4c7; background:#fff; color:#1d2327; font-size:13px; }
    .la-dash .la-range-btn.active { background:#6559b1; border-color:#6559b1; color:#fff; }
    .la-stat-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; margin-bottom:24px; }
    .la-stat { background:#fff; border:1px solid #ddd; border-radius:8px; padding:16px 20px; }
    .la-stat-num { font-size:32px; font-weight:700; color:#6559b1; line-height:1; }
    .la-stat-label { font-size:12px; color:#666; margin-top:4px; }
    .la-panels { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; }
    @media(max-width:700px){ .la-panels{grid-template-columns:1fr;} }
    .la-panel { background:#fff; border:1px solid #ddd; border-radius:8px; padding:16px 20px; }
    .la-panel h3 { margin:0 0 14px; font-size:14px; color:#1d2327; }
    .la-bar-row { display:flex; align-items:center; gap:8px; margin-bottom:7px; font-size:12px; }
    .la-bar-label { width:170px; flex-shrink:0; color:#333; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .la-bar-track { flex:1; background:#f0f0f0; border-radius:4px; height:14px; overflow:hidden; }
    .la-bar-fill { height:100%; background:#6559b1; border-radius:4px; transition:width .3s; }
    .la-bar-fill.green { background:#a9c33f; }
    .la-bar-pct { width:38px; text-align:right; color:#666; }
    .la-hour-grid { display:grid; grid-template-columns:repeat(12,1fr); gap:3px; }
    .la-hour-cell { text-align:center; }
    .la-hour-bar-wrap { height:50px; display:flex; align-items:flex-end; justify-content:center; }
    .la-hour-bar { width:100%; background:#a9c33f; border-radius:2px 2px 0 0; min-height:2px; }
    .la-hour-lbl { font-size:9px; color:#888; margin-top:2px; }
    .la-export-btn { display:inline-block; padding:6px 16px; background:#a9c33f; color:#fff; border-radius:4px; text-decoration:none; font-size:13px; font-weight:600; }
    .la-export-btn:hover { background:#7a9a2a; color:#fff; }
    </style>

    <div class="wrap la-dash">
        <h1>
            <img src="<?php echo esc_url( LAWNACE_PLUGIN_URL . 'assets/images/lawnie-icon.png' ); ?>" alt="" style="width:40px;height:40px;border-radius:50%;vertical-align:middle;margin-right:10px;">Lawnie Dashboard
            <a href="<?php echo esc_url( $export_url ); ?>" class="la-export-btn">⬇ Export CSV</a>
            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=lawnace_clear_logs' ), 'lawnace_clear_logs' ) ); ?>"
               class="la-export-btn"
               style="background:#d63638;margin-left:8px;"
               onclick="return confirm('⚠️ This will permanently delete ALL chat logs and reset the insights cache. This cannot be undone.\n\nAre you sure?')">
               🗑 Clear All Data
            </a>
        </h1>

        <?php if ( ! empty( $_GET['cleared'] ) ) : ?>
            <div class="notice notice-success is-dismissible"><p>✅ All chat logs cleared. Fresh start from today.</p></div>
        <?php endif; ?>

        <div class="la-actions">
            <strong>Date Range:</strong>
            <?php foreach ( [ 'week' => 'Last 7 Days', 'month' => 'Last 30 Days', 'all' => 'All Time' ] as $key => $lbl ) : ?>
            <a href="<?php echo esc_url( add_query_arg( 'range', $key, $base_url ) ); ?>"
               class="la-range-btn <?php echo $range === $key ? 'active' : ''; ?>">
                <?php echo esc_html( $lbl ); ?>
            </a>
            <?php endforeach; ?>
            <span style="color:#666;font-size:12px;">Showing: <strong><?php echo esc_html( $label ); ?></strong></span>
        </div>

        <!-- Stat cards -->
        <div class="la-stat-grid">
            <div class="la-stat">
                <div class="la-stat-num"><?php echo esc_html( $total_sessions ); ?></div>
                <div class="la-stat-label">Total Chat Sessions</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num"><?php echo esc_html( $lead_sessions ); ?></div>
                <div class="la-stat-label">Leads Captured</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num"><?php echo esc_html( $conversion ); ?>%</div>
                <div class="la-stat-label">Lead Conversion Rate</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num"><?php echo esc_html( $total_messages ); ?></div>
                <div class="la-stat-label">Total Customer Messages</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num"><?php echo count( $frustrated ); ?></div>
                <div class="la-stat-label">Short / Drop-off Sessions</div>
            </div>
            <div class="la-stat">
                <div class="la-stat-num" style="color:<?php echo $approval_rate >= 70 ? '#2f7d22' : ( $approval_rate >= 40 ? '#e65100' : '#c62828' ); ?>">
                    <?php echo $total_ratings > 0 ? $approval_rate . '%' : '—'; ?>
                </div>
                <div class="la-stat-label">👍 Approval Rate (<?php echo esc_html( $thumbs_up ); ?>↑ <?php echo esc_html( $thumbs_down ); ?>↓)</div>
            </div>
        </div>

        <!-- ZIP + Competitors side by side -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">

            <!-- ZIP Code Panel -->
            <div class="la-panel">
                <h3>📍 ZIP Codes Mentioned</h3>
                <p style="font-size:11px;color:#888;margin-top:-8px;">Top ZIP codes from customer messages in this period.</p>
                <?php if ( empty( $zip_counts ) ) : ?>
                    <p style="color:#888;font-size:13px;">No ZIP codes detected yet.</p>
                <?php else : ?>
                    <?php $max_zip = max( $zip_counts ) ?: 1; ?>
                    <?php foreach ( $zip_counts as $zip => $cnt ) :
                        $pct = round( ( $cnt / $max_zip ) * 100 );
                    ?>
                        <div class="la-bar-row">
                            <span class="la-bar-label" style="width:60px;font-weight:600;"><?php echo esc_html( $zip ); ?></span>
                            <div class="la-bar-track">
                                <div class="la-bar-fill green" style="width:<?php echo esc_attr( $pct ); ?>%"></div>
                            </div>
                            <span class="la-bar-pct"><?php echo esc_html( $cnt ); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Competitors Panel -->
            <div class="la-panel">
                <h3>🥊 Competitors Mentioned</h3>
                <p style="font-size:11px;color:#888;margin-top:-8px;">Click any row to see the messages.</p>
                <?php if ( empty( $competitor_counts ) ) : ?>
                    <p style="color:#888;font-size:13px;">No competitors detected yet — great sign!</p>
                <?php else : ?>
                    <?php $max_comp = max( $competitor_counts ) ?: 1; ?>
                    <?php foreach ( $competitor_counts as $comp => $cnt ) :
                        $pct = round( ( $cnt / $max_comp ) * 100 );
                    ?>
                        <div class="la-bar-row la-comp-row" data-comp="<?php echo esc_attr( $comp ); ?>" title="Click to see messages" style="cursor:pointer;border-radius:6px;padding:4px 6px;margin:0 -6px;transition:background .15s;">
                            <span class="la-bar-label" style="color:#d63638;font-weight:600;" title="<?php echo esc_attr( $comp ); ?>"><?php echo esc_html( $comp ); ?></span>
                            <div class="la-bar-track">
                                <div class="la-bar-fill" style="width:<?php echo esc_attr( $pct ); ?>%;background:#d63638;"></div>
                            </div>
                            <span class="la-bar-pct"><?php echo esc_html( $cnt ); ?> <span style="color:#d63638;font-size:10px;">▶</span></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

        <!-- Competitor Modal -->
        <div id="la-comp-modal" style="display:none;position:fixed;inset:0;z-index:99999;display:none;align-items:flex-end;justify-content:center;">
            <div id="la-comp-backdrop" style="position:absolute;inset:0;background:rgba(0,0,0,.5);"></div>
            <div id="la-comp-drawer" style="position:relative;background:#fff;border-radius:16px 16px 0 0;width:100%;max-width:780px;max-height:75vh;display:flex;flex-direction:column;box-shadow:0 -8px 32px rgba(0,0,0,.25);">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #eee;flex-shrink:0;">
                    <div>
                        <div id="la-comp-title" style="font-size:16px;font-weight:700;color:#1d2327;"></div>
                        <div id="la-comp-sub" style="font-size:12px;color:#aaa;margin-top:3px;"></div>
                    </div>
                    <button id="la-comp-close" style="background:none;border:none;font-size:26px;cursor:pointer;color:#aaa;line-height:1;padding:0 6px;">&times;</button>
                </div>
                <div id="la-comp-body" style="overflow-y:auto;padding:16px 22px;flex:1;"></div>
            </div>
        </div>

        <style>
        .la-comp-row:hover { background:#fff5f5; }
        @keyframes laSlideUp{from{transform:translateY(60px);opacity:0}to{transform:translateY(0);opacity:1}}
        #la-comp-drawer{animation:laSlideUp .22s ease;}
        </style>

        <script>
        (function(){
            var compData = <?php echo wp_json_encode( $competitor_sessions ); ?>;
            var baseUrl  = <?php echo wp_json_encode( admin_url( 'admin.php?page=lawnace-chat-logs' ) ); ?>;
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
                    var sessionUrl = baseUrl + '&session=' + encodeURIComponent(r.session_id) + '&range=' + range;
                    var div = document.createElement('div');
                    div.style.cssText = 'border:1px solid #f0f0f0;border-radius:8px;padding:12px 14px;margin-bottom:10px;background:#fafafa;';
                    div.innerHTML =
                        '<div style="font-size:13px;color:#333;line-height:1.6;margin-bottom:8px;">' + escHtml(r.message) + '</div>' +
                        '<div style="display:flex;align-items:center;justify-content:space-between;font-size:11px;color:#aaa;">' +
                            '<span>' + escHtml(r.created_at) + '</span>' +
                            '<a href="' + escHtml(sessionUrl) + '" style="color:#6559b1;font-weight:600;text-decoration:none;">View full session →</a>' +
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
                    <p style="color:#888;font-size:13px;">No data yet.</p>
                <?php else : ?>
                    <?php foreach ( $topic_counts as $topic => $count ) :
                        $pct = round( ( $count / $total_topic_hits ) * 100 );
                    ?>
                    <div class="la-bar-row">
                        <span class="la-bar-label" title="<?php echo esc_attr( $topic ); ?>"><?php echo esc_html( $topic ); ?></span>
                        <div class="la-bar-track">
                            <div class="la-bar-fill" style="width:<?php echo esc_attr( $pct ); ?>%"></div>
                        </div>
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

        <!-- Lawnie Intelligence -->
        <div class="la-panel" id="la-insights-panel" style="margin-bottom:24px;border-left:4px solid #6559b1;">
            <style>
            .la-iheader{font-size:11px;font-weight:800;color:#6559b1;text-transform:uppercase;letter-spacing:.7px;margin:22px 0 8px;padding-bottom:5px;border-bottom:2px solid #6559b1;}
            .la-iheader:first-child{margin-top:0;}
            .la-iline{font-size:13px;line-height:1.75;color:#1d2327;}
            .la-ibullet{font-size:13px;line-height:1.75;color:#1d2327;padding-left:16px;position:relative;}
            .la-ibullet::before{content:'•';position:absolute;left:4px;color:#a9c33f;font-weight:700;}
            .la-igap{height:5px;}
            </style>
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
                <div>
                    <h3 style="margin:0;font-size:15px;">✨ Lawnie Intelligence</h3>
                    <?php if ( $cached_insights ) : ?>
                        <span style="font-size:11px;color:#888;">Last generated: <?php echo esc_html( $cached_insights['generated'] ); ?> &mdash; based on <?php echo esc_html( $cached_insights['sessions'] ); ?> sessions</span>
                    <?php else : ?>
                        <span style="font-size:11px;color:#888;">No analysis generated yet. Click the button to get your first insights.</span>
                    <?php endif; ?>
                </div>
                <button id="la-insights-btn" class="button button-primary"
                    style="background:#6559b1;border-color:#4a3f99;"
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'lawnace_insights_nonce' ) ); ?>">
                    <?php echo $cached_insights ? '🔄 Refresh Analysis' : '✨ Generate'; ?>
                </button>
            </div>
            <div id="la-insights-loading" style="display:none;color:#6559b1;font-size:13px;padding:10px 0;">
                ⏳ Analyzing your chat data — this takes about 15 seconds...
            </div>
            <div id="la-insights-output"
                 data-raw="<?php echo $cached_insights ? esc_attr( $cached_insights['text'] ) : ''; ?>">
            </div>
        </div>

        <!-- Row 2: Leads + Drop-offs -->
        <div class="la-panels">

            <div class="la-panel">
                <h3>🎯 Recent Leads</h3>
                <?php if ( empty( $recent_leads ) ) : ?>
                    <p style="color:#888;font-size:13px;">No leads captured yet.</p>
                <?php else : ?>
                    <table class="widefat striped" style="font-size:12px;">
                        <thead><tr><th>Info</th><th>Time</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ( $recent_leads as $lead ) : ?>
                        <tr>
                            <td><?php echo esc_html( $lead->message ); ?></td>
                            <td style="white-space:nowrap"><?php echo esc_html( $lead->created_at ); ?></td>
                            <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $lead->session_id, 'range' => $range ], $base_url ) ); ?>">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="la-panel">
                <h3>⚠️ Short / Drop-off Sessions</h3>
                <p style="font-size:11px;color:#888;margin-top:-8px;">Sessions with 2 or fewer customer messages.</p>
                <?php if ( empty( $frustrated ) ) : ?>
                    <p style="color:#888;font-size:13px;">None found.</p>
                <?php else : ?>
                    <table class="widefat striped" style="font-size:12px;">
                        <thead><tr><th>Session</th><th>Messages</th><th>Started</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ( $frustrated as $s ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( substr( $s->session_id, 0, 12 ) ); ?>&hellip;</code></td>
                            <td><?php echo esc_html( $s->msg_count ); ?></td>
                            <td><?php echo esc_html( $s->started ); ?></td>
                            <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $s->session_id, 'range' => $range ], $base_url ) ); ?>">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        </div>

        <!-- Session list -->
        <div class="la-panel" style="margin-bottom:20px;">
            <h3>💬 All Sessions</h3>
            <?php
            $sessions = $wpdb->get_results( $wpdb->prepare(
                "SELECT session_id,
                        MIN(created_at) AS started,
                        COUNT(*) AS messages,
                        MAX(CASE WHEN role = 'lead' THEN 1 ELSE 0 END) AS has_lead
                 FROM {$table}
                 WHERE created_at >= %s
                 GROUP BY session_id
                 ORDER BY started DESC
                 LIMIT 200", $since
            ) );
            ?>
            <table class="widefat striped" style="font-size:13px;">
                <thead><tr>
                    <th>Session</th><th>Started</th><th>Messages</th><th>Lead</th><th></th>
                </tr></thead>
                <tbody>
                <?php foreach ( $sessions as $s ) : ?>
                <tr>
                    <td><code><?php echo esc_html( substr( $s->session_id, 0, 18 ) ); ?>&hellip;</code></td>
                    <td><?php echo esc_html( $s->started ); ?></td>
                    <td><?php echo esc_html( $s->messages ); ?></td>
                    <td><?php echo $s->has_lead ? '<span style="color:#2f7d22;font-weight:700;">✓ Lead</span>' : '&mdash;'; ?></td>
                    <td><a href="<?php echo esc_url( add_query_arg( [ 'session' => $s->session_id, 'range' => $range ], $base_url ) ); ?>">View</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

    <script>
    (function() {
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

        btn.addEventListener('click', function() {
            btn.disabled = true;
            btn.textContent = 'Analyzing...';
            loading.style.display = 'block';
            output.innerHTML = '';

            var data = new FormData();
            data.append('action', 'lawnace_generate_insights');
            data.append('nonce', btn.dataset.nonce);

            fetch(ajaxurl, { method: 'POST', credentials: 'same-origin', body: data })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                loading.style.display = 'none';
                if (res.success && res.data && res.data.text) {
                    output.innerHTML = formatInsights(res.data.text);
                    btn.textContent = '🔄 Refresh Analysis';
                } else {
                    output.innerHTML = '<div class="la-iline" style="color:#c62828;">Error: ' + escHtml(res.data || 'Unknown error. Check API key in Settings.') + '</div>';
                    btn.textContent = '✨ Try Again';
                }
                btn.disabled = false;
            })
            .catch(function() {
                loading.style.display = 'none';
                output.innerHTML = '<div class="la-iline" style="color:#c62828;">Request failed. Please try again.</div>';
                btn.textContent = '✨ Try Again';
                btn.disabled = false;
            });
        });
    })();
    </script>
    <?php
}

/*
|--------------------------------------------------------------------------
| SESSION DETAIL
|--------------------------------------------------------------------------
*/

function lawnace_render_session_detail( $session_id, $table ) {
    global $wpdb;
    $range    = isset( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : 'month';
    $back_url = add_query_arg( 'range', $range, remove_query_arg( 'session' ) );

    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT role, message, created_at FROM {$table} WHERE session_id = %s ORDER BY created_at ASC",
        $session_id
    ) );
    ?>
    <div class="wrap">
        <h1>Chat Session Detail</h1>
        <p><a href="<?php echo esc_url( $back_url ); ?>">&larr; Back to Dashboard</a></p>
        <p style="color:#666;font-size:13px;">Session: <code><?php echo esc_html( $session_id ); ?></code></p>
        <table class="widefat striped" style="font-size:13px;">
            <thead><tr>
                <th style="width:80px">Role</th>
                <th>Message</th>
                <th style="width:160px">Time</th>
            </tr></thead>
            <tbody>
            <?php foreach ( $rows as $row ) :
                $color = $row->role === 'user' ? '#1d2327' : ( $row->role === 'lead' ? '#2f7d22' : '#4a3f99' );
            ?>
            <tr>
                <td><strong style="color:<?php echo esc_attr( $color ); ?>"><?php echo esc_html( ucfirst( $row->role ) ); ?></strong></td>
                <td style="white-space:pre-wrap"><?php echo esc_html( $row->message ); ?></td>
                <td><?php echo esc_html( $row->created_at ); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
