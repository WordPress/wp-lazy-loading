<?php
/**
 * Tests bootstrap script.
 */

// Detect project directory.
define( 'TESTS_PLUGIN_DIR', dirname( dirname( __DIR__ ) ) );

// Disable xdebug backtrace.
if ( function_exists( 'xdebug_disable' ) ) {
	xdebug_disable();
}

if ( file_exists( TESTS_PLUGIN_DIR . '/vendor/autoload.php' ) ) {
	require_once TESTS_PLUGIN_DIR . '/vendor/autoload.php';
}

if ( false !== getenv( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', getenv( 'WP_PLUGIN_DIR' ) );
} else {
	define( 'WP_PLUGIN_DIR', dirname( TESTS_PLUGIN_DIR ) );
}

// Detect where to load the WordPress tests environment from.
if ( false !== getenv( 'WP_TESTS_DIR' ) ) {
	$test_root = getenv( 'WP_TESTS_DIR' );
} elseif ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	$test_root = getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit';
} elseif ( file_exists( '/tmp/wordpress-tests-lib/includes/bootstrap.php' ) ) {
	$test_root = '/tmp/wordpress-tests-lib';
} else {
	$test_root = dirname( dirname( dirname( dirname( TESTS_PLUGIN_DIR ) ) ) ) . '/tests/phpunit';
}

// Ensure the methods annotated with the (unfixed) lazy-loading ticket are run.
define( 'WP_TESTS_FORCE_KNOWN_BUGS', true );

// Ensure the plugin is loaded.
$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( basename( TESTS_PLUGIN_DIR ) . '/wp-lazy-loading.php' ),
);

// Load the WordPress tests environment.
require_once $test_root . '/includes/bootstrap.php';
