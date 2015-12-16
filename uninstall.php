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

foreach (get_users() as $usr) {
    // Delete all custom user profile data.
    delete_user_meta($usr->ID, 'better-angels_call_for_help');
    delete_user_meta($usr->ID, 'better-angels_public_responder');
    delete_user_meta($usr->ID, 'better-angels_sms');
    delete_user_meta($usr->ID, 'better-angels_sms_provider');
    delete_user_meta($usr->ID, 'better-angels_pronoun');
    delete_user_meta($usr->ID, 'better-angels_installer-dismissed');

    // Delete extra metadata of this user's guardian memberships.
    delete_metadata('users', null, 'better-angels_guardian_' . $usr->ID . '_info', null, true);
}

// Delete response team lists.
delete_metadata('user', null, 'better-angels_guardians', null, true);
