(function ($) {

"use strict";

/**
 * Forces applicable options to be checked as translatable.
 */
Drupal.behaviors.contentTranslationDependentOptions = {
  attach: function (context, settings) {
    var $options = settings.contentTranslationDependentOptions;
    var $collections = [];

    // We're given a generic name to look for so we find all inputs containing
    // that name and copy over the input values that require all columns to be
    // translatable.
    if ($options.dependent_selectors) {
      $.each($options.dependent_selectors, function($field, $dependent_columns) {
        $collections.push({ elements : $(context).find('input[name^="' + $field + '"]'), dependent_columns : $dependent_columns });
      });
    }

    $.each($collections, function($index, $collection) {
      var $fields = $collection.elements;
      var $dependent_columns = $collection.dependent_columns;

      $fields.change(function() {
        Drupal.behaviors.contentTranslationDependentOptions.check($fields, $dependent_columns, $(this));
      });

      // Run the check function on first trigger of this behavior.
      Drupal.behaviors.contentTranslationDependentOptions.check($fields, $dependent_columns, false);
    });
  },
  check: function($fields, $dependent_columns, $changed) {
    // A field that has many different translatable parts can also define one
    // or more columns that require all columns to be translatable.
    $.each($dependent_columns, function($index, $column) {
      var $element = $changed;

      if(!$element) {
        $fields.each(function() {
          if($(this).val() === $column) {
            $element = $(this);
            return false;
          }
        });
      }

      if($element.is('input[value="' + $column + '"]:checked')) {
        $fields.prop('checked', true).not($element).prop('disabled', true);
      } else {
        $fields.prop('disabled', false);
      }
    });
  }
};

/**
 * Makes field translatability inherit bundle translatability.
 */
Drupal.behaviors.contentTranslation = {
  attach: function (context) {
    // Initially hide all field rows for non translatable bundles and all column
    // rows for non translatable fields.
    $(context).find('table .bundle-settings .translatable :input').once('translation-entity-admin-hide', function () {
      var $input = $(this);
      var $bundleSettings = $input.closest('.bundle-settings');
      if (!$input.is(':checked')) {
        $bundleSettings.nextUntil('.bundle-settings').hide();
      }
      else {
        $bundleSettings.nextUntil('.bundle-settings', '.field-settings').find('.translatable :input:not(:checked)').closest('.field-settings').nextUntil(':not(.column-settings)').hide();
      }
    });

    // When a bundle is made translatable all of its field instances should
    // inherit this setting. Instead when it is made non translatable its field
    // instances are hidden, since their translatability no longer matters.
    $('body').once('translation-entity-admin-bind').on('click', 'table .bundle-settings .translatable :input', function (e) {
      var $target = $(e.target);
      var $bundleSettings = $target.closest('.bundle-settings');
      var $settings = $bundleSettings.nextUntil('.bundle-settings');
      var $fieldSettings = $settings.filter('.field-settings');
      if ($target.is(':checked')) {
        $bundleSettings.find('.operations :input[name$="[language_show]"]').prop('checked', true);
        $fieldSettings.find('.translatable :input').prop('checked', true);
        $settings.show();
      }
      else {
        $settings.hide();
      }
    }).on('click', 'table .field-settings .translatable :input', function (e) {
      var $target = $(e.target);
      var $fieldSettings = $target.closest('.field-settings');
      var $columnSettings = $fieldSettings.nextUntil('.field-settings, .bundle-settings');
      if ($target.is(':checked')) {
        $columnSettings.show();
      }
      else {
        $columnSettings.hide();
      }
    });
  }
};

})(jQuery);
