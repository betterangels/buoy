<?php
/**
 * Plugin Name: Better Angels first responder system
 * Plugin URI: https://github.com/meitar/better-angels
 * Description: A community-driven emergency first responder system. <strong>Like this plugin? Please <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=TJLPJYXHSRBEE&amp;lc=US&amp;item_name=Better%20Angels&amp;item_number=better-angels&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted" title="Send a donation to the developer of Better Angels">donate</a>. &hearts; Thank you!</strong>
 * Version: 0.1
 * Author: Maymay <bitetheappleback@gmail.com>
 * Author URI: http://maymay.net/
 * Text Domain: better-angels
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) { exit; } // Disallow direct HTTP access.

class BetterAngelsPlugin {

    private $prefix = 'better-angels_'; //< Internal prefix for settings, etc., derived from shortcode.

    public function __construct () {

        add_action('plugins_loaded', array($this, 'registerL10n'));
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('admin_menu', array($this, 'registerAdminMenu'));
        add_action('admin_head', array($this, 'doAdminHeadActions'));

        add_filter('user_contactmethods', array($this, 'addEmergencyPhoneContactMethod'));

        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate () {
    }

    public function registerL10n () {
        load_plugin_textdomain('better-angels', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function registerSettings () {
        register_setting(
            $this->prefix . 'settings',
            $this->prefix . 'settings',
            array($this, 'validateSettings')
        );
    }

    public function registerAdminMenu () {
        add_options_page(
            __('Better Angels Settings', 'better-angels'),
            __('Better Angels', 'better-angels'),
            'manage_options',
            $this->prefix . 'settings',
            array($this, 'renderOptionsPage')
        );

        add_menu_page(
            __('Emergency Team', 'better-angels'),
            __('My Team', 'better-angels'),
            'read', // give access to users of the Subscribers role
            $this->prefix . 'choose-angels',
            array($this, 'renderChooseAngelsPage')
        );

        add_dashboard_page(
            __('Activate Alert', 'better-angels'),
            __('Activate Alert', 'better-angels'),
            'read', // give access to users of the Subscribers role
            $this->prefix . 'activate-alert',
            array($this, 'renderActivateAlertPage')
        );
    }

    public function doAdminHeadActions () {
        $plugin_data = get_plugin_data(__FILE__);
        $this->registerContextualHelp();
        wp_enqueue_style(
            $this->prefix . 'style',
            plugins_url('style.css', __FILE__),
            false,
            $plugin_data['Version']
        );
    }

    public function addEmergencyPhoneContactMethod ($user_contact) {
        $user_contact[$this->prefix . 'sms'] = esc_html__('Emergency txt (SMS)', 'better-angels');
        return $user_contact;
    }


    // TODO: Write help screens.
    private function registerContextualHelp () {
        $screen = get_current_screen();
    }

    private function showDonationAppeal () {
?>
<div class="donation-appeal">
    <p style="text-align: center; font-style: italic; margin: 1em 3em;"><?php print sprintf(
esc_html__('Better Angels is provided as free software, but sadly grocery stores do not offer free food. If you like this plugin, please consider %1$s to its %2$s. &hearts; Thank you!', 'better-angels'),
'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=meitarm%40gmail%2ecom&lc=US&amp;item_name=Better%20Angels&amp;item_number=better%2dangels&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted">' . esc_html__('making a donation', 'better-angels') . '</a>',
'<a href="http://Cyberbusking.org/">' . esc_html__('houseless, jobless, nomadic developer', 'better-angels') . '</a>'
);?></p>
</div>
<?php
    }

    public function validateSettings ($input) {
        $safe_input = array();
        return $safe_input;
    }

    private function printAllUsersForDataList () {
        $users = get_users();
        foreach ($users as $usr) {
            if ($usr->ID !== get_current_user_id())
            print "<option value=\"{$usr->user_nicename}\">";
        }
    }

    /**
     * Get the guardians for a given user or the current user.
     * 
     * @param int $user_id The user ID to get the guardian list for.
     * @return array An array of WP_User objects.
     */
    public function getMyGuardians ($user_id = false) {
        $user_id = (!is_numeric($user_id)) ? get_current_user_id() : $user_id;
        return array_map('get_userdata', get_user_meta($user_id, $this->prefix . 'guardians', false));
    }

    public function isMyGuardian ($guardian_login, $user_id = false) {
        $user_id = (!is_numeric($user_id)) ? get_current_user_id() : $user_id;
        $guardians = $this->getMyGuardians($user_id);
        foreach ($guardians as $guardian) {
            if ($guardian_login === $guardian->user_login) {
                return true;
            }
        }
        return false;
    }

    /**
     * Adds a user as a guardian.
     *
     * @param string $guardian_login The WP login name of the user who is set as a guardian.
     * @param int $user_id The WP user ID of the user being guarded, or the current user if not specified.
     * @return int|bool The new user meta key on success or false on failure.
     */
    public function addGuardian ($guardian_login, $user_id = false) {
        $user_id = (!is_numeric($user_id)) ? get_current_user_id() : $user_id;
        $guardian_id = username_exists($guardian_login);
        if (!$this->isMyGuardian($guardian_login) && get_user_by('id',  $user_id) && $guardian_id && ($guardian_id !== $user_id)) {
            add_user_meta($user_id, $this->prefix . 'guardians', $guardian_id, false);
        }
        // TODO: What if the user ID passed in doesn't exist?
    }

    public function removeGuardian ($guardian_login, $user_id = false) {
        $user_id = (!is_numeric($user_id)) ? get_current_user_id() : $user_id;
        $guardian_id = username_exists($guardian_login);
        if (get_user_by('id',  $user_id) && $guardian_id) {
            delete_user_meta($user_id, $this->prefix . 'guardians', $guardian_id);
        }
        // TODO: What if the user ID passed in doesn't exist?
    }

    private function updateChooseAngels ($request, $user_id = false) {
        $user_id = (!is_numeric($user_id)) ? get_current_user_id() : $user_id;

        // Anything to delete?
        // Delete before adding!
        $my_guardians = $this->getMyGuardians($user_id);
        if (!empty($request[$this->prefix . 'my_guardians'])) {
            foreach ($my_guardians as $guard) {
                if (!in_array($guard->ID, $request[$this->prefix . 'my_guardians'])) {
                    $this->removeGuardian($guard->user_login, $user_id);
                }
            }
        } else {
            delete_user_meta($user_id, $this->prefix . 'guardians');
        }

        // Anything to add?
        if (!empty($request[$this->prefix . 'add_guardian'])) {
            $this->addGuardian($request[$this->prefix . 'add_guardian']);
        }
    }

    public function renderActivateAlertPage () {
        if (!current_user_can('read')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'better-angels'));
        }
        require_once 'pages/activate-alert.php';
    }

    public function renderChooseAngelsPage () {
        if (!current_user_can('read')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'better-angels'));
        }

        if (isset($_POST[$this->prefix . 'nonce'])
            && wp_verify_nonce($_POST[$this->prefix . 'nonce'], $this->prefix . 'guardians')) {
            $this->updateChooseAngels($_POST);
        }
        $guardians = $this->getMyGuardians();

        require_once 'pages/choose-angels.php';
    }

    public function renderOptionsPage () {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'better-angels'));
        }
        $options = get_option($this->prefix . 'settings');

        require_once 'pages/options.php';

        $this->showDonationAppeal();
    }
}

$better_angels_plugin = new BetterAngelsPlugin();
