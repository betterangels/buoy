<?php
/**
 * Buoy Team Membership List Table
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin\WP_Buoy_Team\Buoy_Team_Membership_List_Table
 *
 * @copyright Copyright (c) 2015-2016 by Meitar "maymay" Moscovitz
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

if (!defined('ABSPATH')) { exit; } // Disallow direct HTTP access.
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Buoy Team membership admin interface.
 */
class Buoy_Team_Membership_List_Table extends WP_List_Table {

    /**
     * The post type being listed.
     *
     * @access private
     *
     * @var string $post_type
     */
    private $post_type;

    /**
     * Constructor.
     *
     * @param string $post_type The post type that this table lists.
     */
    public function __construct ($post_type) {
        $this->post_type = $post_type;

        parent::__construct(array(
            'singular' => 'team',
            'plural' => 'teams'
        ));
    }

    /**
     * @uses Buoy_Teams_List_Table::get_items()
     */
    public function prepare_items () {
        $this->items = $this->get_items();
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
    }

    /**
     * Gets the items (teams) from the WordPress database.
     *
     * @uses Buoy_Teams_List_table::$post_type
     *
     * @return array
     */
    private function get_items () {
        $items = array();

        $user_id = get_current_user_id();

        $posts = get_posts(array(
            'post_type' => $this->post_type,
            'post_status' => 'publish,private',
            'meta_key' => '_team_members',
            'meta_value' => $user_id
        ));

        if (!empty($posts)) {
            foreach ($posts as $post) {
                $author = get_userdata($post->post_author);
                $items[] = array(
                    'ID' => $post->ID,
                    'team_name' => $post->post_title,
                    'team_status' => $post->post_status,
                    'author' => $author->display_name,
                    'confirmed' => get_post_meta($post->ID, "_member_{$user_id}_is_confirmed", true)
                );
            }
        }

        return $items;
    }

    public function get_columns () {
        return array(
            'cb' => '<input type="checkbox" />',
            'team_name' => esc_html__('Team Name', 'buoy'),
            'author' => esc_html__('Alerter', 'buoy'),
            'confirmed' => esc_html__('Confirmed', 'buoy')
        );
    }

    public function column_default ($item, $column_name) {
        return $item[$column_name];
    }

    public function column_cb ($item) {
        return sprintf('<input type="checkbox" name="teams[]" value="%s" />', $item['ID']);
    }

    public function column_confirmed ($item) {
        return ($item['confirmed']) ? esc_html__('Confirmed', 'buoy') : esc_html__('Pending', 'buoy');
    }

    /**
     * Custom column output for the "Team Name" column, used to make
     * the row actions specific to this column.
     */
    public function column_team_name ($item) {
        $toggle_action = ($item['confirmed']) ? 'leave' : 'join';
        $url = wp_nonce_url(
            '?page=' . esc_attr($_GET['page'])
            . '&post_type=' . esc_attr($_GET['post_type'])
            . '&team_id=' . esc_attr($item['ID'])
            . '&action=' . $toggle_action,
            'single-' . $this->_args['plural']
        );
        $onclick = ($item['confirmed']) ? ' onclick="return confirm(commonL10n.warnDelete);"' : '';
        $toggle_html = '<a href="'.$url.'"'.$onclick.'>';
        $toggle_html .= ($item['confirmed']) ? esc_html__('Leave Team', 'buoy') : esc_html__('Join Team', 'buoy');
        $toggle_html .= '</a>';
        $actions = array(
            'toggle_confirm' => $toggle_html
        );
        $private_marker = ('private' === $item['team_status']) ? ' <strong>&mdash; '.esc_html__('Private', 'buoy').'</strong>' : '';
        return sprintf('%1$s %2$s', "{$item['team_name']}$private_marker", $this->row_actions($actions));
    }

    public function no_items () {
        esc_html_e('You are not on any teams.', 'buoy');
    }

    public function get_bulk_actions () {
        return array(
            'join' => __('Join Team', 'buoy'),
            'leave' => __('Leave Team', 'buoy')
        );
    }

}
