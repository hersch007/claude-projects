<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$is_admin = sp_is_admin_member();
$action   = sanitize_key( $_GET['action'] ?? '' );

// ── New / Edit template form ──────────────────────────────────────────────────

if ( $is_admin && in_array( $action, array( 'new', 'edit' ), true ) ) {
    $tpl   = null;
    $items = array();
    $edit_id = (int) ( $_GET['id'] ?? 0 );
    if ( $action === 'edit' && $edit_id ) {
        $tpl   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sp_ob_templates WHERE id=%d", $edit_id ) );
        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sp_ob_items WHERE template_id=%d ORDER BY sort_order ASC", $edit_id
        ) );
    }
    ?>
    <div class="sp-view-header">
        <h1><?php echo $tpl ? 'Edit Checklist Template' : 'New Checklist Template'; ?></h1>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=onboarding' ) ); ?>" class="sp-btn sp-btn-secondary">← Back</a>
    </div>
    <div class="sp-card sp-form-card">
        <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
            <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
            <input type="hidden" name="sp_type" value="ob_template">
            <input type="hidden" name="sp_id"   value="<?php echo $tpl ? (int)$tpl->id : 0; ?>">

            <div class="sp-field">
                <label>Checklist Name <span class="sp-required">*</span></label>
                <input type="text" name="name" value="<?php echo esc_attr( $tpl->name ?? '' ); ?>" required placeholder="e.g. New Client Onboarding">
            </div>
            <div class="sp-field">
                <label>Description</label>
                <textarea name="description" rows="2"><?php echo esc_textarea( $tpl->description ?? '' ); ?></textarea>
            </div>

            <h3 style="margin:24px 0 8px;font-size:.9rem;font-weight:700;">Checklist Items</h3>
            <p class="sp-hint" style="margin-bottom:12px;">Each item becomes a checkbox on the client's onboarding card.</p>

            <div id="sp-ob-items">
                <?php
                $default = empty( $items ) ? array( array( 'title' => '', 'description' => '' ) ) : $items;
                foreach ( $default as $i => $item ) :
                    $t = is_array( $item ) ? ( $item['title'] ?? '' ) : $item->title;
                    $d = is_array( $item ) ? ( $item['description'] ?? '' ) : $item->description;
                ?>
                <div class="sp-ob-item-row" style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:start;">
                    <input type="text"  name="item_title[]" value="<?php echo esc_attr( $t ); ?>" placeholder="Item title" required>
                    <input type="text"  name="item_desc[]"  value="<?php echo esc_attr( $d ); ?>" placeholder="Optional sub-text (hint for team)">
                    <button type="button" class="sp-btn sp-btn-ghost sp-btn-sm sp-ob-remove" title="Remove" style="padding:4px 8px;">✕</button>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="button" id="sp-ob-add" class="sp-btn sp-btn-secondary sp-btn-sm" style="margin-bottom:20px;">+ Add Item</button>

            <div class="sp-form-actions">
                <?php if ( $tpl ) : ?>
                    <a href="<?php echo esc_url( home_url( '/sp-app/?sp_type=ob_template&sp_action=delete&sp_id=' . (int)$tpl->id . '&sp_nonce=' . wp_create_nonce('sp_form') ) ); ?>"
                       class="sp-btn sp-btn-danger" data-sp-confirm="Delete this checklist template?">Delete</a>
                <?php endif; ?>
                <button type="submit" class="sp-btn sp-btn-primary">Save Template</button>
            </div>
        </form>
    </div>
    <script>
    (function(){
        var c = document.getElementById('sp-ob-items');
        document.getElementById('sp-ob-add').addEventListener('click', function(){
            var r = document.createElement('div');
            r.className = 'sp-ob-item-row';
            r.style.cssText = 'display:grid;grid-template-columns:1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:start;';
            r.innerHTML = '<input type="text" name="item_title[]" placeholder="Item title" required>'
                + '<input type="text" name="item_desc[]" placeholder="Optional sub-text">'
                + '<button type="button" class="sp-btn sp-btn-ghost sp-btn-sm sp-ob-remove" style="padding:4px 8px;">✕</button>';
            c.appendChild(r);
        });
        c.addEventListener('click', function(e){
            if ( e.target.classList.contains('sp-ob-remove') ) {
                var rows = c.querySelectorAll('.sp-ob-item-row');
                if ( rows.length > 1 ) e.target.closest('.sp-ob-item-row').remove();
            }
        });
    })();
    </script>
    <?php
    return;
}

// ── Main list ─────────────────────────────────────────────────────────────────

$templates = $wpdb->get_results(
    "SELECT t.*, COUNT(i.id) AS item_count
     FROM {$wpdb->prefix}sp_ob_templates t
     LEFT JOIN {$wpdb->prefix}sp_ob_items i ON i.template_id = t.id
     GROUP BY t.id ORDER BY t.name ASC"
);

