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

    private $default_alert_ttl_num = 2;
    private $default_alert_ttl_multiplier = DAY_IN_SECONDS;

    private $Error; //< WP_Error object

    public function __construct () {
        $this->Error = new WP_Error();

        add_action('plugins_loaded', array($this, 'registerL10n'));
        add_action('init', array($this, 'registerCustomPostTypes'));
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('admin_init', array($this, 'configureCron'));
        add_action('current_screen', array($this, 'registerContextualHelp'));
        add_action('current_screen', array($this, 'maybeRedirect'));
        add_action('send_headers', array($this, 'redirectShortUrl'));
        add_action('wp_before_admin_bar_render', array($this, 'addIncidentMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueMapsScripts'));
        add_action('admin_head-dashboard_page_' . $this->prefix . 'activate-alert', array($this, 'doAdminHeadActivateAlert'));
        add_action('admin_menu', array($this, 'registerAdminMenu'));
        add_action('admin_notices', array($this, 'showAdminNotices'));
        add_action('wp_ajax_' . $this->prefix . 'findme', array($this, 'handleAlert'));
        add_action('wp_ajax_' . $this->prefix . 'schedule-alert', array($this, 'handleScheduledAlert'));
        add_action('wp_ajax_' . $this->prefix . 'unschedule-alert', array($this, 'handleUnscheduleAlert'));
        add_action('wp_ajax_' . $this->prefix . 'update-location', array($this, 'handleLocationUpdate'));
        add_action('wp_ajax_' . $this->prefix . 'upload-media', array($this, 'handleMediaUpload'));
        add_action('wp_ajax_' . $this->prefix . 'dismiss-installer', array($this, 'handleDismissInstaller'));
        add_action('show_user_profile', array($this, 'addProfileFields'));
        add_action('personal_options_update', array($this, 'updateProfileFields'));

        add_action('publish_' . str_replace('-', '_', $this->prefix) . 'alert', array($this, 'publishAlert'));

        add_action('update_option_' . $this->prefix . 'settings', array($this, 'updatedSettings'), 10, 2);
        add_action('added_user_meta', array($this, 'addedUserMeta'), 10, 4);

        add_action($this->prefix . 'delete_old_alerts', array($this, 'deleteOldAlerts'));

        add_filter('user_contactmethods', array($this, 'addContactInfoFields'));

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
        if (!isset($options['alert_ttl_num']) || is_null($options['alert_ttl_num']) || 0 === $options['alert_ttl_num']) {
            $options['alert_ttl_num'] = $this->default_alert_ttl_num;
        }
        if (!isset($options['alert_ttl_multiplier']) || is_null($options['alert_ttl_multiplier']) || 0 === $options['alert_ttl_multiplier']) {
            $options['alert_ttl_multiplier'] = DAY_IN_SECONDS;
        }
        update_option($this->prefix . 'settings', $options);

        $this->updateSchedules(
            '',
            $this->get_alert_ttl_string($options['alert_ttl_num'], $options['alert_ttl_multiplier'])
        );
    }

    private function time_multiplier_to_unit ($num) {
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

    public function deactivate () {
        $options = get_option($this->prefix . 'settings');
        do_action($this->prefix . 'delete_old_alerts');
        wp_clear_scheduled_hook($this->prefix . 'delete_old_alerts');       // clear hook with no args
        wp_clear_scheduled_hook($this->prefix . 'delete_old_alerts', array( // and also with explicit args
            $this->get_alert_ttl_string($options['alert_ttl_num'], $options['alert_ttl_multiplier'])
        ));
    }

    /**
     * Deletes posts older than a certain threshold and possibly their children
     * (media attachments) from the database.
     *
     * @param string $threshold A strtotime()-compatible string indicating some time in the past.
     * @uses get_option() to check the value of this plugin's `delete_old_incident_media` setting for whether to delete attachments (child posts), too.
     * @return void
     */
    public function deleteOldAlerts ($threshold) {
        $options = get_option($this->prefix . 'settings');
        $threshold = empty($threshold)
            ? $this->get_alert_ttl_string($options['alert_ttl_num'], $options['alert_ttl_multiplier'])
            : $threshold;
        $wp_query_args = array(
            'post_type' => str_replace('-', '_', $this->prefix) . 'alert',
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
            if (!empty($options['delete_old_incident_media'])) {
                foreach ($types as $type) {
                    $attached_posts_by_type[$type] = get_attached_media($type, $post_id);
                }
                foreach ($attached_posts_by_type as $type => $posts) {
                    foreach ($posts as $post) {
                        if (!wp_delete_post($post->ID, true)) {
                            $this->debug_log(sprintf(
                                __('Failed to delete attachment post %1$s (child of %2$s) during %3$s', 'better-angels'),
                                $post->ID,
                                $post_id,
                                __FUNCTION__ . '()'
                            ));
                        }
                    }
                }
            }
            if (!wp_delete_post($post_id, true)) {
                $this->debug_log(sprintf(
                    __('Failed to delete post with ID %1$s during %2$s', 'better-angels'),
                    $post_id,
                    __FUNCTION__ . '()'
                ));
            }
        }
    }

    /**
     * Prints a message to the WordPress wp-content/debug.log file
     * if the plugin's "detailed debugging" setting is enabled.
     *
     * @param string $message
     * @return void
     */
    private function debug_log ($message) {
        $options = get_option($this->prefix . 'settings');
        if (!empty($options['debug'])) {
            error_log($this->error_msg($message));
        }
    }

    /**
     * Prepares an error message for logging.
     *
     * @param string $message
     * @return string
     */
    private function error_msg ($message) {
        return '[' . __CLASS__ . ']: ' . $message;
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

    public function configureCron () {
        $options = get_option($this->prefix . 'settings');
        $path_to_wp_cron = ABSPATH . 'wp-cron.php';
        $os_cronjob_comment = '# Buoy WordPress Plugin Cronjob';
        require_once plugin_dir_path(__FILE__) . 'includes/crontab-manager.php';
        $C = new BuoyCrontabManager();
        $os_cron = false;
        foreach ($C->getCron() as $line) {
            if (strpos($line, $path_to_wp_cron)) {
                $os_cron = true;
                break;
            }
        }
        if (empty($options['future_alerts']) && $os_cron) {
            try {
                $C->removeCronJobs("/$os_cronjob_comment/");
            } catch (Exception $e) {
                $this->Error->add(
                    'crontab-remove-jobs',
                    __('Error removing system crontab jobs for timed alerts.', 'better-angels')
                    . PHP_EOL . $e->getMessage(),
                    'error'
                );
            }
        } else if (!empty($options['future_alerts']) && !$os_cron) {
            // TODO: Variablize the frequency
            $job = '*/5 * * * * php ' . $path_to_wp_cron . ' >/dev/null 2>&1 ' . $os_cronjob_comment;
            try {
                $C->appendCronJobs($job)->save();
            } catch (Exception $e) {
                $this->Error->add(
                    'crontab-add-jobs',
                    __('Error installing system cronjob for timed alerts.', 'better-angels')
                    . PHP_EOL . $e->getMessage(),
                    'error'
                );
            }
        }
    }

    /**
     * The "activate alert" screen is intended to be the web app "install"
     * screen for Buoy. We insert special mobile browser specific tags in
     * order to create a native-like "installer" for the user. We only want
     * to do this on this specific screen.
     */
    public function doAdminHeadActivateAlert () {
        print '<meta name="mobile-web-app-capable" content="yes" />';       // Android/Chrome
        print '<meta name="apple-mobile-web-app-capable" content="yes" />'; // Apple/Safari
        print '<meta name="apple-mobile-web-app-status-bar-style" content="black" />';
        print '<meta name="apple-mobile-web-app-title" content="' . esc_attr('Buoy', 'better-angels') . '" />';
        print '<link rel="apple-touch-icon" href="' . plugins_url('img/apple-touch-icon-152x152.png', __FILE__) . '" />';
        // TODO: This isn't showing up, figure out why.
        //print '<link rel="apple-touch-startup-image" href="' . plugins_url('img/apple-touch-startup.png', __FILE__) . '">';
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

        $hook = add_dashboard_page(
            __('Activate Alert', 'better-angels'),
            __('Activate Alert', 'better-angels'),
            'read', // give access to users of the Subscribers role
            $this->prefix . 'activate-alert',
            array($this, 'renderActivateAlertPage')
        );
        add_action('load-' . $hook, array($this, 'addInstallerScripts'));
    }

    public function addInstallerScripts () {
        $x = get_user_meta(get_current_user_id(), $this->prefix . 'installer-dismissed', true);
        if (empty($x)) {
            wp_enqueue_script(
                $this->prefix . 'install-webapp',
                plugins_url('includes/install-webapp.js', __FILE__)
            );

            wp_enqueue_style(
                $this->prefix . 'install-webapp',
                plugins_url('includes/install-webapp.css', __FILE__)
            );
        }
    }

    public function maybeRedirect () {
        $screen = get_current_screen();
        if ('dashboard_page_' . $this->prefix . 'activate-alert' === $screen->id
            && 0 === count($this->getGuardians(get_current_user_id()))) {
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

    public function showAdminNotices () {
        foreach ($this->Error->get_error_codes() as $err_code) {
            foreach ($this->Error->get_error_messages($err_code) as $err_msg) {
                $class = 'notice is-dismissible';
                if ($err_data = $this->Error->get_error_data($err_code)) {
                    $class .= " $err_data";
                }
                print '<div class="' . esc_attr($class) . '"><p>' . nl2br(esc_html($err_msg)) . '</p></div>';
            }
        }
    }

    /**
     * Translate user interface strings used in JavaScript.
     *
     * @return array An array of translated strings suitable for wp_localize_script().
     */
    private function getTranslations () {
        $locale_parts = explode('_', get_locale());
        return array(
            'ietf_language_tag' => array_shift($locale_parts),
            'i18n_install_btn_title' => __('Install Buoy', 'better-angels'),
            'i18n_install_btn_content' => __('Tap this button to install Buoy in your device, then choose "Add to home screen" from the menu.', 'better-angels'),
            'i18n_dismiss' => __('Dismiss', 'better-angels'),
            'i18n_map_title' => __('Incident Map', 'better-angels'),
            'i18n_hide_map' => __('Hide Map', 'better-angels'),
            'i18n_show_map' => __('Show Map', 'better-angels'),
            'i18n_crisis_location' => __('Location of emergency alert signal', 'better-angels'),
            'i18n_missing_crisis_location' => __('Emergency alert signal could not be pinpointed on a map.', 'better-angels'),
            'i18n_my_location' => __('My location', 'better-angels'),
            'i18n_directions' => __('Directions to here', 'better-angels'),
            'i18n_call' => __('Call', 'better-angels'),
            'i18n_responding_to_alert' => __('Responding to alert', 'better-angels'),
            'i18n_schedule_alert' => __('Schedule alert', 'better-angels'),
            'i18n_scheduling_alert' => __('Scheduling alert', 'better-angels'),
            'incident_nonce' => wp_create_nonce($this->prefix . 'incident-nonce')
        );
    }

    public function enqueueAdminScripts ($hook) {
        // Always enqueue this script to ensure iOS Webapp-style launches
        // remain within the webapp capable shell. Otherwise, navigating
        // to a page outside "our app" (like the WP profile page) will make
        // any subsequent navigation return to the built-in iOS Mobile Safari
        // browser, which is a confusing user experience for a user who has
        // "installed" Buoy.
        wp_enqueue_script(
            $this->prefix . 'stay-standalone',
            plugins_url('includes/stay-standalone.js', __FILE__)
        );

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

        $to_hook = array( // list of pages where Bootstrap CSS+JS, certain jQuery is needed
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

            wp_enqueue_style(
                'jquery-datetime-picker',
                plugins_url('includes/jquery.datetimepicker.css', __FILE__)
            );
            wp_enqueue_script(
                'jquery-datetime-picker',
                plugins_url('includes/jquery.datetimepicker.full.min.js', __FILE__),
                array('jquery'),
                null,
                true
            );

            wp_enqueue_style(
                $this->prefix . 'pulse-loader',
                plugins_url('includes/pulse-loader.css', __FILE__)
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
     * Handles modifying various WordPress settings based on a plugin settings update.
     *
     * @param array $old
     * @param array $new
     * @return void
     */
    public function updatedSettings ($old, $new) {
        $this->updateSchedules(
            $this->get_alert_ttl_string($old['alert_ttl_num'], $old['alert_ttl_multiplier']),
            $this->get_alert_ttl_string($new['alert_ttl_num'], $new['alert_ttl_multiplier'])
        );
    }

    private function get_alert_ttl_string ($num, $multiplier, $past = true) {
        $str = intval($num) . ' ' . $this->time_multiplier_to_unit($multiplier);
        return ($past) ? '-' . $str : $str;
    }

    private function updateSchedules ($old_str, $new_str) {
        if (wp_next_scheduled($this->prefix . 'delete_old_alerts', array($old_str))) {
            wp_clear_scheduled_hook($this->prefix . 'delete_old_alerts', array($old_str));
        }
        wp_schedule_event(
            time() + HOUR_IN_SECONDS,
            'hourly',
            $this->prefix . 'delete_old_alerts',
            array($new_str)
        );
    }

    /**
     * Dispatches events to respond to user metadata additions.
     */
    public function addedUserMeta ($meta_id, $object_id, $meta_key, $_meta_value) {
        switch ($meta_key) {
            case $this->prefix . 'guardians':
                $this->sendGuardianRequest($_meta_value);
                break;
        }
    }

    /**
     * Inserts a new alert (incident) as a custom post type in the WordPress database.
     *
     * @param array $post_args Arguments for the post type.
     * @param array $geodata Array includin a `latitude` and a `longitude` key for geodata, or false if no geodata present.
     * @return int Result of `wp_insert_post()` (int ID of new post on success, WP_Error on error.)
     */
    public function newAlert ($post_args, $geodata = false) {
        // Set instance variables
        $this->setIncidentHash(
            serialize(wp_get_current_user())
            . serialize($this->getGuardians(get_current_user_id()))
            . time()
        );
        $this->setChatRoomName(hash('sha1', $this->getIncidentHash() . uniqid('', true)));

        // TODO: Do some validation on $post_args?
        // These values should always be hard-coded.
        $post_args['post_type'] = str_replace('-', '_', $this->prefix) . 'alert';
        $post_args['post_content'] = ''; // Empty content
        $post_args['ping_status'] = 'closed';
        $post_args['comment_status'] = 'closed';

        if (empty($post_args['post_status'])) {
            $post_args['post_status'] = 'publish';
        }

        $alert_id = wp_insert_post($post_args);
        if (!is_wp_error($alert_id)) {
            update_post_meta($alert_id, $this->prefix . 'incident_hash', $this->getIncidentHash());
            update_post_meta($alert_id, $this->prefix . 'chat_room_name', $this->getChatRoomName());
            if ($geodata) {
                update_post_meta($alert_id, 'geo_latitude', $geodata['latitude']);
                update_post_meta($alert_id, 'geo_longitude', $geodata['longitude']);
            }
            // TODO: Should we explicitly mark this geodata privacy info?
            //       See https://codex.wordpress.org/Geodata#Geodata_Format
            //update_post_meta($alert_id, 'geo_public', 1);
        }
        return $alert_id;
    }

    private function alertSubject () {
        return (empty($_POST['msg']))
            ? $this->getCallForHelp(get_current_user_id())
            : wp_strip_all_tags(stripslashes_deep($_POST['msg']));
    }

    public function handleScheduledAlert () {
        check_ajax_referer($this->prefix . 'activate-alert', $this->prefix . 'nonce');
        $err = new WP_Error();
        $old_timezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $when_utc = strtotime(stripslashes_deep($_POST['scheduled-datetime-utc']));
        if (!$when_utc) {
            $err->add(
                'scheduled-datetime-utc',
                __('Buoy could not understand the date and time you entered.', 'better-angels')
            );
        } else {
            $dt = new DateTime("@$when_utc");
            // TODO: This fails to offset the UTC time back to server-local time
            //       correctly if the WP site is manually offset by a 30 minute
            //       offset instead of an hourly offset.
            $dt->setTimeZone(new DateTimeZone(wp_get_timezone_string()));
            $alert_id = $this->newAlert(array(
                'post_title' => $this->alertSubject(),
                'post_status' => 'future',
                'post_date' => $dt->format('Y-m-d H:i:s'),
                'post_date_gmt' => gmdate('Y-m-d H:i:s', $when_utc)
            ));
        }
        date_default_timezone_set($old_timezone);

        if (empty($err->errors)) {
            wp_send_json_success(array(
                'id' => $alert_id,
                'message' => __('Your timed alert has been scheduled. Schedule another?', 'better-angels')
            ));
        } else {
            wp_send_json_error($err);
        }
    }

    public function handleUnscheduleAlert () {
        if (isset($_GET[$this->prefix . 'nonce']) && wp_verify_nonce($_GET[$this->prefix . 'nonce'], $this->prefix . 'unschedule-alert')) {
            $post = get_post($_GET['alert_id']);
            if ($post && get_current_user_id() == $post->post_author) {
                wp_delete_post($post->ID, true); // delete immediately
                if (isset($_SERVER['HTTP_ACCEPT']) && false === strpos($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                    wp_safe_redirect(urldecode($_GET['r']));
                    exit();
                } else {
                    wp_send_json_success();
                }
            }
        } else {
            wp_send_json_error();
        }
    }

    /**
     * Responds to requests activated from the main emergency alert button.
     *
     * TODO: Refactor this method, shouldn't have responsibility for all it's doing.
     * TODO: Currently responds to both Ajax and non-JS form submissions. Again, refactor needed.
     */
    public function handleAlert () {
        check_ajax_referer($this->prefix . 'activate-alert', $this->prefix . 'nonce');

        // Collect info from the browser via Ajax request
        $alert_position = (empty($_POST['pos'])) ? false : $_POST['pos']; // TODO: array_map and sanitize this?

        // Create and publish the new alert.
        $this->newAlert(array('post_title' => $this->alertSubject()), $alert_position);

        // Construct the redirect URL to the alerter's chat room
        $next_url = wp_nonce_url(
            admin_url(
                '?page=' . $this->prefix . 'incident-chat'
                . '&' . $this->prefix . 'incident_hash=' . $this->getIncidentHash()
            ),
            $this->prefix . 'chat', $this->prefix . 'nonce'
        );

        if (isset($_SERVER['HTTP_ACCEPT'])) {
            $accepts = explode(',', $_SERVER['HTTP_ACCEPT']);
        }
        if ($accepts && 'application/json' === array_shift($accepts)) {
            wp_send_json_success($next_url);
        } else {
            wp_safe_redirect(html_entity_decode($next_url));
        }
    }

    /**
     * Runs whenever an alert is published. Sends notifications to an alerter's
     * response team informing them of the alert.
     *
     * @param int $post_id The WordPress post ID of the published alert.
     */
    public function publishAlert ($post_id) {
        $incident_hash = get_post_meta($post_id, $this->prefix . 'incident_hash', true);
        if (empty($incident_hash)) { $incident_hash = $this->getIncidentHash(); }
        $responder_link = admin_url(
            '?page=' . $this->prefix . 'review-alert'
            . '&' . $this->prefix . 'incident_hash=' . $incident_hash
        );
        $responder_short_link = home_url(
            '?' . str_replace('_', '-', $this->prefix) . 'alert='
            . substr($incident_hash, 0, 8)
        );

        $alerter = get_userdata(get_post_field('post_author', $post_id));
        $guardians = $this->getGuardians($alerter->ID);
        foreach ($guardians as $guardian) {
            $sms = preg_replace('/[^0-9]/', '', get_user_meta($guardian->ID, $this->prefix . 'sms', true));
            $sms_provider = get_user_meta($guardian->ID, $this->prefix . 'sms_provider', true);
            $headers = array(
                'From: "' . $alerter->display_name . '" <' . $alerter->user_email . '>'
            );
            $subject = get_post_field('post_title', $post_id);

            // TODO: Write a more descriptive message.
            wp_mail($guardian->user_email, $subject, $responder_link, $headers);

            // Send an email that will be converted to an SMS by the
            // telco company if the guardian has provided an emergency txt number.
            if (!empty($sms) && !empty($sms_provider)) {
                $sms_max_length = 160;
                // We need to ensure that SMS notifications fit within the 160 character
                // limit of SMS transmissions. Since we're using email-to-SMS gateways,
                // a subject will be wrapped inside of parentheses, making it two chars
                // longer than whatever its original contents are. Then a space is
                // inserted between the subject and the message body. The total length
                // of strlen($subject) + 2 + 1 + strlen($message) must be less than 160.
                $extra_length = 3; // two parenthesis and a space
                // but in practice, there seems to be another 7 chars eaten up somewhere?
                $extra_length += 7;
                $url_length = strlen($responder_short_link);
                $full_length = strlen($subject) + $extra_length + $url_length;
                if ($full_length > $sms_max_length) {
                    // truncate the $subject since the link must be fully included
                    $subject = substr($subject, 0, $sms_max_length - $url_length - $extra_length);
                }
                wp_mail(
                    $sms . $this->getSmsEmailGatewayDomain($sms_provider),
                    $subject,
                    $responder_short_link,
                    $headers
                );
            }
        }
    }

    /**
     * Responds to Ajax POSTs containing new position information of responders/alerter.
     * Sends back the location of all responders to this alert.
     */
    public function handleLocationUpdate () {
        check_ajax_referer($this->prefix . 'incident-nonce', $this->prefix . 'nonce');
        $new_position = $_POST['pos'];
        $alert_post = $this->getAlert($_POST['incident_hash']);
        $me = wp_get_current_user();
        $mkey = ($me->ID == $alert_post->post_author) ? 'alerter_location': "responder_{$me->ID}_location";
        update_post_meta($alert_post->ID, $this->prefix . $mkey, $new_position);

        $alerter = get_userdata($alert_post->post_author);
        $alerter_info = array(
            'id' => $alert_post->post_author,
            'geo' => get_post_meta($alert_post->ID, $this->prefix . 'alerter_location', true),
            'display_name' => $alerter->display_name,
            'avatar_url' => get_avatar_url($alerter->ID, array('size' => 32))
        );
        $phone_number = get_user_meta($alert_post->post_author, $this->prefix . 'sms', true);
        if (!empty($phone_number)) {
            $alerter_info['call'] = $phone_number;
        }
        $data = array($alerter_info);
        wp_send_json_success(array_merge($data, $this->getResponderInfo($alert_post)));
    }

    public function handleMediaUpload () {
        check_ajax_referer($this->prefix . 'incident-nonce', $this->prefix . 'nonce');

        $post = $this->getAlert($_GET[$this->prefix . 'incident_hash']);
        $keys = array_keys($_FILES);
        $k  = array_shift($keys);
        $id = media_handle_upload($k, $post->ID);
        $m = wp_get_attachment_metadata($id);
        return (is_wp_error($id))
            ? wp_send_json_error($id)
            : wp_send_json_success(array(
                'id' => $id,
                'media_type' => substr(
                    $m['sizes']['thumbnail']['mime-type'], 0, strpos($m['sizes']['thumbnail']['mime-type'], '/')
                ),
                'html' => wp_get_attachment_image($id)
            ));
    }

    public function handleDismissInstaller () {
        check_ajax_referer($this->prefix . 'incident-nonce', $this->prefix . 'nonce');

        update_user_meta(get_current_user_id(), $this->prefix . 'installer-dismissed', true);
    }

    /**
     * Retrieves an array of responder metadata for an alert.
     *
     * @param object $alert_post The WP_Post object of the alert.
     * @return array
     */
    public function getResponderInfo ($alert_post) {
        $responders = $this->getIncidentResponders($alert_post);
        $res = array();
        foreach ($responders as $responder_id) {
            $responder_data = get_userdata($responder_id);
            $this_responder = array(
                'id' => $responder_id,
                'display_name' => $responder_data->display_name,
                'avatar_url' => get_avatar_url($responder_id, array('size' => 32)),
                'geo' => $this->getResponderGeoLocation($alert_post, $responder_id)
            );
            $phone_number = get_user_meta($responder_id, $this->prefix . 'sms', true); 
            if (!empty($phone_number)) {
                $this_responder['call'] = $phone_number;
            }
            $res[] = $this_responder;
        }
        return $res;
    }

    public function addContactInfoFields ($user_contact) {
        $user_contact[$this->prefix . 'sms'] = esc_html__('Phone number', 'better-angels');
        $user_contact[$this->prefix . 'pronoun'] = esc_html__('Gender pronoun', 'better-angels');
        return $user_contact;
    }

    public function addProfileFields () {
        require_once 'pages/profile.php';
    }
    public function updateProfileFields () {
        // TODO: Whitelist valid providers.
        update_user_meta(get_current_user_id(), $this->prefix . 'sms_provider', $_POST[$this->prefix . 'sms_provider']);
        update_user_meta(get_current_user_id(), $this->prefix . 'call_for_help', strip_tags($_POST[$this->prefix . 'call_for_help']));

        if (!empty($_POST[$this->prefix . 'public_responder'])) {
            update_user_meta(get_current_user_id(), $this->prefix . 'public_responder', 1);
        } else {
            delete_user_meta(get_current_user_id(), $this->prefix . 'public_responder');
        }
    }

    // TODO: Write help screens.
    public function registerContextualHelp () {
        $screen = get_current_screen();
    }

    /**
     * Detects an alert "short URL," which is a GET request with a special querystring parameter
     * that matches the first 8 characters of an alert's incident hash value and, if matched,
     * redirects to the full URL of that particular alert, then `exit()`s.
     *
     * @return void
     */
    public function redirectShortUrl () {
        $get_param = str_replace('_', '-', $this->prefix) . 'alert';
        if (!empty($_GET[$get_param]) && 8 === strlen($_GET[$get_param])) {
            $post = $this->getAlert(urldecode($_GET[$get_param]));
            $full_hash = get_post_meta($post->ID, $this->prefix . 'incident_hash', true);
            if ($full_hash) {
                wp_safe_redirect(admin_url(
                    '?page=' . $this->prefix . 'review-alert'
                    . '&' . $this->prefix . 'incident_hash=' . urlencode($full_hash)
                ));
                exit();
            }
        }
    }

    /**
     * Gets alert posts with an incident hash.
     *
     * @return array
     */
    public function getActiveAlerts () {
        return get_posts(array(
            'post_type' => str_replace('-', '_', $this->prefix) . 'alert',
            'meta_key' => $this->prefix . 'incident_hash'
        ));
    }

    /**
     * Gets scheduled alert posts.
     *
     * @param int $uid The WordPress user ID of an author's scheduled posts to look up.
     * @return array
     */
    public function getScheduledAlerts ($uid = false) {
        $args = array(
            'post_type' => str_replace('-', '_', $this->prefix) . 'alert',
            'post_status' => 'future'
        );
        if (false !== $uid) {
            $args['author'] = $uid;
        }
        return get_posts($args);
    }

    public function addIncidentMenu () {
        global $wp_admin_bar;

        $alerts = array(
            'my_alerts' => array(),
            'my_responses' => array(),
            'my_scheduled_alerts' => array()
        );
        foreach ($this->getActiveAlerts() as $post) {
            if (get_current_user_id() == $post->post_author) {
                $alerts['my_alerts'][] = $post;
            } else if (in_array(get_current_user_id(), $this->getIncidentResponders($post))) {
                $alerts['my_responses'][] = $post;
            }
        }
        foreach ($this->getScheduledAlerts(get_current_user_id()) as $post) {
            if (get_current_user_id() == $post->post_author) {
                $alerts['my_scheduled_alerts'][] = $post;
            }
        }

        if (!empty($alerts['my_alerts']) || !empty($alerts['my_responses']) || !empty($alerts['my_scheduled_alerts'])) {
            $wp_admin_bar->add_menu(array(
                'id' => $this->prefix . 'active-incidents-menu',
                'title' => __('Active alerts', 'better-angels')
            ));
        }

        // Add group nodes to WP Toolbar
        foreach ($alerts as $group_name => $posts) {
            $wp_admin_bar->add_group(array(
                'id' => $this->prefix . $group_name,
                'parent' => $this->prefix . 'active-incidents-menu'
            ));
        }

        $dtfmt = get_option('date_format') . ' ' . get_option('time_format');
        foreach ($alerts['my_alerts'] as $post) {
            $author = get_userdata($post->post_author);
            $url = wp_nonce_url(
                admin_url('?page=' . $this->prefix . 'incident-chat&' . $this->prefix . 'incident_hash=' . get_post_meta($post->ID, $this->prefix . 'incident_hash', true)),
                $this->prefix . 'chat', $this->prefix . 'nonce'
            );
            $wp_admin_bar->add_node(array(
                'id' => $this->prefix . 'active-incident-' . $post->ID,
                'title' => sprintf(__('My alert on %2$s', 'better-angels'), $author->display_name, date($dtfmt, strtotime($post->post_date))),
                'parent' => $this->prefix . 'my_alerts',
                'href' => $url
            ));
        }

        foreach ($alerts['my_responses'] as $post) {
            $author = get_userdata($post->post_author);
            $url = wp_nonce_url(
                admin_url('?page=' . $this->prefix . 'incident-chat&' . $this->prefix . 'incident_hash=' . get_post_meta($post->ID, $this->prefix . 'incident_hash', true)),
                $this->prefix . 'chat', $this->prefix . 'nonce'
            );
            $wp_admin_bar->add_node(array(
                'id' => $this->prefix . 'active-incident-' . $post->ID,
                'title' => sprintf(__('Alert issued by %1$s on %2$s', 'better-angels'), $author->display_name, date($dtfmt, strtotime($post->post_date))),
                'parent' => $this->prefix . 'my_responses',
                'href' => $url
            ));
        }

        foreach ($alerts['my_scheduled_alerts'] as $post) {
            $url = wp_nonce_url(
                admin_url('admin-ajax.php?action=' . $this->prefix . 'unschedule-alert&alert_id=' . $post->ID . '&r=' . esc_url($_SERVER['REQUEST_URI'])),
                $this->prefix . 'unschedule-alert', $this->prefix . 'nonce'
            );
            $wp_admin_bar->add_node(array(
                'id' => $this->prefix . 'scheduled-alert-' . $post->ID,
                'title' => sprintf(__('Cancel scheduled alert for %1$s','better-angels'), date($dtfmt, strtotime($post->post_date))),
                'meta' => array(
                    'title' => __('Cancel this alert', 'better-angels')
                ),
                'parent' => $this->prefix . 'my_scheduled_alerts',
                'href' => $url
            ));
        }
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
                case 'alert_ttl_num':
                    if ($v > 0) {
                        $safe_input[$k] = intval($v);
                    } else {
                        $safe_input[$k] = $this->default_alert_ttl_num;
                    }
                    break;
                case 'alert_ttl_multiplier':
                case 'future_alerts':
                case 'delete_old_incident_media':
                case 'debug':
                    $safe_input[$k] = intval($v);
                    break;
            }
        }
        return $safe_input;
    }

    private function printUsersForDataList () {
        $args = array();
        if (!current_user_can('list_users')) {
            $args = array(
                'meta_key' => $this->prefix . 'public_responder',
                'meta_value' => 1
            );
        }
        $users = get_users($args);
        foreach ($users as $usr) {
            if ($usr->ID !== get_current_user_id() && !$this->isGuardian($usr->ID, get_current_user_id())) {
                print "<option value=\"{$usr->user_nicename}\">";
            }
        }
    }

    /**
     * Get the guardians (resonse team members who receive alerts) for a given user.
     *
     * @return array An array of WP_User objects.
     */
    public function getGuardians ($user_id) {
        $team = $this->getResponseTeam($user_id);
        $guardians = array();
        foreach ($team as $u) {
            $info = $u->{$this->prefix . 'guardian_info'};
            if (!empty($info['receive_alerts']) && !empty($info['confirmed'])) {
                $guardians[] = $u;
            }
        }
        return $guardians;
    }

    /**
     * Checks to see if a user account is on the response team of a user.
     *
     * @param string $guardian_ID The WP user ID of the account to check.
     * @param string $user_id The WP user ID of the user whose team to check.
     * @return bool True if $guardian_login is the username of a team member for the current user.
     */
    public function isGuardian ($guardian_id, $user_id) {
        $team = $this->getResponseTeam($user_id);
        foreach ($team as $user) {
            if ($guardian_id === $user->ID) {
                return true;
            }
        }
        return false;
    }

    private function getUserGenderPronoun ($user_id) {
        $pronoun = get_user_meta($user_id, $this->prefix . 'pronoun', true);
        return (empty($pronoun)) ? 'their' : $pronoun;
    }

    /**
     * Sends a notification (by email) asking a user for confirmation to join a response team.
     *
     * @param int $guardian_id The user to notify.
     * @return bool Same as wp_mail()'s return value.
     */
    private function sendGuardianRequest ($guardian_id) {
        $curr_user = wp_get_current_user();
        // Send an email notification to the guardian asking for permission to be added to team.
        $g = get_userdata($guardian_id);
        $subject = sprintf(
            __('%1$s wants you to join %2$s crisis response team.', 'better-angels'),
            $curr_user->display_name, $this->getUserGenderPronoun($curr_user->ID)
        );
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
     * @param int $guardian_id The WP user ID of the user who is set as a guardian.
     * @param int $user_id The WP user ID of the user being guarded.
     * @return mixed
     */
    public function addGuardian ($guardian_id, $user_id) {
        $err = new WP_Error();
        if (in_array($guardian_id, get_user_meta($user_id, $this->prefix . 'guardians'))) {
            $err->add(
                'duplicate-guardian',
                __('Cannot add the same user as a guardian twice.', 'better-angels'),
                $guardian_id
            );
        }
        if (false === get_userdata($guardian_id)) {
            $err->add(
                'no-such-user',
                __('No such user account.', 'better-angels'),
                $guardian_id
            );
        }
        if (get_current_user_id() == $guardian_id || $user_id === $guardian_id) {
            $err->add(
                'cannot-guard-self',
                __('Cannot add yourself as your own guardian.', 'better-angels'),
                $guardian_id
            );
        }

        if (empty($err->errors)) {
            add_user_meta($user_id, $this->prefix . 'guardians', $guardian_id);
            $gk = $this->prefix . 'guardian_' . $guardian_id . '_info';
            add_user_meta($user_id, $gk, array('confirmed' => false));
        } else {
            return $err;
        }
    }

    public function getGuardianInfo ($guardian_id, $user_id) {
        $info = get_user_meta($user_id, $this->prefix . 'guardian_' . $guardian_id . '_info', true);
        if (is_array($info) && !array_key_exists('confirmed', $info)) {
            $info['confirmed'] = false;
        }
        return $info;
    }

    public function setGuardianInfo ($guardian_id, $user_id, $info_arr) {
        $cur_info = $this->getGuardianInfo($guardian_id, $user_id);
        $new_info = array_replace($cur_info, $info_arr);
        return update_user_meta($user_id, $this->prefix . 'guardian_' . $guardian_id . '_info', $new_info);
    }

    /**
     * Removes a user from being the guardian of another user.
     *
     * @param string $guardian_id WP user name of the account to remove.
     * @param int $user_id WP user ID number of the team owner.
     * @return void
     */
    public function removeGuardian ($guardian_id, $user_id) {
        delete_user_meta($user_id, $this->prefix . 'guardians', $guardian_id);
        delete_user_meta($user_id, $this->prefix . 'guardian_' . $guardian_id . '_info');
    }

    /**
     * Get an array of users who are on a particular user's team.
     *
     * @param int $user_id User ID of the team owner.
     * @return array List of WP_User objects comprising all users.
     */
    public function getResponseTeam ($user_id) {
        $full_team = array_map(
            'get_userdata', get_user_meta($user_id, $this->prefix . 'guardians')
        );
        foreach ($full_team as $k => $g) {
            $info = $this->getGuardianInfo($g->ID, $user_id);
            $prop = $this->prefix . 'guardian_info';
            $full_team[$k]->$prop = $info;
        }
        return $full_team;
    }

    private function updateChooseAngels ($request) {
        $user_id = get_current_user_id();
        $wp_user = get_userdata($user_id);

        // Anything to edit/toggle type?
        if (!empty($request[$this->prefix . 'guardian'])) {
            foreach ($request[$this->prefix . 'guardian'] as $id => $data) {
                $this->setGuardianInfo(
                    $id,
                    $user_id,
                    array('receive_alerts' => (bool)$data['receive_alerts'])
                );
            }
        }

        // Anything to delete?
        // Delete before adding!
        $all_my_guardians = array_map(
            'get_userdata', get_user_meta($user_id, $this->prefix . 'guardians')
        );

        if (!empty($request[$this->prefix . 'my_guardians'])) {
            foreach ($all_my_guardians as $guard) {
                if (!in_array($guard->ID, $request[$this->prefix . 'my_guardians'])) {
                    $this->removeGuardian($guard->ID, $user_id);
                }
            }
        } else { // delete all guardians
            delete_user_meta($user_id, $this->prefix . 'guardians');
            foreach ($all_my_guardians as $guard) {
                delete_user_meta($user_id, $this->prefix . 'guardian_' . $guard->ID . '_info');
            }
        }

        // Anything to add?
        if (!empty($request[$this->prefix . 'add_guardian'])) {
            $ginfo = (isset($request[$this->prefix . 'is_fake_guardian']))
                ? array('receive_alerts' => false) : array('receive_alerts' => true);
            $guardian_id = username_exists($request[$this->prefix . 'add_guardian']);
            // add the user if a valid username was entered
            if ($guardian_id) {
                $this->setupGuardian($guardian_id, $user_id, $ginfo);
            } else if (is_email($request[$this->prefix . 'add_guardian'])) {
                $user = get_user_by('email', $request[$this->prefix . 'add_guardian']);
                if ($user) {
                    $this->setupGuardian($user->ID, $user_id, $ginfo);
                } else {
                    $subject = sprintf(
                        __('%1$s invites you to join the Buoy emergency response alternative on %2$s!', 'better-angels'),
                        $wp_user->display_name,
                        get_bloginfo('name')
                    );
                    $msg = __('Buoy is a community-driven emergency dispatch and response technology. It is designed to connect people in crisis with trusted friends, family, and other nearby allies who can help. We believe that in situations where traditional emergency services are not available, reliable, trustworthy, or sufficient, communities can come together to aid each other in times of need.', 'better-angels');
                    $msg .= "\n\n";
                    $msg .= sprintf(
                        __('%1$s wants you to join %2$s crisis response team.', 'better-angels'),
                        $wp_user->display_name, $this->getUserGenderPronoun($wp_user->ID)
                    );
                    $msg .= "\n\n";
                    $msg .= __('To join, sign up for an account here:', 'better-angels');
                    $msg .= "\n\n" . wp_registration_url();
                    wp_mail($request[$this->prefix . 'add_guardian'], $subject, $msg);
                    $this->Error->add(
                        'unknown-email',
                        sprintf(esc_html__('You have invited %s to join this Buoy site, but they are not yet on your response team. Contact them privately (such as by phone or txt) to make sure they created an account. Then come back here and add them again.', 'better-angels'), $request[$this->prefix . 'add_guardian']),
                        $request[$this->prefix . 'add_guardian']
                    );
                }
            }
        }
    }

    /**
     * Sets up a guardian relationship between two users.
     *
     * @param int $guardian_id The user ID number of the guardian.
     * @param int $user_id The user ID number of the user being guarded.
     * @param array $settings Additional metadata to set for the guardian.
     * @return void
     */
    private function setupGuardian ($guardian_id, $user_id, $settings) {
        $this->addGuardian($guardian_id, $user_id);
        $this->setGuardianInfo($guardian_id, $user_id, $settings);
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
            esc_html_e('You do not have sufficient permissions to access this page.', 'better-angels');
            return;
        }
        require_once 'pages/activate-alert.php';
    }

    public function renderReviewAlertPage () {
        if (empty($_GET[$this->prefix . 'incident_hash'])) {
            return;
        }
        $alert_post = $this->getAlert($_GET[$this->prefix . 'incident_hash']);
        if (!current_user_can('read') || !$this->isGuardian(get_current_user_id(), $alert_post->post_author)) {
            esc_html_e('You do not have sufficient permissions to access this page.', 'better-angels');
            return;
        }
        require_once 'pages/review-alert.php';
    }

    /**
     * Retrieves an alert post from the WordPress database.
     *
     * @param string $incident_hash An incident hash string, at least 8 characters long.
     * @return WP_Post|null
     */
    public function getAlert ($incident_hash) {
        if (strlen($incident_hash) < 8) { return NULL; }
        $posts = get_posts(array(
            'post_type' => str_replace('-', '_', $this->prefix) . 'alert',
            'meta_key' => $this->prefix . 'incident_hash',
            'meta_value' => "^$incident_hash",
            'meta_compare' => 'REGEXP'
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
        $alert_post = $this->getAlert(urldecode($_GET[$this->prefix . 'incident_hash']));
        if (!$alert_post || !current_user_can('read') || !isset($_GET[$this->prefix . 'nonce']) || !wp_verify_nonce($_GET[$this->prefix . 'nonce'], "{$this->prefix}chat")) {
            esc_html_e('You do not have sufficient permissions to access this page.', 'better-angels');
            return;
        }
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

    /**
     * Returns an HTML structure containing nested lists and list items
     * referring to any media attached to the given post ID.
     *
     * @param int $post_id The post ID from which to fetch attached media.
     * @return string HTML ready for insertion into an `<ul>` element.
     */
    private function getIncidentMediaList ($post_id) {
        $html = '';

        $posts = array(
            'video' => get_attached_media('video', $post_id),
            'image' => get_attached_media('image', $post_id),
            'audio' => get_attached_media('audio', $post_id)
        );

        foreach ($posts as $type => $set) {
            $html .= '<li class="' . esc_attr($type) . '">';
            switch ($type) {
                case 'video':
                    $html .= esc_html('Video attachments', 'better-angels');
                    break;
                case 'image':
                    $html .= esc_html('Image attachments', 'better-angels');
                    break;
                case 'audio':
                    $html .= esc_html('Audio attachments', 'better-angels');
                    break;
            }
            $html .= ' <span class="badge">' . count($set) . '</span>';
            $html .= '<ul>';

            foreach ($set as $post) {
                $html .= '<li id="incident-media-'. $post->ID .'">';
                $html .= wp_get_attachment_image($post->ID);
                $html .= '</li>';
            }

            $html .= '</ul>';
            $html .= '</li>';
        }

        return $html;
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

        // Join one or more teams.
        if (isset($_POST[$this->prefix . 'nonce']) && wp_verify_nonce($_POST[$this->prefix . 'nonce'], $this->prefix . 'update-teams')) {
            $join_teams = (empty($_POST[$this->prefix . 'join_teams'])) ? array() : $_POST[$this->prefix . 'join_teams'];
            foreach ($join_teams as $owner_id) {
                $this->setGuardianInfo(get_current_user_id(), $owner_id, array('confirmed' => true));
            }

            // Leave a team.
            if (!empty($_POST[$this->prefix . 'leave-team'])) {
                $this->removeGuardian(get_current_user_id(), username_exists($_POST[$this->prefix . 'leave-team']));
            }
        }

        require_once 'pages/confirm-guardianship.php';
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

new BetterAngelsPlugin();

/**
 * Helpers.
 */
if (!function_exists('wp_get_timezone_string')) {
    /**
    * Helper to retrieve the timezone string for a site until
    * a WP core method exists (see http://core.trac.wordpress.org/ticket/24730).
    *
    * Adapted from http://www.php.net/manual/en/function.timezone-name-from-abbr.php#89155
    * Copied from WooCommerce code:
    * https://github.com/woothemes/woocommerce/blob/5893875b0c03dda7b2d448d1a904ccfad3cdae3f/includes/wc-formatting-functions.php#L441-L485
    *
    * @return string valid PHP timezone string
    */
    function wp_get_timezone_string() {
        // if site timezone string exists, return it
        if ( $timezone = get_option( 'timezone_string' ) ) {
            return $timezone;
        }
        // get UTC offset, if it isn't set then return UTC
        if ( 0 === ( $utc_offset = get_option( 'gmt_offset', 0 ) ) ) {
            return 'UTC';
        }
        // adjust UTC offset from hours to seconds
        $utc_offset *= 3600;
        // attempt to guess the timezone string from the UTC offset
        $timezone = timezone_name_from_abbr( '', $utc_offset, 0 );
        // last try, guess timezone string manually
        if ( false === $timezone ) {
            $is_dst = date( 'I' );
            foreach ( timezone_abbreviations_list() as $abbr ) {
                foreach ( $abbr as $city ) {
                    if ( $city['dst'] == $is_dst && $city['offset'] == $utc_offset ) {
                        return $city['timezone_id'];
                    }
                }
            }
            // fallback to UTC
            return 'UTC';
        }
        return $timezone;
    }
}
