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
     * Whether to force-enqueue assets (set when rendering inline).
     *
     * Why: should_enqueue_assets() relies on global $post which misses
     * widgets, template parts, and reusable blocks. This flag lets
     * render_video_slider() bypass the guard.
     *
     * @since    1.5.2
     * @access   private
     * @var      bool
     */
    private $force_enqueue = false;

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
        if ( ! $this->force_enqueue && ! $this->should_enqueue_assets() ) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'css/reel-it-public.css',
            array(),
            $this->version,
            'all'
        );

        // Overrides are merged into reel-it-public.css — no separate file needed.
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        if ( ! $this->force_enqueue && ! $this->should_enqueue_assets() ) {
            return;
        }

        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'js/reel-it-public.js',
            array(),
            $this->version,
            array(
                'in_footer' => true,
                'strategy'  => 'defer',
            )
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
        // Why: enqueue here instead of relying solely on should_enqueue_assets(),
        // which misses widgets, template parts, and dynamically rendered contexts.
        $this->force_enqueue = true;
        $this->enqueue_styles();
        $this->enqueue_scripts();
        $this->force_enqueue = false;

        // Why: fetch options once and pass through to prepare_attributes()
        // to avoid a duplicate get_option() call inside that method.
        $options = get_option( 'reel_it_options', array() );
        $atts    = $this->prepare_attributes( $atts, $options );

        $videos = $this->resolve_videos( $atts );
        if ( is_string( $videos ) ) {
            // Why: resolve_videos returns an HTML string on empty/error paths.
            return $videos;
        }

        // Why: wp_unique_id uses a process-global counter, avoiding the
        // microsecond-based collision risk of uniqid() under concurrency.
        $unique_id     = wp_unique_id( 'reel-it-' );
        $border_radius = isset( $options['border_radius'] ) ? intval( $options['border_radius'] ) : Reel_It::DEFAULT_BORDER_RADIUS;
        $video_gap     = isset( $options['video_gap'] ) ? intval( $options['video_gap'] ) : Reel_It::DEFAULT_VIDEO_GAP;
        $total_slides  = count( $videos );

        // Why: $public_instance lets the view partial call resolve_poster_url()
        // for both main posters and thumbnails without duplicating logic.
        $public_instance = $this;

        ob_start();
        include plugin_dir_path( __FILE__ ) . 'views/slider.php';
        return ob_get_clean();
    }

    /**
     * Normalise and merge block/shortcode attributes with global defaults.
     *
     * Why: extracted from render_video_slider() so the 4-step attribute flow
     * (camelCase map → type cast → strip overrides → merge defaults) is named,
     * testable, and self-documenting instead of buried in a 300-line method.
     *
     * @since  1.6.0
     * @param  array $atts Raw attributes from block or shortcode.
     * @return array       Normalised attributes.
     */
    private function prepare_attributes( $atts, $options = null ) {
        // Step 1: Map camelCase block attributes to snake_case.
        $mapping = array(
            'useFeed'            => 'use_feed',
            'feedId'             => 'feed_id',
            'showControls'       => 'show_controls',
            'showThumbnails'     => 'show_thumbnails',
            'sliderSpeed'        => 'slider_speed',
            'videosPerRow'       => 'videos_per_row',
            'videosPerRowMobile' => 'videos_per_row_mobile',
        );
        foreach ( $mapping as $camel => $snake ) {
            if ( isset( $atts[ $camel ] ) ) {
                $atts[ $snake ] = $atts[ $camel ];
            }
        }

        // Step 2: Cast types before shortcode_atts (block attrs arrive as native types).
        if ( isset( $atts['use_feed'] ) ) {
            $atts['use_feed'] = filter_var( $atts['use_feed'], FILTER_VALIDATE_BOOLEAN );
        }
        if ( isset( $atts['feed_id'] ) ) {
            $atts['feed_id'] = intval( $atts['feed_id'] );
        }

        // Step 3: Enforce global-only settings — these cannot be overridden per block.
        // Why: the block editor exposes these fields but the site owner's global
        // defaults should always win; removing the keys before merge achieves this.
        unset( $atts['autoplay'], $atts['show_controls'], $atts['show_thumbnails'], $atts['slider_speed'] );

        // Step 4: Merge with global defaults.
        // Why: accept pre-fetched $options to avoid a duplicate get_option() call.
        if ( null === $options ) {
            $options = get_option( 'reel_it_options', array() );
        }

        return shortcode_atts(
            array(
                'videos'               => array(),
                'use_feed'             => false,
                'feed_id'              => 0,
                'autoplay'             => isset( $options['default_autoplay'] ) ? (bool) $options['default_autoplay'] : false,
                'show_controls'        => isset( $options['default_show_controls'] ) ? (bool) $options['default_show_controls'] : true,
                'slider_speed'         => isset( $options['default_slider_speed'] ) ? intval( $options['default_slider_speed'] ) : Reel_It::DEFAULT_SLIDER_SPEED,
                'show_thumbnails'      => isset( $options['default_show_thumbnails'] ) ? (bool) $options['default_show_thumbnails'] : true,
                'videos_per_row'       => isset( $options['default_videos_per_row'] ) ? floatval( $options['default_videos_per_row'] ) : 3,
                'videos_per_row_mobile' => 1.5,
            ),
            $atts,
            'reel-it-slider'
        );
    }

    /**
     * Resolve the video list from a feed or from block attributes.
     *
     * @since  1.6.0
     * @param  array $atts Processed attributes from prepare_attributes().
     * @return array|string Video array on success, HTML message string on failure.
     */
    private function resolve_videos( $atts ) {
        if ( ! empty( $atts['use_feed'] ) && ! empty( $atts['feed_id'] ) ) {
            $database    = Reel_It_Database::instance();
            $feed_videos = $database->get_feed_videos( intval( $atts['feed_id'] ) );

            if ( empty( $feed_videos ) ) {
                return '<p>' . __( 'No videos found in feed.', 'reel-it' ) . '</p>';
            }

            $videos = array();
            foreach ( $feed_videos as $fv ) {
                $video_url = wp_get_attachment_url( $fv->video_id );
                if ( $video_url ) {
                    $videos[] = array(
                        'id'        => $fv->video_id,
                        'title'     => $fv->post_title,
                        'url'       => $video_url,
                        'mime'      => $fv->post_mime_type,
                        'thumbnail' => $fv->video_id,
                    );
                }
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

        // Why: Batch-prime the WP object cache to avoid N+1 get_post() queries.
        $video_ids = array_filter( array_map( function( $v ) {
            return isset( $v['id'] ) ? intval( $v['id'] ) : 0;
        }, $videos ) );
        if ( ! empty( $video_ids ) ) {
            _prime_post_caches( $video_ids, false, false );
            /* Why: pre-fetch ALL post meta (poster IDs, linked products, etc.)
               in a single query so slider.php's per-slide get_post_meta() calls
               hit the warmed object cache instead of issuing N+1 queries. */
            update_meta_cache( 'post', $video_ids );
        }

        // Pre-filter deleted attachments so slide count and aria-labels are accurate.
        return array_values( array_filter( $videos, function( $video ) {
            $vid = isset( $video['id'] ) ? intval( $video['id'] ) : 0;
            $url = isset( $video['url'] ) ? $video['url'] : '';
            if ( empty( $url ) ) {
                return false;
            }
            if ( $vid && ! get_post( $vid ) ) {
                return false;
            }
            return true;
        } ) );
    }

    /**
     * Resolve the poster image URL for a video attachment.
     *
     * Why: the poster-resolution logic (custom meta → attachment thumbnail
     * fallback) was duplicated in the main render loop and in the thumbnail
     * loop. This shared helper eliminates Finding 6.
     *
     * @since  1.6.0
     * @param  int    $video_id  Attachment ID of the video.
     * @param  string $size      WordPress image size name (e.g. 'large', 'thumbnail').
     * @return array{url: string, srcset: string, sizes: string}
     */
    public function resolve_poster_url( $video_id, $size = 'large' ) {
        $result = array( 'url' => '', 'srcset' => '', 'sizes' => '' );

        if ( ! $video_id ) {
            return $result;
        }

        $poster_id = get_post_meta( $video_id, '_reel_it_poster_id', true );

        if ( $poster_id ) {
            $result['url']    = wp_get_attachment_image_url( intval( $poster_id ), $size );
            $result['srcset'] = wp_get_attachment_image_srcset( intval( $poster_id ), $size );
            $result['sizes']  = wp_get_attachment_image_sizes( intval( $poster_id ), $size );
        }

        // Fallback: use the attachment's own thumbnail (video-generated).
        if ( empty( $result['url'] ) ) {
            $result['url'] = wp_get_attachment_image_url( $video_id, $size );
            if ( ! empty( $result['url'] ) ) {
                $result['srcset'] = wp_get_attachment_image_srcset( $video_id, $size );
                $result['sizes']  = wp_get_attachment_image_sizes( $video_id, $size );
            }
        }

        return $result;
    }

    /**
     * Handle analytics event tracking via AJAX.
     *
     * @since 1.4.0
     */
    public function ajax_track_event() {
        // Why: this endpoint is registered for wp_ajax_nopriv_ — cached pages serve
        // stale nonces that always fail verification. Rate limiting (L376-383) and
        // transient dedup (L385-391) already guard against abuse.
        if ( is_user_logged_in() ) {
            check_ajax_referer( 'reel_it_nonce', 'nonce' );
        }
        
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

        // Rate limit: max 30 events per IP per minute to prevent bot spam.
        $ip_hash = md5( $_SERVER['REMOTE_ADDR'] ?? 'unknown' );
        $rate_key = 'reel_it_rate_' . $ip_hash;
        $event_count = (int) get_transient( $rate_key );
        if ( $event_count >= 30 ) {
            wp_send_json_error( array( 'message' => __( 'Rate limit exceeded', 'reel-it' ) ) );
        }
        set_transient( $rate_key, $event_count + 1, 60 );

        // BUG-06 fix: transient-based deduplication to prevent analytics spam
        $dedup_key = 'reel_it_evt_' . md5( $session_id . $event_type . $video_id );
        if ( get_transient( $dedup_key ) ) {
            // Already recorded this exact event recently
            wp_send_json_success( array( 'message' => 'duplicate' ) );
        }
        set_transient( $dedup_key, 1, 60 ); // 60-second dedup window

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

    /**
     * Add DNS prefetch hint for the AJAX endpoint.
     *
     * Why: the first analytics POST from the frontend incurs a cold DNS lookup.
     * Pre-resolving saves ~50-100ms on that request.
     *
     * @since  1.7.0
     * @param  array  $urls          Existing resource hint URLs.
     * @param  string $relation_type Hint type (dns-prefetch, preconnect, etc.).
     * @return array
     */
    public function add_resource_hints( $urls, $relation_type ) {
        if ( 'dns-prefetch' === $relation_type ) {
            $parsed = wp_parse_url( admin_url( 'admin-ajax.php' ) );
            if ( ! empty( $parsed['host'] ) ) {
                $urls[] = '//' . $parsed['host'];
            }
        }
        return $urls;
    }
}