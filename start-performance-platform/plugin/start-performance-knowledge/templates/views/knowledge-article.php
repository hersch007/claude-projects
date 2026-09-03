<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$is_admin = sp_is_admin_member();
$action   = sanitize_key( $_GET['action'] ?? '' );
$id       = (int)( $_GET['id'] ?? 0 );

// ── Edit / New ────────────────────────────────────────────────────────────────
if ( $is_admin && ( $action === 'new' || $action === 'edit' || ( $id && $action === '' && isset($_GET['action']) === false && $is_admin && isset($_GET['edit']) ) ) ) {
    $art  = ( $id && $action === 'edit' ) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sp_kb_articles WHERE id=%d",$id)) : null;
    $cats = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sp_kb_categories WHERE type='kb' ORDER BY name ASC");
    ?>
    <div class="sp-view-header">
        <h1><?php echo $art ? 'Edit Article' : 'New Article'; ?></h1>
        <a href="<?php echo esc_url(home_url('/sp-app/?view=knowledge')); ?>" class="sp-btn sp-btn-secondary">&larr; Back</a>
    </div>
    <div class="sp-card sp-form-card">
        <form method="post" action="<?php echo esc_url(home_url('/sp-app/')); ?>" enctype="multipart/form-data">
            <?php wp_nonce_field('sp_form','sp_nonce'); ?>
            <input type="hidden" name="sp_type" value="kb_article">
            <input type="hidden" name="sp_id"   value="<?php echo $art ? (int)$art->id : 0; ?>">
            <div class="sp-field"><label>Title <span class="sp-required">*</span></label><input type="text" name="title" value="<?php echo esc_attr($art->title??''); ?>" required></div>
            <div class="sp-form-row">
                <div class="sp-field">
                    <label>Category</label>
                    <select name="category_id">
                        <option value="0">-- Uncategorized --</option>
                        <?php foreach($cats as $c): ?>
                            <option value="<?php echo $c->id; ?>" <?php selected($art->category_id??0,$c->id); ?>><?php echo esc_html($c->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sp-field">
                    <label>Visibility</label>
                    <select name="visibility">
                        <option value="team"  <?php selected($art->visibility??'team','team'); ?>>All Team</option>
                        <option value="admin" <?php selected($art->visibility??'team','admin'); ?>>Admins Only</option>
                    </select>
                </div>
            </div>
            <div class="sp-field"><label>Tags <span class="sp-hint">(comma-separated)</span></label><input type="text" name="tags" value="<?php echo esc_attr($art->tags??''); ?>" placeholder="e.g. sales, onboarding, process"></div>

            <?php if(!$art): ?>
            <div class="sp-field" style="background:var(--sp-bg,#f9fafb);border:1px dashed var(--sp-border,#e5e7eb);border-radius:8px;padding:14px 16px;">
                <label style="margin-bottom:6px;display:block;">Import from File <span class="sp-hint">(optional &mdash; paste text content below, or upload a .txt / .html file to pre-fill the editor)</span></label>
                <input type="file" name="article_import" accept=".txt,.html,.htm" style="font-size:.85rem;">
            </div>
            <?php endif; ?>

            <div class="sp-field">
                <label>Content <span class="sp-required">*</span></label>
                <div class="sp-editor-toolbar" id="kb-toolbar">
                    <button type="button" onclick="spFmt('bold')" title="Bold"><b>B</b></button>
                    <button type="button" onclick="spFmt('italic')" title="Italic"><i>I</i></button>
                    <button type="button" onclick="spFmt('underline')" title="Underline"><u>U</u></button>
                    <span class="sp-toolbar-sep"></span>
                    <button type="button" onclick="spBlock('h2')" title="Heading 2">H2</button>
                    <button type="button" onclick="spBlock('h3')" title="Heading 3">H3</button>
                    <button type="button" onclick="spBlock('p')" title="Paragraph">P</button>
                    <span class="sp-toolbar-sep"></span>
                    <button type="button" onclick="spFmt('insertUnorderedList')" title="Bullet list">&#8226; List</button>
                    <button type="button" onclick="spFmt('insertOrderedList')" title="Numbered list">1. List</button>
                    <span class="sp-toolbar-sep"></span>
                    <button type="button" onclick="spInsertLink()" title="Link">Link</button>
                    <button type="button" onclick="spFmt('removeFormat')" title="Clear formatting">Clear</button>
                </div>
                <div id="kb-editor" contenteditable="true" class="sp-rich-editor"><?php echo $art ? wp_kses_post($art->content) : ''; ?></div>
                <textarea name="content" id="kb-content-hidden" style="display:none;" required><?php echo esc_textarea($art->content??''); ?></textarea>
            </div>

            <div class="sp-form-actions">
                <?php if($art): ?>
                    <a href="<?php echo esc_url(home_url('/sp-app/?sp_delete=kb_article&id='.(int)$art->id.'&_wpnonce='.wp_create_nonce('sp_delete_kb_article_'.(int)$art->id))); ?>"
                       class="sp-btn sp-btn-danger" data-sp-confirm="Delete this article?">Delete</a>
                <?php endif; ?>
                <button type="submit" class="sp-btn sp-btn-primary">Save Article</button>
            </div>
        </form>
    </div>

<style>
.sp-editor-toolbar{display:flex;flex-wrap:wrap;gap:2px;padding:6px 8px;background:var(--sp-bg,#f9fafb);border:1px solid var(--sp-border,#e5e7eb);border-bottom:none;border-radius:8px 8px 0 0;}
.sp-editor-toolbar button{background:none;border:1px solid transparent;border-radius:4px;padding:3px 9px;font-size:.82rem;cursor:pointer;color:var(--sp-text,#1e293b);}
.sp-editor-toolbar button:hover{background:#fff;border-color:var(--sp-border,#e5e7eb);}
.sp-toolbar-sep{width:1px;background:var(--sp-border,#e5e7eb);margin:2px 4px;}
.sp-rich-editor{min-height:320px;padding:16px;border:1px solid var(--sp-border,#e5e7eb);border-radius:0 0 8px 8px;background:#fff;font-size:.9rem;line-height:1.7;outline:none;overflow-y:auto;}
.sp-rich-editor:focus{border-color:var(--sp-primary,#2563eb);}
.sp-rich-editor h2{font-size:1.2rem;font-weight:700;margin:.8em 0 .3em;}
.sp-rich-editor h3{font-size:1rem;font-weight:700;margin:.8em 0 .3em;}
.sp-rich-editor ul,.sp-rich-editor ol{padding-left:1.4em;margin:.5em 0;}
.sp-rich-editor a{color:var(--sp-primary,#2563eb);}
</style>
<script>
(function(){
    var ed = document.getElementById('kb-editor');
    var hidden = document.getElementById('kb-content-hidden');
    if(!ed) return;

    function spFmt(cmd){ document.execCommand(cmd,false,null); ed.focus(); }
    function spBlock(tag){
        ed.focus();
        document.execCommand('formatBlock',false,tag);
    }
    function spInsertLink(){
        var url = prompt('URL:');
        if(url){ document.execCommand('createLink',false,url); }
        ed.focus();
    }
    window.spFmt = spFmt;
    window.spBlock = spBlock;
    window.spInsertLink = spInsertLink;

    // sync to hidden textarea on submit
    ed.closest('form').addEventListener('submit', function(){
        hidden.value = ed.innerHTML;
        hidden.removeAttribute('required');
    });

    // file import — read .txt or .html and paste into editor
    var imp = document.querySelector('input[name="article_import"]');
    if(imp){
        imp.addEventListener('change', function(){
            var file = this.files[0];
            if(!file) return;
            var reader = new FileReader();
            reader.onload = function(e){
                var txt = e.target.result;
                if(file.name.match(/\.html?$/i)){
                    ed.innerHTML = txt;
                } else {
                    // plain text: wrap paragraphs
                    var html = txt.split(/\n\n+/).map(function(p){
                        return '<p>' + p.replace(/\n/g,'<br>') + '</p>';
                    }).join('');
                    ed.innerHTML = html;
                }
                // auto-fill title from filename if empty
                var titleField = document.querySelector('input[name="title"]');
                if(titleField && !titleField.value){
                    titleField.value = file.name.replace(/\.[^.]+$/,'').replace(/[-_]/g,' ');
                }
            };
            reader.readAsText(file);
        });
    }
})();
</script>
    <?php return;
}

// ── View article ──────────────────────────────────────────────────────────────
if ( ! $id ) { wp_redirect(home_url('/sp-app/?view=knowledge')); exit; }
$art = $wpdb->get_row($wpdb->prepare(
    "SELECT a.*, c.name AS cat_name, t.name AS author
     FROM {$wpdb->prefix}sp_kb_articles a
     LEFT JOIN {$wpdb->prefix}sp_kb_categories c ON c.id=a.category_id
     LEFT JOIN {$wpdb->prefix}sp_team t ON t.id=a.created_by
     WHERE a.id=%d", $id
));
if(!$art){ echo '<p class="sp-empty">Article not found.</p>'; return; }
$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}sp_kb_articles SET view_count = view_count + 1 WHERE id = %d", $id ) );
?>

<div class="sp-view-header">
    <h1><?php echo esc_html($art->title); ?></h1>
    <div style="display:flex;gap:8px;">
        <?php if($is_admin): ?>
            <a href="<?php echo esc_url(home_url('/sp-app/?view=knowledge-article&action=edit&id='.$id)); ?>" class="sp-btn sp-btn-secondary">Edit</a>
        <?php endif; ?>
        <a href="<?php echo esc_url(home_url('/sp-app/?view=knowledge')); ?>" class="sp-btn sp-btn-ghost">&larr; Knowledge Base</a>
    </div>
</div>

<?php if(isset($_GET['saved'])): ?><div class="sp-notice sp-notice-success">Article saved.</div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 200px;gap:20px;align-items:start;">
    <div class="sp-card" style="padding:28px 32px;">
        <div class="sp-kb-content" style="line-height:1.7;font-size:.92rem;">
            <?php echo wp_kses_post($art->content); ?>
        </div>
    </div>
    <div>
        <div class="sp-card" style="padding:14px 16px;font-size:.82rem;">
            <?php if($art->cat_name): ?>
                <div style="margin-bottom:10px;"><span style="color:var(--sp-muted);font-weight:600;display:block;margin-bottom:2px;">Category</span><?php echo esc_html($art->cat_name); ?></div>
            <?php endif; ?>
            <?php if($art->tags): ?>
                <div style="margin-bottom:10px;"><span style="color:var(--sp-muted);font-weight:600;display:block;margin-bottom:4px;">Tags</span>
                    <?php foreach(array_filter(array_map('trim',explode(',',$art->tags))) as $tag): ?>
                        <span style="display:inline-block;background:var(--sp-bg,#f9fafb);border:1px solid var(--sp-border,#e5e7eb);border-radius:99px;padding:1px 8px;font-size:.72rem;margin:2px 2px 2px 0;"><?php echo esc_html($tag); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div style="margin-bottom:6px;"><span style="color:var(--sp-muted);font-weight:600;display:block;margin-bottom:2px;">Author</span><?php echo esc_html($art->author??'System'); ?></div>
            <div style="margin-bottom:6px;"><span style="color:var(--sp-muted);font-weight:600;display:block;margin-bottom:2px;">Updated</span><?php echo esc_html(date('M j, Y',strtotime($art->updated_at))); ?></div>
            <?php if($is_admin): ?>
            <div><span style="color:var(--sp-muted);font-weight:600;display:block;margin-bottom:2px;">Views</span><?php echo number_format((int)($art->view_count??0)); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.sp-kb-content h1,.sp-kb-content h2,.sp-kb-content h3{font-weight:700;margin:1.2em 0 .4em;}
.sp-kb-content h2{font-size:1.15rem;}
.sp-kb-content h3{font-size:1rem;}
.sp-kb-content ul,.sp-kb-content ol{padding-left:1.4em;margin:.6em 0;}
.sp-kb-content li{margin:.3em 0;}
.sp-kb-content p{margin:.6em 0;}
.sp-kb-content a{color:var(--sp-primary,#2563eb);}
.sp-kb-content code{background:var(--sp-bg,#f9fafb);padding:2px 5px;border-radius:4px;font-size:.88em;font-family:monospace;}
.sp-kb-content pre{background:var(--sp-bg,#f9fafb);padding:12px;border-radius:8px;overflow:auto;font-size:.82rem;}
.sp-kb-content blockquote{border-left:3px solid var(--sp-primary,#2563eb);margin:0;padding:8px 16px;color:var(--sp-muted);}
.sp-kb-content hr{border:none;border-top:1px solid var(--sp-border,#e5e7eb);margin:1.5em 0;}
</style>
