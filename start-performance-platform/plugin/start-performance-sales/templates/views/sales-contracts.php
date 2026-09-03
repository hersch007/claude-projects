<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$action    = sanitize_key( $_GET['action'] ?? 'list' );
$id        = (int) ( $_GET['id'] ?? 0 );
$contacts  = $wpdb->get_results( "SELECT id, CONCAT(first_name,' ',last_name) AS name FROM {$wpdb->prefix}sp_contacts ORDER BY first_name,last_name" );
$companies = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sp_companies ORDER BY name" );
$statuses  = array( 'Draft', 'Sent', 'Active', 'Completed', 'Cancelled' );
$status_colors = array( 'Draft'=>'#6b7280','Sent'=>'#3b82f6','Active'=>'#16a34a','Completed'=>'#8b5cf6','Cancelled'=>'#dc2626' );

// ── View ──────────────────────────────────────────────────────────────────────
if ( $action === 'view' && $id ) {
    $ctr = $wpdb->get_row( $wpdb->prepare(
        "SELECT ct.*, CONCAT(c.first_name,' ',c.last_name) AS contact_name, co.name AS company_name, t.name AS author
         FROM {$wpdb->prefix}sp_contracts ct
         LEFT JOIN {$wpdb->prefix}sp_contacts c  ON c.id=ct.contact_id
         LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id=ct.company_id
         LEFT JOIN {$wpdb->prefix}sp_team t        ON t.id=ct.created_by
         WHERE ct.id=%d", $id ) );
    if(!$ctr){ echo '<p class="sp-empty">Not found.</p>'; return; }
    $col = $status_colors[$ctr->status]??'#6b7280';
    ?>
    <div class="sp-view-header">
        <div>
            <h1><?php echo esc_html($ctr->title); ?></h1>
            <span style="font-size:.82rem;color:var(--sp-muted);"><?php echo esc_html($ctr->number); ?></span>
        </div>
        <div style="display:flex;gap:8px;align-items:center;" class="sp-no-print">
            <span style="padding:4px 12px;border-radius:99px;font-size:.78rem;font-weight:700;background:<?php echo $col; ?>22;color:<?php echo $col; ?>;"><?php echo esc_html($ctr->status); ?></span>
            <button onclick="window.print()" class="sp-btn sp-btn-secondary">Print / PDF</button>
            <a href="<?php echo esc_url(home_url('/sp-app/?view=sales-contracts&action=edit&id='.$id)); ?>" class="sp-btn sp-btn-secondary">Edit</a>
            <a href="<?php echo esc_url(home_url('/sp-app/?view=sales-contracts')); ?>" class="sp-btn sp-btn-ghost">← Contracts</a>
        </div>
    </div>
    <?php if(isset($_GET['saved'])): ?><div class="sp-notice sp-notice-success">Saved.</div><?php endif; ?>
    <?php if(isset($_GET['converted'])): ?><div class="sp-notice sp-notice-success">Contract created from proposal. Review content and set start date before sending.</div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 220px;gap:18px;align-items:start;">
        <div class="sp-card" style="padding:28px 32px;">
            <div class="sp-kb-content" style="line-height:1.75;font-size:.91rem;"><?php echo wp_kses_post($ctr->content); ?></div>
            <?php if($ctr->signed_at): ?>
            <div style="margin-top:24px;padding:16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;">
                <div style="font-weight:700;color:#16a34a;margin-bottom:4px;">✓ Signed</div>
                <div style="font-size:.83rem;color:#166534;"><?php echo esc_html($ctr->signed_by_name); ?> · <?php echo esc_html(date('M j, Y',strtotime($ctr->signed_at))); ?></div>
            </div>
            <?php endif; ?>
        </div>
        <div>
            <div class="sp-card" style="padding:16px;">
                <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--sp-muted);margin-bottom:12px;">Details</div>
                <?php if(trim($ctr->contact_name)): ?><div style="font-size:.83rem;margin-bottom:8px;"><span style="color:var(--sp-muted);">Contact</span><br><?php echo esc_html(trim($ctr->contact_name)); ?></div><?php endif; ?>
                <?php if($ctr->company_name): ?><div style="font-size:.83rem;margin-bottom:8px;"><span style="color:var(--sp-muted);">Company</span><br><?php echo esc_html($ctr->company_name); ?></div><?php endif; ?>
                <?php if($ctr->value>0): ?><div style="font-size:.83rem;margin-bottom:8px;"><span style="color:var(--sp-muted);">Value</span><br><strong>$<?php echo number_format((float)$ctr->value,2); ?></strong></div><?php endif; ?>
                <?php if($ctr->start_date): ?><div style="font-size:.83rem;margin-bottom:6px;"><span style="color:var(--sp-muted);">Start</span><br><?php echo esc_html(date('M j, Y',strtotime($ctr->start_date))); ?></div><?php endif; ?>
                <?php if($ctr->end_date): ?><div style="font-size:.83rem;margin-bottom:14px;"><span style="color:var(--sp-muted);">End</span><br><?php echo esc_html(date('M j, Y',strtotime($ctr->end_date))); ?></div><?php endif; ?>

                <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--sp-muted);margin-bottom:8px;" class="sp-no-print">Update Status</div>
                <form method="post" action="<?php echo esc_url(home_url('/sp-app/')); ?>" class="sp-no-print">
                    <?php wp_nonce_field('sp_form','sp_nonce'); ?>
                    <input type="hidden" name="sp_type" value="contract_status">
                    <input type="hidden" name="sp_id"   value="<?php echo $id; ?>">
                    <select name="status" style="width:100%;margin-bottom:8px;" id="sp-ctr-status" onchange="document.getElementById('sp-ctr-sign-wrap').style.display=this.value==='Active'?'block':'none';">
                        <?php foreach($statuses as $s): ?><option value="<?php echo $s; ?>"<?php selected($ctr->status,$s); ?>><?php echo $s; ?></option><?php endforeach; ?>
                    </select>
                    <div id="sp-ctr-sign-wrap" style="display:<?php echo $ctr->status==='Active'?'block':'none'; ?>;">
                        <div style="font-size:.78rem;color:var(--sp-muted);margin-bottom:4px;">Signed by</div>
                        <input type="text" name="signed_by_name" value="<?php echo esc_attr($ctr->signed_by_name); ?>" placeholder="Signer name" style="width:100%;margin-bottom:6px;">
                        <input type="date" name="signed_at" value="<?php echo esc_attr($ctr->signed_at??''); ?>" style="width:100%;margin-bottom:8px;">
                    </div>
                    <button type="submit" class="sp-btn sp-btn-primary sp-btn-sm" style="width:100%;">Update</button>
                </form>
                <div style="margin-top:10px;" class="sp-no-print">
                    <a href="<?php echo esc_url(home_url('/sp-app/?sp_type=contract&sp_action=delete&sp_id='.$id.'&sp_nonce='.wp_create_nonce('sp_form'))); ?>"
                       class="sp-btn sp-btn-danger sp-btn-sm" style="width:100%;" data-sp-confirm="Delete this contract?">Delete</a>
                </div>
            </div>
        </div>
    </div>
    <style>.sp-kb-content p{margin:.5em 0}.sp-kb-content ul,.sp-kb-content ol{padding-left:1.4em}.sp-kb-content li{margin:.25em 0}</style>
    <?php return;
}

