// React Datepicker integration with drupal behaviour services.
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.reactDatepicker = {
    attach: function (context, settings) {
      // Load and execute react datepicker build.
      $.getScript(
        "/profiles/contrib/o2e_obe_profile/themes/o2e_obe_subtheme/js/datepicker-build.min.js"
      );
    },
  };
})(jQuery, Drupal);
