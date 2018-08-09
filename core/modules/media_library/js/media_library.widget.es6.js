/**
 * @file media_library.widget.js
 */
(($, Drupal) => {
  /**
   * Allows users to re-order their selection with drag+drop.
   */
  Drupal.behaviors.MediaLibraryWidgetSortable = {
    attach(context) {
      // Allow media items to be re-sorted with drag+drop in the widget.
      $('.js-media-library-selection', context)
        .once('media-library-sortable')
        .sortable({
          tolerance: 'pointer',
          helper: 'clone',
          handle: '.js-media-library-item-preview',
          stop: ({ target }) => {
            // Update all the hidden "weight" fields.
            $(target)
              .children()
              .each((index, child) => {
                $(child)
                  .find('.js-media-library-item-weight')
                  .val(index);
              });
          },
        });
    },
  };

  /**
   * Allows selection order to be set without drag+drop for accessibility.
   */
  Drupal.behaviors.MediaLibraryWidgetToggleWeight = {
    attach(context) {
      const strings = {
        show: Drupal.t('Show media item weights'),
        hide: Drupal.t('Hide media item weights'),
      };
      $('.js-media-library-widget-toggle-weight', context)
        .once('media-library-toggle')
        .on('click', e => {
          e.preventDefault();
          $(e.currentTarget)
            .toggleClass('active')
            .text(
              $(e.currentTarget).hasClass('active')
                ? strings.hide
                : strings.show,
            )
            .parent()
            .find('.js-media-library-item-weight')
            .parent()
            .toggle();
        })
        .text(strings.show);
      $('.js-media-library-item-weight', context)
        .once('media-library-toggle')
        .parent()
        .hide();
    },
  };

  /**
   * Warn users when clicking outgoing links from the library or widget.
   */
  Drupal.behaviors.MediaLibraryWidgetWarn = {
    attach(context) {
      $('.js-media-library-item a[href]', context)
        .once('media-library-warn-link')
        .on('click', e => {
          const message = Drupal.t(
            'Unsaved changes to the form will be lost. Are you sure you want to leave?',
          );
          const confirmation = window.confirm(message);
          if (!confirmation) {
            e.preventDefault();
          }
        });
    },
  };

  /**
   * Prevent users from selecting more items than allowed in the view.
   */
  Drupal.behaviors.MediaLibraryWidgetRemaining = {
    attach(context, settings) {
      const $view = $('.js-media-library-view', context).once(
        'media-library-remaining',
      );
      $view
        .find('.js-media-library-item input[type="checkbox"]')
        .on('change', () => {
          if (
            settings.media_library &&
            settings.media_library.selection_remaining
          ) {
            const $checkboxes = $view.find(
              '.js-media-library-item input[type="checkbox"]',
            );
            if (
              $checkboxes.filter(':checked').length ===
              settings.media_library.selection_remaining
            ) {
              $checkboxes
                .not(':checked')
                .prop('disabled', true)
                .closest('.js-media-library-item')
                .addClass('media-library-item--disabled');
            } else {
              $checkboxes
                .prop('disabled', false)
                .closest('.js-media-library-item')
                .removeClass('media-library-item--disabled');
            }
          }
        });
    },
  };
})(jQuery, Drupal);
