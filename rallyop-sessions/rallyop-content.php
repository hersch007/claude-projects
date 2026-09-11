<?php
if (!defined('ABSPATH')) { exit; }

function rallyop_public_pages() {
    $updated = 'September 10, 2026';
    return [
        'privacy-policy' => [
            'title' => 'Privacy Policy',
            'content' => '<div class="rop-info-page rop-legal"><p class="rop-page-kicker">RALLYOP LEGAL</p><h1>Privacy Policy</h1><p class="rop-updated">Last updated ' . $updated . '</p><p class="rop-lead">This Privacy Policy explains how RallyOP collects, uses, discloses, and protects information when you use our open-play group, scheduling, ranking, and scorekeeping services.</p>
            <div class="rop-callout"><strong>Plain-language summary</strong><p>We collect only the information needed to run RallyOP. We do not sell personal information or use it for cross-context behavioral advertising.</p></div>
            <h2>1. Scope and controller</h2><p>This policy applies to rallyop.com and RallyOP services. “RallyOP,” “we,” and “us” refer to the operator of RallyOP. Questions and privacy requests may be sent to <a href="mailto:play@rallyop.com">play@rallyop.com</a>.</p>
            <h2>2. Information we collect</h2><h3>Information you provide</h3><ul><li>Email address and authentication information used for sign-in.</li><li>Display name, player profile, group membership, invitations, RSVPs, and session details.</li><li>Game information, including dates, teams, scores, results, ratings, rankings, seasons, achievements, and comeback markers.</li><li>Messages you send to us for support, privacy, or account requests.</li></ul><h3>Information collected automatically</h3><ul><li>Basic device, browser, IP address, request, error, and security-log information.</li><li>Necessary cookies and similar storage used for authentication, preferences, security, and site operation.</li></ul><p>We do not intentionally request precise location, payment-card, health, biometric, or government-identification information.</p>
            <h2>3. How we use information</h2><ul><li>Authenticate users and protect accounts.</li><li>Create and administer groups, invitations, sessions, rosters, and seasons.</li><li>Record games and calculate records, ratings, statistics, and achievements.</li><li>Operate, troubleshoot, secure, improve, and communicate about the service.</li><li>Comply with law and enforce our Terms and Community Guidelines.</li></ul>
            <h2>4. Legal bases</h2><p>Where applicable, we process information to provide the service you request, pursue legitimate interests such as security and product improvement, comply with legal obligations, and obtain consent when required.</p>
            <h2>5. How information is shared</h2><ul><li><strong>Group members:</strong> profile, roster, game, session, ranking, and achievement information is visible to members of the relevant group.</li><li><strong>Group owners:</strong> owners can administer memberships, invitations, player links, games, and seasons.</li><li><strong>Service providers:</strong> hosting, email delivery, security, maintenance, and analytics providers may process information on our behalf.</li><li><strong>Legal and safety:</strong> information may be disclosed when reasonably necessary to comply with law, protect rights or safety, investigate abuse, or complete a business transfer.</li></ul><p>RallyOP does not sell personal information or share it for cross-context behavioral advertising.</p>
            <h2>6. Cookies and similar technologies</h2><p>RallyOP uses necessary cookies and local storage for sign-in, session continuity, security, and preferences. If optional analytics or marketing technologies are introduced, this policy and any required consent controls will be updated first.</p>
            <h2>7. Retention</h2><p>Account and group records are retained while needed to provide the service. Game history may remain until edited or removed by an authorized group owner or until a valid deletion request is completed. Security logs and expired sign-in tokens may be retained for a limited period for fraud prevention, troubleshooting, and legal compliance. We may retain de-identified or aggregated information that no longer identifies a user.</p>
            <h2>8. Your choices and rights</h2><p>Depending on your location, you may have rights to request access, correction, deletion, portability, restriction, or objection; withdraw consent; and appeal a denied request. You may also sign out, ask a group owner to unlink your player profile, or request account deletion. Email <a href="mailto:play@rallyop.com?subject=Privacy%20Request">play@rallyop.com</a> with “Privacy Request” in the subject. We may verify your identity before acting.</p>
            <h2>9. Security</h2><p>We use access controls, expiring sign-in links, security monitoring, and reasonable administrative and technical safeguards. No online service can guarantee absolute security. Keep sign-in and invitation links private and notify us if you suspect unauthorized access.</p>
            <h2>10. International processing</h2><p>Information may be processed in countries other than where you live. Where required, we use appropriate safeguards for international transfers.</p>
            <h2>11. Children</h2><p>RallyOP is not directed to children under 13, and we do not knowingly collect their personal information. Users under the age of legal majority should use RallyOP only with permission from a parent or legal guardian. Contact us to request removal of a child’s information.</p>
            <h2>12. Third-party links</h2><p>RallyOP may link to third-party services. Their privacy practices are governed by their own policies.</p>
            <h2>13. Changes to this policy</h2><p>We may update this policy as the service changes. We will post the revised date and provide additional notice when legally required.</p>
            <h2>14. Contact</h2><p>Questions, complaints, or privacy requests: <a href="mailto:play@rallyop.com">play@rallyop.com</a>.</p></div>'
        ],
        'terms' => [
            'title' => 'Terms of Use',
            'content' => '<div class="rop-info-page rop-legal"><p class="rop-page-kicker">RALLYOP LEGAL</p><h1>Terms of Use</h1><p class="rop-updated">Last updated ' . $updated . '</p><p class="rop-lead">These Terms govern your use of rallyop.com and RallyOP’s group, scheduling, ranking, and scorekeeping services. By using RallyOP, you agree to these Terms.</p>
            <h2>1. Eligibility and acceptance</h2><p>You must be at least 13 and legally able to agree to these Terms. If you are under the age of legal majority, a parent or guardian must approve your use. Do not use RallyOP if these Terms are unacceptable to you.</p>
            <h2>2. Accounts and access</h2><p>You are responsible for activity through your account and for maintaining control of your email, device, sign-in links, and invitation links. Provide accurate information, do not impersonate others, and promptly report suspected unauthorized access.</p>
            <h2>3. Groups and owner responsibilities</h2><p>Group owners administer membership, invitations, rosters, linked profiles, games, sessions, and seasons. Owners must act fairly, respect member privacy, and have authority to submit information about their groups. RallyOP may not resolve private disputes about scores, membership, or ownership.</p>
            <h2>4. User content and game records</h2><p>You retain ownership of information you submit. You grant RallyOP a non-exclusive, worldwide, royalty-free license to host, reproduce, display, adapt, and process that content solely to operate, secure, and improve the service. You represent that you have the necessary rights and that the information is lawful and reasonably accurate.</p>
            <h2>5. Ratings, rankings, and achievements</h2><p>Ratings, statistics, rankings, streaks, achievements, and predictions are recreational features derived from submitted information. They are not official certifications of skill, and we do not guarantee accuracy or suitability for league, tournament, wagering, employment, or eligibility decisions.</p>
            <h2>6. Acceptable use</h2><p>You may not misuse RallyOP, interfere with the service, probe or bypass security, scrape at unreasonable volume, introduce malicious code, access another person’s account, send unlawful invitations, harass others, submit deceptive results, infringe rights, or use RallyOP for illegal activity.</p>
            <h2>7. Intellectual property</h2><p>RallyOP’s software, designs, branding, logos, and original content are owned by RallyOP or its licensors and protected by applicable law. These Terms provide a limited, revocable, non-transferable right to use the service for its intended purpose; they do not transfer ownership.</p>
            <h2>8. Feedback</h2><p>If you provide suggestions, you permit us to use them without restriction or compensation, while we remain free not to implement them.</p>
            <h2>9. Service changes and availability</h2><p>We may modify, suspend, limit, or discontinue features. Maintenance, outages, and data loss can occur. Keep independent records of information that is important to you.</p>
            <h2>10. Suspension and termination</h2><p>We may restrict or terminate access when reasonably necessary to protect RallyOP or users, enforce these Terms, respond to legal requirements, or address abuse. You may stop using RallyOP and request account deletion at any time. Provisions that by their nature should survive termination will survive.</p>
            <h2>11. Recreational activity and assumption of risk</h2><p>RallyOP organizes information; it does not supervise games, inspect courts or equipment, provide coaching, or make medical decisions. Racquet sports involve risks including collision, falls, overexertion, property damage, serious injury, and death. You are responsible for assessing your fitness, surroundings, equipment, weather, court rules, and play. To the fullest extent permitted by law, you voluntarily assume risks associated with participating in activities coordinated through RallyOP.</p>
            <h2>12. Disclaimers</h2><p>To the fullest extent permitted by law, RallyOP is provided “as is” and “as available,” without warranties of merchantability, fitness for a particular purpose, non-infringement, accuracy, availability, or uninterrupted operation. Some jurisdictions do not allow certain disclaimers, so they may not fully apply to you.</p>
            <h2>13. Limitation of liability</h2><p>To the fullest extent permitted by law, RallyOP and its operators, suppliers, and affiliates will not be liable for indirect, incidental, special, consequential, exemplary, or punitive damages, lost data, lost profits, personal injury arising from play, or losses caused by user content or unauthorized access. Where liability cannot be excluded, aggregate liability will not exceed the greater of amounts you paid RallyOP during the previous twelve months or US $100. These limitations do not apply where prohibited by law.</p>
            <h2>14. Indemnification</h2><p>To the extent permitted by law, you agree to defend and indemnify RallyOP and its operators from claims arising from your unlawful use, your content, your violation of these Terms, or your infringement of another person’s rights.</p>
            <h2>15. Governing law and disputes</h2><p>Applicable law governs these Terms without overriding consumer protections that cannot be waived. Before filing a claim, contact <a href="mailto:play@rallyop.com?subject=Dispute%20Notice">play@rallyop.com</a> and allow 30 days for informal resolution. Nothing in this section prevents either party from seeking urgent injunctive relief or using a qualifying small-claims process.</p>
            <h2>16. General terms</h2><p>If a provision is unenforceable, the remaining provisions remain effective. Failure to enforce a provision is not a waiver. You may not assign these Terms without consent; RallyOP may assign them as part of a reorganization or transfer. These Terms, the Privacy Policy, and incorporated guidelines form the entire agreement regarding the service.</p>
            <h2>17. Changes and contact</h2><p>We may update these Terms and will post the revised date. Continued use after changes take effect constitutes acceptance where permitted by law. Questions: <a href="mailto:play@rallyop.com">play@rallyop.com</a>.</p></div>'
        ],
        'community-guidelines' => [
            'title' => 'Community Guidelines',
            'content' => '<div class="rop-info-page"><p class="rop-page-kicker">PLAY FAIR</p><h1>Community Guidelines</h1><p class="rop-lead">RallyOP works when competition stays friendly, results stay honest, and every player feels welcome.</p><div class="rop-card-grid"><article><h2>Respect the crew</h2><p>No harassment, threats, discrimination, bullying, unwanted contact, or personal attacks.</p></article><article><h2>Record honestly</h2><p>Submit accurate teams, scores, dates, and results. Correct honest mistakes promptly.</p></article><article><h2>Invite responsibly</h2><p>Share group and sign-in links only with intended players. Never use invitations for spam.</p></article><article><h2>Protect privacy</h2><p>Do not publish private contact information, sensitive details, or photos without permission.</p></article><article><h2>Play safely</h2><p>Follow court rules, use suitable equipment, respect skill differences, and stop play when conditions are unsafe.</p></article><article><h2>Keep ratings in perspective</h2><p>Ratings are for friendly competition. Never use standings to shame, exclude, or intimidate players.</p></article></div><h2>Resolving issues</h2><p>Start with a calm conversation or ask a group owner to review the record. Serious safety, privacy, or abuse concerns may be reported to <a href="mailto:play@rallyop.com?subject=Community%20Report">play@rallyop.com</a>. Include the group name and a concise description; do not send unnecessary sensitive information.</p><h2>Enforcement</h2><p>RallyOP may remove content, restrict invitations, suspend access, or close accounts when reasonably necessary to protect users or the service. Context, severity, history, and available evidence may be considered.</p></div>'
        ],
        'faq' => [
            'title' => 'Frequently Asked Questions',
            'content' => '<div class="rop-info-page"><p class="rop-page-kicker">NEED A LINE CALL?</p><h1>Frequently Asked Questions</h1><p class="rop-lead">Quick answers for organizing open play, recording games, and understanding RallyOP.</p><div class="rop-faq"><details open><summary>What is RallyOP?</summary><p>RallyOP is a private open-play organizer for friends. Create a group, invite players, record games, schedule sessions, and track friendly rankings.</p></details><details><summary>How do I join a group?</summary><p>Open the invitation link from the group owner and accept the invitation. Use the same email address when signing in.</p></details><details><summary>Who can record or edit a game?</summary><p>Available controls depend on your group role. Group owners can manage the roster and correct game records.</p></details><details><summary>How are ratings calculated?</summary><p>Ratings move after recorded results based on the expected outcome. Beating a stronger team generally produces a larger increase. Ratings are recreational, not official tournament ratings.</p></details><details><summary>Why do two players share a rank?</summary><p>Players can tie when their ratings or results are equal. RallyOP may display both as joint leaders.</p></details><details><summary>What are seasons?</summary><p>Seasons create a fresh competitive chapter while preserving prior champions and archived results.</p></details><details><summary>How do sessions and RSVPs work?</summary><p>Schedule a date, time, location, and player limit. Members can respond “I’m in,” “Maybe,” or “Can’t.”</p></details><details><summary>Can I remove my information?</summary><p>Ask the group owner to unlink your player profile or email <a href="mailto:play@rallyop.com?subject=Account%20Request">play@rallyop.com</a> for an account or privacy request.</p></details><details><summary>Is RallyOP only for pickleball?</summary><p>RallyOP is designed around doubles open play, especially pickleball, but groups may use it for similar friendly formats.</p></details><details><summary>How do I report a problem?</summary><p>Email <a href="mailto:play@rallyop.com?subject=RallyOP%20Support">play@rallyop.com</a> with the group name, what happened, and the device/browser you used.</p></details></div></div>'
        ],
        'about' => [
            'title' => 'About RallyOP',
            'content' => '<div class="rop-info-page"><p class="rop-page-kicker">OPEN PLAY WITH FRIENDS</p><h1>Make every game count—without making it too serious.</h1><p class="rop-lead">RallyOP gives friend groups a simple clubhouse for invitations, sessions, scores, rankings, rivalries, and the stories that develop across a season.</p><div class="rop-card-grid"><article><h2>Built for the group chat</h2><p>RallyOP turns scattered messages and forgotten scores into one shared home for your crew.</p></article><article><h2>Competitive, not complicated</h2><p>Record a result in seconds, then let standings and statistics update automatically.</p></article><article><h2>Private by design</h2><p>Groups control their roster and invitations. Your open-play history is meant for the people you play with.</p></article></div><h2>Why RallyOP exists</h2><p>Every regular group eventually asks the same questions: Who is playing? Who won last time? Which partnership is undefeated? RallyOP keeps the answers together while preserving what matters most—the fun of showing up and playing again.</p><p><a class="rop-page-cta" href="/#clubhouse">OPEN THE CLUBHOUSE</a></p></div>'
        ],
        'contact' => [
            'title' => 'Contact RallyOP',
            'content' => '<div class="rop-info-page"><p class="rop-page-kicker">TALK COURTSIDE</p><h1>Contact RallyOP</h1><p class="rop-lead">Questions, feedback, privacy requests, or a problem with your group? Send us an email and we’ll point the ball in the right direction.</p><div class="rop-contact-card"><h2>General support</h2><p><a href="mailto:play@rallyop.com?subject=RallyOP%20Support">play@rallyop.com</a></p><p>For faster help, include your group name, a short description of the issue, and the device or browser you used. Do not send passwords or sign-in links.</p><p><a class="rop-page-cta" href="mailto:play@rallyop.com?subject=RallyOP%20Support">EMAIL RALLYOP</a></p></div><div class="rop-card-grid"><article><h2>Privacy requests</h2><p>Use the subject “Privacy Request” for access, correction, deletion, or account questions.</p></article><article><h2>Community reports</h2><p>Use the subject “Community Report” for safety, harassment, or misuse concerns.</p></article></div></div>'
        ],
    ];
}

