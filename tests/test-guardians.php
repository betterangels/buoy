<?php

class GuardianTest extends WP_UnitTestCase {

    public function setUp () {
        parent::setUp();
        $this->plugin = new BetterAngelsPLugin();
        wp_create_user('survivor', 'password', 'survivor@nowhere.invalid');
        wp_create_user('sam', 'password', 'sam@nowhere.invalid');
        wp_create_user('john', 'password', 'john@nowhere.invalid');
        wp_create_user('alice', 'password', 'alice@nowhere.invalid');
        wp_create_user('bob', 'password', 'bob@nowhere.invalid');

        wp_set_current_user(username_exists('survivor'));
    }

    function test_isMyGuardian () {
        $this->assertFalse($this->plugin->isMyGuardian('sam'));

        $this->plugin->addGuardian('sam');

        $this->assertTrue($this->plugin->isMyGuardian('sam'));

        $this->assertFalse($this->plugin->isMyGuardian('foobar'));
    }

	function test_getMyGuardians () {
        // Survivor's guardian list should start empty.
        $this->assertEquals(array(), $this->plugin->getMyGuardians());

        // After adding a guardian, which is a user that
        // must actually exist, then the current user's
        // guardian list should include that user.
        $this->plugin->addGuardian('sam');
        $expected = array(get_userdata(username_exists('sam')));
        $this->assertEquals($expected, $this->plugin->getMyGuardians());

        // Adding the same guardian again should not result
        // in a duplicate guardian.
        $this->plugin->addGuardian('sam');
        $this->assertEquals($expected, $this->plugin->getMyGuardians());

        // Let's add another guardian for good measure.
        $this->plugin->addGuardian('john');
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
}

