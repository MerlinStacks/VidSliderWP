<?php
/**
 * Settings page functionality for the plugin.
 *
 * @since      1.0.0
 * @package    Wp_Vids_Reel
 * @subpackage Wp_Vids_Reel/admin
 */

/**
 * Settings page functionality.
 *
 * @since      1.0.0
 * @package    Wp_Vids_Reel
 * @subpackage Wp_Vids_Reel/admin
 */
class Wp_Vids_Reel_Settings {

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
     * Add settings page to admin menu
     *
     * @since    1.0.0
     */
    public function add_settings_page() {
        add_options_page(
            __( 'WP Vids Reel Settings', 'wp-vids-reel' ),
            __( 'Video Slider', 'wp-vids-reel' ),
            'manage_options',
            'wp-vids-reel-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting(
            'wp_vids_reel_settings',
            'wp_vids_reel_options',
            array( $this, 'sanitize_settings' )
        );

        // General Settings
        add_settings_section(
            'wp_vids_reel_general',
            __( 'General Settings', 'wp-vids-reel' ),
            array( $this, 'render_section_info' ),
            'wp-vids-reel-settings'
        );

        add_settings_field(
            'default_autoplay',
            __( 'Default Autoplay', 'wp-vids-reel' ),
            array( $this, 'render_checkbox_field' ),
            'wp-vids-reel-settings',
            'wp_vids_reel_general',
            array(
                'id' => 'default_autoplay',
                'description' => __( 'Enable autoplay by default for new video sliders', 'wp-vids-reel' )
            )
        );

        add_settings_field(
            'default_show_controls',
            __( 'Default Show Controls', 'wp-vids-reel' ),
            array( $this, 'render_checkbox_field' ),
            'wp-vids-reel-settings',
            'wp_vids_reel_general',
            array(
                'id' => 'default_show_controls',
                'description' => __( 'Show video controls by default', 'wp-vids-reel' )
            )
        );

        add_settings_field(
            'default_show_thumbnails',
            __( 'Default Show Thumbnails', 'wp-vids-reel' ),
            array( $this, 'render_checkbox_field' ),
            'wp-vids-reel-settings',
            'wp_vids_reel_general',
            array(
                'id' => 'default_show_thumbnails',
                'description' => __( 'Show thumbnail navigation by default', 'wp-vids-reel' )
            )
        );

        add_settings_field(
            'default_slider_speed',
            __( 'Default Slider Speed', 'wp-vids-reel' ),
            array( $this, 'render_range_field' ),
            'wp-vids-reel-settings',
            'wp_vids_reel_general',
            array(
                'id' => 'default_slider_speed',
                'min' => 1000,
                'max' => 10000,
                'step' => 500,
                'description' => __( 'Default time between slides in milliseconds', 'wp-vids-reel' )
            )
        );

        // Upload Settings
        add_settings_section(
            'wp_vids_reel_upload',
            __( 'Upload Settings', 'wp-vids-reel' ),
            array( $this, 'render_section_info' ),
            'wp-vids-reel-settings'
        );

        add_settings_field(
            'max_file_size',
            __( 'Maximum File Size', 'wp-vids-reel' ),
            array( $this, 'render_text_field' ),
            'wp-vids-reel-settings',
            'wp_vids_reel_upload',
            array(
                'id' => 'max_file_size',
                'type' => 'number',
                'description' => __( 'Maximum file size for video uploads in MB (default: 50)', 'wp-vids-reel' )
            )
        );

        add_settings_field(
            'allowed_formats',
            __( 'Allowed Formats', 'wp-vids-reel' ),
            array( $this, 'render_checkbox_group_field' ),
            'wp-vids-reel-settings',
            'wp_vids_reel_upload',
            array(
                'id' => 'allowed_formats',
                'options' => array(
                    'mp4' => 'MP4',
                    'webm' => 'WebM',
                    'ogg' => 'OGG',
                    'mov' => 'QuickTime',
                    'avi' => 'AVI'
                ),
                'description' => __( 'Select which video formats are allowed for upload', 'wp-vids-reel' )
            )
        );
    }

