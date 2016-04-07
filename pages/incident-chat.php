<?php
$options = WP_Buoy_Settings::get_instance();
$alerter = new WP_Buoy_User($alert->wp_post->post_author);
$curr_user = wp_get_current_user();
$auto_show_modal = ($curr_user->ID === $alerter->wp_user->ID) ? 'auto-show-modal' : '';
?>
<div id="alert-map" class="well well-sm hidden">
    <div role="toolbar" aria-label="<?php esc_html_e('Incident toolbar', 'buoy');?>" class="btn-toolbar">
        <div class="btn-group btn-group-lg" role="group">
            <button id="toggle-incident-map-btn" class="btn btn-default" type="button"><?php esc_html_e('Show Map', 'buoy');?></button>
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" type="button">
                <span class="caret"></span>
                <span class="sr-only"><?php esc_html_e('Toggle map actions', 'buoy');?></span>
            </button>
            <ul class="dropdown-menu">
                <li><a id="fit-map-to-markers-btn" role="button" href="#"><?php esc_html_e('Zoom to fit', 'buoy');?></a></li>
                <li role="separator" class="divider"></li>
                <li><a id="go-to-my-location" href="#" role="button" data-user-id="<?php print esc_attr(get_current_user_id());?>"><?php esc_html_e('Go to my location', 'buoy');?></a></li>
            </ul>
        </div>
        <div id="incident-media-group" class="btn-group btn-group-lg" role="group">
            <button id="upload-media-btn" type="button" class="btn btn-default"><?php esc_html_e('Upload media', 'buoy');?></button>
            <input type="file" multiple="multiple" accept="audio/*,video/*,image/*" style="display:none;" />
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" type="button">
                <span class="caret"></span>
                <span class="sr-only"><?php esc_html_e('Toggle incident media', 'buoy');?></span>
            </button>
            <ul class="dropdown-menu">
                <?php print self::getIncidentMediaList($alert->wp_post->ID);?>
            </ul>
        </div>
    </div><!-- /.btn-toolbar -->
</div><!-- /.well.well-sm -->
<div id="map-container"
    data-incident-hash="<?php print esc_attr($alert->get_hash());?>"
    data-incident-latitude="<?php print esc_attr(get_post_meta($alert->wp_post->ID, 'geo_latitude', true));?>"
    data-incident-longitude="<?php print esc_attr(get_post_meta($alert->wp_post->ID, 'geo_longitude', true));?>"
    data-responder-info='<?php print esc_attr(json_encode($alert->get_incident_state()));?>'
    data-my-avatar-url="<?php print esc_attr(get_avatar_url(get_current_user_id(), array('size' => 32)));?>"
    >
    <div id="map">
        <noscript>
            <div class="notice error">
                <p><?php esc_html_e('To view a map of the crisis area, JavaScript must be enabled in your browser.', 'buoy');?></p>
            </div>
        </noscript>
    </div>
</div>

<div id="alert-chat-room-container" style="height: 100%;">
    <?php do_action(self::$prefix.'_chat_room', $alert, $curr_user);?>
</div>

<div id="safety-information-modal" class="modal fade <?php esc_attr_e($auto_show_modal);?>" role="dialog" aria-labelledby="safety-information-modal-label">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'buoy');?>"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="safety-information-modal-label"><?php esc_html_e('Safety information', 'buoy');?></h4>
            </div>
            <div class="modal-body">
                <?php print $options->get('safety_info'); // TODO: Can we harden against XSS here? ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'buoy');?></button>
            </div>
        </div><!-- .modal-content -->
    </div><!-- .modal-dialog -->
</div><!-- .modal.fade -->
