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
        // Why: must match the action name sent by admin JS (L85) and block-editor.js (L524).
        add_action( 'wp_ajax_reel_it_upload_video', array( $this, 'handle_video_upload' ) );
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * Only loads on Reel It admin pages to avoid style bleed into other WP screens.
     *
     * @since    1.0.0
     * @param    string    $hook_suffix    The current admin page hook suffix.
     */
    public function enqueue_styles( $hook_suffix ) {
        // Why: prevent CSS from loading on every WP admin page
        if ( strpos( $hook_suffix, 'reel-it' ) === false ) {
            return;
        }

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
     * Only loads on Reel It admin pages with deferred footer loading.
     *
     * @since    1.0.0
     * @param    string    $hook_suffix    The current admin page hook suffix.
     */
    public function enqueue_scripts( $hook_suffix ) {
        // Why: prevent JS from loading on every WP admin page
        if ( strpos( $hook_suffix, 'reel-it' ) === false ) {
            return;
        }

        wp_register_script(
            'reel-it-utils',
            plugin_dir_url( __FILE__ ) . 'js/reel-it-utils.js',
            array(),
            $this->version,
            true
        );

        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'js/reel-it-admin.js',
            array( 'jquery', 'reel-it-utils' ),
            $this->version,
            true // Why: load in footer so it doesn't block initial render
        );

        // Pass AJAX URL, config, and UI strings to JavaScript.
        // Why: strings live on the same object as ajax_url so admin JS doesn't
        // need to reach into a different script's global (old reelItSettings).
        $options = get_option( 'reel_it_options', array() );
        wp_localize_script(
            $this->plugin_name,
            'reel_it_admin',
            array(
                'ajax_url'    => admin_url( 'admin-ajax.php' ),
                'nonce'       => wp_create_nonce( 'reel_it_nonce' ),
                // BUG-09 fix: pass configured max file size so JS doesn't hardcode 50MB
                'maxFileSize' => isset( $options['max_file_size'] ) ? intval( $options['max_file_size'] ) : Reel_It::DEFAULT_MAX_FILE_SIZE,
                'strings'     => array(
                    'videoUploaded'        => __( 'Video uploaded successfully.', 'reel-it' ),
                    'uploadFailed'         => __( 'Upload failed.', 'reel-it' ),
                    'uploadFailedInvalid'  => __( 'Upload failed: invalid server response.', 'reel-it' ),
                    'uploadFailedServer'   => __( 'Upload failed: server error.', 'reel-it' ),
                    'uploadFailedNetwork'  => __( 'Upload failed: network error.', 'reel-it' ),
                    'selectAction'         => __( 'Please select an action.', 'reel-it' ),
                    'selectAtLeastOneVideo' => __( 'Please select at least one video.', 'reel-it' ),
                    'actionFailed'         => __( 'Action failed.', 'reel-it' ),
                    'actionFailedServer'   => __( 'Action failed: server error.', 'reel-it' ),
                    'uploading'            => __( 'Uploading...', 'reel-it' ),
                    'confirmRemoveVideo'   => __( 'Are you sure you want to remove this video?', 'reel-it' ),
                    'videoRemoved'         => __( 'Video removed.', 'reel-it' ),
                ),
            )
        );
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

        // Map response keys to match existing admin endpoint expectations
        wp_send_json_success( array(
            'attachment_id' => $result['id'],
            'url'           => $result['url'],
            'title'         => $result['title'],
        ) );
    }
}