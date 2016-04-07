<?php
/**
 * Tests for Buoy Alerts
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\WP_Buoy_Alert\Tests
 *
 * @copyright Copyright (c) 2015-2016 by Meitar "maymay" Moscovitz
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

/**
 * Buoy alerts testing class.
 *
 * @link https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/
 */
class BuoyAlertsTest extends Buoy_UnitTestCase {

    /**
     * Sets up the testing environment before each test.
     *
     * @link https://phpunit.de/manual/current/en/fixtures.html
     */
    public function setUp () {
        parent::setUp();
    }

    /**
     * Ensures that WordPress recognizes what a "Buoy Alert" is.
     */
    public function test_alert_post_type_exists () {
        $this->assertTrue(post_type_exists('buoy_alert'));
    }

    /**
     * Asking for a non-existent alert should throw an Exception.
     */
    public function test_unknown_alert_throws_exception () {
        $this->setExpectedExceptionRegExp('Exception', '/^No alert with lookup "this alert does not exist" found.$/');
        new WP_Buoy_Alert('this alert does not exist');
    }

    public function test_setting_up_new_alert_creates_chat_room_name () {
        $this->markTestIncomplete();
    }

    public function test_new_alert_created () {
        $this->markTestIncomplete('Must have a team in order to publish an alert.');
    }

    public function test_get_alert_with_at_least_8_character_string () {
        $this->markTestIncomplete();
    }

    /**
     * Checks (de)activating the plugin (un)schedules "old" alerts for
     * deletion.
     *
     * It is important for Buoy alerts to be ephemeral in nature, not
     * necessarily stored in a database for a long time. This test is
     * used to make sure that merely activating the plugin is enough
     * to tell the plugin to delete any incident data that was made
     * 48 hours ago (or longer).
     *
     * This test also checks for the inverse: that this scheduled job
     * is automatically removed whenever the plugin is deactivated.
     *
     * Note that this test does *not* ensure the alert data itself is
     * deleted, only that the automatic job scheduler has the correct
     * information.
     *
     * @ticket 21
     */
    public function test_schedule_and_unschedule_delete_old_alerts_hook_on_activation_and_deactivation () {
        $network_wide = is_multisite();

        WP_Buoy_Plugin::activate($network_wide);
        $this->assertEquals('hourly', wp_get_schedule('buoy_delete_old_alerts', array('-2 days')));

        WP_Buoy_Plugin::deactivate();
        $this->assertFalse(wp_get_schedule('buoy_delete_old_alerts', array('-2 days')));
    }

    public function test_deleteOldAlerts () {
        $this->markTestIncomplete();
    }

    public function test_addIncidentResponder () {
        $this->markTestIncomplete();
    }

    public function test_canOnlyAddSameIncidentResponderOnce () {
        $this->markTestIncomplete();
    }

    public function test_getIncidentResponders () {
        $this->markTestIncomplete();
    }

    public function test_getResponderGeoLocation () {
        $this->markTestIncomplete();
    }

    /**
     * Ensures that the "Go to my location" button refers to the current user.
     *
     * @ticket 135
     */
    public function test_go_to_my_location_references_current_user_id () {
        $this->markTestIncomplete();
    }

}
