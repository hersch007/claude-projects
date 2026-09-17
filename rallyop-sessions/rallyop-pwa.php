<?php
if (!defined('ABSPATH')) { exit; }

function rop_pwa_icon_url($size) {
    $icon = get_site_icon_url($size);
    if ($icon) { return $icon; }
    $logo_id = absint(get_theme_mod('custom_logo'));
    $logo = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';
    return $logo ?: home_url('/wp-content/themes/rallyop-v14/assets/images/rallyop-logo.png');
}

add_action('template_redirect', function () {
    $path = trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    if ($path === 'rallyop-manifest.webmanifest' || isset($_GET['rop_manifest'])) {
        $group_id = max(0, absint($_GET['group'] ?? 0));
        $start = $group_id ? add_query_arg(['group' => $group_id, 'source' => 'pwa'], home_url('/')) . '#clubhouse' : add_query_arg('source', 'pwa', home_url('/')) . '#clubhouse';
        $record = $group_id ? add_query_arg('group', $group_id, home_url('/')) . '#record-game' : home_url('/#record-game');
        nocache_headers();
        header('Content-Type: application/manifest+json; charset=utf-8');
        echo wp_json_encode([
            'id' => home_url('/'),
            'name' => 'RallyOP — Open Play With Friends',
            'short_name' => 'RallyOP',
            'description' => 'Record games, follow scores, and track friendly open-play standings.',
            'start_url' => $start,
            'scope' => '/',
            'display' => 'standalone',
            'display_override' => ['window-controls-overlay', 'standalone', 'minimal-ui'],
            'orientation' => 'portrait-primary',
            'background_color' => '#f7f3e8',
            'theme_color' => '#173426',
            'categories' => ['sports', 'social', 'utilities'],
            'icons' => [
                ['src' => rop_pwa_icon_url(192), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => rop_pwa_icon_url(512), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
            'shortcuts' => [[
                'name' => 'Record a game', 'short_name' => 'Record', 'url' => $record,
                'icons' => [['src' => rop_pwa_icon_url(192), 'sizes' => '192x192']],
            ]],
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }
    if ($path === 'rallyop-sw.js' || isset($_GET['rop_sw'])) {
        nocache_headers();
        header('Content-Type: application/javascript; charset=utf-8');
        header('Service-Worker-Allowed: /');
        ?>
const CACHE='rallyop-shell-v1';
const OFFLINE=`<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#173426"><style>body{margin:0;padding:28px;background:#f7f3e8;color:#173426;font:18px system-ui}main{max-width:420px;margin:15vh auto;padding:26px;background:white;border-radius:18px;border:1px solid #ddd8cc}h1{font-size:38px;margin:0 0 10px}button{width:100%;padding:15px;background:#c9ff3d;border:0;border-radius:10px;font-weight:900;font-size:16px}</style><main><h1>RallyOP</h1><p>You're offline right now. Reconnect to load current scores or save a game.</p><button onclick="location.reload()">TRY AGAIN</button></main>`;
self.addEventListener('install',event=>event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(['/'])).then(()=>self.skipWaiting())));
self.addEventListener('activate',event=>event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(key=>key.startsWith('rallyop-')&&key!==CACHE).map(key=>caches.delete(key)))).then(()=>self.clients.claim())));
self.addEventListener('fetch',event=>{
  const request=event.request,url=new URL(request.url);
  if(request.method!=='GET'||url.origin!==location.origin||url.pathname.startsWith('/wp-admin')||url.pathname.startsWith('/wp-login'))return;
  if(request.mode==='navigate'){
    event.respondWith(fetch(request).catch(()=>new Response(OFFLINE,{headers:{'Content-Type':'text/html; charset=utf-8'}})));
    return;
  }
  if(['style','script','image','font'].includes(request.destination)){
    event.respondWith(caches.match(request).then(hit=>hit||fetch(request).then(response=>{if(response.ok){const copy=response.clone();caches.open(CACHE).then(cache=>cache.put(request,copy));}return response;})));
  }
});
        <?php
        exit;
    }
}, 0);

add_action('wp_head', function () {
    $group_id = max(0, absint($_GET['group'] ?? 0));
    $manifest_url = add_query_arg(array_filter(['rop_manifest' => 1, 'group' => $group_id]), home_url('/'));
    ?>
    <link rel="manifest" href="<?php echo esc_url($manifest_url); ?>">
    <meta name="theme-color" content="#173426">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="RallyOP">
    <link rel="apple-touch-icon" href="<?php echo esc_url(rop_pwa_icon_url(192)); ?>">
    <?php
}, 2);

