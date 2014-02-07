/**
 * @file
 * Drupal Image Caption plugin theme override.
 */

(function (CKEDITOR) {

  "use strict";

  CKEDITOR.on('instanceCreated', function (event) {
    var editor = event.editor;

    // Listen to widget definitions and customize them as needed. It's
    // basically rewriting parts of the definition.
    editor.on('widgetDefinition', function (event) {
      var widgetDefinition = event.data;

      // Customize the "drupalimagecaption" widget definition.
      if (widgetDefinition.name === 'drupalimagecaption') {

        widgetDefinition.template =
          '<figure class="caption caption-img">' +
            '<img src="" data-caption="" data-align="center" />' +
            '<figcaption></figcaption>' +
            '</figure>';

        // Define the editables created by the overridden upcasting.
        widgetDefinition.editables = {
          caption: 'figcaption'
        };

        // Define the additional parts created by the overridden upcasting.
        widgetDefinition.parts.caption = 'figcaption';

        // Override "data" so we can make the new widget structure
        // behave according to changes on data.
        widgetDefinition.data = CKEDITOR.tools.override(widgetDefinition.data, function (originalDataFn) {
          return function () {
            // Call the original "data" implementation.
            originalDataFn.apply(this, arguments);

            // The image is wrapped in <figure>.
            if (this.element.is('figure')) {
              // The image is wrapped in <figure>, but it should no longer be.
              if (!this.data.hasCaption && this.data.data_align === null) {
                // Destroy this widget, so we can unwrap the <img>.
                editor.widgets.destroy(this);
                // Unwrap <img> from <figure>.
                this.parts.image.replace(this.element);
                // Reinitialize this widget with the current data.
                editor.widgets.initOn(this.parts.image, 'drupalimagecaption', this.data);
              }
              // The image is wrapped in <figure>, as it should be; update it.
              else {
                // Set the caption visibility.
                this.parts.caption.setStyle('display', this.data.hasCaption ? '' : 'none');

                // Set the alignment, if any.
                this.element.removeClass('align-left');
                this.element.removeClass('align-center');
                this.element.removeClass('align-right');
                if (this.data.data_align) {
                  this.element.addClass('align-' + this.data.data_align);
                }
              }
            }
            // The image is not wrapped in <figure>.
            else if (this.element.is('img')) {
              // The image is not wrapped in <figure>, but it should be.
              if (this.data.hasCaption) {
                // Destroy this widget, so we can wrap the <img>.
                editor.widgets.destroy(this);
                // Replace the widget's element (the <img>) with the template (a
                // <figure> wrapping an <img>) and then replace the the template's
                // default <img> by our <img> so we won't lose attributes. We must
                // do this manually because upcast() won't run.
                var figure = CKEDITOR.dom.element.createFromHtml(this.template.output(), editor.document);
                figure.replace(this.element);
                this.element.replace(figure.findOne('img'));
                // Reinitialize this widget with the current data.
                editor.widgets.initOn(figure, 'drupalimagecaption', this.data);
              }
              else if (this.data.data_align !== null) {
                this.element.addClass('align-' + this.data.data_align);
              }
            }
          };
        });

        // Upcast to <figure> if data-caption or data-align is set.
        widgetDefinition.upcast = CKEDITOR.tools.override(widgetDefinition.upcast, function (originalUpcastFn) {
          return function (el) {
            // Execute the original upcast first. If "true", this is an
            // element to be upcasted.
            if (originalUpcastFn.apply(this, arguments)) {
              var figure;
              var captionValue = el.attributes['data-caption'];
              var alignValue = el.attributes['data-align'];

              // Wrap image in <figure> only if data-caption is set.
              if (captionValue !== undefined) {
                var classes = 'caption caption-img';
                if (alignValue !== null) {
                  classes += ' align-' + alignValue;
                }
                figure = el.wrapWith(new CKEDITOR.htmlParser.element('figure', { 'class': classes }));
                var caption = CKEDITOR.htmlParser.fragment.fromHtml(captionValue || '', 'figcaption');
                figure.add(caption);
              }
              else if (alignValue !== undefined) {
                if (el.attributes['class'] === undefined) {
                  el.attributes['class'] = '';
                }
                el.attributes['class'] += 'align-' + alignValue;
              }

              return figure || el;
            }
          };
        });

        // Downcast to <img>.
        widgetDefinition.downcast = CKEDITOR.tools.override(widgetDefinition.downcast, function (originalDowncastFn) {
          return function (el) {
            if (el.name === 'figure') {
              // Update data with the current caption.
              var caption = el.getFirst('figcaption');
              caption = caption ? caption.getHtml() : '';
              this.setData({
                data_caption: caption
              });

              // We downcast to just the <img> element.
              el = el.getFirst('img');
            }

            // Call the original downcast to setup the <img>
            // meta data accordingly.
            return originalDowncastFn.call(this, el) || el;
          };
        });

        // Generate a <figure>-wrapped <img> if either data-caption or data-align
        // are set for a newly created image.
        widgetDefinition.insert = CKEDITOR.tools.override(widgetDefinition.downcast, function (originalInsertFn) {
          return function () {
            var saveCallback = function (returnValues) {
              // We can't create an image with an empty "src" attribute.
              if (returnValues.attributes.src.length === 0) {
                return;
              }
              // Normalize the "data_align" attribute and the "hasCaption" value.
              if (returnValues.attributes.data_align === '' || returnValues.attributes.data_align === 'none') {
                returnValues.attributes.data_align = null;
              }
              if (typeof returnValues.hasCaption === 'number') {
                returnValues.hasCaption = !!returnValues.hasCaption;
              }
              // Use the original save callback if the image has no caption.
              if (returnValues.hasCaption === false) {
                widgetDefinition._insertSaveCallback.apply(this, arguments);
                return;
              }

              editor.fire('saveSnapshot');

              // Build the HTML for the widget.
              var html = '<figure class="caption caption-img';
              if (returnValues.attributes.data_align && returnValues.attributes.data_align !== 'none') {
                html += ' align-' + returnValues.attributes.data_align;
              }
              html += '"><img ';
              for (var attr in returnValues.attributes) {
                if (returnValues.attributes.hasOwnProperty(attr) && !attr.match(/^data_/)) {
                  html += attr + '="' + returnValues.attributes[attr] + '" ';
                  html += 'data-cke-saved-' + attr + '="' + returnValues.attributes[attr] + '" ';
                }
              }
              // The init() method will run on this and if it does not find
              // data-caption or data-align attributes, the subsequent call to the
              // data() method will cause the <figure> to be transformed back to
              // an <img>. Hence, set the data-caption and data-align attributes
              // on the newly inserted <img>.
              if (returnValues.hasCaption) {
                html += ' data-caption=""';
                html += ' data-cke-saved-data-caption=""';
              }
              if (returnValues.attributes.data_align && returnValues.attributes.data_align !== 'none') {
                html += ' data-align="' + returnValues.attributes.data_align + '"';
                html += ' data-cke-saved-data-align="' + returnValues.attributes.data_align + '"';
              }
              html += '/>';
              html += '<figcaption data-placeholder="' + Drupal.t('Enter caption here') + '"></figcaption>';
              html += '</figure>';
              var el = new CKEDITOR.dom.element.createFromHtml(html, editor.document);
              editor.insertElement(editor.widgets.wrapElement(el, 'drupalimagecaption'));

              // Save snapshot for undo support.
              editor.fire('saveSnapshot');

              // Initialize and focus the widget.
              var widget = editor.widgets.initOn(el, 'drupalimagecaption');
              widget.focus();
            };
            var override = {
              imageDOMElement: null,
              existingValues: { hasCaption: false, data_align: '' },
              saveCallback: saveCallback,
              dialogTitle: editor.config.drupalImage_dialogTitleAdd
            };
            if (this._selectionWillCreateInlineImage()) {
              override.existingValues.isInline = this._selectionWillCreateInlineImage();
              delete override.saveCallback;
            }
            editor.execCommand('drupalimage', override);
          };
        });
      }
    });
  });

})(CKEDITOR);
