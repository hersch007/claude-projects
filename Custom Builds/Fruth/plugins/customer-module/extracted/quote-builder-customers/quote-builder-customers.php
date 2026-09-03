<?php
/**
 * Plugin Name: Quote Builder — Customer Database
 * Description: Adds a reusable customer database, search/autofill in the Quote Builder, and quote history to the Quote Builder plugin. Requires Quote Builder Core (sales-quote-system). Shortcode: [sqs_customer_database] (Customers).
 * Version:     1.3.2
 * Author:      Start Advertising | RH Brashear
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Keep this in sync with the "Version:" line in the header comment above --
// tests/check-plugin-versions.js enforces this automatically on every run.
define( 'QBC_VERSION', '1.3.2' );

/**
 * Boot only when the core plugin is present.
 */
add_action( 'plugins_loaded', 'qbc_boot', 20 );
function qbc_boot() {
    if ( ! defined( 'SQS_VERSION' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>Quote Builder — Customer Database</strong> requires the <strong>Quote Builder Core</strong> plugin to be active.</p></div>';
        } );
        return;
    }

    // ── Hook into Core's extension points (same pattern the Print Module uses) ──
    add_action( 'sqs_quote_info_extra_fields', 'qbc_extra_info_fields' );
    add_action( 'sqs_quote_saved', 'qbc_on_quote_saved', 10, 2 );
    add_action( 'sqs_toolbar_extra_buttons', 'qbc_toolbar_button' );
    add_action( 'sqs_portal_extra_tiles', 'qbc_portal_tile' );
    add_action( 'wp_head', 'qbc_portal_tile_styles' );
    add_action( 'admin_init', 'qbc_maybe_upgrade' );
}

/**
 * register_activation_hook() only fires on explicit activate/deactivate --
 * it will not re-run when a new plugin zip is simply uploaded over the old
 * one while already active. This runs dbDelta() again (safe/idempotent --
 * it only adds missing columns, never drops data) whenever QBC_VERSION
 * changes, so schema additions like this one take effect without requiring
 * a manual deactivate/reactivate.
 */
function qbc_maybe_upgrade() {
    if ( get_option( 'qbc_schema_version' ) !== QBC_VERSION ) {
        qbc_activate();
        update_option( 'qbc_schema_version', QBC_VERSION );
    }
}

// ── Portal: third tile (Customers), rendered via Core's one new extension
//    point, sqs_portal_extra_tiles ──────────────────────────────────────────
function qbc_portal_tile() {
    ?>
    <a href="<?php echo esc_url( qbc_customer_database_url() ); ?>" class="sqs-portal-btn customers">
        <div class="sqs-portal-btn-icon">&#128101;</div>
        <div><span class="sqs-portal-btn-label">Customers</span><span class="sqs-portal-btn-desc">Search, add &amp; review customer records</span></div>
    </a>
    <?php
}

// Core's Portal CSS defines .sqs-portal-btn.sales and .sqs-portal-btn.admin
// but has no "customers" color variant -- adding one here (purely additive,
// a new selector) rather than touching Core's existing CSS block.
function qbc_portal_tile_styles() {
    if ( is_admin() ) return;
    ?>
    <style>
    .sqs-portal-btn.customers{background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-color:#bbf7d0;color:#14532d;box-shadow:0 4px 16px rgba(21,128,61,.10)}
    .sqs-portal-btn.customers:hover{box-shadow:0 8px 24px rgba(21,128,61,.18);border-color:#15803d}
    .sqs-portal-btn.customers .sqs-portal-btn-icon{background:#15803d;color:white}
    </style>
    <?php
}

/**
 * Resolves the Customer Database page URL. Deliberately a local copy of
 * Core's shortcode-lookup-with-fallback pattern (sqs_pricing_calculator_
 * shortcode_page_url()) rather than a call into Core -- this is a small,
 * generic, non-business-logic utility, and duplicating it keeps this module
 * fully self-contained/removable, matching how each plugin in this project
 * is independent.
 */
function qbc_customer_database_url() {
    global $wpdb;
    $page_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = 'page' AND post_status = 'publish'
             AND post_content LIKE %s
             LIMIT 1",
            '%[' . $wpdb->esc_like( 'sqs_customer_database' ) . ']%'
        )
    );
    if ( $page_id ) {
        return get_permalink( (int) $page_id );
    }
    // home_url() already resolves to this site's /fruth/ base -- do not
    // repeat "/fruth" here or this fallback doubles up to /fruth/fruth/customers/.
    return home_url( '/customers/' );
}

// ── Toolbar: Customers button (mirrors the Print Module's own toolbar button) ──
function qbc_toolbar_button() {
    echo '<a href="' . esc_url( qbc_customer_database_url() ) . '" target="_blank" rel="noopener" class="sqs-bar-btn">&#128101; Customers</a>';
}

// ── Table names ────────────────────────────────────────────────────────────
function qbc_customers_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'sqs_customers';
}

function qbc_customer_quotes_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'sqs_customer_quotes';
}

// Core's own quotes table -- this module deliberately reads from it (for the
// quote_number/status/revision lookups needed by "quote history") but never
// writes to it or alters its schema. Table name mirrored locally rather than
// calling Core's sqs_pricing_calculator_quotes_table_name(), keeping this
// module's only real dependency on Core the documented hooks + SQS_VERSION,
// not Core's internal function names.
function qbc_core_quotes_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'sqs_quotes';
}

