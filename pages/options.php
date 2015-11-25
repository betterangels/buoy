<?php
$options = get_option($this->prefix . 'settings');
?>
<h2><?php esc_html_e('Buoy Settings', 'better-angels');?></h2>
<form method="POST" action="<?php print admin_url('options.php');?>">
<?php settings_fields($this->prefix . 'settings');?>
<fieldset><legend><?php esc_html_e('Configure site-wide Buoy options', 'better-angels');?></legend>
<table class="form-table" summary="<?php esc_attr_e('', 'better-angels');?>">
    <tbody>
        <tr>
            <th>
                <label for="<?php esc_attr_e(str_replace('-', '_', $this->prefix));?>safety_info"><?php esc_html_e('Safety information', 'better-angels');?></label>
            </th>
            <td>
                <p class="description">
                    <?php print sprintf(
                        esc_html__('Provide relevant safety information. This can include hotline phone numbers, links to nearby shelters or other support resources, and any other information you believe would be useful. When finished, press %1$sSave Changes%2$s at the bottom of this page.', 'better-angels'),
                        '<strong>', '</strong>'
                    );?>
                </p>
                <?php wp_editor(
                    $options['safety_info'],
                    str_replace('-', '_', $this->prefix) . 'safety_info',
                    array(
                        'textarea_name' => $this->prefix . 'settings[safety_info]',
                        'drag_drop_upload' => true
                    )
                );?>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e(str_replace('-', '_', $this->prefix));?>future_alerts"><?php esc_html_e('Timed alerts/Safe calls', 'better-angels');?></label>
            </th>
            <td>
                <label>
                    <input type="checkbox"
                        id="<?php print esc_attr(str_replace('-', '_', $this->prefix));?>future_alerts"
                        name="<?php print esc_attr($this->prefix);?>settings[future_alerts]"
                        <?php if (isset($options['future_alerts'])) { checked($options['future_alerts']); }?>
                        value="1"
                        />
                    <?php esc_html_e('Enable timed alerts ("safe call" feature)', 'better-angels');?>
                    <p class="description"><?php esc_html_e('When checked, users will be able to schedule alerts to be sent some time in the future. This is sometimes known as a "safe call," a way of alerting a response team to a potentially dangerous situation if the alerter is unreachable.', 'better-angels');;?></p>
                </label>
            </td>
        </tr>
    </tbody>
</table>
</fieldset>
<?php submit_button();?>
</form>
