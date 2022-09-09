/**
 * @file
 * Media Library overrides for Claro
 */
(($, Drupal, window) => {
  /**
   * Update the media library selection when loaded or media items are selected.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to select media items.
   */
  Drupal.behaviors.MediaLibraryItemSelectionClaro = {
    attach() {
      // Move the selection count to the beginning of the button pane after it
      // has been added to the Media Library dialog.
      // @todo replace with theme function override in
      //   https://drupal.org/node/3134526
      if (!once('media-library-selection-info-claro-event', 'html').length) {
        return;
      }
      $(window).on(
        'dialog:aftercreate',
        (event, dialog, $element, settings) => {
          // Since the dialog HTML is not part of the context, we can't use
          // context here.
          const moveCounter = ($selectedCount, $buttonPane) => {
            const $moveSelectedCount = $selectedCount.detach();
            $buttonPane.prepend($moveSelectedCount);
          };

          const $buttonPane = $element
            .closest('.media-library-widget-modal')
            .find('.ui-dialog-buttonpane');
          if (!$buttonPane.length) {
            return;
          }
          const $selectedCount = $buttonPane.find(
            '.js-media-library-selected-count',
          );

          // If the `selected` counter is already present, it can be moved from
          // the end of the button pane to the beginning.
          if ($selectedCount.length) {
            moveCounter($selectedCount, $buttonPane);
          } else {
            // If the `selected` counter is not yet present, create a mutation
            // observer that checks for items added to the button pane. As soon
            // as the counter is added, move it from the end of the button pane
            // to the beginning.
            const selectedCountObserver = new MutationObserver(() => {
              const $selectedCountFind = $buttonPane.find(
                '.js-media-library-selected-count',
              );
              if ($selectedCountFind.length) {
                moveCounter($selectedCountFind, $buttonPane);
                selectedCountObserver.disconnect();
              }
            });
            selectedCountObserver.observe($buttonPane[0], {
              attributes: false,
              childList: true,
              characterData: false,
              subtree: true,
            });
          }
        },
      );
    },
  };
})(jQuery, Drupal, window);
