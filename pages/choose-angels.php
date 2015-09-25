<h2><?php esc_html_e('Choose your team members', 'better-angels');?></h2>
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
                <datalist id="<?php esc_attr_e($this->prefix);?>guardians_list"/>
                    <?php $this->printAllUsersForDataList();?>
                </datalist>
                <p class="description">
                    <?php print sprintf(
                        esc_html__('Your team members are the people you want to notify in the event of an emergency. Type your trusted friends names in the text box, then press %1$sSave Changes%2$s at the bottom of this page.', 'better-angels'),
                        '<strong>', '</strong>'
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
                <?php if (empty($guardians)) : print sprintf(esc_html__('You have not chosen any team members. Maybe you want to %1$sadd a team member%2$s?', 'better-angels'), '<a href="#' . $this->prefix . 'guardians">', '</a>'); endif;?>
                <?php foreach ($guardians as $guardian) : ?>
                    <li>
                        <label>
                            <input
                                type="checkbox"
                                checked="checked"
                                value="<?php esc_attr_e($guardian->ID);?>"
                                name="<?php esc_attr_e($this->prefix);?>my_guardians[]">
                            <?php print esc_html($guardian->user_nicename);?>
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
