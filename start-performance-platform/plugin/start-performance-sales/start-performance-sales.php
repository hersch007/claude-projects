<?php
/*
 * Plugin Name: Start Performance — Sales Core
 * Description: Leads, pipeline, estimates, proposals, and contracts for the Start Performance Platform
 * Version:     1.1.9
 * Author:      Richard Brashear / Start Performance
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SP_SALES_VERSION',    '1.1.9' );
define( 'SP_SALES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// ── Boot ──────────────────────────────────────────────────────────────────────

add_action( 'plugins_loaded', 'sp_sales_boot', 20 );

function sp_sales_boot() {
    if ( ! function_exists( 'sp_register_view' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Start Performance — Sales Core</strong> requires the Start Performance core plugin.</p></div>';
        } );
        return;
    }
    sp_sales_register();
}

// ── Activation ────────────────────────────────────────────────────────────────

register_activation_hook( __FILE__, 'sp_sales_activate' );
add_action( 'sp_activate', 'sp_sales_create_tables' );

function sp_sales_activate() {
    sp_sales_create_tables();
}

function sp_sales_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Estimates
    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_estimates (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  number varchar(30) NOT NULL DEFAULT '',
  contact_id bigint(20) unsigned NOT NULL DEFAULT 0,
  company_id bigint(20) unsigned NOT NULL DEFAULT 0,
  title varchar(255) NOT NULL DEFAULT '',
  status varchar(30) NOT NULL DEFAULT 'Draft',
  tax_rate decimal(5,2) NOT NULL DEFAULT 0.00,
  discount decimal(10,2) NOT NULL DEFAULT 0.00,
  notes text,
  subtotal decimal(12,2) NOT NULL DEFAULT 0.00,
  total decimal(12,2) NOT NULL DEFAULT 0.00,
  valid_until date DEFAULT NULL,
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY status (status),
  KEY contact_id (contact_id),
  KEY company_id (company_id)
) $charset;" );

    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_estimate_items (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  estimate_id bigint(20) unsigned NOT NULL DEFAULT 0,
  sort_order int(11) NOT NULL DEFAULT 0,
  description varchar(500) NOT NULL DEFAULT '',
  qty decimal(10,2) NOT NULL DEFAULT 1.00,
  unit_price decimal(12,2) NOT NULL DEFAULT 0.00,
  line_total decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY  (id),
  KEY estimate_id (estimate_id)
) $charset;" );

    // Proposals
    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_proposals (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  number varchar(30) NOT NULL DEFAULT '',
  contact_id bigint(20) unsigned NOT NULL DEFAULT 0,
  company_id bigint(20) unsigned NOT NULL DEFAULT 0,
  title varchar(255) NOT NULL DEFAULT '',
  status varchar(30) NOT NULL DEFAULT 'Draft',
  overview text,
  scope text,
  deliverables text,
  timeline text,
  pricing_notes text,
  total_value decimal(12,2) NOT NULL DEFAULT 0.00,
  valid_until date DEFAULT NULL,
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY status (status),
  KEY contact_id (contact_id),
  KEY company_id (company_id)
) $charset;" );

    // Contracts
    dbDelta( "CREATE TABLE {$wpdb->prefix}sp_contracts (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  number varchar(30) NOT NULL DEFAULT '',
  contact_id bigint(20) unsigned NOT NULL DEFAULT 0,
  company_id bigint(20) unsigned NOT NULL DEFAULT 0,
  title varchar(255) NOT NULL DEFAULT '',
  status varchar(30) NOT NULL DEFAULT 'Draft',
  content longtext,
  value decimal(12,2) NOT NULL DEFAULT 0.00,
  start_date date DEFAULT NULL,
  end_date date DEFAULT NULL,
  signed_at date DEFAULT NULL,
  signed_by_name varchar(255) NOT NULL DEFAULT '',
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY status (status),
  KEY contact_id (contact_id),
  KEY company_id (company_id)
) $charset;" );
}

// ── Registration ──────────────────────────────────────────────────────────────

function sp_sales_register() {
    sp_sales_create_tables();

    sp_register_addon( 'sp-sales', array(
        'name'        => 'Sales Core',
        'version'     => SP_SALES_VERSION,
        'description' => 'Create and track estimates, proposals, and contracts linked to contacts and companies.',
        'icon'        => '<path fill="currentColor" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill="currentColor" fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>',
        'plugin_file' => plugin_basename( __FILE__ ),
        'core_slot'   => 'sales-core',
    ) );

    sp_register_view( 'leads',            SP_SALES_PLUGIN_DIR . 'templates/views/sales-leads.php' );
    sp_register_view( 'sales-estimates',  SP_SALES_PLUGIN_DIR . 'templates/views/sales-estimates.php' );
    sp_register_view( 'sales-proposals',  SP_SALES_PLUGIN_DIR . 'templates/views/sales-proposals.php' );
    sp_register_view( 'sales-contracts',  SP_SALES_PLUGIN_DIR . 'templates/views/sales-contracts.php' );

    add_filter( 'sp_nav_items',     'sp_sales_nav_items' );
    add_filter( 'sp_allowed_views', 'sp_sales_allowed_views' );

    add_action( 'sp_post_handler_lead',       'sp_sales_save_lead' );
    add_action( 'sp_delete_handler_lead',     'sp_sales_delete_lead' );
    add_action( 'sp_post_handler_estimate',   'sp_sales_save_estimate' );
    add_action( 'sp_delete_handler_estimate', 'sp_sales_delete_estimate' );
    add_action( 'sp_post_handler_proposal',   'sp_sales_save_proposal' );
    add_action( 'sp_delete_handler_proposal', 'sp_sales_delete_proposal' );
    add_action( 'sp_post_handler_contract',   'sp_sales_save_contract' );
    add_action( 'sp_delete_handler_contract', 'sp_sales_delete_contract' );

    // Status change actions
    add_action( 'sp_post_handler_estimate_status', 'sp_sales_update_estimate_status' );
    add_action( 'sp_post_handler_proposal_status', 'sp_sales_update_proposal_status' );
    add_action( 'sp_post_handler_contract_status', 'sp_sales_update_contract_status' );

    // Conversion actions
    add_action( 'sp_post_handler_estimate_to_proposal', 'sp_sales_convert_estimate_to_proposal' );
    add_action( 'sp_post_handler_proposal_to_contract', 'sp_sales_convert_proposal_to_contract' );


    // Dashboard stats
    add_action( 'sp_dashboard_after_stats', 'sp_sales_dashboard_stats' );
}

// ── Nav ───────────────────────────────────────────────────────────────────────

function sp_sales_nav_items( $items ) {
    $result = array();
    foreach ( $items as $item ) {
        $result[] = $item;
        if ( ! empty( $item['section'] ) && ! empty( $item['section_id'] ) && $item['section_id'] === 'sales-core' ) {
            $result[] = array( 'view' => 'leads', 'label' => 'Leads',
                'icon' => '<path fill="currentColor" d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>' );
            $result[] = array( 'view' => 'sales-estimates', 'label' => 'Estimates',
                'icon' => '<path fill="currentColor" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill="currentColor" fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>' );
            $result[] = array( 'view' => 'sales-proposals', 'label' => 'Proposals',
                'icon' => '<path fill="currentColor" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"/>' );
            $result[] = array( 'view' => 'sales-contracts', 'label' => 'Contracts',
                'icon' => '<path fill="currentColor" d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zm-2.207 2.207L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>' );
        }
    }
    return $result;
}

function sp_sales_allowed_views( $views ) {
    $views[] = 'leads';
    $views[] = 'sales-estimates';
    $views[] = 'sales-proposals';
    $views[] = 'sales-contracts';
    return $views;
}

// ── Lead handlers ─────────────────────────────────────────────────────────────

function sp_sales_save_lead( $id ) {
    global $wpdb;
    $data = array(
        'contact_id' => (int) ( isset( $_POST['contact_id'] ) ? $_POST['contact_id'] : 0 ),
        'company_id' => (int) ( isset( $_POST['company_id'] ) ? $_POST['company_id'] : 0 ),
        'source'     => sanitize_text_field(     isset( $_POST['source'] ) ? $_POST['source'] : '' ),
        'status'     => sanitize_key(            isset( $_POST['status'] ) ? $_POST['status'] : 'new' ),
        'score'      => min( 100, max( 0, (int) ( isset( $_POST['score'] ) ? $_POST['score'] : 0 ) ) ),
        'notes'      => sanitize_textarea_field( isset( $_POST['notes'] )  ? $_POST['notes']  : '' ),
    );
    if ( $id ) {
        $wpdb->update( $wpdb->prefix . 'sp_leads', $data, array( 'id' => $id ) );
        sp_log_activity( 'lead', $id, 'updated', 'Lead updated' );
    } else {
        $data['created_at'] = current_time( 'mysql' );
        $wpdb->insert( $wpdb->prefix . 'sp_leads', $data );
        $id = $wpdb->insert_id;
        sp_log_activity( 'lead', $id, 'created', 'Lead created' );
    }
    wp_redirect( home_url( '/sp-app/?view=leads&saved=1' ) ); exit;
}

function sp_sales_delete_lead( $id ) {
    global $wpdb;
    $wpdb->delete( $wpdb->prefix . 'sp_leads', array( 'id' => $id ) );
    wp_redirect( home_url( '/sp-app/?view=leads' ) ); exit;
}

// ── Number generator ──────────────────────────────────────────────────────────

function sp_sales_next_number( $prefix ) {
    $key  = 'sp_sales_seq_' . $prefix;
    $next = (int) get_option( $key, 0 ) + 1;
    update_option( $key, $next );
    return strtoupper( $prefix ) . '-' . str_pad( $next, 4, '0', STR_PAD_LEFT );
}

// ── Estimate handlers ─────────────────────────────────────────────────────────

function sp_sales_save_estimate( $id ) {
    global $wpdb;
    $member = sp_get_current_team_member();
    $by     = $member ? (int) $member->id : 0;

    $descs  = (array) ( $_POST['item_desc']  ?? array() );
    $qtys   = (array) ( $_POST['item_qty']   ?? array() );
    $prices = (array) ( $_POST['item_price'] ?? array() );

    $subtotal = 0.00;
    $line_items = array();
    foreach ( $descs as $i => $desc ) {
        $desc = sanitize_text_field( $desc );
        if ( $desc === '' ) continue;
        $qty   = (float) ( $qtys[$i]   ?? 1 );
        $price = (float) ( $prices[$i] ?? 0 );
        $line  = round( $qty * $price, 2 );
        $subtotal += $line;
        $line_items[] = array( 'sort_order' => $i + 1, 'description' => $desc, 'qty' => $qty, 'unit_price' => $price, 'line_total' => $line );
    }

    $tax_rate = (float) ( $_POST['tax_rate'] ?? 0 );
    $discount = (float) ( $_POST['discount'] ?? 0 );
    $tax      = round( ( $subtotal - $discount ) * $tax_rate / 100, 2 );
    $total    = round( $subtotal - $discount + $tax, 2 );

    $data = array(
        'contact_id'  => (int) ( $_POST['contact_id'] ?? 0 ),
        'company_id'  => (int) ( $_POST['company_id'] ?? 0 ),
        'title'       => sanitize_text_field( $_POST['title'] ?? '' ),
        'status'      => sanitize_key( $_POST['status'] ?? 'Draft' ),
        'tax_rate'    => $tax_rate,
        'discount'    => $discount,
        'notes'       => sanitize_textarea_field( $_POST['notes'] ?? '' ),
        'subtotal'    => round( $subtotal, 2 ),
        'total'       => $total,
        'valid_until' => sanitize_text_field( $_POST['valid_until'] ?? '' ) ?: null,
        'updated_at'  => current_time( 'mysql' ),
    );

    if ( $id ) {
        $wpdb->update( $wpdb->prefix . 'sp_estimates', $data, array( 'id' => $id ) );
        $wpdb->delete( $wpdb->prefix . 'sp_estimate_items', array( 'estimate_id' => $id ) );
    } else {
        $data['number']     = sp_sales_next_number( 'EST' );
        $data['created_by'] = $by;
        $data['created_at'] = current_time( 'mysql' );
        $wpdb->insert( $wpdb->prefix . 'sp_estimates', $data );
        $id = $wpdb->insert_id;
    }

    foreach ( $line_items as $li ) {
        $li['estimate_id'] = $id;
        $wpdb->insert( $wpdb->prefix . 'sp_estimate_items', $li );
    }

    if ( function_exists( 'sp_log_activity' ) ) {
        sp_log_activity( 'estimate', $id, 'updated', $data['title'], $by );
    }

    wp_redirect( home_url( "/sp-app/?view=sales-estimates&action=view&id={$id}&saved=1" ) ); exit;
}

function sp_sales_delete_estimate( $id ) {
    global $wpdb;
    $wpdb->delete( $wpdb->prefix . 'sp_estimate_items', array( 'estimate_id' => $id ) );
    $wpdb->delete( $wpdb->prefix . 'sp_estimates',      array( 'id' => $id ) );
    wp_redirect( home_url( '/sp-app/?view=sales-estimates&deleted=1' ) ); exit;
}

function sp_sales_update_estimate_status( $id ) {
    global $wpdb;
    $allowed = array( 'Draft', 'Sent', 'Accepted', 'Declined', 'Expired' );
    $status  = sanitize_text_field( $_POST['status'] ?? '' );
    if ( ! in_array( $status, $allowed ) ) { wp_redirect( home_url( '/sp-app/?view=sales-estimates' ) ); exit; }
    $wpdb->update( $wpdb->prefix . 'sp_estimates',
        array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ),
        array( 'id' => $id )
    );
    wp_redirect( home_url( "/sp-app/?view=sales-estimates&action=view&id={$id}&saved=1" ) ); exit;
}

// ── Proposal handlers ─────────────────────────────────────────────────────────

function sp_sales_save_proposal( $id ) {
    global $wpdb;
    $member = sp_get_current_team_member();
    $by     = $member ? (int) $member->id : 0;

    $data = array(
        'contact_id'    => (int) ( $_POST['contact_id'] ?? 0 ),
        'company_id'    => (int) ( $_POST['company_id'] ?? 0 ),
        'title'         => sanitize_text_field( $_POST['title'] ?? '' ),
        'status'        => sanitize_key( $_POST['status'] ?? 'Draft' ),
        'overview'      => wp_kses_post( $_POST['overview']      ?? '' ),
        'scope'         => wp_kses_post( $_POST['scope']         ?? '' ),
        'deliverables'  => wp_kses_post( $_POST['deliverables']  ?? '' ),
        'timeline'      => wp_kses_post( $_POST['timeline']      ?? '' ),
        'pricing_notes' => wp_kses_post( $_POST['pricing_notes'] ?? '' ),
        'total_value'   => (float) ( $_POST['total_value'] ?? 0 ),
        'valid_until'   => sanitize_text_field( $_POST['valid_until'] ?? '' ) ?: null,
        'updated_at'    => current_time( 'mysql' ),
    );

    if ( $id ) {
        $wpdb->update( $wpdb->prefix . 'sp_proposals', $data, array( 'id' => $id ) );
    } else {
        $data['number']     = sp_sales_next_number( 'PROP' );
        $data['created_by'] = $by;
        $data['created_at'] = current_time( 'mysql' );
        $wpdb->insert( $wpdb->prefix . 'sp_proposals', $data );
        $id = $wpdb->insert_id;
    }
    wp_redirect( home_url( "/sp-app/?view=sales-proposals&action=view&id={$id}&saved=1" ) ); exit;
}

function sp_sales_delete_proposal( $id ) {
    global $wpdb;
    $wpdb->delete( $wpdb->prefix . 'sp_proposals', array( 'id' => $id ) );
    wp_redirect( home_url( '/sp-app/?view=sales-proposals&deleted=1' ) ); exit;
}

function sp_sales_update_proposal_status( $id ) {
    global $wpdb;
    $allowed = array( 'Draft', 'Sent', 'Under Review', 'Accepted', 'Declined' );
    $status  = sanitize_text_field( $_POST['status'] ?? '' );
    if ( ! in_array( $status, $allowed ) ) { wp_redirect( home_url( '/sp-app/?view=sales-proposals' ) ); exit; }
    $wpdb->update( $wpdb->prefix . 'sp_proposals',
        array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ),
        array( 'id' => $id )
    );
    wp_redirect( home_url( "/sp-app/?view=sales-proposals&action=view&id={$id}&saved=1" ) ); exit;
}

// ── Contract handlers ─────────────────────────────────────────────────────────

function sp_sales_save_contract( $id ) {
    global $wpdb;
    $member = sp_get_current_team_member();
    $by     = $member ? (int) $member->id : 0;

    $data = array(
        'contact_id'    => (int) ( $_POST['contact_id'] ?? 0 ),
        'company_id'    => (int) ( $_POST['company_id'] ?? 0 ),
        'title'         => sanitize_text_field( $_POST['title'] ?? '' ),
        'status'        => sanitize_key( $_POST['status'] ?? 'Draft' ),
        'content'       => wp_kses_post( $_POST['content'] ?? '' ),
        'value'         => (float) ( $_POST['value'] ?? 0 ),
        'start_date'    => sanitize_text_field( $_POST['start_date'] ?? '' ) ?: null,
        'end_date'      => sanitize_text_field( $_POST['end_date']   ?? '' ) ?: null,
        'updated_at'    => current_time( 'mysql' ),
    );

    if ( $id ) {
        $wpdb->update( $wpdb->prefix . 'sp_contracts', $data, array( 'id' => $id ) );
    } else {
        $data['number']     = sp_sales_next_number( 'CTR' );
        $data['created_by'] = $by;
        $data['created_at'] = current_time( 'mysql' );
        $wpdb->insert( $wpdb->prefix . 'sp_contracts', $data );
        $id = $wpdb->insert_id;
    }
    wp_redirect( home_url( "/sp-app/?view=sales-contracts&action=view&id={$id}&saved=1" ) ); exit;
}

function sp_sales_delete_contract( $id ) {
    global $wpdb;
    $wpdb->delete( $wpdb->prefix . 'sp_contracts', array( 'id' => $id ) );
    wp_redirect( home_url( '/sp-app/?view=sales-contracts&deleted=1' ) ); exit;
}

function sp_sales_update_contract_status( $id ) {
    global $wpdb;
    $allowed     = array( 'Draft', 'Sent', 'Active', 'Expired', 'Cancelled' );
    $status      = sanitize_text_field( $_POST['status'] ?? '' );
    if ( ! in_array( $status, $allowed ) ) { wp_redirect( home_url( '/sp-app/?view=sales-contracts' ) ); exit; }
    $signed_at   = sanitize_text_field( $_POST['signed_at']   ?? '' ) ?: null;
    $signed_name = sanitize_text_field( $_POST['signed_by_name'] ?? '' );
    $wpdb->update( $wpdb->prefix . 'sp_contracts',
        array( 'status' => $status, 'signed_at' => $signed_at, 'signed_by_name' => $signed_name, 'updated_at' => current_time('mysql') ),
        array( 'id' => $id )
    );
    wp_redirect( home_url( "/sp-app/?view=sales-contracts&action=view&id={$id}&saved=1" ) ); exit;
}

// ── Dashboard widget ──────────────────────────────────────────────────────────

function sp_sales_dashboard_stats() {
    if ( sp_is_view_hidden( 'sales-estimates' ) && sp_is_view_hidden( 'sales-proposals' ) && sp_is_view_hidden( 'sales-contracts' ) ) return;
    global $wpdb;
    $has_est  = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_estimates'" );
    $has_prop = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_proposals'" );
    $has_ctr  = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sp_contracts'" );
    if ( ! $has_est ) return;

    $open_est   = $has_est  ? (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sp_estimates  WHERE status IN ('Draft','Sent')") : 0;
    $open_prop  = $has_prop ? (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sp_proposals  WHERE status IN ('Draft','Sent')") : 0;
    $active_ctr = $has_ctr  ? (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sp_contracts  WHERE status='Active'") : 0;
    ?>
    <div class="sp-stats sp-stats-3">
        <div class="sp-stat-card">
            <div class="sp-stat-value"><?php echo $open_est; ?></div>
            <div class="sp-stat-label">Open Estimates</div>
        </div>
        <div class="sp-stat-card">
            <div class="sp-stat-value"><?php echo $open_prop; ?></div>
            <div class="sp-stat-label">Open Proposals</div>
        </div>
        <div class="sp-stat-card">
            <div class="sp-stat-value"><?php echo $active_ctr; ?></div>
            <div class="sp-stat-label">Active Contracts</div>
        </div>
    </div>
    <?php
}

// ── Estimate → Proposal conversion ───────────────────────────────────────────
function sp_sales_convert_estimate_to_proposal( $id ) {
    global $wpdb;
    if ( ! sp_is_admin_member() && ! sp_is_super_admin() ) wp_die( 'Unauthorized' );
    $est = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_estimates WHERE id=%d", $id ) );
    if ( ! $est ) wp_die( 'Estimate not found' );
    $member = sp_get_current_team_member();
    $by     = $member ? (int) $member->id : 0;
    $now    = current_time( 'mysql' );
    $wpdb->insert( $wpdb->prefix . 'sp_proposals', array(
        'number'        => sp_sales_next_number( 'PROP' ),
        'contact_id'    => $est->contact_id,
        'company_id'    => $est->company_id,
        'title'         => $est->title,
        'status'        => 'Draft',
        'pricing_notes' => $est->notes ?: '',
        'total_value'   => $est->total,
        'valid_until'   => $est->valid_until,
        'created_by'    => $by,
        'created_at'    => $now,
        'updated_at'    => $now,
    ) );
    $new_id = $wpdb->insert_id;
    wp_redirect( home_url( "/sp-app/?view=sales-proposals&action=view&id={$new_id}&converted=estimate" ) ); exit;
}

// ── Proposal → Contract conversion ───────────────────────────────────────────
function sp_sales_convert_proposal_to_contract( $id ) {
    global $wpdb;
    if ( ! sp_is_admin_member() && ! sp_is_super_admin() ) wp_die( 'Unauthorized' );
    $prop = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_proposals WHERE id=%d", $id ) );
    if ( ! $prop ) wp_die( 'Proposal not found' );
    $member = sp_get_current_team_member();
    $by     = $member ? (int) $member->id : 0;
    $now    = current_time( 'mysql' );
    $parts  = array_filter( array(
        $prop->overview      ? '<h2>Overview</h2>' . $prop->overview           : '',
        $prop->scope         ? '<h2>Scope of Work</h2>' . $prop->scope         : '',
        $prop->deliverables  ? '<h2>Deliverables</h2>' . $prop->deliverables   : '',
        $prop->timeline      ? '<h2>Timeline</h2>' . $prop->timeline           : '',
        $prop->pricing_notes ? '<h2>Pricing</h2>' . $prop->pricing_notes       : '',
    ) );
    $wpdb->insert( $wpdb->prefix . 'sp_contracts', array(
        'number'     => sp_sales_next_number( 'CTR' ),
        'contact_id' => $prop->contact_id,
        'company_id' => $prop->company_id,
        'title'      => $prop->title,
        'status'     => 'Draft',
        'content'    => implode( "\n\n", $parts ),
        'value'      => $prop->total_value,
        'end_date'   => $prop->valid_until,
        'created_by' => $by,
        'created_at' => $now,
        'updated_at' => $now,
    ) );
    $new_id = $wpdb->insert_id;
    wp_redirect( home_url( "/sp-app/?view=sales-contracts&action=view&id={$new_id}&converted=proposal" ) ); exit;
}
