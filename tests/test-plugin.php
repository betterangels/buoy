<?php
/**
 * Tests for Buoy Plugin hooks with WordPress proper.
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\Tests
 *
 * @copyright Copyright (c) 2015-2016 by Meitar "maymay" Moscovitz
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

/**
 * Buoy plugin testing class.
 *
 * @link https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/
 */
class BuoyPluginTest extends Buoy_UnitTestCase {

    /**
     * Sets up the testing environment before each test.
     *
     * @link https://phpunit.de/manual/current/en/fixtures.html
     */
    public function setUp () {
        parent::setUp();
    }

    public function test_can_activate_without_errors () {
        WP_Buoy_Plugin::activate(false);
    }

    public function test_can_deactivate_without_errors () {
        WP_Buoy_Plugin::deactivate();
    }

}
