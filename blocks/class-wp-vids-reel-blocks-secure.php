<?php
/**
 * The block editor functionality of the plugin with enhanced security.
 *
 * @since      1.0.0
 * @package    Wp_Vids_Reel
 * @subpackage Wp_Vids_Reel/blocks
 */

/**
 * The block editor functionality of the plugin.
 *
 * Defines the plugin name, version, and registers the Gutenberg blocks.
 *
 * @since      1.0.0
 * @package    Wp_Vids_Reel
 * @subpackage Wp_Vids_Reel/blocks
 */
class Wp_Vids_Reel_Blocks_Secure {

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
            'wp-vids-reel-block-editor',
            plugin_dir_url( __FILE__ ) . 'js/block-editor.js',
            array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-data', 'wp-i18n' ),
            $this->version,
            true
        );

        // Register block editor style
        wp_register_style(
            'wp-vids-reel-block-editor',
            plugin_dir_url( __FILE__ ) . 'css/block-editor.css',
            array( 'wp-edit-blocks' ),
            $this->version
        );

        // Register block style
        wp_register_style(
            'wp-vids-reel-block-style',
            plugin_dir_url( __FILE__ ) . 'css/block-style.css',
            array(),
            $this->version
        );

        // Get default settings
        $options = get_option( 'wp_vids_reel_options', array() );
        
        // Pass data to block editor
        wp_localize_script(
            'wp-vids-reel-block-editor',
            'wpVidsReelBlock',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'wp_vids_reel_nonce' ),
                'defaults' => array(
                    'autoplay' => isset( $options['default_autoplay'] ) ? (bool) $options['default_autoplay'] : false,
                    'showControls' => isset( $options['default_show_controls'] ) ? (bool) $options['default_show_controls'] : true,
                    'showThumbnails' => isset( $options['default_show_thumbnails'] ) ? (bool) $options['default_show_thumbnails'] : true,
                    'sliderSpeed' => isset( $options['default_slider_speed'] ) ? intval( $options['default_slider_speed'] ) : 5000,
                ),
                'strings' => array(
                    'blockTitle' => __( 'Video Slider', 'wp-vids-reel' ),
                    'blockDescription' => __( 'Display videos in a slider format', 'wp-vids-reel' ),
                    'selectVideos' => __( 'Select Videos', 'wp-vids-reel' ),
                    'uploadVideo' => __( 'Upload Video', 'wp-vids-reel' ),
                    'noVideosSelected' => __( 'No videos selected', 'wp-vids-reel' ),
                    'autoplay' => __( 'Autoplay', 'wp-vids-reel' ),
                    'showControls' => __( 'Show Controls', 'wp-vids-reel' ),
                    'showThumbnails' => __( 'Show Thumbnails', 'wp-vids-reel' ),
                    'sliderSpeed' => __( 'Slider Speed (ms)', 'wp-vids-reel' ),
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
        register_block_type( 'wp-vids-reel/video-slider', array(
            'editor_script' => 'wp-vids-reel-block-editor',
            'editor_style'  => 'wp-vids-reel-block-editor',
            'style'         => 'wp-vids-reel-block-style',
            'attributes'    => array(
                'videos' => array(
                    'type' => 'array',
                    'default' => array(),
                ),
                'autoplay' => array(
                    'type' => 'boolean',
                    'default' => false,
                ),
                'showControls' => array(
                    'type' => 'boolean',
                    'default' => true,
                ),
                'showThumbnails' => array(
                    'type' => 'boolean',
                    'default' => true,
                ),
                'sliderSpeed' => array(
                    'type' => 'number',
                    'default' => 5000,
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
        if ( ! class_exists( 'Wp_Vids_Reel_Public' ) ) {
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wp-vids-reel-public.php';
        }

        $public_class = new Wp_Vids_Reel_Public( $this->plugin_name, $this->version );
        return $public_class->render_video_slider( $attributes );
    }

    /**
     * Register AJAX handlers
     *
     * @since    1.0.0
     */
    private function register_ajax_handlers() {
        // Register video upload handler
        add_action( 'wp_ajax_wp_vids_reel_upload_video', array( $this, 'handle_video_upload' ) );
        add_action( 'wp_ajax_nopriv_wp_vids_reel_upload_video', array( $this, 'handle_video_upload' ) );

        // Register media query handler
        add_action( 'wp_ajax_wp_vids_reel_query_videos', array( $this, 'handle_video_query' ) );
        add_action( 'wp_ajax_nopriv_wp_vids_reel_query_videos', array( $this, 'handle_video_query' ) );
    }

    /**
     * Handle video upload with enhanced security
     *
     * @since    1.0.0
     */
    public function handle_video_upload() {
        check_ajax_referer( 'wp_vids_reel_nonce', 'nonce' );

        if ( ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to upload files.', 'wp-vids-reel' ) ) );
        }

        if ( ! isset( $_FILES['video_file'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'wp-vids-reel' ) ) );
        }

        $file = $_FILES['video_file'];
        
        // Additional security: Validate file upload
        if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid file upload.', 'wp-vids-reel' ) ) );
        }
        
        // Get allowed types from settings or use defaults
        $options = get_option( 'wp_vids_reel_options', array() );
        $allowed_formats = isset( $options['allowed_formats'] ) ? $options['allowed_formats'] : array( 'mp4', 'webm', 'ogg' );
        
        // Convert format names to MIME types
        $mime_types = array(
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo'
        );
        
        $allowed_types = array();
        foreach ( $allowed_formats as $format ) {
            if ( isset( $mime_types[$format] ) ) {
                $allowed_types[] = $mime_types[$format];
            }
        }
        
        // Enhanced MIME type validation
        if ( function_exists( 'finfo_open' ) ) {
            $finfo = finfo_open( FILEINFO_MIME_TYPE );
            if ( $finfo ) {
                $detected_mime = finfo_file( $finfo, $file['tmp_name'] );
                if ( ! in_array( $detected_mime, $allowed_types ) ) {
                    finfo_close( $finfo );
                    wp_send_json_error( array( 'message' => __( 'File type does not match extension. Please upload a valid video file.', 'wp-vids-reel' ) ) );
                }
                finfo_close( $finfo );
            }
        }
        
        if ( ! in_array( $file['type'], $allowed_types ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid file type. Please upload a supported video file.', 'wp-vids-reel' ) ) );
        }
        
        // Check file size
        $max_file_size = isset( $options['max_file_size'] ) ? intval( $options['max_file_size'] ) : 50;
        $max_file_size_bytes = $max_file_size * 1024 * 1024; // Convert MB to bytes
        
        if ( $file['size'] > $max_file_size_bytes ) {
            wp_send_json_error( array( 'message' => sprintf( __( 'File is too large. Maximum size is %d MB.', 'wp-vids-reel' ), $max_file_size ) ) );
        }
        
        // Additional security: Check file content for malicious patterns
        $file_content = file_get_contents( $file['tmp_name'] );
        if ( $file_content === false || strpos( $file_content, '<?php' ) !== false ) {
            wp_send_json_error( array( 'message' => __( 'Invalid file content.', 'wp-vids-reel' ) ) );
        }

        // Handle the upload with enhanced security
        $upload_overrides = array(
            'test_form' => false,
            'mimes' => $allowed_types
        );
        
        $uploaded_file = wp_handle_upload( $file, $upload_overrides );

        if ( isset( $uploaded_file['error'] ) ) {
            wp_send_json_error( array( 'message' => $uploaded_file['error'] ) );
        }

        // Insert into media library with additional security
        $attachment = array(
            'post_mime_type' => $uploaded_file['type'],
            'post_title' => sanitize_file_name( $uploaded_file['file'] ),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_author' => get_current_user_id() // Ensure current user is the author
        );

        $attach_id = wp_insert_attachment( $attachment, $uploaded_file['file'] );
        
        if ( $attach_id ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            $attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded_file['file'] );
            wp_update_attachment_metadata( $attach_id, $attach_data );

            // Log the upload for security auditing
            error_log( sprintf( 'WP Vids Reel: Video uploaded by user %d - Attachment ID: %d', get_current_user_id(), $attach_id ) );

            wp_send_json_success( array(
                'id' => $attach_id,
                'url' => $uploaded_file['url'],
                'title' => get_the_title( $attach_id ),
                'mime' => $uploaded_file['type']
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to save video to media library.', 'wp-vids-reel' ) ) );
        }
    }

    /**
     * Handle video query with enhanced security
     *
     * @since    1.0.0
     */
    public function handle_video_query() {
        check_ajax_referer( 'wp_vids_reel_nonce', 'nonce' );

        // Only allow users who can upload files to query videos
        if ( ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to access video library.', 'wp-vids-reel' ) ) );
        }

        $search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';
        $page = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
        $per_page = 20;

        // Validate page number
        if ( $page < 1 ) {
            $page = 1;
        }

        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'video',
            'post_status' => 'inherit',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'author' => get_current_user_id(), // Only show current user's videos
        );

        if ( ! empty( $search ) ) {
            $args['s'] = $search;
        }

        $query = new WP_Query( $args );
        $videos = array();

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $attachment_id = get_the_ID();
                
                // Verify user can access this attachment
                if ( ! current_user_can( 'read_post', $attachment_id ) ) {
                    continue;
                }
                
                $videos[] = array(
                    'id' => $attachment_id,
                    'title' => get_the_title(),
                    'url' => wp_get_attachment_url( $attachment_id ),
                    'thumbnail' => wp_get_attachment_image_url( $attachment_id, 'thumbnail' ),
                    'mime' => get_post_mime_type( $attachment_id ),
                );
            }
        }

        wp_reset_postdata();

        wp_send_json_success( array(
            'videos' => $videos,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
        ) );
    }
}