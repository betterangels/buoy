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
                <div class="form-group">
                    <label for="scheduled-datetime"><?php esc_html_e('Send alert at', 'better-angels');?></label>
                    <input id="scheduled-datetime" class="form-control" type="datetime" />
                    <p class="help-block"><?php esc_html_e('Enter a date and time at which your alert will be sent. You can either select from the pop-up options after clicking in the field, or type a natural-language English expression, such as "next Thursday" (meaning the upcoming Thursday at this time) or "+3 hours" (meaning three hours from now).', 'better-angels');?></p>
                    <label for="scheduled-crisis-message"><?php esc_html_e('Crisis message', 'better-angels');?></label>
                    <textarea id="scheduled-crisis-message" class="form-control"></textarea>
                    <p class="help-block"><?php esc_html_e('Briefly provide an explanation of where you expect to be and what you think you will need from your response team. This message will be sent to your response team at the time you specified unless you cancel the alert.', 'better-angels');?></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-dismiss="modal"><?php esc_html_e('Schedule alert', 'better-angels');?></button>
            </div>
        </div><!-- .modal-content -->
    </div><!-- .modal-dialog -->
</div><!-- .modal -->
<div id="submitting-alert-modal" class="modal" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <p><?php esc_html_e('Detecting your location and sending alert&hellip;', 'better-angels');?></p>
                <div class="pulse-loader"><?php esc_html_e('Loading&hellip;', 'better-angels');?></div>
            </div>
        </div><!-- .modal-content -->
    </div><!-- .modal-dialog -->
</div><!-- .modal -->
