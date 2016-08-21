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
 * Class to schedule, poll, and forward messages over IMAP to an SMS.
 */
class WP_Buoy_SMS_Email_Bridge {

    /**
     * The WordPress hook name ("tag").
     *
     * @var string
     */
    const hook = 'buoy_sms_email_bridge_run';

    /**
     * The back-off timing multiplier.
     *
     * @var int
     */
    const backoff_multiplier = 2;

    /**
     * The back-off time step in seconds.
     *
     * @var int
     */
    const backoff_time_step = 30;

    /**
     * The maximum number of seconds to backoff for.
     *
     * @var int
     */
    const backoff_max_seconds = 600; // 10 minutes

    /**
     * The mapping of known SMS provider services and their public
     * SMS-to-Email gateway domains.
     *
     * @var string[]
     */
    private static $sms_provider_to_email_map = array(
        'AT&T' => 'txt.att.net',
        'Alltel' => 'message.alltel.com',
        'Boost Mobile' => 'myboostmobile.com',
        'Cricket' => 'sms.mycricket.com',
        'Metro PCS' => 'mymetropcs.com',
        'Nextel' => 'messaging.nextel.com',
        'Ptel' => 'ptel.com',
        'Qwest' => 'qwestmp.com',
        'Sprint' => array(
            'messaging.sprintpcs.com',
            'pm.sprint.com'
        ),
        'Suncom' => 'tms.suncom.com',
        "The People's Operator (CDMA)" => 'messaging.sprintpcs.com',
        "The People's Operator (GSM)" => 'mailmymobile.net',
        'T-Mobile' => 'tmomail.net',
        'Tracfone' => 'mmst5.tracfone.com',
        'U.S. Cellular' => 'email.uscc.net',
        'Verizon' => 'vtext.com',
        'Virgin Mobile' => 'vmobl.com',
    );

