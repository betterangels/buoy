<?php
/**
 * Buoy Admin Dashboard Widget plugin check
 *
 * @link https://codex.wordpress.org/Dashboard_Widgets_API
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * @copyright Copyright (c) 2016 by Meitar "maymay" Moscovitz
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin
 */

if (!defined('ABSPATH')) { exit; } // Disallow direct HTTP access.

if (!current_user_can('activate_plugins')) {
    return;
}

//
// Print the "Supplemental plugins" status report.
//
foreach (WP_Buoy_Helper::checkLocalPluginStatus() as $status => $plugins) :
    if ('missing' === $status && !empty($plugins) && current_user_can('install_plugins')) {
?>
<h3><?php esc_html_e('Missing supplemental plugins:', 'buoy');?></h3>
<ul>
<?php foreach ($plugins as $slug => $plugin) {
    $name = explode('/', $slug);
    $name = ucwords(str_replace('-', ' ', array_shift($name)));
?>
    <li>
        <a href="<?php print esc_url(WP_Buoy_Helper::getPluginInstallUrlBySlug($slug));?>"><?php print esc_html($name);?></a>
    </li>
<?php } ?>
</ul>
<p class="description"><?php esc_html_e('These recommended plugins were not found. Consider installing them.', 'buoy');?></p>
<?php
    } else if ('disabled' === $status && !empty($plugins)) {
?>
<h3><?php esc_html_e('Disabled supplemental plugins:', 'buoy');?></h3>
<ul>
<?php foreach ($plugins as $slug => $plugin) { ?>
    <li>
        <a href="<?php print esc_url(WP_Buoy_Helper::getPluginSearchUrl($plugin['Name']));?>"><?php print esc_html($plugin['Name']);?></a>
    </li>
<?php } ?>
</ul>
<p class="description"><?php esc_html_e('These recommended plugins are installed but disabled. Consider activating them.', 'buoy');?></p>
<?php
    } else if ('active' === $status && !empty($plugins)) {
?>
<h3><?php esc_html_e('Active supplemental plugins:', 'buoy');?></h3>
<ul>
<?php foreach ($plugins as $slug => $plugin) { ?>
    <li>
        <a href="<?php print esc_url(WP_Buoy_Helper::getPluginDirectoryUrlBySlug($slug));?>"><?php print esc_html($plugin['Name']);?></a>
    </li>
<?php } ?>
</ul>
<?php
    }
endforeach;
?>
<p class="description"><?php esc_html_e('Supplemental plugins are recommended components that enhance the usability and functionality of your Buoy.', 'buoy');?></p>

