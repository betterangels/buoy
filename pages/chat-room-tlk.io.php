<div id="tlkio" data-channel="<?php print esc_attr($alert->get_chat_room_name());?>" data-nickname="<?php print esc_attr($curr_user->display_name);?>">
    <noscript>
        <div class="notice error">
            <p><?php esc_html_e('To access the incident chat room, JavaScript must be enabled in your browser.', 'buoy');?></p>
        </div>
    </noscript>
</div>
<script async src="https://tlk.io/embed.js" type="text/javascript"></script>