// ── Activation: create the two tables this module owns ─────────────────────
function qbc_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $customers_table = qbc_customers_table_name();
    // "address" holds the street address line only (kept as its original
    // column name/type -- dbDelta doesn't reliably alter an existing
    // column's type, only add new ones); city/state/zip/assigned_rep are
    // new columns added via dbDelta's safe ADD COLUMN behavior.
    $customers_sql = "CREATE TABLE $customers_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        company_name varchar(191) NOT NULL DEFAULT '',
        contact_name varchar(191) NOT NULL DEFAULT '',
        email varchar(191) NOT NULL DEFAULT '',
        phone varchar(60) NOT NULL DEFAULT '',
        address text NOT NULL,
        city varchar(100) NOT NULL DEFAULT '',
        state varchar(50) NOT NULL DEFAULT '',
        zip varchar(20) NOT NULL DEFAULT '',
        assigned_rep varchar(191) NOT NULL DEFAULT '',
        notes text NOT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY company_name (company_name),
        KEY contact_name (contact_name),
        KEY assigned_rep (assigned_rep)
    ) $charset_collate;";
    dbDelta( $customers_sql );

    // Pure linking table -- deliberately does NOT alter Core's wp_sqs_quotes
    // schema. Keyed on quote_number (not just quote_id) because Core creates
    // a new row with a new id for every quote *revision* while keeping the
    // same quote_number, so linking on quote_id alone would silently drop
    // older/newer revisions from a customer's history.
    $customer_quotes_table = qbc_customer_quotes_table_name();
    $customer_quotes_sql = "CREATE TABLE $customer_quotes_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        customer_id bigint(20) unsigned NOT NULL,
        quote_id bigint(20) unsigned NOT NULL,
        quote_number varchar(40) NOT NULL DEFAULT '',
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY customer_quote (customer_id, quote_id),
        KEY customer_id (customer_id),
        KEY quote_number (quote_number)
    ) $charset_collate;";
    dbDelta( $customer_quotes_sql );
}
register_activation_hook( __FILE__, 'qbc_activate' );

// ── Session + access control ────────────────────────────────────────────────
// Deliberately self-contained rather than calling into Core's private
// functions -- the stable contract between these plugins is SQS_VERSION plus
// the documented do_action hooks, nothing else. Both Core and this module
// independently guard session_start() with session_id(), so having two
// idempotent copies is safe: whichever runs first actually starts it.
function qbc_maybe_start_session() {
    if ( ! session_id() && ! headers_sent() ) {
        session_start();
    }
}

/**
 * Anyone already authenticated into any part of the Quote Builder system
 * (sales session, admin/editor session, or a real WordPress admin) can use
 * the customer database -- sales reps need it while building quotes, and
 * pricing admins may also manage customer records.
 */
function qbc_user_can_access() {
    qbc_maybe_start_session();
    $sales_logged_in    = ! empty( $_SESSION['sqs_calc_sales_logged_in'] ) && true === $_SESSION['sqs_calc_sales_logged_in'];
    $frontend_logged_in = ! empty( $_SESSION['sqs_calc_frontend_logged_in'] ) && true === $_SESSION['sqs_calc_frontend_logged_in'];
    return $sales_logged_in || $frontend_logged_in || current_user_can( 'manage_options' );
}

// ── Sanitization helper ──────────────────────────────────────────────────────
function qbc_sanitize_customer_fields( $data ) {
    return array(
        'company_name'  => isset( $data['companyName'] ) ? sanitize_text_field( $data['companyName'] ) : '',
        'contact_name'  => isset( $data['contactName'] ) ? sanitize_text_field( $data['contactName'] ) : '',
        'email'         => isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '',
        'phone'         => isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '',
        'address'       => isset( $data['address'] ) ? sanitize_textarea_field( $data['address'] ) : '',
        'city'          => isset( $data['city'] ) ? sanitize_text_field( $data['city'] ) : '',
        'state'         => isset( $data['state'] ) ? sanitize_text_field( $data['state'] ) : '',
        'zip'           => isset( $data['zip'] ) ? sanitize_text_field( $data['zip'] ) : '',
        'assigned_rep'  => isset( $data['assignedRep'] ) ? sanitize_text_field( $data['assignedRep'] ) : '',
        'notes'         => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : '',
    );
}

// ── AJAX: list customers (with a per-row linked-quote count) ────────────────
add_action( 'wp_ajax_sqs_list_customers',        'qbc_ajax_list_customers' );
add_action( 'wp_ajax_nopriv_sqs_list_customers', 'qbc_ajax_list_customers' );
function qbc_ajax_list_customers() {
    if ( ! qbc_user_can_access() ) {
        wp_send_json_error( array( 'message' => 'Not authorized.' ), 403 );
    }
    check_ajax_referer( 'sqs_quote_actions', 'nonce' );

    global $wpdb;
    $customers_table = qbc_customers_table_name();
    $links_table     = qbc_customer_quotes_table_name();

    $search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

    if ( $search !== '' ) {
        $like = '%' . $wpdb->esc_like( $search ) . '%';
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.*, (SELECT COUNT(*) FROM $links_table l WHERE l.customer_id = c.id) AS quote_count
                 FROM $customers_table c
                 WHERE c.company_name LIKE %s OR c.contact_name LIKE %s OR c.email LIKE %s OR c.assigned_rep LIKE %s
                 ORDER BY c.company_name ASC LIMIT 200",
                $like, $like, $like, $like
            ),
            ARRAY_A
        );
    } else {
        $rows = $wpdb->get_results(
            "SELECT c.*, (SELECT COUNT(*) FROM $links_table l WHERE l.customer_id = c.id) AS quote_count
             FROM $customers_table c
             ORDER BY c.company_name ASC LIMIT 200",
            ARRAY_A
        );
    }

    wp_send_json_success( array( 'customers' => $rows ? $rows : array() ) );
}

