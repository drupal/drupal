/**
 * @file
 * Machine name functionality.
 *
 * @internal
 */

(function ($, Drupal, drupalSettings, transliterateLibrary) {
  /**
   * Trims string by a character.
   *
   * @param {string} string
   *   The string to trim.
   * @param {string} character
   *   The characters to trim.
   *
   * @returns {string}
   *   A trimmed string.
   */
  const trimByChar = (string, character) => {
    const first = [...string].findIndex((char) => char !== character);
    const last = [...string].reverse().findIndex((char) => char !== character);
    return string.substring(first, string.length - last);
  };

  /**
   * Transform a human-readable name to a machine name.
   *
   * @param {string} source
   *   A string to transform.
   * @see trans {object} settings
   *   The machine name settings for the corresponding field.
   * @param {string} settings.replace_pattern
   *   A regular expression (without modifiers) matching disallowed characters
   *   in the machine name; e.g., '[^a-z0-9]+'.
   * @param {string} settings.replace
   *   A character to replace disallowed characters with; e.g., '_' or '-'.
   * @param {number} settings.maxlength
   *   The maximum length of the machine name.
   *
   * @return {string}
   *   The machine name string.
   */
  const prepareMachineName = (source, settings) => {
    const rx = new RegExp(settings.replace_pattern, 'g');

    return trimByChar(
      source
        .toLowerCase()
        .replace(rx, settings.replace)
        .substring(0, settings.maxlength),
      settings.replace,
    );
  };

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

        const needsTransliteration = !/^[A-Za-z0-9_\s]*$/.test(baseValue);
        if (needsTransliteration) {
          self.showMachineName(self.transliterate(baseValue, options), data);
        } else {
          self.showMachineName(prepareMachineName(baseValue, options), data);
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
        if ($target[0].disabled) {
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
          if (/^[A-Za-z0-9_\s]*$/.test($source[0].value)) {
            self.showMachineName(
              prepareMachineName($source[0].value, options),
              eventData,
            );
          } else {
            self.showMachineName(machine, eventData);
          }
        }

        // If it is editable, append an edit link.
        const $link = $(
          '<span class="admin-link"><button type="button" class="link" aria-label="'
            .concat(
              Drupal.t('Edit machine name'),
              '" data-drupal-selector="'.concat(
                $target.data('drupal-selector'),
              ),
              '-machine-name-admin-link">',
            )
            .concat(Drupal.t('Edit'), '</button></span>'),
        )
          .on('click', eventData, clickEditHandler)
          .on('keyup', (e) => {
            // Avoid propagating a keyup event from the machine name input.
            if (e.key === 'Enter' || eventData.code === 'Space') {
              e.preventDefault();
              e.stopImmediatePropagation();
              e.target.click();
            }
          })
          .on('keydown', (e) => {
            if (e.key === 'Enter' || eventData.code === 'Space') {
              e.preventDefault();
            }
          });
        $suffix.append($link);

        // Preview the machine name in realtime when the human-readable name
        // changes, but only if there is no machine name yet; i.e., only upon
        // initial creation, not when editing.
        if ($target[0].value === '') {
          // Listen to the 'change' and 'input' events that are fired
          // immediately as the user is typing for faster response time. This is
          // safe because the event handler doesn't include any slow
          // asynchronous operations (e.g., network requests) that could
          // accumulate.
          $source
            .on(
              'change.machineName input.machineName',
              eventData,
              machineNameHandler,
            )
            // Initialize machine name preview.
            .trigger('change.machineName');
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
     * @param {string} settings.replace
     *   A character to replace disallowed characters with; e.g., '_' or '-'.
     * @param {number} settings.maxlength
     *   The maximum length of the machine name.
     *
     * @return {string}
     *   The transliterated source string.
     */
    transliterate(source, settings) {
      const languageOverrides =
        drupalSettings.transliteration_language_overrides[
          drupalSettings.langcode
        ];
      const replace = {};
      if (languageOverrides) {
        Object.keys(languageOverrides).forEach((key) => {
          // Updates the keys from hexadecimal to strings.
          replace[String.fromCharCode(key)] = languageOverrides[key];
        });
      }

      const transliteratedSource = transliterateLibrary(source, { replace });

      return prepareMachineName(transliteratedSource, settings);
    },
  };
})(jQuery, Drupal, drupalSettings, transliterate);
