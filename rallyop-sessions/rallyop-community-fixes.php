<?php
if (!defined('ABSPATH')) { exit; }

add_action('wp_head', function() {
    if (!is_front_page() || !is_user_logged_in()) { return; }
    echo '<style id="rallyop-logged-in-hero-fix">body.logged-in.home>.hero{display:none!important}body.logged-in.home>.app-wrap{margin-top:0!important;padding-top:64px!important}</style>';
}, 20);

add_action('wp_footer', function() {
    if (!is_front_page() || !is_user_logged_in()) { return; }
    ?>
    <style id="rallyop-community-card-fix">
      .rop-feed-card{border:2px solid #17251d;box-shadow:6px 6px 0 #caff4c}
      .rop-feed-card+.rop-feed-card{margin-top:8px}
      .rop-community{margin:18px auto!important;padding:22px!important}
      .rop-community h2{font-size:clamp(28px,4vw,42px)!important}
      .rop-community h3{font-size:20px!important}
      .rop-feed-card{padding:14px!important;box-shadow:4px 4px 0 #caff4c}
      .rop-feed-card>strong{font-size:16px!important}
      .rop-feed-card p{font-size:14px!important}
      .rop-reactions button{min-width:0!important;padding:7px 10px!important;font-size:13px!important;line-height:1.15!important;font-weight:800}
      .rop-reactions button img,.rop-reactions button .emoji{width:18px!important;height:18px!important;margin:0 3px 0 0!important}
      .rop-reaction-count{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;margin-left:6px;padding:0 5px;border-radius:999px;background:#17251d;color:#fff;font-size:11px;font-weight:900;line-height:1}
    </style>
    <script id="rallyop-community-team-separator">
    (function(){
      function fix(){
        var community=document.getElementById('rallyop-community'),groupHead=document.querySelector('.app-wrap .rop-group-head'),fallback=document.querySelector('.app-wrap .rop-profile');
        var anchor=groupHead||fallback;if(community&&anchor&&community.previousElementSibling!==anchor){anchor.parentNode.insertBefore(community,anchor.nextSibling);}
        if(community){document.querySelectorAll('a').forEach(function(link){if(link.textContent.trim()==='Clubhouse')link.href=location.origin+'/?group='+community.dataset.group+'#clubhouse';});}
        document.querySelectorAll('.rop-feed-card[data-game] p').forEach(function(p){if(p.classList.contains('rop-feed-matchup'))return;p.textContent=p.textContent.replace(/\s*(?:\|\s*)?TEAM/g,' | TEAM');});
        var labels={'🔥':' Fire','👏':' Applaud','🥒':' Pickle'};
        document.querySelectorAll('.rop-reactions button[name="reaction"]').forEach(function(button){var emoji=button.value,count=(button.textContent.match(/\d+/)||[])[0];if(!labels[emoji])return;button.textContent=emoji+labels[emoji];if(count){var badge=document.createElement('span');badge.className='rop-reaction-count';badge.textContent=count;button.appendChild(badge);}});
      }
      if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',fix);else fix();setTimeout(fix,1000);
    })();
    </script>
    <?php
}, 146);
