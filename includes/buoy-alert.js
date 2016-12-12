/**
 * Buoy Alert Screen
 *
 * @license GPL-3.0
 */

/**
 This file determines the UI interactions for the buoy-alert web page.
 index.php?page=buoy_activate_alert

 It complements the page class-buoy-alert.php

 The file contains
 (1) single main function and
 (2) 6 namespaces to handle the UI
 (3) A sharedEventBus to handle communication between UI elements

 Function:
 activateAlert() : sends an Alert to the Server

 The UI functionality and the corresponding code Namespaces and Objects are
 (1)  Functionality: Schedule Alert
 Purpose  :   Add an alert for a future date.
 UI Elements:  Schedule Button and Schedule Modal
 Namespace:  ScheduleAlertNamespace

 (2)  Functionality:  Message Teams
 Purpose:   Send Message to Team Members
 UI Elements: Message Button and Message Modal
 Namespace:  MessageTeamNamespace

 (3)  Functionality:  Activate Alert
 Purpose:   Immediately send out an alert
 UI Elements: Activate Alert Form
 Namespace:  activateAlertNamespace

 (4)  Functionality:  Menu Bar
 Purpose:   Access List of Buoy Alerts on Menu Bar to Unschedule them
 UI Elements: Buoy Menu Bar and Scheduled Alert Link
 Namespace:  menubarScheduledAlertsNamespace

 (5) Functionality:  Choose Teams Panel
 Purpose:  Wraps the Panel for Choosing Team Members
 UI Elements: Choose Team Panel
 Namespace:  ChooseTeamsPanelNamespace

 (6) Functionality:  Submitting Alert Modal
 Purpose:  Wraps the Modal which appears after Submitting an Alert
 UI Elements: Submitting Alert Modal
 Namespace:  SubmittingAlertModalNamespace

 */
