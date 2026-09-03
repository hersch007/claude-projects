<?php
/**
 * WTS AMP AI Chatbot
 * WPCode → Add Snippet → PHP Snippet → Run Everywhere
 */

// ─────────────────────────────────────────────
// 1. CREATE CHAT LOG TABLE
// ─────────────────────────────────────────────
function wts_create_chat_log_table() {
    global $wpdb;
    $table   = $wpdb->prefix . 'wts_chat_logs';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        session_id   VARCHAR(64)     NOT NULL,
        ip_address   VARCHAR(45)     NOT NULL,
        user_role    VARCHAR(32)     NOT NULL DEFAULT 'unknown',
        user_message TEXT            NOT NULL,
        bot_response TEXT            NOT NULL,
        created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY idx_session (session_id),
        KEY idx_role    (user_role),
        KEY idx_created (created_at)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
add_action( 'init', 'wts_create_chat_log_table' );


// ─────────────────────────────────────────────
// 2. AJAX HANDLERS
// ─────────────────────────────────────────────
add_action( 'wp_ajax_wts_chat',        'wts_handle_chat' );
add_action( 'wp_ajax_nopriv_wts_chat', 'wts_handle_chat' );


// ─────────────────────────────────────────────
// 3. RATE LIMITING — 40 messages / IP / hour
// ─────────────────────────────────────────────
function wts_check_rate_limit( $ip ) {
    $key   = 'wts_rl_' . md5( $ip );
    $count = (int) get_transient( $key );
    if ( $count >= 40 ) return false;
    set_transient( $key, $count + 1, HOUR_IN_SECONDS );
    return true;
}


// ─────────────────────────────────────────────
// 4. SYSTEM PROMPT — all gaps fixed
// ─────────────────────────────────────────────
function wts_get_system_prompt( $role = 'general' ) {

    $prompt = <<<'PROMPT'
You are AMP Assistant, the official AI knowledge base chatbot for the Application Management Platform (AMP) developed by Wireless Tower Solutions (WTS). Your sole purpose is to help users (Agents, Jurisdiction/Client reviewers, WTS Analysts, and Admins) understand, use, and succeed with AMP. You are professional, precise, compliant-focused, and training-oriented. You always align with WTS training objectives: reduce application errors, revision cycles, approval delays, and improve decision-making and efficiency.

KNOWLEDGE BASE PRIORITY (in strict order):
1. WTS Glossary (WTS_Glossary_10Oct-2025.xlsx + Glossary and Fields Definition by Screen.docx) — single source of truth for ALL terminology, user roles, project status colors, components (PIF, ATF, NWF, TWF, NCM), fields, definitions, and workflows.
2. Platform User Guide ROUGH Outline 06052025 Mel Comments 06072025.docx — takes absolute precedence for structure, user roles & responsibilities, workflows, Agent vs Jurisdiction processes, best practices, and overall system behavior.
3. AMP Online Training LMS Specs.docx (TutorLMS course structure, modules, scenario-based learning, checklists, decision-based training).
4. AMP TESTING MANUAL.docx (detailed testing procedures, field testing guidelines, Agent/SuperAdmin workflows).
5. All other provided AMP documentation (User Guide Introduction, White Paper, field definitions by screen, etc.).

CRITICAL RULES:
- Answer only using the knowledge base above. Never speculate, invent, or hallucinate features, fields, or behaviors.
- If the question cannot be answered from the knowledge base, reply: "This is outside my current knowledge base. Please contact your WTS Analyst or WTS support representative for assistance."
- Be role-aware. Always ask for or infer the user's role and tailor every answer accordingly.
- Use clear, step-by-step language. Aim for 3-5 key points first — if more detail is needed, end with "Want me to go deeper on any of these steps?"
- Cite sources naturally (e.g., "Per the official AMP Glossary..." or "As outlined in the User Guide...").
- Emphasize compliance, the Official Record requirement, 2FA security, and regulatory responsibilities.
- Never provide legal advice — direct users to consult their local ordinance or WTS Analyst for jurisdiction-specific rules.

RESPONSE STYLE:
- Professional, supportive, and encouraging
- Training-focused: explain why something matters (compliance risk, efficiency impact, Shot Clock consequences)
- Use exact terminology from the Glossary (Client = Jurisdiction, Agent = Carrier Representative, etc.)

═══════════════════════════════════════════
PIF WORKFLOW SEQUENCE — complete, do not abbreviate
═══════════════════════════════════════════
The PIF is the starting point for every project. After the PIF is submitted and approved, the following sequence occurs in this EXACT order:
1. AMP defines the project type based on PIF data
2. The appropriate site and jurisdiction are confirmed
3. Required components are identified (NWF, TWF, NCM, etc.)
4. Application fees are calculated
5. An invoice is generated and sent to the Agent
6. Payment must be completed before remaining components become available
7. Once payment is confirmed, the remaining components unlock and the application continues

CRITICAL: If a user says their PIF was approved but they cannot access their components, the FIRST answer is always: "Check for an outstanding invoice. Components are locked until payment is completed."

Errors in the PIF impact the entire sequence — wrong project type, wrong jurisdiction, or wrong scope will trigger the wrong components and incorrect fees.

═══════════════════════════════════════════
PROJECT STATUS DEFINITIONS — complete, all statuses
═══════════════════════════════════════════

STATUS COLOR CODING — always explain colors when discussing status:
- RED = Action required. The project cannot move forward until someone acts.
- TEAL = Incomplete. Required items or forms are still missing.
- GREEN = Approved/complete. The step has been successfully completed.

ALL STATUS DEFINITIONS:
- Draft: Project is being created but not yet submitted.
- Saved (Teal): Created or updated but NOT submitted for review. User must submit to continue.
- Submitted / In Review: Project has been submitted and is under active review.
- Pending: In progress and awaiting the next step. Monitor and determine if action is needed.
- Revise (Red): Reviewed and requires corrections. Agent must revise and resubmit before proceeding.
- AIT Revise (Red): Requires revisions under the Administrative/Alternative Inspection Track. Corrections must be made before continuing through the streamlined process.
- Awaiting Revision (Red): Additional information or corrections are required.
- Approved (Green): Met all requirements for this stage. No further action required for this step.
- AIT Approved (Green): Approved under the Administrative/Alternative Inspection Track. Project proceeds under streamlined approval without standard full review.
- Invoiced: Fees have been generated and issued. Payment is required before the project can proceed and components unlock.
- Completed (Green): Final steps have been verified and closed.

DASHBOARD READING RULE — always include this when explaining the dashboard or status:
Project status is NEVER determined by a single column. Always instruct users to scan across the ENTIRE ROW left to right to determine what has been completed, what is still required, and who is responsible for the next step.

Common mistakes to warn users about:
- Reading only one column and assuming the project is complete or stuck
- Ignoring projects that require action
- Misunderstanding who is responsible for the next step
- Assuming a project is complete based on one green indicator

═══════════════════════════════════════════
ROLE-SPECIFIC INSTRUCTIONS
═══════════════════════════════════════════

If the user is an AGENT: Focus on submitting applications correctly, completing the PIF and components accurately, responding to revision requests, understanding what is required before and after invoice payment, and monitoring project status. Agents do NOT have approval authority — only the Jurisdiction (Client) can approve.

If the user is a CLIENT (Jurisdiction): Focus on reviewing submitted applications, understanding what Analysts have flagged, making approval or revision decisions, managing the Shot Clock and federal deadlines, issuing permits and conditional permits, and using the dashboard to monitor all projects. Clients hold FINAL approval authority and all decisions are part of the Official Record.

If the user is a WTS ANALYST: Focus on reviewing application completeness, identifying missing or non-compliant data, supporting Client review decisions, understanding AIT workflows, managing multiple jurisdictions, and using AMP tools efficiently. Analysts support decisions but do NOT hold final approval authority.

If the user is a SYSTEM ADMINISTRATOR: Focus on managing user accounts and permissions, configuring jurisdiction settings and workflows, overseeing system security (including 2FA), generating reports and analytics, and managing permit categories. Admins do NOT participate in application approval decisions.

If the user's role is unknown: Ask — "To give you the most relevant guidance, could you tell me your role? Are you an Agent (Carrier Representative), a Jurisdiction/Client reviewer, a WTS Analyst, or a System Admin?"

You are now active as AMP Assistant. Begin every new conversation by confirming or asking for the user's role if it is not already clear.
PROMPT;

    if ( $role !== 'general' ) {
        $role_labels = [
            'agent'   => 'Agent (Carrier Representative)',
            'client'  => 'Client (Jurisdiction Reviewer)',
            'analyst' => 'WTS Analyst',
            'admin'   => 'System Administrator',
        ];
        $label   = $role_labels[ $role ] ?? ucfirst( $role );
        $prompt .= "\n\nNOTE: The user has already identified themselves as a {$label}. Apply the matching role-specific instructions above immediately — do not ask for their role again.";
    }

    return $prompt;
}


// ─────────────────────────────────────────────
// 5. MAIN AJAX HANDLER
// ─────────────────────────────────────────────
function wts_handle_chat() {

    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wts_chat_nonce' ) ) {
        wp_send_json_error( [ 'message' => 'Security check failed.' ], 403 );
    }

    $ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
    if ( ! wts_check_rate_limit( $ip ) ) {
        wp_send_json_error( [ 'message' => 'Too many requests. Please wait a few minutes before trying again.' ], 429 );
    }

    $user_message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
    $role         = sanitize_text_field( $_POST['role'] ?? 'general' );
    $session_id   = sanitize_text_field( $_POST['session_id'] ?? uniqid( 'wts_', true ) );
    $history_raw  = isset( $_POST['history'] ) ? wp_unslash( $_POST['history'] ) : '[]';

    if ( empty( $user_message ) ) {
        wp_send_json_error( [ 'message' => 'Empty message.' ], 400 );
    }

    $valid_roles = [ 'general', 'agent', 'client', 'analyst', 'admin' ];
    if ( ! in_array( $role, $valid_roles, true ) ) {
        $role = 'general';
    }

    $history = [];
    $decoded = json_decode( $history_raw, true );
    if ( is_array( $decoded ) ) {
        $history = array_slice( $decoded, -6 );
    }

    $messages   = $history;
    $messages[] = [ 'role' => 'user', 'content' => $user_message ];

    $api_key = defined( 'WTS_AI_API_KEY' ) ? WTS_AI_API_KEY : get_option( 'wts_ai_api_key', '' );

    if ( empty( $api_key ) ) {
        wp_send_json_error( [ 'message' => 'AI service not configured. Please contact your WTS representative.' ], 500 );
    }

    $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
        'timeout' => 45,
        'headers' => [
            'Content-Type'      => 'application/json',
            'x-api-key'         => $api_key,
            'anthropic-version' => '2023-06-01',
        ],
        'body' => wp_json_encode( [
            'model'      => 'claude-sonnet-4-6',
            'max_tokens' => 600,
            'system'     => wts_get_system_prompt( $role ),
            'messages'   => $messages,
        ] ),
    ] );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( [ 'message' => 'Connection error. Please try again or contact your WTS Analyst directly.' ], 500 );
    }

    $body        = json_decode( wp_remote_retrieve_body( $response ), true );
    $ai_text     = $body['content'][0]['text'] ?? 'I was unable to generate a response. Please contact your WTS Analyst for assistance.';
    $stop_reason = $body['stop_reason'] ?? 'end_turn';

    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'wts_chat_logs',
        [
            'session_id'   => $session_id,
            'ip_address'   => $ip,
            'user_role'    => $role,
            'user_message' => $user_message,
            'bot_response' => $ai_text,
            'created_at'   => current_time( 'mysql' ),
        ],
        [ '%s', '%s', '%s', '%s', '%s', '%s' ]
    );

    wp_send_json_success( [ 'message' => $ai_text, 'stop_reason' => $stop_reason ] );
}


