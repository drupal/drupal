(function ($) {

"use strict";

Drupal.behaviors.TranslationEnable = {
  attach: function (context) {
    $('#edit-node-type-language-default, #edit-node-type-language-hidden', context).change(function(context) {
      var default_language = $('#edit-node-type-language-default').val();

      if ((default_language === 'und' || default_language === 'zxx' || default_language === 'mul') && $('#edit-node-type-language-hidden').attr('checked')) {
        $('.form-item-node-type-language-translation-enabled').hide();
        $('#edit-node-type-language-translation-enabled').removeAttr('checked');
      } else {
        $('.form-item-node-type-language-translation-enabled').show();
      }
    });
    $('#edit-node-type-language-default', context).trigger('change');
  }
};

})(jQuery);
