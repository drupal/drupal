/**
 * @file
 * Provides UI/UX progressive enhancements on Olivero's theme settings by
 * creating an HTMLColorInput element and synchronizing its input with a text
 * input to provide an accessible and user-friendly interface. Additionally,
 * provides a select element with pre-defined color values for easy color
 * switching.
 */

((Drupal, settings, once) => {
  const colorSchemeOptions = settings.olivero.colorSchemes;

  /**
   * Announces the text value of the field's label.
   *
   * @param {HTMLElement} changedInput
   *  The form element that was changed.
   */
  function announceFieldChange(changedInput) {
    const fieldTitle =
      changedInput.parentElement.querySelector('label').innerText;
    const fieldValue = changedInput.value;
    const announcement = Drupal.t('@fieldName has changed to @fieldValue', {
      '@fieldName': fieldTitle,
      '@fieldValue': fieldValue,
    });
    Drupal.announce(announcement);
  }

  /**
   * `input` event callback to keep text & color inputs in sync.
   *
   * @param {HTMLElement} changedInput input element changed by user
   * @param {HTMLElement} inputToSync input element to synchronize
   */
  function synchronizeInputs(changedInput, inputToSync) {
    inputToSync.value = changedInput.value;

    changedInput.setAttribute('data-olivero-custom-color', changedInput.value);
    inputToSync.setAttribute('data-olivero-custom-color', changedInput.value);

    const colorSchemeSelect = document.querySelector(
      '[data-drupal-selector="edit-color-scheme"]',
    );

    if (colorSchemeSelect.value !== '') {
      colorSchemeSelect.value = '';
      announceFieldChange(colorSchemeSelect);
    }
  }

  /**
   * Set individual colors when a pre-defined color scheme is selected.
   *
   * @param {Event.target} target input element for which the value has changed.
   */
  function setColorScheme({ target }) {
    if (!target.value) return;

    const selectedColorScheme = colorSchemeOptions[target.value].colors;

    if (selectedColorScheme) {
      Object.entries(selectedColorScheme).forEach(([key, color]) => {
        document
          .querySelectorAll(`input[name="${key}"], input[name="${key}_visual"]`)
          .forEach((input) => {
            if (input.value !== color) {
              input.value = color;
              if (input.type === 'text') {
                announceFieldChange(input);
              }
            }
          });
      });
    } else {
      document
        .querySelectorAll(`input[data-olivero-custom-color]`)
        .forEach((input) => {
          input.value = input.getAttribute('data-olivero-custom-color');
        });
    }
  }

  /**
   * Displays and initializes the color scheme selector.
   *
   * @param {HTMLSelectElement} selectElement div[data-drupal-selector="edit-color-scheme"]
   */
  function initColorSchemeSelect(selectElement) {
    selectElement.closest('[style*="display:none;"]').style.display = '';
    selectElement.addEventListener('change', setColorScheme);
    Object.entries(colorSchemeOptions).forEach((option) => {
      const [key, values] = option;

      const { label, colors } = values;

      let allColorsMatch = true;
      Object.entries(colors).forEach(([colorName, colorHex]) => {
        const field = document.querySelector(
          `input[type="text"][name="${colorName}"]`,
        );
        if (field.value !== colorHex) {
          allColorsMatch = false;
        }
      });

      if (allColorsMatch) {
        selectElement.value = key;
      }
    });
  }

  /**
   * Initializes Olivero theme-settings color picker.
   *   creates a color-type input and inserts it after the original text field.
   *   modifies aria values to make label apply to both inputs.
   *   adds event listeners to keep text & color inputs in sync.
   *
   * @param {HTMLElement} textInput The textfield input from the Drupal form API
   */
  function initColorPicker(textInput) {
    // Create input element.
    const colorInput = document.createElement('input');

    // Set new input's attributes.
    colorInput.type = 'color';
    colorInput.classList.add(
      'form-color',
      'form-element',
      'form-element--type-color',
      'form-element--api-color',
    );
    colorInput.value = textInput.value;
    colorInput.setAttribute('name', `${textInput.name}_visual`);
    colorInput.setAttribute(
      'data-olivero-custom-color',
      textInput.getAttribute('data-olivero-custom-color'),
    );

    // Insert new input into DOM.
    textInput.after(colorInput);

    // Make field label apply to textInput and colorInput.
    const fieldID = textInput.id;
    const label = document.querySelector(`label[for="${fieldID}"]`);
    label.removeAttribute('for');
    label.setAttribute('id', `${fieldID}-label`);

    textInput.setAttribute('aria-labelledby', `${fieldID}-label`);
    colorInput.setAttribute('aria-labelledby', `${fieldID}-label`);

    // Add `input` event listener to keep inputs synchronized.
    textInput.addEventListener('input', () => {
      synchronizeInputs(textInput, colorInput);
    });

    colorInput.addEventListener('input', () => {
      synchronizeInputs(colorInput, textInput);
    });
  }

  /**
   * Olivero Color Picker behavior.
   *
   * @type {Drupal~behavior}
   * @prop {Drupal~behaviorAttach} attach
   *   Initializes color picker fields.
   */
  Drupal.behaviors.oliveroColorPicker = {
    attach: () => {
      const colorSchemeSelect = once(
        'olivero-color-picker',
        '[data-drupal-selector="edit-color-scheme"]',
      );

      colorSchemeSelect.forEach((selectElement) => {
        initColorSchemeSelect(selectElement);
      });

      const colorTextInputs = once(
        'olivero-color-picker',
        '[data-drupal-selector="olivero-color-picker"] input[type="text"]',
      );

      colorTextInputs.forEach((textInput) => {
        initColorPicker(textInput);
      });
    },
  };
})(Drupal, drupalSettings, once);