// ── AJAX: get a single customer ──────────────────────────────────────────────
add_action( 'wp_ajax_sqs_get_customer',        'qbc_ajax_get_customer' );
add_action( 'wp_ajax_nopriv_sqs_get_customer', 'qbc_ajax_get_customer' );
function qbc_ajax_get_customer() {
    if ( ! qbc_user_can_access() ) {
        wp_send_json_error( array( 'message' => 'Not authorized.' ), 403 );
    }
    check_ajax_referer( 'sqs_quote_actions', 'nonce' );

    $customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
    if ( ! $customer_id ) {
        wp_send_json_error( array( 'message' => 'Missing customer ID.' ), 400 );
    }

    global $wpdb;
    $table = qbc_customers_table_name();
    $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $customer_id ), ARRAY_A );
    if ( ! $row ) {
        wp_send_json_error( array( 'message' => 'Customer not found.' ), 404 );
    }
    wp_send_json_success( array( 'customer' => $row ) );
}

// ── AJAX: add or edit a customer ─────────────────────────────────────────────
add_action( 'wp_ajax_sqs_save_customer',        'qbc_ajax_save_customer' );
add_action( 'wp_ajax_nopriv_sqs_save_customer', 'qbc_ajax_save_customer' );
function qbc_ajax_save_customer() {
    if ( ! qbc_user_can_access() ) {
        wp_send_json_error( array( 'message' => 'Not authorized.' ), 403 );
    }
    check_ajax_referer( 'sqs_quote_actions', 'nonce' );

    $customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
    $fields = qbc_sanitize_customer_fields( wp_unslash( $_POST ) );

    if ( '' === $fields['company_name'] && '' === $fields['contact_name'] ) {
        wp_send_json_error( array( 'message' => 'Company name or contact name is required.' ), 400 );
    }

    global $wpdb;
    $table = qbc_customers_table_name();
    $now = current_time( 'mysql' );

    if ( $customer_id ) {
        $fields['updated_at'] = $now;
        $wpdb->update( $table, $fields, array( 'id' => $customer_id ) );
    } else {
        $fields['created_at'] = $now;
        $fields['updated_at'] = $now;
        $wpdb->insert( $table, $fields );
        $customer_id = absint( $wpdb->insert_id );
    }

    wp_send_json_success( array( 'customer_id' => $customer_id, 'message' => 'Customer saved.' ) );
}

// ── AJAX: delete a customer (blocked if any quotes are linked) ──────────────
add_action( 'wp_ajax_sqs_delete_customer',        'qbc_ajax_delete_customer' );
add_action( 'wp_ajax_nopriv_sqs_delete_customer', 'qbc_ajax_delete_customer' );
function qbc_ajax_delete_customer() {
    if ( ! qbc_user_can_access() ) {
        wp_send_json_error( array( 'message' => 'Not authorized.' ), 403 );
    }
    check_ajax_referer( 'sqs_quote_actions', 'nonce' );

    $customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
    if ( ! $customer_id ) {
        wp_send_json_error( array( 'message' => 'Missing customer ID.' ), 400 );
    }

    global $wpdb;
    $links_table = qbc_customer_quotes_table_name();
    $linked_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $links_table WHERE customer_id = %d", $customer_id ) );
    if ( $linked_count > 0 ) {
        wp_send_json_error( array(
            'message' => sprintf(
                'This customer has %d linked quote%s and cannot be deleted.',
                $linked_count,
                1 === $linked_count ? '' : 's'
            ),
        ), 409 );
    }

    $table = qbc_customers_table_name();
    $wpdb->delete( $table, array( 'id' => $customer_id ) );

    wp_send_json_success( array( 'message' => 'Customer deleted.' ) );
}

// ── Links a saved quote to a customer (fires on every save, including every
//    revision) ─────────────────────────────────────────────────────────────
// $payload here does NOT carry quote_number (Core only sets that on its own
// local $row array before inserting, never merges it back into $payload),
// so quote_number is looked up directly from Core's quotes table using the
// $quote_id the hook always reliably provides.
function qbc_on_quote_saved( $quote_id, $payload ) {
    $quote_id = absint( $quote_id );
    if ( ! $quote_id ) {
        return;
    }

    global $wpdb;
    $links_table = qbc_customer_quotes_table_name();

    // Clear any prior link for this exact row first -- handles a user
    // correcting which customer a quote belongs to on a re-save.
    $wpdb->delete( $links_table, array( 'quote_id' => $quote_id ) );

    $meta = ( isset( $payload['meta'] ) && is_array( $payload['meta'] ) ) ? $payload['meta'] : array();
    $customer_id = isset( $meta['customerId'] ) ? absint( $meta['customerId'] ) : 0;
    if ( ! $customer_id ) {
        return; // No customer selected for this quote -- nothing to link.
    }

    $quotes_table = qbc_core_quotes_table_name();
    $quote_number = $wpdb->get_var( $wpdb->prepare( "SELECT quote_number FROM $quotes_table WHERE id = %d", $quote_id ) );

    $wpdb->insert( $links_table, array(
        'customer_id'  => $customer_id,
        'quote_id'     => $quote_id,
        'quote_number' => $quote_number ? $quote_number : '',
        'created_at'   => current_time( 'mysql' ),
    ) );
}

// ── AJAX: a customer's linked quote history, newest first ──────────────────
add_action( 'wp_ajax_sqs_get_customer_quotes',        'qbc_ajax_get_customer_quotes' );
add_action( 'wp_ajax_nopriv_sqs_get_customer_quotes', 'qbc_ajax_get_customer_quotes' );
function qbc_ajax_get_customer_quotes() {
    if ( ! qbc_user_can_access() ) {
        wp_send_json_error( array( 'message' => 'Not authorized.' ), 403 );
    }
    check_ajax_referer( 'sqs_quote_actions', 'nonce' );

    $customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
    if ( ! $customer_id ) {
        wp_send_json_error( array( 'message' => 'Missing customer ID.' ), 400 );
    }

    global $wpdb;
    $links_table  = qbc_customer_quotes_table_name();
    $quotes_table = qbc_core_quotes_table_name();

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT q.quote_number, q.revision_label, q.status, q.product_type, q.total_sales, q.updated_at
             FROM $links_table l
             INNER JOIN $quotes_table q ON q.id = l.quote_id
             WHERE l.customer_id = %d
             ORDER BY q.quote_number DESC, q.revision_number DESC",
            $customer_id
        ),
        ARRAY_A
    );

    wp_send_json_success( array( 'quotes' => $rows ? $rows : array() ) );
}

