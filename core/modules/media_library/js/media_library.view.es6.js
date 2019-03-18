/**
 * @file media_library.view.es6.js
 */
(($, Drupal) => {
  /**
   * Adds checkbox to select all items in the library.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to select all media items.
   */
  Drupal.behaviors.MediaLibrarySelectAll = {
    attach(context) {
      const $view = $('.js-media-library-view', context).once(
        'media-library-select-all',
      );
      if ($view.length && $view.find('.js-media-library-item').length) {
        const $checkbox = $(
          '<input type="checkbox" class="form-checkbox" />',
        ).on('click', ({ currentTarget }) => {
          // Toggle all checkboxes.
          const $checkboxes = $(currentTarget)
            .closest('.media-library-view')
            .find('.js-media-library-item input[type="checkbox"]');
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
          .find('.js-media-library-item')
          .first()
          .before($label);
      }
    },
  };
})(jQuery, Drupal);
