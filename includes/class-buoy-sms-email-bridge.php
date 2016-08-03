<?php
/**
 * Buoy SMS to Email Bridge
 *
 * Class for interacting with email accounts and forwarding as TXTs.
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\SMS_Email_Bridge
 *
 * @copyright Copyright (c) 2016 by Meitar "maymay" Moscovitz
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

if (!defined('ABSPATH')) { exit; } // Disallow direct HTTP access.

/**
 * Class for the bridge's interaction with WordPress.
 */
class WP_Buoy_SMS_Email_Bridge {

    /**
     * Registers bridge tools to the WordPress UI.
     */
    public static function register () {
        add_management_page(
            esc_html__('Buoy SMS-Email Bridge', 'buoy'),
            esc_html__('Buoy Team sms/txts', 'buoy'),
            'manage_options',
            'buoy_sms_email_bridge_tool',
            array(__CLASS__, 'renderSmsEmailBridgeToolPage')
        );
    }

    public static function renderSmsEmailBridgeToolPage () {
        include dirname(__FILE__).'/../pages/tool-page-sms-email-bridge.php';
    }

    /**
     * Performs a runtime check of an email address for SMS messages.
     *
     * This method is called by the WP-Cron system to perform a check
     * of an given team's SMS/txt email account.
     *
     * @param int $post_id The ID of the post ("team") whose settings to use.
     */
    public static function run ($post_id) {
        $settings = WP_Buoy_Settings::get_instance();

        $post = get_post($post_id);
        if (null === $post || empty($post->sms_email_bridge_enabled)) {
            return; // no post? bridge disabled? nothing to do!
        }

        $imap_args = array(
            'username' => $post->sms_email_bridge_username,
            'password' => $post->sms_email_bridge_password,
            'hostspec' => $post->sms_email_bridge_server,
            'port' => $post->sms_email_bridge_port,
            'secure' => $post->sms_email_bridge_connection_security,
        );
        if ($settings->get('debug')) {
            $imap_args['debug'] = WP_CONTENT_DIR.'/debug.log';
        }
        try {
            $imap_client = new Horde_Imap_Client_Socket($imap_args);
        } catch (Horde_Imap_Client_Exception $e) {
            // TODO: Handle Horde IMAP client instantiation exception.
        }

        $query = new Horde_Imap_Client_Search_Query();
        $query->flag(Horde_Imap_Client::FLAG_SEEN, false);

        try {
            $results = $imap_client->search('INBOX', $query);
            var_dump($results);
        } catch (Horde_Imap_Client_Exception $e) {
            var_dump($e);
        }

    }

}
