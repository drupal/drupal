/**
 * @file
 * Drupal's Settings Tray library.
 */

(function ($, Drupal) {

  'use strict';

  var blockConfigureSelector = '[data-outside-in-edit]';
  var toggleEditSelector = '[data-drupal-outsidein="toggle"]';
  var itemsToToggleSelector = '[data-offcanvas-main-canvas], #toolbar-bar, [data-drupal-outsidein="editable"] a, [data-drupal-outsidein="editable"] button';
  var contextualItemsSelector = '[data-contextual-id] a, [data-contextual-id] button';
  var quickEditItemSelector = '[data-quickedit-entity-id]';

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
  $(document).on('drupalContextualLinkAdded', function (event, data) {
    // Bind Ajax behaviors to all items showing the class.
    // @todo Fix contextual links to work with use-ajax links in
    //    https://www.drupal.org/node/2764931.
    Drupal.attachBehaviors(data.$el[0]);

    // Bind a listener to all 'Quick edit' links for blocks
    // Click "Edit" button in toolbar to force Contextual Edit which starts
    // Settings Tray edit mode also.
    data.$el.find(blockConfigureSelector)
      .on('click.outsidein', function () {
        if (!isInEditMode()) {
          $(toggleEditSelector).trigger('click').trigger('click.outside_in');
        }
        // Always disable QuickEdit regardless of whether "EditMode" was just enabled.
        disableQuickEdit();
      });
  });

  $(document).on('keyup.outsidein', function (e) {
    if (isInEditMode() && e.keyCode === 27) {
      Drupal.announce(
        Drupal.t('Exited edit mode.')
      );
      toggleEditMode();
    }
  });

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
   * Helper to check the state of the outside-in mode.
   *
   * @todo don't use a class for this.
   *
   * @return {boolean}
   *  State of the outside-in edit mode.
   */
  function isInEditMode() {
    return $('#toolbar-bar').hasClass('js-outside-in-edit-mode');
  }

  /**
   * Helper to toggle Edit mode.
   */
  function toggleEditMode() {
    setEditModeState(!isInEditMode());
  }

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
   * Closes/removes offcanvas.
   */
  function closeOffCanvas() {
    $('.ui-dialog-offcanvas .ui-dialog-titlebar-close').trigger('click');
  }

  /**
   *  Helper to switch edit mode state.
   *
   * @param {boolean} editMode
   *  True enable edit mode, false disable edit mode.
   */
  function setEditModeState(editMode) {
    editMode = !!editMode;
    var $editButton = $(toggleEditSelector);
    var $editables;
    // Turn on edit mode.
    if (editMode) {
      $editButton.text(Drupal.t('Editing'));
      closeToolbarTrays();

      $editables = $('[data-drupal-outsidein="editable"]').once('outsidein');
      if ($editables.length) {
        // Use event capture to prevent clicks on links.
        document.querySelector('[data-offcanvas-main-canvas]').addEventListener('click', preventClick, true);

        // When a click occurs try and find the outside-in edit link
        // and click it.
        $editables
          .not(contextualItemsSelector)
          .on('click.outsidein', function (e) {
            // Contextual links are allowed to function in Edit mode.
            if ($(e.target).closest('.contextual').length || !localStorage.getItem('Drupal.contextualToolbar.isViewing')) {
              return;
            }
            $(e.currentTarget).find(blockConfigureSelector).trigger('click');
            disableQuickEdit();
          });
        $(quickEditItemSelector)
          .not(contextualItemsSelector)
          .on('click.outsidein', function (e) {
            // For all non-contextual links or the contextual QuickEdit link close the off-canvas tray.
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
      $editables = $('[data-drupal-outsidein="editable"]').removeOnce('outsidein');
      if ($editables.length) {
        document.querySelector('[data-offcanvas-main-canvas]').removeEventListener('click', preventClick, true);
        $editables.off('.outsidein');
        $(quickEditItemSelector).off('.outsidein');
      }

      $editButton.text(Drupal.t('Edit'));
      closeOffCanvas();
      disableQuickEdit();
    }
    getItemsToToggle().toggleClass('js-outside-in-edit-mode', editMode);
    $('.edit-mode-inactive').toggleClass('visually-hidden', editMode);
  }

  /**
   * Attaches contextual's edit toolbar tab behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches contextual toolbar behavior on a contextualToolbar-init event.
   */
  Drupal.behaviors.outsideInEdit = {
    attach: function () {
      var editMode = localStorage.getItem('Drupal.contextualToolbar.isViewing') === 'false';
      if (editMode) {
        setEditModeState(true);
      }
    }
  };

  /**
   * Toggle the js-outside-edit-mode class on items that we want to disable while in edit mode.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Toggle the js-outside-edit-mode class.
   */
  Drupal.behaviors.toggleEditMode = {
    attach: function () {

      $(toggleEditSelector).once('outsidein').on('click.outsidein', toggleEditMode);

      var search = Drupal.ajax.WRAPPER_FORMAT + '=drupal_dialog';
      var replace = Drupal.ajax.WRAPPER_FORMAT + '=drupal_dialog_offcanvas';
      // Loop through all Ajax links and change the format to dialog-offcanvas when
      // needed.
      Drupal.ajax.instances
        .filter(function (instance) {
          var hasElement = instance && !!instance.element;
          var rendererOffcanvas = false;
          var wrapperOffcanvas = false;
          if (hasElement) {
            rendererOffcanvas = $(instance.element).attr('data-dialog-renderer') === 'offcanvas';
            wrapperOffcanvas = instance.options.url.indexOf('drupal_dialog_offcanvas') === -1;
          }
          return hasElement && rendererOffcanvas && wrapperOffcanvas;
        })
        .forEach(function (instance) {
          // @todo Move logic for data-dialog-renderer attribute into ajax.js
          //   https://www.drupal.org/node/2784443
          instance.options.url = instance.options.url.replace(search, replace);
          // Check to make sure existing dialogOptions aren't overridden.
          if (!('dialogOptions' in instance.options.data)) {
            instance.options.data.dialogOptions = {};
          }
          instance.options.data.dialogOptions.outsideInActiveEditableId = $(instance.element).parents('.outside-in-editable').attr('id');
          instance.progress = {type: 'fullscreen'};
        });
    }
  };

  // Manage Active editable class on opening and closing of the dialog.
  $(window).on({
    'dialog:beforecreate': function (event, dialog, $element, settings) {
      if ($element.is('#drupal-offcanvas')) {
        $('body .outside-in-active-editable').removeClass('outside-in-active-editable');
        var $activeElement = $('#' + settings.outsideInActiveEditableId);
        if ($activeElement.length) {
          $activeElement.addClass('outside-in-active-editable');
          settings.dialogClass += ' ui-dialog-outside-in';
        }
      }
    },
    'dialog:beforeclose': function (event, dialog, $element) {
      if ($element.is('#drupal-offcanvas')) {
        $('body .outside-in-active-editable').removeClass('outside-in-active-editable');
      }
    }
  });

})(jQuery, Drupal);
