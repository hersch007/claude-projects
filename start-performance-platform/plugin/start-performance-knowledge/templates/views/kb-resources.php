<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$is_admin = sp_is_admin_member();
$action   = sanitize_key( $_GET['action'] ?? '' );
$cat_id   = (int)( $_GET['cat'] ?? 0 );
$search   = sanitize_text_field( $_GET['s'] ?? '' );
$edit_id  = (int)( $_GET['id'] ?? 0 );

// -- Category form ----------------------------------------------------------
if ( $is_admin && $action === 'new-cat' ) { ?>
    <div class="sp-view-header">
        <h1>New Resource Category</h1>
        <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-resources')); ?>" class="sp-btn sp-btn-secondary">&larr; Back</a>
    </div>
    <div class="sp-card sp-form-card">
        <form method="post" action="<?php echo esc_url(home_url('/sp-app/')); ?>">
            <?php wp_nonce_field('sp_form','sp_nonce'); ?>
            <input type="hidden" name="sp_type"  value="kb_category">
            <input type="hidden" name="sp_id"    value="0">
            <input type="hidden" name="cat_type" value="resource">
            <div class="sp-field"><label>Category Name <span class="sp-required">*</span></label><input type="text" name="name" required></div>
            <div class="sp-field"><label>Description</label><textarea name="description" rows="2"></textarea></div>
            <div class="sp-form-actions"><button type="submit" class="sp-btn sp-btn-primary">Save Category</button></div>
        </form>
    </div>
    <?php return;
}

// -- Add / Edit resource form ------------------------------------------------
if ( $is_admin && ( $action === 'new' || ( $action === 'edit' && $edit_id ) ) ) {
    $cats = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sp_kb_categories WHERE type='resource' ORDER BY name ASC");
    $res  = ( $action === 'edit' ) ? $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}sp_kb_resources WHERE id=%d", $edit_id) ) : null;
    $is_file_res = $res && $res->resource_type === 'file';
    ?>
    <div class="sp-view-header">
        <h1><?php echo $res ? 'Edit Resource' : 'Add Resource'; ?></h1>
        <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-resources')); ?>" class="sp-btn sp-btn-secondary">&larr; Back</a>
    </div>
    <div class="sp-card sp-form-card">
        <form method="post" action="<?php echo esc_url(home_url('/sp-app/')); ?>" enctype="multipart/form-data">
            <?php wp_nonce_field('sp_form','sp_nonce'); ?>
            <input type="hidden" name="sp_type" value="kb_resource">
            <input type="hidden" name="sp_id"   value="<?php echo $res ? (int)$res->id : 0; ?>">
            <div class="sp-field"><label>Title <span class="sp-required">*</span></label><input type="text" name="title" value="<?php echo esc_attr($res->title??''); ?>" required></div>
            <div class="sp-field"><label>Description</label><textarea name="description" rows="2"><?php echo esc_textarea($res->description??''); ?></textarea></div>
            <div class="sp-field">
                <label>Category</label>
                <select name="category_id">
                    <option value="0">-- Uncategorized --</option>
                    <?php foreach($cats as $c): ?>
                        <option value="<?php echo $c->id; ?>" <?php selected($res->category_id??0,$c->id); ?>><?php echo esc_html($c->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sp-field">
                <label>Type</label>
                <div style="display:flex;gap:16px;margin-top:6px;">
                    <label style="display:flex;align-items:center;gap:6px;font-weight:normal;cursor:pointer;">
                        <input type="radio" name="resource_type" value="link" <?php checked(!$is_file_res); ?> onchange="document.getElementById('sp-res-file').style.display='none';document.getElementById('sp-res-link').style.display='';">
                        Link / URL
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;font-weight:normal;cursor:pointer;">
                        <input type="radio" name="resource_type" value="file" <?php checked($is_file_res); ?> onchange="document.getElementById('sp-res-link').style.display='none';document.getElementById('sp-res-file').style.display='';">
                        Upload File
                    </label>
                </div>
            </div>
            <div class="sp-field" id="sp-res-link" <?php if($is_file_res) echo 'style="display:none;"'; ?>>
                <label>URL <?php if(!$is_file_res): ?><span class="sp-required">*</span><?php endif; ?></label>
                <input type="url" name="url" value="<?php echo esc_attr($res->url??''); ?>" placeholder="https://">
            </div>
            <div class="sp-field" id="sp-res-file" <?php if(!$is_file_res) echo 'style="display:none;"'; ?>>
                <label>File <?php if($is_file_res): ?><span class="sp-required">*</span><?php endif; ?></label>
                <?php if($is_file_res && $res->filename): ?>
                    <p class="sp-hint" style="margin-bottom:6px;">Current: <strong><?php echo esc_html($res->filename); ?></strong> &mdash; upload a new file to replace it, or leave blank to keep.</p>
                <?php endif; ?>
                <input type="file" name="resource_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.png,.jpg,.jpeg,.gif,.mp4,.mov">
            </div>
            <div class="sp-form-actions">
                <?php if($res): ?>
                    <a href="<?php echo esc_url(home_url('/sp-app/?sp_delete=kb_resource&id='.(int)$res->id.'&_wpnonce='.wp_create_nonce('sp_delete_kb_resource_'.(int)$res->id))); ?>"
                       class="sp-btn sp-btn-danger" data-sp-confirm="Delete this resource?">Delete</a>
                <?php endif; ?>
                <button type="submit" class="sp-btn sp-btn-primary"><?php echo $res ? 'Save Changes' : 'Add Resource'; ?></button>
            </div>
        </form>
    </div>
    <?php return;
}

