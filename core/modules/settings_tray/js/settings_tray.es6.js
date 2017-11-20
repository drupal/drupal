/**
 * @file
 * Drupal's Settings Tray library.
 *
 * @private
 */

(function ($, Drupal) {
  const blockConfigureSelector = '[data-settings-tray-edit]';
  const toggleEditSelector = '[data-drupal-settingstray="toggle"]';
  const itemsToToggleSelector = '[data-off-canvas-main-canvas], #toolbar-bar, [data-drupal-settingstray="editable"] a, [data-drupal-settingstray="editable"] button';
  const contextualItemsSelector = '[data-contextual-id] a, [data-contextual-id] button';
  const quickEditItemSelector = '[data-quickedit-entity-id]';

  /**
   * Prevent default click events except contextual links.
   *
   * In edit mode the default action of click events is suppressed.
   *
   * @param {jQuery.Event} event
   *   The click event.
   */
  function preventClick(event) {
    // Do not prevent contextual links.
    if ($(event.target).closest('.contextual-links').length) {
      return;
    }
    event.preventDefault();
  }

  /**
   * Close any active toolbar tray before entering edit mode.
   */
  function closeToolbarTrays() {
    $(Drupal.toolbar.models.toolbarModel.get('activeTab')).trigger('click');
  }

  /**
   * Disables the QuickEdit module editor if open.
   */
  function disableQuickEdit() {
    $('.quickedit-toolbar button.action-cancel').trigger('click');
  }

  /**
   * Closes/removes off-canvas.
   */
  function closeOffCanvas() {
    $('.ui-dialog-off-canvas .ui-dialog-titlebar-close').trigger('click');
  }

  /**
   * Gets all items that should be toggled with class during edit mode.
   *
   * @return {jQuery}
   *   Items that should be toggled.
   */
  function getItemsToToggle() {
    return $(itemsToToggleSelector).not(contextualItemsSelector);
  }

  /**
   * Helper to switch edit mode state.
   *
   * @param {boolean} editMode
   *   True enable edit mode, false disable edit mode.
   */
  function setEditModeState(editMode) {
    if (!document.querySelector('[data-off-canvas-main-canvas]')) {
      throw new Error('data-off-canvas-main-canvas is missing from settings-tray-page-wrapper.html.twig');
    }
    editMode = !!editMode;
    const $editButton = $(toggleEditSelector);
    let $editables;
    // Turn on edit mode.
    if (editMode) {
      $editButton.text(Drupal.t('Editing'));
      closeToolbarTrays();

      $editables = $('[data-drupal-settingstray="editable"]').once('settingstray');
      if ($editables.length) {
        // Use event capture to prevent clicks on links.
        document.querySelector('[data-off-canvas-main-canvas]').addEventListener('click', preventClick, true);

        // When a click occurs try and find the settings-tray edit link
        // and click it.
        $editables
          .not(contextualItemsSelector)
          .on('click.settingstray', (e) => {
            // Contextual links are allowed to function in Edit mode.
            if ($(e.target).closest('.contextual').length || !localStorage.getItem('Drupal.contextualToolbar.isViewing')) {
              return;
            }
            $(e.currentTarget).find(blockConfigureSelector).trigger('click');
            disableQuickEdit();
          });
        $(quickEditItemSelector)
          .not(contextualItemsSelector)
          .on('click.settingstray', (e) => {
            /**
             * For all non-contextual links or the contextual QuickEdit link
             * close the off-canvas dialog.
             */
            if (!$(e.target).parent().hasClass('contextual') || $(e.target).parent().hasClass('quickedit')) {
              closeOffCanvas();
            }
            // Do not trigger if target is quick edit link to avoid loop.
            if ($(e.target).parent().hasClass('contextual') || $(e.target).parent().hasClass('quickedit')) {
              return;
            }
            $(e.currentTarget).find('li.quickedit a').trigger('click');
          });
      }
    }
    // Disable edit mode.
    else {
      $editables = $('[data-drupal-settingstray="editable"]').removeOnce('settingstray');
      if ($editables.length) {
        document.querySelector('[data-off-canvas-main-canvas]').removeEventListener('click', preventClick, true);
        $editables.off('.settingstray');
        $(quickEditItemSelector).off('.settingstray');
      }

      $editButton.text(Drupal.t('Edit'));
      closeOffCanvas();
      disableQuickEdit();
    }
    getItemsToToggle().toggleClass('js-settings-tray-edit-mode', editMode);
    $('.edit-mode-inactive').toggleClass('visually-hidden', editMode);
  }

  /**
   * Helper to check the state of the settings-tray mode.
   *
   * @todo don't use a class for this.
   *
   * @return {boolean}
   *   State of the settings-tray edit mode.
   */
  function isInEditMode() {
    return $('#toolbar-bar').hasClass('js-settings-tray-edit-mode');
  }

  /**
   * Helper to toggle Edit mode.
   */
  function toggleEditMode() {
    setEditModeState(!isInEditMode());
  }

  /**
   * Reacts to contextual links being added.
   *
   * @param {jQuery.Event} event
   *   The `drupalContextualLinkAdded` event.
   * @param {object} data
   *   An object containing the data relevant to the event.
   *
   * @listens event:drupalContextualLinkAdded
   */
  $(document).on('drupalContextualLinkAdded', (event, data) => {

    // When the first contextual link is added to the page set Edit Mode.
    $('body').once('settings_tray.edit_mode_init').each(() => {
      const editMode = localStorage.getItem('Drupal.contextualToolbar.isViewing') === 'false';
      if (editMode) {
        setEditModeState(true);
      }
    });

    /**
     * Bind Ajax behaviors to all items showing the class.
     * @todo Fix contextual links to work with use-ajax links in
     * https://www.drupal.org/node/2764931.
     */
    Drupal.attachBehaviors(data.$el[0]);
    /**
     * Bind a listener to all 'Quick edit' links for blocks. Click "Edit"
     * button in toolbar to force Contextual Edit which starts Settings Tray
     * edit mode also.
     */
    data.$el.find(blockConfigureSelector)
      .on('click.settingstray', () => {
        if (!isInEditMode()) {
          $(toggleEditSelector).trigger('click').trigger('click.settings_tray');
        }
        /**
         * Always disable QuickEdit regardless of whether "EditMode" was just
         * enabled.
         */
        disableQuickEdit();
      });
  });

  $(document).on('keyup.settingstray', (e) => {
    if (isInEditMode() && e.keyCode === 27) {
      Drupal.announce(
        Drupal.t('Exited edit mode.'),
      );
      toggleEditMode();
    }
  });

  /**
   * Toggle the js-settings-tray-edit-mode class on items that we want to
   * disable while in edit mode.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Toggle the js-settings-tray-edit-mode class.
   */
  Drupal.behaviors.toggleEditMode = {
    attach() {
      $(toggleEditSelector).once('settingstray').on('click.settingstray', toggleEditMode);
      // Find all Ajax instances that use the 'off_canvas' renderer.
      Drupal.ajax.instances
        // If there is an element and the renderer is 'off_canvas' then we want
        // to add our changes.
        .filter(instance => instance && $(instance.element).attr('data-dialog-renderer') === 'off_canvas')
        // Loop through all Ajax instances that use the 'off_canvas' renderer to
        // set active editable ID.
        .forEach((instance) => {
          // Check to make sure existing dialogOptions aren't overridden.
          if (!('dialogOptions' in instance.options.data)) {
            instance.options.data.dialogOptions = {};
          }
          instance.options.data.dialogOptions.settingsTrayActiveEditableId = $(instance.element).parents('.settings-tray-editable').attr('id');
          instance.progress = { type: 'fullscreen' };
        });
    },
  };

  // Manage Active editable class on opening and closing of the dialog.
  $(window).on({
    'dialog:beforecreate': (event, dialog, $element, settings) => {
      if ($element.is('#drupal-off-canvas')) {
        $('body .settings-tray-active-editable').removeClass('settings-tray-active-editable');
        const $activeElement = $(`#${settings.settingsTrayActiveEditableId}`);
        if ($activeElement.length) {
          $activeElement.addClass('settings-tray-active-editable');
        }
      }
    },
    'dialog:beforeclose': (event, dialog, $element) => {
      if ($element.is('#drupal-off-canvas')) {
        $('body .settings-tray-active-editable').removeClass('settings-tray-active-editable');
      }
    },
  });
}(jQuery, Drupal));
