<?php
$full_team = $this->getResponseTeam(get_current_user_id());
?>
<h2><?php esc_html_e('Choose your team members', 'better-angels');?></h2>
<?php
foreach ($this->Error->errors as $code => $err) {
    switch ($code) {
        case 'no-such-user':
        case 'unknown-email':
            foreach ($err as $msg) {
?>
<div class="notice error is-dismissible">
<p><?php print esc_html($msg);?></p>
</div>
<?php
            }
    } // end switch
}
?>
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
                    <?php $this->printUsersForDataList();?>
                </datalist>
                <p class="description">
                    <?php print sprintf(
                        esc_html__('Your team members are the people you want to notify in the event of an emergency. Type your trusted friends names or email addresses in the text box, then press %1$sSave Changes%2$s at the bottom of this page. The person you choose will get an invitation asking them to confirm joining your team.', 'better-angels'),
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
                                value="<?php esc_attr_e($guardian->ID);?>"
                                name="<?php esc_attr_e($this->prefix);?>my_guardians[]">
                            <?php print esc_html($guardian->user_nicename);?>
                            <?php if (!empty($guardian->{$this->prefix . 'guardian_info'})) : ?>
                            <?php
                                $descriptors = array();
                                if (empty($guardian->{$this->prefix . 'guardian_info'}['receive_alerts'])) {
                                    $descriptors[] = esc_html__('fake', 'better-angels');
                                }
                                if (empty($guardian->{$this->prefix . 'guardian_info'}['confirmed'])) {
                                    $descriptors[] = esc_html__('pending', 'better-angels');
                                }
                                if (!empty($descriptors)) {
                                    print '<span class="description">(' . join(', ', $descriptors) . ')</span>';
                                }
                            ?>
                            <?php endif;?>
                        </label>
                        <label>
                            <input
                                type="radio"
                                <?php checked(false, empty($guardian->{$this->prefix . 'guardian_info'}['receive_alerts']));?>
                                value="1"
                                name="<?php esc_attr_e($this->prefix);?>guardian[<?php print esc_attr($guardian->ID);?>][receive_alerts]">
                            <?php esc_html_e('real', 'better-angels');?>
                        </label>
                        <label>
                            <input
                                type="radio"
                                <?php checked(true, empty($guardian->{$this->prefix . 'guardian_info'}['receive_alerts']));?>
                                value="0"
                                name="<?php esc_attr_e($this->prefix);?>guardian[<?php print esc_attr($guardian->ID);?>][receive_alerts]">
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