// ── AJAX: lightweight search used by the Quote Builder autocomplete ────────
add_action( 'wp_ajax_sqs_search_customers',        'qbc_ajax_search_customers' );
add_action( 'wp_ajax_nopriv_sqs_search_customers', 'qbc_ajax_search_customers' );
function qbc_ajax_search_customers() {
    if ( ! qbc_user_can_access() ) {
        wp_send_json_error( array( 'message' => 'Not authorized.' ), 403 );
    }
    check_ajax_referer( 'sqs_quote_actions', 'nonce' );

    $term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
    if ( strlen( $term ) < 2 ) {
        wp_send_json_success( array( 'customers' => array() ) );
    }

    global $wpdb;
    $table = qbc_customers_table_name();
    $like = '%' . $wpdb->esc_like( $term ) . '%';
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, company_name, contact_name, email, phone FROM $table
             WHERE company_name LIKE %s OR contact_name LIKE %s
             ORDER BY company_name ASC LIMIT 20",
            $like, $like
        ),
        ARRAY_A
    );
    wp_send_json_success( array( 'customers' => $rows ? $rows : array() ) );
}

// ── Quote Builder integration: customer search + autofill ──────────────────
// Injected into Core's Quote Info grid via sqs_quote_info_extra_fields, which
// always fires *after* Core's own 5 hardcoded fields (companyName, customerName,
// quoteDate, preparedBy, internalRef) -- there's no hook that fires earlier.
// To still show this field first/top-left as requested, it uses CSS Grid's
// `order` property (order:-1, lower than every other field's default order:0)
// on the parent .sqc-qd-grid -- a purely visual reposition, no DOM/hook change.
// The hidden #quoteCustomerId field uses Core's existing generic
// data-sqs-meta mechanism (getQuoteMeta() reads element .value directly at
// save time) so the selected customer flows into the saved quote's JSON
// payload with zero Core code changes.
function qbc_extra_info_fields() {
    $ajax = array(
        'url'   => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'sqs_quote_actions' ),
    );
    ?>
    <style>
        /* Moves this field to the front of the 3-col Quote Info grid (top-left)
           and gives it a distinct blue tint so it reads as a search/lookup
           field rather than a plain text input, matching the request to make
           it "clear you can search". */
        #qbcCustomerFieldWrap { order: -1; }
        #qbcQuoteCustomerSearch {
            background: #eff6ff;
            border-color: #93c5fd !important;
        }
        #qbcQuoteCustomerSearch:focus { border-color: #2563eb !important; }
        .qbc-result:hover { background: #eff6ff; }
        .qbc-result-add { color: #0284c7; font-weight: 700; }
        .qbc-quickadd-panel { padding: 10px; border-top: 1px solid #e2e8f0; }
        .qbc-quickadd-panel input { width: 100%; margin-bottom: 6px; box-sizing: border-box; }
        .qbc-quickadd-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 4px; }
    </style>
    <div class="sqc-field" id="qbcCustomerFieldWrap" style="position:relative;">
        <label>&#128269; Customer <span style="font-weight:400;text-transform:none;color:#64748b;">(type to search)</span></label>
        <input id="qbcQuoteCustomerSearch" placeholder="Search by company or contact name..." autocomplete="off" />
        <div id="qbcQuoteCustomerResults" style="display:none;position:absolute;top:100%;left:0;right:0;z-index:50;background:#fff;border:1px solid #cbd5e1;border-radius:8px;margin-top:4px;box-shadow:0 8px 24px rgba(15,23,42,.12);max-height:320px;overflow:auto;"></div>
        <input type="hidden" id="quoteCustomerId" data-sqs-meta="customerId" value="" />
    </div>
    <script>
    (function () {
        const AJAX = <?php echo wp_json_encode( $ajax ); ?>;

        function post(action, data) {
            const fd = new FormData();
            fd.append('action', action);
            fd.append('nonce', AJAX.nonce);
            Object.keys(data || {}).forEach(k => fd.append(k, data[k] == null ? '' : data[k]));
            return fetch(AJAX.url, { method: 'POST', credentials: 'same-origin', body: fd }).then(r => r.json());
        }

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
        }

        document.addEventListener('DOMContentLoaded', function () {
            const input   = document.getElementById('qbcQuoteCustomerSearch');
            const results = document.getElementById('qbcQuoteCustomerResults');
            if (!input) return;
            let searchTimer;
            let lastTerm = '';

            function selectCustomer(id, company, contact) {
                const companyEl  = document.getElementById('companyName');
                const customerEl = document.getElementById('customerName');
                const idEl       = document.getElementById('quoteCustomerId');
                if (companyEl)  companyEl.value  = company || '';
                if (customerEl) customerEl.value = contact || '';
                if (idEl)       idEl.value       = id || '';
                input.value = company || contact || '';
                results.style.display = 'none';
            }

            function renderResults(customers, term) {
                const rows = customers.map(c =>
                    `<div class="qbc-result" data-id="${c.id}" data-company="${esc(c.company_name)}" data-contact="${esc(c.contact_name)}" style="padding:8px 12px;cursor:pointer;border-bottom:1px solid #f1f5f9;font-size:13px;">
                        <strong>${esc(c.company_name)}</strong>${c.contact_name ? ' — ' + esc(c.contact_name) : ''}
                    </div>`
                ).join('');
                const noMatches = customers.length ? '' : '<div style="padding:8px 12px;color:#94a3b8;font-size:13px;">No matches</div>';
                const addRow = `<div class="qbc-result qbc-result-add" data-term="${esc(term)}" style="padding:8px 12px;cursor:pointer;font-size:13px;">+ Add New Customer${term ? ' "' + esc(term) + '"' : ''}</div>`;
                results.innerHTML = rows + noMatches + addRow;
                results.style.display = 'block';
            }

            function showQuickAddForm(prefillCompany) {
                results.innerHTML = `<div class="qbc-quickadd-panel">
                    <div style="font-weight:700;font-size:13px;margin-bottom:8px;">Add New Customer</div>
                    <input id="qbcQaCompany" placeholder="Company name" value="${esc(prefillCompany || '')}" />
                    <input id="qbcQaContact" placeholder="Contact name" />
                    <input id="qbcQaEmail" type="email" placeholder="Email (optional)" />
                    <input id="qbcQaPhone" placeholder="Phone (optional)" />
                    <div id="qbcQaError" style="color:#991b1b;font-size:12px;display:none;margin-bottom:4px;"></div>
                    <div class="qbc-quickadd-actions">
                        <button type="button" class="sqs-bar-btn" id="qbcQaCancel">Cancel</button>
                        <button type="button" class="sqs-bar-btn sqs-bar-btn-primary" id="qbcQaSave">Save &amp; Use</button>
                    </div>
                </div>`;
                results.style.display = 'block';
                document.getElementById('qbcQaCompany').focus();

                document.getElementById('qbcQaCancel').addEventListener('click', function () {
                    results.style.display = 'none';
                });
                document.getElementById('qbcQaSave').addEventListener('click', function () {
                    const company = document.getElementById('qbcQaCompany').value.trim();
                    const contact = document.getElementById('qbcQaContact').value.trim();
                    const email   = document.getElementById('qbcQaEmail').value.trim();
                    const phone   = document.getElementById('qbcQaPhone').value.trim();
                    const errEl   = document.getElementById('qbcQaError');
                    if (!company && !contact) {
                        errEl.textContent = 'Company name or contact name is required.';
                        errEl.style.display = 'block';
                        return;
                    }
                    post('sqs_save_customer', { companyName: company, contactName: contact, email: email, phone: phone }).then(res => {
                        if (res.success) {
                            selectCustomer(res.data.customer_id, company, contact);
                        } else {
                            errEl.textContent = (res.data && res.data.message) || 'Could not save customer.';
                            errEl.style.display = 'block';
                        }
                    });
                });
            }

            // Defensive clear against browser autofill: Core's Company/Contact/
            // Prepared By/Internal Ref fields have no server-side value and no
            // saved-quote data has been loaded yet at this point in a fresh page
            // load -- any text showing here is the *browser* re-filling form
            // history from a previous visit, not real quote data. Runs before
            // the qbc_customer_id prefill check below and before Core's own
            // "Load Quote" flow can act (that's a later user click), so neither
            // is affected by this -- it only clears what would otherwise be
            // stale leftovers on a brand-new quote.
            ['companyName', 'customerName', 'preparedBy', 'internalRef'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });

            // Prefill from ?qbc_customer_id=<id> -- set by the "Start Quote"
            // button on the Customer Database page's row list. Only runs on
            // a fresh page load (a brand-new quote), never overwrites a
            // customer already chosen while editing an in-progress quote.
            const prefillId = new URLSearchParams(window.location.search).get('qbc_customer_id');
            if (prefillId && !document.getElementById('quoteCustomerId').value) {
                post('sqs_get_customer', { customer_id: prefillId }).then(res => {
                    if (res.success && res.data.customer) {
                        const c = res.data.customer;
                        selectCustomer(c.id, c.company_name, c.contact_name);
                    }
                });
            }

            input.addEventListener('input', function () {
                clearTimeout(searchTimer);
                const term = input.value.trim();
                lastTerm = term;
                if (term.length < 2) { results.style.display = 'none'; return; }
                searchTimer = setTimeout(function () {
                    post('sqs_search_customers', { term: term }).then(res => {
                        const customers = (res.success && res.data.customers) ? res.data.customers : [];
                        renderResults(customers, term);
                    });
                }, 250);
            });

            input.addEventListener('focus', function () {
                if (input.value.trim().length >= 2) input.dispatchEvent(new Event('input'));
            });

            results.addEventListener('click', function (e) {
                // Stop this click from also reaching the document-level
                // "close on outside click" listener below. Without this,
                // showQuickAddForm() replacing results.innerHTML detaches
                // e.target from the DOM mid-bubble, so by the time the
                // document listener runs, results.contains(e.target) is
                // false (the node is now orphaned) -- making it think the
                // click landed outside and immediately hiding the very
                // form this click just opened.
                e.stopPropagation();
                const addRow = e.target.closest('.qbc-result-add');
                if (addRow) { showQuickAddForm(addRow.dataset.term || lastTerm); return; }
                const row = e.target.closest('.qbc-result');
                if (!row) return;
                selectCustomer(row.dataset.id, row.dataset.company, row.dataset.contact);
            });

            document.addEventListener('click', function (e) {
                if (e.target !== input && !results.contains(e.target)) {
                    results.style.display = 'none';
                }
            });
        });
    })();
    </script>
    <?php
}

