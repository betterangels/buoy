<?php
/**
 * Buoy Team
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\Teams
 *
 * @copyright Copyright (c) 2015-2016 by Meitar "maymay" Moscovitz
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

if (!defined('ABSPATH')) { exit; } // Disallow direct HTTP access.

/**
 * Main class for a Buoy Team.
 *
 * Teams are groups/lists of potential responders managed by one user
 * who invites them to join said team. They are implemented as a WP
 * custom post type.
 */
class WP_Buoy_Team extends WP_Buoy_Plugin {

    /**
     * The team post.
     *
     * @var WP_Post
     */
    public $wp_post;

    /**
     * The team owner.
     *
     * @var WP_User
     */
    private $author;

    /**
     * An array of WP_User objects representing the team membership.
     *
     * The members list include those who are not yet confirmed (pending).
     *
     * @var WP_User[]
     */
    private $members = array();

    /**
     * Addresses of people without accounts who were invited to join.
     *
     * @var string[]
     */
    private $invitees;

    /**
     * Constructor.
     *
     * @param int $team_id The post ID of the team.
     *
     * @return WP_Buoy_Team
     */
    public function __construct ($team_id) {
        $this->wp_post = get_post($team_id);
        $this->members = array_map('get_userdata', array_unique(get_post_meta($this->wp_post->ID, '_team_members')));
        $this->invitees = array_unique(get_post_meta($this->wp_post->ID, '_invitees'));
        $this->author = get_userdata($this->wp_post->post_author);
    }

    /**
     * Gets team member IDs by state.
     *
     * For the "invited" state, an ID is an email address.
     *
     * @param array $states Defaults to `array('confirmed', 'unconfirmed', 'invited')`.
     *
     * @return array[]|WP_Error 2D array of state => IDs
     */
    public function get_members_in_states ($states = array()) {
        $defaults = array('confirmed', 'unconfirmed', 'invited');
        $states = wp_parse_args($states, $defaults);
        $ret = array();
        foreach ($states as $state) {
            switch ($state) {
                case 'confirmed':
                case 'unconfirmed':
                case 'invited':
                    $func = "get_{$state}_members";
                    $ret[$state] = $this->$func();
                    break;
                default:
                    return new WP_Error(
                        'no-such-state',
                        __('Invalid state for membership.', 'buoy'),
                        $state
                    );
                    break;
            }
        }
        return $ret;
    }

    /**
     * Checks whether or not this team is one of user's default teams.
     *
     * @return bool
     */
    public function is_default () {
        return (bool) get_post_meta($this->wp_post->ID, self::$prefix.'_default_team', true);
    }

    /**
     * Makes this team part a default team of the author.
     *
     * @return WP_Buoy_Team
     */
    public function set_default () {
        update_post_meta($this->wp_post->ID, self::$prefix.'_default_team', true);
        return $this;
    }

    /**
     * Removes this team from the list of default teams.
     *
     * Refuses to remove the team from the list of default teams if
     * the team's author does not have any other default teams. This
     * ensures that a user always has at least one default team.
     *
     * @return WP_Buoy_Team
     */
    public function unset_default () {
        $user = new WP_Buoy_User($this->author->ID);
        if (1 < count($user->get_default_teams())) {
            delete_post_meta($this->wp_post->ID, self::$prefix.'_default_team');
        }
        return $this;
    }

    /**
     * Gets the Team's owner.
     *
     * @return WP_Buoy_User
     */
    public function get_team_owner () {
        return new WP_Buoy_User($this->author->ID);
    }

    /**
     * Gets a list of all the user IDs associated with this team.
     *
     * This does not do any checking about whether the given user ID
     * is "confirmed" or not.
     *
     * @return string[] IDs are actually returned as string values.
     */
    public function get_member_ids () {
        return array_unique(get_post_meta($this->wp_post->ID, '_team_members'));
    }

    /**
     * Gets a list of all email addresses invited to join this team.
     *
     * @return string[]
     */
    public function get_invited_members () {
        return array_unique(get_post_meta($this->wp_post->ID, '_invitees'));
    }

    /**
     * Checks whether or not the given user ID is on the team.
     *
     * This does not check whether the user is confirmed or not, only
     * whether the user has been at least invited to be a member of a
     * team.
     *
     * @uses WP_Buoy_Team::get_member_ids()
     *
     * @param int $user_id
     *
     * @return bool
     */
    public function is_member ($user_id) {
        return in_array($user_id, $this->get_member_ids());
    }

