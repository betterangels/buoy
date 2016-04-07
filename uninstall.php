<?php
/**
 * Buoy uninstaller.
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\Uninstaller
 */

// Don't execute any uninstall code unless WordPress core requests it.
if (!defined('WP_UNINSTALL_PLUGIN')) { exit(); }

require_once plugin_dir_path(__FILE__) . 'buoy.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-buoy-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-buoy-user-settings.php';

$my_prefix = WP_Buoy_Plugin::$prefix;
$post_types = array(
    "{$my_prefix}_alert",
    "{$my_prefix}_team"
);
$posts = get_posts(array(
    'post_type' => $post_types,
    'post_status' => get_post_stati(),
    'posts_per_page' => -1
));
foreach ($posts as $post) {
    wp_delete_post($post->ID, true);
}

// Delete plugin options.
delete_option(WP_Buoy_Settings::get_instance()->get_meta_key());

foreach (get_users() as $usr) {
    // Delete all custom user profile data.
    $usropt = new WP_Buoy_User_Settings($usr);
    foreach ($usropt->default as $k => $v) {
        $usropt->delete($k);
    }
    $usropt->save();
}
