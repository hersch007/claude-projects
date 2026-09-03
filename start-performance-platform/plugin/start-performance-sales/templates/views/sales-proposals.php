<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$is_admin  = sp_is_admin_member();
$action    = sanitize_key( $_GET['action'] ?? 'list' );
$id        = (int) ( $_GET['id'] ?? 0 );
$contacts  = $wpdb->get_results( "SELECT id, CONCAT(first_name,' ',last_name) AS name FROM {$wpdb->prefix}sp_contacts ORDER BY first_name,last_name" );
$companies = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sp_companies ORDER BY name" );
$statuses  = array( 'Draft', 'Sent', 'Under Review', 'Accepted', 'Declined' );
$status_colors = array( 'Draft'=>'#6b7280','Sent'=>'#3b82f6','Under Review'=>'#f59e0b','Accepted'=>'#16a34a','Declined'=>'#dc2626' );
$status_map = array( 'draft'=>'Draft','sent'=>'Sent','underreview'=>'Under Review','under review'=>'Under Review','accepted'=>'Accepted','declined'=>'Declined' );

$sections = array(
    'overview'      => 'Overview',
    'scope'         => 'Scope of Work',
    'deliverables'  => 'Deliverables',
    'timeline'      => 'Timeline',
    'pricing_notes' => 'Pricing & Terms',
);

// ── View ──────────────────────────────────────────────────────────────────────
if ( $action === 'view' && $id ) {
    $prop = $wpdb->get_row( $wpdb->prepare(
        "SELECT p.*, CONCAT(c.first_name,' ',c.last_name) AS contact_name, co.name AS company_name, t.name AS author
         FROM {$wpdb->prefix}sp_proposals p
         LEFT JOIN {$wpdb->prefix}sp_contacts c  ON c.id=p.contact_id
         LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id=p.company_id
         LEFT JOIN {$wpdb->prefix}sp_team t        ON t.id=p.created_by
         WHERE p.id=%d", $id ) );
    if(!$prop){ echo '<p class="sp-empty">Not found.</p>'; return; }
    $prop->status = $status_map[ strtolower( str_replace( '_', '', $prop->status ?? '' ) ) ] ?? $prop->status;
    $col = $status_colors[$prop->status] ?? '#6b7280';
    ?>
    <div class="sp-view-header">
        <div>
            <h1><?php echo esc_html($prop->title); ?></h1>
            <span style="font-size:.82rem;color:var(--sp-muted);"><?php echo esc_html($prop->number); ?></span>
        </div>
        <div style="display:flex;gap:8px;align-items:center;" class="sp-no-print">
            <span style="padding:4px 12px;border-radius:99px;font-size:.78rem;font-weight:700;background:<?php echo $col; ?>22;color:<?php echo $col; ?>;"><?php echo esc_html($prop->status); ?></span>
            <button onclick="window.print()" class="sp-btn sp-btn-secondary">Print / PDF</button>
            <a href="<?php echo esc_url(home_url('/sp-app/?view=sales-proposals&action=edit&id='.$id)); ?>" class="sp-btn sp-btn-secondary">Edit</a>
            <?php if ( sp_is_admin_member() || sp_is_super_admin() ): ?>
            <form method="post" action="<?php echo esc_url(home_url('/sp-app/')); ?>" style="display:inline;">
                <?php wp_nonce_field('sp_form','sp_nonce'); ?>
                <input type="hidden" name="sp_type" value="proposal_to_contract">
                <input type="hidden" name="sp_id"   value="<?php echo $id; ?>">
                <button type="submit" class="sp-btn sp-btn-primary">Convert to Contract &rarr;</button>
            </form>
            <?php endif; ?>
            <a href="<?php echo esc_url(home_url('/sp-app/?view=sales-proposals')); ?>" class="sp-btn sp-btn-ghost">← Proposals</a>
        </div>
    </div>
    <?php if(isset($_GET['saved'])): ?><div class="sp-notice sp-notice-success">Saved.</div><?php endif; ?>
    <?php if(isset($_GET['converted'])): ?><div class="sp-notice sp-notice-success">Proposal created from estimate. Review and update before sending.</div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 220px;gap:18px;align-items:start;">
        <div>
        <?php foreach($sections as $field=>$label):
            if(empty($prop->$field)) continue;
        ?>
            <div class="sp-card" style="padding:22px 26px;margin-bottom:14px;">
                <h3 style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--sp-muted);margin:0 0 12px;"><?php echo esc_html($label); ?></h3>
                <div class="sp-kb-content" style="line-height:1.7;font-size:.9rem;"><?php echo wp_kses_post($prop->$field); ?></div>
            </div>
        <?php endforeach; ?>
        </div>
        <div>
            <div class="sp-card" style="padding:16px;">
                <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--sp-muted);margin-bottom:12px;">Details</div>
                <?php if(trim($prop->contact_name)): ?><div style="font-size:.83rem;margin-bottom:8px;"><span style="color:var(--sp-muted);">Contact</span><br><?php echo esc_html(trim($prop->contact_name)); ?></div><?php endif; ?>
                <?php if($prop->company_name): ?><div style="font-size:.83rem;margin-bottom:8px;"><span style="color:var(--sp-muted);">Company</span><br><?php echo esc_html($prop->company_name); ?></div><?php endif; ?>
                <?php if($prop->total_value>0): ?><div style="font-size:.83rem;margin-bottom:8px;"><span style="color:var(--sp-muted);">Value</span><br><strong>$<?php echo number_format((float)$prop->total_value,2); ?></strong></div><?php endif; ?>
                <?php if($prop->valid_until): ?><div style="font-size:.83rem;margin-bottom:14px;"><span style="color:var(--sp-muted);">Valid Until</span><br><?php echo esc_html(date('M j, Y',strtotime($prop->valid_until))); ?></div><?php endif; ?>

                <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--sp-muted);margin-bottom:8px;" class="sp-no-print">Update Status</div>
                <form method="post" action="<?php echo esc_url(home_url('/sp-app/')); ?>" class="sp-no-print">
                    <?php wp_nonce_field('sp_form','sp_nonce'); ?>
                    <input type="hidden" name="sp_type" value="proposal_status">
                    <input type="hidden" name="sp_id"   value="<?php echo $id; ?>">
                    <select name="status" style="width:100%;margin-bottom:8px;">
                        <?php foreach($statuses as $s): ?><option value="<?php echo $s; ?>"<?php selected($prop->status,$s); ?>><?php echo $s; ?></option><?php endforeach; ?>
                    </select>
                    <button type="submit" class="sp-btn sp-btn-primary sp-btn-sm" style="width:100%;">Update</button>
                </form>
                <div style="margin-top:10px;" class="sp-no-print">
                    <a href="<?php echo esc_url(home_url('/sp-app/?sp_type=proposal&sp_action=delete&sp_id='.$id.'&sp_nonce='.wp_create_nonce('sp_form'))); ?>"
                       class="sp-btn sp-btn-danger sp-btn-sm" style="width:100%;" data-sp-confirm="Delete this proposal?">Delete</a>
                </div>
            </div>
        </div>
    </div>
    <style>.sp-kb-content p{margin:.5em 0}.sp-kb-content ul,.sp-kb-content ol{padding-left:1.4em}.sp-kb-content li{margin:.25em 0}</style>
    <?php return;
}

