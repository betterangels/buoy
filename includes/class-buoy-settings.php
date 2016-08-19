<?php
/**
 * Buoy Alert.
 *
 * A Buoy Alert may also be referred to as an "incident" depending on
 * context.
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\Settings
 *
 * @copyright Copyright (c) 2015-2016 by Meitar "maymay" Moscovitz
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

if (!defined('ABSPATH')) { exit; } // Disallow direct HTTP access.

/**
 * Handles plugin-wide options.
 *
 * This is distinct from user-specific options.
 */
class WP_Buoy_Settings {

    /**
     * Singleton.
     *
     * @var WP_Buoy_Settings
     */
    private static $instance;

    /**
     * Database meta key name.
     *
     * @var string
     */
    private $meta_key;

    /**
     * Current settings.
     *
     * @var array
     */
    private $options;

    /**
     * List of default values for settings.
     *
     * @var array
     */
    private $defaults;

    /**
     * Constructor.
     *
     * @return WP_Buoy_Settings
     */
    private function __construct () {
        $this->meta_key = WP_Buoy_Plugin::$prefix . '_settings';
        $this->options  = $this->get_options();
        $this->defaults  = array(
            'alert_ttl_num' => 2,
            'alert_ttl_multiplier' => DAY_IN_SECONDS,
            'safety_info' => file_get_contents(plugin_dir_path(__FILE__) . 'default-safety-information.html'),
            'chat_system' => 'post_comments',
            'future_alerts' => (function_exists('posix_getpwuid')) ? true : false,
            'delete_old_incident_media' => false,
            'debug' => false
        );
    }

    /**
     * Gets the plugin options from the WordPress database.
     *
     * @uses get_option()
     *
     * @return mixed
     */
    private function get_options () {
        return get_option($this->meta_key, null);
    }

    /**
     * Gets a default setting value.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get_defaults ($key = null) {
        return (null === $key) ? $this->defaults : $this->defaults[$key];
    }

    /**
     * Gets the name of the meta key in which options are stored.
     *
     * @return string
     */
    public function get_meta_key () {
        return $this->meta_key;
    }

    /**
     * Saves default plugin options to the database when the plugin
     * is activated by a user without overwriting existing values.
     * 
     * @uses is_multisite()
     * @uses get_sites()
     * @uses get_current_blog_id()
     * @uses switch_to_blog()
     * @uses WP_Buoy_Settings::activateSite()
     * @uses restore_current_blog()
     *
     * @param bool $network_wide
     *
     * @return void
     */
    public function activate ($network_wide) {
        $sites = (is_multisite() && $network_wide) ? get_sites() : array((object) array('blog_id' => get_current_blog_id()));
        foreach ($sites as $site) {
            $restore = false;
            if (get_current_blog_id() != $site->blog_id) {
                $restore = true;
                switch_to_blog($site->blog_id);
            }
            $this->activateSite();
            if ($restore) {
                restore_current_blog();
            }
        }
    }

    /**
     * Sets up Buoy settings for a site.
     *
     * @return void
     */
    private function activateSite () {
        foreach ($this->defaults as $k => $v) {
            if (!$this->has($k)) {
                $this->set($k, $v);
            }
        }
        $this->save();
        $this->updateSchedules(
            '',
            self::get_alert_ttl_string($this->get('alert_ttl_num'), $this->get('alert_ttl_multiplier'))
        );
    }

    /**
     * Turns Buoy functionality off for this site.
     *
     * @uses wp_clear_scheduled_hook()
     *
     * @return void
     */
    public static function deactivate () {
        $options = self::get_instance();
        do_action(WP_Buoy_Plugin::$prefix . '_delete_old_alerts');
        wp_clear_scheduled_hook(WP_Buoy_Plugin::$prefix . '_delete_old_alerts');       // clear hook with no args
        wp_clear_scheduled_hook(WP_Buoy_Plugin::$prefix . '_delete_old_alerts', array( // and also with explicit args
            self::get_alert_ttl_string($options->get('alert_ttl_num'), $options->get('alert_ttl_multiplier'))
        ));
    }