$active = $wpdb->get_results(
    "SELECT i.*,
        t.name AS template_name,
        COALESCE( CONCAT(c.first_name,' ',c.last_name), co.name, '' ) AS record_name,
        COUNT(DISTINCT it.id)     AS total_items,
        COUNT(DISTINCT p.item_id) AS done_items
     FROM {$wpdb->prefix}sp_ob_instances i
     JOIN  {$wpdb->prefix}sp_ob_templates t  ON t.id = i.template_id
     LEFT JOIN {$wpdb->prefix}sp_contacts  c  ON i.record_type='contact' AND i.record_id=c.id
     LEFT JOIN {$wpdb->prefix}sp_companies co ON i.record_type='company' AND i.record_id=co.id
     LEFT JOIN {$wpdb->prefix}sp_ob_items it  ON it.template_id = i.template_id
     LEFT JOIN {$wpdb->prefix}sp_ob_progress p ON p.instance_id = i.id
     WHERE i.status='active'
     GROUP BY i.id ORDER BY i.started_at DESC"
);
?>

<div class="sp-view-header">
    <h1>Onboarding</h1>
    <?php if ( $is_admin ) : ?>
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=onboarding&action=new' ) ); ?>" class="sp-btn sp-btn-secondary">+ New Template</a>
    <?php endif; ?>
</div>

<?php if ( isset( $_GET['saved'] ) )   : ?><div class="sp-notice sp-notice-success">Template saved.</div><?php endif; ?>
<?php if ( isset( $_GET['deleted'] ) ) : ?><div class="sp-notice sp-notice-success">Template deleted.</div><?php endif; ?>

<?php if ( ! empty( $active ) ) : ?>
<div class="sp-card sp-table-card" style="margin-bottom:24px;">
    <div class="sp-card-header"><h2>Active Onboardings</h2></div>
    <table class="sp-table">
        <thead><tr><th>Checklist</th><th>Client</th><th>Progress</th><th>Started</th></tr></thead>
        <tbody>
        <?php foreach ( $active as $inst ) :
            $total = (int) $inst->total_items;
            $done  = (int) $inst->done_items;
            $pct   = $total > 0 ? round( $done / $total * 100 ) : 0;
            $view_link = $inst->record_type === 'company'
                ? home_url( '/sp-app/?view=companies&action=view&id=' . $inst->record_id )
                : home_url( '/sp-app/?view=contacts&action=view&id=' . $inst->record_id );
        ?>
            <tr>
                <td><?php echo esc_html( $inst->template_name ); ?></td>
                <td><a href="<?php echo esc_url( $view_link ); ?>" class="sp-link"><?php echo esc_html( $inst->record_name ?: '—' ); ?></a>
                    <span style="font-size:.75rem;color:var(--sp-muted);"> (<?php echo esc_html( $inst->record_type ); ?>)</span></td>
                <td style="min-width:160px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="flex:1;background:var(--sp-border,#e5e7eb);border-radius:99px;height:6px;">
                            <div style="width:<?php echo $pct; ?>%;height:6px;border-radius:99px;background:<?php echo $pct===100 ? '#16a34a' : 'var(--sp-primary,#2563eb)'; ?>;"></div>
                        </div>
                        <span style="font-size:.78rem;color:var(--sp-muted);white-space:nowrap;"><?php echo $done; ?>/<?php echo $total; ?></span>
                    </div>
                </td>
                <td class="sp-muted"><?php echo esc_html( date( 'M j, Y', strtotime( $inst->started_at ) ) ); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="sp-card sp-table-card">
    <div class="sp-card-header"><h2>Checklist Templates</h2></div>
    <?php if ( empty( $templates ) ) : ?>
        <p class="sp-empty">No templates yet.
            <?php if ( $is_admin ) : ?>
                <a href="<?php echo esc_url( home_url( '/sp-app/?view=onboarding&action=new' ) ); ?>" class="sp-link">Create your first →</a>
            <?php endif; ?>
        </p>
    <?php else : ?>
        <table class="sp-table">
            <thead><tr><th>Name</th><th>Items</th><th>Description</th><th></th></tr></thead>
            <tbody>
            <?php foreach ( $templates as $t ) : ?>
                <tr>
                    <td><strong><?php echo esc_html( $t->name ); ?></strong></td>
                    <td><span class="sp-badge"><?php echo (int)$t->item_count; ?> item<?php echo $t->item_count != 1 ? 's' : ''; ?></span></td>
                    <td class="sp-muted"><?php echo esc_html( $t->description ?: '—' ); ?></td>
                    <td style="text-align:right;">
                        <?php if ( $is_admin ) : ?>
                            <a href="<?php echo esc_url( home_url( '/sp-app/?view=onboarding&action=edit&id=' . (int)$t->id ) ); ?>" class="sp-btn sp-btn-secondary sp-btn-sm">Edit</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="sp-hint" style="padding:12px 16px 4px;">To assign a checklist to a client, open their <a href="<?php echo esc_url( home_url('/sp-app/?view=contacts') ); ?>" class="sp-link">contact</a> or <a href="<?php echo esc_url( home_url('/sp-app/?view=companies') ); ?>" class="sp-link">company</a> record.</p>
    <?php endif; ?>
</div>
