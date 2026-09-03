<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$addons    = function_exists( 'sp_get_addons' ) ? sp_get_addons() : array();
$is_super  = function_exists( 'sp_is_super_admin' )  && sp_is_super_admin();
$ajax_url  = admin_url( 'admin-ajax.php' );
$nonce     = wp_create_nonce( 'sp_addon_toggle' );
$inactive  = ( $is_super && function_exists( 'sp_get_inactive_addon_plugins' ) ) ? sp_get_inactive_addon_plugins() : array();
?>
<div class="sp-page-header">
    <h1>Add-ons</h1>
</div>

<div class="sp-addon-grid">

<?php if ( empty( $addons ) ) : ?>
    <div class="sp-card sp-form-card">
        <p class="sp-empty">No add-ons installed yet.</p>
    </div>
<?php else : ?>
    <?php foreach ( $addons as $id => $addon ) : ?>
    <div class="sp-addon-card" id="sp-addon-card-<?php echo esc_attr( $id ); ?>">
        <div class="sp-addon-card-header">
            <div class="sp-addon-icon">
                <?php
                $icon     = isset( $addon['icon'] ) ? $addon['icon'] : '';
                $fallback = '<path d="M12 2l8.66 5v10L12 22l-8.66-5V7z"/><path d="M12 8v8M8 12h8"/>';
                ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                    <?php echo $icon ? $icon : $fallback; ?>
                </svg>
            </div>
            <div>
                <div class="sp-addon-name"><?php echo esc_html( $addon['name'] ); ?></div>
                <div class="sp-addon-version">v<?php echo esc_html( $addon['version'] ); ?></div>
            </div>
            <span class="sp-badge sp-badge-active" style="margin-left:auto">Active</span>
        </div>
        <p class="sp-addon-desc"><?php echo esc_html( $addon['description'] ); ?></p>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <?php if ( $addon['settings_url'] ) : ?>
                <a href="<?php echo esc_url( $addon['settings_url'] ); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">Settings</a>
            <?php endif; ?>
            <?php if ( $is_super && ! empty( $addon['plugin_file'] ) ) : ?>
                <button type="button"
                    class="sp-btn sp-btn-ghost sp-btn-sm sp-addon-toggle-btn"
                    style="color:#ef4444;border-color:#fca5a5"
                    data-addon-id="<?php echo esc_attr( $id ); ?>"
                    data-plugin="<?php echo esc_attr( $addon['plugin_file'] ); ?>"
                    data-toggle="deactivate"
                    data-nonce="<?php echo esc_attr( $nonce ); ?>"
                    data-ajax="<?php echo esc_url( $ajax_url ); ?>">
                    Deactivate
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php foreach ( $inactive as $file => $data ) : ?>
    <div class="sp-addon-card" id="sp-addon-card-inactive-<?php echo esc_attr( sanitize_key( $file ) ); ?>" style="opacity:.75">
        <div class="sp-addon-card-header">
            <div class="sp-addon-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                    <path d="M12 2l8.66 5v10L12 22l-8.66-5V7z"/><path d="M12 8v8M8 12h8"/>
                </svg>
            </div>
            <div>
                <div class="sp-addon-name"><?php echo esc_html( $data['Name'] ); ?></div>
                <div class="sp-addon-version">v<?php echo esc_html( $data['Version'] ); ?></div>
            </div>
            <span class="sp-badge" style="margin-left:auto;background:#f1f5f9;color:#64748b">Inactive</span>
        </div>
        <p class="sp-addon-desc"><?php echo esc_html( $data['Description'] ); ?></p>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <button type="button"
                class="sp-btn sp-btn-primary sp-btn-sm sp-addon-toggle-btn"
                data-addon-id="<?php echo esc_attr( sanitize_key( $file ) ); ?>"
                data-plugin="<?php echo esc_attr( $file ); ?>"
                data-toggle="activate"
                data-nonce="<?php echo esc_attr( $nonce ); ?>"
                data-ajax="<?php echo esc_url( $ajax_url ); ?>">
                Activate
            </button>
        </div>
    </div>
<?php endforeach; ?>

</div>

<?php if ( $is_super ) : ?>
<script>
document.querySelectorAll('.sp-addon-toggle-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        var self       = btn;
        var isActivate = btn.dataset.toggle === 'activate';
        var label      = isActivate ? 'Activate' : 'Deactivate';
        var doIt = function(){
            self.disabled    = true;
            self.textContent = label + '…';
            var fd = new FormData();
            fd.append('action',      'sp_addon_toggle');
            fd.append('nonce',       btn.dataset.nonce);
            fd.append('plugin_file', btn.dataset.plugin);
            fd.append('toggle',      btn.dataset.toggle);
            fetch(btn.dataset.ajax, { method:'POST', body:fd })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    if ( data.success ) {
                        window.location.reload();
                    } else {
                        btn.disabled    = false;
                        btn.textContent = label;
                        alert('Error: ' + (data.data || 'Unknown error'));
                    }
                })
                .catch(function(){
                    btn.disabled    = false;
                    btn.textContent = label;
                    alert('Request failed.');
                });
        };
        if (window.spConfirm) {
            var msg = isActivate ? 'Activate this add-on? The platform will reload.' : 'Deactivate this add-on? The platform will reload.';
            window.spConfirm(msg, {title:label + ' Add-on', confirm:label, icon: isActivate ? '✅' : '⚠️', btnClass: isActivate ? 'sp-btn-primary' : 'sp-btn-danger'}).then(function(ok){ if(ok) doIt(); });
        } else { doIt(); }
    });
});
</script>
<?php endif; ?>
