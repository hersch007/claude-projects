<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$is_admin = sp_is_admin_member();
$action   = sanitize_key( $_GET['action'] ?? '' );
$cat_id   = (int)( $_GET['cat'] ?? 0 );
$search   = sanitize_text_field( $_GET['s'] ?? '' );

// ── Category form ─────────────────────────────────────────────────────────────
if ( $is_admin && $action === 'new-cat' ) {
    ?>
    <div class="sp-view-header">
        <h1>New Category</h1>
        <a href="<?php echo esc_url(home_url('/sp-app/?view=knowledge')); ?>" class="sp-btn sp-btn-secondary">&larr; Back</a>
    </div>
    <div class="sp-card sp-form-card">
        <form method="post" action="<?php echo esc_url(home_url('/sp-app/')); ?>">
            <?php wp_nonce_field('sp_form','sp_nonce'); ?>
            <input type="hidden" name="sp_type" value="kb_category">
            <input type="hidden" name="sp_id"   value="0">
            <input type="hidden" name="cat_type" value="kb">
            <div class="sp-field"><label>Category Name <span class="sp-required">*</span></label><input type="text" name="name" required></div>
            <div class="sp-field"><label>Description</label><textarea name="description" rows="2"></textarea></div>
            <div class="sp-form-actions"><button type="submit" class="sp-btn sp-btn-primary">Save Category</button></div>
        </form>
    </div>
    <?php return;
}

// ── Article list / search ─────────────────────────────────────────────────────
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sp_kb_categories WHERE type='kb' ORDER BY sort_order ASC, name ASC");

$where  = array("a.visibility IN ('team','admin')");
$args   = array();
if ( $cat_id ) { $where[] = 'a.category_id=%d'; $args[] = $cat_id; }
if ( $search ) { $where[] = "(a.title LIKE %s OR a.content LIKE %s OR a.tags LIKE %s)"; $s="%{$search}%"; $args=array_merge($args,array($s,$s,$s)); }
$where_sql = implode(' AND ',$where);
$sql = "SELECT a.*, c.name AS cat_name, t.name AS author
        FROM {$wpdb->prefix}sp_kb_articles a
        LEFT JOIN {$wpdb->prefix}sp_kb_categories c ON c.id=a.category_id
        LEFT JOIN {$wpdb->prefix}sp_team t ON t.id=a.created_by
        WHERE {$where_sql} ORDER BY a.updated_at DESC";
$articles = $args ? $wpdb->get_results($wpdb->prepare($sql,$args)) : $wpdb->get_results($sql);
?>

<div class="sp-view-header">
    <h1>Knowledge Base</h1>
    <div style="display:flex;gap:8px;">
        <?php if($is_admin): ?>
            <a href="<?php echo esc_url(home_url('/sp-app/?view=knowledge-article&action=new')); ?>" class="sp-btn sp-btn-primary">+ New Article</a>
            <a href="<?php echo esc_url(home_url('/sp-app/?view=knowledge&action=new-cat')); ?>" class="sp-btn sp-btn-secondary">+ Category</a>
        <?php endif; ?>
    </div>
</div>

<?php if(isset($_GET['saved']))  : ?><div class="sp-notice sp-notice-success">Saved.</div><?php endif; ?>
<?php if(isset($_GET['deleted'])): ?><div class="sp-notice sp-notice-success">Deleted.</div><?php endif; ?>

<div style="display:grid;grid-template-columns:200px 1fr;gap:20px;align-items:start;">

    <div>
        <div class="sp-card" style="padding:12px;">
            <form method="get" action="<?php echo esc_url(home_url('/sp-app/')); ?>" style="margin-bottom:10px;">
                <input type="hidden" name="view" value="knowledge">
                <div style="display:flex;gap:6px;align-items:stretch;">
                    <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search…" style="flex:1;font-size:.82rem;min-width:0;">
                    <button type="submit" class="sp-btn sp-btn-primary sp-btn-sm" style="flex-shrink:0;align-self:stretch;padding-top:0;padding-bottom:0;">Go</button>
                </div>
            </form>
            <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--sp-muted);margin-bottom:8px;">Categories</div>
            <a href="<?php echo esc_url(home_url('/sp-app/?view=knowledge')); ?>"
               style="display:block;padding:5px 8px;border-radius:6px;font-size:.85rem;<?php echo !$cat_id && !$search ? 'background:var(--sp-primary,#2563eb);color:#fff;' : 'color:var(--sp-text);'; ?>">All Articles</a>
            <?php foreach($categories as $cat): ?>
            <a href="<?php echo esc_url(home_url('/sp-app/?view=knowledge&cat='.$cat->id)); ?>"
               style="display:block;padding:5px 8px;border-radius:6px;font-size:.85rem;margin-top:2px;<?php echo $cat_id===$cat->id ? 'background:var(--sp-primary,#2563eb);color:#fff;' : 'color:var(--sp-text);'; ?>">
                <?php echo esc_html($cat->name); ?>
            </a>
            <?php endforeach; ?>
            <?php if(empty($categories)): ?><p style="font-size:.78rem;color:var(--sp-muted);margin-top:8px;">No categories yet.</p><?php endif; ?>
        </div>
    </div>

    <div>
        <?php if(empty($articles)): ?>
            <div class="sp-card" style="padding:20px;">
                <p class="sp-empty"><?php echo $search ? 'No articles match your search.' : 'No articles yet.'; ?>
                    <?php if($is_admin && !$search): ?><a href="<?php echo esc_url(home_url('/sp-app/?view=knowledge-article&action=new')); ?>" class="sp-link"> Write the first one →</a><?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <?php foreach($articles as $art): ?>
            <div class="sp-card" style="padding:16px 20px;margin-bottom:12px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                    <div style="flex:1;">
                        <a href="<?php echo esc_url(home_url('/sp-app/?view=knowledge-article&id='.$art->id)); ?>"
                           style="font-size:1rem;font-weight:700;color:var(--sp-text);text-decoration:none;"><?php echo esc_html($art->title); ?></a>
                        <div style="font-size:.78rem;color:var(--sp-muted);margin-top:4px;">
                            <?php if($art->cat_name): ?><span class="sp-badge" style="margin-right:6px;"><?php echo esc_html($art->cat_name); ?></span><?php endif; ?>
                            <?php if($art->tags): ?>
                                <?php foreach(array_filter(array_map('trim',explode(',',$art->tags))) as $tag): ?>
                                    <span style="display:inline-block;background:var(--sp-bg,#f9fafb);border:1px solid var(--sp-border,#e5e7eb);border-radius:99px;padding:1px 8px;font-size:.72rem;margin-right:3px;"><?php echo esc_html($tag); ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:.82rem;color:var(--sp-muted);margin-top:6px;">
                            <?php echo esc_html(substr(wp_strip_all_tags($art->content),0,160)); ?>…
                        </div>
                    </div>
                    <div style="text-align:right;white-space:nowrap;flex-shrink:0;">
                        <div style="font-size:.75rem;color:var(--sp-muted);margin-bottom:6px;"><?php echo esc_html(date('M j, Y',strtotime($art->updated_at))); ?><?php if($is_admin && ($art->view_count??0)>0): ?> &middot; <?php echo number_format((int)$art->view_count); ?> views<?php endif; ?></div>
                        <?php if($art->visibility==='admin'): ?><span class="sp-badge" style="font-size:.68rem;display:block;margin-bottom:6px;">Admin only</span><?php endif; ?>
                        <?php if($is_admin): ?>
                            <a href="<?php echo esc_url(home_url('/sp-app/?view=knowledge-article&action=edit&id='.$art->id)); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">Edit</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
