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

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
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
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Record an analytics event.
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

        return $wpdb->insert(
            $this->table_name,
            array(
                'video_id'   => absint( $data['video_id'] ),
                'feed_id'    => $data['feed_id'] ? absint( $data['feed_id'] ) : null,
                'event_type' => sanitize_text_field( $data['event_type'] ),
                'watch_time' => absint( $data['watch_time'] ),
                'product_id' => $data['product_id'] ? absint( $data['product_id'] ) : null,
                'session_id' => sanitize_text_field( $data['session_id'] ),
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%d', '%d', '%s', '%s' )
        );
    }

    /**
     * Get summary statistics for the dashboard.
     *
     * @since 1.4.0
     * @param int $days Number of days to look back.
     * @return array
     */
    public function get_summary_stats( $days = 30 ) {
        global $wpdb;

        $date_limit = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        // Total plays
        $total_plays = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE event_type = 'play' AND created_at >= %s",
            $date_limit
        ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        // Total completions
        $total_completions = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE event_type = 'complete' AND created_at >= %s",
            $date_limit
        ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        // Total product clicks
        $total_clicks = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE event_type = 'product_click' AND created_at >= %s",
            $date_limit
        ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        // Average watch time
        $avg_watch_time = $wpdb->get_var( $wpdb->prepare(
            "SELECT AVG(watch_time) FROM {$this->table_name} WHERE event_type = 'complete' AND created_at >= %s",
            $date_limit
        ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        // Unique sessions (visitors)
        $unique_visitors = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM {$this->table_name} WHERE created_at >= %s",
            $date_limit
        ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        return array(
            'total_plays'       => intval( $total_plays ),
            'total_completions' => intval( $total_completions ),
            'total_clicks'      => intval( $total_clicks ),
            'avg_watch_time'    => round( floatval( $avg_watch_time ), 1 ),
            'unique_visitors'   => intval( $unique_visitors ),
            'completion_rate'   => $total_plays > 0 ? round( ( $total_completions / $total_plays ) * 100, 1 ) : 0,
        );
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

        // Enrich with video titles
        foreach ( $results as &$row ) {
            $post = get_post( $row['video_id'] );
            $row['title'] = $post ? $post->post_title : __( 'Deleted Video', 'reel-it' );
            $row['completion_rate'] = $row['plays'] > 0 
                ? round( ( $row['completions'] / $row['plays'] ) * 100, 1 ) 
                : 0;
        }

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
        global $wpdb;

        $date_limit = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                SUM(CASE WHEN event_type = 'play' THEN 1 ELSE 0 END) as plays,
                SUM(CASE WHEN event_type = 'complete' THEN 1 ELSE 0 END) as completions,
                SUM(CASE WHEN event_type = 'product_click' THEN 1 ELSE 0 END) as clicks
            FROM {$this->table_name}
            WHERE DATE(created_at) >= %s
            GROUP BY DATE(created_at)
            ORDER BY date ASC",
            $date_limit
        ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

        return $results;
    }
}
