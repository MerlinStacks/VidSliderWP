<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @since      1.0.0
 * @package    Reel_It
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/** Remove plugin-owned data from the current site. */
function reel_it_uninstall_site_data() {
    global $wpdb;

    // Delete plugin options.
    delete_option( 'reel_it_options' );
    delete_option( 'reel_it_db_version' );

    // Clear scheduled analytics pruning if uninstalled without deactivation.
    wp_clear_scheduled_hook( 'reel_it_daily_prune' );

    delete_transient( 'reel_it_stats_version' );
    delete_transient( 'reel_it_stats_invalidated_at' );
    $transient_prefix = $wpdb->esc_like( '_transient_reel_it_' ) . '%';
    $timeout_prefix   = $wpdb->esc_like( '_transient_timeout_reel_it_' ) . '%';
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $transient_prefix,
            $timeout_prefix
        )
    ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

    // Drop child tables before parent tables for legacy foreign keys.
    $tables_to_drop = array( 'reel_it_analytics', 'reel_it_feed_videos', 'reel_it_feeds' );
    foreach ( $tables_to_drop as $suffix ) {
        $table  = $wpdb->prefix . $suffix;
        $result = $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        if ( false === $result ) {
            error_log( sprintf( 'Reel It: failed to drop table %s during uninstall.', $table ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }

    // Scope metadata cleanup to attachments.
    $meta_result = $wpdb->query(
        "DELETE pm FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         WHERE pm.meta_key IN ('_reel_it_linked_products', '_reel_it_poster_id')
         AND p.post_type = 'attachment'"
    ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    if ( false === $meta_result ) {
        error_log( 'Reel It: failed to delete plugin post meta during uninstall.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    }

    wp_cache_delete( 'reel_it_all_feeds', 'reel_it' );
    wp_cache_delete( 'reel_it_feeds_thumbs', 'reel_it' );
    wp_cache_delete( 'reel_it_feeds_last_changed', 'reel_it' );
}

if ( is_multisite() ) {
    $offset = 0;
    do {
        $site_ids = get_sites( array( 'fields' => 'ids', 'number' => 100, 'offset' => $offset, 'orderby' => 'id', 'order' => 'ASC' ) );
        foreach ( $site_ids as $site_id ) {
            switch_to_blog( $site_id );
            try {
                reel_it_uninstall_site_data();
            } finally {
                restore_current_blog();
            }
        }
        $offset += 100;
    } while ( count( $site_ids ) === 100 );
} else {
    reel_it_uninstall_site_data();
}

// Note: We don't delete uploaded videos as they are part of the media library
// and users might want to keep them even after uninstalling the plugin
