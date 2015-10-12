<?php

class GuardianTest extends WP_UnitTestCase {

    public function setUp () {
        parent::setUp();
        $this->plugin = new BetterAngelsPlugin();
        wp_create_user('survivor', 'password', 'survivor@nowhere.invalid');
        wp_create_user('sam', 'password', 'sam@nowhere.invalid');
        wp_create_user('john', 'password', 'john@nowhere.invalid');
        wp_create_user('alice', 'password', 'alice@nowhere.invalid');
        wp_create_user('bob', 'password', 'bob@nowhere.invalid');

        wp_set_current_user(username_exists('survivor'));
    }

    public function test_isMyGuardian () {
        $this->assertFalse($this->plugin->isMyGuardian('sam'));

        add_user_meta(get_current_user_id(), 'better-angels_pending_guardians', username_exists('sam'), false);
        $this->plugin->addGuardian('sam', get_current_user_id(), false);

        $this->assertTrue($this->plugin->isMyGuardian('sam'));

        $this->assertFalse($this->plugin->isMyGuardian('foobar'));
    }

	public function test_getMyGuardians () {
        // Survivor's guardian list should start empty.
        $this->assertEquals(array(), $this->plugin->getMyGuardians());

        // After adding a guardian, which is a user that
        // must actually exist, then the current user's
        // guardian list should include that user.
        add_user_meta(get_current_user_id(), 'better-angels_pending_guardians', username_exists('sam'), false);
        $this->plugin->addGuardian('sam', get_current_user_id(), false);
        $expected = array(get_userdata(username_exists('sam')));
        $this->assertEquals($expected, $this->plugin->getMyGuardians());

        // Let's add another guardian for good measure.
        add_user_meta(get_current_user_id(), 'better-angels_pending_guardians', username_exists('john'), false);
        $this->plugin->addGuardian('john', get_current_user_id(), false);
        $expected = array_merge($expected, array(get_userdata(username_exists('john'))));
        $this->assertEquals($expected, $this->plugin->getMyGuardians());

        // Removing a guardian should remove them from
        // the list of guardians retrieved.
        $this->plugin->removeGuardian('sam');
        array_shift($expected);
        $this->assertEquals($expected, $this->plugin->getMyGuardians());
        // 'john' should still be a guardian, as he was
        // added but not removed.
        $this->assertTrue($this->plugin->isMyGuardian('john'));
        // This means there should be 1 and only 1 guardian
        // in the list.
        $this->assertCount(1, $this->plugin->getMyGuardians());
    }

    public function test_cannotAddSameGuardianTwice () {
        $expected = array(get_userdata(username_exists('sam')));
        add_user_meta(get_current_user_id(), 'better-angels_pending_guardians', username_exists('sam'), false);
        $this->plugin->addGuardian('sam', get_current_user_id(), false);
        $this->plugin->addGuardian('sam', get_current_user_id(), false);
        $this->assertEquals($expected, $this->plugin->getMyGuardians());
	}

    public function test_addFakeGuardian () {
        // Add a guardian as a "fake"
        add_user_meta(get_current_user_id(), 'better-angels_pending_fake_guardians', username_exists('bob'), false);
        $this->plugin->addGuardian('bob', get_current_user_id(), true);
        // This person should still be part of the resonse team.
        $this->assertTrue($this->plugin->isMyGuardian('bob'));
        // But since the only added user is a "fake," we still have no "guardians."
        $this->assertEmpty($this->plugin->getMyGuardians());
    }

    public function test_cannotAddSelfAsGuardian () {
        $curr_user = wp_get_current_user();
        $this->plugin->addGuardian($curr_user->user_login, get_current_user_id(), false);
        $this->assertFalse($this->plugin->isMyGuardian($curr_user->user_login));
    }

    public function test_cannotAddGuardianWithoutInvitation () {
        $this->assertEmpty(get_user_meta(get_current_user_id(), 'better-angels_pending_guardians'), username_exists('sam'));
        $this->plugin->addGuardian('sam', get_current_user_id(), false);
        $this->assertEmpty(get_user_meta(get_current_user_id(), 'better-angels_pending_guardians'), username_exists('sam'));
    }

    public function test_canAddGuardianWithInvitation () {
        $this->assertFalse($this->plugin->isMyGuardian('sam'));
        add_user_meta(get_current_user_id(), 'better-angels_pending_guardians', username_exists('sam'), false);

        $this->plugin->addGuardian('sam', get_current_user_id(), false);

        $this->assertTrue($this->plugin->isMyGuardian('sam'));
        $this->assertNotEmpty($this->plugin->getMyGuardians());

        $this->assertEquals(
            username_exists('sam'),
            get_user_meta(get_current_user_id(), 'better-angels_guardians', username_exists('sam'), true)
        );
        $this->assertEmpty(get_user_meta(get_current_user_id(), 'better-angels_pending_guardians', username_exists('sam'), true));
    }

    public function test_cannotAddRealGuardianIfInvitationWasForFakePosition () {
        add_user_meta(get_current_user_id(), 'better-angels_pending_fake_guardians', username_exists('sam'), false);
        $this->plugin->addGuardian('sam', get_current_user_id(), false);
        $this->assertEmpty(get_user_meta(get_current_user_id(), 'better-angels_guardians'));
    }

    public function test_setTeamMembership () {
        // TODO: Need tests for this, should've done them first.
    }

}

