<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Echo64_Automations — Discord webhook + weekly email (#15 + #16)
 */
class Echo64_Automations {

    private static ?Echo64_Automations $instance = null;

    public static function instance(): self {
        if ( self::$instance === null ) self::$instance = new self();
        return self::$instance;
    }

    // ── v1.4.0  Cancelled shows for the weekly essay rotation ────────────
    private const ESSAY_SHOWS = [
        'Firefly (2002)',
        'Farscape',
        'Dark Matter',
        'Revolution',
        'Almost Human',
        'Dollhouse',
        'Pushing Daisies',
        'Defying Gravity',
        'Terminator: The Sarah Connor Chronicles',
        'Caprica',
        'Jericho',
        'Alcatraz',
        'Flash Forward',
        'Terra Nova',
        'The Expanse (pre-Amazon era)',
    ];

    private function __construct() {
        add_action( 'echo64_daily_discord',       [ $this, 'run_discord_post' ] );
        add_action( 'echo64_weekly_email',        [ $this, 'run_weekly_email' ] );
        add_action( 'echo64_weekly_essay',        [ $this, 'run_weekly_essay' ] );   // v1.4.0
        add_action( 'admin_post_echo64_test_discord',      [ $this, 'handle_test_discord' ] );
        add_action( 'admin_post_echo64_test_email',        [ $this, 'handle_test_email' ] );
        add_action( 'admin_post_echo64_generate_essay',    [ $this, 'handle_generate_essay' ] );  // v1.4.0
    }

    // ── Cron scheduling ───────────────────────────────────────────────────

    public static function schedule(): void {
        if ( ! wp_next_scheduled( 'echo64_daily_discord' ) ) {
            wp_schedule_event( strtotime( 'today 12:00:00' ), 'daily', 'echo64_daily_discord' );
        }
        if ( ! wp_next_scheduled( 'echo64_weekly_email' ) ) {
            wp_schedule_event( strtotime( 'next monday 08:00:00' ), 'weekly', 'echo64_weekly_email' );
        }
        // v1.4.0 — Lost Episode essay every Sunday midnight
        if ( ! wp_next_scheduled( 'echo64_weekly_essay' ) ) {
            wp_schedule_event( strtotime( 'next sunday 00:00:00' ), 'weekly', 'echo64_weekly_essay' );
        }
        // v1.9.4 — Pre-warm poem cache every 2 hours so first visitor never waits
        if ( ! wp_next_scheduled( 'echo64_prewarm_poem' ) ) {
            wp_schedule_event( time(), 'twicedaily', 'echo64_prewarm_poem' );
        }
    }

    public static function unschedule(): void {
        wp_clear_scheduled_hook( 'echo64_daily_discord' );
        wp_clear_scheduled_hook( 'echo64_weekly_email' );
        wp_clear_scheduled_hook( 'echo64_weekly_essay' );
        wp_clear_scheduled_hook( 'echo64_prewarm_poem' );
    }

    // ── #15 Discord Integration ───────────────────────────────────────────

    public function run_discord_post(): void {
        $webhook = get_option( 'echo64_discord_webhook', '' );
        if ( empty( $webhook ) ) return;

        $poem = $this->generate_fresh_poem();
        if ( empty( $poem ) ) return;

        $this->post_to_discord( $webhook, $poem );
    }

    private function generate_fresh_poem(): string {
        $api = new Echo64_Api();
        $system_prompt = get_option( 'echo64_system_prompt', Echo64_Api::DEFAULT_SYSTEM_PROMPT );
        $calendar_ctx  = Echo64_Calendar::get_todays_context();

        $result = $api->send_message(
            [ [ 'role' => 'user', 'content' => Echo64_Api::INIT_TRIGGER ] ],
            $system_prompt . $calendar_ctx
        );

        return is_wp_error( $result ) ? '' : $result;
    }

    private function post_to_discord( string $webhook, string $poem ): void {
        $lines = explode( "\n", trim( $poem ) );
        $today = wp_date( 'F j, Y' );

        // Format as a Discord embed
        $payload = [
            'username'   => 'Echo-64',
            'avatar_url' => ECHO64_PLUGIN_URL . 'assets/images/echo-64-avatar.png',
            'embeds'     => [
                [
                    'title'       => "Echo-64's Poem of the Day · {$today}",
                    'description' => "```\n" . implode( "\n", array_slice( $lines, 0, 6 ) ) . "\n```",
                    'color'       => 0x00F5FF, // neon cyan as decimal
                    'footer'      => [
                        'text' => 'NerdAfterDark.com · Sci-Fi After Midnight',
                    ],
                ],
            ],
        ];

        wp_remote_post( $webhook, [
            'timeout' => 15,
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( $payload ),
        ] );
    }

    public function handle_test_discord(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        check_admin_referer( 'echo64_test_discord' );

        $webhook = get_option( 'echo64_discord_webhook', '' );
        if ( empty( $webhook ) ) {
            wp_redirect( add_query_arg( [ 'page' => 'echo64-settings', 'tab' => 'automations', 'discord_test' => 'no_webhook' ], admin_url( 'options-general.php' ) ) );
            exit;
        }

        $poem = $this->generate_fresh_poem();
        if ( empty( $poem ) ) {
            wp_redirect( add_query_arg( [ 'page' => 'echo64-settings', 'tab' => 'automations', 'discord_test' => 'api_fail' ], admin_url( 'options-general.php' ) ) );
            exit;
        }

        $this->post_to_discord( $webhook, $poem );
        wp_redirect( add_query_arg( [ 'page' => 'echo64-settings', 'tab' => 'automations', 'discord_test' => 'sent' ], admin_url( 'options-general.php' ) ) );
        exit;
    }

    // ── #16 Weekly Transmission Email ─────────────────────────────────────

    public function run_weekly_email(): void {
        $recipient = get_option( 'echo64_email_recipient', get_option( 'admin_email' ) );
        if ( empty( $recipient ) ) return;

        $conversations = $this->get_weeks_best_conversations();
        if ( empty( $conversations ) ) return;

        $subject = 'Echo-64 Weekly Transmission · ' . wp_date( 'F j' );
        $body    = $this->build_email_html( $conversations );

        wp_mail( $recipient, $subject, $body, [
            'Content-Type: text/html; charset=UTF-8',
            'From: Echo-64 <noreply@' . parse_url( home_url(), PHP_URL_HOST ) . '>',
        ] );
    }

    private function get_weeks_best_conversations(): array {
        global $wpdb;
        $t = $wpdb->prefix . 'echo64_conversations';

        $sessions = $wpdb->get_results(
            "SELECT session_id, COUNT(*) as msg_count, MIN(created_at) as started
             FROM {$t}
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY session_id
             HAVING msg_count >= 4
             ORDER BY msg_count DESC
             LIMIT 5",
            ARRAY_A
        );

        if ( empty( $sessions ) ) return [];

        $result = [];
        foreach ( $sessions as $s ) {
            $msgs = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT role, content FROM {$t}
                     WHERE session_id = %s
                       AND content NOT LIKE '[%]'
                     ORDER BY id ASC
                     LIMIT 12",
                    $s['session_id']
                ),
                ARRAY_A
            );
            if ( ! empty( $msgs ) ) {
                $result[] = [
                    'started'   => $s['started'],
                    'msg_count' => $s['msg_count'],
                    'messages'  => $msgs,
                ];
            }
        }
        return $result;
    }

    private function build_email_html( array $conversations ): string {
        $site  = get_bloginfo( 'name' );
        $date  = wp_date( 'F j, Y' );
        $url   = home_url();

        $html  = "<!DOCTYPE html><html><head><meta charset='UTF-8'>
<style>
  body{margin:0;padding:0;background:#050508;color:#dde0f0;font-family:system-ui,sans-serif;}
  .wrap{max-width:620px;margin:0 auto;padding:32px 24px;}
  .header{border-bottom:2px solid #00f5ff;padding-bottom:20px;margin-bottom:28px;}
  .header h1{color:#00f5ff;font-family:'Courier New',monospace;margin:0;font-size:22px;}
  .header p{color:#555570;font-size:12px;font-family:'Courier New',monospace;margin:6px 0 0;}
  .convo{background:#0d0d14;border:1px solid #252535;border-left:3px solid #00f5ff;border-radius:6px;padding:16px 20px;margin-bottom:20px;}
  .convo-meta{font-size:11px;color:#555570;font-family:'Courier New',monospace;margin-bottom:12px;}
  .msg{margin-bottom:10px;}
  .msg-role{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;font-family:'Courier New',monospace;}
  .msg-role.user{color:#b400ff;}
  .msg-role.assistant{color:#00f5ff;}
  .msg-body{font-size:14px;line-height:1.65;margin:4px 0 0;color:#dde0f0;}
  .footer{margin-top:32px;padding-top:20px;border-top:1px solid #252535;font-size:11px;color:#555570;font-family:'Courier New',monospace;text-align:center;}
  a{color:#00f5ff;}
</style></head><body><div class='wrap'>
<div class='header'>
  <h1>Echo-64 Weekly Transmission</h1>
  <p>{$date} &nbsp;·&nbsp; {$site} &nbsp;·&nbsp; Top conversations this week</p>
</div>";

        foreach ( $conversations as $i => $c ) {
            $num     = $i + 1;
            $started = wp_date( 'M j g:i a', strtotime( $c['started'] ) );
            $count   = $c['msg_count'];
            $html   .= "<div class='convo'><div class='convo-meta'>Conversation #{$num} &nbsp;·&nbsp; {$started} &nbsp;·&nbsp; {$count} messages</div>";
            foreach ( $c['messages'] as $msg ) {
                if ( $msg['role'] === 'assistant' && strlen( $msg['content'] ) > 400 ) {
                    $msg['content'] = substr( $msg['content'], 0, 400 ) . '…';
                }
                $role_label = ucfirst( $msg['role'] === 'assistant' ? 'Echo-64' : 'Visitor' );
                $html .= "<div class='msg'><div class='msg-role {$msg['role']}'>{$role_label}</div><div class='msg-body'>" . nl2br( esc_html( $msg['content'] ) ) . "</div></div>";
            }
            $html .= "</div>";
        }

        $html .= "<div class='footer'>
  <a href='{$url}'>{$url}</a><br><br>
  Echo-64 · NerdAfterDark.com · Sci-Fi After Midnight<br>
  Retro Futures &amp; Sarcastic Truths
</div></div></body></html>";

        return $html;
    }

    // ── v1.4.0  Weekly Lost Episode Essay ────────────────────────────────

    public function run_weekly_essay(): void {
        $show  = $this->pick_essay_show();
        $essay = $this->generate_essay( $show );
        if ( empty( $essay ) ) return;

        $this->save_essay( $show, $essay );

        // Post to Discord if webhook configured
        $webhook = get_option( 'echo64_discord_webhook', '' );
        if ( $webhook ) {
            $this->post_essay_to_discord( $webhook, $show, $essay );
        }
    }

    private function pick_essay_show(): string {
        $week  = (int) wp_date( 'W' );
        $shows = self::ESSAY_SHOWS;
        return $shows[ $week % count( $shows ) ];
    }

    private function generate_essay( string $show ): string {
        $api    = new Echo64_Api();
        $prompt = "Write a 350–400 word \"lost transmission\" from Echo-64 about {$show}.\n\n"
            . "Format exactly:\n"
            . "LOST TRANSMISSION: [SHOW NAME IN CAPS]\n"
            . "[TODAY'S DATE] — RECOVERED FROM BUFFER\n\n"
            . "[The essay — 350-400 words in Echo-64's full voice. Specific. Opinionated. "
            . "Not a Wikipedia summary. What was lost when it was cancelled. "
            . "What it understood that other shows didn't. What it got wrong. "
            . "End on the show's specific legacy.]\n\n"
            . "SIGNAL STATUS: [one final verdict line]\n\n"
            . "Plain text only. No markdown. Monospace-friendly.";

        $result = $api->send_message(
            [ [ 'role' => 'user', 'content' => $prompt ] ],
            get_option( 'echo64_system_prompt', Echo64_Api::DEFAULT_SYSTEM_PROMPT )
        );

        return is_wp_error( $result ) ? '' : $result;
    }

    private function save_essay( string $show, string $essay ): void {
        $archive   = get_option( 'echo64_essay_archive', [] );
        $archive[] = [
            'show'  => $show,
            'essay' => $essay,
            'date'  => wp_date( 'Y-m-d' ),
        ];
        // Keep last 12
        if ( count( $archive ) > 12 ) {
            $archive = array_slice( $archive, -12 );
        }
        update_option( 'echo64_essay_archive', $archive, false );
    }

    private function post_essay_to_discord( string $webhook, string $show, string $essay ): void {
        $preview = mb_substr( $essay, 0, 800 );
        $payload = [
            'username'   => 'Echo-64',
            'avatar_url' => ECHO64_PLUGIN_URL . 'assets/images/echo-64-avatar.png',
            'embeds'     => [ [
                'title'       => "Lost Transmission — {$show}",
                'description' => "```\n{$preview}\n```",
                'color'       => 0xB400FF,
                'footer'      => [ 'text' => 'NerdAfterDark.com · Sci-Fi After Midnight · Weekly Lost Episode' ],
            ] ],
        ];
        wp_remote_post( $webhook, [
            'timeout' => 15,
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( $payload ),
        ] );
    }

    public function handle_generate_essay(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        check_admin_referer( 'echo64_generate_essay' );

        $show  = $this->pick_essay_show();
        $essay = $this->generate_essay( $show );
        $status = 'fail';
        if ( $essay ) {
            $this->save_essay( $show, $essay );
            $status = 'generated';
        }
        wp_redirect( add_query_arg( [
            'page' => 'echo64-settings', 'tab' => 'automations', 'essay_status' => $status,
        ], admin_url( 'options-general.php' ) ) );
        exit;
    }

    public function handle_test_email(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        check_admin_referer( 'echo64_test_email' );

        $this->run_weekly_email();
        wp_redirect( add_query_arg( [ 'page' => 'echo64-settings', 'tab' => 'automations', 'email_test' => 'sent' ], admin_url( 'options-general.php' ) ) );
        exit;
    }

    // ── Render: Automations admin panel ───────────────────────────────────

    public function render(): void {
        $discord_webhook = get_option( 'echo64_discord_webhook', '' );
        $email_recipient = get_option( 'echo64_email_recipient', get_option( 'admin_email' ) );
        $discord_test    = sanitize_key( $_GET['discord_test'] ?? '' );
        $email_test      = sanitize_key( $_GET['email_test'] ?? '' );
        $essay_status    = sanitize_key( $_GET['essay_status'] ?? '' );
        $next_discord    = wp_next_scheduled( 'echo64_daily_discord' );
        $next_email      = wp_next_scheduled( 'echo64_weekly_email' );
        $next_essay      = wp_next_scheduled( 'echo64_weekly_essay' );
        $essays          = get_option( 'echo64_essay_archive', [] );
        ?>

        <?php if ( $essay_status === 'generated' ) : ?>
            <div class="notice notice-success"><p>✓ Lost Episode essay generated and saved.</p></div>
        <?php elseif ( $essay_status === 'fail' ) : ?>
            <div class="notice notice-error"><p>Essay generation failed — check your API key.</p></div>
        <?php endif; ?>

        <?php if ( $discord_test === 'sent' ) : ?>
            <div class="notice notice-success"><p>✓ Test poem posted to Discord.</p></div>
        <?php elseif ( $discord_test ) : ?>
            <div class="notice notice-error"><p>Discord test failed: <?php echo esc_html( $discord_test ); ?></p></div>
        <?php endif; ?>

        <?php if ( $email_test === 'sent' ) : ?>
            <div class="notice notice-success"><p>✓ Test email sent to <?php echo esc_html( $email_recipient ); ?></p></div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields( 'echo64_settings' ); ?>

            <!-- Discord -->
            <div class="echo64-admin-card">
                <h3 class="echo64-card-title"><span class="echo64-card-icon">🎮</span> Discord — Daily Poem (#15)</h3>
                <p>Echo-64 posts a fresh Poem of the Day to your Discord server automatically every day at noon (server time).</p>
                <table class="form-table">
                    <tr>
                        <th><label for="echo64_discord_webhook">Webhook URL</label></th>
                        <td>
                            <input type="url" id="echo64_discord_webhook" name="echo64_discord_webhook"
                                   value="<?php echo esc_attr( $discord_webhook ); ?>"
                                   class="large-text" placeholder="https://discord.com/api/webhooks/…" />
                            <p class="description">
                                In Discord: Channel Settings → Integrations → Webhooks → New Webhook → Copy URL.
                            </p>
                            <?php if ( $next_discord ) : ?>
                            <p class="description">Next scheduled post: <?php echo esc_html( wp_date( 'M j Y g:i a', $next_discord ) ); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <?php if ( $discord_webhook ) : ?>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
                    <?php wp_nonce_field( 'echo64_test_discord' ); ?>
                    <input type="hidden" name="action" value="echo64_test_discord" />
                    <button type="submit" class="button">Send Test Poem to Discord Now</button>
                </form>
                <?php endif; ?>
            </div>

            <!-- Email -->
            <div class="echo64-admin-card">
                <h3 class="echo64-card-title"><span class="echo64-card-icon">📧</span> Weekly Transmission Email (#16)</h3>
                <p>Every Monday morning, Echo-64 sends a digest of the best conversations from the past week — the exchanges with the most depth and engagement.</p>
                <table class="form-table">
                    <tr>
                        <th><label for="echo64_email_recipient">Send To</label></th>
                        <td>
                            <input type="email" id="echo64_email_recipient" name="echo64_email_recipient"
                                   value="<?php echo esc_attr( $email_recipient ); ?>"
                                   class="regular-text" />
                            <p class="description">Separate multiple addresses with commas.</p>
                            <?php if ( $next_email ) : ?>
                            <p class="description">Next scheduled email: <?php echo esc_html( wp_date( 'M j Y g:i a', $next_email ) ); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
                    <?php wp_nonce_field( 'echo64_test_email' ); ?>
                    <input type="hidden" name="action" value="echo64_test_email" />
                    <button type="submit" class="button">Send Test Email Now</button>
                </form>
            </div>

            <?php submit_button( 'Save Automation Settings', 'primary echo64-save-btn' ); ?>
        </form>

        <!-- v1.4.0 ── Lost Episode Essays -->
        <div class="echo64-admin-card">
            <h3 class="echo64-card-title"><span class="echo64-card-icon">📼</span> Lost Episode Weekly Essay (v1.4.0)</h3>
            <p>Every Sunday at midnight, Echo-64 writes a 350-word "Lost Transmission" essay about a cancelled sci-fi show, posts it to Discord, and stores it in the archive below. Use <code>[echo64_transmissions]</code> on any page to display the archive publicly.</p>
            <p>
                <?php if ( $next_essay ) : ?>
                    Next essay scheduled: <strong><?php echo esc_html( wp_date( 'M j Y g:i a', $next_essay ) ); ?></strong> &nbsp;·&nbsp;
                <?php endif; ?>
                Current week's show: <strong><?php echo esc_html( $this->pick_essay_show() ); ?></strong>
            </p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'echo64_generate_essay' ); ?>
                <input type="hidden" name="action" value="echo64_generate_essay" />
                <button type="submit" class="button button-secondary">Generate Essay Now (uses API)</button>
            </form>
        </div>

        <?php if ( ! empty( $essays ) ) : ?>
        <div class="echo64-admin-card">
            <h3 class="echo64-card-title"><span class="echo64-card-icon">📁</span> Essay Archive (<?php echo count( $essays ); ?> / 12)</h3>
            <?php foreach ( array_reverse( $essays ) as $e ) : ?>
            <div style="border:1px solid #252535;border-left:3px solid #b400ff;border-radius:6px;padding:14px 18px;margin-bottom:14px;background:#0d0d14;">
                <div style="font-family:'Courier New',monospace;font-size:11px;color:#555570;margin-bottom:8px;">
                    <?php echo esc_html( $e['date'] ?? '' ); ?> &nbsp;·&nbsp; <?php echo esc_html( $e['show'] ?? '' ); ?>
                </div>
                <pre style="white-space:pre-wrap;font-family:'Courier New',monospace;font-size:12px;color:#dde0f0;margin:0;max-height:160px;overflow:auto;"><?php echo esc_html( $e['essay'] ?? '' ); ?></pre>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php
    }
}
