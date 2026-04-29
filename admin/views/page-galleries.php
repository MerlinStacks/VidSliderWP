<?php
/**
 * Template: Galleries admin page.
 *
 * Why: extracted from Reel_It_Settings::render_galleries_page() to keep the
 * class file under the 200-line limit. This file is included via require and
 * receives $feeds from the calling method.
 *
 * @package Reel_It
 * @since   1.5.1
 * @var     array $feeds Feed objects with thumbnail data.
 */

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<div class="wrap reel-it-settings-wrap reel-it-galleries-page">
    <div class="reel-it-header">
        <h1><?php echo esc_html( __( 'Galleries', 'reel-it' ) ); ?></h1>
        <div class="reel-it-header-description">
            <p><?php esc_html_e( 'Organize your videos into reusable collections and galleries', 'reel-it' ); ?></p>
        </div>
    </div>

    <div class="reel-it-feeds-section">
        <div class="reel-it-feeds-container">
            <div class="reel-it-feeds-list">
                <?php
                if ( ! empty( $feeds ) ) {
                    foreach ( $feeds as $feed ) {
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
                        <button type="button" class="button button-secondary reel-it-hidden" id="reel-it-cancel-edit"><?php esc_html_e( 'Cancel', 'reel-it' ); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Video Manager Modal -->
    <div id="reel-it-video-modal" class="reel-it-modal reel-it-hidden">
        <div class="reel-it-modal-overlay"></div>
        <div class="reel-it-modal-content">
            <div class="reel-it-modal-header">
                <h2 id="reel-it-modal-title"><?php esc_html_e( 'Manage Gallery Videos', 'reel-it' ); ?></h2>
                <button type="button" class="reel-it-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
            </div>
            <div class="reel-it-modal-body">
                <div class="reel-it-video-toolbar">
                    <button type="button" class="button button-primary" id="reel-it-add-videos">
                        <span class="dashicons dashicons-plus-alt2 reel-it-icon-inline"></span> <?php esc_html_e( 'Add Videos', 'reel-it' ); ?>
                    </button>
                    <span class="spinner reel-it-toolbar-spinner"></span>
                </div>

                <div id="reel-it-video-list-container" class="reel-it-video-grid">
                    <!-- Videos will be loaded here via AJAX -->
                </div>

                <div id="reel-it-no-videos-message" class="reel-it-empty-state">
                    <span class="dashicons dashicons-video-alt3 reel-it-empty-state-icon"></span>
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
    <div id="reel-it-product-modal" class="reel-it-modal reel-it-hidden reel-it-product-modal">
        <div class="reel-it-modal-overlay"></div>
        <div class="reel-it-modal-content">
            <div class="reel-it-modal-header">
                <h2><?php esc_html_e( 'Tag Products', 'reel-it' ); ?></h2>
                <button type="button" class="reel-it-product-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
            </div>
            <div class="reel-it-modal-body">
                <div class="reel-it-product-search-container">
                    <input type="text" id="reel-it-product-search" class="large-text" placeholder="<?php esc_attr_e( 'Search for products...', 'reel-it' ); ?>" autocomplete="off">
                    <span class="spinner reel-it-product-spinner" id="reel-it-product-spinner"></span>
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
