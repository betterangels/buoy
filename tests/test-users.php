<?php
/**
 * Tests for Buoy Users
 *
 * Automated code tets for the `WP_Buoy_User` class. This code tests
 * the "User" component of the Buoy plugin for WordPress for correct
 * and expected functioning.
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\WP_Buoy_User\Tests
 *
 * @copyright Copyright (c) 2015-2016 by Meitar "maymay" Moscovitz
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

/**
 * Buoy users testing class.
 *
 * @link https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/
 */
class BuoyUsersTest extends Buoy_UnitTestCase {

    /**
     * Sets up the testing environment before each test.
     *
     * @link https://phpunit.de/manual/current/en/fixtures.html
     */
    public function setUp () {
        parent::setUp();
    }

    /**
     * Tests the Buoy User component's constructor.
     */
    public function test_new_buoy_user_must_be_an_existing_wordpress_user () {
        $id = $this->factory->user->create();

        $actual = new WP_Buoy_User($id);
        $this->assertInstanceOf('WP_Buoy_User', $actual);

        $this->setExpectedExceptionRegExp('Exception', '/^Invalid user ID: this user does not exist$/');
        $actual = new WP_Buoy_User('this user does not exist'); // should throw Exception
    }

    /**
     * Ensures responders are found even in private teams.
     */
    public function test_responders_can_exist_on_private_teams () {
        $alerter   = $this->factory->user->create_and_get(array('role' => 'subscriber'));
        $responder = $this->factory->user->create_and_get(array('role' => 'subscriber'));

        wp_set_current_user($alerter->ID);

        $post = $this->factory->post->create_and_get(array(
            'post_type' => 'buoy_team',
            'post_status' => 'private'
        ));
        $team = new WP_Buoy_Team($post->ID);
        $team
            ->add_member($responder->ID)
            ->confirm_member($responder->ID);
        $alerter = new WP_Buoy_User($alerter->ID);
        $this->assertTrue($alerter->has_responder());
    }

}
