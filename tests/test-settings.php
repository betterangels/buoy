<?php

class BuoySettingsTest extends WP_UnitTestCase {

    public function setUp () {
        parent::setUp();
        $this->plugin = new BetterAngelsPlugin();
    }

    public function test_defaultSafetyInformationExists () {
        $this->plugin->activate();
        $options = get_option('better-angels_settings');
        $this->assertNotEmpty($options['safety_info']);
    }

}

