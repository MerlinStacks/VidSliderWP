<?php
/**
 * Plugin Name: Reel It - Shoppable Video Slider
 * Plugin URI: https://customkings.com.au/reel-it
 * Description: A WordPress plugin for showcasing videos in a slider using the block editor with video upload functionality.
 * Version: 1.3.0
 * Author: SLDevs
 * Author URI: https://customkings.com.au
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: reel-it
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
define( 'REEL_IT_VERSION', '1.3.0' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-reel-it.php';

/**
 * Activation hook - creates database tables on activation/upgrade.
 */
function reel_it_activate() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-reel-it-database.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-reel-it-analytics.php';
    
    $database = new Reel_It_Database();
    $database->create_tables();
    
    $analytics = new Reel_It_Analytics();
    $analytics->create_table();
    
    // Store version for upgrade checks
    update_option( 'reel_it_db_version', REEL_IT_VERSION );
}
register_activation_hook( __FILE__, 'reel_it_activate' );

/**
 * Check for database upgrades on admin init.
 */
function reel_it_check_db_upgrade() {
    $installed_version = get_option( 'reel_it_db_version', '0' );
    if ( version_compare( $installed_version, REEL_IT_VERSION, '<' ) ) {
        reel_it_activate();
    }
}
add_action( 'admin_init', 'reel_it_check_db_upgrade' );

/**
 * Begins execution of the plugin.
 */
function reel_it_run() {
    $plugin = new Reel_It();
    $plugin->run();
}

reel_it_run();