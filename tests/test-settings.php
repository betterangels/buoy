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

}

