<?php
/**
 * Web App Manifest file.
 *
 * This file is generates the Web App manifest used by some browsers
 * like Chrome for providing standalone webapp integration with the
 * mobile device operating system (Android).
 *
 * @link https://www.w3.org/TR/appmanifest/ W3C Web App Manifest specification
 * @link https://developer.mozilla.org/en-US/docs/Web/Manifest MDN documentation
 * @link https://developer.chrome.com/multidevice/android/installtohomescreen Chrome "Install to homescreen" documentation.
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * @copyright Copyright (c) 2016 by Meitar "maymay" Moscovitz
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin
 */

header('Content-Type: application/manifest+json');

$user = wp_get_current_user();
$manifest = new stdClass();

$manifest->lang = str_replace('_', '-', get_locale());
$manifest->name = __('Buoy: Activate Alert', 'buoy');
$manifest->short_name = __('Buoy', 'buoy');
$manifest->start_url = admin_url('index.php?page=' . self::$prefix . '_activate_alert');
$manifest->display = 'standalone';
$manifest->orientation = 'portrait';
// TODO: Icons
//$manifest->icons = array();
$manifest->splash_screens = array(
    array(
        'src' => plugins_url('img/apple-touch-icon-152x152.png', __FILE__)
    )
);
$manifest->theme_color = $user->admin_color;
// TODO: Related applications, this is for opening a native app, when we have one.
//$manifest->prefer_related_applications = true;
//$manifest->related_applications = array();

print json_encode($manifest);
