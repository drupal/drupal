/**
 * @file
 * Attaches the behaviors for the Color module.
 */

(function ($, Drupal) {
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
      const form = $(context).find('#system-theme-settings .color-form').once('color');
      if (form.length === 0) {
        return;
      }
      const inputs = [];
      const hooks = [];
      const locks = [];
      let focused = null;

      // Add Farbtastic.
      $('<div class="color-placeholder"></div>').once('color').prependTo(form);
      const farb = $.farbtastic('.color-placeholder');

      // Decode reference colors to HSL.
      const reference = settings.color.reference;
      Object.keys(reference || {}).forEach((color) => {
        reference[color] = farb.RGBToHSL(farb.unpack(reference[color]));
      });

      // Build a preview.
      const height = [];
      const width = [];
      // Loop through all defined gradients.
      Object.keys(settings.gradients || {}).forEach((i) => {
        // Add element to display the gradient.
        $('.color-preview').once('color').append(`<div id="gradient-${i}"></div>`);
        const gradient = $(`.color-preview #gradient-${i}`);
        // Add height of current gradient to the list (divided by 10).
        height.push(parseInt(gradient.css('height'), 10) / 10);
        // Add width of current gradient to the list (divided by 10).
        width.push(parseInt(gradient.css('width'), 10) / 10);
        // Add rows (or columns for horizontal gradients).
        // Each gradient line should have a height (or width for horizontal
        // gradients) of 10px (because we divided the height/width by 10
        // above).
        for (j = 0; j < (settings.gradients[i].direction === 'vertical' ? height[i] : width[i]); ++j) {
          gradient.append('<div class="gradient-line"></div>');
        }
      });

      // Set up colorScheme selector.
      form.find('#edit-scheme').on('change', function () {
        const schemes = settings.color.schemes;
        const colorScheme = this.options[this.selectedIndex].value;
        if (colorScheme !== '' && schemes[colorScheme]) {
          // Get colors of active scheme.
          colors = schemes[colorScheme];
          Object.keys(colors || {}).forEach((fieldName) => {
            callback($(`#edit-palette-${fieldName}`), colors[fieldName], false, true);
          });
          preview();
        }
      });

      /**
       * Renders the preview.
       */
      function preview() {
        Drupal.color.callback(context, settings, form, farb, height, width);
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
        given = farb.RGBToHSL(farb.unpack(given));

        // Hue: apply delta.
        given[0] += ref2[0] - ref1[0];

        // Saturation: interpolate.
        if (ref1[1] === 0 || ref2[1] === 0) {
          given[1] = ref2[1];
        }
        else {
          d = ref1[1] / ref2[1];
          if (d > 1) {
            given[1] /= d;
          }
          else {
            given[1] = 1 - ((1 - given[1]) * d);
          }
        }

        // Luminance: interpolate.
        if (ref1[2] === 0 || ref2[2] === 0) {
          given[2] = ref2[2];
        }
        else {
          d = ref1[2] / ref2[2];
          if (d > 1) {
            given[2] /= d;
          }
          else {
            given[2] = 1 - ((1 - given[2]) * d);
          }
        }

        return farb.pack(farb.HSLToRGB(given));
      }

      /**
       * Callback for Farbtastic when a new color is chosen.
       *
       * @param {HTMLElement} input
       *   The input element where the color is chosen.
       * @param {string} color
       *   The color that was chosen through the input.
       * @param {bool} propagate
       *   Whether or not to propagate the color to a locked pair value
       * @param {bool} colorScheme
       *   Flag to indicate if the user is using a color scheme when changing
       *   the color.
       */
      function callback(input, color, propagate, colorScheme) {
        let matched;
        // Set background/foreground colors.
        $(input).css({
          backgroundColor: color,
          color: farb.RGBToHSL(farb.unpack(color))[2] > 0.5 ? '#000' : '#fff',
        });

        // Change input value.
        if ($(input).val() && $(input).val() !== color) {
          $(input).val(color);

          // Update locked values.
          if (propagate) {
            i = input.i;
            for (j = i + 1; ; ++j) {
              if (!locks[j - 1] || $(locks[j - 1]).is('.is-unlocked')) {
                break;
              }
              matched = shiftColor(color, reference[input.key], reference[inputs[j].key]);
              callback(inputs[j], matched, false);
            }
            for (j = i - 1; ; --j) {
              if (!locks[j] || $(locks[j]).is('.is-unlocked')) {
                break;
              }
              matched = shiftColor(color, reference[input.key], reference[inputs[j].key]);
              callback(inputs[j], matched, false);
            }

            // Update preview.
            preview();
          }

          // Reset colorScheme selector.
          if (!colorScheme) {
            resetScheme();
          }
        }
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
       * Focuses Farbtastic on a particular field.
       *
       * @param {jQuery.Event} e
       *   The focus event on the field.
       */
      function focus(e) {
        const input = e.target;
        // Remove old bindings.
        if (focused) {
          $(focused)
            .off('keyup', farb.updateValue)
            .off('keyup', preview)
            .off('keyup', resetScheme)
            .parent()
            .removeClass('item-selected');
        }

        // Add new bindings.
        focused = input;
        farb.linkTo((color) => {
          callback(input, color, true, false);
        });
        farb.setColor(input.value);
        $(focused)
          .on('keyup', farb.updateValue)
          .on('keyup', preview)
          .on('keyup', resetScheme)
          .parent()
          .addClass('item-selected');
      }

      // Initialize color fields.
      form.find('.js-color-palette input.form-text')
        .each(function () {
          // Extract palette field name.
          this.key = this.id.substring(13);

          // Link to color picker temporarily to initialize.
          farb.linkTo(() => {}).setColor('#000').linkTo(this);

          // Add lock.
          const i = inputs.length;
          if (inputs.length) {
            let toggleClick = true;
            const lock = $(`<button class="color-palette__lock">${Drupal.t('Unlock')}</button>`).on('click', function (e) {
              e.preventDefault();
              if (toggleClick) {
                $(this).addClass('is-unlocked').html(Drupal.t('Lock'));
                $(hooks[i - 1]).attr('class',
                  locks[i - 2] && $(locks[i - 2]).is(':not(.is-unlocked)') ? 'color-palette__hook is-up' : 'color-palette__hook',
                );
                $(hooks[i]).attr('class',
                  locks[i] && $(locks[i]).is(':not(.is-unlocked)') ? 'color-palette__hook is-down' : 'color-palette__hook',
                );
              }
              else {
                $(this).removeClass('is-unlocked').html(Drupal.t('Unlock'));
                $(hooks[i - 1]).attr('class',
                  locks[i - 2] && $(locks[i - 2]).is(':not(.is-unlocked)') ? 'color-palette__hook is-both' : 'color-palette__hook is-down',
                );
                $(hooks[i]).attr('class',
                  locks[i] && $(locks[i]).is(':not(.is-unlocked)') ? 'color-palette__hook is-both' : 'color-palette__hook is-up',
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
        })
        .on('focus', focus);

      form.find('.js-color-palette label');

      // Focus first color.
      inputs[0].focus();

      // Render preview.
      preview();
    },
  };
}(jQuery, Drupal));
