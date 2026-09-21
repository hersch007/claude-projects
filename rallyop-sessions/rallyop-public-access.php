<?php
if (!defined('ABSPATH')) { exit; }

function rop_public_pin_option($group_id) {
    return 'rop_recording_pin_' . absint($group_id);
}

function rop_public_ensure_pin($group_id) {
    $key = rop_public_pin_option($group_id);
    if (!get_option($key)) {
        add_option($key, wp_hash_password('2468'), '', false);
    }
}

function rop_public_redirect($group_id, $message = '') {
    $args = ['group' => absint($group_id)];
    if ($message !== '') { $args['rop_pin_notice'] = $message; }
    wp_safe_redirect(add_query_arg($args, home_url('/')) . '#clubhouse');
    exit;
}

function rop_public_add_game() {
    $group_id = absint($_POST['group_id'] ?? 0);
    if (!$group_id || !function_exists('rop_group') || !rop_group($group_id)) {
        wp_die('Group not found.', 'RallyOP', ['response' => 404]);
    }
    check_admin_referer('rop_public_add_game_' . $group_id);
    rop_public_ensure_pin($group_id);

    $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $rate_key = 'rop_pin_attempt_' . md5($group_id . '|' . $ip);
    $attempts = (int) get_transient($rate_key);
    if ($attempts >= 5) { rop_public_redirect($group_id, 'Too many PIN attempts. Try again in 10 minutes.'); }

    $pin = preg_replace('/\D+/', '', (string) ($_POST['group_pin'] ?? ''));
    if (!wp_check_password($pin, get_option(rop_public_pin_option($group_id)))) {
        set_transient($rate_key, $attempts + 1, 10 * MINUTE_IN_SECONDS);
        rop_public_redirect($group_id, 'That recording PIN is not correct.');
    }
    delete_transient($rate_key);

    global $wpdb;
    $tables = rop_tables();
    $ids = array_map('absint', [$_POST['a1'] ?? 0, $_POST['a2'] ?? 0, $_POST['b1'] ?? 0, $_POST['b2'] ?? 0]);
    $valid = $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM {$tables['players']} WHERE group_id=%d AND id IN (%d,%d,%d,%d)",
        $group_id, $ids[0], $ids[1], $ids[2], $ids[3]
    ));
    $score_a = absint($_POST['score_a'] ?? 0);
    $score_b = absint($_POST['score_b'] ?? 0);
    if (count(array_unique($ids)) !== 4 || count($valid) !== 4 || $score_a === $score_b) {
        rop_public_redirect($group_id, 'Choose four different players and enter a winning score.');
    }
    $wpdb->insert($tables['games'], [
        'group_id' => $group_id,
        'played_at' => current_time('mysql'),
        'team_a1' => $ids[0], 'team_a2' => $ids[1],
        'team_b1' => $ids[2], 'team_b2' => $ids[3],
        'score_a' => $score_a, 'score_b' => $score_b,
    ], ['%d','%s','%d','%d','%d','%d','%d','%d']);
    wp_safe_redirect(add_query_arg([
        'group' => $group_id,
        'rop_pin_notice' => 'Game saved.',
    ], home_url('/')) . '#standings');
    exit;
}
add_action('admin_post_nopriv_rop_public_add_game', 'rop_public_add_game');
add_action('admin_post_rop_public_add_game', 'rop_public_add_game');

function rop_public_set_pin() {
    if (!is_user_logged_in()) { auth_redirect(); }
    $group_id = absint($_POST['group_id'] ?? 0);
    check_admin_referer('rop_set_group_pin_' . $group_id);
    $group = function_exists('rop_group') ? rop_group($group_id) : null;
    if (!$group || (int) $group->owner_id !== get_current_user_id()) {
        wp_die('Only the group owner can change the recording PIN.', 'RallyOP', ['response' => 403]);
    }
    $pin = preg_replace('/\D+/', '', (string) ($_POST['group_pin'] ?? ''));
    if (strlen($pin) < 4 || strlen($pin) > 8) { rop_public_redirect($group_id, 'Use a 4–8 digit PIN.'); }
    update_option(rop_public_pin_option($group_id), wp_hash_password($pin), false);
    rop_public_redirect($group_id, 'Recording PIN updated.');
}
add_action('admin_post_rop_set_group_pin', 'rop_public_set_pin');

