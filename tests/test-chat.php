<?php

class ChatRoomTest extends WP_UnitTestCase {

    public function setUp () {
        parent::setUp();
        $this->plugin = new BetterAngelsPlugin();
    }

    public function test_chatRoomPrefixIsCorrect () {
        $this->plugin->setChatRoomName('some string');
        $this->assertStringStartsWith('buoy_', $this->plugin->getChatRoomName());
    }

}

