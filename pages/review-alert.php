<?php
$alert_post = $this->getAlert(urldecode($_GET[$this->prefix . 'incident_hash']));
$alerter = get_userdata($alert_post->post_author);
$respond_link = wp_nonce_url(
    admin_url(
        '?page=' . $this->prefix . 'incident-chat'
        . '&' . $this->prefix . 'incident_hash=' . get_post_meta($alert_post->ID, $this->prefix . 'incident_hash', true)
    ),
    $this->prefix . 'chat', $this->prefix . 'nonce'
);
?>
<div id="map-container"
    data-alerter="<?php print esc_attr($alerter->display_name);?>"
    data-latitude="<?php print esc_attr(get_post_meta($alert_post->ID, 'geo_latitude', true));?>"
    data-longitude="<?php print esc_attr(get_post_meta($alert_post->ID, 'geo_longitude', true));?>"
    data-icon="<?php print esc_attr(get_avatar_url($alerter->ID, array('size' => 32)));?>"
    data-info-window-text="<?php print sprintf(esc_attr__("%s's last known location", 'better-angels'), $alerter->display_name);?>"
    >
    <div id="map"></div>
</div>
<h1><?php print sprintf(esc_html__('%1$s sent an alert', 'better-angels'), $alerter->display_name);?></h1>
<blockquote id="crisis-message">
    <p>
        <?php print esc_html($alert_post->post_title);?>
    </p>
</blockquote>
<form id="incident-response-form" action="<?php print esc_url($respond_link);?>" method="POST">
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
