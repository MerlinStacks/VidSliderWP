<?php
/**
 * Multisite database isolation and lifecycle regressions.
 *
 * @package Reel_It
 */
class Test_Reel_It_Multisite extends WP_UnitTestCase {

	private $site_ids = array();

	public function set_up(): void {
		parent::set_up();
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Run with WP_MULTISITE=1.' );
		}
		reel_it_activate();
	}

	public function tear_down(): void {
		global $wpdb;
		foreach ( $this->site_ids as $site_id ) {
			$prefix = $wpdb->get_blog_prefix( $site_id );
			foreach ( array( 'reel_it_analytics', 'reel_it_feed_videos', 'reel_it_feeds' ) as $table ) {
				$wpdb->query( "DROP TABLE IF EXISTS {$prefix}{$table}" );
			}
			wpmu_delete_blog( $site_id, true );
		}
		parent::tear_down();
	}

	private function create_site() {
		$id = self::factory()->blog->create();
		$this->site_ids[] = $id;
		return $id;
	}

	/** A held database reference must follow switch_to_blog and restoration. */
	public function test_database_writes_stay_on_the_current_site(): void {
		$database = Reel_It_Database::instance();
		$original_id = $database->create_feed( 'Original site' );
		$site_id = $this->create_site();
		switch_to_blog( $site_id );
		try {
			reel_it_activate();
			$id = $database->create_feed( 'Second site' );
			$this->assertSame( 'Second site', $database->get_feed( $id )->name );
			$database->update_feed( $id, 'Updated second site' );
			$this->assertSame( 'Updated second site', $database->get_feed( $id )->name );
		} finally {
			restore_current_blog();
		}
		$this->assertSame( 'Original site', $database->get_feed( $original_id )->name );
	}

	/** Network activation and deactivation process every site's cron schedule. */
	public function test_network_lifecycle_initializes_and_unschedules_sites(): void {
		$site_id = $this->create_site();
		$original_blog = get_current_blog_id();
		reel_it_activate( true );
		$this->assertSame( $original_blog, get_current_blog_id() );
		switch_to_blog( $site_id );
		try {
			$this->assertSame( REEL_IT_VERSION, get_option( 'reel_it_db_version' ) );
			$this->assertNotFalse( wp_next_scheduled( 'reel_it_daily_prune' ) );
			$this->assertNotFalse( Reel_It_Database::instance()->create_feed( 'Network site' ) );
		} finally {
			restore_current_blog();
		}
		reel_it_deactivate( true );
		switch_to_blog( $site_id );
		try {
			$this->assertFalse( wp_next_scheduled( 'reel_it_daily_prune' ) );
		} finally {
			restore_current_blog();
		}
	}

	/** New sites inherit tables and cron when the plugin is network active. */
	public function test_new_site_is_initialized_for_network_active_plugin(): void {
		$original = get_site_option( 'active_sitewide_plugins', array() );
		$plugins = $original;
		$plugins[ plugin_basename( dirname( __DIR__ ) . '/reel-it.php' ) ] = time();
		update_site_option( 'active_sitewide_plugins', $plugins );
		try {
			$site_id = $this->create_site();
			switch_to_blog( $site_id );
			try {
				$this->assertSame( REEL_IT_VERSION, get_option( 'reel_it_db_version' ) );
				$this->assertNotFalse( wp_next_scheduled( 'reel_it_daily_prune' ) );
			} finally {
				restore_current_blog();
			}
		} finally {
			update_site_option( 'active_sitewide_plugins', $original );
		}
	}
}
