<?php
$teams = get_users(array(
    'meta_key' => $this->prefix . 'guardians',
    'meta_value' => get_current_user_id()
));
$invited = array();
$joined = array();
foreach ($teams as $owner) {
    $info = $this->getGuardianInfo(get_current_user_id(), $owner->ID);
    if (empty($info['confirmed'])) {
        $invited[] = $owner;
    } else {
        $joined[] = $owner;
    }
}
?>
<h2><?php esc_html_e('Confirm team membership', 'better-angels');?></h2>
<form method="POST" action="<?php print esc_url(admin_url('admin.php?page='.$this->prefix.'confirm-guardianship'));?>">
<?php wp_nonce_field($this->prefix . 'update-teams', $this->prefix . 'nonce');?>
<fieldset><legend><?php esc_html_e('Confirm your team memberships', 'better-angels');?></legend>
<table class="form-table" summary="<?php esc_attr_e('', 'better-angels');?>">
    <tbody>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>join_teams"><?php esc_html_e('Accept team invitations', 'better-angels');?></label>
            </th>
            <td>
                <ul id="<?php esc_attr_e($this->prefix);?>join_teams">
                <?php if (empty($invited)) : print sprintf(esc_html__('You have not been invited to join any teams. Maybe you want to %1$sadd a team member%2$s to your own team?', 'better-angels'), '<a href="' . admin_url("?page={$this->prefix}choose-angels") . '">', '</a>'); endif;?>
                <?php foreach ($invited as $user) : ?>
                    <li>
                        <label>
                            <input
                                type="checkbox"
                                value="<?php esc_attr_e($user->ID);?>"
                                name="<?php esc_attr_e($this->prefix);?>join_teams[]">
                            <?php print esc_html($user->user_nicename);?>
                        </label>
                    </li>
                <?php endforeach;?>
                </ul>
                <p class="description"><?php print sprintf(
                    esc_html__('These are your current team invitations. To confirm one of them so that you are added as a member of their team and can receive alerts from them, ensure their box is checked and click %1$sSave Changes%2$s at the bottom of this page. The person whose team you join will be able to see that you have accepted their invitation and are on their response team.', 'better-angels'),
                    '<strong>', '</strong>'
                );?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>my_teams"><?php esc_html_e('Current teams', 'better-angels');?></label>
            </th>
            <td>
                <ul id="<?php esc_attr_e($this->prefix);?>my_teams">
                <?php if (empty($joined)) : print sprintf(esc_html__('You are not on any response teams. Maybe you want to %1$sadd a team member%2$s to your own team?', 'better-angels'), '<a href="' . admin_url("?page={$this->prefix}choose-angels") . '">', '</a>'); endif;?>
                <?php foreach ($joined as $user) : ?>
                    <li>
                        <?php print esc_html($user->user_nicename);?>
                        <button type="submit" class="button button-small"
                            name="<?php print esc_attr($this->prefix);?>leave-team"
                            value="<?php print esc_attr($user->user_login);?>"
                        >
                            <?php print sprintf(esc_html__("Leave %s's team", 'better-angels'), $user->user_nicename);?>
                        </button>
                    </li>
                <?php endforeach;?>
                </ul>
                <p class="description"><?php print sprintf(
                    esc_html__('These are your current team memberships. To leave the team so that you no longer receive alerts from them, click the "Leave" button next to their name. The person whose team you leave will be able to see that you are no longer on their response team.', 'better-angels'),
                    '<strong>', '</strong>'
                );?></p>
            </td>
        </tr>
    </tbody>
</table>
</fieldset>
<?php submit_button();?>
</form>
