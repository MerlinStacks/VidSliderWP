<?php
/**
 * The block editor functionality of the plugin with enhanced security.
 *
 * @since      1.0.0
 * @package    Reel_It
 * @subpackage Reel_It/blocks
 */

/**
 * The block editor functionality of the plugin.
 *
 * Defines the plugin name, version, and registers the Gutenberg blocks.
 *
 * @since      1.0.0
 * @package    Reel_It
 * @subpackage Reel_It/blocks
 */
class Reel_It_Blocks_Secure {

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
     * Register all blocks
     *
     * @since    1.0.0
     */
    public function register_blocks() {
        $this->register_block_assets();
        $this->register_video_slider_block();
        $this->register_ajax_handlers();
    }

    /**
     * Register block assets
     *
     * @since    1.0.0
     */
    private function register_block_assets() {
        // Register block editor script
        wp_register_script(
            'reel-it-block-editor',
            plugin_dir_url( __FILE__ ) . 'js/block-editor.js',
            array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-data', 'wp-i18n' ),
            $this->version,
            true
        );

        // Register block editor style
        wp_register_style(
            'reel-it-block-editor',
            plugin_dir_url( __FILE__ ) . 'css/block-editor.css',
            array( 'wp-edit-blocks' ),
            $this->version
        );

        // Register block style
        wp_register_style(
            'reel-it-block-style',
            plugin_dir_url( __FILE__ ) . 'css/block-style.css',
            array(),
            $this->version
        );

        // Get default settings
        $options = get_option( 'reel_it_options', array() );
        
        // Pass data to block editor
        wp_localize_script(
            'reel-it-block-editor',
            'reelItBlock',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'reel_it_nonce' ),
                'defaults' => array(
                    'videosPerRow' => isset( $options['default_videos_per_row'] ) ? intval( $options['default_videos_per_row'] ) : 3,
                ),
                'strings' => array(
                    'blockTitle' => __( 'Video Slider', 'reel-it' ),
                    'blockDescription' => __( 'Display videos in a slider format', 'reel-it' ),
                    'selectVideos' => __( 'Select Videos', 'reel-it' ),
                    'uploadVideo' => __( 'Upload Video', 'reel-it' ),
                    'noVideosSelected' => __( 'No videos selected', 'reel-it' ),
                )
            )
        );
    }

    /**
     * Register video slider block
     *
     * @since    1.0.0
     */
    private function register_video_slider_block() {
        // Why: 'style' key intentionally omitted — block-style.css uses a grid layout
        // that conflicts with the public carousel flexbox. Public CSS handles frontend.
        register_block_type( 'reel-it/video-slider', array(
            'editor_script' => 'reel-it-block-editor',
            'editor_style'  => 'reel-it-block-editor',
            'attributes'    => array(
                'videos' => array(
                    'type' => 'array',
                    'default' => array(),
                ),
                'useFeed' => array(
                    'type' => 'boolean',
                    'default' => false,
                ),
                'feedId' => array(
                    'type' => 'number',
                    'default' => 0,
                ),
                'videosPerRow' => array(
                    'type' => 'number',
                    'default' => 3,
                ),
                'videosPerRowMobile' => array(
                    'type' => 'number',
                    'default' => 1.5,
                ),
                'width' => array(
                    'type' => 'string',
                    'default' => '',
                ),
                'fullWidth' => array(
                    'type' => 'boolean',
                    'default' => false,
                ),
            ),
            'render_callback' => array( $this, 'render_video_slider_block' ),
        ) );
    }

    /**
     * Render video slider block
     *
     * @since    1.0.0
     * @param    array    $attributes    Block attributes.
     * @return   string                  HTML output.
     */
    public function render_video_slider_block( $attributes ) {
        if ( ! class_exists( 'Reel_It_Public' ) ) {
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-reel-it-public.php';
        }

        $public_class = new Reel_It_Public( $this->plugin_name, $this->version );
        /* Why: render_video_slider() already calls enqueue_styles()/enqueue_scripts()
           with force_enqueue = true. Calling them here on a separate instance had no
           effect because force_enqueue defaults to false and should_enqueue_assets()
           may return false during REST/SSR preview. */
        
        $wrapper_attrs = array();
        
        if ( ! empty( $attributes['fullWidth'] ) ) {
            // Force 100% width if full width toggle is on
            $wrapper_attrs['style'] = 'width: 100%;';
        } elseif ( ! empty( $attributes['width'] ) ) {
            $wrapper_attrs['style'] = 'width: ' . esc_attr( $attributes['width'] ) . ';';
        }

        $wrapper_attributes = get_block_wrapper_attributes( $wrapper_attrs );

        return sprintf(
            '<div %s>%s</div>',
            $wrapper_attributes,
            $public_class->render_video_slider( $attributes )
        );
    }

    /**
     * Register AJAX handlers
     *
     * @since    1.0.0
     */
    private function register_ajax_handlers() {
        // Why: upload handler is already registered in Reel_It_Admin — registering
        // a second handler for the same action is dead code and risks response
        // shape mismatches if load order changes.
        // add_action( 'wp_ajax_reel_it_upload_video', array( $this, 'handle_video_upload' ) );
        // Remove nopriv access for security - only authenticated users can upload

        // Register media query handler - only for authenticated users with upload capabilities
        add_action( 'wp_ajax_reel_it_query_videos', array( $this, 'handle_video_query' ) );
        // Remove nopriv access for security - only authenticated users can query videos
    }

    /**
     * Handle video upload with enhanced security
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

        wp_send_json_success( $result );
    }

    /**
     * Handle video query with enhanced security and performance optimizations
     *
     * @since    1.0.0
     */
    public function handle_video_query() {
        Reel_It_Ajax_Helper::verify( 'upload_files' );

        // BUG-07 fix: block editor sends via POST, not GET
        $search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $per_page = Reel_It::DEFAULT_PAGINATION;

        // Validate page number
        if ( $page < 1 ) {
            $page = 1;
        }

        // Use Database class for querying
        if ( ! class_exists( 'Reel_It_Database' ) ) {
             require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-reel-it-database.php';
        }
        
        $database = Reel_It_Database::instance();
        
        $args = array(
            'search' => $search,
            'page' => $page,
            'per_page' => $per_page,
            // Why: users with upload_files can see all media, matching WP core behavior.
            'fields' => 'ids'
        );
        
        $result = $database->get_available_videos( $args );

        wp_send_json_success( $result );
    }
}