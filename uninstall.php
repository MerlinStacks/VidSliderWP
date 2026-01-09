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

// Drop tables (order matters due to foreign key constraints)
$wpdb->query( "DROP TABLE IF EXISTS {$analytics_table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$feed_videos_table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$feeds_table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange

// Delete all video-linked product meta
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_reel_it_linked_products'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

// Note: We don't delete uploaded videos as they are part of the media library
// and users might want to keep them even after uninstalling the plugin