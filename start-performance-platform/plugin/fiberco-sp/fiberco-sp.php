<?php
/**
 * Plugin Name: FiberCo — Start Performance Integration
 * Description: Surfaces FiberCo AI Chatbot leads inside the Start Performance app — a "FiberCo Leads" review view under Sales Core, dashboard cards, KPIs, and CSV export. Reads the fiberco_chat_logs table created by the FiberCo AI Chatbot plugin.
 * Version:     0.2.1
 * Author:      Start Performance | RH Brashear
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FIBERCO_SP_VERSION', '0.2.1' );
define( 'FIBERCO_SP_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Boot only inside Start Performance (core provides sp_register_view).
 */
add_action( 'plugins_loaded', 'fiberco_sp_boot', 25 );
function fiberco_sp_boot() {
	if ( ! function_exists( 'sp_register_view' ) ) {
		return; // SP core not present — nothing to do.
	}
	fiberco_sp_register();
}

function fiberco_sp_register() {
	sp_register_addon( 'fiberco-leads', array(
		'name'        => 'FiberCo Leads',
		'version'     => FIBERCO_SP_VERSION,
		'description' => 'AI chatbot lead capture, conversation review, escalations, and CSV export for FiberCo.',
		'icon'        => '<path fill="currentColor" fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a9.06 9.06 0 01-2.347-.306l-3.223 1.61a.75.75 0 01-1.05-.86l.82-2.87C2.79 13.33 2 11.75 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zm-11-1a1 1 0 100 2 1 1 0 000-2zm3 0a1 1 0 100 2 1 1 0 000-2zm4 1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"/>',
		'plugin_file' => plugin_basename( __FILE__ ),
		'core_slot'   => 'sales-core',
	) );

	sp_register_view( 'fiberco-leads', FIBERCO_SP_DIR . 'templates/fiberco-leads.php' );

	add_filter( 'sp_nav_items',     'fiberco_sp_nav_items' );
	add_filter( 'sp_allowed_views', 'fiberco_sp_allowed_views' );

	add_action( 'sp_dashboard_before_stats', 'fiberco_sp_dashboard_stats' );
	add_filter( 'sp_intel_kpi_cards',        'fiberco_sp_intel_kpi_cards' );
	add_filter( 'sp_intel_summary_lines',    'fiberco_sp_intel_summary_lines', 20, 4 );
}

/**
 * Lead stats from the chatbot's fiberco_chat_logs table. Each row is one
 * visitor-message → bot-reply exchange; a "conversation" is a distinct session.
 */
function fiberco_sp_lead_stats() {
	static $cache = null;
	if ( $cache !== null ) { return $cache; }
	global $wpdb;
	$t = $wpdb->prefix . 'fiberco_chat_logs';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t ) ) !== $t ) {
		return $cache = array( 'messages' => 0, 'conversations' => 0, 'escalations' => 0, 'week' => 0, 'has_table' => false );
	}
	$week_cut = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 7 * DAY_IN_SECONDS );
	$cache = array(
		'messages'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t" ),
		'conversations' => (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM $t" ),
		'escalations'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE escalated = 1" ),
		'week'          => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE created_at >= %s", $week_cut ) ),
		'has_table'     => true,
	);
	return $cache;
}

/** Insert "FiberCo Leads" right after the Sales Core section header. */
function fiberco_sp_nav_items( $items ) {
	$result = array();
	foreach ( $items as $item ) {
		$result[] = $item;
		if ( ! empty( $item['section'] ) && ! empty( $item['section_id'] ) && 'sales-core' === $item['section_id'] ) {
			$result[] = array(
				'view'   => 'fiberco-leads',
				'label'  => 'FiberCo Leads',
				'custom' => true,
				'icon'   => '<path fill="currentColor" fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a9.06 9.06 0 01-2.347-.306l-3.223 1.61a.75.75 0 01-1.05-.86l.82-2.87C2.79 13.33 2 11.75 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9a1 1 0 100 2 1 1 0 000-2zm3 0a1 1 0 100 2 1 1 0 000-2zm4 1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"/>',
			);
		}
	}
	return $result;
}

function fiberco_sp_allowed_views( $views ) {
	$views[] = 'fiberco-leads';
	return $views;
}

/** Dashboard cards (before core stats): Conversations + Escalations. */
function fiberco_sp_dashboard_stats() {
	if ( function_exists( 'sp_is_view_hidden' ) && sp_is_view_hidden( 'fiberco-leads' ) ) { return; }
	$s = fiberco_sp_lead_stats();
	if ( ! $s['has_table'] || $s['messages'] === 0 ) { return; }
	?>
	<div class="sp-stats sp-stats-2">
		<div class="sp-stat-card" style="border-top-color:#0057FF">
			<div class="sp-stat-value"><?php echo number_format( $s['conversations'] ); ?></div>
			<div class="sp-stat-label">Chat Conversations</div>
		</div>
		<div class="sp-stat-card" style="border-top-color:<?php echo $s['escalations'] > 0 ? '#f59e0b' : '#10b981'; ?>">
			<div class="sp-stat-value"><?php echo number_format( $s['escalations'] ); ?></div>
			<div class="sp-stat-label">Escalations</div>
		</div>
	</div>
	<?php
}

/** KPI Dashboard cards. */
function fiberco_sp_intel_kpi_cards( $cards ) {
	$s = fiberco_sp_lead_stats();
	if ( ! $s['has_table'] || $s['messages'] === 0 ) { return $cards; }
	$cards[] = array( 'label' => 'Chat Conversations', 'value' => number_format( $s['conversations'] ), 'sub' => $s['week'] . ' this week', 'accent' => 'accent' );
	$cards[] = array( 'label' => 'Escalations', 'value' => number_format( $s['escalations'] ), 'sub' => $s['escalations'] > 0 ? 'need a human' : 'all handled by AI', 'accent' => $s['escalations'] > 0 ? 'warn' : 'green' );
	return $cards;
}

/** AI Summary line. */
function fiberco_sp_intel_summary_lines( $lines, $days, $since, $today ) {
	$s = fiberco_sp_lead_stats();
	if ( ! $s['has_table'] || $s['messages'] === 0 ) { return $lines; }
	$line = "- FiberCo chat: {$s['conversations']} conversations ({$s['messages']} messages), {$s['escalations']} escalated to a human";
	if ( $s['week'] > 0 ) { $line .= ", {$s['week']} messages in the last {$days} days"; }
	$lines[] = $line;
	return $lines;
}

/**
 * SP-authed CSV export of the chat leads (separate from the chatbot's own
 * wp-admin export so it works from inside the SP app).
 */
add_action( 'init', 'fiberco_sp_maybe_export_csv' );
function fiberco_sp_maybe_export_csv() {
	if ( empty( $_GET['fiberco_sp_export'] ) ) { return; }
	if ( ! function_exists( 'sp_is_authed' ) || ! sp_is_authed() ) { return; }
	global $wpdb;
	$t = $wpdb->prefix . 'fiberco_chat_logs';
	$rows = $wpdb->get_results( "SELECT * FROM $t ORDER BY created_at DESC", ARRAY_A );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=fiberco-leads-' . gmdate( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'ID', 'Session ID', 'IP Address', 'Visitor Message', 'Bot Response', 'Escalated', 'Date/Time' ) );
	foreach ( (array) $rows as $r ) {
		fputcsv( $out, array( $r['id'], $r['session_id'], $r['ip_address'], $r['user_message'], $r['bot_response'], $r['escalated'] ? 'Yes' : 'No', $r['created_at'] ) );
	}
	fclose( $out );
	exit;
}
