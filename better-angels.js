(function () {
var BETTER_ANGELS = {};
BETTER_ANGELS.postAlert = function () {
    if (!navigator.geolocation){
        if (console && console.error) {
            console.error('Geolocation is not supported by your browser');
        }
        return;
    }
    navigator.geolocation.getCurrentPosition(
        function (position) { // success callback
            var data = {
                'action': 'better-angels_findme',
                'pos': position.coords,
            };
            if (jQuery('#crisis-message').val()) {
                data.msg = jQuery('#crisis-message').val();
            }
            jQuery.post(ajaxurl, data,
                function (response) {
                    if (response.success) {
                        // decode the HTML-encoded stuff WP sends
                        window.location.href = jQuery('<div/>').html(response.data).text();
                    }
                }
            );
        },
        function () { // error callback
            if (console && console.error) {
                console.error("Unable to retrieve location."); 
            }
        }
    );
};
/**
 * Creates a google map centered on the given coordinates.
 *
 * @param object coords An object of geolocated data with properties named "lat" and "lng".
 */
BETTER_ANGELS.initMap = function (coords) {
    if ('undefined' === typeof google) { return; }
    var map = new google.maps.Map(document.getElementById('map'), {
        'zoom': 16,
        'center': coords
    });
    var infowindow_content = '<p>'
            + '<img src="' + jQuery('#map-container').data('icon')
            + '" alt="' + jQuery('#map-container').data('alerter')
            + '" style="float:left;margin-right:5px;" />'
            + jQuery('#map-container').data('info-window-text')
            + '</p>';
    var infowindow = new google.maps.InfoWindow({
        'content': infowindow_content
    });
    var marker = new google.maps.Marker({
        'position': coords,
        'map': map,
        'title': better_angels_vars.i18n_map_title
    });
    marker.addListener('click', function () {
        infowindow.open(map, marker);
    });
    map.setCenter(coords);
    marker.setPosition(coords);
};
BETTER_ANGELS.getQueryVariable = function (variable) {
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i=0;i<vars.length;i++) {
        var pair = vars[i].split("=");
        if(pair[0] == variable){return pair[1];}
    }
    return(false);
};
BETTER_ANGELS.init = function () {
    jQuery(document).ready(function () {
        jQuery('#activate-btn-submit').one('click', BETTER_ANGELS.postAlert);
        jQuery('#activate-msg-btn-submit').on('click', function () {
            jQuery('#emergency-message-modal').modal('show');
        });
        jQuery('#emergency-message-modal').on('shown.bs.modal', function () {
              jQuery('#crisis-message').focus();
        })
        jQuery('#emergency-message-modal').one('hidden.bs.modal', BETTER_ANGELS.postAlert);

        // Show/hide incident map
        jQuery('#toggle-incident-map-btn').on('click', function () {
            var map_container = jQuery('#map-container');
            if (map_container.is(':visible')) {
                map_container.slideUp();
                this.textContent = better_angels_vars.i18n_show_map;
            } else {
                map_container.slideDown({
                    'complete': function () { google.maps.event.trigger(map, 'resize'); }
                });
                this.textContent = better_angels_vars.i18n_hide_map;
            }
        });

        // Show "safety information" on page load,
        // TODO: this should automatically be dismissed when another user
        // enters the chat room.
        if (BETTER_ANGELS.getQueryVariable('show_safety_modal')) {
            jQuery(window).load(function () {
                jQuery('#safety-information-modal').modal('show');
            });
        }

    });

    jQuery(window).on('load', function () {
        if (jQuery('.dashboard_page_better-angels_review-alert #map').length) {
            BETTER_ANGELS.initMap({
                'lat': parseFloat(jQuery('#map-container').data('latitude')),
                'lng': parseFloat(jQuery('#map-container').data('longitude'))
            });
        } else if (jQuery('.dashboard_page_better-angels_incident-chat #map').length) {
            navigator.geolocation.getCurrentPosition(
                function (position) {
                    var coords = {
                        'lat': parseFloat(position.coords.latitude),
                        'lng': parseFloat(position.coords.longitude)
                    };
                    BETTER_ANGELS.initMap(coords);
                }
            );
        }
    });

};
BETTER_ANGELS.init();
})();