// ─── Frontend Customer Database page ───────────────────────────────────────
// Shortcode: [sqs_customer_database]
add_shortcode( 'sqs_customer_database', 'qbc_customer_database_shortcode' );
function qbc_customer_database_shortcode() {
    if ( ! defined( 'SQS_VERSION' ) ) {
        return '<p><strong>Quote Builder — Customer Database</strong> requires the Quote Builder Core plugin to be active.</p>';
    }
    ob_start();
    if ( ! qbc_user_can_access() ) {
        qbc_render_signin_required();
    } else {
        qbc_render_customer_app();
    }
    return ob_get_clean();
}

/**
 * This module owns no login credentials of its own -- it borrows access
 * from Core's existing sales/editor sessions. A shortcode callback runs too
 * late in the page lifecycle for a reliable header-based redirect (Core's
 * own frontend pages don't attempt one either), so this renders a plain
 * message with a link back to the Portal instead.
 */
function qbc_render_signin_required() {
    ?>
    <div style="max-width:460px;margin:80px auto;background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:32px;box-shadow:0 18px 40px rgba(15,23,42,.08);font-family:Arial,Helvetica,sans-serif;text-align:center;">
        <h2 style="margin:0 0 10px;font-size:22px;color:#172033;">Sign In Required</h2>
        <p style="color:#64748b;margin:0 0 20px;">Sign in through the Quote Builder or Data Editor to access the Customer Database.</p>
        <a href="<?php echo esc_url( sqs_pricing_calculator_portal_url() ); ?>" style="display:inline-flex;align-items:center;justify-content:center;padding:13px 20px;background:#0f1623;color:#ffffff;border-radius:12px;font-size:14px;font-weight:700;text-decoration:none;">&#8592; Return to Home</a>
    </div>
    <?php
}

