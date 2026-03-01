/* eslint-disable func-names, no-mutable-exports, comma-dangle, strict */

((Drupal, drupalSettings) => {
  Drupal.behaviors.ginSettings = {
    attach: function attach(context) {
      Drupal.ginSettings.init(context);
    },
  };

  Drupal.ginSettings = {
    init: function (context) {
      // Watch dark mode setting has changed.
      context.querySelectorAll('input[name="enable_dark_mode"]')
        .forEach(el => el.addEventListener('change', e => {
          const darkMode = e.currentTarget.value;
          const accentColorPreset = document.querySelector('[data-drupal-selector="edit-preset-accent-color"] input:checked').value;
          const focusColorPreset = document.querySelector('select[name="preset_focus_color"]').value;

          // Toggle dark mode.
          this.darkMode(darkMode);

          // Set custom color if 'custom' is set.
          if (accentColorPreset === 'custom') {
            const accentColorSetting = document.querySelector('input[name="accent_color"]').value;

            Drupal.ginAccent.setCustomAccentColor(accentColorSetting);
          } else {
            Drupal.ginAccent.setAccentColor(accentColorPreset);
          }

          // Toggle Focus color.
          if (focusColorPreset === 'custom') {
            const focusColorSetting = document.querySelector('input[name="focus_color"]').value;

            Drupal.ginAccent.setCustomFocusColor(focusColorSetting);
          } else {
            Drupal.ginAccent.setFocusColor(focusColorPreset);
          }
        }));

      // Watch Accent color setting has changed.
      context.querySelectorAll('[data-drupal-selector="edit-preset-accent-color"] input')
        .forEach(el => el.addEventListener('change', e => {
          const accentColorPreset = e.currentTarget.value;

          // Update.
          Drupal.ginAccent.clearAccentColor();
          Drupal.ginAccent.setAccentColor(accentColorPreset);

          // Set custom color if 'custom' is set.
          if (accentColorPreset === 'custom') {
            const accentColorSetting = document.querySelector('input[name="accent_color"]').value;

            Drupal.ginAccent.setCustomAccentColor(accentColorSetting);
          }
        }));

      // Watch Accent color picker has changed.
      context.querySelectorAll('input[name="accent_picker"]')
        .forEach(el => el.addEventListener('change', e => {
          const accentColorSetting = e.currentTarget.value;

          // Sync fields.
          document.querySelector('input[name="accent_color"]').value = accentColorSetting;

          // Update.
          Drupal.ginAccent.setCustomAccentColor(accentColorSetting);
        }));

      // Watch Accent color setting has changed.
      context.querySelectorAll('input[name="accent_color"]')
        .forEach(el => el.addEventListener('change', e => {
          const accentColorSetting = e.currentTarget.value;

          // Sync fields.
          document.querySelector('input[name="accent_picker"]').value = accentColorSetting;

          // Update.
          Drupal.ginAccent.setCustomAccentColor(accentColorSetting);
        }));

      // Watch Focus color setting has changed.
      document.querySelector('select[name="preset_focus_color"]').addEventListener('change', e => {
        const focusColorPreset = e.currentTarget.value;

        // Update.
        Drupal.ginAccent.clearFocusColor();
        Drupal.ginAccent.setFocusColor(focusColorPreset);

        // Set custom color if 'custom' is set.
        if (focusColorPreset === 'custom') {
          const focusColorSetting = document.querySelector('input[name="focus_color"]').value;

          Drupal.ginAccent.setCustomFocusColor(focusColorSetting);
        }
      });

      // Watch Focus color picker has changed.
      document.querySelector('input[name="focus_picker"]').addEventListener('change', e => {
        const focusColorSetting = e.currentTarget.value;

        // Sync fields.
        document.querySelector('input[name="focus_color"]').value = focusColorSetting;

        // Update.
        Drupal.ginAccent.setCustomFocusColor(focusColorSetting);
      });

      // Watch Accent color setting has changed.
      document.querySelector('input[name="focus_color"]').addEventListener('change', e => {
        const focusColorSetting = e.currentTarget.value;

        // Sync fields.
        document.querySelector('input[name="focus_picker"]').value = focusColorSetting;

        // Update.
        Drupal.ginAccent.setCustomFocusColor(focusColorSetting);
      });

      // Watch Hight contrast mode setting has changed.
      document.querySelector('input[name="high_contrast_mode"]').addEventListener('change', e => {
        const highContrastMode = e.currentTarget.matches(':checked');

        // Update.
        this.setHighContrastMode(highContrastMode);
      });

    },

    darkMode: function (darkModeParam = null) {
      const darkModeEnabled = darkModeParam != null ? darkModeParam : drupalSettings.gin.dark_mode;
      const darkModeClass = drupalSettings.gin.dark_mode_class;

      if (
        darkModeEnabled == 1 ||
        (darkModeEnabled === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches)
      ) {
        document.querySelector('html').classList.add(darkModeClass);
      }
      else {
        document.querySelector('html').classList.remove(darkModeClass);
      }

      // Change to dark mode.
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (e.matches && document.querySelector('input[name="enable_dark_mode"]:checked').value === 'auto') {
          document.documentElement.classList.add(darkModeClass);
        }
      });

      // Change to light mode.
      window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', e => {
        if (e.matches && document.querySelector('input[name="enable_dark_mode"]:checked').value === 'auto') {
          document.documentElement.classList.remove(darkModeClass);
        }
      });
    },

    setHighContrastMode: function (param = null) {
      const enabled = param != null ? param : drupalSettings.gin.high_contrast_mode;
      const className = drupalSettings.gin.high_contrast_mode_class;

      // Needs to check for both: backwards compatibility.
      if (enabled === true || enabled === 1) {
        document.body.classList.add(className);
      }
      else {
        document.body.classList.remove(className);
      }
    },

  };
})(Drupal, drupalSettings);
