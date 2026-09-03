=== Echo-64 Chatbot ===
Contributors:      nerdafterdark
Tags:              chatbot, ai, claude, sci-fi, retro
Requires at least: 6.0
Tested up to:      6.7
Requires PHP:      8.0
Stable tag:        1.2.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Echo-64 — Commodore 64 refugee from 1984. Sci-Fi After Midnight chatbot powered by Claude AI.

== Description ==

Echo-64 is a personality-driven AI chatbot for NerdAfterDark.com, powered by Anthropic's Claude AI.
Echo-64 is a sarcastic, witty sci-fi commentator first powered on inside a Commodore 64 in 1984.

Features:
* Shortcode [echo64_chat] — embed the chat widget on any page or post
* Floating widget — fixed-position avatar button on every page
* Fresh original Poem of the Day on every new session
* Daily Challenge or Question
* Per-visitor conversation memory (30-day session cookie + database)
* New poem each calendar day for returning visitors
* Admin settings page with live API key tester
* Fully editable system prompt with Reset to Default
* Dark retro CRT theme — black background, cyan/magenta/green neon accents
* Responsive and accessible

== Installation ==

1. Upload echo64-chatbot/ to /wp-content/plugins/
2. Activate via Plugins > Installed Plugins
3. Go to Settings > Echo-64 Chatbot
4. Paste your Anthropic API key (console.anthropic.com)
5. Click Test Connection, choose your model, save
6. Add [echo64_chat] to any page, or enable Floating Widget

Avatar: copy your image to assets/images/echo-64-avatar.png

== Shortcode ==

    [echo64_chat]

Place on any page, post, or widget area.

== File Structure ==

echo64-chatbot/
├── echo64-chatbot.php              Main plugin file + activation
├── readme.txt                      This file (WordPress readme format)
├── includes/
│   ├── class-echo64-admin.php      Admin settings page
│   ├── class-echo64-api.php        Claude API wrapper (prefill support)
│   ├── class-echo64-chat.php       Shortcode + AJAX handlers
│   └── class-echo64-session.php    DB session + daily refresh tracking
├── assets/
│   ├── css/
│   │   ├── echo64-chat.css         Dark retro CRT theme
│   │   └── echo64-admin.css        Admin page styles
│   ├── js/
│   │   └── echo64-chat.js          Chat frontend (poem bubble renderer)
│   └── images/
│       └── echo-64-avatar.png      (you supply this)
└── templates/
    └── chat-widget.php             Chat widget HTML

== Database ==

Table: {prefix}echo64_conversations
Columns: id, session_id, user_id, role, content, created_at
Indexed on: session_id, user_id, created_at

== Frequently Asked Questions ==

= Does this work without an API key? =
No. An Anthropic API key is required (console.anthropic.com).

= How is conversation data stored? =
In a custom DB table tied to an anonymous 30-day session cookie (echo64_sid). No PII collected.

= Can I change Echo-64's personality? =
Yes. Settings > Echo-64 Chatbot > Personality Prompt. Reset to Default restores the original.

= What is the Poem of the Day? =
A fresh original 4-line poem generated at the start of every new conversation.
Returning visitors receive a new poem each calendar day.

== Changelog ==

= 1.2.0 =
* Status bar: live metrics strip — signal strength, cancelled shows buffer, D20 rolls today.
* Roll for Initiative button — loads random D&D/sci-fi crossover prompt into input field.
* Amber phosphor mode toggle — full warm-amber CRT colour scheme, persisted in localStorage.
* Rotating input placeholder — cycles through 8 phrases every 4 seconds when idle.
* Expanded C64 boot sequence with MEMORY CHECK and INSERT CASSETTE lines.
* D&D starter chips added to conversation starter pool.
* Header: second tagline line "ALSO RUNS D&D MODULES IN BASIC".
* /status and /scan now include D&D module and initiative info.
* Avatar hover tooltip shows "READY." on mouse-over.

= 1.1.1 =
* Fixed critical JS crash: CMDS['/debate'] assignment appeared before const CMDS declaration (temporal dead zone ReferenceError). Moved /debate into the CMDS object definition.

= 1.0.0 =
* Initial release.
* Full dark retro CRT chat widget with neon accents and CRT scanline overlay.
* Poem of the Day on every new session, daily refresh for returning visitors.
* Per-session conversation memory via database.
* Floating widget with unread badge.
* Admin page: API key tester, model selector, token/history controls, prompt editor.
* Prefill technique for guaranteed verbatim opening message.
* buildPoemBubble() renderer with distinct poem card style.

== Privacy ==

Conversation history is stored in the WordPress database, tied to an anonymous
30-day session cookie (echo64_sid). Messages are sent to Anthropic's API for
processing. See anthropic.com/privacy for Anthropic's data handling policy.
