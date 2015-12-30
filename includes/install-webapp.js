/**
 * This module performs mobile browser detection and triggers an
 * "installation" event that the Buoy module responds to deliver
 * a user interface that prompts for native-live installation.
 */
var BUOY_INSTALLER = (function () {

    this.system;
    this.installed;

    var detectSystem = function () {
        if (detectMobileSafari()) {
            return 'ios';
        }
    };

    var detectMobileSafari = function () {
        return 'standalone' in window.navigator;
    };

    var isInstalled = function () {
        switch (system) {
            case 'ios':
                return window.navigator.standalone;
        }
    };

    var install = function () {
        switch (system) {
            case 'ios':
                return install_ios();
        }
    };

    var install_ios = function () {
        jQuery.event.trigger('install.ios');
    };

    var init = function () {
        system = detectSystem();
        installed = isInstalled();
        if (!installed && system) {
            install();
        }
    };

    return {
        'init': init
    };

})();

jQuery(document).ready(function () {
    BUOY_INSTALLER.init();
});
