<?php
if (!defined('ABSPATH')) { exit; }

function rallyop_community_install() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta("CREATE TABLE {$wpdb->prefix}rallyop_challenges (
      id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      group_id bigint(20) unsigned NOT NULL,
      created_by bigint(20) unsigned NOT NULL,
      challenger varchar(190) NOT NULL,
      opponent varchar(190) NOT NULL,
      note varchar(280) NOT NULL DEFAULT '',
      status varchar(16) NOT NULL DEFAULT 'pending',
      created_at datetime NOT NULL,
      updated_at datetime NOT NULL,
      PRIMARY KEY (id), KEY group_status (group_id,status)
    ) $charset;");
    dbDelta("CREATE TABLE {$wpdb->prefix}rallyop_game_reactions (
      game_id bigint(20) unsigned NOT NULL,
      group_id bigint(20) unsigned NOT NULL,
      user_id bigint(20) unsigned NOT NULL,
      reaction varchar(12) NOT NULL,
      updated_at datetime NOT NULL,
      PRIMARY KEY (game_id,user_id), KEY group_game (group_id,game_id)
    ) $charset;");
    dbDelta("CREATE TABLE {$wpdb->prefix}rallyop_game_comments (
      id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      game_id bigint(20) unsigned NOT NULL,
      group_id bigint(20) unsigned NOT NULL,
      user_id bigint(20) unsigned NOT NULL,
      display_name varchar(120) NOT NULL,
      comment varchar(500) NOT NULL,
      created_at datetime NOT NULL,
      PRIMARY KEY (id), KEY group_game (group_id,game_id)
    ) $charset;");
    update_option('rallyop_community_db_version', '1.0.0');
}
add_action('plugins_loaded', function(){ if (get_option('rallyop_community_db_version') !== '1.0.0') { rallyop_community_install(); } });

function rallyop_group_players($group_id) {
    global $wpdb;
    $table = rallyop_companion_table('players');
    return $table ? $wpdb->get_results($wpdb->prepare("SELECT * FROM `$table` WHERE group_id=%d ORDER BY name", $group_id)) : [];
}
function rallyop_my_group_player($group_id) {
    foreach (rallyop_group_players($group_id) as $player) { if (rallyop_companion_is_own_player($player)) { return $player; } }
    return null;
}
function rallyop_can_access_group($group_id) { return rallyop_companion_can_manage_group($group_id) || (bool) rallyop_my_group_player($group_id); }
function rallyop_community_redirect($group_id) { wp_safe_redirect(add_query_arg('group', $group_id, home_url('/')) . '#rallyop-community'); exit; }

add_action('admin_post_rallyop_create_challenge', function(){
    if (!is_user_logged_in()) { auth_redirect(); }
    $group_id=max(1,absint($_POST['group_id']??1));check_admin_referer('rallyop_create_challenge_'.$group_id);
    if (!rallyop_can_access_group($group_id)) { wp_die('You are not a member of this group.','RallyOP',['response'=>403]); }
    $players=rallyop_group_players($group_id);$names=[];foreach($players as $p){$names[]=(string)$p->name;}
    $values=[];foreach(['challenger_1','challenger_2','opponent_1','opponent_2'] as $key){$name=sanitize_text_field(wp_unslash($_POST[$key]??''));if(!in_array($name,$names,true))rallyop_community_redirect($group_id);$values[]=$name;}
    if(count(array_unique($values))!==4)rallyop_community_redirect($group_id);
    global $wpdb;$now=current_time('mysql');$wpdb->insert($wpdb->prefix.'rallyop_challenges',['group_id'=>$group_id,'created_by'=>get_current_user_id(),'challenger'=>$values[0].' & '.$values[1],'opponent'=>$values[2].' & '.$values[3],'note'=>substr(sanitize_text_field(wp_unslash($_POST['note']??'')),0,280),'status'=>'pending','created_at'=>$now,'updated_at'=>$now],['%d','%d','%s','%s','%s','%s','%s','%s']);
    rallyop_community_redirect($group_id);
});

