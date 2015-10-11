<?php
/**
 * Better Angels uninstaller
 *
 * @package plugin
 */

// Don't execute any uninstall code unless WordPress core requests it.
if (!defined('WP_UNINSTALL_PLUGIN')) { exit(); }

// Delete options.
delete_option('better-angels_settings');

// Delete all incidents.
$posts = get_posts(array(
    'post_type' => 'better_angels_alert'
));
foreach ($posts as $post) {
    wp_delete_post($post->ID, true);
}

// Delete all custom user profile data.
foreach (get_users() as $usr) {
    delete_user_meta($usr->ID, 'better-angels_call_for_help');
    delete_user_meta($usr->ID, 'better-angels_guardians');
    delete_user_meta($usr->ID, 'better-angels_sms');
    delete_user_meta($usr->ID, 'better-angels_sms_provider');
}

// Delete all response team lists.
delete_metadata('user', null, 'better-angels_guardians', null, true);
delete_metadata('user', null, 'better-angels_fake_guardians', null, true);
delete_metadata('user', null, 'better-angels_pending_guardians', null, true);
delete_metadata('user', null, 'better-angels_pending_fake_guardians', null, true);