function rallyop_install_public_pages() {
    if (get_option('rallyop_public_pages_version') === '1.1.0') { return; }
    $all_pages = rallyop_public_pages();
    $all_pages['privacy'] = $all_pages['privacy-policy'];
    $all_pages['privacy']['title'] = 'RallyOP Privacy Policy';
    $all_pages['terms-of-use'] = $all_pages['terms'];
    $all_pages['terms-of-use']['title'] = 'RallyOP Terms of Use';
    foreach ($all_pages as $slug => $page) {
        $existing = get_page_by_path($slug, OBJECT, 'page');
        $post = [
            'post_title' => $page['title'],
            'post_name' => $slug,
            'post_content' => $page['content'],
            'post_status' => 'publish',
            'post_type' => 'page',
        ];
        if ($existing) { $post['ID'] = $existing->ID; }
        wp_insert_post(wp_slash($post));
    }
    update_option('rallyop_public_pages_version', '1.1.0');
}
add_action('init', 'rallyop_install_public_pages', 30);

add_filter('the_content', function($content) {
    if (!is_singular('page') || !in_the_loop() || !is_main_query()) { return $content; }
    $page = get_queried_object();
    $pages = rallyop_public_pages();
    $pages['privacy'] = $pages['privacy-policy'];
    $pages['terms-of-use'] = $pages['terms'];
    if ($page && isset($pages[$page->post_name])) { return $pages[$page->post_name]['content']; }
    return $content;
}, 90);

