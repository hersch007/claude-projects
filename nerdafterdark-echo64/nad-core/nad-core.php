<?php
/**
 * Plugin Name:       Nerd After Dark — Core
 * Plugin URI:        https://www.nerdafterdark.com
 * Description:       Core site functionality for NerdAfterDark.com — Transmissions custom post type and site-wide features.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            NerdAfterDark
 * Author URI:        https://www.nerdafterdark.com
 * License:           GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'NAD_VERSION',    '1.0.0' );
define( 'NAD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NAD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once NAD_PLUGIN_DIR . 'includes/class-nad-transmissions.php';

add_action( 'plugins_loaded', function () {
    NAD_Transmissions::instance();
} );

register_activation_hook( __FILE__, function () {
    NAD_Transmissions::register_post_type();
    flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function () {
    flush_rewrite_rules();
} );
