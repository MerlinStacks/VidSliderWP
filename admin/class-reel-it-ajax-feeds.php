<?php
/**
 * AJAX handlers for feed management.
 *
 * Extracted from Reel_It_Settings to reduce file size.
 * All WordPress AJAX action names remain unchanged.
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
 * Feed-related AJAX handlers.
 *
 * @since      1.5.0
 * @package    Reel_It
 * @subpackage Reel_It/admin
 */
class Reel_It_Ajax_Feeds {

    /**
     * Lazily instantiated database helper.
     *
     * @var Reel_It_Database|null
     */
    private $database = null;

    /**
     * Return (and cache) the shared database instance.
     *
     * @return Reel_It_Database
     */
    private function get_database() {
        if ( null === $this->database ) {
            $this->database = Reel_It_Database::instance();
        }
        return $this->database;
    }

    /**
     * Create a new video feed.
     *
     * @return void Sends JSON response.
     */
    public function ajax_create_feed() {
        Reel_It_Ajax_Helper::verify();
        $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
        if ( empty( $name ) ) {
            wp_send_json_error( array( 'message' => __( 'Name required', 'reel-it' ) ) );
        }
        $feed_id = $this->get_database()->create_feed( $name, $description );
        if ( $feed_id ) {
            wp_send_json_success( array( 'message' => __( 'Feed created', 'reel-it' ), 'feed_id' => $feed_id ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed', 'reel-it' ) ) );
        }
    }

    /**
     * Update an existing video feed.
     *
     * @return void Sends JSON response.
     */
    public function ajax_update_feed() {
        Reel_It_Ajax_Helper::verify();
        $feed_id = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;
        $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
        if ( empty( $name ) || $feed_id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid data', 'reel-it' ) ) );
        }
        if ( $this->get_database()->update_feed( $feed_id, $name, $description ) ) {
            wp_send_json_success( array( 'message' => __( 'Updated', 'reel-it' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed', 'reel-it' ) ) );
        }
    }

    /**
     * Delete a video feed and its associated videos.
     *
     * @return void Sends JSON response.
     */
    public function ajax_delete_feed() {
        Reel_It_Ajax_Helper::verify();
        $feed_id = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;
        if ( $feed_id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid ID', 'reel-it' ) ) );
        }
        if ( $this->get_database()->delete_feed( $feed_id ) ) {
            wp_send_json_success( array( 'message' => __( 'Deleted', 'reel-it' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed', 'reel-it' ) ) );
        }
    }

    /**
     * Get all videos in a feed with thumbnail URLs.
     *
     * @return void Sends JSON response.
     */
    public function ajax_get_feed_videos() {
        Reel_It_Ajax_Helper::verify();
        $feed_id = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;
        $videos = $this->get_database()->get_feed_videos( $feed_id );

        $videos_array = array();
        foreach ( $videos as $video ) {
            $video_data = (array) $video;
            $video_data['thumbnail'] = wp_get_attachment_image_url( $video->video_id, 'thumbnail' );
            $videos_array[] = $video_data;
        }

        wp_send_json_success( array( 'videos' => $videos_array ) );
    }

    /**
     * Add a video to a feed.
     *
     * @return void Sends JSON response.
     */
    public function ajax_add_video_to_feed() {
        Reel_It_Ajax_Helper::verify();
        $feed_id = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;
        $video_id = isset( $_POST['video_id'] ) ? intval( $_POST['video_id'] ) : 0;
        if ( $this->get_database()->add_video_to_feed( $feed_id, $video_id ) ) {
            wp_send_json_success( array( 'message' => __( 'Added', 'reel-it' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed', 'reel-it' ) ) );
        }
    }

    /**
     * Remove a video from a feed.
     *
     * @return void Sends JSON response.
     */
    public function ajax_remove_video_from_feed() {
        Reel_It_Ajax_Helper::verify();
        $feed_id = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;
        $video_id = isset( $_POST['video_id'] ) ? intval( $_POST['video_id'] ) : 0;
        if ( $this->get_database()->remove_video_from_feed( $feed_id, $video_id ) ) {
            wp_send_json_success( array( 'message' => __( 'Removed', 'reel-it' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed', 'reel-it' ) ) );
        }
    }

    /**
     * Update the sort order of videos in a feed.
     *
     * @return void Sends JSON response.
     */
    public function ajax_update_video_order() {
        Reel_It_Ajax_Helper::verify();
        $feed_id = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;
        // Sanitize array of arrays
        $orders_raw = isset( $_POST['video_orders'] ) ? wp_unslash( $_POST['video_orders'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $orders = array();
        if ( is_array( $orders_raw ) ) {
            foreach ( $orders_raw as $order_item ) {
                if ( is_array( $order_item ) ) {
                    $orders[] = array_map( 'sanitize_text_field', $order_item );
                }
            }
        }
        $database = $this->get_database();
        foreach ( $orders as $o ) {
            if ( isset( $o['video_id'] ) && isset( $o['sort_order'] ) ) {
                $database->update_video_sort_order( $feed_id, intval( $o['video_id'] ), intval( $o['sort_order'] ) );
            }
        }
        // Why: update_video_sort_order() already calls clear_feed_cache() per update.
        wp_send_json_success( array( 'message' => __( 'Updated', 'reel-it' ) ) );
    }

    /**
     * Search for available videos in the media library.
     *
     * @return void Sends JSON response.
     */
    public function ajax_search_videos() {
        Reel_It_Ajax_Helper::verify( 'upload_files' );
        $search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
        $per_page = isset( $_POST['per_page'] ) ? intval( $_POST['per_page'] ) : Reel_It::DEFAULT_PAGINATION;
        wp_send_json_success( $this->get_database()->get_available_videos( $search, $page, $per_page ) );
    }

    /**
     * Get all feeds.
     *
     * @return void Sends JSON response.
     */
    public function ajax_get_feeds() {
        Reel_It_Ajax_Helper::verify();
        wp_send_json_success( array( 'feeds' => $this->get_database()->get_feeds() ) );
    }

    /**
     * Get feed thumbnail data.
     *
     * @return void Sends JSON response.
     */
    public function ajax_get_feed_thumbnail() {
        Reel_It_Ajax_Helper::verify();
        $feed_id = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;
        $data = $this->get_database()->get_feed_thumbnail_data( $feed_id );
        if ( $data['success'] ) {
            wp_send_json_success( $data );
        } else {
            wp_send_json_error( $data );
        }
    }
}
