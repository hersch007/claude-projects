<?php
/**
 * Plugin Name: Quote Builder — Print Module
 * Description: Adds customer-facing print quotes to the Quote Builder plugin. Requires Quote Builder Core (sales-quote-system). No shortcode of its own — attaches to the [sqs_pricing_calculator] Quotes page via hooks.
 * Version:     1.14.9
 * Author:      Start Advertising | RH Brashear
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Keep this in sync with the "Version:" line in the header comment above --
// tests/check-plugin-versions.js enforces this automatically on every run.
define( 'SQSP_VERSION', '1.14.9' );

/**
 * Boot only when the core plugin is present.
 */
add_action( 'plugins_loaded', 'sqsp_boot', 20 );
function sqsp_boot() {
	if ( ! defined( 'SQS_VERSION' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p><strong>Quote Builder — Print Module</strong> requires the <strong>Quote Builder Core</strong> plugin to be active.</p></div>';
		} );
		return;
	}

	// ── Hook into the three core extension points ──────────────────────────────
	add_action( 'sqs_toolbar_extra_buttons',  'sqsp_toolbar_button' );
	add_action( 'sqs_quote_info_extra_fields', 'sqsp_extra_info_fields' );
	add_action( 'sqs_bottom_stack_extra',     'sqsp_print_card' );
	add_action( 'wp_head',                    'sqsp_head_assets' );
	add_action( 'sp_app_footer',              'sqsp_head_assets' );
}

// ── Admin settings (company address + quote terms) ────────────────────────────
add_action( 'admin_menu', 'sqsp_admin_menu', 20 );
function sqsp_admin_menu() {
	if ( ! defined( 'SQS_VERSION' ) ) return;
	add_submenu_page(
		'sqs-pricing-calculator',
		'Print Module Settings',
		'Print Settings',
		'manage_options',
		'sqs-print-settings',
		'sqsp_settings_page'
	);
}

function sqsp_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$msg = '';
	if ( isset( $_POST['sqsp_save'] ) ) {
		check_admin_referer( 'sqsp_settings_save', 'sqsp_nonce' );
		if ( isset( $_POST['sqs_company_address'] ) ) {
			update_option( 'sqs_company_address', sanitize_textarea_field( wp_unslash( $_POST['sqs_company_address'] ) ) );
		}
		if ( isset( $_POST['sqs_quote_terms'] ) ) {
			update_option( 'sqs_quote_terms', sanitize_textarea_field( wp_unslash( $_POST['sqs_quote_terms'] ) ) );
		}
		$msg = 'Settings saved.';
	}
	$addr  = get_option( 'sqs_company_address', '' );
	$terms = get_option( 'sqs_quote_terms', '' );
	?>
	<div class="wrap">
	<h1>Quote Builder &mdash; Print Module Settings</h1>
	<?php if ( $msg ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $msg ); ?></p></div><?php endif; ?>
	<form method="post" style="max-width:560px;">
		<?php wp_nonce_field( 'sqsp_settings_save', 'sqsp_nonce' ); ?>
		<input type="hidden" name="sqsp_save" value="1" />
		<div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:24px;margin:16px 0;">
			<h2 style="margin-top:0;border-bottom:1px solid #eee;padding-bottom:10px;">&#128438; Print Settings</h2>
			<p>
				<label><strong>Company Address</strong> <span class="description">(printed top-right on every quote)</span><br>
				<textarea name="sqs_company_address" class="large-text" rows="3"
					placeholder="123 Main St&#10;City, ST 00000&#10;(555) 000-0000"
					style="margin-top:4px;"><?php echo esc_textarea( $addr ); ?></textarea></label>
			</p>
			<p>
				<label><strong>Quote Terms / Disclaimer</strong> <span class="description">(printed at the bottom of every quote)</span><br>
				<textarea name="sqs_quote_terms" class="large-text" rows="4"
					placeholder="Quotes are valid for 30 days. All orders are subject to standard terms and conditions..."
					style="margin-top:4px;"><?php echo esc_textarea( $terms ); ?></textarea></label>
			</p>
		</div>
		<p><button class="button button-primary button-large">Save Settings</button></p>
	</form>
	</div>
	<?php
}

