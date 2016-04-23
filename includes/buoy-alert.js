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
        jQuery('#custom-message-alert-btn').on('click', handleCustomMessageButton);
        jQuery('#schedule-future-alert-btn').on('click', handleFutureAlertButton);

        jQuery('#activate-alert-form').on('submit', handleSubmit);
        jQuery('#emergency-message-modal button.btn-success').on('click', handleCustomMessageSubmit);

        jQuery('#emergency-message-modal').on('shown.bs.modal', function () {
            jQuery('#crisis-message').focus();
        })

        jQuery('#scheduled-alert-modal button.btn-success').on('click', handleFutureAlertSubmit);

        if (jQuery('#scheduled-datetime-tz').length) {
            jQuery('#scheduled-datetime-tz').datetimepicker({
                'lazyInit': true,
                'lang': buoy_vars.ietf_language_tag,
                'minDate': 0, // today is the earliest allowable date
                'mask': true,
                'validateOnBlur': false
            });
        }

        jQuery('#wp-admin-bar-buoy_my_scheduled_alerts a').each(function () {
            jQuery(this).on('click', unscheduleAlert);
        });

    };
    
    /**
     * Handles the alert submission form (a new alert).
     *
     * @param {Event} e
     */
    var handleSubmit = function (e) {
        e.preventDefault();
        jQuery(this).find('#activate-btn-submit').prop('disabled', true);
        showSubmittingAlertModal();
        activateAlert();
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
            jQuery('#scheduled-alert-modal button.btn-success').prop('disabled', false);
            jQuery('#scheduled-alert-modal button.btn-success').html(buoy_vars.i18n_schedule_alert);
            jQuery('#submitting-alert-modal').modal('hide');
        });
    };

    /**
     * Displays the "Detecting your location and sending alert" modal.
     */
    var showSubmittingAlertModal = function () {
        jQuery('#submitting-alert-modal').modal({
            'show': true,
            'backdrop': 'static'
        });
    };

    /**
     * Handles user input clicking the custom message alert button.
     */
    var handleCustomMessageButton = function () {
        jQuery('#choose-teams-panel').removeClass('hidden');
        jQuery('#emergency-message-modal .modal-body').append(jQuery('#choose-teams-panel').detach());
        jQuery('#emergency-message-modal').modal('show');
    };

    /**
     * Handles user input clicking the future alert button.
     */
    var handleFutureAlertButton = function () {
        jQuery('#choose-teams-panel').removeClass('hidden');
        jQuery('#scheduled-alert-modal .modal-body').append(jQuery('#choose-teams-panel').detach());
        jQuery('#scheduled-alert-modal').modal('show');
    };

    /**
     * Activates an alert.
     */
    var activateAlert = function () {
        // Always post an alert even if we fail to get geolocation.
        navigator.geolocation.getCurrentPosition(postAlert, postAlert, {
            'timeout': 5000 // wait max of 5 seconds to get a location
        });
        // In Firefox, if the user clicks "Not now" when asked to give
        // geolocation permissions, the above timeout never gets called.
        // See the debate at
        // https://bugzilla.mozilla.org/show_bug.cgi?id=675533
        // Until this debate is resolved, we need to manually detect this
        // timeout ourselves.
        submit_alert_timer_id = setTimeout(postAlert, 6000);
    };

    /**
     * Sends an HTTP POST with alert data.
     *
     * @see {@link https://developer.mozilla.org/en-US/docs/Web/API/Position}
     *
     * @param {Position}
     */
    var postAlert = function (position) {
        if (submit_alert_timer_id) { clearTimeout(submit_alert_timer_id); }
        var data = {
            'action': jQuery('#activate-alert-form input[name="action"]').val(),
            'buoy_nonce': jQuery('#buoy_nonce').val()
        };
        if (position && position.coords) {
            data.pos = position.coords;
        }
        if (jQuery('#crisis-message').val()) {
            data.msg = jQuery('#crisis-message').val();
        }
        var teams = jQuery('#choose-teams-panel :checked').map(function () {
            return this.value;
        }).get();
        if (teams.length) {
            data.buoy_teams = teams;
        }
        jQuery.post(ajaxurl, data,
            function (response) {
                if (response.success) {
                    // decode the HTML-encoded stuff WP sends
                    window.location.href = jQuery('<div/>').html(response.data).text();
                }
            },
            'json'
        );
    };

    /**
     * Creates a scheduled alert.
     *
     * @param callback
     */
    var scheduleAlert = function (callback) {
        var data = {
            'action': jQuery('#activate-alert-form input[name="action"]').val(),
            'msg': jQuery('#scheduled-crisis-message').val(),
            'scheduled-datetime-utc': new Date(jQuery('#scheduled-datetime-tz').val()).toUTCString(),
            'buoy_teams': jQuery('#choose-teams-panel :checked').map(function () {
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
                    jQuery('#scheduled-alert-modal').find('.has-error').removeClass('has-error');
                    jQuery('#scheduled-alert-modal').find('[aria-invalid]').removeAttr('aria-invalid');
                    jQuery('#scheduled-alert-modal').find('div.alert[role="alert"]').remove();
                    jQuery('#scheduled-alert-modal .modal-body > :first-child')
                        .before('<div class="alert alert-success" role="alert"><p>' + response.data.message + '</p></div>');
                    jQuery('#scheduled-alert-modal input, #scheduled-alert-modal textarea').val('');
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
                        jQuery('#wp-admin-bar-buoy-alert-menu').remove();
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
