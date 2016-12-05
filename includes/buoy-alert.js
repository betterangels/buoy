/**
 * Buoy Alert Screen
 *
 * @license GPL-3.0
 */

/**
 * Module for the alert screen.
 */
var BUOY_ALERT = (function () {

    /**
     * Backup timer.
     *
     * This is the window timer ID used for ensuring that an alert is
     * still POST'ed to the server if the user clicks "Not now" when
     * they are asked for location sharing permissions. In FireFox, a
     * user who answers "Not now" will otherwise never send the POST.
     */
    var submit_alert_timer_id;

    /**
     * Attaches event listeners to the Panic buttons.
     */
    var attachHandlers = function () {
        jQuery(buoy_dom_hooks.custom_alert_button).on('click', handleCustomMessageButton);
        jQuery(buoy_dom_hooks.timed_alert_button).on('click', handleFutureAlertButton);

        jQuery(buoy_dom_hooks.activate_alert_form).on('submit', handleSubmit);
        jQuery(buoy_dom_hooks.emergency_message_modal + ' button.btn-success').on('click', handleCustomMessageSubmit);

        jQuery(buoy_dom_hooks.emergency_message_modal).on('shown.bs.modal', function () {
            jQuery(buoy_dom_hooks.crisis_message).focus();
        })

        jQuery(buoy_dom_hooks.scheduled_alert_modal + ' button.btn-success').on('click', handleFutureAlertSubmit);

        if (jQuery(buoy_dom_hooks.scheduled_datetime_tz).length) {
            jQuery(buoy_dom_hooks.scheduled_datetime_tz).datetimepicker({
                'lazyInit': true,
                'lang': buoy_vars.ietf_language_tag,
                'minDate': 0, // today is the earliest allowable date
                'mask': true,
                'validateOnBlur': false
            });
        }

        jQuery(buoy_dom_hooks.scheduled_alerts_menu_links).each(function () {
            jQuery(this).on('click', unscheduleAlert);
        });

    };
    
    /**
     * Handles user input pressing the "Send" button on a custom message alert.
     */
    var handleCustomMessageSubmit = function () {
        showSubmittingAlertModal();
        activateAlert();
    };

    /**
     * Handles user input clicking "Schedule alert" in the modal.
     */
    var handleFutureAlertSubmit = function () {
        jQuery(this).prop('disabled', true);
        jQuery(this).html(buoy_vars.i18n_scheduling_alert);
        showSubmittingAlertModal();
        scheduleAlert(function () {
            jQuery(buoy_dom_hooks.scheduled_alert_modal + ' button.btn-success').prop('disabled', false);
            jQuery(buoy_dom_hooks.scheduled_alert_modal + ' button.btn-success').html(buoy_vars.i18n_schedule_alert);
            jQuery(buoy_dom_hooks.submitting_alert_modal).modal('hide');
        });
    };

    /**
     * Handles user input clicking the custom message alert button.
     */
    var handleCustomMessageButton = function () {
        jQuery(buoy_dom_hooks.choose_teams_panel).removeClass('hidden');
        jQuery(buoy_dom_hooks.emergency_message_modal + ' .modal-body').append(jQuery(buoy_dom_hooks.choose_teams_panel).detach());
        jQuery(buoy_dom_hooks.emergency_message_modal).modal('show');
    };

    /**
     * Handles user input clicking the future alert button.
     */
    var handleFutureAlertButton = function () {
        jQuery(buoy_dom_hooks.choose_teams_panel).removeClass('hidden');
        jQuery(buoy_dom_hooks.scheduled_alert_modal + ' .modal-body').append(jQuery(buoy_dom_hooks.choose_teams_panel).detach());
        jQuery(buoy_dom_hooks.scheduled_alert_modal).modal('show');
    };

    /**
     * Creates a scheduled alert.
     *
     * @param callback
     */
    var scheduleAlert = function (callback) {
        var data = {
            'action': jQuery(buoy_dom_hooks.activate_alert_form + ' input[name="action"]').val(),
            'msg': jQuery(buoy_dom_hooks.scheduled_crisis_message).val(),
            'scheduled-datetime-utc': new Date(jQuery(buoy_dom_hooks.scheduled_datetime_tz).val()).toUTCString(),
            'buoy_teams': jQuery(buoy_dom_hooks.choose_teams_panel + ' :checked').map(function () {
                return this.value;
            }).get(),
            'buoy_nonce': jQuery('#buoy_nonce').val()
        };
        jQuery.post(ajaxurl, data,
            function (response) {
                if (false === response.success) {
                    for (k in response.data) {
                        jQuery('#' + response.data[k].code).parent().addClass('has-error');
                        jQuery('#' + response.data[k].code).attr('aria-invalid', true);
                        jQuery('<div class="alert alert-danger" role="alert"><p>' + response.data[k].message + '</p></div>')
                            .insertBefore('#' + response.data[k].code);
                    }
                } else {
                    jQuery(buoy_dom_hooks.scheduled_alert_modal).find('.has-error').removeClass('has-error');
                    jQuery(buoy_dom_hooks.scheduled_alert_modal).find('[aria-invalid]').removeAttr('aria-invalid');
                    jQuery(buoy_dom_hooks.scheduled_alert_modal).find('div.alert[role="alert"]').remove();
                    jQuery(buoy_dom_hooks.scheduled_alert_modal + ' .modal-body > :first-child')
                        .before('<div class="alert alert-success" role="alert"><p>' + response.data.message + '</p></div>');
                    jQuery(buoy_dom_hooks.scheduled_alert_modal + ' input, ' + buoy_dom_hooks.scheduled_alert_modal + ' textarea').val('');
                }
                callback();
            },
            'json'
        );
    };

    /**
     * Handles user input for unscheduling the given alert from the admin bar.
     */
    var unscheduleAlert = function (e) {
        e.preventDefault();
        var a_el = jQuery(this);
        jQuery.post(a_el.attr('href'), {'action': 'buoy_unschedule_alert'},
            function (response) {
                if (response.success) {
                    a_el.remove();
                    if (0 === BUOY.countIncidentMenuItems()) {
                        jQuery(buoy_dom_hooks.menu_id).remove();
                    }
                }
            },
            'json'
        );
    };

    return {
        'attachHandlers': attachHandlers
    };

})();
