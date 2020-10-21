/**
 * @file
 * Drag+drop based in-place editor for images.
 */

(function ($, _, Drupal) {
  Drupal.quickedit.editors.image = Drupal.quickedit.EditorView.extend(
    /** @lends Drupal.quickedit.editors.image# */ {
      /**
       * @constructs
       *
       * @augments Drupal.quickedit.EditorView
       *
       * @param {object} options
       *   Options for the image editor.
       */
      initialize(options) {
        Drupal.quickedit.EditorView.prototype.initialize.call(this, options);
        // Set our original value to our current HTML (for reverting).
        this.model.set('originalValue', this.$el.html().trim());
        // $.val() callback function for copying input from our custom form to
        // the Quick Edit Field Form.
        this.model.set('currentValue', function (index, value) {
          const matches = $(this)
            .attr('name')
            .match(/(alt|title)]$/);
          if (matches) {
            const name = matches[1];
            const $toolgroup = $(
              `#${options.fieldModel.toolbarView.getMainWysiwygToolgroupId()}`,
            );
            const $input = $toolgroup.find(
              `.quickedit-image-field-info input[name="${name}"]`,
            );
            if ($input.length) {
              return $input.val();
            }
          }
        });
      },

      /**
       * {@inheritdoc}
       *
       * @param {Drupal.quickedit.FieldModel} fieldModel
       *   The field model that holds the state.
       * @param {string} state
       *   The state to change to.
       * @param {object} options
       *   State options, if needed by the state change.
       */
      stateChange(fieldModel, state, options) {
        const from = fieldModel.previous('state');
        switch (state) {
          case 'inactive':
            break;

          case 'candidate':
            if (from !== 'inactive') {
              this.$el.find('.quickedit-image-dropzone').remove();
              this.$el.removeClass('quickedit-image-element');
            }
            if (from === 'invalid') {
              this.removeValidationErrors();
            }
            break;

          case 'highlighted':
            break;

          case 'activating':
            // Defer updating the field model until the current state change has
            // propagated, to not trigger a nested state change event.
            _.defer(() => {
              fieldModel.set('state', 'active');
            });
            break;

          case 'active': {
            const self = this;

            // Indicate that this element is being edited by Quick Edit Image.
            this.$el.addClass('quickedit-image-element');

            // Render our initial dropzone element. Once the user reverts changes
            // or saves a new image, this element is removed.
            const $dropzone = this.renderDropzone(
              'upload',
              Drupal.t('Drop file here or click to upload'),
            );

            $dropzone.on('dragenter', function (e) {
              $(this).addClass('hover');
            });
            $dropzone.on('dragleave', function (e) {
              $(this).removeClass('hover');
            });

            $dropzone.on('drop', function (e) {
              // Only respond when a file is dropped (could be another element).
              if (
                e.originalEvent.dataTransfer &&
                e.originalEvent.dataTransfer.files.length
              ) {
                $(this).removeClass('hover');
                self.uploadImage(e.originalEvent.dataTransfer.files[0]);
              }
            });

            $dropzone.on('click', (e) => {
              // Create an <input> element without appending it to the DOM, and
              // trigger a click event. This is the easiest way to arbitrarily
              // open the browser's upload dialog.
              $('<input type="file">')
                .trigger('click')
                .on('change', function () {
                  if (this.files.length) {
                    self.uploadImage(this.files[0]);
                  }
                });
            });

            // Prevent the browser's default behavior when dragging files onto
            // the document (usually opens them in the same tab).
            $dropzone.on('dragover dragenter dragleave drop click', (e) => {
              e.preventDefault();
              e.stopPropagation();
            });

            this.renderToolbar(fieldModel);
            break;
          }

          case 'changed':
            break;

          case 'saving':
            if (from === 'invalid') {
              this.removeValidationErrors();
            }

            this.save(options);
            break;

          case 'saved':
            break;

          case 'invalid':
            this.showValidationErrors();
            break;
        }
      },

      /**
       * Validates/uploads a given file.
       *
       * @param {File} file
       *   The file to upload.
       */
      uploadImage(file) {
        // Indicate loading by adding a special class to our icon.
        this.renderDropzone(
          'upload loading',
          Drupal.t('Uploading <i>@file</i>…', { '@file': file.name }),
        );

        // Build a valid URL for our endpoint.
        const fieldID = this.fieldModel.get('fieldID');
        const url = Drupal.quickedit.util.buildUrl(
          fieldID,
          Drupal.url(
            'quickedit/image/upload/!entity_type/!id/!field_name/!langcode/!view_mode',
          ),
        );

        // Construct form data that our endpoint can consume.
        const data = new FormData();
        data.append('files[image]', file);

        // Construct a POST request to our endpoint.
        const self = this;
        this.ajax({
          type: 'POST',
          url,
          data,
          success(response) {
            const $el = $(self.fieldModel.get('el'));
            // Indicate that the field has changed - this enables the
            // "Save" button.
            self.fieldModel.set('state', 'changed');
            self.fieldModel.get('entity').set('inTempStore', true);
            self.removeValidationErrors();

            // Replace our html with the new image. If we replaced our entire
            // element with data.html, we would have to implement complicated logic
            // like what's in Drupal.quickedit.AppView.renderUpdatedField.
            const $content = $(response.html)
              .closest('[data-quickedit-field-id]')
              .children();
            $el.empty().append($content);
          },
        });
      },

      /**
       * Utility function to make an AJAX request to the server.
       *
       * In addition to formatting the correct request, this also handles error
       * codes and messages by displaying them visually inline with the image.
       *
       * Drupal.ajax is not called here as the Form API is unused by this
       * in-place editor, and our JSON requests/responses try to be
       * editor-agnostic. Ideally similar logic and routes could be used by
       * modules like CKEditor for drag+drop file uploads as well.
       *
       * @param {object} options
       *   Ajax options.
       * @param {string} options.type
       *   The type of request (i.e. GET, POST, PUT, DELETE, etc.)
       * @param {string} options.url
       *   The URL for the request.
       * @param {*} options.data
       *   The data to send to the server.
       * @param {function} options.success
       *   A callback function used when a request is successful, without errors.
       */
      ajax(options) {
        const defaultOptions = {
          context: this,
          dataType: 'json',
          cache: false,
          contentType: false,
          processData: false,
          error() {
            this.renderDropzone(
              'error',
              Drupal.t('A server error has occurred.'),
            );
          },
        };

        const ajaxOptions = $.extend(defaultOptions, options);
        const successCallback = ajaxOptions.success;

        // Handle the success callback.
        ajaxOptions.success = function (response) {
          if (response.main_error) {
            this.renderDropzone('error', response.main_error);
            if (response.errors.length) {
              this.model.set('validationErrors', response.errors);
            }
            this.showValidationErrors();
          } else {
            successCallback(response);
          }
        };

        $.ajax(ajaxOptions);
      },

      /**
       * Renders our toolbar form for editing metadata.
       *
       * @param {Drupal.quickedit.FieldModel} fieldModel
       *   The current Field Model.
       */
      renderToolbar(fieldModel) {
        const $toolgroup = $(
          `#${fieldModel.toolbarView.getMainWysiwygToolgroupId()}`,
        );
        let $toolbar = $toolgroup.find('.quickedit-image-field-info');
        if ($toolbar.length === 0) {
          // Perform an AJAX request for extra image info (alt/title).
          const fieldID = fieldModel.get('fieldID');
          const url = Drupal.quickedit.util.buildUrl(
            fieldID,
            Drupal.url(
              'quickedit/image/info/!entity_type/!id/!field_name/!langcode/!view_mode',
            ),
          );
          const self = this;
          self.ajax({
            type: 'GET',
            url,
            success(response) {
              $toolbar = $(Drupal.theme.quickeditImageToolbar(response));
              $toolgroup.append($toolbar);
              $toolbar.on('keyup paste', () => {
                fieldModel.set('state', 'changed');
              });
              // Re-position the toolbar, which could have changed size.
              fieldModel.get('entity').toolbarView.position();
            },
          });
        }
      },

      /**
       * Renders our dropzone element.
       *
       * @param {string} state
       *   The current state of our editor. Only used for visual styling.
       * @param {string} text
       *   The text to display in the dropzone area.
       *
       * @return {jQuery}
       *   The rendered dropzone.
       */
      renderDropzone(state, text) {
        let $dropzone = this.$el.find('.quickedit-image-dropzone');
        // If the element already exists, modify its contents.
        if ($dropzone.length) {
          $dropzone
            .removeClass('upload error hover loading')
            .addClass(`.quickedit-image-dropzone ${state}`)
            .children('.quickedit-image-text')
            .html(text);
        } else {
          $dropzone = $(
            Drupal.theme('quickeditImageDropzone', {
              state,
              text,
            }),
          );
          this.$el.append($dropzone);
        }

        return $dropzone;
      },

      /**
       * {@inheritdoc}
       */
      revert() {
        this.$el.html(this.model.get('originalValue'));
      },

      /**
       * {@inheritdoc}
       */
      getQuickEditUISettings() {
        return {
          padding: false,
          unifiedToolbar: true,
          fullWidthToolbar: true,
          popup: false,
        };
      },

      /**
       * {@inheritdoc}
       */
      showValidationErrors() {
        const errors = Drupal.theme('quickeditImageErrors', {
          errors: this.model.get('validationErrors'),
        });
        $(`#${this.fieldModel.toolbarView.getMainWysiwygToolgroupId()}`).append(
          errors,
        );
        this.getEditedElement().addClass('quickedit-validation-error');
        // Re-position the toolbar, which could have changed size.
        this.fieldModel.get('entity').toolbarView.position();
      },

      /**
       * {@inheritdoc}
       */
      removeValidationErrors() {
        $(`#${this.fieldModel.toolbarView.getMainWysiwygToolgroupId()}`)
          .find('.quickedit-image-errors')
          .remove();
        this.getEditedElement().removeClass('quickedit-validation-error');
      },
    },
  );
})(jQuery, _, Drupal);
