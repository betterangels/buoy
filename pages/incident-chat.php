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
<?php if ('tlk.io' === $alert->get_chat_system()) { ?>
    <div id="tlkio" data-channel="<?php print esc_attr($alert->get_chat_room_name());?>" data-nickname="<?php print esc_attr($curr_user->display_name);?>">
        <noscript>
            <div class="notice error">
                <p><?php esc_html_e('To access the incident chat room, JavaScript must be enabled in your browser.', 'buoy');?></p>
            </div>
        </noscript>
    </div>
    <script async src="https://tlk.io/embed.js" type="text/javascript"></script>
<?php } else if ('post_comments' === $alert->get_chat_system()) { ?>
    <div id="comments-chat">
        <iframe
            src="<?php print admin_url('admin-ajax.php');?>?action=<?php print esc_attr(self::$prefix.'_post_comments_chat');?>&amp;hash=<?php print esc_attr($alert->get_hash());?>#page-footer"
            name="<?php print esc_attr(self::$prefix);?>_post_comments_chat"
            width="100%"
            allowfullscreen="allowfullscreen"
            seamless="seamless"
        >
            <?php esc_html_e('To access the incident chat room, inline frames must be supported by your browser.', 'buoy');?>
        </iframe>
<?php
add_filter('comments_open', '__return_true');
$submit_field  = '<div class="input-group">';
$submit_field .= '<input type="text" id="comment" name="comment"';
$submit_field .= ' class="form-control" aria-requred="true" required="required"';
$submit_field .= ' placeholder="'.$curr_user->display_name.'&hellip;" />';
$submit_field .= '<span class="input-group-btn">%1$s</span> %2$s';
$submit_field .= wp_nonce_field(self::$prefix.'_chat_comment', self::$prefix.'_chat_comment_nonce', true, false);
$submit_field .= '</div><!-- .input-group -->';
ob_start();
comment_form(array(
    'comment_field' => '', // use the submit_field instead,
    'label_submit' => esc_attr__('Send', 'buoy'),
    'class_submit' => 'btn btn-success',
    'id_submit' => 'submit-btn',
    'name_submit' => 'submit-btn',
    'submit_button' => '<button type="submit" class="%3$s" id="%2$s" name="%1$s">%4$s</button>',
    'submit_field' => $submit_field,
    'logged_in_as' => '',
    'title_reply' => '',
    'title_reply_before' => '',
    'cancel_reply_before' => '',
    'cancel_reply_link' => ' ',
), $alert->wp_post->ID);
$comment_form = ob_get_contents();
ob_end_clean();
print links_add_target($comment_form, self::$prefix.'_post_comments_chat', array('form'));
?>
    </div>
<?php } ?>
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
