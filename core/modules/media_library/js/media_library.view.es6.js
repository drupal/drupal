/**
 * @file media_library.view.es6.js
 */
(($, Drupal) => {
  /**
   * Adds hover effect to media items.
   */
  Drupal.behaviors.MediaLibraryHover = {
    attach(context) {
      $(
        '.media-library-item .js-click-to-select-trigger,.media-library-item .js-click-to-select-checkbox',
        context,
      )
        .once('media-library-item-hover')
        .on('mouseover mouseout', ({ currentTarget, type }) => {
          $(currentTarget)
            .closest('.media-library-item')
            .toggleClass('is-hover', type === 'mouseover');
        });
    },
  };

  /**
   * Adds focus effect to media items.
   */
  Drupal.behaviors.MediaLibraryFocus = {
    attach(context) {
      $('.media-library-item .js-click-to-select-checkbox input', context)
        .once('media-library-item-focus')
        .on('focus blur', ({ currentTarget, type }) => {
          $(currentTarget)
            .closest('.media-library-item')
            .toggleClass('is-focus', type === 'focus');
        });
    },
  };

  /**
   * Adds checkbox to select all items in the library.
   */
  Drupal.behaviors.MediaLibrarySelectAll = {
    attach(context) {
      const $view = $('.media-library-view', context).once(
        'media-library-select-all',
      );
      if ($view.length && $view.find('.media-library-item').length) {
        const $checkbox = $(
          '<input type="checkbox" class="form-checkbox" />',
        ).on('click', ({ currentTarget }) => {
          // Toggle all checkboxes.
          const $checkboxes = $(currentTarget)
            .closest('.media-library-view')
            .find('.media-library-item input[type="checkbox"]');
          $checkboxes
            .prop('checked', $(currentTarget).prop('checked'))
            .trigger('change');
          // Announce the selection.
          const announcement = $(currentTarget).prop('checked')
            ? Drupal.t('Zero items selected')
            : Drupal.t('All @count items selected', {
                '@count': $checkboxes.length,
              });
          Drupal.announce(announcement);
        });
        const $label = $(
          '<label class="media-library-select-all"></label>',
        ).text(Drupal.t('Select all media'));
        $label.prepend($checkbox);
        $view
          .find('.media-library-item')
          .first()
          .before($label);
      }
    },
  };
})(jQuery, Drupal);
