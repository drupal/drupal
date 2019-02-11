/**
 * @file media_library.widget.js
 */
(($, Drupal) => {
  /**
   * Allows users to re-order their selection with drag+drop.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to re-order selected media items.
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
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to toggle the weight field for media items.
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
})(jQuery, Drupal);
