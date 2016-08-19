<?php
/**
 * Buoy User
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\WP_Buoy_User
 *
 * @copyright Copyright (c) 2015-2016 by Meitar "maymay" Moscovitz
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

if (!defined('ABSPATH')) { exit; } // Disallow direct HTTP access.

/**
 * Manages interaction between WordPress API and Buoy user settings.
 *
 * @todo Make better use of WP_User's magic accessors, which can also
 *       be used to automatically fetch usermeta settings.
 */
class WP_Buoy_User extends WP_Buoy_Plugin {

    /**
     * The WordPress user.
     *
     * @var WP_User
     */
    public $wp_user;

    /**
     * The user's plugin settings.
     *
     * @var WP_Buoy_User_Settings
     */
    private $options;

    /**
     * The user's teams.
     *
     * @var int[]
     */
    private $teams;

    /**
     * Constructor.
     *
     * @uses get_userdata()
     * @uses WP_Buoy_User_Settings
     *
     * @param int $user_id
     *
     * @return WP_Buoy_User
     *
     * @throws Exception if the provided `$user_id` does not reference a valid `WP_User` object.
     */
    public function __construct ($user_id) {
        $this->wp_user = get_userdata($user_id);
        if (false === $this->wp_user) {
            throw new Exception(sprintf(
                __('Invalid user ID: %s', 'buoy'),
                $user_id
            ));
        }
        $this->options = new WP_Buoy_User_Settings($this->wp_user);
    }

    /**
     * Gets a Buoy User object from a given phone number.
     *
     * @param string $phone_number
     *
     * @return false|WP_Buoy_User
     */
    public static function getByPhoneNumber ($phone_number) {
        $phone = implode('.?', str_split(self::sanitize_phone_number($phone_number)));
        $users = get_users(array(
            'meta_key' => 'buoy_phone_number',
            'meta_value' => $phone,
            'meta_compare' => 'REGEXP',
        ));
        return (empty($users)) ? false : new self($users[0]->ID);
    }

