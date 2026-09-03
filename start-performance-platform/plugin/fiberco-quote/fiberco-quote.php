<?php
/**
 * Plugin Name: FiberCo Quote Wizard
 * Description: Public [fiberco_quote] availability → plan → info → checkout funnel for FiberCo. Ported from the WPCode PHP snippet (v4.22); integrates with the FiberCo AI Chatbot handoff (window.fibercoQuoteStartFromChat).
 * Version:     4.26
 * Author:      Start Performance | RH Brashear
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * FiberCo Quote Wizard v4.22
 * Single address field + business checkbox
 * WPCode Plugin: PHP Snippet — Run Everywhere
 * Usage: [fiberco_quote]
 *
 * Powered by Start Performance — startadvertising.com — Richard Brashear
 */

add_shortcode( 'fiberco_quote', 'fiberco_quote_render' );

function fiberco_quote_render() {
    ob_start();
    fiberco_quote_styles();
    fiberco_quote_html();
    fiberco_quote_scripts();
    return ob_get_clean();
}

function fiberco_quote_plans() {
    return array(
        array('id'=>'quantummax','type'=>'residential','name'=>'Quantum Max 10 Gig','price'=>'129.95','speed'=>'10 Gbps','tag'=>'Ultimate speed for power users','features'=>array('10 Gbps upload and download','Wall-to-Wall Indoor Wi-Fi','SecureShield Protection cyber-threat protection','FamilyZone parental controls','RocketRouter Wi-Fi 7','24/7 Technical Support','Custom Installation','FiberCo Mobile App'),'badge'=>'Best Plan','color'=>'gold'),
        array('id'=>'velocitypro','type'=>'residential','name'=>'Velocity Pro 5 Gig','price'=>'109.95','speed'=>'5 Gbps','tag'=>'Premium speed for busy homes','features'=>array('5 Gbps upload and download','SecureShield Protection cyber-threat protection','FamilyZone parental controls','RocketRouter Wi-Fi 7','24/7 Technical Support','Custom Installation','FiberCo Mobile App'),'badge'=>'Most Popular','color'=>'blue'),
        array('id'=>'everydaygig','type'=>'residential','name'=>'Everyday Gig','price'=>'89.95','speed'=>'1 Gbps','tag'=>'Fast fiber for everyday life','features'=>array('1 Gbps upload and download','Wall-to-Wall Indoor Wi-Fi','SecureShield Protection cyber-threat protection','FamilyZone parental controls','RocketRouter Wi-Fi 7','24/7 Technical Support','Custom Installation','FiberCo Mobile App'),'badge'=>'','color'=>'teal'),
        array('id'=>'streamwork','type'=>'residential','name'=>'Stream and Work 750','price'=>'69.95','speed'=>'750 Mbps','tag'=>'Reliable streaming and work speed','features'=>array('750 Mbps upload and download','SecureShield Protection cyber-threat protection','Device prioritization and FamilyZone controls','RocketRouter Wi-Fi 7','24/7 Technical Support','Custom Installation','FiberCo Mobile App'),'badge'=>'','color'=>'purple'),
        array('id'=>'essentialconnect','type'=>'residential','name'=>'Essential Connect 250','price'=>'49.95','speed'=>'250 Mbps','tag'=>'Simple, secure and essential','features'=>array('250 Mbps upload and download','SecureShield Protection cyber-threat protection','RocketRouter Wi-Fi 7','24/7 Technical Support','Custom Installation','FiberCo Mobile App'),'badge'=>'','color'=>'green'),
        array('id'=>'fastgig','type'=>'business','name'=>'Fast Gig Business','price'=>'249.95','speed'=>'1 Gbps','tag'=>'Large teams and data-intensive tasks','features'=>array('1 Gbps symmetrical speeds','Wi-Fi 6 router included','24/7 technical support','Optional static IP','Ideal for large teams'),'badge'=>'Enterprise','color'=>'blue'),
        array('id'=>'fast400','type'=>'business','name'=>'Fast 400 Business','price'=>'149.95','speed'=>'400 Mbps','tag'=>'Video conferencing and file transfers','features'=>array('400 Mbps symmetrical speeds','Wi-Fi 6 router included','24/7 technical support','Optional static IP','Perfect for video conferencing'),'badge'=>'Most Popular','color'=>'teal'),
        array('id'=>'fast250','type'=>'business','name'=>'Fast 250 Business','price'=>'79.95','speed'=>'250 Mbps','tag'=>'Video calls and small business use','features'=>array('250 Mbps symmetrical speeds','Wi-Fi 6 router included','24/7 technical support','Optional static IP','Great for small businesses'),'badge'=>'','color'=>'purple'),
    );
}

function fiberco_quote_styles() { ?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
#fcq-wrap *{box-sizing:border-box;margin:0;padding:0}
#fcq-wrap {
    font-family:'Plus Jakarta Sans',system-ui,sans-serif;
    background:#f0f4ff;min-height:100vh;padding:0 0 80px;
    color:#111827;position:relative;
    width:100vw !important;max-width:100vw !important;
    margin-left:calc(-50vw + 50%) !important;
    box-sizing:border-box !important;overflow-x:hidden;
}
.fcq-inner{max-width:100%;margin:0 auto;padding:0 40px;position:relative}

