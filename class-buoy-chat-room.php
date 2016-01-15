<?php
/**
 * Buoy Alert chat room for built-in WordPress "chat" using comments.
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\WP_Buoy_Alert\WordPress_Chat
 *
 * @copyright Copyright (c) 2015-2016 by Meitar "maymay" Moscovitz
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

if (!defined('ABSPATH')) { exit; } // Disallow direct HTTP access.

/**
 * Loads and displays comments for built-in "chat" feature.
 */
class WP_Buoy_Chat_Room extends WP_Buoy_Plugin {

    /**
     * The visible chat messages.
     *
     * @var WP_Comment[]
     */
    private $_comments;

    /**
     * The alert associatd with this chat room.
     *
     * @var WP_Buoy_Alert
     */
    private $_alert;

    /**
     * Constructor.
     *
     * @param int|string $lookup
     */
    public function __construct ($lookup) {
        $this->_alert = new WP_Buoy_Alert($lookup);
        $this->_comments = get_comments(array(
            'post_id' => $this->_alert->wp_post->ID
        ));
        add_filter(self::$prefix . '_post_comments_chat_room_meta_refresh', array(__CLASS__, 'filterMetaRefresh'));
    }

    /**
     * Whether or not a given user is a responder for this alert.
     *
     * @param int $user_id
     *
     * @return bool
     */
    public function is_responder ($user_id) {
        return $this->_alert->is_responder($user_id);
    }

    /**
     * Whether or not a given user is the "owner" of the chat room.
     *
     * @param int $user_id
     *
     * @return bool
     */
    public function is_alerter ($user_id) {
        return $user_id == $this->_alert->wp_post->post_author;
    }

    /**
     * Retrieves the title of the alert for this chat room.
     *
     * @return string
     */
    public function get_title () {
        return $this->_alert->wp_post->post_title;
    }

    /**
     * Gets the comment HTML.
     *
     * @uses wp_parse_args()
     * @uses wp_list_comments()
     *
     * @param array $args Arguments to pass to `wp_list_comments()`
     *
     * @return string|void
     */
    public function list_comments ($args = array()) {
        add_filter('comment_class', array(__CLASS__, 'filterCommentClass'), 10, 5);
        $defaults = array(
            'reverse_top_level' => 'desc'
        );
        $args = wp_parse_args($args, $defaults);
        wp_list_comments($args, $this->_comments);
    }

    /**
     * Filters how quickly to refresh the chat room.
     *
     * @param int $refresh Refresh rate (in seconds).
     *
     * @return int
     */
    public static function filterMetaRefresh ($refresh) {
        $options = WP_Buoy_Settings::get_instance();
        if ($options->get('debug')) {
            return '';
        } else {
            return $refresh;
        }
    }

    /**
     * Adds our own class to each comment "chat message" output.
     *
     * @link https://developer.wordpress.org/reference/hooks/comment_class/
     *
     * @param string[] $classes
     *
     * @return string[]
     */
    public static function filterCommentClass ($classes) {
        $classes[] = self::$prefix . '-chat-message';
        return $classes;
    }

    /**
     * Renders a chat room.
     *
     * @global $buoy_chat_room
     *
     * @todo Remove this global. Maybe template-ize this a bit better
     *       with actual `load_template()` functions and similar to a
     *       WordPress front-end? That would let theme developers use
     *       their skills to customize the built-in chat room, too.
     *
     * @return void
     */
    public function render () {
        global $buoy_chat_room;

        require_once dirname(__FILE__) . '/templates/comments-chat-room.php';

        do_action('shutdown');
        exit();
    }

}