add_action('admin_post_rallyop_challenge_status', function(){
    if(!is_user_logged_in()){auth_redirect();}$id=absint($_POST['challenge_id']??0);check_admin_referer('rallyop_challenge_status_'.$id);global $wpdb;$table=$wpdb->prefix.'rallyop_challenges';$c=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d",$id));if(!$c||!rallyop_can_access_group((int)$c->group_id))wp_die('Not allowed.','RallyOP',['response'=>403]);
    $mine=rallyop_my_group_player((int)$c->group_id);$may=rallyop_companion_can_manage_group((int)$c->group_id)||($mine&&strpos(' & '.$c->opponent.' & ',' & '.$mine->name.' & ')!==false);if(!$may)wp_die('Only a challenged player or group owner can respond.','RallyOP',['response'=>403]);$status=sanitize_key($_POST['status']??'');if(in_array($status,['accepted','declined'],true))$wpdb->update($table,['status'=>$status,'updated_at'=>current_time('mysql')],['id'=>$id],['%s','%s'],['%d']);rallyop_community_redirect((int)$c->group_id);
});

add_action('admin_post_rallyop_react_game', function(){
    if(!is_user_logged_in()){auth_redirect();}$group_id=max(1,absint($_POST['group_id']??1));$game_id=absint($_POST['game_id']??0);check_admin_referer('rallyop_react_game_'.$group_id);if(!$game_id||!rallyop_can_access_group($group_id))wp_die('Not allowed.','RallyOP',['response'=>403]);$reaction=sanitize_text_field(wp_unslash($_POST['reaction']??''));if(in_array($reaction,['🔥','👏','🥒'],true)){global $wpdb;$wpdb->replace($wpdb->prefix.'rallyop_game_reactions',['game_id'=>$game_id,'group_id'=>$group_id,'user_id'=>get_current_user_id(),'reaction'=>$reaction,'updated_at'=>current_time('mysql')],['%d','%d','%d','%s','%s']);}rallyop_community_redirect($group_id);
});

add_action('admin_post_rallyop_comment_game', function(){
    if(!is_user_logged_in()){auth_redirect();}$group_id=max(1,absint($_POST['group_id']??1));$game_id=absint($_POST['game_id']??0);check_admin_referer('rallyop_comment_game_'.$group_id);if(!$game_id||!rallyop_can_access_group($group_id))wp_die('Not allowed.','RallyOP',['response'=>403]);$comment=trim(substr(sanitize_text_field(wp_unslash($_POST['comment']??'')),0,500));if($comment!==''){global $wpdb;$mine=rallyop_my_group_player($group_id);$name=$mine?(string)$mine->name:wp_get_current_user()->display_name;$wpdb->insert($wpdb->prefix.'rallyop_game_comments',['game_id'=>$game_id,'group_id'=>$group_id,'user_id'=>get_current_user_id(),'display_name'=>$name,'comment'=>$comment,'created_at'=>current_time('mysql')],['%d','%d','%d','%s','%s','%s']);}rallyop_community_redirect($group_id);
});

