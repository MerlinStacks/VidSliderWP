<?php
/**
 * Database integrity tests.
 *
 * @package Reel_It
 */

/**
 * Tests gallery relationship integrity.
 */
class Test_Reel_It_Database extends WP_UnitTestCase {
	/**
	 * Create plugin tables before each test.
	 */
	public function set_up(): void {
		parent::set_up();
		reel_it_activate();
	}

	/**
	 * A video can only occur once in a gallery.
	 */
	public function test_duplicate_gallery_video_is_rejected(): void {
		$database = Reel_It_Database::instance();
		$feed_id  = $database->create_feed( 'Duplicate test ' . wp_generate_uuid4() );
		$video_id = self::factory()->post->create(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'video/mp4',
			)
		);

		$this->assertIsInt( $database->add_video_to_feed( $feed_id, $video_id ) );
		$this->assertFalse( $database->add_video_to_feed( $feed_id, $video_id ) );
	}

	/**
	 * Relationships cannot target a missing gallery.
	 */
	public function test_missing_gallery_is_rejected(): void {
		$database = Reel_It_Database::instance();
		$video_id = self::factory()->post->create(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'video/mp4',
			)
		);

		$this->assertFalse( $database->add_video_to_feed( PHP_INT_MAX, $video_id ) );
	}

	/** New videos append after a manually reordered gallery. */
	public function test_new_video_appends_after_existing_sort_order(): void {
		$database = Reel_It_Database::instance();
		$feed_id  = $database->create_feed( 'Ordering ' . wp_generate_uuid4() );
		$ids      = self::factory()->post->create_many( 3, array(
			'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => 'video/mp4',
		) );
		$database->add_video_to_feed( $feed_id, $ids[0], 10 );
		$database->add_video_to_feed( $feed_id, $ids[1], 0 );
		$database->add_video_to_feed( $feed_id, $ids[2] );
		$videos = $database->get_feed_videos( $feed_id );
		$this->assertSame( array( $ids[1], $ids[0], $ids[2] ), array_map( 'intval', wp_list_pluck( $videos, 'video_id' ) ) );
		$this->assertSame( 11, (int) $videos[2]->sort_order );
	}
}
