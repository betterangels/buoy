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
    private $comments;

    /**
     * The alert associatd with this chat room.
     *
     * @var WP_Buoy_Alert
     */
    private $alert;

    /**
     * Constructor.
     *
     * @param int|string $lookup
     */
    public function __construct ($lookup) {
        $this->alert = new WP_Buoy_Alert($lookup);
        $this->comments = get_comments(array(
            'post_id' => $this->getPostId()
        ));
    }

    /**
     * Gets the ID of the WordPress post.
     *
     * @return int
     */
    public function getPostId () {
        return $this->alert->wp_post->ID;
    }

    /**
     * Whether or not a given user is a responder for this alert.
     *
     * @param int $user_id
     *
     * @return bool
     */
    public function is_responder ($user_id) {
        return $this->alert->is_responder($user_id);
    }

    /**
     * Whether or not a given user is the "owner" of the chat room.
     *
     * @param int $user_id
     *
     * @return bool
     */
    public function is_alerter ($user_id) {
        return $user_id == $this->alert->wp_post->post_author;
    }

    /**
     * Retrieves the title of the alert for this chat room.
     *
     * @return string
     */
    public function get_title () {
        return $this->alert->wp_post->post_title;
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
            'reverse_top_level' => 'desc',
            'callback' => array(__CLASS__, 'renderComment')
        );
        $args = wp_parse_args($args, $defaults);
        wp_list_comments($args, $this->comments);
    }

    /**
     * Renders an individual comment.
     *
     * @param WP_Comment $comment
     * @param array $args
     * @param int $depth
     *
     * @return void
     */
    public static function renderComment ($comment, $args, $depth) {
        $side = (get_current_user_id() == $comment->user_id) ? 'right': 'left';
        print '<li id="comment-'.esc_attr($comment->comment_ID).'" ';
        print comment_class("media media-on-$side", $comment, $comment->comment_post_ID, false).'>';
        switch ($side) {
            case 'right': // Body first, then media.
                WP_Buoy_Chat_Room::renderCommentBody($comment, $args, $depth);
                WP_Buoy_Chat_Room::renderCommentMedia($side, $comment, $args, $depth);
                break;
            default: // Media first, then body.
                WP_Buoy_Chat_Room::renderCommentMedia($side, $comment, $args, $depth);
                WP_Buoy_Chat_Room::renderCommentBody($comment, $args, $depth);
                break;
        }
        // omit closing `</li>`, WordPress adds it automatically
    }

    /**
     * Renders an individual comment's media element.
     *
     * @param string $align Either `left` or `right`
     * @param WP_Comment $comment
     * @param array $args
     * @param int $depth
     *
     * @return void
     */
    protected static function renderCommentMedia ($align, $comment, $args, $depth) {
?>
    <div class="media-<?php print esc_attr($align);?> media-bottom vcard">
        <span class="comment-author fn"><?php comment_author($comment);?></span>
        <a href="mailto:<?php print esc_attr(comment_author_email($comment));?>">
            <?php print get_avatar($comment, 48, '', false, array(
                'class' => 'media-object'
            ));?>
        </a>
    </div>
<?php
    }

    /**
     * Renders an individual comment's body.
     *
     * @param WP_Comment $comment
     * @param array $args
     * @param int $depth
     *
     * @return void
     */
    protected static function renderCommentBody ($comment, $args, $depth) {
        $time_ago = human_time_diff(get_comment_time('U'), current_time('timestamp')).' '.__('ago');
?>
    <div class="media-body">
        <?php comment_text($comment, $args);?>
        <footer>
            <time datetime="<?php print esc_attr(get_comment_time('c'));?>"><?php print esc_html($time_ago);?></time>
        </footer>
    </div>
<?php
    }

    /**
     * Outputs the <meta> tag for refreshing the chat room automatically.
     *
     * @todo The default refresh rate could (should?) become an admin
     *       option configurable via the plugin's settings page.
     *
     *       Is there a way to go to the #page-footer upon reresh by setting the url here?
     *       Placing it in the meta tag here doesn't seem to work (browser ignores it?)
     *
     * @return void
     */
    public static function renderMetaRefresh () {
        /**
         * Filters the chat room refresh rate.
         */
        $refresh = apply_filters(self::$prefix.'_chat_room_meta_refresh_rate', 5);

        /**
         * Filters the URL to which the chat room reloads to.
         */
        $url     = esc_attr(apply_filters(self::$prefix.'_chat_room_meta_refresh_url', $_SERVER['REQUEST_URI']));

        $html = '<noscript><meta http-equiv="refresh" content="%1$s;url=%2$s" /></noscript>';
        $options = WP_Buoy_Settings::get_instance();
        if ($options->get('debug')) {
            return; // don't print anything
        }
        print sprintf($html, $refresh, str_replace('&reset', '', $url));
    }

    /**
     * Adds "do_form_reset" to the body class for new chat reloads.
     *
     * @link https://developer.wordpress.org/reference/hooks/body_class/
     *
     * @param string[] $classes
     *
     * @return string[]
     */
    public static function filterBodyClass ($classes) {
        // If we're posting a new comment, then we tell the parent frame to
        // reset the form field.
        if (isset($_GET['reset'])) {
            $classes[] = 'do_form_reset';
        }
        $classes[] = 'wp-core-ui'; // for dismissible notices
        return $classes;
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
        $classes[] = self::$prefix.'-chat-message';
        return $classes;
    }

    /**
     * Spruces up the comment text when displaying a comment.
     *
     * Chat messages are stored as comments in the WordPress database
     * so this filter takes the text (typically a single line) and
     * adds some basic expected functionality like turning links to
     * images into inline images, and parsing simple markdown.
     *
     * @link https://developer.wordpress.org/reference/hooks/comment_text/
     *
     * @uses Parsedown::text()
     * @uses links_add_target()
     *
     * @param string $comment_text
     *
     * @return string
     */
    public static function filterCommentText ($comment_text) {
        // Detect any URLs that point to recognized images, and embed them.
        $pat = '!(?:([^:/?#\s]+):)?(?://([^/?#]*))?([^?#\s]*\.(jpe?g|JPE?G|gif|GIF|png|PNG))(?:\?([^#]*))?(?:#(.*))?!';
        $rep = '<a href="$0"><img src="$0" alt="['.sprintf(esc_attr__('%1$s image from %2$s', 'buoy'), '.$4 ', '$2').']" style="max-width:100%;" /></a>';
        $comment_text = preg_replace($pat, $rep, $comment_text);

        // Finally, parse the result as markdown for more formatting
        if (!class_exists('Parsedown')) {
            require_once dirname(__FILE__).'/vendor/wp-screen-help-loader/vendor/parsedown/Parsedown.php';
        }
        $comment_text = Parsedown::instance()->text($comment_text);

        return links_add_target($comment_text);
    }

    /**
     * Renders a chat room.
     *
     * @return void
     */
    public function render () {
        // TODO: This should become a "real" template, but for now, we just
        //       empty the major front-end template hooks so we have a clean
        //       slate from which to define a simple HTML "template."
        remove_all_actions('wp_head');
        remove_all_actions('wp_footer');

        // Then we re-add only the script we actually need for a very
        // minimalist "chat room" template page.
        add_action('wp_head', array(__CLASS__, 'renderMetaRefresh'), 10, 2);
        add_action('wp_head', 'wp_print_styles');
        add_action('wp_head', 'wp_print_head_scripts');
        add_action('wp_head', 'rest_register_scripts', -100); // -100 cuz that's how the REST API plugin does it

        WP_Buoy_Alert::enqueueBootstrapFramework();
        wp_enqueue_style(
            self::$prefix.'-chat-room',
            plugins_url('../templates/comments-chat-room.css', __FILE__),
            array('colors'),
            null
        );
        wp_enqueue_script(
            self::$prefix.'-chat-room',
            plugins_url('../templates/comments-chat-room.js', __FILE__),
            array('common', 'wp-api'),
            null
        );
        wp_localize_script(self::$prefix.'-chat-room', self::$prefix.'_chat_room_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'api_base' => site_url('/?rest_route=/wp/v2')
        ));

        add_filter('body_class', array(__CLASS__, 'filterBodyClass'));
        add_filter('comment_text', array(__CLASS__, 'filterCommentText'), 5); // early priority

        require_once dirname(__FILE__).'/../templates/comments-chat-room.php';

        do_action('shutdown');
        exit();
    }

}
