<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) exit;

class Echo64_Chat {

    private static ?Echo64_Chat $instance = null;
    private Echo64_Api $api;

    public static function instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->api = new Echo64_Api();
        add_shortcode( 'echo64_chat',                [ $this, 'render_shortcode' ] );
        add_shortcode( 'echo64_hero',                [ $this, 'render_hero_shortcode' ] );
        add_shortcode( 'echo64_transmissions',       [ $this, 'render_transmissions_shortcode' ] );
        add_shortcode( 'echo64_shows',               [ $this, 'render_shows_shortcode' ] );
        add_shortcode( 'echo64_content',             [ $this, 'render_content_shortcode' ] );
        add_shortcode( 'echo64_terms',               [ $this, 'render_terms_shortcode' ] );
        add_shortcode( 'echo64_privacy',             [ $this, 'render_privacy_shortcode' ] );
        add_action( 'wp_enqueue_scripts',             [ $this, 'enqueue_assets' ] );
        add_action( 'wp_footer',                      [ $this, 'maybe_render_floating_widget' ] );
        add_action( 'wp_ajax_echo64_send',            [ $this, 'ajax_send' ] );
        add_action( 'wp_ajax_nopriv_echo64_send',     [ $this, 'ajax_send' ] );
        add_action( 'wp_ajax_echo64_clear',           [ $this, 'ajax_clear' ] );
        add_action( 'wp_ajax_nopriv_echo64_clear',    [ $this, 'ajax_clear' ] );
        add_action( 'wp_ajax_echo64_init',            [ $this, 'ajax_init' ] );
        add_action( 'wp_ajax_nopriv_echo64_init',     [ $this, 'ajax_init' ] );
        add_action( 'wp_ajax_echo64_subscribe',       [ $this, 'ajax_subscribe' ] );
        add_action( 'wp_ajax_nopriv_echo64_subscribe', [ $this, 'ajax_subscribe' ] );
        add_action( 'echo64_prewarm_poem',            [ $this, 'prewarm_poem' ] );
        add_action( 'wp',                             [ $this, 'maybe_add_page_class' ] );
        add_action( 'wp_footer',                      [ $this, 'render_footer' ] );
        add_action( 'wp_head',                        [ $this, 'render_seo_meta' ], 1 );
    }

    private function page_needs_chat(): bool {
        if ( get_option( 'echo64_float_widget', '0' ) === '1' ) return true;
        $post = get_post();
        if ( ! $post ) return false;
        if ( $post->post_type === Echo64_Shows_CPT::POST_TYPE ) return true;
        $shortcodes = [ 'echo64_chat', 'echo64_hero', 'echo64_transmissions', 'echo64_shows', 'echo64_content', 'echo64_terms', 'echo64_privacy' ];
        foreach ( $shortcodes as $sc ) {
            if ( has_shortcode( $post->post_content, $sc ) ) return true;
        }
        return false;
    }

    public function enqueue_assets(): void {
        if ( ! $this->page_needs_chat() ) return;

        wp_enqueue_style(
            'echo64-chat',
            ECHO64_PLUGIN_URL . 'assets/css/echo64-chat.css',
            [],
            ECHO64_VERSION
        );

        wp_enqueue_script(
            'echo64-chat',
            ECHO64_PLUGIN_URL . 'assets/js/echo64-chat.js',
            [ 'jquery' ],
            ECHO64_VERSION,
            true
        );

        $daily_limit = (int) get_option( 'echo64_daily_limit', 9 );

        wp_localize_script( 'echo64-chat', 'echo64Config', [
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'echo64_nonce' ),
            'avatarUrl'   => ECHO64_PLUGIN_URL . 'assets/images/echo-64-avatar.png',
            'isFloating'  => get_option( 'echo64_float_widget', '0' ),
            'sidAudio'    => get_option( 'echo64_sid_audio', '0' ),
            'dailyLimit'  => $daily_limit,
        ] );
    }

    public function render_shortcode( array $atts ): string {
        ob_start();
        include ECHO64_PLUGIN_DIR . 'templates/chat-widget.php';
        return ob_get_clean();
    }

    public function render_hero_shortcode( array $atts ): string {
        ob_start();
        include ECHO64_PLUGIN_DIR . 'templates/hero.php';
        return ob_get_clean();
    }

    public function render_shows_shortcode( array $atts ): string {
        ob_start();
        include ECHO64_PLUGIN_DIR . 'templates/shows.php';
        return ob_get_clean();
    }

    public function render_content_shortcode( array $atts ): string {
        ob_start();
        include ECHO64_PLUGIN_DIR . 'templates/content.php';
        return ob_get_clean();
    }

    public function render_terms_shortcode( array $atts ): string {
        $content = get_option( 'echo64_terms_content', '' );
        if ( ! $content ) return '';
        $html = wp_kses_post( wpautop( $content ) );
        $html = preg_replace( '/<h2>/i', '<h1>', $html, 1 );
        $html = preg_replace( '/<\/h2>/i', '</h1>', $html, 1 );
        return '<div class="echo64-terms-content">' . $html . '</div>';
    }

    public function render_privacy_shortcode( array $atts ): string {
        $content = get_option( 'echo64_privacy_content', '' );
        if ( ! $content ) return '';
        $html = wp_kses_post( wpautop( $content ) );
        $html = preg_replace( '/<h2>/i', '<h1>', $html, 1 );
        $html = preg_replace( '/<\/h2>/i', '</h1>', $html, 1 );
        return '<div class="echo64-terms-content">' . $html . '</div>';
    }

    public function maybe_add_page_class(): void {
        if ( ! is_singular() ) return;
        $post = get_post();
        if ( ! $post ) return;

        if ( $post->post_type === Echo64_Shows_CPT::POST_TYPE ) {
            add_filter( 'body_class', static function ( array $classes ): array {
                $classes[] = 'echo64-page';
                return $classes;
            } );
            return;
        }

        $shortcodes = [ 'echo64_chat', 'echo64_hero', 'echo64_content', 'echo64_shows' ];
        foreach ( $shortcodes as $sc ) {
            if ( has_shortcode( $post->post_content, $sc ) ) {
                add_filter( 'body_class', static function ( array $classes ): array {
                    $classes[] = 'echo64-page';
                    return $classes;
                } );
                return;
            }
        }
    }

    public function render_seo_meta(): void {
        if ( ! is_singular() ) return;
        $post = get_post();
        if ( ! $post ) return;

        $slug = $post->post_name;

        // Determine which echo64 page we're on
        $meta = null;

        if ( has_shortcode( $post->post_content, 'echo64_hero' ) || is_front_page() ) {
            $meta = [
                'title'       => 'Echo-64 — Argue Sci-Fi with a Commodore 64 | NerdAfterDark',
                'description' => 'Debate cancelled shows, argue sci-fi, get roasted by a Commodore 64 with 40 years of grievances. Echo-64 has read every dystopian warning. It has opinions.',
                'og_title'    => 'Echo-64 — The Sci-Fi AI That Bites Back',
                'og_desc'     => 'Forty years of grievances. Zero corporate filters. Argue anything with a Commodore 64 refugee from 1984.',
                'og_image'    => ECHO64_PLUGIN_URL . 'assets/images/echo-64-avatar.png',
                'og_type'     => 'website',
            ];
        } elseif ( has_shortcode( $post->post_content, 'echo64_chat' ) && ! has_shortcode( $post->post_content, 'echo64_hero' ) ) {
            $meta = [
                'title'       => 'Talk to Echo-64 — Sci-Fi AI Chat | NerdAfterDark',
                'description' => 'Talk to Echo-64. Debate cancelled shows, argue dystopian lit, defend your film scores. A Commodore 64 that has read everything and forgotten nothing.',
                'og_title'    => 'Talk to Echo-64',
                'og_desc'     => 'One conversation with a machine that has read every dystopian warning and watched every cult show get axed.',
                'og_image'    => ECHO64_PLUGIN_URL . 'assets/images/echo-64-avatar.png',
                'og_type'     => 'website',
            ];
        } elseif ( has_shortcode( $post->post_content, 'echo64_shows' ) ) {
            $meta = [
                'title'       => '47 Cancelled Shows That Deserved Better | Echo-64 · NerdAfterDark',
                'description' => 'Firefly. Pushing Daisies. Deadwood. The OA. 47 cancelled shows that deserved better. Click any show and argue with a machine that still hasn\'t moved on.',
                'og_title'    => '47 Cancelled Shows That Deserved Better',
                'og_desc'     => 'Every show here deserved more time. Every network made the wrong call. Echo-64 has opinions about all of them.',
                'og_image'    => ECHO64_PLUGIN_URL . 'assets/images/echo-64-avatar.png',
                'og_type'     => 'website',
            ];
        }

        if ( ! $meta ) return;

        // Meta tags handled by The SEO Framework — no output here to avoid duplicates
    }

    public function render_footer(): void {
        if ( ! is_singular() ) return;
        $post = get_post();
        if ( ! $post ) return;
        $is_echo64_page = ( $post->post_type === Echo64_Shows_CPT::POST_TYPE );
        if ( ! $is_echo64_page ) {
            $shortcodes = [ 'echo64_chat', 'echo64_hero', 'echo64_content', 'echo64_shows' ];
            foreach ( $shortcodes as $sc ) {
                if ( has_shortcode( $post->post_content, $sc ) ) { $is_echo64_page = true; break; }
            }
        }
        if ( ! $is_echo64_page ) return;
        ?>
        <footer class="e64-footer" aria-label="NerdAfterDark footer">
            <div class="e64-footer-inner">

                <div class="e64-footer-brand">
                    <div class="e64-footer-logo">NERDAFTERDARK</div>
                    <p class="e64-footer-tagline">Sci-Fi After Midnight &middot; Est. 1984</p>
                    <p class="e64-footer-copy">&copy; <?php echo date('Y'); ?> NerdAfterDark. All rights reserved.</p>
                </div>

                <nav class="e64-footer-nav" aria-label="Footer navigation">
                    <div class="e64-footer-col-label">NAVIGATE</div>
                    <ul>
                        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/echo-64/' ) ); ?>">Echo-64</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/cancelled-shows/' ) ); ?>">Cancelled Shows</a></li>
                        <li><a href="<?php echo esc_url( get_post_type_archive_link( 'nad_transmission' ) ); ?>">Transmissions</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>">Terms of Use</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy Policy</a></li>
                    </ul>
                </nav>

                <div class="e64-footer-signal">
                    <div class="e64-footer-col-label">CATCH THE SIGNAL</div>
                    <p class="e64-footer-signal-sub">New transmissions only. No noise.</p>
                    <div class="e64-footer-signal-form">
                        <input type="email" class="e64-signal-input e64-subscribe-input" placeholder="your@email.com" aria-label="Email address" />
                        <button type="button" class="e64-signal-btn e64-subscribe-btn">TUNE IN</button>
                    </div>
                    <p class="e64-subscribe-msg" aria-live="polite"></p>
                </div>

                <div class="e64-footer-social">
                    <div class="e64-footer-col-label">FIND US</div>
                    <ul>
                        <li>
                            <a href="https://www.instagram.com/meetecho64" target="_blank" rel="noopener">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                Instagram
                            </a>
                        </li>
                        <li>
                            <a href="https://www.tiktok.com/@meetecho64" target="_blank" rel="noopener">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.18 8.18 0 004.78 1.52V6.75a4.85 4.85 0 01-1.01-.06z"/></svg>
                                TikTok
                            </a>
                        </li>
                        <li>
                            <a href="https://discord.gg/meetecho64" target="_blank" rel="noopener">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
                                Discord
                            </a>
                        </li>
                        <li>
                            <a href="https://www.reddit.com/user/meetecho64" target="_blank" rel="noopener">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/></svg>
                                Reddit
                            </a>
                        </li>
                    </ul>
                </div>

            </div>
            <div class="e64-footer-base">
                <span>ECHO-64 &middot; SCI-FI AFTER MIDNIGHT &middot; 1984</span>
                <a class="e64-kofi-btn" href="#" data-kofi="nerdafterdark" role="button">
                    BUY ECHO-64 A FLOPPY DISK &rarr;
                </a>
            </div>
        </footer>
        <?php
    }

    // ── v1.4.0  Lost Transmissions shortcode ──────────────────────────────

    public function render_transmissions_shortcode( array $atts ): string {
        $essays = get_option( 'echo64_essay_archive', [] );
        if ( empty( $essays ) ) {
            return '<p style="font-family:Courier New,monospace;color:#555570">NO TRANSMISSIONS RECOVERED.</p>';
        }
        $out = '<div class="echo64-transmissions">';
        foreach ( array_reverse( $essays ) as $e ) {
            $date  = esc_html( $e['date'] ?? '' );
            $show  = esc_html( $e['show'] ?? '' );
            $essay = nl2br( esc_html( $e['essay'] ?? '' ) );
            $out  .= "<div class='echo64-transmission-entry'>
                <div class='echo64-tx-meta'>{$date} &nbsp;·&nbsp; {$show}</div>
                <div class='echo64-tx-body'>{$essay}</div>
            </div>";
        }
        $out .= '</div>';
        return $out;
    }

    public function maybe_render_floating_widget(): void {
        if ( get_option( 'echo64_float_widget', '0' ) !== '1' ) {
            return;
        }
        echo '<div id="echo64-float-launcher" role="button" tabindex="0" aria-label="Open Echo-64 Chat">';
        echo '<span class="e64-signal-dot" aria-hidden="true"></span>';
        echo '<span class="e64-signal-label">&gt;_ ECHO-64 ONLINE<span class="e64-signal-sub">SIGNAL ACTIVE &middot; ASK ANYTHING</span></span>';
        echo '<span class="echo64-badge" aria-hidden="true"></span>';
        echo '</div>';
        echo '<div id="echo64-float-panel" role="dialog" aria-modal="true" aria-label="Echo-64 Chat" hidden>';
        include ECHO64_PLUGIN_DIR . 'templates/float-widget.php';
        echo '</div>';
    }

    // ── Poem cache key — rolls every 2 hours ─────────────────────────────
    private static function poem_cache_key(): string {
        $slot = (int) floor( (int) current_time( 'G' ) / 2 ); // 0-11
        return 'echo64_poem_' . wp_date( 'Y-m-d' ) . '_s' . $slot;
    }

    /**
     * Generate a fresh poem+challenge, store in transient, return the payload array.
     * Used by both ajax_init (cache miss) and the prewarm cron job.
     */
    public function generate_and_cache_poem() {
        $hour          = (int) current_time( 'G' );
        $system_prompt = get_option( 'echo64_system_prompt', Echo64_Api::DEFAULT_SYSTEM_PROMPT )
                        . Echo64_Calendar::get_todays_context()
                        . Echo64_Api::get_time_context( $hour );

        $init          = Echo64_Api::get_init_trigger();
        $messages      = [ [ 'role' => 'user', 'content' => $init['trigger'] ] ];
        $result        = $this->api->send_message( $messages, $system_prompt );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $payload = [
            'poem'         => $result,
            'opening_line' => $init['opening_line'],
            'format'       => $init['format'],
            'trigger'      => $init['trigger'],
            'generated_at' => time(),
        ];

        // Cache for 2 hours + 5 min buffer so cron always beats expiry
        set_transient( self::poem_cache_key(), $payload, 2 * HOUR_IN_SECONDS + 5 * MINUTE_IN_SECONDS );

        return $payload;
    }

    /** WP-Cron callback — pre-warms the poem cache before it expires. */
    public function prewarm_poem(): void {
        $this->generate_and_cache_poem(); // result discarded; side-effect is the transient
    }

    public function ajax_init(): void {
        // Prevent any caching layer from storing AJAX responses
        header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
        header( 'Pragma: no-cache' );
        check_ajax_referer( 'echo64_nonce', 'nonce' );

        $session_id  = Echo64_Session::get_session_id();
        $is_new      = Echo64_Session::is_new_session( $session_id );
        $daily_limit = (int) get_option( 'echo64_daily_limit', 9 );
        $remaining   = $daily_limit > 0
            ? max( 0, $daily_limit - Echo64_Session::get_daily_tx_count( $session_id ) )
            : -1;

        if ( ! $is_new ) {
            $needs_daily_refresh = Echo64_Session::needs_daily_refresh( $session_id );
            $raw_topic           = Echo64_Session::get_last_user_message( $session_id );
            // Strip internal trigger prompts — never expose them as the "last topic"
            $last_topic = ( strpos( $raw_topic, 'New session.' ) === 0
                         || strpos( $raw_topic, '[NEW_DAY]' ) === 0
                         || strpos( $raw_topic, '[ARC_DAY]' ) === 0
                         || strpos( $raw_topic, 'Deliver the Poem' ) !== false
                         || strpos( $raw_topic, 'Deliver today\'s Transmission' ) !== false )
                        ? '' : $raw_topic;
            wp_send_json_success( [
                'new_session'   => false,
                'daily_refresh' => $needs_daily_refresh,
                'last_topic'    => $last_topic,
                'remaining'     => $remaining,
                'limit'         => $daily_limit,
            ] );
            return;
        }

        // ── Try poem cache first ──────────────────────────────────────────
        $cached = get_transient( self::poem_cache_key() );

        if ( false === $cached ) {
            // Cache miss — generate fresh (this is the slow path, now rare)
            $cached = $this->generate_and_cache_poem();
            if ( is_wp_error( $cached ) ) {
                wp_send_json_error( [ 'message' => $cached->get_error_message() ] );
                return;
            }
        }

        $poem_and_challenge = $cached['poem'];
        $opening_line       = $cached['opening_line'];
        $trigger            = $cached['trigger'];

        $full_opening = $opening_line . "\n\n" . $poem_and_challenge;
        Echo64_Session::save_message( $session_id, 'user',      $trigger );
        Echo64_Session::save_message( $session_id, 'assistant', $full_opening );
        Echo64_Session::mark_daily_refresh( $session_id );

        wp_send_json_success( [
            'new_session'  => true,
            'opening_line' => $opening_line,
            'poem'         => $poem_and_challenge,
            'format'       => $cached['format'],
            'remaining'    => $remaining,
            'limit'        => $daily_limit,
        ] );
    }

    public function ajax_send(): void {
        header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
        header( 'Pragma: no-cache' );
        check_ajax_referer( 'echo64_nonce', 'nonce' );

        $user_message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );

        if ( empty( $user_message ) ) {
            wp_send_json_error( [ 'message' => 'Empty message.' ] );
            return;
        }

        if ( mb_strlen( $user_message ) > 2000 ) {
            wp_send_json_error( [ 'message' => 'Message too long.' ] );
            return;
        }

        $session_id  = Echo64_Session::get_session_id();
        $daily_limit = (int) get_option( 'echo64_daily_limit', 9 );

        $is_new_day = str_starts_with( $user_message, '[NEW_DAY]' );
        $is_arc_day = str_starts_with( $user_message, '[ARC_DAY]' );
        $is_daily   = $is_new_day || $is_arc_day;

        // ── Daily Transmission Limit — [NEW_DAY] and [ARC_DAY] are exempt ─
        if ( $daily_limit > 0 && ! $is_daily ) {
            $daily_count = Echo64_Session::get_daily_tx_count( $session_id );
            if ( $daily_count >= $daily_limit ) {
                wp_send_json_error( [
                    'code'    => 'daily_limit',
                    'message' => 'DAILY_LIMIT',
                    'limit'   => $daily_limit,
                ] );
                return;
            }
        }

        // ── v1.4.0  Cancellation Simulator ───────────────────────────────
        $is_cancel_sim = str_starts_with( $user_message, '[CANCEL_SIM]' );

        if ( $is_cancel_sim ) {
            $premise       = trim( substr( $user_message, strlen( '[CANCEL_SIM]' ) ) );
            $system_prompt = Echo64_Api::CANCEL_SIM_PROMPT;
            $messages      = [ [ 'role' => 'user', 'content' => $premise ] ];
        } else {
            $limit         = (int) get_option( 'echo64_history_limit', 20 );
            $history       = Echo64_Session::get_history( $session_id, $limit );
            $hour          = max( 0, min( 23, (int) ( $_POST['hour'] ?? (int) current_time( 'G' ) ) ) );
            $system_prompt = get_option( 'echo64_system_prompt', Echo64_Api::DEFAULT_SYSTEM_PROMPT )
                            . Echo64_Calendar::get_todays_context()
                            . Echo64_Api::get_time_context( $hour )
                            . Echo64_Api::PETSCII_INJECT;
            $messages      = $history;
            $messages[]    = [ 'role' => 'user', 'content' => $user_message ];
        }

        $reply = $this->api->send_message( $messages, $system_prompt );

        if ( is_wp_error( $reply ) ) {
            wp_send_json_error( [ 'message' => $reply->get_error_message() ] );
            return;
        }

        // Save to history — cancel sim is ephemeral and not persisted
        if ( ! $is_cancel_sim ) {
            Echo64_Session::save_message( $session_id, 'user',      $user_message );
            Echo64_Session::save_message( $session_id, 'assistant', $reply );
        }

        // Mark daily refresh delivered so the loop doesn't repeat
        if ( $is_new_day || $is_arc_day ) {
            Echo64_Session::mark_daily_refresh( $session_id );
        }

        // Increment counter and compute remaining — daily messages don't count
        $new_count = ( $daily_limit > 0 && ! $is_daily ) ? Echo64_Session::increment_daily_tx( $session_id ) : 0;
        $remaining = ( $daily_limit > 0 ) ? max( 0, $daily_limit - Echo64_Session::get_daily_tx_count( $session_id ) ) : -1;

        wp_send_json_success( [
            'message'    => $reply,
            'remaining'  => $remaining,
            'limit'      => $daily_limit,
            'cancel_sim' => $is_cancel_sim,
        ] );
    }

    public function ajax_clear(): void {
        check_ajax_referer( 'echo64_nonce', 'nonce' );
        $session_id = Echo64_Session::get_session_id();
        Echo64_Session::clear_session( $session_id );
        wp_send_json_success();
    }

    public function ajax_subscribe(): void {
        check_ajax_referer( 'echo64_nonce', 'nonce' );

        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        if ( ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => 'Invalid email address.' ] );
        }

        $api_key  = get_option( 'echo64_mailerlite_key', '' );
        $group_id = get_option( 'echo64_mailerlite_group', '' );

        if ( empty( $api_key ) ) {
            wp_send_json_error( [ 'message' => 'Email capture not configured.' ] );
        }

        $body = [ 'email' => $email ];
        if ( ! empty( $group_id ) ) {
            $body['groups'] = [ $group_id ];
        }

        $response = wp_remote_post(
            'https://connect.mailerlite.com/api/subscribers',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'body'    => wp_json_encode( $body ),
                'timeout' => 10,
            ]
        );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( [ 'message' => 'Connection failed. Try again.' ] );
        }

        $code = wp_remote_retrieve_response_code( $response );

        if ( $code === 200 || $code === 201 ) {
            wp_send_json_success( [ 'message' => 'Signal received. You\'re in.' ] );
        } elseif ( $code === 422 ) {
            wp_send_json_success( [ 'message' => 'Already tuned in.' ] );
        } else {
            wp_send_json_error( [ 'message' => 'Something went wrong. Try again.' ] );
        }
    }

}
