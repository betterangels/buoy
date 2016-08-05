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
 * Concrete SMS class for use in the Buoy WordPress plugin.
 */
class WP_Buoy_SMS {

    /**
     * Total number of characters a single SMS message can contain.
     *
     * @var int
     */
    const MAX_LENGTH = 160;

    /**
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
     * @param WP_User $user
     */
    public function setSender ($user) {
        $this->sender = $user;
    }

    public function getContent () {
        return $this->content;
    }

    public function setContent ($string) {
        $this->content = $string;
    }

    /**
     * Register a WordPress filter to be stripped before sending.
     *
     * @param string|array $wp_hook
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

    private function truncate ($content) {
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
    }

    /**
     * Sends the SMS message using WordPress built-in email functions.
     */
    private function transmit () {
        // Get the site domain and get rid of "www." We deliberately
        // replace the user's own email address with the address of
        // the WP server, because many shared hosting environments on
        // cheap systems filter outgoing mail configured differnetly.
        $from_domain = strtolower( $_SERVER['SERVER_NAME'] );
        if ( substr( $from_domain, 0, 4 ) == 'www.' ) {
            $from_domain = substr( $from_domain, 4 );
        }
        $headers = array(
            "From: \"{$this->sender->display_name}\" <wordpress@{$from_domain}>"
        );
        wp_mail($this->to, '', $this->truncate($this->getContent()), $headers);
    }

    /**
     * Restores WordPress filter state after SMS transmission.
     */
    private function finish () {
        foreach ($this->wp_filters as $f) {
            call_user_func_array('add_filter', $f);
        }
    }

    /**
     * Sends the SMS message.
     */
    public function send () {
        $this->prepare();
        do {
            $this->transmit();
        } while (!empty($this->excess));
        $this->finish();
    }

}
