/**
 * @file
 * Provides theme functions for image Quick Edit's client-side HTML.
 */

(function(Drupal) {
  /**
   * Theme function for validation errors of the Image in-place editor.
   *
   * @param {object} settings
   *   Settings object used to construct the markup.
   * @param {string} settings.errors
   *   Already escaped HTML representing error messages.
   *
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.quickeditImageErrors = function(settings) {
    return `<div class="quickedit-image-errors">${settings.errors}</div>`;
  };

  /**
   * Theme function for the dropzone element of the Image module's in-place
   * editor.
   *
   * @param {object} settings
   *   Settings object used to construct the markup.
   * @param {string} settings.state
   *   State of the upload.
   * @param {string} settings.text
   *   Text to display inline with the dropzone element.
   *
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.quickeditImageDropzone = function(settings) {
    return (
      `<div class="quickedit-image-dropzone ${settings.state}">` +
      '  <i class="quickedit-image-icon"></i>' +
      `  <span class="quickedit-image-text">${settings.text}</span>` +
      '</div>'
    );
  };

  /**
   * Theme function for the toolbar of the Image module's in-place editor.
   *
   * @param {object} settings
   *   Settings object used to construct the markup.
   * @param {bool} settings.alt_field
   *   Whether or not the "Alt" field is enabled for this field.
   * @param {bool} settings.alt_field_required
   *   Whether or not the "Alt" field is required for this field.
   * @param {string} settings.alt
   *   The current value for the "Alt" field.
   * @param {bool} settings.title_field
   *   Whether or not the "Title" field is enabled for this field.
   * @param {bool} settings.title_field_required
   *   Whether or not the "Title" field is required for this field.
   * @param {string} settings.title
   *   The current value for the "Title" field.
   *
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.quickeditImageToolbar = function(settings) {
    let html = '<form class="quickedit-image-field-info">';
    if (settings.alt_field) {
      html +=
        `<div><label for="alt" class="${
          settings.alt_field_required ? 'required' : ''
        }">${Drupal.t('Alternative text')}</label>` +
        `<input type="text" placeholder="${settings.alt}" value="${
          settings.alt
        }" name="alt" ${settings.alt_field_required ? 'required' : ''}/>` +
        '  </div>';
    }
    if (settings.title_field) {
      html +=
        `<div><label for="title" class="${
          settings.title_field_required ? 'form-required' : ''
        }">${Drupal.t('Title')}</label>` +
        `<input type="text" placeholder="${settings.title}" value="${
          settings.title
        }" name="title" ${settings.title_field_required ? 'required' : ''}/>` +
        '</div>';
    }
    html += '</form>';

    return html;
  };
})(Drupal);
