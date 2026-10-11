<?php
/**
 * Rendering regressions for block defaults and analytics attribution.
 *
 * @package Reel_It
 */
class Test_Reel_It_Rendering extends WP_UnitTestCase {

	/** Old gallery IDs must not follow a block back to manual selection. */
	public function test_manual_selection_does_not_emit_stale_feed_id(): void {
		$public = new Reel_It_Public( 'reel-it', REEL_IT_VERSION );
		$html   = $public->render_video_slider( array(
			'useFeed' => false,
			'feedId'  => 123,
			'videos'  => array( array( 'url' => 'https://example.org/video.mp4' ) ),
		) );
		$this->assertStringContainsString( 'data-feed-id="0"', $html );
	}

	/** Gutenberg omits attributes matching its default when serializing. */
	public function test_omitted_column_attribute_uses_configured_default(): void {
		$original = get_option( 'reel_it_options', array() );
		$blocks   = new Reel_It_Blocks_Secure( 'reel-it', REEL_IT_VERSION );
		try {
			update_option( 'reel_it_options', array( 'default_videos_per_row' => 4 ) );
			unregister_block_type( 'reel-it/video-slider' );
			$blocks->register_blocks();
			$parsed = parse_blocks( '<!-- wp:reel-it/video-slider {"videos":[{"url":"https://example.org/video.mp4"}]} /-->' );
			$this->assertStringContainsString( '--reel-it-columns-desktop: 4;', render_block( $parsed[0] ) );
		} finally {
			update_option( 'reel_it_options', $original );
			unregister_block_type( 'reel-it/video-slider' );
			$blocks->register_blocks();
		}
	}
}
