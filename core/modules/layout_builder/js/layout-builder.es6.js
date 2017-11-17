(($, { ajax, behaviors }) => {
  behaviors.layoutBuilder = {
    attach(context) {
      $(context).find('.layout-builder--layout__region').sortable({
        items: '> .draggable',
        connectWith: '.layout-builder--layout__region',

        /**
         * Updates the layout with the new position of the block.
         *
         * @param {jQuery.Event} event
         *   The jQuery Event object.
         * @param {Object} ui
         *   An object containing information about the item being sorted.
         */
        update(event, ui) {
          // Only process if the item was moved from one region to another.
          if (ui.sender) {
            ajax({
              url: [
                ui.item.closest('[data-layout-update-url]').data('layout-update-url'),
                ui.sender.closest('[data-layout-delta]').data('layout-delta'),
                ui.item.closest('[data-layout-delta]').data('layout-delta'),
                ui.sender.data('region'),
                $(this).data('region'),
                ui.item.data('layout-block-uuid'),
                ui.item.prev('[data-layout-block-uuid]').data('layout-block-uuid'),
              ]
              .filter(element => element !== undefined)
              .join('/'),
            }).execute();
          }
        },
      });
    },
  };
})(jQuery, Drupal);