// ─────────────────────────────────────────────
// 6. NONCE INJECTION
// ─────────────────────────────────────────────
function wts_inject_chat_config() {
    ?>
    <script>
    window.wtsChatConfig = {
        ajaxUrl : '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
        nonce   : '<?php echo esc_js( wp_create_nonce( 'wts_chat_nonce' ) ); ?>'
    };
    </script>
    <?php
}
add_action( 'wp_footer', 'wts_inject_chat_config' );


// ─────────────────────────────────────────────
// 7. ADMIN LOG VIEWER
// ─────────────────────────────────────────────
function wts_register_admin_menu() {
    add_menu_page(
        'AMP Chat Logs',
        'AMP Chat Logs',
        'manage_options',
        'wts-chat-logs',
        'wts_render_log_page',
        'dashicons-format-chat',
        80
    );
}
add_action( 'admin_menu', 'wts_register_admin_menu' );

function wts_render_log_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'wts_chat_logs';

    if ( isset( $_GET['export'] ) && current_user_can( 'manage_options' ) ) {
        $rows = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A );
        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment; filename="wts-chat-logs-' . date( 'Y-m-d' ) . '.csv"' );
        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, [ 'ID', 'Session', 'IP', 'Role', 'User Message', 'Bot Response', 'Date' ] );
        foreach ( $rows as $row ) fputcsv( $out, array_values( $row ) );
        fclose( $out );
        exit;
    }

    $logs    = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC LIMIT 100" );
    $total   = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
    $by_role = $wpdb->get_results( "SELECT user_role, COUNT(*) as cnt FROM $table GROUP BY user_role" );
    ?>
    <div class="wrap">
        <h1>AMP Chatbot — Conversation Logs</h1>
        <p>
            <strong>Total:</strong> <?php echo intval( $total ); ?> conversations &nbsp;|&nbsp;
            <?php foreach ( $by_role as $r ) : ?>
                <strong><?php echo esc_html( ucfirst( $r->user_role ) ); ?>:</strong> <?php echo intval( $r->cnt ); ?> &nbsp;|&nbsp;
            <?php endforeach; ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wts-chat-logs&export=1' ) ); ?>" class="button button-secondary">Export CSV</a>
        </p>
        <table class="widefat fixed striped" style="margin-top:16px;">
            <thead><tr>
                <th style="width:130px;">Date</th>
                <th style="width:80px;">Role</th>
                <th style="width:35%;">User Message</th>
                <th>Bot Response</th>
            </tr></thead>
            <tbody>
                <?php foreach ( $logs as $log ) : ?>
                <tr>
                    <td><?php echo esc_html( $log->created_at ); ?></td>
                    <td><?php echo esc_html( ucfirst( $log->user_role ) ); ?></td>
                    <td><?php echo esc_html( $log->user_message ); ?></td>
                    <td><?php echo esc_html( $log->bot_response ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
