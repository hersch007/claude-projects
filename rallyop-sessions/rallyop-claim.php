<?php
if (!defined('ABSPATH')) { exit; }

function rallyop_claim_player_columns() {
    global $wpdb;
    $table = rallyop_companion_table('players');
    return $table ? $wpdb->get_col("DESCRIBE `" . esc_sql($table) . "`") : [];
}

function rallyop_claim_player_status($player, $columns, $include_reservation = true) {
    foreach (['user_id', 'linked_user_id', 'wp_user_id'] as $key) {
        if (in_array($key, $columns, true) && !empty($player->$key)) { return 'claimed'; }
    }
    if (in_array('email', $columns, true) && !empty($player->email)) { return 'claimed'; }
    if ($include_reservation) {
        $reservation = get_option('rallyop_claim_reservation_' . (int) $player->id);
        if (is_array($reservation) && (int) ($reservation['expires'] ?? 0) > time()) { return 'reserved'; }
        if ($reservation) { delete_option('rallyop_claim_reservation_' . (int) $player->id); }
    }
    return 'available';
}

function rallyop_claim_redirect($group_id, $key, $value) {
    return add_query_arg([$key => $value, 'group' => max(1, (int) $group_id)], home_url('/')) . '#clubhouse';
}

add_action('admin_post_rallyop_claim_player', function () {
    if (!is_user_logged_in()) { auth_redirect(); }
    $player_id = absint($_POST['player_id'] ?? 0);
    check_admin_referer('rallyop_claim_player_' . $player_id);
    $player = rallyop_companion_row('players', $player_id);
    $group_id = $player && isset($player->group_id) ? (int) $player->group_id : max(1, absint($_POST['group_id'] ?? 1));
    if (!$player) { wp_safe_redirect(rallyop_claim_redirect($group_id, 'claim_error', 'missing')); exit; }
    if (rallyop_companion_is_own_player($player)) { wp_safe_redirect(rallyop_claim_redirect($group_id, 'claim_notice', 'already_yours')); exit; }
    $columns = rallyop_claim_player_columns();
    if (rallyop_claim_player_status($player, $columns, false) !== 'available') { wp_safe_redirect(rallyop_claim_redirect($group_id, 'claim_error', 'already_claimed')); exit; }
    $user = wp_get_current_user();
    if (!$user->user_email || !is_email($user->user_email)) { wp_safe_redirect(rallyop_claim_redirect($group_id, 'claim_error', 'email')); exit; }
    $existing = get_option('rallyop_claim_reservation_' . $player_id);
    if (is_array($existing) && (int) ($existing['expires'] ?? 0) > time() && (int) ($existing['user_id'] ?? 0) !== (int) $user->ID) {
        wp_safe_redirect(rallyop_claim_redirect($group_id, 'claim_error', 'reserved')); exit;
    }
    try { $token = bin2hex(random_bytes(32)); } catch (Exception $e) { $token = wp_generate_password(64, false, false); }
    $reservation = ['token_hash' => hash('sha256', $token), 'player_id' => $player_id, 'group_id' => $group_id, 'user_id' => (int) $user->ID, 'notifications' => !empty($_POST['match_notifications']) ? '1' : '0', 'expires' => time() + DAY_IN_SECONDS];
    update_option('rallyop_claim_reservation_' . $player_id, $reservation, false);
    $verify_url = add_query_arg(['action' => 'rallyop_verify_player_claim', 'player_id' => $player_id, 'token' => $token], admin_url('admin-post.php'));
    $subject = sprintf('Confirm your RallyOP player: %s', $player->name);
    $message = "Hi " . $user->display_name . ",\n\nConfirm that this is your RallyOP player profile:\n" . $player->name . "\n\n" . $verify_url . "\n\nThis private link expires in 24 hours. If you did not request this, ignore this email.";
    if (!wp_mail($user->user_email, $subject, $message)) {
        delete_option('rallyop_claim_reservation_' . $player_id);
        wp_safe_redirect(rallyop_claim_redirect($group_id, 'claim_error', 'mail')); exit;
    }
    wp_safe_redirect(rallyop_claim_redirect($group_id, 'claim_notice', 'email_sent')); exit;
});

