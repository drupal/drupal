/* eslint-disable no-bitwise, no-nested-ternary, no-mutable-exports, comma-dangle, strict */

((Drupal, drupalSettings, once) => {
  Drupal.behaviors.ginAccent = {
    attach: function attach(context) {
      once('ginAccent', 'html', context).forEach(() => {
        // Check dark mode.
        Drupal.ginAccent.checkDarkMode();

        // Set focus color.
        Drupal.ginAccent.setFocusColor();
      });
    },
  };

  Drupal.ginAccent = {
    setAccentColor: function setAccentColor(preset = null) {
      const accentColorPreset = preset != null ? preset : drupalSettings.gin.preset_accent_color;
      const accentColors = drupalSettings.gin.accent_colors;
      const presetColor = accentColors[accentColorPreset]['hex'];

      document.documentElement.style.setProperty('--accent-base', presetColor);
    },

    setCustomAccentColor: function setCustomAccentColor(color = null, element = document.documentElement) {
      // If custom color is set, generate colors through JS.
      const accentColor = color != null ? color : drupalSettings.gin.accent_color;
      if (accentColor) {
        this.clearAccentColor(element);

        element.style.setProperty('--accent-base', accentColor);
      }
    },

    clearAccentColor: (element = document.documentElement) => {
      element.style.removeProperty('--accent-base');
    },

    setFocusColor: function setFocusColor(preset = null, color = null) {
      const focusColorPreset = preset != null ? preset : drupalSettings.gin.preset_focus_color;
      document.documentElement.setAttribute('data-gin-focus', focusColorPreset);

      if (focusColorPreset === 'custom') {
       this.setCustomFocusColor(color);
      }
    },

    setCustomFocusColor: function setCustomFocusColor(color = null, element = document.documentElement) {
      const accentColor = color != null ? color : drupalSettings.gin.focus_color;

      // Set preset color.
      if (accentColor) {
        this.clearFocusColor(element);

        const strippedAccentColor = accentColor.replace('#', '');
        const darkAccentColor = this.mixColor('ffffff', strippedAccentColor, 65);
        const style = document.createElement('style');
        style.className = 'gin-custom-focus';
        style.innerHTML = `
          [data-gin-focus="custom"] {\n\
            --gin-color-focus: ${accentColor};\n\
          }\n\
          .gin--dark-mode[data-gin-focus="custom"],\n\
          .gin--dark-mode [data-gin-focus="custom"] {\n\
            --gin-color-focus: ${darkAccentColor};\n\
          }`;

        element.append(style);
      }
    },

    clearFocusColor: (element = document.documentElement) => {
      if (element.querySelectorAll('.gin-custom-focus').length > 0) {
        const removeElement = element.querySelector('.gin-custom-focus');
        removeElement.parentNode.removeChild(removeElement);
      }
    },

    checkDarkMode: () => {
      const darkModeClass = drupalSettings.gin.dark_mode_class;

      // Change to dark mode.
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (e.matches && window.ginDarkMode === 'auto') {
          document.documentElement.classList.add(darkModeClass);
        }
      });

      // Change to light mode.
      window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', e => {
        if (e.matches && window.ginDarkMode === 'auto') {
          document.documentElement.classList.remove(darkModeClass);
        }
      });
    },

    // https://gist.github.com/jedfoster/7939513
    mixColor: (color_1, color_2, weight) => {
      function d2h(d) { return d.toString(16); }
      function h2d(h) { return parseInt(h, 16); }

      weight = (typeof(weight) !== 'undefined') ? weight : 50;

      var color = "#";

      for (var i = 0; i <= 5; i += 2) {
        var v1 = h2d(color_1.substr(i, 2)),
            v2 = h2d(color_2.substr(i, 2)),
            val = d2h(Math.floor(v2 + (v1 - v2) * (weight / 100.0)));

        while(val.length < 2) { val = '0' + val; }
        color += val;
      }

      return color;
    },

  };
})(Drupal, drupalSettings, once);
