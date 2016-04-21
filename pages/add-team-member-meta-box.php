<table class="form-table">
    <tbody>
        <tr>
            <th>
                <label for="add-team-member"><?php esc_html_e('Responder username or email', 'buoy');?></label>
            </th>
            <td>
                <input class="code large-text"
                    list="available-team-members-list"
                    id="add-team-member"
                    name="<?php print esc_attr(WP_Buoy_Plugin::$prefix);?>_add_team_member"
                    placeholder="<?php print sprintf(esc_attr__('email%s', 'buoy'), "@{$_SERVER['SERVER_NAME']}");?>"
                />
                <datalist id="available-team-members-list">
<?php
$args = array();
if (!current_user_can('list_users')) {
    $args = array(
        'meta_key' => WP_Buoy_Plugin::$prefix . '_public_responder',
        'meta_value' => 1
    );
}
$users = get_users($args);
foreach ($users as $usr) {
    if ($usr->ID !== get_current_user_id()) {
        print "<option value=\"{$usr->user_login}\" />{$usr->display_name}</options>";
    }
}
?>
                </datalist>
                <p class="description"><?php
                    esc_html_e('Invite someone you trust to join your team by entering their username or email address here. If they do not already have an account on this Buoy, an invitation will be emailed to them.' , 'buoy');
                ?></p>
            </td>
        </tr>
    </tbody>
</table>
