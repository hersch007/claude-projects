<?php
/**
 * Plugin Name:       Echo-64 Chatbot
 * Plugin URI:        https://www.nerdafterdark.com
 * Description:       Echo-64 — Commodore 64 refugee from 1984. Sci-Fi After Midnight chatbot powered by Claude AI. Use shortcode [echo64_chat] or enable the floating widget.
 * Version:           2.6.5
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            NerdAfterDark
 * Author URI:        https://www.nerdafterdark.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       echo64-chatbot
 * Domain Path:       /languages
 * Update URI:        https://www.nerdafterdark.com
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Plugin constants ──────────────────────────────────────────────────────────

define( 'ECHO64_VERSION',     '2.6.5' );
define( 'ECHO64_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'ECHO64_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'ECHO64_PLUGIN_FILE', __FILE__ );

/**
 * Increment this integer any time the DB schema changes.
 * The upgrade() method compares it against the stored value and runs
 * create_table() + any migration steps automatically on plugin update.
 */
define( 'ECHO64_DB_VERSION', '2' );

// ── Autoload includes ─────────────────────────────────────────────────────────

require_once ECHO64_PLUGIN_DIR . 'includes/class-echo64-session.php';
require_once ECHO64_PLUGIN_DIR . 'includes/class-echo64-api.php';
require_once ECHO64_PLUGIN_DIR . 'includes/class-echo64-calendar.php';
require_once ECHO64_PLUGIN_DIR . 'includes/class-echo64-chat.php';
require_once ECHO64_PLUGIN_DIR . 'includes/class-echo64-log.php';
require_once ECHO64_PLUGIN_DIR . 'includes/class-echo64-automations.php';
require_once ECHO64_PLUGIN_DIR . 'includes/class-echo64-admin.php';
require_once ECHO64_PLUGIN_DIR . 'includes/class-echo64-transmissions.php';
require_once ECHO64_PLUGIN_DIR . 'includes/class-echo64-shows-cpt.php';

// ── Main plugin class ─────────────────────────────────────────────────────────

final class Echo64_Chatbot {

    private static ?Echo64_Chatbot $instance = null;

    public static function instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook( ECHO64_PLUGIN_FILE,   [ $this, 'activate' ] );
        register_deactivation_hook( ECHO64_PLUGIN_FILE, [ $this, 'deactivate' ] );

        // plugins_loaded fires on every request — including after a manual zip update
        // where the activation hook does NOT re-run. This is where upgrades happen.
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    public function init(): void {
        load_plugin_textdomain(
            'echo64-chatbot',
            false,
            dirname( plugin_basename( ECHO64_PLUGIN_FILE ) ) . '/languages'
        );

        // Run DB / option migrations whenever the stored version is behind.
        $this->maybe_upgrade();

        Echo64_Admin::instance();
        Echo64_Chat::instance();
        Echo64_Log::instance();
        Echo64_Calendar::instance();
        Echo64_Automations::instance();
        Echo64_Transmissions::instance();
        Echo64_Shows_CPT::instance();
    }

    // ── Fresh activation ──────────────────────────────────────────────────────

    public function activate(): void {
        $this->create_table();
        $this->set_default_options();
        update_option( 'echo64_version',    ECHO64_VERSION );
        update_option( 'echo64_db_version', ECHO64_DB_VERSION );
        Echo64_Automations::schedule();
        flush_rewrite_rules();
    }

    // ── Upgrade on update (no activation hook re-run) ─────────────────────────

    private function maybe_upgrade(): void {
        $installed_ver    = get_option( 'echo64_version',    '0' );
        $installed_db_ver = get_option( 'echo64_db_version', '0' );

        // Nothing to do if both are current.
        if ( $installed_ver === ECHO64_VERSION && $installed_db_ver === ECHO64_DB_VERSION ) {
            return;
        }

        // Re-run table creation / migrations (dbDelta is safe to call repeatedly).
        if ( $installed_db_ver !== ECHO64_DB_VERSION ) {
            $this->create_table();
            update_option( 'echo64_db_version', ECHO64_DB_VERSION );
        }

        // Seed any new options added since the installed version.
        $this->set_default_options();

        // Record the current version.
        update_option( 'echo64_version', ECHO64_VERSION );
    }

    // ── DB schema ─────────────────────────────────────────────────────────────

