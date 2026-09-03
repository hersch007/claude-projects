<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$is_admin = sp_is_admin_member();
$wf_id    = (int) ( $_GET['id'] ?? 0 );

if ( ! $wf_id ) {
    wp_redirect( home_url( '/sp-app/?view=operations' ) ); exit;
}

$wf    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_op_workflows WHERE id=%d", $wf_id ) );
$steps = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}sp_op_workflow_steps WHERE workflow_id=%d ORDER BY sort_order ASC", $wf_id
) );

if ( ! $wf ) {
    echo '<p class="sp-empty">Workflow not found.</p>'; return;
}
?>

<div class="sp-view-header">
    <h1><?php echo esc_html( $wf->name ); ?></h1>
    <div style="display:flex;gap:8px;">
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=operations&action=start&wf=' . $wf_id ) ); ?>" class="sp-btn sp-btn-primary">▶ Start Workflow</a>
        <?php if ( $is_admin ) : ?>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=operations&action=edit&id=' . $wf_id ) ); ?>" class="sp-btn sp-btn-secondary">Edit</a>
        <?php endif; ?>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=operations' ) ); ?>" class="sp-btn sp-btn-ghost">← Back</a>
    </div>
</div>

<?php if ( isset( $_GET['saved'] ) ) : ?>
    <div class="sp-notice sp-notice-success">Workflow saved.</div>
<?php endif; ?>

<?php if ( $wf->description ) : ?>
    <p style="color:var(--sp-muted);margin-bottom:20px;"><?php echo esc_html( $wf->description ); ?></p>
<?php endif; ?>

<div class="sp-card" style="padding:20px;">
    <h3 style="font-size:.85rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--sp-muted);margin:0 0 16px;">
        Steps (<?php echo count($steps); ?>)
    </h3>
    <?php if ( empty( $steps ) ) : ?>
        <p class="sp-empty">No steps defined yet.</p>
    <?php else : ?>
        <div style="display:flex;flex-direction:column;gap:0;">
        <?php foreach ( $steps as $i => $s ) : ?>
            <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 0;<?php echo $i > 0 ? 'border-top:1px solid var(--sp-border,#e5e7eb);' : ''; ?>">
                <div style="width:28px;height:28px;border-radius:50%;background:var(--sp-primary,#2563eb);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;flex-shrink:0;">
                    <?php echo $i + 1; ?>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:600;"><?php echo esc_html( $s->title ); ?></div>
                    <div style="font-size:.78rem;color:var(--sp-muted);margin-top:3px;">
                        <?php if ( $s->due_offset_days > 0 ) : ?>
                            Due: Day +<?php echo (int)$s->due_offset_days; ?>
                        <?php else : ?>
                            Due: Start date
                        <?php endif; ?>
                        <?php if ( $s->assigned_role ) : ?>
                            &nbsp;·&nbsp; Assigned to: <?php echo esc_html( ucfirst( $s->assigned_role ) ); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
