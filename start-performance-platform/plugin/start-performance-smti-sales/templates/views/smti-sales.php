<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$is_admin    = sp_is_admin_member();
$products    = function_exists( 'sp_smti_sales_get_products' )    ? sp_smti_sales_get_products()    : array();
$distributors = function_exists( 'sp_smti_sales_get_distributors' ) ? sp_smti_sales_get_distributors() : array();
$recent      = function_exists( 'sp_smti_sales_get_recent_quotes' ) ? sp_smti_sales_get_recent_quotes( 8 ) : array();
$api_base    = get_option( 'sp_smti_sales_api_base',  'https://startwebservicesbackup.com/smti/wp-json/smti/v1' );
$api_token   = get_option( 'sp_smti_sales_api_token', '' );
$last_sync   = get_option( 'sp_smti_sales_last_sync', '' );
$logo_url    = get_option( 'sp_smti_sales_logo_url', 'https://50794111.fs1.hubspotusercontent-na1.net/hubfs/50794111/Sport%20Medical%20Logo%20Update%2011112025_SMTI%20Logo%20(Vertical%20Black).png' );
$ajax_url    = admin_url( 'admin-ajax.php' );
$no_products = empty( $products );
?>
<style>
/* ── SMTI Quote Builder ── */
.qb-page-header{margin-bottom:20px}
.qb-page-header h1{font-size:22px;font-weight:700;color:#0f172a;margin:0 0 2px}
.qb-page-header p{font-size:13px;color:#64748b;margin:0}
.qb-toolbar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;margin-bottom:18px}
.qb-toolbar button{display:inline-flex;align-items:center;height:34px;padding:0 14px;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;border:1px solid #cbd5e1;background:#fff;color:#334155;transition:background .15s,border-color .15s;white-space:nowrap}
.qb-toolbar button:hover{background:#f1f5f9;border-color:#94a3b8}
.qb-toolbar button.active-tab{background:#0f172a;border-color:#0f172a;color:#fff}
.qb-toolbar-sep{width:1px;height:22px;background:#e2e8f0;flex-shrink:0}
.qb-toolbar-spacer{flex:1}
.qb-sync-btn{font-size:11px !important;color:#0369a1 !important;border-color:#bae6fd !important}
.qb-sync-btn:hover{background:#f0f9ff !important;border-color:#0284c7 !important}
.qb-no-products{background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:14px 16px;margin-bottom:18px;font-size:13px;color:#92400e}
.qb-no-products strong{display:block;margin-bottom:4px}
.qb-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
.qb-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:20px}
.qb-card h2{font-size:14px;font-weight:700;color:#0f172a;margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid #f1f5f9}
.qb-card--full{grid-column:1/-1}
.qb-field{margin-bottom:12px}
.qb-field label{display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px}
.qb-field input,.qb-field select,.qb-field textarea{width:100%;box-sizing:border-box;border:1px solid #e2e8f0;border-radius:7px;padding:8px 10px;font-size:13px;color:#1e293b;background:#fff;font-family:inherit;transition:border-color .15s}
.qb-field input:focus,.qb-field select:focus,.qb-field textarea:focus{outline:none;border-color:#94a3b8}
.qb-field--highlight{background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:10px 12px;margin-bottom:14px}
.qb-field--highlight label{color:#0369a1}
.qb-field.has-error input,.qb-field.has-error select{border-color:#f43f5e}
.qb-field-error{font-size:11px;color:#f43f5e;margin-top:4px}
.qb-form-alert{background:#fef2f2;border:1px solid #fecaca;border-radius:7px;padding:10px 12px;font-size:13px;color:#dc2626;margin-bottom:12px}

/* Address search */
.address-search-results{position:absolute;left:0;right:0;top:100%;z-index:200;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 24px rgba(15,23,42,.12);margin-top:4px;max-height:280px;overflow-y:auto}
.qb-field--address-search{position:relative}
.address-search-item{padding:10px 12px;cursor:pointer;border-bottom:1px solid #f1f5f9;font-size:13px}
.address-search-item:hover{background:#f8fafc}
.address-search-item:last-child{border-bottom:none}
.address-search-label{font-weight:600;color:#1e293b}
.address-search-meta{font-size:11px;color:#64748b;margin-top:2px}
.address-search-empty{padding:12px;font-size:12px;color:#94a3b8;text-align:center}
.is-hidden{display:none!important}

/* Products */
.search-wrap{display:flex;gap:8px}
.search-wrap input{flex:1}
.search-wrap button{flex-shrink:0;height:38px;padding:0 14px;border:1px solid #e2e8f0;border-radius:7px;background:#fff;font-size:12px;font-weight:700;cursor:pointer;color:#334155}
.search-wrap button:hover{background:#f1f5f9}
.product-results{margin-top:12px;max-height:360px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px}
.product-item{border-bottom:1px solid #f1f5f9;padding:10px 12px}
.product-item:last-child{border-bottom:none}
.product-row{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
.product-main{flex:1;min-width:0}
.product-code{font-size:11px;font-weight:700;color:#64748b;letter-spacing:.04em;text-transform:uppercase}
.product-title{font-size:13px;font-weight:600;color:#1e293b;margin:1px 0}
.product-description{font-size:11px;color:#94a3b8}
.product-side{flex-shrink:0;text-align:right}
.product-price{font-size:13px;font-weight:700;color:#0f172a;margin-bottom:6px}
.product-actions button{height:30px;padding:0 12px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;font-size:12px;font-weight:700;cursor:pointer;color:#334155}
.product-actions button:hover{background:#f1f5f9}
.product-actions button:disabled{opacity:.5;cursor:default}
.add-status{font-size:11px;color:#22c55e;margin-left:6px}
.product-helper{font-size:13px;color:#94a3b8;text-align:center;padding:20px 0;margin:0}

/* Cart */
.qb-table{width:100%;border-collapse:collapse;font-size:13px}
.qb-table th{background:#f8fafc;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;padding:8px 10px;text-align:left;border-bottom:1px solid #e2e8f0}
.qb-table td{padding:8px 10px;border-bottom:1px solid #f1f5f9;vertical-align:middle;color:#1e293b}
.qb-table input[type=number]{width:60px;border:1px solid #e2e8f0;border-radius:6px;padding:4px 6px;font-size:12px;text-align:center}
.discount-cell{display:flex;gap:4px;align-items:center}
.discount-type-select{width:58px;height:30px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;padding:0 4px}
.discount-value-input{width:70px;height:30px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;padding:0 6px}
.discount-help{display:block;font-size:10px;color:#94a3b8;margin-top:2px}
.freight-row td{background:#f8fafc;color:#64748b}
.freight-price-input{width:100px !important}
.freight-static{color:#94a3b8;font-size:12px}
.qb-table button[onclick^=removeItem]{height:26px;width:26px;padding:0;border:1px solid #fecaca;border-radius:5px;background:#fff;color:#f43f5e;cursor:pointer;font-size:11px;font-weight:700}
.qb-table button[onclick^=removeItem]:hover{background:#fef2f2}

/* Totals */
.quote-totals{padding:4px 0}
.quote-totals p{display:flex;justify-content:space-between;padding:6px 0;margin:0;font-size:14px;border-bottom:1px solid #f1f5f9;color:#334155}
.quote-totals p:last-child{border-bottom:none;font-size:15px;font-weight:700;color:#0f172a}
.quote-note{font-size:11px !important;color:#94a3b8 !important;font-weight:400 !important;margin-top:4px}

/* Actions */
.qb-actions-wrapper{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.quote-helper-text{font-size:12px;color:#94a3b8;margin:0}
.qb-actions{display:flex;gap:8px;flex-wrap:wrap}
.qb-actions button{height:38px;padding:0 16px;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;border:1px solid #e2e8f0;background:#fff;color:#334155;transition:background .15s}
.qb-actions button:hover{background:#f1f5f9}
#submitQuoteBtn{background:#0f172a;border-color:#0f172a;color:#fff}
#submitQuoteBtn:hover{background:#1e293b}
#submitQuoteBtn:disabled{opacity:.6;cursor:default}

/* Success */
.qb-success{background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:16px 20px;margin-bottom:18px}
.qb-success-inner{display:flex;gap:14px;align-items:flex-start}
.qb-success-icon{width:36px;height:36px;background:#22c55e;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;flex-shrink:0}
.qb-success-content h3{margin:0 0 4px;font-size:15px;font-weight:700;color:#15803d}
.qb-success-content p{margin:0 0 6px;font-size:13px;color:#166534}
.qb-success-list{margin:4px 0 0 16px;padding:0;font-size:12px;color:#166534}
.qb-success-list li{margin-bottom:2px}

/* Preview / print */
.quote-sheet{font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#2f3a45;max-width:860px;margin:0 auto}
.quote-top{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:16px;padding-bottom:12px;border-bottom:3px solid #dbe3eb}
.quote-logo{max-height:70px;max-width:200px;width:auto}
.quote-title{margin:0;font-size:32px;font-weight:300;color:#5a5a5a;text-align:right}
.quote-number-display{margin-top:6px;font-size:13px;font-weight:700;color:#123b63;text-align:right}
.quote-section-bar{margin:24px 0 10px;padding:8px 12px;background:#dbe3eb;color:#33485c;font-size:12px;font-weight:600}
.quote-meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px}
.quote-meta-table{display:grid;grid-template-columns:115px 1fr;column-gap:8px;row-gap:4px;font-size:11px;line-height:1.3}
.quote-meta-label{color:#33485c;font-weight:600}
.quote-meta-value{color:#3e4c59;word-break:break-word}
.quote-products-table{width:100%;border-collapse:collapse;margin-top:12px;margin-bottom:20px}
.quote-products-table th{padding:7px 8px;background:#6b7280;color:#fff;text-align:left;font-size:11px;font-weight:600;border-right:1px solid #9ca3af}
.quote-products-table th:last-child{border-right:none}
.quote-products-table td{padding:6px 8px;border-right:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb;font-size:11px;color:#33485c;vertical-align:top}
.quote-products-table td:last-child{border-right:none}
.quote-products-table tbody tr:nth-child(even) td{background:#f9fafb}
.col-qty{width:40px}.col-code{width:100px}.col-title{width:auto}.col-price,.col-total{width:90px;text-align:right}.col-disc{width:80px;text-align:right}
.quote-totals-preview{width:260px;margin-left:auto;margin-top:16px;margin-bottom:16px}
.quote-total-row{display:flex;justify-content:space-between;padding:3px 0;font-size:12px;color:#33485c;border-bottom:1px solid #f1f5f9}
.quote-total-row:last-child{border-bottom:none;font-weight:700;font-size:13px;color:#1f2d3a}
.quote-terms-3col{display:grid;grid-template-columns:1fr 1fr 1.1fr;gap:16px;margin:12px 0}
.quote-terms-3col p,.quote-terms-3col li{margin:0 0 4px;font-size:10px;line-height:1.3;color:#444}
.quote-terms-3col ul{margin:2px 0 0 14px;padding:0}
.quote-signature-block{}
.sig-row{display:grid;grid-template-columns:60px 1fr;align-items:end;column-gap:8px;margin-bottom:8px}
.sig-label{font-size:10px;color:#444}
.sig-line{display:block;width:100%;height:12px;border-bottom:1px solid #c0c0c0}
.quote-true-footer{margin-top:24px;padding-top:10px;border-top:1px solid #e2e8f0}
.quote-footer-text{font-size:10px;line-height:1.4;color:#666;margin:0 0 8px}
.quote-footer-company{display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:10px;line-height:1.4;color:#555}
.quote-footer-company a{color:#1a5fb4}
.quote-footer-divider{border-top:1px solid #e2e8f0;margin-bottom:8px}

/* Revisions */
.revision-history-item{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f1f5f9;gap:12px}
.revision-history-title{font-size:13px;font-weight:600;color:#1e293b}
.revision-history-badge{display:inline-block;font-size:10px;font-weight:700;background:#dbeafe;color:#1d4ed8;border-radius:4px;padding:1px 6px;margin-left:6px}
.revision-history-meta{font-size:11px;color:#94a3b8;margin-top:2px}
.revision-history-item button{height:28px;padding:0 12px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap}
.revision-history-item button:hover{background:#f8fafc}
.revision-compare-toolbar{display:flex;gap:8px;align-items:center;margin-bottom:14px;flex-wrap:wrap}
.revision-compare-toolbar select{height:34px;border:1px solid #e2e8f0;border-radius:7px;font-size:12px;padding:0 8px;min-width:160px}
.revision-compare-toolbar button{height:34px;padding:0 14px;border:1px solid #e2e8f0;border-radius:7px;background:#fff;font-size:12px;font-weight:700;cursor:pointer}
.revision-compare-summary{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px}
.revision-compare-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px}
.revision-compare-box h3{margin:0 0 8px;font-size:13px;font-weight:700;color:#1e293b}
.revision-compare-box p{margin:0 0 4px;font-size:12px;color:#334155}
.revision-diff-list{display:flex;flex-direction:column;gap:10px}
.revision-diff-item{border:1px solid #e2e8f0;border-radius:8px;padding:12px;background:#fff}
.revision-diff-item--added{border-color:#86efac;background:#f0fdf4}
.revision-diff-item--removed{border-color:#fca5a5;background:#fef2f2}
.revision-diff-item--changed{border-color:#fdba74;background:#fff7ed}
.revision-diff-title{font-size:13px;font-weight:700;color:#1e293b;margin-bottom:4px}
.revision-diff-meta{font-size:11px;color:#64748b;margin-bottom:8px}
.revision-diff-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.revision-diff-col p{margin:0 0 3px;font-size:12px;color:#334155}

/* Saved quotes table */
.qb-saved-table{width:100%;border-collapse:collapse;font-size:13px}
.qb-saved-table th{background:#f8fafc;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;padding:8px 10px;text-align:left;border-bottom:1px solid #e2e8f0}
.qb-saved-table td{padding:9px 10px;border-bottom:1px solid #f1f5f9;color:#1e293b;vertical-align:middle}
.qb-saved-table tr:hover td{background:#fafafa}
.sp-link{color:#0369a1;text-decoration:none;font-weight:600}
.sp-link:hover{text-decoration:underline}

@media(max-width:700px){.qb-grid{grid-template-columns:1fr}.quote-meta-grid{grid-template-columns:1fr}.quote-terms-3col{grid-template-columns:1fr}.revision-compare-summary{grid-template-columns:1fr}.revision-diff-grid{grid-template-columns:1fr}.qb-actions-wrapper{flex-direction:column;align-items:flex-start}}

/* ── Toast notifications ── */
#sp-toast-container{position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none}
.sp-toast{display:flex;align-items:center;gap:10px;background:#1e293b;color:#f1f5f9;padding:12px 18px;border-radius:10px;font-size:13px;font-weight:500;box-shadow:0 4px 24px rgba(0,0,0,.18);pointer-events:all;animation:sp-toast-in .18s ease;max-width:360px;line-height:1.4}
.sp-toast.sp-toast-success{border-left:4px solid #22c55e}
.sp-toast.sp-toast-error{border-left:4px solid #ef4444}
.sp-toast.sp-toast-info{border-left:4px solid #3b82f6}
.sp-toast.sp-toast-warn{border-left:4px solid #f59e0b}
.sp-toast-out{animation:sp-toast-out .2s ease forwards}
@keyframes sp-toast-in{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
@keyframes sp-toast-out{to{opacity:0;transform:translateY(8px)}}

/* ── Confirm modal ── */
#sp-confirm-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:99998;display:none;align-items:center;justify-content:center}
#sp-confirm-overlay.active{display:flex}
#sp-confirm-box{background:#fff;border-radius:14px;padding:28px 28px 20px;max-width:380px;width:90%;box-shadow:0 8px 40px rgba(0,0,0,.18)}
#sp-confirm-box h3{font-size:15px;font-weight:700;color:#0f172a;margin:0 0 8px}
#sp-confirm-box p{font-size:13px;color:#64748b;margin:0 0 20px;line-height:1.5}
#sp-confirm-box .sp-confirm-actions{display:flex;gap:10px;justify-content:flex-end}
#sp-confirm-box .sp-confirm-actions button{padding:8px 20px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none}
#sp-confirm-cancel{background:#f1f5f9;color:#475569}
#sp-confirm-ok{background:#cc1f1f;color:#fff}
</style>

<!-- Toast container -->
<div id="sp-toast-container"></div>

<!-- Confirm modal -->
<div id="sp-confirm-overlay">
    <div id="sp-confirm-box">
        <h3 id="sp-confirm-title">Confirm</h3>
        <p id="sp-confirm-msg"></p>
        <div class="sp-confirm-actions">
            <button id="sp-confirm-cancel">Cancel</button>
            <button id="sp-confirm-ok">Confirm</button>
        </div>
    </div>
</div>

<div class="sp-view-header">
    <div>
        <h1>SMTI Quote Builder</h1>
        <p class="sp-muted">Create distributor quotes, sync to HubSpot, and generate quote numbers.</p>
    </div>
</div>

<?php if ( $no_products ) : ?>
<div class="qb-no-products">
    <strong>Product catalog not synced.</strong>
    Go to Settings → SMTI Sales Settings and save, then click "Sync Products &amp; Distributors" to load products from HubDB before building quotes.
</div>
<?php endif; ?>

<!-- Toolbar -->
<div class="qb-toolbar">
    <button type="button" id="topNewQuoteBtn">+ New Quote</button>
    <button type="button" id="topLoadQuoteToggleBtn">Open Quote</button>
    <div class="qb-toolbar-sep"></div>
    <button type="button" id="topRevisionHistoryBtn" style="display:none">Revisions</button>
    <button type="button" id="topCompareQuotesBtn" style="display:none">Compare</button>
    <button type="button" id="topCustomerBtn">Customer</button>
    <button type="button" id="topProductsBtn">Add Products</button>
    <button type="button" id="topPreviewBtn">Review &amp; Save</button>
    <div class="qb-toolbar-spacer"></div>
    <?php if ( $is_admin ) : ?>
    <button type="button" id="syncProductsBtn" class="qb-sync-btn">
        ↻ Sync Products<?php echo $last_sync ? ' <span style="font-weight:400;opacity:.7">(' . esc_html( date( 'M j', strtotime( $last_sync ) ) ) . ')</span>' : ''; ?>
    </button>
    <?php endif; ?>
</div>

<!-- Success panel -->
<div id="quoteSuccessPanel" class="qb-success is-hidden">
    <div class="qb-success-inner">
        <div class="qb-success-icon">✓</div>
        <div class="qb-success-content">
            <h3>Quote Saved</h3>
            <p id="quoteSuccessMeta"></p>
            <ul id="quoteSuccessList" class="qb-success-list"></ul>
        </div>
    </div>
</div>

<form id="quoteBuilderForm">
<input type="hidden" id="current_deal_id" value="">
<input type="hidden" id="current_quote_number" value="">

<!-- Load quote panel -->
<div class="sp-card" id="loadQuoteCard" style="display:none;margin-bottom:16px">
    <h2 style="font-size:14px;font-weight:700;margin:0 0 12px;color:#0f172a">Open Existing Quote</h2>
    <div class="search-wrap">
        <input type="text" id="existingDealId" placeholder="Quote # (SMTI-202406-0001) or HubSpot Deal ID">
        <button type="button" id="loadQuoteBtn" class="sp-btn sp-btn-primary" style="height:38px">Load Quote</button>
    </div>
    <p style="font-size:12px;color:#94a3b8;margin:8px 0 0">Enter a quote number or HubSpot Deal ID to load and edit an existing quote.</p>
<?php if ( ! empty( $recent ) ) : ?>
    <div style="margin-top:20px;border-top:1px solid #e2e8f0;padding-top:16px">
        <h3 style="font-size:13px;font-weight:700;margin:0 0 10px;color:#0f172a">Recent Quotes</h3>
        <table class="qb-saved-table">
            <thead><tr>
                <th>Quote #</th><th>Customer</th><th>Company</th><th>Region</th><th>Total</th><th>Status</th><th>Date</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ( $recent as $q ) : ?>
                <tr>
                    <td><strong><?php echo $q['quote_number'] ? esc_html( $q['quote_number'] ) : '—'; ?></strong></td>
                    <td><?php echo esc_html( $q['customer_name'] ?: '—' ); ?></td>
                    <td><?php echo esc_html( $q['company_name'] ?: '—' ); ?></td>
                    <td><span style="text-transform:uppercase;font-size:11px;font-weight:700;color:#64748b"><?php echo esc_html( $q['pricing_region'] ?: 'us' ); ?></span></td>
                    <td><strong>$<?php echo number_format( floatval( $q['total'] ), 2 ); ?></strong></td>
                    <td><span class="sp-badge sp-badge-<?php echo esc_attr( strtolower( $q['status'] ) ); ?>"><?php echo esc_html( $q['status'] ); ?></span></td>
                    <td class="sp-muted"><?php echo esc_html( date( 'M j, Y', strtotime( $q['created_at'] ) ) ); ?></td>
                    <td style="white-space:nowrap">
                        <?php if ( $q['deal_id'] ) : ?>
                        <a href="#" class="sp-link load-saved-quote" data-deal="<?php echo esc_attr( $q['deal_id'] ); ?>">Load</a>
                        &nbsp;
                        <a href="#" class="sp-danger delete-local-quote" data-deal="<?php echo esc_attr( $q['deal_id'] ); ?>" style="font-size:12px;color:#94a3b8" title="Remove from local list">✕</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</div>

<!-- Revision history -->
<div class="sp-card" id="revisionHistoryCard" style="display:none;margin-bottom:16px">
    <h2 style="font-size:14px;font-weight:700;margin:0 0 12px;color:#0f172a">Quote Revision History</h2>
    <div id="revisionHistoryContent"><p class="product-helper">Load a quote to see revision history.</p></div>
</div>

<!-- Revision compare -->
<div class="sp-card" id="revisionCompareCard" style="display:none;margin-bottom:16px">
    <h2 style="font-size:14px;font-weight:700;margin:0 0 12px;color:#0f172a">Revision Comparison</h2>
    <div id="revisionCompareContent"><p class="product-helper">Choose two revisions to compare.</p></div>
</div>

<div class="qb-grid">

    <!-- Distributor card -->
    <div class="qb-card" id="distributorCard">
        <h2>Distributor Information</h2>
        <div class="qb-field qb-field--highlight">
            <label>Select Distributor</label>
            <select id="distributor_select">
                <option value="">Select a distributor</option>
                <?php foreach ( $distributors as $d ) : ?>
                <option value="<?php echo esc_attr( $d['slug'] ); ?>">
                    <?php echo esc_html( $d['name'] ); ?><?php if ( $d['company'] ) echo ' — ' . esc_html( $d['company'] ); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="qb-field"><label>Distributor Company</label><input type="text" id="distributor_company"></div>
        <div class="qb-field"><label>Rep First Name</label><input type="text" id="distributor_first_name"></div>
        <div class="qb-field"><label>Rep Last Name</label><input type="text" id="distributor_last_name"></div>
        <div class="qb-field"><label>Rep Email</label><input type="email" id="distributor_email"></div>
        <div class="qb-field"><label>Rep Phone</label><input type="text" id="distributor_phone"></div>
        <div class="qb-field"><label>Shipping Method</label><input type="text" id="shipping_method"></div>
        <div class="qb-field"><label>Discount Reason</label><input type="text" id="discount_reason"></div>
        <div class="qb-field"><label>Notes</label><textarea id="notes" rows="4"></textarea></div>
    </div>

    <!-- Customer card -->
    <div class="qb-card" id="customerCard">
        <h2>Customer Information</h2>
        <div class="qb-field qb-field--address-search qb-field--highlight">
            <label>Search Existing HubSpot Customers</label>
            <input type="text" id="address1" autocomplete="off" placeholder="Search by company, name, email, address…">
            <div id="addressSearchResults" class="address-search-results is-hidden"></div>
        </div>
        <div class="qb-field"><label>Account Name</label><input type="text" id="account_name"></div>
        <div class="qb-field"><label>First Name</label><input type="text" id="customer_first_name"></div>
        <div class="qb-field"><label>Last Name</label><input type="text" id="customer_last_name"></div>
        <div class="qb-field"><label>Email</label><input type="email" id="customer_email"></div>
        <div class="qb-field"><label>Phone</label><input type="text" id="customer_phone"></div>
        <div class="qb-field"><label>Address Line 1</label><input type="text" id="mailing_address1"></div>
        <div class="qb-field"><label>Address Line 2</label><input type="text" id="address2"></div>
        <div class="qb-field"><label>City</label><input type="text" id="city"></div>
        <div class="qb-field"><label>State</label><input type="text" id="state"></div>
        <div class="qb-field"><label>Postal Code</label><input type="text" id="postal_code"></div>
        <div class="qb-field"><label>Country</label><input type="text" id="country"></div>
        <div class="qb-field">
            <label>Pricing Region <span style="color:#f43f5e">*</span></label>
            <select id="quote_location">
                <option value="us" selected>US</option>
                <option value="int">INT</option>
            </select>
        </div>
    </div>

    <!-- Products -->
    <div class="qb-card qb-card--full" id="productsCard">
        <h2>Products</h2>
        <div class="qb-field">
            <label>Search Products</label>
            <div class="search-wrap">
                <input type="text" id="product_search" placeholder="Search by code, title, or description…">
                <button type="button" id="clearSearchBtn">Clear</button>
            </div>
        </div>
        <div id="productResults" class="product-results"></div>
    </div>

    <!-- Cart -->
    <div class="qb-card qb-card--full" id="selectedItemsCard">
        <h2>Selected Items</h2>
        <div style="overflow-x:auto">
            <table class="qb-table" id="cartTable">
                <thead><tr>
                    <th>Qty</th><th>Code</th><th>Product</th><th>Unit Price</th><th>Discount</th><th>Line Total</th><th></th>
                </tr></thead>
                <tbody id="cartTableBody"></tbody>
            </table>
        </div>
    </div>

    <!-- Totals -->
    <div class="qb-card qb-card--full" id="quoteTotalsCard">
        <h2>Quote Totals</h2>
        <div class="quote-totals">
            <p><strong>Subtotal</strong><span id="subtotalDisplay">$0.00</span></p>
            <p id="discountRow" style="display:none"><strong>Discount</strong><span id="discountDisplay">$0.00</span></p>
            <p><strong>Freight</strong><span id="freightDisplay">—</span></p>
            <p><strong>Total</strong><span id="totalDisplay">$0.00</span></p>
            <p class="quote-note" style="font-size:11px;border-bottom:none">Freight is entered manually on each quote.</p>
        </div>
    </div>

    <!-- Preview -->
    <div class="qb-card qb-card--full" id="quotePreviewCard" style="display:none">
        <h2>Quote Preview</h2>
        <div id="quotePreviewContent"></div>
    </div>

</div><!-- .qb-grid -->

<!-- Action bar -->
<div class="qb-actions-wrapper">
    <p class="quote-helper-text">Saving creates a HubSpot Deal and generates a Quote Number.</p>
    <div class="qb-actions">
        <button type="button" id="clearCartBtn">Clear</button>
        <button type="button" id="previewQuoteBtn">Preview Draft</button>
        <button type="button" id="printQuoteBtn">Print / PDF</button>
        <button type="button" id="submitQuoteBtn">Save &amp; Generate Quote #</button>
    </div>
    <div style="margin-top:16px;text-align:center">
        <button type="button" id="bottomNewQuoteBtn" class="sp-btn sp-btn-ghost" style="font-size:13px;padding:10px 28px">
            ＋ Start New Quote
        </button>
    </div>
</div>

</form>


<script>
const SMTI_API_BASE  = <?php echo wp_json_encode( rtrim( rest_url( 'smti-sales/v1' ), '/' ) ); ?>;
const SMTI_API_TOKEN = '';
const SP_AJAX_URL    = <?php echo wp_json_encode( $ajax_url ); ?>;
const FREIGHT_PRODUCT_CODE = 'FREIGHT';

window.smtiProducts = <?php echo wp_json_encode( array_values( $products ) ); ?>;
// Stable array index per product so the Add button resolves to the EXACT row the
// user clicked. Product codes are not unique (two distinct products can share a
// code+location, e.g. 830-550 / 850-000), so resolving by code+location alone can
// add the wrong-priced item. Index resolution eliminates that ambiguity.
window.smtiProducts.forEach(function(p, i){ p.__idx = i; });
window.smtiDistributors = <?php echo wp_json_encode( array_values( $distributors ) ); ?>;

let cart = [];
let selectedContactId = null;
let addressSearchTimer = null;
let latestAddressQuery = '';
let currentRevisionList = [];

const helperMessage = "<p class='product-helper'>Start typing to search products.</p>";

// ── Toast & Confirm helpers ──────────────────────────────────────────────────
function spToast(msg, type, duration) {
    type = type||'info'; duration = duration||3500;
    const c = document.getElementById('sp-toast-container');
    const t = document.createElement('div');
    t.className = 'sp-toast sp-toast-'+type;
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(function(){ t.classList.add('sp-toast-out'); setTimeout(function(){ t.remove(); }, 220); }, duration);
}
function spToastSuccess(msg){ spToast(msg,'success'); }
function spToastError(msg){ spToast(msg,'error',5000); }
function spToastInfo(msg){ spToast(msg,'info'); }
function spToastWarn(msg){ spToast(msg,'warn',4500); }

function spConfirm(title, msg, okLabel, onOk) {
    okLabel = okLabel||'Confirm';
    document.getElementById('sp-confirm-title').textContent = title;
    document.getElementById('sp-confirm-msg').textContent   = msg;
    document.getElementById('sp-confirm-ok').textContent    = okLabel;
    const overlay = document.getElementById('sp-confirm-overlay');
    overlay.classList.add('active');
    function cleanup(){ overlay.classList.remove('active'); }
    document.getElementById('sp-confirm-ok').onclick     = function(){ cleanup(); onOk(); };
    document.getElementById('sp-confirm-cancel').onclick = cleanup;
    overlay.onclick = function(e){ if(e.target===overlay) cleanup(); };
}

// ── API helpers ──────────────────────────────────────────────────────────────

function smtiApiFetch(url, options) {
    return fetch(url, options || {});
}

function spAjax(action, data, callback) {
    const body = new FormData();
    body.append('action', 'sp_smti_sales_' + action);
    if (data) {
        Object.keys(data).forEach(function(k) {
            body.append(k, typeof data[k] === 'object' ? JSON.stringify(data[k]) : data[k]);
        });
    }
    fetch(SP_AJAX_URL, {method: 'POST', body: body})
        .then(function(r) { return r.json(); })
        .then(function(r) { callback(null, r.success ? r.data : null, r.success ? null : (r.data && r.data.message ? r.data.message : 'Request failed')); })
        .catch(function(e) { callback(e, null, e.message); });
}

function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', {style:'currency',currency:'USD',minimumFractionDigits:2}).format(Number(value)||0);
}
function money(v) { return formatCurrency(Number(v)||0); }
function toMoneyNumber(v) { const n = parseFloat(v); return Number.isFinite(n) ? n : 0; }
function safeDiscountType(v) { const r = String(v||'').trim().toLowerCase(); return (r==='percent'||r==='dollar'||r==='none') ? r : 'none'; }

function normalizeCartItemDiscount(item) {
    if (!item || item.is_freight) {
        if (item) { item.item_discount_type='none'; item.item_discount_value=0; item.item_discount_percent=0; item.item_discount_amount=0; }
        return item;
    }
    const rawLineTotal = (Number(item.quantity)||1) * (Number(item.unit_price)||0);
    let dt = safeDiscountType(item.item_discount_type);
    let dv = Number(item.item_discount_value);
    if (!Number.isFinite(dv)) dv = 0;
    if (!dt || dt==='none') {
        const lp = Number(item.item_discount_percent)||0;
        if (lp > 0) { dt='percent'; dv=lp; } else { dt='none'; dv=0; }
    }
    let da=0, ep=0;
    if (dt==='percent') { dv=Math.max(0,Math.min(100,dv)); da=rawLineTotal*(dv/100); ep=dv; }
    else if (dt==='dollar') { dv=Math.max(0,dv); da=Math.min(dv,rawLineTotal); ep=rawLineTotal>0?(da/rawLineTotal)*100:0; }
    else { dt='none'; dv=0; da=0; ep=0; }
    item.item_discount_type=dt; item.item_discount_value=dv; item.item_discount_amount=da; item.item_discount_percent=ep;
    return item;
}

function discountLabel(item) {
    if (!item) return '—';
    const type = item.discount_type || item.item_discount_type || 'none';
    const val  = Number(item.discount_value != null ? item.discount_value : (item.item_discount_value||0));
    if (type==='percent' && val>0) return val+'%';
    if (type==='dollar'  && val>0) return money(val);
    return '—';
}

function fillDistributorFields(d) {
    const parts = String(d.name||'').trim().split(' ');
    document.getElementById('distributor_company').value    = d.company||'';
    document.getElementById('distributor_first_name').value = parts[0]||'';
    document.getElementById('distributor_last_name').value  = parts.length>1 ? parts.slice(1).join(' ') : '';
    document.getElementById('distributor_email').value      = d.email||'';
    document.getElementById('distributor_phone').value      = d.phone||'';
}

function getSelectedLocation() { return String(document.getElementById('quote_location').value||'').trim().toLowerCase(); }

function createFreightItem() {
    return {product_code:FREIGHT_PRODUCT_CODE,product_title:'Freight',category:'Shipping',product_description:'Manual freight.',unit_price:'',dealer_price:'',location:getSelectedLocation()||'us',active:true,allow_discount:false,max_discount_percent:0,collateral_file:'',quantity:1,item_discount_type:'none',item_discount_value:0,item_discount_percent:0,item_discount_amount:0,is_freight:true};
}
function getFreightItem() { return cart.find(function(i){return i.is_freight===true||String(i.product_code||'').toUpperCase()===FREIGHT_PRODUCT_CODE;}); }
function ensureFreightItem() {
    let fi = getFreightItem();
    if (!fi) { fi = createFreightItem(); cart.push(fi); }
    fi.is_freight=true; fi.product_code=FREIGHT_PRODUCT_CODE; fi.product_title='Freight'; fi.quantity=1;
    fi.item_discount_type='none'; fi.item_discount_value=0; fi.item_discount_percent=0; fi.item_discount_amount=0;
    fi.location=getSelectedLocation()||fi.location||'us';
    return fi;
}
function resetCartToDefault() { cart=[]; ensureFreightItem(); }
function getNonFreightItems() { return cart.filter(function(i){return !i.is_freight;}); }

// ── Product search ────────────────────────────────────────────────────────────

function getFilteredProducts(query) {
    const q = String(query||'').trim().toLowerCase();
    const loc = getSelectedLocation();
    return window.smtiProducts.filter(function(p) {
        return p.active == 1 &&
            String(p.product_code||'').toUpperCase() !== FREIGHT_PRODUCT_CODE &&
            String(p.location||'').toLowerCase() === loc &&
            (String(p.product_code||'').toLowerCase().includes(q) ||
             String(p.product_title||'').toLowerCase().includes(q) ||
             String(p.product_description||'').toLowerCase().includes(q));
    });
}

function isProductInCart(code, title, location) {
    return cart.some(function(i){ return !i.is_freight
        && String(i.product_code)===String(code)
        && String(i.product_title||'').toLowerCase()===String(title||'').toLowerCase()
        && String(i.location||'').toLowerCase()===String(location||'').toLowerCase(); });
}

function productActionsHtml(inCart, idx) {
    return inCart
        ? `<button type="button" disabled>Added</button><span class="add-status">✓</span>`
        : `<button type="button" onclick="addProductByIndex(${idx})">Add</button>`;
}

function renderProducts(products) {
    const container = document.getElementById('productResults');
    container.innerHTML = '';
    if (!products.length) { container.innerHTML = "<p class='product-helper'>No products found.</p>"; return; }
    products.forEach(function(p) {
        const inCart = isProductInCart(p.product_code, p.product_title, p.location);
        const idx = (typeof p.__idx === 'number') ? p.__idx : window.smtiProducts.indexOf(p);
        const div = document.createElement('div');
        div.className = 'product-item';
        div.dataset.code = p.product_code || '';
        div.dataset.title = p.product_title || '';
        div.dataset.loc = p.location || '';
        div.dataset.idx = idx;
        div.innerHTML = `<div class="product-row"><div class="product-main"><div class="product-code">${p.product_code||''}</div><div class="product-title">${p.product_title||''}</div>${p.product_description?`<div class="product-description">${p.product_description}</div>`:''}</div><div class="product-side"><div class="product-price">${formatCurrency(Number(p.unit_price||0))}</div><div class="product-actions">${productActionsHtml(inCart, idx)}</div></div></div>`;
        container.appendChild(div);
    });
}

// Update only the Add/Added button of each already-rendered row in place, without
// wiping and rebuilding the whole list. Called after cart changes so the button
// the user just clicked isn't destroyed mid-click (which caused the "click a couple
// times before it registers" glitch) and the scroll position is preserved.
function refreshProductButtons() {
    const container = document.getElementById('productResults');
    if (!container) return;
    container.querySelectorAll('.product-item').forEach(function(row){
        const actions = row.querySelector('.product-actions');
        if (!actions) return;
        const inCart = isProductInCart(row.dataset.code, row.dataset.title, row.dataset.loc);
        actions.innerHTML = productActionsHtml(inCart, row.dataset.idx);
    });
}

// Add the EXACT product at this index in window.smtiProducts. Resolving by index
// (not code+location) guarantees the row the user clicked is the row that's added,
// even when two products share a code+location. Cart-line identity is code+title+
// location so a same-code-different-title product is its own line, and re-adding
// the same product increments its quantity.
window.addProductByIndex = function(idx) {
    const err = document.getElementById('productsError'); if (err) err.remove();
    ensureFreightItem();
    const product = window.smtiProducts[Number(idx)];
    if (!product || String(product.product_code||'').toUpperCase()===FREIGHT_PRODUCT_CODE) return;
    const existing = cart.find(function(i){ return !i.is_freight
        && String(i.product_code).trim()===String(product.product_code).trim()
        && String(i.product_title||'').toLowerCase()===String(product.product_title||'').toLowerCase()
        && String(i.location||'').toLowerCase()===String(product.location||'').toLowerCase(); });
    if (existing) { existing.quantity+=1; normalizeCartItemDiscount(existing); }
    else {
        const ni = Object.assign({},product,{quantity:1,item_discount_type:'none',item_discount_value:0,item_discount_percent:0,item_discount_amount:0,is_freight:false});
        normalizeCartItemDiscount(ni); cart.push(ni);
    }
    renderCart();
    refreshProductButtons();
    document.getElementById('product_search').focus();
};

// Back-compat shim: older cached pages / any external caller may still invoke
// addToCartByCode(code, location). Resolve to the first matching product's index
// and delegate. New code paths use addProductByIndex directly.
window.addToCartByCode = function(productCode, location) {
    const i = window.smtiProducts.findIndex(function(p){ return String(p.product_code).trim()===String(productCode).trim() && String(p.location||'').toLowerCase()===String(location||'').toLowerCase(); });
    if (i >= 0) window.addProductByIndex(i);
};

function getDiscountSummaryText(item) {
    normalizeCartItemDiscount(item);
    if (item.item_discount_type==='percent' && item.item_discount_value>0) return item.item_discount_value+'%';
    if (item.item_discount_type==='dollar'  && item.item_discount_value>0) return formatCurrency(item.item_discount_value);
    return '—';
}

// ── Cart render ───────────────────────────────────────────────────────────────

function renderCart() {
    ensureFreightItem();
    const tbody = document.getElementById('cartTableBody');
    tbody.innerHTML = '';
    const regular = cart.filter(function(i){return !i.is_freight;});
    const fi = getFreightItem();
    let subtotal=0, totalDiscount=0, totalAfterDiscount=0;

    regular.forEach(function(item) {
        normalizeCartItemDiscount(item);
        const idx = cart.indexOf(item);
        const qty = Number(item.quantity)||1;
        const price = Number(item.unit_price)||0;
        const raw = qty*price, da = Number(item.item_discount_amount)||0, lt = raw-da;
        subtotal+=raw; totalDiscount+=da; totalAfterDiscount+=lt;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="number" value="${qty}" min="1" style="width:60px;border:1px solid #e2e8f0;border-radius:6px;padding:4px;font-size:12px;text-align:center" onchange="updateQty(${idx},this.value)"></td>
            <td style="font-size:11px;font-weight:700;color:#64748b">${item.product_code}</td>
            <td>${item.product_title}</td>
            <td>${formatCurrency(price)}</td>
            <td>
                <div class="discount-cell">
                    <select class="discount-type-select" onchange="updateItemDiscountType(${idx},this.value)">
                        <option value="none" ${item.item_discount_type==='none'?'selected':''}>None</option>
                        <option value="percent" ${item.item_discount_type==='percent'?'selected':''}>%</option>
                        <option value="dollar" ${item.item_discount_type==='dollar'?'selected':''}>$</option>
                    </select>
                    <input type="number" class="discount-value-input" value="${item.item_discount_type==='none'?'':(Number(item.item_discount_value)||0)}" min="0" step="0.01" onchange="updateItemDiscountValue(${idx},this.value)">
                </div>
                <span class="discount-help">Applied: ${getDiscountSummaryText(item)}</span>
            </td>
            <td>${formatCurrency(lt)}</td>
            <td><button type="button" onclick="removeItem(${idx})">✕</button></td>
        `;
        tbody.appendChild(tr);
    });

    const fa = fi ? toMoneyNumber(fi.unit_price) : 0;
    const fv = fi && fi.unit_price !== '' ? fi.unit_price : '';
    const freightTr = document.createElement('tr');
    freightTr.className = 'freight-row';
    freightTr.innerHTML = `
        <td>1</td>
        <td style="font-size:11px;font-weight:700;color:#94a3b8">FREIGHT</td>
        <td style="color:#94a3b8">Freight</td>
        <td><input type="number" class="freight-price-input" value="${fv}" min="0" step="0.01" placeholder="Enter freight" style="width:100px;border:1px solid #e2e8f0;border-radius:6px;padding:4px 6px;font-size:12px" onchange="updateFreightAmount(this.value)"></td>
        <td><span class="freight-static">—</span></td>
        <td>${fa>0?formatCurrency(fa):'—'}</td>
        <td><span class="freight-static">—</span></td>
    `;
    tbody.appendChild(freightTr);
    updateTotals(subtotal, totalDiscount, totalAfterDiscount, fa);
}

window.updateFreightAmount = function(v) { const fi=ensureFreightItem(); fi.unit_price=v===''?'':Math.max(0,parseFloat(v)||0); renderCart(); };
window.updateItemDiscountType = function(idx,v) {
    if (!cart[idx]||cart[idx].is_freight) return;
    cart[idx].item_discount_type = safeDiscountType(v);
    if (cart[idx].item_discount_type==='none') cart[idx].item_discount_value=0;
    normalizeCartItemDiscount(cart[idx]); renderCart();
};
window.updateItemDiscountValue = function(idx,v) {
    if (!cart[idx]||cart[idx].is_freight) return;
    const n = parseFloat(v); cart[idx].item_discount_value = Number.isFinite(n)?Math.max(0,n):0;
    if (!cart[idx].item_discount_type||cart[idx].item_discount_type==='none') cart[idx].item_discount_type='percent';
    normalizeCartItemDiscount(cart[idx]); renderCart();
};
window.updateQty = function(idx,qty) { cart[idx].quantity=parseInt(qty,10)||1; normalizeCartItemDiscount(cart[idx]); renderCart(); };
window.removeItem = function(idx) {
    if (cart[idx]&&cart[idx].is_freight) return;
    cart.splice(idx,1); renderCart();
    refreshProductButtons();
};

function getQuoteTotals() {
    ensureFreightItem();
    let sub=0, disc=0, freight=0, total=0, hasDc=false;
    cart.forEach(function(item) {
        if (item.is_freight) { freight+=toMoneyNumber(item.unit_price); return; }
        normalizeCartItemDiscount(item);
        const raw=(Number(item.quantity)||1)*(Number(item.unit_price)||0);
        const da=Number(item.item_discount_amount)||0;
        sub+=raw; disc+=da; total+=(raw-da);
        if (da>0) hasDc=true;
    });
    total+=freight;
    return {subtotal:sub,discountAmount:disc,freightAmount:freight,total:total,hasDiscount:hasDc};
}

function updateTotals(subtotal, totalDiscount, totalAfterDiscount, freightAmount) {
    document.getElementById('subtotalDisplay').innerText = formatCurrency(subtotal);
    const dr = document.getElementById('discountRow');
    if (totalDiscount>0) { dr.style.display='flex'; document.getElementById('discountDisplay').innerText=formatCurrency(totalDiscount); } else { dr.style.display='none'; }
    document.getElementById('freightDisplay').innerText = freightAmount>0 ? formatCurrency(freightAmount) : '—';
    document.getElementById('totalDisplay').innerText = formatCurrency((totalAfterDiscount||0)+(freightAmount||0));
}

// ── Validation ────────────────────────────────────────────────────────────────

function clearValidation() {
    document.querySelectorAll('.qb-field').forEach(function(f){ f.classList.remove('has-error'); const e=f.querySelector('.qb-field-error'); if(e) e.remove(); });
    const pe=document.getElementById('productsError'); if(pe) pe.remove();
}
function showFieldError(fieldId, msg) {
    const input=document.getElementById(fieldId); if (!input) return;
    const field=input.closest('.qb-field'); if (!field) return;
    field.classList.add('has-error');
    let err=field.querySelector('.qb-field-error');
    if (!err) { err=document.createElement('div'); err.className='qb-field-error'; field.appendChild(err); }
    err.textContent=msg;
}
function validateQuoteForm() {
    clearValidation();
    const email=document.getElementById('customer_email').value.trim();
    const first=document.getElementById('customer_first_name').value.trim();
    const last=document.getElementById('customer_last_name').value.trim();
    const acct=document.getElementById('account_name').value.trim();
    if (!selectedContactId && !email) { showFieldError('customer_email','Customer email is required'); document.getElementById('customer_email').scrollIntoView({behavior:'smooth',block:'center'}); return false; }
    if (!selectedContactId && !first && !last && !acct) { showFieldError('customer_first_name','Enter a name or account'); return false; }
    if (!getNonFreightItems().length) {
        const pc=document.getElementById('productsCard');
        let pe=document.getElementById('productsError');
        if (!pe) { pe=document.createElement('div'); pe.id='productsError'; pe.className='qb-form-alert'; pc.insertBefore(pe,pc.children[1]); }
        pe.textContent='Add at least one product before saving.'; pc.scrollIntoView({behavior:'smooth',block:'start'}); return false;
    }
    return true;
}

// ── Build payload ─────────────────────────────────────────────────────────────

function buildDealPayload() {
    const totals = getQuoteTotals();
    const g = function(id){ return document.getElementById(id) ? (document.getElementById(id).value||'') : ''; };
    const cFirst=g('customer_first_name'), cLast=g('customer_last_name'), dFirst=g('distributor_first_name'), dLast=g('distributor_last_name');
    const acct=g('account_name'), base=(acct||[cFirst,cLast].join(' ').trim()||'SMTI Quote');
    return {
        current_deal_id: g('current_deal_id'),
        contact_id: selectedContactId||'',
        account_name: acct,
        customer_first_name: cFirst, customer_last_name: cLast,
        customer_name: [cFirst,cLast].join(' ').trim(),
        customer_email: g('customer_email'), customer_phone: g('customer_phone'),
        address1: g('mailing_address1'), address2: g('address2'),
        city: g('city'), state: g('state'), postal_code: g('postal_code'), country: g('country'),
        distributor_company: g('distributor_company'),
        distributor_first_name: dFirst, distributor_last_name: dLast,
        distributor_name: [dFirst,dLast].join(' ').trim(),
        distributor_email: g('distributor_email'), distributor_phone: g('distributor_phone'),
        shipping_method: g('shipping_method'), discount_reason: g('discount_reason'), notes: g('notes'),
        pricing_region: g('quote_location'),
        subtotal: totals.subtotal, discount_amount: totals.discountAmount,
        freight_amount: totals.freightAmount, total: totals.total,
        deal_name: base+' - SMTI Quote',
        items: cart.map(function(item) {
            if (item.is_freight) {
                const fa=toMoneyNumber(item.unit_price);
                return {product_code:FREIGHT_PRODUCT_CODE,product_title:'Freight',product_description:'Manual freight.',region:item.location||'',quantity:1,unit_price:fa,line_subtotal:fa,item_discount_type:'none',item_discount_value:0,item_discount_percent:0,item_discount_amount:0,line_total:fa,is_freight:true};
            }
            normalizeCartItemDiscount(item);
            const qty=Number(item.quantity)||1, up=Number(item.unit_price)||0, raw=qty*up, da=Number(item.item_discount_amount)||0;
            return {product_code:item.product_code||'',product_title:item.product_title||'',product_description:item.product_description||'',region:item.location||'',quantity:qty,unit_price:up,line_subtotal:raw,item_discount_type:safeDiscountType(item.item_discount_type),item_discount_value:Number(item.item_discount_value)||0,item_discount_percent:Number(item.item_discount_percent)||0,item_discount_amount:da,line_total:raw-da,is_freight:false};
        })
    };
}

// ── Populate form from quote JSON ─────────────────────────────────────────────

function fillCustomerFields(data) {
    const g = function(id,v){ const el=document.getElementById(id); if(el) el.value=v||''; };
    g('account_name', data.account_name||data.company_name||data.company||'');
    g('customer_first_name',data.customer_first_name); g('customer_last_name',data.customer_last_name);
    g('customer_email',data.customer_email); g('customer_phone',data.customer_phone);
    g('mailing_address1',data.address1); g('address2',data.address2);
    g('city',data.city); g('state',data.state); g('postal_code',data.postal_code); g('country',data.country);
}

function populateQuoteFromJson(payload, meta) {
    const g = function(id,v){ const el=document.getElementById(id); if(el) el.value=v||''; };
    g('current_deal_id',   payload.deal_id||(meta&&meta.deal_id)||'');
    g('existingDealId',    payload.deal_id||(meta&&meta.deal_id)||'');
    g('current_quote_number', (meta&&meta.quote_number)||payload.quote_number||'');
    selectedContactId = payload.contact_id||'';
    g('account_name',payload.account_name); g('customer_first_name',payload.customer_first_name); g('customer_last_name',payload.customer_last_name);
    g('customer_email',payload.customer_email); g('customer_phone',payload.customer_phone);
    g('mailing_address1',payload.address1); g('address2',payload.address2);
    g('city',payload.city); g('state',payload.state); g('postal_code',payload.postal_code); g('country',payload.country);
    g('distributor_company',payload.distributor_company); g('distributor_first_name',payload.distributor_first_name); g('distributor_last_name',payload.distributor_last_name);
    g('distributor_email',payload.distributor_email); g('distributor_phone',payload.distributor_phone);
    g('shipping_method',payload.shipping_method); g('discount_reason',payload.discount_reason); g('notes',payload.notes);
    g('quote_location',payload.pricing_region||'us');

    cart = [];
    if (Array.isArray(payload.items)) {
        payload.items.forEach(function(item) {
            if (String(item.product_code||'').toUpperCase()===FREIGHT_PRODUCT_CODE) {
                cart.push(Object.assign(createFreightItem(),{unit_price:item.unit_price||'',location:item.region||payload.pricing_region||'us'}));
            } else {
                const mp = window.smtiProducts.find(function(p){ return String(p.product_code||'').trim()===String(item.product_code||'').trim() && String(p.location||'').toLowerCase()===String(item.region||payload.pricing_region||'').toLowerCase(); });
                const li = {
                    product_code:item.product_code||'', product_title:item.product_title||(mp?mp.product_title:''),
                    product_description:item.product_description||(mp?mp.product_description:''), category:mp?mp.category:'',
                    unit_price:Number(item.unit_price)||0, dealer_price:mp?mp.dealer_price:'',
                    location:item.region||payload.pricing_region||'us', active:true,
                    allow_discount:mp?mp.allow_discount:true, max_discount_percent:mp?mp.max_discount_percent:100,
                    collateral_file:mp?mp.collateral_file:'', quantity:Number(item.quantity)||1,
                    item_discount_type:safeDiscountType(item.item_discount_type||(Number(item.item_discount_percent)>0?'percent':'none')),
                    item_discount_value:Number(item.item_discount_value)||(Number(item.item_discount_percent)||0),
                    item_discount_percent:Number(item.item_discount_percent)||0, item_discount_amount:Number(item.item_discount_amount)||0, is_freight:false
                };
                normalizeCartItemDiscount(li); cart.push(li);
            }
        });
    }
    ensureFreightItem(); renderCart();
    const q = document.getElementById('product_search').value;
    if (String(q).trim()) renderProducts(getFilteredProducts(q));
    else document.getElementById('productResults').innerHTML = helperMessage;
}

// ── Customer search ───────────────────────────────────────────────────────────

function hideAddressResults() {
    const ar = document.getElementById('addressSearchResults');
    if (!ar) return; ar.innerHTML=''; ar.classList.add('is-hidden'); ar.style.display='none';
}
function renderAddressResults(results) {
    const ar = document.getElementById('addressSearchResults'); if (!ar) return;
    ar.classList.remove('is-hidden'); ar.style.display='block';
    if (!results||!results.length) { ar.innerHTML=`<div class="address-search-empty">No match in HubSpot — complete the form and save to create a new contact.</div>`; return; }
    ar.innerHTML = results.map(function(item){
        const sc = String(item.company||item.company_name||item.account_name||'').replace(/"/g,'&quot;');
        const meta = [item.name||'', item.email||'', sc].filter(Boolean).join(' • ');
        return `<div class="address-search-item" data-contact-id="${item.contact_id||''}" data-company="${sc}"><div class="address-search-label">${item.label||''}</div><div class="address-search-meta">${meta}</div></div>`;
    }).join('');
}
function searchCustomers(query) {
    latestAddressQuery = query;
    const ar = document.getElementById('addressSearchResults');
    const url = SMTI_API_BASE+'/search-contacts?q='+encodeURIComponent(query);
    ar.classList.remove('is-hidden'); ar.style.display='block';
    ar.innerHTML = "<div class='address-search-item'>Searching…</div>";
    smtiApiFetch(url).then(function(r){ return r.json(); }).then(function(data){
        if (document.getElementById('address1').value.trim()!==latestAddressQuery) return;
        renderAddressResults(data.results||[]);
    }).catch(function(){ ar.innerHTML=`<div class="address-search-empty">Search failed. Check API connection.</div>`; });
}
function fetchContactDetails(contactId) {
    smtiApiFetch(SMTI_API_BASE+'/get-contact-details?contact_id='+encodeURIComponent(contactId))
        .then(function(r){return r.json();}).then(function(data){
            if (!data.found) { spToastError(data.message||'No contact found.'); return; }
            fillCustomerFields(data); hideAddressResults();
        }).catch(function(){ spToastError('Could not load customer details.'); });
}

// ── Revisions ─────────────────────────────────────────────────────────────────

function formatSavedAt(ts) {
    if (!ts) return ''; const d=new Date(Number(ts)); return Number.isNaN(d.getTime())?'':d.toLocaleString();
}
function fetchQuoteRevisions(dealId) {
    if (!dealId) return;
    const card=document.getElementById('revisionHistoryCard'), cont=document.getElementById('revisionHistoryContent');
    if (!card||!cont) return;
    card.style.display='block'; cont.innerHTML="<p class='product-helper'>Loading…</p>";
    smtiApiFetch(SMTI_API_BASE+'/get-quote-revisions?deal_id='+encodeURIComponent(dealId))
        .then(function(r){return r.json();}).then(function(data){
            if (!data.success) { cont.innerHTML="<p class='product-helper'>Could not load revisions.</p>"; return; }
            renderRevisionHistory(data.revisions||[], dealId);
        }).catch(function(){ cont.innerHTML="<p class='product-helper'>Could not load revisions.</p>"; });
}
function renderRevisionHistory(revisions, dealId) {
    const card=document.getElementById('revisionHistoryCard'), cont=document.getElementById('revisionHistoryContent');
    if (!card||!cont) return;
    if (!revisions.length) { cont.innerHTML="<p class='product-helper'>No revisions yet.</p>"; card.style.display='block'; return; }
    cont.innerHTML='<div class="revision-history-list">'+revisions.map(function(r){
        return `<div class="revision-history-item"><div class="revision-history-main"><div class="revision-history-title">${r.quote_number}${r.is_current_revision==='true'?'<span class="revision-history-badge">Current</span>':''}</div><div class="revision-history-meta">Rev ${r.revision_number} • ${r.quote_status||'Saved'} • ${r.line_count||0} line(s) • ${formatSavedAt(r.saved_at)}</div></div><div class="revision-history-actions"><button type="button" onclick="loadQuoteRevisionByNumber('${dealId}','${r.quote_number}')">Load</button></div></div>`;
    }).join('')+'</div>';
    currentRevisionList = revisions;
    renderRevisionCompareToolbar(revisions, dealId);
    card.style.display='block';
    document.getElementById('topRevisionHistoryBtn').style.display='inline-flex';
    document.getElementById('topCompareQuotesBtn').style.display='inline-flex';
}
window.loadQuoteRevisionByNumber = function(dealId, quoteNumber) {
    smtiApiFetch(SMTI_API_BASE+'/get-quote-revision?deal_id='+encodeURIComponent(dealId)+'&quote_number='+encodeURIComponent(quoteNumber))
        .then(function(r){return r.json();}).then(function(data){
            if (!data.success||!data.quote_json) { spToastError(data.message||'Could not load revision.'); return; }
            populateQuoteFromJson(data.quote_json, data); spToastSuccess('Revision loaded: '+data.quote_number);
        }).catch(function(){ spToastError('Error loading revision.'); });
};
function renderRevisionCompareToolbar(revisions, dealId) {
    const card=document.getElementById('revisionCompareCard'), cont=document.getElementById('revisionCompareContent');
    if (!card||!cont) return;
    if (revisions.length<2) { cont.innerHTML="<p class='product-helper'>Need at least two revisions to compare.</p>"; card.style.display='block'; return; }
    const opts=revisions.map(function(r){return `<option value="${r.quote_number}">${r.quote_number}</option>`;}).join('');
    cont.innerHTML=`<div class="revision-compare-toolbar"><select id="compareFromRevision">${opts}</select><select id="compareToRevision">${opts}</select><button type="button" id="runRevisionCompareBtn">Compare</button></div><div id="revisionCompareResults"><p class="product-helper">Choose two revisions and click Compare.</p></div>`;
    document.getElementById('compareFromRevision').value=revisions[revisions.length>1?1:0].quote_number;
    document.getElementById('compareToRevision').value=revisions[0].quote_number;
    document.getElementById('runRevisionCompareBtn').addEventListener('click',function(){
        const fr=document.getElementById('compareFromRevision').value, to=document.getElementById('compareToRevision').value;
        if (!fr||!to||fr===to) { spToastWarn('Choose two different revisions.'); return; }
        fetchRevisionCompare(dealId, fr, to);
    });
    card.style.display='block';
}
function fetchRevisionCompare(dealId, from, to) {
    const res=document.getElementById('revisionCompareResults'); if(res) res.innerHTML="<p class='product-helper'>Comparing…</p>";
    smtiApiFetch(SMTI_API_BASE+'/get-quote-revision-compare?deal_id='+encodeURIComponent(dealId)+'&from_quote_number='+encodeURIComponent(from)+'&to_quote_number='+encodeURIComponent(to))
        .then(function(r){return r.json();}).then(function(data){
            if (!data.success||!res) return;
            const ft=data.summary&&data.summary.from||{}, tt=data.summary&&data.summary.to||{}, ct=data.summary&&data.summary.counts||{};
            res.innerHTML=`<div class="revision-compare-summary"><div class="revision-compare-box"><h3>From: ${data.from_quote_number}</h3><p><strong>Subtotal:</strong> ${money(ft.subtotal)}</p><p><strong>Discount:</strong> ${money(ft.discount)}</p><p><strong>Freight:</strong> ${money(ft.freight)}</p><p><strong>Total:</strong> ${money(ft.total)}</p></div><div class="revision-compare-box"><h3>To: ${data.to_quote_number}</h3><p><strong>Subtotal:</strong> ${money(tt.subtotal)}</p><p><strong>Discount:</strong> ${money(tt.discount)}</p><p><strong>Freight:</strong> ${money(tt.freight)}</p><p><strong>Total:</strong> ${money(tt.total)}</p></div></div><div class="revision-compare-box" style="margin-bottom:14px"><h3>Changes</h3><p><strong>Added:</strong> ${ct.added||0} &nbsp; <strong>Removed:</strong> ${ct.removed||0} &nbsp; <strong>Changed:</strong> ${ct.changed||0} &nbsp; <strong>Unchanged:</strong> ${ct.unchanged||0}</p></div><div class="revision-diff-list">${(data.diffs||[]).map(function(d){ const fi=d.from,ti=d.to; return `<div class="revision-diff-item revision-diff-item--${d.change_type}"><div class="revision-diff-title">${(ti&&(ti.product_code||ti.product_title))||(fi&&(fi.product_code||fi.product_title))||d.key}</div><div class="revision-diff-meta">Type: <strong>${d.change_type}</strong>${d.changes&&d.changes.length?' • '+d.changes.join(', '):''}</div><div class="revision-diff-grid"><div class="revision-diff-col"><strong>From</strong>${fi?`<p>Qty: ${fi.quantity}</p><p>Price: ${money(fi.unit_price)}</p><p>Disc: ${discountLabel(fi)}</p><p>Total: ${money(fi.line_total)}</p>`:'<p>Not present</p>'}</div><div class="revision-diff-col"><strong>To</strong>${ti?`<p>Qty: ${ti.quantity}</p><p>Price: ${money(ti.unit_price)}</p><p>Disc: ${discountLabel(ti)}</p><p>Total: ${money(ti.line_total)}</p>`:'<p>Not present</p>'}</div></div></div>`;}).join('')}</div>`;
        }).catch(function(){ if(res) res.innerHTML="<p class='product-helper'>Compare failed.</p>"; });
}

// ── Quote preview / print ─────────────────────────────────────────────────────

function buildPreviewHtml() {
    const totals = getQuoteTotals();
    const g = function(id){ return document.getElementById(id) ? (document.getElementById(id).value||'') : ''; };
    const region=g('quote_location').toUpperCase(), dFirst=g('distributor_first_name'), dLast=g('distributor_last_name');
    const distName=[dFirst,dLast].join(' ').trim()||'—', distEmail=g('distributor_email').trim(), shipMethod=g('shipping_method').trim(), discReason=g('discount_reason').trim(), notes=g('notes').trim();
    const acct=g('account_name').trim()||'—', cFirst=g('customer_first_name'), cLast=g('customer_last_name');
    const custName=[cFirst,cLast].join(' ').trim()||'—', custEmail=g('customer_email').trim()||'—', custPhone=g('customer_phone').trim()||'—';
    const a1=g('mailing_address1').trim(), a2=g('address2'), city=g('city'), state=g('state'), zip=g('postal_code'), country=g('country');
    const addr=[a1,a2,[city,state,zip].filter(Boolean).join(city&&state?', ':' '),country].filter(Boolean).join('<br>')||'—';
    const today=new Date(), exp=new Date(today); exp.setDate(exp.getDate()+30);
    const qnum=g('current_quote_number')||'Draft Quote';
    const logoUrl = <?php echo wp_json_encode( $logo_url ); ?>;
    const companyName = <?php echo wp_json_encode( get_option( 'sp_smti_sales_company', 'Sport Medical Technology Inc' ) ); ?>;
    const companyAddress = <?php echo wp_json_encode( get_option( 'sp_smti_sales_address', "49 Natcon Dr\nShirley, NY 11967\nUnited States of America" ) ); ?>;
    const quoteTerms = <?php echo wp_json_encode( get_option( 'sp_smti_sales_terms', 'This Quotation is valid for 30 days.' ) ); ?>;

    const previewItems = cart.filter(function(i){return !i.is_freight;}).concat(cart.filter(function(i){return i.is_freight;}));
    const rows = previewItems.map(function(item){
        if (item.is_freight) {
            const fa=toMoneyNumber(item.unit_price); if (fa<=0) return '';
            return `<tr><td class="col-qty">1</td><td class="col-code">FREIGHT</td><td class="col-title">Freight</td><td class="col-price">${formatCurrency(fa)}</td>${totals.hasDiscount?'<td class="col-disc">—</td>':''}<td class="col-total">${formatCurrency(fa)}</td></tr>`;
        }
        normalizeCartItemDiscount(item);
        const qty=Number(item.quantity)||1, price=Number(item.unit_price)||0, lt=(qty*price)-(Number(item.item_discount_amount)||0);
        let dt='';
        if (item.item_discount_type==='percent'&&Number(item.item_discount_value)>0) dt=item.item_discount_value+'%';
        else if (item.item_discount_type==='dollar'&&Number(item.item_discount_value)>0) dt=formatCurrency(item.item_discount_value);
        return `<tr><td class="col-qty">${qty}</td><td class="col-code">${item.product_code||''}</td><td class="col-title">${item.product_title||''}</td><td class="col-price" style="text-align:right">${formatCurrency(price)}</td>${totals.hasDiscount?`<td class="col-disc" style="text-align:right">${dt}</td>`:''}<td class="col-total" style="text-align:right">${formatCurrency(lt)}</td></tr>`;
    }).join('');

    const optMeta=[
        distEmail?`<div class="quote-meta-label">Email</div><div class="quote-meta-value">${distEmail}</div>`:'',
        notes?`<div class="quote-meta-label">Notes</div><div class="quote-meta-value">${notes}</div>`:'',
        region?`<div class="quote-meta-label">Pricing Region</div><div class="quote-meta-value">${region}</div>`:'',
        shipMethod?`<div class="quote-meta-label">Shipping Method</div><div class="quote-meta-value">${shipMethod}</div>`:'',
        totals.hasDiscount&&discReason?`<div class="quote-meta-label">Discount Reason</div><div class="quote-meta-value">${discReason}</div>`:''
    ].join('');

    const addrFormatted = companyAddress.replace(/\n/g,'<br>');

    return `<div class="quote-sheet">
<div class="quote-top">
  <div>${logoUrl?`<img src="${logoUrl}" class="quote-logo" alt="${companyName}">`:`<div style="font-size:18px;font-weight:800;color:#1a202c">${companyName}</div>`}</div>
  <div style="text-align:right"><h1 class="quote-title">Quotation</h1><div class="quote-number-display">${qnum}</div></div>
</div>
<div class="quote-section-bar">Customer Contact:</div>
<div class="quote-meta-grid">
  <div class="quote-meta-table">
    <div class="quote-meta-label">Full Name</div><div class="quote-meta-value">${custName}</div>
    <div class="quote-meta-label">Account Name</div><div class="quote-meta-value">${acct}</div>
    <div class="quote-meta-label">Mailing Address</div><div class="quote-meta-value">${addr}</div>
    <div class="quote-meta-label">Phone</div><div class="quote-meta-value">${custPhone}</div>
    <div class="quote-meta-label">Email</div><div class="quote-meta-value">${custEmail}</div>
  </div>
  <div class="quote-meta-table">
    <div class="quote-meta-label">Quote Number</div><div class="quote-meta-value">${qnum}</div>
    <div class="quote-meta-label">Created Date</div><div class="quote-meta-value">${today.toLocaleDateString('en-US')}</div>
    <div class="quote-meta-label">Expiration Date</div><div class="quote-meta-value">${exp.toLocaleDateString('en-US')}</div>
    <div class="quote-meta-label">SMTI Contact</div><div class="quote-meta-value">${distName}</div>
    ${optMeta}
  </div>
</div>
<table class="quote-products-table quote-products-table--clean">
  <thead><tr>
    <th class="col-qty">Qty</th><th class="col-code">Product Code</th><th class="col-title">Product Title</th>
    <th class="col-price" style="text-align:right">Unit Price</th>${totals.hasDiscount?'<th class="col-disc" style="text-align:right">Discount</th>':''}
    <th class="col-total" style="text-align:right">Line Total</th>
  </tr></thead>
  <tbody>${rows}</tbody>
</table>
<div class="quote-totals-preview">
  <div class="quote-total-row"><span>Subtotal</span><span>${formatCurrency(totals.subtotal)}</span></div>
  ${totals.discountAmount>0?`<div class="quote-total-row"><span>Discount</span><span>${formatCurrency(totals.discountAmount)}</span></div>`:''}
  ${totals.freightAmount>0?`<div class="quote-total-row"><span>Freight</span><span>${formatCurrency(totals.freightAmount)}</span></div>`:''}
  <div class="quote-total-row"><strong>Total</strong><strong>${formatCurrency(totals.total)}</strong></div>
</div>
<div class="quote-section-bar">Terms &amp; Conditions:</div>
<div class="quote-terms-3col">
  <div>
    <p><strong>Financial Terms:</strong></p>
    <p><strong>Taxes:</strong> Plus sales tax where applicable</p>
    <p><strong>Institutions:</strong> 1.5% Net 10, Net 30 Days Subject to Credit Approval</p>
    <p><strong>Other Customers:</strong> 50% required on order placement, balance due prior to shipment or net 30, subject to credit approval</p>
    <p><strong>Shipping Terms:</strong> FOB Shirley, New York</p>
  </div>
  <div>
    <p><strong>Please remit payment to:</strong></p>
    <ul>
      <li><strong>Bank Name:</strong> JP Morgan Chase</li>
      <li><strong>Account#:</strong> 713690959</li>
      <li><strong>Type:</strong> Checking</li>
      <li><strong>Wire Routing #:</strong> 2100021</li>
      <li><strong>SWIFT:</strong> CHASUS33</li>
      <li><strong>City/State:</strong> New York, NY</li>
      <li><strong>ACH Routing #:</strong> 322271627</li>
    </ul>
  </div>
  <div class="quote-signature-block">
    <p><strong>Customer Approval as Quoted:</strong></p>
    <div class="sig-row"><span class="sig-label">Signature:</span><span class="sig-line"></span></div>
    <div class="sig-row"><span class="sig-label">Title:</span><span class="sig-line"></span></div>
    <div class="sig-row"><span class="sig-label">Date:</span><span class="sig-line"></span></div>
  </div>
</div>
<div class="quote-true-footer">
  <div class="quote-footer-divider"></div>
  <p class="quote-footer-text">${quoteTerms}</p>
  <div class="quote-footer-company">
    <div><strong>${companyName}</strong><br>${addrFormatted}</div>
    <div><a href="mailto:sales@smti.co">sales@smti.co</a><br>USA: +1 800 224 6339<br>International: +1 631 924 9000</div>
  </div>
</div>
</div>`;
}

function renderQuotePreview() {
    const card=document.getElementById('quotePreviewCard'), cont=document.getElementById('quotePreviewContent');
    if (!card||!cont) return;
    cont.innerHTML=buildPreviewHtml(); card.style.display='block';
    card.scrollIntoView({behavior:'smooth',block:'start'});
}

// ── Event listeners ───────────────────────────────────────────────────────────

document.getElementById('distributor_select').addEventListener('change', function(){
    const d = window.smtiDistributors.find(function(x){ return String(x.slug)===String(this.value); }.bind(this));
    if (d) fillDistributorFields(d);
    else { ['distributor_company','distributor_first_name','distributor_last_name','distributor_email','distributor_phone'].forEach(function(id){ var el=document.getElementById(id); if(el) el.value=''; }); }
});

(function(){
    const ai=document.getElementById('address1'), ar=document.getElementById('addressSearchResults');
    if (!ai||!ar) return;
    ai.addEventListener('input', function(){
        selectedContactId=null; clearTimeout(addressSearchTimer);
        const q=this.value.trim();
        if (q.length<2) { hideAddressResults(); return; }
        ar.classList.remove('is-hidden'); ar.style.display='block';
        ar.innerHTML="<div class='address-search-item'>Searching…</div>";
        addressSearchTimer=setTimeout(function(){ searchCustomers(q); }, 300);
    });
    ar.addEventListener('click', function(e){
        const item=e.target.closest('.address-search-item'); if (!item) return;
        const contactId=item.getAttribute('data-contact-id'), company=item.getAttribute('data-company')||'';
        document.getElementById('account_name').value=company;
        if (contactId) { selectedContactId=contactId; fetchContactDetails(contactId); }
    });
    document.addEventListener('click', function(e){ if (!ar.contains(e.target)&&e.target!==ai) hideAddressResults(); });
})();

['customer_email','customer_first_name','customer_last_name','account_name'].forEach(function(id){
    const f=document.getElementById(id); if(!f) return;
    f.addEventListener('input',function(){ const w=f.closest('.qb-field'); if(!w) return; w.classList.remove('has-error'); const e=w.querySelector('.qb-field-error'); if(e) e.remove(); });
});

document.getElementById('product_search').addEventListener('input', function(){
    const q=this.value, loc=getSelectedLocation();
    if (!loc) { document.getElementById('productResults').innerHTML="<p class='product-helper'>Select pricing region first.</p>"; return; }
    if (!String(q).trim()) { document.getElementById('productResults').innerHTML=helperMessage; return; }
    renderProducts(getFilteredProducts(q));
});

document.getElementById('quote_location').addEventListener('change', function(){
    const fi=ensureFreightItem(); fi.location=this.value;
    document.getElementById('productResults').innerHTML=helperMessage; renderCart();
    document.getElementById('product_search').focus();
});

document.getElementById('clearSearchBtn').addEventListener('click', function(){
    document.getElementById('product_search').value=''; document.getElementById('productResults').innerHTML=helperMessage; document.getElementById('product_search').focus();
});

document.getElementById('loadQuoteBtn').addEventListener('click', function(){
    const id=(document.getElementById('existingDealId').value||'').trim(); if (!id) { spToastWarn('Enter a Quote Number (e.g. SMTI-202406-0001) or HubSpot Deal ID.'); return; }
    const isQuoteNum = /^SMTI-/i.test(id);
    const qs = isQuoteNum ? 'quote_number='+encodeURIComponent(id) : 'deal_id='+encodeURIComponent(id);
    smtiApiFetch(SMTI_API_BASE+'/get-deal-quote?'+qs).then(function(r){return r.json();}).then(function(data){
        if (!data.success||!data.quote_json) { spToastError(data.message||'Could not load quote.'); return; }
        populateQuoteFromJson(data.quote_json,data); fetchQuoteRevisions(data.deal_id||id);
        document.getElementById('topRevisionHistoryBtn').style.display='inline-flex';
        document.getElementById('topCompareQuotesBtn').style.display='inline-flex';
        spToastSuccess('Quote loaded: '+(data.quote_number||id));
    }).catch(function(){ spToastError('Error loading quote.'); });
});

document.getElementById('submitQuoteBtn').addEventListener('click', function(){
    if (!validateQuoteForm()) return;
    const btn=this; btn.disabled=true; btn.textContent=document.getElementById('current_deal_id').value?'Updating…':'Creating…';
    const payload=buildDealPayload();
    smtiApiFetch(SMTI_API_BASE+'/create-deal',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})
        .then(function(r){return r.json();}).then(function(data){
            if (!data.success) { spToastError(data.message||'Failed to save quote.'); btn.disabled=false; btn.textContent='Save & Generate Quote #'; return; }
            if (data.deal_id) { document.getElementById('current_deal_id').value=data.deal_id; document.getElementById('existingDealId').value=data.deal_id; fetchQuoteRevisions(data.deal_id); }
            if (data.contact_id) selectedContactId=data.contact_id;
            if (data.quote_number) document.getElementById('current_quote_number').value=data.quote_number;
            const sp=document.getElementById('quoteSuccessPanel'), sm=document.getElementById('quoteSuccessMeta'), sl=document.getElementById('quoteSuccessList');
            if (sp&&sm) {
                sp.classList.remove('is-hidden'); sp.style.display='';
                sm.innerText='Quote #: '+(data.quote_number||'—')+' | Deal ID: '+(data.deal_id||'—');
                if (sl) {
                    const items=[data.was_update?'Deal updated':'Deal created'];
                    if (data.contact_created||data.contact_was_created) items.push('Contact created');
                    else if (data.contact_was_found_by_email) items.push('Contact matched');
                    if (data.company_created||data.company_was_created) items.push('Company created');
                    else if (data.company_was_found_by_name) items.push('Company matched');
                    items.push('Stage: '+(data.deal_stage_label||'Quote Submitted'));
                    sl.innerHTML=items.map(function(i){return '<li>'+i+'</li>';}).join('');
                }
                sp.scrollIntoView({behavior:'smooth',block:'start'});
            }
            document.getElementById('topRevisionHistoryBtn').style.display='inline-flex';
            document.getElementById('topCompareQuotesBtn').style.display='inline-flex';
            // Also save locally via AJAX
            spAjax('save_quote', {payload: JSON.stringify(Object.assign({},payload,{deal_id:data.deal_id,quote_number:data.quote_number}))}, function(){});
            btn.disabled=false; btn.textContent='Save & Generate Quote #';
        }).catch(function(e){ spToastError('Error: '+e.message); btn.disabled=false; btn.textContent='Save & Generate Quote #'; });
});

function clearFullQuote() {
    // Hidden state
    ['current_deal_id','current_quote_number','existingDealId'].forEach(function(id){
        var el=document.getElementById(id); if(el) el.value='';
    });
    selectedContactId = null;

    // Customer fields
    ['address1','account_name','customer_first_name','customer_last_name',
     'customer_email','customer_phone','mailing_address1','address2',
     'city','state','postal_code','country'].forEach(function(id){
        var el=document.getElementById(id); if(el) el.value='';
    });
    var loc=document.getElementById('quote_location'); if(loc) loc.value='us';
    hideAddressResults();

    // Distributor fields
    ['distributor_first_name','distributor_last_name','distributor_email',
     'distributor_phone','shipping_method','discount_reason','notes'].forEach(function(id){
        var el=document.getElementById(id); if(el) el.value='';
    });
    var ds=document.getElementById('distributor_select'); if(ds) { ds.value=''; ds.dispatchEvent(new Event('change')); }
    var dc=document.getElementById('distributor_company'); if(dc) dc.value='';

    // Cart & products
    resetCartToDefault(); renderCart();
    document.getElementById('productResults').innerHTML=helperMessage;

    // Preview & history panels
    var pc=document.getElementById('quotePreviewCard'); if(pc) pc.style.display='none';
    var pcc=document.getElementById('quotePreviewContent'); if(pcc) pcc.innerHTML='';
    var rh=document.getElementById('revisionHistoryCard');
    if(rh){ rh.style.display='none'; document.getElementById('revisionHistoryContent').innerHTML="<p class='product-helper'>Load a quote to see revision history.</p>"; }
    var rc=document.getElementById('revisionCompareCard');
    if(rc){ rc.style.display='none'; document.getElementById('revisionCompareContent').innerHTML="<p class='product-helper'>Choose two revisions to compare.</p>"; }
    currentRevisionList=[];

    // Success panel & toolbar
    document.getElementById('quoteSuccessPanel').classList.add('is-hidden');
    document.getElementById('topRevisionHistoryBtn').style.display='none';
    document.getElementById('topCompareQuotesBtn').style.display='none';
    document.getElementById('loadQuoteCard').style.display='none';
    document.getElementById('topLoadQuoteToggleBtn').textContent='Open Quote';

    window.scrollTo({top:0, behavior:'smooth'});
}

document.getElementById('clearCartBtn').addEventListener('click', clearFullQuote);
document.getElementById('bottomNewQuoteBtn').addEventListener('click', clearFullQuote);

document.getElementById('previewQuoteBtn').addEventListener('click', function(){
    if (!getNonFreightItems().length) { spToastWarn('Add at least one product before previewing.'); return; }
    renderQuotePreview();
});

document.getElementById('printQuoteBtn').addEventListener('click', function(){
    if (!getNonFreightItems().length) { spToastWarn('Add at least one product before printing.'); return; }
    renderQuotePreview();
    const previewCard=document.getElementById('quotePreviewCard'), previewContent=document.getElementById('quotePreviewContent');
    if (!previewCard||!previewContent.innerHTML.trim()) { spToastWarn('Preview the quote first.'); return; }
    spToastInfo('Tip: Portrait layout — turn OFF Headers and Footers for best print results.');
    const win=window.open('','_blank','width=1400,height=1000');
    if (!win) { spToastError('Popup blocked — please allow popups for this site.'); return; }
    win.document.open();
    win.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Quotation</title><style>body{font-family:Arial,sans-serif;color:#2f3a45;margin:0;background:#fff;font-size:11px;line-height:1.25}'+
        '.quote-sheet{padding:0;background:#fff;color:#2f3a45;font-family:Arial,sans-serif}'+
        '.quote-top{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:12px}'+
        '.quote-logo{width:130px;max-width:100%;height:auto}'+
        '.quote-title{margin:0;font-size:34px;line-height:1;font-weight:300;color:#5a5a5a;text-align:right}'+
        '.quote-number-display{margin-top:8px;font-size:13px;font-weight:700;color:#123b63;text-align:right}'+
        '.quote-section-bar{margin:30px 0 12px;padding:10px 12px;background:#dbe3eb;color:#33485c;font-size:12px;font-weight:600}'+
        '.quote-meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:40px}'+
        '.quote-meta-table{display:grid;grid-template-columns:115px 1fr;column-gap:8px;row-gap:4px;font-size:11px;line-height:1.25}'+
        '.quote-meta-label{color:#33485c;font-weight:600}.quote-meta-value{color:#3e4c59;word-break:break-word}'+
        '.quote-products-table{width:100%;margin-top:20px;border-collapse:collapse}'+
        '.quote-products-table th{padding:6px 8px;border-right:1px solid #777;background:#8a8a8a;color:#fff;text-align:left;font-size:11px;font-weight:600}'+
        '.quote-products-table td{padding:6px 8px;border-right:1px solid #d7dde3;border-bottom:1px solid #d7dde3;vertical-align:top;font-size:11px;color:#33485c}'+
        '.quote-products-table th:last-child,.quote-products-table td:last-child{border-right:none}'+
        '.quote-products-table tbody tr:nth-child(even) td{background:#f2f2f2}'+
        '.col-qty{width:50px}.col-code{width:110px}.col-price,.col-total{width:100px;white-space:nowrap;text-align:right}.col-disc{width:90px;text-align:right}'+
        '.quote-totals-preview{width:280px;margin-left:auto;margin-top:42px;margin-bottom:20px}'+
        '.quote-total-row{display:flex;justify-content:space-between;gap:16px;padding:2px 0;font-size:11px;color:#33485c}'+
        '.quote-total-row strong{font-size:12px;color:#1f2d3a}'+
        '.quote-terms-3col{display:grid;grid-template-columns:1fr 1fr 1.15fr;gap:18px;margin-top:12px;margin-bottom:12px}'+
        '.quote-terms-3col p,.quote-terms-3col li{margin:0 0 4px;font-size:10px;line-height:1.25;color:#444}'+
        '.quote-terms-3col ul{margin:2px 0 0 14px;padding:0}'+
        '.sig-row{display:grid;grid-template-columns:60px 1fr;align-items:end;column-gap:8px;margin-bottom:8px}'+
        '.sig-label{font-weight:400;font-size:10px;color:#444}.sig-line{display:block;width:100%;height:12px;border-bottom:1px solid #b7b7b7}'+
        '.quote-true-footer{margin-top:36px}.quote-footer-divider{margin-bottom:8px;border-top:1px solid #d7dde3}'+
        '.quote-footer-text{margin:0 0 8px;font-size:9.5px;line-height:1.25;color:#555}'+
        '.quote-footer-company{display:grid;grid-template-columns:1fr 1fr;gap:18px;font-size:9.5px;line-height:1.25;color:#555}'+
        '.quote-footer-company a{color:#1a5fb4;text-decoration:none}'+
        '@page{size:portrait;margin:.5in}'+
        '</style></head><body>'+buildPreviewHtml()+'</body></html>');
    win.document.close();
    const imgs=win.document.images;
    if (!imgs.length) { setTimeout(function(){win.focus();win.print();},300); return; }
    let loaded=0; const total=imgs.length;
    Array.from(imgs).forEach(function(img){
        if (img.complete) { loaded++; if(loaded===total) setTimeout(function(){win.focus();win.print();},300); }
        else { img.onload=img.onerror=function(){ loaded++; if(loaded===total) setTimeout(function(){win.focus();win.print();},300); }; }
    });
});

// ── Toolbar navigation ────────────────────────────────────────────────────────

function scrollToCard(id) {
    const el=document.getElementById(id); if (!el) return;
    const y=el.getBoundingClientRect().top+window.pageYOffset-20;
    window.scrollTo({top:y,behavior:'smooth'});
}
function setActiveTopButton(id) {
    document.querySelectorAll('.qb-toolbar button').forEach(function(b){b.classList.remove('active-tab');});
    const b=document.getElementById(id); if(b) b.classList.add('active-tab');
}

document.getElementById('topNewQuoteBtn').addEventListener('click', function(){
    clearFullQuote();
    scrollToCard('customerCard');
    document.getElementById('address1').focus();
});
document.getElementById('topLoadQuoteToggleBtn').addEventListener('click', function(){
    const card=document.getElementById('loadQuoteCard');
    const isHidden=card.style.display==='none'||card.style.display==='';
    card.style.display=isHidden?'block':'none';
    this.textContent=isHidden?'Hide Load Quote':'Open Quote';
    if (isHidden) { scrollToCard('loadQuoteCard'); document.getElementById('existingDealId').focus(); }
});
document.getElementById('topCustomerBtn').addEventListener('click', function(){ scrollToCard('customerCard'); setActiveTopButton('topCustomerBtn'); });
document.getElementById('topProductsBtn').addEventListener('click', function(){ scrollToCard('productsCard'); setActiveTopButton('topProductsBtn'); document.getElementById('product_search').focus(); });
document.getElementById('topPreviewBtn').addEventListener('click', function(){ scrollToCard('selectedItemsCard'); setActiveTopButton('topPreviewBtn'); });
document.getElementById('topRevisionHistoryBtn').addEventListener('click', function(){ const c=document.getElementById('revisionHistoryCard'); if(c){c.style.display='block';c.scrollIntoView({behavior:'smooth',block:'start'});} });
document.getElementById('topCompareQuotesBtn').addEventListener('click', function(){ const c=document.getElementById('revisionCompareCard'); if(c){c.style.display='block';c.scrollIntoView({behavior:'smooth',block:'start'});} });

// Scroll-based active toolbar tab
const sectionMap=[{id:'distributorCard',btn:'topCustomerBtn'},{id:'customerCard',btn:'topCustomerBtn'},{id:'productsCard',btn:'topProductsBtn'},{id:'selectedItemsCard',btn:'topPreviewBtn'}];
window.addEventListener('scroll', function(){
    let cur=null;
    sectionMap.forEach(function(s){ const el=document.getElementById(s.id); if(!el) return; const r=el.getBoundingClientRect(); if(r.top<=120&&r.bottom>=120) cur=s.btn; });
    if (cur) setActiveTopButton(cur);
});

// Load saved quote from recent table
document.querySelectorAll('.load-saved-quote').forEach(function(a){
    a.addEventListener('click', function(e){
        e.preventDefault();
        const dealId=this.getAttribute('data-deal'); if (!dealId) return;
        document.getElementById('existingDealId').value=dealId;
        document.getElementById('loadQuoteCard').style.display='block';
        document.getElementById('loadQuoteBtn').click();
    });
});

// Delete local quote record from recent table
document.querySelectorAll('.delete-local-quote').forEach(function(a){
    a.addEventListener('click', function(e){
        e.preventDefault();
        var _self=this;
        spConfirm('Remove Quote', 'Remove this quote from the local list? This does not affect HubSpot.', 'Remove', function(){
            const dealId = _self.getAttribute('data-deal');
            const row    = _self.closest('tr');
            smtiApiFetch(SMTI_API_BASE+'/delete-local-quote?deal_id='+encodeURIComponent(dealId), {method:'DELETE'})
                .then(function(r){return r.json();})
                .then(function(data){
                    if (data.success && row) row.remove();
                });
        });
    });
});

<?php if ( $is_admin ) : ?>
document.getElementById('syncProductsBtn').addEventListener('click', function(){
    const btn=this; btn.disabled=true; btn.textContent='Syncing…';
    const body=new FormData(); body.append('action','sp_smti_sales_sync');
    fetch(SP_AJAX_URL,{method:'POST',body:body}).then(function(r){return r.json();}).then(function(r){
        if (r.success&&r.data) {
            btn.textContent='✓ Synced ('+r.data.products+' products, '+r.data.distributors+' distributors)';
            setTimeout(function(){ location.reload(); }, 1500);
        } else {
            btn.textContent='Sync failed'; btn.disabled=false;
            spToastError((r.data&&r.data.message)||'Sync failed. Check SMTI Sales settings.');
        }
    }).catch(function(e){ btn.textContent='Sync error'; btn.disabled=false; spToastError(e.message); });
});
<?php endif; ?>

// ── Init ──────────────────────────────────────────────────────────────────────
resetCartToDefault();
renderCart();
document.getElementById('product_search').disabled = false;
document.getElementById('productResults').innerHTML = helperMessage;
</script>