// ── Edit / New ────────────────────────────────────────────────────────────────
if ( $action === 'new' || $action === 'edit' ) {
    $prop = ($action==='edit'&&$id) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sp_proposals WHERE id=%d",$id)) : null;
    ?>
    <div class="sp-view-header">
        <h1><?php echo $prop?'Edit Proposal':'New Proposal'; ?></h1>
        <a href="<?php echo esc_url(home_url('/sp-app/?view=sales-proposals')); ?>" class="sp-btn sp-btn-secondary">← Back</a>
    </div>
    <div class="sp-card sp-form-card">
    <form method="post" action="<?php echo esc_url(home_url('/sp-app/')); ?>">
        <?php wp_nonce_field('sp_form','sp_nonce'); ?>
        <input type="hidden" name="sp_type" value="proposal">
        <input type="hidden" name="sp_id"   value="<?php echo $prop?(int)$prop->id:0; ?>">

        <div class="sp-field"><label>Title <span class="sp-required">*</span></label><input type="text" name="title" value="<?php echo esc_attr($prop->title??''); ?>" required></div>
        <div class="sp-form-row">
            <div class="sp-field"><label>Contact</label>
                <select name="contact_id"><option value="0">— None —</option>
                <?php foreach($contacts as $c): ?><option value="<?php echo $c->id; ?>"<?php selected($prop->contact_id??0,$c->id); ?>><?php echo esc_html(trim($c->name)); ?></option><?php endforeach; ?></select>
            </div>
            <div class="sp-field"><label>Company</label>
                <select name="company_id"><option value="0">— None —</option>
                <?php foreach($companies as $c): ?><option value="<?php echo $c->id; ?>"<?php selected($prop->company_id??0,$c->id); ?>><?php echo esc_html($c->name); ?></option><?php endforeach; ?></select>
            </div>
            <div class="sp-field"><label>Status</label>
                <select name="status"><?php foreach($statuses as $s): ?><option value="<?php echo $s; ?>"<?php selected($prop->status??'Draft',$s); ?>><?php echo $s; ?></option><?php endforeach; ?></select>
            </div>
        </div>
        <div class="sp-form-row">
            <div class="sp-field"><label>Total Value ($)</label><input type="number" name="total_value" value="<?php echo (float)($prop->total_value??0); ?>" min="0" step="any"></div>
            <div class="sp-field"><label>Valid Until</label><input type="date" name="valid_until" value="<?php echo esc_attr($prop->valid_until??''); ?>"></div>
        </div>

        <?php foreach($sections as $field=>$label): ?>
        <div class="sp-field" style="margin-top:16px;">
            <label><?php echo esc_html($label); ?></label>
            <textarea name="<?php echo $field; ?>" style="display:none;"><?php echo esc_textarea($prop->$field??''); ?></textarea>
            <div class="sp-editor-wrap"></div>
        </div>
        <?php endforeach; ?>

        <div class="sp-form-actions"><button type="submit" class="sp-btn sp-btn-primary">Save Proposal</button></div>
    </form>
    </div>
    <?php return;
}

