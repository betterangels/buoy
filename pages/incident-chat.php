<?php
$curr_user = wp_get_current_user();
?>
<div id="alert-map" role="alert" class="alert alert-warning alert-dismissible fade in">
    <button id="toggle-incident-map-btn" class="btn btn-default" type="button"><?php esc_html_e('Show Map', 'better-angels');?></button>
</div>
<div id="map-container" class="container-fluid">
    <div id="map"></div>
</div>
<div id="tlkio" data-channel="<?php print esc_attr($this->getChatRoomName());?>" data-nickname="<?php esc_attr_e($curr_user->display_name);?>" style="height:100%;"></div><script async src="https://tlk.io/embed.js" type="text/javascript"></script>

<div id="safety-information-modal" class="modal fade" role="dialog" aria-labelledby="safety-information-modal-label">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'better-angels');?>"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="safety-information-modal-label"><?php esc_html_e('Safety information', 'better-angels');?></h4>
            </div>
            <div class="modal-body">
                <h1><?php esc_html_e('Emergency Numbers', 'better-angels');?></h1>
                <ul>
                    <li><?php esc_html_e('Seek a shelter for victims of domestic violence, it is always safe to call the shelter.');?></li>
                    <li><?php esc_html_e('Call the National Domestic Violence Hotline and they can provide you the location of the nearest shelter: 1-800-799-7233', 'better-angels');?></li>
                    <li><?php esc_html_e('Love is Respect - To speak to a supportive peer advocate:', 'better-angels');?>
                        <ul>
                            <li><?php esc_html_e('call:', 'better-angels');?> <a href="tel:18663319474">1.866.331.9474</a></li>
                            <li><?php esc_html_e('txt:', 'better-angels');?> <a href="sms:22522?body=loveis">loveis to 22522</a></li>
                            <li><?php esc_html_e('or chat 24/7 at', 'better-angels');?> <a href="http://loveisrespect.org/">loveisrespect.org</a></li>
                        </ul>
                    </li>
                    <li><?php esc_html_e('New Mexico Crisis and Access Line can provide directions to Shelters, refer to Mental Health, Detox, Vocational assistance, Legal Assistance:', 'better-angels');?> <a href="tel:18556627474">1-855-662-7474</a></li>
                    <li><?php esc_html_e('If you have a companion animal you need to keep safe but can’t take with you this number is to the New Mexico Companion Animal Rescue Effort (CARE):', 'better-angels');?>
                        <ul>
                            <li><a href="tel:18443252273">1-844-325-2273</a></li>
                            <li><?php esc_html_e('TTY or TTD:', 'better-angels');?> <a href="tel:18006598831">1-800-659-8831</a></li>
                        </ul>
                    </li>
                </ul>
                <h1><?php esc_html_e('Staying Safe', 'better-angels');?></h1>
                <ul>
                    <li><?php esc_html_e('If your abuser is becoming violent, or you believe they could be, leave as quickly and safely as possible and call 911.', 'better-angels');?></li>
                    <li><?php esc_html_e('When you decide to leave, determine a safe route for escape.', 'better-angels');?></li>
                    <li><?php esc_html_e('If an argument seems unavoidable, stay away from any rooms such as the kitchen, bathroom, and garage where you might be trapped or there might be weapons readily available.', 'better-angels');?></li>
                    <li><?php esc_html_e('If you can, pack a bag for you and your children with a change of clothes and all important documents such as birth certificates, social security cards, shot records, check books, ATM cards, drives license, and any medication and money you have, and put it in a safe place where you can access it quickly, like in the trunk of your car or at a friend’s house.', 'better-angels');?></li>
                    <li><?php esc_html_e('If you choose to use alcohol or drugs it is important to be aware of the potential dangers.', 'better-angels');?></li>
                    <li><?php esc_html_e('Teach your children how to call 911 and let them know it is okay to call if there is any violence happening.', 'better-angels');?></li>
                    <li><?php esc_html_e('Be aware of your surroundings and others at all times.', 'better-angels');?></li>
                    <li><?php esc_html_e('Do not go to places where your abuser or their friends frequent.', 'better-angels');?></li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php esc_html_e('Close', 'better-angels');?></button>
            </div>
        </div><!-- .modal-content -->
    </div><!-- .modal-dialog -->
</div><!-- .modal.fade -->
