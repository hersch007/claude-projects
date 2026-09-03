<?php
/*
 * Plugin Name: Start Performance — AI
 * Description: AI assistant addon for the Start Performance Platform
 * Version:     1.3.4
 * Author:      Richard Brashear / Start Performance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SP_AI_VERSION',    '1.3.4' );
define( 'SP_AI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// ── Dependency check ──────────────────────────────────────────────────────────

add_action( 'plugins_loaded', 'sp_ai_boot', 20 );

function sp_ai_boot() {
    if ( ! function_exists( 'sp_register_view' ) ) {
        add_action( 'admin_notices', 'sp_ai_dependency_notice' );
        return;
    }
    sp_ai_register();
}

function sp_ai_dependency_notice() {
    echo '<div class="notice notice-error"><p><strong>Start Performance — AI</strong> requires the Start Performance core plugin to be installed and active.</p></div>';
}

// ── Registration ──────────────────────────────────────────────────────────────

function sp_ai_register() {
    sp_register_addon( 'sp-ai', array(
        'name'         => 'AI Assistant',
        'version'      => SP_AI_VERSION,
        'description'  => 'AI-powered insights, summaries, and automation for your contacts, leads, and tickets.',
        'settings_url' => home_url( '/sp-app/?view=settings#section-ai' ),
        'icon'         => '<path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>',
        'plugin_file'  => plugin_basename( __FILE__ ),
    ) );

    // No standalone "AI Assistant" nav item or view. AI is a cross-cutting capability:
    // it configures under Settings → AI and surfaces in-context on the screens it
    // enhances (contacts, tickets) plus Pipeline Insights in Intelligence Core.
    // Since the addon is active, drop the core "ai-core" nav section so it doesn't
    // render as an empty header or a misleading locked upsell teaser. (On installs
    // without this addon the teaser stays — that's the intended marketing prompt.)
    add_filter( 'sp_nav_items',            'sp_ai_hide_nav_section', 50 );
    add_action( 'sp_post_handler_ai_settings', 'sp_ai_save_settings' );
    add_filter( 'sp_settings_anchor_tabs', 'sp_ai_settings_tab' );
    add_action( 'sp_settings_sections',    'sp_ai_settings_section' );
    add_action( 'sp_contact_edit_after',           'sp_ai_contact_summary_ui' );
    add_action( 'wp_ajax_sp_ai_contact_summary',   'sp_ai_ajax_contact_summary' );
    // Pipeline Insights renders in Intelligence Core, under the AI Smart Summary.
    add_action( 'sp_intel_after_ai_summary',       'sp_ai_pipeline_insights_ui' );
    add_action( 'wp_ajax_sp_ai_pipeline_insights', 'sp_ai_ajax_pipeline_insights' );
    add_action( 'sp_ticket_edit_after',              'sp_ai_ticket_triage_ui' );
    add_action( 'wp_ajax_sp_ai_ticket_triage',       'sp_ai_ajax_ticket_triage' );
    add_action( 'sp_tickets_after_list',             'sp_ai_ticket_analysis_ui' );
    add_action( 'wp_ajax_sp_ai_ticket_analysis',     'sp_ai_ajax_ticket_analysis' );
}

// ── Activation ────────────────────────────────────────────────────────────────

register_activation_hook( __FILE__, 'sp_ai_activate' );

function sp_ai_activate() {
    // No custom tables needed yet — settings stored in wp_options
}

// ── Nav filter ────────────────────────────────────────────────────────────────

// Remove the "AI Assistant" core-slot section from the sidebar. AI is not a
// destination — no header, no locked upsell teaser — while this addon is active.
function sp_ai_hide_nav_section( $items ) {
    $out = array();
    foreach ( $items as $item ) {
        if ( ! empty( $item['section'] ) && ! empty( $item['section_id'] ) && $item['section_id'] === 'ai-core' ) {
            continue;
        }
        $out[] = $item;
    }
    return $out;
}

// ── Settings handler ──────────────────────────────────────────────────────────

function sp_ai_save_settings( $id ) {
    if ( ! function_exists( 'sp_is_super_admin' ) || ! sp_is_super_admin() ) return;
    $api_key = sanitize_text_field( isset( $_POST['sp_ai_api_key'] ) ? $_POST['sp_ai_api_key'] : '' );
    $model   = sanitize_key(        isset( $_POST['sp_ai_model'] )   ? $_POST['sp_ai_model']   : 'gpt-4o-mini' );
    update_option( 'sp_ai_api_key', $api_key );
    update_option( 'sp_ai_model',   $model );
    wp_redirect( home_url( '/sp-app/?view=settings&saved=1#section-ai' ) ); exit;
}

// ── Settings integration ──────────────────────────────────────────────────────

function sp_ai_settings_tab( $tabs ) {
    if ( function_exists( 'sp_is_super_admin' ) && sp_is_super_admin() ) {
        $tabs[] = array( 'id' => 'section-ai', 'label' => 'AI Integration' );
    }
    return $tabs;
}

function sp_ai_settings_section() {
    if ( ! function_exists( 'sp_is_super_admin' ) || ! sp_is_super_admin() ) return;
    $api_key    = sp_ai_get_api_key();
    $model      = sp_ai_get_model();
    $masked_key = $api_key ? substr( $api_key, 0, 8 ) . str_repeat( '•', 24 ) : '';

    $models = array(
        'gpt-4o-mini'               => 'GPT-4o Mini (fast, economical)',
        'gpt-4o'                    => 'GPT-4o (powerful, higher cost)',
        'claude-sonnet-4-6'         => 'Claude Sonnet 4.6 (Anthropic)',
        'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 (Anthropic, fast)',
    );
    ?>
    <div id="section-ai" class="sp-card sp-form-card sp-settings-section" style="margin-top:16px">
        <h2 class="sp-section-heading">AI Assistant — Chat &amp; Marketing</h2>
        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="ai_settings">
            <input type="hidden" name="sp_id" value="0">

            <div class="sp-field" style="max-width:400px">
                <label>AI Assistant API Key</label>
                <input type="text" name="sp_ai_api_key" value="<?php echo esc_attr( $api_key ); ?>" placeholder="sk-... or sk-ant-..." autocomplete="off" style="font-family:monospace;font-size:13px">
                <span class="sp-hint">Powers the <strong>AI Assistant / marketing pages</strong>. OpenAI (<code>sk-…</code>) or Anthropic (<code>sk-ant-…</code>) per the model below. Also used as a fallback for the KPI Dashboard AI Summary when its own Anthropic key is blank (Anthropic keys only).<?php if ( $masked_key ) : ?><br>Currently set: <?php echo esc_html( $masked_key ); ?><?php endif; ?></span>
            </div>

            <div class="sp-field" style="max-width:320px;margin-top:16px">
                <label>Model</label>
                <select name="sp_ai_model">
                    <?php foreach ( $models as $val => $label ) : ?>
                        <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $model, $val ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="sp-form-actions">
                <button type="submit" class="sp-btn sp-btn-primary">Save AI Settings</button>
            </div>
        </form>
    </div>
    <?php
}

// ── Helper: get config ────────────────────────────────────────────────────────

function sp_ai_get_api_key() {
    return get_option( 'sp_ai_api_key', '' );
}

function sp_ai_get_model() {
    return get_option( 'sp_ai_model', 'gpt-4o-mini' );
}

function sp_ai_is_configured() {
    return sp_ai_get_api_key() !== '';
}

// Authorized to use the AI features: a registered team member OR a super admin.
// Super admins aren't team-member records, so the plain team-member check alone
// rejected them with "Not authorized" (the AI Business Summary already allows
// super admins — this brings these handlers in line).
function sp_ai_authed() {
    if ( function_exists( 'sp_get_current_team_member' ) && sp_get_current_team_member() ) return true;
    return function_exists( 'sp_is_super_admin' ) && sp_is_super_admin();
}

// ── Markdown renderer (server-side) ──────────────────────────────────────────

function sp_ai_render_markdown( $text ) {
    $allowed = array( 'h2' => array(), 'h3' => array(), 'p' => array(), 'ul' => array(), 'li' => array(), 'strong' => array(), 'em' => array() );
    $lines   = explode( "\n", $text );
    $html    = '';
    $in_ul   = false;

    $icon_h2 = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15" style="flex-shrink:0;color:#6366f1;display:inline-block;vertical-align:middle;margin-right:6px"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>';
    $icon_h3 = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" style="flex-shrink:0;color:#94a3b8;display:inline-block;vertical-align:middle;margin-right:5px"><path d="M9 5l7 7-7 7"/></svg>';

    foreach ( $lines as $line ) {
        $t = trim( $line );
        if ( preg_match( '/^# (.+)/', $t, $m ) ) {
            if ( $in_ul ) { $html .= '</ul>'; $in_ul = false; }
            $html .= '<h2>' . $icon_h2 . esc_html( $m[1] ) . '</h2>';
        } elseif ( preg_match( '/^## (.+)/', $t, $m ) ) {
            if ( $in_ul ) { $html .= '</ul>'; $in_ul = false; }
            $html .= '<h3>' . $icon_h3 . esc_html( $m[1] ) . '</h3>';
        } elseif ( preg_match( '/^[-*] (.+)/', $t, $m ) ) {
            if ( ! $in_ul ) { $html .= '<ul>'; $in_ul = true; }
            $html .= '<li>' . sp_ai_inline_md( $m[1] ) . '</li>';
        } elseif ( $t === '' ) {
            if ( $in_ul ) { $html .= '</ul>'; $in_ul = false; }
        } else {
            if ( $in_ul ) { $html .= '</ul>'; $in_ul = false; }
            $html .= '<p>' . sp_ai_inline_md( $t ) . '</p>';
        }
    }
    if ( $in_ul ) $html .= '</ul>';
    return wp_kses( $html, array_merge( $allowed, array( 'svg' => array( 'viewBox' => array(), 'fill' => array(), 'stroke' => array(), 'stroke-width' => array(), 'stroke-linecap' => array(), 'stroke-linejoin' => array(), 'width' => array(), 'height' => array(), 'style' => array() ), 'path' => array( 'd' => array() ) ) ) );
}

function sp_ai_inline_md( $text ) {
    $text = esc_html( $text );
    $text = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );
    $text = preg_replace( '/\*(.+?)\*/',     '<em>$1</em>',         $text );
    return $text;
}

