/**
 * @file
 * Provide warnings when attempting to load CKEditor 5 on IE11.
 */

((Drupal, Modernizr) => {
  const isIE11 = Modernizr.mq(
    '(-ms-high-contrast: active), (-ms-high-contrast: none)',
  );

  // If the browser is IE11, create an alternate version of
  // Drupal.editors.ckeditor5 that provides warnings. In IE11, the incompatible
  // Javascript of CKEditor 5 prevents Drupal.editors.ckeditor5 from being
  // created. Features such as Quick Edit that require the presence of a
  // Drupal.editors.ckeditor5, even for fields that do not use CKEditor 5.
  if (isIE11) {
    // This will reference a MutationObserver used by several functions in
    // Drupal.editors.ckeditor5. It is declared here and not the editor object
    // in order to work with IE11 object scope.
    let quickEditLabelObserver = null;

    /**
     * @namespace
     */
    Drupal.editors.ckeditor5 = {
      /**
       * Provides a warning when trying to attach CKEditor 5 to a field in IE11.
       *
       * @param {HTMLElement} element
       *   The element the editor would have been attached to.
       */
      attach: function attach(element) {
        // Add a Drupal.Message container above the textarea and use it to
        // provide a warning message to IE11 users.
        const editorMessageContainer = document.createElement('div');
        element.parentNode.insertBefore(editorMessageContainer, element);
        const editorMessages = new Drupal.Message(editorMessageContainer);
        editorMessages.add(
          Drupal.t(
            'Internet Explorer 11 user: a rich text editor is available for this field when used with any other supported browser.',
          ),
          {
            type: 'warning',
          },
        );
      },

      /**
       * Editor detach callback.
       */
      detach: function detach() {
        const quickEditToolbar = document.querySelector(
          '#quickedit-entity-toolbar .quickedit-toolbar',
        );
        if (quickEditToolbar) {
          // Remove classes that style the Quick Edit toolbar as a warning.
          quickEditToolbar.classList.remove('ck5-ie11');
          quickEditToolbar.classList.add('icon-pencil');

          // Disconnect the observer from the Quick Edit label that converts the
          // label to a warning. A warning isn't needed for fields not using
          // CKEditor 5.
          quickEditLabelObserver.disconnect();
        }
      },

      /**
       * Registers a callback which CKEditor 5 will call on change:data event.
       */
      onChange: function onChange() {
        // Intentionally empty.
      },

      /**
       * Provides a warning when Quick Edit tries to attach CKEditor 5 in IE11.
       *
       * @param {HTMLElement} element
       *   The element the editor would have been attached to.
       *
       * @see Drupal.quickedit.editors.editor
       */
      attachInlineEditor: function attachInlineEditor(element) {
        const quickEditToolbar = document.querySelector(
          '#quickedit-entity-toolbar .quickedit-toolbar',
        );

        const notEditableAlert = Drupal.t('Field Not Editable');
        const notEditableMessage = Drupal.t(
          'CKEditor 5 is not compatible with IE11.',
        );

        /**
         * Changes the Quick Edit label to a warning.
         *
         * @param {Element} toolbarLabel
         *   The Quick Edit toolbar label element.
         */
        function quickEditLabelWarnIE11(toolbarLabel) {
          // Disconnect the observer to prevent infinite recursion.
          quickEditLabelObserver.disconnect();

          // Change the quickEdit toolbar label to a warning that informs
          // IE11 users that they can't edit the field due to CKEditor not
          // working with IE11.
          toolbarLabel.innerHTML = `<div><b>${notEditableAlert}</b><div>${notEditableMessage}</div></div>`;
          quickEditLabelObserver.observe(toolbarLabel, { childList: true });
        }

        if (quickEditToolbar) {
          quickEditToolbar.classList.add('ck5-ie11');
          quickEditToolbar.classList.remove('icon-pencil');
          element.classList.add('ck5-ie11');

          // Prepare variables that will be used for changing the QuickEdit
          // toolbar label to a warning.
          const toolbarLabel = quickEditToolbar.querySelector(
            '.quickedit-toolbar-label',
          );

          // Updating the Quick Edit label to display as a warning is triggered
          // via an observer. An observer us used as there are multiple
          // Quick Edit Backbone View events that alter the contents of this
          // label. An observer is used to guarantee the warning persists
          // without having to override multiple Backbone event handlers.
          quickEditLabelObserver = new MutationObserver((mutations) => {
            for (let i = 0; i < mutations.length; i++) {
              if (mutations[i].type === 'childList') {
                quickEditLabelWarnIE11(toolbarLabel);
              }
            }
          });
          quickEditLabelObserver.observe(toolbarLabel, { childList: true });
        }
      },
    };
  }
})(Drupal, Modernizr);
