/**
 * @file media_library.widget.js
 */
(($, Drupal, window) => {
  /**
   * Wrapper object for the current state of the media library.
   */
  Drupal.MediaLibrary = {
    /**
     * When a user interacts with the media library we want the selection to
     * persist as long as the media library modal is opened. We temporarily
     * store the selected items while the user filters the media library view or
     * navigates to different tabs.
     */
    currentSelection: [],
  };

  /**
   * Warn users when clicking outgoing links from the library or widget.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to links in the media library.
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
   * Load media library content through AJAX.
   *
   * Standard AJAX links (using the 'use-ajax' class) replace the entire library
   * dialog. When navigating to a media type through the vertical tabs, we only
   * want to load the changed library content. This is not only more efficient,
   * but also provides a more accessible user experience for screen readers.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to vertical tabs in the media library.
   *
   * @todo Remove when the AJAX system adds support for replacing a specific
   *   selector via a link.
   *   https://www.drupal.org/project/drupal/issues/3026636
   */
  Drupal.behaviors.MediaLibraryTabs = {
    attach(context) {
      const $menu = $('.js-media-library-menu');
      $menu
        .find('a', context)
        .once('media-library-menu-item')
        .on('click', e => {
          e.preventDefault();
          e.stopPropagation();

          // Replace the library content.
          const ajaxObject = Drupal.ajax({
            wrapper: 'media-library-content',
            url: e.currentTarget.href,
            dialogType: 'ajax',
            progress: {
              type: 'fullscreen',
              message: Drupal.t('Please wait...'),
            },
          });

          // Override the AJAX success callback to shift focus to the media
          // library content.
          ajaxObject.success = function(response, status) {
            // Remove the progress element.
            if (this.progress.element) {
              $(this.progress.element).remove();
            }
            if (this.progress.object) {
              this.progress.object.stopMonitoring();
            }
            $(this.element).prop('disabled', false);

            // Execute the AJAX commands.
            Object.keys(response || {}).forEach(i => {
              if (response[i].command && this.commands[response[i].command]) {
                this.commands[response[i].command](this, response[i], status);
              }
            });

            // Set focus to the media library content.
            document.getElementById('media-library-content').focus();

            // Remove any response-specific settings so they don't get used on
            // the next call by mistake.
            this.settings = null;
          };
          ajaxObject.execute();

          // Set the active tab.
          $menu.find('.active-tab').remove();
          $menu.find('a').removeClass('active');
          $(e.currentTarget)
            .addClass('active')
            .html(
              Drupal.t(
                '@title<span class="active-tab visually-hidden"> (active tab)</span>',
                { '@title': $(e.currentTarget).html() },
              ),
            );
        });
    },
  };

  /**
   * Update the media library selection when loaded or media items are selected.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to select media items.
   */
  Drupal.behaviors.MediaLibraryItemSelection = {
    attach(context, settings) {
      const $form = $('.js-media-library-views-form', context);
      const currentSelection = Drupal.MediaLibrary.currentSelection;

      if (!$form.length) {
        return;
      }

      const $mediaItems = $(
        '.js-media-library-item input[type="checkbox"]',
        $form,
      );

      // Update the selection array and the hidden form field when a media item
      // is selected.
      $mediaItems.once('media-item-change').on('change', e => {
        const id = e.currentTarget.value;

        // Update the selection.
        const position = currentSelection.indexOf(id);
        if (e.currentTarget.checked) {
          // Check if the ID is not already in the selection and add if needed.
          if (position === -1) {
            currentSelection.push(id);
          }
        } else if (position !== -1) {
          // Remove the ID when it is in the current selection.
          currentSelection.splice(position, 1);
        }

        // Set the selection in the hidden form element.
        $form
          .find('#media-library-modal-selection')
          .val(currentSelection.join())
          .trigger('change');
      });

      /**
       * Disable media items.
       *
       * @param {jQuery} $items
       *   A jQuery object representing the media items that should be disabled.
       */
      function disableItems($items) {
        $items
          .prop('disabled', true)
          .closest('.js-media-library-item')
          .addClass('media-library-item--disabled');
      }

      /**
       * Enable media items.
       *
       * @param {jQuery} $items
       *   A jQuery object representing the media items that should be enabled.
       */
      function enableItems($items) {
        $items
          .prop('disabled', false)
          .closest('.js-media-library-item')
          .removeClass('media-library-item--disabled');
      }

      /**
       * Update the number of selected items in the button pane.
       *
       * @param {number} remaining
       *   The number of remaining slots.
       */
      function updateSelectionInfo(remaining) {
        const $buttonPane = $(
          '.media-library-widget-modal .ui-dialog-buttonpane',
        );
        if (!$buttonPane.length) {
          return;
        }

        // Add the selection count.
        const latestCount = Drupal.theme(
          'mediaLibrarySelectionCount',
          Drupal.MediaLibrary.currentSelection,
          remaining,
        );
        const $existingCount = $buttonPane.find(
          '.media-library-selected-count',
        );
        if ($existingCount.length) {
          $existingCount.replaceWith(latestCount);
        } else {
          $buttonPane.append(latestCount);
        }
      }

      // The hidden selection form field changes when the selection is updated.
      $('#media-library-modal-selection', $form)
        .once('media-library-selection-change')
        .on('change', e => {
          updateSelectionInfo(settings.media_library.selection_remaining);

          // Prevent users from selecting more items than allowed.
          if (
            currentSelection.length ===
            settings.media_library.selection_remaining
          ) {
            disableItems($mediaItems.not(':checked'));
            enableItems($mediaItems.filter(':checked'));
          } else {
            enableItems($mediaItems);
          }
        });

      // Apply the current selection to the media library view. Changing the
      // checkbox values triggers the change event for the media items. The
      // change event handles updating the hidden selection field for the form.
      currentSelection.forEach(value => {
        $form
          .find(`input[type="checkbox"][value="${value}"]`)
          .prop('checked', true)
          .trigger('change');
      });

      // Hide selection button if nothing is selected. We can't use the
      // context here because the dialog copies the select button.
      $(window)
        .once('media-library-toggle-buttons')
        .on('dialog:aftercreate', () => {
          updateSelectionInfo(settings.media_library.selection_remaining);
        });
    },
  };

  /**
   * Clear the current selection.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to clear the selection when the library modal closes.
   */
  Drupal.behaviors.MediaLibraryModalClearSelection = {
    attach() {
      $(window)
        .once('media-library-clear-selection')
        .on('dialog:afterclose', () => {
          Drupal.MediaLibrary.currentSelection = [];
        });
    },
  };

  /**
   * Theme function for the selection count.
   *
   * @param {Array.<number>} selection
   *   An array containing the selected media item IDs.
   * @param {number} remaining
   *   The number of remaining slots.
   *
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.mediaLibrarySelectionCount = function(selection, remaining) {
    // When the remaining number of items is -1, we allow an unlimited number of
    // items. In that case we don't want to show the number of remaining slots.
    let selectItemsText = Drupal.formatPlural(
      remaining,
      '@selected of @count item selected',
      '@selected of @count items selected',
      {
        '@selected': selection.length,
      },
    );
    if (remaining === -1) {
      selectItemsText = Drupal.formatPlural(
        selection.length,
        '1 item selected',
        '@count items selected',
      );
    }
    return `<div class="media-library-selected-count" aria-live="polite">${selectItemsText}</div>`;
  };
})(jQuery, Drupal, window);