add_action('wp_enqueue_scripts', function() {
    $slugs = array_merge(array_keys(rallyop_public_pages()), ['privacy', 'terms-of-use']);
    if (!is_page($slugs)) { return; }
    wp_register_style('rallyop-info-pages', false, [], '1.0.0');
    wp_enqueue_style('rallyop-info-pages');
    wp_add_inline_style('rallyop-info-pages', '.rop-info-page{max-width:920px;margin:70px auto;padding:0 28px 80px;color:#17251d}.rop-page-kicker{margin:0 0 16px;color:#ef5a32;font-size:12px;font-weight:900;letter-spacing:.16em}.rop-info-page h1{max-width:850px;margin:0 0 18px;font-size:clamp(44px,7vw,82px);line-height:.98;letter-spacing:-.04em}.rop-info-page .rop-lead{max-width:760px;margin:0 0 45px;font-size:21px;line-height:1.55}.rop-updated{font-size:12px;font-weight:800;letter-spacing:.08em;color:#68736d}.rop-info-page h2{margin:42px 0 12px;font-size:28px}.rop-info-page h3{margin:24px 0 8px;font-size:19px}.rop-info-page p,.rop-info-page li{font-size:16px;line-height:1.7}.rop-info-page li+li{margin-top:7px}.rop-callout{margin:28px 0;padding:24px;border-left:6px solid #087cff;background:#e8f3ff}.rop-callout p{margin:6px 0 0}.rop-card-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:34px 0}.rop-card-grid article{padding:24px;background:#f7f3e8;border-top:5px solid #caff4c}.rop-card-grid h2{margin:0 0 10px;font-size:23px}.rop-faq details{border-top:1px solid #d7d1c5;padding:20px 0}.rop-faq summary{cursor:pointer;font-size:20px;font-weight:850}.rop-faq details p{padding-right:30px}.rop-contact-card{margin:32px 0;padding:34px;background:#17271e;color:#fff}.rop-contact-card h2{margin-top:0}.rop-contact-card a{color:#caff4c}.rop-page-cta{display:inline-block;margin-top:12px;padding:16px 22px;background:#caff4c;color:#17251d!important;font-weight:900;letter-spacing:.08em;text-decoration:none;box-shadow:5px 5px 0 #ef5a32}@media(max-width:760px){.rop-info-page{margin-top:42px;padding:0 20px 60px}.rop-card-grid{grid-template-columns:1fr}.rop-info-page .rop-lead{font-size:18px}}');
});

