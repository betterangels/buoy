<button id="activate-msg-btn-submit" class="btn">
    <img src="<?php print plugins_url('../img/chat-bubble-1.svg', __FILE__);?>" alt="<?php esc_attr_e('Send emergency message', 'better-angels')?>" />
</button>
<button id="activate-btn-submit" class="btn">
    <img src="<?php print plugins_url('../img/life-ring.svg', __FILE__);?>" alt="<?php esc_attr_e('Activate alert', 'better-angels')?>" />
</button>

<div id="emergency-message-modal" class="modal" role="dialog" aria-labelledby="emergency-message-modal-label">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'better-angels');?>"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="emergency-message-modal-label"><?php esc_html_e('Message to my team', 'better-angels');?></h4>
            </div>
            <div class="modal-body">
                <textarea id="crisis-message"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-dismiss="modal"><?php esc_html_e('Send', 'better-angels');?></button>
            </div>
        </div><!-- .modal-content -->
    </div><!-- .modal-dialog -->
</div><!-- .modal -->
