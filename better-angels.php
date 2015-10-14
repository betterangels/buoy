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
    private $incident_hash; //< Hash of the current incident ("alert").
    private $chat_room_name; //< The name of the chat room for this incident.

    public function __construct () {

        add_action('plugins_loaded', array($this, 'registerL10n'));
        add_action('init', array($this, 'registerCustomPostTypes'));
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('current_screen', array($this, 'registerContextualHelp'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueMapsScripts'));
        add_action('admin_menu', array($this, 'registerAdminMenu'));
        add_action('current_screen', array($this, 'maybeRedirect'));
        add_action('wp_ajax_' . $this->prefix . 'findme', array($this, 'handleAlert'));
        add_action('show_user_profile', array($this, 'addProfileFields'));
        add_action('personal_options_update', array($this, 'updateProfileFields'));

        add_action('added_user_meta', array($this, 'addedUserMeta'), 10, 4);
        add_action('updated_user_meta', array($this, 'updatedUserMeta'), 10, 4);

        add_action($this->prefix . 'delete_old_alerts', array($this, 'deleteOldAlerts'));

        add_filter('user_contactmethods', array($this, 'addEmergencyPhoneContactMethod'));

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function setIncidentHash ($string_to_hash) {
        $this->incident_hash = hash('sha256', $string_to_hash);
    }
    public function getIncidentHash () {
        return $this->incident_hash;
    }
    public function setChatRoomName ($name) {
        $prefix = 'buoy_';
        // need to limit the length of this string due to Tlk.io integration for now
        $this->chat_room_name = $prefix . substr($name, 0, 20);
    }
    public function getChatRoomName () {
        return $this->chat_room_name;
    }

    public function activate () {
        $options = get_option($this->prefix . 'settings');
        if (false === $options || empty($options['safety_info'])) {
            $options['safety_info'] = file_get_contents(dirname(__FILE__) . '/includes/default-safety-information.html');
        }
        update_option($this->prefix . 'settings', $options);

        if (!wp_next_scheduled($this->prefix . 'delete_old_alerts')) {
            wp_schedule_event(
                time() + HOUR_IN_SECONDS,
                'twicedaily',
                $this->prefix . 'delete_old_alerts'
            );
        }
    }

    public function deactivate () {
        do_action($this->prefix . 'delete_old_alerts');
        wp_clear_scheduled_hook($this->prefix . 'delete_old_alerts');
    }

    /**
     * Deletes posts older than a certain threshold from the database.
     *
     * @param string $threshold A strtotime()-compatible string indicating some time in the past. Defaults to '-2 days'.
     */
    public function deleteOldAlerts ($threshold = '-2 days') {
        $threshold = (empty($threshold)) ? '-2 days' : $threshold;
        $wp_query_args = array(
            'post_type' => str_replace('-', '_', $this->prefix) . 'alert',
            'date_query' => array(
                'column' => 'post_date_gmt',
                'before' => $threshold,
                'inclusive' => true
            ),
            'fields' => 'ids'
        );
        $query = new WP_Query($wp_query_args);
        foreach ($query->posts as $post_id) {
            wp_delete_post($post_id, true); // delete immediately
        }
    }

    public function registerL10n () {
        load_plugin_textdomain('better-angels', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function registerCustomPostTypes () {
        register_post_type(str_replace('-', '_', $this->prefix) . 'alert', array(
            'label' => __('Incidents', 'better-angels'),
            'description' => __('A call for help.', 'better-angels'),
            'public' => false,
            'show_ui' => false,
            // TODO: Do we need to/should we use custom capabilities?
            //       Or will the default "post" types be sufficient?
            //'capability_type' => str_replace('-', '_', $this->prefix)
            'hierarchical' => false,
            'supports' => array(
                'title',
                'author',
                'custom-fields'
            ),
            'has_archive' => false,
            'rewrite' => false,
            'can_export' => false
        ));
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

        $hook = add_menu_page(
            __('Emergency Team', 'better-angels'),
            __('My Team', 'better-angels'),
            'read',
            $this->prefix . 'choose-angels',
            array($this, 'renderChooseAngelsPage'),
            plugins_url('img/icon-bw-life-preserver.svg', __FILE__)
        );
        add_action('load-' . $hook, array($this, 'addChooseAngelsHelpTabs'));

        $hook = add_submenu_page(
            $this->prefix . 'choose-angels',
            __('Team membership', 'better-angels'),
            __('Team membership', 'better-angels'),
            'read',
            $this->prefix . 'confirm-guardianship',
            array($this, 'renderTeamMembershipPage')
        );
        add_action('load-' . $hook, array($this, 'addTeamMembershipPageHelpTabs'));

        add_submenu_page(
            $this->prefix . 'choose-angels',
            __('Safety information', 'better-angels'),
            __('Safety information', 'better-angels'),
            'read',
            $this->prefix . 'safety-info',
            array($this, 'renderSafetyInfoPage')
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

    public function maybeRedirect () {
        $screen = get_current_screen();
        if ('dashboard_page_' . $this->prefix . 'activate-alert' === $screen->id
            && 0 === count($this->getMyGuardians())) {
            wp_safe_redirect(admin_url('admin.php?page=' . $this->prefix . 'choose-angels&msg=no-guardians'));
            exit();
        }
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
     * Dispatches events to respond to user metadata additions.
     */
    public function addedUserMeta ($meta_id, $object_id, $meta_key, $_meta_value) {
        switch ($meta_key) {
            case $this->prefix . 'pending_guardians':
            case $this->prefix . 'pending_fake_guardians':
                $g = get_userdata($_meta_value);
                $this->sendGuardianRequest($g->user_login);
                break;
        }
    }

    /**
     * Dispatches events to respond to user metadata updates.
     */
    public function updatedUserMeta ($meta_id, $object_id, $meta_key, $_meta_value) {
    }

    /**
     * Inserts a new alert (incident) as a custom post type in the WordPress database.
     *
     * @param array $post_args Arguments for the post type.
     * @param array $geodata Array includin a `latitude` and a `longitude` key for geodata.
     * @return int Result of `wp_insert_post()` (int ID of new post on success, WP_Error on error.)
     */
    public function newAlert ($post_args, $geodata) {
        // TODO: Do some validation on $post_args?
        // These values should always be hard-coded.
        $post_args['post_type'] = str_replace('-', '_', $this->prefix) . 'alert';
        $post_args['post_content'] = ''; // Empty content
        $post_args['post_status'] = 'publish'; // TODO: Should we use a custom status to restrict access here?
        $post_args['ping_status'] = 'closed';
        $post_args['comment_status'] = 'closed';

        $alert_id = wp_insert_post($post_args);
        if (!is_wp_error($alert_id)) {
            update_post_meta($alert_id, $this->prefix . 'incident_hash', $this->getIncidentHash());
            update_post_meta($alert_id, 'geo_latitude', $geodata['latitude']);
            update_post_meta($alert_id, 'geo_longitude', $geodata['longitude']);
            // TODO: Should we explicitly mark this geodata privacy info?
            //       See https://codex.wordpress.org/Geodata#Geodata_Format
            //update_post_meta($alert_id, 'geo_public', 1);
            update_post_meta($alert_id, $this->prefix . 'chat_room_name', $this->getChatRoomName());
        }
        return $alert_id;
    }

    /**
     * Responds to ajax requests activated from the main emergency alert button.
     *
     * TODO: Refactor this method, shouldn't have responsibility for all it's doing.
     */
    public function handleAlert () {
        // Collect info from the browser via Ajax request
        $alert_position = $_POST['pos'];
        $me = wp_get_current_user();
        $guardians = $this->getMyGuardians();
        $subject = (empty($_POST['msg'])) ? $this->getCallForHelp($me->ID) : wp_strip_all_tags($_POST['msg']);

        // Set instance variables
        $this->setIncidentHash(serialize($me) . serialize($guardians) . time());
        $this->setChatRoomName(hash('sha1', $this->getIncidentHash() . uniqid('', true)));

        // Create a new alert in the DB
        // TODO: Should we use a custom post type? Should we use a custom table?
        $alert_id = $this->newAlert(array('post_title' => $subject), $alert_position);

        // TODO: Investigate use of nonce here.
        //       WordPress nonces seem to be user specific, so we can't verify this
        //       because this link is intentionally intended to be clicked on by a
        //       different, second user.
        $responder_link = wp_nonce_url(
            admin_url(
                '?page=' . $this->prefix . 'review-alert'
                . '&' . $this->prefix . 'incident_hash=' . $this->getIncidentHash()
            ),
            $this->prefix . 'review', $this->prefix . 'nonce'
        );
        // TODO: Write a more descriptive message.
        $message = $responder_link;

        foreach ($guardians as $guardian) {
            // TODO: Wrap this "send an alert" procedure into its own function?
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

        // Construct the redirect URL to the alerter's chat room
        $next_url = wp_nonce_url(
            admin_url(
                '?page=' . $this->prefix . 'incident-chat'
                . '&' . $this->prefix . 'incident_hash=' . $this->getIncidentHash()
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
        foreach ($input as $k => $v) {
            switch ($k) {
                case 'safety_info':
                    $safe_input[$k] = force_balance_tags($v);
                    break;
            }
        }
        return $safe_input;
    }

    private function printAllUsersForDataList () {
        $users = get_users();
        foreach ($users as $usr) {
            if ($usr->ID !== get_current_user_id() && !$this->isMyGuardian($usr->user_login))
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

    /**
     * Get the pending guardians for a given user or the current user.
     *
     * @param int $user_id The user ID to get the pending guardian list for.
     * @return array An array of WP_User objects.
     */
    public function getMyPendingGuardians ($user_id = false) {
        $user_id = (!is_numeric($user_id)) ? get_current_user_id() : $user_id;
        return array_map('get_userdata', get_user_meta($user_id, $this->prefix . 'pending_guardians', false));
    }

    public function getMyFakeGuardians ($user_id = false) {
        $user_id = (!is_numeric($user_id)) ? get_current_user_id() : $user_id;
        return array_map('get_userdata', get_user_meta($user_id, $this->prefix . 'fake_guardians', false));
    }

    public function getMyPendingFakeGuardians ($user_id = false) {
        $user_id = (!is_numeric($user_id)) ? get_current_user_id() : $user_id;
        return array_map('get_userdata', get_user_meta($user_id, $this->prefix . 'pending_fake_guardians', false));
    }

    /**
     * Checks to see if a user account is on the response team of the current user.
     *
     * @param string $guardian_login The WP user login name of the account to check.
     * @return bool True if $guardian_login is the username of a team member for the current user.
     */
    public function isMyGuardian ($guardian_login) {
        $team = $this->getResponseTeam(true, get_current_user_id());
        foreach ($team as $user) {
            if ($guardian_login === $user->user_login) {
                return true;
            }
        }
        return false;
    }

    /**
     * Requests that a user be added to a team of guardians.
     *
     * @param string $guardian_login The WP login name of the user who is to be added to a team.
     * @param int $user_id The WP user ID of the user whose team to add the guardian to.
     * @param bool $fake Whether or not to add this guardian as a "fake" team member.
     * @return void
     */
    public function requestGuardian ($guardian_login, $user_id, $fake) {
        if (!$this->isMyGuardian($guardian_login)) {
            $meta_key = $this->prefix . 'pending_' . (($fake) ? 'fake_' : '' ) . 'guardians';
            add_user_meta($user_id, $meta_key, username_exists($guardian_login), false);
        }
    }

    /**
     * Sends a notification (by email) asking a user for confirmation to join a response team.
     *
     * @param string $guardian_login The user to notify.
     * @return bool Same as wp_mail()'s return value.
     */
    private function sendGuardianRequest ($guardian_login) {
        $curr_user = wp_get_current_user();
        $guardian_id = username_exists($guardian_login);
        // Send an email notification to the guardian asking for permission to be added to team.
        $g = get_userdata($guardian_id);
        $subject = sprintf(__('%s wants you to join their crisis response team', 'better-angels'), $curr_user->display_name);
        // TODO: Write a better message.
        $msg = wp_nonce_url(
            admin_url(
                'admin.php?page=' . $this->prefix . 'confirm-guardianship'
            ),
            $this->prefix . 'confirm-guardianship', $this->prefix . 'nonce'
        );
        return wp_mail($g->user_email, $subject, $msg);
    }

    /**
     * Adds a user as a guardian for another user.
     *
     * @param string $guardian_login The WP login name of the user who is set as a guardian.
     * @param int $user_id The WP user ID of the user being guarded.
     * @param bool $fake Whether to add this guardian as a "fake" team member or not.
     * @return void
     */
    public function addGuardian ($guardian_login, $user_id, $fake) {
        $guardian_id = username_exists($guardian_login);
        $meta_key = (($fake) ? 'fake_' : '' ) . 'guardians';
        $existing_guardians = get_user_meta($user_id, $this->prefix . $meta_key, false);
        $invitations = get_user_meta($user_id, $this->prefix . 'pending_' . $meta_key, false);
        if ($guardian_id !== $user_id && !in_array($guardian_id, $existing_guardians) && in_array($guardian_id, $invitations)) {
            add_user_meta($user_id, $this->prefix . $meta_key, $guardian_id, false);
            delete_user_meta($user_id, $this->prefix . 'pending_' . $meta_key, $guardian_id);
        }
        // TODO: What if the user ID passed in doesn't exist?
    }

    /**
     * Removes a user from all "guardian" (team member) lists.
     *
     * @param string $guardian_login WP user name of the account to remove.
     * @param int $user_id WP user ID number of the team owner.
     * @return void
     */
    public function removeGuardian ($guardian_login, $user_id = false) {
        $user_id = (!is_numeric($user_id)) ? get_current_user_id() : $user_id;
        $guardian_id = username_exists($guardian_login);
        if (get_user_by('id',  $user_id) && $guardian_id) {
            delete_user_meta($user_id, $this->prefix . 'guardians', $guardian_id);
            delete_user_meta($user_id, $this->prefix . 'fake_guardians', $guardian_id);
            delete_user_meta($user_id, $this->prefix . 'pending_guardians', $guardian_id);
            delete_user_meta($user_id, $this->prefix . 'pending_fake_guardians', $guardian_id);
        }
        // TODO: What if the user ID passed in doesn't exist?
    }

    /**
     * Get an array of users who are on a particular user's team.
     *
     * @param bool $include_fake Whether or not to include "fake" team members.
     * @param int $user_id User ID of the team owner. (Defaults to current user.)
     * @return array List of WP_User objects comprising all users.
     */
    public function getResponseTeam ($include_fake = false, $user_id = false) {
        $user_id = (!is_numeric($user_id)) ? get_current_user_id() : $user_id;
        $users = array_merge(
            $this->getMyGuardians($user_id),
            $this->getMyPendingGuardians($user_id)
        );
        if ($include_fake) {
            $users = array_merge(
                $users,
                $this->getMyFakeGuardians($user_id),
                $this->getMyPendingFakeGuardians($user_id)
            );
        }
        return $users;
    }

    private function updateChooseAngels ($request) {
        $user_id = get_current_user_id();

        // Anything to edit/toggle type?
        if (!empty($request[$this->prefix . 'guardian'])) {
            foreach ($request[$this->prefix . 'guardian'] as $id => $data) {
                $this->setGuardianType($id, $data['type']);
            }
        }

        // Anything to delete?
        // Delete before adding!
        $all_my_guardians = $this->getResponseTeam(true, $user_id);
        if (!empty($request[$this->prefix . 'my_guardians'])) {
            foreach ($all_my_guardians as $guard) {
                if (!in_array($guard->ID, $request[$this->prefix . 'my_guardians'])) {
                    $this->removeGuardian($guard->user_login, $user_id);
                }
            }
        } else { // delete all guardians
            delete_user_meta($user_id, $this->prefix . 'guardians');
            delete_user_meta($user_id, $this->prefix . 'fake_guardians');
            delete_user_meta($user_id, $this->prefix . 'pending_guardians');
            delete_user_meta($user_id, $this->prefix . 'pending_fake_guardians');
        }

        // Anything to add?
        if (!empty($request[$this->prefix . 'add_guardian'])) {
            $this->requestGuardian(
                sanitize_user($request[$this->prefix . 'add_guardian'], true),
                get_current_user_id(),
                isset($request[$this->prefix . 'is_fake_guardian'])
            );
        }
    }

    public function setGuardianType ($guardian_id, $type) {
        if ('fake' === $type) {
            $this->setAsFakeGuardian($guardian_id);
        } else {
            $this->setAsRealGuardian($guardian_id);
        }
    }

    public function setAsFakeGuardian ($guardian_id) {
        $g = get_userdata($guardian_id);
        $m = get_user_meta(get_current_user_id(), $this->prefix . 'pending_guardians', $guardian_id, true);
        if (empty($m)) {
            $this->removeGuardian($g->user_login, get_current_user_id());
            update_user_meta(get_current_user_id(), $this->prefix . 'pending_fake_guardians', $guardian_id);
            $this->addGuardian($g->user_login, get_current_user_id(), true);
        } else {
            $this->removeGuardian($g->user_login, get_current_user_id());
            update_user_meta(get_current_user_id(), $this->prefix . 'pending_fake_guardians', $guardian_id);
        }
    }

    public function setAsRealGuardian ($guardian_id) {
        $g = get_userdata($guardian_id);
        $m = get_user_meta(get_current_user_id(), $this->prefix . 'pending_fake_guardians', $guardian_id, true);
        if (empty($m)) {
            $this->removeGuardian($g->user_login, get_current_user_id());
            update_user_meta(get_current_user_id(), $this->prefix . 'pending_guardians', $guardian_id);
            $this->addGuardian($g->user_login, get_current_user_id(), false);
        } else {
            $this->removeGuardian($g->user_login, get_current_user_id());
            update_user_meta(get_current_user_id(), $this->prefix . 'pending_guardians', $guardian_id);
        }
    }

    public function addChooseAngelsHelpTabs () {
        $screen = get_current_screen();
        $html = '';
        $html .= '<p>'. esc_html__('You can choose who to send emergency alerts to if you find yourself in a crisis situation. The people listed here will be notified of where you are and what you need when you activate an alert using Buoy, unless they have the word "fake" next to their name.', 'better-angels') . '</p>';
        $html .= '<p>' . esc_html__('The people you trust must already have accounts on this website in order for you to add them to your team. If they do not yet have accounts here, or if you do not know their account name, contact them privately and ask them to sign up.', 'better-angels') . '</p>';
        $html .= '<p>' . esc_html__('Your current team members are shown in the list below with a check mark next to their user name. A "pending" next to their name means they have not yet approved your invitation. A "fake" next to their name means they are not actually going to get alerts you send. Your team members must accept your invitation before your emergency alerts are sent to them.', 'better-angels') . '</p>';
        $screen->add_help_tab(array(
            'title' => __('About your Buoy personal emergency response team', 'better-angels'),
            'id' => esc_html($this->prefix . 'about-choose-angels-help'),
            'content' => $html
        ));
        $html = '';
        $html .= '<p>' . esc_html__('To add a team member, type their user name in the "Add a team member" box. Alternatively, click or tap inside the box once to select it, then click or tap inside the box again to reveal a drop-down menu of active accounts you can add to your team. When you have entered the user name of the person you want to add to your team, click the "Save Changes" button at the bottom of this page.', 'better-angels') . '</p>';
        $html .= '<p>' . esc_html__('If someone you know is pressuring you to add them to your team but you do not actually want them to get emergency alerts from you, check the "Add as fake team member" box to make them think they are on your team, but not actually send them any alerts.', 'better-angels') . '</p>';
        $html .= '<p>' . esc_html__('To remove a person from your team, uncheck the checkbox next to their user name and click the "Save Changes" button at the bottom of this page. People you remove from your team will be able to see that you have removed them, so do not remove "fake" members until you feel it is safe for you to do so.', 'better-angels') . '</p>';
        $screen->add_help_tab(array(
            'title' => __('Adding and removing team members', 'better-angels'),
            'id' => esc_html($this->prefix . 'add-remove-choose-angels-help'),
            'content' => $html
        ));
    }

    public function addTeamMembershipPageHelpTabs () {
        // TODO: Write the help for this page.
    }

    public function renderActivateAlertPage () {
        if (!current_user_can('read')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'better-angels'));
        }
        require_once 'pages/activate-alert.php';
    }

    public function renderReviewAlertPage () {
        // NOTE: WordPress nonces are user-specific, so we don't use one here.
        if (!current_user_can('read')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'better-angels'));
        }
        // TODO: Make it so that only the alerter's guardians (or admin users) can see this page.
        require_once 'pages/review-alert.php';
    }

    public function getAlert ($incident_hash) {
        $posts = get_posts(array(
            'post_type' => str_replace('-', '_', $this->prefix) . 'alert',
            'meta_key' => $this->prefix . 'incident_hash',
            'meta_value' => $incident_hash
        ));
        return array_pop($posts);
    }

    public function addIncidentResponder ($alert_post, $user_id) {
        if (!in_array($user_id, get_post_meta($alert_post->ID, $this->prefix . 'responders'))) {
            add_post_meta($alert_post->ID, $this->prefix . 'responders', $user_id, false);
        }
    }

    /**
     * Sets the geo-located metadata for a responer in the context of an alert. The responder is the current user.
     *
     * @param object $alert_post The WP post object of the alert incident.
     * @param array $geo An array with `latitude` and `longitude` keys.
     * @return void
     */
    public function setResponderGeoLocation ($alert_post, $geo) {
        update_post_meta($alert_post->ID, $this->prefix . 'responder_' . get_current_user_id() . '_location', $geo);
    }
    public function getResponderGeoLocation ($alert_post, $user_id) {
        return get_post_meta($alert_post->ID, $this->prefix . 'responder_' . $user_id . '_location', true);
    }

    /**
     * Retrieves the list of responders for a given alert.
     *
     * @param object $alert_post The WP Post object of the alert.
     * @return array
     */
    public function getIncidentResponders ($alert_post) {
        return get_post_meta($alert_post->ID, $this->prefix . 'responders', false);
    }

    public function renderIncidentChatPage () {
        if (!current_user_can('read') || !wp_verify_nonce($_GET[$this->prefix . 'nonce'], "{$this->prefix}chat")) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'better-angels'));
        }
        $alert_post = $this->getAlert(urldecode($_GET[$this->prefix . 'incident_hash']));
        if (get_current_user_id() != $alert_post->post_author) {
            $this->addIncidentResponder($alert_post, get_current_user_id());
            // TODO: Clean this up a bit, maybe the JavaScript should send JSON data?
            if (!empty($_POST[$this->prefix . 'location'])) {
                $p = explode(',', $_POST[$this->prefix . 'location']);
                $responder_geo = array(
                    'latitude' => $p[0],
                    'longitude' => $p[1]
                );
                $this->setResponderGeoLocation($alert_post, $responder_geo);
            }
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
        require_once 'pages/choose-angels.php';
    }

    public function renderTeamMembershipPage () {
        if (!current_user_can('read')) {
        // TODO: Figure out this cross-user nonce thing.
        //if (!current_user_can('read') || !wp_verify_nonce($_GET[$this->prefix . 'nonce'], $this->prefix . 'confirm-guardianship')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'better-angels'));
        }
        if (isset($_POST[$this->prefix . 'nonce']) && wp_verify_nonce($_POST[$this->prefix . 'nonce'], $this->prefix . 'update-teams')) {
            $my_teams = (empty($_POST[$this->prefix . 'my_teams'])) ? array() : $_POST[$this->prefix . 'my_teams'];
            $join_teams = (empty($_POST[$this->prefix . 'join_teams'])) ? array() : $_POST[$this->prefix . 'join_teams'];
            $this->setTeamMembership(array_merge($my_teams, $join_teams));
        }
        require_once 'pages/confirm-guardianship.php';
    }

    /**
     * Adds a user to the teams specified and removes them from any teams not listed.
     *
     * @param array $user_teams The user IDs of other users whose team the current user should be on.
     */
    public function setTeamMembership ($user_teams) {
        $curr_user = wp_get_current_user();

        // Remove the current user from all team lists.
        delete_metadata('user', null, $this->prefix . 'guardians', $curr_user->ID, true);
        delete_metadata('user', null, $this->prefix . 'fake_guardians', $curr_user->ID, true);

        // Add them only to the lists they should actually be on.
        foreach ($user_teams as $user_id) {
            $fake = get_user_meta($user_id, $this->prefix . 'pending_fake_guardians', $curr_user->ID, true);
            $this->addGuardian(
                $curr_user->user_login,
                $user_id,
                (empty($fake)) ? false : true
            );
        }
    }

    public function renderSafetyInfoPage () {
        if (!current_user_can('read')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'better-angels'));
        }
        $options = get_option($this->prefix . 'settings');
        print $options['safety_info']; // TODO: Can we harden against XSS here?
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
