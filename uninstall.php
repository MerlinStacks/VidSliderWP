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

global $wpdb;

// Delete plugin options
delete_option( 'reel_it_options' );
delete_option( 'reel_it_db_version' );

// Clean up all transients
delete_transient( 'reel_it_video_cache' );
delete_transient( 'reel_it_feeds_data' );

// Drop custom database tables
$analytics_table = $wpdb->prefix . 'reel_it_analytics';
$feeds_table = $wpdb->prefix . 'reel_it_feeds';
$feed_videos_table = $wpdb->prefix . 'reel_it_feed_videos';

// Drop tables (order matters due to foreign key constraints: child → parent)
$tables_to_drop = array( $analytics_table, $feed_videos_table, $feeds_table );
foreach ( $tables_to_drop as $table ) {
    $result = $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
    if ( false === $result ) {
        error_log( sprintf( 'Reel It: failed to drop table %s during uninstall.', $table ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    }
}

// Delete all video-linked product meta (scoped to attachments only)
// Why: avoids deleting identically-named meta on non-attachment posts.
$meta_result = $wpdb->query(
    "DELETE pm FROM {$wpdb->postmeta} pm
     INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
     WHERE pm.meta_key IN ('_reel_it_linked_products', '_reel_it_poster_id')
     AND p.post_type = 'attachment'"
); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
if ( false === $meta_result ) {
    error_log( 'Reel It: failed to delete plugin post meta during uninstall.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
}

// Note: We don't delete uploaded videos as they are part of the media library
// and users might want to keep them even after uninstalling the plugin