    /**
     * Gets the user's (published) teams.
     *
     * @return int[]
     */
    public function get_teams () {
        $this->teams = get_posts(array(
            'post_type' => self::$prefix . '_team',
            'post_status' => array('publish', 'private'),
            'author' => $this->wp_user->ID,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        return $this->teams;
    }

    /**
     * Gets the user's default teams.
     *
     * @uses get_posts()
     *
     * @return int[]
     */
    public function get_default_teams () {
        return get_posts(array(
            'author'      => $this->wp_user->ID,
            'fields'      => 'ids',
            'meta_key'    => self::$prefix.'_default_team',
            'meta_value'  => true,
            'numberposts' => -1,
            'post_type'   => self::$prefix.'_team',
        ));
    }

    /**
     * Checks whether or not the user has at least one responder.
     *
     * A "responder" in this context is a "confirmed" team member.
     * At least one responder is needed before the "Activate Alert"
     * screen will be of any use, obviously. This looks for confirmed
     * members on any of the user's teams and returns as soon as it
     * can find one.
     *
     * @uses WP_Buoy_Team::has_responder()
     *
     * @return bool
     */
    public function has_responder () {
        if (null === $this->teams) {
            $this->get_teams();
        }
        // We need a loop here because, unless we use straight SQL,
        // we can't do a REGEXP compare on the `meta_key`, only the
        // `meta_value` itself. There's an experimental way to do it
        // over on Stack Exchange but this is more standard for now.
        //
        // See https://wordpress.stackexchange.com/a/193841/66139
        foreach ($this->teams as $team_id) {
            $team = new WP_Buoy_Team($team_id);
            if ($team->has_responder()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Alias of WP_Buoy_User::get_gender_pronoun_possessive()
     *
     * @uses WP_Buoy_User::get_gender_pronoun_possessive()
     *
     * @return string
     */
    public function get_pronoun () {
        return $this->get_gender_pronoun_possessive();
    }

    /**
     * Gets the possessive gender pronoun of a user.
     *
     * @uses WP_Buoy_User::get_option()
     * @uses sanitize_text_field()
     *
     * @return string
     */
    public function get_gender_pronoun_possessive () {
        return sanitize_text_field($this->get_option('gender_pronoun_possessive', __('their', 'buoy')));
    }

    /**
     * Get a user's pre-defined crisis message, or a default message if empty.
     *
     * @uses WP_Buoy_User::get_option()
     * @uses sanitize_text_field()
     *
     * @return string
     */
    public function get_crisis_message () {
        $msg = sanitize_text_field($this->get_option('crisis_message'));
        return ($msg) ? $msg : __('Please help!', 'buoy');
    }

    /**
     * Gets a user's email-to-SMS address based on their profile.
     *
     * @return string
     */
    public function get_sms_email () {
        $sms_email = '';

        $sms = $this->get_phone_number();
        $provider = $this->get_option('sms_provider');

        if (!empty($sms) && !empty($provider)) {
            $sms_email = $sms . WP_Buoy_SMS_Email_Bridge::getEmailToSmsGatewayDomain($provider);
        }


        return $sms_email;
    }

    /**
     * Retrieves info about this user's last known state associated
     * with an alert that they have responded to.
     *
     * @param int $alert_id
     *
     * @return array
     */
    public function get_incident_response_info ($alert_id) {
        $alert = new WP_Buoy_Alert($alert_id);
        $r = array(
            'id' => $this->wp_user->ID,
            'display_name' => $this->wp_user->display_name,
            'avatar_url' => get_avatar_url($this->wp_user->ID, array('size' => 32)),
            'geo' => $alert->get_responder_geo($this->wp_user->ID)
        );
        if ($phone = $this->get_phone_number()) {
            $r['call'] = $phone;
        }
        return $r;
    }

    /**
     * Gets a user's phone number, without dashes or other symbols.
     *
     * @uses WP_Buoy_User::get_option()
     * @uses sanitize_text_field()
     *
     * @return string
     */
    public function get_phone_number () {
        return self::sanitize_phone_number($this->get_option('phone_number', ''));
    }

    /**
     * Returns a sanitized phone number (no dashes or symbols).
     *
     * @param string $phone_number
     *
     * @return string
     */
    private static function sanitize_phone_number ($phone_number) {
        return sanitize_text_field(preg_replace('/[^0-9]/', '', $phone_number));
    }

    /**
     * Gets the value of a user option they have set.
     *
     * @uses WP_Buoy_User_Settings::get()
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     *
     * @access private
     */
    private function get_option ($name, $default = null) {
        return $this->options->get($name, $default);
    }

    /**
     * Registers user-related WordPress hooks.
     *
     * @uses WP_Buoy_Plugin::addHelpTab()
     *
     * @return void
     */
    public static function register () {
        add_action('load-profile.php', array('WP_Buoy_Plugin', 'addHelpTab'));
        add_action('show_user_profile', array(__CLASS__, 'renderProfile'));
        add_action('personal_options_update', array(__CLASS__, 'saveProfile'));

        add_action(self::$prefix . '_team_emptied', array(__CLASS__, 'warnIfNoResponder'));
    }

    /**
     * Sends a warning to a user if they no longer have responders.
     *
     * @uses WP_Buoy_User::has_responder()
     *
     * @param WP_Buoy_Team $team The team that has been emptied.
     *
     * @return bool
     */
    public static function warnIfNoResponder ($team) {
        $buoy_user = new self($team->wp_post->post_author);
        if (false === $buoy_user->has_responder()) {
            // TODO: This should be a bit cleaner. Maybe part of the WP_Buoy_Notification class?
            $subject = __('You no longer have crisis responders.', 'buoy');
            $msg = __('Either you have removed the last of your Buoy crisis response team members, or they have all left your teams. You will not be able to send a Buoy alert to anyone until you add more people to your team(s).', 'buoy');
            wp_mail($buoy_user->wp_user->user_email, $subject, $msg);
        }
    }

    /**
     * Prints the HTML for the custom profile fields.
     *
     * @param WP_User $profileuser
     *
     * @uses WP_Buoy_User_Settings::get()
     *
     * @return void
     */
    public static function renderProfile ($profileuser) {
        $usropt = new WP_Buoy_User_Settings($profileuser);
        require_once dirname(dirname(__FILE__)).'/pages/profile.php';
    }

    /**
     * Saves profile field values to the database on profile update.
     *
     * @global $_POST Used to access values submitted by profile form.
     *
     * @param int $user_id
     *
     * @uses WP_Buoy_User_Settings::set()
     * @uses WP_Buoy_User_Settings::save()
     *
     * @return void
     */
    public static function saveProfile ($user_id) {
        $options = new WP_Buoy_User_Settings($user_id);
        $options
            ->set('gender_pronoun_possessive', sanitize_text_field($_POST[self::$prefix.'_gender_pronoun_possessive']))
            ->set('phone_number', sanitize_text_field($_POST[self::$prefix . '_phone_number']))
            ->set('sms_provider', sanitize_text_field($_POST[self::$prefix . '_sms_provider']))
            ->set('crisis_message', sanitize_text_field($_POST[self::$prefix . '_crisis_message']))
            ->set('public_responder', (isset($_POST[self::$prefix . '_public_responder'])) ? true : false)
            ->save();
    }

    /**
     * Returns the HTML for a Bootstrap Panel of the user's teams.
     *
     * @return string
     */
    public function renderChooseTeamsPanelHtml () {
        $teams = $this->get_teams();
?>
<div class="panel panel-default">
    <div class="panel-heading" role="tab" id="">
        <h3 class="panel-title">
            <?php esc_html_e('Choose teams', 'buoy');?>
        </h3>
    </div>
    <div class="panel-body">
        <table class="table" summary="<?php esc_attr_e('Your teams with responders', 'buoy');?>">
            <thead>
                <tr>
                    <th></th>
                    <th><?php esc_html_e('Team name', 'buoy');?></th>
                    <th><?php esc_html_e('Responders', 'buoy');?></th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($teams as $team_id) : $team = new WP_Buoy_Team($team_id); if (!$team->has_responder()) { continue; } ?>
                <tr>
                    <td>
                        <input type="checkbox"
                            id="<?php print esc_attr(self::$prefix);?>_team-<?php print esc_attr($team_id);?>"
                            name="<?php print esc_attr(self::$prefix);?>_teams[]"
                            <?php checked($team->is_default());?>
                            value="<?php print esc_attr($team_id);?>"
                        />
                    </td>
                    <td>
                        <label for="<?php print esc_attr(self::$prefix);?>_team-<?php print esc_attr($team_id);?>">
                            <?php print esc_html($team->wp_post->post_title);?>
                            <?php if ('private' === $team->wp_post->post_status) { ?>
                                — <?php esc_html_e('Private', 'buoy');?>
                            <?php } ?>
                        </label>
                    </td>
                    <td>
                        <?php print esc_html(count($team->get_confirmed_members()));?>
                    </td>
                </tr>
<?php endforeach;?>
            </tbody>
        </table>
    </div>
</div>
<?php
    }

}
