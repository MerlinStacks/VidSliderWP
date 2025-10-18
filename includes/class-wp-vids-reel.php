<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 * @package    Wp_Vids_Reel
 * @subpackage Wp_Vids_Reel/includes
 */

/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Wp_Vids_Reel
 * @subpackage Wp_Vids_Reel/includes
 */
class Wp_Vids_Reel {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Wp_Vids_Reel_Loader    $loader    Maintains and registers all hooks for the plugin.
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
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if ( defined( 'WP_VIDS_REEL_VERSION' ) ) {
            $this->version = WP_VIDS_REEL_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'wp-vids-reel';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_block_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-vids-reel-loader.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-vids-reel-i18n.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-vids-reel-admin.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-vids-reel-settings.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wp-vids-reel-public.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'blocks/class-wp-vids-reel-blocks.php';

        $this->loader = new Wp_Vids_Reel_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Wp_Vids_Reel_i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Wp_Vids_Reel_Admin( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        
        $plugin_settings = new Wp_Vids_Reel_Settings( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'admin_menu', $plugin_settings, 'add_settings_page' );
        $this->loader->add_action( 'admin_init', $plugin_settings, 'register_settings' );
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Wp_Vids_Reel_Public( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
    }

    /**
     * Register all of the hooks related to the block editor functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_block_hooks() {
        $plugin_blocks = new Wp_Vids_Reel_Blocks( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'init', $plugin_blocks, 'register_blocks' );
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
     * @return    Wp_Vids_Reel_Loader    Orchestrates the hooks of the plugin.
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
}