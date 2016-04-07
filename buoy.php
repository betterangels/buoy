<?php
/**
 * The Buoy plugin for WordPress.
 *
 * WordPress plugin header information:
 *
 * * Plugin Name: Buoy (a Better Angels crisis response system)
 * * Plugin URI: https://github.com/meitar/better-angels
 * * Description: A community-based crisis response system. <strong>Like this plugin? Please <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=TJLPJYXHSRBEE&amp;lc=US&amp;item_name=Better%20Angels&amp;item_number=better-angels&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted" title="Send a donation to the developer of Better Angels Buoy">donate</a>. &hearts; Thank you!</strong>
 * * Version: 0.1.2
 * * Author: Maymay <bitetheappleback@gmail.com>
 * * Author URI: https://maymay.net/
 * * License: GPL-3
 * * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 * * Text Domain: buoy
 * * Domain Path: /languages
 *
 * @link https://developer.wordpress.org/plugins/the-basics/header-requirements/
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * @copyright Copyright (c) 2015-2016 by Meitar "maymay" Moscovitz
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin
 */

if (!defined('ABSPATH')) { exit; } // Disallow direct HTTP access.

if (!defined('WP_BUOY_MIN_PHP_VERSION')) {
    /**
     * The minimum version of PHP needed to run the plugin.
     *
     * This is explicit because WordPress supports even older versions
     * of PHP, so we check the running version on plugin activation.
     *
     * We need PHP 5.3 or later since WP_Buoy_Plugin::error_msg() uses
     * late static binding to get caller information in child classes.
     *
     * @link https://secure.php.net/manual/en/language.oop5.late-static-bindings.php
     */
    define('WP_BUOY_MIN_PHP_VERSION', '5.3');
}

/**
 * Base class that WordPress uses to register and initialize plugin.
 */
class WP_Buoy_Plugin {

    /**
     * @var string $prefix String to prefix option names, settings, etc.
     *
     * @access public
     */
    public static $prefix = 'buoy';

