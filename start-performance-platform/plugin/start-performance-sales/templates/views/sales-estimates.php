<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$is_admin = sp_is_admin_member();
$action   = sanitize_key( $_GET['action'] ?? 'list' );
$id       = (int) ( $_GET['id'] ?? 0 );

$contacts  = $wpdb->get_results( "SELECT id, CONCAT(first_name,' ',last_name) AS name FROM {$wpdb->prefix}sp_contacts ORDER BY first_name,last_name" );
$companies = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sp_companies ORDER BY name" );

$statuses = array( 'Draft', 'Sent', 'Accepted', 'Declined', 'Expired' );
$status_colors = array( 'Draft'=>'#6b7280','Sent'=>'#3b82f6','Accepted'=>'#16a34a','Declined'=>'#dc2626','Expired'=>'#9ca3af' );

// ── View ──────────────────────────────────────────────────────────────────────
if ( $action === 'view' && $id ) {
    $est   = $wpdb->get_row( $wpdb->prepare(
        "SELECT e.*, CONCAT(c.first_name,' ',c.last_name) AS contact_name, co.name AS company_name, t.name AS author
         FROM {$wpdb->prefix}sp_estimates e
         LEFT JOIN {$wpdb->prefix}sp_contacts c  ON c.id=e.contact_id
         LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id=e.company_id
         LEFT JOIN {$wpdb->prefix}sp_team t        ON t.id=e.created_by
         WHERE e.id=%d", $id ) );
    if ( ! $est ) { echo '<p class="sp-empty">Not found.</p>'; return; }
    $est->status = ucfirst( strtolower( $est->status ?? 'Draft' ) );
    $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_estimate_items WHERE estimate_id=%d ORDER BY sort_order ASC", $id ) );
    $tax   = round( ( $est->subtotal - $est->discount ) * $est->tax_rate / 100, 2 );
    $col   = $status_colors[ $est->status ] ?? '#6b7280';
    ?>
    <div class="sp-view-header">
        <div>
            <h1><?php echo esc_html( $est->title ); ?></h1>
            <span style="font-size:.82rem;color:var(--sp-muted);"><?php echo esc_html( $est->number ); ?></span>
        </div>
        <div style="display:flex;gap:8px;align-items:center;" class="sp-no-print">
            <span style="padding:4px 12px;border-radius:99px;font-size:.78rem;font-weight:700;background:<?php echo $col; ?>22;color:<?php echo $col; ?>;"><?php echo esc_html( $est->status ); ?></span>
            <button onclick="window.print()" class="sp-btn sp-btn-secondary">Print / PDF</button>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=sales-estimates&action=edit&id='.$id ) ); ?>" class="sp-btn sp-btn-secondary">Edit</a>
            <?php if ( sp_is_admin_member() || sp_is_super_admin() ): ?>
            <form method="post" action="<?php echo esc_url(home_url('/sp-app/')); ?>" style="display:inline;">
                <?php wp_nonce_field('sp_form','sp_nonce'); ?>
                <input type="hidden" name="sp_type" value="estimate_to_proposal">
                <input type="hidden" name="sp_id"   value="<?php echo $id; ?>">
                <button type="submit" class="sp-btn sp-btn-primary">Convert to Proposal &rarr;</button>
            </form>
            <?php endif; ?>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=sales-estimates' ) ); ?>" class="sp-btn sp-btn-ghost">← Estimates</a>
        </div>
    </div>
    <?php if ( isset($_GET['saved']) ): ?><div class="sp-notice sp-notice-success">Saved.</div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 220px;gap:18px;align-items:start;">
        <div>
            <div class="sp-card" style="padding:0;overflow:hidden;">
                <table class="sp-table">
                    <thead><tr><th>Description</th><th style="text-align:right;">Qty</th><th style="text-align:right;">Unit Price</th><th style="text-align:right;">Total</th></tr></thead>
                    <tbody>
                    <?php foreach($items as $li): ?>
                        <tr>
                            <td><?php echo esc_html($li->description); ?></td>
                            <td style="text-align:right;"><?php echo number_format((float)$li->qty,2); ?></td>
                            <td style="text-align:right;">$<?php echo number_format((float)$li->unit_price,2); ?></td>
                            <td style="text-align:right;font-weight:600;">$<?php echo number_format((float)$li->line_total,2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="3" style="text-align:right;color:var(--sp-muted);">Subtotal</td><td style="text-align:right;">$<?php echo number_format((float)$est->subtotal,2); ?></td></tr>
                        <?php if($est->discount>0): ?><tr><td colspan="3" style="text-align:right;color:var(--sp-muted);">Discount</td><td style="text-align:right;color:#dc2626;">−$<?php echo number_format((float)$est->discount,2); ?></td></tr><?php endif; ?>
                        <?php if($est->tax_rate>0): ?><tr><td colspan="3" style="text-align:right;color:var(--sp-muted);">Tax (<?php echo $est->tax_rate; ?>%)</td><td style="text-align:right;">$<?php echo number_format($tax,2); ?></td></tr><?php endif; ?>
                        <tr style="font-weight:700;font-size:1rem;"><td colspan="3" style="text-align:right;">Total</td><td style="text-align:right;">$<?php echo number_format((float)$est->total,2); ?></td></tr>
                    </tfoot>
                </table>
            </div>
            <?php if($est->notes): ?><div class="sp-card" style="padding:16px 20px;margin-top:14px;font-size:.87rem;line-height:1.6;"><?php echo nl2br(esc_html($est->notes)); ?></div><?php endif; ?>
        </div>
        <div class="sp-print-sidebar">
            <div class="sp-card" style="padding:16px;">
                <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--sp-muted);margin-bottom:12px;">Details</div>
                <?php if(trim($est->contact_name)): ?><div style="font-size:.83rem;margin-bottom:8px;"><span style="color:var(--sp-muted);">Contact</span><br><?php echo esc_html(trim($est->contact_name)); ?></div><?php endif; ?>
                <?php if($est->company_name): ?><div style="font-size:.83rem;margin-bottom:8px;"><span style="color:var(--sp-muted);">Company</span><br><?php echo esc_html($est->company_name); ?></div><?php endif; ?>
                <?php if($est->valid_until): ?><div style="font-size:.83rem;margin-bottom:8px;"><span style="color:var(--sp-muted);">Valid Until</span><br><?php echo esc_html(date('M j, Y',strtotime($est->valid_until))); ?></div><?php endif; ?>
                <div style="font-size:.83rem;margin-bottom:14px;"><span style="color:var(--sp-muted);">Created</span><br><?php echo esc_html(date('M j, Y',strtotime($est->created_at))); ?><?php if($est->author): ?> by <?php echo esc_html($est->author); ?><?php endif; ?></div>

                <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--sp-muted);margin-bottom:8px;" class="sp-no-print">Update Status</div>
                <form method="post" action="<?php echo esc_url(home_url('/sp-app/')); ?>" class="sp-no-print">
                    <?php wp_nonce_field('sp_form','sp_nonce'); ?>
                    <input type="hidden" name="sp_type" value="estimate_status">
                    <input type="hidden" name="sp_id"   value="<?php echo $id; ?>">
                    <select name="status" style="width:100%;margin-bottom:8px;">
                        <?php $cur_status=ucfirst(strtolower($est->status??'')); foreach($statuses as $s): ?><option value="<?php echo $s; ?>"<?php selected($cur_status,$s); ?>><?php echo $s; ?></option><?php endforeach; ?>
                    </select>
                    <button type="submit" class="sp-btn sp-btn-primary sp-btn-sm" style="width:100%;">Update</button>
                </form>

                <div style="margin-top:12px;" class="sp-no-print">
                    <a href="<?php echo esc_url(home_url('/sp-app/?sp_type=estimate&sp_action=delete&sp_id='.$id.'&sp_nonce='.wp_create_nonce('sp_form'))); ?>"
                       class="sp-btn sp-btn-danger sp-btn-sm" style="width:100%;" data-sp-confirm="Delete this estimate?">Delete</a>
                </div>
            </div>
        </div>
    </div>
    <?php return;
}

// ── Edit / New ────────────────────────────────────────────────────────────────
if ( $action === 'new' || $action === 'edit' ) {
    $est   = ( $action === 'edit' && $id ) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sp_estimates WHERE id=%d",$id)) : null;
    $items = $est ? $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sp_estimate_items WHERE estimate_id=%d ORDER BY sort_order ASC",$id)) : array();
    if(empty($items)) $items = array((object)array('description'=>'','qty'=>1,'unit_price'=>0,'line_total'=>0));
    ?>
    <div class="sp-view-header">
        <h1><?php echo $est ? 'Edit Estimate' : 'New Estimate'; ?></h1>
        <a href="<?php echo esc_url(home_url('/sp-app/?view=sales-estimates')); ?>" class="sp-btn sp-btn-secondary">← Back</a>
    </div>
    <div class="sp-card sp-form-card">
    <form method="post" action="<?php echo esc_url(home_url('/sp-app/')); ?>">
        <?php wp_nonce_field('sp_form','sp_nonce'); ?>
        <input type="hidden" name="sp_type" value="estimate">
        <input type="hidden" name="sp_id"   value="<?php echo $est ? (int)$est->id : 0; ?>">

        <div class="sp-field"><label>Title <span class="sp-required">*</span></label><input type="text" name="title" value="<?php echo esc_attr($est->title??''); ?>" required></div>
        <div class="sp-form-row">
            <div class="sp-field"><label>Contact</label>
                <select name="contact_id"><option value="0">— None —</option>
                <?php foreach($contacts as $c): ?><option value="<?php echo $c->id; ?>"<?php selected($est->contact_id??0,$c->id); ?>><?php echo esc_html(trim($c->name)); ?></option><?php endforeach; ?></select>
            </div>
            <div class="sp-field"><label>Company</label>
                <select name="company_id"><option value="0">— None —</option>
                <?php foreach($companies as $c): ?><option value="<?php echo $c->id; ?>"<?php selected($est->company_id??0,$c->id); ?>><?php echo esc_html($c->name); ?></option><?php endforeach; ?></select>
            </div>
            <div class="sp-field"><label>Status</label>
                <select name="status"><?php foreach($statuses as $s): ?><option value="<?php echo $s; ?>"<?php selected($est->status??'Draft',$s); ?>><?php echo $s; ?></option><?php endforeach; ?></select>
            </div>
            <div class="sp-field"><label>Valid Until</label><input type="date" name="valid_until" value="<?php echo esc_attr($est->valid_until??''); ?>"></div>
        </div>

        <h3 style="margin:20px 0 10px;font-size:.9rem;font-weight:700;">Line Items</h3>
        <div style="display:grid;grid-template-columns:1fr 80px 110px 100px 36px;gap:6px;margin-bottom:6px;font-size:.75rem;font-weight:700;color:var(--sp-muted);text-transform:uppercase;">
            <span>Description</span><span style="text-align:right;">Qty</span><span style="text-align:right;">Unit Price</span><span style="text-align:right;">Total</span><span></span>
        </div>
        <div id="sp-est-items">
        <?php foreach($items as $li): ?>
        <div class="sp-est-row" style="display:grid;grid-template-columns:1fr 80px 110px 100px 36px;gap:6px;margin-bottom:6px;align-items:center;">
            <input type="text" name="item_desc[]" value="<?php echo esc_attr($li->description); ?>" placeholder="Item description" required>
            <input type="number" name="item_qty[]" value="<?php echo (float)$li->qty; ?>" min="0" step="any" class="sp-est-qty" style="text-align:right;">
            <input type="number" name="item_price[]" value="<?php echo (float)$li->unit_price; ?>" min="0" step="any" class="sp-est-price" style="text-align:right;" placeholder="0.00">
            <input type="text" class="sp-est-line" readonly style="text-align:right;background:var(--sp-bg,#f9fafb);font-weight:600;" value="$<?php echo number_format((float)$li->line_total,2); ?>">
            <button type="button" class="sp-btn sp-btn-ghost sp-btn-sm sp-est-remove" style="padding:4px 6px;">✕</button>
        </div>
        <?php endforeach; ?>
        </div>
        <button type="button" id="sp-est-add-row" class="sp-btn sp-btn-secondary sp-btn-sm" style="margin-bottom:18px;">+ Add Line</button>

        <div class="sp-form-row">
            <div class="sp-field"><label>Discount ($)</label><input type="number" name="discount" value="<?php echo (float)($est->discount??0); ?>" min="0" step="any" id="sp-est-discount"></div>
            <div class="sp-field"><label>Tax Rate (%)</label><input type="number" name="tax_rate" value="<?php echo (float)($est->tax_rate??0); ?>" min="0" step="any" id="sp-est-tax"></div>
        </div>

        <div style="text-align:right;padding:12px 0;border-top:1px solid var(--sp-border,#e5e7eb);margin-top:8px;">
            <span style="color:var(--sp-muted);margin-right:12px;">Subtotal: <strong id="sp-est-subtotal">$0.00</strong></span>
            <span style="font-size:1.05rem;font-weight:700;">Total: <strong id="sp-est-total">$0.00</strong></span>
        </div>

        <div class="sp-field"><label>Notes</label><textarea name="notes" rows="3" placeholder="Payment terms, conditions, etc."><?php echo esc_textarea($est->notes??''); ?></textarea></div>
        <div class="sp-form-actions"><button type="submit" class="sp-btn sp-btn-primary">Save Estimate</button></div>
    </form>
    </div>

    <script>
    (function(){
        function fmt(n){ return '$'+parseFloat(n||0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,','); }
        function recalc(){
            var sub=0;
            document.querySelectorAll('#sp-est-items .sp-est-row').forEach(function(r){
                var q=parseFloat(r.querySelector('.sp-est-qty').value)||0;
                var p=parseFloat(r.querySelector('.sp-est-price').value)||0;
                var t=Math.round(q*p*100)/100;
                r.querySelector('.sp-est-line').value=fmt(t);
                sub+=t;
            });
            var disc=parseFloat(document.getElementById('sp-est-discount').value)||0;
            var tax=parseFloat(document.getElementById('sp-est-tax').value)||0;
            var taxAmt=Math.round((sub-disc)*tax/100*100)/100;
            document.getElementById('sp-est-subtotal').textContent=fmt(sub);
            document.getElementById('sp-est-total').textContent=fmt(sub-disc+taxAmt);
        }
        document.getElementById('sp-est-items').addEventListener('input',recalc);
        document.getElementById('sp-est-discount').addEventListener('input',recalc);
        document.getElementById('sp-est-tax').addEventListener('input',recalc);
        document.getElementById('sp-est-add-row').addEventListener('click',function(){
            var r=document.createElement('div');
            r.className='sp-est-row';
            r.style.cssText='display:grid;grid-template-columns:1fr 80px 110px 100px 36px;gap:6px;margin-bottom:6px;align-items:center;';
            r.innerHTML='<input type="text" name="item_desc[]" placeholder="Item description" required>'
                +'<input type="number" name="item_qty[]" value="1" min="0" step="any" class="sp-est-qty" style="text-align:right;">'
                +'<input type="number" name="item_price[]" value="0" min="0" step="any" class="sp-est-price" style="text-align:right;">'
                +'<input type="text" class="sp-est-line" readonly style="text-align:right;background:var(--sp-bg,#f9fafb);font-weight:600;" value="$0.00">'
                +'<button type="button" class="sp-btn sp-btn-ghost sp-btn-sm sp-est-remove" style="padding:4px 6px;">✕</button>';
            document.getElementById('sp-est-items').appendChild(r);
        });
        document.getElementById('sp-est-items').addEventListener('click',function(e){
            if(e.target.classList.contains('sp-est-remove')){
                var rows=document.querySelectorAll('#sp-est-items .sp-est-row');
                if(rows.length>1){ e.target.closest('.sp-est-row').remove(); recalc(); }
            }
        });
        recalc();
    })();
    </script>
    <?php return;
}

// ── List ──────────────────────────────────────────────────────────────────────
$filter = sanitize_key( $_GET['status'] ?? '' );
$where  = $filter ? $wpdb->prepare( "WHERE e.status=%s", ucfirst($filter) ) : '';
$ests   = $wpdb->get_results(
    "SELECT e.*, CONCAT(c.first_name,' ',c.last_name) AS contact_name, co.name AS company_name
     FROM {$wpdb->prefix}sp_estimates e
     LEFT JOIN {$wpdb->prefix}sp_contacts c  ON c.id=e.contact_id
     LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id=e.company_id
     $where ORDER BY e.updated_at DESC"
);
?>
<div class="sp-view-header">
    <h1>Estimates</h1>
    <a href="<?php echo esc_url(home_url('/sp-app/?view=sales-estimates&action=new')); ?>" class="sp-btn sp-btn-primary">+ New Estimate</a>
</div>
<?php if(isset($_GET['deleted'])): ?><div class="sp-notice sp-notice-success">Deleted.</div><?php endif; ?>

<div style="display:flex;gap:8px;margin-bottom:16px;">
    <?php foreach(array(''=>'All')+array_combine($statuses,$statuses) as $sv=>$sl):
        $active = ($filter===$sv);
    ?>
    <a href="<?php echo esc_url(home_url('/sp-app/?view=sales-estimates'.($sv?'&status='.$sv:''))); ?>"
       class="sp-btn sp-btn-sm <?php echo $active?'sp-btn-primary':'sp-btn-ghost'; ?>"><?php echo $sl; ?></a>
    <?php endforeach; ?>
</div>

<div class="sp-card sp-table-card">
<?php if(empty($ests)): ?>
    <p class="sp-empty">No estimates yet. <a href="<?php echo esc_url(home_url('/sp-app/?view=sales-estimates&action=new')); ?>" class="sp-link">Create one →</a></p>
<?php else: ?>
    <table class="sp-table">
        <thead><tr><th>Number</th><th>Title</th><th>Client</th><th>Total</th><th>Status</th><th>Updated</th><th></th></tr></thead>
        <tbody>
        <?php foreach($ests as $e):
            $col = $status_colors[$e->status]??'#6b7280';
            $client = trim($e->contact_name) ?: $e->company_name ?: '—';
        ?>
        <tr>
            <td class="sp-muted" style="font-size:.8rem;"><?php echo esc_html($e->number); ?></td>
            <td><a href="<?php echo esc_url(home_url('/sp-app/?view=sales-estimates&action=view&id='.$e->id)); ?>" class="sp-link"><?php echo esc_html($e->title); ?></a></td>
            <td class="sp-muted"><?php echo esc_html($client); ?></td>
            <td style="font-weight:600;">$<?php echo number_format((float)$e->total,2); ?></td>
            <td><span style="padding:2px 10px;border-radius:99px;font-size:.72rem;font-weight:700;background:<?php echo $col; ?>22;color:<?php echo $col; ?>;"><?php echo esc_html($e->status); ?></span></td>
            <td class="sp-muted"><?php echo esc_html(date('M j, Y',strtotime($e->updated_at))); ?></td>
            <td><a href="<?php echo esc_url(home_url('/sp-app/?view=sales-estimates&action=edit&id='.$e->id)); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">Edit</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>
