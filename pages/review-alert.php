<?php
$alerter = get_userdata(username_exists($_GET['who']));
$respond_link = wp_nonce_url(
    admin_url(
        '?page=' . $this->prefix . 'incident-chat'
        . '&chat_room=' . urldecode($_GET['chat_room'])
    ),
    $this->prefix . 'chat', $this->prefix . 'nonce'
);
?>
<div id="map-container"
    data-alerter="<?php print esc_attr($alerter->display_name);?>"
    data-latitude="<?php print esc_attr($_GET['latitude']);?>"
    data-longitude="<?php print esc_attr($_GET['longitude']);?>"
    data-icon="<?php print esc_attr(get_avatar_url($alerter->ID, array('size' => 32)));?>"
    data-info-window-text="<?php print sprintf(esc_attr__("%s's last known location", 'better-angels'), $alerter->display_name);?>"
    >
    <div id="map"></div>
</div>
<h1><?php print sprintf(esc_html__('%1$s sent an alert', 'better-angels'), $alerter->display_name);?></h1>
<blockquote id="crisis-message">
    <p>
        <?php print esc_html(urldecode($_GET['msg']));?>
    </p>
</blockquote>
<p class="submit">
    <a class="button button-primary" href="<?php print esc_url($respond_link);?>"><?php esc_html_e('Respond', 'better-angels');?></a>
</p>
