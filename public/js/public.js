/**
 * Simple LMS Public JavaScript
 */
(function($) {
    'use strict';

    var SimpleLMSPublic = {

        /**
         * Initialize.
         */
        init: function() {
            this.initVideoResponsive();
        },

        /**
         * Make embedded videos responsive.
         */
        initVideoResponsive: function() {
            // The CSS handles responsive videos with padding-bottom trick.
            // This JS is reserved for future enhancements.
        }
    };

    $(document).ready(function() {
        SimpleLMSPublic.init();
    });

})(jQuery);
