<?php
/**
 * Better Angels uninstaller
 *
 * @package plugin
 */

// Don't execute any uninstall code unless WordPress core requests it.
if (!defined('WP_UNINSTALL_PLUGIN')) { exit(); }

// Delete options.
//delete_option('better-angels_');

foreach (get_users() as $usr) {
    delete_user_meta($usr->ID, 'better-angels_call_for_help');
    delete_user_meta($usr->ID, 'better-angels_guardians');
    delete_user_meta($usr->ID, 'better-angels_sms');
    delete_user_meta($usr->ID, 'better-angels_sms_provider');
}
