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
            var new_chat = Math.random().toString(36).substring(2); // not "secure," I know
            jQuery.post(ajaxurl,
                {
                    'action': 'better-angels_findme',
                    'pos': position.coords,
                    'chat': new_chat
                },
                function (response) {
                    if (console && console.log) {
                        console.log(response);
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
}
BETTER_ANGELS.getQueryVariable = function (variable) {
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i=0;i<vars.length;i++) {
        var pair = vars[i].split("=");
        if(pair[0] == variable){return pair[1];}
    }
    return(false);
}

jQuery(document).ready(function () {
    jQuery('#activate-btn-submit').on('click', BETTER_ANGELS.geoFindMe);
});
