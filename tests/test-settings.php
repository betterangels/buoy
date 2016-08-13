<?php
/**
 * Tests for Buoy Settings
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\WP_Buoy_Settings\Tests
 *
 * @copyright Copyright (c) 2015-2016 by Meitar "maymay" Moscovitz
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

/**
 * Buoy settings testing class.
 *
 * @link https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/
 */
class BuoySettingsTest extends Buoy_UnitTestCase {

    /**
     * Sets up the testing environment before each test.
     *
     * @link https://phpunit.de/manual/current/en/fixtures.html
     */
    public function setUp () {
        parent::setUp();
    }

    /**
     * Ensures default "Safety Information" exists.
     */
    public function test_default_safety_information_exists () {
        $this->assertNotEmpty(WP_Buoy_Settings::get_instance()->get('safety_info'));
    }

    /**
     * Ensures "timed/scheduled/future alerts" are off unless we can use POSIX functions,
     * which are required by the Crontab manager class.
     */
    public function test_future_alerts_are_only_enabled_on_posix_systems () {
        $x = WP_Buoy_Settings::get_instance()->get('future_alerts');
        if (function_exists('posix_getpwuid')) {
            $this->assertTrue($x);
        } else {
            $this->assertFalse($x);
        }
    }

    /**
     * Ensures the "timed/scheduled/future alerts" feature toggle is only displayed on POSIX systems.
     */
    public function test_future_alerts_feature_toggle_ui_depends_on_posix () {
        $x = 'name="buoy_settings\[future_alerts\]"';
        if (function_exists('posix_getpwuid')) {
            $p = "/$x/";
        } else {
            $p = "/^((?!$x).)*$/s";
        }
        $this->expectOutputRegex($p);
        $id = $this->factory->user->create(array('role' => 'administrator'));
        wp_set_current_user($id);
        WP_Buoy_Settings::renderOptionsPage();
    }

}

