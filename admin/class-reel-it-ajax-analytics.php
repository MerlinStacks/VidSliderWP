<?php
/**
 * AJAX handler for analytics data retrieval.
 *
 * Extracted from Reel_It_Settings to reduce file size.
 * The WordPress AJAX action name remains unchanged.
 *
 * @since      1.5.0
 * @package    Reel_It
 * @subpackage Reel_It/admin
 */

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Analytics AJAX handler.
 *
 * @since      1.5.0
 * @package    Reel_It
 * @subpackage Reel_It/admin
 */
class Reel_It_Ajax_Analytics {

    /**
     * Fetch analytics data for the dashboard.
     *
     * Caps the look-back window at 365 days to prevent
     * expensive unbounded queries.
     *
     * @return void Sends JSON response.
     */
    public function ajax_get_analytics() {
        Reel_It_Ajax_Helper::verify();

        // Cap days to 365 to prevent expensive unbounded queries.
        $days = isset( $_POST['days'] ) ? min( absint( $_POST['days'] ), 365 ) : 30;
        $analytics = new Reel_It_Analytics();

        wp_send_json_success( array(
            'stats'      => $analytics->get_summary_stats( $days ),
            'top_videos' => $analytics->get_top_videos( $days ),
            'daily'      => $analytics->get_daily_stats( $days ),
        ) );
    }
}