// ── Contact Summary UI ────────────────────────────────────────────────────────

function sp_ai_contact_summary_ui( $contact_id ) {
    if ( ! sp_ai_is_configured() ) return;
    $nonce  = wp_create_nonce( 'sp_ai_contact_summary' );
    $cached = get_option( 'sp_ai_contact_summary_' . $contact_id );
    $text   = $cached ? $cached['text'] : '';
    $gen_at = $cached ? $cached['generated_at'] : 0;
    $gen_label = $gen_at ? 'Generated ' . human_time_diff( $gen_at ) . ' ago' : '';
    $action_html = '<span id="sp-ai-summary-meta" style="margin-left:auto;font-size:11px;color:#94a3b8">' . esc_html( $gen_label ) . '</span>';
    sp_ai_card_start( 'AI Contact Summary', $action_html );
    $well = sp_ai_content_well_style();
    ?>
        <?php if ( $text ) : ?>
        <div id="sp-ai-summary-output" class="sp-ai-md-out" style="<?php echo $well; ?>font-size:14px;line-height:1.7;color:#1e293b;margin-bottom:16px"><?php echo sp_ai_render_markdown( $text ); ?></div>
        <?php else : ?>
        <div id="sp-ai-summary-output" style="display:none;<?php echo $well; ?>font-size:14px;line-height:1.7;color:#1e293b;margin-bottom:16px;white-space:pre-wrap"></div>
        <?php endif; ?>
        <div style="display:flex;align-items:center;gap:10px">
            <button type="button" id="sp-ai-summary-btn" style="background:rgba(255,255,255,.08);border:none;border-radius:6px;padding:6px 14px;cursor:pointer;color:#e2e8f0;font-size:.8rem;"
                data-contact="<?php echo esc_attr( $contact_id ); ?>"
                data-nonce="<?php echo esc_attr( $nonce ); ?>"
                data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
                <?php echo $text ? 'Regenerate' : 'Generate Summary'; ?>
            </button>
            <span id="sp-ai-summary-status" style="font-size:12px;color:#94a3b8;display:none">Generating…</span>
        </div>
    <?php sp_ai_card_end(); ?>
    <script>
    (function(){
        var btn    = document.getElementById('sp-ai-summary-btn');
        var output = document.getElementById('sp-ai-summary-output');
        var status = document.getElementById('sp-ai-summary-status');
        var meta   = document.getElementById('sp-ai-summary-meta');
        if ( ! btn ) return;
        btn.addEventListener('click', function(){
            btn.disabled = true;
            status.style.display = 'inline';
            var fd = new FormData();
            fd.append('action',     'sp_ai_contact_summary');
            fd.append('contact_id', btn.dataset.contact);
            fd.append('nonce',      btn.dataset.nonce);
            fetch(btn.dataset.ajax, { method:'POST', body:fd })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    status.style.display = 'none';
                    btn.disabled         = false;
                    btn.textContent      = 'Regenerate';
                    if ( data.success ) {
                        output.textContent   = data.data.text;
                        output.style.display = 'block';
                        meta.textContent     = 'Generated just now';
                    } else {
                        output.textContent   = 'Error: ' + (data.data || 'Unknown error');
                        output.style.display = 'block';
                    }
                })
                .catch(function(){
                    status.style.display = 'none';
                    btn.disabled         = false;
                    output.textContent   = 'Request failed. Check your connection.';
                    output.style.display = 'block';
                });
        });
    })();
    </script>
    <?php
}