// ── Edit / New ────────────────────────────────────────────────────────────────
if ( $action === 'new' || $action === 'edit' ) {
    $ctr = ($action==='edit'&&$id) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sp_contracts WHERE id=%d",$id)) : null;
    ?>
    <div class="sp-view-header">
        <h1><?php echo $ctr?'Edit Contract':'New Contract'; ?></h1>
        <a href="<?php echo esc_url(home_url('/sp-app/?view=sales-contracts')); ?>" class="sp-btn sp-btn-secondary">← Back</a>
    </div>
    <div class="sp-card sp-form-card">
    <form method="post" action="<?php echo esc_url(home_url('/sp-app/')); ?>">
        <?php wp_nonce_field('sp_form','sp_nonce'); ?>
        <input type="hidden" name="sp_type" value="contract">
        <input type="hidden" name="sp_id"   value="<?php echo $ctr?(int)$ctr->id:0; ?>">
        <div class="sp-field"><label>Title <span class="sp-required">*</span></label><input type="text" name="title" value="<?php echo esc_attr($ctr->title??''); ?>" required></div>
        <div class="sp-form-row">
            <div class="sp-field"><label>Contact</label>
                <select name="contact_id"><option value="0">— None —</option>
                <?php foreach($contacts as $c): ?><option value="<?php echo $c->id; ?>"<?php selected($ctr->contact_id??0,$c->id); ?>><?php echo esc_html(trim($c->name)); ?></option><?php endforeach; ?></select>
            </div>
            <div class="sp-field"><label>Company</label>
                <select name="company_id"><option value="0">— None —</option>
                <?php foreach($companies as $c): ?><option value="<?php echo $c->id; ?>"<?php selected($ctr->company_id??0,$c->id); ?>><?php echo esc_html($c->name); ?></option><?php endforeach; ?></select>
            </div>
            <div class="sp-field"><label>Status</label>
                <select name="status"><?php foreach($statuses as $s): ?><option value="<?php echo $s; ?>"<?php selected($ctr->status??'Draft',$s); ?>><?php echo $s; ?></option><?php endforeach; ?></select>
            </div>
        </div>
        <div class="sp-form-row">
            <div class="sp-field"><label>Contract Value ($)</label><input type="number" name="value" value="<?php echo (float)($ctr->value??0); ?>" min="0" step="any"></div>
            <div class="sp-field"><label>Start Date</label><input type="date" name="start_date" value="<?php echo esc_attr($ctr->start_date??''); ?>"></div>
            <div class="sp-field"><label>End Date</label><input type="date" name="end_date" value="<?php echo esc_attr($ctr->end_date??''); ?>"></div>
        </div>
        <div class="sp-field" style="margin-top:16px;">
            <label>Contract Content <span class="sp-required">*</span></label>
            <textarea name="content" style="display:none;" required><?php echo esc_textarea($ctr->content??''); ?></textarea>
            <div class="sp-editor-wrap" style="min-height:400px;"></div>
        </div>
        <div class="sp-form-actions"><button type="submit" class="sp-btn sp-btn-primary">Save Contract</button></div>
    </form>
    </div>
    <?php return;
}

