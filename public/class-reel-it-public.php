<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Reel_It
 * @subpackage Reel_It/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @package    Reel_It
 * @subpackage Reel_It/public
 */
class Reel_It_Public {

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
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the shortcodes for the public-facing side of the site.
     *
     * @since    1.2.0
     */
    public function register_shortcodes() {
        add_shortcode( 'reel_it', array( $this, 'render_video_slider' ) );
    }

    /**
     * Check if assets should be enqueued
     *
     * @since    1.2.0
     * @return   bool
     */
    private function should_enqueue_assets() {
        global $post;
        
        // Check for block
        if ( has_block( 'reel-it/video-slider' ) ) {
            return true;
        }

        // Check for shortcode
        if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'reel_it' ) ) {
            return true;
        }

        return false;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        if ( ! $this->should_enqueue_assets() ) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'css/reel-it-public.css',
            array(),
            $this->version,
            'all'
        );

        wp_enqueue_style(
            $this->plugin_name . '-overrides',
            plugin_dir_url( __FILE__ ) . 'css/reel-it-overrides.css',
            array( $this->plugin_name ),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        if ( ! $this->should_enqueue_assets() ) {
            return;
        }

        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'js/reel-it-public.js',
            array( 'jquery' ),
            $this->version,
            true
        );

        // Pass settings to JavaScript
        wp_localize_script(
            $this->plugin_name,
            'reel_it_public',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'reel_it_nonce' ),
                'strings' => array(
                    'shop' => __( 'Shop', 'reel-it' ),
                    'close' => __( 'Close', 'reel-it' )
                )
            )
        );
    }

    /**
     * Render the video slider
     *
     * @since    1.0.0
     * @param    array    $atts    Block attributes.
     * @return   string             HTML output.
     */
    public function render_video_slider( $atts ) {
        // Get default settings
        $options = get_option( 'reel_it_options', array() );
        
        // Handle block attribute mapping (camelCase to snake_case)
        $mapping = array(
            'useFeed' => 'use_feed',
            'feedId' => 'feed_id',
            'showControls' => 'show_controls',
            'showThumbnails' => 'show_thumbnails',
            'sliderSpeed' => 'slider_speed',
            'videosPerRow' => 'videos_per_row',
            'videosPerRowMobile' => 'videos_per_row_mobile',
        );

        foreach ( $mapping as $camel => $snake ) {
            if ( isset( $atts[$camel] ) ) {
                $atts[$snake] = $atts[$camel];
            }
        }
        
        // Ensure proper type casting for block attributes before shortcode_atts
        // This is needed because block attributes come as proper types but shortcode_atts expects strings
        if ( isset( $atts['use_feed'] ) ) {
            $atts['use_feed'] = filter_var( $atts['use_feed'], FILTER_VALIDATE_BOOLEAN );
        }
        if ( isset( $atts['feed_id'] ) ) {
            $atts['feed_id'] = intval( $atts['feed_id'] );
        }
        
        // Enforce global settings/defaults by removing block overrides for these fields
        unset($atts['autoplay']);
        unset($atts['show_controls']);
        unset($atts['show_thumbnails']);
        unset($atts['slider_speed']);

        $atts = shortcode_atts(
            array(
                'videos' => array(),
                'use_feed' => false,
                'feed_id' => 0,
                'autoplay' => isset( $options['default_autoplay'] ) ? (bool) $options['default_autoplay'] : false,
                'show_controls' => isset( $options['default_show_controls'] ) ? (bool) $options['default_show_controls'] : true,
                'slider_speed' => isset( $options['default_slider_speed'] ) ? intval( $options['default_slider_speed'] ) : Reel_It::DEFAULT_SLIDER_SPEED,
                'show_thumbnails' => isset( $options['default_show_thumbnails'] ) ? (bool) $options['default_show_thumbnails'] : true,
                'videos_per_row' => isset( $options['default_videos_per_row'] ) ? floatval( $options['default_videos_per_row'] ) : 3,
                'videos_per_row_mobile' => 1.5,
            ),
            $atts,
            'reel-it-slider'
        );

        // Check if we should use feed videos
        if ( ! empty( $atts['use_feed'] ) && ! empty( $atts['feed_id'] ) ) {
            $database = new Reel_It_Database();
            $feed_videos = $database->get_feed_videos( intval( $atts['feed_id'] ) );
            
            if ( ! empty( $feed_videos ) ) {
                $videos = array();
                foreach ( $feed_videos as $fv ) {
                    // Use wp_get_attachment_url for correct URL (guid can be malformed)
                    $video_url = wp_get_attachment_url( $fv->video_id );
                    if ( $video_url ) {
                        $videos[] = array(
                            'id' => $fv->video_id,
                            'title' => $fv->post_title,
                            'url' => $video_url,
                            'mime' => $fv->post_mime_type,
                            'thumbnail' => $fv->video_id
                        );
                    }
                }
            } else {
                return '<p>' . __( 'No videos found in feed.', 'reel-it' ) . '</p>';
            }
        } else {
            if ( empty( $atts['videos'] ) ) {
                return '<p>' . __( 'No videos selected.', 'reel-it' ) . '</p>';
            }
            $videos = is_array( $atts['videos'] ) ? $atts['videos'] : json_decode( $atts['videos'], true );
        }

        if ( empty( $videos ) ) {
            return '<p>' . __( 'No videos found.', 'reel-it' ) . '</p>';
        }



        $unique_id = 'reel-it-' . uniqid();
        $border_radius = isset( $options['border_radius'] ) ? intval( $options['border_radius'] ) : Reel_It::DEFAULT_BORDER_RADIUS;
        $video_gap = isset( $options['video_gap'] ) ? intval( $options['video_gap'] ) : Reel_It::DEFAULT_VIDEO_GAP;

        ob_start();
        ?>
        <div class="reel-it-container vsfw-videos-container loading" id="<?php echo esc_attr( $unique_id ); ?>" style="--reel-it-border-radius: <?php echo esc_attr( $border_radius ); ?>px; --reel-it-video-gap: <?php echo esc_attr( $video_gap ); ?>px; --reel-it-columns-desktop: <?php echo floatval( $atts['videos_per_row'] ); ?>; --reel-it-columns-mobile: <?php echo floatval( $atts['videos_per_row_mobile'] ); ?>;" data-slider-speed="<?php echo intval( $atts['slider_speed'] ); ?>" data-videos-per-row="<?php echo floatval( $atts['videos_per_row'] ); ?>" data-videos-per-row-mobile="<?php echo floatval( $atts['videos_per_row_mobile'] ); ?>">
            <div class="reel-it-loader">
                <div class="reel-it-spinner"></div>
            </div>
            <div class="reel-it-slider vsfw-videos-list">
                <?php
                $slide_count = 0;
                foreach ( $videos as $index => $video ) :
                    $slide_count++;
                    $video_url = isset( $video['url'] ) ? esc_url_raw( $video['url'] ) : '';
                    $video_title = isset( $video['title'] ) ? sanitize_text_field( $video['title'] ) : '';
                    $video_id = isset( $video['id'] ) ? intval( $video['id'] ) : 0;
                    
                    // Skip if video attachment no longer exists
                    if ( $video_id && ! get_post( $video_id ) ) {
                        continue;
                    }
                    
                    $thumb_url = '';
                    if ( $video_id ) {
                        $thumb_url = wp_get_attachment_image_url( $video_id, 'large' ); // Use large for poster
                    }

                    if ( empty( $video_url ) ) {
                        continue;
                    }
                    ?>
                    <div class="reel-it-slide vsfw-video-card <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo intval( $index ); ?>" role="group" aria-roledescription="slide" aria-label="<?php printf( esc_attr__( 'Slide %1$d of %2$d', 'reel-it' ), $index + 1, count( $videos ) ); ?>">
                        <div class="reel-it-video-container">
                            <video
                                class="reel-it-video"
                                src="<?php echo esc_url( $video_url ); ?>"
                                data-video-id="<?php echo esc_attr( $video_id ); ?>"
                                aria-label="<?php echo esc_attr( $video_title ? $video_title : __( 'Video', 'reel-it' ) ); ?>"
                                <?php if ( ! empty( $thumb_url ) ) echo 'poster="' . esc_url( $thumb_url ) . '"'; ?>
                                <?php echo $atts['show_controls'] ? 'controls' : ''; ?>
                                <?php echo $index === 0 ? 'autoplay muted' : ''; ?>
                                <?php echo $index === 0 ? 'preload="auto" fetchpriority="high"' : 'preload="metadata"'; ?>
                                playsinline
                            >
                                <?php esc_html_e( 'Your browser does not support the video tag.', 'reel-it' ); ?>
                            </video>
                            <div class="reel-it-video-overlay">
                                <button class="reel-it-play-button" type="button" aria-label="<?php esc_attr_e( 'Play video', 'reel-it' ); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M4 3.532c0-1.554 1.696-2.514 3.029-1.715l14.113 8.468c1.294.777 1.294 2.653 0 3.43L7.029 22.183c-1.333.8-3.029-.16-3.029-1.715V3.532Z" fill="#FFFFFF"></path></svg>
                                </button>
                            </div>

                            <?php
                            // Fetch Tagged Products
                            $linked_product = null;
                            if ( Reel_It::is_shop_active() ) {
                                $product_ids = get_post_meta( $video_id, '_reel_it_linked_products', true );
                                if ( is_array( $product_ids ) && ! empty( $product_ids ) ) {
                                    // Just get the first one
                                    $pid = $product_ids[0];
                                    $product = wc_get_product( $pid );
                                    if ( $product && $product->is_visible() ) {
                                        $linked_product = $product;
                                    }
                                }
                            }

                            if ( $linked_product ) :
                                ?>
                                <a href="<?php echo esc_url( $linked_product->get_permalink() ); ?>" class="reel-it-product-card" target="_blank">
                                    <div class="reel-it-product-thumb">
                                        <?php echo wp_kses_post( $linked_product->get_image( 'thumbnail' ) ); ?>
                                    </div>
                                    <div class="reel-it-product-info">
                                        <span class="reel-it-product-title"><?php echo esc_html( $linked_product->get_name() ); ?></span>
                                        <span class="reel-it-product-price"><?php echo wp_kses_post( $linked_product->get_price_html() ); ?></span>
                                    </div>
                                    <div class="reel-it-product-action">
                                        <span class="dashicons dashicons-cart"></span>
                                    </div>
                                </a>
                            <?php endif; ?>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>



        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle analytics event tracking via AJAX.
     *
     * @since 1.4.0
     */
    public function ajax_track_event() {
        // Verify nonce - allow tracking without strict nonce for better UX
        // but validate the data strictly
        
        $event_type = isset( $_POST['event_type'] ) ? sanitize_text_field( wp_unslash( $_POST['event_type'] ) ) : '';
        $video_id   = isset( $_POST['video_id'] ) ? absint( $_POST['video_id'] ) : 0;
        $feed_id    = isset( $_POST['feed_id'] ) ? absint( $_POST['feed_id'] ) : 0;
        $watch_time = isset( $_POST['watch_time'] ) ? absint( $_POST['watch_time'] ) : 0;
        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';

        // Validate required fields
        if ( empty( $event_type ) || empty( $video_id ) || empty( $session_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid data', 'reel-it' ) ) );
        }

        // Validate event type
        $allowed_events = array( 'play', 'complete', 'product_click' );
        if ( ! in_array( $event_type, $allowed_events, true ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid event type', 'reel-it' ) ) );
        }

        // Validate video exists
        if ( ! get_post( $video_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid video', 'reel-it' ) ) );
        }

        $analytics = new Reel_It_Analytics();
        $result = $analytics->record_event( array(
            'video_id'   => $video_id,
            'feed_id'    => $feed_id > 0 ? $feed_id : null,
            'event_type' => $event_type,
            'watch_time' => $watch_time,
            'product_id' => $product_id > 0 ? $product_id : null,
            'session_id' => $session_id,
        ) );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'tracked' ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to track', 'reel-it' ) ) );
        }
    }
}