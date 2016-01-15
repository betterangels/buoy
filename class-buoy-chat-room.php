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
     * Constructor.
     *
     * @param int|string $lookup
     */
    public function __construct ($lookup) {
        $alert = new WP_Buoy_Alert($lookup);
        $this->_comments = get_comments($alert->wp_post->ID);
    }

    /**
     * Gets the comment HTML.
     *
     * @return string|void
     */
    public function wp_list_comments () {
        $this->_comments_html = wp_list_comments(array(
            'format' => 'xhtml',
            'reverse_top_level' => 'desc',
            //'callback' => array(__CLASS__, 'renderComment')
        ), $this->_comments);
    }

    /**
     * Renders an individual comment.
     *
     * Used as a callback function from {@see https://developer.wordpress.org/reference/functions/wp_list_comments `wp_list_comments()`}.
     *
     * @param WP_Comment $comment
     * @param array $args
     * @param int $depth
     *
     * @return void
     */
    public static function renderComment ($comment, $args, $depth) {
        print '<li>';
        print $comment->comment_content;
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
    }

}