    /**
     * Gets an alert time-to-live string.
     *
     * This is used to create a `strtotime()`-compatible string from
     * the plugin's settings.
     *
     * @param int $num
     * @param int $multiplier
     * @param bool $past
     *
     * @return string
     */
    private static function get_alert_ttl_string ($num, $multiplier, $past = true) {
        $str = intval($num) . ' ' . self::time_multiplier_to_unit($multiplier);
        return ($past) ? '-' . $str : $str;
    }

    /**
     * Converts a number of seconds to that value's English time unit.
     *
     * @uses HOUR_IN_SECONDS
     * @uses WEEK_IN_SECONDS
     * @uses DAY_IN_SECONDS
     *
     * @param int $num
     *
     * @return string
     */
    private static function time_multiplier_to_unit ($num) {
        switch ($num) {
            case HOUR_IN_SECONDS:
                return 'hours';
            case WEEK_IN_SECONDS:
                return 'weeks';
            case DAY_IN_SECONDS:
            default:
                return 'days';
        }
    }

    /**
     * Resets WP-Cron scheduled tasks.
     *
     * @uses wp_next_scheduled()
     * @uses wp_clear_scheduled_hook()
     * @uses wp_schedule_event()
     *
     * @param string $old_str
     * @param string $new_str
     *
     * @return void
     */
    private function updateSchedules ($old_str, $new_str) {
        if (wp_next_scheduled(WP_Buoy_Plugin::$prefix . '_delete_old_alerts', array($old_str))) {
            wp_clear_scheduled_hook(WP_Buoy_Plugin::$prefix . '_delete_old_alerts', array($old_str));
        }
        wp_schedule_event(
            time() + HOUR_IN_SECONDS,
            'hourly',
            WP_Buoy_Plugin::$prefix . '_delete_old_alerts',
            array($new_str)
        );
    }

    /**
     * Deletes alerts older than a certain threshold.
     *
     * Also deletes their child posts (media attachments) if that
     * option is enabled.
     *
     * @todo Should this actually be part of the Alert class?
     *
     * @param string $threshold A `strtotime()`-compatible string indicating some time in the past.
     *
     * @return void
     */
    public static function deleteOldAlerts ($threshold) {
        $options = self::get_instance();
        $threshold = empty($threshold)
            ? self::get_alert_ttl_string($options->get('alert_ttl_num'), $options->get('alert_ttl_multiplier'))
            : $threshold;
        $wp_query_args = array(
            'post_type' => WP_Buoy_Plugin::$prefix . '_alert',
            'posts_per_page' => -1,
            'date_query' => array(
                'column' => 'post_date',
                'before' => $threshold,
                'inclusive' => true
            ),
            'fields' => 'ids'
        );
        $query = new WP_Query($wp_query_args);
        foreach ($query->posts as $post_id) {
            $attached_posts_by_type = array();
            $types = array('image', 'audio', 'video');
            $delete_media = $options->get('delete_old_incident_media');
            if (!empty($delete_media)) {
                foreach ($types as $type) {
                    $attached_posts_by_type[$type] = get_attached_media($type, $post_id);
                }
                foreach ($attached_posts_by_type as $type => $posts) {
                    foreach ($posts as $post) {
                        if (!wp_delete_post($post->ID, true)) {
                            error_log(sprintf(
                                __('Failed to delete attachment post %1$s (child of %2$s) during %3$s', 'buoy'),
                                $post->ID,
                                $post_id,
                                __FUNCTION__ . '()'
                            ));
                        }
                    }
                }
            }
            if (!wp_delete_post($post_id, true)) {
                error_log(sprintf(
                    __('Failed to delete post with ID %1$s during %2$s', 'buoy'),
                    $post_id,
                    __FUNCTION__ . '()'
                ));
            }
        }
    }

