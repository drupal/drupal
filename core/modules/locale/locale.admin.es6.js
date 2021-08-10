/**
 * @file
 * Locale admin behavior.
 */

(function ($, Drupal) {
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
    attach() {
      const form = once('localetranslatedirty', '#locale-translate-edit-form');
      if (form.length) {
        const $form = $(form);
        // Display a notice if any row changed.
        $form.one('formUpdated.localeTranslateDirty', 'table', function () {
          const $marker = $(
            Drupal.theme('localeTranslateChangedWarning'),
          ).hide();
          $(this).addClass('changed').before($marker);
          $marker.fadeIn('slow');
        });
        // Highlight changed row.
        $form.on('formUpdated.localeTranslateDirty', 'tr', function () {
          const $row = $(this);
          const rowToMark = once('localemark', $row);
          const marker = Drupal.theme('localeTranslateChangedMarker');

          $row.addClass('changed');
          // Add an asterisk only once if row changed.
          if (rowToMark.length) {
            $(rowToMark).find('td:first-child .js-form-item').append(marker);
          }
        });
      }
    },
    detach(context, settings, trigger) {
      if (trigger === 'unload') {
        const form = once.remove(
          'localetranslatedirty',
          '#locale-translate-edit-form',
        );
        if (form.length) {
          $(form).off('formUpdated.localeTranslateDirty');
        }
      }
    },
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
    attach(context, settings) {
      const table = once('expand-updates', '#locale-translation-status-form');
      if (table.length) {
        const $table = $(table);
        const $tbodies = $table.find('tbody');

        // Open/close the description details by toggling a tr class.
        $tbodies.on('click keydown', '.description', function (e) {
          if (e.keyCode && e.keyCode !== 13 && e.keyCode !== 32) {
            return;
          }
          e.preventDefault();
          const $tr = $(this).closest('tr');

          $tr.toggleClass('expanded');

          // Change screen reader text.
          $tr.find('.locale-translation-update__prefix').text(() => {
            if ($tr.hasClass('expanded')) {
              return Drupal.t('Hide description');
            }

            return Drupal.t('Show description');
          });
        });
        $table.find('.requirements, .links').hide();
      }
    },
  };

  $.extend(
    Drupal.theme,
    /** @lends Drupal.theme */ {
      /**
       * Creates markup for a changed translation marker.
       *
       * @return {string}
       *   Markup for the marker.
       */
      localeTranslateChangedMarker() {
        return `<abbr class="warning ajax-changed" title="${Drupal.t(
          'Changed',
        )}">*</abbr>`;
      },

      /**
       * Creates markup for the translation changed warning.
       *
       * @return {string}
       *   Markup for the warning.
       */
      localeTranslateChangedWarning() {
        return `<div class="clearfix messages messages--warning">${Drupal.theme(
          'localeTranslateChangedMarker',
        )} ${Drupal.t(
          'Changes made in this table will not be saved until the form is submitted.',
        )}</div>`;
      },
    },
  );
})(jQuery, Drupal);
