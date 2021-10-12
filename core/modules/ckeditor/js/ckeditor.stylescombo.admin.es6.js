/**
 * @file
 * CKEditor StylesCombo admin behavior.
 */

(function ($, Drupal, drupalSettings, _) {
  /**
   * Ensures that the "stylescombo" button's metadata remains up-to-date.
   *
   * Triggers the CKEditorPluginSettingsChanged event whenever the "stylescombo"
   * plugin settings change, to ensure that the corresponding feature metadata
   * is immediately updated — i.e. ensure that HTML tags and classes entered
   * here are known to be "required", which may affect filter settings.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches admin behavior to the "stylescombo" button.
   */
  Drupal.behaviors.ckeditorStylesComboSettings = {
    attach(context) {
      const $context = $(context);

      // React to changes in the list of user-defined styles: calculate the new
      // stylesSet setting up to 2 times per second, and if it is different,
      // fire the CKEditorPluginSettingsChanged event with the updated parts of
      // the CKEditor configuration. (This will, in turn, cause the hidden
      // CKEditor instance to be updated and a drupalEditorFeatureModified event
      // to fire.)
      const $ckeditorActiveToolbar = $context
        .find('.ckeditor-toolbar-configuration')
        .find('.ckeditor-toolbar-active');
      let previousStylesSet =
        drupalSettings.ckeditor.hiddenCKEditorConfig.stylesSet;
      const that = this;
      $context
        .find('[name="editor[settings][plugins][stylescombo][styles]"]')
        .on('blur.ckeditorStylesComboSettings', function () {
          const styles = $(this).val().trim();
          const stylesSet = that._generateStylesSetSetting(styles);
          if (!_.isEqual(previousStylesSet, stylesSet)) {
            previousStylesSet = stylesSet;
            $ckeditorActiveToolbar.trigger('CKEditorPluginSettingsChanged', [
              { stylesSet },
            ]);
          }
        });
    },

    /**
     * Builds the "stylesSet" configuration part of the CKEditor JS settings.
     *
     * @see \Drupal\ckeditor\Plugin\ckeditor\plugin\StylesCombo::generateStylesSetSetting()
     *
     * Note that this is a more forgiving implementation than the PHP version:
     * the parsing works identically, but instead of failing on invalid styles,
     * we just ignore those.
     *
     * @param {string} styles
     *   The "styles" setting.
     *
     * @return {Array}
     *   An array containing the "stylesSet" configuration.
     */
    _generateStylesSetSetting(styles) {
      const stylesSet = [];

      styles = styles.replace(/\r/g, '\n');
      const lines = styles.split('\n');
      for (let i = 0; i < lines.length; i++) {
        const style = lines[i].trim();

        // Ignore empty lines in between non-empty lines.
        if (style.length === 0) {
          continue;
        }

        // Validate syntax: element[.class...]|label pattern expected.
        if (
          style.match(/^ *[a-zA-Z0-9]+ *(\.[a-zA-Z0-9_-]+ *)*\| *.+ *$/) ===
          null
        ) {
          // Instead of failing, we just ignore any invalid styles.
          continue;
        }

        // Parse.
        const parts = style.split('|');
        const selector = parts[0];
        const label = parts[1];
        const classes = selector.split('.');
        const element = classes.shift();

        // Build the data structure CKEditor's stylescombo plugin expects.
        // @see https://ckeditor.com/docs/ckeditor4/latest/guide/dev_howtos_styles.html
        stylesSet.push({
          attributes: { class: classes.join(' ') },
          element,
          name: label,
        });
      }

      return stylesSet;
    },
  };

  /**
   * Provides the summary for the "stylescombo" plugin settings vertical tab.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior to the plugin settings vertical tab.
   */
  Drupal.behaviors.ckeditorStylesComboSettingsSummary = {
    attach() {
      $('[data-ckeditor-plugin-id="stylescombo"]').drupalSetSummary(
        (context) => {
          const styles = $(
            '[data-drupal-selector="edit-editor-settings-plugins-stylescombo-styles"]',
          )
            .val()
            .trim();
          if (styles.length === 0) {
            return Drupal.t('No styles configured');
          }

          const count = styles.split('\n').length;
          return Drupal.t('@count styles configured', { '@count': count });
        },
      );
    },
  };
})(jQuery, Drupal, drupalSettings, _);
