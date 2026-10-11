<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Reel_It
 */

$tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $tests_dir ) {
	$tests_dir = '/tmp/wordpress-tests-lib';
}

$autoload = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

$polyfills_dir = dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills';
if ( is_dir( $polyfills_dir ) ) {
	putenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH=' . $polyfills_dir );
}

if ( ! file_exists( $tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "WordPress test suite not found. Set WP_TESTS_DIR.\n" );
	exit( 1 );
}

require_once $tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () {
		require dirname( __DIR__ ) . '/reel-it.php';
	}
);

require $tests_dir . '/includes/bootstrap.php';