// ── Toolbar: Print Quote button ───────────────────────────────────────────────
function sqsp_toolbar_button() {
	echo '<button id="sqsPrintBtn" type="button" class="sqs-bar-btn">&#128438; Print Quote</button>';
}

// ── Extra quote-info fields (injected into core's Quote Info grid) ────────────
function sqsp_extra_info_fields() {
	?>
	<div class="sqc-field"><label>Customer Email</label><input id="customerEmail" type="email" data-sqs-meta="customerEmail" placeholder="customer@example.com" /></div>
	<div class="sqc-field"><label>Prepared By Email</label><input id="preparedByEmail" type="email" data-sqs-meta="preparedByEmail" placeholder="rep@yourcompany.com" /></div>
	<div class="sqc-field"><label>Lead Time</label><input id="leadTime" data-sqs-meta="leadTime" value="4-5 weeks" placeholder="e.g. 4-5 weeks" /></div>
	<div class="sqc-field"><label>FOB Location</label><input id="fobLocation" data-sqs-meta="fobLocation" value="ORIGIN" placeholder="e.g. ORIGIN" /></div>
	<?php
}

// ── Print card (hidden on-screen, visible when printing) ─────────────────────
function sqsp_print_card() {
	// Fall back to Start Performance branding when the standalone quote-system
	// options are empty (they are, on the SP-integrated Fruth site — branding
	// lives in sp_logo_url / sp_platform_name instead).
	$logo    = get_option( 'sqs_logo_url', '' )    ?: get_option( 'sp_logo_url', '' );
	$company = get_option( 'sqs_company_name', '' ) ?: get_option( 'sp_platform_name', 'Quote Builder' );
	$address = get_option( 'sqs_company_address', '' );

	$default_terms = 'Please note, our minimum order is $500.00, per item. Quotes are valid for 30 Days. All orders are shipped +/- 10% unless otherwise specified and +/- 20% on all printed and an up-charge for exact order requirements. All dimensions are inside dimensions (ID) unless otherwise state. All zipper bags are manufactured ID and have an 1/2" OD added to the ID due to the sonic welds.';
	$terms = get_option( 'sqs_quote_terms', '' ) ?: $default_terms;
	?>
	<div class="sqc-card sqc-print-only">
		<div class="sqp-header">
			<?php if ( $logo ) : ?>
				<img src="<?php echo esc_url( $logo ); ?>" class="sqp-logo" alt="<?php echo esc_attr( $company ); ?>" />
			<?php else : ?>
				<div style="font-size:20px;font-weight:800;color:#1a202c;"><?php echo esc_html( $company ); ?></div>
			<?php endif; ?>
			<div class="sqp-addr"><?php echo nl2br( esc_html( $address ) ); ?></div>
		</div>
		<div class="sqp-accent-bar"></div>

		<div class="sqp-qnum"><span class="sqp-badge">Quotation</span>&nbsp; <span id="prtQnum">—</span></div>

		<div class="sqp-info">
			<div class="sqp-info-col">
				<div class="sqp-info-row"><span class="sqp-info-lbl">Account Name</span><span class="sqp-info-val" id="prtCompany">—</span></div>
				<div class="sqp-info-row"><span class="sqp-info-lbl">Contact Name</span><span class="sqp-info-val" id="prtCustomer">—</span></div>
				<div class="sqp-info-row"><span class="sqp-info-lbl">Created Date</span><span class="sqp-info-val" id="prtDate">—</span></div>
			</div>
			<div class="sqp-info-col">
				<div class="sqp-info-row"><span class="sqp-info-lbl">Prepared By</span><span class="sqp-info-val" id="prtPrepBy">—</span></div>
				<div class="sqp-info-row"><span class="sqp-info-lbl">Email</span><span class="sqp-info-val" id="prtPrepEmail">—</span></div>
					<div class="sqp-info-row"><span class="sqp-info-lbl">Issue Date</span><span class="sqp-info-val" id="prtIssueDate">—</span></div>
			</div>
		</div>

		<table class="sqp-table">
			<thead><tr>
				<th style="width:44%;">Product Description</th>
				<th style="text-align:right;">Sales Price</th>
				<th style="text-align:right;">Quantity</th>
				<th style="text-align:center;width:42px;">MOQ</th>
				<th style="text-align:right;">Total Price</th>
			</tr></thead>
			<tbody id="prtItems"></tbody>
		</table>

		<div class="sqp-foot-info">
			<div class="sqp-foot-row"><span class="sqp-info-lbl">Lead Time</span><span class="sqp-info-val" id="prtLeadTime">—</span></div>
			<div class="sqp-foot-row"><span class="sqp-info-lbl">FOB</span><span class="sqp-info-val" id="prtFob">—</span></div>
			<div class="sqp-foot-row sqp-valid">Valid 30 days from issue date</div>
		</div>

		<p class="sqp-terms" id="prtTerms"><?php echo nl2br( esc_html( $terms ) ); ?></p>
		<div class="sqp-stamp" id="prtStamp"></div>
	</div>
	<?php
}

