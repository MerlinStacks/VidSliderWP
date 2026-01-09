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
        
        // Register AJAX handlers
        add_action( 'wp_ajax_reel_it_admin_upload_video', array( $this, 'handle_video_upload' ) );
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'css/reel-it-admin.css',
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
            plugin_dir_url( __FILE__ ) . 'js/reel-it-admin.js',
            array( 'jquery' ),
            $this->version,
            false
        );

        // Pass AJAX URL to JavaScript
        wp_localize_script(
            $this->plugin_name,
            'reel_it_admin',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'reel_it_nonce' ),
            )
        );
    }

    /**
     * Handle video upload via AJAX
     *
     * @since    1.0.0
     */
    public function handle_video_upload() {
        check_ajax_referer( 'reel_it_nonce', 'nonce' );

        if ( ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to upload files.', 'reel-it' ) ) );
        }

        if ( ! isset( $_FILES['video_file'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'reel-it' ) ) );
        }

        $file = $_FILES['video_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        $result = Reel_It_Upload_Handler::handle_upload( $file );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        // Map response keys to match existing admin endpoint expectations
        wp_send_json_success( array(
            'attachment_id' => $result['id'],
            'url'           => $result['url'],
            'title'         => $result['title'],
        ) );
    }
}