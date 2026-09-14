<?php
if (!defined('ABSPATH')) { exit; }

add_action('template_redirect', function() {
    if (!is_front_page() || !is_user_logged_in() || isset($_GET['join'])) { return; }
    $user_id = get_current_user_id();
    $requested = absint($_GET['group'] ?? 0);
    if ($requested && rallyop_can_access_group($requested)) {
        update_user_meta($user_id, 'rallyop_last_group_id', $requested);
        return;
    }
    if ($requested) { return; }
    $last_group = absint(get_user_meta($user_id, 'rallyop_last_group_id', true));
    if (!$last_group || !rallyop_can_access_group($last_group)) { return; }
    $args = ['group' => $last_group];
    if (!empty($_GET['rop_notice'])) { $args['rop_notice'] = sanitize_text_field(wp_unslash($_GET['rop_notice'])); }
    wp_safe_redirect(add_query_arg($args, home_url('/')) . '#clubhouse');
    exit;
}, 8);

add_action('wp_footer', function() {
    if (!is_front_page() || !is_user_logged_in()) { return; }
    ?>
    <style id="rallyop-app-shell-styles">
      .rop-app-bar,.rop-mobile-nav{display:none}
      @media(max-width:760px){
        body.logged-in.home{padding-bottom:78px}
        body.logged-in.home>.app-wrap{padding:18px 14px 30px!important}
        .rop-app-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0 0 14px;padding:13px 14px;background:#17251d;color:#fff;border-radius:12px}
        .rop-app-bar-copy{min-width:0}.rop-app-bar-copy span{display:block;color:#caff4c;font-size:10px;font-weight:900;letter-spacing:.12em}.rop-app-bar-copy strong{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:18px}
        .rop-app-record{flex:none;border:0;border-radius:9px;background:#caff4c;color:#17251d;padding:11px 13px;font-size:12px;font-weight:900}
        .rop-mobile-nav{display:grid;grid-template-columns:repeat(4,1fr);position:fixed;z-index:99980;right:10px;bottom:10px;left:10px;padding:7px;background:#17251d;border:1px solid rgba(255,255,255,.18);border-radius:16px;box-shadow:0 12px 38px rgba(0,0,0,.3)}
        .rop-mobile-nav button{display:grid;place-items:center;gap:3px;min-width:0;padding:8px 3px;border:0;border-radius:10px;background:transparent;color:#dfe8e2;font-size:10px;font-weight:850;letter-spacing:.03em}
        .rop-mobile-nav button::before{content:attr(data-icon);font-size:19px;line-height:1}
        .rop-mobile-nav button[aria-selected="true"]{background:#caff4c;color:#17251d}
        body.rop-app-ready .app-wrap>.rop-welcome,body.rop-app-ready .app-wrap>.rop-profile,body.rop-app-ready .app-wrap>.rop-groups-panel,body.rop-app-ready .app-wrap>.rop-group-head,body.rop-app-ready .app-wrap>#rallyop-community,body.rop-app-ready .app-wrap>.rop-rankings-feature,body.rop-app-ready .app-wrap>.rop-history-feature,body.rop-app-ready .app-wrap>.rop-actions-panel,body.rop-app-ready>#rallyop-performance,body.rop-app-ready>#open-play-sessions{display:none!important}
        body[data-rop-view="home"] .app-wrap>.rop-group-head,body[data-rop-view="home"] .app-wrap>#rallyop-community,body[data-rop-view="home"] .app-wrap>.rop-rankings-feature,body[data-rop-view="home"] .app-wrap>.rop-history-feature,body[data-rop-view="home"]>#open-play-sessions{display:block!important}
        body[data-rop-view="record"] .app-wrap>.rop-group-head,body[data-rop-view="record"] .app-wrap>.rop-actions-panel{display:block!important}
        body[data-rop-view="activity"] .app-wrap>.rop-group-head,body[data-rop-view="activity"] .app-wrap>#rallyop-community{display:block!important}
        body[data-rop-view="stats"] .app-wrap>.rop-group-head,body[data-rop-view="stats"] .app-wrap>.rop-rankings-feature,body[data-rop-view="stats"] .app-wrap>.rop-history-feature,body[data-rop-view="stats"]>#rallyop-performance{display:block!important}
        .app-wrap>.rop-profile{position:static!important;width:auto!important;margin:0 0 12px!important;padding:16px!important;border-radius:12px!important;background:#fff!important;box-shadow:none!important}
        .rop-groups-panel,.rop-group-head,.rop-rankings-feature,.rop-history-feature,.rop-actions-panel,.rop-community,#open-play-sessions,#rallyop-performance{margin:0 0 14px!important;padding:18px!important;border-radius:12px!important;box-shadow:none!important}
        .rop-group-head{display:flex!important;align-items:center!important;justify-content:space-between!important;gap:10px!important}
        .rop-group-head h2{font-size:27px!important}.rop-group-head .rop-button{padding:10px 12px!important;font-size:11px!important}
        .rop-community-grid,.rop-stat-panels,.rop-seasons{grid-template-columns:1fr!important}
        .rop-community{background:#f7f3e8!important}.rop-community .rop-heading-row p{font-size:14px}.rop-feed-card{box-shadow:none!important;border:1px solid #d3d0c7!important;border-left:4px solid #ef5a32!important}.rop-challenges{border-radius:10px}
        .rop-actions-panel>.eyebrow,.rop-actions-panel>.rop-action-intro{display:none}.rop-actions-panel>h2{margin:0 0 14px!important;font-size:30px!important}.rop-action-drawer{border-radius:10px!important}.rop-action-drawer>summary{padding:16px!important}
        #record-game[open]>summary{display:none}.rop-form{padding:14px!important}.rop-matchup{display:grid!important;grid-template-columns:1fr!important;gap:10px!important}.rop-versus{padding:0!important}.rop-team{padding:13px!important;border-radius:10px!important}.rop-team label{font-size:11px!important}.rop-team select,.rop-team input,.rop-form input[type="date"]{min-height:48px!important;padding:10px!important;font-size:16px!important}.rop-form .rop-button.alt{position:sticky;bottom:83px;width:100%!important;margin-top:12px!important;padding:16px!important;border-radius:10px!important;font-size:15px!important;box-shadow:0 8px 24px rgba(0,0,0,.2)}
        .rop-table-wrap{margin:0 -6px}.rop-derived-table th,.rop-derived-table td{padding:9px 6px!important;font-size:12px!important}.rop-achievements{grid-template-columns:1fr 1fr!important}
        .site-footer{padding-bottom:95px!important}
      }
    </style>
    <script id="rallyop-app-shell-script">
    (function(){
      var media=window.matchMedia('(max-width:760px)');
      function groupName(){var h=document.querySelector('.app-wrap .rop-group-head h2');return h?h.textContent.trim():'Your clubhouse';}
      function install(){
        if(!media.matches)return;
        var wrap=document.querySelector('.app-wrap'),community=document.getElementById('rallyop-community');if(!wrap||!community)return;
        if(!document.querySelector('.rop-app-bar')){var bar=document.createElement('div');bar.className='rop-app-bar';bar.innerHTML='<div class="rop-app-bar-copy"><span>NOW PLAYING</span><strong></strong></div><button type="button" class="rop-app-record" data-rop-go="record">+ RECORD GAME</button>';bar.querySelector('strong').textContent=groupName();wrap.insertBefore(bar,wrap.firstElementChild);}
        if(!document.querySelector('.rop-mobile-nav')){var nav=document.createElement('nav');nav.className='rop-mobile-nav';nav.setAttribute('aria-label','Clubhouse');nav.innerHTML='<button type="button" data-rop-go="home" data-icon="⌂">Home</button><button type="button" data-rop-go="record" data-icon="＋">Record</button><button type="button" data-rop-go="activity" data-icon="◉">Activity</button><button type="button" data-rop-go="stats" data-icon="▥">Stats</button>';document.body.appendChild(nav);}
        document.body.classList.add('rop-app-ready');
        var hash=location.hash,view=hash==='#record-game'?'record':hash==='#rallyop-community'?'activity':hash==='#rallyop-performance'?'stats':'home';setView(view,false);
      }
      function setView(view,scroll){
        document.body.dataset.ropView=view;document.querySelectorAll('[data-rop-go]').forEach(function(b){b.setAttribute('aria-selected',b.dataset.ropGo===view?'true':'false');});
        if(view==='record'){var drawer=document.getElementById('record-game');if(drawer)drawer.open=true;}
        if(scroll)window.scrollTo({top:document.getElementById('clubhouse').offsetTop-10,behavior:'smooth'});
      }
      document.addEventListener('click',function(e){var b=e.target.closest('[data-rop-go]');if(!b)return;e.preventDefault();setView(b.dataset.ropGo,true);});
      if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',install);else install();setTimeout(install,900);
    })();
    </script>
    <?php
}, 180);
