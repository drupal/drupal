/**
 * @file
 * Provides theme functions for all of Quick Edit's client-side HTML.
 */

(function ($, Drupal) {
  /**
   * Theme function for a "backstage" for the Quick Edit module.
   *
   * @param {object} settings
   *   Settings object used to construct the markup.
   * @param {string} settings.id
   *   The id to apply to the backstage.
   *
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.quickeditBackstage = function (settings) {
    let html = '';
    html += `<div id="${settings.id}"></div>`;
    return html;
  };

  /**
   * Theme function for a toolbar container of the Quick Edit module.
   *
   * @param {object} settings
   *   Settings object used to construct the markup.
   * @param {string} settings.id
   *   the id to apply to the backstage.
   *
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.quickeditEntityToolbar = function (settings) {
    let html = '';
    html += `<div id="${settings.id}" class="quickedit quickedit-toolbar-container clearfix">`;
    html += '<i class="quickedit-toolbar-pointer"></i>';
    html += '<div class="quickedit-toolbar-content">';
    html +=
      '<div class="quickedit-toolbar quickedit-toolbar-entity clearfix icon icon-pencil">';
    html += '<div class="quickedit-toolbar-label"></div>';
    html += '</div>';
    html +=
      '<div class="quickedit-toolbar quickedit-toolbar-field clearfix"></div>';
    html += '</div><div class="quickedit-toolbar-lining"></div></div>';
    return html;
  };

  /**
   * Theme function for a toolbar container of the Quick Edit module.
   *
   * @param {object} settings
   *   Settings object used to construct the markup.
   * @param {string} settings.entityLabel
   *   The title of the active entity.
   * @param {string} settings.fieldLabel
   *   The label of the highlighted or active field.
   *
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.quickeditEntityToolbarLabel = function (settings) {
    // @todo Add XSS regression test coverage in https://www.drupal.org/node/2547437
    return `<span class="field">${Drupal.checkPlain(
      settings.fieldLabel,
    )}</span>${Drupal.checkPlain(settings.entityLabel)}`;
  };

  /**
   * Element defining a containing box for the placement of the entity toolbar.
   *
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.quickeditEntityToolbarFence = function () {
    return '<div id="quickedit-toolbar-fence"></div>';
  };

  /**
   * Theme function for a toolbar container of the Quick Edit module.
   *
   * @param {object} settings
   *   Settings object used to construct the markup.
   * @param {string} settings.id
   *   The id to apply to the toolbar container.
   *
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.quickeditFieldToolbar = function (settings) {
    return `<div id="${settings.id}"></div>`;
  };

  /**
   * Theme function for a toolbar toolgroup of the Quick Edit module.
   *
   * @param {object} settings
   *   Settings object used to construct the markup.
   * @param {string} [settings.id]
   *   The id of the toolgroup.
   * @param {string} settings.classes
   *   The class of the toolgroup.
   * @param {Array} settings.buttons
   *   See {@link Drupal.theme.quickeditButtons}.
   *
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.quickeditToolgroup = function (settings) {
    // Classes.
    const classes = settings.classes || [];
    classes.unshift('quickedit-toolgroup');
    let html = '';
    html += `<div class="${classes.join(' ')}"`;
    if (settings.id) {
      html += ` id="${settings.id}"`;
    }
    html += '>';
    html += Drupal.theme('quickeditButtons', { buttons: settings.buttons });
    html += '</div>';
    return html;
  };

  /**
   * Theme function for buttons of the Quick Edit module.
   *
   * Can be used for the buttons both in the toolbar toolgroups and in the
   * modal.
   *
   * @param {object} settings
   *   Settings object used to construct the markup.
   * @param {Array} settings.buttons
   * - String type: the type of the button (defaults to 'button')
   * - Array classes: the classes of the button.
   * - String label: the label of the button.
   *
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.quickeditButtons = function (settings) {
    let html = '';
    for (let i = 0; i < settings.buttons.length; i++) {
      const button = settings.buttons[i];
      if (!button.hasOwnProperty('type')) {
        button.type = 'button';
      }
      // Attributes.
      const attributes = [];
      const attrMap = settings.buttons[i].attributes || {};
      Object.keys(attrMap).forEach((attr) => {
        attributes.push(attr + (attrMap[attr] ? `="${attrMap[attr]}"` : ''));
      });
      html += `<button type="${button.type}" class="${
        button.classes
      }" ${attributes.join(' ')}>${button.label}</button>`;
    }
    return html;
  };

  /**
   * Theme function for a form container of the Quick Edit module.
   *
   * @param {object} settings
   *   Settings object used to construct the markup.
   * @param {string} settings.id
   *   The id to apply to the toolbar container.
   * @param {string} settings.loadingMsg
   *   The message to show while loading.
   *
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.quickeditFormContainer = function (settings) {
    let html = '';
    html += `<div id="${settings.id}" class="quickedit-form-container">`;
    html += '  <div class="quickedit-form">';
    html += '    <div class="placeholder">';
    html += settings.loadingMsg;
    html += '    </div>';
    html += '  </div>';
    html += '</div>';
    return html;
  };

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
  Drupal.theme.quickeditImageErrors = function (settings) {
    return `<div class="quickedit-image-errors">${settings.errors}</div>`;
  };

  /**
   * Theme function for the dropzone element of the in-place editor.
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
  Drupal.theme.quickeditImageDropzone = function (settings) {
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
  Drupal.theme.quickeditImageToolbar = function (settings) {
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
})(jQuery, Drupal);