    /**
     * Connects to an IMAP server with the settings from a given post.
     *
     * @param WP_Post $wp_post
     *
     * @return Horde_Imap_Client_Socket
     */
    private static function connectImap ($wp_post) {
        $settings = WP_Buoy_Settings::get_instance();
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
            if ($settings->get('debug')) {
                error_log(__CLASS__ . ' failed to instantiate IMAP client.');
            }
        }
        return $imap_client;
    }

    /**
     * Registers bridge to WordPress API.
     */
    public static function register () {
        add_action(self::hook, array(__CLASS__, 'run'), 10, 2);
    }

    /**
     * Retrieves plain message text from MIME-encoded emails.
     *
     * @param Horde_Imap_Client_Data_Fetch $horde_fetched_data
     *
     * @return string
     */
    public static function getMessagePlainText ($horde_fetched_data) {
        $plain_text_content = '';

        $email_text = $horde_fetched_data->getFullMsg();
        $mime_hdrs = Horde_Mime_Headers::parseHeaders($email_text);
        $mime_part = Horde_Mime_Part::parseMessage($email_text);
        $type_hdr = $mime_hdrs->getHeader('Content-Type');

        // Horde_Mime_Part doesn't seem to deal well with MIME mail
        // that is "multipart/related" so parse that ourselves.
        if ($type_hdr && 'multipart/related' === $type_hdr->value_single) {
            $boundary = $type_hdr->params['boundary'];
            $lines = preg_split('/\R/', $mime_part->getContents());
            $split_parts = array();
            $i = -1;

            // parse raw email source into MIME parts by boundary
            foreach ($lines as $line) {
                if (!empty($line) && false !== strpos($line, $boundary)) {
                    $i = $i + 1;
                    $line_type = 'header_lines';
                    $split_parts[$i] = array(
                        'header_lines' => array(),
                        'body_lines'   => array(),
                    );
                    continue;
                } else if ('header_lines' === $line_type && empty($line)) {
                    $line_type = 'body_lines';
                    continue;
                }
                $split_parts[$i][$line_type][] = $line;
            }

            // find the MIME part that has text/plain encoding
            $part_key = false;
            foreach ($split_parts as $i => $part) {
                if(count(preg_grep('/Content-Type:\s*text\/plain/i', $part['header_lines']))) {
                    $part_key = $i;
                    break;
                }
            }

            $text_part = $split_parts[$part_key];

            // find if the body part is encoded
            $encoding = array();
            foreach ($text_part['header_lines'] as $line) {
                preg_match('/Content-Transfer-Encoding:\s*(.*)$/i', $line, $encoding);
                if (isset($encoding[1])) {
                    $encoding = $encoding[1];
                    break;
                }
            }

            // decode if necessary
            $lines = array();
            foreach ($text_part['body_lines'] as $line) {
                switch ($encoding) {
                    case 'base64':
                        $lines[] = base64_decode($line);
                        break;
                    default:
                        $lines[] = $line;
                            break;
                }
            }
            $plain_text_content = implode('', $lines);
        } else {
            $body_id = $mime_part->findBody();
            $body_part = $mime_part->getPart($body_id);
            $plain_text_content = $body_part->getContents();
        }

        return $plain_text_content;
    }

    /**
     * Parses email headers to retrieve the `From` mailbox address.
     *
     * @param Horde_Imap_Client_Data_Fetch $horde_fetched_data
     *
     * @return string
     */
    public static function getSenderNumber ($horde_fetched_data) {
        $h = Horde_Mime_Headers::parseHeaders($horde_fetched_data->getFullMsg());
        return $h->getHeader('From')->getAddressList(true)->first()->mailbox;
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

        $settings = WP_Buoy_Settings::get_instance();

        // Get a list of confirmed team members with phone numbers.
        $team = new WP_Buoy_Team($post);
        $recipients = array();
        $recipients[] = $team->get_team_owner(); // and the Team owner
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
            $q_unseen = new Horde_Imap_Client_Search_Query();
            $q_unseen->flag(Horde_Imap_Client::FLAG_SEEN, false);
            $q->andSearch($q_unseen);

            // Also search for this number with a "+1" prefix
            $q1 = new Horde_Imap_Client_Search_Query();
            $q1->headerText('From', '+1'.$rcpt->get_phone_number());
            // and that we haven't yet "read"
            $q_unseen = new Horde_Imap_Client_Search_Query();
            $q_unseen->flag(Horde_Imap_Client::FLAG_SEEN, false);
            $q1->andSearch($q_unseen);

            $queries[] = $q;
            $queries[] = $q1;
        }

        $imap_query->orSearch($queries);

        try {
            $results = $imap_client->search('INBOX', $imap_query);
        } catch (Horde_Imap_Client_Exception $e) {
            if ($settings->get('debug')) {
                error_log($e->raw_msg);
            }
        }

        // Fetch the content of each message we found
        if (isset($results) && $results['count']) {
            $f = new Horde_Imap_Client_Fetch_Query();
            $f->fullText();
            try {
                $fetched = $imap_client->fetch('INBOX', $f, array(
                    'ids' => $results['match']
                ));
                $SMS = new WP_Buoy_SMS();
                foreach ($fetched as $data) {
                    $txt = self::getMessagePlainText($data);
                    // strip any "+1" prefix
                    $from_phone = preg_replace('/^\+1/', '', self::getSenderNumber($data));
                    $sender = WP_Buoy_User::getByPhoneNumber($from_phone);

                    // forward the body text to each member of the team,
                    self::forward($SMS, "{$sender->wp_user->display_name}: $txt", $recipients,
                        // TODO: If this returns `false` then we must deal
                        //       with the resulting Fatal Error in self::forward()
                        $sender,
                        array(
                            // Set the From header so as to create a thread for each Team
                            "From: \"{$sender->wp_user->display_name}\" <{$post->post_name}@".self::getThisServerDomain().'>',
                            // This breaks Verizon's Email->SMS gateway. :(
                            // TODO: How do we get auto-reply addressing to work?
                            //'Reply-To: '.$post->sms_email_bridge_address
                        )
                    );
                }
                // since there was a new message to forward,
                // schedule another run with reset back-off counter.
                self::scheduleNext($post_id, 0);
            } catch (Horde_Imap_Client_Exception $e) {
                // TODO: Handle fetch error.
            }
        } else { // couldn't get any new messages
            self::scheduleNext($post_id, get_post_meta($post_id, 'sms_email_bridge_backoff_step', true));
        }
    }

    /**
     * Schedules the next run for the given team.
     *
     * This method uses an adaptive recheck algorithm similar to TCP's
     * adaptive retransmission timer.
     *
     * @uses self::getNextRunTime Implements the adaptive timing algorithm.
     *
     * @param int $post_id
     * @param int $backoff_step
     */
    public static function scheduleNext ($post_id, $backoff_step = 0) {
        $backoff_step = absint($backoff_step);
        $time = self::getNextRunTime($backoff_step);

        $settings = WP_Buoy_Settings::get_instance();
        if ($settings->get('debug')) {
            $msg = sprintf(
                'Scheduling %s run for post ID %s at %s (back-off step is %s)',
                __CLASS__,
                $post_id,
                date('r', $time),
                $backoff_step
            );
            error_log($msg);
        }

        wp_schedule_single_event($time, self::hook, array($post_id));
        $next_step = (0 === $backoff_step) ? 1 : $backoff_step * self::backoff_multiplier;
        update_post_meta($post_id, 'sms_email_bridge_backoff_step', $next_step);
    }

    /**
     * Unschedules the next run of the bridge for the given team post.
     *
     * @param int $post_id
     */
    public static function unscheduleNext ($post_id) {
        $settings = WP_Buoy_Settings::get_instance();
        if ($settings->get('debug')) {
            error_log('Unscheduling '.__CLASS__.' run for post ID '.$post_id);
        }
        delete_post_meta($post_id, 'sms_email_bridge_backoff_step');
        if ($next_time = wp_next_scheduled(self::hook, array($post_id))) {
            wp_unschedule_event($next_time, self::hook, array($post_id));
        }
    }

    /**
     * Determines when the next run should be.
     *
     * This is implemented by providing a "back-off timer" value as a
     * counter beginning from 0. When 0 is passed, the back-off value
     * is equal to the time step. Otherwise, the counter is multiplied
     * by a multiplier (usually 2).
     *
     * This creates the following situation when the time step is 30 seconds
     * and the multiplier value is 2:
     *
     *     Run number 1, back-off counter 0, next run in 30 seconds
     *     Run number 2, back-off counter 1, next run in 1 minute
     *     Run number 3, back-off counter 2, next run in 2 minutes
     *     Run number 4, back-off counter 4, next run in 4 minutes
     *     Run number 5, back-off counter 8, next run in 8 minutes
     *
     * Total elapsed time for five runs is 15 minutes and 30 seconds.
     * When message activity is detected, we reset the counter to 0.
     *
     * This algorithm helps ensure we don't overload the remote server
     * but still lets us detect the presence and then forward messages
     * relatively quickly when an active conversation is taking place.
     *
     * The algorithm above is similar to TCP's adaptive retransmission
     * algorithm. (Research that algorithm for more insight on this.)
     *
     * @param int $backoff_step
     *
     * @return int
     */
    private static function getNextRunTime ($backoff_step) {
        $backoff = (0 === $backoff_step)
            ? self::backoff_time_step
            : (self::backoff_time_step * ($backoff_step * self::backoff_multiplier));
        if ($backoff > self::backoff_max_seconds) {
            $backoff = self::backoff_max_seconds;
        }
        return time() + $backoff;
    }

    /**
     * Forwards a text message to a set of recipients.
     *
     * @param WP_Buoy_SMS $SMS The `WP_Bouy_SMS` object to use.
     * @param string $text
     * @param WP_Buoy_User[] $recipients
     * @param WP_Buoy_User $sender
     * @param string[] $headers Extra headers to set.
     */
    private static function forward ($SMS, $text, $recipients, $sender, $headers = array()) {
        $SMS->setContent($text);
        foreach ($headers as $header) {
            $SMS->addHeader($header);
        }
        foreach ($recipients as $rcpt) {
            // don't address to the sender
            if ($sender->get_phone_number() !== $rcpt->get_phone_number()) {
                $SMS->addAddressee($rcpt);
            }
        }
        $SMS->setSender($sender);
        $SMS->send();
    }

    /**
     * Utility function to return the domain name portion of a given
     * telco's email-to-SMS gateway address.
     *
     * The returned string includes the prefixed `@` sign.
     *
     * @param string $provider A recognized `sms_provider` key.
     *
     * @return string
     */
    public static function getEmailToSmsGatewayDomain ($provider) {
        if (is_array(self::$sms_provider_to_email_map[$provider])) {
            $domain = array_rand(self::$sms_provider_to_email_map[$provider]);
        } else {
            $domain = self::$sms_provider_to_email_map[$provider];
        }
        return '@'.$domain;
    }

    /**
     * Gets the list of known SMS providers we can send email through.
     *
     * @return string[]
     */
    public static function getSmsProviders () {
        return array_keys(self::$sms_provider_to_email_map);
    }

    /**
     * Get the site domain and get rid of "www."
     *
     * We deliberately replace the user's own email address with the
     * address of the WP server, because many shared hosting environments
     * on cheap systems filter outgoing mail configured differnetly.
     *
     * @return string
     */
    public static function getThisServerDomain () {
        $d = strtolower( $_SERVER['SERVER_NAME'] );
        if ( substr( $d, 0, 4 ) == 'www.' ) {
            $d = substr( $d, 4 );
        }
        return $d;
    }

}
