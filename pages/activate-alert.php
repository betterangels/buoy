<?php $options = WP_Buoy_Settings::get_instance();?>
<form id="activate-alert-form" action="<?php print esc_attr(admin_url('admin-ajax.php'));?>" method="POST">
    <?php wp_nonce_field(self::$prefix . '_new_alert', self::$prefix . '_nonce');?>
    <input type="hidden"
        name="action"
        value="<?php print esc_attr(self::$prefix);?>_new_alert"
    />

    <div id="modal-features" class="hide-if-no-js">
        <?php if ($options->get('future_alerts')) : ?>
        <button id="timed-alert-button" class="btn" type="button">
            <img src="<?php print esc_attr(plugins_url('../img/stock_alarm.svg', __FILE__));?>" alt="<?php esc_attr_e('Schedule timed alert', 'buoy');?>" />
        </button>
        <?php endif; ?>
        <button id="contextual-alert-button" class="btn" type="button">
            <img src="<?php print esc_attr(plugins_url('../img/chat-bubble-1.svg', __FILE__));?>" alt="<?php esc_attr_e('Send emergency message', 'buoy')?>" />
        </button>
    </div>
    <button id="immediate-alert-button" class="btn">
        <img src="<?php print esc_attr(plugins_url('../img/life-ring.svg', __FILE__));?>" alt="<?php esc_attr_e('Activate alert', 'buoy')?>" />
    </button>
    <div id="choose-teams-panel" class="hidden">
        <?php $buoy_user->renderChooseTeamsPanelHtml();?>
    </div>
</form>

<div id="contextual-alert-modal" class="modal" role="dialog" aria-labelledby="contextual-alert-modal-label">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'buoy');?>"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="contextual-alert-modal-label"><?php esc_html_e('Message to my team', 'buoy');?></h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <textarea id="crisis-message" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-dismiss="modal"><?php esc_html_e('Send', 'buoy');?></button>
            </div>
        </div><!-- .modal-content -->
    </div><!-- .modal-dialog -->
</div><!-- .modal -->
<div id="timed-alert-modal" class="modal" role="dialog" aria-labelledby="timed-alert-modal-label">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'buoy');?>"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="timed-alert-modal-label"><?php esc_html_e('Schedule a timed alert', 'buoy');?></h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div>
                        <label for="scheduled-datetime-tz"><?php esc_html_e('Send alert at', 'buoy');?></label>
                        <input id="scheduled-datetime-tz" name="scheduled-datetime-tz" class="form-control" type="datetime-local" />
                        <p class="help-block"><?php esc_html_e('Enter a date and time at which your alert will be sent. You can either select from the pop-up options after clicking in the field, or type a specific date and time in the "YYYY/MM/DD HH:mm" format.', 'buoy');?></p>
                    </div>
                    <div>
                        <label for="scheduled-crisis-message"><?php esc_html_e('Crisis message', 'buoy');?></label>
                        <textarea id="scheduled-crisis-message" class="form-control"></textarea>
                        <p class="help-block"><?php esc_html_e('Briefly provide an explanation of where you expect to be and what you think you will need from your response team. This message will be sent to your response team at the time you specified unless you cancel the alert.', 'buoy');?></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-dismiss="modal"><?php esc_html_e('Schedule alert', 'buoy');?></button>
            </div>
        </div><!-- .modal-content -->
    </div><!-- .modal-dialog -->
</div><!-- .modal -->
<div id="submitting-alert-modal" class="modal" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <p><?php esc_html_e('Detecting your location and sending alert&hellip;', 'buoy');?></p>
                <div class="pulse-loader"><?php esc_html_e('Loading&hellip;', 'buoy');?></div>
            </div>
        </div><!-- .modal-content -->
    </div><!-- .modal-dialog -->
</div><!-- .modal -->
