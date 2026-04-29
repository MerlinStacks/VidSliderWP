<?php
/**
 * Settings page functionality for the plugin.
 *
 * @since      1.0.0
 * @package    Reel_It
 * @subpackage Reel_It/admin
 */

/**
 * Settings page functionality.
 *
 * @since      1.0.0
 * @package    Reel_It
 * @subpackage Reel_It/admin
 */
class Reel_It_Settings {

    private $plugin_name;
    private $version;
    private $database;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    private function get_database() {
        if ( ! isset( $this->database ) ) {
            $this->database = Reel_It_Database::instance();
        }
        return $this->database;
    }

    /**
     * Set up admin menu and pages
     */
    public function add_settings_page() {
        // Top Level Menu - VidSliderWP
        // Default page is Galleries (replaces the 'manage feeds' tab)
        add_menu_page(
            __( 'Reel IT', 'reel-it' ),
            __( 'Reel IT', 'reel-it' ),
            'manage_options',
            'reel-it',
            array( $this, 'render_galleries_page' ),
            'dashicons-video-alt2',
            26
        );

        // Submenu: Galleries (Same as top level)
        add_submenu_page(
            'reel-it',
            __( 'Galleries', 'reel-it' ),
            __( 'Galleries', 'reel-it' ),
            'manage_options',
            'reel-it',
            array( $this, 'render_galleries_page' )
        );

        // Submenu: Settings
        add_submenu_page(
            'reel-it',
            __( 'Settings', 'reel-it' ),
            __( 'Settings', 'reel-it' ),
            'manage_options',
            'reel-it-settings',
            array( $this, 'render_settings_page' )
        );

        // Submenu: Analytics
        add_submenu_page(
            'reel-it',
            __( 'Analytics', 'reel-it' ),
            __( 'Analytics', 'reel-it' ),
            'manage_options',
            'reel-it-analytics',
            array( $this, 'render_analytics_page' )
        );
    }