    /**
     * Sanitize settings
     *
     * @since    1.0.0
     * @param    array    $input    Input data to sanitize.
     * @return   array              Sanitized data.
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();

        // Sanitize checkboxes
        $checkbox_fields = array( 'default_autoplay', 'default_show_controls', 'default_show_thumbnails' );
        foreach ( $checkbox_fields as $field ) {
            $sanitized[$field] = isset( $input[$field] ) ? 1 : 0;
        }

        // Sanitize number fields
        if ( isset( $input['default_slider_speed'] ) ) {
            $sanitized['default_slider_speed'] = intval( $input['default_slider_speed'] );
        }

        if ( isset( $input['max_file_size'] ) ) {
            $sanitized['max_file_size'] = intval( $input['max_file_size'] );
        }

        // Sanitize allowed formats
        if ( isset( $input['allowed_formats'] ) && is_array( $input['allowed_formats'] ) ) {
            $sanitized['allowed_formats'] = array_map( 'sanitize_text_field', $input['allowed_formats'] );
        }

        return $sanitized;
    }

    /**
     * Render settings page
     *
     * @since    1.0.0
     */
    public function render_settings_page() {
        ?>
        <div class="wrap wp-vids-reel-settings-wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <form action="options.php" method="post">
                <?php
                settings_fields( 'wp_vids_reel_settings' );
                do_settings_sections( 'wp-vids-reel-settings' );
                submit_button( __( 'Save Settings', 'wp-vids-reel' ) );
                ?>
            </form>

            <div class="wp-vids-reel-help-section">
                <h3><?php _e( 'How to Use', 'wp-vids-reel' ); ?></h3>
                <ol>
                    <li><?php _e( 'Edit any post or page where you want to add a video slider', 'wp-vids-reel' ); ?></li>
                    <li><?php _e( 'Click the + icon to add a new block', 'wp-vids-reel' ); ?></li>
                    <li><?php _e( 'Search for "Video Slider" and select it', 'wp-vids-reel' ); ?></li>
                    <li><?php _e( 'Upload videos or select from your media library', 'wp-vids-reel' ); ?></li>
                    <li><?php _e( 'Configure the slider settings in the block inspector', 'wp-vids-reel' ); ?></li>
                    <li><?php _e( 'Publish your page to see the video slider in action', 'wp-vids-reel' ); ?></li>
                </ol>
            </div>

            <div class="wp-vids-reel-help-section">
                <h3><?php _e( 'Supported Video Formats', 'wp-vids-reel' ); ?></h3>
                <ul>
                    <li><strong>MP4:</strong> <?php _e( 'Most widely supported format', 'wp-vids-reel' ); ?></li>
                    <li><strong>WebM:</strong> <?php _e( 'Open-source format, good compression', 'wp-vids-reel' ); ?></li>
                    <li><strong>OGG:</strong> <?php _e( 'Open-source format', 'wp-vids-reel' ); ?></li>
                    <li><strong>QuickTime:</strong> <?php _e( 'Apple format', 'wp-vids-reel' ); ?></li>
                    <li><strong>AVI:</strong> <?php _e( 'Legacy format', 'wp-vids-reel' ); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Render section info
     *
     * @since    1.0.0
     */
    public function render_section_info() {
        echo '<p>' . __( 'Configure the default settings for your video sliders.', 'wp-vids-reel' ) . '</p>';
    }

    /**
     * Render checkbox field
     *
     * @since    1.0.0
     * @param    array    $args    Field arguments.
     */
    public function render_checkbox_field( $args ) {
        $options = get_option( 'wp_vids_reel_options', array() );
        $value = isset( $options[$args['id']] ) ? $options[$args['id']] : 0;
        
        echo '<input type="checkbox" id="' . esc_attr( $args['id'] ) . '" name="wp_vids_reel_options[' . esc_attr( $args['id'] ) . ']" value="1" ' . checked( 1, $value, false ) . ' />';
        echo '<label for="' . esc_attr( $args['id'] ) . '"> ' . esc_html( $args['description'] ) . '</label>';
    }

    /**
     * Render range field
     *
     * @since    1.0.0
     * @param    array    $args    Field arguments.
     */
    public function render_range_field( $args ) {
        $options = get_option( 'wp_vids_reel_options', array() );
        $value = isset( $options[$args['id']] ) ? $options[$args['id']] : 5000;
        
        echo '<input type="range" id="' . esc_attr( $args['id'] ) . '" name="wp_vids_reel_options[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '" min="' . esc_attr( $args['min'] ) . '" max="' . esc_attr( $args['max'] ) . '" step="' . esc_attr( $args['step'] ) . '" class="wp-vids-reel-range-slider" />';
        echo '<span class="wp-vids-reel-range-value">' . esc_html( $value ) . 'ms</span>';
        echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
    }

    /**
     * Render text field
     *
     * @since    1.0.0
     * @param    array    $args    Field arguments.
     */
    public function render_text_field( $args ) {
        $options = get_option( 'wp_vids_reel_options', array() );
        $value = isset( $options[$args['id']] ) ? $options[$args['id']] : '';
        $type = isset( $args['type'] ) ? $args['type'] : 'text';
        
        echo '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $args['id'] ) . '" name="wp_vids_reel_options[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
    }

    /**
     * Render checkbox group field
     *
     * @since    1.0.0
     * @param    array    $args    Field arguments.
     */
    public function render_checkbox_group_field( $args ) {
        $options = get_option( 'wp_vids_reel_options', array() );
        $selected = isset( $options[$args['id']] ) ? $options[$args['id']] : array();
        
        foreach ( $args['options'] as $value => $label ) {
            $checked = in_array( $value, $selected ) ? 'checked="checked"' : '';
            echo '<label><input type="checkbox" name="wp_vids_reel_options[' . esc_attr( $args['id'] ) . '][]" value="' . esc_attr( $value ) . '" ' . $checked . ' /> ' . esc_html( $label ) . '</label><br>';
        }
        
        echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
    }
}