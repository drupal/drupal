/**
 * @file
 * CKEditor 5 implementation of {@link Drupal.editors} API.
 */
/* global CKEditor5 */
((Drupal, debounce, CKEditor5, $) => {
  /**
   * The CKEDITOR instances.
   *
   * @type {Map}
   */
  Drupal.CKEditor5Instances = new Map();

  /**
   * The callback functions.
   *
   * @type {Map}
   */
  const callbacks = new Map();

  /**
   * List of element ids with the required attribute.
   *
   * @type {Set}
   */
  const required = new Set();

  function findFunc(scope, name) {
    if (!scope) {
      return null;
    }
    const parts = name.includes('.') ? name.split('.') : name;

    if (parts.length > 1) {
      return findFunc(scope[parts.shift()], parts);
    }
    return typeof scope[parts[0]] === 'function' ? scope[parts[0]] : null;
  }

  function buildFunc(config) {
    const { func } = config;
    // Assuming a global object.
    const fn = findFunc(window, func.name);
    if (typeof fn === 'function') {
      const result = func.invoke ? fn(...func.args) : fn;
      return result;
    }
    return null;
  }

  /**
   * Converts a string representing regexp to a RegExp object.
   *
   * @param {Object} config
   *   An object containing configuration.
   * @param {string} config.pattern
   *   The regexp pattern that is used to create the RegExp object.
   *
   * @return {RegExp}
   *   Regexp object built from the string regexp.
   */
  function buildRegexp(config) {
    const { pattern } = config.regexp;

    const main = pattern.match(/\/(.+)\/.*/)[1];
    const options = pattern.match(/\/.+\/(.*)/)[1];

    return new RegExp(main, options);
  }

  /**
   * Casts configuration items to correct types.
   *
   * @param {Object} config
   *   The config object.
   * @return {Object}
   *   The config object with items transformed to correct type.
   */
  function processConfig(config) {
    /**
     * Processes an array in config recursively.
     *
     * @param {Array} config
     *   An array that should be processed recursively.
     * @return {Array}
     *   An array that has been processed recursively.
     */
    function processArray(config) {
      return config.map((item) => {
        if (typeof item === 'object') {
          return processConfig(item);
        }

        return item;
      });
    }

    return Object.entries(config).reduce((processed, [key, value]) => {
      if (typeof value === 'object') {
        if (value.hasOwnProperty('func')) {
          processed[key] = buildFunc(value);
        } else if (value.hasOwnProperty('regexp')) {
          processed[key] = buildRegexp(value);
        } else if (Array.isArray(value)) {
          processed[key] = processArray(value);
        } else {
          processed[key] = processConfig(value);
        }
      } else {
        processed[key] = value;
      }

      return processed;
    }, {});
  }

  /**
   * Set an id to a data-attribute for registering this element instance.
   *
   * @param {Element} element
   *   An element that should receive unique ID.
   *
   * @return {string}
   *   The id to use for this element.
   */
  const setElementId = (element) => {
    const id = Math.random().toString().slice(2, 9);
    element.setAttribute('data-ckeditor5-id', id);

    return id;
  };

  /**
   * Return a unique selector for the element.
   *
   * @param {HTMLElement} element
   *   An element which unique ID should be retrieved.
   *
   * @return {string}
   *   The id to use for this element.
   */
  const getElementId = (element) => element.getAttribute('data-ckeditor5-id');

  /**
   * Select CKEditor5 plugin classes to include.
   *
   * Found in the CKEditor5 global js object as {package.Class}.
   *
   * @param {Array} plugins
   *  List of package and Class name of plugins
   *
   * @return {Array}
   *   List of JavaScript Classes to add in the extraPlugins property of config.
   */
  function selectPlugins(plugins) {
    return plugins.map((pluginDefinition) => {
      const [build, name] = pluginDefinition.split('.');
      if (CKEditor5[build] && CKEditor5[build][name]) {
        return CKEditor5[build][name];
      }

      // eslint-disable-next-line no-console
      console.warn(`Failed to load ${build} - ${name}`);
      return null;
    });
  }

  /**
   * Adds CSS to ensure proper styling of CKEditor 5 inside off-canvas dialogs.
   *
   * @param {HTMLElement} element
   *   The element the editor is attached to.
   */
  const offCanvasCss = (element) => {
    element.parentNode.setAttribute('data-drupal-ck-style-fence', true);

    // Only proceed if the styles haven't been added yet.
    if (!document.querySelector('#ckeditor5-off-canvas-reset')) {
      const prefix = `#drupal-off-canvas [data-drupal-ck-style-fence]`;
      let existingCss = '';

      // Find every existing style that doesn't come from off-canvas resets and
      // copy them to new styles with a prefix targeting CKEditor inside an
      // off-canvas dialog.
      [...document.styleSheets].forEach((sheet) => {
        if (
          !sheet.href ||
          (sheet.href && sheet.href.indexOf('off-canvas') === -1)
        ) {
          // This is wrapped in a try/catch as Chromium browsers will fail if
          // the stylesheet was provided via a CORS request.
          // @see https://bugs.chromium.org/p/chromium/issues/detail?id=775525
          try {
            const rules = sheet.cssRules;
            [...rules].forEach((rule) => {
              let { cssText } = rule;
              const selector = rule.cssText.split('{')[0];

              // Prefix all selectors added after a comma.
              cssText = cssText.replace(
                selector,
                selector.replace(/,/g, `, ${prefix}`),
              );

              // When adding to existingCss, prefix the first selector as well.
              existingCss += `${prefix} ${cssText}`;
            });
          } catch (e) {
            // eslint-disable-next-line no-console
            console.warn(
              `Stylesheet ${sheet.href} not included in CKEditor reset due to the browser's CORS policy.`,
            );
          }
        }
      });

      // Additional styles that need to be explicity added in addition to the
      // prefixed versions of existing css in `existingCss`.
      const addedCss = [
        `${prefix} .ck.ck-content {display:block;min-height:5rem;}`,
        `${prefix} .ck.ck-content * {display:initial;background:initial;color:initial;padding:initial;}`,
        `${prefix} .ck.ck-content li {display:list-item}`,
        `${prefix} .ck.ck-content ol li {list-style-type: decimal}`,
        `${prefix} .ck[contenteditable], ${prefix} .ck[contenteditable] * {-webkit-user-modify: read-write;-moz-user-modify: read-write;}`,
      ];

      // Styles to ensure block elements are displayed as such inside
      // off-canvas dialogs. These are all element types that are styled with
      // ` all: initial;` in the off-canvas reset that should default to being
      // displayed as blocks within CKEditor.
      // @see core/misc/dialog/off-canvas.reset.pcss.css
      const blockSelectors = [
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'p',
        'ol',
        'ul',
        'address',
        'article',
        'aside',
        'blockquote',
        'body',
        'dd',
        'div',
        'dl',
        'dt',
        'fieldset',
        'figcaption',
        'figure',
        'footer',
        'form',
        'header',
        'hgroup',
        'hr',
        'html',
        'legend',
        'main',
        'menu',
        'pre',
        'section',
        'xmp',
      ]
        .map((blockElement) => `${prefix} .ck.ck-content ${blockElement}`)
        .join(', \n');
      const blockCss = `${blockSelectors} { display: block; }`;

      const prefixedCss = [...addedCss, existingCss, blockCss].join('\n');

      // Create a new style tag with the prefixed styles added above.
      const offCanvasCss = document.createElement('style');
      offCanvasCss.innerHTML = prefixedCss;
      offCanvasCss.setAttribute('id', 'ckeditor5-off-canvas-reset');
      document.body.appendChild(offCanvasCss);
    }
  };

  /**
   * @namespace
   */
  Drupal.editors.ckeditor5 = {
    /**
     * Editor attach callback.
     *
     * @param {HTMLElement} element
     *   The element to attach the editor to.
     * @param {string} format
     *   The text format for the editor.
     */
    attach(element, format) {
      const { editorClassic } = CKEditor5;
      const {
        toolbar,
        plugins,
        config: pluginConfig,
        language,
      } = format.editorSettings;
      const extraPlugins = selectPlugins(plugins);

      const config = {
        extraPlugins,
        toolbar,
        language,
        ...processConfig(pluginConfig),
      };
      // Set the id immediately so that it is available when onChange is called.
      const id = setElementId(element);
      const { ClassicEditor } = editorClassic;

      ClassicEditor.create(element, config)
        .then((editor) => {
          // Save a reference to the initialized instance.
          Drupal.CKEditor5Instances.set(id, editor);

          // CKEditor4 had a feature to remove the required attribute
          // see: https://www.drupal.org/project/drupal/issues/1954968
          if (element.hasAttribute('required')) {
            required.add(id);
            element.removeAttribute('required');
          }

          editor.model.document.on('change:data', () => {
            const callback = callbacks.get(id);
            if (callback) {
              if (editor.plugins.has('SourceEditing')) {
                // If the change:data is being called while in source editing
                // mode, it means that the form is being submitted. To avoid
                // race conditions, in this case the callback gets called
                // without decorating the callback with debounce.
                // @see https://www.drupal.org/i/3229174
                // @see Drupal.editorDetach
                if (editor.plugins.get('SourceEditing').isSourceEditingMode) {
                  callback();
                  return;
                }
              }

              // Marks the field as changed.
              // @see Drupal.editorAttach
              debounce(callback, 400)();
            }
          });

          const isOffCanvas = element.closest('#drupal-off-canvas');

          if (isOffCanvas) {
            offCanvasCss(element);
          }
        })
        .catch((error) => {
          // eslint-disable-next-line no-console
          console.error(error);
        });
    },

    /**
     * Editor detach callback.
     *
     * @param {HTMLElement} element
     *   The element to detach the editor from.
     * @param {string} format
     *   The text format used for the editor.
     * @param {string} trigger
     *   The event trigger for the detach.
     */
    detach(element, format, trigger) {
      const id = getElementId(element);
      const editor = Drupal.CKEditor5Instances.get(id);
      if (!editor) {
        return;
      }
      if (trigger === 'serialize') {
        editor.updateSourceElement();
      } else {
        element.removeAttribute('contentEditable');

        // Prepare variables that will be used when discarding Quickedit changes.
        let textElement = null;
        let originalValue = null;
        const usingQuickEdit = (((Drupal || {}).quickedit || {}).editors || {})
          .editor;
        if (usingQuickEdit) {
          // The revert() function in QuickEdit's text editor does not work with
          // CKEditor 5, as it is triggered before CKEditor 5 is fully
          // destroyed. This function is overridden so the functionality it
          // provides can happen after the CKEditor destroy() promise is
          // fulfilled.
          // This pulls the necessary values from the QuickEdit Backbone Model
          // before it is destroyed, so they can be used by
          // `editor.destroy().then()` to perform the expected revert.
          Drupal.quickedit.editors.editor.prototype.revert =
            function revertQuickeditChanges() {
              textElement = this.$textElement[0];
              originalValue = this.model.get('originalValue');
            };
        }

        editor
          .destroy()
          .then(() => {
            // If textElement and originalValue are not null, a QuickEdit
            // revert has been requested. Perform the revert here as it
            // can't happen until the CKEditor instance is destroyed.
            if (textElement && originalValue) {
              textElement.innerHTML = originalValue;
            }

            // Clean up stored references.
            Drupal.CKEditor5Instances.delete(id);
            callbacks.delete(id);
            if (required.has(id)) {
              element.setAttribute('required', 'required');
              required.delete(id);
            }
          })
          .catch((error) => {
            // eslint-disable-next-line no-console
            console.error(error);
          });
      }
    },

    /**
     * Registers a callback which CKEditor5 will call on change:data event.
     *
     * @param {HTMLElement} element
     *   The element where the change occurred.
     * @param {function} callback
     *   Callback called with the value of the editor.
     */
    onChange(element, callback) {
      callbacks.set(getElementId(element), callback);
    },

    /**
     * Attaches an inline editor to a DOM element.
     *
     * @param {HTMLElement} element
     *   The element to attach the editor to.
     * @param {object} format
     *   The text format used in the editor.
     * @param {string} [mainToolbarId]
     *   The id attribute for the main editor toolbar, if any.
     *
     * @see Drupal.quickedit.editors.editor
     */
    attachInlineEditor(element, format, mainToolbarId) {
      const { editorDecoupled } = CKEditor5;
      const {
        toolbar,
        plugins,
        config: pluginConfig,
        language,
      } = format.editorSettings;
      const extraPlugins = selectPlugins(plugins);
      const config = {
        extraPlugins,
        toolbar,
        language,
        ...processConfig(pluginConfig),
      };
      const id = setElementId(element);
      const { DecoupledEditor } = editorDecoupled;

      DecoupledEditor.create(element, config)
        .then((editor) => {
          Drupal.CKEditor5Instances.set(id, editor);
          const toolbar = document.getElementById(mainToolbarId);
          toolbar.appendChild(editor.ui.view.toolbar.element);
          editor.model.document.on('change:data', () => {
            const callback = callbacks.get(id);
            if (callback) {
              // Quick Edit requires the current data to update EditorModel.
              // @see Drupal.quickedit.editors.editor
              debounce(callback, 400)(editor.getData());
            }
          });
        })
        .catch((error) => {
          // eslint-disable-next-line no-console
          console.error(error);
        });
    },
  };

  Drupal.ckeditor5 = {
    /**
     * Variable storing the current dialog's save callback.
     *
     * @type {?function}
     */
    saveCallback: null,

    openDialog(url, saveCallback, dialogSettings) {
      // Add a consistent dialog class.
      const classes = dialogSettings.dialogClass
        ? dialogSettings.dialogClass.split(' ')
        : [];
      classes.push('ui-dialog--narrow');
      dialogSettings.dialogClass = classes.join(' ');
      dialogSettings.autoResize =
        window.matchMedia('(min-width: 600px)').matches;
      dialogSettings.width = 'auto';

      const $content = $(
        `<div class="ckeditor5-dialog-loading"><span style="top: -40px;" class="ckeditor5-dialog-loading-link">${Drupal.t(
          'Loading...',
        )}</span></div>`,
      );
      $content.appendTo($('body'));

      const ckeditorAjaxDialog = Drupal.ajax({
        dialog: dialogSettings,
        dialogType: 'modal',
        selector: '.ckeditor5-dialog-loading-link',
        url,
        progress: { type: 'throbber' },
        submit: {
          editor_object: {},
        },
      });
      ckeditorAjaxDialog.execute();

      // After a short delay, show "Loading…" message.
      window.setTimeout(() => {
        $content.find('span').animate({ top: '0px' });
      }, 1000);

      // Store the save callback to be executed when this dialog is closed.
      Drupal.ckeditor5.saveCallback = saveCallback;
    },
  };

  // Respond to new dialogs that are opened by CKEditor, closing the AJAX loader.
  $(window).on('dialog:beforecreate', () => {
    $('.ckeditor5-dialog-loading').animate(
      { top: '-40px' },
      function removeDialogLoading() {
        $(this).remove();
      },
    );
  });

  // Respond to dialogs that are saved, sending data back to CKEditor.
  $(window).on('editor:dialogsave', (e, values) => {
    if (Drupal.ckeditor5.saveCallback) {
      Drupal.ckeditor5.saveCallback(values);
    }
  });

  // Respond to dialogs that are closed, removing the current save handler.
  $(window).on('dialog:afterclose', () => {
    if (Drupal.ckeditor5.saveCallback) {
      Drupal.ckeditor5.saveCallback = null;
    }
  });
})(Drupal, Drupal.debounce, CKEditor5, jQuery);