    public function enqueue_settings_assets( $hook_suffix ) {
        // Enqueue on both gallery and settings pages.
        // The hook suffix for the top level menu is 'toplevel_page_reel-it'
        // The hook suffix for the submenu is 'vidsliderwp_page_reel-it-settings'
        
        if ( strpos( $hook_suffix, 'reel-it' ) === false ) {
            return;
        }

        // Enqueue Media Library
        wp_enqueue_media();

        // Google Fonts: Inter
        wp_enqueue_style( 
            'reel-it-fonts', 
            'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', 
            array(), 
            $this->version 
        );

        wp_enqueue_style(
            'reel-it-settings-modern',
            plugin_dir_url( __FILE__ ) . 'css/reel-it-settings.css',
            array(),
            $this->version,
            'all'
        );
        
        wp_enqueue_script(
            'reel-it-settings-modern',
            plugin_dir_url( __FILE__ ) . 'js/reel-it-settings.js',
            array( 'jquery', 'jquery-ui-sortable', 'reel-it-utils' ),
            $this->version,
            true
        );
        
        wp_localize_script(
            'reel-it-settings-modern',
            'reelItSettings',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'reel_it_nonce' ),
                'strings' => array(
                    'settingsSaved' => __( 'Settings saved successfully!', 'reel-it' ),
                    'savingSettings' => __( 'Saving settings...', 'reel-it' ),
                    'errorOccurred' => __( 'An error occurred. Please try again.', 'reel-it' ),
                    // translators: %s: Tab name
                    'tabChanged' => __( 'Tab changed to %s', 'reel-it' ),
                    'feedNameRequired' => __( 'Feed name is required.', 'reel-it' ),
                    // translators: %s: Feed name
                    'confirmDelete' => __( 'Are you sure you want to delete the feed "%s"? This action cannot be undone.', 'reel-it' ),
                    'feedCreated' => __( 'Feed created successfully.', 'reel-it' ),
                    'feedUpdated' => __( 'Feed updated successfully.', 'reel-it' ),
                    'feedDeleted' => __( 'Feed deleted successfully.', 'reel-it' ),
                    'feedCreateFailed' => __( 'Failed to create feed.', 'reel-it' ),
                    'feedUpdateFailed' => __( 'Failed to update feed.', 'reel-it' ),
                    'feedDeleteFailed' => __( 'Failed to delete feed.', 'reel-it' ),
                    'invalidFeedData' => __( 'Invalid feed data.', 'reel-it' ),
                    'invalidFeedId' => __( 'Invalid feed ID.', 'reel-it' ),
                    'noPermissionFeeds' => __( 'You do not have permission to manage feeds.', 'reel-it' ),
                    'videoAdded' => __( 'Video added to feed successfully.', 'reel-it' ),
                    'videoRemoved' => __( 'Video removed from feed successfully.', 'reel-it' ),
                    'videoAddFailed' => __( 'Failed to add video to feed.', 'reel-it' ),
                    'videoRemoveFailed' => __( 'Failed to remove video from feed.', 'reel-it' ),
                    'videoOrderUpdated' => __( 'Video order updated successfully.', 'reel-it' ),
                    'invalidVideoData' => __( 'Invalid video data.', 'reel-it' ),
                    'noPermissionVideos' => __( 'You do not have permission to manage videos.', 'reel-it' ),
                    'createFeed' => __( 'Create Feed', 'reel-it' ),
                    'updateFeed' => __( 'Update Feed', 'reel-it' ),
                    'cancel' => __( 'Cancel', 'reel-it' ),
                    'edit' => __( 'Edit', 'reel-it' ),
                    'confirmRemoveVideo' => __( 'Are you sure you want to remove this video?', 'reel-it' ),
                    'videoRemoved' => __( 'Video removed.', 'reel-it' ),
                    'selectValidVideo' => __( 'Please select a valid video file.', 'reel-it' ),
                    'selectVideos' => __( 'Select Videos', 'reel-it' ),
                    'addToSlider' => __( 'Add to Slider', 'reel-it' ),
                    'uploading' => __( 'Uploading...', 'reel-it' ),
                    'delete' => __( 'Delete', 'reel-it' ),
                    'success' => __( 'Success', 'reel-it' ),
                    'error' => __( 'Error', 'reel-it' ),
                    'noFeedsCreated' => __( 'No galleries created yet.', 'reel-it' ),
                    'manageVideos' => __( 'Manage Videos', 'reel-it' ),
                    'videosAddedSuccess' => __( 'Videos added successfully', 'reel-it' ),
                    'videoOrderFailed' => __( 'Failed to update order', 'reel-it' ),
                    'tagsSaved' => __( 'Tags saved successfully', 'reel-it' ),
                    'tagsSaveFailed' => __( 'Error saving tags', 'reel-it' ),
                    'saveTags' => __( 'Save Tags', 'reel-it' ),
                    'connectionError' => __( 'Connection error. Please try again.', 'reel-it' ),
                    'shortcodeCopied' => __( 'Shortcode copied to clipboard!', 'reel-it' ),
                    'shortcodeCopyFailed' => __( 'Failed to copy shortcode', 'reel-it' ),
                    'singleProductNotice' => __( 'Only one product can be tagged per video.', 'reel-it' ),
                    'saving' => __( 'Saving...', 'reel-it' )
                )
            )
        );
    }

    public function register_settings() {
        register_setting(
            'reel_it_settings',
            'reel_it_options',
            array( $this, 'sanitize_settings' )
        );
        $this->register_general_settings();
        $this->register_upload_settings();
    }

    private function register_general_settings() {
        add_settings_section(
            'reel_it_general',
            __( 'General Settings', 'reel-it' ),
            array( $this, 'render_section_info' ),
            'reel-it-settings'
        );

        $fields = array(
            'default_autoplay' => array(
                'title' => __( 'Default Autoplay', 'reel-it' ),
                'description' => __( 'Enable autoplay by default for new video sliders', 'reel-it' )
            ),
            'default_show_controls' => array(
                'title' => __( 'Default Show Controls', 'reel-it' ),
                'description' => __( 'Show video controls by default', 'reel-it' )
            ),
            'default_show_thumbnails' => array(
                'title' => __( 'Default Show Thumbnails', 'reel-it' ),
                'description' => __( 'Show thumbnail navigation by default', 'reel-it' )
            ),
            'default_slider_speed' => array(
                'title' => __( 'Default Slider Speed', 'reel-it' ),
                'description' => __( 'Default time between slides in milliseconds', 'reel-it' ),
                'min' => 1000, 'max' => 10000, 'step' => 500
            ),
            'border_radius' => array(
                'title' => __( 'Border Radius (px)', 'reel-it' ),
                'description' => __( 'Rounded corners for video cards', 'reel-it' ),
                'min' => 0, 'max' => 50, 'step' => 1
            ),
            'video_gap' => array(
                'title' => __( 'Video Gap (px)', 'reel-it' ),
                'description' => __( 'Space between videos', 'reel-it' ),
                'min' => 0, 'max' => 50, 'step' => 1
            ),
            'default_videos_per_row' => array(
                'title' => __( 'Default Videos Per Row', 'reel-it' ),
                'description' => __( 'Number of videos visible per row on desktop', 'reel-it' ),
                'min' => 1, 'max' => 6, 'step' => 1
            )
        );

        foreach ( $fields as $id => $args ) {
            add_settings_field(
                $id,
                $args['title'],
                array( $this, 'render_field' ),
                'reel-it-settings',
                'reel_it_general',
                array(
                    'id' => $id,
                    'type' => ( $id === 'default_slider_speed' ) ? 'range' : ( ( in_array( $id, array( 'border_radius', 'video_gap', 'default_videos_per_row' ), true ) ) ? 'number' : 'checkbox' ),
                    'description' => $args['description'],
                    'min' => isset( $args['min'] ) ? $args['min'] : null,
                    'max' => isset( $args['max'] ) ? $args['max'] : null,
                    'step' => isset( $args['step'] ) ? $args['step'] : null
                )
            );
        }
    }

    private function register_upload_settings() {
        add_settings_section(
            'reel_it_upload',
            __( 'Upload Settings', 'reel-it' ),
            array( $this, 'render_section_info' ),
            'reel-it-settings'
        );

        add_settings_field(
            'max_file_size',
            __( 'Maximum File Size', 'reel-it' ),
            array( $this, 'render_field' ),
            'reel-it-settings',
            'reel_it_upload',
            array(
                'id' => 'max_file_size',
                'type' => 'number',
                'description' => __( 'Maximum file size for video uploads in MB (default: 50)', 'reel-it' )
            )
        );

        add_settings_field(
            'allowed_formats',
            __( 'Allowed Formats', 'reel-it' ),
            array( $this, 'render_field' ),
            'reel-it-settings',
            'reel_it_upload',
            array(
                'id' => 'allowed_formats',
                'type' => 'checkbox_group',
                'description' => __( 'Select which video formats are allowed for upload', 'reel-it' ),
                'options' => array(
                    'mp4' => 'MP4', 'webm' => 'WebM', 'ogg' => 'OGG', 'mov' => 'QuickTime', 'avi' => 'AVI'
                )
            )
        );
    }
    // -- RENDER PAGES --

    /**
     * Render the Galleries management page.
     *
     * @since 1.0.0
     */
    public function render_galleries_page() {
        $database = $this->get_database();
        $feeds    = $database->get_feeds_with_thumbnails();
        require __DIR__ . '/views/page-galleries.php';
    }

    /**
     * Render the Settings page.
     *
     * @since 1.0.0
     */
    public function render_settings_page() {
        require __DIR__ . '/views/page-settings.php';
    }

    public function sanitize_settings( $input ) {
        // Why: merging preserves keys from other tabs/sources that aren't in this POST.
    $sanitized = get_option( 'reel_it_options', array() );
        $checkbox_fields = array( 'default_autoplay', 'default_show_controls', 'default_show_thumbnails' );
        foreach ( $checkbox_fields as $field ) { $sanitized[$field] = isset( $input[$field] ) ? 1 : 0; }
        if ( isset( $input['default_slider_speed'] ) ) { $sanitized['default_slider_speed'] = intval( $input['default_slider_speed'] ); }
        if ( isset( $input['border_radius'] ) ) { $sanitized['border_radius'] = intval( $input['border_radius'] ); }
        if ( isset( $input['video_gap'] ) ) { $sanitized['video_gap'] = intval( $input['video_gap'] ); }
    if ( isset( $input['default_videos_per_row'] ) ) { $sanitized['default_videos_per_row'] = max( 1, min( 6, intval( $input['default_videos_per_row'] ) ) ); }
        // Why: zero or negative values would block all uploads.
    if ( isset( $input['max_file_size'] ) ) { $sanitized['max_file_size'] = max( 1, intval( $input['max_file_size'] ) ); }
        if ( isset( $input['allowed_formats'] ) && is_array( $input['allowed_formats'] ) ) {
            $sanitized['allowed_formats'] = array_map( 'sanitize_text_field', $input['allowed_formats'] );
        }
        return $sanitized;
    }

    // Why: render_section_info kept for backward compat with register_settings() callbacks.
    public function render_section_info() { echo '<p>' . esc_html__( 'Configure the default settings.', 'reel-it' ) . '</p>'; }

    /**
     * Render the Analytics dashboard page.
     *
     * @since 1.4.0
     */
    public function render_analytics_page() {
        // BUG-14 fix: clamp days to a max of 365 to prevent expensive queries
        $days = isset( $_GET['days'] ) ? min( absint( wp_unslash( $_GET['days'] ) ), 365 ) : 30; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $analytics  = new Reel_It_Analytics();
        $stats      = $analytics->get_summary_stats( $days );
        $top_videos = $analytics->get_top_videos( $days, 10 );
        require __DIR__ . '/views/page-analytics.php';
    }
}

