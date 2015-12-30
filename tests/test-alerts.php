<?php

class AlertsTest extends WP_UnitTestCase {

    public function setUp () {
        parent::setUp();
        wp_set_current_user(null, 'survivor');
    }

    public function test_alert_post_type_exists () {
        $this->assertTrue(post_type_exists('buoy_alert'));
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

    public function test_activation_schedules_hourly_delete_old_alerts_hook () {
        WP_Buoy_Plugin::activate();
        $this->assertEquals('hourly', wp_get_schedule('buoy_delete_old_alerts', array('-2 days')));
    }

    public function test_deactivation_unschedules_delete_old_alerts_hook () {
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

}
