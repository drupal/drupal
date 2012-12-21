/**
 * @file
 * Determines which editor to use based on a class attribute.
 */
(function (jQuery, drupalSettings) {

"use strict";

  jQuery.widget('Drupal.createEditable', jQuery.Midgard.midgardEditable, {
    _create: function() {
      this.vie = this.options.vie;

      this.options.domService = 'edit';
      this.options.predicateSelector = '*'; //'.edit-field.edit-allowed';

      this.options.editors.direct = {
        widget: 'drupalContentEditableWidget',
        options: {}
      };
      this.options.editors['direct-with-wysiwyg'] = {
        widget: drupalSettings.edit.wysiwygEditorWidgetName,
        options: {}
      };
      this.options.editors.form = {
        widget: 'drupalFormWidget',
        options: {}
      };

      jQuery.Midgard.midgardEditable.prototype._create.call(this);
    },

    _propertyEditorName: function(data) {
      if (jQuery(this.element).hasClass('edit-type-direct')) {
        if (jQuery(this.element).hasClass('edit-type-direct-with-wysiwyg')) {
          return 'direct-with-wysiwyg';
        }
        return 'direct';
      }
      return 'form';
    }
  });

})(jQuery, drupalSettings);