// ── Inject CSS + JS on every frontend page (tiny payload, internal tool) ─────
function sqsp_head_assets() {
	if ( is_admin() ) return;
	// Output once per request. Hooked to BOTH wp_head (standalone Fruth site)
	// and sp_app_footer (the Start Performance app route, which does NOT emit
	// wp_head — so without this the print CSS/JS never loaded in the embedded
	// app and the Print Quote button was dead).
	static $done = false;
	if ( $done ) return;
	$done = true;
	?>
	<style>
	/* ── Quote Builder Print Module ── */

	/* Print button style */
	#sqsPrintBtn { color:#0369a1 !important; border-color:#7dd3fc !important; }
	#sqsPrintBtn:hover { background:#f0f9ff !important; border-color:#0284c7 !important; color:#0284c7 !important; }

	/* Hide print card on screen — core also sets this, belt-and-suspenders */
	.sqc-print-only { display:none !important; }

	/* ── @media print ── */
	/*
	 * JS moves .sqc-print-only to be a direct <body> child before window.print().
	 * That lets us use display:none on all other body > * without fighting the
	 * WordPress theme nesting. One page, instant preview.
	 */
	@page { size:letter portrait; margin:0.65in; }
	@media print {
		/* Hide everything except our print card (which JS moved to body) */
		body > *:not(.sqc-print-only) { display:none !important; }

		.sqc-print-only {
			display:block !important;
			position:static !important;
			width:100% !important;
			margin:0 !important;
			padding:0 !important;
			background:#fff !important;
			font-family:Arial,Helvetica,sans-serif !important;
			font-size:13px !important;
			color:#1a202c !important;
			box-sizing:border-box !important;
		}

		/* ── Inner layout styles ── */
		/* Header: logo + address.
		 * @page already applies 0.65in page margins, so no extra side padding here. */
		.sqp-header { display:flex !important; align-items:flex-start !important; justify-content:space-between !important; padding:0 0 12px !important; border-bottom:none !important; margin-bottom:0 !important; }
		.sqp-logo { max-height:68px !important; max-width:220px !important; width:auto !important; display:block !important; }
		.sqp-addr { font-size:11px !important; color:#4a5568 !important; text-align:right !important; line-height:1.65 !important; }

		/* Red accent stripe below header */
		.sqp-accent-bar { display:block !important; height:4px !important; background:#c62828 !important; margin-bottom:35px !important; -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; }

		/* Content sections — no extra padding; @page margin handles the gutters */
		.sqp-qnum,
		.sqp-info,
		.sqp-table,
		.sqp-foot-info,
		.sqp-terms,
		.sqp-stamp { padding-left:0 !important; padding-right:0 !important; }

		/* Quote number + QUOTATION badge */
		.sqp-qnum { display:flex !important; align-items:center !important; gap:8px !important; font-size:14px !important; font-weight:700 !important; color:#1a202c !important; margin-bottom:16px !important; }
		.sqp-qnum > span:last-child { font-weight:400 !important; }
		.sqp-badge { display:inline-block !important; background:#c62828 !important; color:#fff !important; font-size:10px !important; font-weight:800 !important; letter-spacing:.07em !important; text-transform:uppercase !important; padding:3px 9px !important; border-radius:3px !important; vertical-align:middle !important; -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; }

		/* Info block */
		.sqp-info { display:grid !important; grid-template-columns:1fr 1fr !important; gap:0 !important; margin-bottom:35px !important; border:1px solid #c8cdd5 !important; border-radius:3px !important; overflow:hidden !important; font-size:12px !important; }
		.sqp-info-col { display:block !important; padding:12px 14px !important; }
		.sqp-info-col + .sqp-info-col { border-left:1px solid #c8cdd5 !important; }
		.sqp-info-row { display:flex !important; gap:6px !important; margin-bottom:5px !important; line-height:1.45 !important; }
		.sqp-info-row:last-child { margin-bottom:0 !important; }
		.sqp-info-lbl { font-weight:700 !important; color:#374151 !important; min-width:92px !important; flex-shrink:0 !important; }
		.sqp-info-val { color:#1a202c !important; }

		/* Line items table */
		.sqp-table { display:table !important; width:100% !important; border-collapse:collapse !important; font-size:12px !important; margin-bottom:50px !important; }
		.sqp-table thead { display:table-header-group !important; }
		.sqp-table thead tr { background:#c62828 !important; color:#fff !important; }
		.sqp-table thead th { display:table-cell !important; padding:9px 10px !important; text-align:left !important; font-weight:700 !important; font-size:11px !important; text-transform:uppercase !important; letter-spacing:.04em !important; background:#c62828 !important; color:#fff !important; border:none !important; -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; }
		.sqp-table thead th:not(:first-child) { text-align:right !important; }
		.sqp-table tbody { display:table-row-group !important; }
		.sqp-table tbody tr:nth-child(even) { background:#f7f8fa !important; -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; }
		.sqp-table tbody td { display:table-cell !important; padding:8px 10px !important; border-bottom:1px solid #e2e8f0 !important; color:#1a202c !important; vertical-align:middle !important; }
		.sqp-table tbody td:not(:first-child) { text-align:right !important; }
		.sqp-table tfoot { display:table-footer-group !important; }
		.sqp-table tfoot td { display:table-cell !important; padding:8px 10px !important; font-weight:700 !important; border-top:2px solid #c62828 !important; background:#f3f4f6 !important; -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; }
		.sqp-table tfoot td:not(:first-child) { text-align:right !important; }

		/* Footer info row */
		.sqp-foot-info { display:flex !important; gap:28px !important; font-size:12px !important; margin-bottom:10px !important; align-items:baseline !important; }
		.sqp-foot-row { display:flex !important; gap:6px !important; align-items:baseline !important; }
		.sqp-foot-row .sqp-info-lbl { min-width:auto !important; }
		.sqp-valid { margin-left:auto !important; font-size:11px !important; color:#c62828 !important; font-style:italic !important; font-weight:600 !important; -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; }

		/* Terms */
		.sqp-terms { display:block !important; font-size:10.5px !important; color:#6b7280 !important; line-height:1.55 !important; margin:12px 0 0 !important; border-top:1px solid #e2e8f0 !important; padding-top:8px !important; }

		/* Stamp */
		.sqp-stamp { display:block !important; font-size:9px !important; color:#9ca3af !important; text-align:right !important; margin-top:10px !important; }
	}
	</style>

	<script>
	(function () {
		'use strict';

		// ── Helpers (use SQS public API once core boots) ──────────────────────
		function gv(id) { var el = document.getElementById(id); return el ? el.value.trim() : ''; }
		function sv(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; }

		function money(v) {
			return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(v || 0));
		}
		function num(v, d) {
			d = d == null ? 2 : d;
			return Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
		}
		function esc(s) {
			return String(s || '').replace(/[&<>"']/g, function (m) {
				return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
			});
		}

		// ── Print button ──────────────────────────────────────────────────────
		// Move the print div to a direct <body> child so @media print can use
		// body > *:not(.sqc-print-only) { display:none } without fighting the
		// WordPress theme nesting. Restore it to its original location after print.
		// Event delegation on document so the Print button works no matter when
		// this script runs relative to DOMContentLoaded. Embedded in Start
		// Performance this script executes AFTER the app's DOMContentLoaded has
		// already fired, so a plain DOMContentLoaded listener never runs and the
		// button goes dead. Delegation binds immediately and is timing-proof.
		document.addEventListener('click', function (ev) {
			var btn = ev.target && ev.target.closest ? ev.target.closest('#sqsPrintBtn') : null;
			if (!btn) return;
			var printEl    = document.querySelector('.sqc-print-only');
			if (!printEl) { window.print(); return; }
			var origParent = printEl.parentNode;
			var origNext   = printEl.nextSibling;
			document.body.appendChild(printEl);
			function restore() {
				if (origNext) { origParent.insertBefore(printEl, origNext); }
				else          { origParent.appendChild(printEl); }
				window.removeEventListener('afterprint', restore);
			}
			window.addEventListener('afterprint', restore);
			window.print();
		});

		// ── Listen for core's sqs:update broadcast ────────────────────────────
		document.addEventListener('sqs:update', function (e) {
			renderPrintQuote(e.detail.result, e.detail.state, e.detail.quoteNumber);
		});

		// Build product description — Veritiv-style: dimensions first, then material, then packaging.
		// Format: [W x L x Gauge] [Material / Product Code] [— Packaging if not "No Bags"]
		//
		// Material label sources (first match wins):
		//   1. Product Code / Description combobox (user-entered or selected)
		//   2. Fallback: filmType from form state
		//
		// Example output:
		//   9.84" x 19.68" x .002" Cleanroom Polyethylene (LDPE) Bag
		//   12" x 100 ft x .004" CFB1000 — LDPE — Double Bag
		function buildDesc(r, state) {
			var product = state.product;
			var f = state.forms[product];

			// Gauge: 0.004 → ".004"
			var g = Number(f.gauge || 0);
			var gaugeStr = '.' + (g * 1000).toFixed(1).replace(/\.0$/, '').replace(/^0+/, '').padStart(3, '0') + '"';
			var w = Number(f.width || 0);

			// Dimension string — W x L x Gauge
			var dims;
			if (product === 'tubing') {
				dims = w + '" x ' + Number(f.lengthFt || 0) + ' ft x ' + gaugeStr;
			} else if (product === 'inline') {
				dims = w + '" x ' + Number(f.lengthIn || 0) + '" x ' + gaugeStr;
			} else {
				dims = w + '" x ' + Number(f.lengthIn || 0) + '" (+ ' + Number(f.lipIn || 0) + '" lip) x ' + gaugeStr;
			}

			// Material: combobox value takes priority; fall back to filmType
			var pcEl = document.getElementById(product + '-productCode');
			var matLabel = (pcEl && pcEl.value.trim()) ? pcEl.value.trim() : (f.filmType || '');

			// Packaging suffix — omit when "No Bags" or blank
			var pkgSuffix = (f.packaging && f.packaging !== 'No Bags') ? ' — ' + f.packaging : '';

			// Description first, then dimensions (e.g. "LDPE Bag — 12" x 8" x .004"")
			var base = matLabel ? matLabel + ' — ' + dims : dims;
			return base + pkgSuffix;
		}

		function renderPrintQuote(r, state, quoteNumber) {
			var company   = gv('companyName')    || '—';
			var customer  = gv('customerName')   || '—';
			var prepBy    = gv('preparedBy')     || '—';
			var prepEmail = gv('preparedByEmail')|| '—';
			var leadTime  = gv('leadTime')       || '—';
			var fob       = gv('fobLocation')    || '—';
			var notes     = gv('quoteNotes');
			var rawDate   = gv('quoteDate');
			var qnum      = gv('quoteNumber') || quoteNumber || '—';

			var printDate = rawDate
				? new Date(rawDate + 'T00:00:00').toLocaleDateString('en-US', { month: 'numeric', day: 'numeric', year: 'numeric' })
				: new Date().toLocaleDateString('en-US', { month: 'numeric', day: 'numeric', year: 'numeric' });

			sv('prtQnum',     qnum);
			sv('prtCompany',  company);
			sv('prtCustomer', customer);
			sv('prtDate',     printDate);
			sv('prtIssueDate', new Date().toLocaleDateString('en-US', { month: 'numeric', day: 'numeric', year: 'numeric' }));
			sv('prtPrepBy',   prepBy);
			sv('prtPrepEmail',prepEmail);
			sv('prtLeadTime', leadTime);
			sv('prtFob',      fob);
			sv('prtStamp',    'Generated ' + new Date().toLocaleString());

			// The Terms/Disclaimer block always shows the legal terms rendered by
			// PHP (sqs_quote_terms, or the packaging default). The quote's internal
			// Notes field is NOT printed on the customer-facing quote — it would
			// otherwise clobber the legal terms with "Internal notes...".

			// Build product description in Veritiv format
			var desc = buildDesc(r, state);

			// Build line-items from price breaks — each tier gets a MOQ checkbox
			var rows = r.priceBreaks.map(function (pb) {
				if (!pb.qty) return '';
				var qtyStr = num(pb.qty, 0);
				return '<tr>'
					+ '<td>' + esc(desc) + '</td>'
					+ '<td style="text-align:right;">' + money(pb.unitPrice) + '</td>'
					+ '<td style="text-align:right;">' + esc(qtyStr) + '</td>'
					+ '<td style="text-align:center;font-size:14px;">&#9744;</td>'
					+ '<td style="text-align:right;">' + money(pb.sales) + '</td>'
					+ '</tr>';
			}).join('');

			// Append extra charge rows when non-zero
			var f = state.forms[state.product];
			var dash = '<td style="text-align:right;">—</td>';
			var pkgFee    = Number(f.customPackagingFee || 0);
			var specCharge = Number(f.specialtyCharge   || 0);
			var toolCharge = Number(f.toolingCharge     || 0);
			if (pkgFee > 0) {
				rows += '<tr>'
					+ '<td>Custom Packaging Fee</td>' + dash
					+ '<td style="text-align:right;">1</td>'
					+ '<td style="text-align:center;">—</td>'
					+ '<td style="text-align:right;">' + money(pkgFee) + '</td>'
					+ '</tr>';
			}
			if (specCharge > 0) {
				rows += '<tr>'
					+ '<td>Specialty Process Charge</td>' + dash
					+ '<td style="text-align:right;">1</td>'
					+ '<td style="text-align:center;">—</td>'
					+ '<td style="text-align:right;">' + money(specCharge) + '</td>'
					+ '</tr>';
			}
			if (toolCharge > 0) {
				rows += '<tr>'
					+ '<td>Tooling Charge</td>' + dash
					+ '<td style="text-align:right;">1</td>'
					+ '<td style="text-align:center;">—</td>'
					+ '<td style="text-align:right;">' + money(toolCharge) + '</td>'
					+ '</tr>';
			}

			var itemsEl = document.getElementById('prtItems');
			if (itemsEl) {
				itemsEl.innerHTML = rows || '<tr><td colspan="5" style="color:#9ca3af;padding:10px;">No price breaks calculated.</td></tr>';
			}

		}

	}());
	</script>
	<?php
}
