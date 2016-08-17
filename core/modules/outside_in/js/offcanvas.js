/**
 * @file
 * Drupal's off-canvas library.
 */

(function ($, Drupal) {

  'use strict';

  // Set the initial state of the off-canvas element.
  // If the state has been set previously, use it.
  Drupal.offCanvas = {
    visible: (Drupal.offCanvas ? Drupal.offCanvas.visible : false)
  };

  /**
   * Create a wrapper container for the off-canvas element.
   *
   * @return {jQuery}
   *   jQuery object that is the off-canvas wrapper element.
   */
  Drupal.theme.createOffCanvasWrapper = function createOffCanvasWrapper() {
    return $('<div id="offcanvas" ' + (document.dir === 'ltr' ? 'data-offset-right' : 'data-offset-left') + ' role="region" aria-labelledby="offcanvas-header"></div>');
  };

  /**
   * Create the title element for the off-canvas element.
   *
   * @param {string} title
   *   The title string.
   *
   * @return {object}
   *   jQuery object that is the off-canvas title element.
   */
  Drupal.theme.createTitle = function createTitle(title) {
    return $('<h1 id="offcanvas-header">' + title + '</h1>');
  };

  /**
   * Create the actual off-canvas content.
   *
   * @param {string} data
   *   This is fully rendered HTML from Drupal.
   *
   * @return {object}
   *   jQuery object that is the off-canvas content element.
   */
  Drupal.theme.createOffCanvasContent = function createOffCanvasContent(data) {
    return $('<div class="offcanvas-content">' + data + '</div>');
  };

  /**
   * Create the off-canvas close element.
   *
   * @param {object} offCanvasWrapper
   *   The jQuery off-canvas wrapper element
   * @param {object} pageWrapper
   *   The jQuery off page wrapper element
   *
   * @return {jQuery}
   *   jQuery object that is the off-canvas close element.
   */
  Drupal.theme.createOffCanvasClose = function createOffCanvasClose(offCanvasWrapper, pageWrapper) {
    return $([
      '<button class="offcanvasClose" aria-label="',
      Drupal.t('Close configuration tray.'),
      '"><span class="visually-hidden">',
      Drupal.t('Close'),
      '</span></button>'
    ].join(''))
    .on('click', function () {
      pageWrapper
        .removeClass('js-tray-open')
        .one('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend', function () {
          Drupal.offCanvas.visible = false;
          offCanvasWrapper.remove();
          Drupal.announce(Drupal.t('Configuration tray closed.'));
        }
      );
    });
  };


  /**
   * Command to open an off-canvas element.
   *
   * @param {Drupal.Ajax} ajax
   *   The Drupal Ajax object.
   * @param {object} response
   *   Object holding the server response.
   * @param {number} [status]
   *   The HTTP status code.
   */
  Drupal.AjaxCommands.prototype.openOffCanvas = function (ajax, response, status) {
    // Discover display/viewport size.
    // @todo Work on breakpoints for tray size:
    //   https://www.drupal.org/node/2784599.
    var $pageWrapper = $('#main-canvas-wrapper');
    // var pageWidth = $pageWrapper.width();

    // Construct off-canvas wrapper
    var $offcanvasWrapper = Drupal.theme('createOffCanvasWrapper');

    // Construct off-canvas internal elements.
    var $offcanvasClose = Drupal.theme('createOffCanvasClose', $offcanvasWrapper, $pageWrapper);
    var $title = Drupal.theme('createTitle', response.dialogOptions.title);
    var $offcanvasContent = Drupal.theme('createOffCanvasContent', response.data);

    // Put everything together.
    $offcanvasWrapper.append([$offcanvasClose, $title, $offcanvasContent]);

    // Handle opening or updating tray with content.
    var existingTray = false;
    if (Drupal.offCanvas.visible) {
      // Remove previous content then append new content.
      $pageWrapper.find('#offcanvas').remove();
      existingTray = true;
    }
    $pageWrapper.addClass('js-tray-open');
    Drupal.offCanvas.visible = true;
    $pageWrapper.append($offcanvasWrapper);
    if (existingTray) {
      Drupal.announce(Drupal.t('Configuration tray content has been updated.'));
    }
    else {
      Drupal.announce(Drupal.t('Configuration tray opened.'));
    }
    Drupal.attachBehaviors(document.querySelector('#offcanvas'), drupalSettings);
  };

})(jQuery, Drupal);