function rallyop_community_markup(){
    if(!is_user_logged_in())return'';global $wpdb;$group_id=max(1,absint($_GET['group']??1));if(!rallyop_can_access_group($group_id))return'';$players=rallyop_group_players($group_id);$mine=rallyop_my_group_player($group_id);$challenges=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rallyop_challenges WHERE group_id=%d ORDER BY created_at DESC LIMIT 20",$group_id));$reactions=$wpdb->get_results($wpdb->prepare("SELECT game_id,reaction,COUNT(*) count FROM {$wpdb->prefix}rallyop_game_reactions WHERE group_id=%d GROUP BY game_id,reaction",$group_id));$comments=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rallyop_game_comments WHERE group_id=%d ORDER BY created_at ASC LIMIT 100",$group_id));$reaction_map=[];foreach($reactions as $r){$reaction_map[(int)$r->game_id][$r->reaction]=(int)$r->count;}$comment_map=[];foreach($comments as $c){$comment_map[(int)$c->game_id][]= ['name'=>$c->display_name,'comment'=>$c->comment,'time'=>mysql2date('M j · g:i A',$c->created_at)];}
    ob_start();?><section id="rallyop-community" class="rop-community" data-group="<?php echo esc_attr($group_id);?>" data-post="<?php echo esc_url(admin_url('admin-post.php'));?>" data-react-nonce="<?php echo esc_attr(wp_create_nonce('rallyop_react_game_'.$group_id));?>" data-comment-nonce="<?php echo esc_attr(wp_create_nonce('rallyop_comment_game_'.$group_id));?>" data-reactions="<?php echo esc_attr(wp_json_encode($reaction_map));?>" data-comments="<?php echo esc_attr(wp_json_encode($comment_map));?>">
      <div class="rop-kicker">THE CONVERSATION</div><div class="rop-heading-row"><div><h2>Clubhouse Activity</h2><p>Results, challenges, reactions, and friendly court talk.</p></div></div><div class="rop-community-grid"><div><h3>Activity feed</h3><div class="rop-feed" data-rop-feed><?php foreach($challenges as $c):?><article class="rop-feed-card rop-challenge-event"><span>CHALLENGE · <?php echo esc_html(strtoupper($c->status));?></span><strong><?php echo esc_html($c->challenger);?> vs <?php echo esc_html($c->opponent);?></strong><?php if($c->note):?><p><?php echo esc_html($c->note);?></p><?php endif;?><small><?php echo esc_html(mysql2date('M j · g:i A',$c->created_at));?></small></article><?php endforeach;?></div></div>
      <aside class="rop-challenges"><h3>Friendly challenges</h3><?php if(count($players)>=4):?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>" class="rop-challenge-form"><input type="hidden" name="action" value="rallyop_create_challenge"><input type="hidden" name="group_id" value="<?php echo esc_attr($group_id);?>"><?php wp_nonce_field('rallyop_create_challenge_'.$group_id);?><?php foreach(['challenger_1'=>'YOUR TEAM','challenger_2'=>'YOUR PARTNER','opponent_1'=>'OPPONENT 1','opponent_2'=>'OPPONENT 2'] as $key=>$label):?><label><?php echo esc_html($label);?><select name="<?php echo esc_attr($key);?>" required><option value="">Select player</option><?php foreach($players as $p):?><option value="<?php echo esc_attr($p->name);?>" <?php selected($key==='challenger_1'&&$mine&&(int)$mine->id===(int)$p->id);?>><?php echo esc_html($p->name);?></option><?php endforeach;?></select></label><?php endforeach;?><label>COURT TALK<input name="note" maxlength="280" placeholder="Winner buys the next round?"></label><button class="rop-primary">SEND CHALLENGE</button></form><?php else:?><p>Add at least four players to create a doubles challenge.</p><?php endif;?>
      <div class="rop-pending-challenges"><?php foreach($challenges as $c):if($c->status!=='pending')continue;$can_reply=rallyop_companion_can_manage_group($group_id)||($mine&&strpos(' & '.$c->opponent.' & ',' & '.$mine->name.' & ')!==false);?><div><strong><?php echo esc_html($c->challenger);?></strong><span>vs <?php echo esc_html($c->opponent);?></span><?php if($can_reply):?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="rallyop_challenge_status"><input type="hidden" name="challenge_id" value="<?php echo esc_attr($c->id);?>"><?php wp_nonce_field('rallyop_challenge_status_'.$c->id);?><button name="status" value="accepted">ACCEPT</button><button name="status" value="declined">DECLINE</button></form><?php endif;?></div><?php endforeach;?></div></aside></div></section><?php return ob_get_clean();
}

