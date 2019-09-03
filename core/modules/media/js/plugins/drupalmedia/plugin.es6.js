/**
 * @file
 * Drupal Media embed plugin.
 */

(function(jQuery, Drupal, CKEDITOR) {
  /**
   * Gets the focused widget, if of the type specific for this plugin.
   *
   * @param {CKEDITOR.editor} editor
   *   A CKEditor instance.
   *
   * @return {?CKEDITOR.plugins.widget}
   *   The focused drupalmedia widget instance, or null.
   */
  function getFocusedWidget(editor) {
    const widget = editor.widgets.focused;

    if (widget && widget.name === 'drupalmedia') {
      return widget;
    }
    return null;
  }

  /**
   * Makes embedded items linkable by integrating with the drupallink plugin.
   *
   * @param {CKEDITOR.editor} editor
   *   A CKEditor instance.
   */
  function linkCommandIntegrator(editor) {
    if (!editor.plugins.drupallink) {
      return;
    }

    CKEDITOR.plugins.drupallink.registerLinkableWidget('drupalmedia');

    editor.getCommand('drupalunlink').on('exec', function(evt) {
      const widget = getFocusedWidget(editor);

      if (!widget) {
        return;
      }

      widget.setData('link', null);

      this.refresh(editor, editor.elementPath());

      evt.cancel();
    });

    editor.getCommand('drupalunlink').on('refresh', function(evt) {
      const widget = getFocusedWidget(editor);

      if (!widget) {
        return;
      }

      this.setState(
        widget.data.link ? CKEDITOR.TRISTATE_OFF : CKEDITOR.TRISTATE_DISABLED,
      );

      evt.cancel();
    });

    // Register context menu items for editing link.
    if (editor.contextMenu) {
      editor.contextMenu.addListener(() => {
        const widget = getFocusedWidget(editor);

        if (!widget) {
          return;
        }

        if (widget.data.link) {
          return {
            link: CKEDITOR.TRISTATE_OFF,
            unlink: CKEDITOR.TRISTATE_OFF,
          };
        }
        return {};
      });
    }
  }

  /**
   * Themes the error displayed when the media embed preview fails.
   *
   * @return {string}
   *   An HTML string to insert in the CKEditor.
   */
  Drupal.theme.mediaEmbedError = function() {
    const error = Drupal.t(
      'An error occurred while trying to preview the media. Please save your work and reload this page.',
    );
    return `<div class="media-embed-error media-embed-error--preview-error">${error}</div>`;
  };

  /**
   * Themes the edit button for a media embed.
   *
   * @return {string}
   *   An HTML string to insert in the CKEditor.
   */
  Drupal.theme.mediaEmbedEditButton = function() {
    return `<button class="media-library-item__edit">${Drupal.t(
      'Edit media',
    )}</button>`;
  };

  CKEDITOR.plugins.add('drupalmedia', {
    requires: 'widget',

    beforeInit(editor) {
      // Configure CKEditor DTD for custom drupal-media element.
      // @see https://www.drupal.org/node/2448449#comment-9717735
      const { dtd } = CKEDITOR;
      // Allow text within the drupal-media tag.
      dtd['drupal-media'] = { '#': 1 };
      // Register drupal-media element as an allowed child in each tag that can
      // contain a div element and as an allowed child of the a tag.
      Object.keys(dtd).forEach(tagName => {
        if (dtd[tagName].div) {
          dtd[tagName]['drupal-media'] = 1;
        }
      });
      dtd.a['drupal-media'] = 1;

      editor.widgets.add('drupalmedia', {
        allowedContent: {
          'drupal-media': {
            attributes: {
              '!data-entity-type': true,
              '!data-entity-uuid': true,
              'data-align': true,
              'data-caption': true,
              alt: true,
              title: true,
            },
            classes: {},
          },
        },
        // Minimum HTML which is required by this widget to work.
        // This does not use the object format used above, but a
        // CKEDITOR.style instance, because requiredContent does not support
        // the object format.
        // @see https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_filter_contentRule.html
        requiredContent: new CKEDITOR.style({
          element: 'drupal-media',
          attributes: {
            'data-entity-type': '',
            'data-entity-uuid': '',
          },
        }),

        pathName: Drupal.t('Embedded media'),

        editables: {
          caption: {
            selector: 'figcaption',
            allowedContent: 'a[!href]; em strong cite code br',
            pathName: Drupal.t('Caption'),
          },
        },

        upcast(element, data) {
          const { attributes } = element;
          // This matches the behavior of the corresponding server-side text filter plugin.
          if (
            element.name !== 'drupal-media' ||
            attributes['data-entity-type'] !== 'media' ||
            attributes['data-entity-uuid'] === undefined
          ) {
            return;
          }
          data.attributes = CKEDITOR.tools.copy(attributes);
          data.hasCaption = data.attributes.hasOwnProperty('data-caption');
          // Add space to the empty caption to allow the server-side text
          // filter to render a caption, allowing the placeholder-rendering
          // CSS to work.
          if (data.hasCaption && data.attributes['data-caption'] === '') {
            data.attributes['data-caption'] = ' ';
          }
          data.link = null;
          if (element.parent.name === 'a') {
            data.link = CKEDITOR.tools.copy(element.parent.attributes);
            // Omit CKEditor-internal attributes.
            Object.keys(element.parent.attributes).forEach(attrName => {
              if (attrName.indexOf('data-cke-') !== -1) {
                delete data.link[attrName];
              }
            });
          }
          // @see media_field_widget_form_alter()
          const hostEntityLangcode = document
            .getElementById(editor.name)
            .getAttribute('data-media-embed-host-entity-langcode');
          if (hostEntityLangcode) {
            data.hostEntityLangcode = hostEntityLangcode;
          }
          return element;
        },

        destroy() {
          this._tearDownDynamicEditables();
        },

        data(event) {
          // Only run during changes.
          if (this.oldData) {
            // The server-side text filter plugin treats both an empty
            // `data-caption` attribute and a non-existing one the same: it
            // does not render a caption. But in the CKEditor Widget, we need
            // to be able to show an empty caption with placeholder text using
            // CSS even when technically there is no `data-caption` attribute
            // value yet. That's why this CKEditor Widget has an independent
            // `hasCaption` boolean (which is not an attribute) to know when
            // to generate a non-empty `data-caption` attribute when the
            // content creator has enabled caption: this makes the server-side
            // text filter render a caption, allowing the placeholder-rendering
            // CSS to work.
            // @see core/modules/filter/css/filter.caption.css
            // @see ckeditor_ckeditor_css_alter()
            if (!this.data.hasCaption && this.oldData.hasCaption) {
              delete this.data.attributes['data-caption'];
            } else if (this.data.hasCaption && !this.oldData.hasCaption) {
              this.data.attributes['data-caption'] = ' ';
            }
          }

          if (this._previewNeedsServerSideUpdate()) {
            editor.fire('lockSnapshot');
            this._tearDownDynamicEditables();

            this._loadPreview(widget => {
              widget._setUpDynamicEditables();
              widget._setUpEditButton();
              editor.fire('unlockSnapshot');
            });
          }

          // Allow entity_embed.editor.css to respond to changes (for example in alignment).
          this.element.setAttributes(this.data.attributes);
          // Convert data-align attribute to class so we're not applying styles
          // to data attributes.
          // @todo Consider removing this in https://www.drupal.org/project/drupal/issues/3072279
          if (this.data.attributes.hasOwnProperty('data-align')) {
            this.element
              .getParent()
              .addClass(`align-${this.data.attributes['data-align']}`);
          } else {
            // If data-align property is removed, remove any align classes from
            // from the widget wrapper. These classes are moved to the wrapper
            // so that the alignment will still display within CKEditor.
            const classes = this.element.getParent().$.classList;
            for (let i = 0; i < classes.length; i++) {
              if (classes[i].indexOf('align-') === 0) {
                this.element.getParent().removeClass(classes[i]);
              }
            }
          }

          // Track the previous state to allow checking if preview needs
          // server side update.
          this.oldData = CKEDITOR.tools.clone(this.data);
        },

        downcast() {
          const downcastElement = new CKEDITOR.htmlParser.element(
            'drupal-media',
            this.data.attributes,
          );
          if (this.data.link) {
            const link = new CKEDITOR.htmlParser.element('a', this.data.link);
            link.add(downcastElement);
            return link;
          }
          return downcastElement;
        },

        _setUpDynamicEditables() {
          // Now that the caption is available in the DOM, make it editable.
          if (this.initEditable('caption', this.definition.editables.caption)) {
            const captionEditable = this.editables.caption;
            // @see core/modules/filter/css/filter.caption.css
            // @see ckeditor_ckeditor_css_alter()
            captionEditable.setAttribute(
              'data-placeholder',
              Drupal.t('Enter caption here'),
            );
            // Ensure that any changes made to the caption are persisted in the
            // widget's data-caption attribute.
            this.captionObserver = new MutationObserver(() => {
              const mediaAttributes = CKEDITOR.tools.clone(
                this.data.attributes,
              );
              mediaAttributes['data-caption'] = captionEditable.getData();
              this.setData('attributes', mediaAttributes);
            });
            this.captionObserver.observe(captionEditable.$, {
              characterData: true,
              attributes: true,
              childList: true,
              subtree: true,
            });
            // Some browsers will add a <br> tag to a newly created DOM element
            // with no content. Remove this <br> if it is the only thing in the
            // caption. Our placeholder support requires the element to be
            // entirely empty.
            // @see core/modules/filter/css/filter.caption.css
            // @see core/modules/ckeditor/js/plugins/drupalimagecaption/plugin.es6.js
            if (
              captionEditable.$.childNodes.length === 1 &&
              captionEditable.$.childNodes.item(0).nodeName === 'BR'
            ) {
              captionEditable.$.removeChild(
                captionEditable.$.childNodes.item(0),
              );
            }
          }
        },

        /**
         * Injects HTML for edit button into the preview that was just loaded.
         */
        _setUpEditButton() {
          // No buttons for missing media.
          if (this.element.findOne('.media-embed-error')) {
            return;
          }

          /**
           * Determines if a node is an element node.
           *
           * @param {CKEDITOR.dom.node} n
           *   A DOM node to evaluate.
           *
           * @return {bool}
           *   Returns true if node is an element node and not a non-element
           *   node (such as NODE_TEXT, NODE_COMMENT, NODE_DOCUMENT or
           *   NODE_DOCUMENT_FRAGMENT).
           *
           * @see https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR.html#property-NODE_ELEMENT
           */
          const isElementNode = function(n) {
            return n.type === CKEDITOR.NODE_ELEMENT;
          };

          // Find the actual embedded media in the DOM.
          const embeddedMediaContainer = this.data.hasCaption
            ? this.element.findOne('figure')
            : this.element;
          let embeddedMedia = embeddedMediaContainer.getFirst(isElementNode);
          // If there is a link, the top-level element is the `a` tag, and the
          // embedded media will be within the `a` tag.
          if (this.data.link) {
            embeddedMedia = embeddedMedia.getFirst(isElementNode);
          }
          // To allow the edit button to be absolutely positioned, the parent
          // element must be positioned relative.
          embeddedMedia.setStyle('position', 'relative');

          const editButton = CKEDITOR.dom.element.createFromHtml(
            Drupal.theme('mediaEmbedEditButton'),
          );
          embeddedMedia.getFirst().insertBeforeMe(editButton);

          // Make the edit button do things.
          const widget = this;
          this.element
            .findOne('.media-library-item__edit')
            .on('click', event => {
              const saveCallback = function(values) {
                event.cancel();
                editor.fire('saveSnapshot');
                if (values.hasOwnProperty('attributes')) {
                  // Combine the dialog attributes with the widget attributes.
                  // This copies the properties from widget.data.attributes to
                  // values.attributes. (Properties already present
                  // in values.attributes are not overwritten.)
                  CKEDITOR.tools.extend(
                    values.attributes,
                    widget.data.attributes,
                  );
                  // Allow the dialog to delete attributes by setting them
                  // to `false` or `none`. For example: `alt`.
                  Object.keys(values.attributes).forEach(prop => {
                    if (
                      values.attributes[prop] === false ||
                      (prop === 'data-align' &&
                        values.attributes[prop] === 'none')
                    ) {
                      delete values.attributes[prop];
                    }
                  });
                }
                widget.setData({
                  attributes: values.attributes,
                  hasCaption: !!values.hasCaption,
                });
                editor.fire('saveSnapshot');
              };

              Drupal.ckeditor.openDialog(
                editor,
                Drupal.url(
                  `editor/dialog/media/${editor.config.drupal.format}`,
                ),
                widget.data,
                saveCallback,
                {},
              );
            });

          // Allow opening the dialog with the return key or the space bar
          // by triggering a click event when a keydown event occurs on
          // the edit button.
          this.element
            .findOne('.media-library-item__edit')
            .on('keydown', event => {
              // The character code for the return key.
              const returnKey = 13;
              // The character code for the space bar.
              const spaceBar = 32;
              if (typeof event.data !== 'undefined') {
                const keypress = event.data.getKey();
                if (keypress === returnKey || keypress === spaceBar) {
                  // Clicks the edit button that triggered the 'keydown'
                  // event.
                  event.sender.$.click();
                }
                // Stop propagation to keep the return key from
                // adding a line break.
                event.data.$.stopPropagation();
                event.data.$.stopImmediatePropagation();
              }
            });
        },

        _tearDownDynamicEditables() {
          // If we are watching for changes to the caption, stop doing that.
          if (this.captionObserver) {
            this.captionObserver.disconnect();
          }
        },

        /**
         * Determines if the preview needs to be re-rendered by the server.
         *
         * @return {boolean}
         *   Returns true if the data hashes differ.
         */
        _previewNeedsServerSideUpdate() {
          // When the widget is first loading, it of course needs to still get a preview!
          if (!this.ready) {
            return true;
          }

          return this._hashData(this.oldData) !== this._hashData(this.data);
        },

        /**
         * Computes a hash of the data that can only be previewed by the server.
         *
         * @return {string}
         */
        _hashData(data) {
          const dataToHash = CKEDITOR.tools.clone(data);
          // The caption does not need rendering.
          delete dataToHash.attributes['data-caption'];
          // Changed link destinations do not affect the visual preview.
          if (dataToHash.link) {
            delete dataToHash.link.href;
          }
          return JSON.stringify(dataToHash);
        },

        /**
         * Loads an media embed preview and runs a callback after insertion.
         *
         * Note the absence of caching, that's because this uses a GET request (which is cacheable) and the server takes
         * special care to make the responses privately cacheable (i.e. per session) in the browser.
         *
         * @see \Drupal\media\Controller\MediaFilterController::preview()
         *
         * @param {function} callback
         *   A callback function that will be called after the preview has
         *   loaded. Receives the widget instance.
         */
        _loadPreview(callback) {
          jQuery.get({
            url: Drupal.url(`media/${editor.config.drupal.format}/preview`),
            data: {
              text: this.downcast().getOuterHtml(),
            },
            dataType: 'html',
            success: previewHtml => {
              this.element.setHtml(previewHtml);
              callback(this);
            },
            error: () => {
              this.element.setHtml(Drupal.theme('mediaEmbedError'));
            },
          });
        },
      });
    },

    afterInit(editor) {
      linkCommandIntegrator(editor);
    },
  });
})(jQuery, Drupal, CKEDITOR);