    /**
     * Entry point for the WordPress framework into plugin code.
     *
     * This is the method called when WordPress loads the plugin file.
     * It is responsible for "registering" the plugin's main functions
     * with the {@see https://codex.wordpress.org/Plugin_API WordPress Plugin API}.
     *
     * @uses add_action()
     * @uses register_activation_hook()
     * @uses register_deactivation_hook()
     *
     * @return void
     */
    public static function register () {
        add_action('plugins_loaded', array(__CLASS__, 'registerL10n'));
        add_action('init', array(__CLASS__, 'initialize'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueueFrontEndScripts'));
        add_action('admin_head', array(__CLASS__, 'addHelpSidebar'));
        add_action('admin_head-dashboard_page_' . self::$prefix . '_activate_alert', array(__CLASS__, 'renderWebAppHTML'));

        add_action('wp_ajax_nopriv_' . self::$prefix . '_webapp_manifest', array(__CLASS__, 'renderWebAppManifest'));

        register_activation_hook(__FILE__, array(__CLASS__, 'activate'));
        register_deactivation_hook(__FILE__, array(__CLASS__, 'deactivate'));
    }

    /**
     * Loads localization files from plugin's languages directory.
     *
     * @uses load_plugin_textdomain()
     *
     * @return void
     */
    public static function registerL10n () {
        load_plugin_textdomain('buoy', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * Loads plugin componentry and calls that component's register()
     * method. Called at the WordPress `init` hook.
     *
     * @uses WP_Buoy_Settings::register()
     * @uses WP_Buoy_Team::register()
     * @uses WP_Buoy_Notification::register()
     * @uses WP_Buoy_User::register()
     * @uses WP_Buoy_Alert::register()
     *
     * @return void
     */
    public static function initialize () {
        require_once 'class-buoy-settings.php';
        require_once 'class-buoy-user-settings.php';
        require_once 'class-buoy-team.php';
        require_once 'class-buoy-notification.php';
        require_once 'class-buoy-user.php';
        require_once 'class-buoy-alert.php';

        if (!class_exists('WP_Screen_Help_Loader')) {
            require_once 'includes/vendor/wp-screen-help-loader/class-wp-screen-help-loader.php';
        }

        WP_Buoy_Settings::register();
        WP_Buoy_Team::register();
        WP_Buoy_Notification::register();
        WP_Buoy_User::register();
        WP_Buoy_Alert::register();
    }

    /**
     * Method to run when the plugin is activated by a user in the
     * WordPress Dashboard admin screen.
     *
     * @link https://developer.wordpress.org/reference/hooks/activate_plugin/
     *
     * @uses WP_Buoy_Plugin::checkPrereqs()
     * @uses WP_Buoy_Settings::activate()
     *
     * @param bool $network_wide
     *
     * @return void
     */
    public static function activate ($network_wide) {
        self::checkPrereqs();

        require_once 'class-buoy-settings.php';
        WP_Buoy_Settings::get_instance()->activate($network_wide);

        // TODO: Remove this after enough migrations.
        require_once 'class-buoy-user-settings.php';
        require_once 'class-buoy-team.php';
        self::migrateDefaultTeamSettings();
    }

    /**
     * Checks system requirements and exits if they are not met.
     *
     * This first checks to ensure minimum WordPress and PHP versions
     * have been satisfied. If not, the plugin deactivates and exits.
     *
     * @global $wp_version
     *
     * @uses $wp_version
     * @uses WP_BUOY_MIN_PHP_VERSION
     * @uses WP_Buoy_Plugin::get_minimum_wordpress_version()
     * @uses deactivate_plugins()
     * @uses plugin_basename()
     *
     * @return void
     */
    public static function checkPrereqs () {
        global $wp_version;
        $min_wp_version = self::get_minimum_wordpress_version();

        if (version_compare(WP_BUOY_MIN_PHP_VERSION, PHP_VERSION) > 0) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(sprintf(
                __('Buoy requires at least PHP version %1$s. You have PHP version %2$s.', 'buoy'),
                WP_BUOY_MIN_PHP_VERSION, PHP_VERSION
            ));
        }
        if (version_compare($min_wp_version, $wp_version) > 0) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(sprintf(
                __('Buoy requires at least WordPress version %1$s. You have WordPress version %2$s.', 'buoy'),
                $min_wp_version, $wp_version
            ));
        }
    }

    /**
     * Updates old "default team" settings.
     *
     * This automatically moves the new "default team" internal data
     * to the right places. Should be safe to remove after a few updates.
     *
     * @since 0.1.3
     *
     * @ignore This is purely a migration function, do not include in API docs.
     */
    public static function migrateDefaultTeamSettings () {
        foreach (get_users() as $usr) {
            $usropt = new WP_Buoy_User_Settings($usr);
            $old_default = $usropt->get('default_team');
            if ($old_default) {
                $team = new WP_Buoy_Team($old_default);
                $team->set_default();
                $usropt
                    ->delete('default_team')
                    ->save();
            }
        }
    }

    /**
     * Returns the "Requires at least" value from plugin's readme.txt.
     *
     * @link https://wordpress.org/plugins/about/readme.txt WordPress readme.txt standard
     *
     * @return string
     */
    public static function get_minimum_wordpress_version () {
        $lines = @file(plugin_dir_path(__FILE__) . 'readme.txt');
        foreach ($lines as $line) {
            preg_match('/^Requires at least: ([0-9.]+)$/', $line, $m);
            if ($m) {
                return $m[1];
            }
        }
    }

    /**
     * Method to run when the plugin is deactivated by a user in the
     * WordPress Dashboard admin screen.
     *
     * @uses WP_Buoy_Settings::deactivate()
     *
     * @return void
     */
    public static function deactivate () {
        WP_Buoy_Settings::get_instance()->deactivate();
    }

    /**
     * Enqueues globally relevant scripts and stylesheets.
     *
     * @link https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/
     *
     * @return void
     */
    public static function enqueueFrontEndScripts () {
        $plugin_data = get_plugin_data(plugin_dir_path(__FILE__) . self::$prefix . '.php');
        wp_enqueue_style(
            self::$prefix . '-style',
            plugins_url(self::$prefix . '.css', __FILE__),
            array(),
            $plugin_data['Version']
        );

        // Always enqueue this script to ensure iOS Webapp-style launches
        // remain within the webapp capable shell. Otherwise, navigating
        // to a page outside "our app" (like the WP profile page) will make
        // any subsequent navigation return to the built-in iOS Mobile Safari
        // browser, which is a confusing user experience for a user who has
        // "installed" Buoy.
        wp_enqueue_script(
            self::$prefix . '-stay-standalone',
            plugins_url('includes/stay-standalone.js', __FILE__),
            array(),
            $plugin_data['Version']
        );
    }

    /**
     * Attaches on-screen help tabs to the WordPress built-in help.
     *
     * Loads the appropriate document from the localized `help` folder
     * and inserts it as a help tab on the current screen.
     *
     * @uses WP_Screen_Help_Loader::applyTabs()
     *
     * @return void
     */
    public static function addHelpTab () {
        $help = new WP_Screen_Help_Loader(plugin_dir_path(__FILE__) . 'help');
        $help->applyTabs();
    }

    /**
     * Appends appropriate sidebar content based on current screen.
     *
     * @uses WP_Screen_Help_Loader::applySidebar()
     *
     * @return void
     */
    public static function addHelpSidebar () {
        $help = new WP_Screen_Help_Loader(plugin_dir_path(__FILE__) . 'help');
        $help->applySidebar();
    }

    /**
     * Prints the Web App manifest file.
     *
     * @link https://developer.wordpress.org/reference/hooks/wp_ajax_nopriv__requestaction/
     *
     * @return void
     */
    public static function renderWebAppManifest () {
        require_once 'pages/manifest.json.php';
        exit();
    }

    /**
     * Prints meta tag indicators for native-like functionality.
     *
     * The "activate alert" screen is intended to be the web app "install"
     * screen for Buoy. We insert special mobile browser specific tags in
     * order to create a native-like "installer" for the user. We only want
     * to do this on this specific screen.
     *
     * @return void
     */
    public static function renderWebAppHTML () {
        // Android/Chrome
        print '<meta name="mobile-web-app-capable" content="yes" />';
        print '<link rel="manifest" href="' . admin_url('admin-ajax.php?action=' . self::$prefix . '_webapp_manifest') . '" />';

        // Apple/Safari
        print '<meta name="apple-mobile-web-app-capable" content="yes" />';
        print '<meta name="apple-mobile-web-app-status-bar-style" content="black" />';
        print '<meta name="apple-mobile-web-app-title" content="' . esc_attr('Buoy', 'buoy') . '" />';
        print '<link rel="apple-touch-icon" href="' . plugins_url('img/apple-touch-icon-152x152.png', __FILE__) . '" />';
        // TODO: This isn't showing up, figure out why.
        //print '<link rel="apple-touch-startup-image" href="' . plugins_url('img/apple-touch-startup.png', __FILE__) . '">';
    }


    /**
     * Prepares an error message for logging.
     *
     * @param string $message
     *
     * @return string
     */
    private static function error_msg ($message) {
        $dbt = debug_backtrace();
        // the "2" is so we get the name of the function that originally called debug_log()
        // This works so long as error_msg() is always called by debug_log()
        return '[' . get_called_class() . '::' . $dbt[2]['function'] . '()]: ' . $message;
    }

    /**
     * Prints a message to the WordPress debug log if the plugin's
     * "detailed debugging" setting is enabled.
     *
     * By default, the WordPress debug log is `wp-content/debug.log`
     * relative to the WordPress installation root (`ABSPATH`).
     *
     * @link https://codex.wordpress.org/Debugging_in_WordPress
     *
     * @uses WP_Buoy_Settings::get()
     *
     * @param string $message
     *
     * @return void
     */
    protected static function debug_log ($message) {
        if (WP_Buoy_Settings::get_instance()->get('debug')) {
            error_log(static::error_msg($message));
        }
    }

}

WP_Buoy_Plugin::register();