// ── List ──────────────────────────────────────────────────────────────────────
$filter = sanitize_key(str_replace(' ','_',$_GET['status']??''));
$where  = '';
if($filter){
    $sf=ucwords(str_replace('_',' ',$filter));
    $where=$wpdb->prepare("WHERE p.status=%s",$sf);
}
$props = $wpdb->get_results(
    "SELECT p.*, CONCAT(c.first_name,' ',c.last_name) AS contact_name, co.name AS company_name
     FROM {$wpdb->prefix}sp_proposals p
     LEFT JOIN {$wpdb->prefix}sp_contacts c  ON c.id=p.contact_id
     LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id=p.company_id
     $where ORDER BY p.updated_at DESC"
);
?>
<div class="sp-view-header">
    <h1>Proposals</h1>
    <a href="<?php echo esc_url(home_url('/sp-app/?view=sales-proposals&action=new')); ?>" class="sp-btn sp-btn-primary">+ New Proposal</a>
</div>
<?php if(isset($_GET['deleted'])): ?><div class="sp-notice sp-notice-success">Deleted.</div><?php endif; ?>

<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
    <a href="<?php echo esc_url(home_url('/sp-app/?view=sales-proposals')); ?>" class="sp-btn sp-btn-sm <?php echo !$filter?'sp-btn-primary':'sp-btn-ghost'; ?>">All</a>
    <?php foreach($statuses as $s):
        $sv=strtolower(str_replace(' ','_',$s));
        $active=$filter===$sv;
    ?>
    <a href="<?php echo esc_url(home_url('/sp-app/?view=sales-proposals&status='.$sv)); ?>"
       class="sp-btn sp-btn-sm <?php echo $active?'sp-btn-primary':'sp-btn-ghost'; ?>"><?php echo $s; ?></a>
    <?php endforeach; ?>
</div>

<div class="sp-card sp-table-card">
<?php if(empty($props)): ?>
    <p class="sp-empty">No proposals yet. <a href="<?php echo esc_url(home_url('/sp-app/?view=sales-proposals&action=new')); ?>" class="sp-link">Create one →</a></p>
<?php else: ?>
    <table class="sp-table">
        <thead><tr><th>Number</th><th>Title</th><th>Client</th><th>Value</th><th>Status</th><th>Updated</th><th></th></tr></thead>
        <tbody>
        <?php foreach($props as $p):
            $p->status = $status_map[ strtolower( str_replace( '_', '', $p->status ?? '' ) ) ] ?? $p->status;
            $col=$status_colors[$p->status]??'#6b7280';
            $client=trim($p->contact_name)?:$p->company_name?:'—';
        ?>
        <tr>
            <td class="sp-muted" style="font-size:.8rem;"><?php echo esc_html($p->number); ?></td>
            <td><a href="<?php echo esc_url(home_url('/sp-app/?view=sales-proposals&action=view&id='.$p->id)); ?>" class="sp-link"><?php echo esc_html($p->title); ?></a></td>
            <td class="sp-muted"><?php echo esc_html($client); ?></td>
            <td style="font-weight:600;"><?php echo $p->total_value>0?'$'.number_format((float)$p->total_value,2):'—'; ?></td>
            <td><span style="padding:2px 10px;border-radius:99px;font-size:.72rem;font-weight:700;background:<?php echo $col; ?>22;color:<?php echo $col; ?>;"><?php echo esc_html($p->status); ?></span></td>
            <td class="sp-muted"><?php echo esc_html(date('M j, Y',strtotime($p->updated_at))); ?></td>
            <td><a href="<?php echo esc_url(home_url('/sp-app/?view=sales-proposals&action=edit&id='.$p->id)); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">Edit</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>
