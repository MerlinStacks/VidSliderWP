<?php
/**
 * Slider HTML template.
 *
 * Why: extracted from render_video_slider() so the 300-line God method is
 * decomposed into data logic (in the class) and presentation (here).
 * Variables are injected via the calling method's scope.
 *
 * @var string $unique_id       Unique DOM id for this slider instance.
 * @var int    $border_radius   Border radius in px.
 * @var int    $video_gap       Gap between videos in px.
 * @var array  $atts            Processed shortcode/block attributes.
 * @var array  $videos          Filtered video array (each: id, title, url, mime).
 * @var int    $total_slides    Count of $videos.
 * @var object $public_instance Reference to Reel_It_Public (for resolve_poster_url).
 *
 * @since 1.6.0
 * @package Reel_It
 */

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<div class="reel-it-container vsfw-videos-container loading" id="<?php echo esc_attr( $unique_id ); ?>" role="region" aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Video gallery', 'reel-it' ); ?>" tabindex="0" style="--reel-it-border-radius: <?php echo esc_attr( $border_radius ); ?>px; --reel-it-video-gap: <?php echo esc_attr( $video_gap ); ?>px; --reel-it-columns-desktop: <?php echo floatval( $atts['videos_per_row'] ); ?>; --reel-it-columns-mobile: <?php echo floatval( $atts['videos_per_row_mobile'] ); ?>;" data-slider-speed="<?php echo intval( $atts['slider_speed'] ); ?>" data-videos-per-row="<?php echo floatval( $atts['videos_per_row'] ); ?>" data-videos-per-row-mobile="<?php echo floatval( $atts['videos_per_row_mobile'] ); ?>" data-feed-id="<?php echo intval( $atts['feed_id'] ); ?>" <?php if ( ! empty( $atts['autoplay'] ) ) echo 'data-autoplay="1"'; ?>>
    <div class="reel-it-loader">
        <div class="reel-it-spinner"></div>
    </div>
    <div class="reel-it-slider vsfw-videos-list" aria-live="polite">
        <?php foreach ( $videos as $index => $video ) :
            $video_url   = esc_url_raw( $video['url'] );
            $video_title = isset( $video['title'] ) ? sanitize_text_field( $video['title'] ) : '';
            $video_id    = isset( $video['id'] ) ? intval( $video['id'] ) : 0;

            // Resolve poster at 'large' size via shared helper.
            $poster = $public_instance->resolve_poster_url( $video_id, 'large' );
        ?>
            <div class="reel-it-slide vsfw-video-card <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo intval( $index ); ?>" role="group" aria-roledescription="slide" aria-label="<?php printf( esc_attr__( 'Slide %1$d of %2$d', 'reel-it' ), $index + 1, $total_slides ); ?>">
                <div class="reel-it-video-container <?php echo ! empty( $poster['url'] ) ? 'has-poster' : ''; ?>" data-video-src="<?php echo esc_url( $video_url ); ?>" data-video-id="<?php echo esc_attr( $video_id ); ?>">
                    <?php if ( ! empty( $poster['url'] ) ) : ?>
                        <img
                            class="reel-it-poster"
                            src="<?php echo esc_url( $poster['url'] ); ?>"
                            <?php if ( ! empty( $poster['srcset'] ) ) echo 'srcset="' . esc_attr( $poster['srcset'] ) . '"'; ?>
                            <?php if ( ! empty( $poster['sizes'] ) ) echo 'sizes="' . esc_attr( $poster['sizes'] ) . '"'; ?>
                            alt="<?php echo esc_attr( $video_title ? $video_title : __( 'Video thumbnail', 'reel-it' ) ); ?>"
                            decoding="async"
                            <?php echo $index === 0 ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"'; ?>
                            width="360" height="640"
                        >
                    <?php else : ?>
                        <img
                            class="reel-it-poster"
                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                            data-needs-poster="true"
                            alt="<?php echo esc_attr( $video_title ? $video_title : __( 'Video thumbnail', 'reel-it' ) ); ?>"
                            decoding="async"
                            loading="lazy"
                            width="360" height="640"
                        >
                    <?php endif; ?>
                    <noscript>
                        <video
                            class="reel-it-video"
                            src="<?php echo esc_url( $video_url ); ?>"
                            <?php if ( ! empty( $poster['url'] ) ) echo 'poster="' . esc_url( $poster['url'] ) . '"'; ?>
                            controls playsinline preload="metadata"
                            aria-label="<?php echo esc_attr( $video_title ? $video_title : __( 'Video', 'reel-it' ) ); ?>"
                        >
                            <?php esc_html_e( 'Your browser does not support the video tag.', 'reel-it' ); ?>
                        </video>
                    </noscript>
                    <div class="reel-it-video-overlay">
                        <button class="reel-it-play-button" type="button" aria-label="<?php esc_attr_e( 'Play video', 'reel-it' ); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M4 3.532c0-1.554 1.696-2.514 3.029-1.715l14.113 8.468c1.294.777 1.294 2.653 0 3.43L7.029 22.183c-1.333.8-3.029-.16-3.029-1.715V3.532Z" fill="#FFFFFF"></path></svg>
                        </button>
                    </div>
                    <?php /* Why: JS (_bindVideoEvents + mute toggle) expects this container.
                             It's hidden until a video starts playing via .visible class. */ ?>
                    <div class="reel-it-controls-container">
                        <button class="reel-it-control-button reel-it-mute-btn" type="button" aria-label="<?php esc_attr_e( 'Toggle mute', 'reel-it' ); ?>">
                            <svg class="reel-it-icon-muted" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>
                            <svg class="reel-it-icon-unmuted" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                        </button>
                    </div>

                    <?php
                    // Fetch the first tagged product for the overlay card.
                    $linked_product = null;
                    if ( Reel_It::is_shop_active() ) {
                        $product_ids = get_post_meta( $video_id, '_reel_it_linked_products', true );
                        if ( is_array( $product_ids ) && ! empty( $product_ids ) ) {
                            $product = wc_get_product( $product_ids[0] );
                            if ( $product && $product->is_visible() ) {
                                $linked_product = $product;
                            }
                        }
                    }

                    if ( $linked_product ) :
                    ?>
                        <a href="<?php echo esc_url( $linked_product->get_permalink() ); ?>" class="reel-it-product-card" target="_blank" data-product-id="<?php echo esc_attr( $linked_product->get_id() ); ?>">
                            <div class="reel-it-product-thumb">
                                <?php echo wp_kses_post( $linked_product->get_image( 'thumbnail' ) ); ?>
                            </div>
                            <div class="reel-it-product-info">
                                <span class="reel-it-product-title"><?php echo esc_html( $linked_product->get_name() ); ?></span>
                                <span class="reel-it-product-price"><?php echo wp_kses_post( $linked_product->get_price_html() ); ?></span>
                            </div>
                            <div class="reel-it-product-action">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                            </div>
                        </a>
                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php /* Why: JS constructor queries .reel-it-prev / .reel-it-next for arrow nav.
             Only render if more than one slide exists. */ ?>
    <?php if ( $total_slides > 1 && ! empty( $atts['show_controls'] ) ) : ?>
        <button class="reel-it-prev" type="button" aria-label="<?php esc_attr_e( 'Previous slide', 'reel-it' ); ?>">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
        </button>
        <button class="reel-it-next" type="button" aria-label="<?php esc_attr_e( 'Next slide', 'reel-it' ); ?>">
            <span class="dashicons dashicons-arrow-right-alt2"></span>
        </button>
    <?php endif; ?>

    <?php /* Why: JS binds .reel-it-thumbnail click events for jump-to-slide.
             Only render when thumbnails are enabled and there's more than 1 slide. */ ?>
    <?php if ( $total_slides > 1 && ! empty( $atts['show_thumbnails'] ) ) : ?>
        <div class="reel-it-thumbnails" role="tablist" aria-label="<?php esc_attr_e( 'Video thumbnails', 'reel-it' ); ?>">
            <?php foreach ( $videos as $thumb_index => $thumb_video ) :
                $thumb_vid_id = isset( $thumb_video['id'] ) ? intval( $thumb_video['id'] ) : 0;
                $thumb_poster = $public_instance->resolve_poster_url( $thumb_vid_id, 'thumbnail' );
            ?>
                <button class="reel-it-thumbnail <?php echo $thumb_index === 0 ? 'active' : ''; ?>" type="button" data-slide="<?php echo intval( $thumb_index ); ?>" role="tab" aria-selected="<?php echo $thumb_index === 0 ? 'true' : 'false'; ?>" aria-label="<?php printf( esc_attr__( 'Go to slide %d', 'reel-it' ), $thumb_index + 1 ); ?>">
                    <?php if ( ! empty( $thumb_poster['url'] ) ) : ?>
                        <img src="<?php echo esc_url( $thumb_poster['url'] ); ?>" alt="" loading="lazy" width="60" height="106">
                    <?php else : ?>
                        <span class="dashicons dashicons-video-alt3"></span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
