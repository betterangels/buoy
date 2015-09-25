<?php
/**
 * Plugin Name: Buoy (a Better Angels first responder system)
 * Plugin URI: https://github.com/meitar/better-angels
 * Description: Tell your friends where you are and what you need. (A community-driven emergency first responder system.) <strong>Like this plugin? Please <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=TJLPJYXHSRBEE&amp;lc=US&amp;item_name=Better%20Angels&amp;item_number=better-angels&amp;currency_code=USD&amp;bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted" title="Send a donation to the developer of Better Angels">donate</a>. &hearts; Thank you!</strong>
 * Version: 0.1
 * Author: Maymay <bitetheappleback@gmail.com>
 * Author URI: http://maymay.net/
 * Text Domain: better-angels
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) { exit; } // Disallow direct HTTP access.

class BetterAngelsPlugin {

    private $prefix = 'better-angels_'; //< Internal prefix for settings, etc., derived from shortcode.
    private $chat_room_name; //< The name of the chat room for this incident.

    public function __construct () {

        add_action('plugins_loaded', array($this, 'registerL10n'));
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('current_screen', array($this, 'registerContextualHelp'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueMapsScripts'));
        add_action('admin_menu', array($this, 'registerAdminMenu'));
        add_action('wp_ajax_' . $this->prefix . 'findme', array($this, 'newAlert'));
        add_action('show_user_profile', array($this, 'addProfileFields'));
        add_action('personal_options_update', array($this, 'updateProfileFields'));

        add_filter('user_contactmethods', array($this, 'addEmergencyPhoneContactMethod'));

        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function setChatRoomName ($name) {
        $this->chat_room_name = $name;
    }
    public function getChatRoomName () {
        return $this->chat_room_name;
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
            __('Buoy Settings', 'better-angels'),
            __('Buoy', 'better-angels'),
            'manage_options',
            $this->prefix . 'settings',
            array($this, 'renderOptionsPage')
        );

        add_menu_page(
            __('Emergency Team', 'better-angels'),
            __('My Team', 'better-angels'),
            'read',
            $this->prefix . 'choose-angels',
            array($this, 'renderChooseAngelsPage'),
            plugins_url('img/icon-bw-life-preserver.svg', __FILE__)
        );

        add_submenu_page(
            null,
            __('Respond to Alert', 'better-angels'),
            __('Respond to Alert', 'better-angels'),
            'read',
            $this->prefix . 'review-alert',
            array($this, 'renderReviewAlertPage')
        );

        add_submenu_page(
            null,
            __('Incident Chat', 'better-angels'),
            __('Incident Chat', 'better-angels'),
            'read',
            $this->prefix . 'incident-chat',
            array($this, 'renderIncidentChatPage')
        );

        add_dashboard_page(
            __('Activate Alert', 'better-angels'),
            __('Activate Alert', 'better-angels'),
            'read', // give access to users of the Subscribers role
            $this->prefix . 'activate-alert',
            array($this, 'renderActivateAlertPage')
        );
    }

    public function enqueueMapsScripts ($hook) {
        $to_hook = array( // list of pages where maps API is needed
            'dashboard_page_' . $this->prefix . 'incident-chat',
            'dashboard_page_' . $this->prefix . 'review-alert'
        );
        if ($this->isAppPage($hook, $to_hook)) {
            wp_enqueue_script(
                $this->prefix . 'maps-api',
                'https://maps.googleapis.com/maps/api/js?language=' . get_locale(),
                $this->prefix . 'script',
                null, // do not set a WP version!
                true
            );
        }
    }

    /**
     * Translate user interface strings used in JavaScript.
     *
     * @return array An array of translated strings suitable for wp_localize_script().
     */
    private function getTranslations () {
        return array(
            'i18n_map_title' => __('Incident Map', 'better-angels'),
            'i18n_hide_map' => __('Hide Map', 'better-angels'),
            'i18n_show_map' => __('Show Map', 'better-angels')
        );
    }

    public function enqueueAdminScripts ($hook) {
        $plugin_data = get_plugin_data(__FILE__);
        wp_enqueue_style(
            $this->prefix . 'style',
            plugins_url('style.css', __FILE__),
            false,
            $plugin_data['Version']
        );
        wp_register_script(
            $this->prefix . 'script',
            plugins_url(str_replace('_', '', $this->prefix) . '.js', __FILE__),
            false,
            $plugin_data['Version']
        );
        wp_localize_script($this->prefix . 'script', str_replace('-', '_', $this->prefix) . 'vars', $this->getTranslations());
        wp_enqueue_script($this->prefix . 'script');

        $to_hook = array( // list of pages where Bootstrap CSS+JS is needed
            'dashboard_page_' . $this->prefix . 'activate-alert',
            'dashboard_page_' . $this->prefix . 'incident-chat'
        );
        if ($this->isAppPage($hook, $to_hook)) {
            wp_enqueue_style(
                $this->prefix . 'bootstrap',
                'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css'
            );
            wp_enqueue_script(
                $this->prefix . 'bootstrap',
                'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js',
                false,
                null,
                true
            );
        }
    }

    /**
     * Checks to see if the current page, called by a WordPress hook,
     * is one of the "app pages" where important functionality provided
     * by this plugin occurrs. Used to check whether or not to enqueue
     * certain additional, heavyweight assets, like BootstrapCSS.
     *
     * @param string $hook The hook name that called this page. (Set by WordPress.)
     * @param array $matches Optional list of hook names that should be matched against, useful for checking against a single hook.
     * @return bool True if the page is "one of ours," false otherwise.
     */
    private function isAppPage ($hook, $matches = array()) {
        $our_hooks = array(
            'dashboard_page_' . $this->prefix . 'activate-alert',
            'dashboard_page_' . $this->prefix . 'incident-chat',
            'dashboard_page_' . $this->prefix . 'review-chat',
        );

        if (0 < count($matches)) { $our_hooks = $matches; }

        foreach ($our_hooks as $the_hook) {
            if ($the_hook === $hook) {
                return true;
            }
        }
        return false;
    }

    public function getSmsEmailGatewayDomain($provider) {
        $provider_domains = array(
            'AT&T' => '@txt.att.net',
            'Alltel' => '@message.alltel.com',
            'Boost Mobile' => '@myboostmobile.com',
            'Cricket' => '@sms.mycricket.com',
            'Metro PCS' => '@mymetropcs.com',
            'Nextel' => '@messaging.nextel.com',
            'Ptel' => '@ptel.com',
            'Qwest' => '@qwestmp.com',
            'Sprint' => array(
                '@messaging.sprintpcs.com',
                '@pm.sprint.com'
            ),
            'Suncom' => '@tms.suncom.com',
            'T-Mobile' => '@tmomail.net',
            'Tracfone' => '@mmst5.tracfone.com',
            'U.S. Cellular' => '@email.uscc.net',
            'Verizon' => '@vtext.com',
            'Virgin Mobile' => '@vmobl.com'
        );
        if (is_array($provider_domains[$provider])) {
            $at_domain = array_rand($provider_domains[$provider]);
        } else {
            $at_domain = $provider_domains[$provider];
        }
        return $at_domain;
    }

    /**
     * Get a user's pre-defined crisis message, or a default message if empty.
     *
     * @return string The message.
     */
    private function getCallForHelp ($user_id = null) {
        $user_id = (!is_numeric($user_id)) ? get_current_user_id() : $user_id;
        $call_for_help = wp_strip_all_tags(get_user_meta($user_id, $this->prefix . 'call_for_help', true));
        return (empty($call_for_help))
            ? __('Please help!', 'better-angels') : $call_for_help;
    }

    /**
     * Responds to ajax requests activated from the main emergency alert button.
     *
     * TODO: Refactor this method, shouldn't have responsibility for all it's doing.
     */
    public function newAlert () {
        $alert_position = $_POST['pos'];
        $me = wp_get_current_user();
        $guardians = $this->getMyGuardians();

        $this->setChatRoomName(
            str_replace('-', '_', $this->prefix)
            // need to limit the length of this string due to Tlk.io integration for now
            . substr(hash('md5', serialize($me) . serialize($guardians) . time()), 0, 10)
        );

        $subject = (empty($_POST['msg'])) ? $this->getCallForHelp($me->ID) : wp_strip_all_tags($_POST['msg']);
        $responder_link = wp_nonce_url(
            admin_url(
                '?page=' . $this->prefix . 'review-alert'
                . '&who=' . urlencode($me->user_login)
                . '&latitude=' . urlencode($alert_position['latitude'])
                . '&longitude=' . urlencode($alert_position['longitude'])
                . '&chat_room=' . urlencode($this->getChatRoomName())
                . '&msg=' . urlencode($subject)
            ),
            $this->prefix . 'review', $this->prefix . 'nonce'
        );
        // TODO: Write a more descriptive message.
        $message = $responder_link;

        foreach ($guardians as $guardian) {
            $sms = preg_replace('/[^0-9]/', '', get_user_meta($guardian->ID, $this->prefix . 'sms', true));
            $sms_provider = get_user_meta($guardian->ID, $this->prefix . 'sms_provider', true);
            $headers = array(
                'From: "' . $me->display_name . '" <' . $me->user_email . '>'
            );

            wp_mail($guardian->user_email, $subject, $message, $headers);

            // Send an email that will be converted to an SMS by the
            // telco company if the guardian has provided an emergency txt number.
            if (!empty($sms) && !empty($sms_provider)) {
                wp_mail(
                    $sms . $this->getSmsEmailGatewayDomain($sms_provider),
                    $subject,
                    $message,
                    $headers
                );
            }
        }
        $next_url = wp_nonce_url(
            admin_url(
                '?page=' . $this->prefix . 'incident-chat'
                . '&show_safety_modal=1' // for the alerter
                . '&chat_room=' . $this->getChatRoomName()
            ),
            $this->prefix . 'chat', $this->prefix . 'nonce'
        );
        wp_send_json_success($next_url);
    }

    public function addEmergencyPhoneContactMethod ($user_contact) {
        $user_contact[$this->prefix . 'sms'] = esc_html__('Emergency txt (SMS)', 'better-angels');
        return $user_contact;
    }

    public function addProfileFields () {
        require_once 'pages/profile.php';
    }
    public function updateProfileFields () {
        // TODO: Whitelist valid providers.
        update_user_meta(get_current_user_id(), $this->prefix . 'sms_provider', $_POST[$this->prefix . 'sms_provider']);
        update_user_meta(get_current_user_id(), $this->prefix . 'call_for_help', strip_tags($_POST[$this->prefix . 'call_for_help']));
    }

    // TODO: Write help screens.
    public function registerContextualHelp () {
        $screen = get_current_screen();
    }

    private function showDonationAppeal () {
?>
<div class="donation-appeal">
    <p style="text-align: center; font-style: italic; margin: 1em 3em;"><?php print sprintf(
esc_html__('Bouy is provided as free software, but sadly grocery stores do not offer free food. If you like this plugin, please consider %1$s to its %2$s. &hearts; Thank you!', 'better-angels'),
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

    public function renderReviewAlertPage () {
        if (!current_user_can('read')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'better-angels'));
        }
        // TODO: Make it so that only the alerter's guardians (or admin users) can see this page.
        require_once 'pages/review-alert.php';
    }

    public function renderIncidentChatPage () {
        if (!current_user_can('read')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'better-angels'));
        }
        if (wp_verify_nonce($_GET[$this->prefix . 'nonce'], "{$this->prefix}chat")) {
            $this->setChatRoomName($_GET['chat_room']);
        }
        require_once 'pages/incident-chat.php';
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
