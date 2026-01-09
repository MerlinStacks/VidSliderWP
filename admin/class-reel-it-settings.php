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
            $this->database = new Reel_It_Database();
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
            array( 'jquery', 'jquery-ui-sortable' ),
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
                    'noFeedsCreated' => __( 'No galleries created yet.', 'reel-it' )
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
                    'type' => ( $id === 'default_slider_speed' ) ? 'range' : ( ( $id === 'border_radius' || $id === 'video_gap' ) ? 'number' : 'checkbox' ),
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

    public function render_galleries_page() {
        ?>
        <div class="wrap reel-it-settings-wrap reel-it-galleries-page">
            <div class="reel-it-header">
                <h1><?php echo esc_html( __( 'Galleries', 'reel-it' ) ); ?></h1>
                <div class="reel-it-header-description">
                    <p><?php esc_html_e( 'Organize your videos into reusable collections and galleries', 'reel-it' ); ?></p>
                </div>
            </div>

            <div class="reel-it-feeds-section">
                <!-- No Tabs here, just the gallery management interface directly -->
                <div class="reel-it-feeds-container">
                    <div class="reel-it-feeds-list">
                        <!-- Content loaded via PHP initially or AJAX -->
                        <?php
                        $database = $this->get_database();
                        $feeds = $database->get_feeds_with_thumbnails();
                        
                        if ( ! empty( $feeds ) ) {
                            foreach ( $feeds as $feed ) {
                                // Simplified rendering for backend view
                                echo '<div class="reel-it-feed-item" data-feed-id="' . esc_attr( $feed->id ) . '">';
                                    echo '<div class="reel-it-feed-content">';
                                        
                                        // Preview / Thumbnail
                                        echo '<div class="reel-it-feed-preview">';
                                            if ( ! empty( $feed->thumbnail_url ) ) {
                                                echo '<img src="' . esc_url( $feed->thumbnail_url ) . '" alt="' . esc_attr( $feed->thumbnail_alt ) . '" class="reel-it-feed-img">';
                                            } else {
                                                echo '<div class="reel-it-feed-placeholder"><span class="dashicons dashicons-video-alt3"></span></div>';
                                            }
                                        echo '</div>';
                                        
                                        echo '<div class="reel-it-feed-info">';
                                            echo '<div class="reel-it-feed-header">';
                                                echo '<div class="reel-it-feed-name">' . esc_html( $feed->name ) . '</div>';
                                                echo '<div class="reel-it-feed-actions">';
                                                    echo '<button type="button" class="button button-small button-primary reel-it-manage-videos" data-feed-id="' . esc_attr( $feed->id ) . '" data-name="' . esc_attr( $feed->name ) . '">' . esc_html__( 'Manage Videos', 'reel-it' ) . '</button>';
                                                    echo '<button type="button" class="button button-small reel-it-edit-feed" data-feed-id="' . esc_attr( $feed->id ) . '" data-name="' . esc_attr( $feed->name ) . '" data-description="' . esc_attr( $feed->description ) . '">' . esc_html__( 'Edit', 'reel-it' ) . '</button>';
                                                    echo '<button type="button" class="button button-small button-secondary reel-it-delete-feed" data-feed-id="' . esc_attr( $feed->id ) . '" data-name="' . esc_attr( $feed->name ) . '">' . esc_html__( 'Delete', 'reel-it' ) . '</button>';
                                                echo '</div>';
                                            echo '</div>';
                                            echo '<div class="reel-it-feed-description">' . esc_html( $feed->description ) . '</div>';
                                            echo '<div class="reel-it-feed-meta">';
                                                // translators: %s: Number of videos
                                                echo '<span class="reel-it-video-count">' . esc_html( sprintf( _n( '%s Video', '%s Videos', $feed->video_count, 'reel-it' ), $feed->video_count ) ) . '</span>';
                                                
                                                // Shortcode snippet
                                                echo '<div class="reel-it-shortcode-wrapper">';
                                                    echo '<code class="reel-it-shortcode">[reel_it feed_id="' . esc_attr( $feed->id ) . '" use_feed="true"]</code>';
                                                    echo '<button type="button" class="button button-small reel-it-copy-shortcode" title="' . esc_attr__( 'Copy Shortcode', 'reel-it' ) . '">';
                                                        echo '<span class="dashicons dashicons-clipboard"></span>';
                                                    echo '</button>';
                                                echo '</div>';
                                            echo '</div>';
                                        echo '</div>';
                                    echo '</div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="reel-it-no-feeds">';
                                echo '<p>' . esc_html__( 'No galleries created yet. Create your first gallery to get started.', 'reel-it' ) . '</p>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    
                    <div class="reel-it-feed-editor">
                        <h4><?php esc_html_e( 'Create New Gallery', 'reel-it' ); ?></h4>
                        <form id="reel-it-new-feed-form">
                            <div class="reel-it-form-row">
                                <label for="feed-name"><?php esc_html_e( 'Gallery Name', 'reel-it' ); ?> <span class="required">*</span></label>
                                <input type="text" id="feed-name" name="feed_name" class="regular-text" required placeholder="<?php esc_attr_e('e.g. Summer Collection', 'reel-it'); ?>">
                            </div>
                            <div class="reel-it-form-row">
                                <label for="feed-description"><?php esc_html_e( 'Description', 'reel-it' ); ?></label>
                                <textarea id="feed-description" name="feed_description" rows="3" class="large-text" placeholder="<?php esc_attr_e('Optional description for this gallery', 'reel-it'); ?>"></textarea>
                            </div>
                            <div class="reel-it-form-actions">
                                <button type="button" class="button button-primary" id="reel-it-create-feed"><?php esc_html_e( 'Create Gallery', 'reel-it' ); ?></button>
                                <button type="button" class="button button-secondary" id="reel-it-cancel-edit" style="display: none;"><?php esc_html_e( 'Cancel', 'reel-it' ); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Video Manager Modal -->
            <div id="reel-it-video-modal" class="reel-it-modal" style="display:none;">
                <div class="reel-it-modal-overlay"></div>
                <div class="reel-it-modal-content">
                    <div class="reel-it-modal-header">
                        <h2 id="reel-it-modal-title"><?php esc_html_e( 'Manage Gallery Videos', 'reel-it' ); ?></h2>
                        <button type="button" class="reel-it-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
                    </div>
                    <div class="reel-it-modal-body">
                        <div class="reel-it-video-toolbar">
                            <button type="button" class="button button-primary" id="reel-it-add-videos">
                                <span class="dashicons dashicons-plus-alt2" style="margin-top:4px;"></span> <?php esc_html_e( 'Add Videos', 'reel-it' ); ?>
                            </button>
                            <span class="spinner" style="float:none; margin:0 10px;"></span>
                        </div>
                        
                        <div id="reel-it-video-list-container" class="reel-it-video-grid">
                            <!-- Videos will be loaded here via AJAX -->
                        </div>
                        
                        <div id="reel-it-no-videos-message" style="display:none; text-align:center; padding:40px; color:#64748b;">
                            <span class="dashicons dashicons-video-alt3" style="font-size:48px; width:48px; height:48px; color:#cbd5e1; margin-bottom:10px;"></span>
                            <p><?php esc_html_e( 'No videos in this gallery yet.', 'reel-it' ); ?></p>
                            <button type="button" class="button button-secondary reel-it-add-first-video"><?php esc_html_e( 'Add Your First Video', 'reel-it' ); ?></button>
                        </div>
                    </div>
                    <div class="reel-it-modal-footer">
                        <button type="button" class="button button-large button-secondary reel-it-modal-close-btn"><?php esc_html_e( 'Close', 'reel-it' ); ?></button>
                    </div>
                </div>
            </div>

            <!-- Product Tagging Modal -->
            <div id="reel-it-product-modal" class="reel-it-modal" style="display:none; z-index: 100001;">
                <div class="reel-it-modal-overlay"></div>
                <div class="reel-it-modal-content" style="max-width: 600px;">
                    <div class="reel-it-modal-header">
                        <h2><?php esc_html_e( 'Tag Products', 'reel-it' ); ?></h2>
                        <button type="button" class="reel-it-product-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
                    </div>
                    <div class="reel-it-modal-body">
                        <div class="reel-it-product-search-container">
                            <input type="text" id="reel-it-product-search" class="large-text" placeholder="<?php esc_attr_e( 'Search for products...', 'reel-it' ); ?>" autocomplete="off">
                            <span class="spinner" id="reel-it-product-spinner" style="float:none; margin: 10px 0 0;"></span>
                            <ul id="reel-it-product-results" class="reel-it-product-list">
                                <!-- Search results -->
                            </ul>
                        </div>
                        
                        <div class="reel-it-tagged-products-section">
                            <h4><?php esc_html_e( 'Tagged Products', 'reel-it' ); ?></h4>
                            <div id="reel-it-tagged-list" class="reel-it-product-chips">
                                <!-- Tagged items -->
                            </div>
                            <p id="reel-it-no-tags" class="description"><?php esc_html_e( 'No products tagged yet.', 'reel-it' ); ?></p>
                        </div>
                    </div>
                    <div class="reel-it-modal-footer">
                        <button type="button" class="button button-primary" id="reel-it-save-tags"><?php esc_html_e( 'Save Tags', 'reel-it' ); ?></button>
                        <button type="button" class="button button-secondary reel-it-product-modal-close-btn"><?php esc_html_e( 'Cancel', 'reel-it' ); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings_page() {
        ?>
        <div class="wrap reel-it-settings-wrap">
            <div class="reel-it-header">
                <h1><?php echo esc_html( __( 'Settings', 'reel-it' ) ); ?></h1>
                <div class="reel-it-header-description">
                    <p><?php esc_html_e( 'Configure default settings for your video sliders', 'reel-it' ); ?></p>
                </div>
            </div>

            <!-- Tabbed Interface for Settings Only -->
            <div class="reel-it-tabs-container">
                <nav class="reel-it-tabs">
                    <button class="reel-it-tab active" data-tab="general">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php esc_html_e( 'General', 'reel-it' ); ?>
                    </button>
                    <button class="reel-it-tab" data-tab="upload">
                        <span class="dashicons dashicons-upload"></span>
                        <?php esc_html_e( 'Upload', 'reel-it' ); ?>
                    </button>
                    <button class="reel-it-tab" data-tab="help">
                        <span class="dashicons dashicons-editor-help"></span>
                        <?php esc_html_e( 'Help', 'reel-it' ); ?>
                    </button>
                </nav>

                <div class="reel-it-tab-content">
                    <!-- General Tab -->
                    <div class="reel-it-tab-pane active" id="general">
                        <form action="options.php" method="post" class="reel-it-settings-form">
                            <?php
                            settings_fields( 'reel_it_settings' );
                            $options = get_option( 'reel_it_options', array() );
                            ?>
                            <div class="reel-it-form-section">
                                <p class="reel-it-section-desc"><?php esc_html_e( 'Configure the default settings.', 'reel-it' ); ?></p>
                                
                                <!-- Autoplay -->
                                <div class="reel-it-form-row">
                                    <div class="reel-it-toggle-row">
                                        <?php $autoplay = isset( $options['default_autoplay'] ) ? $options['default_autoplay'] : 0; ?>
                                        <input type="checkbox" id="default_autoplay" name="reel_it_options[default_autoplay]" value="1" <?php checked( 1, $autoplay ); ?>>
                                        <label for="default_autoplay" style="display:inline;"><?php esc_html_e( 'Default Autoplay', 'reel-it' ); ?></label>
                                    </div>
                                    <p class="description"><?php esc_html_e( 'Enable autoplay by default for new video sliders', 'reel-it' ); ?></p>
                                </div>

                                <!-- Show Controls -->
                                <div class="reel-it-form-row">
                                    <div class="reel-it-toggle-row">
                                        <?php $controls = isset( $options['default_show_controls'] ) ? $options['default_show_controls'] : 0; ?>
                                        <input type="checkbox" id="default_show_controls" name="reel_it_options[default_show_controls]" value="1" <?php checked( 1, $controls ); ?>>
                                        <label for="default_show_controls" style="display:inline;"><?php esc_html_e( 'Show Controls', 'reel-it' ); ?></label>
                                    </div>
                                    <p class="description"><?php esc_html_e( 'Show video controls by default', 'reel-it' ); ?></p>
                                </div>

                                <!-- Show Thumbnails -->
                                <div class="reel-it-form-row">
                                    <div class="reel-it-toggle-row">
                                        <?php $thumbs = isset( $options['default_show_thumbnails'] ) ? $options['default_show_thumbnails'] : 0; ?>
                                        <input type="checkbox" id="default_show_thumbnails" name="reel_it_options[default_show_thumbnails]" value="1" <?php checked( 1, $thumbs ); ?>>
                                        <label for="default_show_thumbnails" style="display:inline;"><?php esc_html_e( 'Show Thumbnails', 'reel-it' ); ?></label>
                                    </div>
                                    <p class="description"><?php esc_html_e( 'Show thumbnail navigation by default', 'reel-it' ); ?></p>
                                </div>

                                <!-- Slider Speed -->
                                <div class="reel-it-form-row">
                                    <label for="default_slider_speed"><?php esc_html_e( 'Default Slider Speed', 'reel-it' ); ?></label>
                                    <?php $speed = isset( $options['default_slider_speed'] ) ? $options['default_slider_speed'] : Reel_It::DEFAULT_SLIDER_SPEED; ?>
                                    <input type="range" id="default_slider_speed" name="reel_it_options[default_slider_speed]" value="<?php echo esc_attr( $speed ); ?>" min="1000" max="10000" step="500" class="reel-it-range-slider">
                                    <span class="reel-it-range-value" style="display:block; margin-top:5px; font-weight:600; color:var(--vids-primary);"><?php echo esc_html( $speed ); ?>ms</span>
                                    <p class="description"><?php esc_html_e( 'Default time between slides in milliseconds', 'reel-it' ); ?></p>
                                </div>

                                <!-- Border Radius -->
                                <div class="reel-it-form-row">
                                    <label for="border_radius"><?php esc_html_e( 'Border Radius (px)', 'reel-it' ); ?></label>
                                    <?php $radius = isset( $options['border_radius'] ) ? $options['border_radius'] : Reel_It::DEFAULT_BORDER_RADIUS; ?>
                                    <input type="number" id="border_radius" name="reel_it_options[border_radius]" value="<?php echo esc_attr( $radius ); ?>" min="0" max="50" class="regular-text">
                                    <p class="description"><?php esc_html_e( 'Rounded corners for video cards (0 for square)', 'reel-it' ); ?></p>
                                </div>

                                <!-- Video Gap -->
                                <div class="reel-it-form-row">
                                    <label for="video_gap"><?php esc_html_e( 'Video Gap (px)', 'reel-it' ); ?></label>
                                    <?php $gap = isset( $options['video_gap'] ) ? $options['video_gap'] : Reel_It::DEFAULT_VIDEO_GAP; ?>
                                    <input type="number" id="video_gap" name="reel_it_options[video_gap]" value="<?php echo esc_attr( $gap ); ?>" min="0" max="100" class="regular-text">
                                    <p class="description"><?php esc_html_e( 'Space between videos (default: 15)', 'reel-it' ); ?></p>
                                </div>
                            </div>

                            <div class="reel-it-form-actions">
                                <?php submit_button( __( 'Save Settings', 'reel-it' ), 'primary', 'submit', false ); ?>
                            </div>
                        </form>
                    </div>

                    <!-- Upload Tab -->
                    <div class="reel-it-tab-pane" id="upload">
                        <div class="reel-it-upload-section">
                            <h3><?php esc_html_e( 'Upload Settings', 'reel-it' ); ?></h3>
                            <form action="options.php" method="post" class="reel-it-settings-form">
                                <?php
                                settings_fields( 'reel_it_settings' );
                                $options = get_option( 'reel_it_options', array() );
                                ?>
                                <div class="reel-it-form-section">
                                    
                                    <!-- Max File Size -->
                                    <div class="reel-it-form-row">
                                        <label for="max_file_size"><?php esc_html_e( 'Maximum File Size (MB)', 'reel-it' ); ?></label>
                                        <?php $size = isset( $options['max_file_size'] ) ? $options['max_file_size'] : Reel_It::DEFAULT_MAX_FILE_SIZE; ?>
                                        <input type="number" id="max_file_size" name="reel_it_options[max_file_size]" value="<?php echo esc_attr( $size ); ?>" class="regular-text">
                                        <p class="description"><?php esc_html_e( 'Maximum file size for video uploads in MB', 'reel-it' ); ?></p>
                                    </div>

                                    <!-- Allowed Formats -->
                                    <div class="reel-it-form-row">
                                        <label><?php esc_html_e( 'Allowed Formats', 'reel-it' ); ?></label>
                                        <div class="reel-it-checkbox-group" style="margin-top:0.5rem;">
                                            <?php 
                                            $formats = array(
                                                'mp4' => 'MP4', 'webm' => 'WebM', 'ogg' => 'OGG', 'mov' => 'QuickTime', 'avi' => 'AVI'
                                            );
                                            $selected = isset( $options['allowed_formats'] ) ? $options['allowed_formats'] : array();
                                            foreach ( $formats as $key => $label ) {
                                                $checked = in_array( $key, $selected );
                                                echo '<label class="reel-it-checkbox-inline" style="display:inline-flex; align-items:center; margin-right:15px;">';
                                                echo '<input type="checkbox" name="reel_it_options[allowed_formats][]" value="' . esc_attr( $key ) . '" ' . checked( $checked, true, false ) . ' style="width:1.2rem!important; height:1.2rem!important; border-radius:4px!important; margin-right:5px;">';
                                                echo '<span>' . esc_html( $label ) . '</span>';
                                                echo '</label>';
                                            }
                                            ?>
                                        </div>
                                        <p class="description"><?php esc_html_e( 'Select which video formats are allowed for upload', 'reel-it' ); ?></p>
                                    </div>
                                </div>
                                
                                <div class="reel-it-form-actions">
                                    <?php submit_button( __( 'Save Upload Settings', 'reel-it' ), 'primary', 'submit', false ); ?>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Help Tab -->
                    <div class="reel-it-tab-pane" id="help">
                        <div class="reel-it-help-section">
                            <h3><?php esc_html_e( 'How to Use', 'reel-it' ); ?></h3>
                            <div class="reel-it-help-content">
                                <p><?php esc_html_e( 'Follow these steps to get started:', 'reel-it' ); ?></p>
                                <ol>
                                    <li><?php esc_html_e( 'Go to <strong>Galleries</strong> to create your first video collection.', 'reel-it' ); ?></li>
                                    <li><?php esc_html_e( 'Upload videos directly to your gallery.', 'reel-it' ); ?></li>
                                    <li><?php esc_html_e( 'Go to any Page or Post and insert the <strong>Video Slider</strong> block.', 'reel-it' ); ?></li>
                                    <li><?php esc_html_e( 'Select the gallery you created in the block settings side panel.', 'reel-it' ); ?></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_field( $args ) {
        $options = get_option( 'reel_it_options', array() );
        $value = isset( $options[$args['id']] ) ? $options[$args['id']] : '';
        
        switch ( $args['type'] ) {
            case 'checkbox':
                echo '<input type="checkbox" id="' . esc_attr( $args['id'] ) . '" name="reel_it_options[' . esc_attr( $args['id'] ) . ']" value="1" ' . checked( 1, $value, false ) . ' />';
                echo '<label for="' . esc_attr( $args['id'] ) . '"> ' . esc_html( $args['description'] ) . '</label>';
                break;
                
            case 'range':
                $min = isset( $args['min'] ) ? $args['min'] : 1000;
                $max = isset( $args['max'] ) ? $args['max'] : 10000;
                $step = isset( $args['step'] ) ? $args['step'] : 500;
                $value = !empty( $value ) ? $value : Reel_It::DEFAULT_SLIDER_SPEED;
                echo '<input type="range" id="' . esc_attr( $args['id'] ) . '" name="reel_it_options[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '" min="' . esc_attr( $min ) . '" max="' . esc_attr( $max ) . '" step="' . esc_attr( $step ) . '" class="reel-it-range-slider" />';
                echo '<span class="reel-it-range-value">' . esc_html( $value ) . 'ms</span>';
                break;
                
            case 'number':
                echo '<input type="number" id="' . esc_attr( $args['id'] ) . '" name="reel_it_options[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '" min="1" max="500" class="regular-text" />';
                break;
                
            case 'checkbox_group':
                echo '<div class="reel-it-checkbox-group">';
                $selected = is_array( $value ) ? $value : array();
                foreach ( $args['options'] as $option_value => $label ) {
                    $checked = in_array( $option_value, $selected );
                    echo '<label class="reel-it-checkbox-label">';
                    echo '<input type="checkbox" name="reel_it_options[' . esc_attr( $args['id'] ) . '][]" value="' . esc_attr( $option_value ) . '" ' . checked( $checked, true, false ) . ' />';
                    echo '<span class="reel-it-checkbox-text">' . esc_html( $label ) . '</span>';
                    echo '</label>';
                }
                echo '</div>';
                break;
        }
    }

    public function sanitize_settings( $input ) {
        $sanitized = array();
        $checkbox_fields = array( 'default_autoplay', 'default_show_controls', 'default_show_thumbnails' );
        foreach ( $checkbox_fields as $field ) { $sanitized[$field] = isset( $input[$field] ) ? 1 : 0; }
        if ( isset( $input['default_slider_speed'] ) ) { $sanitized['default_slider_speed'] = intval( $input['default_slider_speed'] ); }
        if ( isset( $input['border_radius'] ) ) { $sanitized['border_radius'] = intval( $input['border_radius'] ); }
        if ( isset( $input['video_gap'] ) ) { $sanitized['video_gap'] = intval( $input['video_gap'] ); }
        if ( isset( $input['max_file_size'] ) ) { $sanitized['max_file_size'] = intval( $input['max_file_size'] ); }
        if ( isset( $input['allowed_formats'] ) && is_array( $input['allowed_formats'] ) ) {
            $sanitized['allowed_formats'] = array_map( 'sanitize_text_field', $input['allowed_formats'] );
        }
        return $sanitized;
    }

    public function render_section_info() { echo '<p>' . esc_html__( 'Configure the default settings.', 'reel-it' ) . '</p>'; }
    public function render_feeds_section_info() { echo '<p>' . esc_html__( 'Manage your video feeds.', 'reel-it' ) . '</p>'; }

    public function render_upload_settings_section() {
        echo '<p>' . esc_html__( 'Configure upload limits and formats.', 'reel-it' ) . '</p>';
        do_settings_fields( 'reel-it-settings', 'reel_it_upload' );
    }

    // AJAX handlers...
    public function ajax_create_feed() {
        check_ajax_referer( 'reel_it_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => __( 'Permission denied', 'reel-it' ) ) );
        $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
        if ( empty( $name ) ) wp_send_json_error( array( 'message' => __( 'Name required', 'reel-it' ) ) );
        $database = $this->get_database();
        $feed_id = $database->create_feed( $name, $description );
        if ( $feed_id ) wp_send_json_success( array( 'message' => __( 'Feed created', 'reel-it' ), 'feed_id' => $feed_id ) );
        else wp_send_json_error( array( 'message' => __( 'Failed', 'reel-it' ) ) );
    }

    public function ajax_update_feed() {
        check_ajax_referer( 'reel_it_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => __( 'Permission denied', 'reel-it' ) ) );
        $feed_id = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;
        $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
        if ( empty( $name ) || $feed_id <= 0 ) wp_send_json_error( array( 'message' => __( 'Invalid data', 'reel-it' ) ) );
        $database = $this->get_database();
        if ( $database->update_feed( $feed_id, $name, $description ) ) wp_send_json_success( array( 'message' => __( 'Updated', 'reel-it' ) ) );
        else wp_send_json_error( array( 'message' => __( 'Failed', 'reel-it' ) ) );
    }

    public function ajax_delete_feed() {
        check_ajax_referer( 'reel_it_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => __( 'Permission denied', 'reel-it' ) ) );
        $feed_id = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;
        if ( $feed_id <= 0 ) wp_send_json_error( array( 'message' => __( 'Invalid ID', 'reel-it' ) ) );
        $database = $this->get_database();
        if ( $database->delete_feed( $feed_id ) ) wp_send_json_success( array( 'message' => __( 'Deleted', 'reel-it' ) ) );
        else wp_send_json_error( array( 'message' => __( 'Failed', 'reel-it' ) ) );
    }

    public function ajax_get_feed_videos() {
        check_ajax_referer( 'reel_it_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => __( 'Permission denied', 'reel-it' ) ) );
        $feed_id = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;
        $database = $this->get_database();
        $videos = $database->get_feed_videos( $feed_id );
        
        // Add thumbnail URL for each video (convert to arrays for JSON response)
        $videos_array = array();
        foreach ( $videos as $video ) {
            $video_data = (array) $video;
            $video_data['thumbnail'] = wp_get_attachment_image_url( $video->video_id, 'thumbnail' );
            $videos_array[] = $video_data;
        }
        
        wp_send_json_success( array( 'videos' => $videos_array ) );
    }

    public function ajax_add_video_to_feed() {
        check_ajax_referer( 'reel_it_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => __( 'Permission denied', 'reel-it' ) ) );
        $feed_id = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;
        $video_id = isset( $_POST['video_id'] ) ? intval( $_POST['video_id'] ) : 0;
        $database = $this->get_database();
        if ( $database->add_video_to_feed( $feed_id, $video_id ) ) {
            delete_transient( 'reel_it_feeds_data' );
            wp_send_json_success( array( 'message' => __( 'Added', 'reel-it' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed', 'reel-it' ) ) );
        }
    }

    public function ajax_remove_video_from_feed() {
        check_ajax_referer( 'reel_it_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => __( 'Permission denied', 'reel-it' ) ) );
        $feed_id = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;
        $video_id = isset( $_POST['video_id'] ) ? intval( $_POST['video_id'] ) : 0;
        $database = $this->get_database();
        if ( $database->remove_video_from_feed( $feed_id, $video_id ) ) {
            delete_transient( 'reel_it_feeds_data' );
            wp_send_json_success( array( 'message' => __( 'Removed', 'reel-it' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed', 'reel-it' ) ) );
        }
    }

    public function ajax_update_video_order() {
        check_ajax_referer( 'reel_it_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => __( 'Permission denied', 'reel-it' ) ) );
        $feed_id = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;
        // Sanitize array of arrays
        $orders_raw = isset( $_POST['video_orders'] ) ? wp_unslash( $_POST['video_orders'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $orders = array();
        if ( is_array( $orders_raw ) ) {
            foreach ( $orders_raw as $order_item ) {
                if ( is_array( $order_item ) ) {
                    $orders[] = array_map( 'sanitize_text_field', $order_item );
                }
            }
        }
        $database = $this->get_database();
        foreach ( $orders as $o ) {
            if ( isset( $o['video_id'] ) && isset( $o['sort_order'] ) ) {
                $database->update_video_sort_order( $feed_id, intval( $o['video_id'] ), intval( $o['sort_order'] ) );
            }
        }
        delete_transient( 'reel_it_feeds_data' );
        wp_send_json_success( array( 'message' => __( 'Updated', 'reel-it' ) ) );
    }

    public function ajax_search_videos() {
        check_ajax_referer( 'reel_it_nonce', 'nonce' );
        if ( ! current_user_can( 'upload_files' ) ) wp_send_json_error( array( 'message' => __( 'Permission denied', 'reel-it' ) ) );
        $search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
        $per_page = isset( $_POST['per_page'] ) ? intval( $_POST['per_page'] ) : Reel_It::DEFAULT_PAGINATION;
        $database = $this->get_database();
        wp_send_json_success( $database->get_available_videos( $search, $page, $per_page ) );
    }

    public function ajax_get_feeds() {
        check_ajax_referer( 'reel_it_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => __( 'Permission denied', 'reel-it' ) ) );
        $database = $this->get_database();
        wp_send_json_success( array( 'feeds' => $database->get_feeds() ) );
    }

    public function ajax_get_feed_thumbnail() {
        check_ajax_referer( 'reel_it_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => __( 'Permission denied', 'reel-it' ) ) );
        $feed_id = isset( $_POST['feed_id'] ) ? intval( $_POST['feed_id'] ) : 0;
        $database = $this->get_database();
        $data = $database->get_feed_thumbnail_data( $feed_id );
        if ( $data['success'] ) wp_send_json_success( $data );
        else wp_send_json_error( $data );
    }

    // -- Product Tagging AJAX Handlers --

    public function ajax_search_products() {
        check_ajax_referer( 'reel_it_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => __( 'Permission denied', 'reel-it' ) ) );
        
        if ( ! Reel_It::is_shop_active() ) {
            wp_send_json_error( array( 'message' => __( 'WooCommerce is not active.', 'reel-it' ) ) );
        }

        $term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
        
        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => Reel_It::DEFAULT_PAGINATION,
            's'              => $term,
            'fields'         => 'ids' // just get IDs to then fetch product objects
        );

        $query = new WP_Query( $args );
        $products = array();

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $product_id ) {
                $product = wc_get_product( $product_id );
                if ( $product ) {
                    $image_id = $product->get_image_id();
                    $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src();
                    
                    $products[] = array(
                        'id' => $product->get_id(),
                        'text' => $product->get_name() . ' (' . wp_strip_all_tags( wc_price( $product->get_price() ) ) . ')',
                        'price' => $product->get_price_html(),
                        'image' => $image_url
                    );
                }
            }
        }

        wp_send_json_success( array( 'results' => $products ) );
    }

    public function ajax_get_video_products() {
        check_ajax_referer( 'reel_it_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => __( 'Permission denied', 'reel-it' ) ) );
        
        if ( ! Reel_It::is_shop_active() ) {
            wp_send_json_error( array( 'message' => __( 'WooCommerce is not active.', 'reel-it' ) ) );
        }

        $video_id = isset( $_POST['video_id'] ) ? intval( $_POST['video_id'] ) : 0;
        if ( ! $video_id ) wp_send_json_error( array( 'message' => __( 'Invalid Video ID', 'reel-it' ) ) );

        $product_ids = get_post_meta( $video_id, '_reel_it_linked_products', true );
        if ( ! is_array( $product_ids ) ) $product_ids = array();

        $products = array();
        foreach ( $product_ids as $pid ) {
            $product = wc_get_product( $pid );
            if ( $product ) {
                $image_id = $product->get_image_id();
                $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src();

                $products[] = array(
                    'id' => $product->get_id(),
                    'text' => $product->get_name(),
                    'price' => $product->get_price_html(),
                    'image' => $image_url,
                    'permalink' => $product->get_permalink()
                );
            }
        }

        wp_send_json_success( array( 'products' => $products ) );
    }

    public function ajax_save_video_products() {
        check_ajax_referer( 'reel_it_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( array( 'message' => __( 'Permission denied', 'reel-it' ) ) );
        
        $video_id = isset( $_POST['video_id'] ) ? intval( $_POST['video_id'] ) : 0;
        $product_ids = isset( $_POST['products'] ) ? array_map( 'intval', $_POST['products'] ) : array();

        if ( ! $video_id ) wp_send_json_error( array( 'message' => __( 'Invalid Video ID', 'reel-it' ) ) );

        update_post_meta( $video_id, '_reel_it_linked_products', $product_ids );
        
        wp_send_json_success( array( 'message' => __( 'Products Saved', 'reel-it' ) ) );
    }

    /**
     * Render the Analytics dashboard page.
     *
     * @since 1.4.0
     */
    public function render_analytics_page() {
        $days = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 30;
        $analytics = new Reel_It_Analytics();
        $stats = $analytics->get_summary_stats( $days );
        $top_videos = $analytics->get_top_videos( $days, 10 );
        ?>
        <div class="wrap reel-it-wrap reel-it-analytics-wrap">
            <div class="reel-it-header">
                <h1><?php esc_html_e( 'Video Analytics', 'reel-it' ); ?></h1>
                <div class="reel-it-date-filter">
                    <a href="<?php echo esc_url( add_query_arg( 'days', 7 ) ); ?>" class="button <?php echo $days === 7 ? 'button-primary' : ''; ?>"><?php esc_html_e( '7 Days', 'reel-it' ); ?></a>
                    <a href="<?php echo esc_url( add_query_arg( 'days', 30 ) ); ?>" class="button <?php echo $days === 30 ? 'button-primary' : ''; ?>"><?php esc_html_e( '30 Days', 'reel-it' ); ?></a>
                    <a href="<?php echo esc_url( add_query_arg( 'days', 90 ) ); ?>" class="button <?php echo $days === 90 ? 'button-primary' : ''; ?>"><?php esc_html_e( '90 Days', 'reel-it' ); ?></a>
                </div>
            </div>

            <div class="reel-it-stats-grid">
                <div class="reel-it-stat-card">
                    <span class="reel-it-stat-icon dashicons dashicons-visibility"></span>
                    <div class="reel-it-stat-content">
                        <span class="reel-it-stat-value"><?php echo esc_html( number_format( $stats['total_plays'] ) ); ?></span>
                        <span class="reel-it-stat-label"><?php esc_html_e( 'Total Plays', 'reel-it' ); ?></span>
                    </div>
                </div>
                <div class="reel-it-stat-card">
                    <span class="reel-it-stat-icon dashicons dashicons-yes-alt"></span>
                    <div class="reel-it-stat-content">
                        <span class="reel-it-stat-value"><?php echo esc_html( number_format( $stats['total_completions'] ) ); ?></span>
                        <span class="reel-it-stat-label"><?php esc_html_e( 'Completions', 'reel-it' ); ?></span>
                    </div>
                </div>
                <div class="reel-it-stat-card">
                    <span class="reel-it-stat-icon dashicons dashicons-chart-line"></span>
                    <div class="reel-it-stat-content">
                        <span class="reel-it-stat-value"><?php echo esc_html( $stats['completion_rate'] ); ?>%</span>
                        <span class="reel-it-stat-label"><?php esc_html_e( 'Completion Rate', 'reel-it' ); ?></span>
                    </div>
                </div>
                <div class="reel-it-stat-card">
                    <span class="reel-it-stat-icon dashicons dashicons-cart"></span>
                    <div class="reel-it-stat-content">
                        <span class="reel-it-stat-value"><?php echo esc_html( number_format( $stats['total_clicks'] ) ); ?></span>
                        <span class="reel-it-stat-label"><?php esc_html_e( 'Product Clicks', 'reel-it' ); ?></span>
                    </div>
                </div>
            </div>

            <div class="reel-it-card">
                <h2><?php esc_html_e( 'Top Performing Videos', 'reel-it' ); ?></h2>
                <?php if ( empty( $top_videos ) ) : ?>
                    <p class="reel-it-no-data"><?php esc_html_e( 'No analytics data yet. Views will appear once visitors start watching your videos.', 'reel-it' ); ?></p>
                <?php else : ?>
                    <table class="widefat striped reel-it-analytics-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Video', 'reel-it' ); ?></th>
                                <th><?php esc_html_e( 'Plays', 'reel-it' ); ?></th>
                                <th><?php esc_html_e( 'Completions', 'reel-it' ); ?></th>
                                <th><?php esc_html_e( 'Completion Rate', 'reel-it' ); ?></th>
                                <th><?php esc_html_e( 'Product Clicks', 'reel-it' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $top_videos as $video ) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $video['title'] ); ?></strong></td>
                                    <td><?php echo esc_html( number_format( $video['plays'] ) ); ?></td>
                                    <td><?php echo esc_html( number_format( $video['completions'] ) ); ?></td>
                                    <td><?php echo esc_html( $video['completion_rate'] ); ?>%</td>
                                    <td><?php echo esc_html( number_format( $video['clicks'] ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for fetching analytics data.
     *
     * @since 1.4.0
     */
    public function ajax_get_analytics() {
        check_ajax_referer( 'reel_it_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'reel-it' ) ) );
        }

        $days = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 30;
        $analytics = new Reel_It_Analytics();

        wp_send_json_success( array(
            'stats'      => $analytics->get_summary_stats( $days ),
            'top_videos' => $analytics->get_top_videos( $days ),
            'daily'      => $analytics->get_daily_stats( $days ),
        ) );
    }
}