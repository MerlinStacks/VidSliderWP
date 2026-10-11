<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Reel_It
 * @subpackage Reel_It/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @package    Reel_It
 * @subpackage Reel_It/admin
 */
class Reel_It_Admin {

    /**
     * Register the upload handler.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // The action name must match block-editor.js.
        add_action( 'wp_ajax_reel_it_upload_video', array( $this, 'handle_video_upload' ) );
    }

    /**
     * Handle video upload via AJAX
     *
     * @since    1.0.0
     */
    public function handle_video_upload() {
        Reel_It_Ajax_Helper::verify( 'upload_files' );

        if ( ! isset( $_FILES['video_file'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'reel-it' ) ) );
        }

        $file = $_FILES['video_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        $result = Reel_It_Upload_Handler::handle_upload( $file );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        // Keep attachment_id for older callers, but return the canonical video
        // shape expected by the block editor and current admin JS.
        wp_send_json_success( array(
            'id'            => $result['id'],
            'attachment_id' => $result['id'],
            'url'           => $result['url'],
            'title'         => $result['title'],
            'mime'          => $result['mime'],
            'thumbnail'     => $result['thumbnail'],
        ) );
    }
}
