<?php
/*
 * Plugin Name: Start Performance — WTS Client Setup
 * Description: WTS-specific client onboarding questionnaire for the Start Performance Platform
 * Version:     1.0.15
 * Author:      Richard Brashear / Start Performance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SP_WTS_VERSION',    '1.0.15' );
define( 'SP_WTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// ── Boot ──────────────────────────────────────────────────────────────────────

add_action( 'plugins_loaded', 'sp_wts_boot', 20 );

function sp_wts_boot() {
    if ( ! function_exists( 'sp_register_view' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Start Performance — WTS Client Setup</strong> requires the Start Performance core plugin.</p></div>';
        } );
        return;
    }
    sp_wts_register();
}

// ── Activation ────────────────────────────────────────────────────────────────

register_activation_hook( __FILE__, 'sp_wts_activate' );
add_action( 'sp_activate', 'sp_wts_create_tables' );

function sp_wts_activate() {
    sp_wts_create_tables();
}

function sp_wts_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_wts_onboarding (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  member_id bigint(20) unsigned NOT NULL DEFAULT 0,
  submitter_name varchar(255) NOT NULL DEFAULT '',
  submitted_at datetime NOT NULL,
  ordinance_name varchar(500) NOT NULL DEFAULT '',
  jurisdiction_name varchar(255) NOT NULL DEFAULT '',
  governing_body_ordinance varchar(255) NOT NULL DEFAULT '',
  governing_body_appeal varchar(255) NOT NULL DEFAULT '',
  replaces_existing varchar(10) NOT NULL DEFAULT '',
  existing_ordinance_details varchar(500) NOT NULL DEFAULT '',
  code_designation varchar(255) NOT NULL DEFAULT '',
  exemptions text,
  additional_exemptions text,
  approval_authority varchar(255) NOT NULL DEFAULT '',
  approving_department varchar(255) NOT NULL DEFAULT '',
  building_permits_entity varchar(255) NOT NULL DEFAULT '',
  tower_siting_1 varchar(255) NOT NULL DEFAULT '',
  tower_siting_2 varchar(255) NOT NULL DEFAULT '',
  tower_siting_3 varchar(255) NOT NULL DEFAULT '',
  tower_siting_4 varchar(255) NOT NULL DEFAULT '',
  tower_siting_5 varchar(255) NOT NULL DEFAULT '',
  tower_siting_6 varchar(255) NOT NULL DEFAULT '',
  tower_siting_7 varchar(255) NOT NULL DEFAULT '',
  tower_registration_confirmed varchar(10) NOT NULL DEFAULT '',
  jurisdiction_owned_poles varchar(10) NOT NULL DEFAULT '',
  jur_fee_colocation varchar(100) NOT NULL DEFAULT '',
  jur_fee_new_tower varchar(100) NOT NULL DEFAULT '',
  jur_fee_tower_registration varchar(100) NOT NULL DEFAULT '',
  jur_fee_approved_update varchar(100) NOT NULL DEFAULT '',
  jur_fee_swf_new varchar(100) NOT NULL DEFAULT '',
  jur_fee_swf_colocation varchar(100) NOT NULL DEFAULT '',
  jur_fee_annual_row varchar(100) NOT NULL DEFAULT '',
  ins_colocation varchar(100) NOT NULL DEFAULT '',
  ins_small_wireless varchar(100) NOT NULL DEFAULT '',
  ins_new_tower varchar(100) NOT NULL DEFAULT '',
  ins_cgl varchar(100) NOT NULL DEFAULT '',
  ins_auto varchar(100) NOT NULL DEFAULT '',
  ins_workers_comp varchar(100) NOT NULL DEFAULT '',
  ins_additional_insured varchar(10) NOT NULL DEFAULT '',
  mailing_jur_name varchar(255) NOT NULL DEFAULT '',
  mailing_address varchar(500) NOT NULL DEFAULT '',
  mailing_city varchar(100) NOT NULL DEFAULT '',
  mailing_state varchar(50) NOT NULL DEFAULT '',
  mailing_zip varchar(20) NOT NULL DEFAULT '',
  mailing_attention varchar(255) NOT NULL DEFAULT '',
  primary_name varchar(255) NOT NULL DEFAULT '',
  primary_position varchar(255) NOT NULL DEFAULT '',
  primary_department varchar(255) NOT NULL DEFAULT '',
  primary_phone varchar(50) NOT NULL DEFAULT '',
  primary_cell varchar(50) NOT NULL DEFAULT '',
  primary_email varchar(255) NOT NULL DEFAULT '',
  secondary_name varchar(255) NOT NULL DEFAULT '',
  secondary_position varchar(255) NOT NULL DEFAULT '',
  secondary_department varchar(255) NOT NULL DEFAULT '',
  secondary_phone varchar(50) NOT NULL DEFAULT '',
  secondary_cell varchar(50) NOT NULL DEFAULT '',
  secondary_email varchar(255) NOT NULL DEFAULT '',
  additional_name varchar(255) NOT NULL DEFAULT '',
  additional_position varchar(255) NOT NULL DEFAULT '',
  additional_department varchar(255) NOT NULL DEFAULT '',
  additional_phone varchar(50) NOT NULL DEFAULT '',
  additional_cell varchar(50) NOT NULL DEFAULT '',
  additional_email varchar(255) NOT NULL DEFAULT '',
  billing_same_as_primary varchar(10) NOT NULL DEFAULT '',
  billing_name varchar(255) NOT NULL DEFAULT '',
  billing_position varchar(255) NOT NULL DEFAULT '',
  billing_phone varchar(50) NOT NULL DEFAULT '',
  billing_invoice_email varchar(10) NOT NULL DEFAULT '',
  billing_mailing_jur varchar(255) NOT NULL DEFAULT '',
  billing_mailing_address varchar(500) NOT NULL DEFAULT '',
  billing_mailing_city varchar(100) NOT NULL DEFAULT '',
  billing_mailing_state varchar(50) NOT NULL DEFAULT '',
  billing_mailing_zip varchar(20) NOT NULL DEFAULT '',
  billing_mailing_attention varchar(255) NOT NULL DEFAULT '',
  billing_w9_same varchar(10) NOT NULL DEFAULT '',
  billing_w9_contact varchar(255) NOT NULL DEFAULT '',
  e911_name varchar(255) NOT NULL DEFAULT '',
  e911_position varchar(255) NOT NULL DEFAULT '',
  e911_department varchar(255) NOT NULL DEFAULT '',
  e911_phone varchar(50) NOT NULL DEFAULT '',
  e911_email varchar(255) NOT NULL DEFAULT '',
  e911_description text,
  e911_link varchar(500) NOT NULL DEFAULT '',
  PRIMARY KEY  (id),
  KEY member_id (member_id)
) $charset;" );
}

// ── Registration ──────────────────────────────────────────────────────────────

function sp_wts_register() {
    sp_wts_create_tables();

    sp_register_view( 'client-setup', SP_WTS_PLUGIN_DIR . 'templates/views/client-setup.php' );

    add_filter( 'sp_nav_items',     'sp_wts_nav_items',     15 );
    add_filter( 'sp_allowed_views', 'sp_wts_allowed_views', 15 );

    add_action( 'sp_post_handler_client_setup',            'sp_wts_save_questionnaire' );
    add_action( 'sp_post_handler_wts_add_jurisdiction',    'sp_wts_handle_add_jurisdiction' );
    add_action( 'sp_post_handler_wts_approve_jurisdiction', 'sp_wts_handle_approve_jurisdiction' );
    add_action( 'sp_post_handler_wts_resend_invite',        'sp_wts_handle_resend_invite' );
    add_action( 'sp_post_handler_wts_delete_jurisdiction',  'sp_wts_handle_delete_jurisdiction' );
    add_action( 'sp_post_handler_wts_set_inactive',          'sp_wts_handle_set_inactive' );
    add_action( 'sp_post_handler_wts_reactivate',            'sp_wts_handle_reactivate_jurisdiction' );
    add_action( 'sp_post_handler_wts_edit_jurisdiction',     'sp_wts_handle_edit_jurisdiction' );
}

// ── Nav — injects after Operations Core's Onboarding item ─────────────────────

function sp_wts_nav_items( $items ) {
    // AMP Login — external portal link, injected above Intelligence Core (after the first divider)
    $amp_login = array(
        'url'        => 'https://wirelesstowersolutions.com/Sites/',
        'label'      => 'AMP Login',
        'target'     => '_blank',
        'icon'       => '<path fill="currentColor" d="M14 5a1 1 0 011-1h5a1 1 0 011 1v5a1 1 0 11-2 0V7.414l-7.293 7.293a1 1 0 01-1.414-1.414L17.586 6H15a1 1 0 01-1-1zM3 7a2 2 0 012-2h5a1 1 0 010 2H5v12h12v-5a1 1 0 112 0v5a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>',
        'item_style' => 'color:var(--sp-accent);font-weight:600;background:var(--sp-accent-bg);',
    );
    $pre = array(); $inserted_amp = false;
    foreach ( $items as $item ) {
        $pre[] = $item;
        if ( ! $inserted_amp && ! empty( $item['divider'] ) ) {
            $pre[] = $amp_login;
            $inserted_amp = true;
        }
    }
    if ( ! $inserted_amp ) { array_unshift( $pre, $amp_login ); }
    $items = $pre;

    $client_setup = array(
        'view'  => 'client-setup',
        'label' => 'Client Setup',
        'icon'  => '<path fill="currentColor" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill="currentColor" fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>',
    );

    $result  = array();
    $injected = false;
    foreach ( $items as $item ) {
        $result[] = $item;
        if ( ! $injected && isset( $item['view'] ) && $item['view'] === 'onboarding' ) {
            $result[]  = $client_setup;
            $injected  = true;
        }
    }

    // Operations Core not active — append after the operations-core section header if present,
    // otherwise just append to the end.
    if ( ! $injected ) {
        $ops_pos = null;
        foreach ( $result as $i => $item ) {
            if ( ! empty( $item['section'] ) && ( $item['section_id'] ?? '' ) === 'operations-core' ) {
                $ops_pos = $i;
            }
        }
        if ( $ops_pos !== null ) {
            array_splice( $result, $ops_pos + 1, 0, array( $client_setup ) );
        } else {
            $result[] = $client_setup;
        }
    }

    return $result;
}

function sp_wts_allowed_views( $views ) {
    $views[] = 'client-setup';
    return $views;
}

// ── Save questionnaire ────────────────────────────────────────────────────────

function sp_wts_save_questionnaire( $id ) {
    global $wpdb;
    $member = sp_get_current_team_member();
    if ( ! $member ) { wp_redirect( home_url('/sp-app/?view=client-setup') ); exit; }

    $wp_user      = wp_get_current_user();
    $display_name = $wp_user->display_name ?: ( $member->display_name ?? '' );
    $exemptions   = json_encode( array_map( 'sanitize_key', (array)( $_POST['exemptions'] ?? array() ) ) );

    $data = array(
        'member_id'                    => (int)$member->id,
        'submitter_name'               => sanitize_text_field( $display_name ),
        'submitted_at'                 => current_time('mysql'),
        'ordinance_name'               => sanitize_text_field( $_POST['ordinance_name']              ?? '' ),
        'jurisdiction_name'            => sanitize_text_field( $_POST['jurisdiction_name']            ?? '' ),
        'governing_body_ordinance'     => sanitize_text_field( $_POST['governing_body_ordinance']     ?? '' ),
        'governing_body_appeal'        => sanitize_text_field( $_POST['governing_body_appeal']        ?? '' ),
        'replaces_existing'            => sanitize_key(        $_POST['replaces_existing']            ?? '' ),
        'existing_ordinance_details'   => sanitize_text_field( $_POST['existing_ordinance_details']   ?? '' ),
        'code_designation'             => sanitize_text_field( $_POST['code_designation']             ?? '' ),
        'exemptions'                   => $exemptions,
        'additional_exemptions'        => sanitize_textarea_field( $_POST['additional_exemptions']    ?? '' ),
        'approval_authority'           => sanitize_text_field( $_POST['approval_authority']           ?? '' ),
        'approving_department'         => sanitize_text_field( $_POST['approving_department']         ?? '' ),
        'building_permits_entity'      => sanitize_text_field( $_POST['building_permits_entity']      ?? '' ),
        'tower_siting_1'               => sanitize_text_field( $_POST['tower_siting_1']               ?? '' ),
        'tower_siting_2'               => sanitize_text_field( $_POST['tower_siting_2']               ?? '' ),
        'tower_siting_3'               => sanitize_text_field( $_POST['tower_siting_3']               ?? '' ),
        'tower_siting_4'               => sanitize_text_field( $_POST['tower_siting_4']               ?? '' ),
        'tower_siting_5'               => sanitize_text_field( $_POST['tower_siting_5']               ?? '' ),
        'tower_siting_6'               => sanitize_text_field( $_POST['tower_siting_6']               ?? '' ),
        'tower_siting_7'               => sanitize_text_field( $_POST['tower_siting_7']               ?? '' ),
        'tower_registration_confirmed' => sanitize_key(        $_POST['tower_registration_confirmed']  ?? '' ),
        'jurisdiction_owned_poles'     => sanitize_key(        $_POST['jurisdiction_owned_poles']      ?? '' ),
        'jur_fee_colocation'           => sanitize_text_field( $_POST['jur_fee_colocation']           ?? '' ),
        'jur_fee_new_tower'            => sanitize_text_field( $_POST['jur_fee_new_tower']            ?? '' ),
        'jur_fee_tower_registration'   => sanitize_text_field( $_POST['jur_fee_tower_registration']   ?? '' ),
        'jur_fee_approved_update'      => sanitize_text_field( $_POST['jur_fee_approved_update']      ?? '' ),
        'jur_fee_swf_new'              => sanitize_text_field( $_POST['jur_fee_swf_new']              ?? '' ),
        'jur_fee_swf_colocation'       => sanitize_text_field( $_POST['jur_fee_swf_colocation']       ?? '' ),
        'jur_fee_annual_row'           => sanitize_text_field( $_POST['jur_fee_annual_row']           ?? '' ),
        'ins_colocation'               => sanitize_text_field( $_POST['ins_colocation']               ?? '' ),
        'ins_small_wireless'           => sanitize_text_field( $_POST['ins_small_wireless']           ?? '' ),
        'ins_new_tower'                => sanitize_text_field( $_POST['ins_new_tower']                ?? '' ),
        'ins_cgl'                      => sanitize_text_field( $_POST['ins_cgl']                      ?? '' ),
        'ins_auto'                     => sanitize_text_field( $_POST['ins_auto']                     ?? '' ),
        'ins_workers_comp'             => sanitize_text_field( $_POST['ins_workers_comp']             ?? '' ),
        'ins_additional_insured'       => sanitize_key(        $_POST['ins_additional_insured']       ?? '' ),
        'mailing_jur_name'             => sanitize_text_field( $_POST['mailing_jur_name']             ?? '' ),
        'mailing_address'              => sanitize_text_field( $_POST['mailing_address']              ?? '' ),
        'mailing_city'                 => sanitize_text_field( $_POST['mailing_city']                 ?? '' ),
        'mailing_state'                => sanitize_text_field( $_POST['mailing_state']                ?? '' ),
        'mailing_zip'                  => sanitize_text_field( $_POST['mailing_zip']                  ?? '' ),
        'mailing_attention'            => sanitize_text_field( $_POST['mailing_attention']            ?? '' ),
        'primary_name'                 => sanitize_text_field( $_POST['primary_name']                 ?? '' ),
        'primary_position'             => sanitize_text_field( $_POST['primary_position']             ?? '' ),
        'primary_department'           => sanitize_text_field( $_POST['primary_department']           ?? '' ),
        'primary_phone'                => sanitize_text_field( $_POST['primary_phone']                ?? '' ),
        'primary_cell'                 => sanitize_text_field( $_POST['primary_cell']                 ?? '' ),
        'primary_email'                => sanitize_email(      $_POST['primary_email']                ?? '' ),
        'secondary_name'               => sanitize_text_field( $_POST['secondary_name']               ?? '' ),
        'secondary_position'           => sanitize_text_field( $_POST['secondary_position']           ?? '' ),
        'secondary_department'         => sanitize_text_field( $_POST['secondary_department']         ?? '' ),
        'secondary_phone'              => sanitize_text_field( $_POST['secondary_phone']              ?? '' ),
        'secondary_cell'               => sanitize_text_field( $_POST['secondary_cell']               ?? '' ),
        'secondary_email'              => sanitize_email(      $_POST['secondary_email']              ?? '' ),
        'additional_name'              => sanitize_text_field( $_POST['additional_name']              ?? '' ),
        'additional_position'          => sanitize_text_field( $_POST['additional_position']          ?? '' ),
        'additional_department'        => sanitize_text_field( $_POST['additional_department']        ?? '' ),
        'additional_phone'             => sanitize_text_field( $_POST['additional_phone']             ?? '' ),
        'additional_cell'              => sanitize_text_field( $_POST['additional_cell']              ?? '' ),
        'additional_email'             => sanitize_email(      $_POST['additional_email']             ?? '' ),
        'billing_same_as_primary'      => sanitize_key(        $_POST['billing_same_as_primary']      ?? '' ),
        'billing_name'                 => sanitize_text_field( $_POST['billing_name']                 ?? '' ),
        'billing_position'             => sanitize_text_field( $_POST['billing_position']             ?? '' ),
        'billing_phone'                => sanitize_text_field( $_POST['billing_phone']                ?? '' ),
        'billing_invoice_email'        => sanitize_key(        $_POST['billing_invoice_email']        ?? '' ),
        'billing_mailing_jur'          => sanitize_text_field( $_POST['billing_mailing_jur']          ?? '' ),
        'billing_mailing_address'      => sanitize_text_field( $_POST['billing_mailing_address']      ?? '' ),
        'billing_mailing_city'         => sanitize_text_field( $_POST['billing_mailing_city']         ?? '' ),
        'billing_mailing_state'        => sanitize_text_field( $_POST['billing_mailing_state']        ?? '' ),
        'billing_mailing_zip'          => sanitize_text_field( $_POST['billing_mailing_zip']          ?? '' ),
        'billing_mailing_attention'    => sanitize_text_field( $_POST['billing_mailing_attention']    ?? '' ),
        'billing_w9_same'              => sanitize_key(        $_POST['billing_w9_same']              ?? '' ),
        'billing_w9_contact'           => sanitize_text_field( $_POST['billing_w9_contact']           ?? '' ),
        'e911_name'                    => sanitize_text_field( $_POST['e911_name']                    ?? '' ),
        'e911_position'                => sanitize_text_field( $_POST['e911_position']                ?? '' ),
        'e911_department'              => sanitize_text_field( $_POST['e911_department']              ?? '' ),
        'e911_phone'                   => sanitize_text_field( $_POST['e911_phone']                   ?? '' ),
        'e911_email'                   => sanitize_email(      $_POST['e911_email']                   ?? '' ),
        'e911_description'             => sanitize_textarea_field( $_POST['e911_description']         ?? '' ),
        'e911_link'                    => esc_url_raw(         $_POST['e911_link']                    ?? '' ),
    );

    $existing_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}sp_wts_onboarding WHERE member_id=%d", $member->id
    ) );

    if ( $existing_id ) {
        $wpdb->update( $wpdb->prefix . 'sp_wts_onboarding', $data, array('id' => (int)$existing_id) );
    } else {
        $wpdb->insert( $wpdb->prefix . 'sp_wts_onboarding', $data );
    }

    // Advance onboarding status from 'invited' → 'pending' on first submit
    if ( get_option( 'sp_wts_member_status_' . $member->id, '' ) === 'invited' ) {
        update_option( 'sp_wts_member_status_' . $member->id, 'pending', false );
    }

    // Confirmation email to jurisdiction contact
    $contact_email = $member->email ?? '';
    if ( $contact_email ) {
        wp_mail(
            $contact_email,
            'WTS Application Received',
            "Thank you for submitting the WTS Client Setup Questionnaire.\n\n"
            . "We have received your submission and our team is currently reviewing your information.\n"
            . "A WTS representative will be in touch with you shortly.\n\n"
            . "Thank you,\nWireless Tower Solutions"
        );
    }

    // Admin notification email
    $notify_email = get_option( 'sp_wts_onboarding_email', '' );
    if ( $notify_email ) {
        $jurisdiction = $data['jurisdiction_name']; // already sanitized when building $data
        $subject      = 'Client Setup Questionnaire Submitted' . ( $jurisdiction ? ' — ' . $jurisdiction : '' );
        $body         = "A client has submitted the WTS Client Setup questionnaire.\n\n"
                      . "Submitted by: {$display_name}\n"
                      . ( $jurisdiction ? "Jurisdiction: {$jurisdiction}\n" : '' )
                      . "\nView it in the app: " . home_url( '/sp-app/?view=client-setup' );
        wp_mail( $notify_email, $subject, $body );
    }

    wp_redirect( home_url('/sp-app/?view=client-setup&submitted=1') ); exit;
}

// ── Add New Jurisdiction ──────────────────────────────────────────────────────

function sp_wts_handle_add_jurisdiction() {
    if ( ! sp_is_admin_member() && ! sp_is_super_admin() ) {
        wp_redirect( home_url('/sp-app/?view=client-setup') ); exit;
    }
    global $wpdb;

    $contact_name = sanitize_text_field( $_POST['contact_name'] ?? '' );
    $email        = sanitize_email(      $_POST['contact_email'] ?? '' );
    $jur_name     = sanitize_text_field( $_POST['jurisdiction_name'] ?? '' );

    if ( ! $email ) {
        wp_redirect( home_url('/sp-app/?view=client-setup&error=noemail') ); exit;
    }
    $existing = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}sp_team WHERE email=%s", $email
    ) );
    if ( $existing ) {
        wp_redirect( home_url('/sp-app/?view=client-setup&error=exists') ); exit;
    }

    $wpdb->insert( $wpdb->prefix . 'sp_team', array(
        'name'       => $contact_name ?: ( $jur_name ?: $email ),
        'email'      => $email,
        'role'       => 'user',
        'status'     => 'active',
        'pin'        => wp_hash_password( wp_generate_password( 24, true, true ) ),
        'created_at' => current_time('mysql'),
    ) );
    $member_id = (int) $wpdb->insert_id;

    update_option( 'sp_wts_member_status_' . $member_id, 'invited', false );
    if ( $jur_name ) {
        update_option( 'sp_wts_member_jur_' . $member_id, $jur_name, false );
    }

    sp_wts_send_invite( $member_id, $email, $contact_name, $jur_name );

    wp_redirect( home_url('/sp-app/?view=client-setup&invited=1') ); exit;
}

// ── Approve Jurisdiction ──────────────────────────────────────────────────────

function sp_wts_handle_approve_jurisdiction( $member_id ) {
    if ( ! sp_is_admin_member() && ! sp_is_super_admin() ) {
        wp_redirect( home_url('/sp-app/?view=client-setup') ); exit;
    }
    $member_id = $member_id ?: (int)( $_POST['sp_id'] ?? 0 );
    if ( ! $member_id ) {
        wp_redirect( home_url('/sp-app/?view=client-setup') ); exit;
    }

    global $wpdb;
    $member   = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sp_team WHERE id=%d", $member_id
    ) );
    $jur_name = get_option( 'sp_wts_member_jur_' . $member_id, '' );

    // Generate a readable 6-digit temporary PIN
    $temp_pin = (string) wp_rand( 100000, 999999 );
    $wpdb->update(
        $wpdb->prefix . 'sp_team',
        array( 'pin' => wp_hash_password( $temp_pin ) ),
        array( 'id'  => $member_id )
    );

    delete_option( 'sp_wts_member_status_' . $member_id );
    update_option( 'sp_wts_force_pin_' . $member_id, '1', false );

    // Send approval email with login credentials
    if ( $member && $member->email ) {
        $sp_name   = get_option( 'sp_platform_name', 'Start Performance' );
        $login_url = home_url( '/sp-login/' );
        $greeting  = $member->name ? "Hi {$member->name}," : 'Hello,';
        $jur_line  = $jur_name ? "Your setup for {$jur_name} has been reviewed and approved." : 'Your client setup has been reviewed and approved.';

        $subject = 'Your ' . $sp_name . ' Access Has Been Approved';
        $body    = "{$greeting}\n\n"
                 . "{$jur_line} Your account is now active.\n\n"
                 . "--- Your Login Details ---\n\n"
                 . "Login page: {$login_url}\n"
                 . "Your name:  {$member->name}\n"
                 . "Temp PIN:   {$temp_pin}\n\n"
                 . "On the login screen, select your name from the list and enter your temporary PIN.\n\n"
                 . "You will be prompted to set a permanent PIN when you first log in.\n\n"
                 . "— The {$sp_name} Team";

        wp_mail( $member->email, $subject, $body );
    }

    wp_redirect( home_url('/sp-app/?view=client-setup&approved=1') ); exit;
}

// ── Set Inactive ──────────────────────────────────────────────────────────────

function sp_wts_handle_set_inactive( $member_id ) {
    if ( ! sp_is_admin_member() && ! sp_is_super_admin() ) {
        wp_redirect( home_url('/sp-app/?view=client-setup') ); exit;
    }
    $member_id = $member_id ?: (int)( $_POST['sp_id'] ?? 0 );
    if ( ! $member_id ) {
        wp_redirect( home_url('/sp-app/?view=client-setup') ); exit;
    }
    update_option( 'sp_wts_member_status_' . $member_id, 'inactive', false );
    wp_redirect( home_url('/sp-app/?view=client-setup&inactivated=1') ); exit;
}

// ── Reactivate Jurisdiction ───────────────────────────────────────────────────

function sp_wts_handle_reactivate_jurisdiction( $member_id ) {
    if ( ! sp_is_admin_member() && ! sp_is_super_admin() ) {
        wp_redirect( home_url('/sp-app/?view=client-setup') ); exit;
    }
    $member_id = $member_id ?: (int)( $_POST['sp_id'] ?? 0 );
    if ( ! $member_id ) {
        wp_redirect( home_url('/sp-app/?view=client-setup') ); exit;
    }
    delete_option( 'sp_wts_member_status_' . $member_id );
    wp_redirect( home_url('/sp-app/?view=client-setup&reactivated=1') ); exit;
}

// ── Edit Jurisdiction ─────────────────────────────────────────────────────────

function sp_wts_handle_edit_jurisdiction( $member_id ) {
    if ( ! sp_is_admin_member() && ! sp_is_super_admin() ) {
        wp_redirect( home_url('/sp-app/?view=client-setup') ); exit;
    }
    $member_id = $member_id ?: (int)( $_POST['sp_id'] ?? 0 );
    if ( ! $member_id ) {
        wp_redirect( home_url('/sp-app/?view=client-setup') ); exit;
    }
    global $wpdb;

    $contact_name = sanitize_text_field( $_POST['contact_name'] ?? '' );
    $email        = sanitize_email( $_POST['contact_email'] ?? '' );
    $jur_name     = sanitize_text_field( $_POST['jurisdiction_name'] ?? '' );

    if ( ! $email ) {
        wp_redirect( home_url('/sp-app/?view=client-setup&action=edit-jurisdiction&id=' . $member_id . '&error=noemail') ); exit;
    }

    $current_email = $wpdb->get_var( $wpdb->prepare(
        "SELECT email FROM {$wpdb->prefix}sp_team WHERE id=%d", $member_id
    ) );
    if ( $email !== $current_email ) {
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}sp_team WHERE email=%s AND id != %d", $email, $member_id
        ) );
        if ( $exists ) {
            wp_redirect( home_url('/sp-app/?view=client-setup&action=edit-jurisdiction&id=' . $member_id . '&error=exists') ); exit;
        }
    }

    $wpdb->update(
        $wpdb->prefix . 'sp_team',
        array( 'name' => $contact_name ?: $email, 'email' => $email ),
        array( 'id' => $member_id )
    );

    if ( $jur_name ) {
        update_option( 'sp_wts_member_jur_' . $member_id, $jur_name, false );
    } else {
        delete_option( 'sp_wts_member_jur_' . $member_id );
    }

    wp_redirect( home_url('/sp-app/?view=client-setup&saved=1') ); exit;
}

// ── Resend Invite ─────────────────────────────────────────────────────────────

function sp_wts_handle_resend_invite( $member_id ) {
    if ( ! sp_is_admin_member() && ! sp_is_super_admin() ) {
        wp_redirect( home_url('/sp-app/?view=client-setup') ); exit;
    }
    $member_id = $member_id ?: (int)( $_POST['sp_id'] ?? 0 );
    if ( ! $member_id ) {
        wp_redirect( home_url('/sp-app/?view=client-setup') ); exit;
    }
    global $wpdb;
    $member = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sp_team WHERE id=%d AND status='active'", $member_id
    ) );
    if ( ! $member ) {
        wp_redirect( home_url('/sp-app/?view=client-setup') ); exit;
    }
    $jur_name = get_option( 'sp_wts_member_jur_' . $member_id, '' );
    sp_wts_send_invite( $member_id, $member->email, $member->name, $jur_name );
    wp_redirect( home_url('/sp-app/?view=client-setup&resent=1') ); exit;
}

// ── Shared invite sender ──────────────────────────────────────────────────────

function sp_wts_send_invite( $member_id, $email, $name, $jur_name = '' ) {
    $token      = wp_generate_password( 32, false );
    set_transient( 'sp_wts_invite_' . $token, array( 'member_id' => (int) $member_id ), 7 * DAY_IN_SECONDS );

    $sp_name    = get_option( 'sp_platform_name', 'Start Performance' );
    $invite_url = home_url( '/sp-login/?sp_invite=' . $token );
    $greeting   = $name ? "Hi {$name}," : 'Hello,';
    $jur_line   = $jur_name ? "You've been invited to complete the client setup for {$jur_name}.\n\n" : "You've been invited to complete your client setup.\n\n";

    $subject = 'Complete Your ' . $sp_name . ' Client Setup';
    $body    = "{$greeting}\n\n"
             . $jur_line
             . "Click the link below to access your setup questionnaire:\n\n"
             . $invite_url . "\n\n"
             . "This link expires in 7 days. If you need a new one, contact your WTS representative.\n\n"
             . "— The {$sp_name} Team";

    wp_mail( $email, $subject, $body );
}

// ── Delete Jurisdiction ───────────────────────────────────────────────────────

function sp_wts_handle_delete_jurisdiction( $member_id ) {
    if ( ! sp_is_admin_member() && ! sp_is_super_admin() ) {
        wp_redirect( home_url('/sp-app/?view=client-setup') ); exit;
    }
    $member_id = $member_id ?: (int)( $_POST['sp_id'] ?? 0 );
    if ( ! $member_id ) {
        wp_redirect( home_url('/sp-app/?view=client-setup') ); exit;
    }
    global $wpdb;
    $wpdb->update( $wpdb->prefix . 'sp_team', array( 'status' => 'inactive' ), array( 'id' => $member_id ) );
    delete_option( 'sp_wts_member_status_' . $member_id );
    delete_option( 'sp_wts_member_jur_' . $member_id );
    $wpdb->delete( $wpdb->prefix . 'sp_wts_onboarding', array( 'member_id' => $member_id ) );
    wp_redirect( home_url('/sp-app/?view=client-setup&deleted=1') ); exit;
}
