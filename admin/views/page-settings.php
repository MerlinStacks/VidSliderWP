<?php
/**
 * Template: Settings admin page.
 *
 * Why: extracted from Reel_It_Settings::render_settings_page() to keep the
 * class file under the 200-line limit.
 *
 * @package Reel_It
 * @since   1.5.1
 */

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) ) {
    die;
}
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
            <!-- Single form wrapping all tab panes prevents settings overwriting each other -->
            <form action="options.php" method="post" class="reel-it-settings-form">
                <?php
                settings_fields( 'reel_it_settings' );
                $options = get_option( 'reel_it_options', array() );
                ?>

            <!-- General Tab -->
            <div class="reel-it-tab-pane active" id="general">
                    <div class="reel-it-form-section">
                        <p class="reel-it-section-desc"><?php esc_html_e( 'Configure the default settings.', 'reel-it' ); ?></p>

                        <!-- Autoplay -->
                        <div class="reel-it-form-row">
                            <div class="reel-it-toggle-row">
                                <?php $autoplay = isset( $options['default_autoplay'] ) ? $options['default_autoplay'] : 0; ?>
                                <input type="checkbox" id="default_autoplay" name="reel_it_options[default_autoplay]" value="1" <?php checked( 1, $autoplay ); ?>>
                                <label for="default_autoplay" class="reel-it-toggle-label"><?php esc_html_e( 'Default Autoplay', 'reel-it' ); ?></label>
                            </div>
                            <p class="description"><?php esc_html_e( 'Enable autoplay by default for new video sliders', 'reel-it' ); ?></p>
                        </div>

                        <!-- Show Controls -->
                        <div class="reel-it-form-row">
                            <div class="reel-it-toggle-row">
                                <?php $controls = isset( $options['default_show_controls'] ) ? $options['default_show_controls'] : 0; ?>
                                <input type="checkbox" id="default_show_controls" name="reel_it_options[default_show_controls]" value="1" <?php checked( 1, $controls ); ?>>
                                <label for="default_show_controls" class="reel-it-toggle-label"><?php esc_html_e( 'Show Controls', 'reel-it' ); ?></label>
                            </div>
                            <p class="description"><?php esc_html_e( 'Show video controls by default', 'reel-it' ); ?></p>
                        </div>

                        <!-- Show Thumbnails -->
                        <div class="reel-it-form-row">
                            <div class="reel-it-toggle-row">
                                <?php $thumbs = isset( $options['default_show_thumbnails'] ) ? $options['default_show_thumbnails'] : 0; ?>
                                <input type="checkbox" id="default_show_thumbnails" name="reel_it_options[default_show_thumbnails]" value="1" <?php checked( 1, $thumbs ); ?>>
                                <label for="default_show_thumbnails" class="reel-it-toggle-label"><?php esc_html_e( 'Show Thumbnails', 'reel-it' ); ?></label>
                            </div>
                            <p class="description"><?php esc_html_e( 'Show thumbnail navigation by default', 'reel-it' ); ?></p>
                        </div>

                        <!-- Slider Speed -->
                        <div class="reel-it-form-row">
                            <label for="default_slider_speed"><?php esc_html_e( 'Default Slider Speed', 'reel-it' ); ?></label>
                            <?php $speed = isset( $options['default_slider_speed'] ) ? $options['default_slider_speed'] : Reel_It::DEFAULT_SLIDER_SPEED; ?>
                            <input type="range" id="default_slider_speed" name="reel_it_options[default_slider_speed]" value="<?php echo esc_attr( $speed ); ?>" min="1000" max="10000" step="500" class="reel-it-range-slider">
                            <span class="reel-it-range-value"><?php echo esc_html( $speed ); ?>ms</span>
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

                        <!-- Default Videos Per Row -->
                        <div class="reel-it-form-row">
                            <label for="default_videos_per_row"><?php esc_html_e( 'Default Videos Per Row', 'reel-it' ); ?></label>
                            <?php $vpr = isset( $options['default_videos_per_row'] ) ? $options['default_videos_per_row'] : 3; ?>
                            <input type="number" id="default_videos_per_row" name="reel_it_options[default_videos_per_row]" value="<?php echo esc_attr( $vpr ); ?>" min="1" max="6" step="1" class="regular-text">
                            <p class="description"><?php esc_html_e( 'Number of videos visible per row on desktop (default: 3)', 'reel-it' ); ?></p>
                        </div>
                    </div>
            </div>

            <!-- Upload Tab -->
            <div class="reel-it-tab-pane" id="upload">
                <div class="reel-it-upload-section">
                    <h3><?php esc_html_e( 'Upload Settings', 'reel-it' ); ?></h3>
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
                                <div class="reel-it-checkbox-group reel-it-checkbox-group-spaced">
                                    <?php
                                    $formats = array(
                                        'mp4' => 'MP4', 'webm' => 'WebM', 'ogg' => 'OGG', 'mov' => 'QuickTime', 'avi' => 'AVI'
                                    );
                                    $selected = isset( $options['allowed_formats'] ) ? $options['allowed_formats'] : array();
                                    foreach ( $formats as $key => $label ) {
                                        $checked = in_array( $key, $selected );
                                        echo '<label class="reel-it-checkbox-inline">';
                                        echo '<input type="checkbox" name="reel_it_options[allowed_formats][]" value="' . esc_attr( $key ) . '" ' . checked( $checked, true, false ) . '>';
                                        echo '<span>' . esc_html( $label ) . '</span>';
                                        echo '</label>';
                                    }
                                    ?>
                                </div>
                                <p class="description"><?php esc_html_e( 'Select which video formats are allowed for upload', 'reel-it' ); ?></p>
                            </div>
                        </div>
                </div>
            </div>

            <div class="reel-it-form-actions">
                <?php submit_button( __( 'Save Settings', 'reel-it' ), 'primary', 'submit', false ); ?>
            </div>
            </form>

            <!-- Help Tab -->
            <div class="reel-it-tab-pane" id="help">
                <div class="reel-it-help-section">
                    <h3><?php esc_html_e( 'How to Use', 'reel-it' ); ?></h3>
                    <div class="reel-it-help-content">
                        <p><?php esc_html_e( 'Follow these steps to get started:', 'reel-it' ); ?></p>
                        <ol>
                            <li><?php
                                printf(
                                    /* translators: %s: bold "Galleries" label */
                                    esc_html__( 'Go to %s to create your first video collection.', 'reel-it' ),
                                    '<strong>' . esc_html__( 'Galleries', 'reel-it' ) . '</strong>'
                                );
                            ?></li>
                            <li><?php esc_html_e( 'Upload videos directly to your gallery.', 'reel-it' ); ?></li>
                            <li><?php
                                printf(
                                    /* translators: %s: bold "Video Slider" label */
                                    esc_html__( 'Go to any Page or Post and insert the %s block.', 'reel-it' ),
                                    '<strong>' . esc_html__( 'Video Slider', 'reel-it' ) . '</strong>'
                                );
                            ?></li>
                            <li><?php esc_html_e( 'Select the gallery you created in the block settings side panel.', 'reel-it' ); ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
