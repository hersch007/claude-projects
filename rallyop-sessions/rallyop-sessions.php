<?php
/**
 * Plugin Name: RallyOP Sessions
 * Description: Schedule open-play sessions and collect player RSVPs inside the RallyOP clubhouse.
 * Version: 4.0.0
 * Author: RallyOP
 */

if (!defined('ABSPATH')) { exit; }

require_once __DIR__ . '/rallyop-content.php';
require_once __DIR__ . '/rallyop-community.php';

function rallyop_companion_table($kind) {
    global $wpdb;
    static $found = [];
    if (array_key_exists($kind, $found)) { return $found[$kind]; }
    $needles = $kind === 'players' ? ['rop_players', 'rallyop_players'] : ['rop_groups', 'rallyop_groups'];
    foreach ($needles as $suffix) {
        $table = $wpdb->prefix . $suffix;
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) { return $found[$kind] = $table; }
    }
    $tables = $wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($wpdb->prefix) . '%'));
    foreach ($tables as $table) {
        if (strpos($table, $wpdb->prefix) !== 0 || stripos($table, $kind) === false) { continue; }
        $columns = $wpdb->get_col("DESCRIBE `" . esc_sql($table) . "`");
        if (in_array('id', $columns, true) && in_array('name', $columns, true)) { return $found[$kind] = $table; }
    }
    return $found[$kind] = '';
}