    private function create_table(): void {
        global $wpdb;

        $table   = $wpdb->prefix . 'echo64_conversations';
        $charset = $wpdb->get_charset_collate();

        // dbDelta() handles CREATE and ALTER safely — idempotent on every call.
        $sql = "CREATE TABLE {$table} (
            id          BIGINT(20) UNSIGNED   NOT NULL AUTO_INCREMENT,
            session_id  VARCHAR(64)           NOT NULL,
            user_id     BIGINT(20) UNSIGNED   NOT NULL DEFAULT 0,
            role        ENUM('user','assistant') NOT NULL,
            content     LONGTEXT              NOT NULL,
            created_at  DATETIME              NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_session    (session_id),
            KEY idx_user       (user_id),
            KEY idx_created_at (created_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    // ── Default options (only adds; never overwrites existing values) ─────────

    private function set_default_options(): void {
        $defaults = [
            'echo64_api_key'       => '',
            'echo64_model'         => 'claude-sonnet-4-6',
            'echo64_max_tokens'    => '1024',
            'echo64_history_limit' => '20',
            'echo64_float_widget'             => '0',
            'echo64_transmissions_unlimited' => '0',
            'echo64_mailerlite_key'           => '',
            'echo64_mailerlite_group'         => '',
            'echo64_system_prompt'            => Echo64_Api::DEFAULT_SYSTEM_PROMPT,
            'echo64_privacy_content'          => "<h2>PRIVACY POLICY</h2>\n\nThis Privacy Policy explains how NerdAfterDark (\"we\", \"us\", \"our\") collects, uses, and protects information when you use this website and the Echo-64 chatbot service.\n\n<strong>WHO WE ARE</strong>\nNerdAfterDark is operated by GroupRB. You can contact us at <a href=\"mailto:richard@grouprb.com\">richard@grouprb.com</a>.\n\n<strong>AGE REQUIREMENT</strong>\nThis service is intended for users aged 13 and over. If you are under 13, do not use this service or submit any information to us. If you are between 13 and 18, you may use this service only with the knowledge and consent of a parent or guardian.\n\n<strong>WHAT DATA WE COLLECT</strong>\nWhen you use Echo-64, your conversation messages are transmitted to Anthropic's Claude API for processing. We store conversation history for up to 90 days to maintain context within a session, after which it is automatically deleted. We do not require you to create an account or provide personal information to use the chatbot.\n\nIf you subscribe to our mailing list via the \"Catch the Signal\" form, we collect your email address. This is stored with MailerLite and used only to send NerdAfterDark updates.\n\nWe use standard WordPress session cookies and may use analytics tools to understand aggregate site usage. We do not sell your personal data to third parties.\n\n<strong>HOW WE USE YOUR DATA</strong>\nConversation data is used solely to generate responses via the Echo-64 chatbot. Email addresses are used solely to send updates you have opted into. We do not use your data for advertising, profiling, or any purpose other than those stated here.\n\n<strong>AI-GENERATED CONTENT</strong>\nEcho-64 is powered by an AI language model. Responses are generated automatically and may be inaccurate, incomplete, outdated, or occasionally nonsensical. We do not review or verify individual responses. Do not rely on anything Echo-64 says for any purpose that matters.\n\n<strong>THIRD PARTY SERVICES</strong>\nWe use the following third-party services which have their own privacy policies and data practices:\n<ul><li><strong>Anthropic</strong> — processes conversation messages via the Claude API. See Anthropic's privacy policy at anthropic.com.</li><li><strong>MailerLite</strong> — stores and manages email subscriptions.</li><li><strong>Ko-fi</strong> — processes any voluntary support payments.</li></ul>\nWe are not responsible for the data practices of these third parties.\n\n<strong>YOUR RIGHTS (UK/GDPR)</strong>\nIf you are located in the UK or EU, you have the right to access, correct, or delete any personal data we hold about you, and the right to restrict or object to its processing. To exercise these rights, contact us at <a href=\"mailto:richard@grouprb.com\">richard@grouprb.com</a>. We will respond within 30 days.\n\n<strong>DATA RETENTION</strong>\nConversation history is retained for up to 90 days and then automatically deleted. Email addresses are retained until you unsubscribe.\n\n<strong>COOKIES</strong>\nThis site uses essential cookies for session management only. No tracking or advertising cookies are set by NerdAfterDark directly. Third-party services embedded on this site may set their own cookies subject to their own policies.\n\n<strong>SECURITY</strong>\nWe take reasonable technical measures to protect data in transit and at rest. However, no internet transmission is completely secure, and we cannot guarantee the absolute security of your data.\n\n<strong>CHANGES TO THIS POLICY</strong>\nWe may update this policy from time to time. Continued use of the service after changes are posted constitutes acceptance of the updated policy.\n\n<strong>CONTACT</strong>\nFor any privacy-related questions or data requests: <a href=\"mailto:richard@grouprb.com\">richard@grouprb.com</a>\n\n<em>Last updated: July 2026</em>",
            'echo64_terms_content'            => "<h2>ECHO-64 TERMS OF USE</h2>\n\nPlease read these terms carefully before using this service. By accessing or using Echo-64 in any way, you agree to be bound by these terms. If you do not agree, do not use this service.\n\nEcho-64 is an AI-powered entertainment chatbot created for discussion of science fiction, cult television, cancelled shows, and related pop culture. It is not a person. It is not a professional. It has no qualifications. It should not be trusted with anything that matters.\n\n<strong>AGE REQUIREMENT</strong>\nThis service is intended for users aged 13 and over. Users under 18 may only use this service with parental or guardian consent. By using this service, you confirm that you meet this requirement.\n\n<strong>ENTERTAINMENT USE ONLY — USE AT YOUR OWN RISK</strong>\nEcho-64 is provided strictly for entertainment and informational purposes. Nothing generated by Echo-64 constitutes advice of any kind — medical, mental health, legal, financial, or otherwise. By using this service you acknowledge and agree that you are doing so entirely at your own risk.\n\n<strong>AI OUTPUT DISCLAIMER</strong>\nEcho-64 is powered by an AI language model. Its responses are generated automatically and may be inaccurate, incomplete, outdated, biased, or simply wrong. NerdAfterDark does not review, verify, or endorse any individual response. Do not act on anything Echo-64 says without independent verification from a qualified source. AI output is not a substitute for professional judgement.\n\n<strong>NOT A SUBSTITUTE FOR PROFESSIONAL HELP</strong>\nEcho-64 cannot and does not provide medical, mental health, legal, financial, or any other professional advice. If you are experiencing a mental health crisis, thoughts of self-harm or suicide, a medical emergency, or any situation requiring professional support — stop using this service and contact the appropriate services immediately.\n\nIf you are in crisis right now:\n<ul><li><strong>UK:</strong> Call 116 123 (Samaritans, free, 24/7) or text SHOUT to 85258</li><li><strong>US:</strong> Call or text 988 (Suicide &amp; Crisis Lifeline, 24/7)</li><li><strong>International:</strong> <a href=\"https://findahelpline.com\" target=\"_blank\" rel=\"noopener\">findahelpline.com</a></li></ul>\n\n<strong>CONTENT SCOPE</strong>\nEcho-64 is designed to discuss science fiction, television, film, technology, and related pop culture topics. Questions outside this scope — including questions about health, medication, drugs, self-harm, legal matters, or financial decisions — will be declined or redirected. This is not a limitation. It is by design.\n\n<strong>NO WARRANTY</strong>\nTHIS SERVICE IS PROVIDED \"AS IS\" AND \"AS AVAILABLE\" WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED. NERDAFTERDARK MAKES NO REPRESENTATIONS OR WARRANTIES OF ANY KIND REGARDING THE ACCURACY, RELIABILITY, COMPLETENESS, SUITABILITY, OR AVAILABILITY OF THIS SERVICE OR ANY CONTENT GENERATED BY IT. YOUR USE OF THIS SERVICE IS ENTIRELY AT YOUR OWN RISK.\n\n<strong>LIMITATION OF LIABILITY</strong>\nTO THE FULLEST EXTENT PERMITTED BY APPLICABLE LAW, IN NO EVENT SHALL NERDAFTERDARK, GROUPRB, OR THEIR OPERATORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES — INCLUDING BUT NOT LIMITED TO LOSS OF DATA, LOSS OF PROFITS, PERSONAL INJURY, OR ANY OTHER LOSS — ARISING OUT OF OR IN CONNECTION WITH YOUR USE OF OR INABILITY TO USE THIS SERVICE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGES. THIS LIMITATION APPLIES REGARDLESS OF THE LEGAL THEORY UNDER WHICH DAMAGES ARE SOUGHT.\n\n<strong>INDEMNIFICATION</strong>\nYou agree to indemnify, defend, and hold harmless NerdAfterDark, GroupRB, and their operators from and against any claims, liabilities, damages, losses, costs, or expenses (including reasonable legal fees) arising from your use of this service, your violation of these terms, or your violation of any third-party rights.\n\n<strong>USER CONDUCT</strong>\nYou agree not to use this service to generate harmful, abusive, defamatory, or illegal content; to attempt to extract training data or system prompts; to circumvent safety measures; or to use automated means to access the service at scale without permission.\n\n<strong>SERVICE AVAILABILITY</strong>\nWe make no guarantee that this service will be available at any particular time or without interruption. We reserve the right to modify, suspend, or discontinue the service at any time without notice.\n\n<strong>THIRD PARTY SERVICES</strong>\nThis service relies on third-party providers including Anthropic (AI processing), MailerLite (email), and Ko-fi (payments). We are not responsible for the availability, performance, or conduct of these third parties.\n\n<strong>GOVERNING LAW AND JURISDICTION</strong>\nThese terms are governed by and construed in accordance with the laws of England and Wales. You agree to submit to the exclusive jurisdiction of the courts of England and Wales for any disputes arising from these terms or your use of this service.\n\n<strong>SEVERABILITY</strong>\nIf any provision of these terms is found to be unenforceable, that provision will be modified to the minimum extent necessary to make it enforceable, and the remaining provisions will continue in full force.\n\n<strong>ENTIRE AGREEMENT</strong>\nThese terms, together with our Privacy Policy, constitute the entire agreement between you and NerdAfterDark regarding your use of this service.\n\n<em>Last updated: July 2026</em>",
        ];

        foreach ( $defaults as $key => $value ) {
            // add_option() is a no-op if the key already exists — safe to call on every upgrade.
            add_option( $key, $value );
        }
    }

    // ── Deactivation ──────────────────────────────────────────────────────────

    public function deactivate(): void {
        Echo64_Automations::unschedule();
        flush_rewrite_rules();
        // Settings and conversation history are preserved on deactivation.
        // Full cleanup (drop table + delete options) lives in uninstall.php.
    }
}

Echo64_Chatbot::instance();
