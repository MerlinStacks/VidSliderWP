<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @since      1.0.0
 * @package    Wp_Vids_Reel
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options
delete_option( 'wp_vids_reel_options' );

// Clean up any transients
delete_transient( 'wp_vids_reel_video_cache' );

// Note: We don't delete uploaded videos as they are part of the media library
// and users might want to keep them even after uninstalling the plugin