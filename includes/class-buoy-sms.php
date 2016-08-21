<?php
/**
 * Buoy SMS messages.
 *
 * Integrates Buoy with SMS services and provides an API for handling
 * transmission and reception of txt messages.
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\SMS
 *
 * @copyright Copyright (c) 2016 by Meitar "maymay" Moscovitz
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

if (!defined('ABSPATH')) { exit; } // Disallow direct HTTP access.

/**
 * Class to make and send SMS messages via WordPress.
 */
class WP_Buoy_SMS {

    /**
     * Total number of characters a single SMS message can contain.
     *
     * @var int
     */
    const MAX_LENGTH = 160;

    /**
     * The users to whom the SMS should be sent.
     *
     * @var WP_Buoy_User[]
     */
    private $addressees = array();

    /**
     * The sender of the SMS.
     *
     * @var false|WP_Buoy_User
     */
    private $sender = false;

    /**
     * Array of addresses.
     *
     * @var string[]
     */
    private $to = array();

    /**
     * Extra headers to send.
     *
     * @var string[]
     */
    private $headers = array();

    /**
     * Contents of the SMS message.
     *
     * @var string
     */
    private $content = '';

    /**
     * Leftover SMS content after truncation, if any.
     *
     * @var string
     */
    private $excess = '';

    /**
     * WordPress filter hooks to remove prior to sending.
     *
     * @var array
     */
    private $wp_filters = array();

    /**
     * Constructor.
     */
    public function __construct () {
    }

    /**
     * Clears all stored SMS preparation so the object can be reused.
     */
    public function reset () {
        $this->addressees = $this->to = $this->headers = $this->wp_filters = array();
        $this->sender = false;
        $this->content = $this->excess = '';
    }

    /**
     * Gets an addressee.
     *
     * @param string|int $index An addressee index to get, if only one is wanted.
     *
     * @return array|WP_User
     */
    public function getAddressee ($index = false) {
        if (is_numeric($index)) {
            $index = (int) $index;
        }
        return (is_int($index))
            ? $this->addressees[$index]
            : $this->addressees;
    }

    /**
     * Adds another addressee to send the SMS message to.
     *
     * @param WP_User $addressee
     *
     * @return void
     */
    public function addAddressee ($addressee) {
        $this->addressees[] = $addressee;
    }

    /**
     * Sets the sender of the SMS.
     *
     * @param WP_User $user
     */
    public function setSender ($user) {
        $this->sender = $user;
    }

    /**
     * Adds a header to the sent message.
     *
     * @param string $header
     */
    public function addHeader ($header) {
        $this->headers[] = $header;
    }

    /**
     * Gets the contents of the SMS.
     *
     * @return string
     */
    public function getContent () {
        return $this->content;
    }

    /**
     * Sets the SMS message content.
     *
     * This can be longer than the maximum SMS length. If it is, the
     * message will get truncated and subsequent SMS messages will be
     * automatically sent, too.
     *
     * @param string $string
     */
    public function setContent ($string) {
        $this->content = $string;
    }

    /**
     * Register a WordPress filter to be stripped before sending.
     *
     * @param callable $filter_callback The callable hooked function or method to strip.
     * @param string $wp_hook The hook from which to strip the callable.
     */
    public function addStrippedFilter ($filter_callback, $wp_hook = 'wp_mail') {
        $priority = has_filter($wp_hook, $filter_callback);
        $num_args = false;
        $func = (is_array($filter_callback))
            ? implode('::', $filter_callback)
            : $filter_callback;

        if (false !== $priority) {
            $num_args = $GLOBALS['wp_filter'][$wp_hook][$priority][$func]['accepted_args'];
        }

        $this->wp_filters[] = array(
            'hook' => $wp_hook,
            'func' => $filter_callback,
            'priority' => $priority,
            'num_args' => $num_args,
        );
    }

    /**
     * Gets the message content that fits inside a single SMS/txt.
     *
     * Truncates the current SMS message if necessary and then sets
     * the contents to the remainder of the message.
     *
     * @return string
     */
    private function getMessage () {
        $content = $this->getContent();
        $msg = '';
        $len = strlen($content);

        if ($len > self::MAX_LENGTH) {
            $msg = substr($content, 0, self::MAX_LENGTH);
            $this->excess = substr($content, self::MAX_LENGTH);
        } else {
            $msg = $content;
            $this->excess = '';
        }

        $this->setContent($this->excess);
        return $msg;
    }

    /**
     * Removes registered filter.
     *
     * @param array $filter_record An array containing information about the attached filter.
     *
     * @see self::addStrippedFilter() for info on the $filter_record structure.
     */
    private function stripFilter ($filter_record) {
        remove_filter($filter_record['hook'], $filter_record['func']);
    }

    /**
     * Prepares the SMS message for sending.
     */
    private function prepare () {
        foreach ($this->getAddressee() as $Buoy_User) {
            $smsemail = $Buoy_User->get_sms_email();
            if ($smsemail) {
                $this->to[] = $smsemail;
            }
        }
        foreach ($this->wp_filters as $f) {
            $this->stripFilter($f);
        }

        // Ensure the "From" header uses an email address whose domain
        // part is this server. We deliberately replace the caller's
        // own email domain with the address of the WP server, because
        // many shared hosting environments on cheap systems filter
        // outgoing mail configured differnetly.
        for ($i = 0; $i < count($this->headers); $i++) {
            if (0 === stripos($this->headers[$i], 'From:')) {
                $p = explode('@', $this->headers[$i]);
                $last = array_pop($p);
                $is_bracketed = strpos('>', $last);
                $domain_part = trim(str_replace('>', '', $last));
                $from_domain = WP_Buoy_SMS_Email_Bridge::getThisServerDomain();
                if ($from_domain !== $domain_part) {
                    if ($is_bracketed) {
                        $from_domain .= '>';
                    }
                    $this->headers[$i] = implode('@', array_merge($p, array($from_domain)));
                }
            }
        }
    }

    /**
     * Restores WordPress filter state after SMS transmission.
     */
    private function finish () {
        foreach ($this->wp_filters as $f) {
            call_user_func_array('add_filter', $f);
        }
        $this->reset();
    }

    /**
     * Sends the SMS message and resets the object when done.
     */
    public function send () {
        // Never sign SMS messages with PGP.
        $this->addStrippedFilter(array('WP_PGP_Encrypted_Emails', 'wp_mail'));

        $this->prepare();
        do {
            wp_mail($this->to, '', $this->getMessage(), $this->headers);
        } while (!empty($this->excess));
        $this->finish();
    }

}
