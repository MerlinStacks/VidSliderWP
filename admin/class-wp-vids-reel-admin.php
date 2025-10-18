<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Wp_Vids_Reel
 * @subpackage Wp_Vids_Reel/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @package    Wp_Vids_Reel
 * @subpackage Wp_Vids_Reel/admin
 */
class Wp_Vids_Reel_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'css/wp-vids-reel-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'js/wp-vids-reel-admin.js',
            array( 'jquery' ),
            $this->version,
            false
        );

        // Pass AJAX URL to JavaScript
        wp_localize_script(
            $this->plugin_name,
            'wp_vids_reel_admin',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'wp_vids_reel_nonce' ),
            )
        );
    }

    /**
     * Handle video upload via AJAX
     *
     * @since    1.0.0
     */
    public function handle_video_upload() {
        check_ajax_referer( 'wp_vids_reel_nonce', 'nonce' );

        if ( ! current_user_can( 'upload_files' ) ) {
            wp_die( __( 'You do not have permission to upload files.', 'wp-vids-reel' ) );
        }

        if ( ! isset( $_FILES['video_file'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'wp-vids-reel' ) ) );
        }

        $file = $_FILES['video_file'];
        
        // Check if it's a video file
        $allowed_types = array( 'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo' );
        if ( ! in_array( $file['type'], $allowed_types ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid file type. Please upload a video file.', 'wp-vids-reel' ) ) );
        }

        // Handle the upload
        $uploaded_file = wp_handle_upload( $file, array( 'test_form' => false ) );

        if ( isset( $uploaded_file['error'] ) ) {
            wp_send_json_error( array( 'message' => $uploaded_file['error'] ) );
        }

        // Insert into media library
        $attachment = array(
            'post_mime_type' => $uploaded_file['type'],
            'post_title' => sanitize_file_name( $uploaded_file['file'] ),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment( $attachment, $uploaded_file['file'] );
        
        if ( $attach_id ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            $attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded_file['file'] );
            wp_update_attachment_metadata( $attach_id, $attach_data );

            wp_send_json_success( array(
                'attachment_id' => $attach_id,
                'url' => $uploaded_file['url'],
                'title' => get_the_title( $attach_id )
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to save video to media library.', 'wp-vids-reel' ) ) );
        }
    }
}