    /**
     * Registers WordPress hooks.
     *
     * @return void
     */
    public static function register () {
        add_action('admin_init', array(__CLASS__, 'configureCron'));
        add_action('admin_init', array(__CLASS__, 'registerSettings'));
        add_action('admin_menu', array(__CLASS__, 'registerAdminMenu'));

        add_action(WP_Buoy_Plugin::$prefix . '_delete_old_alerts', array(__CLASS__, 'deleteOldAlerts'));

        add_action('update_option_' . WP_Buoy_Plugin::$prefix . '_settings', array(__CLASS__, 'updatedsettings'), 10, 2);
    }

    /**
     * Updates WordPress settings based on changed plugin settings.
     *
     * @link https://developer.wordpress.org/reference/hooks/update_option_option/
     *
     * @param array $old_value
     * @param array $new
     *
     * @return void
     */
    public static function updatedSettings ($old_value, $value) {
        self::get_instance()->updateSchedules(
            self::get_alert_ttl_string($old_value['alert_ttl_num'], $old_value['alert_ttl_multiplier']),
            self::get_alert_ttl_string($value['alert_ttl_num'], $value['alert_ttl_multiplier'])
        );
    }

    /**
     * Gets the instance of this object.
     *
     * @return WP_Buoy_Settings
     */
    public static function get_instance () {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Gets an option.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get ($key, $default = null) {
        return ($this->has($key)) ? $this->options[$key] : $default;
    }

    /**
     * Sets an option name and value pair.
     *
     * @return WP_Buoy_Settings
     */
    public function set ($key, $value) {
        $this->options[$key] = $value;
        return $this;
    }

    /**
     * Checks whether or not an option is set.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has ($key) {
        return isset($this->options[$key]);
    }

    /**
     * Saves current options to the database.
     *
     * @return WP_Buoy_Settings
     */
    public function save () {
        update_option($this->meta_key, $this->options);
        return $this;
    }

    /**
     * Deletes an option.
     *
     * @return WP_Buoy_Settings
     */
    public function delete ($key) {
        unset($this->options[$key]);
        return $this;
    }

    /**
     * Registers the plugin setting with WordPress. All the plugin's
     * options are stored in a serialized array. This means there is
     * only one record in the WordPress options table in the database
     * to record all the plugin's settings.
     *
     * @link https://codex.wordpress.org/Settings_API
     *
     * @return void
     */
    public static function registerSettings () {
        register_setting(
            WP_Buoy_Plugin::$prefix . '_settings',
            WP_Buoy_Plugin::$prefix . '_settings',
            array(__CLASS__, 'validateSettings')
        );
    }

    /**
     * Writes or removes the plugin's cron jobs in the OS-level cron.
     *
     * @return void
     */
    public static function configureCron () {
        // Don't do anything if we're not a POSIX system.
        if (!function_exists('posix_getpwuid')) {
            return;
        }
        require_once plugin_dir_path(__FILE__) . 'crontab-manager.php';

        $C = new Buoy_Crontab_Manager();
        $path_to_wp_cron = ABSPATH . 'wp-cron.php';
        $os_cronjob_comment = '# Buoy WordPress Plugin Cronjob';
        $job = '*/5 * * * * php ' . $path_to_wp_cron . ' >/dev/null 2>&1 ' . $os_cronjob_comment;

        $options = self::get_instance();
        if ($options->get('future_alerts')) {
            if (!$C->jobExists($path_to_wp_cron)) {
                try {
                    $C->appendCronJobs($job)->save();
                } catch (Exception $e) {
                    error_log(
                        __('Error installing system cronjob for timed alerts.', 'buoy')
                        . PHP_EOL . $e->getMessage()
                    );
                }
            }
        } else {
            if ($C->jobExists($path_to_wp_cron)) {
                try {
                    $C->removeCronJobs("/$os_cronjob_comment/")->save();
                } catch (Exception $e) {
                    error_log(
                        __('Error removing system crontab jobs for timed alerts.', 'buoy')
                        . PHP_EOL . $e->getMessage()
                    );
                }
            }
        }
    }

    /**
     * WordPress validation callback for the Settings API hook.
     *
     * @link https://codex.wordpress.org/Settings_API
     *
     * @param array $input
     *
     * @return array
     */
    public static function validateSettings ($input) {
        // TODO: Refactor this, maybe can do better since the array
        //       of valid options are all in the self::default var.
        $options = self::get_instance();
        $safe_input = array();
        foreach ($input as $k => $v) {
            switch ($k) {
                case 'safety_info':
                    $safe_input[$k] = force_balance_tags($v);
                    break;
                case 'alert_ttl_num':
                    if ($v > 0) {
                        $safe_input[$k] = intval($v);
                    } else {
                        $safe_input[$k] = $options->get_defaults('alert_ttl_num');
                    }
                    break;
                case 'chat_system':
                    $safe_input[$k] = sanitize_text_field($v);
                    break;
                case 'future_alerts':
                    if (function_exists('posix_getpwuid')) {
                        $safe_input[$k] = intval($v);
                    }
                    break;
                case 'alert_ttl_multiplier':
                case 'delete_old_incident_media':
                case 'debug':
                    $safe_input[$k] = intval($v);
                    break;
            }
        }
        return $safe_input;
    }

    /**
     * Adds items to the Dashboard Admin menu.
     *
     * @uses WP_Buoy_Plugin::addHelpTab()
     *
     * @link https://developer.wordpress.org/reference/hooks/menu_order/
     *
     * @return void
     */
    public static function registerAdminMenu () {
        $hooks = array();

        $hooks[] = add_options_page(
            __('Buoy Settings', 'buoy'),
            __('Buoy', 'buoy'),
            'manage_options',
            WP_Buoy_Plugin::$prefix . '_settings',
            array(__CLASS__, 'renderOptionsPage')
        );

        $hooks[] = add_submenu_page(
            'edit.php?post_type=' . WP_Buoy_Plugin::$prefix . '_team',
            __('Safety information', 'buoy'),
            __('Safety information', 'buoy'),
            'read',
            WP_Buoy_Plugin::$prefix . '_safety_info',
            array(__CLASS__, 'renderSafetyInfoPage')
        );

        foreach ($hooks as $hook) {
            add_action('load-' . $hook, array('WP_Buoy_Plugin', 'addHelpTab'));
        }

        add_filter('custom_menu_order', '__return_true');
        add_filter('menu_order', array(__CLASS__, 'reorderSubmenu'));
    }

    /**
     * Ensure "Safety information" is last in "My Teams" submenu list.
     *
     * @global array $submenu
     *
     * @param array $menu_order The top-level admin menu order. Returned unchanged.
     *
     * @return array
     */
    public static function reorderSubmenu ($menu_order) {
        global $submenu;
        $find = 'edit.php?post_type=' . WP_Buoy_Plugin::$prefix . '_team';
        if (isset($submenu[$find])) {
            foreach ($submenu[$find] as $k => $v) {
                if (in_array(WP_Buoy_Plugin::$prefix . '_safety_info', $v)) {
                    unset($submenu[$find][$k]);
                    $submenu[$find][9999] = $v;
                }
            }
        }
        return $menu_order;
    }

    /**
     * Displays the admin options page.
     *
     * @return void
     */
    public static function renderOptionsPage () {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'buoy'));
        }
        require plugin_dir_path(dirname(__FILE__)).'/pages/options.php';
    }

    /**
     * Displays the "Safety Info" page.
     *
     * @return void
     */
    public static function renderSafetyInfoPage () {
        if (!current_user_can('read')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'better-angels'));
        }
        $options = self::get_instance();
        print $options->get('safety_info'); // TODO: Can we harden this against XSS more?
    }

}