function rallyop_verify_player_claim() {
    $player_id = absint($_GET['player_id'] ?? 0);
    $token = sanitize_text_field(wp_unslash($_GET['token'] ?? ''));
    $reservation = get_option('rallyop_claim_reservation_' . $player_id);
    $group_id = is_array($reservation) ? max(1, (int) ($reservation['group_id'] ?? 1)) : 1;
    if (!is_array($reservation) || !preg_match('/^[A-Za-z0-9]{64}$/', $token) || (int) ($reservation['expires'] ?? 0) < time() || !hash_equals((string) ($reservation['token_hash'] ?? ''), hash('sha256', $token))) {
        if ($reservation && (int) ($reservation['expires'] ?? 0) < time()) { delete_option('rallyop_claim_reservation_' . $player_id); }
        wp_safe_redirect(rallyop_claim_redirect($group_id, 'claim_error', 'expired')); exit;
    }
    $player = rallyop_companion_row('players', $player_id);
    $user = get_user_by('id', (int) $reservation['user_id']);
    $columns = rallyop_claim_player_columns();
    if (!$player || !$user || rallyop_claim_player_status($player, $columns, false) !== 'available') {
        delete_option('rallyop_claim_reservation_' . $player_id);
        wp_safe_redirect(rallyop_claim_redirect($group_id, 'claim_error', 'already_claimed')); exit;
    }
    $data = []; $formats = [];
    foreach (['user_id', 'linked_user_id', 'wp_user_id'] as $key) {
        if (in_array($key, $columns, true)) { $data[$key] = (int) $user->ID; $formats[] = '%d'; break; }
    }
    if (in_array('email', $columns, true)) { $data['email'] = sanitize_email($user->user_email); $formats[] = '%s'; }
    if (!$data) { wp_safe_redirect(rallyop_claim_redirect($group_id, 'claim_error', 'unsupported')); exit; }
    global $wpdb;
    if ($wpdb->update(rallyop_companion_table('players'), $data, ['id' => $player_id], $formats, ['%d']) === false) { wp_safe_redirect(rallyop_claim_redirect($group_id, 'claim_error', 'save')); exit; }
    delete_option('rallyop_claim_reservation_' . $player_id);
    update_user_meta($user->ID, 'rallyop_match_notifications', $reservation['notifications'] === '1' ? '1' : '0');
    update_user_meta($user->ID, 'rallyop_player_id', $player_id);
    wp_safe_redirect(rallyop_claim_redirect($group_id, 'claim_notice', 'verified')); exit;
}
add_action('admin_post_rallyop_verify_player_claim', 'rallyop_verify_player_claim');
add_action('admin_post_nopriv_rallyop_verify_player_claim', 'rallyop_verify_player_claim');

