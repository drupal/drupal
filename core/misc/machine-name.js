/**
 * @file
 * Machine name functionality.
 */

(function ($, Drupal, drupalSettings) {
  /**
   * Attach the machine-readable name form element behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches machine-name behaviors.
   */
  Drupal.behaviors.machineName = {
    /**
     * Attaches the behavior.
     *
     * @param {Element} context
     *   The context for attaching the behavior.
     * @param {object} settings
     *   Settings object.
     * @param {object} settings.machineName
     *   A list of elements to process, keyed by the HTML ID of the form
     *   element containing the human-readable value. Each element is an object
     *   defining the following properties:
     *   - target: The HTML ID of the machine name form element.
     *   - suffix: The HTML ID of a container to show the machine name preview
     *     in (usually a field suffix after the human-readable name
     *     form element).
     *   - label: The label to show for the machine name preview.
     *   - replace_pattern: A regular expression (without modifiers) matching
     *     disallowed characters in the machine name; e.g., '[^a-z0-9]+'.
     *   - replace: A character to replace disallowed characters with; e.g.,
     *     '_' or '-'.
     *   - standalone: Whether the preview should stay in its own element
     *     rather than the suffix of the source element.
     *   - field_prefix: The #field_prefix of the form element.
     *   - field_suffix: The #field_suffix of the form element.
     */
    attach(context, settings) {
      const self = this;
      const $context = $(context);
      let timeout = null;
      let xhr = null;

      function clickEditHandler(e) {
        const data = e.data;
        data.$wrapper.removeClass('hidden');
        if (data.$target.attr('data-machine-name-require-when-visible')) {
          data.$target
            .attr('required', true)
            .removeAttr('data-machine-name-require-when-visible');
        }
        data.$target.trigger('focus');
        data.$suffix.hide();
        data.$source.off('.machineName');
      }

      function machineNameHandler(e) {
        const data = e.data;
        const options = data.options;
        const baseValue = e.target.value;

        const rx = new RegExp(options.replace_pattern, 'g');
        const expected = baseValue
          .toLowerCase()
          .replace(rx, options.replace)
          .substr(0, options.maxlength);

        // Abort the last pending request because the label has changed and it
        // is no longer valid.
        if (xhr && xhr.readystate !== 4) {
          xhr.abort();
          xhr = null;
        }

        // Wait 300 milliseconds for Ajax request since the last event to update
        // the machine name i.e., after the user has stopped typing.
        if (timeout) {
          clearTimeout(timeout);
          timeout = null;
        }
        if (baseValue.toLowerCase() !== expected) {
          timeout = setTimeout(() => {
            xhr = self.transliterate(baseValue, options).done((machine) => {
              self.showMachineName(machine.substr(0, options.maxlength), data);
            });
          }, 300);
        } else {
          self.showMachineName(expected, data);
        }
      }

      Object.keys(settings.machineName).forEach((sourceId) => {
        const options = settings.machineName[sourceId];

        const $source = $(
          once(
            'machine-name',
            $context.find(sourceId).addClass('machine-name-source'),
          ),
        );
        const $target = $context
          .find(options.target)
          .addClass('machine-name-target');
        const $suffix = $context.find(options.suffix);
        const $wrapper = $target.closest('.js-form-item');
        // All elements have to exist.
        if (
          !$source.length ||
          !$target.length ||
          !$suffix.length ||
          !$wrapper.length
        ) {
          return;
        }
        // Skip processing upon a form validation error on a non-empty
        // machine name.
        if (
          $target.hasClass('error') &&
          $target[0].value &&
          $target[0].value.trim().length
        ) {
          return;
        }
        // Figure out the maximum length for the machine name.
        options.maxlength = $target.attr('maxlength');
        // Hide the form item container of the machine name form element.
        $wrapper.addClass('hidden');
        if ($target.attr('required')) {
          $target
            .removeAttr('required')
            .attr('data-machine-name-require-when-visible');
        }

        // Initial machine name from the target field default value.
        const machine = $target[0].value;
        // Append the machine name preview to the source field.
        const $preview = $(
          `<span class="machine-name-value">${
            options.field_prefix
          }${Drupal.checkPlain(machine)}${options.field_suffix}</span>`,
        );
        $suffix.empty();
        if (options.label) {
          $suffix.append(
            `<span class="machine-name-label">${options.label}: </span>`,
          );
        }
        $suffix.append($preview);

        // If the machine name cannot be edited, stop further processing.
        if ($target.is(':disabled')) {
          return;
        }

        const eventData = {
          $source,
          $target,
          $suffix,
          $wrapper,
          $preview,
          options,
        };

        // If no initial value, determine machine name based on the
        // human-readable form element value.
        if (machine === '' && $source[0].value !== '') {
          self.transliterate($source[0].value, options).done((machineName) => {
            self.showMachineName(
              machineName.substr(0, options.maxlength),
              eventData,
            );
          });
        }

        // If it is editable, append an edit link.
        const $link = $(
          '<span class="admin-link"><button type="button" class="link" aria-label="'
            .concat(Drupal.t('Edit machine name'), '">')
            .concat(Drupal.t('Edit'), '</button></span>'),
        ).on('click', eventData, clickEditHandler);
        $suffix.append($link);

        // Preview the machine name in realtime when the human-readable name
        // changes, but only if there is no machine name yet; i.e., only upon
        // initial creation, not when editing.
        if ($target[0].value === '') {
          $source
            .on('formUpdated.machineName', eventData, machineNameHandler)
            // Initialize machine name preview.
            .trigger('formUpdated.machineName');
        }
      });
    },

    showMachineName(machine, data) {
      const settings = data.options;
      // Set the machine name to the transliterated value.
      if (machine !== '') {
        if (machine !== settings.replace) {
          data.$target[0].value = machine;
          data.$preview.html(
            settings.field_prefix +
              Drupal.checkPlain(machine) +
              settings.field_suffix,
          );
        }
        data.$suffix.show();
      } else {
        data.$suffix.hide();
        data.$target[0].value = machine;
        data.$preview.empty();
      }
    },

    /**
     * Transliterate a human-readable name to a machine name.
     *
     * @param {string} source
     *   A string to transliterate.
     * @param {object} settings
     *   The machine name settings for the corresponding field.
     * @param {string} settings.replace_pattern
     *   A regular expression (without modifiers) matching disallowed characters
     *   in the machine name; e.g., '[^a-z0-9]+'.
     * @param {string} settings.replace_token
     *   A token to validate the regular expression.
     * @param {string} settings.replace
     *   A character to replace disallowed characters with; e.g., '_' or '-'.
     * @param {number} settings.maxlength
     *   The maximum length of the machine name.
     *
     * @return {jQuery}
     *   The transliterated source string.
     */
    transliterate(source, settings) {
      return $.get(Drupal.url('machine_name/transliterate'), {
        text: source,
        langcode: drupalSettings.langcode,
        replace_pattern: settings.replace_pattern,
        replace_token: settings.replace_token,
        replace: settings.replace,
        lowercase: true,
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
