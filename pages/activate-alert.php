<?php
$options = get_option($this->prefix . 'settings');
?>
<form id="activate-alert-form" action="<?php print esc_attr(admin_url('admin-ajax.php'));?>" method="POST">
    <input type="hidden"
        name="action"
        value="<?php print esc_attr($this->prefix)?>findme"
    />

    <div id="modal-features" class="hidden">
        <?php if (isset($options['future_alerts'])) : ?>
        <button id="schedule-future-alert-btn" class="btn" type="button">
            <img src="<?php print plugins_url('../img/stock_alarm.svg', __FILE__);?>" alt="<?php esc_attr_e('Schedule timed alert', 'better-angels');?>" />
        </button>
        <?php endif; ?>
        <button id="activate-msg-btn-submit" class="btn" type="button">
            <img src="<?php print plugins_url('../img/chat-bubble-1.svg', __FILE__);?>" alt="<?php esc_attr_e('Send emergency message', 'better-angels')?>" />
        </button>
    </div>
    <button id="activate-btn-submit" class="btn">
        <img src="<?php print plugins_url('../img/life-ring.svg', __FILE__);?>" alt="<?php esc_attr_e('Activate alert', 'better-angels')?>" />
    </button>
</form>

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
<div id="scheduled-alert-modal" class="modal" role="dialog" aria-labelledby="scheduled-alert-modal-label">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'better-angels');?>"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="scheduled-alert-modal-label"><?php esc_html_e('Schedule a timed alert', 'better-angels');?></h4>
            </div>
            <div class="modal-body">
                <textarea id="scheduled-crisis-message"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-dismiss="modal"><?php esc_html_e('Schedule', 'better-angels');?></button>
            </div>
        </div><!-- .modal-content -->
    </div><!-- .modal-dialog -->
</div><!-- .modal -->