// ── Contact Summary AJAX handler ──────────────────────────────────────────────

function sp_ai_ajax_contact_summary() {
    if ( ! check_ajax_referer( 'sp_ai_contact_summary', 'nonce', false ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }
    if ( ! sp_ai_authed() ) {
        wp_send_json_error( 'Not authorized' );
    }

    $contact_id = (int) ( isset( $_POST['contact_id'] ) ? $_POST['contact_id'] : 0 );
    if ( ! $contact_id ) wp_send_json_error( 'No contact ID' );

    global $wpdb;

    $contact = $wpdb->get_row( $wpdb->prepare(
        "SELECT c.*, co.name AS company_name, co.industry
         FROM {$wpdb->prefix}sp_contacts c
         LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id = c.company_id
         WHERE c.id = %d", $contact_id
    ) );
    if ( ! $contact ) wp_send_json_error( 'Contact not found' );

    $leads = $wpdb->get_results( $wpdb->prepare(
        "SELECT status, source, score, notes, created_at
         FROM {$wpdb->prefix}sp_leads WHERE contact_id = %d ORDER BY created_at DESC", $contact_id
    ) );

    $tickets = array();
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_tickets'" ) ) {
        $tickets = $wpdb->get_results( $wpdb->prepare(
            "SELECT title, status, priority, created_at
             FROM {$wpdb->prefix}sp_tickets WHERE contact_id = %d ORDER BY created_at DESC LIMIT 10", $contact_id
        ) );
    }

    // Build prompt context
    $name = trim( $contact->first_name . ' ' . $contact->last_name );
    $lines = array();
    $lines[] = "Contact: $name";
    if ( $contact->email )        $lines[] = "Email: {$contact->email}";
    if ( $contact->phone )        $lines[] = "Phone: {$contact->phone}";
    if ( $contact->company_name ) $lines[] = "Company: {$contact->company_name}" . ( $contact->industry ? " ({$contact->industry})" : '' );
    if ( $contact->source )       $lines[] = "Source: {$contact->source}";
    $lines[] = "Status: {$contact->status}";
    $lines[] = "Added: " . date( 'M j, Y', strtotime( $contact->created_at ) );
    if ( $contact->notes )        $lines[] = "Notes: {$contact->notes}";

    if ( $leads ) {
        $lines[] = '';
        $lines[] = 'Leads (' . count( $leads ) . '):';
        foreach ( $leads as $l ) {
            $line = '- ' . ucfirst( $l->status );
            if ( $l->source ) $line .= ", source: {$l->source}";
            if ( $l->score )  $line .= ", score: {$l->score}";
            if ( $l->notes )  $line .= ". Notes: {$l->notes}";
            $lines[] = $line;
        }
    }

    if ( $tickets ) {
        $lines[] = '';
        $lines[] = 'Support Tickets (' . count( $tickets ) . '):';
        foreach ( $tickets as $t ) {
            $lines[] = "- [{$t->priority}] {$t->title} ({$t->status})";
        }
    }

    $context = implode( "\n", $lines );

    $prompt = "You are a CRM assistant. Write a concise 2-3 paragraph summary of this contact for a sales or service rep about to reach out. Cover who they are, their history with the business, any open issues or opportunities, and a suggested next action. Be direct and practical — no fluff.\n\n$context";

    $summary = sp_ai_call_api( $prompt );

    if ( is_wp_error( $summary ) ) {
        wp_send_json_error( $summary->get_error_message() );
    }

    update_option( 'sp_ai_contact_summary_' . $contact_id, array(
        'text'         => $summary,
        'generated_at' => time(),
    ), false );

    wp_send_json_success( array( 'text' => $summary ) );
}