function qbc_customer_app_styles() {
    ?>
    <style>
        /* Reuses the same .sqs-data-* class names as Core's Pricing Editor page
           for visual consistency -- defined independently here since Core's
           style-rendering function is private to that plugin. */
        .sqs-data-app{font-family:Arial,Helvetica,sans-serif;background:#f5f7fb;border:1px solid #dfe5ef;border-radius:18px;padding:20px;color:#172033;box-shadow:0 18px 40px rgba(15,23,42,.08);max-width:1100px;margin:24px auto;}
        .sqs-data-app *{box-sizing:border-box}
        .sqs-data-top{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;background:linear-gradient(135deg,#1f2937,#334155);color:#fff;border-radius:16px;padding:20px;margin-bottom:16px}
        .sqs-data-top h1{margin:0;font-size:26px;line-height:1.1;color:#fff}
        .sqs-data-top p{margin:6px 0 0;color:#dbe3ef}
        .sqs-data-brand{font-size:12px;text-transform:uppercase;letter-spacing:.14em;color:#cbd5e1}
        .sqs-data-message{background:#ecfdf5;border:1px solid #bbf7d0;color:#14532d;border-radius:12px;padding:10px 12px;margin:12px 0}
        .sqs-data-error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:12px;padding:10px 12px;margin:12px 0}
        /* Dark background (matching .sqs-data-top's tone) so this sticky bar
           reads as a persistent toolbar distinct from the light page content
           scrolling beneath it, rather than blending into the same white as
           the cards below. */
        .sqs-data-actions{position:sticky;top:10px;z-index:20;display:flex;align-items:center;gap:10px;flex-wrap:wrap;background:#1f2937;border:1px solid #0f1623;border-radius:14px;padding:12px;margin:12px 0 18px;box-shadow:0 10px 28px rgba(15,23,42,.25)}
        .sqs-data-actions input[type=search]::placeholder{color:#94a3b8}
        .sqs-data-actions input[type=search]{flex:1;min-width:220px;height:40px;border:1px solid #cbd5e1;border-radius:10px;padding:8px 12px;font-size:14px;}
        .sqs-data-btn{appearance:none;border:0;border-radius:999px;background:#c62828;color:#fff;font-weight:800;padding:11px 18px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center}
        .sqs-data-btn:hover{background:#a81d1d;color:#fff}
        .sqs-data-btn.secondary{background:#334155}
        .sqs-data-btn.light{background:#eef2f7;color:#172033}
        .sqs-data-btn:disabled{background:#cbd5e1;cursor:not-allowed}
        .sqs-data-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:16px;box-shadow:0 8px 24px rgba(15,23,42,.05)}
        .sqs-data-table-wrap{overflow:auto;border:1px solid #e2e8f0;border-radius:12px}
        .sqs-data-table{width:100%;border-collapse:collapse;min-width:760px}
        .sqs-data-table th{background:#f8fafc;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#475569;position:sticky;top:0;z-index:1;text-align:left;padding:8px;border-bottom:1px solid #edf2f7;}
        .sqs-data-table td{border-bottom:1px solid #edf2f7;padding:8px;text-align:left;vertical-align:middle;}
        .sqs-data-mini{border:1px solid #cbd5e1;background:#fff;color:#172033;border-radius:999px;padding:6px 11px;cursor:pointer;font-weight:700;font-size:12px;}
        .sqs-data-remove{border:0;background:transparent;color:#b91c1c;cursor:pointer;font-weight:700}
        .sqs-data-remove:disabled{color:#cbd5e1;cursor:not-allowed}
        .qbc-modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:9998;align-items:center;justify-content:center;}
        .qbc-modal-overlay.open{display:flex;}
        .qbc-modal{background:#fff;border-radius:16px;padding:24px;width:100%;max-width:520px;max-height:85vh;overflow:auto;box-shadow:0 24px 60px rgba(15,23,42,.25);}
        .qbc-modal h2{margin:0 0 16px;font-size:20px;}
        .qbc-field{margin-bottom:14px;}
        .qbc-field label{display:block;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#475569;margin-bottom:6px;}
        .qbc-field input,.qbc-field textarea{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:9px 10px;font-size:14px;font-family:inherit;}
        .qbc-field-row{display:flex;gap:10px;}
        .qbc-field-row .qbc-field{min-width:0;}
        .qbc-modal-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:18px;}
        .qbc-history-row{padding:8px 0;border-bottom:1px solid #edf2f7;font-size:13px;}
    </style>
    <?php
}

function qbc_render_customer_app() {
    qbc_customer_app_styles();
    $ajax = array(
        'url'       => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'sqs_quote_actions' ),
        // sqs_pricing_calculator_sales_quote_builder_url() is a thin, stable
        // URL accessor (same category as the portal_url() call already used
        // throughout this module) -- calling it directly is fine, unlike
        // Core's actual business-logic functions.
        'quoteUrl'  => sqs_pricing_calculator_sales_quote_builder_url(),
    );
    $home_url = sqs_pricing_calculator_portal_url();
    ?>
    <div class="sqs-data-app">
        <div class="sqs-data-top">
            <div>
                <div class="sqs-data-brand">Quote Builder</div>
                <h1>Customer Database</h1>
                <p>Search, add, and manage customer records used across quotes.</p>
            </div>
            <a href="<?php echo esc_url( $home_url ); ?>" style="display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:11px 18px;background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.25);border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">&#8592; Home</a>
        </div>

        <div id="qbcMessage"></div>

        <div class="sqs-data-actions">
            <input type="search" id="qbcSearch" placeholder="Search by company, contact, or email..." />
            <button type="button" class="sqs-data-btn" id="qbcAddBtn">+ Add Customer</button>
        </div>

        <div class="sqs-data-card">
            <div class="sqs-data-table-wrap">
                <table class="sqs-data-table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Rep</th>
                            <th>Quotes</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="qbcTableBody"><tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:20px;">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit modal -->
    <div class="qbc-modal-overlay" id="qbcFormOverlay">
        <div class="qbc-modal">
            <h2 id="qbcFormTitle">Add Customer</h2>
            <input type="hidden" id="qbcCustomerId" value="" />
            <div class="qbc-field"><label>Company Name</label><input id="qbcCompanyName" /></div>
            <div class="qbc-field"><label>Contact Name</label><input id="qbcContactName" /></div>
            <div class="qbc-field"><label>Email</label><input id="qbcEmail" type="email" /></div>
            <div class="qbc-field"><label>Phone</label><input id="qbcPhone" /></div>
            <div class="qbc-field"><label>Assigned Rep</label><input id="qbcAssignedRep" placeholder="Sales rep name" /></div>
            <div class="qbc-field"><label>Street Address</label><textarea id="qbcAddress" rows="2"></textarea></div>
            <div class="qbc-field-row">
                <div class="qbc-field" style="flex:2;"><label>City</label><input id="qbcCity" /></div>
                <div class="qbc-field" style="flex:1;"><label>State</label><input id="qbcState" maxlength="2" style="text-transform:uppercase;" /></div>
                <div class="qbc-field" style="flex:1;"><label>ZIP</label><input id="qbcZip" /></div>
            </div>
            <div class="qbc-field"><label>Notes</label><textarea id="qbcNotes" rows="2"></textarea></div>
            <div class="qbc-modal-actions">
                <button type="button" class="sqs-data-btn light" id="qbcFormCancel">Cancel</button>
                <button type="button" class="sqs-data-btn" id="qbcFormSave">Save Customer</button>
            </div>
        </div>
    </div>

    <!-- Quote history modal -->
    <div class="qbc-modal-overlay" id="qbcHistoryOverlay">
        <div class="qbc-modal">
            <h2>Quote History</h2>
            <div id="qbcHistoryBody" style="max-height:50vh;overflow:auto;"></div>
            <div class="qbc-modal-actions">
                <button type="button" class="sqs-data-btn light" id="qbcHistoryClose">Close</button>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const AJAX = <?php echo wp_json_encode( $ajax ); ?>;
        let customers = [];

        function post(action, data) {
            const fd = new FormData();
            fd.append('action', action);
            fd.append('nonce', AJAX.nonce);
            Object.keys(data || {}).forEach(k => fd.append(k, data[k] == null ? '' : data[k]));
            return fetch(AJAX.url, { method: 'POST', credentials: 'same-origin', body: fd }).then(r => r.json());
        }

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
        }

        function showMessage(text, isError) {
            const el = document.getElementById('qbcMessage');
            el.innerHTML = `<div class="${isError ? 'sqs-data-error' : 'sqs-data-message'}">${esc(text)}</div>`;
            setTimeout(() => { el.innerHTML = ''; }, 4000);
        }

        function renderTable() {
            const tbody = document.getElementById('qbcTableBody');
            if (!customers.length) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:20px;">No customers yet.</td></tr>';
                return;
            }
            tbody.innerHTML = customers.map(c => {
                const hasQuotes = Number(c.quote_count) > 0;
                return `<tr>
                    <td>${esc(c.company_name)}</td>
                    <td>${esc(c.contact_name)}</td>
                    <td>${esc(c.email)}</td>
                    <td>${esc(c.phone)}</td>
                    <td>${esc(c.assigned_rep)}</td>
                    <td>${hasQuotes ? `<button type="button" class="sqs-data-mini qbc-history-btn" data-id="${c.id}">${c.quote_count}</button>` : '0'}</td>
                    <td style="white-space:nowrap;">
                        <a href="${AJAX.quoteUrl}?qbc_customer_id=${c.id}" target="_blank" rel="noopener" class="sqs-data-mini" style="text-decoration:none;display:inline-block;">Start Quote</a>
                        <button type="button" class="sqs-data-mini qbc-edit-btn" data-id="${c.id}">Edit</button>
                        <button type="button" class="sqs-data-remove qbc-delete-btn" data-id="${c.id}" ${hasQuotes ? 'disabled title="Cannot delete: quotes are linked to this customer"' : ''}>Remove</button>
                    </td>
                </tr>`;
            }).join('');
        }

        function loadCustomers(search) {
            post('sqs_list_customers', { search: search || '' }).then(res => {
                if (res.success) {
                    customers = res.data.customers;
                    renderTable();
                } else {
                    showMessage((res.data && res.data.message) || 'Could not load customers.', true);
                }
            });
        }

        function openAddModal() {
            document.getElementById('qbcFormTitle').textContent = 'Add Customer';
            document.getElementById('qbcCustomerId').value = '';
            ['qbcCompanyName','qbcContactName','qbcEmail','qbcPhone','qbcAssignedRep','qbcAddress','qbcCity','qbcState','qbcZip','qbcNotes'].forEach(id => { document.getElementById(id).value = ''; });
            document.getElementById('qbcFormOverlay').classList.add('open');
        }

        function openEditModal(id) {
            const c = customers.find(x => String(x.id) === String(id));
            if (!c) return;
            document.getElementById('qbcFormTitle').textContent = 'Edit Customer';
            document.getElementById('qbcCustomerId').value = c.id;
            document.getElementById('qbcCompanyName').value = c.company_name || '';
            document.getElementById('qbcContactName').value = c.contact_name || '';
            document.getElementById('qbcEmail').value = c.email || '';
            document.getElementById('qbcPhone').value = c.phone || '';
            document.getElementById('qbcAssignedRep').value = c.assigned_rep || '';
            document.getElementById('qbcAddress').value = c.address || '';
            document.getElementById('qbcCity').value = c.city || '';
            document.getElementById('qbcState').value = c.state || '';
            document.getElementById('qbcZip').value = c.zip || '';
            document.getElementById('qbcNotes').value = c.notes || '';
            document.getElementById('qbcFormOverlay').classList.add('open');
        }

        function closeFormModal() {
            document.getElementById('qbcFormOverlay').classList.remove('open');
        }

        function saveCustomer() {
            const payload = {
                customer_id: document.getElementById('qbcCustomerId').value,
                companyName: document.getElementById('qbcCompanyName').value.trim(),
                contactName: document.getElementById('qbcContactName').value.trim(),
                email: document.getElementById('qbcEmail').value.trim(),
                phone: document.getElementById('qbcPhone').value.trim(),
                assignedRep: document.getElementById('qbcAssignedRep').value.trim(),
                address: document.getElementById('qbcAddress').value.trim(),
                city: document.getElementById('qbcCity').value.trim(),
                state: document.getElementById('qbcState').value.trim().toUpperCase(),
                zip: document.getElementById('qbcZip').value.trim(),
                notes: document.getElementById('qbcNotes').value.trim(),
            };
            if (!payload.companyName && !payload.contactName) {
                showMessage('Company name or contact name is required.', true);
                return;
            }
            post('sqs_save_customer', payload).then(res => {
                if (res.success) {
                    closeFormModal();
                    showMessage(res.data.message || 'Customer saved.', false);
                    loadCustomers(document.getElementById('qbcSearch').value);
                } else {
                    showMessage((res.data && res.data.message) || 'Could not save customer.', true);
                }
            });
        }

        function deleteCustomer(id) {
            if (!window.confirm('Remove this customer? This cannot be undone.')) return;
            post('sqs_delete_customer', { customer_id: id }).then(res => {
                if (res.success) {
                    showMessage(res.data.message || 'Customer deleted.', false);
                    loadCustomers(document.getElementById('qbcSearch').value);
                } else {
                    showMessage((res.data && res.data.message) || 'Could not delete customer.', true);
                }
            });
        }

        function openHistoryModal(id) {
            const body = document.getElementById('qbcHistoryBody');
            body.innerHTML = '<div style="color:#94a3b8;padding:10px 0;">Loading...</div>';
            document.getElementById('qbcHistoryOverlay').classList.add('open');
            post('sqs_get_customer_quotes', { customer_id: id }).then(res => {
                if (res.success && res.data.quotes && res.data.quotes.length) {
                    body.innerHTML = res.data.quotes.map(q =>
                        `<div class="qbc-history-row"><strong>${esc(q.quote_number)}</strong> ${esc(q.revision_label || '')} &mdash; ${esc(q.status || '')} &mdash; ${esc(q.updated_at || '')}</div>`
                    ).join('');
                } else {
                    body.innerHTML = '<div style="color:#94a3b8;padding:10px 0;">No quotes linked yet.</div>';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            loadCustomers('');

            document.getElementById('qbcAddBtn').addEventListener('click', openAddModal);
            document.getElementById('qbcFormCancel').addEventListener('click', closeFormModal);
            document.getElementById('qbcFormSave').addEventListener('click', saveCustomer);
            document.getElementById('qbcHistoryClose').addEventListener('click', function () {
                document.getElementById('qbcHistoryOverlay').classList.remove('open');
            });

            let searchTimer;
            document.getElementById('qbcSearch').addEventListener('input', function (e) {
                clearTimeout(searchTimer);
                const val = e.target.value;
                searchTimer = setTimeout(() => loadCustomers(val), 250);
            });

            document.getElementById('qbcTableBody').addEventListener('click', function (e) {
                const editBtn = e.target.closest('.qbc-edit-btn');
                const delBtn = e.target.closest('.qbc-delete-btn');
                const histBtn = e.target.closest('.qbc-history-btn');
                if (editBtn) openEditModal(editBtn.dataset.id);
                if (delBtn && !delBtn.disabled) deleteCustomer(delBtn.dataset.id);
                if (histBtn) openHistoryModal(histBtn.dataset.id);
            });
        });
    })();
    </script>
    <?php
}