function rop_public_group_markup($group_id) {
    global $wpdb;
    $tables = rop_tables();
    $group = rop_group($group_id);
    if (!$group) { return ''; }
    rop_public_ensure_pin($group_id);
    $players = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$tables['players']} WHERE group_id=%d ORDER BY name", $group_id));
    $games = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$tables['games']} WHERE group_id=%d ORDER BY played_at DESC, id DESC", $group_id));
    $ranked = rop_rankings($players, $games);
    $names = [];
    foreach ($players as $player) { $names[$player->id] = $player->name; }
    ob_start();
    ?>
    <div class="rop-public-app" id="clubhouse">
      <header class="rop-public-head"><div><span>OPEN PLAY GROUP</span><h1><?php echo esc_html($group->name); ?></h1><p>Scores, standings, and court activity—no sign-in required.</p></div><a href="#record-game">+ RECORD GAME</a></header>
      <?php if (!empty($_GET['rop_pin_notice'])): ?><div class="rop-public-notice"><?php echo esc_html(wp_unslash($_GET['rop_pin_notice'])); ?></div><?php endif; ?>
      <nav class="rop-public-tabs" aria-label="Group sections"><a href="#record-game">Record</a><a href="#scores">Scores</a><a href="#standings">Standings</a></nav>
      <section id="record-game" class="rop-public-card rop-public-record"><div class="rop-public-kicker">QUICK ENTRY</div><h2>Record a game</h2>
        <?php if (count($players) >= 4): ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="rop_public_add_game"><input type="hidden" name="group_id" value="<?php echo esc_attr($group_id); ?>"><?php wp_nonce_field('rop_public_add_game_' . $group_id); ?>
          <div class="rop-public-teams"><fieldset><legend>Team 1</legend><?php rop_public_player_select('a1', $players); rop_public_player_select('a2', $players); ?><label>Score<input type="number" inputmode="numeric" name="score_a" min="0" value="11" required></label></fieldset>
          <b>VS</b><fieldset><legend>Team 2</legend><?php rop_public_player_select('b1', $players); rop_public_player_select('b2', $players); ?><label>Score<input type="number" inputmode="numeric" name="score_b" min="0" value="7" required></label></fieldset></div>
          <label class="rop-public-pin">GROUP PIN<input type="password" inputmode="numeric" pattern="[0-9]{4,8}" name="group_pin" minlength="4" maxlength="8" placeholder="4–8 digits" required></label><button type="submit">SAVE GAME</button>
        </form><?php else: ?><p>This group needs four players before games can be recorded.</p><?php endif; ?>
      </section>
      <section id="standings" class="rop-public-card"><div class="rop-public-kicker">CURRENT TABLE</div><h2>Standings</h2><div class="rop-public-table"><table><thead><tr><th>#</th><th>Player</th><th>W–L</th><th>Rating</th></tr></thead><tbody><?php foreach ($ranked as $index => $player): ?><tr><td><?php echo $index + 1; ?></td><td><strong><?php echo esc_html($player->name); ?></strong></td><td><?php echo esc_html($player->wins . '–' . $player->losses); ?></td><td><?php echo esc_html($player->rating); ?></td></tr><?php endforeach; ?></tbody></table></div></section>
      <section id="scores" class="rop-public-card"><div class="rop-public-kicker">LATEST FIRST</div><h2>Recent scores</h2><div class="rop-public-scores"><?php foreach ($games as $game): $a_win=$game->score_a>$game->score_b; ?><article><span><?php echo esc_html(mysql2date('M j', $game->played_at)); ?></span><div class="<?php echo $a_win?'winner':''; ?>"><strong><?php echo esc_html(($names[$game->team_a1] ?? 'Player') . ' & ' . ($names[$game->team_a2] ?? 'Player')); ?></strong><b><?php echo esc_html($game->score_a); ?></b></div><div class="<?php echo !$a_win?'winner':''; ?>"><strong><?php echo esc_html(($names[$game->team_b1] ?? 'Player') . ' & ' . ($names[$game->team_b2] ?? 'Player')); ?></strong><b><?php echo esc_html($game->score_b); ?></b></div></article><?php endforeach; ?><?php if (!$games): ?><p>No games recorded yet.</p><?php endif; ?></div></section>
    </div>
    <?php
    return ob_get_clean();
}

