<?php $options = self::get_instance();?>
<h2><?php esc_html_e('Buoy Settings', 'buoy');?></h2>
<form method="POST" action="<?php print admin_url('options.php');?>">
<?php settings_fields(WP_Buoy_Plugin::$prefix . '_settings');?>
<fieldset><legend><?php esc_html_e('Configure site-wide Buoy options', 'buoy');?></legend>
<table class="form-table" summary="<?php esc_attr_e('', 'buoy');?>">
    <tbody>
        <tr>
            <th>
                <label for="<?php esc_attr_e(WP_Buoy_Plugin::$prefix);?>_safety_info"><?php esc_html_e('Safety information', 'buoy');?></label>
            </th>
            <td>
                <p class="description">
                    <?php print sprintf(
                        esc_html__('Provide relevant safety information. This can include hotline phone numbers, links to nearby shelters or other support resources, and any other information you believe would be useful. When finished, press %1$sSave Changes%2$s at the bottom of this page.', 'buoy'),
                        '<strong>', '</strong>'
                    );?>
                </p>
                <?php wp_editor(
                    $options->get('safety_info'),
                    WP_Buoy_Plugin::$prefix . '_safety_info',
                    array(
                        'textarea_name' => WP_Buoy_Plugin::$prefix . '_settings[safety_info]',
                        'drag_drop_upload' => true
                    )
                );?>
            </td>
        </tr>
<?php if (function_exists('posix_getpwuid')) : ?>
        <tr>
            <th>
                <label for="<?php esc_attr_e(WP_Buoy_Plugin::$prefix);?>_future_alerts">
                    <?php esc_html_e('Enable timed alerts/safe calls', 'buoy');?>
                </label>
            </th>
            <td>
                <input type="checkbox"
                    id="<?php print esc_attr(WP_Buoy_Plugin::$prefix);?>_future_alerts"
                    name="<?php print esc_attr(WP_Buoy_Plugin::$prefix);?>_settings[future_alerts]"
                    <?php checked($options->get('future_alerts'));?>
                    value="1"
                    />
                <span class="description"><?php esc_html_e('When checked, users will be able to schedule alerts to be sent some time in the future. This is sometimes known as a "safe call," a way of alerting a response team to a potentially dangerous situation if the alerter is unreachable.', 'buoy');?></span>
            </td>
        </tr>
<?php endif; ?>
        <tr>
            <th>
                <label for="<?php esc_attr_e(WP_Buoy_Plugin::$prefix);?>_alert_ttl_num"><?php esc_html_e('Alert time to live', 'buoy');?></label>
            </th>
            <td>
                <input type="number"
                    id="<?php print esc_attr(WP_Buoy_Plugin::$prefix);?>_alert_ttl_num"
                    name="<?php print esc_attr(WP_Buoy_Plugin::$prefix);?>_settings[alert_ttl_num]"
                    placeholder="2"
                    value="<?php print esc_attr($options->get('alert_ttl_num'));?>"
                    size="3"
                />
                <select
                    id="<?php print esc_attr(WP_Buoy_Plugin::$prefix);?>_alert_ttl_multiplier"
                    name="<?php print esc_attr(WP_Buoy_Plugin::$prefix);?>_settings[alert_ttl_multiplier]"
                />
                    <option value="<?php print esc_attr(DAY_IN_SECONDS);?>" <?php selected($options->get('alert_ttl_multiplier'), DAY_IN_SECONDS);?>><?php esc_html_e('days', 'buoy');?></option>
                    <option value="<?php print esc_attr(HOUR_IN_SECONDS);?>" <?php selected($options->get('alert_ttl_multiplier'), HOUR_IN_SECONDS);?>><?php esc_html_e('hours', 'buoy');?></option>
                    <option value="<?php print esc_attr(WEEK_IN_SECONDS);?>" <?php selected($options->get('alert_ttl_multiplier'), WEEK_IN_SECONDS);?>><?php esc_html_e('weeks', 'buoy');?></option>
                </select>
                <p class="description"><?php esc_html_e('Choose how long alerts are kept active. Alerts that were created before this threshold will be automatically deleted.');?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="<?php esc_attr_e(WP_Buoy_Plugin::$prefix);?>_delete_old_incident_media"><?php esc_html_e('Delete attached incident media when deleting old alerts?', 'buoy');?></label>
            </th>
            <td>
                <input type="checkbox"
                    id="<?php print esc_attr(WP_Buoy_Plugin::$prefix);?>_delete_old_incident_media"
                    name="<?php print esc_attr(WP_Buoy_Plugin::$prefix);?>_settings[delete_old_incident_media]"
                    <?php checked($options->get('delete_old_incident_media'));?>
                    value="1"
                />
                <span class="description"><?php esc_html_e('When checked, any media (images, audio recordings, videos) attached to a Buoy Alert will be deleted along with the Alert itself when it expires.', 'buoy');?></span>
            </td>
        </tr>
    </tbody>