// -- List -------------------------------------------------------------------
$where = array('1=1'); $args = array();
if ( $cat_id )  { $where[] = 'r.category_id=%d'; $args[] = $cat_id; }
if ( $search )  { $where[] = '(r.title LIKE %s OR r.description LIKE %s)'; $s="%{$search}%"; $args[]=($s); $args[]=($s); }
$sql = "SELECT r.*, c.name AS cat_name
        FROM {$wpdb->prefix}sp_kb_resources r
        LEFT JOIN {$wpdb->prefix}sp_kb_categories c ON c.id=r.category_id
        WHERE ".implode(' AND ',$where)." ORDER BY c.sort_order ASC, c.name ASC, r.title ASC";
$resources = $args ? $wpdb->get_results($wpdb->prepare($sql,$args)) : $wpdb->get_results($sql);
?>

<div class="sp-view-header">
    <h1>Resource Library</h1>
    <div style="display:flex;gap:8px;align-items:center;">
        <form method="get" action="<?php echo esc_url(home_url('/sp-app/')); ?>" style="display:flex;gap:0;">
            <input type="hidden" name="view" value="kb-resources">
            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search resources..." style="border-radius:6px 0 0 6px;border:1px solid var(--sp-border,#e5e7eb);border-right:none;padding:7px 12px;font-size:.85rem;outline:none;width:200px;">
            <button type="submit" class="sp-btn sp-btn-primary" style="border-radius:0 6px 6px 0;padding:7px 14px;">Search</button>
            <?php if($search): ?><a href="<?php echo esc_url(home_url('/sp-app/?view=kb-resources')); ?>" class="sp-btn sp-btn-ghost" style="padding:7px 10px;">&times;</a><?php endif; ?>
        </form>
        <?php if($is_admin): ?>
            <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-resources&action=new')); ?>" class="sp-btn sp-btn-primary">+ Add Resource</a>
            <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-resources&action=new-cat')); ?>" class="sp-btn sp-btn-secondary">+ Category</a>
        <?php endif; ?>
    </div>
</div>

<?php if(isset($_GET['saved']))  : ?><div class="sp-notice sp-notice-success">Resource saved.</div><?php endif; ?>
<?php if(isset($_GET['deleted'])): ?><div class="sp-notice sp-notice-success">Resource deleted.</div><?php endif; ?>

<?php if($search): ?>
    <p style="font-size:.85rem;color:var(--sp-muted);margin-bottom:12px;">
        <?php echo count($resources); ?> result<?php echo count($resources)===1?'':'s'; ?> for &ldquo;<?php echo esc_html($search); ?>&rdquo;
    </p>
<?php endif; ?>

<?php if(empty($resources)): ?>
    <div class="sp-card" style="padding:20px;"><p class="sp-empty">
        <?php echo $search ? 'No resources match your search.' : 'No resources yet.'; ?>
        <?php if($is_admin && !$search): ?><a href="<?php echo esc_url(home_url('/sp-app/?view=kb-resources&action=new')); ?>" class="sp-link">Add the first one &rarr;</a><?php endif; ?>
    </p></div>
<?php else: ?>
    <?php
    $by_cat = array();
    foreach($resources as $r){
        $cn = $r->cat_name ?: '-- Uncategorized';
        $by_cat[$cn][] = $r;
    }
    foreach($by_cat as $cat_name => $rows):
    ?>
    <div class="sp-card sp-table-card" style="margin-bottom:18px;">
        <div class="sp-card-header"><h2><?php echo esc_html($cat_name); ?></h2></div>
        <table class="sp-table">
            <thead><tr><th>Title</th><th>Type</th><th>Description</th><th>Added</th><?php if($is_admin): ?><th>Views</th><?php endif; ?><th></th></tr></thead>
            <tbody>
            <?php foreach($rows as $r):
                $is_file = $r->resource_type === 'file';
                $icon = $is_file
                    ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path fill="currentColor" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z"/></svg>'
                    : '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path fill="currentColor" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>';
            ?>
            <tr>
                <td>
                    <a href="<?php echo esc_url($r->url); ?>" target="_blank" class="sp-link sp-res-link" data-res-id="<?php echo (int)$r->id; ?>" style="display:flex;align-items:center;gap:6px;">
                        <?php echo $icon; ?> <?php echo esc_html($r->title); ?>
                    </a>
                </td>
                <td><span class="sp-badge"><?php echo $is_file ? 'File' : 'Link'; ?><?php if($is_file && $r->filesize): ?> &middot; <?php echo esc_html(sp_format_filesize($r->filesize)); ?><?php endif; ?></span></td>
                <td class="sp-muted"><?php echo esc_html($r->description ?: '--'); ?></td>
                <td class="sp-muted sp-nowrap"><?php echo esc_html(date('M j, Y',strtotime($r->created_at))); ?></td>
                <?php if($is_admin): ?><td class="sp-muted" style="font-size:.8rem;"><?php echo (int)($r->view_count??0) ?: '&mdash;'; ?></td><?php endif; ?>
                <td style="white-space:nowrap;">
                    <?php if($is_admin): ?>
                        <a href="<?php echo esc_url(home_url('/sp-app/?view=kb-resources&action=edit&id='.(int)$r->id)); ?>"
                           class="sp-btn sp-btn-ghost sp-btn-sm">Edit</a>
                        <a href="<?php echo esc_url(home_url('/sp-app/?sp_delete=kb_resource&id='.(int)$r->id.'&_wpnonce='.wp_create_nonce('sp_delete_kb_resource_'.(int)$r->id))); ?>"
                           class="sp-btn sp-btn-ghost sp-btn-sm" data-sp-confirm="Delete this resource?" style="color:var(--sp-danger,#dc2626);">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
<script>
document.querySelectorAll('.sp-res-link').forEach(function(a){
    a.addEventListener('click', function(){
        var id = a.getAttribute('data-res-id');
        if(!id) return;
        var fd = new FormData();
        fd.append('action','sp_kb_view'); fd.append('kind','resource'); fd.append('id',id);
        fd.append('_ajax_nonce','<?php echo wp_create_nonce("sp_kb_view"); ?>');
        fetch('<?php echo esc_url(admin_url("admin-ajax.php")); ?>', {method:'POST',credentials:'same-origin',body:fd}).catch(function(){});
    });
});
</script>