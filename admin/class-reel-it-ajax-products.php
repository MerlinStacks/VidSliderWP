<?php
/**
 * AJAX handlers for product tagging.
 *
 * Extracted from Reel_It_Settings to reduce file size.
 * All WordPress AJAX action names remain unchanged.
 *
 * @since      1.5.0
 * @package    Reel_It
 * @subpackage Reel_It/admin
 */

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Product tagging AJAX handlers.
 *
 * @since      1.5.0
 * @package    Reel_It
 * @subpackage Reel_It/admin
 */
class Reel_It_Ajax_Products {

    /**
     * Search WooCommerce products by keyword.
     *
     * @return void Sends JSON response.
     */
    public function ajax_search_products() {
        Reel_It_Ajax_Helper::verify();

        if ( ! Reel_It::is_shop_active() ) {
            wp_send_json_error( array( 'message' => __( 'WooCommerce is not active.', 'reel-it' ) ) );
        }

        $term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => Reel_It::DEFAULT_PAGINATION,
            's'              => $term,
            'fields'         => 'ids',
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
                        'id'    => $product->get_id(),
                        'text'  => $product->get_name() . ' (' . wp_strip_all_tags( wc_price( $product->get_price() ) ) . ')',
                        'price' => $product->get_price_html(),
                        'image' => $image_url,
                    );
                }
            }
        }

        wp_send_json_success( array( 'results' => $products ) );
    }

    /**
     * Get products linked to a specific video.
     *
     * @return void Sends JSON response.
     */
    public function ajax_get_video_products() {
        Reel_It_Ajax_Helper::verify();

        if ( ! Reel_It::is_shop_active() ) {
            wp_send_json_error( array( 'message' => __( 'WooCommerce is not active.', 'reel-it' ) ) );
        }

        $video_id = isset( $_POST['video_id'] ) ? intval( $_POST['video_id'] ) : 0;
        // BUG-04 fix: validate the post is actually an attachment
        if ( ! $video_id || get_post_type( $video_id ) !== 'attachment' ) {
            wp_send_json_error( array( 'message' => __( 'Invalid attachment', 'reel-it' ) ) );
        }

        $product_ids = get_post_meta( $video_id, '_reel_it_linked_products', true );
        if ( ! is_array( $product_ids ) ) {
            $product_ids = array();
        }

        $products = array();
        foreach ( $product_ids as $pid ) {
            $product = wc_get_product( $pid );
            if ( $product ) {
                $image_id = $product->get_image_id();
                $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src();

                $products[] = array(
                    'id'        => $product->get_id(),
                    'text'      => $product->get_name(),
                    'price'     => $product->get_price_html(),
                    'image'     => $image_url,
                    'permalink' => $product->get_permalink(),
                );
            }
        }

        wp_send_json_success( array( 'products' => $products ) );
    }

    /**
     * Save product tags for a video.
     *
     * @return void Sends JSON response.
     */
    public function ajax_save_video_products() {
        Reel_It_Ajax_Helper::verify();

        $video_id = isset( $_POST['video_id'] ) ? intval( $_POST['video_id'] ) : 0;
        // BUG-04 fix: validate the post is actually an attachment
        if ( ! $video_id || get_post_type( $video_id ) !== 'attachment' ) {
            wp_send_json_error( array( 'message' => __( 'Invalid attachment', 'reel-it' ) ) );
        }
        // BUG-05 fix: validate as array and unslash before intval mapping
        $product_ids = isset( $_POST['products'] ) && is_array( $_POST['products'] )
            ? array_map( 'intval', wp_unslash( $_POST['products'] ) )
            : array();

        update_post_meta( $video_id, '_reel_it_linked_products', $product_ids );

        wp_send_json_success( array( 'message' => __( 'Products Saved', 'reel-it' ) ) );
    }
}