function rallyop_companion_row($kind, $id) {
    global $wpdb;
    $table = rallyop_companion_table($kind);
    return $table ? $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table` WHERE id=%d", $id)) : null;
}

function rallyop_companion_can_manage_group($group_id) {
    if (current_user_can('manage_options')) { return true; }
    $group = rallyop_companion_row('groups', $group_id);
    if (!$group) { return false; }
    $user_id = get_current_user_id();
    foreach (['owner_user_id', 'owner_id', 'user_id', 'created_by'] as $key) {
        if (isset($group->$key) && (int) $group->$key === $user_id) { return true; }
    }
    return false;
}

function rallyop_companion_is_own_player($player) {
    if (!$player || !is_user_logged_in()) { return false; }
    $user = wp_get_current_user();
    foreach (['user_id', 'linked_user_id', 'wp_user_id'] as $key) {
        if (isset($player->$key) && (int) $player->$key === (int) $user->ID) { return true; }
    }
    return isset($player->email) && $player->email && strtolower(trim($player->email)) === strtolower(trim($user->user_email));
}

add_action('admin_post_rallyop_rename_player', function() {
    if (!is_user_logged_in()) { auth_redirect(); }
    $player_id = absint($_POST['player_id'] ?? 0);
    check_admin_referer('rallyop_rename_player_' . $player_id);
    $player = rallyop_companion_row('players', $player_id);
    $group_id = $player && isset($player->group_id) ? (int) $player->group_id : max(1, absint($_POST['group_id'] ?? 1));
    if (!$player || (!rallyop_companion_can_manage_group($group_id) && !rallyop_companion_is_own_player($player))) {
        wp_die('You do not have permission to rename this player.', 'RallyOP', ['response' => 403]);
    }
    $name = trim(sanitize_text_field(wp_unslash($_POST['name'] ?? '')));
    $notice = 'name_error';
    if ($name !== '' && mb_strlen($name) <= 80) {
        global $wpdb;
        $table = rallyop_companion_table('players');
        $duplicate = isset($player->group_id) ? $wpdb->get_var($wpdb->prepare("SELECT id FROM `$table` WHERE group_id=%d AND LOWER(name)=LOWER(%s) AND id<>%d LIMIT 1", $group_id, $name, $player_id)) : false;
        if (!$duplicate && $wpdb->update($table, ['name' => $name], ['id' => $player_id], ['%s'], ['%d']) !== false) { $notice = 'name_saved'; }
        elseif ($duplicate) { $notice = 'name_taken'; }
    }
    wp_safe_redirect(add_query_arg(['group' => $group_id, $notice => 1], home_url('/')) . '#clubhouse');
    exit;
});

add_action('wp_footer', function() {
    if (!is_front_page() || !is_user_logged_in()) { return; }
    global $wpdb;
    $group_id = max(1, absint($_GET['group'] ?? 1));
    $players_table = rallyop_companion_table('players');
    $players = $players_table ? $wpdb->get_results($wpdb->prepare("SELECT * FROM `$players_table` WHERE group_id=%d ORDER BY name", $group_id)) : [];
    $editable = [];
    foreach ($players as $player) {
        if (rallyop_companion_can_manage_group($group_id) || rallyop_companion_is_own_player($player)) {
            $editable[(int) $player->id] = ['name' => (string) $player->name, 'mine' => rallyop_companion_is_own_player($player)];
        }
    }
    if (!$editable) { return; }
    $payload = [];
    foreach ($editable as $id => $data) { $payload[$id] = $data + ['nonce' => wp_create_nonce('rallyop_rename_player_' . $id)]; }
    ?>
    <style id="rallyop-name-editor-styles">
      .rop-name-edit-form{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:8px 0}.rop-name-edit-form label{width:100%;font-size:10px;font-weight:900;letter-spacing:.1em;text-transform:uppercase}.rop-name-edit-form input[type=text]{box-sizing:border-box;min-width:170px;flex:1;padding:10px 11px;border:1px solid #a9aaa4;background:#fff}.rop-name-edit-form button{padding:10px 13px!important;background:#087cff!important;color:#fff!important;border:0!important;font-size:11px!important;font-weight:900!important}.rop-player-admin-row{align-items:flex-start!important}.rop-player-admin-row>.rop-name-edit-form{flex:1 1 260px;margin:0}.rop-player-admin-row>.rop-name-edit-form label{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}.rop-profile-my-name{margin-top:14px;padding-top:14px;border-top:1px solid #d5cec0}.rop-group-settings form:has(input[name="action"][value="rop_rename_group"]) input[name="name"]{border:2px solid #087cff!important}.rop-group-settings form:has(input[name="action"][value="rop_rename_group"])::before{content:'GROUP NAME';display:block;width:100%;margin-bottom:7px;font-size:10px;font-weight:900;letter-spacing:.1em}
      @media(max-width:700px){.rop-player-admin-row>.rop-name-edit-form{flex-basis:100%}.rop-name-edit-form input[type=text]{width:100%}}
    </style>
    <script id="rallyop-name-editor-script">
    (function(){
      var players=<?php echo wp_json_encode($payload); ?>, action=<?php echo wp_json_encode(admin_url('admin-post.php')); ?>, group=<?php echo wp_json_encode($group_id); ?>;
      function form(id,compact){var p=players[id];if(!p)return null;var f=document.createElement('form');f.className='rop-name-edit-form'+(compact?' rop-profile-my-name':'');f.method='post';f.action=action;f.innerHTML='<input type="hidden" name="action" value="rallyop_rename_player"><input type="hidden" name="player_id" value="'+id+'"><input type="hidden" name="group_id" value="'+group+'"><input type="hidden" name="_wpnonce" value="'+p.nonce+'"><label for="rop-name-'+id+(compact?'-profile':'')+'">'+(compact?'EDIT MY NAME':'PLAYER NAME')+'</label><input id="rop-name-'+id+(compact?'-profile':'')+'" type="text" name="name" maxlength="80" required value=""><button type="submit">SAVE NAME</button>';f.querySelector('input[name="name"]').value=p.name;return f;}
      function install(){
        document.querySelectorAll('.rop-player-link-form').forEach(function(link){var id=link.querySelector('input[name="player_id"]');if(!id||!players[id.value])return;var row=link.closest('.rop-player-admin-row');if(row&&!row.querySelector('.rop-name-edit-form'))row.insertBefore(form(id.value,false),row.querySelector('.rop-remove-player-form'));
        });
        var profile=document.querySelector('[data-rop-profile]'),name=profile&&profile.querySelector('[data-profile-name]');if(profile&&name&&!profile.querySelector('.rop-profile-my-name')){Object.keys(players).some(function(id){if(players[id].mine&&players[id].name===name.textContent.trim()){name.parentElement.appendChild(form(id,true));return true;}return false;});}
      }
      document.addEventListener('click',function(e){if(e.target.closest('.rop-player-link')){setTimeout(install,40);setTimeout(install,250);}});
      if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',install);else install();setTimeout(install,700);
      var profile=document.querySelector('[data-rop-profile]');if(profile&&window.MutationObserver){new MutationObserver(function(){install();}).observe(profile,{subtree:true,childList:true,characterData:true,attributes:true,attributeFilter:['hidden']});}
    })();
    </script>
    <?php
}, 110);

register_activation_hook(__FILE__, 'rallyop_sessions_activate');
function rallyop_sessions_activate() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $sessions = $wpdb->prefix . 'rallyop_sessions';
    $rsvps = $wpdb->prefix . 'rallyop_session_rsvps';
    $seasons = $wpdb->prefix . 'rallyop_seasons';
    $highlights = $wpdb->prefix . 'rallyop_game_highlights';
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta("CREATE TABLE $sessions (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        group_id bigint(20) unsigned NOT NULL DEFAULT 1,
        title varchar(160) NOT NULL,
        starts_at datetime NOT NULL,
        location varchar(190) NOT NULL DEFAULT '',
        notes text NOT NULL,
        capacity smallint(5) unsigned NOT NULL DEFAULT 0,
        created_by bigint(20) unsigned NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY group_starts (group_id, starts_at)
    ) $charset;");
    dbDelta("CREATE TABLE $rsvps (
        session_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        status varchar(12) NOT NULL DEFAULT 'yes',
        updated_at datetime NOT NULL,
        PRIMARY KEY (session_id, user_id),
        KEY status (status)
    ) $charset;");
    dbDelta("CREATE TABLE $seasons (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        group_id bigint(20) unsigned NOT NULL DEFAULT 1,
        name varchar(120) NOT NULL,
        starts_on date NOT NULL,
        ends_on date NULL,
        champion varchar(160) NOT NULL DEFAULT '',
        created_by bigint(20) unsigned NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY group_dates (group_id, starts_on, ends_on)
    ) $charset;");
    dbDelta("CREATE TABLE $highlights (
        game_id bigint(20) unsigned NOT NULL,
        group_id bigint(20) unsigned NOT NULL DEFAULT 1,
        is_comeback tinyint(1) unsigned NOT NULL DEFAULT 0,
        marked_by bigint(20) unsigned NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY (game_id),
        KEY group_comeback (group_id, is_comeback)
    ) $charset;");
    update_option('rallyop_companion_db_version', '2.1.0');
}

add_action('plugins_loaded', function() {
    if (get_option('rallyop_companion_db_version') !== '2.1.0') { rallyop_sessions_activate(); }
});

add_action('wp_footer', function() {
    if (!is_front_page() || !is_user_logged_in()) { return; }
    ?>
    <style id="rallyop-profile-styles">
      .rop-player-link{appearance:none;border:0;background:transparent;color:inherit;padding:0;font:inherit;font-weight:800;text-decoration:underline;text-decoration-color:#087cff;text-decoration-thickness:2px;text-underline-offset:4px;cursor:pointer}.rop-player-link:hover{color:#087cff}.rop-profile-backdrop{position:fixed;inset:0;z-index:99990;background:rgba(7,18,12,.72);backdrop-filter:blur(4px)}.rop-profile{position:fixed;top:0;right:0;z-index:99999;width:min(680px,100%);height:100vh;box-sizing:border-box;overflow:auto;padding:42px;background:#f7f3e8;color:#17251d;box-shadow:-18px 0 60px rgba(0,0,0,.3)}.rop-profile[hidden],.rop-profile-backdrop[hidden]{display:none!important}.rop-profile-close{position:absolute;top:18px;right:18px;width:42px;height:42px;border:0;background:#17251d;color:#fff;font-size:26px;cursor:pointer}.rop-profile-hero{display:flex;gap:20px;align-items:center;padding-bottom:26px;border-bottom:1px solid #d5cec0}.rop-profile-hero h2{margin:7px 0 4px;font-size:44px;line-height:1}.rop-profile-hero p{margin:0;color:#5c675f}.rop-profile-avatar{display:grid;place-items:center;width:92px;height:92px;flex:none;border-radius:50%;background:#087cff;color:#fff;font-size:38px;font-weight:900}.rop-profile-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin:20px 0}.rop-profile-metrics div{padding:14px;background:#fff;border-top:3px solid #ef5a32}.rop-profile-metrics span{display:block;font-size:9px;font-weight:800;letter-spacing:.08em;color:#6c756f}.rop-profile-metrics strong{display:block;margin-top:7px;font-size:21px}.rop-profile-chart{padding:20px;background:#17271e;color:#fff}.rop-profile-chart h3{margin:0 0 10px}.rop-profile-chart svg{display:block;width:100%;height:auto}.rop-profile-chart line{stroke:#64746a;stroke-width:1}.rop-profile-chart polyline{fill:none;stroke:#38a3ff;stroke-width:6;stroke-linecap:round;stroke-linejoin:round}.rop-profile-chart [data-profile-rating-label]{text-align:right;font-weight:800;color:#76c5ff}.rop-profile-columns{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px}.rop-profile-columns>div{padding:20px;background:#fff}.rop-profile-columns h3{margin:0 0 12px;font-size:18px}.rop-profile-columns h3:not(:first-child){margin-top:24px}.rop-profile-tag{display:inline-block;margin:0 5px 6px 0;padding:7px 9px;background:#dcebd3;font-size:11px;font-weight:800}.rop-profile-opponent{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e1ddd4}@media(max-width:600px){.rop-profile{padding:28px 20px}.rop-profile-hero h2{font-size:34px}.rop-profile-avatar{width:70px;height:70px}.rop-profile-metrics{grid-template-columns:1fr 1fr}.rop-profile-columns{grid-template-columns:1fr}}
    </style>
    <script id="rallyop-profile-script">
    (function(){
      function games(){var out=[];document.querySelectorAll('.rop-game-date').forEach(function(d){var row=d.closest('tr'),t=row&&row.querySelectorAll('.rop-game-team');if(!t||t.length!==2)return;function team(x){var s=x.querySelector('span'),m=s&&s.textContent.match(/·\s*(\d+)/);return{names:x.querySelector('strong').textContent.split('&').map(function(n){return n.trim();}),score:m?parseInt(m[1],10):0,won:x.classList.contains('winner')}}out.push({a:team(t[0]),b:team(t[1]),date:d.textContent.trim()});});return out;}
      function ranking(){var r={};document.querySelectorAll('.rop-table').forEach(function(t){var h=Array.from(t.querySelectorAll('th')).map(function(x){return x.textContent.trim();});if(h.indexOf('Rating')<0)return;t.querySelectorAll('tbody tr').forEach(function(row){var c=row.children,n=(c[1]&&c[1].textContent||'').replace('♛','').trim();if(n)r[n]=parseInt(c[c.length-1].textContent,10)||1200;});});return r;}
      function addLinks(){document.querySelectorAll('.rop-table').forEach(function(t){var h=Array.from(t.querySelectorAll('th')).map(function(x){return x.textContent.trim();}),i=h.indexOf('Player');if(i<0)return;t.querySelectorAll('tbody tr').forEach(function(r){var c=r.children[i];if(!c||c.querySelector('.rop-player-link'))return;var crown=c.querySelector('.rop-crown'),name=c.textContent.replace('♛','').trim(),b=document.createElement('button');b.type='button';b.className='rop-player-link';b.dataset.player=name;b.textContent=name;c.textContent='';c.appendChild(b);if(crown)c.appendChild(crown);});});document.querySelectorAll('[data-rop-player-stats] tr').forEach(function(r){var c=r.children[0],strong=c&&c.querySelector('strong');if(!strong||c.querySelector('.rop-player-link'))return;var name=strong.textContent,b=document.createElement('button');b.type='button';b.className='rop-player-link';b.dataset.player=name;b.textContent=name;c.textContent='';c.appendChild(b);});}
      function openProfile(name){var all=games(),ratings=ranking(),s={w:0,l:0,pf:0,pa:0,form:[]},partners={},opponents={};all.forEach(function(g){var mine=g.a.names.indexOf(name)>=0?g.a:(g.b.names.indexOf(name)>=0?g.b:null);if(!mine)return;var other=mine===g.a?g.b:g.a,won=mine.won;s[won?'w':'l']++;s.pf+=mine.score;s.pa+=other.score;s.form.push(won?'W':'L');mine.names.filter(function(n){return n!==name;}).forEach(function(n){partners[n]=partners[n]||{w:0,l:0};partners[n][won?'w':'l']++;});other.names.forEach(function(n){opponents[n]=opponents[n]||{w:0,l:0};opponents[n][won?'w':'l']++;});});var partner=Object.keys(partners).sort(function(a,b){return partners[b].w-partners[a].w||partners[b].w+partners[b].l-(partners[a].w+partners[a].l);})[0],tough=Object.keys(opponents).sort(function(a,b){return opponents[b].w-opponents[b].l-(opponents[a].w-opponents[a].l);});var rating=ratings[name]||1200,total=s.w+s.l,pct=total?Math.round(s.w*100/total):0,profile=document.querySelector('[data-rop-profile]');profile.querySelector('[data-profile-avatar]').textContent=name.charAt(0).toUpperCase();profile.querySelector('[data-profile-name]').textContent=name;profile.querySelector('[data-profile-summary]').textContent=(total?total+' games played':'No games yet')+' · '+(document.querySelector('.rop-season-pill')||{textContent:'ALL TIME'}).textContent;profile.querySelector('[data-profile-metrics]').innerHTML='<div><span>RATING</span><strong>'+rating+'</strong></div><div><span>RECORD</span><strong>'+s.w+'–'+s.l+'</strong></div><div><span>WIN RATE</span><strong>'+pct+'%</strong></div><div><span>POINT DIFF.</span><strong>'+(s.pf-s.pa>0?'+':'')+(s.pf-s.pa)+'</strong></div>';profile.querySelector('[data-profile-form]').innerHTML=s.form.slice(0,8).map(function(x){return '<i class="rop-form-dot '+x.toLowerCase()+'">'+x+'</i>';}).join('')||'No results yet';profile.querySelector('[data-profile-partner]').textContent=partner?partner+' · '+partners[partner].w+'–'+partners[partner].l:'No partner data yet';profile.querySelector('[data-profile-opponents]').innerHTML=tough.slice(0,4).map(function(n){var o=opponents[n];return '<div class="rop-profile-opponent"><strong>'+n+'</strong><span>'+o.w+'–'+o.l+' against</span></div>';}).join('')||'No opponent data yet';var earned=[];document.querySelectorAll('[data-rop-achievements]>div').forEach(function(a){if(a.textContent.indexOf(name)>=0)earned.push(a.querySelector('span').textContent);});profile.querySelector('[data-profile-achievements]').innerHTML=earned.map(function(x){return '<span class="rop-profile-tag">'+x+'</span>';}).join('')||'<span class="rop-profile-tag">First games logged</span>';var ratingHistory=[1200],current=1200;all.slice().reverse().forEach(function(g){var mine=g.a.names.indexOf(name)>=0?g.a:(g.b.names.indexOf(name)>=0?g.b:null);if(!mine)return;current+=mine.won?12:-12;ratingHistory.push(current);});ratingHistory[ratingHistory.length-1]=rating;var min=Math.min.apply(null,ratingHistory.concat([1180])),max=Math.max.apply(null,ratingHistory.concat([1220])),points=ratingHistory.map(function(v,i){var x=20+(ratingHistory.length===1?0:i*560/(ratingHistory.length-1)),y=145-(v-min)*120/Math.max(1,max-min);return x+','+y;}).join(' ');profile.querySelector('[data-profile-line]').setAttribute('points',points);profile.querySelector('[data-profile-rating-label]').textContent='Current rating: '+rating;profile.hidden=false;document.querySelector('.rop-profile-backdrop').hidden=false;document.body.style.overflow='hidden';window.history.replaceState(null,'','?player='+encodeURIComponent(name)+'#player-profile');}
      document.addEventListener('click',function(e){var p=e.target.closest('.rop-player-link');if(p)openProfile(p.dataset.player);if(e.target.closest('[data-rop-profile-close]')){document.querySelector('[data-rop-profile]').hidden=true;document.querySelector('.rop-profile-backdrop').hidden=true;document.body.style.overflow='';history.replaceState(null,'',location.pathname+location.search.replace(/[?&]player=[^&]*/,'')+'#rallyop-performance');}});function ready(){addLinks();var q=new URLSearchParams(location.search).get('player');if(q)setTimeout(function(){openProfile(q);},200);}setTimeout(ready,500);setTimeout(addLinks,1700);
    })();
    </script>
    <?php
}, 30);

add_action('admin_post_rallyop_create_session', 'rallyop_create_session');
function rallyop_create_session() {
    if (!is_user_logged_in()) { auth_redirect(); }
    check_admin_referer('rallyop_create_session');
    global $wpdb;
    $group_id = max(1, absint($_POST['group_id'] ?? 1));
    $title = sanitize_text_field($_POST['title'] ?? 'Open Play');
    $date = sanitize_text_field($_POST['date'] ?? '');
    $time = sanitize_text_field($_POST['time'] ?? '');
    $location = sanitize_text_field($_POST['location'] ?? '');
    $notes = sanitize_textarea_field($_POST['notes'] ?? '');
    $capacity = min(99, max(0, absint($_POST['capacity'] ?? 0)));
    $timestamp = strtotime($date . ' ' . $time);
    if ($title && $timestamp) {
        $wpdb->insert($wpdb->prefix . 'rallyop_sessions', [
            'group_id' => $group_id,
            'title' => $title,
            'starts_at' => wp_date('Y-m-d H:i:s', $timestamp),
            'location' => $location,
            'notes' => $notes,
            'capacity' => $capacity,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
        ], ['%d','%s','%s','%s','%s','%d','%d','%s']);
        $session_id = (int) $wpdb->insert_id;
        if ($session_id) {
            $wpdb->replace($wpdb->prefix . 'rallyop_session_rsvps', [
                'session_id' => $session_id,
                'user_id' => get_current_user_id(),
                'status' => 'yes',
                'updated_at' => current_time('mysql'),
            ], ['%d','%d','%s','%s']);
        }
    }
    wp_safe_redirect(add_query_arg(['group' => $group_id, 'session_added' => 1], home_url('/')) . '#open-play-sessions');
    exit;
}

add_action('admin_post_rallyop_session_rsvp', 'rallyop_session_rsvp');
function rallyop_session_rsvp() {
    if (!is_user_logged_in()) { auth_redirect(); }
    $session_id = absint($_POST['session_id'] ?? 0);
    check_admin_referer('rallyop_session_rsvp_' . $session_id);
    global $wpdb;
    $session = $wpdb->get_row($wpdb->prepare("SELECT group_id FROM {$wpdb->prefix}rallyop_sessions WHERE id = %d", $session_id));
    $allowed = ['yes', 'maybe', 'no'];
    $status = sanitize_key($_POST['status'] ?? 'yes');
    if ($session && in_array($status, $allowed, true)) {
        $wpdb->replace($wpdb->prefix . 'rallyop_session_rsvps', [
            'session_id' => $session_id,
            'user_id' => get_current_user_id(),
            'status' => $status,
            'updated_at' => current_time('mysql'),
        ], ['%d','%d','%s','%s']);
    }
    $group_id = $session ? (int) $session->group_id : 1;
    wp_safe_redirect(add_query_arg('group', $group_id, home_url('/')) . '#open-play-sessions');
    exit;
}

add_action('admin_post_rallyop_start_season', 'rallyop_start_season');
function rallyop_start_season() {
    if (!is_user_logged_in()) { auth_redirect(); }
    check_admin_referer('rallyop_start_season');
    global $wpdb;
    $group_id = max(1, absint($_POST['group_id'] ?? 1));
    $name = sanitize_text_field($_POST['season_name'] ?? 'New Season');
    $starts_on = sanitize_text_field($_POST['starts_on'] ?? wp_date('Y-m-d'));
    $table = $wpdb->prefix . 'rallyop_seasons';
    $active = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE group_id=%d AND ends_on IS NULL LIMIT 1", $group_id));
    if (!$active && $name && preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $starts_on)) {
        $wpdb->insert($table, ['group_id'=>$group_id,'name'=>$name,'starts_on'=>$starts_on,'created_by'=>get_current_user_id(),'created_at'=>current_time('mysql')], ['%d','%s','%s','%d','%s']);
    }
    wp_safe_redirect(add_query_arg('group', $group_id, home_url('/')) . '#rallyop-performance'); exit;
}

add_action('admin_post_rallyop_end_season', 'rallyop_end_season');
function rallyop_end_season() {
    if (!is_user_logged_in()) { auth_redirect(); }
    $season_id = absint($_POST['season_id'] ?? 0);
    check_admin_referer('rallyop_end_season_' . $season_id);
    global $wpdb;
    $table = $wpdb->prefix . 'rallyop_seasons';
    $season = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d AND ends_on IS NULL", $season_id));
    if ($season) {
        $champion = sanitize_text_field($_POST['champion'] ?? '');
        $wpdb->update($table, ['ends_on'=>wp_date('Y-m-d'),'champion'=>$champion], ['id'=>$season_id], ['%s','%s'], ['%d']);
    }
    $group_id = $season ? (int)$season->group_id : 1;
    wp_safe_redirect(add_query_arg('group', $group_id, home_url('/')) . '#rallyop-performance'); exit;
}

add_action('admin_post_rallyop_toggle_comeback', 'rallyop_toggle_comeback');
function rallyop_toggle_comeback() {
    if (!is_user_logged_in()) { auth_redirect(); }
    check_admin_referer('rallyop_toggle_comeback');
    global $wpdb;
    $game_id = absint($_POST['game_id'] ?? 0);
    $group_id = max(1, absint($_POST['group_id'] ?? 1));
    $table = $wpdb->prefix . 'rallyop_game_highlights';
    $current = $wpdb->get_var($wpdb->prepare("SELECT is_comeback FROM $table WHERE game_id=%d", $game_id));
    if ($game_id) {
        $wpdb->replace($table, ['game_id'=>$game_id,'group_id'=>$group_id,'is_comeback'=>$current ? 0 : 1,'marked_by'=>get_current_user_id(),'updated_at'=>current_time('mysql')], ['%d','%d','%d','%d','%s']);
    }
    wp_safe_redirect(add_query_arg('group', $group_id, home_url('/')) . '#rallyop-performance'); exit;
}

function rallyop_performance_markup() {
    if (!is_user_logged_in()) { return ''; }
    global $wpdb;
    $group_id = max(1, absint($_GET['group'] ?? 1));
    $table = $wpdb->prefix . 'rallyop_seasons';
    $active = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE group_id=%d AND ends_on IS NULL ORDER BY starts_on DESC LIMIT 1", $group_id));
    $archive = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE group_id=%d AND ends_on IS NOT NULL ORDER BY ends_on DESC LIMIT 8", $group_id));
    $comeback_ids = $wpdb->get_col($wpdb->prepare("SELECT game_id FROM {$wpdb->prefix}rallyop_game_highlights WHERE group_id=%d AND is_comeback=1", $group_id));
    $comeback_nonce = wp_create_nonce('rallyop_toggle_comeback');
    ob_start(); ?>
    <section id="rallyop-performance" class="rop-performance" data-season-start="<?php echo esc_attr($active ? $active->starts_on : ''); ?>" data-comebacks="<?php echo esc_attr(implode(',', array_map('absint', $comeback_ids))); ?>" data-comeback-nonce="<?php echo esc_attr($comeback_nonce); ?>" data-group-id="<?php echo esc_attr($group_id); ?>" data-admin-post="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <div class="rop-kicker">THE NUMBERS</div>
      <div class="rop-heading-row"><div><h2>Stats &amp; Achievements</h2><p>See what’s working, who’s hot, and which partnerships own the court.</p></div><div class="rop-season-pill"><?php echo $active ? esc_html($active->name) : 'ALL TIME'; ?></div></div>
      <div class="rop-achievements" data-rop-achievements><div><span>WEEKLY CHAMPION</span><strong>—</strong></div><div><span>LONGEST STREAK</span><strong>—</strong></div><div><span>BIGGEST UPSET</span><strong>—</strong></div><div><span>COMEBACK WIN</span><strong>Not tracked yet</strong></div><div><span>MOST ACTIVE</span><strong>—</strong></div></div>
      <div class="rop-stat-panels">
        <div class="rop-stat-panel"><h3>Player performance</h3><div class="rop-table-wrap"><table class="rop-table rop-derived-table"><thead><tr><th>Player</th><th>W–L</th><th>Win %</th><th>Point diff.</th><th>Recent</th></tr></thead><tbody data-rop-player-stats></tbody></table></div></div>
        <div class="rop-stat-panel"><h3>Partner combinations</h3><div class="rop-table-wrap"><table class="rop-table rop-derived-table"><thead><tr><th>Partners</th><th>W–L</th><th>Win %</th><th>Diff.</th></tr></thead><tbody data-rop-partners></tbody></table></div></div>
        <div class="rop-stat-panel rop-wide"><h3>Head-to-head</h3><div class="rop-table-wrap"><table class="rop-table rop-derived-table"><thead><tr><th>Matchup</th><th>Games</th><th>Series</th><th>Point diff.</th></tr></thead><tbody data-rop-headtohead></tbody></table></div></div>
      </div>
      <div class="rop-seasons"><div><div class="rop-kicker">SEASONS</div><h3><?php echo $active ? esc_html($active->name) : 'Start a fresh season'; ?></h3><?php if ($active): ?><p>Running since <?php echo esc_html(wp_date('M j, Y', strtotime($active->starts_on))); ?>. Ending it archives the champion and starts a clean statistical chapter.</p><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="rallyop_end_season"><input type="hidden" name="season_id" value="<?php echo absint($active->id); ?>"><input type="hidden" name="champion" value="" data-rop-champion><?php wp_nonce_field('rallyop_end_season_' . $active->id); ?><button class="rop-secondary" type="submit">END &amp; ARCHIVE SEASON</button></form><?php else: ?><form class="rop-season-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="rallyop_start_season"><input type="hidden" name="group_id" value="<?php echo absint($group_id); ?>"><?php wp_nonce_field('rallyop_start_season'); ?><label>SEASON NAME<input name="season_name" placeholder="Fall 2026" required maxlength="120"></label><label>START DATE<input type="date" name="starts_on" value="<?php echo esc_attr(wp_date('Y-m-d')); ?>" required></label><button class="rop-primary" type="submit">START SEASON</button></form><?php endif; ?></div>
        <?php if ($archive): ?><div class="rop-archive"><h4>Season archive</h4><?php foreach ($archive as $season): ?><div><strong><?php echo esc_html($season->name); ?></strong><span><?php echo esc_html(wp_date('M j, Y', strtotime($season->starts_on))); ?>–<?php echo esc_html(wp_date('M j, Y', strtotime($season->ends_on))); ?></span><b><?php echo $season->champion ? esc_html($season->champion) . ' · Champion' : 'Archived'; ?></b></div><?php endforeach; ?></div><?php endif; ?>
      </div>
      <aside class="rop-profile" data-rop-profile hidden aria-label="Player profile"><button type="button" class="rop-profile-close" data-rop-profile-close aria-label="Close player profile">×</button><div class="rop-profile-hero"><div class="rop-profile-avatar" data-profile-avatar></div><div><div class="rop-kicker">PLAYER PROFILE</div><h2 data-profile-name></h2><p data-profile-summary></p></div></div><div class="rop-profile-metrics" data-profile-metrics></div><div class="rop-profile-chart"><h3>Rating history</h3><svg viewBox="0 0 600 170" role="img" aria-label="Rating history chart"><line x1="20" y1="145" x2="580" y2="145"></line><polyline data-profile-line points=""></polyline></svg><div data-profile-rating-label></div></div><div class="rop-profile-columns"><div><h3>Recent form</h3><div data-profile-form></div><h3>Achievements</h3><div data-profile-achievements></div></div><div><h3>Best partner</h3><p data-profile-partner></p><h3>Toughest opponents</h3><div data-profile-opponents></div></div></div></aside><div class="rop-profile-backdrop" data-rop-profile-close hidden></div>
    </section><?php return ob_get_clean();
}

function rallyop_sessions_markup() {
    if (!is_user_logged_in()) { return ''; }
    $GLOBALS['rallyop_sessions_rendered'] = true;
    global $wpdb;
    $group_id = max(1, absint($_GET['group'] ?? 1));
    $sessions_table = $wpdb->prefix . 'rallyop_sessions';
    $rsvps_table = $wpdb->prefix . 'rallyop_session_rsvps';
    $sessions = $wpdb->get_results($wpdb->prepare(
        "SELECT s.*, SUM(r.status='yes') yes_count, SUM(r.status='maybe') maybe_count
         FROM $sessions_table s LEFT JOIN $rsvps_table r ON r.session_id=s.id
         WHERE s.group_id=%d AND s.starts_at >= DATE_SUB(%s, INTERVAL 3 HOUR)
         GROUP BY s.id ORDER BY s.starts_at ASC LIMIT 12",
        $group_id, current_time('mysql')
    ));
    $my_rsvps = [];
    if ($sessions) {
        $ids = implode(',', array_map('absint', wp_list_pluck($sessions, 'id')));
        $rows = $wpdb->get_results($wpdb->prepare("SELECT session_id,status FROM $rsvps_table WHERE user_id=%d AND session_id IN ($ids)", get_current_user_id()));
        foreach ($rows as $row) { $my_rsvps[(int)$row->session_id] = $row->status; }
    }
    ob_start(); ?>
    <section id="open-play-sessions" class="rop-sessions">
      <div class="rop-kicker">NEXT UP</div>
      <div class="rop-heading-row"><div><h2>Open Play Sessions</h2><p>Pick a time, rally the crew, and know who’s in.</p></div><button class="rop-primary" type="button" data-rop-toggle>＋ SCHEDULE</button></div>
      <?php if (!empty($_GET['session_added'])): ?><div class="rop-notice">Session scheduled. You’re marked as in.</div><?php endif; ?>
      <form class="rop-create" data-rop-form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" hidden>
        <input type="hidden" name="action" value="rallyop_create_session"><input type="hidden" name="group_id" value="<?php echo esc_attr($group_id); ?>">
        <?php wp_nonce_field('rallyop_create_session'); ?>
        <label>SESSION NAME<input name="title" value="Open Play" required maxlength="160"></label>
        <div class="rop-grid"><label>DATE<input type="date" name="date" required min="<?php echo esc_attr(wp_date('Y-m-d')); ?>"></label><label>TIME<input type="time" name="time" required></label></div>
        <label>COURT / LOCATION<input name="location" maxlength="190" placeholder="Riverside Courts"></label>
        <div class="rop-grid"><label>PLAYER LIMIT<input type="number" name="capacity" min="0" max="99" value="8"><small>Use 0 for no limit</small></label><label>NOTE<input name="notes" maxlength="300" placeholder="Bring a ball and water"></label></div>
        <button class="rop-primary" type="submit">CREATE SESSION</button>
      </form>
      <div class="rop-session-list">
      <?php if (!$sessions): ?><div class="rop-empty"><strong>No sessions scheduled yet.</strong><span>Be the first to put the next game on the calendar.</span></div><?php endif; ?>
      <?php foreach ($sessions as $session): $mine = $my_rsvps[(int)$session->id] ?? ''; $start = strtotime($session->starts_at); ?>
        <article class="rop-session-card">
          <div class="rop-date"><strong><?php echo esc_html(wp_date('M', $start)); ?></strong><b><?php echo esc_html(wp_date('j', $start)); ?></b><span><?php echo esc_html(wp_date('D', $start)); ?></span></div>
          <div class="rop-session-info"><h3><?php echo esc_html($session->title); ?></h3><p><?php echo esc_html(wp_date('g:i A', $start)); ?><?php if ($session->location): ?> · <?php echo esc_html($session->location); ?><?php endif; ?></p><?php if ($session->notes): ?><small><?php echo esc_html($session->notes); ?></small><?php endif; ?><div class="rop-count"><strong><?php echo absint($session->yes_count); ?> IN</strong><?php if ($session->capacity): ?> / <?php echo absint($session->capacity); ?> spots<?php endif; ?><?php if ($session->maybe_count): ?> · <?php echo absint($session->maybe_count); ?> maybe<?php endif; ?></div></div>
          <form class="rop-rsvp" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="rallyop_session_rsvp"><input type="hidden" name="session_id" value="<?php echo absint($session->id); ?>"><?php wp_nonce_field('rallyop_session_rsvp_' . $session->id); ?>
            <?php foreach (['yes'=>'I’M IN','maybe'=>'MAYBE','no'=>'CAN’T'] as $value=>$label): ?><button name="status" value="<?php echo esc_attr($value); ?>" class="<?php echo $mine === $value ? 'active' : ''; ?>"><?php echo esc_html($label); ?></button><?php endforeach; ?>
          </form>
        </article>
      <?php endforeach; ?>
      </div>
    </section><?php
    return ob_get_clean();
}

add_shortcode('rallyop_sessions', 'rallyop_sessions_markup');
add_filter('the_content', function($content) {
    if (is_front_page() && is_user_logged_in() && in_the_loop() && is_main_query()) { return $content . rallyop_sessions_markup(); }
    return $content;
}, 25);

add_action('wp_footer', function() {
    if (is_front_page() && is_user_logged_in() && empty($GLOBALS['rallyop_sessions_rendered'])) {
        echo rallyop_sessions_markup();
    }
    if (is_front_page() && is_user_logged_in()) { echo rallyop_performance_markup(); }
}, 5);

add_action('wp_enqueue_scripts', function() {
    if (!is_front_page() || !is_user_logged_in()) { return; }
    wp_register_style('rallyop-sessions', false, [], '2.1.0'); wp_enqueue_style('rallyop-sessions');
    wp_add_inline_style('rallyop-sessions', '.rop-sessions{max-width:1180px;margin:28px auto 72px;padding:36px;border:1px solid #d8d3c7;background:#f7f3e8;color:#17251d;font-family:inherit}.rop-kicker{font-size:12px;font-weight:800;letter-spacing:.16em;color:#db532d}.rop-heading-row{display:flex;justify-content:space-between;gap:20px;align-items:flex-start}.rop-heading-row h2{font-size:clamp(30px,5vw,54px);line-height:1;margin:10px 0}.rop-heading-row p{margin:0 0 24px}.rop-primary{background:#ef5a32!important;color:#fff!important;border:0!important;padding:15px 20px!important;font-weight:800!important;letter-spacing:.05em;cursor:pointer}.rop-create{margin:18px 0 28px;padding:24px;background:#19291f;color:#fff}.rop-create label{display:block;font-size:11px;font-weight:800;letter-spacing:.1em;margin-bottom:16px}.rop-create input{box-sizing:border-box;width:100%;margin-top:7px;padding:13px;border:1px solid #718078;background:#fff;color:#17251d}.rop-create small{display:block;margin-top:5px;font-weight:400;letter-spacing:0}.rop-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.rop-session-list{display:grid;gap:12px}.rop-session-card{display:grid;grid-template-columns:82px 1fr auto;gap:20px;align-items:center;padding:20px;background:#fff;border:1px solid #ded9cf}.rop-date{text-align:center;border-right:1px solid #ded9cf;padding-right:18px}.rop-date strong,.rop-date span{display:block;font-size:11px;letter-spacing:.1em}.rop-date b{display:block;font-size:34px;line-height:1.1}.rop-session-info h3{margin:0 0 5px;font-size:23px}.rop-session-info p,.rop-session-info small{display:block;margin:0 0 5px}.rop-count{margin-top:10px;font-size:12px;color:#59655e}.rop-rsvp{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}.rop-rsvp button{border:1px solid #17251d;background:transparent;color:#17251d;padding:9px 11px;font-size:11px;font-weight:800;cursor:pointer}.rop-rsvp button.active{background:#17251d;color:#fff}.rop-empty{display:flex;flex-direction:column;gap:5px;padding:28px;border:1px dashed #aaa297}.rop-notice{padding:12px 16px;margin:0 0 18px;background:#dbe8cf;color:#20351c;font-weight:700}.rop-invite-accept,form:has(input[name="action"][value*="accept"]) button,a[href*="accept_invite"],a[href*="accept-invite"]{display:inline-flex!important;align-items:center!important;justify-content:center!important;background:#087cff!important;color:#fff!important;border:2px solid #087cff!important;box-shadow:0 5px 0 #064fa1!important;padding:15px 24px!important;font-weight:900!important;letter-spacing:.06em!important;text-decoration:none!important;text-transform:uppercase!important;transition:transform .15s,background .15s,border-color .15s!important}.rop-invite-accept:hover,form:has(input[name="action"][value*="accept"]) button:hover,a[href*="accept_invite"]:hover,a[href*="accept-invite"]:hover{background:#ef4e35!important;border-color:#ef4e35!important;box-shadow:0 5px 0 #9e2b1a!important;transform:translateY(-2px)!important}@media(max-width:700px){.rop-sessions{margin:16px 12px 50px;padding:22px}.rop-heading-row{display:block}.rop-heading-row .rop-primary{margin-bottom:18px}.rop-grid{grid-template-columns:1fr}.rop-session-card{grid-template-columns:62px 1fr}.rop-rsvp{grid-column:1/-1;justify-content:stretch}.rop-rsvp button{flex:1}.rop-date{padding-right:12px}}');
    wp_add_inline_style('rallyop-sessions', '.rop-performance{max-width:1180px;margin:28px auto;padding:36px;background:#17271e;color:#fff}.rop-performance .rop-heading-row h2{color:#fff}.rop-season-pill{padding:10px 14px;background:#087cff;color:#fff;font-size:12px;font-weight:900;letter-spacing:.1em}.rop-achievements{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin:24px 0}.rop-achievements>div{min-height:100px;padding:16px;background:#22362a;border-top:4px solid #ef5a32}.rop-achievements span{display:block;font-size:10px;font-weight:800;letter-spacing:.09em;color:#aebbb3}.rop-achievements strong{display:block;margin-top:12px;font-size:18px}.rop-stat-panels{display:grid;grid-template-columns:1fr 1fr;gap:14px}.rop-stat-panel{padding:22px;background:#f7f3e8;color:#17251d}.rop-stat-panel.rop-wide{grid-column:1/-1}.rop-stat-panel h3,.rop-seasons h3{margin:0 0 16px;font-size:24px}.rop-derived-table{width:100%;border-collapse:collapse}.rop-derived-table th,.rop-derived-table td{padding:10px 8px;border-bottom:1px solid #d7d0c2;text-align:left;font-size:13px}.rop-derived-table th{font-size:10px;letter-spacing:.08em}.rop-form-dot{display:inline-flex;width:22px;height:22px;margin-right:3px;align-items:center;justify-content:center;border-radius:50%;font-size:10px;font-weight:900}.rop-form-dot.w{background:#d7ebcb;color:#21531a}.rop-form-dot.l{background:#f1d3ca;color:#842a18}.rop-seasons{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:14px;padding:24px;background:#22362a}.rop-season-form{display:grid;grid-template-columns:1fr 1fr auto;gap:10px;align-items:end}.rop-season-form label{font-size:10px;font-weight:800;letter-spacing:.08em}.rop-season-form input{box-sizing:border-box;width:100%;margin-top:7px;padding:12px}.rop-secondary{padding:13px 18px;border:1px solid #fff;background:transparent;color:#fff;font-weight:800}.rop-archive>div{display:grid;grid-template-columns:1fr auto;gap:4px 12px;padding:10px 0;border-bottom:1px solid #4b5b51}.rop-archive span{font-size:12px;color:#afbbb3}.rop-archive b{grid-row:1/3;grid-column:2;align-self:center;color:#f2c94c}.rop-empty-row{text-align:center!important;padding:24px!important;color:#67736b}.rop-table-wrap{overflow-x:auto}@media(max-width:850px){.rop-performance{margin:16px 12px;padding:22px}.rop-achievements{grid-template-columns:1fr 1fr}.rop-stat-panels,.rop-seasons{grid-template-columns:1fr}.rop-stat-panel.rop-wide{grid-column:auto}.rop-season-form{grid-template-columns:1fr}.rop-heading-row{display:block}.rop-season-pill{display:inline-block;margin-bottom:18px}}');
    wp_register_script('rallyop-sessions', '', [], '2.1.0', true); wp_enqueue_script('rallyop-sessions');
    wp_add_inline_script('rallyop-sessions', "document.addEventListener('DOMContentLoaded',function(){var s=document.getElementById('open-play-sessions'),f=document.querySelector('footer');if(s&&f&&s.parentNode===document.body)f.parentNode.insertBefore(s,f);var stats={};document.querySelectorAll('.rop-game-team strong').forEach(function(n){n.textContent.split('&').forEach(function(x){var p=x.trim();if(p&&!stats[p])stats[p]=[0,0];});});document.querySelectorAll('.rop-game-team').forEach(function(t){var won=t.classList.contains('winner');var names=t.querySelector('strong');if(!names)return;names.textContent.split('&').forEach(function(x){var p=x.trim();if(stats[p])stats[p][won?0:1]++;});});document.querySelectorAll('.rop-table').forEach(function(table){var heads=Array.from(table.querySelectorAll('th')).map(function(h){return h.textContent.trim();});var recordIndex=heads.indexOf('Record');if(recordIndex<0)return;table.querySelectorAll('tbody tr').forEach(function(row){var cells=row.children;if(cells.length<=recordIndex)return;var name=(cells[1].textContent||'').replace('♛','').trim();if(stats[name])cells[recordIndex].textContent=stats[name][0]+'–'+stats[name][1];});});document.querySelectorAll('a,button,input[type=submit]').forEach(function(el){var label=(el.textContent||el.value||'').trim();if(/^(accept invitation|accept invite|join group)$/i.test(label))el.classList.add('rop-invite-accept');});});document.addEventListener('click',function(e){var b=e.target.closest('[data-rop-toggle]');if(!b)return;var f=document.querySelector('[data-rop-form]');f.hidden=!f.hidden;b.textContent=f.hidden?'＋ SCHEDULE':'× CLOSE';if(!f.hidden)f.querySelector('input[name=date]').focus();});");
    wp_add_inline_script('rallyop-sessions', "(function(){function run(){var stats={};document.querySelectorAll('.rop-game-team strong').forEach(function(n){n.textContent.split('&').forEach(function(x){var p=x.trim();if(p&&!stats[p])stats[p]=[0,0];});});document.querySelectorAll('.rop-game-team').forEach(function(t){var n=t.querySelector('strong');if(!n)return;n.textContent.split('&').forEach(function(x){var p=x.trim();if(stats[p])stats[p][t.classList.contains('winner')?0:1]++;});});document.querySelectorAll('.rop-table').forEach(function(t){var h=Array.from(t.querySelectorAll('th')).map(function(x){return x.textContent.trim();}),i=h.indexOf('Record');if(i<0)return;t.querySelectorAll('tbody tr').forEach(function(r){var c=r.children,n=(c[1]&&c[1].textContent||'').replace('♛','').trim();if(c[i]&&stats[n])c[i].textContent=stats[n][0]+'–'+stats[n][1];});});document.querySelectorAll('a,button,input[type=submit]').forEach(function(e){var l=(e.textContent||e.value||'').trim();if(/^(accept invitation|accept invite|join group)$/i.test(l))e.classList.add('rop-invite-accept');});}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',run);else run();setTimeout(run,300);setTimeout(run,1200);})();");
    wp_add_inline_script('rallyop-sessions', "(function(){function esc(s){return String(s).replace(/[&<>\"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',\"'\":'&#039;'}[c];});}function pct(w,l){return w+l?Math.round(w*100/(w+l))+'%':'—';}function render(){var box=document.getElementById('rallyop-performance');if(!box)return;var footer=document.querySelector('footer'),sessions=document.getElementById('open-play-sessions');if(footer&&box.parentNode===document.body)footer.parentNode.insertBefore(box,sessions||footer);var ratings={};document.querySelectorAll('.rop-table').forEach(function(t){var h=Array.from(t.querySelectorAll('th')).map(function(x){return x.textContent.trim();});if(h.indexOf('Rating')<0)return;t.querySelectorAll('tbody tr').forEach(function(r){var c=r.children,n=(c[1]&&c[1].textContent||'').replace('♛','').trim();if(n)ratings[n]=parseInt(c[c.length-1].textContent,10)||1200;});});var players={},pairs={},series={},games=[];document.querySelectorAll('.rop-game-date').forEach(function(d){var row=d.closest('tr'),teams=row?row.querySelectorAll('.rop-game-team'):[];if(teams.length!==2)return;var a=Array.from(teams[0].querySelector('strong').textContent.split('&')).map(x=>x.trim()),b=Array.from(teams[1].querySelector('strong').textContent.split('&')).map(x=>x.trim()),sa=parseInt((teams[0].querySelector('span').textContent.match(/·\\s*(\\d+)/)||[])[1],10),sb=parseInt((teams[1].querySelector('span').textContent.match(/·\\s*(\\d+)/)||[])[1],10);if(!Number.isFinite(sa)||!Number.isFinite(sb))return;var aw=teams[0].classList.contains('winner'),date=new Date(d.textContent.trim()+' '+new Date().getFullYear());games.push({a:a,b:b,sa:sa,sb:sb,aw:aw,date:date});});games.forEach(function(g){[[g.a,g.sa,g.sb,g.aw],[g.b,g.sb,g.sa,!g.aw]].forEach(function(x){x[0].forEach(function(n){if(!players[n])players[n]={w:0,l:0,pf:0,pa:0,form:[]};players[n][x[3]?'w':'l']++;players[n].pf+=x[1];players[n].pa+=x[2];players[n].form.push(x[3]?'W':'L');});var key=x[0].slice().sort().join(' & ');if(!pairs[key])pairs[key]={w:0,l:0,pf:0,pa:0};pairs[key][x[3]?'w':'l']++;pairs[key].pf+=x[1];pairs[key].pa+=x[2];});var ak=g.a.slice().sort().join(' & '),bk=g.b.slice().sort().join(' & '),sk=[ak,bk].sort(),key=sk.join(' vs ');if(!series[key])series[key]={teams:sk,w:[0,0],diff:0,games:0};var ai=sk.indexOf(ak);series[key].games++;series[key].w[g.aw?ai:1-ai]++;series[key].diff+=(ai===0?g.sa-g.sb:g.sb-g.sa);});var pbody=box.querySelector('[data-rop-player-stats]'),prows=Object.keys(players).sort(function(a,b){return players[b].w-players[a].w||(players[b].pf-players[b].pa)-(players[a].pf-players[a].pa);}).map(function(n){var s=players[n],form=s.form.slice(0,5).map(function(x){return '<i class=\"rop-form-dot '+x.toLowerCase()+'\">'+x+'</i>';}).join('');return '<tr><td><strong>'+esc(n)+'</strong></td><td>'+s.w+'–'+s.l+'</td><td>'+pct(s.w,s.l)+'</td><td>'+((s.pf-s.pa)>0?'+':'')+(s.pf-s.pa)+'</td><td>'+form+'</td></tr>';}).join('');pbody.innerHTML=prows||'<tr><td colspan=\"5\" class=\"rop-empty-row\">Record a game to unlock stats.</td></tr>';var tbody=box.querySelector('[data-rop-partners]'),pairrows=Object.keys(pairs).sort(function(a,b){return pairs[b].w-pairs[a].w;}).map(function(n){var s=pairs[n];return '<tr><td><strong>'+esc(n)+'</strong></td><td>'+s.w+'–'+s.l+'</td><td>'+pct(s.w,s.l)+'</td><td>'+((s.pf-s.pa)>0?'+':'')+(s.pf-s.pa)+'</td></tr>';}).join('');tbody.innerHTML=pairrows||'<tr><td colspan=\"4\" class=\"rop-empty-row\">No partnerships yet.</td></tr>';var hbody=box.querySelector('[data-rop-headtohead]'),hrows=Object.keys(series).map(function(k){var s=series[k];return '<tr><td>'+esc(k)+'</td><td>'+s.games+'</td><td>'+s.w[0]+'–'+s.w[1]+'</td><td>'+((s.diff)>0?'+':'')+s.diff+'</td></tr>';}).join('');hbody.innerHTML=hrows||'<tr><td colspan=\"4\" class=\"rop-empty-row\">No matchups yet.</td></tr>';var names=Object.keys(players),most=names.sort(function(a,b){return players[b].w+players[b].l-(players[a].w+players[a].l);})[0]||'—',weekly={},cut=Date.now()-7*86400000;games.filter(g=>g.date.getTime()>=cut).forEach(function(g){(g.aw?g.a:g.b).forEach(n=>weekly[n]=(weekly[n]||0)+1);});var champ=Object.keys(weekly).sort((a,b)=>weekly[b]-weekly[a])[0]||'—',streakName='—',streakMax=0;Object.keys(players).forEach(function(n){var run=0;players[n].form.slice().reverse().forEach(function(x){run=x==='W'?run+1:0;streakMax<run&&(streakMax=run,streakName=n+' · '+run);});});var upset='No upset yet',best=0;games.forEach(function(g){var wa=g.aw?g.a:g.b,la=g.aw?g.b:g.a,wr=wa.reduce((s,n)=>s+(ratings[n]||1200),0)/wa.length,lr=la.reduce((s,n)=>s+(ratings[n]||1200),0)/la.length;if(lr-wr>best){best=Math.round(lr-wr);upset=wa.join(' & ')+' · +'+best;}});var cards=box.querySelectorAll('[data-rop-achievements]>div strong');if(cards.length===5){cards[0].textContent=champ;cards[1].textContent=streakName;cards[2].textContent=upset;cards[4].textContent=most;}var lead=Object.keys(players).sort(function(a,b){return players[b].w-players[a].w||(players[b].pf-players[b].pa)-(players[a].pf-players[a].pa);})[0]||'';var input=box.querySelector('[data-rop-champion]');if(input)input.value=lead;}setTimeout(render,350);setTimeout(render,1400);})();");
    wp_add_inline_style('rallyop-sessions', '.rop-comeback-form{display:inline}.rop-comeback-btn{margin-left:6px!important;padding:6px 8px!important;border:1px solid #087cff!important;background:transparent!important;color:#087cff!important;font-size:9px!important;font-weight:900!important}.rop-comeback-btn.active{background:#087cff!important;color:#fff!important}');
    wp_add_inline_script('rallyop-sessions', "(function(){function addComebacks(){var box=document.getElementById('rallyop-performance');if(!box)return;var marked=(box.dataset.comebacks||'').split(',').filter(Boolean),winner='Not marked yet';document.querySelectorAll('.rop-game-date').forEach(function(d){var row=d.closest('tr'),edit=row&&row.nextElementSibling,gameInput=edit&&edit.querySelector('input[name=game_id]'),cell=row&&row.lastElementChild;if(!gameInput||!cell||cell.querySelector('.rop-comeback-form'))return;var id=gameInput.value,isMarked=marked.indexOf(id)>=0,win=row.querySelector('.rop-game-team.winner strong');if(isMarked&&win)winner=win.textContent.trim();var form=document.createElement('form');form.className='rop-comeback-form';form.method='post';form.action=box.dataset.adminPost;[['action','rallyop_toggle_comeback'],['game_id',id],['group_id',box.dataset.groupId],['_wpnonce',box.dataset.comebackNonce]].forEach(function(v){var i=document.createElement('input');i.type='hidden';i.name=v[0];i.value=v[1];form.appendChild(i);});var b=document.createElement('button');b.type='submit';b.className='rop-comeback-btn'+(isMarked?' active':'');b.textContent=isMarked?'COMEBACK ✓':'MARK COMEBACK';form.appendChild(b);cell.appendChild(form);});var cards=box.querySelectorAll('[data-rop-achievements]>div strong');if(cards.length===5)cards[3].textContent=winner;}setTimeout(addComebacks,500);setTimeout(addComebacks,1600);})();");
});

add_action('wp_footer', function() {
    if (!is_front_page() || !is_user_logged_in()) { return; }
    ?>
    <style id="rallyop-profile-layout-fix">
      section.rop-profile:not([data-rop-profile]){position:relative!important;inset:auto!important;width:100%!important;height:auto!important;overflow:visible!important;padding:0!important;background:transparent!important;color:inherit!important;box-shadow:none!important}
      aside.rop-profile[data-rop-profile]{position:fixed!important;top:var(--wp-admin--admin-bar--height,0px)!important;right:0!important;bottom:auto!important;left:auto!important;z-index:99999!important;display:block!important;width:min(640px,calc(100vw - 24px))!important;max-width:640px!important;height:calc(100dvh - var(--wp-admin--admin-bar--height,0px))!important;max-height:none!important;margin:0!important;padding:38px!important;overflow-x:hidden!important;overflow-y:auto!important;background:#f7f3e8!important;color:#17251d!important;box-shadow:-18px 0 60px rgba(0,0,0,.32)!important}
      aside.rop-profile[data-rop-profile][hidden]{display:none!important}
      aside.rop-profile[data-rop-profile] .rop-profile-hero{display:grid!important;grid-template-columns:92px minmax(0,1fr)!important;gap:20px!important;align-items:center!important;padding:10px 52px 26px 0!important}
      aside.rop-profile[data-rop-profile] .rop-profile-hero>div:last-child{min-width:0!important}
      aside.rop-profile[data-rop-profile] .rop-profile-hero h2{overflow-wrap:anywhere!important;font-size:clamp(36px,7vw,52px)!important}
      aside.rop-profile[data-rop-profile] .rop-profile-metrics{grid-template-columns:repeat(2,minmax(0,1fr))!important}
      aside.rop-profile[data-rop-profile] .rop-profile-columns{grid-template-columns:repeat(2,minmax(0,1fr))!important}
      aside.rop-profile[data-rop-profile] .rop-profile-opponent{gap:12px!important;align-items:start!important}
      aside.rop-profile[data-rop-profile] .rop-profile-opponent strong{min-width:0!important;overflow-wrap:anywhere!important;font-size:16px!important}
      aside.rop-profile[data-rop-profile] .rop-profile-opponent span{flex:none!important;text-align:right!important;font-size:12px!important}
      .rop-profile-backdrop:not([hidden]){display:block!important;position:fixed!important;inset:0!important;z-index:99990!important;background:rgba(7,18,12,.72)!important;backdrop-filter:blur(4px)!important}
      @media(max-width:600px){aside.rop-profile[data-rop-profile]{top:0!important;width:100vw!important;max-width:none!important;height:100dvh!important;padding:24px 18px 34px!important}aside.rop-profile[data-rop-profile] .rop-profile-hero{grid-template-columns:68px minmax(0,1fr)!important;gap:14px!important;padding:24px 44px 20px 0!important}aside.rop-profile[data-rop-profile] .rop-profile-hero h2{font-size:34px!important}aside.rop-profile[data-rop-profile] .rop-profile-avatar{width:68px!important;height:68px!important;font-size:28px!important}aside.rop-profile[data-rop-profile] .rop-profile-columns{grid-template-columns:1fr!important}.rop-profile-close{position:fixed!important;top:12px!important;right:12px!important}}
    </style>
    <script id="rallyop-route-guard">
    document.addEventListener('click',function(e){
      var groupLink=e.target.closest('a[href*="?group="]');
      if(groupLink){e.preventDefault();e.stopImmediatePropagation();var profile=document.querySelector('[data-rop-profile]'),backdrop=document.querySelector('.rop-profile-backdrop');if(profile)profile.hidden=true;if(backdrop)backdrop.hidden=true;document.body.style.overflow='';window.location.assign(groupLink.href);return;}
      var player=e.target.closest('.rop-player-link');
      if(player&&!(player.tagName==='BUTTON'&&player.hasAttribute('data-player'))){e.stopImmediatePropagation();e.preventDefault();}
      if(player&&player.tagName==='BUTTON'&&player.hasAttribute('data-player')){document.body.style.overflow='hidden';}
    },true);
    </script>
    <?php
}, 99);

add_action('wp_footer', function() {
    if (!is_front_page() || !is_user_logged_in()) { return; }
    ?>
    <script id="rallyop-stats-live-repair">
    (function(){
      var timer;
      function cell(row,text,tag){var td=document.createElement('td'),node=tag?document.createElement(tag):null;if(node){node.textContent=text;td.appendChild(node);}else td.textContent=text;row.appendChild(td);return td;}
      function rate(w,l){return w+l?Math.round(w*100/(w+l))+'%':'—';}
      function repair(){
        var box=document.getElementById('rallyop-performance');if(!box)return;
        var games=[];
        document.querySelectorAll('.rop-game-date').forEach(function(date){
          var row=date.closest('tr'),teams=row&&row.querySelectorAll('.rop-game-team');if(!teams||teams.length!==2)return;
          function read(team){var strong=team.querySelector('strong'),score=team.querySelector('span');if(!strong||!score)return null;var nums=score.textContent.match(/\d+/g)||[],points=nums.length?parseInt(nums[nums.length-1],10):NaN;return{names:strong.textContent.split('&').map(function(n){return n.trim();}).filter(Boolean),score:points,won:team.classList.contains('winner')};}
          var a=read(teams[0]),b=read(teams[1]);if(a&&b&&a.names.length&&b.names.length&&Number.isFinite(a.score)&&Number.isFinite(b.score))games.push({a:a,b:b});
        });
        if(!games.length)return;
        var players={},pairs={},series={};
        games.forEach(function(g){
          [[g.a,g.b],[g.b,g.a]].forEach(function(sides){var mine=sides[0],other=sides[1];mine.names.forEach(function(n){var p=players[n]||(players[n]={w:0,l:0,pf:0,pa:0,form:[]});p[mine.won?'w':'l']++;p.pf+=mine.score;p.pa+=other.score;p.form.push(mine.won?'W':'L');});var key=mine.names.slice().sort().join(' & '),pair=pairs[key]||(pairs[key]={w:0,l:0,pf:0,pa:0});pair[mine.won?'w':'l']++;pair.pf+=mine.score;pair.pa+=other.score;});
          var ak=g.a.names.slice().sort().join(' & '),bk=g.b.names.slice().sort().join(' & '),ordered=[ak,bk].sort(),key=ordered.join(' vs '),s=series[key]||(series[key]={w:[0,0],games:0,diff:0}),ai=ordered.indexOf(ak);s.games++;s.w[g.a.won?ai:1-ai]++;s.diff+=ai===0?g.a.score-g.b.score:g.b.score-g.a.score;
        });
        var pb=box.querySelector('[data-rop-player-stats]');if(pb){pb.textContent='';Object.keys(players).sort(function(a,b){return players[b].w-players[a].w;}).forEach(function(n){var p=players[n],r=document.createElement('tr');cell(r,n,'strong');cell(r,p.w+'–'+p.l);cell(r,rate(p.w,p.l));cell(r,(p.pf-p.pa>0?'+':'')+(p.pf-p.pa));var recent=cell(r,'');p.form.slice(-5).forEach(function(x){var i=document.createElement('i');i.className='rop-form-dot '+x.toLowerCase();i.textContent=x;recent.appendChild(i);});pb.appendChild(r);});}
        var pairBody=box.querySelector('[data-rop-partners]');if(pairBody){pairBody.textContent='';Object.keys(pairs).sort(function(a,b){return pairs[b].w-pairs[a].w;}).forEach(function(n){var p=pairs[n],r=document.createElement('tr');cell(r,n,'strong');cell(r,p.w+'–'+p.l);cell(r,rate(p.w,p.l));cell(r,(p.pf-p.pa>0?'+':'')+(p.pf-p.pa));pairBody.appendChild(r);});}
        var head=box.querySelector('[data-rop-headtohead]');if(head){head.textContent='';Object.keys(series).forEach(function(n){var s=series[n],r=document.createElement('tr');cell(r,n);cell(r,s.games);cell(r,s.w[0]+'–'+s.w[1]);cell(r,(s.diff>0?'+':'')+s.diff);head.appendChild(r);});}
      }
      function schedule(){clearTimeout(timer);timer=setTimeout(repair,60);}
      if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',schedule);else schedule();window.addEventListener('load',schedule);setTimeout(schedule,500);setTimeout(schedule,1600);setTimeout(schedule,3500);
      function connect(){var game=document.querySelector('.rop-game-date'),table=game&&game.closest('table');if(table&&window.MutationObserver&&!table.dataset.ropStatsWatch){table.dataset.ropStatsWatch='1';new MutationObserver(schedule).observe(table,{childList:true,subtree:true,characterData:true});}}
      setTimeout(connect,600);setTimeout(connect,1800);setTimeout(connect,3600);
    })();
    </script>
    <?php
}, 130);

add_action('wp_footer', function() {
    if (!is_user_logged_in()) { return; }
    global $wpdb;
    $table = rallyop_companion_table('players');
    if (!$table) { return; }
    $group_id = absint($_GET['group'] ?? 0);
    $players = $group_id
        ? $wpdb->get_results($wpdb->prepare("SELECT * FROM `$table` WHERE group_id=%d ORDER BY id", $group_id))
        : $wpdb->get_results("SELECT * FROM `$table` ORDER BY id");
    $mine = null;
    foreach ($players as $player) { if (rallyop_companion_is_own_player($player)) { $mine = $player; break; } }
    if (!$mine) { return; }
    $mine_group = isset($mine->group_id) ? (int) $mine->group_id : max(1, $group_id);
    $profile_url = add_query_arg(['group' => $mine_group, 'player' => (string) $mine->name], home_url('/')) . '#player-profile';
    ?>
    <style id="rallyop-account-profile-link-style">
      .header-account .rop-account-profile-link{color:inherit!important;font-weight:800!important;text-decoration:underline!important;text-decoration-color:#087cff!important;text-decoration-thickness:2px!important;text-underline-offset:4px!important}.header-account .rop-account-profile-link:hover{color:#087cff!important}
      .rop-mobile-account .rop-account-profile-link{display:block!important;min-width:0!important;padding:10px 0!important;border:0!important;color:#fff!important;font-size:12px!important;text-transform:none!important;overflow-wrap:anywhere!important}
    </style>
    <script id="rallyop-account-profile-link-script">
    (function(){var account=document.querySelector('.site-header .header-account'),label=account&&account.querySelector('span');if(!label)return;var link=document.createElement('a');link.className='rop-account-profile-link';link.href=<?php echo wp_json_encode($profile_url); ?>;link.textContent=label.textContent.trim();link.setAttribute('aria-label','Open my player profile');label.replaceWith(link);})();
    </script>
    <?php
}, 135);

add_action('wp_footer', function() {
    ?>
    <style id="rallyop-mobile-menu-styles">
      .rop-menu-toggle,.rop-mobile-menu{display:none}
      @media(max-width:900px){
        .site-header{position:relative!important;z-index:10000!important;box-sizing:border-box!important;width:100%!important;min-height:76px!important;padding:12px 16px!important;justify-content:space-between!important;gap:14px!important}
        .site-header>a{display:flex!important;align-items:center!important;min-width:0!important}
        .site-header .site-logo{width:auto!important;max-width:min(210px,65vw)!important;height:auto!important}
        .site-header>.site-nav,.site-header>.header-account{display:none!important}
        .rop-menu-toggle{display:grid!important;place-items:center!important;width:48px!important;height:48px!important;flex:0 0 48px!important;padding:0!important;border:2px solid #17251d!important;background:#ccff3f!important;color:#17251d!important;box-shadow:4px 4px 0 #17251d!important;cursor:pointer!important}
        .rop-menu-toggle-lines,.rop-menu-toggle-lines::before,.rop-menu-toggle-lines::after{display:block;width:23px;height:3px;background:currentColor;content:'';transition:transform .18s,opacity .18s}
        .rop-menu-toggle-lines{position:relative}.rop-menu-toggle-lines::before{position:absolute;top:-7px}.rop-menu-toggle-lines::after{position:absolute;top:7px}
        .rop-menu-toggle[aria-expanded="true"] .rop-menu-toggle-lines{background:transparent}.rop-menu-toggle[aria-expanded="true"] .rop-menu-toggle-lines::before{top:0;transform:rotate(45deg)}.rop-menu-toggle[aria-expanded="true"] .rop-menu-toggle-lines::after{top:0;transform:rotate(-45deg)}
        .rop-mobile-menu{position:absolute!important;top:calc(100% + 1px)!important;right:12px!important;left:12px!important;z-index:10001!important;box-sizing:border-box!important;padding:12px!important;background:#17271e!important;border:1px solid #30473a!important;box-shadow:0 18px 35px rgba(0,0,0,.32)!important}
        .rop-mobile-menu.is-open{display:block!important}
        .rop-mobile-menu a{display:block!important;padding:15px 14px!important;border-bottom:1px solid #3c5045!important;color:#fff!important;font-size:18px!important;font-weight:900!important;line-height:1.1!important;letter-spacing:.02em!important;text-decoration:none!important;text-transform:uppercase!important}
        .rop-mobile-menu a:hover,.rop-mobile-menu a:focus{background:#087cff!important;color:#fff!important}
        .rop-mobile-account{display:flex!important;align-items:center!important;justify-content:space-between!important;gap:12px!important;padding:13px 14px 2px!important;color:#b9c5bd!important;font-size:12px!important;overflow-wrap:anywhere!important}
        .rop-mobile-account a{flex:none!important;padding:10px 12px!important;border:1px solid #ccff3f!important;color:#ccff3f!important;font-size:12px!important}
      }
    </style>
    <script id="rallyop-mobile-menu-script">
    (function(){
      var header=document.querySelector('.site-header'),nav=header&&header.querySelector('.site-nav');if(!header||!nav||header.querySelector('.rop-menu-toggle'))return;
      var button=document.createElement('button');button.type='button';button.className='rop-menu-toggle';button.setAttribute('aria-expanded','false');button.setAttribute('aria-controls','rop-mobile-menu');button.setAttribute('aria-label','Open menu');button.innerHTML='<span class="rop-menu-toggle-lines" aria-hidden="true"></span>';
      var menu=document.createElement('div');menu.id='rop-mobile-menu';menu.className='rop-mobile-menu';menu.setAttribute('aria-hidden','true');
      nav.querySelectorAll('a').forEach(function(link){menu.appendChild(link.cloneNode(true));});
      var account=header.querySelector('.header-account');if(account){var mobileAccount=document.createElement('div');mobileAccount.className='rop-mobile-account';Array.from(account.childNodes).forEach(function(node){mobileAccount.appendChild(node.cloneNode(true));});menu.appendChild(mobileAccount);}
      header.appendChild(button);header.appendChild(menu);
      function close(){button.setAttribute('aria-expanded','false');button.setAttribute('aria-label','Open menu');menu.setAttribute('aria-hidden','true');menu.classList.remove('is-open');document.body.classList.remove('rop-menu-open');}
      button.addEventListener('click',function(){var open=button.getAttribute('aria-expanded')!=='true';if(open){button.setAttribute('aria-expanded','true');button.setAttribute('aria-label','Close menu');menu.setAttribute('aria-hidden','false');menu.classList.add('is-open');document.body.classList.add('rop-menu-open');}else close();});
      menu.addEventListener('click',function(e){if(e.target.closest('a'))close();});document.addEventListener('keydown',function(e){if(e.key==='Escape')close();});document.addEventListener('click',function(e){if(menu.classList.contains('is-open')&&!header.contains(e.target))close();});
    })();
    </script>
    <?php
}, 140);