/* NAV */
/* ── Top utility bar ── */
.fcq-utilbar{background:#f8faff;border-bottom:1px solid #e5e8f0;padding:0 48px;height:36px;display:flex;align-items:center;justify-content:flex-end;gap:24px}
.fcq-utilbar a{font-size:12px;font-weight:500;color:#6b7280;text-decoration:none;transition:color .15s}
.fcq-utilbar a:hover{color:#0057FF}
.fcq-utilbar-divider{width:1px;height:14px;background:#d1d5db}

/* ── Main nav ── */
.fcq-topbar{background:#fff;border-bottom:1px solid #e5e8f0;padding:0;position:sticky;top:0;z-index:100;box-shadow:0 1px 8px rgba(0,0,0,0.04)}
.fcq-topbar-inner{max-width:100%;margin:0 auto;display:flex;align-items:center;justify-content:space-between;height:80px;padding:0 48px}

/* Modern logo */
.fcq-logo{display:flex;align-items:center;gap:14px;text-decoration:none;flex-shrink:0}
.fcq-logo-mark{
    width:46px;height:46px;border-radius:14px;
    background:linear-gradient(135deg,#0057FF 0%,#003FC7 100%);
    display:flex;align-items:center;justify-content:center;flex-shrink:0;
    box-shadow:0 4px 14px rgba(0,87,255,0.3);
}
.fcq-logo-text{display:flex;flex-direction:column;gap:1px}
.fcq-logo-name{font-size:22px;font-weight:800;color:#111827;letter-spacing:-0.5px;line-height:1}
.fcq-logo-tagline{font-size:10px;font-weight:600;color:#0057FF;letter-spacing:1.5px;text-transform:uppercase;line-height:1}

/* Nav links */
.fcq-nav{display:flex;align-items:center;gap:4px}
.fcq-nav-link{
    color:#374151;font-size:14px;font-weight:600;
    text-decoration:none;padding:10px 16px;border-radius:8px;
    transition:background .15s,color .15s;white-space:nowrap;
}
.fcq-nav-link:hover{background:#f0f4ff;color:#0057FF}
.fcq-nav-link.active{color:#0057FF}

/* Phone in nav */
.fcq-nav-phone{
    display:flex;align-items:center;gap:7px;
    color:#374151;font-size:14px;font-weight:700;
    text-decoration:none;padding:10px 16px;
    border-radius:8px;transition:background .15s;
}
.fcq-nav-phone:hover{background:#f0f4ff;color:#0057FF}
.fcq-nav-phone-icon{font-size:15px}

/* CTA button */
.fcq-nav-cta{
    background:#0057FF;color:#fff;font-size:14px;font-weight:700;
    padding:12px 28px;border-radius:10px;text-decoration:none;
    transition:background .15s,transform .1s,box-shadow .15s;
    box-shadow:0 4px 14px rgba(0,87,255,0.3);
    white-space:nowrap;margin-left:8px;
}
.fcq-nav-cta:hover{background:#0041CC;transform:translateY(-1px);box-shadow:0 6px 20px rgba(0,87,255,0.4)}

/* HERO */
.fcq-hero{background:linear-gradient(145deg,#001f6b 0%,#0047d9 38%,#006bff 62%,#002a8a 100%);padding:82px 48px 0;text-align:center;position:relative;overflow:hidden;width:100%;background-size:180% 180%;animation:fcqHeroShift 16s ease-in-out infinite;box-shadow:inset 0 -36px 80px rgba(0,20,80,.25),0 22px 48px rgba(0,31,99,.18)}
.fcq-hero::before{content:'';position:absolute;inset:-10%;background-image:radial-gradient(circle at 20% 22%,rgba(255,255,255,0.30) 0%,transparent 30%),radial-gradient(circle at 78% 18%,rgba(147,197,253,0.28) 0%,transparent 30%),radial-gradient(circle at 50% 76%,rgba(255,255,255,0.16) 0%,transparent 42%),linear-gradient(180deg,rgba(255,255,255,.06),transparent 42%);pointer-events:none;z-index:0}
.fcq-hero::after{content:'';position:absolute;inset:0;background-image:linear-gradient(118deg,transparent 0%,rgba(255,255,255,.04) 42%,rgba(255,255,255,.15) 50%,transparent 58%),linear-gradient(90deg,rgba(255,255,255,.045) 1px,transparent 1px);background-size:300px 300px,84px 84px;opacity:.70;pointer-events:none;z-index:0;animation:fcqFiberDrift 22s linear infinite}
.fcq-hero .fcq-hero-eyebrow,.fcq-hero h1,.fcq-hero p,.fcq-hero-stats,.fcq-hero-wave{position:relative;z-index:1}
.fcq-hero h1{font-size:clamp(32px,4.5vw,58px);font-weight:800;color:#fff;letter-spacing:-0.8px;line-height:1.08;margin-bottom:18px;text-shadow:0 10px 34px rgba(0,22,82,.38)}
.fcq-hero h1 em{font-style:normal;color:#dbeafe;text-shadow:0 0 28px rgba(191,219,254,.35)}
.fcq-hero p{font-size:17px;color:rgba(255,255,255,0.94);max-width:640px;margin:0 auto 34px;line-height:1.75;text-align:center;display:block;padding-top:15px !important;padding-bottom:15px !important;text-shadow:0 2px 14px rgba(0,31,99,.28)}
.fcq-hero-eyebrow{display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,0.20);border:1.5px solid rgba(255,255,255,0.55);border-radius:999px;padding:9px 22px;font-size:12px;font-weight:800;color:#fff;letter-spacing:.9px;text-transform:uppercase;margin-bottom:26px;box-shadow:0 8px 24px rgba(0,20,80,0.22),inset 0 1px 0 rgba(255,255,255,.25);backdrop-filter:blur(12px)}
.fcq-hero-dot{width:6px;height:6px;border-radius:50%;background:#4ade80;animation:fcqPulse 1.5s ease-in-out infinite}
@keyframes fcqPulse{0%,100%{opacity:1}50%{opacity:.3}}
@keyframes fcqHeroShift{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}
@keyframes fcqFiberDrift{from{background-position:0 0,0 0}to{background-position:600px 600px,84px 0}}
.fcq-hero-stats{display:inline-flex;align-items:center;justify-content:center;gap:0;margin:0 auto 22px;padding:24px 36px;border-radius:26px;background:linear-gradient(180deg,rgba(255,255,255,.18),rgba(255,255,255,.10));border:1.5px solid rgba(255,255,255,.28);box-shadow:0 18px 55px rgba(0,20,80,.32),inset 0 1px 0 rgba(255,255,255,.30);backdrop-filter:blur(16px);max-width:920px}
.fcq-hero-stat{min-width:135px;padding:0 24px;text-align:center}
.fcq-hero-stat-num{display:block;font-size:34px;font-weight:800;color:#fff;letter-spacing:-.8px;line-height:1;text-shadow:0 7px 22px rgba(0,20,80,.32)}
.fcq-hero-stat-label{display:block;margin-top:8px;font-size:11.5px;font-weight:800;color:rgba(255,255,255,.82);text-transform:uppercase;letter-spacing:.9px}
.fcq-hero-stat-divider{width:1px;height:54px;background:linear-gradient(180deg,transparent,rgba(255,255,255,.38),transparent)}
.fcq-hero-wave{display:block;width:calc(100% + 4px);margin-left:-2px;margin-bottom:-2px;line-height:0}
.fcq-hero-wave svg{display:block;width:100%;height:80px}

/* PROGRESS */
.fcq-progress-wrap{background:#fff;border-radius:20px;box-shadow:0 4px 24px rgba(0,0,0,0.08);padding:32px 56px;margin:40px auto 0;max-width:100%;position:relative;z-index:10}
.fcq-progress{display:flex;align-items:flex-start;justify-content:space-between;position:relative}
.fcq-progress::before{content:'';position:absolute;top:18px;left:28px;right:28px;height:2px;background:#e5e8f0;z-index:0}
.fcq-progress-filled{position:absolute;top:18px;left:28px;height:2px;background:#0057FF;z-index:1;transition:width .4s ease;width:0%}
.fcq-step{display:flex;flex-direction:column;align-items:center;gap:10px;position:relative;z-index:2}
.fcq-step-circle{width:36px;height:36px;border-radius:50%;border:2px solid #d1d5db;background:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#9ca3af;transition:all .3s}
.fcq-step-label{font-size:11.5px;font-weight:600;color:#9ca3af;white-space:nowrap;transition:color .3s}
.fcq-step.active .fcq-step-circle{border-color:#0057FF;background:#0057FF;color:#fff;box-shadow:0 0 0 5px rgba(0,87,255,0.12)}
.fcq-step.active .fcq-step-label{color:#0057FF}
.fcq-step.done .fcq-step-circle{border-color:#10b981;background:#10b981;color:#fff}
.fcq-step.done .fcq-step-label{color:#10b981}

/* TRUST ROW */
.fcq-trust-row{display:flex;flex-wrap:wrap;justify-content:center;gap:14px;margin:40px auto 44px;max-width:100%;padding:20px 32px;background:#fff;border-radius:16px;border:1px solid #e5e8f0;box-shadow:0 2px 12px rgba(0,0,0,0.04)}
.fcq-trust-badge{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#374151}
.fcq-trust-icon{font-size:18px}

/* PANELS */
.fcq-panel{display:none;animation:fcqFade .3s ease}
.fcq-panel.active{display:block}
@keyframes fcqFade{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

/* PANEL HEAD */
.fcq-panel-head{text-align:center;margin-bottom:36px;margin-top:8px}
.fcq-panel-head h2{font-size:clamp(22px,3.5vw,32px);font-weight:800;color:#111827;margin-bottom:10px;letter-spacing:-0.5px;text-align:center}
.fcq-panel-head h2 span{color:#0057FF}
.fcq-panel-head p{font-size:15px;color:#6b7280;max-width:480px;margin:0 auto;line-height:1.7;text-align:center;display:block}

/* WHITE CARD */
.fcq-card{background:#fff;border:1px solid #e5e8f0;border-radius:20px;padding:52px 48px;box-shadow:0 2px 12px rgba(0,0,0,0.04)}

/* ADDRESS CARD */
.fcq-addr-card{max-width:1100px;width:100%;margin:0 auto;padding:56px 80px;border-radius:24px;box-shadow:0 8px 48px rgba(0,87,255,0.10),0 2px 8px rgba(0,0,0,0.05);border:1.5px solid #dce8ff;text-align:center}
.fcq-addr-head{margin-bottom:40px}
.fcq-addr-icon{margin:0 auto 20px;display:inline-block}
.fcq-addr-title{font-size:clamp(22px,3vw,32px);font-weight:800;color:#111827;margin-bottom:12px;letter-spacing:-0.3px;text-align:center}
.fcq-addr-title span{color:#0057FF}
.fcq-addr-sub{font-size:15px;color:#6b7280;max-width:460px;margin:0 auto;line-height:1.65;text-align:center;display:block}
.fcq-addr-field{display:flex;flex-direction:column;gap:0;text-align:left;margin-bottom:32px}
.fcq-addr-label{font-size:13px;font-weight:800;color:#374151;letter-spacing:.5px;margin-bottom:10px;display:block;text-transform:uppercase}
.fcq-addr-input{width:100%;background:#f8faff;border:2px solid #d1d9f0;border-radius:12px;padding:18px 22px;font-size:16px;font-family:'Plus Jakarta Sans',sans-serif;color:#111827;outline:none;transition:border-color .2s,box-shadow .2s;box-shadow:0 1px 3px rgba(0,0,0,0.05)}
.fcq-addr-input-lg{font-size:17px !important;padding:22px 24px !important;border-radius:14px !important}
.fcq-addr-input::placeholder{color:#9ca3af}
.fcq-addr-input:focus{border-color:#0057FF;background:#fff;box-shadow:0 0 0 4px rgba(0,87,255,0.10)}
.fcq-addr-biz-row{margin:4px 0 32px;text-align:left}
.fcq-addr-biz-label{display:inline-flex;align-items:center;gap:12px;font-size:15px;color:#374151;font-weight:500;cursor:pointer}
.fcq-addr-biz-check{width:20px;height:20px;border-radius:4px;cursor:pointer;accent-color:#0057FF;flex-shrink:0}
.fcq-addr-cta{margin-top:16px}
.fcq-addr-big{font-size:17px !important;padding:22px 24px !important;border-radius:14px !important;width:100%}
.fcq-addr-check-row{margin:24px 0 8px;text-align:left}
.fcq-addr-check-label{display:inline-flex;align-items:center;gap:12px;cursor:pointer;font-size:15px;color:#374151;font-weight:500;user-select:none}
.fcq-addr-checkbox{display:none}
.fcq-addr-check-box{width:22px;height:22px;border-radius:6px;border:2px solid #d1d5db;background:#fff;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s}
.fcq-addr-checkbox:checked + .fcq-addr-check-box{background:#0057FF;border-color:#0057FF}
.fcq-addr-checkbox:checked + .fcq-addr-check-box::after{content:'✓';color:#fff;font-size:13px;font-weight:800;line-height:1}
.fcq-addr-btn{width:100%;max-width:420px;padding:20px 40px !important;font-size:17px !important;border-radius:50px !important;box-shadow:0 6px 24px rgba(0,87,255,0.35) !important;margin-bottom:16px}
.fcq-addr-note{font-size:12.5px;color:#9ca3af;text-align:center}

/* AVAILABILITY RESULT */
#fcq-avail-result{margin-top:18px;border-radius:14px;padding:16px 20px;display:none;align-items:center;gap:14px;background:#f0fdf4;border:1.5px solid #86efac}
#fcq-avail-result.show{display:flex}
.fcq-avail-icon{width:40px;height:40px;border-radius:50%;background:#10b981;color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.fcq-avail-text strong{display:block;font-size:15px;font-weight:700;color:#065f46}
.fcq-avail-text span{font-size:13px;color:#6b7280}

/* TYPE CARDS */
#fcq-type-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;max-width:560px;margin:0 auto 32px}
.fcq-type-card{background:#fff;border:2px solid #e5e8f0;border-radius:20px;padding:32px 24px;cursor:pointer;text-align:center;transition:border-color .2s,box-shadow .2s,transform .15s}
.fcq-type-card:hover{border-color:#0057FF;box-shadow:0 8px 24px rgba(0,87,255,0.1);transform:translateY(-3px)}
.fcq-type-card.selected{border-color:#0057FF;box-shadow:0 8px 32px rgba(0,87,255,0.15);transform:translateY(-3px)}
.fcq-type-icon{font-size:48px;margin-bottom:12px;display:block}
.fcq-type-name{font-size:20px;font-weight:800;color:#111827;margin-bottom:6px}
.fcq-type-desc{font-size:13px;color:#6b7280;line-height:1.6}

/* PLAN CARDS */
#fcq-plans-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));gap:14px;margin-bottom:28px}
.fcq-plan-card{background:#fff;border:2px solid #e5e8f0;border-radius:20px;padding:24px 18px 20px;cursor:pointer;text-align:center;transition:border-color .2s,box-shadow .2s,transform .15s;position:relative;overflow:hidden}
.fcq-plan-card:hover{border-color:#0057FF;box-shadow:0 8px 24px rgba(0,87,255,0.1);transform:translateY(-4px)}
.fcq-plan-card.selected{border-color:#0057FF;box-shadow:0 8px 32px rgba(0,87,255,0.15);transform:translateY(-4px)}
.fcq-plan-card.hidden{display:none}
.fcq-plan-card[data-color="gold"] .fcq-plan-accent{background:#fbbf24}
.fcq-plan-card[data-color="blue"] .fcq-plan-accent{background:#0057FF}
.fcq-plan-card[data-color="teal"] .fcq-plan-accent{background:#0d9488}
.fcq-plan-card[data-color="purple"] .fcq-plan-accent{background:#7c3aed}
.fcq-plan-card[data-color="green"] .fcq-plan-accent{background:#16a34a}
.fcq-plan-card[data-color="gold"].selected{border-color:#fbbf24;box-shadow:0 8px 32px rgba(251,191,36,0.2)}
.fcq-plan-card[data-color="teal"].selected{border-color:#0d9488;box-shadow:0 8px 32px rgba(13,148,136,0.2)}
.fcq-plan-card[data-color="purple"].selected{border-color:#7c3aed;box-shadow:0 8px 32px rgba(124,58,237,0.2)}
.fcq-plan-card[data-color="green"].selected{border-color:#16a34a;box-shadow:0 8px 32px rgba(22,163,74,0.2)}
.fcq-plan-accent{position:absolute;top:0;left:0;right:0;height:5px}
.fcq-plan-badge{display:inline-block;font-size:10px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;padding:4px 12px;border-radius:20px;margin-bottom:12px;margin-top:6px}
.badge-gold{background:#fef3c7;color:#92400e}
.badge-blue{background:#dbeafe;color:#1e40af}
.badge-teal{background:#ccfbf1;color:#0f766e}
.badge-purple{background:#ede9fe;color:#5b21b6}
.badge-green{background:#dcfce7;color:#15803d}
.fcq-plan-name{font-size:14px;font-weight:800;color:#111827;margin-bottom:6px;line-height:1.3}
.fcq-plan-speed{font-size:26px;font-weight:800;color:#0057FF;line-height:1}
.fcq-plan-updown{font-size:11px;color:#9ca3af;margin-bottom:10px}
.fcq-plan-price{font-size:30px;font-weight:800;color:#111827;line-height:1}
.fcq-plan-price sup{font-size:15px;font-weight:700;vertical-align:super}
.fcq-plan-price sub{font-size:12px;font-weight:500;color:#9ca3af}
.fcq-plan-tag{font-size:12px;color:#6b7280;margin:8px 0 14px;min-height:30px;line-height:1.5}
.fcq-plan-features{list-style:none;text-align:left;border-top:1px solid #f3f4f6;padding-top:12px}
.fcq-plan-features li{font-size:11.5px;color:#374151;padding:3px 0;display:flex;align-items:flex-start;gap:7px;line-height:1.5}
.fcq-plan-features li::before{content:'\2713';color:#10b981;font-weight:800;flex-shrink:0;margin-top:1px}
.fcq-speed-bar-wrap{margin:12px auto 6px;background:#eef2f7;border-radius:999px;height:10px;overflow:hidden;max-width:210px;box-shadow:inset 0 1px 2px rgba(17,24,39,0.10)}
.fcq-speed-bar{height:10px;border-radius:999px;background:#0057FF;transition:width .3s;box-shadow:0 2px 8px rgba(0,87,255,0.30)}
.fcq-plan-card[data-color="gold"] .fcq-speed-bar{background:#fbbf24}
.fcq-plan-card[data-color="teal"] .fcq-speed-bar{background:#0d9488}
.fcq-plan-card[data-color="purple"] .fcq-speed-bar{background:#7c3aed}
.fcq-plan-card[data-color="green"] .fcq-speed-bar{background:#16a34a}
.fcq-select-check{width:26px;height:26px;border-radius:50%;border:2px solid #d1d5db;margin:12px auto 0;display:flex;align-items:center;justify-content:center;font-size:13px;color:transparent;transition:all .2s}
.fcq-plan-card.selected .fcq-select-check{background:#0057FF;border-color:#0057FF;color:#fff}

/* UPSELL */
#fcq-upsell-card{background:#fff;border:2px solid #e5e8f0;border-radius:24px;padding:44px;max-width:600px;margin:0 auto;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,0.06)}
.fcq-upsell-icon-wrap{width:72px;height:72px;border-radius:20px;background:#eff6ff;border:1px solid #bfdbfe;display:flex;align-items:center;justify-content:center;font-size:32px;margin:0 auto 20px}
.fcq-upsell-title{font-size:24px;font-weight:800;color:#111827;margin-bottom:8px}
.fcq-upsell-sub{font-size:14px;color:#6b7280;margin-bottom:28px}
.fcq-upsell-features{list-style:none;margin:0 auto 28px;max-width:440px;text-align:left}
.fcq-upsell-features li{font-size:14px;color:#374151;padding:12px 0;display:flex;align-items:center;gap:12px;border-bottom:1px solid #f3f4f6}
.fcq-upsell-features li:last-child{border-bottom:none}
.fcq-upsell-features li::before{content:'\2713';color:#10b981;font-weight:800;font-size:15px;flex-shrink:0}
.fcq-upsell-price{font-size:28px;font-weight:800;color:#0057FF;margin-bottom:28px}
.fcq-upsell-price span{font-size:14px;font-weight:500;color:#9ca3af}
.fcq-upsell-btns{display:flex;gap:12px;flex-wrap:wrap;justify-content:center}

/* SUMMARY BAR */
#fcq-summary-bar{background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1.5px solid #bfdbfe;border-radius:16px;padding:24px 28px;margin-bottom:24px}
#fcq-summary-bar h4{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:1.5px;color:#6b7280;margin-bottom:10px}
.fcq-summary-plan-name{font-size:22px;font-weight:800;color:#111827;margin-bottom:2px}
.fcq-summary-plan-speed{font-size:13px;color:#6b7280;margin-bottom:16px}
.fcq-summary-line{display:flex;justify-content:space-between;font-size:13.5px;padding:8px 0;border-bottom:1px solid #bfdbfe;color:#374151}
.fcq-summary-line:last-child{border-bottom:none;font-weight:800;font-size:16px;padding-top:14px;color:#111827}
.fcq-ins-badge{background:#d1fae5;color:#065f46;border-radius:5px;padding:2px 7px;font-size:10px;font-weight:700;margin-left:6px}

/* SECTION TITLE */
.fcq-section-title{font-size:11px;font-weight:800;color:#6b7280;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid #f3f4f6}

/* CARD ICONS */
.fcq-card-icons{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.fcq-card-icon{background:#f9fafb;border:1px solid #e5e8f0;border-radius:8px;padding:5px 14px;font-size:11px;font-weight:700;color:#374151;letter-spacing:.5px}

/* PAYMENT */
.fcq-pay-summary{background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:14px;padding:22px 26px;margin-bottom:28px}
.fcq-pay-row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #e0f2fe;font-size:14px;color:#374151}
.fcq-pay-row:last-child{border-bottom:none;font-weight:800;font-size:16px;color:#111827;padding-top:14px}

/* FORM FIELDS */
.fcq-field-row{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-bottom:22px}
.fcq-field-row.three{grid-template-columns:2fr 1fr 1fr}
.fcq-field-row.full{grid-template-columns:1fr}
.fcq-field{display:flex;flex-direction:column;gap:8px}
.fcq-field label{font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:1px}
.fcq-field input,.fcq-field select{background:#f9fafb;border:1.5px solid #e5e8f0;border-radius:12px;padding:16px 20px;font-size:15px;font-family:'Plus Jakarta Sans',sans-serif;color:#111827;outline:none;transition:border-color .2s,box-shadow .2s}
.fcq-field input::placeholder{color:#9ca3af}
.fcq-field input:focus,.fcq-field select:focus{border-color:#0057FF;background:#fff;box-shadow:0 0 0 4px rgba(0,87,255,0.08)}
.fcq-field input.error,.fcq-field select.error{border-color:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,0.08)}
.fcq-error-msg{font-size:12px;color:#ef4444;display:none;font-weight:600}
.fcq-error-msg.show{display:block}

/* BUTTONS */
.fcq-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:17px 40px;border-radius:12px;font-size:15px;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;cursor:pointer;border:none;letter-spacing:.2px;transition:all .18s;white-space:nowrap}
.fcq-btn-primary{background:#0057FF;color:#fff;box-shadow:0 4px 16px rgba(0,87,255,0.3)}
.fcq-btn-primary:hover{background:#0041CC;transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,87,255,0.4)}
.fcq-btn-outline{background:#fff;color:#374151;border:1.5px solid #d1d5db}
.fcq-btn-outline:hover{border-color:#9ca3af;color:#111827}
.fcq-btn-success{background:#059669;color:#fff;box-shadow:0 4px 16px rgba(5,150,105,0.3)}
.fcq-btn-success:hover{background:#047857;transform:translateY(-2px);box-shadow:0 8px 24px rgba(5,150,105,0.4)}
.fcq-btn-ghost{background:#f9fafb;color:#6b7280;border:1.5px solid #e5e8f0}
.fcq-btn-ghost:hover{background:#f3f4f6;color:#374151}
.fcq-btn-row{display:flex;gap:14px;justify-content:flex-end;margin-top:36px;flex-wrap:wrap;align-items:center}
.fcq-btn-row .fcq-btn-outline{margin-right:auto}
.fcq-spinner{width:16px;height:16px;border:2.5px solid rgba(255,255,255,0.35);border-top-color:#fff;border-radius:50%;animation:fcqSpin .7s linear infinite;display:none}
@keyframes fcqSpin{to{transform:rotate(360deg)}}
@keyframes fcqToastIn{from{opacity:0;transform:translateX(-50%) translateY(16px)}to{opacity:1;transform:translateX(-50%) translateY(0)}}
@keyframes fcqToastOut{from{opacity:1;transform:translateX(-50%) translateY(0)}to{opacity:0;transform:translateX(-50%) translateY(16px)}}

/* BOTTOM TRUST */
.fcq-bottom-trust{display:flex;flex-wrap:wrap;justify-content:center;gap:10px;margin-top:24px}
.fcq-bottom-trust-item{display:flex;align-items:center;gap:6px;font-size:12.5px;color:#6b7280;font-weight:500}

/* CONFIRMATION */
#fcq-step-confirm{text-align:center}
.fcq-confirm-shell{max-width:760px;margin:0 auto;background:#fff;border:1px solid #dbe7ff;border-radius:28px;padding:34px;box-shadow:0 18px 60px rgba(0,87,255,0.14),0 4px 18px rgba(17,24,39,0.06)}
.fcq-confirm-lottie{width:86px;height:86px;border-radius:28px;background:linear-gradient(135deg,#d1fae5,#eff6ff);border:2px solid #6ee7b7;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:38px;animation:fcqPop .4s cubic-bezier(0.34,1.56,0.64,1) both}
@keyframes fcqPop{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
.fcq-confirm-title{font-size:34px;font-weight:800;color:#111827;margin-bottom:10px;letter-spacing:-0.5px}
.fcq-confirm-sub{font-size:15px;color:#6b7280;max-width:560px;margin:0 auto 26px;line-height:1.8}
.fcq-confirm-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:0 0 18px}
.fcq-confirm-cell{background:#f8faff;border:1px solid #dbe7ff;border-radius:16px;padding:18px 20px;text-align:left;box-shadow:0 2px 8px rgba(0,0,0,0.03)}
.fcq-confirm-cell-label{font-size:10px;font-weight:800;color:#8b9ab7;text-transform:uppercase;letter-spacing:1.2px;margin-bottom:7px}
.fcq-confirm-cell-value{font-size:15px;font-weight:800;color:#111827}
.fcq-confirm-order{background:linear-gradient(135deg,#0057FF,#0041CC);border-radius:20px;padding:24px 28px;margin:0 0 18px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 12px 28px rgba(0,87,255,0.25)}
.fcq-confirm-order-label{font-size:11px;font-weight:800;color:rgba(255,255,255,0.75);text-transform:uppercase;letter-spacing:1.2px;text-align:left}
.fcq-confirm-order-num{font-size:24px;font-weight:800;color:#fff;letter-spacing:2px;text-align:left}
.fcq-confirm-items{background:#f8faff;border:1px solid #dbe7ff;border-radius:18px;overflow:hidden;margin:0}
.fcq-confirm-items h4{font-size:10px;font-weight:800;color:#8b9ab7;text-transform:uppercase;letter-spacing:1.4px;padding:16px 20px;border-bottom:1px solid #dbe7ff;background:#eef6ff}
.fcq-confirm-items ul{list-style:none}
.fcq-confirm-items li{font-size:14px;color:#374151;padding:15px 22px;display:flex;align-items:flex-start;gap:11px;border-bottom:1px solid #e8eef8;line-height:1.5;text-align:left}
.fcq-confirm-items li:last-child{border-bottom:none;font-weight:800;font-size:17px;color:#0057FF;background:#eff6ff;justify-content:center;text-align:center}
.fcq-confirm-items li::before{content:'\2713';color:#10b981;font-weight:800;flex-shrink:0;margin-top:1px}
.fcq-confirm-items li:last-child::before{display:none}
.fcq-demo-notice{background:#fffbeb;border:1px solid #fcd34d;border-radius:14px;padding:14px 22px;font-size:12.5px;color:#92400e;max-width:760px;margin:22px auto 0;line-height:1.65;text-align:center}

/* RESPONSIVE */
@media(max-width:900px){#fcq-plans-grid{grid-template-columns:repeat(3,1fr)}.fcq-progress-wrap{padding:24px 24px;margin:24px 16px 0}.fcq-addr-card{padding:40px 32px !important}}
@media(max-width:640px){.fcq-hero-stats{display:grid;grid-template-columns:1fr 1fr;width:100%;padding:18px;margin-bottom:18px}.fcq-hero-stat-num{font-size:28px}.fcq-hero-stat{min-width:0;padding:10px}.fcq-hero-stat-divider{display:none}.fcq-confirm-shell{padding:24px 16px;border-radius:22px}.fcq-field-row,.fcq-field-row.three{grid-template-columns:1fr}#fcq-plans-grid{grid-template-columns:1fr 1fr}#fcq-type-grid{grid-template-columns:1fr 1fr}.fcq-btn-row{flex-direction:column}.fcq-btn-row .fcq-btn-outline{margin-right:0}.fcq-upsell-btns{flex-direction:column}.fcq-confirm-grid{grid-template-columns:1fr}.fcq-card{padding:32px 24px}.fcq-progress-wrap{padding:20px 16px}.fcq-progress::before{left:18px;right:18px}.fcq-hero{padding:50px 20px 0}.fcq-addr-card{padding:32px 20px !important}}
@media(max-width:440px){#fcq-plans-grid,#fcq-type-grid{grid-template-columns:1fr}.fcq-nav-link{display:none}}
</style>
<?php }

function fiberco_quote_html() {
    $plans = fiberco_quote_plans();
    $states = array('AL','AK','AZ','AR','CA','CO','CT','DE','FL','GA','HI','ID','IL','IN','IA','KS','KY','LA','ME','MD','MA','MI','MN','MS','MO','MT','NE','NV','NH','NJ','NM','NY','NC','ND','OH','OK','OR','PA','RI','SC','SD','TN','TX','UT','VT','VA','WA','WV','WI','WY');
?>
<div id="fcq-wrap">

  <!-- Utility bar -->
  <div class="fcq-utilbar">
    <a href="tel:18005551234">&#128222; 1-800-555-1234</a>
    <div class="fcq-utilbar-divider"></div>
    <a href="https://startwebservicesbackup.com/fiber/">My Account</a>
    <div class="fcq-utilbar-divider"></div>
    <a href="https://startadvertising.com" target="_blank" rel="noopener">About Start</a>
  </div>

  <!-- Main nav -->
  <div class="fcq-topbar">
    <div class="fcq-topbar-inner">
      <a href="https://startwebservicesbackup.com/fiber/" class="fcq-logo">
        <div class="fcq-logo-mark">
          <svg viewBox="0 0 24 24" fill="none" width="22" height="22"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" fill="#fff" stroke="#fff" stroke-width="1.5" stroke-linejoin="round"/></svg>
        </div>
        <div class="fcq-logo-text">
          <span class="fcq-logo-name">FiberCo</span>
          <span class="fcq-logo-tagline">Fiber Internet</span>
        </div>
      </a>
      <nav class="fcq-nav">
        <a href="https://startwebservicesbackup.com/fiber/" class="fcq-nav-link">Home</a>
        <a href="https://startwebservicesbackup.com/fiber/" class="fcq-nav-link">Plans and Pricing</a>
        <a href="https://startwebservicesbackup.com/fiber/demo-page/" class="fcq-nav-link">Support</a>
        <a href="https://startwebservicesbackup.com/fiber/" class="fcq-nav-link">Business</a>
        <a href="tel:18005551234" class="fcq-nav-phone"><span class="fcq-nav-phone-icon">&#128222;</span>1-800-555-1234</a>
        <a href="https://startwebservicesbackup.com/fiber/" class="fcq-nav-cta">Get a Quote</a>
      </nav>
    </div>
  </div>

  <div class="fcq-hero">
    <div class="fcq-hero-eyebrow"><span class="fcq-hero-dot"></span>Free Availability Check</div>
    <h1>Fast Fiber Internet<br><em>Built for Every Home and Business</em></h1>
    <p>Enter your address and get a personalized quote in under 2 minutes &mdash; no contracts, no data caps, no surprises.</p>
    <div class="fcq-hero-stats">
    <div class="fcq-hero-stat"><span class="fcq-hero-stat-num">4 Gbps</span><span class="fcq-hero-stat-label">Max Speed</span></div>
    <div class="fcq-hero-stat-divider"></div>
    <div class="fcq-hero-stat"><span class="fcq-hero-stat-num">$0</span><span class="fcq-hero-stat-label">Install Fee</span></div>
    <div class="fcq-hero-stat-divider"></div>
    <div class="fcq-hero-stat"><span class="fcq-hero-stat-num">No</span><span class="fcq-hero-stat-label">Contracts</span></div>
    <div class="fcq-hero-stat-divider"></div>
    <div class="fcq-hero-stat"><span class="fcq-hero-stat-num">24/7</span><span class="fcq-hero-stat-label">Support</span></div>
  </div>
  <div class="fcq-hero-wave"><svg viewBox="0 0 1440 80" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg"><path d="M0,80 L0,80 C240,20 480,0 720,0 C960,0 1200,20 1440,80 L1440,80 Z" fill="#f0f4ff"/></svg></div>
  </div>

  <div class="fcq-inner">

    <div class="fcq-trust-row">
      <div class="fcq-trust-badge"><span class="fcq-trust-icon">&#128274;</span>Secure and Encrypted</div>
      <div class="fcq-trust-badge"><span class="fcq-trust-icon">&#128197;</span>No Contracts</div>
      <div class="fcq-trust-badge"><span class="fcq-trust-icon">&#9889;</span>Free Installation</div>
      <div class="fcq-trust-badge"><span class="fcq-trust-icon">&#128241;</span>24/7 Support</div>
    </div>

    <!-- Progress/timeline sits directly above the active step's card -->
    <div class="fcq-progress-wrap">
      <div class="fcq-progress" id="fcq-progress">
        <div class="fcq-progress-filled" id="fcq-progress-bar"></div>
        <div class="fcq-step active" data-step="1"><div class="fcq-step-circle">1</div><div class="fcq-step-label">Address</div></div>
        <div class="fcq-step" data-step="2"><div class="fcq-step-circle">2</div><div class="fcq-step-label">Plan</div></div>
        <div class="fcq-step" data-step="3"><div class="fcq-step-circle">3</div><div class="fcq-step-label">Your Info</div></div>
        <div class="fcq-step" data-step="4"><div class="fcq-step-circle">4</div><div class="fcq-step-label">Payment</div></div>
        <div class="fcq-step" data-step="5"><div class="fcq-step-circle">&#10003;</div><div class="fcq-step-label">Done</div></div>
      </div>
    </div>

    <!-- STEP 1: Address -->
    <div class="fcq-panel active" id="fcq-step-1">
      <div class="fcq-card fcq-addr-card">
        <div class="fcq-addr-head">
          <div class="fcq-addr-icon">
            <svg viewBox="0 0 64 64" fill="none" width="64" height="64"><circle cx="32" cy="32" r="32" fill="#eff6ff"/><path d="M32 14C25.37 14 20 19.37 20 26c0 10 12 24 12 24s12-14 12-24c0-6.63-5.37-12-12-12zm0 16a4 4 0 110-8 4 4 0 010 8z" fill="#0057FF"/></svg>
          </div>
          <h2 class="fcq-addr-title">Check <span>Availability</span> at Your Address</h2>
          <p class="fcq-addr-sub">Enter your address to find fiber plans and services available in your area.</p>
        </div>
        <div class="fcq-addr-field">
          <label class="fcq-addr-label">Address</label>
          <input class="fcq-addr-input fcq-addr-big" type="text" id="fcq-street" placeholder="12345 Main St, City, State, ZIP" autocomplete="street-address">
          <span class="fcq-error-msg" id="fcq-err-street">Please enter your address.</span>
        </div>
        <input type="hidden" id="fcq-city" value="N/A">
        <input type="hidden" id="fcq-state" value="N/A">
        <input type="hidden" id="fcq-zip" value="00000">
        <div class="fcq-addr-biz-row">
          <label class="fcq-addr-biz-label">
            <input type="checkbox" id="fcq-is-business" class="fcq-addr-biz-check" onchange="fcqSetBizType(this.checked)">
            <span>This is a business address</span>
          </label>
        </div>
        <div id="fcq-avail-result">
          <div class="fcq-avail-icon">&#10003;</div>
          <div class="fcq-avail-text">
            <strong id="fcq-avail-title">&#127881; Fiber is available at your address!</strong>
            <span id="fcq-avail-sub">FiberCo is ready to connect you.</span>
          </div>
        </div>
        <div class="fcq-addr-cta">
          <button class="fcq-btn fcq-btn-primary fcq-addr-btn" id="fcq-check-btn" onclick="fcqCheckAddress()">
            <span id="fcq-check-label">Check Availability</span>
            <div class="fcq-spinner" id="fcq-check-spinner"></div>
          </button>
          <p class="fcq-addr-note">&#128274; Secure check &mdash; we never share your address</p>
        </div>
      </div>
    </div>

    <!-- STEP 2: Plans -->
    <div class="fcq-panel" id="fcq-step-2">
      <div class="fcq-panel-head">
        <h2>Choose Your <span>Internet Plan</span></h2>
        <p>All plans include symmetrical speeds, no data caps, no contracts, and free installation.</p>
      </div>
      <div id="fcq-plans-grid">
        <?php
        $speed_map = array('10 Gbps'=>10000,'5 Gbps'=>5000,'1 Gbps'=>1000,'750 Mbps'=>750,'400 Mbps'=>400,'250 Mbps'=>250);
        foreach($plans as $plan):
          $spd = isset($speed_map[$plan['speed']]) ? $speed_map[$plan['speed']] : 300;
          $pct = round($spd / 10000 * 100);
          $bc = 'badge-'.$plan['color'];
        ?>
        <div class="fcq-plan-card <?php echo esc_attr($plan['type']); ?>-plan" id="card-<?php echo esc_attr($plan['id']); ?>" data-color="<?php echo esc_attr($plan['color']); ?>" onclick="fcqSelectPlan('<?php echo esc_js($plan['id']); ?>')">
          <div class="fcq-plan-accent"></div>
          <?php if($plan['badge']): ?><div class="fcq-plan-badge <?php echo esc_attr($bc); ?>"><?php echo esc_html($plan['badge']); ?></div><?php else: ?><div style="height:26px"></div><?php endif; ?>
          <div class="fcq-plan-name"><?php echo esc_html($plan['name']); ?></div>
          <div class="fcq-plan-speed"><?php echo esc_html($plan['speed']); ?></div>
          <div class="fcq-speed-bar-wrap"><div class="fcq-speed-bar" style="width:<?php echo $pct; ?>%"></div></div>
          <div class="fcq-plan-updown">&#8593; Upload and &#8595; Download</div>
          <div class="fcq-plan-price"><sup>$</sup><?php echo esc_html($plan['price']); ?><sub>/mo</sub></div>
          <div class="fcq-plan-tag"><?php echo wp_kses_post($plan['tag']); ?></div>
          <ul class="fcq-plan-features"><?php foreach($plan['features'] as $f): ?><li><?php echo wp_kses_post($f); ?></li><?php endforeach; ?></ul>
          <div class="fcq-select-check">&#10003;</div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="fcq-btn-row">
        <button class="fcq-btn fcq-btn-outline" onclick="fcqGoTo(1)">&#8592; Back</button>
        <button class="fcq-btn fcq-btn-primary" onclick="fcqPlanNext()">Continue &#8594;</button>
      </div>
    </div>

    <!-- STEP 2b: Upsell -->
    <div class="fcq-panel" id="fcq-step-2b">
      <div class="fcq-panel-head">
        <h2>Add <span>Priority Care Protection?</span></h2>
        <p>One optional add-on to keep your connection covered.</p>
      </div>
      <div id="fcq-upsell-card">
        <div class="fcq-upsell-icon-wrap">&#128737;</div>
        <div class="fcq-upsell-title">Priority Care Protection</div>
        <div class="fcq-upsell-sub">Peace of mind for your home or business connection</div>
        <ul class="fcq-upsell-features">
          <li>Priority same-day or next-day tech dispatch</li>
          <li>Free equipment replacement &mdash; router, ONT and cables</li>
          <li>Covers inside wiring and connection issues</li>
          <li>No service call fees &mdash; ever</li>
          <li>24/7 escalated support hotline</li>
        </ul>
        <div class="fcq-upsell-price">+ $9.99 <span>/ month</span></div>
        <div class="fcq-upsell-btns">
          <button class="fcq-btn fcq-btn-success" onclick="fcqUpsell(true)">&#10003; Yes, Add Protection</button>
          <button class="fcq-btn fcq-btn-ghost" onclick="fcqUpsell(false)">No Thanks, Continue</button>
        </div>
      </div>
      <div class="fcq-btn-row">
        <button class="fcq-btn fcq-btn-outline" onclick="fcqGoTo(2)">&#8592; Back</button>
      </div>
    </div>

    <!-- STEP 3: Info -->
    <div class="fcq-panel" id="fcq-step-3">
      <div class="fcq-panel-head">
        <h2>Tell Us <span>About Yourself</span></h2>
        <p>We'll use this to set up your account and schedule installation.</p>
      </div>
      <div id="fcq-summary-bar">
        <h4>Your Selected Plan</h4>
        <div class="fcq-summary-plan-name" id="fcq-sum-name">&#8212;</div>
        <div class="fcq-summary-plan-speed" id="fcq-sum-speed">&#8212;</div>
        <div class="fcq-summary-line"><span>Monthly Rate</span><span id="fcq-sum-price">&#8212;</span></div>
        <div class="fcq-summary-line" id="fcq-sum-ins-row" style="display:none"><span>Priority Care Protection</span><span>+ $9.99/mo &nbsp;<span class="fcq-ins-badge">ADDED</span></span></div>
        <div class="fcq-summary-line"><span>Installation</span><span style="color:#059669;font-weight:700">FREE (promo)</span></div>
        <div class="fcq-summary-line"><span>First Month Total</span><span id="fcq-sum-total">&#8212;</span></div>
      </div>
      <div class="fcq-card">
        <div class="fcq-section-title">Contact Information</div>
        <div class="fcq-field-row">
          <div class="fcq-field"><label>First Name</label><input type="text" id="fcq-fname" placeholder="Jane" autocomplete="given-name"><span class="fcq-error-msg" id="fcq-err-fname">Required.</span></div>
          <div class="fcq-field"><label>Last Name</label><input type="text" id="fcq-lname" placeholder="Smith" autocomplete="family-name"><span class="fcq-error-msg" id="fcq-err-lname">Required.</span></div>
        </div>
        <div class="fcq-field-row full" style="margin-bottom:16px"><div class="fcq-field"><label>Email Address</label><input type="email" id="fcq-email" placeholder="jane@example.com" autocomplete="email"><span class="fcq-error-msg" id="fcq-err-email">Enter a valid email address.</span></div></div>
        <div class="fcq-field-row full" style="margin-bottom:24px"><div class="fcq-field"><label>Phone Number</label><input type="tel" id="fcq-phone" placeholder="(555) 555-5555" autocomplete="tel"><span class="fcq-error-msg" id="fcq-err-phone">Enter a valid phone number.</span></div></div>
        <div class="fcq-section-title">Preferred Install Date</div>
        <div class="fcq-field-row full" style="margin-bottom:8px"><div class="fcq-field"><label>Install Date</label><input type="date" id="fcq-install-date" autocomplete="off"><span class="fcq-error-msg" id="fcq-err-date">Please select an install date.</span></div></div>
        <p style="font-size:12.5px;color:#9ca3af;margin-top:10px;line-height:1.65">We've pre-selected a date 3 days from today. A technician will confirm your exact window.</p>
      </div>
      <div class="fcq-btn-row">
        <button class="fcq-btn fcq-btn-outline" onclick="fcqGoTo('2b')">&#8592; Back</button>
        <button class="fcq-btn fcq-btn-primary" onclick="fcqInfoNext()">Continue to Payment &#8594;</button>
      </div>
    </div>

    <!-- STEP 4: Payment -->
    <div class="fcq-panel" id="fcq-step-4">
      <div class="fcq-panel-head">
        <h2>Payment <span>Information</span></h2>
        <p>Your first month is billed today. No contracts &mdash; cancel anytime.</p>
      </div>
      <div class="fcq-card">
        <div class="fcq-section-title">Order Summary</div>
        <div class="fcq-pay-summary">
          <div class="fcq-pay-row"><span>Plan</span><span id="fcq-pay-plan" style="font-weight:700">&#8212;</span></div>
          <div class="fcq-pay-row"><span>Monthly Rate</span><span id="fcq-pay-rate">&#8212;</span></div>
          <div class="fcq-pay-row" id="fcq-pay-ins-row" style="display:none"><span>Priority Care Protection</span><span style="color:#059669;font-weight:700">+ $9.99/mo</span></div>
          <div class="fcq-pay-row"><span>Installation</span><span style="color:#059669;font-weight:700">FREE</span></div>
          <div class="fcq-pay-row"><span>Due Today</span><span id="fcq-pay-due">&#8212;</span></div>
        </div>
        <div class="fcq-section-title">Card Details</div>
        <div class="fcq-card-icons"><div class="fcq-card-icon">VISA</div><div class="fcq-card-icon">MC</div><div class="fcq-card-icon">AMEX</div><div class="fcq-card-icon">DISC</div></div>
        <div class="fcq-field-row full" style="margin-bottom:16px"><div class="fcq-field"><label>Name on Card</label><input type="text" id="fcq-card-name" placeholder="Jane Smith" autocomplete="cc-name"><span class="fcq-error-msg" id="fcq-err-cname">Required.</span></div></div>
        <div class="fcq-field-row full" style="margin-bottom:16px"><div class="fcq-field"><label>Card Number</label><input type="text" id="fcq-card-num" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" maxlength="19" autocomplete="cc-number"><span class="fcq-error-msg" id="fcq-err-cnum">Enter a valid 16-digit card number.</span></div></div>
        <div class="fcq-field-row" style="margin-bottom:16px">
          <div class="fcq-field"><label>Expiry Date</label><input type="text" id="fcq-card-exp" placeholder="MM / YY" maxlength="7" autocomplete="cc-exp"><span class="fcq-error-msg" id="fcq-err-cexp">Enter expiry date.</span></div>
          <div class="fcq-field"><label>CVV</label><input type="text" id="fcq-card-cvv" placeholder="&bull;&bull;&bull;" maxlength="4" autocomplete="cc-csc"><span class="fcq-error-msg" id="fcq-err-ccvv">Enter CVV.</span></div>
        </div>
        <p style="font-size:12px;color:#9ca3af;line-height:1.55">&#128274; DEMO &mdash; no real payment will be processed.</p>
      </div>
      <div class="fcq-bottom-trust">
        <span class="fcq-bottom-trust-item">&#128737; SSL Encrypted</span>
        <span class="fcq-bottom-trust-item">&#128203; No hidden fees</span>
        <span class="fcq-bottom-trust-item">&#10003; Cancel anytime</span>
      </div>
      <div class="fcq-btn-row">
        <button class="fcq-btn fcq-btn-outline" onclick="fcqGoTo(3)">&#8592; Back</button>
        <button class="fcq-btn fcq-btn-success" id="fcq-pay-btn" onclick="fcqSubmitOrder()">
          <span id="fcq-pay-label">&#128274; Complete Order</span>
          <div class="fcq-spinner" id="fcq-pay-spinner"></div>
        </button>
      </div>
    </div>

    <!-- STEP 5: Confirmation -->
    <div class="fcq-panel" id="fcq-step-confirm">
      <div class="fcq-confirm-shell">
        <div class="fcq-confirm-lottie">&#10003;</div>
        <div class="fcq-confirm-title">You're all set!</div>
        <div class="fcq-confirm-sub">Your FiberCo order has been received. A confirmation will be sent to <strong id="fcq-conf-email">your email</strong>.<br>A technician will call to confirm your installation appointment.</div>
        <div class="fcq-confirm-order">
          <div><div class="fcq-confirm-order-label">Order Number</div><div class="fcq-confirm-order-num" id="fcq-conf-order">&#8212;</div></div>
          <div style="font-size:36px">&#127881;</div>
        </div>
        <div class="fcq-confirm-grid">
          <div class="fcq-confirm-cell"><div class="fcq-confirm-cell-label">Name</div><div class="fcq-confirm-cell-value" id="fcq-conf-name">&#8212;</div></div>
          <div class="fcq-confirm-cell"><div class="fcq-confirm-cell-label">Account Type</div><div class="fcq-confirm-cell-value" id="fcq-conf-type">&#8212;</div></div>
          <div class="fcq-confirm-cell" style="grid-column:1/-1"><div class="fcq-confirm-cell-label">Service Address</div><div class="fcq-confirm-cell-value" id="fcq-conf-addr">&#8212;</div></div>
          <div class="fcq-confirm-cell" style="grid-column:1/-1"><div class="fcq-confirm-cell-label">Install Date</div><div class="fcq-confirm-cell-value" id="fcq-conf-date">&#8212;</div></div>
        </div>
        <div class="fcq-confirm-items"><h4>What's in Your Order</h4><ul id="fcq-conf-items"></ul></div>
      </div>
      <div class="fcq-demo-notice"><strong>&#9432; DEMO MODE:</strong> No real order was placed and no payment was processed.</div>
      <div style="text-align:center;margin-top:32px"><button class="fcq-btn fcq-btn-outline" onclick="fcqReset()">&#8635; Start Over</button></div>
    </div>

  </div>

  <div style="text-align:center;padding:24px 0 8px;font-size:11px;color:#9ca3af;letter-spacing:.3px">
    Powered by <a href="https://startadvertising.com" target="_blank" rel="noopener" style="color:#0057FF;text-decoration:none;font-weight:600">Start Performance</a>
  </div>

</div>
<?php }

function fiberco_quote_scripts() {
    $plans_json = wp_json_encode(fiberco_quote_plans());
?>
<script>
(function(){
'use strict';
var FCQ_PLANS=<?php echo $plans_json; ?>;
var fcqState={step:1,serviceType:'residential',plan:null,insurance:false,address:{},customer:{}};

window.fcqGoTo=function(step){
  document.querySelectorAll('.fcq-panel').forEach(function(p){p.classList.remove('active')});
  var panelId=(step===5)?'fcq-step-confirm':'fcq-step-'+step;
  var el=document.getElementById(panelId);
  if(el) el.classList.add('active');
  fcqState.step=step;
  var dotNum=(step==='2b')?2:parseInt(step,10);
  fcqUpdateProgress(dotNum);
  var target=document.querySelector('.fcq-progress-wrap');
  if(target) window.scrollTo({top:target.getBoundingClientRect().top+window.pageYOffset-24,behavior:'smooth'});
};

function fcqUpdateProgress(current){
  for(var i=1;i<=5;i++){
    var dot=document.querySelector('.fcq-step[data-step="'+i+'"]');
    if(!dot) continue;
    dot.classList.remove('active','done');
    if(i<current) dot.classList.add('done');
    else if(i===current) dot.classList.add('active');
  }
  var pct=Math.max(0,Math.min(100,(current-1)/4*100));
  var bar=document.getElementById('fcq-progress-bar');
  if(bar) bar.style.width=pct+'%';
}

window.fcqCheckAddress=function(){
  var address=document.getElementById('fcq-street').value.trim();
  var isBiz=document.getElementById('fcq-is-business');
  var inp=document.getElementById('fcq-street');
  var err=document.getElementById('fcq-err-street');
  // Loosened for the demo: availability always returns "available", so we just
  // capture the address instead of demanding a perfect street+city+state+ZIP.
  // Accept anything with a few characters and at least one letter (a street/city name).
  var hasFullAddr = address.length >= 4 && /[a-z]/i.test(address);
  if(!address){
    inp.classList.add('error');
    err.textContent = '⚠ Please enter your street address.';
    err.classList.add('show');return;
  }
  if(!hasFullAddr){
    inp.classList.add('error');
    err.innerHTML = '📍 Please enter your address to check availability — e.g. <em>123 Main St, Rock Hill SC</em>';
    err.style.cssText='display:block;font-size:13.5px;color:#b45309;background:#fffbeb;border:1.5px solid #fcd34d;border-radius:10px;padding:12px 16px;margin-top:10px;font-weight:500;line-height:1.6';
    return;
  }
  inp.classList.remove('error');
  err.classList.remove('show');
  err.style.cssText='';
  err.textContent='Please enter your address including city, state and ZIP.';
  // Set service type from checkbox - skip step 1b entirely
  fcqState.serviceType=(isBiz&&isBiz.checked)?'business':'residential';
  document.querySelectorAll('.fcq-plan-card').forEach(function(card){
    card.classList.toggle('hidden',!card.classList.contains(fcqState.serviceType+'-plan'));
    card.classList.remove('selected');
  });
  var btn=document.getElementById('fcq-check-btn');
  var lbl=document.getElementById('fcq-check-label');
  var spn=document.getElementById('fcq-check-spinner');
  btn.disabled=true;lbl.textContent='Checking...';spn.style.display='block';
  setTimeout(function(){
    btn.disabled=false;spn.style.display='none';
    fcqState.address={street:address,city:'',state:'',zip:''};
    var res=document.getElementById('fcq-avail-result');
    res.className='show';
    document.getElementById('fcq-avail-title').textContent='Fiber is available at your address!';
    document.getElementById('fcq-avail-sub').textContent=address;
    lbl.textContent='View Plans';
    btn.onclick=function(){fcqGoTo(2);};
  },1600);
};

window.fcqSetBizType=function(isBiz){
  fcqState.serviceType=isBiz?'business':'residential';
  document.querySelectorAll('.fcq-plan-card').forEach(function(card){
    card.classList.toggle('hidden',!card.classList.contains(fcqState.serviceType+'-plan'));
    card.classList.remove('selected');
  });
  fcqState.plan=null;
};



function fcqSplitChatName(fullName){
  fullName=(fullName||'').trim();
  if(!fullName) return {first:'',last:''};
  var parts=fullName.split(/\s+/);
  return {first:parts.shift()||'',last:parts.join(' ')||''};
}

function fcqSetInputValue(id,value){
  var el=document.getElementById(id);
  if(el && value){el.value=value;}
}


function fcqNormalizePlanId(plan){
  plan=(plan||'').toString().toLowerCase().trim();
  plan=plan.replace(/&amp;/g,'and');
  var compact=plan.replace(/[^a-z0-9]/g,'');
  var map={
    'quantummax':'quantummax','quantummax10gig':'quantummax','10gig':'quantummax','10gbps':'quantummax','highest':'quantummax','fastest':'quantummax','topspeed':'quantummax','maxspeed':'quantummax','best':'quantummax',
    'velocitypro':'velocitypro','velocitypro5gig':'velocitypro','5gig':'velocitypro','5gbps':'velocitypro',
    'everydaygig':'everydaygig','1gig':'everydaygig','1gbps':'everydaygig','gig':'everydaygig',
    'streamwork':'streamwork','streamandwork750':'streamwork','streamwork750':'streamwork','750mbps':'streamwork','750':'streamwork',
    'essentialconnect':'essentialconnect','essentialconnect250':'essentialconnect','250mbps':'essentialconnect','250':'essentialconnect',
    'fastgig':'fastgig','fastgigbusiness':'fastgig',
    'fast400':'fast400','fast400business':'fast400',
    'fast250':'fast250','fast250business':'fast250'
  };
  if(map[compact]) return map[compact];
  if(/quantum|max|fastest|highest|10\s*(gig|gbps|gb)/.test(plan)) return 'quantummax';
  if(/velocity|5\s*(gig|gbps|gb)/.test(plan)) return 'velocitypro';
  if(/everyday|1\s*(gig|gbps|gb)/.test(plan)) return 'everydaygig';
  if(/stream|work|750/.test(plan)) return 'streamwork';
  if(/essential|starter|250/.test(plan)) return 'essentialconnect';
  if(/fast\s*400/.test(plan)) return 'fast400';
  if(/fast\s*250/.test(plan)) return 'fast250';
  if(/business|fast\s*big|fast\s*gig/.test(plan)) return 'fastgig';
  return plan;
}

function fcqPopulatePaymentFromChat(options){
  options=options||{};
  var nameParts=fcqSplitChatName(options.name||'');
  var firstName=options.firstName||options.fname||nameParts.first||'';
  var lastName=options.lastName||options.lname||nameParts.last||'';
  var email=options.email||'';
  var phone=options.phone||'';
  var installDate=options.installDate||options.date||'';
  if(!installDate){
    var def=new Date();
    def.setDate(def.getDate()+3);
    installDate=def.getFullYear()+'-'+String(def.getMonth()+1).padStart(2,'0')+'-'+String(def.getDate()).padStart(2,'0');
  }
  fcqSetInputValue('fcq-fname',firstName);
  fcqSetInputValue('fcq-lname',lastName);
  fcqSetInputValue('fcq-email',email);
  fcqSetInputValue('fcq-phone',phone);
  fcqSetInputValue('fcq-install-date',installDate);
  if(!fcqState.plan){return false;}
  fcqState.customer={fname:firstName,lname:lastName,email:email,phone:phone,date:installDate};
  if(typeof fcqBuildSummary==='function') fcqBuildSummary();
  var p=fcqState.plan,ins=fcqState.insurance;
  var base=parseFloat(p.price);
  var total=ins?(base+9.99).toFixed(2):p.price;
  var payPlan=document.getElementById('fcq-pay-plan'); if(payPlan) payPlan.textContent=p.name+' - '+p.speed;
  var payRate=document.getElementById('fcq-pay-rate'); if(payRate) payRate.textContent='$'+p.price+'/mo';
  var payDue=document.getElementById('fcq-pay-due'); if(payDue) payDue.textContent='$'+total;
  var insPayRow=document.getElementById('fcq-pay-ins-row'); if(insPayRow) insPayRow.style.display=ins?'flex':'none';
  fcqSetInputValue('fcq-card-name',(firstName+' '+lastName).trim());
  return true;
}

window.fibercoQuoteStartFromChat=function(options){
  options=options||{};
  var quoteUrl=(options.quoteUrl||'https://startwebservicesbackup.com/fiber/');
  var address=(options.address||'').trim();
  var type=(options.type||'residential').toLowerCase();
  var plan=fcqNormalizePlanId((options.plan||'').trim());
  var jumpToPayment=!!(options.jumpToPayment||options.jump_to_payment||options.reviewPayment);
  var wrap=document.getElementById('fcq-wrap');
  if(!wrap){
    var params=new URLSearchParams();
    params.set('fcq_chat_quote','1');
    if(address) params.set('address',address);
    if(type) params.set('type',type);
    if(plan) params.set('plan',plan);
    if(options.name) params.set('name',options.name);
    if(options.firstName||options.fname) params.set('firstName',options.firstName||options.fname);
    if(options.lastName||options.lname) params.set('lastName',options.lastName||options.lname);
    if(options.email) params.set('email',options.email);
    if(options.phone) params.set('phone',options.phone);
    if(options.installDate||options.date) params.set('installDate',options.installDate||options.date);
    if(options.protection||options.insurance) params.set('protection','1');
    if(jumpToPayment) params.set('jumpToPayment','1');
    window.location.href=quoteUrl+(quoteUrl.indexOf('?')===-1?'?':'&')+params.toString();
    return;
  }
  if(typeof fcqGoTo==='function') fcqGoTo(1);
  var street=document.getElementById('fcq-street');
  var biz=document.getElementById('fcq-is-business');
  if(biz){
    biz.checked=(type==='business');
    if(typeof fcqSetBizType==='function') fcqSetBizType(biz.checked);
  }
  if(street){
    if(address) street.value=address;
    street.focus();
  }
  var target=document.querySelector('.fcq-progress-wrap')||wrap;
  if(target) window.scrollTo({top:target.getBoundingClientRect().top+window.pageYOffset-24,behavior:'smooth'});

  function continueAfterAvailability(){
    if(plan && typeof fcqSelectPlan==='function') fcqSelectPlan(plan);
    if(jumpToPayment && plan){
      fcqState.insurance=!!(options.protection||options.insurance);
      if(fcqPopulatePaymentFromChat(options)){
        if(typeof fcqGoTo==='function') fcqGoTo(4);
        if(typeof fcqShowToast==='function') fcqShowToast('Finn built your order — review and complete payment.');
        return;
      }
    }
    if(typeof fcqGoTo==='function') fcqGoTo(2);
  }

  var hasFullAddr=address.length>=4 && /[a-z]/i.test(address);
  if(hasFullAddr && typeof fcqCheckAddress==='function'){
    fcqCheckAddress();
    window.setTimeout(continueAfterAvailability,1900);
  } else {
    window.setTimeout(continueAfterAvailability,350);
  }
};

function fcqApplyChatQuoteParams(){
  var qs=new URLSearchParams(window.location.search||'');
  if(!qs.has('fcq_chat_quote')) return;
  window.setTimeout(function(){
    if(typeof window.fibercoQuoteStartFromChat==='function'){
      window.fibercoQuoteStartFromChat({
        address:qs.get('address')||'',
        type:qs.get('type')||'residential',
        plan:qs.get('plan')||'',
        name:qs.get('name')||'',
        firstName:qs.get('firstName')||'',
        lastName:qs.get('lastName')||'',
        email:qs.get('email')||'',
        phone:qs.get('phone')||'',
        installDate:qs.get('installDate')||'',
        jumpToPayment:qs.get('jumpToPayment')==='1',
        protection:qs.get('protection')==='1'
      });
    }
  },400);
}

window.fcqSelectPlan=function(planId){
  document.querySelectorAll('.fcq-plan-card').forEach(function(c){c.classList.remove('selected')});
  var card=document.getElementById('card-'+planId);
  if(card) card.classList.add('selected');
  fcqState.plan=FCQ_PLANS.find(function(p){return p.id===planId;});
};

window.fcqPlanNext=function(){
  if(!fcqState.plan){fcqShowToast('Please select a plan to continue.');return;}
  fcqGoTo('2b');
};

window.fcqUpsell=function(addIt){
  fcqState.insurance=addIt;
  fcqBuildSummary();
  fcqGoTo(3);
};

function fcqBuildSummary(){
  var p=fcqState.plan,ins=fcqState.insurance;
  var base=parseFloat(p.price);
  var total=ins?(base+9.99).toFixed(2):p.price;
  document.getElementById('fcq-sum-name').textContent=p.name;
  document.getElementById('fcq-sum-speed').textContent=p.speed;
  document.getElementById('fcq-sum-price').textContent='$'+p.price+'/mo';
  document.getElementById('fcq-sum-total').textContent='$'+total+'/mo';
  var insRow=document.getElementById('fcq-sum-ins-row');
  if(insRow) insRow.style.display=ins?'flex':'none';
}

window.fcqInfoNext=function(){
  var fname=document.getElementById('fcq-fname').value.trim();
  var lname=document.getElementById('fcq-lname').value.trim();
  var email=document.getElementById('fcq-email').value.trim();
  var phone=document.getElementById('fcq-phone').value.trim();
  var date=document.getElementById('fcq-install-date').value;
  var valid=true;
  function chk(id,errId,test){var el=document.getElementById(id),err=document.getElementById(errId);if(!test){el.classList.add('error');err.classList.add('show');valid=false;}else{el.classList.remove('error');err.classList.remove('show');}}
  chk('fcq-fname','fcq-err-fname',fname.length>0);
  chk('fcq-lname','fcq-err-lname',lname.length>0);
  chk('fcq-email','fcq-err-email',/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email));
  chk('fcq-phone','fcq-err-phone',phone.replace(/\D/g,'').length>=10);
  chk('fcq-install-date','fcq-err-date',date.length>0);
  if(!valid) return;
  fcqState.customer={fname:fname,lname:lname,email:email,phone:phone,date:date};
  var p=fcqState.plan,ins=fcqState.insurance;
  var base=parseFloat(p.price);
  var total=ins?(base+9.99).toFixed(2):p.price;
  document.getElementById('fcq-pay-plan').textContent=p.name+' - '+p.speed;
  document.getElementById('fcq-pay-rate').textContent='$'+p.price+'/mo';
  document.getElementById('fcq-pay-due').textContent='$'+total;
  var insPayRow=document.getElementById('fcq-pay-ins-row');
  if(insPayRow) insPayRow.style.display=ins?'flex':'none';
  // Auto-fill "Name on Card" from the contact name (only if not already typed) so
  // the demo flows straight to Complete Order without stopping on an empty field.
  var cardName=document.getElementById('fcq-card-name');
  if(cardName && !cardName.value.trim()) cardName.value=(fname+' '+lname).trim();
  fcqGoTo(4);
};

document.addEventListener('DOMContentLoaded',function(){
  var cn=document.getElementById('fcq-card-num');
  if(cn) cn.value='5555 5555 5555 5555';
  var cv=document.getElementById('fcq-card-cvv');
  if(cv) cv.value='555';
  var ce=document.getElementById('fcq-card-exp');
  if(ce) ce.value='12 / 32';

  var numEl=document.getElementById('fcq-card-num');
  if(numEl) numEl.addEventListener('input',function(){var v=this.value.replace(/\D/g,'').substring(0,16);this.value=v.match(/.{1,4}/g)?v.match(/.{1,4}/g).join(' '):v;});
  var expEl=document.getElementById('fcq-card-exp');
  if(expEl) expEl.addEventListener('input',function(){var v=this.value.replace(/\D/g,'').substring(0,4);if(v.length>=3)v=v.substring(0,2)+' / '+v.substring(2);this.value=v;});
  var streetEl=document.getElementById('fcq-street');
  if(streetEl) streetEl.addEventListener('keydown',function(e){
    if(e.key==='Enter'){
      e.preventDefault();
      var checkBtn=document.getElementById('fcq-check-btn');
      if(checkBtn && typeof checkBtn.onclick==='function'){checkBtn.onclick();}
      else {fcqCheckAddress();}
    }
  });
  var dateEl=document.getElementById('fcq-install-date');
  if(dateEl){var today=new Date();dateEl.min=today.getFullYear()+'-'+String(today.getMonth()+1).padStart(2,'0')+'-'+String(today.getDate()).padStart(2,'0');var def=new Date(today);def.setDate(def.getDate()+3);dateEl.value=def.getFullYear()+'-'+String(def.getMonth()+1).padStart(2,'0')+'-'+String(def.getDate()).padStart(2,'0');}
  // Default to show residential plans
  fcqSetBizType(false);
  fcqApplyChatQuoteParams();
});

window.fcqSubmitOrder=function(){
  var cname=document.getElementById('fcq-card-name').value.trim();
  var cnum=document.getElementById('fcq-card-num').value.replace(/\s/g,'');
  var cexp=document.getElementById('fcq-card-exp').value.trim();
  var ccvv=document.getElementById('fcq-card-cvv').value.trim();
  var valid=true;
  function chk(id,errId,test){var el=document.getElementById(id),err=document.getElementById(errId);if(!test){el.classList.add('error');err.classList.add('show');valid=false;}else{el.classList.remove('error');err.classList.remove('show');}}
  chk('fcq-card-name','fcq-err-cname',cname.length>1);
  chk('fcq-card-num','fcq-err-cnum',cnum.length===16);
  chk('fcq-card-exp','fcq-err-cexp',/^\d{2}\s*\/\s*\d{2}$/.test(cexp));
  chk('fcq-card-cvv','fcq-err-ccvv',ccvv.length>=3);
  if(!valid) return;
  var btn=document.getElementById('fcq-pay-btn');
  var lbl=document.getElementById('fcq-pay-label');
  var spn=document.getElementById('fcq-pay-spinner');
  btn.disabled=true;lbl.textContent='Processing...';spn.style.display='block';
  setTimeout(function(){
    btn.disabled=false;lbl.textContent='Complete Order';spn.style.display='none';
    var c=fcqState.customer,p=fcqState.plan,a=fcqState.address,ins=fcqState.insurance;
    var tot=ins?(parseFloat(p.price)+9.99).toFixed(2):p.price;
    var orderNum='FC-'+Math.random().toString(36).substr(2,6).toUpperCase();
    document.getElementById('fcq-conf-email').textContent=c.email;
    document.getElementById('fcq-conf-name').textContent=c.fname+' '+c.lname;
    document.getElementById('fcq-conf-addr').textContent=a.street;
    document.getElementById('fcq-conf-type').textContent=(fcqState.serviceType==='business')?'Business':'Residential';
    document.getElementById('fcq-conf-date').textContent=fcqFormatDate(c.date);
    document.getElementById('fcq-conf-order').textContent=orderNum;
    var items=['<strong>'+p.name+'</strong> - '+p.speed+' symmetrical fiber','No data caps, no annual contract','Free professional installation (promo included)'];
    if(ins){items.push('<strong>FiberCo Priority Care Protection</strong> - +$9.99/mo');items.push('Priority dispatch, free equipment replacement, no service fees');}
    items.push('Monthly Total: <strong>$'+tot+'</strong>');
    var ul=document.getElementById('fcq-conf-items');ul.innerHTML='';
    items.forEach(function(item){var li=document.createElement('li');li.innerHTML=item;ul.appendChild(li);});
    fcqGoTo(5);
  },2200);
};

function fcqFormatDate(ds){if(!ds) return '-';var d=new Date(ds+'T00:00:00');return d.toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'});}

function fcqShowToast(msg){
  var existing=document.getElementById('fcq-toast');
  if(existing) existing.remove();
  var t=document.createElement('div');
  t.id='fcq-toast';
  t.innerHTML='<span style="font-size:18px">&#9888;</span> '+msg;
  t.style.cssText='position:fixed;bottom:32px;left:50%;transform:translateX(-50%);background:#1e3a8a;color:#fff;padding:16px 32px;border-radius:50px;font-size:15px;font-weight:600;font-family:Plus Jakarta Sans,sans-serif;box-shadow:0 8px 32px rgba(0,87,255,0.35);display:flex;align-items:center;gap:10px;z-index:9999;animation:fcqToastIn .3s ease;white-space:nowrap;';
  document.body.appendChild(t);
  setTimeout(function(){
    t.style.animation='fcqToastOut .3s ease forwards';
    setTimeout(function(){t.remove();},300);
  },3000);
}

window.fcqReset=function(){
  fcqState={step:1,serviceType:'residential',plan:null,insurance:false,address:{},customer:{}};
  document.querySelectorAll('.fcq-plan-card').forEach(function(c){c.classList.remove('selected','hidden')});
  document.querySelectorAll('.fcq-field input,.fcq-field select').forEach(function(el){el.value='';el.classList.remove('error')});
  document.querySelectorAll('.fcq-error-msg').forEach(function(el){el.classList.remove('show')});
  document.getElementById('fcq-avail-result').className='';
  document.getElementById('fcq-street').value='';
  document.getElementById('fcq-is-business').checked=false;
  var lbl=document.getElementById('fcq-check-label');if(lbl) lbl.textContent='Check Availability';
  var btn=document.getElementById('fcq-check-btn');if(btn) btn.onclick=function(){fcqCheckAddress();};
  var dateEl=document.getElementById('fcq-install-date');
  if(dateEl){var def=new Date();def.setDate(def.getDate()+3);dateEl.value=def.getFullYear()+'-'+String(def.getMonth()+1).padStart(2,'0')+'-'+String(def.getDate()).padStart(2,'0');}
  fcqSetBizType(false);
  fcqGoTo(1);
};
})();
</script>
<?php }
/* ── FiberCo funnel: render on a bare full-bleed canvas (no theme header/footer) ── */
add_filter( 'template_include', 'fiberco_quote_canvas_template', 99 );
function fiberco_quote_canvas_template( $template ) {
	if ( is_admin() || is_feed() || is_embed() || is_404() ) { return $template; }
	$post = get_post();
	if ( $post && is_singular() && has_shortcode( (string) $post->post_content, 'fiberco_quote' ) ) {
		$canvas = plugin_dir_path( __FILE__ ) . 'canvas.php';
		if ( file_exists( $canvas ) ) { return $canvas; }
	}
	return $template;
}
