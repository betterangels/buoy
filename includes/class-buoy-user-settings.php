<?php
/**
 * Buoy User Settings
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\WP_Buoy_User\WP_Buoy_User_Settings
 *
 * @copyright Copyright (c) 2015-2016 by Meitar "maymay" Moscovitz
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

if (!defined('ABSPATH')) { exit; } // Disallow direct HTTP access.

/**
 * Manages user-specific settings for a given user.
 */
class WP_Buoy_User_Settings {

    /**
     * The user associated with these settings.
     *
     * @var WP_User
     */
    private $user;

    /**
     * User's current settings.
     *
     * @var array
     */
    private $options;

    /**
     * List of default values for user profile settings.
     *
     * @todo Possible values should not be part of default values.
     * @todo Remove the `default_team` value after next version migration.
     *
     * @var array
     */
    private $defaults = array(
        // option name              => default/possible values
        'crisis_message'            => '',
        'default_team'              => false,
        'gender_pronoun_possessive' => '',
        'installer_dismissed'       => false,
        'phone_number'              => '',
        'public_responder'          => false,
        'sms_provider'              => array(),
    );

    /**
     * Constructor.
     *
     * @param int|WP_User $user
     *
     * @return WP_Buoy_User_Settings
     */
    public function __construct ($user) {
        if (is_numeric($user)) {
            $user = get_userdata($user);
        }
        $this->user = $user;
        $this->defaults['sms_provider'] = array_merge(array(''), WP_Buoy_SMS_Email_Bridge::getSmsProviders());
        $this->options = $this->get_options();
    }

    /**
     * Gets a user setting default, or all defaults.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get_defaults ($key = null) {
        return (null === $key) ? $this->defaults : $this->defaults[$key];
    }

    /**
     * Retrieves user's Buoy settings from the WordPress database.
     *
     * @return array
     */
    private function get_options () {
        $opts = array();
        foreach ($this->defaults as $k => $v) {
            $opts[$k] = get_user_meta($this->user->ID, WP_Buoy_Plugin::$prefix . '_' . $k, true);
        }
        return $opts;
    }

    /**
     * Retrieves a user option.
     *
     * @param string $key
     * @param mixed $default The value to return if the option doesn't exist.
     *
     * @return mixed The current option value, or the $default parameter if the option doesn't exist.
     */
    public function get ($key, $default = null) {
        return ($this->has($key)) ? $this->options[$key] : $default;
    }

    /**
     * Sets a user option.
     *
     * Returns the current instance for chaining.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return WP_Buoy_User_Settings
     */
    public function set ($key, $value) {
        $this->options[$key] = $value;
        return $this;
    }

    /**
     * Checks whether a given option name is set.
     *
     * @param string $key The option name.
     *
     * @return bool
     */
    public function has ($key) {
        return isset($this->options[$key]);
    }

    /**
     * Saves the current options to the database in user meta fields.
     *
     * If an option doesn't exist in the $options array, after it was
     * deleted with WP_Buoy_User_Settings::delete(), for instance, it
     * will be deleted from the database, too.
     *
     * Multiple user meta fields are used so that the WP database is
     * more easily queryable to allow, for example, finding all users
     * whose phone company is Verizon. This aids in debugging issues.
     *
     * @uses WP_Buoy_User_Settings::$options
     *
     * @return WP_Buoy_User_Settings
     */
    public function save () {
        foreach ($this->defaults as $k => $v) {
            if ($this->has($k)) {
                update_user_meta($this->user->ID, WP_Buoy_Plugin::$prefix . '_' . $k, $this->get($k));
            } else {
                delete_user_meta($this->user->ID, WP_Buoy_Plugin::$prefix . '_' . $k);
            }
        }
        return $this;
    }

    /**
     * Removes an option from the current instance's $options array.
     *
     * Returns the current instance for chaining.
     *
     * @param string $key
     *
     * @return WP_Buoy_User_Settings
     */
    public function delete ($key) {
        unset($this->options[$key]);
        return $this;
    }

}
