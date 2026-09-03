<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! sp_is_admin_member() && ! sp_is_super_admin() ) { echo '<p class="sp-empty">Access denied.</p>'; return; }
$is_super = sp_is_super_admin();

$platform_name      = get_option( 'sp_platform_name',  'Start Performance' );
$sp_logo_url        = get_option( 'sp_logo_url',       '' );
$sp_favicon_url     = get_option( 'sp_favicon_url',    '' );
$sp_accent_color    = get_option( 'sp_accent_color',   '#CC1F1F' );
$sp_brand_init      = get_option( 'sp_brand_initials', '' );
$sp_icon_url        = get_option( 'sp_brand_icon_url', '' );
$sp_sidebar_bg      = get_option( 'sp_sidebar_bg',     '#0f1729' );
$sp_nav_color       = get_option( 'sp_nav_color',      '#8b9ab4' );
$sp_nav_hover_color = get_option( 'sp_nav_hover_color','#ffffff' );
$sp_nav_hover_bg    = get_option( 'sp_nav_hover_bg',   'rgba(255,255,255,.07)' );
$hidden_nav      = get_option( 'sp_hidden_nav_items', array() );
if ( ! is_array( $hidden_nav ) ) $hidden_nav = array();

// Collect all nav items with full section_id data for grouped visibility UI
$all_nav = apply_filters( 'sp_nav_items', array(
    array( 'view' => 'dashboard', 'label' => 'Dashboard' ),
    array( 'section' => true, 'section_id' => 'core-system',      'label' => 'Core System'       ),
    array( 'view' => 'contacts',  'label' => 'Contacts'  ),
    array( 'view' => 'companies', 'label' => 'Companies' ),
    array( 'view' => 'leads',     'label' => 'Leads'     ),
    array( 'view' => 'tasks',     'label' => 'Tasks'     ),
    array( 'section' => true, 'section_id' => 'intelligence-core', 'label' => 'Intelligence Core' ),
    array( 'section' => true, 'section_id' => 'sales-core',        'label' => 'Sales Core'        ),
    array( 'section' => true, 'section_id' => 'service-core',      'label' => 'Service Core'      ),
    array( 'section' => true, 'section_id' => 'operations-core',   'label' => 'Operations Core'   ),
    array( 'section' => true, 'section_id' => 'knowledge-core',    'label' => 'Knowledge Core'    ),
    array( 'section' => true, 'section_id' => 'chat-core',         'label' => 'Chat Core'         ),
) );

// Collect sections defined by sp_settings_sections
ob_start();
do_action( 'sp_settings_sections' );
$addon_sections_html = ob_get_clean();

// Build anchor list for the tab nav
// We always have: platform, navigation, team. Plus any from do_action that add IDs.
$anchor_tabs = array();
if ( $is_super ) {
    $anchor_tabs[] = array( 'id' => 'section-platform',   'label' => 'Platform'   );
    $anchor_tabs[] = array( 'id' => 'section-labels',     'label' => 'Sections'   );
    $anchor_tabs[] = array( 'id' => 'section-modules',    'label' => 'Modules'    );
}
$anchor_tabs[] = array( 'id' => 'section-navigation', 'label' => 'Navigation' );
$anchor_tabs[] = array( 'id' => 'section-email',      'label' => 'Email'      );
$anchor_tabs[] = array( 'id' => 'section-team',       'label' => 'Team'       );
// Let addons append anchor tabs via filter
$anchor_tabs = apply_filters( 'sp_settings_anchor_tabs', $anchor_tabs );
?>

<div class="sp-page-header">
    <h1>Settings</h1>
</div>

<!-- Anchor tab nav -->
<div class="sp-settings-tabs">
    <?php foreach ( $anchor_tabs as $tab ) : ?>
        <a href="#<?php echo esc_attr( $tab['id'] ); ?>" class="sp-settings-tab"><?php echo esc_html( $tab['label'] ); ?></a>
    <?php endforeach; ?>
</div>

