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
        navigator.geolocation.getCurrentPosition(success, logGeoError, {'timeout': 5000});
    };

    var updateMyLocation = function (position) {
        var data = {
            'action': 'buoy_update_location',
            'pos': position.coords,
            'incident_hash': incident_hash,
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
                var iw_data = {
                    'directions': 'https://maps.google.com/?saddr=Current+Location&daddr=' + encodeURIComponent(responder.geo.latitude) + ',' + encodeURIComponent(responder.geo.longitude)
                };
                if (responder.call) { iw_data.call = 'tel:' + responder.call; }
                var infowindow = new google.maps.InfoWindow({
                    'content': '<p>' + responder.display_name + '</p>'
                               + infoWindowContent(iw_data)
                });
                marker.addListener('click', function () {
                    infowindow.open(map, marker);
                });
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
        // Always post an alert even if we fail to get geolocation.
        navigator.geolocation.getCurrentPosition(postAlert, postAlert, {
            'timeout': 5000
        });
    };

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
    }

    var postAlert = function (position) {
        var data = {
            'action': jQuery('#activate-alert-form input[name="action"]').val(),
            'buoy_nonce': jQuery('#buoy_nonce').val()
        };
        if (position.coords) {
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
    }

    var infoWindowContent = function (data) {
        var html = '<ul>';
        for (key in data) {
            html += '<li>' + jQuery('<span>').append(
                        jQuery('<a>')
                        .attr('href', data[key])
                        .attr('target', '_blank')
                        .html(buoy_vars['i18n_' + key])
                    ).html() + '</li>';
        }
        html += '</ul>';
        return html;
    };

    /**
     * Creates a google map centered on the given coordinates.
     *
     * @param object coords An object of geolocated data with properties named "lat" and "lng".
     * @param bool mark_coords Whether or not to create a marker and infowindow for the coords location.
     */
    var initMap = function (coords, mark_coords) {
        if ('undefined' === typeof google) { return; }

        this.map = new google.maps.Map(document.getElementById('map'));
        this.marker_bounds = new google.maps.LatLngBounds();

        if (mark_coords) {
            var infowindow = new google.maps.InfoWindow({
                'content': '<p>' + buoy_vars.i18n_crisis_location + '</p>'
                           + infoWindowContent({
                               'directions': 'https://maps.google.com/?saddr=Current+Location&daddr=' + encodeURIComponent(coords.lat) + ',' + encodeURIComponent(coords.lng)
                           })
            });
            var marker = new google.maps.Marker({
                'position': new google.maps.LatLng(coords.lat, coords.lng),
                'map': map,
                'title': buoy_vars.i18n_crisis_location
            });
            this.map_markers.incident = marker;
            marker_bounds.extend(new google.maps.LatLng(coords.lat, coords.lng));
            marker.addListener('click', function () {
                infowindow.open(map, marker);
            });
        }

        if (jQuery('#map-container').data('responder-info')) {
            jQuery.each(jQuery('#map-container').data('responder-info'), function (i, v) {
                var responder_geo = new google.maps.LatLng(
                    parseFloat(v.geo.latitude), parseFloat(v.geo.longitude)
                );
                var infowindow = new google.maps.InfoWindow({
                    'content': '<p>' + v.display_name + '</p>'
                               + infoWindowContent({
                                   'directions': 'https://maps.google.com/?saddr=Current+Location&daddr=' + encodeURIComponent(v.geo.latitude) + ',' + encodeURIComponent(v.geo.longitude),
                                   'call': 'tel:' + v.call
                               })
                });
                var marker = new google.maps.Marker({
                    'position': responder_geo,
                    'map': map,
                    'title': v.display_name,
                    'icon': v.avatar_url
                });
                map_markers[v.id] = marker;
                marker_bounds.extend(responder_geo);
                marker.addListener('click', function () {
                    infowindow.open(map, marker);
                });
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
                'title': buoy_vars.i18n_my_location,
                'icon': jQuery('#map-container').data('my-avatar-url')
            });
            marker_bounds.extend(my_geo);
            map.fitBounds(marker_bounds);
        });
    };

    var init = function (body) {
        body = body || document.body;
        incident_hash = jQuery('#map-container', body).data('incident-hash');
        jQuery(document).ready(function () {
            // Panic buttons (activate alert).
            jQuery('#activate-alert-form').on('submit', function (e) {
                e.preventDefault();
                jQuery(this).find('#activate-btn-submit').prop('disabled', true);
                jQuery('#submitting-alert-modal').modal({
                    'show': true,
                    'backdrop': 'static'
                });
                activateAlert();
            });
            jQuery('#activate-msg-btn-submit').on('click', function () {
                jQuery('#choose-teams-panel').removeClass('hidden');
                jQuery('#emergency-message-modal .modal-body').append(jQuery('#choose-teams-panel').detach());
                jQuery('#emergency-message-modal').modal('show');
            });
            jQuery('#emergency-message-modal').on('shown.bs.modal', function () {
                  jQuery('#crisis-message').focus();
            })
            jQuery('#emergency-message-modal button.btn-success').on('click', function () {
                jQuery('#submitting-alert-modal').modal({
                    'show': true,
                    'backdrop': 'static'
                });
                activateAlert();
            });

            if (jQuery('#scheduled-datetime-tz').length) {
                jQuery('#scheduled-datetime-tz').datetimepicker({
                    'lazyInit': true,
                    'lang': buoy_vars.ietf_language_tag,
                    'minDate': 0, // today is the earliest allowable date
                    'mask': true,
                    'validateOnBlur': false
                });
            }
            jQuery('#schedule-future-alert-btn').on('click', function () {
                jQuery('#choose-teams-panel').removeClass('hidden');
                jQuery('#scheduled-alert-modal .modal-body').append(jQuery('#choose-teams-panel').detach());
                jQuery('#scheduled-alert-modal').modal('show');
            });
            jQuery('#scheduled-alert-modal button.btn-success').on('click', function () {
                jQuery(this).prop('disabled', true);
                jQuery(this).html(buoy_vars.i18n_scheduling_alert);
                jQuery('#submitting-alert-modal').modal({
                    'show': true,
                    'backdrop': 'static'
                });
                scheduleAlert(function () {
                    jQuery('#scheduled-alert-modal button.btn-success').prop('disabled', false);
                    jQuery('#scheduled-alert-modal button.btn-success').html(buoy_vars.i18n_schedule_alert);
                    jQuery('#submitting-alert-modal').modal('hide');
                });
            });

            // Show/hide incident map
            jQuery('#toggle-incident-map-btn').on('click', function () {
                var map_container = jQuery('#map-container');
                if (map_container.is(':visible')) {
                    map_container.slideUp();
                    this.textContent = buoy_vars.i18n_show_map;
                } else {
                    map_container.slideDown({
                        'complete': function () {
                            google.maps.event.trigger(map, 'resize');
                            map.fitBounds(marker_bounds);
                        }
                    });
                    this.textContent = buoy_vars.i18n_hide_map;
                }
            });

            jQuery('#fit-map-to-markers-btn').on('click', function (e) {
                e.preventDefault();
                if (jQuery('#map-container').is(':hidden')) {
                    jQuery('#toggle-incident-map-btn').click();
                }
                map.fitBounds(marker_bounds);
            });
            jQuery('#go-to-my-location').on('click', function (e) {
                e.preventDefault();
                if (jQuery('#map-container').is(':hidden')) {
                    jQuery('#toggle-incident-map-btn').click();
                }
                map.panTo(map_markers[jQuery(this).data('user-id')].getPosition());
                touchMap();
            });

            // Upload media for incident
            jQuery('#upload-media-btn').on('click', function (e) {
                e.preventDefault();
                jQuery(this).next().click();
            });
            jQuery('#upload-media-btn').next().on('change', function (e) {
                var upload_url = ajaxurl + '?action=buoy_upload_media';
                upload_url    += '&buoy_nonce=' + buoy_vars.incident_nonce;
                upload_url    += '&buoy_hash=' + jQuery('#map-container').data('incident-hash');
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
                    { 'timeout': 5000 }
                );
            });

            if (jQuery('.dashboard_page_buoy_chat').length) {
                // TODO: Clear the watcher when failing to get position?
                //       Then what? Keep trying? Show a dialog asking the user to
                //       turn on location services?
                geowatcher_id = navigator.geolocation.watchPosition(updateMyLocation, logGeoError, {
                    'timeout': 5000
                });
            }

            // Note: This works around GitHub issue #47.
            // Could be removed after WebKit and/or Bootstrap fixes this in their libs.
            if (jQuery('.dashboard_page_buoy_chat, .dashboard_page_buoy_activate_alert').length) {
                jQuery('body').append(jQuery('.modal').detach());
            }
            // Show buttons that need JavaScript to function.
            jQuery('#modal-features.hidden, #alert-map.hidden').removeClass('hidden');

            // Enhance the WP Toolbar.
            jQuery('#wp-admin-bar-buoy_my_scheduled_alerts a').each(function () {
                var a_el = jQuery(this);
                a_el.on('click', function (e) {
                    e.preventDefault();
                    jQuery.post(a_el.attr('href'), {'action': 'buoy_unschedule_alert'},
                        function (response) {
                            if (response.success) {
                                a_el.remove();
                                if (0 === countIncidentMenuItems()) {
                                    jQuery('#wp-admin-bar-buoy-alert-menu').remove();
                                }
                            }
                        },
                        'json'
                    );
                });
            });
        });

        jQuery(window).on('load', function () {
            if (jQuery('.dashboard_page_buoy_chat #map, .dashboard_page_buoy_review_alert #map').length) {
                this.emergency_location = {
                    'lat': parseFloat(jQuery('#map-container').data('incident-latitude')),
                    'lng': parseFloat(jQuery('#map-container').data('incident-longitude'))
                };
                if (isNaN(this.emergency_location.lat) || isNaN(this.emergency_location.lng)) {
                    jQuery('<div class="notice error is-dismissible"><p>' + buoy_vars.i18n_missing_crisis_location + '</p></div>')
                        .insertBefore('#map-container');
                    navigator.geolocation.getCurrentPosition(function (pos) {
                        initMap({'lat': pos.coords.latitude, 'lng': pos.coords.longitude}, false);
                    });
                } else {
                    initMap(this.emergency_location, true);
                }
            }
            if (jQuery('.dashboard_page_buoy_review_alert #map').length) {
                addMarkerForCurrentLocation();
            }
        });

        jQuery('#commentform').removeAttr('novalidate');
    };

    var countIncidentMenuItems = function () {
        return jQuery('#wp-admin-bar-buoy-alert-menu a').length;
    };

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

    return {
        'init': init,
        'installWebApp': installWebApp,
        countIncidentMenuItems: countIncidentMenuItems
    };
})();