    /**
     * Adds an invitation for this email address to this team.
     *
     * @param string $email
     */
    public function invite_user ($email) {
        add_post_meta($this->wp_post->ID, '_invitees', $email, false);

        /**
         * Fires when a user is added or invited to a team.
         *
         * @param int|string $added A user ID or the email address of the new user.
         * @param WP_Buoy_Team $this
         */
        do_action(self::$prefix . '_team_member_added', $email, $this);
    }

    /**
     * Adds a user to this team (a new member).
     *
     * @uses add_post_meta()
     * @uses do_action()
     *
     * @param int $user_id
     * @param bool $notify Whether or not to trigger a notification when adding.
     *
     * @return WP_Buoy_Team
     */
    public function add_member ($user_id, $notify = true) {
        add_post_meta($this->wp_post->ID, '_team_members', $user_id, false);
        $this->members[] = get_userdata($user_id);

        do_action(self::$prefix . '_team_member_added', $user_id, $this, $notify);

        return $this;
    }

    /**
     * Removes a member from this team.
     *
     * @uses delete_post_meta()
     * @uses do_action()
     *
     * @param int $user_id
     *
     * @return WP_Buoy_Team
     */
    public function remove_member ($user_id) {
        delete_post_meta($this->wp_post->ID, '_team_members', $user_id);
        delete_post_meta($this->wp_post->ID, '_invitees', $user_id);

        /**
         * Fires when a user is removed from a team.
         *
         * @param int $user_id
         * @param WP_Buoy_Team $this
         */
        do_action(self::$prefix . '_team_member_removed', $user_id, $this);

        return $this;
    }

    /**
     * Sets the confirmation flag for a user on this team.
     *
     * @uses add_post_meta()
     *
     * @param int $user_id
     *
     * @return WP_Buoy_Team
     */
    public function confirm_member ($user_id) {
        add_post_meta($this->wp_post->ID, "_member_{$user_id}_is_confirmed", true, true);
        return $this;
    }

    /**
     * Unsets the confirmation flag for a user on this team.
     *
     * @uses delete_post_meta()
     *
     * @param int $user_id
     *
     * @return WP_Buoy_Team
     */
    public function unconfirm_member ($user_id) {
        delete_post_meta($this->wp_post->ID, "_member_{$user_id}_is_confirmed");
        return $this;
    }

    /**
     * Checks whether or not a user is "confirmed" to be on the team.
     *
     * "Confirmation" consists of a flag in the team post's metadata.
     *
     * @uses get_post_meta()
     *
     * @param int $user_id
     *
     * @return bool
     */
    public function is_confirmed ($user_id) {
        return get_post_meta($this->wp_post->ID, "_member_{$user_id}_is_confirmed", true);
    }

