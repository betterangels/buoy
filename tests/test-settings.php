<?php

class BuoySettingsTest extends WP_UnitTestCase {

    public function setUp () {
        parent::setUp();
    }

    public function test_default_safety_information_exists () {
        $this->assertNotEmpty(WP_Buoy_Settings::get_instance()->get('safety_info'));
    }

}

