<?php
/**
 * Buoy Helper
 *
 * Class containing helper methods.
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\Helper
 *
 * @copyright Copyright (c) 2016 by Meitar "maymay" Moscovitz
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

if (!defined('ABSPATH')) { exit; } // Disallow direct HTTP access.

/**
 * Helper class.
 */
class WP_Buoy_Helper {

    /**
     * Looks for locally installed plugins and suggests additional ones.
     *
     * @uses get_plugins()
     * @uses is_plugin_active()
     *
     * @return array Two-dimensional array whose keys are the plugin's status and values are the plugin slug to suggest.
     */
    public static function checkLocalPluginStatus () {
        $slugs = array(
            'wp-pgp-encrypted-emails/wp-pgp-encrypted-emails.php',
        );

        global $wp_version;

        if (version_compare('4.5', $wp_version) > 0) {
            $slugs[] = 'rest-api/plugin.php';
        }

        // Suggest the following plugins if we are running WordPress
        // versions earlier than 4.7.
        if (version_compare('4.7', $wp_version) === 1) {
            $slugs[] = 'admin-language-per-user/admin-language-per-user.php';
        }

        $plugins  = get_plugins();
        $missing  = array();
        $disabled = array();
        $active   = array();
        foreach ($slugs as $slug) {
            if (!isset($plugins[$slug])) {
                $missing[$slug] = $slug;
            } else if (!is_plugin_active($slug)) {
                $disabled[$slug] = $plugins[$slug];
            } else {
                $active[$slug] = $plugins[$slug];
            }
        }
        return array('missing' => $missing, 'disabled' => $disabled, 'active' => $active);
    }

    /**
     * Gets the URL to a plugin on the WordPress Plugin Directory.
     *
     * @param string $slug
     *
     * @return string
     */
    public static function getPluginDirectoryUrlBySlug ($slug) {
        $url = 'https://wordpress.org/plugins/';
        $parts = explode('/', $slug);
        return trailingslashit($url.array_shift($parts));
    }

    /**
     * Gets the URL to a local plugin installation page.
     *
     * @param string $slug
     *
     * @return string
     */
    public static function getPluginInstallUrlBySlug ($slug) {
        $parts = explode('/', $slug);
        $slug = array_shift($parts);
        return self::maybe_network_admin_url("plugin-install.php?tab=plugin-information&plugin=$slug");
    }

    /**
     * Returns a local plugin search URL.
     *
     * @param string $search
     */
    public static function getPluginSearchUrl ($search) {
        return self::maybe_network_admin_url('plugins.php?s='.urlencode($search));
    }

    /**
     * Returns the appropriate admin URL based on user privilege.
     *
     * @return string
     */
    public static function maybe_network_admin_url ($url) {
        $func = '';
        if (is_multisite() && self::hasMenuItem('plugins') && !is_super_admin(get_current_user_id())) {
            $func = 'admin_url';
        } else {
            $func = 'network_admin_url';
        }
        return $func($url);
    }

    /**
     * Whether or not a given menu item is available to site admins.
     *
     * This is only useful for checking whether the `plugins` menu item
     * exists at the moment because that's the only one that can be
     * toggled on a multisite network.
     *
     * @param string $item
     *
     * @return bool
     */
    private static function hasMenuItem ($item) {
        return array_key_exists($item, get_site_option('menu_items'));
    }

}