// ── List ──────────────────────────────────────────────────────────────────────
$filter = sanitize_key( $_GET['status'] ?? '' );
$where  = $filter ? $wpdb->prepare("WHERE ct.status=%s", ucfirst($filter)) : '';
$ctrs   = $wpdb->get_results(
    "SELECT ct.*, CONCAT(c.first_name,' ',c.last_name) AS contact_name, co.name AS company_name
     FROM {$wpdb->prefix}sp_contracts ct
     LEFT JOIN {$wpdb->prefix}sp_contacts c  ON c.id=ct.contact_id
     LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id=ct.company_id
     $where ORDER BY ct.updated_at DESC"
);
?>
<div class="sp-view-header">
    <h1>Contracts</h1>
    <a href="<?php echo esc_url(home_url('/sp-app/?view=sales-contracts&action=new')); ?>" class="sp-btn sp-btn-primary">+ New Contract</a>
</div>
<?php if(isset($_GET['deleted'])): ?><div class="sp-notice sp-notice-success">Deleted.</div><?php endif; ?>

<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
    <a href="<?php echo esc_url(home_url('/sp-app/?view=sales-contracts')); ?>" class="sp-btn sp-btn-sm <?php echo !$filter?'sp-btn-primary':'sp-btn-ghost'; ?>">All</a>
    <?php foreach($statuses as $s):
        $sv=strtolower($s); $active=$filter===$sv;
    ?>
    <a href="<?php echo esc_url(home_url('/sp-app/?view=sales-contracts&status='.$sv)); ?>"
       class="sp-btn sp-btn-sm <?php echo $active?'sp-btn-primary':'sp-btn-ghost'; ?>"><?php echo $s; ?></a>
    <?php endforeach; ?>
</div>

<div class="sp-card sp-table-card">
<?php if(empty($ctrs)): ?>
    <p class="sp-empty">No contracts yet. <a href="<?php echo esc_url(home_url('/sp-app/?view=sales-contracts&action=new')); ?>" class="sp-link">Create one →</a></p>
<?php else: ?>
    <table class="sp-table">
        <thead><tr><th>Number</th><th>Title</th><th>Client</th><th>Value</th><th>Status</th><th>Period</th><th></th></tr></thead>
        <tbody>
        <?php foreach($ctrs as $ct):
            $col=$status_colors[$ct->status]??'#6b7280';
            $client=trim($ct->contact_name)?:$ct->company_name?:'—';
            $period=$ct->start_date?date('M j, Y',strtotime($ct->start_date)):'—';
            if($ct->end_date) $period.=' – '.date('M j, Y',strtotime($ct->end_date));
        ?>
        <tr>
            <td class="sp-muted" style="font-size:.8rem;"><?php echo esc_html($ct->number); ?></td>
            <td><a href="<?php echo esc_url(home_url('/sp-app/?view=sales-contracts&action=view&id='.$ct->id)); ?>" class="sp-link"><?php echo esc_html($ct->title); ?></a></td>
            <td class="sp-muted"><?php echo esc_html($client); ?></td>
            <td style="font-weight:600;"><?php echo $ct->value>0?'$'.number_format((float)$ct->value,2):'—'; ?></td>
            <td><span style="padding:2px 10px;border-radius:99px;font-size:.72rem;font-weight:700;background:<?php echo $col; ?>22;color:<?php echo $col; ?>;"><?php echo esc_html($ct->status); ?></span></td>
            <td class="sp-muted" style="font-size:.8rem;"><?php echo esc_html($period); ?></td>
            <td><a href="<?php echo esc_url(home_url('/sp-app/?view=sales-contracts&action=edit&id='.$ct->id)); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">Edit</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>
