/**
 * Buoy main "app" file.
 *
 * This file contains the Buoy front-end JavaScript "root" code; all
 * other Buoy JavaScripts are dependent on this file, so this file
 * must be present and loaded before any other dependent files.
 *
 * In this file, we create a global `BUOY` object using the (classic)
 * {@link http://www.adequatelygood.com/JavaScript-Module-Pattern-In-Depth.html|JavaScript Module Pattern}
 * and return a simple interface to interact with {@link http://BackboneJS.org|Backbone}
 * library components (`Models`, `Views`, and `Collections`), alon with
 * some helper functions.
 *
 * @license GPL-3.0
 */

/**
 * The main Buoy "module" is our single global variable for all Buoy
 * front-end behavior and values. It contains all our front-end code.
 */
var BUOY = (function () {

    /**
     * Buoy's data model (using `Backbone.Model`).
     *
     * @var {object}
     */
    Models = {};

    /**
     * Buoy's view code (using `Backbone.View`).
     *
     * @var {object}
     */
    Views = {};

    /**
     * Buoy's model collections (using `Backbone.Collection`).
     *
     * @var {object}
     */
    Collections = {};

    /**
     * Initializer.
     */
    var init = function () {
        // Note: This works around GitHub issue #47.
        // Could be removed after WebKit and/or Bootstrap fixes this in their libs.
        if (jQuery(buoy_dom_hooks.page_chat + ' ' + buoy_dom_hooks.page_activate_alert).length) {
            jQuery('body').append(jQuery('.modal').detach());
        }

        if (jQuery(buoy_dom_hooks.map_container).length) {
            BUOY_MAP.attachHandlers();
        }

    };

    /**
     * Presents a simulated "install" interface to mobile browsers.
     *
     * @todo Extend this to work on non-iOS devices; Firefox, Chrom, etc?
     */
    var installWebApp = function () {
        jQuery('body').append('<button id="install-webapp-btn"></button>');
        jQuery('#install-webapp-btn').attr({
                'data-toggle' : 'popover',
                'data-trigger': 'focus',
                'data-html': true,
                'data-content': buoy_vars.i18n_install_btn_content,
                'title': buoy_vars.i18n_install_btn_title
                         + '<button id="dismiss-installer-btn" class="btn btn-sm btn-primary">'
                         + buoy_vars.i18n_dismiss + ' &times;</button>'
            })
            .popover({
                'placement': 'top'
            })
            .popover('show');
        jQuery('#dismiss-installer-btn').on('click', function () {
            jQuery('#install-webapp-btn').popover('hide');
            jQuery.post(ajaxurl, {
                'action': 'buoy_dismiss_installer',
                'buoy_nonce': buoy_vars.incident_nonce,
            });
        });
    };

    /**
     * Helper to get the number of Buoy Alerts shown in the admin bar.
     *
     * @return {number}
     */
    var countIncidentMenuItems = function () {
        return jQuery(buoy_dom_hooks.menu_id).length;
    };

    return {
        'init': init,
        'installWebApp': installWebApp,
        'countIncidentMenuItems': countIncidentMenuItems,
        'Models': Models,
        'Views': Views,
        'Collections': Collections
    };

})();

jQuery(document).ready(function () {
    BUOY.init();
});

// Respond to the "Install iOS" event (triggered by a simulated install button).
jQuery(document).on('install.ios', function () {
    BUOY.installWebApp();
});

// TODO: EVERYTHING BELOW THIS LINE NEEDS TO BE REFACTORED INTO A `Backbone.View`.

// Upload media for incident
// TODO: Refactor this stuff; should end up in a Backbone.View somewhere up in our Buoy module.
jQuery(document).ready(function () {
    jQuery(buoy_dom_hooks.upload_media_button).on('click', function (e) {
        e.preventDefault();
        jQuery(this).next().click();
    });
    jQuery(buoy_dom_hooks.upload_media_button).next().on('change', function (e) {
        var upload_url = ajaxurl + '?action=buoy_upload_media';
        upload_url    += '&buoy_nonce=' + buoy_vars.incident_nonce;
        upload_url    += '&buoy_hash=' + jQuery(buoy_dom_hooks.map_container).data('incident-hash');
        var file_list = this.files;
        for (var i = 0; i < file_list.length; i++) {
            var fd = new FormData();
            fd.append(file_list[i].name, file_list[i]);
            jQuery.ajax({
                'type': "POST",
                'url': upload_url,
                'data': fd,
                'processData': false,
                'contentType': false,
                'success': function (response) {
                    var li = jQuery(buoy_dom_hooks.incident_media_group_item + '.' + response.data.media_type);
                    li.find('ul').append(
                        jQuery('<li id="incident-media-' + response.data.id + '" />').append(response.data.html)
                        );
                    li.find('.badge').html(parseInt(li.find('.badge').html()) + 1);
                }
            });
        }
    });
});

// Prepare videochat button with Jitsi Meet API.
// TODO: Make more use of the Jitsi Meet External API. For example,
//       instead of hiding the video conference, use api.dispose().
jQuery(document).ready(function () {
    jQuery(document.body).append('<script src="https://meet.jit.si/external_api.js"></script>');
    jQuery(buoy_dom_hooks.vidchat_button).on('click', function (e) {
        jQuery(this).removeClass('btn-default');
        if (jQuery('#jitsiConference0').is(':visible')) {
            jQuery(this).removeClass('btn-danger');
            jQuery(this).addClass('btn-success');
            jQuery('#jitsiConference0').hide();
            jQuery(buoy_dom_hooks.chat_room_container).children().first().show();
        } else {
            jQuery(this).removeClass('btn-success');
            jQuery(this).addClass('btn-danger');
            jQuery(buoy_dom_hooks.chat_room_container).children().first().hide();
            if (jQuery('#jitsiConference0').length) {
                jQuery('#jitsiConference0').show();
            } else {
                var t = document.querySelector('#buoy-jitsi-template');
                buoy_vars.jitsi_fragment = document.importNode(t.content, true);
                jQuery(buoy_dom_hooks.chat_room_container).append(buoy_vars.jitsi_fragment);
            }
        }
    });
});

// Show "safety information" on page load,
// TODO: this should automatically be dismissed when another user
// enters the chat room.
jQuery(document).ready(function () {
    if (jQuery(buoy_dom_hooks.safety_info_modal + '.auto-show-modal').length) {
        jQuery(window).load(function () {
            jQuery(buoy_dom_hooks.safety_info_modal).modal('show');
        });
    }
});

// Respond to incident.
jQuery(document).ready(function () {
    jQuery(buoy_dom_hooks.incident_response_form).one('submit', function (e) {
        e.preventDefault();
        jQuery(e.target).find('input[type="submit"]').prop('disabled', true);
        jQuery(e.target).find('input[type="submit"]').val(buoy_vars.i18n_responding_to_alert);
        navigator.geolocation.getCurrentPosition(
            function (position) {
                jQuery(buoy_dom_hooks.incident_response_form + ' input[name$="location"]')
                    .val(position.coords.latitude + ',' + position.coords.longitude);
                jQuery(e.target).submit();
            },
            function () {
                jQuery(e.target).submit();
            },
            {
                'timeout': 5000
            }
        );
    });
});

// TODO: Where should this actually go?
jQuery(document).ready(function () {
    jQuery('#commentform').removeAttr('novalidate');
});
