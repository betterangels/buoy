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
     * Connects to an IMAP server with the settings from a given post.
     *
     * @param WP_Post $wp_post
     *
     * @return Horde_Imap_Client_Socket
     */
    private static function connectImap ($wp_post) {
        $settings = WP_Buoy_Settings::get_instance();
        // Connect to IMAP server.
        $imap_args = array(
            'username' => $wp_post->sms_email_bridge_username,
            'password' => $wp_post->sms_email_bridge_password,
            'hostspec' => $wp_post->sms_email_bridge_server,
            'port' => $wp_post->sms_email_bridge_port,
            'secure' => $wp_post->sms_email_bridge_connection_security,
        );
        if ($settings->get('debug')) {
            $imap_args['debug'] = WP_CONTENT_DIR.'/debug.log';
        }
        try {
            $imap_client = new Horde_Imap_Client_Socket($imap_args);
        } catch (Horde_Imap_Client_Exception $e) {
            // TODO: Handle Horde IMAP client instantiation exception.
        }

        return $imap_client;
    }

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
        $post = get_post($post_id);
        if (null === $post || empty($post->sms_email_bridge_enabled)) {
            return; // no post? bridge disabled? nothing to do!
        }

        // Get a list of confirmed team members with phone numbers.
        $team = new WP_Buoy_Team($post);
        $recipients = array();
        foreach ($team->get_confirmed_members() as $member) {
            $m = new WP_Buoy_User($member);
            if ($m->get_phone_number()) {
                $recipients[] = $m;
            }
        }

        $imap_client = self::connectImap($post);

        // Search IMAP server for any new new messages
        // that are `From` any of the team member's numbers
        $imap_query = new Horde_Imap_Client_Search_Query();
        $queries = array();

        foreach ($recipients as $rcpt) {
            $q = new Horde_Imap_Client_Search_Query();
            $q->headerText('From', $rcpt->get_phone_number());
            // and that we haven't yet "read"
            $q1 = new Horde_Imap_Client_Search_Query();
            $q1->flag(Horde_Imap_Client::FLAG_SEEN, false);
            $q->andSearch($q1);

            $queries[] = $q;
        }

        $imap_query->orSearch($queries);

        try {
            $results = $imap_client->search('INBOX', $imap_query);
        } catch (Horde_Imap_Client_Exception $e) {
            // TODO: Handle IMAP client error gracefully?
        }

        // Fetch the content of each message we found
        if ($results['count']) {
            $f = new Horde_Imap_Client_Fetch_Query();
            $f->fullText();
            try {
                $fetched = $imap_client->fetch('INBOX', $f, array(
                    'ids' => $results['match']
                ));
            } catch (Horde_Imap_Client_Exception $e) {
                // TODO: Handle fetch error.
            }

            foreach ($fetched as $data) {
                // get the body's plain text content
                $message = Horde_Mime_Part::parseMessage($data->getFullMsg());
                $body_id = $message->findBody();
                $part = $message->getPart($body_id);
                $txt = $part->getContents();

                // and get the sender's number
                $h = Horde_Mime_Headers::parseHeaders($data->getFullMsg());
                $from_phone = $h->getHeader('From')->getAddressList(true)->first()->mailbox;

                // forward the body text to each member of the team,
                $SMS = new WP_Buoy_SMS();
                $tmp_user = new WP_User;
                $tmp_user->display_name = $from_phone;
                $SMS->setSender($tmp_user);
                $SMS->setContent($txt);
                foreach ($recipients as $rcpt) {
                    // except the person who it was sent by.
                    if ($from_phone !== $rcpt->get_phone_number()) {
                        $SMS->addAddressee($rcpt);
                    }
                }
                $SMS->send();
            }
        }
    }

}
