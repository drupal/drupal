/**
 * @file media_library.ui.es6.js
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
   * Command to update the current media library selection.
   *
   * @param {Drupal.Ajax} [ajax]
   *   The Drupal Ajax object.
   * @param {object} response
   *   Object holding the server response.
   * @param {number} [status]
   *   The HTTP status code.
   */
  Drupal.AjaxCommands.prototype.updateMediaLibrarySelection = function (
    ajax,
    response,
    status,
  ) {
    Object.values(response.mediaIds).forEach((value) => {
      Drupal.MediaLibrary.currentSelection.push(value);
    });
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
        .on('keypress', (e) => {
          // The AJAX link has the button role, so we need to make sure the link
          // is also triggered when pressing the spacebar.
          if (e.which === 32) {
            e.preventDefault();
            e.stopPropagation();
            $(e.currentTarget).trigger('click');
          }
        })
        .on('click', (e) => {
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
          ajaxObject.success = function (response, status) {
            // Remove the progress element.
            if (this.progress.element) {
              $(this.progress.element).remove();
            }
            if (this.progress.object) {
              this.progress.object.stopMonitoring();
            }
            $(this.element).prop('disabled', false);

            // Execute the AJAX commands.
            Object.keys(response || {}).forEach((i) => {
              if (response[i].command && this.commands[response[i].command]) {
                this.commands[response[i].command](this, response[i], status);
              }
            });

            // Set focus to the first tabbable element in the media library
            // content.
            $('#media-library-content :tabbable:first').focus();

            // Remove any response-specific settings so they don't get used on
            // the next call by mistake.
            this.settings = null;
          };
          ajaxObject.execute();

          // Set the selected tab.
          $menu.find('.active-tab').remove();
          $menu.find('a').removeClass('active');
          $(e.currentTarget)
            .addClass('active')
            .html(
              Drupal.t(
                '<span class="visually-hidden">Show </span>@title<span class="visually-hidden"> media</span><span class="active-tab visually-hidden"> (selected)</span>',
                { '@title': $(e.currentTarget).data('title') },
              ),
            );

          // Announce the updated content.
          Drupal.announce(
            Drupal.t('Showing @title media.', {
              '@title': $(e.currentTarget).data('title'),
            }),
          );
        });
    },
  };

  /**
   * Load media library displays through AJAX.
   *
   * Standard AJAX links (using the 'use-ajax' class) replace the entire library
   * dialog. When navigating to a media library views display, we only want to
   * load the changed views display content. This is not only more efficient,
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
  Drupal.behaviors.MediaLibraryViewsDisplay = {
    attach(context) {
      const $view = $(context).hasClass('.js-media-library-view')
        ? $(context)
        : $('.js-media-library-view', context);

      // Add a class to the view to allow it to be replaced via AJAX.
      // @todo Remove the custom ID when the AJAX system allows replacing
      //    elements by selector.
      //    https://www.drupal.org/project/drupal/issues/2821793
      $view
        .closest('.views-element-container')
        .attr('id', 'media-library-view');

      // We would ideally use a generic JavaScript specific class to detect the
      // display links. Since we have no good way of altering display links yet,
      // this is the best we can do for now.
      // @todo Add media library specific classes and data attributes to the
      //    media library display links when we can alter display links.
      //    https://www.drupal.org/project/drupal/issues/3036694
      $('.views-display-link-widget, .views-display-link-widget_table', context)
        .once('media-library-views-display-link')
        .on('click', (e) => {
          e.preventDefault();
          e.stopPropagation();

          const $link = $(e.currentTarget);

          // Add a loading and display announcement for screen reader users.
          let loadingAnnouncement = '';
          let displayAnnouncement = '';
          let focusSelector = '';
          if ($link.hasClass('views-display-link-widget')) {
            loadingAnnouncement = Drupal.t('Loading grid view.');
            displayAnnouncement = Drupal.t('Changed to grid view.');
            focusSelector = '.views-display-link-widget';
          } else if ($link.hasClass('views-display-link-widget_table')) {
            loadingAnnouncement = Drupal.t('Loading table view.');
            displayAnnouncement = Drupal.t('Changed to table view.');
            focusSelector = '.views-display-link-widget_table';
          }

          // Replace the library view.
          const ajaxObject = Drupal.ajax({
            wrapper: 'media-library-view',
            url: e.currentTarget.href,
            dialogType: 'ajax',
            progress: {
              type: 'fullscreen',
              message: loadingAnnouncement || Drupal.t('Please wait...'),
            },
          });

          // Override the AJAX success callback to announce the updated content
          // to screen readers.
          if (displayAnnouncement || focusSelector) {
            const success = ajaxObject.success;
            ajaxObject.success = function (response, status) {
              success.bind(this)(response, status);
              // The AJAX link replaces the whole view, including the clicked
              // link. Move the focus back to the clicked link when the view is
              // replaced.
              if (focusSelector) {
                $(focusSelector).focus();
              }
              // Announce the new view is loaded to screen readers.
              if (displayAnnouncement) {
                Drupal.announce(displayAnnouncement);
              }
            };
          }

          ajaxObject.execute();

          // Announce the new view is being loaded to screen readers.
          // @todo Replace custom announcement when
          //   https://www.drupal.org/project/drupal/issues/2973140 is in.
          if (loadingAnnouncement) {
            Drupal.announce(loadingAnnouncement);
          }
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
      const $form = $(
        '.js-media-library-views-form, .js-media-library-add-form',
        context,
      );
      const currentSelection = Drupal.MediaLibrary.currentSelection;

      if (!$form.length) {
        return;
      }

      const $mediaItems = $(
        '.js-media-library-item input[type="checkbox"]',
        $form,
      );

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
      function updateSelectionCount(remaining) {
        // When the remaining number of items is a negative number, we allow an
        // unlimited number of items. In that case we don't want to show the
        // number of remaining slots.
        const selectItemsText =
          remaining < 0
            ? Drupal.formatPlural(
                currentSelection.length,
                '1 item selected',
                '@count items selected',
              )
            : Drupal.formatPlural(
                remaining,
                '@selected of @count item selected',
                '@selected of @count items selected',
                {
                  '@selected': currentSelection.length,
                },
              );
        // The selected count div could have been created outside of the
        // context, so we unfortunately can't use context here.
        $('.js-media-library-selected-count').html(selectItemsText);
      }

      // Update the selection array and the hidden form field when a media item
      // is selected.
      $mediaItems.once('media-item-change').on('change', (e) => {
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

        // Set the selection in the media library add form. Since the form is
        // not necessarily loaded within the same context, we can't use the
        // context here.
        $('.js-media-library-add-form-current-selection').val(
          currentSelection.join(),
        );
      });

      // The hidden selection form field changes when the selection is updated.
      $('#media-library-modal-selection', $form)
        .once('media-library-selection-change')
        .on('change', (e) => {
          updateSelectionCount(settings.media_library.selection_remaining);

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
      currentSelection.forEach((value) => {
        $form
          .find(`input[type="checkbox"][value="${value}"]`)
          .prop('checked', true)
          .trigger('change');
      });

      // Add the selection count to the button pane when a media library dialog
      // is created.
      $(window)
        .once('media-library-selection-info')
        .on('dialog:aftercreate', () => {
          // Since the dialog HTML is not part of the context, we can't use
          // context here.
          const $buttonPane = $(
            '.media-library-widget-modal .ui-dialog-buttonpane',
          );
          if (!$buttonPane.length) {
            return;
          }
          $buttonPane.append(Drupal.theme('mediaLibrarySelectionCount'));
          updateSelectionCount(settings.media_library.selection_remaining);
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
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.mediaLibrarySelectionCount = function () {
    return `<div class="media-library-selected-count js-media-library-selected-count" role="status" aria-live="polite" aria-atomic="true"></div>`;
  };
})(jQuery, Drupal, window);
