<?php

class GuardianTest extends WP_UnitTestCase {

    public function setUp () {
        parent::setUp();
        wp_set_current_user(username_exists('survivor'));
        $this->plugin = new BetterAngelsPlugin();
    }

    public function test_add_a_guardian () {
        $guardian_id = username_exists('sam');
        $this->plugin->addGuardian($guardian_id, get_current_user_id());
        $this->assertTrue(in_array($guardian_id, get_user_meta(get_current_user_id(), 'better-angels_guardians')));

        return $guardian_id;
    }

    /**
     * @depends test_add_a_guardian
     */
    public function test_only_one_guardian_despite_adding_same_guardian_twice ($guardian_id) {
        $this->plugin->addGuardian($guardian_id, get_current_user_id());
        $this->plugin->addGuardian($guardian_id, get_current_user_id());
        $this->assertEquals(array($guardian_id), get_user_meta(get_current_user_id(), 'better-angels_guardians'));
	}

    /**
     * @depends test_add_a_guardian
     */
    public function test_adding_same_guardian_twice_returns_wp_error ($guardian_id) {
        $this->plugin->addGuardian($guardian_id, get_current_user_id());
        $actual = $this->plugin->addGuardian($guardian_id, get_current_user_id());
        $this->assertInstanceOf('WP_Error', $actual);
    }

    public function test_adding_nonexistent_user_as_a_guardian_returns_wp_error () {
        $user_id = 9876543;
        if (get_userdata($user_id) instanceof WP_User) {
            $this->fail("User $user_id exists in the testing database! Cannot proceed with test.");
        }
        $actual = $this->plugin->addGuardian($user_id, get_current_user_id());
        $this->assertInstanceOf('WP_Error', $actual);
    }

    public function test_adding_self_as_guardian_returns_wp_error () {
        $actual = $this->plugin->addGuardian(get_current_user_id(), get_current_user_id());
        $this->assertInstanceOf('WP_Error', $actual);
    }

    /**
     * @depends test_add_a_guardian
     */
    public function test_removing_guardian_removes_meta_field ($guardian_id) {
        $this->plugin->removeGuardian($guardian_id, get_current_user_id());
        $result = get_user_meta(get_current_user_id(), 'better-angels_guardians');
        $this->assertFalse(in_array($guardian_id, $result));
    }

    /**
     * @depends test_add_a_guardian
     */
    public function test_adding_guardian_adds_guardian_meta_field ($guardian_id) {
        $this->plugin->addGuardian($guardian_id, get_current_user_id());
        $info = $this->plugin->getGuardianInfo($guardian_id, get_current_user_id());
        $this->assertEquals(array('confirmed' => false), $info);
    }

    /**
     * @depends test_add_a_guardian
     */
    public function test_removing_guardian_removes_extra_guardian_meta_field ($guardian_id) {
        $this->plugin->addGuardian($guardian_id, get_current_user_id());
        $this->plugin->removeGuardian($guardian_id, get_current_user_id());
        $this->assertSame('', $this->plugin->getGuardianInfo($guardian_id, get_current_user_id()));
    }

    public function provider_test_add_metadata_to_guardian_info () {
        return array(
            array(array('receive_alerts' => true, 'confirmed' => false)),  // pending real guardian
            array(array('receive_alerts' => true, 'confirmed' => true)),   // real guardian
            array(array('receive_alerts' => false, 'confirmed' => false)), // pending fake guardian
            array(array('receive_alerts' => true, 'confirmed' => false)),  // pending real guardian
            array(array('receive_alerts' => false, 'confirmed' => false))  // pending fake guardian
        );
    }

    /**
     * @dataProvider provider_test_add_metadata_to_guardian_info
     * @depends test_add_a_guardian
     */
    public function test_setGuardianInfo ($info_arr, $guardian_id) {
        $this->plugin->addGuardian($guardian_id, get_current_user_id());
        $this->plugin->setGuardianInfo($guardian_id, get_current_user_id(), $info_arr);
        $info = $this->plugin->getGuardianInfo($guardian_id, get_current_user_id());
        $this->assertEquals($info_arr, $info);
    }

    public function test_getMyGuardians () {
        $this->markTestIncomplete();
    }

}