add_action('template_redirect', function() {
    if (is_page('privacy-policy')) { wp_safe_redirect(home_url('/privacy/'), 301); exit; }
    if (is_page('terms')) { wp_safe_redirect(home_url('/terms-of-use/'), 301); exit; }
});

add_action('wp_footer', function() {
    ?>
    <script id="rallyop-public-navigation">
    (function(){var nav=document.querySelector('header nav, [role="banner"] nav, body>header nav');var links=[['FAQ','<?php echo esc_js(home_url('/faq/')); ?>'],['About','<?php echo esc_js(home_url('/about/')); ?>'],['Contact','<?php echo esc_js(home_url('/contact/')); ?>']];if(nav)links.forEach(function(item){if(nav.querySelector('a[href="'+item[1]+'"]'))return;var a=document.createElement('a');a.href=item[1];a.textContent=item[0];a.className='rop-public-nav-link';nav.insertBefore(a,nav.lastElementChild);});var foot=document.querySelector('footer nav'),privacy='<?php echo esc_js(home_url('/privacy/')); ?>',terms='<?php echo esc_js(home_url('/terms-of-use/')); ?>',guide='<?php echo esc_js(home_url('/community-guidelines/')); ?>';if(foot){Array.from(foot.querySelectorAll('a')).forEach(function(a){var t=a.textContent.trim().toLowerCase();if(t==='privacy')a.href=privacy;if(t==='terms')a.href=terms;});if(!foot.querySelector('a[href="'+guide+'"]')){var g=document.createElement('a');g.href=guide;g.textContent='Community Guidelines';foot.appendChild(g);}}})();
    </script>
    <?php
}, 100);

