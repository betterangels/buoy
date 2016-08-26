<?php
/**
 * Buoy Notification
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\WP_Buoy_Notification
 *
 * @copyright Copyright (c) 2015-2016 by Meitar "maymay" Moscovitz
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

if (!defined('ABSPATH')) { exit; } // Disallow direct HTTP access.
/**
 * Class responsible for sending notifications triggered by the right
 * events via the right mechanisms.
 */
class WP_Buoy_Notification extends WP_Buoy_Plugin {

    /**
     * Constructor.
     *
     * @return WP_Buoy_Notification
     */
    public function __construct () {
    }

    /**
     * @return void
     */
    public static function register () {
        add_action('publish_' . self::$prefix . '_team', array(__CLASS__, 'inviteUsers'), 10, 2);
        add_action('private_' . self::$prefix . '_team', array(__CLASS__, 'inviteUsers'), 10, 2);
        add_action(self::$prefix . '_team_member_added', array(__CLASS__, 'addedToTeam'), 10, 3);
        add_action(self::$prefix . '_team_member_removed', array(__CLASS__, 'removedFromTeam'), 10, 2);

    }

    /**
     * Schedules a notification to be sent to the user.
     *
     * @param int|string $who
     * @param WP_Buoy_Team $team
     * @param bool $notify Whether or not to schedule a notification.
     *
     * @return void
     */
    public static function addedToTeam ($who, $team, $notify = true) {
        if ($notify) {
            add_post_meta($team->wp_post->ID, '_' . self::$prefix . '_notify', $who, false);
        }

        // Call the equivalent of the "status_type" hook since adding
        // a member may have happened after publishing the post itself.
        // This catches any just-added members.
        do_action("{$team->wp_post->post_status}_{$team->wp_post->post_type}", $team->wp_post->ID, $team->wp_post);
    }

    /**
     * Removes any scheduled notices to be sent to the user.
     *
     * @param int $user_id
     * @param WP_Buoy_Team $team
     *
     * @return void
     */
    public static function removedFromTeam ($user_id, $team) {
        delete_post_meta($team->wp_post->ID, '_' . self::$prefix . '_notify', $user_id);
    }

    /**
     * Invites users added to a team when it is published.
     *
     * @todo Support inviting via other means than email.
     *
     * @uses wp_mail()
     *
     * @param int $post_id
     * @param WP_Post $post
     */
    public static function inviteUsers ($post_id, $post) {
        $team      = new WP_Buoy_Team($post_id);
        $buoy_user = new WP_Buoy_User($post->post_author);

        $to_notify = array_unique(get_post_meta($post_id, '_' . self::$prefix . '_notify'));

        foreach ($to_notify as $x) {
            if (is_email($x)) {
                self::inviteNewUser($team, $x);
            } else {
                // TODO: Write a better message.
                $subject = sprintf(
                    __('%1$s wants you to join %2$s crisis response team.', 'buoy'),
                    $buoy_user->wp_user->display_name, $buoy_user->get_pronoun()
                );
                $msg = admin_url(
                    'edit.php?post_type=' . $team->wp_post->post_type . '&page=' . self::$prefix . '_team_membership'
                );
                $user = get_userdata($x);
                wp_mail($user->user_email, $subject, $msg);
            }
            delete_post_meta($post_id, '_' . self::$prefix . '_notify', $x);
        }
    }

    /**
     * Sends an email inviting a new user to join this Buoy.
     *
     * @param WP_Buoy_Team $team
     * @param string $email
     *
     * @return void
     */
    public static function inviteNewUser ($team, $email) {
        $buoy_user = new WP_Buoy_User($team->wp_post->post_author);
        $subject = sprintf(
            __('%1$s invites you to join the Buoy emergency response alternative on %2$s!', 'buoy'),
            $buoy_user->wp_user->display_name, get_bloginfo('name')
        );
        $msg = __('Buoy is a community-based crisis response system. It is designed to connect people in need with trusted friends, family, and other nearby allies who can help. We believe that in situations where traditional emergency services are not available, reliable, trustworthy, or sufficient, communities can come together to aid each other in times of need.', 'buoy');
        $msg .= "\n\n";
        $msg .= sprintf(
            __('%1$s wants you to join %2$s crisis response team.', 'buoy'),
            $buoy_user->wp_user->display_name, $buoy_user->get_pronoun()
        );
        $msg .= "\n\n";
        $msg .= __('To join, sign up for an account here:', 'buoy');
        $msg .= "\n\n" . wp_registration_url();
        wp_mail($email, $subject, $msg);
    }

    /**
     * Runs whenever an alert is published. Sends notifications to an
     * alerter's response team informing them of the alert.
     *
     * @global array $wp_filter
     *
     * @param int $post_id
     * @param WP_Post $post
     *
     * @return void
     */
    public static function publishAlert ($post_id, $post) {
        $alert = new WP_Buoy_Alert($post_id);

        $responder_link = admin_url(
            '?page=' . self::$prefix . '_review_alert'
            . '&' . self::$prefix . '_hash=' . $alert->get_hash()
        );
        $responder_short_link = home_url(
            '?' . self::$prefix . '_alert='
            . substr($alert->get_hash(), 0, 8)
        );
        $subject = $post->post_title;

        $alerter = get_userdata($post->post_author);
        $headers = array(
            "From: \"{$alerter->display_name}\" <{$alerter->user_nicename}@".WP_Buoy_SMS_Email_Bridge::getThisServerDomain().'>',
        );

        $SMS = new WP_Buoy_SMS();
        $SMS->setSender($alerter);
        $SMS->setContent("$responder_short_link $subject");
        foreach ($headers as $header) {
            $SMS->addHeader($header);
        }

        foreach ($alert->get_teams() as $team_id) {
            $team = new WP_Buoy_Team($team_id);
            foreach ($team->get_confirmed_members() as $user_id) {
                $responder = new WP_Buoy_User($user_id);

                // TODO: Write a more descriptive message.
                wp_mail($responder->wp_user->user_email, $subject, $responder_link, $headers);

                $smsemail = $responder->get_sms_email();
                if (!empty($smsemail)) {
                    $SMS->addAddressee($responder);
                }
            }
        }

        $SMS->send();
    }

}
