/**
 * @file
 * Drupal's Outside-In library.
 */

(function ($, Drupal) {

  'use strict';

  $('.outside-in-editable')
    // Bind an event listener to the .outside-in-editable div
    // This listen for click events and stops default actions of those elements.
    .on('click', '.js-outside-in-edit-mode', function (e) {
      if (localStorage.getItem('Drupal.contextualToolbar.isViewing') === 'false') {
        e.preventDefault();
      }
    })
    // Bind an event listener to the .outside-in-editable div
    // When a click occurs try and find the outside-in edit link
    // and click it.
    .not('div.contextual a, div.contextual button')
    .on('click', function (e) {
      if ($(e.target.offsetParent).hasClass('contextual')) {
        return;
      }
      if (!localStorage.getItem('Drupal.contextualToolbar.isViewing')) {
        return;
      }
      var editLink = $(e.target).find('a[data-dialog-renderer="offcanvas"]')[0];
      if (!editLink) {
        var closest = $(e.target).closest('.outside-in-editable');
        editLink = closest.find('li a[data-dialog-renderer="offcanvas"]')[0];
      }
      editLink.click();
    });

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
    // Outside-In edit mode also.
    data.$el.find('.outside-inblock-configure a').on('click', function () {
      if (!isActiveMode()) {
        $('div.contextual-toolbar-tab.toolbar-tab button').click();
      }
    });
  });

  /**
   * Gets all items that should be toggled with class during edit mode.
   *
   * @return {*}
   *   Items that should be toggled.
   */
  var getItemsToToggle = function () {
    return $('#main-canvas, #toolbar-bar, .outside-in-editable a, .outside-in-editable button')
      .not('div.contextual a, div.contextual button');
  };

  var isActiveMode = function () {
    return $('#toolbar-bar').hasClass('js-outside-in-edit-mode');
  };

  var setToggleActiveMode = function setToggleActiveMode(forceActive) {
    forceActive = forceActive || false;
    if (forceActive || !isActiveMode()) {
      $('#toolbar-bar .contextual-toolbar-tab button').text(Drupal.t('Editing'));
      // Close the Manage tray if open when entering edit mode.
      if ($('#toolbar-item-administration-tray').hasClass('is-active')) {
        $('#toolbar-item-administration').click();
      }
      getItemsToToggle().addClass('js-outside-in-edit-mode');
      $('.edit-mode-inactive').addClass('visually-hidden');
    }
    else {
      $('#toolbar-bar .contextual-toolbar-tab button').text(Drupal.t('Edit'));
      getItemsToToggle().removeClass('js-outside-in-edit-mode');
      $('.edit-mode-inactive').removeClass('visually-hidden');
    }
  };

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
        setToggleActiveMode(true);
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
  Drupal.behaviors.toggleActiveMode = {
    attach: function () {
      $('.contextual-toolbar-tab.toolbar-tab button').once('toggle-edit-mode').on('click', function () {
        setToggleActiveMode();
      });

      var search = Drupal.ajax.WRAPPER_FORMAT + '=drupal_dialog';
      var replace = Drupal.ajax.WRAPPER_FORMAT + '=drupal_dialog_offcanvas';
      // Loop through all Ajax links and change the format to offcanvas when
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
          instance.progress = {type: 'fullscreen'};
        });
    }
  };

})(jQuery, Drupal);
