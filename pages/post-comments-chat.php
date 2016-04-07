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

/** Loads the additional chat room class. */
require plugin_dir_path(dirname(__FILE__)).'includes/class-buoy-chat-room.php';

nocache_headers();

if (!empty($_GET['hash']) && get_current_user_id()) {
    try {
        $buoy_chat_room = new WP_Buoy_Chat_Room($_GET['hash']);
        if ($buoy_chat_room->is_alerter(get_current_user_id()) || $buoy_chat_room->is_responder(get_current_user_id())) {
            $buoy_chat_room->render();
        }
    } catch (Exception $e) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'buoy'));
    }
}
wp_die(__('You do not have sufficient permissions to access this page.', 'buoy'));
