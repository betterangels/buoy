<?php
$alerter = new WP_Buoy_User($alert->wp_post->post_author);
$respond_url = wp_nonce_url(
    admin_url(
        '?page=' . parent::$prefix . '_chat'
        . '&' . parent::$prefix . '_hash=' . $alert->get_hash()
    ),
    parent::$prefix . '_chat', parent::$prefix . '_nonce'
);
?>
<div id="buoy-map-container"
    data-incident-hash="<?php print esc_attr($alert->get_hash());?>"
    data-incident-latitude="<?php print esc_attr(get_post_meta($alert->wp_post->ID, 'geo_latitude', true));?>"
    data-incident-longitude="<?php print esc_attr(get_post_meta($alert->wp_post->ID, 'geo_longitude', true));?>"
    data-my-avatar-url="<?php print esc_attr(get_avatar_url(get_current_user_id(), array('size' => 32)));?>"
    >
    <div id="buoy-map">
        <p style="padding-top: 3em; text-align:center;">
            <img
                src="<?php print esc_url(plugins_url('img/spinner-2x.gif', dirname(__FILE__)));?>"
                alt="<?php esc_attr_e('Loading&hellip;.', 'buoy');?>"
            />
        </p>
        <noscript>
            <div class="notice error">
                <p><?php esc_html_e('To view a map of the crisis area, JavaScript must be enabled in your browser.', 'buoy');?></p>
            </div>
        </noscript>
    </div>
</div>
<h1><?php
print sprintf(
    esc_html__('%1$s sent an alert %2$s', 'buoy'),
    $alerter->wp_user->display_name,
    sprintf(_x('%s ago', '%s = human-readable time difference', 'buoy'), human_time_diff(strtotime($alert->wp_post->post_date), current_time('timestamp')))
);
?></h1>
<blockquote id="crisis-message">
    <p>
        <?php print esc_html($alert->wp_post->post_title);?>
    </p>
</blockquote>
<form id="incident-response-form" action="<?php print esc_attr($respond_url);?>" method="POST">
    <input type="hidden"
        id="<?php print esc_attr(parent::$prefix)?>_location"
        name="<?php print esc_attr(parent::$prefix)?>_location"
        value=""
    />
    <p class="submit">
        <input type="submit" class="btn btn-lg btn-primary btn-block"
            id="<?php esc_attr_e(parent::$prefix);?>_respond"
            name="<?php esc_attr_e(parent::$prefix);?>_respond"
            value="<?php esc_attr_e('Respond', 'buoy');?>"
        />
    </p>
</form>
