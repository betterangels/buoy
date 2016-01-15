<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/buoy.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

/**
 * Custom test case class.
 *
 * We use PHPUnit's `@ticket` annotation to refer to GitHub issues,
 * but still want to use `WP_UnitTestCase` for its various factory
 * methods, so we subclass it and stub out the Trac-checking part. :)
 */
class Buoy_UnitTestCase extends WP_UnitTestCase {

    /**
     * Empty method to disable integration with WP Core Trac.
     *
     * Overrides the parent's method to disable fetching information
     * from the WordPress Core Trac ticket tracker which causes tests
     * with the `@ticket` annotation in our own test cases to fail.
     *
     * @link https://core.trac.wordpress.org/browser/tags/4.4/tests/phpunit/includes/testcase.php#L434
     *
     * @return void
     */
    protected function checkRequirements () {
        // do nothing!
    }

}
