/**
 * @file
 * Drupal Image Caption plugin.
 *
 * Integrates the Drupal Image plugin with the caption_filter filter if enabled.
 */

(function (CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('drupalimagecaption', {
    requires: 'widget',
    init: function (editor) {

      /**
       * Override drupalimage plugin's image insertion mechanism with our own, to
       * ensure a widget is inserted, rather than a simple image (Widget's auto-
       * discovery only runs upon init).
       */
      editor.on('drupalimageinsert', function (event) {
        editor.execCommand('widgetDrupalimagecaption');
        event.cancel();
      });

      // Register the widget with a unique name "drupalimagecaption".
      editor.widgets.add('drupalimagecaption', {
        allowedContent: 'img[!src,alt,width,height,!data-caption,!data-align]',
        template: '<img src="" />',
        parts: {
          image: 'img'
        },

        // Initialization method called for every widget instance being
        // upcasted.
        init: function () {
          var image = this.parts.image;

          // Save the initial widget data.
          this.setData({
            'data-editor-file-uuid': image.getAttribute('data-editor-file-uuid'),
            src: image.getAttribute('src'),
            width: image.getAttribute('width') || '',
            height: image.getAttribute('height') || '',
            alt: image.getAttribute('alt') || '',
            data_caption: image.getAttribute('data-caption'),
            data_align: image.getAttribute('data-align'),
            hasCaption: image.hasAttribute('data-caption')
          });

          image.removeStyle('float');
        },

        // Called after initialization and on "data" changes.
        data: function () {
          if (this.data['data-editor-file-uuid'] !== null) {
            this.parts.image.setAttribute('data-editor-file-uuid', this.data['data-editor-file-uuid']);
            this.parts.image.setAttribute('data-cke-saved-data-editor-file-uuid', this.data['data-editor-file-uuid']);
          }
          this.parts.image.setAttribute('src', this.data.src);
          this.parts.image.setAttribute('data-cke-saved-src', this.data.src);
          this.parts.image.setAttribute('alt', this.data.alt);
          this.parts.image.setAttribute('data-cke-saved-alt', this.data.alt);
          this.parts.image.setAttribute('width', this.data.width);
          this.parts.image.setAttribute('data-cke-saved-width', this.data.width);
          this.parts.image.setAttribute('height', this.data.height);
          this.parts.image.setAttribute('data-cke-saved-height', this.data.height);
          if (this.data.hasCaption) {
            this.parts.image.setAttribute('data-caption', this.data.data_caption);
            this.parts.image.setAttribute('data-cke-saved-data-caption', this.data.data_caption);
          }
          else {
            this.parts.image.removeAttributes(['data-caption', 'data-cke-saved-data-caption']);
          }
          if (this.data.data_align !== null) {
            this.parts.image.setAttribute('data-align', this.data.data_align);
            this.parts.image.setAttribute('data-cke-saved-data-align', this.data.data_align);
          }
          else {
            this.parts.image.removeAttributes(['data-align', 'data-cke-saved-data-align']);
          }

          // Float the wrapper too.
          if (this.data.data_align === null) {
            this.wrapper.removeStyle('float');
            this.wrapper.removeStyle('text-align');
          }
          else if (this.data.data_align === 'center') {
            this.wrapper.setStyle('float', 'none');
            this.wrapper.setStyle('text-align', 'center');
          }
          else {
            this.wrapper.setStyle('float', this.data.data_align);
            this.wrapper.removeStyle('text-align');
          }
        },

        // Check the elements that need to be converted to widgets.
        upcast: function (el) {
          // Upcast all <img> elements that are alone inside a block element.
          if (el.name === 'img') {
            if (CKEDITOR.dtd.$block[el.parent.name] && el.parent.children.length === 1) {
              return true;
            }
          }
        },

        // Convert the element back to its desired output representation.
        downcast: function (el) {
          if (this.data.hasCaption) {
            el.attributes['data-caption'] = this.data.data_caption;
          }

          if (this.data.data_align) {
            el.attributes['data-align'] = this.data.data_align;
          }

          if (!this.data.width) {
            el.attributes['data-cke-saved-width'] = this.parts.image.$.naturalWidth;
          }
          if (!this.data.height) {
            el.attributes['data-cke-saved-height'] = this.parts.image.$.naturalHeight;
          }
        },

        _selectionWillCreateInlineImage: function () {
          // Returns node or first of its ancestors
          // which is a block or block limit.
          function getBlockParent(node, root) {
            var path = new CKEDITOR.dom.elementPath(node, root);
            return path.block || path.blockLimit;
          }

          var range = editor.getSelection().getRanges()[ 0 ],
            startEl = getBlockParent(range.startContainer, range.root),
            endEl = getBlockParent(range.endContainer, range.root);

          var insideStartEl = range.checkBoundaryOfElement(startEl, CKEDITOR.START);
          var insideEndEl = range.checkBoundaryOfElement(endEl, CKEDITOR.END);

          return !(insideStartEl && insideEndEl);
        },

        _insertSaveCallback: function (returnValues) {
          // We can't create an image with an empty "src" attribute.
          if (returnValues.attributes.src.length === 0) {
            return;
          }

          editor.fire('saveSnapshot');

          // Build the HTML for the widget.
          var html = '<img ';
          for (var attr in returnValues.attributes) {
            if (returnValues.attributes.hasOwnProperty(attr) && !attr.match(/^data_/)) {
              html += attr + '="' + returnValues.attributes[attr] + '" ';
              html += 'data-cke-saved-' + attr + '="' + returnValues.attributes[attr] + '" ';
            }
          }
          if (returnValues.hasCaption) {
            html += 'data-caption="" ';
            html += ' data-cke-saved-data-caption=""';
          }
          if (returnValues.attributes.data_align && returnValues.attributes.data_align !== 'none') {
            html += 'data-align="' + returnValues.attributes.data_align + '" ';
            html += ' data-cke-saved-data-align="' + returnValues.attributes.data_align + '"';
          }
          html += ' />';
          var el = new CKEDITOR.dom.element.createFromHtml(html, editor.document);
          editor.insertElement(editor.widgets.wrapElement(el, 'drupalimagecaption'));

          // Save snapshot for undo support.
          editor.fire('saveSnapshot');

          // Initialize and focus the widget.
          var widget = editor.widgets.initOn(el, 'drupalimagecaption');
          widget.focus();
        },

        insert: function () {
          var override = {
            imageDOMElement: null,
            existingValues: { hasCaption: false, data_align: '' },
            saveCallback: this._insertSaveCallback,
            dialogTitle: editor.config.drupalImage_dialogTitleAdd
          };
          if (this._selectionWillCreateInlineImage()) {
            override.existingValues.isInline = this._selectionWillCreateInlineImage();
            delete override.saveCallback;
          }
          editor.execCommand('drupalimage', override);
        },

        edit: function () {
          var that = this;
          var saveCallback = function (returnValues) {
            editor.fire('saveSnapshot');
            // Set the updated widget data.
            that.setData({
              'data-editor-file-uuid': returnValues.attributes['data-editor-file-uuid'],
              src: returnValues.attributes.src,
              width: returnValues.attributes.width,
              height: returnValues.attributes.height,
              alt: returnValues.attributes.alt,
              hasCaption: !!returnValues.hasCaption,
              data_caption: returnValues.hasCaption ? that.data.data_caption : '',
              data_align: returnValues.attributes.data_align === 'none' ? null : returnValues.attributes.data_align
            });
            // Save snapshot for undo support.
            editor.fire('saveSnapshot');
          };
          var override = {
            imageDOMElement: this.parts.image.$,
            existingValues: this.data,
            saveCallback: saveCallback,
            dialogTitle: this.data.src === '' ? editor.config.drupalImage_dialogTitleAdd : editor.config.drupalImage_dialogTitleEdit
          };
          editor.execCommand('drupalimage', override);
        }
      });
    },

    afterInit: function (editor) {
      function setupAlignCommand(value) {
        var command = editor.getCommand('justify' + value);
        if (command) {
          if (value in { right: 1, left: 1, center: 1 }) {
            command.on('exec', function (event) {
              var widget = getSelectedWidget(editor);
              if (widget && widget.name === 'drupalimagecaption') {
                widget.setData({ data_align: value });
                event.cancel();
              }
            });
          }

          command.on('refresh', function (event) {
            var widget = getSelectedWidget(editor),
              allowed = { left: 1, center: 1, right: 1 },
              align;

            if (widget) {
              align = widget.data.data_align;

              this.setState(
                (align === value) ? CKEDITOR.TRISTATE_ON : (value in allowed) ? CKEDITOR.TRISTATE_OFF : CKEDITOR.TRISTATE_DISABLED);

              event.cancel();
            }
          });
        }
      }

      function getSelectedWidget(editor) {
        var widget = editor.widgets.focused;
        if (widget && widget.name === 'drupalimagecaption') {
          return widget;
        }
        return null;
      }

      // Customize the behavior of the alignment commands.
      setupAlignCommand('left');
      setupAlignCommand('right');
      setupAlignCommand('center');
    }
  });

})(CKEDITOR);
