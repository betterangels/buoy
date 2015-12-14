<?php
$options = get_option($this->prefix . 'settings');
$alert_post = $this->getAlert(urldecode($_GET[$this->prefix . 'incident_hash']));
$alerter = get_userdata($alert_post->post_author);
$curr_user = wp_get_current_user();
$auto_show_modal = ($curr_user->ID === $alerter->ID) ? 'auto-show-modal' : '';
?>
<div id="alert-map" class="well well-sm hidden">
    <div role="toolbar" aria-label="<?php esc_html_e('Incident toolbar', 'better-angels');?>" class="btn-toolbar">
        <div class="btn-group btn-group-lg" role="group">
            <button id="toggle-incident-map-btn" class="btn btn-default" type="button"><?php esc_html_e('Show Map', 'better-angels');?></button>
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" type="button">
                <span class="caret"></span>
                <span class="sr-only"><?php esc_html_e('Toggle map actions', 'better-angels');?></span>
            </button>
            <ul class="dropdown-menu">
                <li><a id="fit-map-to-markers-btn" role="button" href="#"><?php esc_html_e('Zoom to fit', 'better-angels');?></a></li>
                <li role="separator" class="divider"></li>
                <li><a id="go-to-my-location" href="#" role="button" data-user-id="<?php print esc_attr($alerter->ID);?>"><?php esc_html_e('Go to my location', 'better-angels');?></a></li>
            </ul>
        </div>
        <div id="incident-media-group" class="btn-group btn-group-lg" role="group">
            <button id="upload-media-btn" type="button" class="btn btn-default"><?php esc_html_e('Upload media', 'better-angels');?></button>
            <input type="file" multiple="multiple" accept="audio/*,video/*,image/*" style="display:none;" />
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" type="button">
                <span class="caret"></span>
                <span class="sr-only"><?php esc_html_e('Toggle incident media', 'better-angels');?></span>
            </button>
            <ul class="dropdown-menu">
                <?php print $this->getIncidentMediaList($alert_post->ID);?>
            </ul>
        </div>
    </div><!-- /.btn-toolbar -->
</div><!-- /.well.well-sm -->
<div id="map-container" class="container-fluid"
    data-incident-hash="<?php print esc_attr(get_post_meta($alert_post->ID, $this->prefix . 'incident_hash', true));?>"
    data-incident-latitude="<?php print esc_attr(get_post_meta($alert_post->ID, 'geo_latitude', true));?>"
    data-incident-longitude="<?php print esc_attr(get_post_meta($alert_post->ID, 'geo_longitude', true));?>"
    data-responder-info='<?php print esc_attr(json_encode($this->getResponderInfo($alert_post)));?>'
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
<div id="tlkio" data-channel="<?php print esc_attr(get_post_meta($alert_post->ID, $this->prefix . 'chat_room_name', true));?>" data-nickname="<?php esc_attr_e($curr_user->display_name);?>" style="height:100%;">
    <noscript>
        <div class="notice error">
            <p><?php esc_html_e('To access the incident chat room, JavaScript must be enabled in your browser.', 'better-angels');?></p>
        </div>
    </noscript>
</div>
<script async src="https://tlk.io/embed.js" type="text/javascript"></script>

<div id="safety-information-modal" class="modal fade <?php esc_attr_e($auto_show_modal);?>" role="dialog" aria-labelledby="safety-information-modal-label">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'better-angels');?>"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="safety-information-modal-label"><?php esc_html_e('Safety information', 'better-angels');?></h4>
            </div>
            <div class="modal-body">
                <?php print $options['safety_info']; // TODO: Can we harden against XSS here? ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'better-angels');?></button>
            </div>
        </div><!-- .modal-content -->
    </div><!-- .modal-dialog -->
</div><!-- .modal.fade -->
