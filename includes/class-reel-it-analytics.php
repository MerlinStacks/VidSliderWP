<?php
/**
 * Video Analytics Handler
 *
 * Handles tracking and reporting of video engagement metrics.
 *
 * @since      1.4.0
 * @package    Reel_It
 * @subpackage Reel_It/includes
 */

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Analytics class for video engagement tracking.
 *
 * @since      1.4.0
 * @package    Reel_It
 * @subpackage Reel_It/includes
 */
class Reel_It_Analytics {

    /**
     * Table name for analytics.
     *
     * @var string
     */
    private $table_name;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'reel_it_analytics';
    }

    /**
     * Create the analytics table.
     *
     * @since 1.4.0
     */
    public function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Why: dbDelta requires bare CREATE TABLE (no IF NOT EXISTS) to parse
        // the table name for schema diffing on upgrades.
        $sql = "CREATE TABLE {$this->table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            video_id BIGINT UNSIGNED NOT NULL,
            feed_id BIGINT UNSIGNED DEFAULT NULL,
            event_type VARCHAR(50) NOT NULL,
            watch_time INT UNSIGNED DEFAULT 0,
            product_id BIGINT UNSIGNED DEFAULT NULL,
            session_id VARCHAR(64) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_video_id (video_id),
            KEY idx_event_type (event_type),
            KEY idx_created_at (created_at),
            KEY idx_video_event_created (video_id, event_type, created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Record an analytics event.
     *
     * Why: nullable fields need conditional format strings — wpdb coerces
     * NULL + '%d' to 0, making IS NULL queries return wrong results.
     *
     * @since 1.4.0
     * @param array $data Event data.
     * @return bool|int
     */
    public function record_event( $data ) {
        global $wpdb;

        $defaults = array(
            'video_id'   => 0,
            'feed_id'    => null,
            'event_type' => 'view',
            'watch_time' => 0,
            'product_id' => null,
            'session_id' => '',
        );

        $data = wp_parse_args( $data, $defaults );

        $feed_id    = $data['feed_id'] ? absint( $data['feed_id'] ) : null;
        $product_id = $data['product_id'] ? absint( $data['product_id'] ) : null;

        // Invalidate cached dashboards so new events appear promptly.
        $this->clear_stats_cache();

        /* Why: wpdb->insert internally calls array_values() on $formats,
           so null entries shift subsequent format positions. Build dynamically. */
        $columns = array(
            'video_id'   => absint( $data['video_id'] ),
            'event_type' => sanitize_text_field( $data['event_type'] ),
            'watch_time' => absint( $data['watch_time'] ),
            'session_id' => sanitize_text_field( $data['session_id'] ),
            'created_at' => current_time( 'mysql' ),
        );
        $formats = array( '%d', '%s', '%d', '%s', '%s' );

        if ( null !== $feed_id ) {
            $columns['feed_id'] = $feed_id;
            $formats[] = '%d';
        }
        if ( null !== $product_id ) {
            $columns['product_id'] = $product_id;
            $formats[] = '%d';
        }

        return $wpdb->insert( $this->table_name, $columns, $formats ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    /**
     * Delete analytics events older than the retention period.
     *
     * Why: without pruning, the analytics table grows unbounded on
     * high-traffic sites and progressively slows down summary queries.
     *
     * @since 1.5.1
     * @param int $retention_days Number of days to retain. Default 90.
     * @return int|false Number of rows deleted or false on error.
     */
    public function prune_old_events( $retention_days = 90 ) {
        global $wpdb;

        $cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

        $deleted = $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE created_at < %s",
            $cutoff
        ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        if ( false !== $deleted ) {
            $this->clear_stats_cache();
        }

        return $deleted;
    }

    /**
     * Clear all cached analytics dashboard data.
     *
     * Why: instead of deleting individual transient keys (which fails on
     * object-cache backends), we bump a shared version number so every
     * existing key is considered stale on the next lookup.
     *
     * @since 1.7.1
     */
    private function clear_stats_cache() {
        set_transient( 'reel_it_stats_version', time(), DAY_IN_SECONDS );
    }

    /**
     * Shared cache-buster version for analytics dashboard data.
     *
     * Why: deleting transients by wildcard is unreliable on object-cache
     * backends (Redis/Memcached). A version number lets us atomically
     * invalidate every stats key at once.
     *
     * @since 1.7.1
     * @return int
     */
    private function get_cache_version() {
        $version = get_transient( 'reel_it_stats_version' );
        if ( false === $version ) {
            $version = time();
            set_transient( 'reel_it_stats_version', $version, DAY_IN_SECONDS );
        }
        return (int) $version;
    }

    /**
     * Get summary statistics for the dashboard.
     *
     * Why: consolidated from 5 separate queries into a single SELECT
     * with conditional aggregation to reduce DB round-trips.
     *
     * @since 1.4.0
     * @param int $days Number of days to look back.
     * @return array
     */
    public function get_summary_stats( $days = 30 ) {
        $cache_key = 'reel_it_stats_' . $days . '_' . $this->get_cache_version();
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;

        $date_limit = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT
                SUM(CASE WHEN event_type = 'play' THEN 1 ELSE 0 END) AS total_plays,
                SUM(CASE WHEN event_type = 'complete' THEN 1 ELSE 0 END) AS total_completions,
                SUM(CASE WHEN event_type = 'product_click' THEN 1 ELSE 0 END) AS total_clicks,
                AVG(CASE WHEN event_type = 'complete' THEN watch_time ELSE NULL END) AS avg_watch_time,
                COUNT(DISTINCT session_id) AS unique_visitors
            FROM {$this->table_name}
            WHERE created_at >= %s",
            $date_limit
        ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        $total_plays       = intval( $row['total_plays'] );
        $total_completions = intval( $row['total_completions'] );

        $result = array(
            'total_plays'       => $total_plays,
            'total_completions' => $total_completions,
            'total_clicks'      => intval( $row['total_clicks'] ),
            'avg_watch_time'    => round( floatval( $row['avg_watch_time'] ), 1 ),
            'unique_visitors'   => intval( $row['unique_visitors'] ),
            'completion_rate'   => $total_plays > 0 ? round( ( $total_completions / $total_plays ) * 100, 1 ) : 0,
        );

        set_transient( $cache_key, $result, MINUTE_IN_SECONDS * 5 );
        return $result;
    }

    /**
     * Get top performing videos.
     *
     * @since 1.4.0
     * @param int $days   Number of days to look back.
     * @param int $limit  Number of videos to return.
     * @return array
     */
    public function get_top_videos( $days = 30, $limit = 10 ) {
        $cache_key = 'reel_it_top_' . $days . '_' . $limit . '_' . $this->get_cache_version();
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;

        $date_limit = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                video_id,
                SUM(CASE WHEN event_type = 'play' THEN 1 ELSE 0 END) as plays,
                SUM(CASE WHEN event_type = 'complete' THEN 1 ELSE 0 END) as completions,
                SUM(CASE WHEN event_type = 'product_click' THEN 1 ELSE 0 END) as clicks,
                AVG(CASE WHEN event_type = 'complete' THEN watch_time ELSE NULL END) as avg_watch_time
            FROM {$this->table_name}
            WHERE created_at >= %s
            GROUP BY video_id
            ORDER BY plays DESC
            LIMIT %d",
            $date_limit,
            $limit
        ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        $video_ids = wp_list_pluck( $results, 'video_id' );
        if ( ! empty( $video_ids ) ) {
            _prime_post_caches( array_map( 'intval', $video_ids ), false, false );
        }

        foreach ( $results as &$row ) {
            $post = get_post( $row['video_id'] );
            $row['title'] = $post ? $post->post_title : __( 'Deleted Video', 'reel-it' );
            $row['completion_rate'] = $row['plays'] > 0
                ? round( ( $row['completions'] / $row['plays'] ) * 100, 1 )
                : 0;
        }

        set_transient( $cache_key, $results, MINUTE_IN_SECONDS * 5 );
        return $results;
    }

    /**
     * Get daily stats for charts.
     *
     * @since 1.4.0
     * @param int $days Number of days.
     * @return array
     */
    public function get_daily_stats( $days = 30 ) {
        $cache_key = 'reel_it_daily_' . $days . '_' . $this->get_cache_version();
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;

        $date_limit = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                SUM(CASE WHEN event_type = 'play' THEN 1 ELSE 0 END) as plays,
                SUM(CASE WHEN event_type = 'complete' THEN 1 ELSE 0 END) as completions,
                SUM(CASE WHEN event_type = 'product_click' THEN 1 ELSE 0 END) as clicks
            FROM {$this->table_name}
            WHERE created_at >= %s
            GROUP BY DATE(created_at)
            ORDER BY date ASC",
            $date_limit
        ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        set_transient( $cache_key, $results, MINUTE_IN_SECONDS * 5 );
        return $results;
    }

    /**
     * Get analytics metrics keyed by video ID.
     *
     * @since 1.8.0
     * @param array $video_ids List of attachment IDs.
     * @param int   $days      Lookback window.
     * @return array
     */
    public function get_video_stats_map( $video_ids, $days = 30 ) {
        $video_ids = array_values( array_filter( array_map( 'absint', (array) $video_ids ) ) );
        if ( empty( $video_ids ) ) {
            return array();
        }

        $cache_key = 'reel_it_video_stats_' . md5( wp_json_encode( $video_ids ) ) . '_' . absint( $days ) . '_' . $this->get_cache_version();
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;

        $date_limit   = gmdate( 'Y-m-d H:i:s', strtotime( '-' . absint( $days ) . ' days' ) );
        $placeholders = implode( ',', array_fill( 0, count( $video_ids ), '%d' ) );
        $query        = "SELECT
                video_id,
                SUM(CASE WHEN event_type = 'play' THEN 1 ELSE 0 END) AS plays,
                SUM(CASE WHEN event_type = 'complete' THEN 1 ELSE 0 END) AS completions,
                SUM(CASE WHEN event_type = 'product_click' THEN 1 ELSE 0 END) AS clicks
            FROM {$this->table_name}
            WHERE created_at >= %s
              AND video_id IN ({$placeholders})
            GROUP BY video_id";

        $rows = $wpdb->get_results( $wpdb->prepare( $query, array_merge( array( $date_limit ), $video_ids ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        $result = array();
        foreach ( $video_ids as $video_id ) {
            $result[ $video_id ] = array(
                'plays'           => 0,
                'completions'     => 0,
                'clicks'          => 0,
                'completion_rate' => 0,
            );
        }

        foreach ( $rows as $row ) {
            $video_id       = absint( $row['video_id'] );
            $plays          = intval( $row['plays'] );
            $completions    = intval( $row['completions'] );
            $result[ $video_id ] = array(
                'plays'           => $plays,
                'completions'     => $completions,
                'clicks'          => intval( $row['clicks'] ),
                'completion_rate' => $plays > 0 ? round( ( $completions / $plays ) * 100, 1 ) : 0,
            );
        }

        set_transient( $cache_key, $result, MINUTE_IN_SECONDS * 5 );
        return $result;
    }
}