</table>
</fieldset>
<fieldset id="integration-options"><legend><?php esc_html_e('Integration options', 'buoy');?></legend>
<table class="form-table">
    <tbody>
        <tr>
            <th>
                <label for="<?php esc_attr_e(WP_Buoy_Plugin::$prefix);?>_chat_system">
                    <?php esc_html_e('Default chat system provider', 'buoy');?>
                </label>
            </th>
            <td>
                <select
                    id="<?php esc_attr_e(WP_Buoy_Plugin::$prefix);?>_chat_system"
                    name="<?php esc_attr_e(WP_Buoy_Plugin::$prefix);?>_settings[chat_system]"
                >
                    <option value="post_comments" <?php selected($options->get('chat_system'), 'post_comments');?>><?php esc_html_e('Built-in (WordPress commenting system)');?></option>
                    <option value="tlk.io" <?php selected($options->get('chat_system'), 'tlk.io');?>><?php esc_html_e('Tlk.io', 'buoy');?></option>
                </select>
                <p class="description"><?php print sprintf(
                    esc_html__('Choose a default chat system. The built-in WordPress commenting system ensures chat room data never leaves your Buoy. The other options embed external services into your alert. Only use an external service if you trust its operators. Learn more about %1$sBuoy chat system options%2$s.'),
                    '<a href="https://github.com/meitar/better-angels/wiki/Buoy-Administration-Guide#chat-system-options" target="_blank">', '</a>'
                );?></p>
            </td>
        </tr>
    </tbody>
</table>
</fieldset>
<fieldset id="plugin-extras"><legend><?php esc_html_e('Plugin extras', 'buoy');?></legend>
<table class="form-table" summary="<?php esc_attr_e('Additional options to customize plugin behavior.', 'buoy');?>">
    <tbody>
        <tr>
            <th>
                <label for="<?php esc_attr_e(WP_Buoy_Plugin::$prefix);?>_debug">
                    <?php esc_html_e('Enable detailed debugging information?', 'buoy');?>
                </label>
            </th>
            <td>
                <input type="checkbox"
                    id="<?php esc_attr_e(WP_Buoy_Plugin::$prefix);?>_debug"
                    name="<?php esc_attr_e(WP_Buoy_Plugin::$prefix);?>_settings[debug]"
                    <?php checked($options->get('debug'));?>
                    value="1"
                />
                <span class="description"><?php
        print sprintf(
            esc_html__('Turn this on only if you are experiencing problems using this plugin, or if you were told to do so by someone helping you fix a problem (or if you really know what you are doing). When enabled, extremely detailed technical information is displayed as a WordPress admin notice when you take certain actions. If you have also enabled WordPress\'s built-in debugging (%1$s) and debug log (%2$s) feature, additional information will be sent to a log file (%3$s). This file may contain sensitive information, so turn this off and erase the debug log file when you have resolved the issue.', 'buoy'),
            '<a href="https://codex.wordpress.org/Debugging_in_WordPress#WP_DEBUG"><code>WP_DEBUG</code></a>',
            '<a href="https://codex.wordpress.org/Debugging_in_WordPress#WP_DEBUG_LOG"><code>WP_DEBUG_LOG</code></a>',
            '<code>' . content_url() . '/debug.log' . '</code>'
        );
                ?></span>
            </td>
        </tr>
    </tbody>
</table>
</fieldset>
<?php submit_button();?>
</form>
