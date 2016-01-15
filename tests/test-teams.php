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

}
