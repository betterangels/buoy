var BUOY = (function () {
    this.emergency_location;
    this.google_map;

    var getMyPosition = function (success) {
        if (!navigator.geolocation){
            if (console && console.error) {
                console.error('Geolocation is not supported by your browser');
            }
            return;
        }
        navigator.geolocation.getCurrentPosition(
            success,
            function () {
                if (console && console.error) {
                    console.error("Unable to retrieve location."); 
                }
            }
        );
    };

    var activateAlert = function () {
        getMyPosition(postAlert);
    };

    var postAlert = function (position) {
        var data = {
            'action': 'better-angels_findme',
            'pos': position.coords
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
    }

    var respondToIncident = function () {
    };

    /**
     * Creates a google map centered on the given coordinates.
     *
     * @param object coords An object of geolocated data with properties named "lat" and "lng".
     */
    var initMap = function (coords) {
        if ('undefined' === typeof google) { return; }
        google_map = map = new google.maps.Map(document.getElementById('map'), {
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
        map.setCenter(coords);
        marker.setPosition(coords);

        marker.addListener('click', function () {
            infowindow.open(map, marker);
        });

        jQuery.each(jQuery('#map-container').data('responder-info'), function (i, v) {
            var marker = new google.maps.Marker({
                'position': {
                    'lat': parseFloat(v.geo.latitude),
                    'lng': parseFloat(v.geo.longitude)
                },
                'map': map,
                'icon': v.avatar_url
            });
            var infowindow = new google.maps.InfoWindow({
                'content': v.display_name
            });
            marker.addListener('click', function () {
                infowindow.open(map, marker);
            });
        });
    };

    var init = function () {
        jQuery(document).ready(function () {
            // Panic buttons (activate alert).
            jQuery('#activate-btn-submit').one('click', activateAlert);
            jQuery('#activate-msg-btn-submit').on('click', function () {
                jQuery('#emergency-message-modal').modal('show');
            });
            jQuery('#emergency-message-modal').on('shown.bs.modal', function () {
                  jQuery('#crisis-message').focus();
            })
            jQuery('#emergency-message-modal').one('hidden.bs.modal', activateAlert);

            // Show/hide incident map
            jQuery('#toggle-incident-map-btn').on('click', function () {
                var map_container = jQuery('#map-container');
                if (map_container.is(':visible')) {
                    map_container.slideUp();
                    this.textContent = better_angels_vars.i18n_show_map;
                } else {
                    map_container.slideDown({
                        'complete': function () {
                            google.maps.event.trigger(map, 'resize');
                            google_map.panTo(emergency_location);
                        }
                    });
                    this.textContent = better_angels_vars.i18n_hide_map;
                }
            });

            // Show "safety information" on page load,
            // TODO: this should automatically be dismissed when another user
            // enters the chat room.
            if (jQuery('#safety-information-modal.auto-show-modal').length) {
                jQuery(window).load(function () {
                    jQuery('#safety-information-modal').modal('show');
                });
            }

            // Respond to incident.
            jQuery('#incident-response-form').one('submit', function (e) {
                e.preventDefault();
                getMyPosition(function (position) {
                    jQuery('#incident-response-form input[name$="location"]').val(
                        position.coords.latitude + ',' + position.coords.longitude
                    );
                    jQuery(e.target).submit();
                });
            });

        });

        jQuery(window).on('load', function () {
            if (jQuery('.dashboard_page_better-angels_review-alert #map, .dashboard_page_better-angels_incident-chat #map').length) {
                this.emergency_location = {
                    'lat': parseFloat(jQuery('#map-container').data('latitude')),
                    'lng': parseFloat(jQuery('#map-container').data('longitude'))
                };
                initMap(this.emergency_location);
            }
        });

    };

    return {
        'init': init
    };
})();

jQuery(document).ready(function () {
    BUOY.init();
});
