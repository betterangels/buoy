/**
 * Buoy Map.
 *
 * @license GPL-3.0
 */

/**
 * Buoy Map module.
 */
var BUOY_MAP = (function () {

    /**
     * Leaflet Map object itself
     *
     * @type {Map}
     */
    var map;

    /**
     * Bounding box for all markers.
     *
     * @type {LatLngBounds}
     */
    var marker_bounds;

    /**
     * Markers (pins) attached to the map.
     *
     * @type {object}
     */
    var map_markers = {};

    /**
     * Whether the user has manually interacted with the map.
     *
     * @type {boolean}
     */
    var map_touched = false;

    /**
     * Custom Leaflet map icon.
     *
     * @see {@link http://leafletjs.com/examples/custom-icons.html}
     */
    var GravatarIcon = L.Icon.extend({
        'options': {
            'iconSize': [32, 32]
        }
    });

    /**
     * Get a Buoy "gravatar map icon."
     *
     * @param {object} options A {@link http://leafletjs.com/reference.html#icon-options Leaflet icon options object}
     *
     * @return {Leaflet.Icon}
     */
    var gravatarIcon = function (options) {
        return new GravatarIcon(options);
    };

    /**
     * Makes a URL for linking to directions.
     *
     * @todo Currently uses Google Maps, worth changing?
     *
     * @param {Array} latlng
     *
     * @return {string}
     */
    var getDirectionsUrl = function (latlng) {
        return 'https://maps.google.com/?saddr=Current+Location&daddr=' + encodeURIComponent(latlng.join(','));
    };

    /**
     * @param success
     */
    var getMyPosition = function (success) {
        if (!navigator.geolocation){
            if (console && console.error) {
                console.error('Geolocation is not supported by your browser');
            }
            return;
        }
        navigator.geolocation.getCurrentPosition(success, logGeoError, {'timeout': 5000});
    };

    /**
     * @param {Position} position
     */
    var updateMyLocation = function (position) {
        var data = {
            'action': 'buoy_update_location',
            'pos': position.coords,
            'incident_hash': jQuery('#buoy-map-container').data('incident-hash'),
            'buoy_nonce': buoy_vars.incident_nonce
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
            if (!responder.geo) { continue; } // no geo for this responder
            var new_pos = [
                parseFloat(responder.geo.latitude),
                parseFloat(responder.geo.longitude)
            ];
            if (map_markers[responder.id]) {
                map_markers[responder.id].setLatLng(new_pos);
            } else {
                var marker = L.marker(new_pos, {
                    'title': responder.display_name,
                    'icon': gravatarIcon({
                        'iconUrl': responder.avatar_url
                    })
                }).addTo(map);
                map_markers[responder.id] = marker;
                var iw_data = {
                    'directions': getDirectionsUrl([responder.geo.latitude, responder.geo.longitude])
                };
                if (responder.call) { iw_data.call = 'tel:' + responder.call; }
                var infowindow = L.popup().setContent(
                    '<p>' + responder.display_name + '</p>'
                    + infoWindowContent(iw_data)
                );
                marker.bindPopup(infowindow);
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

    /**
     * Gets the HTML for an info window pop up on the map.
     *
     * @param data
     *
     * @return {string}
     */
    var infoWindowContent = function (data) {
        var html = '<ul>';
        for (var key in data) {
            if (data[key]) {
                html += '<li>' + jQuery('<span>').append(
                            jQuery('<a>')
                            .attr('href', data[key])
                            .attr('target', '_blank')
                            .html(buoy_vars['i18n_' + key])
                        ).html() + '</li>';
            }
        }
        html += '</ul>';
        return html;
    };

    /**
     * Creates a Leaflet Map centered on the given coordinates.
     *
     * @param {Position} position
     * @param {boolean} mark_coords Whether to create a marker and infowindow for the `position` argument location.
     */
    var initMap = function (position, mark_coords) {
        if ('undefined' === typeof L) { return; }

        var latlng;
        if (position && position.coords) {
            latlng = {'lat': position.coords.latitude, 'lng': position.coords.longitude};
        } else {
            latlng = {'lat': 0, 'lng': 0};
        }
        map = new L.Map(jQuery(buoy_dom_hooks.incident_map))
            .setView(latlng, 10);
        map.attributionControl.setPrefix('');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            'maxZoom': 19
        }).addTo(map);
        marker_bounds = L.latLngBounds(latlng);

        if (mark_coords) {
            var infowindow = L.popup().setContent(
                '<p>' + buoy_vars.i18n_crisis_location + '</p>'
                + infoWindowContent({
                    'directions': getDirectionsUrl([latlng.lat, latlng.lng])
                })
            );
            var marker = L.marker([latlng.lat, latlng.lng], {
                'title': buoy_vars.i18n_crisis_location
            }).addTo(map).bindPopup(infowindow);
            marker_bounds.extend([latlng.lat, latlng.lng]);
            map_markers.incident = marker;
        }

        if (jQuery(buoy_dom_hooks.map_container).data('responder-info')) {
            jQuery.each(jQuery(buoy_dom_hooks.map_container).data('responder-info'), function (i, v) {
                if (v.geo) {
                    var responder_geo = [
                        parseFloat(v.geo.latitude),
                        parseFloat(v.geo.longitude)
                    ];
                    var infowindow = L.popup().setContent(
                        '<p>' + v.display_name + '</p>'
                        + infoWindowContent({
                            'directions': 'https://maps.google.com/?saddr=Current+Location&daddr=' + encodeURIComponent(v.geo.latitude) + ',' + encodeURIComponent(v.geo.longitude),
                            'call': (v.call) ? 'tel:' + v.call : ''
                        })
                    );
                    var marker = L.marker(responder_geo, {
                        'title': v.display_name,
                        'icon': gravatarIcon({
                            'iconUrl': v.avatar_url
                        })
                    }).addTo(map).bindPopup(infowindow);
                    map_markers[v.id] = marker;
                    marker_bounds.extend(responder_geo);
                }
            });
        }

        map.fitBounds(marker_bounds);
        map.setView(latlng, map.getZoom(), {'animation': true});

        map.on('click', touchMap);
        map.on('drag', touchMap);
    };

    /**
     * Records that the `map` has been manually interacted with.
     */
    var touchMap = function () {
        map_touched = true;
    };

    var addMarkerForCurrentLocation = function () {
        getMyPosition(function (position) {
            var my_geo = [position.coords.latitude, position.coords.longitude];
            var my_marker = L.marker(my_geo, {
                'title': buoy_vars.i18n_my_location,
                'icon': gravatarIcon({
                    'iconUrl': jQuery(buoy_dom_hooks.map_container).data('my-avatar-url')
                })
            }).addTo(map);
            marker_bounds.extend(my_geo);
            map.fitBounds(marker_bounds);
        });
    };

    /**
     * Attaches user interface handlers.
     */
    var attachHandlers = function () {
        jQuery(buoy_dom_hooks.toggle_map_button).on('click', toggleMap);
        jQuery(buoy_dom_hooks.fit_map_button).on('click', fitToMarkers);
        jQuery(buoy_dom_hooks.my_location_button).on('click', panToUserLocation);

        // TODO: This should probably be moved to somewhere else...?
        if (jQuery(buoy_dom_hooks.page_review_alert + ' ' + buoy_dom_hooks.incident_map).length) {
            addMarkerForCurrentLocation();
        }

        // Start tracking current location.
        // TODO: This should probably also be moved to somewhere else...?
        var emergency_location = {
            'lat': parseFloat(jQuery(buoy_dom_hooks.map_container).data('incident-latitude')),
            'lng': parseFloat(jQuery(buoy_dom_hooks.map_container).data('incident-longitude'))
        };
        if (isNaN(emergency_location.lat) || isNaN(emergency_location.lng)) {
            navigator.geolocation.getCurrentPosition(initMap, initMap, {'timeout': 5000});
        } else {
            var loc = {
                'coords': {
                    'latitude': emergency_location.lat,
                    'longitude': emergency_location.lng
                }
            };
            initMap(loc, true);
        }

        if (jQuery(buoy_dom_hooks.page_chat).length) {
            // TODO: Clear the watcher when failing to get position?
            //       Then what? Keep trying? Show a dialog asking the user to
            //       turn on location services?
            geowatcher_id = navigator.geolocation.watchPosition(updateMyLocation, logGeoError, {
                'timeout': 5000
            });
        }
    };

    /**
     * Toggles map visibility.
     */
    var toggleMap = function () {
        var map_container = jQuery(buoy_dom_hooks.map_container);
        if (map_container.is(':visible')) {
            map_container.slideUp();
        } else {
            map_container.slideDown({
                'complete': function () {
                    if (map) {
                        map.invalidateSize(true);
                    }
                    if (marker_bounds.lat && marker_bounds.lng) {
                        map.fitBounds(marker_bounds);
                    }
                }
            });
        }
    };

    /**
     * Fits the map view to the markers on the map.
     */
    var fitToMarkers = function (e) {
        e.preventDefault();
        if (jQuery(buoy_dom_hooks.map_container).is(':hidden')) {
            jQuery(buoy_dom_hooks.toggle_map_button).click();
        }
        map.fitBounds(marker_bounds);
    };

    /**
     * Pans the map to a given location.
     */
    var panToUserLocation = function (e) {
        e.preventDefault();
        if (jQuery(buoy_dom_hooks.map_container).is(':hidden')) {
            jQuery(buoy_dom_hooks.toggle_map_button).click();
        }
        // Pan map view.
        map.setView(
            map_markers[jQuery(this).data('user-id')].getLatLng(),
            map.getZoom(),
            {
                'animation': true
            }
        );
        touchMap();
    };

    return {
        'attachHandlers': attachHandlers
    };

})();
