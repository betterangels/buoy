/**
 * Buoy Alert Form ("Screen")
 *
 * @license GPL-3.0
 */

BUOY.Views.ActivateAlertForm = Backbone.View.extend({
    'events': {
        'submit': 'handleSubmit'
    }
});

/**
 * Handles the alert submission form (a new alert).
 *
 * @param {Event} e
 */
BUOY.Views.ActivateAlertForm.prototype.handleSubmit = function (e) {
    e.preventDefault();
    this.showSubmittingAlertModal();
    this.activateAlert();
}

/**
 * Displays the "Detecting your location and sending alert" modal.
 */
BUOY.Views.ActivateAlertForm.prototype.showSubmittingAlertModal = function () {
    jQuery(buoy_dom_hooks.submitting_alert_modal).modal({
        'show': true,
        'backdrop': 'static'
    });
};

/**
 * Activates an alert.
 *
 * Creates a `BUOY.submit_alert_timer_id` to work around a Firefox
 * bug. This is later cleared in the `postAlert` method.
 */
BUOY.Views.ActivateAlertForm.prototype.activateAlert = function () {
    // Always post an alert even if we fail to get geolocation.
    navigator.geolocation.getCurrentPosition(this.postAlert, this.postAlert, {
        'timeout': 5000 // wait max of 5 seconds to get a location
    });
    // In Firefox, if the user clicks "Not now" when asked to give
    // geolocation permissions, the above timeout never gets called.
    // See the debate at
    // https://bugzilla.mozilla.org/show_bug.cgi?id=675533
    // Until this debate is resolved, we need to manually detect this
    // timeout ourselves.
    BUOY.submit_alert_timer_id = setTimeout(this.postAlert, 6000);
    console.log(BUOY.submit_alert_timer_id);
};

/**
 * Sends an HTTP POST with alert data.
 *
 * @todo This should use Backbone.Sync instead of raw HTTP POST'ing, but that's for later.
 *
 * @see {@link https://developer.mozilla.org/en-US/docs/Web/API/Position}
 *
 * @param {Position} position
 */
BUOY.Views.ActivateAlertForm.prototype.postAlert = function (position) {
    if (BUOY.submit_alert_timer_id) { clearTimeout(BUOY.submit_alert_timer_id); }
    var data = {
        'action': jQuery(buoy_dom_hooks.activate_alert_form + ' input[name="action"]').val(),
        'buoy_nonce': jQuery('#buoy_nonce').val()
    };
    if (position && position.coords) {
        data.pos = position.coords;
    }
    if (jQuery(buoy_dom_hooks.crisis_message).val()) {
        data.msg = jQuery(buoy_dom_hooks.crisis_message).val();
    }
    var teams = jQuery(buoy_dom_hooks.choose_teams_panel + ' :checked').map(function () {
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

// -------------
// Buttons
// -------------
BUOY.Views.ImmediateAlertButton = Backbone.View.extend({
    'events': {
        'click': function () {
            this.$el.prop('disabled', true);
        }
    }
});

// -------------
// RUNTIME
// -------------
jQuery(document).ready(function () {
    TEST = new BUOY.Views.ActivateAlertForm({
        el: document.getElementById('activate-alert-form')
    });
    new BUOY.Views.ImmediateAlertButton({
        el: document.getElementById('immediate-alert-button')
    });
});