<form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
    <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
    <input type="hidden" name="sp_type" value="settings">
    <input type="hidden" name="sp_id" value="0">

    <?php if ( $is_super ) : ?>
    <!-- Platform -->
    <div id="section-platform" class="sp-card sp-form-card sp-settings-section">
        <h2 class="sp-section-heading">Platform</h2>
        <div class="sp-field" style="max-width:320px">
            <label>Platform Name</label>
            <input type="text" name="sp_platform_name" value="<?php echo esc_attr( $platform_name ); ?>">
            <span class="sp-hint">Displayed in the browser title and login page.</span>
        </div>
        <div class="sp-field" style="max-width:480px;margin-top:16px">
            <label>Brand Icon Image <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:11px;color:#94a3b8">(sidebar &amp; login)</span></label>
            <input type="url" name="sp_brand_icon_url" value="<?php echo esc_attr( $sp_icon_url ); ?>" placeholder="https://… (upload to Media Library, paste URL)">
            <span class="sp-hint">Upload your icon to the WordPress Media Library, then paste the URL here. Square image recommended. Overrides letters and the default logo mark.</span>
            <?php if ( $sp_icon_url ) : ?>
                <div style="margin-top:8px"><img src="<?php echo esc_url( $sp_icon_url ); ?>" style="width:40px;height:40px;border-radius:8px;object-fit:cover;border:1px solid #e2e8f0" alt="Icon preview"></div>
            <?php endif; ?>
        </div>
        <div class="sp-field" style="max-width:480px;margin-top:16px">
            <label>Favicon URL <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:11px;color:#94a3b8">(browser tab icon)</span></label>
            <input type="url" name="sp_favicon_url" value="<?php echo esc_attr( $sp_favicon_url ); ?>" placeholder="https://… (.png or .ico, square, 32×32 or 64×64)">
            <span class="sp-hint">Shown in the browser tab for all platform pages. Upload to Media Library and paste the URL. Square PNG recommended.</span>
            <?php if ( $sp_favicon_url ) : ?>
                <div style="margin-top:8px;display:flex;align-items:center;gap:10px;">
                    <img src="<?php echo esc_url( $sp_favicon_url ); ?>" style="width:32px;height:32px;object-fit:contain;border:1px solid #e2e8f0;border-radius:4px;background:#f8fafc;padding:2px" alt="Favicon preview">
                    <span style="font-size:12px;color:#94a3b8;">Preview (32×32)</span>
                </div>
            <?php endif; ?>
        </div>
        <div class="sp-field" style="max-width:320px;margin-top:16px">
            <label>Brand Icon Letters <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:11px;color:#94a3b8">(if no image)</span></label>
            <input type="text" name="sp_brand_initials" value="<?php echo esc_attr( $sp_brand_init ); ?>" maxlength="3" placeholder="e.g. SP" style="max-width:80px">
            <span class="sp-hint">Used only when no icon image is set. Leave blank for the default logo mark.</span>
        </div>
        <div class="sp-field" style="max-width:320px;margin-top:16px">
            <label>Accent Color</label>
            <div style="display:flex;align-items:center;gap:10px">
                <input type="color" name="sp_accent_color" value="<?php echo esc_attr( $sp_accent_color ); ?>" style="width:48px;height:38px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;padding:2px">
                <input type="text" id="sp_accent_hex" value="<?php echo esc_attr( $sp_accent_color ); ?>" maxlength="7" style="width:90px;font-family:monospace" oninput="document.querySelector('[name=sp_accent_color]').value=this.value">
            </div>
            <span class="sp-hint">Used for buttons, active nav, and the brand icon background.</span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px 24px;max-width:600px;margin-top:20px">
            <div class="sp-field">
                <label>Sidebar Background</label>
                <div style="display:flex;align-items:center;gap:10px">
                    <input type="color" name="sp_sidebar_bg" id="sp_sidebar_bg_pick" value="<?php echo esc_attr( $sp_sidebar_bg ); ?>" style="width:48px;height:38px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;padding:2px">
                    <input type="text" value="<?php echo esc_attr( $sp_sidebar_bg ); ?>" maxlength="7" style="width:90px;font-family:monospace" oninput="document.getElementById('sp_sidebar_bg_pick').value=this.value" onchange="document.querySelector('[name=sp_sidebar_bg]').value=this.value">
                </div>
                <span class="sp-hint">Left panel background color.</span>
            </div>
            <div class="sp-field">
                <label>Nav Item Color</label>
                <div style="display:flex;align-items:center;gap:10px">
                    <input type="color" name="sp_nav_color" id="sp_nav_color_pick" value="<?php echo esc_attr( $sp_nav_color ); ?>" style="width:48px;height:38px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;padding:2px">
                    <input type="text" value="<?php echo esc_attr( $sp_nav_color ); ?>" maxlength="7" style="width:90px;font-family:monospace" oninput="document.getElementById('sp_nav_color_pick').value=this.value" onchange="document.querySelector('[name=sp_nav_color]').value=this.value">
                </div>
                <span class="sp-hint">Default nav link text color.</span>
            </div>
            <div class="sp-field">
                <label>Nav Hover Text Color</label>
                <div style="display:flex;align-items:center;gap:10px">
                    <input type="color" name="sp_nav_hover_color" id="sp_nav_hc_pick" value="<?php echo esc_attr( $sp_nav_hover_color ); ?>" style="width:48px;height:38px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;padding:2px">
                    <input type="text" value="<?php echo esc_attr( $sp_nav_hover_color ); ?>" maxlength="7" style="width:90px;font-family:monospace" oninput="document.getElementById('sp_nav_hc_pick').value=this.value" onchange="document.querySelector('[name=sp_nav_hover_color]').value=this.value">
                </div>
                <span class="sp-hint">Nav link text color on hover.</span>
            </div>
            <div class="sp-field">
                <label>Nav Hover Background</label>
                <div style="display:flex;align-items:center;gap:10px">
                    <input type="color" name="sp_nav_hover_bg" id="sp_nav_hbg_pick" value="#ffffff" style="width:48px;height:38px;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;padding:2px">
                    <input type="text" name="sp_nav_hover_bg_text" value="<?php echo esc_attr( $sp_nav_hover_bg ); ?>" maxlength="30" style="width:160px;font-family:monospace" placeholder="rgba(255,255,255,.07)">
                </div>
                <span class="sp-hint">Background on hover. Accepts hex or rgba.</span>
            </div>
        </div>

        <div class="sp-field" style="max-width:480px;margin-top:16px">
            <label>Logo URL <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:11px;color:#94a3b8">(used on printed quotes)</span></label>
            <input type="url" name="sp_logo_url" value="<?php echo esc_attr( $sp_logo_url ); ?>" placeholder="https://…">
            <span class="sp-hint">Paste the full URL of your logo image. PNG or SVG recommended.</span>
            <?php if ( $sp_logo_url ) : ?>
                <div style="margin-top:8px"><img src="<?php echo esc_url( $sp_logo_url ); ?>" style="max-height:48px;max-width:200px;border:1px solid #e2e8f0;border-radius:6px;padding:4px;background:#fff" alt="Logo preview"></div>
            <?php endif; ?>
        </div>
        <div class="sp-form-actions">
            <button type="submit" class="sp-btn sp-btn-primary">Save All Settings</button>
        </div>
    </div>

    <script>
    (function(){
        var picker=document.querySelector('[name=sp_accent_color]');
        var hex=document.getElementById('sp_accent_hex');
        if(picker&&hex){
            picker.addEventListener('input',function(){hex.value=this.value;});
            hex.addEventListener('input',function(){if(/^#[0-9a-fA-F]{6}$/.test(this.value))picker.value=this.value;});
        }
    })();
    </script>

    <!-- Navigation Section Labels -->
    <?php
    $sp_core_sections = array(
        'core-system'       => 'Core System',
        'chat-core'         => 'Chat Core',
        'knowledge-core'    => 'Knowledge Core',
        'sales-core'        => 'Sales Core',
        'service-core'      => 'Service Core',
        'operations-core'   => 'Operations Core',
        'intelligence-core' => 'Intelligence Core',
    );
    ?>
    <div id="section-labels" class="sp-card sp-form-card sp-settings-section" style="margin-top:16px">
        <h2 class="sp-section-heading">Navigation Section Labels</h2>
        <p style="font-size:13px;color:#64748b;margin-bottom:20px">Rename any section to match your brand. Leave blank to use the default name.</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px 24px;max-width:640px">
            <?php foreach ( $sp_core_sections as $id => $default ) : ?>
            <div class="sp-field">
                <label><?php echo esc_html( $default ); ?></label>
                <input type="text" name="sp_section_label[<?php echo esc_attr( $id ); ?>]"
                       value="<?php echo esc_attr( get_option( 'sp_section_label_' . $id, '' ) ); ?>"
                       placeholder="<?php echo esc_attr( $default ); ?>">
            </div>
            <?php endforeach; ?>
        </div>
        <div class="sp-form-actions">
            <button type="submit" class="sp-btn sp-btn-primary">Save All Settings</button>
        </div>
    </div>
    <?php endif; // end super admin only ?>

    <!-- Navigation Visibility -->
    <div id="section-navigation" class="sp-card sp-form-card sp-settings-section" style="margin-top:16px">
        <h2 class="sp-section-heading">Navigation Visibility</h2>
        <p style="font-size:13px;color:#64748b;margin-bottom:20px">Toggle individual items on or off. When all items in a core section are hidden, clicking that section in the nav will show the upsell screen instead.</p>

        <?php
        // Group nav items by section
        $nav_groups  = array(); // array of [ 'section' => [...], 'items' => [...] ]
        $loose_items = array(); // items before any section header
        $cur_group   = null;

        foreach ( $all_nav as $item ) {
            if ( ! empty( $item['section'] ) ) {
                if ( $cur_group !== null ) $nav_groups[] = $cur_group;
                $cur_group = array( 'section' => $item, 'items' => array() );
            } elseif ( ! empty( $item['view'] ) ) {
                if ( $cur_group !== null ) {
                    $cur_group['items'][] = $item;
                } else {
                    $loose_items[] = $item;
                }
            }
        }
        if ( $cur_group !== null ) $nav_groups[] = $cur_group;

        // Core section ids
        $core_section_ids = array( 'intelligence-core','sales-core','service-core','operations-core','knowledge-core','chat-core' );

        // Loose items first (Dashboard, AI, etc.)
        if ( ! empty( $loose_items ) ) : ?>
        <div class="sp-nav-visibility-grid" style="margin-bottom:20px;">
            <?php foreach ( $loose_items as $item ) :
                $v = $item['view']; $checked = ! in_array( $v, $hidden_nav ); ?>
            <label class="sp-nav-toggle<?php echo $checked ? '' : ' sp-nav-toggle--off'; ?>">
                <input type="checkbox" name="sp_nav_visible[]" value="<?php echo esc_attr($v); ?>"<?php checked($checked); ?>
                    onchange="spNavToggle(this)">
                <span class="sp-nav-toggle-label"><span class="sp-nav-toggle-name"><?php echo esc_html($item['label']); ?></span></span>
                <span class="sp-nav-toggle-pill"><?php echo $checked ? 'Visible' : 'Hidden'; ?></span>
            </label>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php foreach ( $nav_groups as $group ) :
            $sec      = $group['section'];
            $sec_id   = $sec['section_id'] ?? '';
            $is_core  = in_array( $sec_id, $core_section_ids );
            $items    = $group['items'];

            // Core sections with no active plugin: show as upsell-only (not hidden)
            if ( empty( $items ) ) {
                if ( ! $is_core ) continue;

                // Map section IDs to their WordPress plugin files
                static $core_plugin_map = array(
                    'operations-core'   => 'start-performance-operations/start-performance-operations.php',
                    'knowledge-core'    => 'start-performance-knowledge/start-performance-knowledge.php',
                    'chat-core'         => 'start-performance-chat/start-performance-chat.php',
                    'sales-core'        => 'start-performance-sales/start-performance-sales.php',
                    'service-core'      => 'start-performance-tickets/start-performance-tickets.php',
                    'intelligence-core' => 'start-performance-intelligence/start-performance-intelligence.php',
                );

                $plugin_file  = $core_plugin_map[ $sec_id ] ?? '';
                $all_plugins  = ( $is_super && $plugin_file ) ? get_plugins() : array();
                $is_installed = $plugin_file && isset( $all_plugins[ $plugin_file ] );
                $act_nonce    = $is_super ? wp_create_nonce( 'sp_addon_toggle' ) : '';
                ?>
                <div style="margin-bottom:18px;border:1px solid var(--sp-border,#e5e7eb);border-radius:10px;overflow:hidden;opacity:.7;">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:11px 16px;background:var(--sp-bg,#f9fafb);border-bottom:1px solid var(--sp-border,#e5e7eb);">
                        <span style="font-size:.85rem;font-weight:700;"><?php echo esc_html($sec['label']); ?></span>
                        <span style="font-size:.72rem;font-weight:600;color:#f59e0b;">Plugin not active — shows upsell</span>
                    </div>
                    <div style="padding:10px 16px;font-size:.8rem;color:var(--sp-muted);display:flex;align-items:center;gap:12px;">
                        <?php if ( $is_super && $is_installed ) : ?>
                            <span>Plugin is installed but not active.</span>
                            <button type="button"
                                class="sp-btn sp-btn-primary sp-btn-sm sp-core-activate-btn"
                                data-plugin="<?php echo esc_attr( $plugin_file ); ?>"
                                data-nonce="<?php echo esc_attr( $act_nonce ); ?>"
                                data-ajax="<?php echo esc_url( admin_url('admin-ajax.php') ); ?>">
                                Activate
                            </button>
                        <?php else : ?>
                            Install the <?php echo esc_html($sec['label']); ?> plugin to enable nav items here.
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                continue;
            }

            $all_visible  = true;
            $all_hidden   = true;
            foreach ( $items as $it ) {
                if ( in_array( $it['view'], $hidden_nav ) ) $all_visible = false;
                else $all_hidden = false;
            }
            $section_status = $all_hidden ? 'All hidden — shows upsell' : ( $all_visible ? 'All visible' : 'Partial' );
            $status_color   = $all_hidden ? '#f59e0b' : ( $all_visible ? '#16a34a' : '#3b82f6' );
        ?>
        <div style="margin-bottom:18px;border:1px solid var(--sp-border,#e5e7eb);border-radius:10px;overflow:hidden;" data-sec-id="<?php echo esc_attr($sec_id); ?>">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:11px 16px;background:var(--sp-bg,#f9fafb);border-bottom:1px solid var(--sp-border,#e5e7eb);">
                <span style="font-size:.85rem;font-weight:700;"><?php echo esc_html($sec['label']); ?></span>
                <span style="font-size:.72rem;font-weight:600;color:<?php echo $status_color; ?>;" id="sp-sec-status-<?php echo esc_attr($sec_id); ?>"><?php echo $section_status; ?></span>
            </div>
            <div style="padding:12px 16px;display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;">
                <?php foreach ( $items as $item ) :
                    if ( empty( $item['view'] ) ) continue;
                    $v = $item['view']; $checked = ! in_array( $v, $hidden_nav ); ?>
                <label class="sp-nav-toggle<?php echo $checked ? '' : ' sp-nav-toggle--off'; ?>" style="margin:0;">
                    <input type="checkbox" name="sp_nav_visible[]" value="<?php echo esc_attr($v); ?>"<?php checked($checked); ?>
                        onchange="spNavToggle(this,'<?php echo esc_js($sec_id); ?>')">
                    <span class="sp-nav-toggle-label">
                        <span class="sp-nav-toggle-name"><?php echo esc_html($item['label']); ?></span>
                        <?php if ( ! empty( $item['custom'] ) ) : ?><span style="font-size:.65rem;font-weight:700;letter-spacing:.04em;background:#ede9fe;color:#7c3aed;border-radius:4px;padding:1px 5px;margin-left:5px;vertical-align:middle;">Custom</span><?php endif; ?>
                    </span>
                    <span class="sp-nav-toggle-pill"><?php echo $checked ? 'Visible' : 'Hidden'; ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <script>
        var spAjaxUrl  = '<?php echo esc_js( admin_url('admin-ajax.php') ); ?>';
        var spNavNonce = '<?php echo wp_create_nonce('sp_nav_visibility'); ?>';

        function spNavToggle(cb, secId) {
            var t = cb.closest('.sp-nav-toggle');
            t.classList.toggle('sp-nav-toggle--off', !cb.checked);
            t.querySelector('.sp-nav-toggle-pill').textContent = cb.checked ? 'Visible' : 'Hidden';
            if (secId) {
                var container = document.querySelector('[data-sec-id="' + secId + '"]');
                if (container) {
                    var boxes = container.querySelectorAll('input[type=checkbox]');
                    var total = boxes.length, vis = 0;
                    boxes.forEach(function(b){ if(b.checked) vis++; });
                    var status = document.getElementById('sp-sec-status-' + secId);
                    if (status) {
                        if (vis === 0)          { status.textContent = 'All hidden — shows upsell'; status.style.color = '#f59e0b'; }
                        else if (vis === total) { status.textContent = 'All visible'; status.style.color = '#16a34a'; }
                        else                   { status.textContent = 'Partial'; status.style.color = '#3b82f6'; }
                    }
                }
            }
            // Save immediately via admin-ajax (bypasses page cache)
            var allBoxes = document.querySelectorAll('input[name="sp_nav_visible[]"]');
            var visible = [];
            allBoxes.forEach(function(b){ if(b.checked) visible.push(b.value); });
            var fd = new FormData();
            fd.append('action', 'sp_save_nav_visibility');
            fd.append('nonce', spNavNonce);
            visible.forEach(function(v){ fd.append('visible[]', v); });
            fetch(spAjaxUrl, { method: 'POST', body: fd });
        }
        </script>

        <div class="sp-form-actions">
            <button type="submit" class="sp-btn sp-btn-primary">Save All Settings</button>
        </div>
    </div>

<script>
document.querySelectorAll('.sp-core-activate-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        btn.disabled = true;
        btn.textContent = 'Activating…';
        var fd = new FormData();
        fd.append('action',      'sp_addon_toggle');
        fd.append('nonce',       btn.dataset.nonce);
        fd.append('plugin_file', btn.dataset.plugin);
        fd.append('toggle',      'activate');
        fetch(btn.dataset.ajax, { method:'POST', body:fd })
            .then(function(r){ return r.json(); })
            .then(function(data){
                if ( data.success ) {
                    window.location.reload();
                } else {
                    btn.disabled = false;
                    btn.textContent = 'Activate';
                    alert('Error: ' + (data.data || 'Unknown error'));
                }
            })
            .catch(function(){
                btn.disabled = false;
                btn.textContent = 'Activate';
                alert('Request failed.');
            });
    });
});
</script>

</form>

<?php echo $addon_sections_html; ?>

<!-- Email -->
<div id="section-email" class="sp-card sp-form-card sp-settings-section" style="margin-top:16px">
    <h2 class="sp-section-heading">Email</h2>
    <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
        <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
        <input type="hidden" name="sp_type" value="settings">
        <input type="hidden" name="sp_id"   value="0">
        <input type="hidden" name="sp_settings_section" value="email">
        <div class="sp-field" style="max-width:400px">
            <label>Client Onboarding Contact</label>
            <input type="email" name="sp_wts_onboarding_email" value="<?php echo esc_attr( get_option( 'sp_wts_onboarding_email', '' ) ); ?>" placeholder="e.g. onboarding@yourcompany.com">
            <span class="sp-hint">This address receives an email whenever a client submits the WTS Client Setup questionnaire.</span>
        </div>
        <div class="sp-field" style="margin-top:18px;padding-top:18px;border-top:1px solid #e2e8f0">
            <label>Welcome Email — Personal Note</label>
            <textarea name="sp_welcome_note" rows="4" style="max-width:520px;resize:vertical;" placeholder="e.g. Welcome to the team! We're excited to have you on board. Reach out anytime if you need help getting started."><?php echo esc_textarea( get_option( 'sp_welcome_note', '' ) ); ?></textarea>
            <span class="sp-hint">This note appears at the top of the welcome email sent to new team members. Leave blank to send without a personal note.</span>
        </div>
        <div class="sp-field" style="margin-top:18px;padding-top:18px;border-top:1px solid #e2e8f0">
            <label style="display:flex;align-items:center;gap:9px;font-weight:600;text-transform:none;letter-spacing:0;color:#0f172a;cursor:pointer;">
                <input type="checkbox" name="sp_daily_digest_enabled" value="1" <?php checked( (int) get_option( 'sp_daily_digest_enabled', 1 ), 1 ); ?> style="width:16px;height:16px;cursor:pointer;">
                Daily reminder digest email
            </label>
            <span class="sp-hint">Emails each active team member a morning summary (8:00 AM site time) of their overdue tasks, tasks due today, and note reminders. Members with nothing pending get no email. Uncheck to stop these emails entirely for this site.</span>
        </div>
        <div class="sp-form-actions" style="margin-top:16px">
            <button type="submit" class="sp-btn sp-btn-primary">Save</button>
        </div>
    </form>
</div>

<?php if ( $is_super ): ?>
<?php
$all_addons = sp_get_addons();
?>
<!-- Modules -->
<div id="section-modules" class="sp-card sp-form-card sp-settings-section" style="margin-top:16px">
    <h2 class="sp-section-heading">Modules</h2>
    <p style="font-size:13px;color:#64748b;margin-bottom:20px;">Enable or disable installed modules for this site. Disabled modules are hidden from the navigation and dashboard — the plugin stays installed but invisible to users.</p>
    <?php if ( isset($_GET['saved']) && $_GET['saved'] === 'modules' ): ?>
        <div class="sp-notice sp-notice-success" style="margin-bottom:16px;">Module visibility saved.</div>
    <?php endif; ?>
    <?php if ( empty( $all_addons ) ): ?>
        <p style="color:#64748b;font-size:.875rem;">No addon modules are currently installed.</p>
    <?php else: ?>
    <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>">
        <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
        <input type="hidden" name="sp_type" value="settings">
        <input type="hidden" name="sp_id"   value="0">
        <input type="hidden" name="sp_settings_section" value="modules">
        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:24px;">
        <?php foreach ( $all_addons as $addon_id => $addon ): ?>
        <?php $enabled = sp_is_module_enabled( $addon_id ); ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border:1px solid var(--sp-border,#e5e7eb);border-radius:8px;background:<?php echo $enabled ? 'var(--sp-bg,#f9fafb)' : 'transparent'; ?>;">
            <div style="display:flex;align-items:center;gap:12px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color:var(--sp-primary,#2563eb);flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo esc_attr( $addon['icon'] ?? 'M4 6h16M4 12h16M4 18h16' ); ?>"/>
                </svg>
                <div>
                    <div style="font-weight:600;font-size:.9rem;"><?php echo esc_html( $addon['name'] ); ?></div>
                    <?php if ( ! empty( $addon['description'] ) ): ?>
                    <div style="font-size:.78rem;color:var(--sp-muted);"><?php echo esc_html( $addon['description'] ); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <label class="sp-toggle" style="flex-shrink:0;margin-left:16px;">
                <input type="checkbox" name="sp_module_enabled[<?php echo esc_attr($addon_id); ?>]" value="1" <?php checked( $enabled ); ?>>
                <span class="sp-toggle-slider"></span>
            </label>
        </div>
        <?php endforeach; ?>
        </div>
        <div class="sp-form-actions">
            <button type="submit" class="sp-btn sp-btn-primary">Save Modules</button>
        </div>
    </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Team -->
<div id="section-team" class="sp-card sp-form-card sp-settings-section" style="margin-top:16px">
    <h2 class="sp-section-heading">Team &amp; Security</h2>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px">Manage team members, roles, and PINs from the Team section.</p>
    <a href="<?php echo esc_url( home_url( '/sp-app/?view=team' ) ); ?>" class="sp-btn sp-btn-ghost">Go to Team &rarr;</a>
</div>
