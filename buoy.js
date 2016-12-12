/**
 * Buoy initializer.
 *
 * @license GPL-3.0
 */

// Determine where "in Buoy" we are, register appropriate handlers.
jQuery(document).ready(function () {
    BUOY.init();
});

// Respond to the "Install iOS" event (triggered by a simulated install button).
jQuery(document).on('install.ios', function () {
    BUOY.installWebApp();
});

/**
 * The main Buoy "module" initializes the page behavior.
 */
var BUOY = (function () {

    /**
     * Initializer.
     */
    var init = function () {
        // Note: This works around GitHub issue #47.
        // Could be removed after WebKit and/or Bootstrap fixes this in their libs.
        if (jQuery('.dashboard_page_buoy_chat, .dashboard_page_buoy_activate_alert').length) {
            jQuery('body').append(jQuery('.modal').detach());
        }

        if (jQuery('#buoy-map-container').length) {
            BUOY_MAP.attachHandlers();
        }

    };

    /**
     * Presents a simulated "install" interface to mobile browsers.
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
        return jQuery('#wp-admin-bar-buoy-alert-menu a').length;
    };

    return {
        'init': init,
        'installWebApp': installWebApp,
        'countIncidentMenuItems': countIncidentMenuItems
    };

})();

// Upload media for incident
// TODO: Refactor this stuff.
jQuery(document).ready(function () {
    jQuery('#upload-media-btn').on('click', function (e) {
        e.preventDefault();
        jQuery(this).next().click();
    });
    jQuery('#upload-media-btn').next().on('change', function (e) {
        var upload_url = ajaxurl + '?action=buoy_upload_media';
        upload_url    += '&buoy_nonce=' + buoy_vars.incident_nonce;
        upload_url    += '&buoy_hash=' + jQuery('#buoy-map-container').data('incident-hash');
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
                    var li = jQuery('#incident-media-group ul.dropdown-menu li.' + response.data.media_type);
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
    jQuery('#buoy-videochat-btn').on('click', function (e) {
        jQuery(this).removeClass('btn-default');
        if (jQuery('#jitsiConference0').is(':visible')) {
            jQuery(this).removeClass('btn-danger');
            jQuery(this).addClass('btn-success');
            jQuery('#jitsiConference0').hide();
            jQuery('#alert-chat-room-container').children().first().show();
        } else {
            jQuery(this).removeClass('btn-success');
            jQuery(this).addClass('btn-danger');
            jQuery('#alert-chat-room-container').children().first().hide();
            if (jQuery('#jitsiConference0').length) {
                jQuery('#jitsiConference0').show();
            } else {
                var t = document.querySelector('#buoy-jitsi-template');
                buoy_vars.jitsi_fragment = document.importNode(t.content, true);
                jQuery('#alert-chat-room-container').append(buoy_vars.jitsi_fragment);
            }
        }
    });
});

// Show "safety information" on page load,
// TODO: this should automatically be dismissed when another user
// enters the chat room.
jQuery(document).ready(function () {
    if (jQuery('#safety-information-modal.auto-show-modal').length) {
        jQuery(window).load(function () {
            jQuery('#safety-information-modal').modal('show');
        });
    }
});

// Respond to incident.
jQuery(document).ready(function () {
    jQuery('#incident-response-form').one('submit', function (e) {
        e.preventDefault();
        jQuery(e.target).find('input[type="submit"]').prop('disabled', true);
        jQuery(e.target).find('input[type="submit"]').val(buoy_vars.i18n_responding_to_alert);
        navigator.geolocation.getCurrentPosition(
            function (position) {
                jQuery('#incident-response-form input[name$="location"]')
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
