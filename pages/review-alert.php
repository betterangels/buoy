<?php
$alert_post = $this->getAlert(urldecode($_GET[$this->prefix . 'incident_hash']));
$alerter = get_userdata($alert_post->post_author);
$respond_url = wp_nonce_url(
    admin_url(
        '?page=' . $this->prefix . 'incident-chat'
        . '&' . $this->prefix . 'incident_hash=' . get_post_meta($alert_post->ID, $this->prefix . 'incident_hash', true)
    ),
    $this->prefix . 'chat', $this->prefix . 'nonce'
);
?>
<div id="map-container" class="container-fluid"
    data-incident-hash="<?php print esc_attr(get_post_meta($alert_post->ID, $this->prefix . 'incident_hash', true));?>"
    data-incident-latitude="<?php print esc_attr(get_post_meta($alert_post->ID, 'geo_latitude', true));?>"
    data-incident-longitude="<?php print esc_attr(get_post_meta($alert_post->ID, 'geo_longitude', true));?>"
    data-my-avatar-url="<?php print esc_attr(get_avatar_url(get_current_user_id(), array('size' => 32)));?>"
    >
    <div id="map">
        <noscript>
            <div class="notice error">
                <p><?php esc_html_e('To view a map of the crisis area, JavaScript must be enabled in your browser.', 'better-angels');?></p>
            </div>
        </noscript>
    </div>
</div>
<h1><?php print sprintf(esc_html__('%1$s sent an alert', 'better-angels'), $alerter->display_name);?></h1>
<blockquote id="crisis-message">
    <p>
        <?php print esc_html($alert_post->post_title);?>
    </p>
</blockquote>
<form id="incident-response-form" action="<?php print esc_url($respond_url);?>" method="POST">
    <input type="hidden"
        id="<?php print esc_attr($this->prefix)?>location"
        name="<?php print esc_attr($this->prefix)?>location"
        value=""
    />
    <p class="submit">
        <input type="submit" class="button button-primary"
            id="<?php esc_attr_e($this->prefix);?>respond"
            name="<?php esc_attr_e($this->prefix);?>respond"
            value="<?php esc_attr_e('Respond', 'better-angels');?>"
        />
    </p>
</form>
