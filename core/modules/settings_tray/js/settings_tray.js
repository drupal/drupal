/**
 * @file
 * Drupal's Settings Tray library.
 *
 * @private
 */

(($, Drupal) => {
  const blockConfigureSelector = '[data-settings-tray-edit]';
  const toggleEditSelector = '[data-drupal-settingstray="toggle"]';
  const itemsToToggleSelector =
    '[data-off-canvas-main-canvas], #toolbar-bar, [data-drupal-settingstray="editable"] a, [data-drupal-settingstray="editable"] button';
  const contextualItemsSelector =
    '[data-contextual-id] a, [data-contextual-id] button';

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
      throw new Error(
        'data-off-canvas-main-canvas is missing from settings-tray-page-wrapper.html.twig',
      );
    }
    editMode = !!editMode;
    let $editables;
    const editButton = document.querySelector(toggleEditSelector);
    // Turn on edit mode.
    if (editMode) {
      if (editButton) {
        editButton.textContent = Drupal.t('Editing');
      }
      closeToolbarTrays();

      $editables = $(
        once('settingstray', '[data-drupal-settingstray="editable"]'),
      );
      if ($editables.length) {
        // Use event capture to prevent clicks on links.
        document
          .querySelector('[data-off-canvas-main-canvas]')
          .addEventListener('click', preventClick, true);
        /**
         * When a click occurs try and find the settings-tray edit link
         * and click it.
         */
        $editables
          .not(contextualItemsSelector)
          .on('click.settingstray', (e) => {
            // Contextual links are allowed to function in Edit mode.
            if (
              $(e.target).closest('.contextual').length ||
              !localStorage.getItem('Drupal.contextualToolbar.isViewing')
            ) {
              return;
            }
            $(e.currentTarget).find(blockConfigureSelector).trigger('click');
          });
      }
    }
    // Disable edit mode.
    else {
      $editables = $(
        once.remove('settingstray', '[data-drupal-settingstray="editable"]'),
      );
      if ($editables.length) {
        document
          .querySelector('[data-off-canvas-main-canvas]')
          .removeEventListener('click', preventClick, true);
        $editables.off('.settingstray');
      }
      if (editButton) {
        editButton.textContent = Drupal.t('Edit');
      }
      closeOffCanvas();
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
   * Prepares Ajax links to work with off-canvas and Settings Tray module.
   */
  function prepareAjaxLinks() {
    // Find all Ajax instances that use the 'off_canvas' renderer.
    Drupal.ajax.instances
      /**
       * If there is an element and the renderer is 'off_canvas' then we want
       * to add our changes.
       */
      .filter(
        (instance) =>
          instance &&
          $(instance.element).attr('data-dialog-renderer') === 'off_canvas',
      )
      /**
       * Loop through all Ajax instances that use the 'off_canvas' renderer to
       * set active editable ID.
       */
      .forEach((instance) => {
        const closestSettingsTray = instance.element.closest(
          '.settings-tray-editable',
        );
        // Check to make sure existing dialogOptions aren't overridden.
        if (!instance.options.data.hasOwnProperty('dialogOptions')) {
          instance.options.data.dialogOptions = {};
        }
        instance.options.data.dialogOptions.settingsTrayActiveEditableId =
          closestSettingsTray.id;
        instance.progress = { type: 'fullscreen' };
      });
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
    /**
     * When contextual links are add we need to set extra properties on the
     * instances in Drupal.ajax.instances for them to work with Edit Mode.
     */
    prepareAjaxLinks();

    // When the first contextual link is added to the page set Edit Mode.
    once('settings_tray.edit_mode_init', 'body').forEach(() => {
      const editMode =
        localStorage.getItem('Drupal.contextualToolbar.isViewing') === 'false';
      if (editMode) {
        setEditModeState(true);
      }
    });

    /**
     * Bind a listener to all 'Quick edit' links for blocks. Click "Edit"
     * button in toolbar to force Contextual Edit which starts Settings Tray
     * edit mode also.
     */
    data.$el.find(blockConfigureSelector).on('click.settingstray', () => {
      if (!isInEditMode()) {
        $(toggleEditSelector).trigger('click').trigger('click.settings_tray');
      }
    });
  });

  $(document).on('keyup.settingstray', (e) => {
    if (isInEditMode() && e.keyCode === 27) {
      Drupal.announce(Drupal.t('Exited edit mode.'));
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
      $(once('settingstray', toggleEditSelector)).on(
        'click.settingstray',
        toggleEditMode,
      );
    },
  };

  // Manage Active editable class on opening and closing of the dialog.
  window.addEventListener('dialog:beforecreate', (e) => {
    if (e.target.id === 'drupal-off-canvas') {
      $('body .settings-tray-active-editable').removeClass(
        'settings-tray-active-editable',
      );
      const $activeElement = $(`#${e.settings.settingsTrayActiveEditableId}`);
      if ($activeElement.length) {
        $activeElement.addClass('settings-tray-active-editable');
      }
    }
  });
  window.addEventListener('dialog:beforeclose', (e) => {
    if (e.target.id === 'drupal-off-canvas') {
      $('body .settings-tray-active-editable').removeClass(
        'settings-tray-active-editable',
      );
    }
  });
})(jQuery, Drupal);
