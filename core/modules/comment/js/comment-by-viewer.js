/**
 * @file
 * Attaches behaviors for the Comment module's "by-viewer" class.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Add 'by-viewer' class to comments written by the current user.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.commentByViewer = {
    attach: function (context) {
      var currentUserID = parseInt(drupalSettings.user.uid, 10);
      $('[data-comment-user-id]')
        .filter(function () {
          return parseInt(this.getAttribute('data-comment-user-id'), 10) === currentUserID;
        })
        .addClass('by-viewer');
    }
  };

})(jQuery, Drupal, drupalSettings);
