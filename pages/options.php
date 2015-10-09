<?php
$options = get_option($this->prefix . 'settings');
?>
<h2><?php esc_html_e('Buoy Settings', 'better-angels');?></h2>
<form method="POST" action="<?php print admin_url('options.php');?>">
<?php settings_fields($this->prefix . 'settings');?>
<fieldset><legend><?php esc_html_e('Compose Safety Information', 'better-angels');?></legend>
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
    </tbody>
</table>
</fieldset>
<?php submit_button();?>
</form>
