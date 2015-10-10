<?php

class AlertsTest extends WP_UnitTestCase {

    public function setUp () {
        parent::setUp();
        $this->plugin = new BetterAngelsPlugin();
    }

    public function test_alertPostTypeExists () {
        $this->assertTrue(post_type_exists('better_angels_alert'));
    }

    public function test_newAlertCreated () {
        $geodata = array(
            'latitude'  => '40.4381307',
            'longitude' => '-3.8199645'
        );
        $id = $this->plugin->newAlert(array('post_title' => 'Test alert.'), $geodata);
        $wp_post = get_post($id);

        $this->assertEquals('Test alert.', $wp_post->post_title);
        $this->assertEquals('closed', $wp_post->comment_status);
        $this->assertEquals('closed', $wp_post->ping_status);
        $this->assertEquals('better_angels_alert', $wp_post->post_type);
        $this->assertEmpty($wp_post->post_content);

        $this->assertEquals($geodata['latitude'], get_post_meta($id, 'geo_latitude', true));
        $this->assertEquals($geodata['longitude'], get_post_meta($id, 'geo_longitude', true));
    }

    public function test_deleteOldAlertsIsScheduledAndUnscheduled () {
        $this->plugin->activate();
        $this->assertEquals('twicedaily', wp_get_schedule('better-angels_delete_old_alerts'));

        $this->plugin->deactivate();
        $this->assertFalse(wp_get_schedule('better-angels_delete_old_alerts'));
    }

    public function test_deleteOldAlerts () {
        $geodata = array(
            'latitude'  => '40.4381307',
            'longitude' => '-3.8199645'
        );
        $post_data = array(
            'post_title' => 'An alert three days ago.',
            'post_date_gmt' => gmdate('Y-m-d H:i:s', strtotime('-3 days'))
        );
        $id_3_days_ago = $this->plugin->newAlert($post_data, $geodata);

        $post_data['post_title'] = 'An alert two days ago.';
        $post_data['post_date_gmt'] = gmdate('Y-m-d H:i:s', strtotime('-2 days'));
        $id_2_days_ago = $this->plugin->newAlert($post_data, $geodata);

        $post_data['post_title'] = 'An alert one day ago.';
        $post_data['post_date_gmt'] = gmdate('Y-m-d H:i:s', strtotime('-1 day'));
        $id_1_day_ago  = $this->plugin->newAlert($post_data, $geodata);

        do_action('better-angels_delete_old_alerts');

        $this->assertNull(get_post($id_3_days_ago));
        $this->assertNull(get_post($id_2_days_ago));
        $this->assertNotNull(get_post($id_1_day_ago));

        $this->plugin->deleteOldAlerts('-1 day');

        $this->assertNull(get_post($id_1_day_ago));
    }

}

