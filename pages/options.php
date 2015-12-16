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
                    <p class="description"><?php esc_html_e('When checked, users will be able to schedule alerts to be sent some time in the future. This is sometimes known as a "safe call," a way of alerting a response team to a potentially dangerous situation if the alerter is unreachable.', 'better-angels');?></p>
                </label>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e(str_replace('-', '_', $this->prefix));?>alert_ttl"><?php esc_html_e('Alert time to live', 'better-angels');?></label>
            </th>
            <td>
                <label>
                    <input type="number"
                        id="<?php print esc_attr(str_replace('-', '_', $this->prefix));?>alert_ttl_num"
                        name="<?php print esc_attr($this->prefix);?>settings[alert_ttl_num]"
                        placeholder="2"
                        value="<?php if (!empty($options['alert_ttl_num'])) { print esc_attr($options['alert_ttl_num']); }?>"
                        size="3"
                    />
                    <select
                        id="<?php print esc_attr(str_replace('-', '_', $this->prefix));?>alert_ttl_multiplier"
                        name="<?php print esc_attr($this->prefix);?>settings[alert_ttl_multiplier]"
                    />
                        <option value="<?php print esc_attr(DAY_IN_SECONDS);?>" <?php if (isset($options['alert_ttl_multiplier'])) selected($options['alert_ttl_multiplier'], DAY_IN_SECONDS);?>><?php esc_html_e('days', 'better-angels');?></option>
                        <option value="<?php print esc_attr(HOUR_IN_SECONDS);?>" <?php if (isset($options['alert_ttl_multiplier'])) selected($options['alert_ttl_multiplier'], HOUR_IN_SECONDS);?>><?php esc_html_e('hours', 'better-angels');?></option>
                        <option value="<?php print esc_attr(WEEK_IN_SECONDS);?>" <?php if (isset($options['alert_ttl_multiplier'])) selected($options['alert_ttl_multiplier'], WEEK_IN_SECONDS);?>><?php esc_html_e('weeks', 'better-angels');?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Choose how long alerts are kept active. Alerts that were created before this threshold will be automatically deleted.');?></p>
                </label>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e(str_replace('-', '_', $this->prefix));?>delete_old_incident_media"><?php esc_html_e('Delete attached incident media when deleting old alerts?', 'better-angels');?></label>
            </th>
            <td>
                <label>
                    <input type="checkbox"
                        id="<?php print esc_attr(str_replace('-', '_', $this->prefix));?>delete_old_incident_media"
                        name="<?php print esc_attr($this->prefix);?>settings[delete_old_incident_media]"
                        <?php if (isset($options['delete_old_incident_media'])) { checked($options['delete_old_incident_media']); }?>
                        value="1"
                        />
                    <span class="description"><?php esc_html_e('When checked, any media (images, audio recordings, videos) attached to a Buoy Alert will be deleted along with the Alert itself when it expires.', 'better-angels');?></span>
                </label>
            </td>
        </tr>
    </tbody>
</table>
</fieldset>
<fieldset id="plugin-extras"><legend><?php esc_html_e('Plugin extras', 'better-angels');?></legend>
<table class="form-table" summary="<?php esc_attr_e('Additional options to customize plugin behavior.', 'better-angels');?>">
    <tbody>
        <tr>
            <th>
                <label for="<?php esc_attr_e($this->prefix);?>debug">
                    <?php esc_html_e('Enable detailed debugging information?', 'better-angels');?>
                </label>
            </th>
            <td>
                <input type="checkbox"
                    id="<?php esc_attr_e($this->prefix);?>debug"
                    name="<?php esc_attr_e($this->prefix);?>settings[debug]"
                    <?php if (isset($options['debug'])) { checked($options['debug']); }?>
                    value="1"
                    />
                <label for="<?php esc_attr_e($this->prefix);?>debug"><span class="description"><?php
        print sprintf(
            esc_html__('Turn this on only if you are experiencing problems using this plugin, or if you were told to do so by someone helping you fix a problem (or if you really know what you are doing). When enabled, extremely detailed technical information is displayed as a WordPress admin notice when you take certain actions. If you have also enabled WordPress\'s built-in debugging (%1$s) and debug log (%2$s) feature, additional information will be sent to a log file (%3$s). This file may contain sensitive information, so turn this off and erase the debug log file when you have resolved the issue.', 'better-angels'),
            '<a href="https://codex.wordpress.org/Debugging_in_WordPress#WP_DEBUG"><code>WP_DEBUG</code></a>',
            '<a href="https://codex.wordpress.org/Debugging_in_WordPress#WP_DEBUG_LOG"><code>WP_DEBUG_LOG</code></a>',
            '<code>' . content_url() . '/debug.log' . '</code>'
        );
                ?></span></label>
            </td>
        </tr>
    </tbody>
</table>
</fieldset>
<?php submit_button();?>
</form>
