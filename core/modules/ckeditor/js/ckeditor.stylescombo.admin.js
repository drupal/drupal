(function ($, Drupal, drupalSettings) {

  "use strict";

  /**
   * Ensures that the "stylescombo" button's metadata remains up-to-date.
   *
   * Triggers the CKEditorPluginSettingsChanged event whenever the "stylescombo"
   * plugin settings change, to ensure that the corresponding feature metadata is
   * immediately updated — i.e. ensure that HTML tags and classes entered here are
   * known to be "required", which may affect filter settings.
   */
  Drupal.behaviors.ckeditorStylesComboSettings = {
    attach: function (context) {
      var $context = $(context);

      // React to changes in the list of user-defined styles: calculate the new
      // stylesSet setting up to 2 times per second, and if it is different, fire
      // the CKEditorPluginSettingsChanged event with the updated parts of the
      // CKEditor configuration. (This will, in turn, cause the hidden CKEditor
      // instance to be updated and a drupalEditorFeatureModified event to fire.)
      var $ckeditorActiveToolbar = $context
        .find('.ckeditor-toolbar-configuration')
        .find('.ckeditor-toolbar-active');
      var previousStylesSet = drupalSettings.ckeditor.hiddenCKEditorConfig.stylesSet;
      var that = this;
      $context.find('[name="editor[settings][plugins][stylescombo][styles]"]')
        .on('blur.ckeditorStylesComboSettings', function () {
          var styles = $.trim($('#edit-editor-settings-plugins-stylescombo-styles').val());
          var stylesSet = that._generateStylesSetSetting(styles);
          if (!_.isEqual(previousStylesSet, stylesSet)) {
            previousStylesSet = stylesSet;
            $ckeditorActiveToolbar.trigger('CKEditorPluginSettingsChanged', [
              {stylesSet: stylesSet}
            ]);
          }
        });
    },

    /**
     * Builds the "stylesSet" configuration part of the CKEditor JS settings.
     *
     * @see \Drupal\ckeditor\Plugin\ckeditor\plugin\StylesCombo::generateStylesSetSetting()
     *
     * Note that this is a more forgiving implementation than the PHP version: the
     * parsing works identically, but instead of failing on invalid styles, we
     * just ignore those.
     *
     * @param String styles
     *   The "styles" setting.
     *
     * @return array
     *   An array containing the "stylesSet" configuration.
     */
    _generateStylesSetSetting: function (styles) {
      var stylesSet = [];

      styles = styles.replace(/\r/g, "\n");
      var lines = styles.split("\n");
      for (var i = 0; i < lines.length; i++) {
        var style = $.trim(lines[i]);

        // Ignore empty lines in between non-empty lines.
        if (style.length === 0) {
          continue;
        }

        // Validate syntax: element[.class...]|label pattern expected.
        if (style.match(/^ *[a-zA-Z0-9]+ *(\.[a-zA-Z0-9_-]+ *)*\| *.+ *$/) === null) {
          // Instead of failing, we just ignore any invalid styles.
          continue;
        }

        // Parse.
        var parts = style.split('|');
        var selector = parts[0];
        var label = parts[1];
        var classes = selector.split('.');
        var element = classes.shift();

        // Build the data structure CKEditor's stylescombo plugin expects.
        // @see http://docs.cksource.com/CKEditor_3.x/Developers_Guide/Styles
        stylesSet.push({
          attributes: {'class': classes.join(' ')},
          element: element,
          name: label
        });
      }

      return stylesSet;
    }
  };

  /**
   * Provides the summary for the "stylescombo" plugin settings vertical tab.
   */
  Drupal.behaviors.ckeditorStylesComboSettingsSummary = {
    attach: function () {
      $('#edit-editor-settings-plugins-stylescombo').drupalSetSummary(function (context) {
        var styles = $.trim($('#edit-editor-settings-plugins-stylescombo-styles').val());
        if (styles.length === 0) {
          return Drupal.t('No styles configured');
        }
        else {
          var count = $.trim(styles).split("\n").length;
          return Drupal.t('@count styles configured', {'@count': count});
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
