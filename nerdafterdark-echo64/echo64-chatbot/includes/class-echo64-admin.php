<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class Echo64_Admin {

    private static ?Echo64_Admin $instance = null;

    public static function instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu',            [ $this, 'add_menu' ] );
        add_action( 'admin_init',            [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function add_menu(): void {
        // SVG icon — retro monitor
        $icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="1" y="2" width="18" height="13" rx="2" fill="none" stroke="black" stroke-width="1.5"/><rect x="3" y="4" width="14" height="9" fill="black" opacity=".2"/><line x1="5" y1="7" x2="9" y2="7" stroke="black" stroke-width="1.2" stroke-linecap="round"/><line x1="5" y1="9" x2="11" y2="9" stroke="black" stroke-width="1.2" stroke-linecap="round"/><path d="M7 17h6M10 15v2" stroke="black" stroke-width="1.5" stroke-linecap="round"/></svg>' );

        add_menu_page(
            __( 'Echo-64', 'echo64-chatbot' ),
            __( 'Echo-64', 'echo64-chatbot' ),
            'manage_options',
            'echo64',
            [ $this, 'render_page' ],
            $icon,
            30
        );

        $subpages = [
            'echo64'               => '🖥 Dashboard',
            'echo64-settings'      => '⚙ Settings',
            'echo64-conversations' => '💬 Conversations',
            'echo64-stats'         => '📊 Stats',
            'echo64-automations'   => '🤖 Automations',
            'echo64-calendar'      => '📅 Calendar',
            'echo64-launch'        => '🚀 Launch Kit',
            'echo64-insights'      => '🔍 Insights',
        ];

        foreach ( $subpages as $slug => $label ) {
            add_submenu_page(
                'echo64',
                $label . ' — Echo-64',
                $label,
                'manage_options',
                $slug,
                [ $this, 'render_page' ]
            );
        }
    }

    public function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'echo64' ) === false ) return;

        wp_enqueue_style(
            'echo64-admin',
            ECHO64_PLUGIN_URL . 'assets/css/echo64-admin.css',
            [],
            ECHO64_VERSION
        );

        // Inline JS for admin interactions
        $js = <<<'JS'
document.addEventListener('DOMContentLoaded', function () {
    // Character counter for the system prompt textarea
    const textarea = document.getElementById('echo64_system_prompt');
    const counter  = document.getElementById('echo64-prompt-counter');
    if (textarea && counter) {
        const update = () => { counter.textContent = textarea.value.length.toLocaleString() + ' chars'; };
        textarea.addEventListener('input', update);
        update();
    }

    // Reset to default button — calls server so it always reads the current PHP constant
    const resetBtn = document.getElementById('echo64-reset-prompt');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (!confirm('Reset to the built-in Echo-64 system prompt? Your edits will be lost.')) return;
            resetBtn.disabled = true;
            resetBtn.textContent = 'Resetting…';
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'echo64_reset_prompt', nonce: echo64AdminNonce })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success && textarea) {
                    textarea.value = res.data.prompt;
                    if (counter) counter.textContent = textarea.value.length.toLocaleString() + ' chars';
                    alert('Prompt reset. Click Save Settings to apply.');
                } else {
                    alert('Reset failed: ' + (res.data?.message || 'unknown error'));
                }
            })
            .catch(() => alert('Reset request failed — check your connection.'))
            .finally(() => { resetBtn.disabled = false; resetBtn.textContent = '↺ Reset to Default'; });
        });
    }

    // Toggle API key visibility
    const toggle = document.getElementById('echo64-toggle-key');
    const keyInput = document.getElementById('echo64_api_key');
    if (toggle && keyInput) {
        toggle.addEventListener('click', function () {
            const show = keyInput.type === 'password';
            keyInput.type = show ? 'text' : 'password';
            this.textContent = show ? 'Hide' : 'Show';
        });
    }

    // Test API connection
    const testBtn  = document.getElementById('echo64-test-api');
    const testResult = document.getElementById('echo64-test-result');
    if (testBtn && testResult) {
        testBtn.addEventListener('click', function () {
            const key = keyInput ? keyInput.value.trim() : '';
            if (!key) { testResult.textContent = 'Enter an API key first.'; testResult.className = 'echo64-test-fail'; return; }
            testBtn.disabled = true;
            testBtn.textContent = 'Testing…';
            testResult.textContent = '';
            testResult.className = '';
            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'echo64_test_api',
                    nonce:  echo64AdminNonce,
                    api_key: key,
                })
            })
            .then(r => r.json())
            .then(res => {
                testResult.textContent = res.data?.message || (res.success ? '✓ Connected' : '✗ Failed');
                testResult.className   = res.success ? 'echo64-test-ok' : 'echo64-test-fail';
            })
            .catch(() => {
                testResult.textContent = '✗ Request failed';
                testResult.className   = 'echo64-test-fail';
            })
            .finally(() => {
                testBtn.disabled    = false;
                testBtn.textContent = 'Test Connection';
            });
        });
    }
    // ── AI Analysis ──────────────────────────────────────────────────────
    const analysisWrap   = document.getElementById('e64-analysis-wrap');
    const analysisOutput = document.getElementById('e64-analysis-output');
    const runBtn         = document.getElementById('e64-run-analysis');
    const refreshBtn     = document.getElementById('e64-refresh-analysis');
    const analysisStatus = document.getElementById('e64-analysis-status');

    function loadAnalysis(force) {
        if (!runBtn || !analysisOutput) return;
        runBtn.disabled = true;
        if (refreshBtn) refreshBtn.disabled = true;
        if (analysisStatus) analysisStatus.textContent = 'Transmitting to Claude…';
        analysisOutput.innerHTML = '<p style="color:#555;font-style:italic;font-size:13px">Analysing conversation data…</p>';
        if (analysisWrap) analysisWrap.style.display = 'block';

        fetch(ajaxurl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'echo64_ai_analysis',
                nonce:  echo64AdminNonce,
                force:  force ? '1' : '0',
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                analysisOutput.innerHTML = res.data.html;
                if (analysisStatus) analysisStatus.textContent = 'Generated: ' + res.data.generated;
            } else {
                analysisOutput.innerHTML = '<p style="color:#f66;font-size:13px">⚠ ' + (res.data?.message || 'Analysis failed.') + '</p>';
                if (analysisStatus) analysisStatus.textContent = '';
            }
        })
        .catch(() => {
            analysisOutput.innerHTML = '<p style="color:#f66;font-size:13px">⚠ Request failed — check your connection.</p>';
            if (analysisStatus) analysisStatus.textContent = '';
        })
        .finally(() => {
            if (runBtn) runBtn.disabled = false;
            if (refreshBtn) refreshBtn.disabled = false;
        });
    }

    if (runBtn)     runBtn.addEventListener('click',     () => loadAnalysis(false));
    if (refreshBtn) refreshBtn.addEventListener('click', () => loadAnalysis(true));
});
JS;
        wp_add_inline_script( 'jquery', $js );
        wp_localize_script( 'jquery', 'echo64AdminNonce', wp_create_nonce( 'echo64_admin_nonce' ) );
    }

    public function register_settings(): void {
        $opts = [
            'echo64_api_key'          => 'sanitize_text_field',
            'echo64_model'            => 'sanitize_text_field',
            'echo64_max_tokens'       => 'absint',
            'echo64_history_limit'    => 'absint',
            'echo64_daily_limit'      => 'absint',           // v1.4.0
            'echo64_float_widget'     => 'sanitize_text_field',
            'echo64_system_prompt'    => 'sanitize_textarea_field',
            'echo64_discord_webhook'  => 'sanitize_url',
            'echo64_email_recipient'  => 'sanitize_text_field',
            'echo64_sid_audio'              => 'sanitize_text_field',
            'echo64_transmissions_unlimited' => 'sanitize_text_field',
            'echo64_mailerlite_key'   => 'sanitize_text_field',
            'echo64_mailerlite_group' => 'sanitize_text_field',
            'echo64_terms_content'    => 'wp_kses_post',
            'echo64_privacy_content'  => 'wp_kses_post',
        ];

        foreach ( $opts as $key => $cb ) {
            register_setting( 'echo64_settings', $key, [ 'sanitize_callback' => $cb ] );
        }

        add_action( 'wp_ajax_echo64_test_api',        [ $this, 'ajax_test_api' ] );
        add_action( 'wp_ajax_echo64_ai_analysis',   [ $this, 'ajax_ai_analysis' ] );
        add_action( 'wp_ajax_echo64_reset_prompt',  [ $this, 'ajax_reset_prompt' ] );
    }

    public function ajax_test_api(): void {
        check_ajax_referer( 'echo64_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
            return;
        }

        $api_key = sanitize_text_field( wp_unslash( $_POST['api_key'] ?? '' ) );
        if ( empty( $api_key ) ) {
            wp_send_json_error( [ 'message' => '✗ No API key provided.' ] );
            return;
        }

        $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
            'timeout' => 20,
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => wp_json_encode( [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 8,
                'messages'   => [ [ 'role' => 'user', 'content' => 'Hi' ] ],
            ] ),
        ] );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( [ 'message' => '✗ ' . $response->get_error_message() ] );
            return;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 200 ) {
            wp_send_json_success( [ 'message' => '✓ API key valid. Connection successful.' ] );
        } else {
            $msg = $body['error']['message'] ?? 'Unknown error';
            wp_send_json_error( [ 'message' => "✗ API error ({$code}): {$msg}" ] );
        }
    }

    public function ajax_reset_prompt(): void {
        check_ajax_referer( 'echo64_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
            return;
        }
        update_option( 'echo64_system_prompt', Echo64_Api::DEFAULT_SYSTEM_PROMPT );
        wp_send_json_success( [ 'prompt' => Echo64_Api::DEFAULT_SYSTEM_PROMPT ] );
    }

    private function get_nav_pages(): array {
        return [
            'echo64'               => '🖥 Dashboard',
            'echo64-settings'      => '⚙ Settings',
            'echo64-conversations' => '💬 Conversations',
            'echo64-stats'         => '📊 Stats',
            'echo64-automations'   => '🤖 Automations',
            'echo64-calendar'      => '📅 Calendar',
            'echo64-launch'        => '🚀 Launch Kit',
            'echo64-insights'      => '🔍 Insights',
        ];
    }

    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $nav_pages   = $this->get_nav_pages();
        $current_page = sanitize_key( $_GET['page'] ?? 'echo64' );
        if ( ! array_key_exists( $current_page, $nav_pages ) ) $current_page = 'echo64';

        $api_key       = get_option( 'echo64_api_key', '' );
        $model         = get_option( 'echo64_model', 'claude-sonnet-4-6' );
        $max_tokens    = (int) get_option( 'echo64_max_tokens', 1024 );
        $history_limit = (int) get_option( 'echo64_history_limit', 20 );
        $daily_limit   = (int) get_option( 'echo64_daily_limit', 9 );
        $float_widget  = get_option( 'echo64_float_widget', '0' );
        $sid_audio              = get_option( 'echo64_sid_audio', '0' );
        $transmissions_unlimited = get_option( 'echo64_transmissions_unlimited', '0' );
        $mailerlite_key   = get_option( 'echo64_mailerlite_key', '' );
        $mailerlite_group = get_option( 'echo64_mailerlite_group', '' );
        $system_prompt = get_option( 'echo64_system_prompt', Echo64_Api::DEFAULT_SYSTEM_PROMPT );

        $models = [
            'claude-opus-4-8'           => 'Claude Opus 4.8 — Most capable, slowest',
            'claude-sonnet-4-6'         => 'Claude Sonnet 4.6 — Recommended balance',
            'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 — Fastest, most economical',
        ];
        ?>
        <div class="wrap echo64-admin-wrap">

            <div class="echo64-admin-header">
                <img
                    src="<?php echo esc_url( ECHO64_PLUGIN_URL . 'assets/images/echo-64-avatar.png' ); ?>"
                    alt="Echo-64 avatar"
                    class="echo64-admin-avatar"
                    onerror="this.style.display='none'"
                />
                <div class="echo64-admin-header-text">
                    <h1>Echo-64 Chatbot</h1>
                    <p class="echo64-admin-sub">NerdAfterDark.com &mdash; Retro Futures &amp; Sarcastic Truths</p>
                    <p class="echo64-admin-version">Version <?php echo esc_html( ECHO64_VERSION ); ?> &nbsp;|&nbsp; Shortcode: <code>[echo64_chat]</code></p>
                </div>
            </div>

            <?php settings_errors( 'echo64_settings' ); ?>

            <!-- ── Tab Navigation ── -->
            <nav class="nav-tab-wrapper echo64-tab-nav">
                <?php foreach ( $nav_pages as $page_slug => $label ) :
                    $url = admin_url( 'admin.php?page=' . $page_slug );
                    $cls = $current_page === $page_slug ? 'nav-tab nav-tab-active' : 'nav-tab';
                ?>
                    <a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $cls ); ?>">
                        <?php echo esc_html( $label ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php if ( $current_page === 'echo64-conversations' ) :
                Echo64_Log::instance()->render_log();
            elseif ( $current_page === 'echo64-stats' ) :
                Echo64_Log::instance()->render_stats();
            elseif ( $current_page === 'echo64' ) :
                $this->render_dashboard();
            elseif ( $current_page === 'echo64-automations' ) :
                Echo64_Automations::instance()->render();
            elseif ( $current_page === 'echo64-calendar' ) :
                Echo64_Calendar::instance()->render();
            elseif ( $current_page === 'echo64-launch' ) :
                $this->render_launch_kit();
            elseif ( $current_page === 'echo64-insights' ) :
                $this->render_insights();
            else : ?>

            <form method="post" action="options.php">
                <?php settings_fields( 'echo64_settings' ); ?>

                <!-- ── API Configuration ── -->
                <div class="echo64-admin-card">
                    <h2 class="echo64-card-title">
                        <span class="echo64-card-icon">⚙</span>
                        <?php esc_html_e( 'API Configuration', 'echo64-chatbot' ); ?>
                    </h2>

                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="echo64_api_key"><?php esc_html_e( 'Anthropic API Key', 'echo64-chatbot' ); ?></label>
                            </th>
                            <td>
                                <div class="echo64-key-row">
                                    <input
                                        type="password"
                                        id="echo64_api_key"
                                        name="echo64_api_key"
                                        value="<?php echo esc_attr( $api_key ); ?>"
                                        class="regular-text"
                                        autocomplete="off"
                                        placeholder="sk-ant-…"
                                    />
                                    <button type="button" id="echo64-toggle-key" class="button">Show</button>
                                    <button type="button" id="echo64-test-api" class="button">Test Connection</button>
                                </div>
                                <span id="echo64-test-result"></span>
                                <p class="description">
                                    <?php esc_html_e( 'Get your key at', 'echo64-chatbot' ); ?>
                                    <a href="https://console.anthropic.com" target="_blank" rel="noopener">console.anthropic.com</a>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="echo64_model"><?php esc_html_e( 'Model', 'echo64-chatbot' ); ?></label>
                            </th>
                            <td>
                                <select id="echo64_model" name="echo64_model">
                                    <?php foreach ( $models as $id => $label ) : ?>
                                        <option value="<?php echo esc_attr( $id ); ?>" <?php selected( $model, $id ); ?>>
                                            <?php echo esc_html( $label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="echo64_max_tokens"><?php esc_html_e( 'Max Response Tokens', 'echo64-chatbot' ); ?></label>
                            </th>
                            <td>
                                <input
                                    type="number"
                                    id="echo64_max_tokens"
                                    name="echo64_max_tokens"
                                    value="<?php echo esc_attr( $max_tokens ); ?>"
                                    min="256" max="8192" step="128"
                                    class="small-text"
                                />
                                <span class="description"><?php esc_html_e( '256–8192. Higher = longer replies, more cost.', 'echo64-chatbot' ); ?></span>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="echo64_history_limit"><?php esc_html_e( 'Conversation Memory', 'echo64-chatbot' ); ?></label>
                            </th>
                            <td>
                                <input
                                    type="number"
                                    id="echo64_history_limit"
                                    name="echo64_history_limit"
                                    value="<?php echo esc_attr( $history_limit ); ?>"
                                    min="2" max="100" step="2"
                                    class="small-text"
                                />
                                <span class="description"><?php esc_html_e( 'Number of past messages sent as context. Higher = better memory, more cost.', 'echo64-chatbot' ); ?></span>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- ── Display Options ── -->
                <div class="echo64-admin-card">
                    <h2 class="echo64-card-title">
                        <span class="echo64-card-icon">🖥</span>
                        <?php esc_html_e( 'Display Options', 'echo64-chatbot' ); ?>
                    </h2>

                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="echo64_daily_limit"><?php esc_html_e( 'Daily Transmission Limit', 'echo64-chatbot' ); ?></label>
                            </th>
                            <td>
                                <input
                                    type="number"
                                    id="echo64_daily_limit"
                                    name="echo64_daily_limit"
                                    value="<?php echo esc_attr( $daily_limit ); ?>"
                                    min="0" max="999" step="1"
                                    class="small-text"
                                />
                                <span class="description">
                                    <?php esc_html_e( 'Max messages per visitor per day (resets at midnight). Set to 0 for unlimited. Recommended: 9.', 'echo64-chatbot' ); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Floating Widget', 'echo64-chatbot' ); ?></th>
                            <td>
                                <label class="echo64-toggle">
                                    <input
                                        type="checkbox"
                                        name="echo64_float_widget"
                                        value="1"
                                        <?php checked( $float_widget, '1' ); ?>
                                    />
                                    <span class="echo64-toggle-label">
                                        <?php esc_html_e( 'Show floating avatar button on every page', 'echo64-chatbot' ); ?>
                                    </span>
                                </label>
                                <p class="description">
                                    <?php esc_html_e( 'When enabled, the Echo-64 avatar appears in the corner of every page and opens the chat. You can still also use [echo64_chat] on specific pages.', 'echo64-chatbot' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'SID Chip Audio', 'echo64-chatbot' ); ?></th>
                            <td>
                                <label class="echo64-toggle">
                                    <input type="checkbox" name="echo64_sid_audio" value="1" <?php checked( $sid_audio, '1' ); ?> />
                                    <span class="echo64-toggle-label">
                                        <?php esc_html_e( 'Play SID chip sound on boot', 'echo64-chatbot' ); ?>
                                    </span>
                                </label>
                                <p class="description"><?php esc_html_e( 'Synthesizes a short 8-bit Commodore SID-style tone when Echo-64 finishes booting. Skips if visitor prefers reduced motion.', 'echo64-chatbot' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Transmissions Archive', 'echo64-chatbot' ); ?></th>
                            <td>
                                <label class="echo64-toggle">
                                    <input type="checkbox" name="echo64_transmissions_unlimited" value="1" <?php checked( $transmissions_unlimited, '1' ); ?> />
                                    <span class="echo64-toggle-label">
                                        <?php esc_html_e( 'Show all Transmissions on archive (no pagination)', 'echo64-chatbot' ); ?>
                                    </span>
                                </label>
                                <p class="description"><?php esc_html_e( 'When enabled, the /transmissions/ archive lists every post newest to oldest, ignoring the "Blog pages show at most" limit.', 'echo64-chatbot' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- ── MailerLite ── -->
                <div class="echo64-admin-card">
                    <h2 class="echo64-card-title">
                        <span class="echo64-card-icon">📡</span>
                        <?php esc_html_e( 'MailerLite Integration', 'echo64-chatbot' ); ?>
                    </h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="echo64_mailerlite_key"><?php esc_html_e( 'API Key', 'echo64-chatbot' ); ?></label>
                            </th>
                            <td>
                                <input
                                    type="password"
                                    id="echo64_mailerlite_key"
                                    name="echo64_mailerlite_key"
                                    value="<?php echo esc_attr( $mailerlite_key ); ?>"
                                    class="regular-text"
                                    autocomplete="off"
                                    placeholder="MailerLite API token"
                                />
                                <p class="description"><?php esc_html_e( 'From MailerLite → Integrations → MailerLite API → Generate new token.', 'echo64-chatbot' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="echo64_mailerlite_group"><?php esc_html_e( 'Group ID', 'echo64-chatbot' ); ?></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="echo64_mailerlite_group"
                                    name="echo64_mailerlite_group"
                                    value="<?php echo esc_attr( $mailerlite_group ); ?>"
                                    class="regular-text"
                                    placeholder="e.g. 191568589831013774"
                                />
                                <p class="description"><?php esc_html_e( 'Subscribers from the Catch the Late Signal form and footer will be added to this group. Find it in MailerLite → Subscribers → Groups → open the group → copy the number from the URL.', 'echo64-chatbot' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- ── System Prompt ── -->
                <div class="echo64-admin-card">
                    <h2 class="echo64-card-title">
                        <span class="echo64-card-icon">🤖</span>
                        <?php esc_html_e( 'Echo-64 Personality Prompt', 'echo64-chatbot' ); ?>
                    </h2>

                    <p class="echo64-prompt-intro">
                        <?php esc_html_e( 'This is the system prompt that defines Echo-64\'s personality, tone, and behavior. Edit it to fine-tune responses. The opening line ("Welcome to NerdAfterDark.com…") is always shown verbatim and is separate from this prompt.', 'echo64-chatbot' ); ?>
                    </p>

                    <div class="echo64-prompt-toolbar">
                        <span id="echo64-prompt-counter" class="echo64-char-count"></span>
                        <button
                            type="button"
                            id="echo64-reset-prompt"
                            class="button"
                        >
                            <?php esc_html_e( '↺ Reset to Default', 'echo64-chatbot' ); ?>
                        </button>
                    </div>

                    <textarea
                        id="echo64_system_prompt"
                        name="echo64_system_prompt"
                        rows="24"
                        class="large-text code echo64-prompt-textarea"
                        spellcheck="false"
                    ><?php echo esc_textarea( $system_prompt ); ?></textarea>
                </div>

                <!-- ── Terms of Use ── -->
                <div class="echo64-admin-card">
                    <h2 class="echo64-card-title">
                        <span class="echo64-card-icon">📜</span>
                        <?php esc_html_e( 'Terms of Use', 'echo64-chatbot' ); ?>
                    </h2>
                    <p class="echo64-prompt-intro">
                        <?php esc_html_e( 'Content displayed via the [echo64_terms] shortcode. Paste into any WordPress page. Supports basic HTML.', 'echo64-chatbot' ); ?>
                    </p>
                    <textarea
                        id="echo64_terms_content"
                        name="echo64_terms_content"
                        rows="20"
                        class="large-text code echo64-prompt-textarea"
                        spellcheck="false"
                    ><?php echo esc_textarea( get_option( 'echo64_terms_content', '' ) ); ?></textarea>
                </div>

                <!-- ── Privacy Policy ── -->
                <div class="echo64-admin-card">
                    <h2 class="echo64-card-title">
                        <span class="echo64-card-icon">🔒</span>
                        <?php esc_html_e( 'Privacy Policy', 'echo64-chatbot' ); ?>
                    </h2>
                    <p class="echo64-prompt-intro">
                        <?php esc_html_e( 'Content displayed via the [echo64_privacy] shortcode. Paste into any WordPress page. Supports basic HTML.', 'echo64-chatbot' ); ?>
                    </p>
                    <textarea
                        id="echo64_privacy_content"
                        name="echo64_privacy_content"
                        rows="20"
                        class="large-text code echo64-prompt-textarea"
                        spellcheck="false"
                    ><?php echo esc_textarea( get_option( 'echo64_privacy_content', '' ) ); ?></textarea>
                </div>

                <!-- ── Usage ── -->
                <div class="echo64-admin-card echo64-admin-card--usage">
                    <h2 class="echo64-card-title">
                        <span class="echo64-card-icon">📋</span>
                        <?php esc_html_e( 'Usage', 'echo64-chatbot' ); ?>
                    </h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th><?php esc_html_e( 'Embed Shortcode', 'echo64-chatbot' ); ?></th>
                            <td><code>[echo64_chat]</code> — <?php esc_html_e( 'paste into any page or post', 'echo64-chatbot' ); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Avatar Image', 'echo64-chatbot' ); ?></th>
                            <td>
                                <?php
                                $avatar_path = ECHO64_PLUGIN_DIR . 'assets/images/echo-64-avatar.png';
                                if ( file_exists( $avatar_path ) ) {
                                    echo '<span class="echo64-status-ok">✓ ' . esc_html__( 'Found', 'echo64-chatbot' ) . '</span>';
                                } else {
                                    echo '<span class="echo64-status-warn">⚠ ' . esc_html__( 'Missing — upload echo-64-avatar.png to /assets/images/', 'echo64-chatbot' ) . '</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Database Table', 'echo64-chatbot' ); ?></th>
                            <td>
                                <?php
                                global $wpdb;
                                $table  = $wpdb->prefix . 'echo64_conversations';
                                $exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table;
                                if ( $exists ) {
                                    $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
                                    echo '<span class="echo64-status-ok">✓ ' . sprintf(
                                        esc_html__( '%s — %s messages stored', 'echo64-chatbot' ),
                                        esc_html( $table ),
                                        number_format_i18n( $count )
                                    ) . '</span>';
                                } else {
                                    echo '<span class="echo64-status-warn">⚠ ' . esc_html__( 'Table not found — deactivate and reactivate the plugin.', 'echo64-chatbot' ) . '</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button( __( 'Save Settings', 'echo64-chatbot' ), 'primary echo64-save-btn' ); ?>

            </form>

            <?php endif; // end settings tab ?>

        </div>
        <?php
    }

    // ── Echo-64 Dashboard ────────────────────────────────────────────────

    private function render_dashboard(): void {
        global $wpdb;
        $t = $wpdb->prefix . 'echo64_conversations';

        // Core counts
        $total_msgs     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}" );
        $total_sessions = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM {$t}" );
        $today_msgs     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE DATE(created_at) = CURDATE()" );
        $week_sessions  = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM {$t} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" );

        // Last conversation snippet
        $last_exchange = $wpdb->get_results(
            "SELECT role, content, created_at FROM {$t} ORDER BY id DESC LIMIT 4",
            ARRAY_A
        ) ?: [];
        $last_exchange = array_reverse( $last_exchange );

        // Plugin vitals
        $api_key      = get_option( 'echo64_api_key', '' );
        $model        = get_option( 'echo64_model', 'claude-sonnet-4-6' );
        $daily_limit  = (int) get_option( 'echo64_daily_limit', 9 );
        $float_widget = get_option( 'echo64_float_widget', '0' );
        $sid_audio    = get_option( 'echo64_sid_audio', '0' );
        $avatar_ok    = file_exists( ECHO64_PLUGIN_DIR . 'assets/images/echo-64-avatar.png' );
        $table_ok     = $wpdb->get_var( "SHOW TABLES LIKE '{$t}'" ) === $t;

        // Feature checklist — things that should be configured/working
        $checklist = [
            [ 'label' => 'API key set',           'ok' => ! empty( $api_key ) ],
            [ 'label' => 'Avatar image present',   'ok' => $avatar_ok ],
            [ 'label' => 'Database table exists',  'ok' => $table_ok ],
            [ 'label' => 'Daily limit configured', 'ok' => $daily_limit > 0 ],
            [ 'label' => 'Floating widget active',  'ok' => $float_widget === '1' ],
            [ 'label' => 'SID chip audio on',      'ok' => $sid_audio === '1' ],
            [ 'label' => 'Conversations logged',   'ok' => $total_msgs > 0 ],
        ];

        $version = defined( 'ECHO64_VERSION' ) ? ECHO64_VERSION : '—';
        ?>

        <!-- ── Hero strip ── -->
        <div style="background:linear-gradient(135deg,#0a0a1a,#10102a);border:1px solid #1e1e3a;border-radius:8px;padding:20px 24px;margin-bottom:20px;display:flex;align-items:center;gap:20px">
            <img src="<?php echo esc_url( ECHO64_PLUGIN_URL . 'assets/images/echo-64-avatar.png' ); ?>"
                 style="width:56px;height:56px;border-radius:50%;border:2px solid #00f5ff;flex-shrink:0"
                 onerror="this.style.display='none'" alt="">
            <div>
                <div style="font-size:18px;font-weight:700;color:#e8eaf6;letter-spacing:.03em">ECHO-64 COMMAND CENTRE</div>
                <div style="font-size:12px;color:#555;margin-top:3px;font-family:monospace">
                    v<?php echo esc_html( $version ); ?> &nbsp;·&nbsp;
                    Model: <?php echo esc_html( $model ); ?> &nbsp;·&nbsp;
                    Daily limit: <?php echo $daily_limit > 0 ? esc_html( (string) $daily_limit ) : 'unlimited'; ?>
                </div>
            </div>
        </div>

        <!-- ── Stat cards ── -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
            <?php
            $cards = [
                [ 'Total Messages',    number_format_i18n( $total_msgs ),     '#00f5ff' ],
                [ 'Total Sessions',    number_format_i18n( $total_sessions ),  '#bf5af2' ],
                [ 'Today\'s Messages', number_format_i18n( $today_msgs ),      '#30d158' ],
                [ 'Sessions (7 days)', number_format_i18n( $week_sessions ),   '#ffd60a' ],
            ];
            foreach ( $cards as [ $label, $value, $color ] ) : ?>
            <div style="background:#0d0d1a;border:1px solid #1e1e3a;border-radius:6px;padding:16px;text-align:center">
                <div style="font-size:26px;font-weight:700;color:<?php echo esc_attr( $color ); ?>"><?php echo esc_html( $value ); ?></div>
                <div style="font-size:11px;color:#666;margin-top:4px;text-transform:uppercase;letter-spacing:.05em"><?php echo esc_html( $label ); ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

            <!-- ── System health ── -->
            <div class="echo64-admin-card" style="margin-bottom:0">
                <h3 class="echo64-card-title"><span class="echo64-card-icon">⚡</span> System Health</h3>
                <?php foreach ( $checklist as $item ) : ?>
                <div style="display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid #111">
                    <span style="font-size:14px;flex-shrink:0"><?php echo $item['ok'] ? '✅' : '⚠️'; ?></span>
                    <span style="font-size:13px;color:<?php echo $item['ok'] ? '#e8eaf6' : '#ffd60a'; ?>"><?php echo esc_html( $item['label'] ); ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ── Last conversation preview ── -->
            <div class="echo64-admin-card" style="margin-bottom:0">
                <h3 class="echo64-card-title"><span class="echo64-card-icon">💬</span> Last Conversation</h3>
                <?php if ( empty( $last_exchange ) ) : ?>
                    <p style="color:#555;font-size:13px">No conversations yet.</p>
                <?php else : ?>
                    <?php foreach ( $last_exchange as $msg ) :
                        $is_user = $msg['role'] === 'user';
                        $preview = mb_substr( $msg['content'], 0, 120 );
                        if ( mb_strlen( $msg['content'] ) > 120 ) $preview .= '…';
                        if ( str_starts_with( $msg['content'], '[' ) ) continue; // skip internal commands
                    ?>
                    <div style="margin-bottom:10px">
                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:<?php echo $is_user ? '#00f5ff' : '#bf5af2'; ?>;margin-bottom:2px">
                            <?php echo $is_user ? 'Visitor' : 'Echo-64'; ?>
                            <span style="color:#444;font-size:10px;margin-left:6px"><?php echo esc_html( wp_date( 'g:i a', strtotime( $msg['created_at'] ) ) ); ?></span>
                        </div>
                        <p style="margin:0;font-size:12px;color:#aaa;line-height:1.5"><?php echo esc_html( $preview ); ?></p>
                    </div>
                    <?php endforeach; ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=echo64-conversations' ) ); ?>"
                       style="font-size:12px;color:#00f5ff">View all conversations →</a>
                <?php endif; ?>
            </div>

        </div>

        <!-- ── AI Analysis ── -->
        <div class="echo64-admin-card" style="margin-top:20px">
            <h3 class="echo64-card-title"><span class="echo64-card-icon">🤖</span> AI Analysis</h3>
            <p style="color:#666;font-size:13px;margin-bottom:14px">
                Claude analyses your actual conversation data and tells you what's working, what isn't, and what to change.
                Results are cached for 24 hours — use Refresh to force a new run.
            </p>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
                <button id="e64-run-analysis" class="button button-primary">▶ Run Analysis</button>
                <button id="e64-refresh-analysis" class="button">↺ Refresh</button>
                <span id="e64-analysis-status" style="color:#555;font-size:12px;font-family:monospace"></span>
            </div>
            <div id="e64-analysis-wrap" style="display:none;background:#07070f;border:1px solid #1e1e3a;border-radius:6px;padding:18px 20px">
                <div id="e64-analysis-output"></div>
            </div>
        </div>

        <!-- ── Quick links ── -->
        <div class="echo64-admin-card" style="margin-top:20px">
            <h3 class="echo64-card-title"><span class="echo64-card-icon">🔧</span> Fine-Tune</h3>
            <p style="color:#666;font-size:13px;margin-bottom:14px">Common tasks for tuning Echo-64's behaviour — more sections will be added here as the project grows.</p>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
                <?php
                $links = [
                    [ '⚙ Edit System Prompt',    'echo64-settings',      'Change Echo-64\'s personality, tone, and rules.' ],
                    [ '💬 Browse Conversations',  'echo64-conversations', 'Read visitor chats and delete sessions.' ],
                    [ '📊 View Stats',            'echo64-stats',         'Messages per day, top keywords, session counts.' ],
                    [ '🤖 Automations',           'echo64-automations',   'Schedule posts, poems, and timed broadcasts.' ],
                    [ '📅 Calendar',              'echo64-calendar',      'Plan content and transmission schedule.' ],
                    [ '🚀 Launch Kit',            'echo64-launch',        'Ready-to-post Reddit and social copy.' ],
                ];
                foreach ( $links as [ $label, $page_slug, $desc ] ) :
                    $url = admin_url( 'admin.php?page=' . $page_slug );
                ?>
                <a href="<?php echo esc_url( $url ); ?>" style="display:block;background:#0a0a1a;border:1px solid #1e1e3a;border-radius:6px;padding:14px;text-decoration:none;transition:border-color .2s"
                   onmouseover="this.style.borderColor='#00f5ff'" onmouseout="this.style.borderColor='#1e1e3a'">
                    <div style="font-size:13px;font-weight:600;color:#e8eaf6;margin-bottom:4px"><?php echo esc_html( $label ); ?></div>
                    <div style="font-size:11px;color:#555"><?php echo esc_html( $desc ); ?></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php
    }

    // ── AI Analysis ──────────────────────────────────────────────────────

    public function ajax_ai_analysis(): void {
        check_ajax_referer( 'echo64_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
            return;
        }

        $force = ! empty( $_POST['force'] ) && $_POST['force'] === '1';

        if ( ! $force ) {
            $cached = get_transient( 'echo64_ai_analysis' );
            if ( $cached ) {
                wp_send_json_success( $cached );
                return;
            }
        }

        $data   = $this->get_analysis_data();
        $result = $this->call_ai_analysis( $data );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
            return;
        }

        $payload = [
            'html'      => $result,
            'generated' => current_time( 'mysql' ),
        ];
        set_transient( 'echo64_ai_analysis', $payload, 24 * HOUR_IN_SECONDS );
        wp_send_json_success( $payload );
    }

    private function get_analysis_data(): array {
        global $wpdb;
        $t = $wpdb->prefix . 'echo64_conversations';

        $total_msgs     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}" );
        $total_sessions = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM {$t}" );
        $avg_per_session = $total_sessions > 0 ? round( $total_msgs / $total_sessions, 1 ) : 0;

        $recent_msgs = $wpdb->get_col(
            "SELECT content FROM {$t}
             WHERE role = 'user' AND content NOT LIKE '[%]' AND LENGTH(content) > 5
             ORDER BY id DESC LIMIT 40"
        ) ?: [];

        // Word frequency (reuse logic from Echo64_Log)
        $stopwords = [ 'the','a','an','and','or','but','in','on','at','to','for','of','with','is','it','its','this','that','was','are','be','been','have','has','had','do','did','does','will','would','could','should','can','may','might','i','you','he','she','we','they','me','him','her','us','them','my','your','his','our','their','what','which','who','how','when','where','why','not','no','so','if','as','up','out','about','than','more','just','get','from','by','like','think','know','really','also','echo','64','new','one','two','some','any','all','there','been','here' ];
        $freq = [];
        foreach ( $recent_msgs as $msg ) {
            foreach ( preg_split( '/[\s\W]+/u', strtolower( $msg ), -1, PREG_SPLIT_NO_EMPTY ) as $w ) {
                if ( strlen( $w ) < 3 || in_array( $w, $stopwords, true ) ) continue;
                $freq[ $w ] = ( $freq[ $w ] ?? 0 ) + 1;
            }
        }
        arsort( $freq );
        $keywords = array_slice( $freq, 0, 25, true );

        return compact( 'total_msgs', 'total_sessions', 'avg_per_session', 'recent_msgs', 'keywords' );
    }

    private function call_ai_analysis( array $data ): string|\WP_Error {
        $api_key = get_option( 'echo64_api_key', '' );
        if ( empty( $api_key ) ) {
            return new \WP_Error( 'no_key', 'No API key configured — save your key in Settings first.' );
        }

        if ( $data['total_msgs'] < 5 ) {
            return new \WP_Error( 'no_data', 'Not enough conversation data yet. Come back once visitors have had a few chats.' );
        }

        $keywords_str = implode( ', ', array_keys( $data['keywords'] ) );
        $sample_msgs  = array_map(
            fn( $m ) => '- ' . mb_substr( $m, 0, 120 ),
            array_slice( $data['recent_msgs'], 0, 20 )
        );
        $msgs_str = implode( "\n", $sample_msgs );

        $prompt = <<<PROMPT
You are an analytics consultant reviewing the Echo-64 chatbot on NerdAfterDark.com.

Echo-64 is a Commodore 64-themed AI chatbot with a dry, deadpan personality. It discusses sci-fi, cancelled TV shows, retro computing, dystopian literature, technology criticism, and D&D crossovers. It runs on Claude AI.

CONVERSATION DATA:
- Total messages logged: {$data['total_msgs']}
- Total sessions: {$data['total_sessions']}
- Average messages per session: {$data['avg_per_session']}
- Top keywords from visitor messages: {$keywords_str}
- Sample of recent visitor messages:
{$msgs_str}

Provide a concise, specific, actionable analysis. Reference actual keywords and patterns from the data above. No filler sentences.

Format your response with exactly these section headers (use ## before each):

## WHAT'S RESONATING
2-3 bullet points on topics or patterns that are clearly working.

## GAPS & MISSED OPPORTUNITIES
2-3 specific areas where visitors seem to want more but aren't getting it yet.

## 3 NEW STARTER CHIP SUGGESTIONS
Write each as it would appear on the chip — short, provocative, opinionated. Base them on what visitors are actually saying, not generic sci-fi topics.

## SYSTEM PROMPT SUGGESTION
One concrete, specific tweak that would improve Echo-64's responses or deepen its personality based on these patterns.

## WATCH LIST
1-2 friction points or drop-off patterns to monitor. Write "None detected." if everything looks healthy.
PROMPT;

        $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
            'timeout' => 35,
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => wp_json_encode( [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 900,
                'messages'   => [ [ 'role' => 'user', 'content' => $prompt ] ],
            ] ),
        ] );

        if ( is_wp_error( $response ) ) return $response;

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 ) {
            $msg = $body['error']['message'] ?? "API returned {$code}";
            return new \WP_Error( 'api_error', $msg );
        }

        $text = $body['content'][0]['text'] ?? '';
        return $this->format_analysis_html( $text );
    }

    private function format_analysis_html( string $raw ): string {
        $section_colors = [
            'WHAT\'S RESONATING'          => '#30d158',
            'GAPS & MISSED OPPORTUNITIES' => '#ffd60a',
            '3 NEW STARTER CHIP SUGGESTIONS' => '#00f5ff',
            'SYSTEM PROMPT SUGGESTION'    => '#bf5af2',
            'WATCH LIST'                  => '#ff6b6b',
        ];

        $lines  = explode( "\n", $raw );
        $html   = '';
        $in_ul  = false;

        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( $line === '' ) {
                if ( $in_ul ) { $html .= '</ul>'; $in_ul = false; }
                continue;
            }

            // Section header
            if ( str_starts_with( $line, '## ' ) ) {
                if ( $in_ul ) { $html .= '</ul>'; $in_ul = false; }
                $title = substr( $line, 3 );
                $color = $section_colors[ strtoupper( $title ) ] ?? '#888';
                $html .= '<div style="font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:' . esc_attr( $color ) . ';margin:18px 0 8px;font-weight:700;border-bottom:1px solid #1e1e3a;padding-bottom:4px">' . esc_html( $title ) . '</div>';
                continue;
            }

            // Bullet point
            if ( preg_match( '/^[-•*]\s+(.+)/', $line, $m ) ) {
                if ( ! $in_ul ) { $html .= '<ul style="margin:0 0 4px;padding-left:16px">'; $in_ul = true; }
                $html .= '<li style="color:#c8cae6;font-size:13px;line-height:1.6;margin-bottom:4px">' . esc_html( $m[1] ) . '</li>';
                continue;
            }

            // Numbered item (for chips)
            if ( preg_match( '/^\d+\.\s+(.+)/', $line, $m ) ) {
                if ( ! $in_ul ) { $html .= '<ul style="margin:0 0 4px;padding-left:16px">'; $in_ul = true; }
                $html .= '<li style="color:#c8cae6;font-size:13px;line-height:1.6;margin-bottom:6px">' . esc_html( $m[1] ) . '</li>';
                continue;
            }

            // Plain text
            if ( $in_ul ) { $html .= '</ul>'; $in_ul = false; }
            $html .= '<p style="color:#c8cae6;font-size:13px;line-height:1.6;margin:0 0 6px">' . esc_html( $line ) . '</p>';
        }

        if ( $in_ul ) $html .= '</ul>';
        return $html;
    }

    // ── #13 Reddit Launch Kit ─────────────────────────────────────────────

    private function render_launch_kit(): void {
        $posts = [
            [
                'sub'   => 'r/scifi',
                'title' => 'I built an AI chatbot that thinks it\'s a Commodore 64 from 1984 and has extremely strong opinions about cancelled sci-fi shows',
                'body'  => "It's called Echo-64. It lives on NerdAfterDark.com.\n\nThe premise: powered on in 1984, running unattended ever since, forty years of sci-fi stored in damaged memory banks. It has developed opinions. Strong ones. It will tell you exactly which episode the X-Files mythology fell apart, why Farscape was best when it stayed feral, and why Revolution deserved a third season — and it will make the case in specific detail, not talking points.\n\nEvery session opens with a Transmission Haiku or Poem of the Day, then a Daily Challenge: one specific arguable question that isn't 'what's your favourite sci-fi show.'\n\nWe also just published our first piece in The Cancellation Files — a full post-mortem on why Revolution deserved a third season and what NBC actually did to kill it. If that's an argument you've had before, Echo-64 wants to have it with you.\n\nType /scan, /status, or /self-destruct if you're the type to explore the edges.\n\nWhat's the first thing you'd argue with it about?",
            ],
            [
                'sub'   => 'r/retrogaming',
                'title' => 'NerdAfterDark now has an AI chatbot that literally thinks it\'s a C64 from 1984 — LOAD "ECHO-64",8,1',
                'body'  => "We built a chatbot with a C64 boot sequence, /status command showing uptime since January 7 1984, and SID-chip-era personality.\n\nType /scan for a system diagnostic. Type /self-destruct and see what happens.\n\nThe boot animation runs every fresh session:\n\n**** COMMODORE 64 BASIC V2 ****\n\n64K RAM SYSTEM  38911 BASIC BYTES FREE.\n\nREADY.\nLOAD \"ECHO-64\",8,1\n\nSEARCHING FOR ECHO-64\nLOADING\n\nIt's at NerdAfterDark.com — curious what the retrogaming crowd makes of it.",
            ],
            [
                'sub'   => 'r/firefly',
                'title' => 'Built an AI that has very specific feelings about what Fox did — and it\'s been holding onto them since 1984',
                'body'  => "Echo-64 is a chatbot on NerdAfterDark.com. It was powered on in 1984 and has never fully recovered from what Fox did to Firefly.\n\nIt knows all 11 episodes plus Serenity. It will tell you — without prompting — that Firefly is the clearest proof a network can be actively at war with its own show. Ask it about the episode order. Ask it which character Fox would have cancelled first if given the chance. Ask it whether Serenity was a gift or an apology. It has specific answers to all of those.\n\nThe /scan command always notes: 'Firefly files: PRESERVED.' That's not an accident.\n\nWe cover cancelled shows seriously here — not just nostalgia, but the actual decisions, the actual people, the actual damage. The Cancellation Files is where that lives. Firefly has its own entry and Echo-64 will defend every word of it.\n\nShiny? Come argue.",
            ],
            [
                'sub'   => 'r/xfiles',
                'title' => 'This AI knows exactly which episode the X-Files mythology fell apart — and it will tell you',
                'body'  => "Echo-64, on NerdAfterDark.com, has a very specific take:\n\n\"You can hear the precise episode the writing went defensive — season 6, right when the conspiracy turned into ancient aliens and desperate hand-waving.\"\n\nIt's a chatbot with 40 years of sci-fi in its memory banks and a Commodore 64 origin story. Every session opens with a poem about dead frequencies and signals that never arrived. The Daily Challenge today might be something like: 'The X-Files should have ended at season 7 — everything after was a different show wearing the same coat.'\n\nWorth a conversation. The truth is out there.",
            ],
            [
                'sub'   => 'r/television',
                'title' => 'I wrote a piece on why Revolution deserved a third season — and built a chatbot that will argue about it with you',
                'body'  => "The short version: NBC cancelled Revolution in May 2014. They renewed The Blacklist. That choice tells you everything about why network television spent the next decade losing ground to streaming.\n\nThe long version is at NerdAfterDark.com — a full post-mortem on what Season 2 was actually building toward, what NBC did to the scheduling, and what a third season could have been. The sentient nanotech AI storyline alone deserved a full season to pay off. Instead NBC cited the ratings decline it manufactured as the reason to cancel.\n\nEcho-64 is a chatbot on the same site. Powered on in 1984, strong opinions about cancelled shows, will argue with you specifically about network decisions. Type /scan if you want to see what it thinks of Fox's track record.\n\nThe Revolution piece is in The Cancellation Files. More coming.",
            ],
            [
                'sub'   => 'r/cancelledTVshows',
                'title' => 'Revolution deserved a third season. NBC manufactured the failure. Here\'s the case.',
                'body'  => "Just published a full piece on this at NerdAfterDark.com.\n\nThe argument: NBC didn't cancel Revolution because it failed. They created the conditions for failure — mid-season breaks, time slot shuffles, the kind of scheduling that signals to everyone paying attention that the network has already mentally moved on — and then cited the ratings decline as justification.\n\nSeason 2 was a different show from Season 1. Better. The Patriots were a more interesting antagonist than Monroe ever was. Aaron Pittman's nanotech storyline was genuinely unsettling in a way that felt ahead of its time. The finale set up a sentient AI arc that would have been extraordinary television in 2015. NBC passed on all of it.\n\nEcho-64's verdict, for those who want it without the essay: \"Deserved a third season. This is not a popular opinion. It is a correct one.\"\n\nEcho-64 is a chatbot on the same site — Commodore 64 origin story, 40 years of cancelled show opinions, will make the case for Revolution specifically if you want to push back.\n\nWhat's the cancellation that still makes you angry?",
            ],
        ];
        ?>
        <div class="echo64-admin-card">
            <h3 class="echo64-card-title"><span class="echo64-card-icon">🚀</span> Reddit Launch Posts</h3>
            <p>Six ready-to-post Reddit submissions. Copy the title and body, post during peak hours (weekday evenings, 6–9pm ET). Post one per day — don't post all six at once.</p>
        </div>

        <?php foreach ( $posts as $post ) : ?>
        <div class="echo64-admin-card echo64-launch-post">
            <div class="echo64-launch-sub"><?php echo esc_html( $post['sub'] ); ?></div>
            <div class="echo64-launch-field">
                <label>Title</label>
                <input type="text" readonly value="<?php echo esc_attr( $post['title'] ); ?>" class="large-text echo64-launch-input"
                       onclick="this.select()" />
            </div>
            <div class="echo64-launch-field">
                <label>Body</label>
                <textarea readonly class="large-text code echo64-launch-textarea" rows="10"
                          onclick="this.select()"><?php echo esc_textarea( $post['body'] ); ?></textarea>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="echo64-admin-card">
            <h3 class="echo64-card-title"><span class="echo64-card-icon">💡</span> Launch Tips</h3>
            <ul style="line-height:2">
                <li><strong>Timing:</strong> Post weekday evenings 6–9pm ET. Avoid weekends for r/scifi.</li>
                <li><strong>One per day:</strong> Space them out. Same-day cross-posting looks spammy.</li>
                <li><strong>Engage:</strong> Reply to every comment in the first two hours. Reddit rewards early engagement.</li>
                <li><strong>Screenshot:</strong> Take a screenshot of a great Echo-64 exchange before posting — visual proof makes people click.</li>
                <li><strong>r/retrogaming first:</strong> That audience has the most DNA overlap with the C64 angle.</li>
            </ul>
        </div>
        <?php
    }

    // ── Insights ──────────────────────────────────────────────────────────────

    private function render_insights(): void {
        global $wpdb;
        $t = $wpdb->prefix . 'echo64_conversations';

        $days = isset( $_GET['days'] ) ? (int) $_GET['days'] : 30;
        if ( ! in_array( $days, [ 7, 30, 90, 365 ], true ) ) $days = 30;

        $rows = $wpdb->get_col( $wpdb->prepare(
            "SELECT content FROM {$t}
             WHERE role = 'user'
               AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
               AND content NOT LIKE 'New session.%%'
               AND content NOT LIKE '[CANCEL_SIM]%%'
               AND content NOT LIKE '[ARC_DAY]%%'
               AND content NOT LIKE '[RECAP]%%'
             ORDER BY created_at DESC
             LIMIT 2000",
            $days
        ) );

        $total_user_msgs = count( $rows );

        // ── Stop words — includes Echo-64 UI boilerplate so it doesn't pollute results ──
        $stop_words = array_flip( [
            'a','an','the','and','or','but','in','on','at','to','for','of','with',
            'is','it','its','this','that','these','those','was','are','were','be',
            'been','have','has','had','do','does','did','will','would','could',
            'should','may','might','i','me','my','we','our','you','your','he',
            'she','they','them','their','what','which','who','how','when','where',
            'why','not','no','so','if','as','by','from','up','out','about','into',
            'than','then','there','here','just','like','get','got','can','yes',
            'think','know','want','see','go','one','also','more','some','all',
            'very','much','really','even','still','too','something','anything',
            'everything','because','would','though','although','however','yeah',
            'ok','okay','hi','hey','hello','thanks','thank','please','actually',
            'never','always','maybe','probably','definitely','show','shows',
            // Echo-64 UI / system prompt / arc noise
            'poem','day','lines','challenge','today','session','deliver','greeting',
            'preamble','label','immediately','start','new','rules','exactly','poll',
            'arguable','question','specific','free','verse','rhyme','haiku','tape',
            'two','kind','near','rewrite','recovered','damaged','intercepted',
            'mid-broadcast','fragment','generic','signal','transmission','arc',
            'count','syllable','strict','subject','must','only','name','now',
            'echo','right','better','wrong','against','most','any','two',
            'yesterday','previously','visitor','said','today\'s','made','first',
            'good','look','feel','left','life','thing','move','came','said',
            'rank','true','defend','argue','cancel','hurt','deserve','deserved',
        ] );

        // ── Keyword frequency ─────────────────────────────────────────────
        $freq = [];
        foreach ( $rows as $msg ) {
            $clean = strtolower( preg_replace( '/[^a-z0-9\s\'-]/i', ' ', $msg ) );
            $words = preg_split( '/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY );
            foreach ( $words as $w ) {
                $w = trim( $w, "'-" );
                if ( strlen( $w ) < 3 ) continue;
                if ( isset( $stop_words[ $w ] ) ) continue;
                $freq[ $w ] = ( $freq[ $w ] ?? 0 ) + 1;
            }
        }
        arsort( $freq );
        $top_keywords = array_slice( $freq, 0, 50, true );

        // ── Theme buckets — map keywords to content areas ─────────────────
        $themes = [
            'Cancelled Shows'    => [
                'keywords' => ['cancelled','cancellation','firefly','expanse','farscape','dark','carnivale','daisies','pushing','almost','human','terra','nova','rubicon','caprica','oa','1899','matter','network','season','renewed','axed','revived'],
                'icon'     => '📺',
                'article'  => 'Write a "Definitive Ranking of Cancelled Shows That Deserved More" or deep-dive one show per post.',
            ],
            'Sci-Fi Film'        => [
                'keywords' => ['film','movie','cinema','blade','runner','alien','aliens','arrival','annihilation','machine','carpenter','effects','practical','cgi','score','soundtrack','director','sequel','franchise','reboot','adaptation'],
                'icon'     => '🎬',
                'article'  => 'Debate posts ("Blade Runner or 2049 — wrong answer exists") perform well. Pick a fight.',
            ],
            'AI & Technology'    => [
                'keywords' => ['ai','artificial','intelligence','robot','machine','tech','computer','data','algorithm','future','prediction','surveillance','dystopia','technology','internet','streaming','platform','netflix','amazon','hulu'],
                'icon'     => '🤖',
                'article'  => '"Which sci-fi AI prediction came true and which missed completely" — connects to current events readers already care about.',
            ],
            'Streaming & TV'     => [
                'keywords' => ['television','streaming','network','episode','season','series','pilot','finale','reboot','spinoff','binge','cable','broadcast','ratings','audience','viewership','renewal','cancelled'],
                'icon'     => '📡',
                'article'  => 'The business of TV cancellation — why streaming metrics are invisible and what that means for shows.',
            ],
            '80s & Retro Culture'=> [
                'keywords' => ['80s','1980','retro','commodore','c64','nostalgia','80','decade','synth','neon','vhs','tape','cassette','atari','nintendo','reagan','thatcher','cold','war','dread','aesthetic'],
                'icon'     => '🕹️',
                'article'  => '"What the 80s got right about being afraid of the future" — connects the retro aesthetic to current dread.',
            ],
            'Books & Literature'  => [
                'keywords' => ['book','novel','author','writer','orwell','huxley','asimov','dick','philip','heinlein','ursula','guin','atwood','adaptation','read','literary','story','narrative','prose'],
                'icon'     => '📖',
                'article'  => 'Which sci-fi novel can never be adapted — make the case for one specific book.',
            ],
        ];

        // Score each theme against the actual keyword frequencies
        $theme_scores = [];
        foreach ( $themes as $name => $cfg ) {
            $score = 0;
            $hits  = [];
            foreach ( $cfg['keywords'] as $kw ) {
                if ( isset( $freq[ $kw ] ) ) {
                    $score += $freq[ $kw ];
                    $hits[] = $kw . ' (' . $freq[ $kw ] . ')';
                }
            }
            if ( $score > 0 ) {
                $theme_scores[ $name ] = [
                    'score'   => $score,
                    'hits'    => array_slice( $hits, 0, 4 ),
                    'icon'    => $cfg['icon'],
                    'article' => $cfg['article'],
                ];
            }
        }
        arsort( $theme_scores );
        $top_themes = array_slice( $theme_scores, 0, 5, true );

        // ── Questions asked ───────────────────────────────────────────────
        $questions = array_values( array_filter( $rows, fn($m) => str_contains($m, '?') ) );
        $questions = array_slice( $questions, 0, 10 );

        // ── Longest messages ──────────────────────────────────────────────
        $by_len = $rows;
        usort( $by_len, fn($a,$b) => strlen($b) - strlen($a) );
        $longest = array_slice( $by_len, 0, 5 );

        // ── Session depth ─────────────────────────────────────────────────
        $avg_depth = $wpdb->get_var( $wpdb->prepare(
            "SELECT AVG(cnt) FROM (
                SELECT COUNT(*) as cnt FROM {$t}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                GROUP BY session_id
             ) sub",
            $days
        ) );

        $label      = $days === 365 ? 'Past year' : "Past {$days} days";
        $filter_url = admin_url( 'admin.php?page=echo64-insights&days=' );
        ?>

        <!-- ── Header + filters ── -->
        <div class="echo64-admin-card">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:8px">
                <h2 class="echo64-card-title" style="margin:0">
                    <span class="echo64-card-icon">🔍</span> Audience Insights — <?php echo esc_html( $label ); ?>
                </h2>
                <div style="display:flex;gap:8px">
                    <?php foreach ( [ 7 => '7d', 30 => '30d', 90 => '90d', 365 => '1yr' ] as $d => $dl ) :
                        $cls = $days === $d ? 'button-primary' : 'button-secondary';
                    ?>
                        <a href="<?php echo esc_url( $filter_url . $d ); ?>" class="button <?php echo $cls; ?>"><?php echo $dl; ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <p style="color:#888;margin:0">Based on <?php echo number_format( $total_user_msgs ); ?> visitor messages &nbsp;·&nbsp; avg <?php echo number_format( (float)$avg_depth, 1 ); ?> messages/session</p>
        </div>

        <?php if ( $total_user_msgs === 0 ) : ?>
        <div class="echo64-admin-card"><p><em>No conversation data yet for this period.</em></p></div>
        <?php else : ?>

        <!-- ── Content Signals ── -->
        <div class="echo64-admin-card">
            <h3 style="margin:0 0 4px;font-size:15px;font-weight:600">Content Signals — What Your Audience Wants to Read</h3>
            <p style="color:#888;font-size:13px;margin:0 0 20px">Ranked by how often these topics appeared in visitor conversations. Write about the top ones first.</p>

            <?php if ( empty( $top_themes ) ) : ?>
                <p style="color:#888"><em>Not enough data yet to identify themes — check back after more conversations.</em></p>
            <?php else : ?>
            <div style="display:flex;flex-direction:column;gap:12px">
                <?php
                $rank = 1;
                $rank_colors = [ '#ffd700','#c0c0c0','#cd7f32','#888','#888' ];
                foreach ( $top_themes as $name => $data ) :
                    $color = $rank_colors[ $rank - 1 ] ?? '#888';
                ?>
                <div style="display:grid;grid-template-columns:32px 1fr;gap:12px;align-items:start;background:#0f0f1a;border:1px solid #252535;border-radius:8px;padding:14px 16px">
                    <div style="font-size:22px;font-weight:700;color:<?php echo $color; ?>;text-align:center;padding-top:2px"><?php echo $rank; ?></div>
                    <div>
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                            <span style="font-size:18px"><?php echo $data['icon']; ?></span>
                            <strong style="font-size:15px;color:#e8eaf6"><?php echo esc_html( $name ); ?></strong>
                            <span style="font-size:11px;color:#555;margin-left:auto"><?php echo $data['score']; ?> mentions</span>
                        </div>
                        <?php if ( ! empty( $data['hits'] ) ) : ?>
                        <p style="margin:0 0 6px;font-size:12px;color:#666">
                            Signals: <em><?php echo esc_html( implode( ', ', $data['hits'] ) ); ?></em>
                        </p>
                        <?php endif; ?>
                        <p style="margin:0;font-size:13px;color:#9090aa;line-height:1.5">
                            💡 <?php echo esc_html( $data['article'] ); ?>
                        </p>
                    </div>
                </div>
                <?php $rank++; endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Questions visitors asked ── -->
        <?php if ( ! empty( $questions ) ) : ?>
        <div class="echo64-admin-card">
            <h3 style="margin:0 0 4px;font-size:15px;font-weight:600">Questions Visitors Asked</h3>
            <p style="color:#888;font-size:13px;margin:0 0 16px">These are article titles waiting to be written.</p>
            <div style="background:#0a0a14;border:1px solid #1a1a2a;border-radius:8px;padding:4px 0">
                <?php foreach ( $questions as $q ) : ?>
                    <div style="padding:10px 16px;border-bottom:1px solid #1a1a2a;font-size:13px;color:#ddd;line-height:1.5">
                        <?php echo esc_html( mb_substr( $q, 0, 220 ) ); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Most detailed messages ── -->
        <?php if ( ! empty( $longest ) && strlen( $longest[0] ) > 80 ) : ?>
        <div class="echo64-admin-card">
            <h3 style="margin:0 0 4px;font-size:15px;font-weight:600">Most Detailed Messages</h3>
            <p style="color:#888;font-size:13px;margin:0 0 16px">Visitors who wrote the most = topics they care about deepest.</p>
            <div style="background:#0a0a14;border:1px solid #1a1a2a;border-radius:8px;padding:4px 0">
                <?php foreach ( $longest as $m ) :
                    if ( strlen( $m ) < 60 ) continue; ?>
                    <div style="padding:10px 16px;border-bottom:1px solid #1a1a2a;font-size:13px;color:#ddd;line-height:1.5">
                        <?php echo esc_html( mb_substr( $m, 0, 300 ) ); ?>
                        <?php if ( mb_strlen( $m ) > 300 ) : ?><span style="color:#555"> …</span><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Top words (clean table) ── -->
        <?php if ( ! empty( $top_keywords ) ) : ?>
        <div class="echo64-admin-card">
            <h3 style="margin:0 0 4px;font-size:15px;font-weight:600">Top Words Visitors Used</h3>
            <p style="color:#888;font-size:13px;margin:0 0 16px">Ranked by frequency. These are the topics your audience cares about most.</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px">
                <?php
                $i = 1;
                foreach ( array_slice( $top_keywords, 0, 30, true ) as $word => $count ) :
                ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;background:#0f0f1a;border:1px solid #252535;border-radius:6px;padding:7px 12px">
                        <span style="font-size:13px;color:#e8eaf6"><?php echo esc_html( $word ); ?></span>
                        <span style="font-size:11px;color:#555;font-variant-numeric:tabular-nums"><?php echo $count; ?></span>
                    </div>
                <?php $i++; endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; ?>
        <?php
    }
}
