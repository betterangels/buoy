/**
 * Buoy Alert module.
 *
 * @file All of Buoy's client-side alert-handling JavaScript.
 *
 * @copyright Copyright (c) 2015-2016 by Meitar "maymay" Moscovitz
 *
 * @license GPL-3.0
 */

// We use an IIFE here to protect the page's global scope.
(function () { // begin immediately-invoked function expression (IIFE)
    // The Buoy user interface is primarily plain, old-school HTML5.
    // There's nothing fancy about it, and much of it will still work
    // correctly without JavaScript enabled. However, to offer a slicker
    // visual user experience, we use BackboneJS to progressively enhance
    // the static HTML content.
    //
    // Backbone helps us structure our interface code into "Views" that
    // can be composed together. This means one View can contain another
    // View inside it. Each interface element that we display in the HTML
    // (like buttons, forms, and so on) are associated with a View at
    // runtime by code in this file. Interactions that require JavaScript
    // to work, like showing and hiding dialogues or other visual effects
    // of a similar nature, are performed by calling methods on the View
    // objects defined below.

    // --------------------- //
    // VIEWS (UI components) //
    // --------------------- //

    // Gotta declare 'em all.
    var ActivateAlertForm,
        ChooseTeamsPanel,
        ContextualAlertButton,
        ContextualAlertModal,
        ImmediateAlertButton,
        SubmittingAlertModal,
        TimedAlertButton,
        TimedAlertModal;

    /**
     * Activate Alert Form
     *
     * This is the containing element for the "Activate Alert" screen.
     *
     * @see {@link https://github.com/betterangels/buoy/wiki/Alerts#accessing-the-activate-alert-screen}
     */
    BUOY.Views.ActivateAlertForm = Backbone.View.extend({
        'events': {
            'submit': 'handleSubmit'
        }
    });

    /**
     * Immediate Alert Button
     *
     * @see {@link https://github.com/betterangels/buoy/wiki/Alerts#immediate-alerts}
     */
    BUOY.Views.ImmediateAlertButton = Backbone.View.extend({
        'events': {
            'click': function () {
                this.$el.prop('disabled', true); // prevent double-clicks
            }
        }
    });

    /**
     * Contextual Alert Button
     *
     * @see {@link https://github.com/betterangels/buoy/wiki/Alerts#contextual-alerts}
     */
    BUOY.Views.ContextualAlertButton = Backbone.View.extend({
        'events': {
            'click': function () {
                ChooseTeamsPanel.show();
                ContextualAlertModal.show();
            }
        }
    });

    /**
     * Timed Alert Button
     *
     * @see {@link https://github.com/betterangels/buoy/wiki/Alerts#timed-alerts}
     */
    BUOY.Views.TimedAlertButton = Backbone.View.extend({
        'events': {
            'click': function () {
                ChooseTeamsPanel.show();
                TimedAlertModal.show();
            }
        }
    });

    /**
     * Contextual Alert Modal
     *
     * This is a Bootstrap Modal.
     *
     * @see {@link https://getbootstrap.com/javascript/#modals}
     */
    BUOY.Views.ContextualAlertModal = Backbone.View.extend({
        'events': {
            'click button.btn-success': function (e) {
                ActivateAlertForm.handleSubmit(e);
            }
        }
    });

    /**
     * Visually displays the modal. (It is hidden by default.)
     */
    BUOY.Views.ContextualAlertModal.prototype.show = function () {
        this.$el.find('.modal-body').append(ChooseTeamsPanel.$el.detach());
        this.$el.modal('show');
        this.$el.find('textarea').focus();
    };

    /**
     * Timed Alert Modal
     *
     * This is a Bootstrap Modal.
     *
     * @see {@link https://getbootstrap.com/javascript/#modals}
     */
    BUOY.Views.TimedAlertModal = Backbone.View.extend({
        'events': {
            'click button.btn-success': function (e) {
                this.$el.find('button.btn-success').prop('disabled', true);
                this.$el.find('button.btn-success').html(buoy_vars.i18n_scheduling_alert);
                SubmittingAlertModal.show();
                ActivateAlertForm.scheduleAlert(this.handleTimedAlertScheduled);
            }
        },
        'initialize': function () {
            // Init the datetimepicker jQuery plugin.
            this.$el.find('#scheduled-datetime-tz').datetimepicker({
                'lazyInit': true,
                'lang': buoy_vars.ietf_language_tag,
                'minDate': 0, // today is the earliest allowable date
                'mask': true,
                'validateOnBlur': false
            });
        }
    });

    /**
     * Updates the Timed Alert Modal after scheduling a Timed Alert.
     *
     * @callback handleTimedAlertScheduled
     * @param {Backbone.View} view
     */
    BUOY.Views.TimedAlertModal.prototype.handleTimedAlertScheduled = function (view) {
        view.$el.find('button.btn-success').prop('disabled', false);
        view.$el.find('button.btn-success').html(buoy_vars.i18n_schedule_alert);
        SubmittingAlertModal.$el.modal('hide');
        view.$el.modal('show'); // not sure why I have to re-show it?
    };

    /**
     * Visually displays the modal. (It is hidden by default.)
     */
    BUOY.Views.TimedAlertModal.prototype.show = function () {
        this.$el.find('.modal-body').append(ChooseTeamsPanel.$el.detach());
        this.$el.modal('show');
        this.$el.find('input[type^="datetime"]').focus();
    };

    /**
     * Submitting Alert Modal
     *
     * This is a Bootstrap Modal.
     *
     * @see {@link https://getbootstrap.com/javascript/#modals}
     */
    BUOY.Views.SubmittingAlertModal = Backbone.View.extend({
        // empty for now
    });

    /**
     * Displays the "Detecting your location and sending alert" modal.
     */
    BUOY.Views.SubmittingAlertModal.prototype.show = function () {
        this.$el.modal({
            'show': true,
            'backdrop': 'static'
        });
    };

    /**
     * Choose Teams Panel
     *
     * This is a Bootstrap Panel.
     *
     * @see {@link https://getbootstrap.com/components/#panels}
     */
    BUOY.Views.ChooseTeamsPanel = Backbone.View.extend({
        // empty for now
    });

    /**
     * Show the Choose Teams Panel. (It is hidden by default.)
     */
    BUOY.Views.ChooseTeamsPanel.prototype.show = function () {
        this.$el.removeClass('hidden');
    };

    /**
     * Handles the alert submission form (a new alert).
     *
     * @param {Event} e
     */
    BUOY.Views.ActivateAlertForm.prototype.handleSubmit = function (e) {
        e.preventDefault();
        SubmittingAlertModal.show();
        this.activateAlert();
    };

    /**
     * Activates an alert.
     *
     * Creates a `BUOY.submit_alert_timer_id` to work around a Firefox
     * bug. This is later cleared in the `postAlert` method.
     *
     * @todo This should eventually become a call to the alert's
     *       Backbone.Model's `.save()` method.
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
    };

    /**
     * Creates a scheduled alert.
     *
     * Calls back to the Timed Alert Modal's
     * `handleTimedAlertScheduled` callback method.
     *
     * @param {handleTimedAlertScheduled} callback
     *
     * @todo This needs some major cleanup, as well. There is a lot
     *       of duplication between this and the `postAlert` method.
     *       Hopefully both of these can be handled by moving towards
     *       using Backbone Models for alerts.
     */
    BUOY.Views.ActivateAlertForm.prototype.scheduleAlert = function (callback) {
        var data = {
            'action': this.$el.find('input[name="action"]').val(),
            'msg': TimedAlertModal.$el.find('textarea').val(),
            'scheduled-datetime-utc': new Date(TimedAlertModal.$el.find('#scheduled-datetime-tz').val()).toUTCString(),
            'buoy_teams': ChooseTeamsPanel.$el.find(':checked').map(function () {
                return this.value;
            }).get(),
            'buoy_nonce': this.$el.find('#buoy_nonce').val()
        };
        jQuery.post(ajaxurl, data,
            function (response) {
                TimedAlertModal.$el.find('div.alert[role="alert"]').remove();
                if (false === response.success) {
                    for (k in response.data) {
                        jQuery('#' + response.data[k].code).parent().addClass('has-error');
                        jQuery('#' + response.data[k].code).attr('aria-invalid', true);
                        jQuery('<div class="alert alert-danger" role="alert"><p>' + response.data[k].message + '</p></div>')
                            .insertBefore('#' + response.data[k].code);
                    }
                } else {
                    TimedAlertModal.$el.find('.has-error').removeClass('has-error');
                    TimedAlertModal.$el.find('[aria-invalid]').removeAttr('aria-invalid');
                    TimedAlertModal.$el.find('.modal-body > :first-child')
                        .before('<div class="alert alert-success" role="alert"><p>' + response.data.message + '</p></div>');
                    TimedAlertModal.$el.find('input, textarea').val('');
                }
                callback(TimedAlertModal);
            },
            'json'
        );
    };

    /**
     * Sends an HTTP POST with alert data.
     *
     * @todo This should use Backbone.Sync instead of raw HTTP POST'ing, but that's for later.
     *       In the meantime, we have some hacky stuff in here where we refrence the
     *       instantiation of this object by its instance variable name. and we should remove
     *       this as soon as the alert Backbone Model is ready for use.
     *
     * @see {@link https://developer.mozilla.org/en-US/docs/Web/API/Position}
     *
     * @param {Position} position
     */
    BUOY.Views.ActivateAlertForm.prototype.postAlert = function (position) {
        if (BUOY.submit_alert_timer_id) { clearTimeout(BUOY.submit_alert_timer_id); }
        var data = {
            // TODO: notice that `ActivateAlertForm` here is an ugly hack
            'action': ActivateAlertForm.$el.find('input[name="action"]').val(),
            'buoy_nonce': ActivateAlertForm.$el.find('#buoy_nonce').val()
        };
        if (position && position.coords) {
            data.pos = position.coords;
        }
        // TODO: notice that `ContextualAlertModal` here is an ugly hack
        if (ContextualAlertModal.$el.find('#crisis-message').val()) {
            data.msg = ContextualAlertModal.$el.find('#crisis-message').val();
        }
        var teams = ChooseTeamsPanel.$el.find(':checked').map(function () {
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
     * Timed Alert menu items.
     *
     * These are items displayed inside of the WordPress Admin Bar.
     *
     * @see {@link https://developer.wordpress.org/reference/classes/WP_Admin_Bar/}
     */
    BUOY.Views.TimedAlertMenuItem = Backbone.View.extend({
        'events': {
            'click': 'unscheduleAlert'
        }
    });

    /**
     * Unschedules a Timed Alert
     *
     * @param {Event} e
     */
    BUOY.Views.TimedAlertMenuItem.prototype.unscheduleAlert = function (e) {
        e.preventDefault();
        var data = {
            'action': 'buoy_unschedule_alert'
        }
        var item = this;
        jQuery.post(this.$el.attr('href'), data, function (response) {
            if (response.success) {
                item.$el.remove();
                if (0 === jQuery('#wp-admin-bar-buoy-alerts-menu a').length) {
                    jQuery('#wp-admin-bar-buoy-alerts-menu').remove();
                }
            }
        }, 'json');
    };

    // ------- //
    // RUNTIME //
    // ------- //
    // Once the HTML DOM is ready, we can actually instantiate our Views.
    jQuery(document).ready(function () {
        ActivateAlertForm = new BUOY.Views.ActivateAlertForm({
            'el': document.getElementById('activate-alert-form')
        });

        ChooseTeamsPanel = new BUOY.Views.ChooseTeamsPanel({
            'el': document.getElementById('choose-teams-panel')
        });

        ContextualAlertModal = new BUOY.Views.ContextualAlertModal({
            'el': document.getElementById('contextual-alert-modal')
        });

        ContextualAlertButton = new BUOY.Views.ContextualAlertButton({
            'el': document.getElementById('contextual-alert-button')
        });

        TimedAlertButton = new BUOY.Views.TimedAlertButton({
            'el': document.getElementById('timed-alert-button')
        });

        ImmediateAlertButton = new BUOY.Views.ImmediateAlertButton({
            'el': document.getElementById('immediate-alert-button')
        });

        SubmittingAlertModal = new BUOY.Views.SubmittingAlertModal({
            'el': document.getElementById('submitting-alert-modal')
        });

        TimedAlertModal = new BUOY.Views.TimedAlertModal({
            'el': document.getElementById('timed-alert-modal')
        });

        jQuery('#wp-admin-bar-buoy_my_scheduled_alerts a').each(function () {
            new BUOY.Views.TimedAlertMenuItem({
                'el': this
            });
        });
    });

})(); // end immediately-invoked function expression (IIFE)
