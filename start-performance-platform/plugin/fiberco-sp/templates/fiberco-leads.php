<?php
/**
 * SP app view: FiberCo Leads — review AI chatbot conversations, escalations,
 * and export. Rendered inside the Start Performance app shell.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;
$t = $wpdb->prefix . 'fiberco_chat_logs';
$has_table = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t ) ) === $t );
$s = function_exists( 'fiberco_sp_lead_stats' ) ? fiberco_sp_lead_stats() : array( 'messages' => 0, 'conversations' => 0, 'escalations' => 0, 'week' => 0 );

$rows = $has_table
	? $wpdb->get_results( "SELECT id, session_id, ip_address, user_message, bot_response, escalated, created_at FROM $t ORDER BY created_at DESC LIMIT 300", ARRAY_A )
	: array();

$export_url = esc_url( home_url( '/?fiberco_sp_export=1' ) );
?>
<div class="sp-view-header" style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
	<div>
		<h1 style="margin:0 0 4px;font-size:1.6rem;font-weight:800;color:var(--sp-text,#0f172a);">FiberCo Leads</h1>
		<p style="margin:0;color:var(--sp-muted,#64748b);font-size:.95rem;">AI chatbot conversations captured from the FiberCo site.</p>
	</div>
	<?php if ( $s['messages'] > 0 ) : ?>
	<a href="<?php echo $export_url; ?>" class="sp-btn" style="display:inline-flex;align-items:center;gap:8px;background:var(--sp-accent,#0057FF);color:#fff;border:none;border-radius:10px;padding:11px 18px;font-weight:700;font-size:.9rem;text-decoration:none;white-space:nowrap;">&#11015; Export CSV</a>
	<?php endif; ?>
</div>

<div class="sp-stats sp-stats-4" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:22px;">
	<div class="sp-stat-card" style="border-top:3px solid #0057FF;background:#fff;border-radius:14px;padding:18px 20px;box-shadow:0 1px 6px rgba(15,23,42,.06);">
		<div class="sp-stat-value" style="font-size:1.9rem;font-weight:800;color:#0f172a;line-height:1;"><?php echo number_format( (int) $s['conversations'] ); ?></div>
		<div class="sp-stat-label" style="margin-top:6px;color:#64748b;font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Conversations</div>
	</div>
	<div class="sp-stat-card" style="border-top:3px solid #0d9488;background:#fff;border-radius:14px;padding:18px 20px;box-shadow:0 1px 6px rgba(15,23,42,.06);">
		<div class="sp-stat-value" style="font-size:1.9rem;font-weight:800;color:#0f172a;line-height:1;"><?php echo number_format( (int) $s['messages'] ); ?></div>
		<div class="sp-stat-label" style="margin-top:6px;color:#64748b;font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Total Messages</div>
	</div>
	<div class="sp-stat-card" style="border-top:3px solid <?php echo (int) $s['escalations'] > 0 ? '#f59e0b' : '#10b981'; ?>;background:#fff;border-radius:14px;padding:18px 20px;box-shadow:0 1px 6px rgba(15,23,42,.06);">
		<div class="sp-stat-value" style="font-size:1.9rem;font-weight:800;color:#0f172a;line-height:1;"><?php echo number_format( (int) $s['escalations'] ); ?></div>
		<div class="sp-stat-label" style="margin-top:6px;color:#64748b;font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Escalations</div>
	</div>
	<div class="sp-stat-card" style="border-top:3px solid #7c3aed;background:#fff;border-radius:14px;padding:18px 20px;box-shadow:0 1px 6px rgba(15,23,42,.06);">
		<div class="sp-stat-value" style="font-size:1.9rem;font-weight:800;color:#0f172a;line-height:1;"><?php echo number_format( (int) $s['week'] ); ?></div>
		<div class="sp-stat-label" style="margin-top:6px;color:#64748b;font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">This Week</div>
	</div>
</div>

<?php if ( empty( $rows ) ) : ?>
	<div style="background:#fff;border:1px solid #e5e8f0;border-radius:14px;padding:48px 24px;text-align:center;color:#64748b;">
		<div style="font-size:38px;margin-bottom:10px;">&#128172;</div>
		<div style="font-size:1.05rem;font-weight:700;color:#0f172a;margin-bottom:4px;">No conversations logged yet</div>
		<div style="font-size:.9rem;">Chat leads from the FiberCo site will appear here as visitors use the assistant.</div>
	</div>
<?php else : ?>
	<div style="margin-bottom:14px;">
		<input type="text" id="fcq-lead-search" placeholder="Search messages, replies, or session&hellip;" style="width:100%;max-width:420px;background:#fff;border:1.5px solid #e5e8f0;border-radius:10px;padding:11px 16px;font-size:.9rem;color:#0f172a;outline:none;">
	</div>
	<div style="background:#fff;border:1px solid #e5e8f0;border-radius:14px;overflow:hidden;box-shadow:0 1px 6px rgba(15,23,42,.06);">
		<div style="overflow-x:auto;">
			<table id="fcq-lead-table" style="width:100%;border-collapse:collapse;font-size:.86rem;min-width:820px;">
				<thead>
					<tr style="background:#f8fafc;text-align:left;">
						<th style="padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;font-weight:700;white-space:nowrap;">Date / Time</th>
						<th style="padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;font-weight:700;">Visitor Message</th>
						<th style="padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;font-weight:700;">Bot Response</th>
						<th style="padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;font-weight:700;text-align:center;white-space:nowrap;">Escalated</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $r ) :
						$dt   = $r['created_at'] ? date_i18n( 'M j, g:i A', strtotime( $r['created_at'] ) ) : '—';
						$esc  = (int) $r['escalated'] === 1;
						$sess = substr( (string) $r['session_id'], 0, 8 );
						$search = strtolower( $r['user_message'] . ' ' . $r['bot_response'] . ' ' . $r['session_id'] );
					?>
					<tr class="fcq-lead-row" data-search="<?php echo esc_attr( $search ); ?>" style="border-top:1px solid #f1f5f9;<?php echo $esc ? 'background:#fffbeb;' : ''; ?>">
						<td style="padding:12px 16px;color:#475569;white-space:nowrap;vertical-align:top;">
							<?php echo esc_html( $dt ); ?>
							<div style="color:#94a3b8;font-size:.72rem;margin-top:2px;font-family:monospace;"><?php echo esc_html( $sess ); ?></div>
						</td>
						<td style="padding:12px 16px;color:#0f172a;vertical-align:top;max-width:280px;"><?php echo esc_html( wp_trim_words( $r['user_message'], 40 ) ); ?></td>
						<td style="padding:12px 16px;color:#475569;vertical-align:top;max-width:340px;"><?php echo esc_html( wp_trim_words( $r['bot_response'], 45 ) ); ?></td>
						<td style="padding:12px 16px;text-align:center;vertical-align:top;white-space:nowrap;">
							<?php if ( $esc ) : ?>
								<span style="display:inline-block;background:#fef3c7;color:#92400e;border-radius:20px;padding:3px 10px;font-size:.72rem;font-weight:800;">&#9888; Human</span>
							<?php else : ?>
								<span style="color:#cbd5e1;">—</span>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<p style="margin:12px 2px 0;color:#94a3b8;font-size:.78rem;">Showing the most recent <?php echo count( $rows ); ?> messages. Use Export CSV for the full log.</p>
	<script>
	(function(){
		var box = document.getElementById('fcq-lead-search');
		if(!box) return;
		box.addEventListener('input', function(){
			var q = this.value.trim().toLowerCase();
			document.querySelectorAll('.fcq-lead-row').forEach(function(row){
				row.style.display = (!q || (row.getAttribute('data-search')||'').indexOf(q) !== -1) ? '' : 'none';
			});
		});
	})();
	</script>
<?php endif; ?>