    /**
     * Checks to ensure there is at least one confirmed member on the
     * team.
     *
     * A "responder" in this context is a confirmed team member.
     *
     * @uses WP_Buoy_Team::is_confirmed()
     *
     * @return bool
     */
    public function has_responder () {
        foreach ($this->members as $member) {
            if ($this->is_confirmed($member->ID)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets the confirmed members of this team.
     *
     * @uses WP_Buoy_Team::get_member_ids()
     * @uses WP_Buoy_Team::is_confirmed()
     *
     * @return int[]
     */
    public function get_confirmed_members () {
        $responders = array();
        foreach ($this->get_member_ids() as $id) {
            if ($this->is_confirmed($id)) {
                $responders[] = $id;
            }
        }
        return $responders;
    }

    /**
     * Gets the unconfirmed members of this team.
     *
     * @uses WP_Buoy_Team::get_member_ids()
     * @uses WP_Buoy_Team::is_confirmed()
     *
     * @return int[]
     */
    public function get_unconfirmed_members () {
        $responders = array();
        foreach ($this->get_member_ids() as $id) {
            if (!$this->is_confirmed($id)) {
                $responders[] = $id;
            }
        }
        return $responders;
    }

    /**
     * @return void
     */
    public static function register () {
        if (!class_exists('Buoy_Team_Membership_List_Table')) {
            require plugin_dir_path(__FILE__) . 'class-buoy-team-membership-list-table.php';
        }
        if (!class_exists('WP_Posts_List_Table')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';
        }

        $post_type = self::$prefix . '_team';
        register_post_type($post_type, array(
            'labels' => array(
                'name' => __('Crisis Response Teams', 'buoy'),
                'singular_name' => __('Crisis Response Team', 'buoy'),
                'add_new_item' => __('Add New Team', 'buoy'),
                'edit_item' => __('Edit Team', 'buoy'),
                'new_item' => __('New Team', 'buoy'),
                'view_item' => __('View Team', 'buoy'),
                'search_items' => __('Search Teams', 'Buoy'),
                'not_found' => __('No teams found', 'Buoy'),
                'not_found_in_trash' => __('No teams found in Trash', 'buoy'),
                'all_items' => __('All Teams', 'buoy'),
                'menu_name' => __('My Teams', 'buoy')
            ),
            'description' => __('Groups of crisis responders', 'buoy'),
            'public' => false,
            'show_ui' => true,
            'capability_type' => $post_type,
            'map_meta_cap' => true,
            'hierarchical' => false,
            'supports' => array(
                'title',
                'author'
            ),
            'register_meta_box_cb' => array(__CLASS__, 'registerMetaBoxes'),
            'has_archive' => false,
            'rewrite' => false,
            'can_export' => false,
            'menu_icon' => 'dashicons-sos',
        ));
        add_action('load-post.php', array('WP_Buoy_Plugin', 'addHelpTab'));
        add_action('load-post-new.php', array('WP_Buoy_Plugin', 'addHelpTab'));
        add_action('load-edit.php', array('WP_Buoy_Plugin', 'addHelpTab'));

        add_filter('enter_title_here', array(__CLASS__, 'filterTitlePlaceholder'), 10, 2);

        // TODO: This should probably be moved so it loads only where needed.
        if (is_admin()) {
            wp_enqueue_style(
                __CLASS__ . '-style',
                plugins_url('../css/admin-teams.css', __FILE__)
            );
        }

        add_action('current_screen', array(__CLASS__, 'processTeamTableActions'));

        add_action('admin_notices', array(__CLASS__, 'renderAdminNotices'));

        add_action('admin_menu', array(__CLASS__, 'registerAdminMenu'));

        add_action('pre_get_posts', array(__CLASS__, 'filterTeamPostsList'));

        add_action('post_updated', array(__CLASS__, 'postUpdated'), 10, 3);
        add_action("save_post_{$post_type}", array(__CLASS__, 'saveTeam'), 10, 2);

        add_action('deleted_post_meta', array(__CLASS__, 'deletedPostMeta'), 10, 4);

        add_action("{$post_type}_member_removed", array(__CLASS__, 'checkMemberCount'), 10, 2);

        add_filter('user_has_cap', array(__CLASS__, 'filterCaps'));

        add_filter("manage_{$post_type}_posts_columns", array(__CLASS__, 'filterTeamPostsColumns'));
        add_filter("manage_edit-{$post_type}_sortable_columns", array(__CLASS__, 'filterSortableColumns'));
        add_action('wp', array(__CLASS__, 'orderTeamPosts'));
        add_action("manage_{$post_type}_posts_custom_column", array(__CLASS__, 'renderTeamPostsColumn'), 10, 2);

        add_action('user_register', array(__CLASS__, 'checkInvitations'));
        add_action('user_register', array(__CLASS__, 'createTeamTemplates'));
    }

    /**
     * @param WP_Post $post
     *
     * @return void
     */
    public static function registerMetaBoxes ($post) {
        $team = new self($post->ID);
        add_meta_box(
            'add-team-member',
            esc_html__('Add new member', 'buoy'),
            array(__CLASS__, 'renderAddTeamMemberMetaBox'),
            null,
            'normal',
            'high'
        );

        add_meta_box(
            'current-team',
            sprintf(
                esc_html__('Current team members %s', 'buoy'),
                '<span class="count">(' . count($team->get_confirmed_members()) . ')</span>'
            ),
            array(__CLASS__, 'renderCurrentTeamMetaBox'),
            null,
            'normal',
            'high'
        );

        add_meta_box(
            'default-team',
            sprintf(
                esc_html__('Default Team? (%s)', 'buoy'),
                ($team->is_default()) ? esc_html__('Yes', 'buoy') : esc_html__('No', 'buoy')
            ),
            array(__CLASS__, 'renderDefaultTeamMetaBox'),
            null,
            'side',
            'low'
        );

        add_meta_box(
            'sms-bridge',
            esc_html__('SMS/txt Messages', 'buoy'),
            array(__CLASS__, 'renderTxtMessagesMetaBox')
        );
    }

    /**
     * @param WP_Post $post
     *
     * @return void
     */
    public static function renderAddTeamMemberMetaBox ($post) {
        wp_nonce_field(self::$prefix . '_add_team_member', self::$prefix . '_add_team_member_nonce');
        require plugin_dir_path(dirname(__FILE__)).'pages/add-team-member-meta-box.php';
    }

    /**
     * @param WP_Post $post
     *
     * @return void
     */
    public static function renderCurrentTeamMetaBox ($post) {
        wp_nonce_field(self::$prefix . '_choose_team', self::$prefix . '_choose_team_nonce');
        require plugin_dir_path(dirname(__FILE__)).'pages/current-team-meta-box.php';
    }

    /**
     * Displays the "default team" meta box.
     *
     * @param WP_Post $post
     *
     * @return void
     */
    public static function renderDefaultTeamMetaBox ($post) {
        $team = new WP_Buoy_Team($post->ID);
        $html = '<input type="checkbox"';
        $html .= ' id="'.self::$prefix.'_default_team"';
        $html .= ' name="'.self::$prefix.'_default_team"';
        $html .= ' value="1"';
        $html .= ' '.checked($team->is_default(), true, false);
        $html .= ' />';
        $html .= esc_html__('Include as default team?', 'buoy');
        $html .= '<p class="description">';
        if ($team->is_default()) {
            $html .= esc_html__('This team is one of your default teams.', 'buoy')
                .' '
                .esc_html__('Uncheck the box to remove this team from your list of default teams.', 'buoy');
        } else {
            $html .= esc_html__('This team is not one of your default teams.', 'buoy')
                .' '
                .esc_html__('Check the box to add this team to your list of default teams.', 'buoy');
        }
        $html .= '</p>';
        print "<label>$html</label>";
    }

    /**
     * Displays the "SMS/txt Messages" meta box.
     *
     * @param WP_Post $post
     *
     * @return void
     */
    public static function renderTxtMessagesMetaBox ($post) {
        $user = new WP_Buoy_User($post->post_author);
        if ($user->get_phone_number()) {
            require_once dirname(__FILE__).'/../pages/meta-box-sms-messages.php';
        } else {
            esc_html_e('You must set a phone number in your profile to use txt messages.', 'buoy');
        }
    }

    /**
     * @return void
     */
    public static function renderTeamMembershipPage () {
        $post_type = self::$prefix . '_team';
        $team_table = new Buoy_Team_Membership_List_Table($post_type);
        $team_table->prepare_items();
        print '<div class="wrap">';
        print '<form'
            . ' action="' . admin_url('edit.php?post_type=' . $post_type . '&page=' . self::$prefix . '_team_membership') . '"'
            . ' method="post">';
        print '<h1>' . esc_html__('Team membership', 'buoy') . '</h1>';
        $team_table->display();
        print '</form>';
        print '</div>';
    }

    /**
     * Sets team parameters based on actions taken in Team admin UI.
     * 
     * @link https://developer.wordpress.org/reference/hooks/current_screen/
     *
     * @global $_POST
     * @global $_GET
     *
     * @uses WP_Screen::$post_type
     * @uses WP_Posts_List_Table::current_action()
     * @uses WP_Buoy_User_Settings::set()
     * @uses WP_Buoy_User_Settings::save()
     * @uses Buoy_Team_Membership_List_Table::current_action()
     * @uses wp_verify_nonce()
     * @uses WP_Buoy_Team::remove_member()
     * @uses WP_Buoy_Team::confirm_member()
     *
     * @param WP_Screen $current_screen
     *
     * @return void
     */
    public static function processTeamTableActions ($current_screen) {
        $post_type = self::$prefix . '_team';
        if ($post_type !== $current_screen->post_type) {
            return;
        }

        if ('post' === $current_screen->base) {
            // The "My Teams" page.
            $table = new WP_Posts_List_Table();
            $action = $table->current_action();
            if ('set_default' === $action || 'unset_default' === $action) {
                $team = new WP_Buoy_Team(absint($_GET['post']));
                $team->$action();
                $msg = self::$prefix . '-default-team-updated';
                wp_safe_redirect(admin_url(
                    "edit.php?post_type={$current_screen->post_type}&msg=$msg"
                ));
                exit();
            }
        } else if ("{$post_type}_page_{$post_type}_membership") {
            // The "Team Membership" page.
            $table = new Buoy_Team_Membership_List_Table($post_type);
            $teams = array();
            if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'single-' . $table->_args['plural'])) {
                $teams[] = $_GET['team_id'];
            } else if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'bulk-' . $table->_args['plural'])) {
                $teams = array_merge($teams, $_POST['teams']);
            }

            if (!empty($teams)) {
                foreach ($teams as $team_id) {
                    $team = new self($team_id);
                    if ('leave' === $table->current_action()) {
                        $team->remove_member(get_current_user_id());
                    }
                    if ('join' === $table->current_action()) {
                        $team->confirm_member(get_current_user_id());
                    }
                }
                wp_safe_redirect(admin_url(
                    'edit.php?page=' . urlencode($_GET['page'])
                    . '&post_type=' . urlencode($_GET['post_type'])
                ));
                exit();
            }
        }
    }

    /**
     * Prints an admin notice for the given message code.
     *
     * @global $_GET['msg']
     *
     * @return void
     */
    public static function renderAdminNotices () {
        if (isset($_GET['msg'])) {
            $notices = array(
                // message-code => array('class' => 'css-class', 'message' => "Message text.")
                self::$prefix . '-default-team-updated' => array(
                    'class' => 'notice updated is-dismissible',
                    'message' => esc_html__('Default team updated.', 'buoy')
                )
            );
            if (array_key_exists($_GET['msg'], $notices)) {
?>
<div class="<?php print esc_attr($notices[$_GET['msg']]['class']);?>">
    <p><?php print esc_html($notices[$_GET['msg']]['message']);?></p>
</div>
<?php
            }
        }
    }

    /**
     * Checks to ensure a user doesn't leave themselves without any
     * responders.
     *
     * Teams are only "active" is they are in the "publish" status.
     * This checks a team transition and if the action leaves a user
     * without any responders, it will re-set the team's status.
     *
     * @link https://developer.wordpress.org/reference/hooks/post_updated/
     *
     * @uses WP_Buoy_User::has_responder()
     * @uses WP_Buoy_Team::is_default()
     * @uses WP_Buoy_Team::set_default()
     *
     * @param int $post_id
     * @param WP_Post $post_after
     * @param WP_Post $post_before
     *
     * @return void
     */
    public static function postUpdated ($post_id, $post_after, $post_before) {
        if (self::$prefix . '_team' !== $post_after->post_type) {
            return;
        }

        $buoy_user = new WP_Buoy_User($post_before->post_author);

        // Prevent the user from trashing their last responder team.
        if ('publish' === $post_before->post_status && 'publish' !== $post_after->post_status) {
            if (!$buoy_user->has_responder()) {
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_status' => 'publish'
                ));
            }
        }

        // Re-set the default team if the default team is trashed.
        $team = new WP_Buoy_Team($post_id);
        if ('trash' === $post_after->post_status && $team->is_default()) {
            $teams = $buoy_user->get_teams();
            $next_team = new WP_Buoy_Team(array_pop($teams));
            $next_team->set_default();
        }
    }

    /**
     * Updates the team metadata (mostly membership list).
     *
     * This is called by WordPress's `save_post_{$post->post_type}` hook.
     *
     * @link https://developer.wordpress.org/reference/hooks/save_post_post-post_type/
     *
     * @global $_POST
     *
     * @uses wp_verify_nonce()
     * @uses WP_Buoy_Team::remove_member()
     * @uses WP_Buoy_Team::get_member_ids()
     * @uses WP_Buoy_Team::add_member()
     * @uses WP_Buoy_Team::set_default()
     * @uses WP_Buoy_Team::unset_default()
     *
     * @param int $post_id
     * @param WP_Post $post
     *
     * @return void
     */
    public static function saveTeam ($post_id, $post) {
        $team = new self($post_id);
        // Remove any team members indicated.
        if (isset($_POST[self::$prefix . '_choose_team_nonce']) && wp_verify_nonce($_POST[self::$prefix . '_choose_team_nonce'], self::$prefix . '_choose_team')) {
            if (isset($_POST['remove_team_members'])) {
                foreach ($_POST['remove_team_members'] as $id) {
                    $team->remove_member($id);
                }
            }
        }

        // Add a new team member
        if (isset($_POST[self::$prefix . '_add_team_member_nonce']) && wp_verify_nonce($_POST[self::$prefix . '_add_team_member_nonce'], self::$prefix . '_add_team_member')) {
            $add_team_member_input = $_POST[self::$prefix . '_add_team_member'];
            $user_id = username_exists($add_team_member_input);
            if (false !== $user_id) {
                if (!in_array($user_id, $team->get_member_ids())) {
                    $team->add_member($user_id);
                }
            } else {
                if (is_email($add_team_member_input)) {
                    $email = $add_team_member_input;
                    $user = get_user_by('email', $email);
                    if ($user) {
                        $team->add_member($user->ID);
                    } else {
                        $team->invite_user($email);
                    }
                }
            }
        }

        // Set default status
        if (!empty($_POST[self::$prefix.'_default_team'])) {
            $team->set_default();
        } else {
            $team->unset_default();
        }

        // If this is the user's only team, make this the default one.
        $cnt = count(get_posts(array(
            'post_type' => $post->post_type,
            'author' => $post->post_author,
            'fields' => 'ids'
        )));
        if (0 === $cnt) {
            $team->set_default();
        }

        // If we're enabling the SMS/email bridge, save that data.
        if (!empty($_POST['sms_email_bridge_enabled'])) {
            update_post_meta($post_id, 'sms_email_bridge_enabled', true);
            // and schedule a check
            WP_Buoy_SMS_Email_Bridge::scheduleNext($post_id, 0);
        } else {
            update_post_meta($post_id, 'sms_email_bridge_enabled', false);
            // and unschedule the next check
            WP_Buoy_SMS_Email_Bridge::unscheduleNext($post_id);
        }
        if (!empty($_POST['sms_email_bridge_address'])) {
            update_post_meta($post_id, 'sms_email_bridge_address', sanitize_email($_POST['sms_email_bridge_address']));
        }
        if (!empty($_POST['sms_email_bridge_username'])) {
            update_post_meta($post_id, 'sms_email_bridge_username', sanitize_text_field($_POST['sms_email_bridge_username']));
        }
        if (!empty($_POST['sms_email_bridge_password'])) {
            update_post_meta($post_id, 'sms_email_bridge_password', $_POST['sms_email_bridge_password']);
        }
        if (!empty($_POST['sms_email_bridge_server'])) {
            update_post_meta($post_id, 'sms_email_bridge_server', sanitize_text_field($_POST['sms_email_bridge_server']));
        }
        if (empty($_POST['sms_email_bridge_port']) || 0 === absint($_POST['sms_email_bridge_port'])) {
            delete_post_meta($post_id, 'sms_email_bridge_port');
        } else {
            update_post_meta($post_id, 'sms_email_bridge_port', absint($_POST['sms_email_bridge_port']));
        }
        if (empty($_POST['sms_email_bridge_connection_security'])) {
            delete_post_meta($post_id, 'sms_email_bridge_connection_security');
        } else {
            switch ($_POST['sms_email_bridge_connection_security']) {
                case 'tlsv1':
                case 'tls':
                case 'ssl':
                case 'sslv3':
                case 'sslv2':
                case 'none':
                    update_post_meta(
                        $post_id,
                        'sms_email_bridge_connection_security',
                        $_POST['sms_email_bridge_connection_security']
                    );
                    break;
                default:
                    update_post_meta($post_id, 'sms_email_bridge_connection_security', 'tlsv1');
                    break;
            }
        }
    }

    /**
     * Hooks the `deleted_post_meta` action.
     *
     * This is used primarily to detect when a user is removed from a
     * team and, when this occurrs, remove the confirmation flag, too.
     *
     * @link https://developer.wordpress.org/reference/hooks/deleted_meta_type_meta/
     *
     * @param array $meta_ids
     * @param int $post_id
     * @param string $meta_key
     * @param mixed $meta_value
     *
     * @return void
     */
    public static function deletedPostMeta ($meta_ids, $post_id, $meta_key, $meta_value) {
        $team = new self($post_id);
        if ('_team_members' === $meta_key) {
            // delete confirmation when removing a team member
            $team->unconfirm_member($meta_value);
        }
    }

    /**
     * Checks if a team no longer has any members.
     *
     * If a team is emptied for any reason, whether because the user
     * has removed all their members or the members themselves decide
     * to leave, this will fire a "{$post->post_type}_emptied" hook.
     *
     * @param int $user_id The user who was just removed.
     * @param WP_Buoy_Team The team they left.
     */
    public static function checkMemberCount ($user_id, $team) {
        $ids = $team->get_member_ids();
        if (empty($ids)) {
            /**
             * Fires after the last member of a team is removed (or leaves).
             *
             * @param WP_Buoy_Team $team
             */
            do_action($team->wp_post->post_type . '_emptied', $team);
        }
    }

    /**
     * @return void
     */
    public static function registerAdminMenu () {
        $hooks = array();

        $hooks[] = add_submenu_page(
            'edit.php?post_type=' . self::$prefix . '_team',
            __('Team membership', 'buoy'),
            __('Team membership', 'buoy'),
            'read',
            self::$prefix . '_team_membership',
            array(__CLASS__, 'renderTeamMembershipPage')
        );

        foreach ($hooks as $hook) {
            add_action('load-' . $hook, array('WP_Buoy_Plugin', 'addHelpTab'));
        }
    }

    /**
     * Dynamically configures user capabilities.
     * This dynamism prevents the need to write capabilities into the
     * database's `options` table's `wp_user_roles` record itself.
     *
     * Currently simply unconditionally gives every user the required
     * capabilities to manage their own crisis response teams.
     *
     * Called by the `user_has_cap` filter.
     *
     * @link https://developer.wordpress.org/reference/hooks/user_has_cap/
     *
     * @param array $caps The user's actual capabilities.
     *
     * @return array $caps
     */
    public static function filterCaps ($caps) {
        $caps['edit_'             . self::$prefix . '_teams'] = true;
        $caps['delete_'           . self::$prefix . '_teams'] = true;
        $caps['publish_'          . self::$prefix . '_teams'] = true;
        $caps['edit_published_'   . self::$prefix . '_teams'] = true;
        $caps['delete_published_' . self::$prefix . '_teams'] = true;
        return $caps;
    }

    /**
     * Sets the placeholder text for the "New Team" page.
     *
     * @link https://developer.wordpress.org/reference/hooks/enter_title_here/
     *
     * @param string $text
     * @param WP_Post $post
     *
     * @return string
     */
    public static function filterTitlePlaceholder ($text, $post) {
        if (self::$prefix . '_team' === $post->post_type) {
            $text = __('Enter team name here', 'buoy');
        }
        return $text;
    }

    /**
     * Add custom columns shown in the "My Teams" admin UI.
     *
     * @link https://developer.wordpress.org/reference/hooks/manage_post_type_posts_columns/
     *
     * @param array $post_columns
     *
     * @return array
     */
    public static function filterTeamPostsColumns ($post_columns) {
        unset($post_columns['author']);
        $post_columns['num_members']       = __('Members', 'buoy');
        $post_columns['confirmed_members'] = __('Confirmed Members', 'buoy');
        $post_columns['default_team']      = __('Default Team?', 'buoy');
        return $post_columns;
    }

    /**
     * Makes the custom columns sortable in the "My Teams" admin UI.
     *
     * @link https://developer.wordpress.org/reference/hooks/manage_this-screen-id_sortable_columns/
     *
     * @param array $sortable_columns
     *
     * @return array
     */
    public static function filterSortableColumns ($sortable_columns) {
        $sortable_columns['num_members']       = self::$prefix . '_team_member_count';
        $sortable_columns['confirmed_members'] = self::$prefix . '_team_confirmed_members';
        return $sortable_columns;
    }

    /**
     * Re-orders the query results based on the team member count.
     *
     * This changes the global `$wp_query->posts` array directly.
     *
     * @todo Possibly the count should be its own meta field managed by us so this hook is more performant?
     *
     * @global $wp_query
     *
     * @uses WP_Buoy_Team::get_member_ids()
     * @uses WP_Buoy_Team::get_confirmed_members()
     *
     * @param WP $wp
     *
     * @return void
     */
    public static function orderTeamPosts ($wp) {
        if (is_admin() && isset($wp->query_vars['orderby'])) {

            if (self::$prefix . '_team_member_count' === $wp->query_vars['orderby']) {
                $method = 'get_member_ids';
            } else if (self::$prefix . '_team_confirmed_members' === $wp->query_vars['orderby']) {
                $method = 'get_confirmed_members';
            }

            if (isset($method)) {
                global $wp_query;
                $member_counts = array();
                foreach ($wp_query->posts as $post) {
                    $team = new WP_Buoy_Team($post->ID);
                    $member_counts[count($team->$method())][] = $post; // variable function
                }

                ksort($member_counts);

                if ('desc' === $wp->query_vars['order']) {
                    $member_counts = array_reverse($member_counts);
                }

                $sorted = array();
                foreach ($member_counts as $counts) {
                    foreach ($counts as $post) {
                        $sorted[] = $post;
                    }
                }

                $wp_query->posts = $sorted;
            }
        }
    }

    /**
     * Add the column content for custom columns in the "My Teams" UI.
     *
     * @link https://developer.wordpress.org/reference/hooks/manage_post-post_type_posts_custom_column/
     *
     * @param string $column_name
     * @param int $post_id
     *
     * @return void
     */
    public static function renderTeamPostsColumn ($column_name, $post_id) {
        $team = new WP_Buoy_Team($post_id);
        switch ($column_name) {
            case 'num_members':
                print esc_html(count($team->get_member_ids()));
                break;
            case 'confirmed_members':
                print esc_html(count($team->get_confirmed_members()));
                break;
            case 'default_team':
                if ($team->is_default()) {
                    print '<strong>' . esc_html__('Yes', 'buoy') . '</strong>';
                }
                break;
        }
    }

    /**
     * Ensures that users can only see their own crisis teams in the
     * WP admin view when viewing their "My Teams" dashboard page.
     *
     * @param WP_Query $query 
     *
     * @return void
     */
    public static function filterTeamPostsList ($query) {
        // @ticket 119
        if (!function_exists('get_current_screen')) {
            require_once ABSPATH . 'wp-admin/includes/screen.php';
        }
        if (is_admin() && $screen = get_current_screen()) {
            if ('edit-' . self::$prefix .'_team' === $screen->id && current_user_can('edit_' . self::$prefix . '_teams')) {
                $query->set('author', get_current_user_id());
                add_filter('views_' . $screen->id, array(__CLASS__, 'filterTeamPostViews'));
                add_filter('post_row_actions', array(__CLASS__, 'postRowActions'), 10, 2);
            }
        }
    }

    /**
     * Removes the views links in the Team posts table.
     *
     * @link https://developer.wordpress.org/reference/hooks/views_this-screen-id/
     *
     * @param array $items
     *
     * @return array
     */
    public static function filterTeamPostViews ($items) {
        return array(); // remove all view links
    }

    /**
     * Customizes the post row actions in the "My Teams" admin UI.
     *
     * @link https://developer.wordpress.org/reference/hooks/post_row_actions/
     *
     * @uses WP_Buoy_Team::is_default()
     * @uses WP_Buoy_Team::has_responder()
     *
     * @param array $items
     * @param WP_Post $post
     *
     * @return array $items
     */
    public static function postRowActions ($items, $post) {
        $team = new WP_Buoy_Team($post->ID);
        if (!$team->is_default() && $team->has_responder() && 'publish' === $post->post_status) {
            $url = admin_url('post.php?post=' . $post->ID . '&action=set_default');
            $items['default'] = '<a href="' . esc_attr($url) . '">' . __('Add to defaults', 'buoy') . '</a>';
        } else if ($team->is_default() && $team->has_responder() && 'publish' === $post->post_status) {
            $url = admin_url('post.php?post=' . $post->ID . '&action=unset_default');
            $items['default'] = '<a href="' . esc_attr($url) . '">' . __('Remove from defaults', 'buoy') . '</a>';
        }

        unset($items['inline hide-if-no-js']); // the "Quick Edit" link
        return $items;
    }

    /**
     * Adds a user to teams they were invited to join before they had
     * created an account.
     *
     * @param int $user_id
     *
     * @return void
     */
    public static function checkInvitations ($user_id) {
        $user = get_userdata($user_id);
        $team_posts = self::getAllTeamPosts();
        foreach ($team_posts as $post) {
            $team = new WP_Buoy_Team($post->ID);
            if (in_array($user->user_email, $team->get_invited_members())) {
                $team->remove_member($user->user_email); // removes the invitation
                $team->add_member($user_id, false);      // then adds the user
            }
        }
    }

    /**
     * Creates teams for newly-registered users.
     *
     * @link https://developer.wordpress.org/reference/hooks/user_register/
     *
     * @param int $user_id
     *
     * @return void
     */
    public static function createTeamTemplates ($user_id) {
        // Create three new "teams" for the new user so that they can
        // begin editing the members lists and can invite folks ASAP.
        $new_teams = array(
            array(
                'post_title' => __('Friends', 'buoy')
            ),
            array(
                'post_title' => __('Family', 'buoy')
            ),
            array(
                'post_title' => __('Neighbours', 'buoy')
            )
        );
        foreach ($new_teams as $new_team) {
            $new_team['post_author'] = $user_id;
            $new_team['post_type'] = self::$prefix.'_team';
            $new_team['post_status'] = 'private';
            wp_insert_post($new_team);
        }
    }

    /**
     * Gets a list of all team posts.
     *
     * @return WP_Post[]
     */
    public static function getAllTeamPosts () {
        return get_posts(array(
            'post_type' => self::$prefix . '_team',
            'post_status' => array('publish', 'draft', 'trash'),
            'posts_per_page' => -1
        ));
    }

}
