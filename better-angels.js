var BETTER_ANGELS = {};
BETTER_ANGELS.geoFindMe = function () {
    if (!navigator.geolocation){
        if (console && console.error) {
            console.error('Geolocation is not supported by your browser');
        }
        return;
    }
    navigator.geolocation.getCurrentPosition(
        function (position) { // success callback
            jQuery.post(ajaxurl,
                {
                    'action': 'better-angels_findme',
                    'pos': position.coords,
                },
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
    // TODO: Fix this confusion about what "coords" is.
    var coords = {
        "lat": parseFloat(coords.latitude),
        "lng": parseFloat(coords.longitude)
    };
    var map = new google.maps.Map(document.getElementById('map'), {
        zoom: 16,
        center: coords
    });
    var marker = new google.maps.Marker({
        position: coords,
        map: map,
        title: 'Crisis'
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

jQuery(document).ready(function () {
    jQuery('#activate-btn-submit').on('click', BETTER_ANGELS.geoFindMe);
});

// Show "safety information" on page load,
// TODO: this should automatically be dismissed when another user
// enters the chat room.
if (BETTER_ANGELS.getQueryVariable('show_safety_modal')) {
    jQuery(window).load(function () {
        jQuery('#safety-information-modal').modal('show');
    });
}

jQuery(document).ready(function () {

    jQuery('#show-incident-map-btn').on('click', function () {
        jQuery('#map-container').slideDown(
            {
            'complete': function () {
                    //google.maps.event.trigger(map, 'resize');
                    navigator.geolocation.getCurrentPosition(function (position) {
                        BETTER_ANGELS.initMap(position);
                    });
                }
            }
        );
    });

    jQuery('#hide-incident-map-btn').on('click', function () {
        jQuery('#map-container').slideUp();
    });
});

jQuery(window).on('load', function () {
    navigator.geolocation.getCurrentPosition(function (position) {
        BETTER_ANGELS.initMap(position);
    });
});
