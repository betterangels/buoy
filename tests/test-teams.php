<?php
/**
 * Tests for Buoy Teams
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\WP_Buoy_Team\Tests
 *
 * @copyright Copyright (c) 2015-2016 by Meitar "maymay" Moscovitz
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

/**
 * Buoy teams testing class.
 *
 * @link https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/
 */
class BuoyTeamsTest extends Buoy_UnitTestCase {

    /**
     * Sets up the testing environment before each test.
     *
     * @link https://phpunit.de/manual/current/en/fixtures.html
     */
    public function setUp () {
        parent::setUp();
    }

    /**
     * Ensures that WordPress recognizes what a "Buoy Team" is.
     */
    public function test_team_post_type_exists () {
        $this->assertTrue(post_type_exists('buoy_team'));
    }

    /**
     * Ensures the drop-down list of available responders outputs the correct values.
     *
     * @ticket 121
     */
    public function test_add_team_member_meta_box_datalist_option_value_matches_user_login () {
        $post = $this->factory->post->create_and_get();
        $admin = $this->factory->user->create_and_get(array('role' => 'administrator'));
        $user = $this->factory->user->create_and_get(array('role' => 'subscriber'));

        wp_set_current_user($admin->ID);
        WP_Buoy_Team::renderAddTeamMemberMetaBox($post->ID);

        $this->expectOutputRegex('/<option value="' . $user->user_login . '"\s*\/>/');
    }

    /**
     * Ensures responders are found even in private teams.
     */
    public function test_confirmed_members_of_private_teams_are_counted_as_responders () {
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
        $this->assertTrue($team->has_responder());
    }

}