add_action('wp_footer', function () {
    if (!is_front_page()) { return; }
    $group_id = max(0, absint($_GET['group'] ?? 0));
    ?>
    <div class="rop-install" data-rop-install hidden><button type="button" data-rop-install-button>ADD RALLYOP TO PHONE</button><button type="button" class="rop-install-close" aria-label="Dismiss install prompt">×</button></div>
    <div class="rop-install-help" data-rop-install-help hidden><div><strong>Put RallyOP on your Home Screen</strong><p data-rop-install-copy>Open your browser menu and choose <b>Add to Home Screen</b> or <b>Install app</b>.</p><button type="button">GOT IT</button></div></div>
    <style>.rop-install{position:fixed;z-index:99999;right:14px;bottom:86px;display:flex;align-items:center;gap:5px;padding:6px;background:#173426;border-radius:12px;box-shadow:0 12px 30px rgba(0,0,0,.28)}.rop-install[hidden],.rop-install-help[hidden]{display:none!important}.rop-install>button:first-child{min-height:44px;padding:10px 15px;border:0;border-radius:8px;background:#c9ff3d;color:#14261c;font-weight:950}.rop-install-close{width:38px;height:38px;border:0;background:transparent;color:#fff;font-size:25px}.rop-install-help{position:fixed;z-index:100000;inset:0;display:grid;place-items:center;padding:20px;background:rgba(10,25,17,.76)}.rop-install-help>div{max-width:390px;padding:24px;background:#fff;border-radius:16px;color:#173426}.rop-install-help strong{font-size:23px}.rop-install-help p{line-height:1.55}.rop-install-help button{width:100%;min-height:46px;border:0;border-radius:9px;background:#c9ff3d;font-weight:950}@media(min-width:901px){.rop-install{bottom:22px}}</style>
    <script id="rallyop-pwa-script">(function(){var pageGroup=<?php echo wp_json_encode($group_id); ?>,installed=window.matchMedia('(display-mode: standalone)').matches||window.navigator.standalone===true,savedGroup='';try{if(pageGroup)localStorage.setItem('rop-pwa-group',String(pageGroup));savedGroup=localStorage.getItem('rop-pwa-group')||'';}catch(e){}if(installed&&!pageGroup&&savedGroup){location.replace('/?group='+encodeURIComponent(savedGroup)+'&source=pwa#clubhouse');return;}if('serviceWorker'in navigator)window.addEventListener('load',function(){navigator.serviceWorker.register('/?rop_sw=1',{scope:'/'}).catch(function(){});});var promptEvent=null,box=document.querySelector('[data-rop-install]'),button=document.querySelector('[data-rop-install-button]'),close=box&&box.querySelector('.rop-install-close'),help=document.querySelector('[data-rop-install-help]'),copy=help&&help.querySelector('[data-rop-install-copy]'),lastDismissed=parseInt(localStorage.getItem('rop-install-dismissed')||'0',10),dismissedRecently=lastDismissed&&Date.now()-lastDismissed<2592000000;if(installed||dismissedRecently||!box)return;var ios=/iphone|ipad|ipod/i.test(navigator.userAgent),android=/android/i.test(navigator.userAgent),mobile=ios||android||window.matchMedia('(max-width:900px)').matches;if(!mobile)return;if(copy&&ios)copy.innerHTML='On iPhone, tap the <b>Share</b> button, then choose <b>Add to Home Screen</b>.';else if(copy&&android)copy.innerHTML='On Android, open the browser menu and choose <b>Install app</b> or <b>Add to Home screen</b>.';window.addEventListener('beforeinstallprompt',function(e){e.preventDefault();promptEvent=e;box.hidden=false;});setTimeout(function(){if(!installed)box.hidden=false;},1800);button.addEventListener('click',function(){if(pageGroup)try{localStorage.setItem('rop-pwa-group',String(pageGroup));}catch(e){}if(promptEvent){promptEvent.prompt();promptEvent.userChoice.then(function(choice){promptEvent=null;if(choice.outcome==='accepted')box.hidden=true;});}else{help.hidden=false;}});close.addEventListener('click',function(){box.hidden=true;localStorage.setItem('rop-install-dismissed',String(Date.now()));});help.querySelector('button').addEventListener('click',function(){help.hidden=true;});window.addEventListener('appinstalled',function(){box.hidden=true;localStorage.setItem('rop-install-dismissed',String(Date.now()));});})();</script>
    <?php
}, 220);