function rop_public_player_select($name, $players) {
    echo '<label>Player<select name="' . esc_attr($name) . '" required><option value="">Select player</option>';
    foreach ($players as $player) { echo '<option value="' . esc_attr($player->id) . '">' . esc_html($player->name) . '</option>'; }
    echo '</select></label>';
}

add_filter('pre_do_shortcode_tag', function ($return, $tag) {
    $owner_preview = is_user_logged_in() && isset($_GET['rop_public_preview']);
    if ($tag !== 'rallyop_app' || (is_user_logged_in() && !$owner_preview)) { return $return; }
    $group_id = absint($_GET['group'] ?? 0);
    return $group_id ? rop_public_group_markup($group_id) : $return;
}, 10, 2);

add_action('wp_footer', function () {
    if (!is_front_page()) { return; }
    ?>
    <style id="rop-public-mobile-styles">
    .rop-public-app{max-width:760px;margin:0 auto;padding:12px 12px 92px;color:#17251d}.rop-public-app *{box-sizing:border-box}.rop-public-head{padding:22px 18px;background:#173426;color:#fff;border-radius:16px;display:flex;align-items:flex-end;justify-content:space-between;gap:14px}.rop-public-head span,.rop-public-kicker{font-size:11px;font-weight:900;letter-spacing:.13em;color:#c9ff3d}.rop-public-head h1{margin:4px 0;font-size:clamp(30px,9vw,48px);line-height:1}.rop-public-head p{margin:8px 0 0;color:#dce6df}.rop-public-head>a,.rop-public-record button{display:inline-flex;justify-content:center;align-items:center;min-height:50px;padding:12px 16px;background:#c9ff3d;color:#14261c!important;text-decoration:none;font-weight:950;border:0;border-radius:10px;white-space:nowrap}.rop-public-notice{margin:12px 0;padding:12px 14px;background:#fff5bf;border-left:4px solid #ef5a32;font-weight:800}.rop-public-tabs{position:sticky;top:0;z-index:20;display:grid;grid-template-columns:repeat(4,1fr);gap:5px;margin:12px 0;padding:6px;background:#fff;border:1px solid #ddd8cc;border-radius:12px}.rop-public-tabs a{padding:10px 5px;text-align:center;text-decoration:none;color:#17251d;font-size:12px;font-weight:900}.rop-public-card{margin:12px 0;padding:18px;background:#fff;border:1px solid #ddd8cc;border-radius:14px}.rop-public-card h2{margin:4px 0 16px;font-size:28px}.rop-public-record form,.rop-public-record fieldset{display:grid;gap:10px}.rop-public-teams{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:9px}.rop-public-record fieldset{min-width:0;margin:0;padding:12px;border:1px solid #d7d3c9;border-radius:10px}.rop-public-record legend{padding:0 5px;font-weight:900}.rop-public-record label{display:grid;gap:5px;font-size:10px;font-weight:900;letter-spacing:.08em}.rop-public-record select,.rop-public-record input{width:100%;min-height:48px;padding:9px;border:1px solid #aaa;border-radius:8px;background:#fff;font-size:16px}.rop-public-pin{margin-top:4px}.rop-public-record button{width:100%;font-size:16px}.rop-public-table{overflow:auto}.rop-public-table table{width:100%;border-collapse:collapse}.rop-public-table th,.rop-public-table td{padding:11px 8px;border-bottom:1px solid #ece9e0;text-align:left}.rop-public-scores{display:grid;gap:10px}.rop-public-scores article{padding:13px;border:1px solid #e1ddd3;border-radius:10px}.rop-public-scores article>span{display:block;margin-bottom:8px;color:#d9502e;font-size:11px;font-weight:900}.rop-public-scores article>div{display:grid;grid-template-columns:1fr auto;gap:10px;padding:5px 0;color:#657068}.rop-public-scores article>div.winner{color:#17251d}.rop-public-scores article b{font-size:20px}.rop-public-pin-settings{max-width:760px;margin:12px auto;padding:16px;background:#fff5bf;border-radius:12px}.rop-public-pin-settings form{display:flex;gap:8px;align-items:end}.rop-public-pin-settings input{min-height:44px;padding:8px;font-size:16px}.rop-public-pin-settings button{min-height:44px;padding:8px 14px;background:#173426;color:#fff;border:0;font-weight:900}
    .rop-public-tabs{grid-template-columns:repeat(3,1fr)}
    .rop-public-quick{display:grid;grid-template-columns:repeat(4,1fr);gap:6px}.rop-public-quick button{min-height:40px!important;padding:7px!important;border:1px solid #173426!important;background:#fff!important;color:#173426!important}.rop-public-quick button.is-selected{background:#173426!important;color:#fff!important}
    @media(max-width:600px){body{overflow-x:hidden}.rop-public-app{padding-left:8px;padding-right:8px}.rop-public-head{align-items:stretch;flex-direction:column}.rop-public-head>a{width:100%}.rop-public-tabs{position:fixed;left:8px;right:8px;bottom:8px;top:auto;margin:0;box-shadow:0 10px 30px rgba(0,0,0,.2)}.rop-public-card{padding:15px}.rop-public-teams{grid-template-columns:1fr}.rop-public-teams>b{text-align:center}.rop-public-pin-settings form{align-items:stretch;flex-direction:column}}
    </style>
    <script id="rop-public-speed-script">
    (function(){
      function run(){var form=document.querySelector('.rop-public-record form');if(!form||form.dataset.speedReady)return;form.dataset.speedReady='1';var group=(form.querySelector('[name="group_id"]')||{}).value||'group',playerKey='rop-public-players-'+group,pinKey='rop-public-pin-'+group,selects=form.querySelectorAll('select[name="a1"],select[name="a2"],select[name="b1"],select[name="b2"]'),pin=form.querySelector('[name="group_pin"]');try{var players=JSON.parse(localStorage.getItem(playerKey)||'[]');selects.forEach(function(s,i){if(players[i])s.value=players[i];});if(pin)pin.value=localStorage.getItem(pinKey)||'';}catch(e){}form.querySelectorAll('input[name="score_a"],input[name="score_b"]').forEach(function(input){var row=document.createElement('div');row.className='rop-public-quick';[7,9,11,15].forEach(function(value){var b=document.createElement('button');b.type='button';b.textContent=value;b.className=Number(input.value)===value?'is-selected':'';b.addEventListener('click',function(){input.value=value;row.querySelectorAll('button').forEach(function(x){x.classList.toggle('is-selected',x===b);});});row.appendChild(b);});input.insertAdjacentElement('afterend',row);});form.addEventListener('submit',function(){try{localStorage.setItem(playerKey,JSON.stringify(Array.from(selects).map(function(s){return s.value;})));if(pin&&pin.value)localStorage.setItem(pinKey,pin.value);}catch(e){}});}
      if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',run);else run();
    })();
    </script>
    <?php
}, 200);

add_action('wp_footer', function () {
    if (!is_front_page() || !is_user_logged_in() || !function_exists('rop_group')) { return; }
    $group_id = absint($_GET['group'] ?? 0); $group = $group_id ? rop_group($group_id) : null;
    if (!$group || (int) $group->owner_id !== get_current_user_id()) { return; }
    rop_public_ensure_pin($group_id);
    ?><section class="rop-public-pin-settings"><strong>Public recording PIN</strong><p>People with your group link can view everything. They need this PIN to save a score. Temporary PIN: <strong>2468</strong>—change it now.</p><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="rop_set_group_pin"><input type="hidden" name="group_id" value="<?php echo esc_attr($group_id); ?>"><?php wp_nonce_field('rop_set_group_pin_' . $group_id); ?><label>NEW 4–8 DIGIT PIN<br><input type="password" inputmode="numeric" pattern="[0-9]{4,8}" name="group_pin" required></label><button>UPDATE PIN</button></form></section><?php
}, 170);
