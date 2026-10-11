<?php
/**
 * Settings sanitization tests.
 *
 * @package Reel_It
 */

require_once dirname( __DIR__ ) . '/admin/class-reel-it-settings.php';

/**
 * Tests plugin option sanitization.
 */
class Test_Reel_It_Settings extends WP_UnitTestCase {
	/**
	 * Numeric settings are constrained to supported values.
	 */
	public function test_numeric_settings_are_clamped(): void {
		$settings = new Reel_It_Settings( 'reel-it', REEL_IT_VERSION );
		$result   = $settings->sanitize_settings(
			array(
				'default_slider_speed'  => 999999,
				'border_radius'          => -5,
				'video_gap'              => 999,
				'default_videos_per_row' => 99,
				'max_file_size'          => 0,
			)
		);

		$this->assertSame( 10000, $result['default_slider_speed'] );
		$this->assertSame( 0, $result['border_radius'] );
		$this->assertSame( 100, $result['video_gap'] );
		$this->assertSame( 6, $result['default_videos_per_row'] );
		$this->assertSame( 1, $result['max_file_size'] );
	}

	/**
	 * Unsupported formats are discarded.
	 */
	public function test_upload_formats_are_allowlisted(): void {
		$settings = new Reel_It_Settings( 'reel-it', REEL_IT_VERSION );
		$result   = $settings->sanitize_settings(
			array(
				'allowed_formats' => array( 'mp4', 'php', 'webm' ),
			)
		);

		$this->assertSame( array( 'mp4', 'webm' ), $result['allowed_formats'] );
	}
}
