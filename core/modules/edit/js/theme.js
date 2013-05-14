/**
 * @file
 * Provides overridable theme functions for all of Edit's client-side HTML.
 */
(function ($, Drupal) {

"use strict";

/**
 * Theme function for a "backstage" for the Edit module.
 *
 * @param Object settings
 *   An object with the following keys:
 *   - String id: the id to apply to the backstage.
 * @return String
 *   The corresponding HTML.
 */
Drupal.theme.editBackstage = function (settings) {
  var html = '';
  html += '<div id="' + settings.id + '" />';
  return html;
};

/**
 * Theme function for a modal of the Edit module.
 *
 * @return String
 *   The corresponding HTML.
 */
Drupal.theme.editModal = function () {
  var classes = 'edit-animate-slow edit-animate-invisible edit-animate-delay-veryfast';
  var html = '';
  html += '<div id="edit_modal" class="' + classes + '" role="dialog">';
  html += '  <div class="main"><p></p></div>';
  html += '  <div class="actions"></div>';
  html += '</div>';
  return html;
};

/**
 * Theme function for a toolbar container of the Edit module.
 *
 * @param settings
 *   An object with the following keys:
 *   - String id: the id to apply to the toolbar container.
 * @return String
 *   The corresponding HTML.
 */
Drupal.theme.editToolbarContainer = function (settings) {
  var html = '';
  html += '<div id="' + settings.id + '" class="edit-toolbar-container">';
  html += '  <div class="edit-toolbar-heightfaker edit-animate-fast">';
  html += '    <div class="edit-toolbar primary" />';
  html += '  </div>';
  html += '</div>';
  return html;
};

/**
 * Theme function for a toolbar toolgroup of the Edit module.
 *
 * @param Object settings
 *   An object with the following keys:
 *   - String id: (optional) the id of the toolgroup
 *   - String classes: the class of the toolgroup.
 *   - Array buttons: @see Drupal.theme.prototype.editButtons().
 * @return String
 *   The corresponding HTML.
 */
Drupal.theme.editToolgroup = function (settings) {
  var classes = 'edit-toolgroup edit-animate-slow edit-animate-invisible edit-animate-delay-veryfast';
  var html = '';
  html += '<div class="' + classes + ' ' + settings.classes + '"';
  if (settings.id) {
    html += ' id="' + settings.id + '"';
  }
  html += '>';
  html += Drupal.theme('editButtons', { buttons: settings.buttons });
  html += '</div>';
  return html;
};

/**
 * Theme function for buttons of the Edit module.
 *
 * Can be used for the buttons both in the toolbar toolgroups and in the modal.
 *
 * @param Object settings
 *   An object with the following keys:
 *   - buttons: an array of objects with the following keys:
 *     - String type: the type of the button (defaults to 'button')
 *     - Array classes: the classes of the button.
 *     - String label: the label of the button.
 *     - String action: sets a data-edit-modal-action attribute.
 * @return String
 *   The corresponding HTML.
 */
Drupal.theme.editButtons = function (settings) {
  var html = '';
  for (var i = 0; i < settings.buttons.length; i++) {
    var button = settings.buttons[i];
    if (!button.hasOwnProperty('type')) {
      button.type = 'button';
    }

    html += '<button type="' + button.type + '" class="' + button.classes + '"';
    html += (button.action) ? ' data-edit-modal-action="' + button.action + '"' : '';
    html += '>';
    html +=    button.label;
    html += '</button>';
  }
  return html;
};

/**
 * Theme function for a form container of the Edit module.
 *
 * @param Object settings
 *   An object with the following keys:
 *   - String id: the id to apply to the toolbar container.
 *   - String loadingMsg: The message to show while loading.
 * @return String
 *   The corresponding HTML.
 */
Drupal.theme.editFormContainer = function (settings) {
  var html = '';
  html += '<div id="' + settings.id + '" class="edit-form-container">';
  html += '  <div class="edit-form">';
  html += '    <div class="placeholder">';
  html +=        settings.loadingMsg;
  html += '    </div>';
  html += '  </div>';
  html += '</div>';
  return html;
};

})(jQuery, Drupal);
