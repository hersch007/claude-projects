<?php
$city_name = get_option( 'sp_city_name', get_bloginfo( 'name' ) );
$accent    = get_option( 'sp_city_accent_color', '#1B3A6B' );

function cc_darken( $hex, $pct = 15 ) {
    $hex = ltrim( $hex, '#' );
    if ( strlen( $hex ) !== 6 ) return $hex;
    list( $r, $g, $b ) = array_map( 'hexdec', str_split( $hex, 2 ) );
    $r = max( 0, $r - round( $r * $pct / 100 ) );
    $g = max( 0, $g - round( $g * $pct / 100 ) );
    $b = max( 0, $b - round( $b * $pct / 100 ) );
    return sprintf( '#%02x%02x%02x', $r, $g, $b );
}
$accent_dark = cc_darken( $accent );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<?php wp_head(); ?>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: #f0f4f8;
    color: #1a202c;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* ── Header ── */
.cc-header {
    background: <?php echo esc_attr( $accent ); ?>;
    padding: 0 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,.18);
}
.cc-header-inner {
    max-width: 860px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 64px;
}
.cc-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}
.cc-logo-icon {
    width: 36px;
    height: 36px;
    background: rgba(255,255,255,.15);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.cc-logo-icon svg { width: 20px; height: 20px; stroke: #fff; fill: none; }
.cc-logo-name  { font-size: 17px; font-weight: 700; color: #fff; line-height: 1.2; }
.cc-logo-sub   { font-size: 11px; color: rgba(255,255,255,.6); display: block; font-weight: 400; }
.cc-header-nav { display: flex; align-items: center; gap: 6px; }
.cc-header-nav a {
    color: rgba(255,255,255,.8);
    text-decoration: none;
    font-size: 13px;
    padding: 6px 12px;
    border-radius: 6px;
    transition: background .15s, color .15s;
}
.cc-header-nav a:hover { background: rgba(255,255,255,.12); color: #fff; }
.cc-header-nav a.cc-btn {
    background: rgba(255,255,255,.15);
    color: #fff;
    border: 1px solid rgba(255,255,255,.25);
    font-weight: 600;
}
.cc-header-nav a.cc-btn:hover { background: rgba(255,255,255,.25); }

/* ── Page hero ── */
.cc-hero {
    background: <?php echo esc_attr( $accent ); ?>;
    padding: 28px 24px 36px;
    text-align: center;
    border-bottom: 4px solid <?php echo esc_attr( $accent_dark ); ?>;
}
.cc-hero h1 { color: #fff; font-size: 26px; font-weight: 700; }
.cc-hero p  { color: rgba(255,255,255,.75); font-size: 15px; margin-top: 6px; }

/* ── Main ── */
.cc-main {
    flex: 1;
    max-width: 860px;
    width: 100%;
    margin: -16px auto 0;
    padding: 0 16px 48px;
}

/* ── Content card ── */
.cc-content-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.08);
    padding: 40px 48px;
    line-height: 1.7;
}
.cc-content-card h1,
.cc-content-card h2,
.cc-content-card h3 {
    color: <?php echo esc_attr( $accent ); ?>;
    margin: 1.4em 0 .5em;
    line-height: 1.3;
}
.cc-content-card h1 { font-size: 26px; margin-top: 0; }
.cc-content-card h2 { font-size: 20px; }
.cc-content-card h3 { font-size: 16px; }
.cc-content-card p  { margin-bottom: 1em; color: #374151; }
.cc-content-card ul,
.cc-content-card ol { padding-left: 1.5em; margin-bottom: 1em; color: #374151; }
.cc-content-card li { margin-bottom: .4em; }
.cc-content-card a  { color: <?php echo esc_attr( $accent ); ?>; }
.cc-content-card strong { color: #111; }
.cc-content-card hr { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
.cc-content-card table { width: 100%; border-collapse: collapse; margin-bottom: 1em; }
.cc-content-card th { background: <?php echo esc_attr( $accent ); ?>; color: #fff; padding: 10px 14px; text-align: left; font-size: 13px; }
.cc-content-card td { padding: 10px 14px; border-bottom: 1px solid #f0f0f0; font-size: 14px; color: #374151; }

/* ── Footer ── */
.cc-footer {
    background: #1a202c;
    color: rgba(255,255,255,.45);
    text-align: center;
    padding: 18px 24px;
    font-size: 12px;
}
.cc-footer a { color: rgba(255,255,255,.45); text-decoration: none; }
.cc-footer a:hover { color: rgba(255,255,255,.75); }

@media (max-width: 600px) {
    .cc-content-card { padding: 24px 20px; }
    .cc-hero h1 { font-size: 21px; }
}
</style>
</head>
<body <?php body_class(); ?>>

<header class="cc-header">
    <div class="cc-header-inner">
        <a class="cc-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
            <div class="cc-logo-icon">
                <svg viewBox="0 0 24 24" stroke-width="2"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 00-1-1h-2a1 1 0 00-1 1v5m4 0H9"/></svg>
            </div>
            <div>
                <span class="cc-logo-name"><?php echo esc_html( $city_name ); ?></span>
                <span class="cc-logo-sub">Public Services</span>
            </div>
        </a>
        <nav class="cc-header-nav">
            <a href="<?php echo esc_url( home_url( '/service-request/' ) ); ?>">Submit a Request</a>
            <a href="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" class="cc-btn">Staff Login</a>
        </nav>
    </div>
</header>
