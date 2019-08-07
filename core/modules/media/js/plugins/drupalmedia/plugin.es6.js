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

  CKEDITOR.plugins.add('drupalmedia', {
    requires: 'widget',

    beforeInit(editor) {
      // Configure CKEditor DTD for custom drupal-media element.
      // @see https://www.drupal.org/node/2448449#comment-9717735
      const dtd = CKEDITOR.dtd;
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
        allowedContent:
          'drupal-media[data-entity-type,data-entity-uuid,data-view-mode,data-align,data-caption,alt,title]',
        // Minimum HTML which is required by this widget to work.
        requiredContent: 'drupal-media[data-entity-type,data-entity-uuid]',

        pathName: Drupal.t('Embedded media'),

        editables: {
          caption: {
            selector: 'figcaption',
            allowedContent: 'a[!href]; em strong cite code br',
            pathName: Drupal.t('Caption'),
          },
        },

        upcast(element, data) {
          const attributes = element.attributes;
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
          return element;
        },

        destroy() {
          this._tearDownDynamicEditables();
        },

        data(event) {
          if (this._previewNeedsServerSideUpdate()) {
            editor.fire('lockSnapshot');
            this._tearDownDynamicEditables();

            this._loadPreview(widget => {
              widget._setUpDynamicEditables();
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
          }
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
         * @returns {boolean}
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
