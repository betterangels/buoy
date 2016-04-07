<?php
/**
 * Buoy Admin Dashboard Widget responder check
 *
 * @link https://codex.wordpress.org/Dashboard_Widgets_API
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * @copyright Copyright (c) 2016 by Meitar "maymay" Moscovitz
 *
 * @package WordPress\Plugin\WP_Buoy_Plugin
 */

if (!defined('ABSPATH')) { exit; } // Disallow direct HTTP access.

if (!current_user_can('edit_'.self::$prefix.'_teams')) {
    return;
}

$user = new WP_Buoy_User(get_current_user_id());
if (!$user->has_responder()) {
?>
<div class="notice error is-dismissible">
    <p>
        <strong><?php esc_html_e('You have no crisis responders.', 'buoy');?></strong>
        <?php print sprintf(
            esc_html__('Add people you trust to %1$syour crisis response teams%2$s.', 'buoy'),
            '<a href="'.admin_url('edit.php?post_type='.self::$prefix.'_team').'">', '</a>'
        );?>
    </p>
</div>
<?php
} else {
?>
<p>&#x2713; <?php esc_html_e('You have responders on this Buoy.', 'buoy');?></p>
<?php
}
