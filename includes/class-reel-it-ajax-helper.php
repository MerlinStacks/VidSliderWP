<?php
/**
 * Shared AJAX guard for nonce + capability checks.
 *
 * Why: the nonce-verify + capability-check + error-response pattern was
 * duplicated across 18 AJAX handlers in 5 files. This class centralises
 * it so every handler is a one-liner.
 *
 * @since      1.6.0
 * @package    Reel_It
 * @subpackage Reel_It/includes
 */

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * AJAX verification helper.
 *
 * @since 1.6.0
 */
class Reel_It_Ajax_Helper {

    /**
     * Verify the AJAX nonce and user capability.
     *
     * Sends a JSON error and terminates if either check fails.
     *
     * @since  1.6.0
     * @param  string $capability WordPress capability required (default: manage_options).
     * @return void
     */
    public static function verify( $capability = 'manage_options' ) {
        check_ajax_referer( 'reel_it_nonce', 'nonce' );

        if ( ! current_user_can( $capability ) ) {
            wp_send_json_error(
                array( 'message' => __( 'Permission denied', 'reel-it' ) )
            );
        }
    }
}
