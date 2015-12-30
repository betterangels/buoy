<table class="form-table" summary="">
    <tbody>
        <tr>
            <th>
                <label for="add-team-member"><?php esc_html_e('Add a team member', 'buoy');?></label>
            </th>
            <td>
                <input
                    list="available-team-members-list"
                    id="add-team-member"
                    name="<?php print esc_attr(WP_Buoy_Plugin::$prefix);?>_add_team_member"
                    placeholder="<?php esc_attr_e('Michelle', 'buoy');?>"
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
        print "<option value=\"{$usr->user_nicename}\" />{$usr->display_name}</options>";
    }
}
?>
                </datalist>
            </td>
        </tr>
    </tbody>
</table>