add_action('wp_footer', function() {
    if (!is_page(['faq', 'about', 'contact'])) { return; }
    ?>
    <style id="rallyop-subpage-hero-styles">
      .rop-subpage-hero{position:relative;isolation:isolate;overflow:hidden;margin:0;padding:clamp(70px,10vw,135px) 28px;background:#102d20;color:#fff}.rop-subpage-hero:before{content:"";position:absolute;z-index:-2;inset:0;background:radial-gradient(circle at 78% 30%,rgba(79,126,87,.45),transparent 34%),linear-gradient(120deg,#0b2419,#173d2a 70%,#0d2b1e)}.rop-subpage-hero:after{content:"";position:absolute;z-index:-1;inset:26px;border:1px solid rgba(255,255,255,.22);pointer-events:none}.rop-subpage-hero-inner{max-width:1180px;margin:auto}.rop-subpage-hero .rop-page-kicker{margin-bottom:30px;color:#cbff4d}.rop-subpage-hero h1{max-width:980px;margin:0;color:#fff;font-size:clamp(52px,9vw,118px);font-weight:850;line-height:.85;letter-spacing:-.055em}.rop-subpage-hero h1 em{color:#cbff4d;font-family:Georgia,serif;font-weight:500}.rop-subpage-hero .rop-lead{max-width:690px;margin:34px 0 28px;color:#fff;font-size:clamp(18px,2vw,24px);line-height:1.5}.rop-subpage-hero .rop-page-cta{margin-top:4px}.rop-info-page.rop-has-subpage-hero{margin-top:0;padding-top:65px}.rop-info-page.rop-has-subpage-hero>h2:first-child{margin-top:0;font-size:clamp(32px,5vw,54px)}@media(max-width:700px){.rop-subpage-hero{padding:72px 20px}.rop-subpage-hero:after{inset:12px}.rop-subpage-hero h1{line-height:.9}.rop-subpage-hero .rop-lead{margin-top:24px}}
    </style>
    <script id="rallyop-subpage-hero-script">
    (function(){var page=document.querySelector('.rop-info-page');if(!page||document.querySelector('.rop-subpage-hero'))return;var path=location.pathname.replace(/^\/+|\/+$/g,''),copy={faq:{eyebrow:'QUICK ANSWERS',title:'EVERY QUESTION.<br><em>ONE CLEAR CALL.</em>',intro:'Everything you need to organize open play, record games, and understand RallyOP.',cta:'OPEN THE CLUBHOUSE',href:'/#clubhouse'},about:{eyebrow:'BUILT FOR OPEN PLAY',title:'THE GROUP CHAT.<br><em>NOW HAS A CLUBHOUSE.</em>',intro:'Make every game count—without making it too serious.',cta:'OPEN THE CLUBHOUSE',href:'/#clubhouse'},contact:{eyebrow:'TALK COURTSIDE',title:'QUESTIONS?<br><em>LET’S RALLY.</em>',intro:'Feedback, privacy requests, or a problem with your group? We’ll point the ball in the right direction.',cta:'EMAIL RALLYOP',href:'mailto:play@rallyop.com?subject=RallyOP%20Support'}}[path];if(!copy)return;var hero=document.createElement('section');hero.className='rop-subpage-hero';hero.innerHTML='<div class="rop-subpage-hero-inner"><p class="rop-page-kicker">'+copy.eyebrow+'</p><h1>'+copy.title+'</h1><p class="rop-lead">'+copy.intro+'</p><a class="rop-page-cta" href="'+copy.href+'">'+copy.cta+'</a></div>';page.parentNode.insertBefore(hero,page);page.classList.add('rop-has-subpage-hero');var oldKicker=page.querySelector(':scope>.rop-page-kicker'),oldTitle=page.querySelector(':scope>h1'),oldLead=page.querySelector(':scope>.rop-lead');if(oldKicker)oldKicker.remove();if(oldTitle)oldTitle.remove();if(oldLead&&path!=='about')oldLead.remove();})();
    </script>
    <?php
}, 110);

add_action('wp_footer', function() {
    if (!is_page(['faq', 'about', 'contact'])) { return; }
    ?>
    <style id="rallyop-subpage-hero-size-fix">
      .rop-subpage-hero{left:50%;width:calc(100vw - 38px);margin-left:0;transform:translateX(-50%)}
      .rop-subpage-hero h1{font-size:clamp(52px,8vw,102.4px);line-height:.82}
      @media(max-width:700px){.rop-subpage-hero{width:calc(100vw - 24px)}.rop-subpage-hero h1{font-size:clamp(48px,14vw,72px);line-height:.86}}
    </style>
    <?php
}, 120);
