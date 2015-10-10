<?php

class AlertsTest extends WP_UnitTestCase {

    public function setUp () {
        parent::setUp();
        $this->plugin = new BetterAngelsPlugin();
    }

    public function test_alertPostTypeExists () {
        $this->assertTrue(post_type_exists('better_angels_alert'));
    }

}