add_action('wp_footer',function(){if(is_front_page()&&is_user_logged_in())echo rallyop_community_markup();},4);
add_action('wp_footer',function(){if(!is_front_page()||!is_user_logged_in())return;?>
<style id="rallyop-community-styles">.rop-community{max-width:1180px;margin:28px auto;padding:36px;box-sizing:border-box;background:#f7f3e8;color:#17251d}.rop-community h2{margin:8px 0;font-size:clamp(34px,5vw,56px)}.rop-community h3{font-size:25px}.rop-community-grid{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(300px,.8fr);gap:22px}.rop-feed{display:grid;gap:12px}.rop-feed-card{padding:18px;border:1px solid #d8d3c7;background:#fff}.rop-feed-card>span{display:block;margin-bottom:8px;color:#db532d;font-size:10px;font-weight:900;letter-spacing:.1em}.rop-feed-card>strong{display:block;font-size:19px}.rop-feed-card p{margin:8px 0}.rop-feed-card small{color:#68736c}.rop-game-engagement{margin-top:14px;padding-top:12px;border-top:1px solid #e0ddd5}.rop-reactions{display:flex;gap:7px;flex-wrap:wrap}.rop-reactions form{margin:0}.rop-reactions button{padding:7px 10px;border:1px solid #c9c6bd;background:#f7f3e8;cursor:pointer}.rop-comments{margin-top:10px}.rop-comment{padding:7px 0;border-bottom:1px solid #ece8df;font-size:13px}.rop-comment strong{margin-right:7px}.rop-comment small{display:block}.rop-comment-form{display:flex;gap:7px;margin-top:9px}.rop-comment-form input{min-width:0;flex:1;padding:10px;border:1px solid #aaa}.rop-comment-form button,.rop-pending-challenges button{padding:9px 11px;border:0;background:#087cff;color:#fff;font-weight:900}.rop-challenges{padding:22px;background:#17271e;color:#fff}.rop-challenges h3{margin-top:0}.rop-challenge-form{display:grid;grid-template-columns:1fr 1fr;gap:10px}.rop-challenge-form label{font-size:10px;font-weight:900;letter-spacing:.08em}.rop-challenge-form select,.rop-challenge-form input{box-sizing:border-box;width:100%;margin-top:6px;padding:11px}.rop-challenge-form label:nth-last-of-type(1),.rop-challenge-form .rop-primary{grid-column:1/-1}.rop-pending-challenges{margin-top:20px}.rop-pending-challenges>div{padding:12px 0;border-top:1px solid #496052}.rop-pending-challenges strong,.rop-pending-challenges span{display:block}.rop-pending-challenges form{display:flex;gap:7px;margin-top:8px}.rop-pending-challenges button[value=declined]{background:#ef5a32}@media(max-width:850px){.rop-community{margin:16px 12px;padding:22px}.rop-community-grid{grid-template-columns:1fr}.rop-challenge-form{grid-template-columns:1fr}.rop-challenge-form label,.rop-challenge-form .rop-primary{grid-column:auto}}</style>
<script id="rallyop-community-script">(function(){function start(){var box=document.getElementById('rallyop-community'),feed=box&&box.querySelector('[data-rop-feed]');if(!box||!feed)return;var reactions=JSON.parse(box.dataset.reactions||'{}'),comments=JSON.parse(box.dataset.comments||'{}'),post=box.dataset.post,group=box.dataset.group;document.querySelectorAll('.rop-game-date').forEach(function(d){var row=d.closest('tr'),edit=row&&row.nextElementSibling,id=edit&&edit.querySelector('input[name=game_id]')&&edit.querySelector('input[name=game_id]').value;if(!id||feed.querySelector('[data-game="'+id+'"]'))return;var teams=row.querySelectorAll('.rop-game-team'),winner=row.querySelector('.rop-game-team.winner strong'),card=document.createElement('article');card.className='rop-feed-card';card.dataset.game=id;card.innerHTML='<span>GAME RESULT · '+d.textContent.trim()+'</span><strong>'+(winner?winner.textContent.trim()+' won':'Game recorded')+'</strong><p>'+Array.from(teams).map(function(t){return t.textContent.trim();}).join(' vs ')+'</p><div class="rop-game-engagement"><div class="rop-reactions"></div><div class="rop-comments"></div></div>';var rr=card.querySelector('.rop-reactions');['🔥','👏','🥒'].forEach(function(emoji){var f=document.createElement('form');f.method='post';f.action=post;f.innerHTML='<input type="hidden" name="action" value="rallyop_react_game"><input type="hidden" name="group_id" value="'+group+'"><input type="hidden" name="game_id" value="'+id+'"><input type="hidden" name="_wpnonce" value="'+box.dataset.reactNonce+'"><button name="reaction" value="'+emoji+'">'+emoji+' '+((reactions[id]&&reactions[id][emoji])||'')+'</button>';rr.appendChild(f);});var cc=card.querySelector('.rop-comments');(comments[id]||[]).forEach(function(c){var p=document.createElement('div');p.className='rop-comment';var strong=document.createElement('strong');strong.textContent=c.name;p.appendChild(strong);p.appendChild(document.createTextNode(c.comment));var small=document.createElement('small');small.textContent=c.time;p.appendChild(small);cc.appendChild(p);});var commentForm=document.createElement('form');commentForm.className='rop-comment-form';commentForm.method='post';commentForm.action=post;commentForm.innerHTML='<input type="hidden" name="action" value="rallyop_comment_game"><input type="hidden" name="group_id" value="'+group+'"><input type="hidden" name="game_id" value="'+id+'"><input type="hidden" name="_wpnonce" value="'+box.dataset.commentNonce+'"><input name="comment" maxlength="500" required placeholder="Add a friendly comment"><button>POST</button>';cc.appendChild(commentForm);feed.insertBefore(card,feed.firstChild);});}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();setTimeout(start,900);})();</script><?php },145);
