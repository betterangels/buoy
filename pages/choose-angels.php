<?php
$full_team = array();
foreach (get_user_meta(get_current_user_id(), $this->prefix . 'guardians') as $x) {
    $full_team[] = array('data' => get_userdata($x));
}
foreach (get_user_meta(get_current_user_id(), $this->prefix . 'fake_guardians') as $x) {
    $full_team[] = array('type' => array('fake'), 'data' => get_userdata($x));
}
foreach (get_user_meta(get_current_user_id(), $this->prefix . 'pending_guardians') as $x) {
    $full_team[] = array('type' => array('pending'), 'data' => get_userdata($x));
}
foreach (get_user_meta(get_current_user_id(), $this->prefix . 'pending_fake_guardians') as $x) {
    $full_team[] = array('type' => array('pending', 'fake'), 'data' => get_userdata($x));
}
?>
<h2><?php esc_html_e('Choose your team members', 'better-angels');?></h2>
<?php
if (isset($_GET['msg']) && 'no-guardians' === $_GET['msg']) {
    $notice = '<div class="error notice is-dismissible"><p>';
    $notice .= esc_html__('You have no team members. Before you can activate an alert, you must invite at least one other user to join your personal emergency response team and they must have accepted your invitation. Use this page to choose a response team.', 'better-angels');
    $notice .= '</p></div>';
    print $notice;
}
?>
<form method="POST" action="<?php print esc_url(admin_url('?page='.$this->prefix.'choose-angels'));?>">
<?php wp_nonce_field($this->prefix . 'guardians', $this->prefix . 'nonce');?>
<fieldset><legend><?php esc_html_e('Choose your team members', 'better-angels');?></legend>
<table class="form-table" summary="<?php esc_attr_e('', 'better-angels');?>">
    <tbody>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>add_guardian"><?php esc_html_e('Add a team member', 'better-angels');?></label>
            </th>
            <td>
                <input list="<?php esc_attr_e($this->prefix);?>guardians_list" id="<?php esc_attr_e($this->prefix);?>add_guardian" name="<?php esc_attr_e($this->prefix);?>add_guardian" placeholder="<?php esc_attr_e('Michelle', 'better-angels')?>" />
                <label>
                    <input type="checkbox" id="<?php esc_attr_e($this->prefix);?>is_fake_guardian" name="<?php esc_attr_e($this->prefix);?>is_fake_guardian" />
                    <?php esc_html_e('Add as fake team member', 'better-angels');?>
                </label>
                <datalist id="<?php esc_attr_e($this->prefix);?>guardians_list"/>
                    <?php $this->printAllUsersForDataList();?>
                </datalist>
                <p class="description">
                    <?php print sprintf(
                        esc_html__('Your team members are the people you want to notify in the event of an emergency. Type your trusted friends names in the text box, then press %1$sSave Changes%2$s at the bottom of this page. The person you choose will get an invitation asking them to confirm joining your team.', 'better-angels'),
                        '<strong>', '</strong>'
                    );?>
                </p>
                <p class="description">
                    <?php print sprintf(
                        esc_html__('You can optionally add the team member as a "fake" who will think that they are on your response team but who will never actually get any alerts from you. Only add a fake team member if you are being pressured into adding someone to your team who you do not actually trust to appropriately support you. This person will get an invitation asking them to confirm joining your team in the same way as a real team member will, but the fake team member will never actually receive alerts you send to the rest of your team.', 'better-angels')
                    );?>
                </p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>my_guardians"><?php esc_html_e('Remove a team member', 'better-angels');?></label>
            </th>
            <td>
                <ul id="<?php esc_attr_e($this->prefix);?>my_guardians">
                <?php if (empty($full_team)): print sprintf(esc_html__('You have not chosen any team members. Maybe you want to %1$sadd a team member%2$s?', 'better-angels'), '<a href="#' . $this->prefix . 'guardians">', '</a>'); endif;?>
                <?php foreach ($full_team as $guardian) : ?>
                    <li>
                        <label>
                            <input
                                type="checkbox"
                                checked="checked"
                                value="<?php esc_attr_e($guardian['data']->ID);?>"
                                name="<?php esc_attr_e($this->prefix);?>my_guardians[]">
                            <?php print esc_html($guardian['data']->user_nicename);?>
                            <?php if (!empty($guardian['type'])) : ?>
                            <span class="description <?php esc_attr_e(join(' ', $guardian['type']));?>">
                            (<?php
                                $cnt = count($guardian['type']);
                                for ($i = 0; $i < $cnt; $i++) {
                                    if ('pending' === $guardian['type'][$i]) {
                                        esc_html_e('pending', 'better-angels');
                                    } else if ('fake' === $guardian['type'][$i]) {
                                        esc_html_e('fake', 'better-angels');
                                    }
                                    if ($i < ($cnt - 1)) { print ', '; }
                                }
                            ?>)
                            </span>
                            <?php endif;?>
                        </label>
                        <label>
                            <input
                                type="radio"
                                <?php checked(!in_array('fake', $guardian['type']));?>
                                value="<?php print esc_attr('real');?>"
                                name="<?php esc_attr_e($this->prefix);?>guardian[<?php print esc_attr($guardian['data']->ID);?>][type]">
                            <?php esc_html_e('real', 'better-angels');?>
                        </label>
                        <label>
                            <input
                                type="radio"
                                <?php checked(in_array('fake', $guardian['type']));?>
                                value="<?php print esc_attr('fake');?>"
                                name="<?php esc_attr_e($this->prefix);?>guardian[<?php print esc_attr($guardian['data']->ID);?>][type]">
                            <?php esc_html_e('fake', 'better-angels');?>
                        </label>
                    </li>
                <?php endforeach;?>
                </ul>
                <p class="description"><?php print sprintf(
                    esc_html__('These are your current team members. To remove one of them so that they no longer receive notifications when you are in an emergency situation, uncheck their box and click %1$sSave Changes%2$s at the bottom of this page.', 'better-angels'),
                    '<strong>', '</strong>'
                );?></p>
            </td>
        </tr>
    </tbody>
</table>
</fieldset>
<?php submit_button();?>
</form>
