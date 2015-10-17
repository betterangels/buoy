var BUOY = (function () {
    this.incident_hash;
    this.emergency_location;
    this.map;           //< Google Map object itself
    this.marker_bounds; //< Google Map marker bounding box
    this.map_markers = {};
    this.map_touched = false; //< Whether the user has manually interacted with the map.
    this.geowatcher_id; //< ID of Geolocation.watchPosition() timer

    var getMyPosition = function (success) {
        if (!navigator.geolocation){
            if (console && console.error) {
                console.error('Geolocation is not supported by your browser');
            }
            return;
        }
        navigator.geolocation.getCurrentPosition(success, logGeoError);
    };

    var updateMyLocation = function (position) {
        var data = {
            'action': 'better-angels_update-location',
            'pos': position.coords,
            'incident_hash': incident_hash
        };
        jQuery.post(ajaxurl, data,
            function (response) {
                if (response.success) {
                    updateMapMarkers(response.data);
                }
            }
        );
    };

    var updateMapMarkers = function (marker_info) {
        for (var i = 0; i < marker_info.length; i++) {
            var responder = marker_info[i];
            var new_pos = new google.maps.LatLng(
                parseFloat(responder.geo.latitude),
                parseFloat(responder.geo.longitude)
            );
            if (map_markers[responder.id]) {
                map_markers[responder.id].setPosition(new_pos);
            } else {
                var marker = new google.maps.Marker({
                    'position': new_pos,
                    'map': map,
                    'title': responder.display_name,
                    'icon': responder.avatar_url
                });
                map_markers[responder.id] = marker;
            }
            marker_bounds.extend(new_pos);
            if (!map_touched) {
                map.fitBounds(marker_bounds);
            };
        }
    };

    var logGeoError = function () {
        if (console && console.error) {
            console.error("Unable to retrieve location.");
        }
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

    /**
     * Creates a google map centered on the given coordinates.
     *
     * @param object coords An object of geolocated data with properties named "lat" and "lng".
     */
    var initMap = function (coords) {
        if ('undefined' === typeof google) { return; }

        this.map = new google.maps.Map(document.getElementById('map'));
        this.marker_bounds = new google.maps.LatLngBounds();

        var infowindow = new google.maps.InfoWindow({
            'content': '<p>' + better_angels_vars.i18n_crisis_location + '</p>'
        });
        var marker = new google.maps.Marker({
            'position': new google.maps.LatLng(coords.lat, coords.lng),
            'map': map,
            'title': better_angels_vars.i18n_crisis_location
        });
        this.map_markers.incident = marker;
        marker_bounds.extend(new google.maps.LatLng(coords.lat, coords.lng));
        marker.addListener('click', function () {
            infowindow.open(map, marker);
        });

        if (jQuery('#map-container').data('responder-info')) {
            jQuery.each(jQuery('#map-container').data('responder-info'), function (i, v) {
                var responder_geo = new google.maps.LatLng(
                    parseFloat(v.geo.latitude), parseFloat(v.geo.longitude)
                );
                var marker = new google.maps.Marker({
                    'position': responder_geo,
                    'map': map,
                    'title': v.display_name,
                    'icon': v.avatar_url
                });
                map_markers[v.id] = marker;
                marker_bounds.extend(responder_geo);
            });
        }

        map.fitBounds(marker_bounds);

        map.addListener('click', touchMap);
        map.addListener('drag', touchMap);
    };

    var touchMap = function () {
        map_touched = true;
    };

    var addMarkerForCurrentLocation = function () {
        getMyPosition(function (position) {
            var my_geo = new google.maps.LatLng(
                position.coords.latitude, position.coords.longitude
            );
            var my_marker = new google.maps.Marker({
                'position': my_geo,
                'map': map,
                'title': better_angels_vars.i18n_my_location,
                'icon': jQuery('#map-container').data('my-avatar-url')
            });
            marker_bounds.extend(my_geo);
            map.fitBounds(marker_bounds);
        });
    };

    var init = function () {
        incident_hash = jQuery('#map-container').data('incident-hash');
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
                            map.fitBounds(marker_bounds);
                        }
                    });
                    this.textContent = better_angels_vars.i18n_hide_map;
                }
            });

            jQuery('#fit-map-to-markers-btn').on('click', function () {
                map.fitBounds(marker_bounds);
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
                jQuery(e.target).find('input[type="submit"]').prop('disabled', true);
                jQuery(e.target).find('input[type="submit"]').val(better_angels_vars.i18n_responding_to_alert);
                getMyPosition(function (position) {
                    jQuery('#incident-response-form input[name$="location"]').val(
                        position.coords.latitude + ',' + position.coords.longitude
                    );
                    jQuery(e.target).submit();
                });
            });

            if (jQuery('.dashboard_page_better-angels_incident-chat').length) {
                geowatcher_id = navigator.geolocation.watchPosition(updateMyLocation, logGeoError);
            }

        });

        jQuery(window).on('load', function () {
            if (jQuery('.dashboard_page_better-angels_review-alert #map, .dashboard_page_better-angels_incident-chat #map').length) {
                this.emergency_location = {
                    'lat': parseFloat(jQuery('#map-container').data('incident-latitude')),
                    'lng': parseFloat(jQuery('#map-container').data('incident-longitude'))
                };
                initMap(this.emergency_location);
            }
            if (jQuery('.dashboard_page_better-angels_review-alert #map').length) {
                addMarkerForCurrentLocation();
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