// ── Pipeline Insights UI ──────────────────────────────────────────────────────

function sp_ai_pipeline_insights_ui() {
    if ( ! sp_ai_is_configured() ) return;
    // Pipeline Insights analyzes the lead pipeline (sp_leads). Hide the card on
    // clients with no leads (e.g. quotes-only Fruth) — nothing to analyze there.
    global $wpdb;
    if ( (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sp_leads" ) < 1 ) return;
    $nonce  = wp_create_nonce( 'sp_ai_pipeline_insights' );
    $cached = get_option( 'sp_ai_pipeline_insights_cache' );
    $text   = $cached ? $cached['text'] : '';
    $gen_at = $cached ? $cached['generated_at'] : 0;
    $gen_label = $gen_at ? 'Generated ' . human_time_diff( $gen_at ) . ' ago' : '';
    $action_html = '<span id="sp-ai-pi-meta" style="margin-left:auto;font-size:11px;color:#94a3b8">' . esc_html( $gen_label ) . '</span>'
        . '<span id="sp-ai-pi-status" style="font-size:12px;color:#94a3b8;display:none">Analyzing…</span>'
        . '<button type="button" id="sp-ai-pi-btn" style="background:rgba(255,255,255,.08);border:none;border-radius:6px;padding:4px 10px;cursor:pointer;color:#e2e8f0;font-size:.75rem;"'
        . ' data-nonce="' . esc_attr( $nonce ) . '" data-ajax="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '">'
        . ( $text ? 'Refresh Insights' : 'Generate Insights' ) . '</button>';
    sp_ai_card_start( 'Pipeline Insights', $action_html );
    $well = sp_ai_content_well_style();
    ?>
        <?php if ( $text ) : ?>
        <div id="sp-ai-pi-output" class="sp-ai-md-out" style="<?php echo $well; ?>font-size:14px;line-height:1.75;color:#1e293b"><?php echo sp_ai_render_markdown( $text ); ?></div>
        <?php else : ?>
        <div id="sp-ai-pi-output" class="sp-ai-md-out" style="display:none;<?php echo $well; ?>font-size:14px;line-height:1.75;color:#1e293b"></div>
        <?php endif; ?>
    <?php sp_ai_card_end(); ?>
    <style>
    .sp-ai-md-out h2{font-size:16px;font-weight:700;color:#0f172a;margin:20px 0 4px;display:flex;align-items:center;gap:7px}
    .sp-ai-md-out h2:first-child{margin-top:0}
    .sp-ai-md-out h3{font-size:14px;font-weight:700;color:#1e293b;margin:14px 0 3px;display:flex;align-items:center;gap:6px}
    .sp-ai-md-out p{margin:0 0 10px;color:#374151}
    .sp-ai-md-out strong{font-weight:600;color:#0f172a}
    .sp-ai-md-out ul{margin:4px 0 10px 18px;padding:0}
    .sp-ai-md-out li{margin-bottom:3px}
    </style>
    <script>
    var iconH2 = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15" style="flex-shrink:0;color:#6366f1"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>';
    var iconH3 = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" style="flex-shrink:0;color:#94a3b8"><path d="M9 5l7 7-7 7"/></svg>';
    function spAiEsc(s){return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
    function spAiInline(t){return spAiEsc(t).replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>').replace(/\*(.+?)\*/g,'<em>$1</em>');}
    function spAiRenderMd(md){
        var lines=md.split('\n'),html='',inUl=false;
        lines.forEach(function(line){
            if(/^# /.test(line)){if(inUl){html+='</ul>';inUl=false;}html+='<h2>'+iconH2+spAiEsc(line.replace(/^# /,''))+'</h2>';}
            else if(/^## /.test(line)){if(inUl){html+='</ul>';inUl=false;}html+='<h3>'+iconH3+spAiEsc(line.replace(/^## /,''))+'</h3>';}
            else if(/^[-*] /.test(line)){if(!inUl){html+='<ul>';inUl=true;}html+='<li>'+spAiInline(line.replace(/^[-*] /,''))+'</li>';}
            else if(line.trim()===''){if(inUl){html+='</ul>';inUl=false;}}
            else{if(inUl){html+='</ul>';inUl=false;}html+='<p>'+spAiInline(line)+'</p>';}
        });
        if(inUl)html+='</ul>';
        return html;
    }
    (function(){
        var btn    = document.getElementById('sp-ai-pi-btn');
        var output = document.getElementById('sp-ai-pi-output');
        var status = document.getElementById('sp-ai-pi-status');
        var meta   = document.getElementById('sp-ai-pi-meta');
        if ( ! btn ) return;
        output.classList.add('sp-ai-md-out');
        btn.addEventListener('click', function(){
            btn.disabled = true;
            status.style.display = 'inline';
            var fd = new FormData();
            fd.append('action', 'sp_ai_pipeline_insights');
            fd.append('nonce',  btn.dataset.nonce);
            fetch(btn.dataset.ajax, { method:'POST', body:fd })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    status.style.display = 'none';
                    btn.disabled         = false;
                    btn.textContent      = 'Refresh Insights';
                    if ( data.success ) {
                        output.innerHTML     = spAiRenderMd(data.data.text);
                        output.style.display = 'block';
                        meta.textContent     = 'Generated just now';
                    } else {
                        output.textContent   = 'Error: ' + (data.data || 'Unknown error');
                        output.style.display = 'block';
                    }
                })
                .catch(function(){
                    status.style.display = 'none';
                    btn.disabled         = false;
                    output.textContent   = 'Request failed. Check your connection.';
                    output.style.display = 'block';
                });
        });
    })();
    </script>
    <?php
}

// ── Pipeline Insights AJAX handler ────────────────────────────────────────────

function sp_ai_ajax_pipeline_insights() {
    if ( ! check_ajax_referer( 'sp_ai_pipeline_insights', 'nonce', false ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }
    if ( ! sp_ai_authed() ) {
        wp_send_json_error( 'Not authorized' );
    }

    global $wpdb;

    // Lead counts by status
    $by_status = $wpdb->get_results(
        "SELECT status, COUNT(*) as count FROM {$wpdb->prefix}sp_leads GROUP BY status ORDER BY count DESC"
    );

    // Leads by source
    $by_source = $wpdb->get_results(
        "SELECT source, COUNT(*) as count FROM {$wpdb->prefix}sp_leads WHERE source != '' GROUP BY source ORDER BY count DESC LIMIT 8"
    );

    // Leads created in last 30 days
    $recent_count = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}sp_leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );

    // Leads with no activity (status still 'new') older than 14 days
    $stale_count = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}sp_leads WHERE status = 'new' AND created_at < DATE_SUB(NOW(), INTERVAL 14 DAY)"
    );

    // Average score
    $avg_score = (float) $wpdb->get_var(
        "SELECT AVG(score) FROM {$wpdb->prefix}sp_leads WHERE score > 0"
    );

    // Top scored leads
    $top_leads = $wpdb->get_results(
        "SELECT l.score, l.status, l.source, c.first_name, c.last_name
         FROM {$wpdb->prefix}sp_leads l
         LEFT JOIN {$wpdb->prefix}sp_contacts c ON c.id = l.contact_id
         WHERE l.score > 0
         ORDER BY l.score DESC LIMIT 5"
    );

    // Build context
    $lines = array();
    $lines[] = 'Pipeline snapshot as of ' . date( 'F j, Y' ) . ':';
    $lines[] = '';

    $lines[] = 'Leads by status:';
    foreach ( $by_status as $row ) {
        $lines[] = "  {$row->status}: {$row->count}";
    }

    if ( $by_source ) {
        $lines[] = '';
        $lines[] = 'Top lead sources:';
        foreach ( $by_source as $row ) {
            $lines[] = "  {$row->source}: {$row->count}";
        }
    }

    $lines[] = '';
    $lines[] = "New leads in last 30 days: $recent_count";
    $lines[] = "Stale new leads (14+ days untouched): $stale_count";

    if ( $avg_score ) {
        $lines[] = 'Average lead score: ' . round( $avg_score, 1 );
    }

    if ( $top_leads ) {
        $lines[] = '';
        $lines[] = 'Top scored leads:';
        foreach ( $top_leads as $l ) {
            $name = trim( $l->first_name . ' ' . $l->last_name ) ?: 'Unknown';
            $lines[] = "  {$name} — score {$l->score}, status: {$l->status}" . ( $l->source ? ", source: {$l->source}" : '' );
        }
    }

    $context = implode( "\n", $lines );

    $prompt = "You are a sales pipeline analyst. Based on the following pipeline data, write a concise 3-4 paragraph analysis covering: overall pipeline health, what's working (top sources, high scores), what needs attention (stale leads, bottlenecks), and 2-3 specific recommended actions for this week. Be direct and actionable — no generic advice.\n\n$context";

    $result = sp_ai_call_api( $prompt );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    update_option( 'sp_ai_pipeline_insights_cache', array(
        'text'         => $result,
        'generated_at' => time(),
    ), false );

    wp_send_json_success( array( 'text' => $result ) );
}

// ── Ticket Triage UI ─────────────────────────────────────────────────────────

function sp_ai_ticket_triage_ui( $ticket_id ) {
    if ( ! sp_ai_is_configured() ) return;
    $nonce  = wp_create_nonce( 'sp_ai_ticket_triage' );
    $cached = get_option( 'sp_ai_ticket_triage_' . $ticket_id );
    $text   = $cached ? $cached['text'] : '';
    $gen_at = $cached ? $cached['generated_at'] : 0;
    $gen_label = $gen_at ? 'Generated ' . human_time_diff( $gen_at ) . ' ago' : '';
    $action_html = '<span id="sp-ai-triage-meta" style="margin-left:auto;font-size:11px;color:#94a3b8">' . esc_html( $gen_label ) . '</span>';
    sp_ai_card_start( 'AI Ticket Triage', $action_html );
    $well = sp_ai_content_well_style();
    ?>
        <?php if ( $text ) : ?>
        <div id="sp-ai-triage-output" class="sp-ai-md-out" style="<?php echo $well; ?>font-size:14px;line-height:1.75;color:#1e293b;margin-bottom:16px"><?php echo sp_ai_render_markdown( $text ); ?></div>
        <?php else : ?>
        <div id="sp-ai-triage-output" class="sp-ai-md-out" style="display:none;<?php echo $well; ?>font-size:14px;line-height:1.75;color:#1e293b;margin-bottom:16px"></div>
        <?php endif; ?>
        <div style="display:flex;align-items:center;gap:10px">
            <button type="button" id="sp-ai-triage-btn" style="background:rgba(255,255,255,.08);border:none;border-radius:6px;padding:6px 14px;cursor:pointer;color:#e2e8f0;font-size:.8rem;"
                data-ticket="<?php echo esc_attr( $ticket_id ); ?>"
                data-nonce="<?php echo esc_attr( $nonce ); ?>"
                data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
                <?php echo $text ? 'Retriage' : 'Triage Ticket'; ?>
            </button>
            <span id="sp-ai-triage-status" style="font-size:12px;color:#94a3b8;display:none">Analyzing…</span>
        </div>
    <?php sp_ai_card_end(); ?>
    <script>
    (function(){
        var btn    = document.getElementById('sp-ai-triage-btn');
        var output = document.getElementById('sp-ai-triage-output');
        var status = document.getElementById('sp-ai-triage-status');
        var meta   = document.getElementById('sp-ai-triage-meta');
        if ( ! btn ) return;
        btn.addEventListener('click', function(){
            btn.disabled = true;
            status.style.display = 'inline';
            var fd = new FormData();
            fd.append('action',    'sp_ai_ticket_triage');
            fd.append('ticket_id', btn.dataset.ticket);
            fd.append('nonce',     btn.dataset.nonce);
            fetch(btn.dataset.ajax, { method:'POST', body:fd })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    status.style.display = 'none';
                    btn.disabled         = false;
                    btn.textContent      = 'Retriage';
                    if ( data.success ) {
                        output.innerHTML     = typeof spAiRenderMd === 'function' ? spAiRenderMd(data.data.text) : data.data.text;
                        output.style.display = 'block';
                        meta.textContent     = 'Generated just now';
                    } else {
                        output.textContent   = 'Error: ' + (data.data || 'Unknown error');
                        output.style.display = 'block';
                    }
                })
                .catch(function(){
                    status.style.display = 'none';
                    btn.disabled         = false;
                    output.textContent   = 'Request failed.';
                    output.style.display = 'block';
                });
        });
    })();
    </script>
    <?php
}

// ── Ticket Triage AJAX handler ────────────────────────────────────────────────

function sp_ai_ajax_ticket_triage() {
    if ( ! check_ajax_referer( 'sp_ai_ticket_triage', 'nonce', false ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }
    if ( ! sp_ai_authed() ) {
        wp_send_json_error( 'Not authorized' );
    }

    $ticket_id = (int) ( isset( $_POST['ticket_id'] ) ? $_POST['ticket_id'] : 0 );
    if ( ! $ticket_id ) wp_send_json_error( 'No ticket ID' );

    global $wpdb;

    $ticket = $wpdb->get_row( $wpdb->prepare(
        "SELECT t.*, c.first_name, c.last_name, co.name AS company_name
         FROM {$wpdb->prefix}sp_tickets t
         LEFT JOIN {$wpdb->prefix}sp_contacts c  ON c.id  = t.contact_id
         LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id = t.company_id
         WHERE t.id = %d", $ticket_id
    ) );
    if ( ! $ticket ) wp_send_json_error( 'Ticket not found' );

    $lines   = array();
    $lines[] = 'Ticket: ' . $ticket->title;
    $lines[] = 'Current status: ' . $ticket->status;
    $lines[] = 'Current priority: ' . $ticket->priority;
    if ( $ticket->assigned_to ) $lines[] = 'Assigned to: ' . $ticket->assigned_to;
    if ( $ticket->first_name )  $lines[] = 'Contact: ' . trim( $ticket->first_name . ' ' . $ticket->last_name );
    if ( $ticket->company_name ) $lines[] = 'Company: ' . $ticket->company_name;
    $lines[] = 'Created: ' . date( 'M j, Y', strtotime( $ticket->created_at ) );
    if ( $ticket->description ) {
        $lines[] = '';
        $lines[] = 'Description:';
        $lines[] = $ticket->description;
    }

    // Prior tickets from same contact for context
    if ( $ticket->contact_id ) {
        $prior = $wpdb->get_results( $wpdb->prepare(
            "SELECT title, status, priority FROM {$wpdb->prefix}sp_tickets
             WHERE contact_id = %d AND id != %d ORDER BY created_at DESC LIMIT 5",
            $ticket->contact_id, $ticket_id
        ) );
        if ( $prior ) {
            $lines[] = '';
            $lines[] = 'Prior tickets from this contact:';
            foreach ( $prior as $p ) {
                $lines[] = '- [' . $p->priority . '] ' . $p->title . ' (' . $p->status . ')';
            }
        }
    }

    $context = implode( "\n", $lines );
    $priorities = function_exists( 'sp_tickets_get_priorities' ) ? implode( ', ', sp_tickets_get_priorities() ) : 'low, normal, high, urgent';
    $statuses   = function_exists( 'sp_tickets_get_statuses' )   ? implode( ', ', sp_tickets_get_statuses() )   : 'open, in_progress, resolved, closed';

    $prompt = "You are a support ticket triage assistant. Based on the ticket below, provide:\n1. **Recommended Priority** ($priorities) — and why\n2. **Recommended Status** ($statuses)\n3. **Summary** — one sentence describing the core issue\n4. **Suggested Next Steps** — 2-3 specific actions for the assignee\n\nBe concise and direct.\n\n$context";

    $result = sp_ai_call_api( $prompt );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    update_option( 'sp_ai_ticket_triage_' . $ticket_id, array(
        'text'         => $result,
        'generated_at' => time(),
    ), false );

    wp_send_json_success( array( 'text' => $result ) );
}

// ── Ticket Analysis UI ────────────────────────────────────────────────────────

function sp_ai_ticket_analysis_ui() {
    if ( ! sp_ai_is_configured() ) return;
    $nonce     = wp_create_nonce( 'sp_ai_ticket_analysis' );
    $cached    = get_option( 'sp_ai_ticket_analysis_cache' );
    $text      = $cached ? $cached['text'] : '';
    $gen_at    = $cached ? $cached['generated_at'] : 0;
    $gen_label = $gen_at ? 'Generated ' . human_time_diff( $gen_at ) . ' ago' : '';
    $action_html = '<span id="sp-ai-ta-meta" style="margin-left:auto;font-size:11px;color:#94a3b8">' . esc_html( $gen_label ) . '</span>'
        . '<span id="sp-ai-ta-status" style="font-size:12px;color:#94a3b8;display:none">Analyzing…</span>'
        . '<button type="button" id="sp-ai-ta-btn" style="background:rgba(255,255,255,.08);border:none;border-radius:6px;padding:4px 10px;cursor:pointer;color:#e2e8f0;font-size:.75rem;"'
        . ' data-nonce="' . esc_attr( $nonce ) . '" data-ajax="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '">'
        . ( $text ? 'Refresh Analysis' : 'Analyze Tickets' ) . '</button>';
    sp_ai_card_start( 'Ticket Analysis', $action_html );
    $well = sp_ai_content_well_style();
    ?>
        <?php if ( $text ) : ?>
        <div id="sp-ai-ta-output" class="sp-ai-md-out" style="<?php echo $well; ?>font-size:14px;line-height:1.75;color:#1e293b"><?php echo sp_ai_render_markdown( $text ); ?></div>
        <?php else : ?>
        <div id="sp-ai-ta-output" class="sp-ai-md-out" style="display:none;<?php echo $well; ?>font-size:14px;line-height:1.75;color:#1e293b"></div>
        <?php endif; ?>
    <?php sp_ai_card_end(); ?>
    <style>
    .sp-ai-md-out h2{font-size:16px;font-weight:700;color:#0f172a;margin:20px 0 4px;display:flex;align-items:center;gap:7px}
    .sp-ai-md-out h2:first-child{margin-top:0}
    .sp-ai-md-out h3{font-size:14px;font-weight:700;color:#1e293b;margin:14px 0 3px;display:flex;align-items:center;gap:6px}
    .sp-ai-md-out p{margin:0 0 10px;color:#374151}
    .sp-ai-md-out strong{font-weight:600;color:#0f172a}
    .sp-ai-md-out ul{margin:4px 0 10px 18px;padding:0}
    .sp-ai-md-out li{margin-bottom:3px}
    </style>
    <script>
    if(typeof spAiRenderMd==='undefined'){
        var _ih2='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15" style="flex-shrink:0;color:#6366f1"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>';
        var _ih3='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" style="flex-shrink:0;color:#94a3b8"><path d="M9 5l7 7-7 7"/></svg>';
        function spAiEsc(s){return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
        function spAiInline(t){return spAiEsc(t).replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>').replace(/\*(.+?)\*/g,'<em>$1</em>');}
        function spAiRenderMd(md){
            var lines=md.split('\n'),html='',inUl=false;
            lines.forEach(function(line){
                if(/^# /.test(line)){if(inUl){html+='</ul>';inUl=false;}html+='<h2>'+_ih2+spAiEsc(line.replace(/^# /,''))+'</h2>';}
                else if(/^## /.test(line)){if(inUl){html+='</ul>';inUl=false;}html+='<h3>'+_ih3+spAiEsc(line.replace(/^## /,''))+'</h3>';}
                else if(/^[-*] /.test(line)){if(!inUl){html+='<ul>';inUl=true;}html+='<li>'+spAiInline(line.replace(/^[-*] /,''))+'</li>';}
                else if(line.trim()===''){if(inUl){html+='</ul>';inUl=false;}}
                else{if(inUl){html+='</ul>';inUl=false;}html+='<p>'+spAiInline(line)+'</p>';}
            });
            if(inUl)html+='</ul>';
            return html;
        }
    }
    (function(){
        var btn    = document.getElementById('sp-ai-ta-btn');
        var output = document.getElementById('sp-ai-ta-output');
        var status = document.getElementById('sp-ai-ta-status');
        var meta   = document.getElementById('sp-ai-ta-meta');
        if ( ! btn ) return;
        btn.addEventListener('click', function(){
            btn.disabled = true;
            status.style.display = 'inline';
            var fd = new FormData();
            fd.append('action', 'sp_ai_ticket_analysis');
            fd.append('nonce',  btn.dataset.nonce);
            fetch(btn.dataset.ajax, { method:'POST', body:fd })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    status.style.display = 'none';
                    btn.disabled         = false;
                    btn.textContent      = 'Refresh Analysis';
                    if ( data.success ) {
                        output.innerHTML     = spAiRenderMd(data.data.text);
                        output.style.display = 'block';
                        meta.textContent     = 'Generated just now';
                    } else {
                        output.textContent   = 'Error: ' + (data.data || 'Unknown error');
                        output.style.display = 'block';
                    }
                })
                .catch(function(){
                    status.style.display = 'none';
                    btn.disabled         = false;
                    output.textContent   = 'Request failed.';
                    output.style.display = 'block';
                });
        });
    })();
    </script>
    <?php
}

// ── Ticket Analysis AJAX handler ──────────────────────────────────────────────

function sp_ai_ajax_ticket_analysis() {
    if ( ! check_ajax_referer( 'sp_ai_ticket_analysis', 'nonce', false ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }
    if ( ! sp_ai_authed() ) {
        wp_send_json_error( 'Not authorized' );
    }

    global $wpdb;

    // Counts by status
    $by_status = $wpdb->get_results(
        "SELECT status, COUNT(*) as count FROM {$wpdb->prefix}sp_tickets GROUP BY status ORDER BY count DESC"
    );

    // Counts by priority (open only)
    $by_priority = $wpdb->get_results(
        "SELECT priority, COUNT(*) as count FROM {$wpdb->prefix}sp_tickets
         WHERE status NOT IN ('resolved','closed') GROUP BY priority ORDER BY FIELD(priority,'urgent','high','normal','low')"
    );

    // Tickets opened in last 30 days
    $recent = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tickets WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );

    // Oldest open tickets
    $oldest = $wpdb->get_results(
        "SELECT title, priority, DATEDIFF(NOW(), created_at) AS age_days, assigned_to
         FROM {$wpdb->prefix}sp_tickets
         WHERE status NOT IN ('resolved','closed')
         ORDER BY created_at ASC LIMIT 5"
    );

    // Contacts with most open tickets
    $repeat = $wpdb->get_results(
        "SELECT c.first_name, c.last_name, COUNT(*) as count
         FROM {$wpdb->prefix}sp_tickets t
         LEFT JOIN {$wpdb->prefix}sp_contacts c ON c.id = t.contact_id
         WHERE t.status NOT IN ('resolved','closed') AND t.contact_id > 0
         GROUP BY t.contact_id ORDER BY count DESC LIMIT 5"
    );

    // Unassigned open tickets
    $unassigned = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}sp_tickets
         WHERE status NOT IN ('resolved','closed') AND (assigned_to = '' OR assigned_to IS NULL)"
    );

    // Build context
    $lines   = array( 'Ticket queue snapshot as of ' . date( 'F j, Y' ) . ':', '' );

    $lines[] = 'Tickets by status:';
    foreach ( $by_status as $r ) $lines[] = "  {$r->status}: {$r->count}";

    if ( $by_priority ) {
        $lines[] = '';
        $lines[] = 'Open tickets by priority:';
        foreach ( $by_priority as $r ) $lines[] = "  {$r->priority}: {$r->count}";
    }

    $lines[] = '';
    $lines[] = "New tickets in last 30 days: $recent";
    $lines[] = "Unassigned open tickets: $unassigned";

    if ( $oldest ) {
        $lines[] = '';
        $lines[] = 'Oldest unresolved tickets:';
        foreach ( $oldest as $t ) {
            $assigned = $t->assigned_to ? "assigned to {$t->assigned_to}" : 'unassigned';
            $lines[]  = "  - [{$t->priority}] {$t->title} — {$t->age_days} days old, $assigned";
        }
    }

    if ( $repeat ) {
        $lines[] = '';
        $lines[] = 'Contacts with most open tickets:';
        foreach ( $repeat as $r ) {
            $name    = trim( $r->first_name . ' ' . $r->last_name ) ?: 'Unknown';
            $lines[] = "  - $name: {$r->count} open tickets";
        }
    }

    $context = implode( "\n", $lines );

    $prompt = "You are a support operations analyst. Based on the following ticket queue data, write a concise analysis covering: overall queue health, the most urgent issues, any patterns worth noting (repeat contacts, aging tickets, priority distribution), and 2-3 specific recommended actions for the team this week. Be direct and actionable.\n\n$context";

    $result = sp_ai_call_api( $prompt );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    update_option( 'sp_ai_ticket_analysis_cache', array(
        'text'         => $result,
        'generated_at' => time(),
    ), false );

    wp_send_json_success( array( 'text' => $result ) );
}

// ── API call helper ───────────────────────────────────────────────────────────

function sp_ai_call_api( $prompt ) {
    $api_key = sp_ai_get_api_key();
    $model   = sp_ai_get_model();

    $is_anthropic = strpos( $model, 'claude' ) !== false;

    if ( $is_anthropic ) {
        $url     = 'https://api.anthropic.com/v1/messages';
        $headers = array(
            'x-api-key'         => $api_key,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        );
        $body = wp_json_encode( array(
            'model'      => $model,
            'max_tokens' => 1024,
            'messages'   => array(
                array( 'role' => 'user', 'content' => $prompt ),
            ),
        ) );
    } else {
        $url     = 'https://api.openai.com/v1/chat/completions';
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        );
        $body = wp_json_encode( array(
            'model'    => $model,
            'messages' => array(
                array( 'role' => 'user', 'content' => $prompt ),
            ),
        ) );
    }

    $response = wp_remote_post( $url, array(
        'headers' => $headers,
        'body'    => $body,
        'timeout' => 30,
    ) );

    if ( is_wp_error( $response ) ) return $response;

    $code = wp_remote_retrieve_response_code( $response );
    $json = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $code !== 200 ) {
        $msg = isset( $json['error']['message'] ) ? $json['error']['message'] : "API error ($code)";
        return new WP_Error( 'sp_ai_api', $msg );
    }

    if ( $is_anthropic ) {
        return isset( $json['content'][0]['text'] ) ? trim( $json['content'][0]['text'] ) : new WP_Error( 'sp_ai_api', 'Empty response' );
    } else {
        return isset( $json['choices'][0]['message']['content'] ) ? trim( $json['choices'][0]['message']['content'] ) : new WP_Error( 'sp_ai_api', 'Empty response' );
    }
}
