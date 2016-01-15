<?php
/**
 * Template handler for the built-in WordPress chat functionality.
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\WP_Buoy_Alert\WordPress_Chat
 *
 * @copyright Copyright (c) 2015-2016 by Meitar "maymay" Moscovitz
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

/** Sets up the WordPress Environment. */
require( dirname(__FILE__) . '/../../../../wp-load.php' );

/** Loads the additional chat room class. */
require plugin_dir_path(dirname(__FILE__)) . 'class-buoy-chat-room.php';

nocache_headers();

if (isset($_GET['hash'])) {
    $buoy_chat_room = new WP_Buoy_Chat_Room($_GET['hash']);
    $buoy_chat_room->render();
} else {
    wp_die(__('You do not have sufficient permissions to access this page.', 'buoy'));
}
