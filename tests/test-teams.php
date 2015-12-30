<?php

class TeamsTest extends WP_UnitTestCase {

    public function setUp () {
        parent::setUp();
    }

    public function test_team_post_type_exists () {
        $this->assertTrue(post_type_exists('buoy_team'));
    }

}
