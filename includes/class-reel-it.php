<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 * @package    Reel_It
 * @subpackage Reel_It/includes
 */

/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Reel_It
 * @subpackage Reel_It/includes
 */
class Reel_It {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Reel_It_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Default Constants
     */
    const DEFAULT_SLIDER_SPEED = 5000;
    const DEFAULT_PAGINATION = 20;
    const DEFAULT_VIDEO_GAP = 15;
    const DEFAULT_BORDER_RADIUS = 0;
    const DEFAULT_MAX_FILE_SIZE = 50;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if ( defined( 'REEL_IT_VERSION' ) ) {
            $this->version = REEL_IT_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'reel-it';

        $this->load_dependencies();
        $this->set_locale();
        // Why: admin classes are only needed in wp-admin or during AJAX.
        // Skipping them on frontend saves ~1200 lines of PHP parse time.
        if ( is_admin() || wp_doing_ajax() ) {
            $this->define_admin_hooks();
        }
        $this->define_public_hooks();
        $this->define_block_hooks();
        // Database tables are now created via activation hook in reel-it.php
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-reel-it-loader.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-reel-it-i18n.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-reel-it-database.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-reel-it-upload-handler.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-reel-it-analytics.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-reel-it-ajax-helper.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-reel-it-public.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'blocks/class-reel-it-blocks-secure.php';

        $this->loader = new Reel_It_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Reel_It_i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        // Why: load admin classes on demand so frontend requests never parse them.
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-reel-it-admin.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-reel-it-settings.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-reel-it-ajax-feeds.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-reel-it-ajax-products.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-reel-it-ajax-analytics.php';

        $plugin_admin = new Reel_It_Admin( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        
        $plugin_settings = new Reel_It_Settings( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'admin_menu', $plugin_settings, 'add_settings_page' );
        $this->loader->add_action( 'admin_init', $plugin_settings, 'register_settings' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_settings, 'enqueue_settings_assets' );
        
        // Register AJAX handlers — delegated to dedicated handler classes
        $ajax_feeds = new Reel_It_Ajax_Feeds();
        $this->loader->add_action( 'wp_ajax_reel_it_create_feed', $ajax_feeds, 'ajax_create_feed' );
        $this->loader->add_action( 'wp_ajax_reel_it_update_feed', $ajax_feeds, 'ajax_update_feed' );
        $this->loader->add_action( 'wp_ajax_reel_it_delete_feed', $ajax_feeds, 'ajax_delete_feed' );
        $this->loader->add_action( 'wp_ajax_reel_it_get_feeds', $ajax_feeds, 'ajax_get_feeds' );
        $this->loader->add_action( 'wp_ajax_reel_it_get_feed_videos', $ajax_feeds, 'ajax_get_feed_videos' );
        $this->loader->add_action( 'wp_ajax_reel_it_add_video_to_feed', $ajax_feeds, 'ajax_add_video_to_feed' );
        $this->loader->add_action( 'wp_ajax_reel_it_remove_video_from_feed', $ajax_feeds, 'ajax_remove_video_from_feed' );
        $this->loader->add_action( 'wp_ajax_reel_it_update_video_order', $ajax_feeds, 'ajax_update_video_order' );
        $this->loader->add_action( 'wp_ajax_reel_it_search_videos', $ajax_feeds, 'ajax_search_videos' );
        
        // Product Tagging AJAX
        $ajax_products = new Reel_It_Ajax_Products();
        $this->loader->add_action( 'wp_ajax_reel_it_search_products', $ajax_products, 'ajax_search_products' );
        $this->loader->add_action( 'wp_ajax_reel_it_get_video_products', $ajax_products, 'ajax_get_video_products' );
        $this->loader->add_action( 'wp_ajax_reel_it_save_video_products', $ajax_products, 'ajax_save_video_products' );
        
        // Analytics AJAX
        $ajax_analytics = new Reel_It_Ajax_Analytics();
        $this->loader->add_action( 'wp_ajax_reel_it_get_analytics', $ajax_analytics, 'ajax_get_analytics' );

        // BUG-11 fix: clean up orphaned feed_videos rows when an attachment is deleted
        $database = Reel_It_Database::instance();
        $this->loader->add_action( 'delete_attachment', $database, 'cleanup_deleted_attachment' );
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Reel_It_Public( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        $this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );
        
        // Analytics tracking - allow both logged-in and guest users
        $this->loader->add_action( 'wp_ajax_reel_it_track_event', $plugin_public, 'ajax_track_event' );
        $this->loader->add_action( 'wp_ajax_nopriv_reel_it_track_event', $plugin_public, 'ajax_track_event' );

        // Why: pre-resolve DNS for the AJAX endpoint so the first analytics
        // POST avoids a cold DNS lookup (~50-100ms saving).
        $this->loader->add_filter( 'wp_resource_hints', $plugin_public, 'add_resource_hints', 10, 2 );
    }

    /**
     * Register all of the hooks related to the block editor functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_block_hooks() {
        $plugin_blocks = new Reel_It_Blocks_Secure( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'init', $plugin_blocks, 'register_blocks' );
    }

    /**
     * Create database tables
     *
     * @since    1.1.1
     * @access   private
     */
    private function create_database_tables() {
        $database = Reel_It_Database::instance();
        $database->create_tables();
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Reel_It_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Check if a supported shop plugin is active
     *
     * @since     1.2.0
     * @return    bool    True if shop is active
     */
    public static function is_shop_active() {
        return class_exists( 'WooCommerce' );
    }
}