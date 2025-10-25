<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Wp_Vids_Reel
 * @subpackage Wp_Vids_Reel/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @package    Wp_Vids_Reel
 * @subpackage Wp_Vids_Reel/public
 */
class Wp_Vids_Reel_Public {

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
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'css/wp-vids-reel-public.css',
            array(),
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
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'js/wp-vids-reel-public.js',
            array( 'jquery' ),
            $this->version,
            true
        );

        // Pass settings to JavaScript
        wp_localize_script(
            $this->plugin_name,
            'wp_vids_reel_public',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'wp_vids_reel_nonce' ),
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
        $options = get_option( 'wp_vids_reel_options', array() );
        
        $atts = shortcode_atts(
            array(
                'videos' => array(),
                'autoplay' => isset( $options['default_autoplay'] ) ? (bool) $options['default_autoplay'] : false,
                'show_controls' => isset( $options['default_show_controls'] ) ? (bool) $options['default_show_controls'] : true,
                'slider_speed' => isset( $options['default_slider_speed'] ) ? intval( $options['default_slider_speed'] ) : 5000,
                'show_thumbnails' => isset( $options['default_show_thumbnails'] ) ? (bool) $options['default_show_thumbnails'] : true,
            ),
            $atts,
            'wp_vids_reel_slider'
        );

        if ( empty( $atts['videos'] ) ) {
            return '<p>' . __( 'No videos selected.', 'wp-vids-reel' ) . '</p>';
        }

        $unique_id = 'wp-vids-reel-' . uniqid();
        $videos = is_array( $atts['videos'] ) ? $atts['videos'] : json_decode( $atts['videos'], true );

        ob_start();
        ?>
        <div class="wp-vids-reel-container" id="<?php echo esc_attr( $unique_id ); ?>" data-slider-speed="<?php echo esc_attr( $atts['slider_speed'] ); ?>">
            <div class="wp-vids-reel-slider">
                <?php foreach ( $videos as $index => $video ) : ?>
                    <?php
                    $video_url = isset( $video['url'] ) ? $video['url'] : '';
                    $video_title = isset( $video['title'] ) ? $video['title'] : '';
                    $video_id = isset( $video['id'] ) ? $video['id'] : '';
                    
                    if ( empty( $video_url ) ) continue;
                    ?>
                    <div class="wp-vids-reel-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>">
                        <video 
                            class="wp-vids-reel-video" 
                            src="<?php echo esc_url( $video_url ); ?>"
                            <?php echo $atts['show_controls'] ? 'controls' : ''; ?>
                            <?php echo $index === 0 ? 'autoplay muted playsinline' : ''; ?>
                            preload="metadata"
                        >
                            <?php _e( 'Your browser does not support the video tag.', 'wp-vids-reel' ); ?>
                        </video>
                        <?php if ( ! empty( $video_title ) ) : ?>
                            <div class="wp-vids-reel-video-title">
                                <?php echo esc_html( $video_title ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ( count( $videos ) > 1 ) : ?>
                <div class="wp-vids-reel-navigation">
                    <button class="wp-vids-reel-prev" aria-label="<?php _e( 'Previous video', 'wp-vids-reel' ); ?>">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                    </button>
                    <button class="wp-vids-reel-next" aria-label="<?php _e( 'Next video', 'wp-vids-reel' ); ?>">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </button>
                </div>

                <?php if ( $atts['show_thumbnails'] ) : ?>
                    <div class="wp-vids-reel-thumbnails">
                        <?php foreach ( $videos as $index => $video ) : ?>
                            <?php
                            $thumbnail_id = isset( $video['thumbnail'] ) ? $video['thumbnail'] : $video_id;
                            $thumbnail_url = wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' );
                            ?>
                            <button class="wp-vids-reel-thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>">
                                <?php if ( $thumbnail_url ) : ?>
                                    <img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( isset( $video['title'] ) ? $video['title'] : '' ); ?>">
                                <?php else : ?>
                                    <div class="wp-vids-reel-thumbnail-placeholder">
                                        <span class="dashicons dashicons-video-alt3"></span>
                                    </div>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}