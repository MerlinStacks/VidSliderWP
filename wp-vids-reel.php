<?php
/**
 * Plugin Name: WP Vids Reel
 * Plugin URI: https://example.com/wp-vids-reel
 * Description: A WordPress plugin for showcasing videos in a slider using the block editor with video upload functionality.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-vids-reel
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Currently plugin version.
 */
define( 'WP_VIDS_REEL_VERSION', '1.0.0' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wp-vids-reel.php';

/**
 * Begins execution of the plugin.
 */
function run_wp_vids_reel() {
    $plugin = new Wp_Vids_Reel();
    $plugin->run();
}

run_wp_vids_reel();