(function ($, _, Backbone) {
    
  var sharedEventBus = _.extend({}, Backbone.Events);
  sharedEventBus.events = {
    showSubmittingAlertModalEvent: "showSubmittingAlertModalEvent",
    hideSubmittingModalEvent: "hideSubmittingModalEvent",
    showChooseTeamsPanelEvent: "showChooseTeamsPanelEvent"
  };

  /**
   * Activates an alert.
   */
  var submit_alert_timer_id;

  function activateAlert() {
    // Always post an alert even if we fail to get geolocation.
    navigator.geolocation.getCurrentPosition(postAlert, postAlert, {
      "timeout": 5000 // wait max of 5 seconds to get a location
    });
    // In Firefox, if the user clicks "Not now" when asked to give
    // geolocation permissions, the above timeout never gets called.
    // See the debate at
    // https://bugzilla.mozilla.org/show_bug.cgi?id=675533
    // Until this debate is resolved, we need to manually detect this
    // timeout ourselves.
    submit_alert_timer_id = setTimeout(postAlert, 6000);

    /**
     * Sends an HTTP POST with alert data.
     *
     * @see {@link https://developer.mozilla.org/en-US/docs/Web/API/Position}
     *
     * @param {Position}
     */
    function postAlert(position) {
      if (submit_alert_timer_id) {
        clearTimeout(submit_alert_timer_id);
      }
      var data = {
        "action": $('#activate-alert-form input[name="action"]').val(),
        "buoy_nonce": $("#buoy_nonce").val()
      };
      if (position && position.coords) {
        data.pos = position.coords;
      }
      if ($("#crisis-message").val()) {
        data.msg = $("#crisis-message").val();
      }
      var teams = $("#choose-teams-panel :checked").map(function () {
        return this.value;
      }).get();
      if (teams.length) {
        data.buoy_teams = teams;
      }
      $.post(ajaxurl, data,
        function (response) {
          if (response.success) {
            // decode the HTML-encoded stuff WP sends
            window.location.href = $("<div/>").html(response.data).text();
          }
        },
        "json"
      );
    }
  }

  /**
   *  NAMESPACES FOR SHARED VIEWS
   */

  /*
   This Panel shows a list of Teams to send an alert or message to.
   It is employed by the MessageTeams and ScheduleAlert Namespaces and requires the global event bus.
   */
  var ChooseTeamsPanelNamespace = (function () {
    var ChooseTeamsPanel = Backbone.View.extend({
      el: "#choose-teams-panel", //buoy_dom_hooks.choose_teams_panel,
      initialize: function () {
        this.listenTo(sharedEventBus, sharedEventBus.events.showChooseTeamsPanelEvent, this.showPanel);
      },
      showPanel: function () {
        $(this.el).removeClass("hidden");
      }
    });

    function initialize() {
      this.chooseTeamsPanel = new ChooseTeamsPanel();
    }

    return {
      initialize: initialize
    };
  }());

  /*
   This Modal Dialog is shown after the submission of an alert
   It is employed by several of the modals and requires the global event bus.
   */
  var SubmittingAlertModalNamespace = (function () {
    var SubmittingAlertModal = Backbone.View.extend({
      el: "#submitting-alert-modal", // buoy_dom_hooks.activate_button_submit,
      initialize: function () {
        this.listenTo(sharedEventBus, sharedEventBus.events.showSubmittingAlertModalEvent, this.showModal);
        this.listenTo(sharedEventBus, sharedEventBus.events.hideSubmittingModalEvent, this.hideModal);
      },
      showModal: function () {
        $(this.el).modal({"show": true, "backdrop": "static"});
      },
      hideModal: function () {
        $(this.el).modal("hide");
      }
    });

    function initialize() {
      this.submittingAlertModal = new SubmittingAlertModal();
    }

    return {
      initialize: initialize
    };
  }());

  /*
   The Message Team Namespace contains elements which
   allow users to send message to their team members
   **
   **  * Backbone Views
   **  ** The Main Button that shows the MessageTeam Modal
   **  ** The MessageTeam Modal itself
   **
   **  Events for communicating to internal and external Views
   **    * showMessageModalEvent
   **    * showSubmittingAlertModalEvent
   **
   **  External Functions
   **    * activateAlert
   */
  var MessageTeamNamespace = (function () {
    var messageEventBus = _.extend({}, Backbone.Events);
    messageEventBus.showMessageModalEvent = "showMessageModalEvent";

    /* Button used to Show the Message Modal*/
    var MainTeamMessageButton = Backbone.View.extend({
      el: "#custom-message-alert-btn",//buoy_dom_hooks.custom_alert_button,
      events: {
        "click": function () {
          messageEventBus.trigger(messageEventBus.showMessageModalEvent);
        }
      }
    });

    /* Entire Message Modal */
    var TeamMessageModal = Backbone.View.extend({
      el: "#emergency-message-modal", //buoy_dom_hooks.emergency_message_modal,
      events: {
        "click": function (e) {
          if (e.target === this.$submit_button.get(0)) {
            sharedEventBus.trigger(sharedEventBus.events.showSubmittingAlertModalEvent);
            activateAlert();
          }
        }
      },
      initialize: function () {
        this.$modal_body = $(this.el).find(".modal-body");
        this.$submit_button = $(this.el).find("button.btn-success");
        this.$message_text = $(this.el).find("#crisis-message");
        this.listenTo(messageEventBus, messageEventBus.showMessageModalEvent, this.showModal);
      },
      showModal: function () {
        $(this.el).modal("show");
        sharedEventBus.trigger(sharedEventBus.events.showChooseTeamsPanelEvent, this.showPanel);
        this.$modal_body.append($("#choose-teams-panel").detach());
        this.$message_text.focus();
      }
    });

    function initialize() {
      this.mainTeamMessageButton = new MainTeamMessageButton();
      this.teamMessageModal = new TeamMessageModal();
    }

    return {
      initialize: initialize
    };
  }());

  /*
   The Schedule Alert Namespace contains elements which
   allow users to issue an alert in the future
   **
   **  * Backbone Views
   **  * The Main Button shows the ScheduleAlert Modal
   **  * The ScheduleAlert Modal itself
   **
   **  Events for communicating to internal and external Views
   **
   **  Internal Functions
   *   * scheduleAlert
   **
   */
  var ScheduleAlertNamespace = (function () {
    var scheduleEventBus = _.extend({}, Backbone.Events);
    scheduleEventBus.showScheduleModalEvent = "showScheduleModalEvent";

    /* Button used to Show the Schedule Alert Modal */
    var MainScheduleAlertButton = Backbone.View.extend({
      el: "#schedule-future-alert-btn", //buoy_dom_hooks.timed_alert_button,
      events: {
        "click": function () {
          scheduleEventBus.trigger(scheduleEventBus.showScheduleModalEvent);
        }
      }
    });

    /* Entire Schedule Alert Modal */
    var ScheduleAlertModal = Backbone.View.extend({
      el: "#scheduled-alert-modal",//buoy_dom_hooks.scheduled_alert_modal,
      initialize: function () {
        this.$modal_body = $(this.el).find(".modal-body");
        this.$message_text = $(this.el).find("#scheduled-crisis-message");
        this.$date_time_picker = $(this.el).find("#scheduled-datetime-tz");
        this.$submit_button = $(this.el).find("button.btn-success");

        this.listenTo(scheduleEventBus, scheduleEventBus.showScheduleModalEvent, this.showModal);

        if (this.$date_time_picker.length) {
          this.$date_time_picker.datetimepicker({
            "lazyInit": true,
            "lang": buoy_vars.ietf_language_tag,
            "minDate": 0, // today is the earliest allowable date
            "mask": true,
            "validateOnBlur": false
          });
        }
      },
      events: {
        "click": function (e) {
          if (e.target === this.$submit_button.get(0)) {
            $(this.el).modal("show");                    // a hack. modal should stay on screen, but this isn"t the best solution
            this.$submit_button.prop("disabled", true);
            this.$submit_button.html(buoy_vars.i18n_scheduling_alert);
            this.scheduleAlert();
            sharedEventBus.trigger(sharedEventBus.events.showSubmittingAlertModalEvent);
          }
        }
      },
      showModal: function () {
        sharedEventBus.trigger(sharedEventBus.events.showChooseTeamsPanelEvent, this.showPanel);
        this.$modal_body.append($("#choose-teams-panel").detach());
        $(this.el).modal("show");
      },

      /* Submits a new alert to the server */
      scheduleAlert: function () {
        var self = this;

        function hideModal() {
          self.$submit_button.prop("disabled", false);
          self.$submit_button.html(buoy_vars.i18n_schedule_alert);
          sharedEventBus.trigger(sharedEventBus.events.hideSubmittingModalEvent);
        }

        var data = {
          "action": $('#activate-alert-form input[name="action"]').val(),
          "msg": $("#scheduled-crisis-message").val(),
          "scheduled-datetime-utc": new Date($("#scheduled-datetime-tz").val()).toUTCString(),
          "buoy_teams": $("#choose-teams-panel :checked").map(function () {
            return this.value;
          }).get(),
          "buoy_nonce": $("#buoy_nonce").val()
        };
        $.post(ajaxurl, data,         // Backbone aliases jQuery.post as "save" which is misleading here
          function (response) {
            if (response.success) {
              $(self.el).find(".has-error").removeClass("has-error");
              $(self.el).find("[aria-invalid]").removeAttr("aria-invalid");
              $(self.el).find('div.alert[role="alert"]').remove();
              $("#scheduled-alert-modal .modal-body > :first-child")
                .before('<div class="alert alert-success" role="alert"><p>' + response.data.message + "</p></div>");
              $("#scheduled-alert-modal input, #scheduled-alert-modal textarea").val("");
            } else {
              for (var k in response.data) {
                $("#" + response.data[k].code).parent().addClass("has-error");
                $("#" + response.data[k].code).attr("aria-invalid", true);
                $('<div class="alert alert-danger" role="alert"><p>' + response.data[k].message + "</p></div>")
                  .insertBefore("#" + response.data[k].code);
              }
            }
            hideModal()
          },
          "json"
        );
      }
    });

    function initialize() {
      this.mainScheduleAlertButton = new MainScheduleAlertButton();
      this.scheduleAlertModal = new ScheduleAlertModal();
    }

    return {
      initialize: initialize
    };
  }());

  /**
   The Activate Alert Namespace contains elements that allow users
   to create an Alert immediately for a response team

   This is a somewhat confusing Namespace because its functionality overlaps
   with what is already provided by the backend PHP.

   That is, the "Activate Alert" functionality is implemented on the Server Side
   as a Form Submission, so that it can be implemented without Javascript

   That said, the following module encompasses the following a single Backbone View
   Which contains an essential UI element:

   * Activate Alert Form
   This represents the entirety of the central page content,
   including the other two buttons on the page when they are visible.

   * Within this view is the "submit_button"
   *  which submits the alert to the server
   */
  var ActivateAlertNamespace = (function () {
    /* Form contains *all* central page content, including the other main UI buttons */
    var ActivateAlertForm = Backbone.View.extend({
      el: "#activate-alert-form", //buoy_dom_hooks.activate_alert_form,
      initialize: function () {
        this.submit_button = $(this.el).find("#activate-btn-submit");
      },
      events: {
        "submit": function (e) {
          e.preventDefault();
          this.submit_button.prop("disabled", true);
          sharedEventBus.trigger(sharedEventBus.events.showSubmittingAlertModalEvent);
          activateAlert();
        }
      }
    });

    function initialize() {
      this.activateAlertForm = new ActivateAlertForm();
    }

    return {
      initialize: initialize
    }
  }());

  /*
   The MenuBar Scheduled Alert Namespace contains elements which
   control the list of alerts on the top menu bar.
   *
   *  Currently, we are punting on this and just including the previous jQuery code
   */
  var MenubarScheduledAlertsNamespace = (function () {
    $("#wp-admin-bar-buoy_my_scheduled_alerts a").each(function () {
      $(this).on("click", unscheduleAlert);
    });
    function unscheduleAlert(e) {
      e.preventDefault();
      var a_el = $(this);
      $.post(a_el.attr("href"), {"action": "buoy_unschedule_alert"},
        function (response) {
          if (response.success) {
            a_el.remove();
            if (0 === BUOY.countIncidentMenuItems()) {
              $("#wp-admin-bar-buoy-alert-menu").remove();
            }
          }
        },
        "json"
      );
    }
  }());

  $(document).ready(function () {
    ActivateAlertNamespace.initialize();
    ChooseTeamsPanelNamespace.initialize();
    SubmittingAlertModalNamespace.initialize();
    MessageTeamNamespace.initialize();
    ScheduleAlertNamespace.initialize();
  });
}(jQuery, _, Backbone));