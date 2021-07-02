/**
 * @file
 * Attaches the behaviors for the Color module.
 */

(function ($, Drupal) {
  /**
   * Utility methods for color manipulation taken from farbtastic.
   *
   * @see https://github.com/mattfarina/farbtastic
   *
   * @namespace
   */
  Drupal.colorUtils = {
    /**
     *
     * @param dec
     *
     * @return {string}
     */
    dec2hex(dec) {
      return (dec < 16 ? '0' : '') + dec.toString(16);
    },
    /**
     *
     * @param rgb
     *
     * @return {string}
     */
    pack(rgb) {
      const r = Math.round(rgb[0] * 255);
      const g = Math.round(rgb[1] * 255);
      const b = Math.round(rgb[2] * 255);
      return `#${this.dec2hex(r)}${this.dec2hex(g)}${this.dec2hex(b)}`;
    },
    /**
     *
     * @param color
     *
     * @return {*[]}
     */
    unpack(color) {
      if (color.length === 7) {
        // eslint-disable-next-line no-inner-declarations
        function x(i) {
          return parseInt(color.substring(i, i + 2), 16) / 255;
        }
        return [x(1), x(3), x(5)];
      }
      if (color.length === 4) {
        // eslint-disable-next-line no-inner-declarations
        function x(i) {
          return parseInt(color.substring(i, i + 1), 16) / 15;
        }
        return [x(1), x(2), x(3)];
      }
    },
    /**
     *
     * @param m1
     * @param m2
     * @param h
     *
     * @return {*}
     */
    hueToRGB(m1, m2, h) {
      h = (h + 1) % 1;
      if (h * 6 < 1) return m1 + (m2 - m1) * h * 6;
      if (h * 2 < 1) return m2;
      if (h * 3 < 2) return m1 + (m2 - m1) * (0.66666 - h) * 6;
      return m1;
    },
    /**
     *
     * @param rgb
     *
     * @return {*[]}
     */
    RGBToHSL(rgb) {
      const [r, g, b] = rgb;
      const min = Math.min(r, g, b);
      const max = Math.max(r, g, b);
      const delta = max - min;
      let h = 0;
      let s = 0;
      const l = (min + max) / 2;
      if (l > 0 && l < 1) {
        s = delta / (l < 0.5 ? 2 * l : 2 - 2 * l);
      }
      if (delta > 0) {
        if (max === r && max !== g) h += (g - b) / delta;
        if (max === g && max !== b) h += 2 + (b - r) / delta;
        if (max === b && max !== r) h += 4 + (r - g) / delta;
        h /= 6;
      }
      return [h, s, l];
    },
    /**
     *
     * @param hsl
     *
     * @return {*[]}
     */
    HSLToRGB(hsl) {
      const [h, s, l] = hsl;
      const m2 = l <= 0.5 ? l * (s + 1) : l + s - l * s;
      const m1 = l * 2 - m2;
      return [
        this.hueToRGB(m1, m2, h + 0.33333),
        this.hueToRGB(m1, m2, h),
        this.hueToRGB(m1, m2, h - 0.33333),
      ];
    },
    /**
     *
     * @param hex
     *
     * @return {*[]}
     */
    hexToHSL(hex) {
      return this.RGBToHSL(this.unpack(hex));
    },
  };

  /**
   * Displays farbtastic color selector and initialize color administration UI.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach color selection behavior to relevant context.
   */
  Drupal.behaviors.color = {
    attach(context, settings) {
      let i;
      let j;
      let colors;
      // This behavior attaches by ID, so is only valid once on a page.
      const form = $(context)
        .find('#system-theme-settings .color-form')
        .once('color');

      if (form.length === 0) {
        return;
      }
      const inputs = [];
      const hooks = [];
      const locks = [];

      if (!Modernizr.inputtypes.color) {
        $(`<div class="messages messages--warning">
          ${Drupal.t(
            'Update colors by changing the hex value in an input. To use a color picker instead, use a browser that supports them natively such as Edge, Chrome, Firefox, Opera or Safari',
          )}.
          </div>`)
          .once('color')
          .prependTo(form);
      }

      // Decode reference colors to HSL.
      const { reference } = settings.color;
      Object.keys(reference || {}).forEach((color) => {
        reference[color] = Drupal.colorUtils.hexToHSL(reference[color]);
      });

      // Build a preview.
      const height = [];
      const width = [];

      /**
       * Renders the preview.
       */
      function preview() {
        Drupal.color.callback(context, settings, form, height, width);
      }

      /**
       * Resets the color scheme selector.
       */
      function resetScheme() {
        form.find('#edit-scheme').each(function () {
          this.selectedIndex = this.options.length - 1;
        });
      }

      /**
       * Shifts a given color, using a reference pair (ref in HSL).
       *
       * This algorithm ensures relative ordering on the saturation and
       * luminance axes is preserved, and performs a simple hue shift.
       *
       * It is also symmetrical. If: shiftColor(c, a, b) === d, then
       * shiftColor(d, b, a) === c.
       *
       * @function Drupal.color~shiftColor
       *
       * @param {string} given
       *   A hex color code to shift.
       * @param {Array.<number>} ref1
       *   First HSL color reference.
       * @param {Array.<number>} ref2
       *   Second HSL color reference.
       *
       * @return {string}
       *   A hex color, shifted.
       */
      function shiftColor(given, ref1, ref2) {
        let d;
        // Convert to HSL.
        given = Drupal.colorUtils.hexToHSL(given);
        // Hue: apply delta.
        given[0] += ref2[0] - ref1[0];
        // Saturation: interpolate.
        if (ref1[1] === 0 || ref2[1] === 0) {
          // eslint-disable-next-line prefer-destructuring
          given[1] = ref2[1];
        } else {
          d = ref1[1] / ref2[1];
          if (d > 1) {
            given[1] /= d;
          } else {
            given[1] = 1 - (1 - given[1]) * d;
          }
        }

        // Luminance: interpolate.
        if (ref1[2] === 0 || ref2[2] === 0) {
          // eslint-disable-next-line prefer-destructuring
          given[2] = ref2[2];
        } else {
          d = ref1[2] / ref2[2];
          if (d > 1) {
            given[2] /= d;
          } else {
            given[2] = 1 - (1 - given[2]) * d;
          }
        }

        return Drupal.colorUtils.HSLToRGB(given);
      }

      function updateLocked(input, color) {
        let matched;

        // Update locked values.
        i = input.i;
        for (j = i + 1; ; ++j) {
          if (!locks[j - 1] || $(locks[j - 1]).is('.is-unlocked')) {
            break;
          }
          matched = shiftColor(
            color,
            reference[input.key],
            reference[inputs[j].key],
          );
          inputs[j].value = Drupal.colorUtils.pack(matched);
        }
        for (j = i - 1; ; --j) {
          if (!locks[j] || $(locks[j]).is('.is-unlocked')) {
            break;
          }
          matched = shiftColor(
            color,
            reference[input.key],
            reference[inputs[j].key],
          );
          inputs[j].value = Drupal.colorUtils.pack(matched);
        }
      }

      $('.form-color')
        .once('color-inputs')
        .each((index, input) => {
          $(input).on('input', (e) => {
            // preview();
            const { value } = e.target;

            // Because IE11 uses text inputs due to not supporting
            // `type=[color]`, we must first confirm the value is valid hex.
            if (/^#([0-9A-F]{3}){1,2}$/i.test(value)) {
              updateLocked(e.target, value);
              preview();
              resetScheme();
            }
          });
        });

      // Loop through all defined gradients.
      Object.keys(settings.gradients || {}).forEach((i) => {
        // Add element to display the gradient.
        $('.color-preview')
          .once('color')
          .append(`<div id="gradient-${i}"></div>`);
        const gradient = $(`.color-preview #gradient-${i}`);
        // Add height of current gradient to the list (divided by 10).
        height.push(parseInt(gradient.css('height'), 10) / 10);
        // Add width of current gradient to the list (divided by 10).
        width.push(parseInt(gradient.css('width'), 10) / 10);
        // Add rows (or columns for horizontal gradients).
        // Each gradient line should have a height (or width for horizontal
        // gradients) of 10px (because we divided the height/width by 10
        // above).
        for (
          j = 0;
          j <
          (settings.gradients[i].direction === 'vertical'
            ? height[i]
            : width[i]);
          ++j
        ) {
          gradient.append('<div class="gradient-line"></div>');
        }
      });

      // Set up colorScheme selector.
      form.find('#edit-scheme').on('change', function () {
        const { schemes } = settings.color;
        const colorScheme = this.options[this.selectedIndex].value;
        if (colorScheme !== '' && schemes[colorScheme]) {
          // Get colors of active scheme.
          colors = schemes[colorScheme];
          Object.keys(colors || {}).forEach((fieldName) => {
            $(`#edit-palette-${fieldName}`).val(colors[fieldName]);
          });
          preview();
        }
      });

      form.find('.js-color-palette input.form-color').each(function () {
        // Extract palette field name.
        this.key = this.id.substring(13);

        // Add lock.
        const i = inputs.length;
        if (inputs.length) {
          let toggleClick = true;
          const lock = $(
            `<button class="color-palette__lock">${Drupal.t(
              'Unlock',
            )}</button>`,
          ).on('click', function (e) {
            e.preventDefault();
            if (toggleClick) {
              $(this).addClass('is-unlocked').html(Drupal.t('Lock'));
              $(hooks[i - 1]).attr(
                'class',
                locks[i - 2] && $(locks[i - 2]).is(':not(.is-unlocked)')
                  ? 'color-palette__hook is-up'
                  : 'color-palette__hook',
              );
              $(hooks[i]).attr(
                'class',
                locks[i] && $(locks[i]).is(':not(.is-unlocked)')
                  ? 'color-palette__hook is-down'
                  : 'color-palette__hook',
              );
            } else {
              $(this).removeClass('is-unlocked').html(Drupal.t('Unlock'));
              $(hooks[i - 1]).attr(
                'class',
                locks[i - 2] && $(locks[i - 2]).is(':not(.is-unlocked)')
                  ? 'color-palette__hook is-both'
                  : 'color-palette__hook is-down',
              );
              $(hooks[i]).attr(
                'class',
                locks[i] && $(locks[i]).is(':not(.is-unlocked)')
                  ? 'color-palette__hook is-both'
                  : 'color-palette__hook is-up',
              );
            }
            toggleClick = !toggleClick;
          });
          $(this).after(lock);
          locks.push(lock);
        }

        // Add hook.
        const hook = $('<div class="color-palette__hook"></div>');
        $(this).after(hook);
        hooks.push(hook);

        $(this).parent().find('.color-palette__lock').trigger('click');
        this.i = i;
        inputs.push(this);
      });

      // Focus first color.
      inputs[0].focus();

      // Render preview.
      preview();
    },
  };
})(jQuery, Drupal);
