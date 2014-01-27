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
   * Theme function for a toolbar container of the Edit module.
   *
   * @param Object settings
   *   An object with the following keys:
   *   - String id: the id to apply to the toolbar container.
   * @return String
   *   The corresponding HTML.
   */
  Drupal.theme.editEntityToolbar = function (settings) {
    var html = '';
    html += '<div id="' + settings.id + '" class="edit edit-toolbar-container clearfix">';
    html += '<i class="edit-toolbar-pointer"></i>';
    html += '<div class="edit-toolbar-content">';
    html += '<div class="edit-toolbar edit-toolbar-entity clearfix icon icon-pencil">';
    html += '<div class="edit-toolbar-label" />';
    html += '</div>';
    html += '<div class="edit-toolbar edit-toolbar-field clearfix" />';
    html += '</div><div class="edit-toolbar-lining"></div></div>';
    return html;
  };

  /**
   * Theme function for a toolbar container of the Edit module.
   *
   * @param Object settings
   *   An object with the following keys:
   *   - String entityLabel: The title of the active entity.
   *   - String fieldLabel: The label of the highlighted or active field.
   * @return String
   *   The corresponding HTML.
   */
  Drupal.theme.editEntityToolbarLabel = function (settings) {
    return '<span class="field">' + settings.fieldLabel + '</span>' + settings.entityLabel;
  };

  /**
   * Element that defines a containing box of the placement of the entity toolbar.
   *
   * @return String
   *   The corresponding HTML.
   */
  Drupal.theme.editEntityToolbarFence = function () {
    return '<div id="edit-toolbar-fence" />';
  };

  /**
   * Theme function for a toolbar container of the Edit module.
   *
   * @param settings
   *   An object with the following keys:
   *   - id: the id to apply to the toolbar container.
   * @return
   *   The corresponding HTML.
   */
  Drupal.theme.editFieldToolbar = function (settings) {
    return '<div id="' + settings.id + '" />';
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
    // Classes.
    var classes = (settings.classes || []);
    classes.unshift('edit-toolgroup');
    var html = '';
    html += '<div class="' + classes.join(' ') + '"';
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
      // Attributes.
      var attributes = [];
      var attrMap = settings.buttons[i].attributes || {};
      for (var attr in attrMap) {
        if (attrMap.hasOwnProperty(attr)) {
          attributes.push(attr + ((attrMap[attr]) ? '="' + attrMap[attr] + '"' : '' ));
        }
      }
      html += '<button type="' + button.type + '" class="' + button.classes + '"' + ' ' + attributes.join(' ') + '>';
      html += button.label;
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
    html += settings.loadingMsg;
    html += '    </div>';
    html += '  </div>';
    html += '</div>';
    return html;
  };

})(jQuery, Drupal);
