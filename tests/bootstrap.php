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

// Set up users.
// TODO: Maybe rewrite the tests to use the user factory?
wp_create_user('survivor', 'password', 'survivor@nowhere.invalid');
wp_create_user('sam', 'password', 'sam@nowhere.invalid');
wp_create_user('john', 'password', 'john@nowhere.invalid');
wp_create_user('alice', 'password', 'alice@nowhere.invalid');
wp_create_user('bob', 'password', 'bob@nowhere.invalid');
