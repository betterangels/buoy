<?php
/**
 * This file is the front-end for the built-in comments-as-chat-room.
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\WP_Buoy_Alert\WordPress_Chat
 *
 * @copyright Copyright (c) 2015-2016 by Meitar "maymay" Moscovitz
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

// TODO: This should become a "real" template, but for now, we just
//       empty the major front-end template hooks so we have a clean
//       slate from which to define a simple HTML "template."
remove_all_actions('wp_head');
remove_all_actions('wp_footer');

add_action('wp_head', 'wp_print_styles');
add_action('wp_head', 'wp_print_head_scripts');
wp_enqueue_style(
    self::$prefix . '-chat-room',
    plugins_url('/comments-chat-room.css', __FILE__),
    array(),
    null
);
wp_enqueue_script(
    self::$prefix . '-chat-room',
    plugins_url('/comments-chat-room.js', __FILE__),
    array(),
    null
);
?><!DOCTYPE html>
<html <?php language_attributes();?>>
<head>
    <?php
    /**
     * Filters the refresh rate of the chat room (in seconds).
     *
     * Return an empty string from this filter to effectively disable
     * automatic refreshing of the chat room.
     *
     * @todo The default refresh rate could (should?) become an admin
     *       option configurable via the plugin's settings page.
     *
     * @param int $refresh Default is `5` seconds.
     */
    ?>
    <!-- TODO:
         Is there a way to go to the #page-footer upon reresh by setting the url here?
         Placing it in the meta tag here doesn't seem to work (browser ignores it?)
    -->
    <meta http-equiv="refresh" content="<?php print esc_attr(apply_filters(self::$prefix . '_post_comments_chat_room_meta_refresh', 5));?>;url=<?php print esc_attr($_SERVER['REQUEST_URI'])?>" />
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width" />
    <title><?php print sprintf(esc_html__('Buoy chat: %s', 'buoy'), $buoy_chat_room->get_title());?></title>
	<?php wp_head();?>
</head>
<body <?php body_class(); // TODO: Make the comments list a Microformats2 `h-feed` structure. ?>>
    <div id="page">
        <header id="masthead" class="site-header" role="banner">
            <h1 class="site-title"><?php print esc_html($buoy_chat_room->get_title());?></h1>
        </header>

        <section class="chat-messages">
            <ul>
                <?php $buoy_chat_room->list_comments();?>
            </ul>
        </section>

        <footer id="page-footer">
            <?php wp_footer();?>
        </footer>
    </div>
</body>
