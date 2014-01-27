(function ($) {

  "use strict";

  /**
   * Make it so when you enter text into the "New set" textfield, the
   * corresponding radio button gets selected.
   */
  Drupal.behaviors.newSet = {
    attach: function (context, settings) {
      var selectDefault = function () {
        $(this).closest('form').find('.form-item-set .form-type-radio:last input').prop('checked', true);
      };
      $('div.form-item-new input').on('focus', selectDefault).on('keyup', selectDefault);
    }
  };

})(jQuery);
