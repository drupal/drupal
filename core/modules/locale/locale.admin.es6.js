/**
 * @file
 * Locale admin behavior.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Marks changes of translations.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to show the user if translations has changed.
   * @prop {Drupal~behaviorDetach} detach
   *   Detach behavior to show the user if translations has changed.
   */
  Drupal.behaviors.localeTranslateDirty = {
    attach: function () {
      var $form = $('#locale-translate-edit-form').once('localetranslatedirty');
      if ($form.length) {
        // Display a notice if any row changed.
        $form.one('formUpdated.localeTranslateDirty', 'table', function () {
          var $marker = $(Drupal.theme('localeTranslateChangedWarning')).hide();
          $(this).addClass('changed').before($marker);
          $marker.fadeIn('slow');
        });
        // Highlight changed row.
        $form.on('formUpdated.localeTranslateDirty', 'tr', function () {
          var $row = $(this);
          var $rowToMark = $row.once('localemark');
          var marker = Drupal.theme('localeTranslateChangedMarker');

          $row.addClass('changed');
          // Add an asterisk only once if row changed.
          if ($rowToMark.length) {
            $rowToMark.find('td:first-child .js-form-item').append(marker);
          }
        });
      }
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        var $form = $('#locale-translate-edit-form').removeOnce('localetranslatedirty');
        if ($form.length) {
          $form.off('formUpdated.localeTranslateDirty');
        }
      }
    }
  };

  /**
   * Show/hide the description details on Available translation updates page.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior for toggling details on the translation update page.
   */
  Drupal.behaviors.hideUpdateInformation = {
    attach: function (context, settings) {
      var $table = $('#locale-translation-status-form').once('expand-updates');
      if ($table.length) {
        var $tbodies = $table.find('tbody');

        // Open/close the description details by toggling a tr class.
        $tbodies.on('click keydown', '.description', function (e) {
          if (e.keyCode && (e.keyCode !== 13 && e.keyCode !== 32)) {
            return;
          }
          e.preventDefault();
          var $tr = $(this).closest('tr');

          $tr.toggleClass('expanded');

          // Change screen reader text.
          $tr.find('.locale-translation-update__prefix').text(function () {
            if ($tr.hasClass('expanded')) {
              return Drupal.t('Hide description');
            }
            else {
              return Drupal.t('Show description');
            }
          });
        });
        $table.find('.requirements, .links').hide();
      }
    }
  };

  $.extend(Drupal.theme, /** @lends Drupal.theme */{

    /**
     * Creates markup for a changed translation marker.
     *
     * @return {string}
     *   Markup for the marker.
     */
    localeTranslateChangedMarker: function () {
      return '<abbr class="warning ajax-changed" title="' + Drupal.t('Changed') + '">*</abbr>';
    },

    /**
     * Creates markup for the translation changed warning.
     *
     * @return {string}
     *   Markup for the warning.
     */
    localeTranslateChangedWarning: function () {
      return '<div class="clearfix messages messages--warning">' + Drupal.theme('localeTranslateChangedMarker') + ' ' + Drupal.t('Changes made in this table will not be saved until the form is submitted.') + '</div>';
    }
  });

})(jQuery, Drupal);