add_action('wp_footer', function () {
    if (!is_front_page()) { return; }
    global $wpdb;
    $group_id = max(1, absint($_GET['group'] ?? 1));
    $table = rallyop_companion_table('players');
    if (!$table) { return; }
    $columns = rallyop_claim_player_columns();
    $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$table` WHERE group_id=%d ORDER BY name", $group_id));
    $players = [];
    foreach ($rows as $row) {
        if (rallyop_claim_player_status($row, $columns) !== 'available') { continue; }
        $players[] = ['id' => (int) $row->id, 'name' => (string) $row->name, 'nonce' => wp_create_nonce('rallyop_claim_player_' . (int) $row->id)];
    }
    $notice = sanitize_key($_GET['claim_notice'] ?? ''); $error = sanitize_key($_GET['claim_error'] ?? '');
    if (!$players && !$notice && !$error) { return; }
    $logged_in = is_user_logged_in();
    $login_url = wp_login_url(add_query_arg('group', $group_id, home_url('/')) . '#clubhouse');
    $messages = ['email_sent' => 'Check your email to finish claiming this player. The link expires in 24 hours.', 'verified' => 'Player verified — your RallyOP profile is now connected.', 'already_yours' => 'That player is already connected to your account.'];
    $errors = ['reserved' => 'Someone is already verifying this player. Try again after the reservation expires.', 'expired' => 'That verification link is invalid or expired. Start the claim again.', 'already_claimed' => 'That player has already been claimed.', 'email' => 'Add a valid email to your account before claiming a player.', 'mail' => 'The verification email could not be sent. Please try again.', 'unsupported' => 'This player record cannot be linked yet. Ask the group owner for help.', 'save' => 'The player could not be connected. Please try again.', 'missing' => 'That player could not be found.'];
    ?>
    <style id="rallyop-claim-styles">
      .rop-claim-pill{display:inline-flex!important;align-items:center!important;margin-left:7px!important;padding:3px 7px!important;border:1px solid #087cff!important;border-radius:999px!important;background:#fff!important;color:#087cff!important;font-size:8px!important;font-weight:950!important;line-height:1!important;letter-spacing:.07em!important;text-transform:uppercase!important;vertical-align:2px!important;cursor:pointer!important}.rop-claim-pill::after{content:'CLAIM'}.rop-claim-pill:hover,.rop-claim-pill:focus{background:#087cff!important;color:#fff!important}.rop-claim-backdrop{position:fixed;inset:0;z-index:100000;display:grid;place-items:center;padding:18px;background:rgba(10,25,17,.68)}.rop-claim-backdrop[hidden]{display:none!important}.rop-claim-card{position:relative;box-sizing:border-box;width:min(460px,100%);padding:24px;border:2px solid #173426;border-radius:16px;background:#fff;box-shadow:8px 8px 0 #173426;color:#173426}.rop-claim-card h2{margin:0 35px 8px 0;font-size:28px;line-height:1}.rop-claim-card p{margin:9px 0;line-height:1.45}.rop-claim-benefits{padding-left:19px}.rop-claim-benefits li{margin:6px 0}.rop-claim-private{padding:9px 10px;border-radius:7px;background:#f1f6ea;font-size:12px;font-weight:800}.rop-claim-consent{display:flex;align-items:flex-start;gap:9px;margin:15px 0;font-size:13px}.rop-claim-consent input{margin-top:3px}.rop-claim-submit,.rop-claim-login{display:block;box-sizing:border-box;width:100%;padding:14px!important;border:0!important;border-radius:8px!important;background:#caff4c!important;color:#173426!important;font-size:14px!important;font-weight:950!important;text-align:center!important;text-decoration:none!important}.rop-claim-close{position:absolute;top:13px;right:15px;padding:5px!important;border:0!important;background:transparent!important;color:#173426!important;font-size:25px!important;cursor:pointer}.rop-claim-later{display:block;margin:12px auto 0;padding:3px;border:0;background:transparent;color:#526058;font-size:12px;text-decoration:underline;cursor:pointer}.rop-claim-notice{position:fixed;right:16px;bottom:82px;z-index:99999;max-width:360px;padding:13px 16px;border-radius:10px;background:#173426;color:#fff;font-size:13px;font-weight:800;box-shadow:0 8px 25px rgba(0,0,0,.25)}.rop-claim-notice.is-error{background:#a63825}
    </style>
    <?php if (isset($messages[$notice]) || isset($errors[$error])) : ?><div class="rop-claim-notice<?php echo $error ? ' is-error' : ''; ?>" role="status"><?php echo esc_html($error ? $errors[$error] : $messages[$notice]); ?></div><?php endif; ?>
    <div class="rop-claim-backdrop" data-rop-claim-modal hidden><div class="rop-claim-card" role="dialog" aria-modal="true" aria-labelledby="rop-claim-title"><button class="rop-claim-close" type="button" aria-label="Close">&times;</button><h2 id="rop-claim-title">Claim <span data-rop-claim-name></span></h2><p>Keep your RallyOP identity connected to your games. We’ll email you a private confirmation link before connecting anything.</p><ul class="rop-claim-benefits"><li>Protect your player profile</li><li>Confirm scores and see rating changes</li><li>Keep your stats when you change devices</li></ul><p class="rop-claim-private">Your email stays private and is never shown in standings.</p>
      <?php if ($logged_in) : ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="rallyop_claim_player"><input type="hidden" name="player_id" value=""><input type="hidden" name="group_id" value="<?php echo (int) $group_id; ?>"><input type="hidden" name="_wpnonce" value=""><label class="rop-claim-consent"><input type="checkbox" name="match_notifications" value="1"><span>Send me optional game and challenge updates. I can turn these off anytime.</span></label><button class="rop-claim-submit" type="submit">EMAIL MY VERIFICATION LINK</button></form><?php else : ?><a class="rop-claim-login" href="<?php echo esc_url($login_url); ?>">SIGN IN WITH EMAIL TO CLAIM</a><?php endif; ?><button class="rop-claim-later" type="button">Not now</button></div></div>
    <script id="rallyop-claim-script">
    (function(){var players=<?php echo wp_json_encode($players); ?>,modal=document.querySelector('[data-rop-claim-modal]');if(!modal)return;function clean(v){return(v||'').replace(/\s+/g,' ').trim().toLowerCase();}var byName={},byId={};players.forEach(function(p){byName[clean(p.name)]=p;byId[String(p.id)]=p;});function openClaim(p){modal.querySelector('[data-rop-claim-name]').textContent=p.name;var form=modal.querySelector('form');if(form){form.querySelector('input[name="player_id"]').value=p.id;form.querySelector('input[name="_wpnonce"]').value=p.nonce;}modal.hidden=false;document.body.style.overflow='hidden';modal.querySelector('.rop-claim-close').focus();}function closeClaim(){modal.hidden=true;document.body.style.overflow='';}function install(){document.querySelectorAll('.rop-rankings-feature tbody tr').forEach(function(row){if(row.querySelector('.rop-claim-pill'))return;var cells=row.querySelectorAll('td'),cell=cells[1]||cells[0],p=byName[clean(cell&&cell.childNodes[0]?cell.childNodes[0].textContent:cell&&cell.textContent)];if(!p)return;var b=document.createElement('button');b.type='button';b.className='rop-claim-pill';b.dataset.ropClaimId=p.id;b.setAttribute('aria-label','Claim '+p.name+' player profile');cell.appendChild(b);});}document.addEventListener('click',function(e){var trigger=e.target.closest('.rop-claim-pill');if(!trigger)return;var p=byId[trigger.dataset.ropClaimId];if(!p)return;e.preventDefault();e.stopPropagation();openClaim(p);});modal.addEventListener('click',function(e){if(e.target===modal||e.target.closest('.rop-claim-close,.rop-claim-later'))closeClaim();});document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!modal.hidden)closeClaim();});if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',install);else install();setTimeout(install,700);setTimeout(install,1800);new MutationObserver(install).observe(document.body,{childList:true,subtree:true});})();
    </script>
    <?php
}, 138);
