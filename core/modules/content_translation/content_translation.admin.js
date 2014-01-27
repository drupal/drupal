(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Forces applicable options to be checked as translatable.
   */
  Drupal.behaviors.contentTranslationDependentOptions = {
    attach: function (context) {
      var $context = $(context);
      var options = drupalSettings.contentTranslationDependentOptions;
      var $fields, dependent_columns;

      function fieldsChangeHandler($fields, dependent_columns) {
        return function (e) {
          Drupal.behaviors.contentTranslationDependentOptions.check($fields, dependent_columns, $(e.target));
        };
      }

      // We're given a generic name to look for so we find all inputs containing
      // that name and copy over the input values that require all columns to be
      // translatable.
      if (options.dependent_selectors) {
        for (var field in options.dependent_selectors) {
          if (options.dependent_selectors.hasOwnProperty(field)) {
            $fields = $context.find('input[name^="' + field + '"]');
            dependent_columns = options.dependent_selectors[field];

            $fields.on('change', fieldsChangeHandler($fields, dependent_columns));
            Drupal.behaviors.contentTranslationDependentOptions.check($fields, dependent_columns);
          }
        }
      }
    },
    check: function ($fields, dependent_columns, $changed) {
      var $element = $changed;
      var column;

      function filterFieldsList(index, field) {
        return $(field).val() === column;
      }

      // A field that has many different translatable parts can also define one
      // or more columns that require all columns to be translatable.
      for (var index in dependent_columns) {
        if (dependent_columns.hasOwnProperty(index)) {
          column = dependent_columns[index];

          if (!$changed) {
            $element = $fields.filter(filterFieldsList);
          }

          if ($element.is('input[value="' + column + '"]:checked')) {
            $fields.prop('checked', true)
              .not($element).prop('disabled', true);
          }
          else {
            $fields.prop('disabled', false);
          }

        }
      }
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
      })
        .on('click', 'table .field-settings .translatable :input', function (e) {
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

})(jQuery, Drupal, drupalSettings);
