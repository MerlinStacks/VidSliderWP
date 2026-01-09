<?php
/**
 * Shared video upload handler utility.
 *
 * Centralizes video upload logic to avoid duplication between admin and block contexts.
 *
 * @since      1.3.1
 * @package    Reel_It
 * @subpackage Reel_It/includes
 */

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Video upload handler utility class.
 *
 * @since      1.3.1
 * @package    Reel_It
 * @subpackage Reel_It/includes
 */
class Reel_It_Upload_Handler {

    /**
     * Default allowed video formats.
     *
     * @var array
     */
    private static $default_formats = array( 'mp4', 'webm', 'ogg' );

    /**
     * MIME type mapping for video formats.
     *
     * @var array
     */
    private static $mime_types = array(
        'mp4'  => 'video/mp4',
        'webm' => 'video/webm',
        'ogg'  => 'video/ogg',
        'mov'  => 'video/quicktime',
        'avi'  => 'video/x-msvideo',
    );

    /**
     * Handle video file upload with security validation.
     *
     * @since  1.3.1
     * @param  array $file The $_FILES['video_file'] array.
     * @return array|WP_Error Success data or WP_Error on failure.
     */
    public static function handle_upload( $file ) {
        // Validate the upload.
        if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
            return new WP_Error( 'invalid_upload', __( 'Invalid file upload.', 'reel-it' ) );
        }

        // Get allowed formats from settings.
        $options         = get_option( 'reel_it_options', array() );
        $allowed_formats = isset( $options['allowed_formats'] ) && ! empty( $options['allowed_formats'] )
            ? $options['allowed_formats']
            : self::$default_formats;

        // Build allowed MIME types array.
        $allowed_types = array();
        $mimes_map     = array();
        foreach ( $allowed_formats as $format ) {
            if ( isset( self::$mime_types[ $format ] ) ) {
                $allowed_types[]        = self::$mime_types[ $format ];
                $mimes_map[ $format ]   = self::$mime_types[ $format ];
            }
        }

        // Enhanced MIME type validation using finfo.
        if ( function_exists( 'finfo_open' ) ) {
            $finfo = finfo_open( FILEINFO_MIME_TYPE );
            if ( $finfo ) {
                $detected_mime = finfo_file( $finfo, $file['tmp_name'] );
                finfo_close( $finfo );
                if ( ! in_array( $detected_mime, $allowed_types, true ) ) {
                    return new WP_Error(
                        'mime_mismatch',
                        __( 'File type does not match extension. Please upload a valid video file.', 'reel-it' )
                    );
                }
            }
        }

        // Validate browser-reported MIME type.
        if ( ! in_array( $file['type'], $allowed_types, true ) ) {
            return new WP_Error(
                'invalid_type',
                __( 'Invalid file type. Please upload a supported video file.', 'reel-it' )
            );
        }

        // Check file size.
        $max_file_size       = isset( $options['max_file_size'] ) ? intval( $options['max_file_size'] ) : Reel_It::DEFAULT_MAX_FILE_SIZE;
        $max_file_size_bytes = $max_file_size * 1024 * 1024;

        if ( $file['size'] > $max_file_size_bytes ) {
            return new WP_Error(
                'file_too_large',
                /* translators: %d: Maximum file size in MB. */
                sprintf( __( 'File is too large. Maximum size is %d MB.', 'reel-it' ), $max_file_size )
            );
        }

        // Check file content for malicious patterns.
        $file_content = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        if ( false === $file_content || false !== strpos( $file_content, '<?php' ) ) {
            return new WP_Error( 'invalid_content', __( 'Invalid file content.', 'reel-it' ) );
        }

        // Handle the upload.
        $upload_overrides = array(
            'test_form' => false,
            'mimes'     => $mimes_map,
        );

        $uploaded_file = wp_handle_upload( $file, $upload_overrides );

        if ( isset( $uploaded_file['error'] ) ) {
            return new WP_Error( 'upload_failed', $uploaded_file['error'] );
        }

        // Insert into media library.
        $attachment = array(
            'post_mime_type' => $uploaded_file['type'],
            'post_title'     => sanitize_file_name( basename( $uploaded_file['file'] ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'post_author'    => get_current_user_id(),
        );

        $attach_id = wp_insert_attachment( $attachment, $uploaded_file['file'] );

        if ( ! $attach_id ) {
            return new WP_Error( 'attachment_failed', __( 'Failed to save video to media library.', 'reel-it' ) );
        }

        // Generate attachment metadata.
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded_file['file'] );
        wp_update_attachment_metadata( $attach_id, $attach_data );

        return array(
            'id'        => $attach_id,
            'url'       => $uploaded_file['url'],
            'title'     => get_the_title( $attach_id ),
            'mime'      => $uploaded_file['type'],
            'thumbnail' => wp_get_attachment_image_url( $attach_id, 'thumbnail' ),
        );
    }
}
