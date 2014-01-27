/**
 * @file
 * Attaches behavior for updating filter_html's settings automatically.
 */

(function ($, _, document, window) {

  "use strict";

  /**
   * Implement a live setting parser to prevent text editors from automatically
   * enabling buttons that are not allowed by this filter's configuration.
   */
  if (Drupal.filterConfiguration) {
    Drupal.filterConfiguration.liveSettingParsers.filter_html = {
      getRules: function () {
        var currentValue = $('#edit-filters-filter-html-settings-allowed-html').val();
        var rules = [], rule;

        // Build a FilterHTMLRule that reflects the hard-coded behavior that
        // strips all "style" attribute and all "on*" attributes.
        rule = new Drupal.FilterHTMLRule();
        rule.restrictedTags.tags = ['*'];
        rule.restrictedTags.forbidden.attributes = ['style', 'on*'];
        rules.push(rule);

        // Build a FilterHTMLRule that reflects the current settings.
        rule = new Drupal.FilterHTMLRule();
        var behavior = Drupal.behaviors.filterFilterHtmlUpdating;
        rule.allow = true;
        rule.tags = behavior._parseSetting(currentValue);
        rules.push(rule);

        return rules;
      }
    };
  }

  Drupal.behaviors.filterFilterHtmlUpdating = {

    // The form item containg the "Allowed HTML tags" setting.
    $allowedHTMLFormItem: null,

    // The description for the "Allowed HTML tags" field.
    $allowedHTMLDescription: null,

    // The user-entered tag list of $allowedHTMLFormItem.
    userTags: null,

    // The auto-created tag list thus far added.
    autoTags: null,

    // Track which new features have been added to the text editor.
    newFeatures: {},

    attach: function (context, settings) {
      var that = this;
      $(context).find('[name="filters[filter_html][settings][allowed_html]"]').once('filter-filter_html-updating', function () {
        that.$allowedHTMLFormItem = $(this);
        that.$allowedHTMLDescription = that.$allowedHTMLFormItem.closest('.form-item').find('.description');
        that.userTags = that._parseSetting(this.value);

        // Update the new allowed tags based on added text editor features.
        $(document)
          .on('drupalEditorFeatureAdded', function (e, feature) {
            that.newFeatures[feature.name] = feature.rules;
            that._updateAllowedTags();
          })
          .on('drupalEditorFeatureModified', function (e, feature) {
            if (that.newFeatures.hasOwnProperty(feature.name)) {
              that.newFeatures[feature.name] = feature.rules;
              that._updateAllowedTags();
            }
          })
          .on('drupalEditorFeatureRemoved', function (e, feature) {
            if (that.newFeatures.hasOwnProperty(feature.name)) {
              delete that.newFeatures[feature.name];
              that._updateAllowedTags();
            }
          });

        // When the allowed tags list is manually changed, update userTags.
        that.$allowedHTMLFormItem.on('change.updateUserTags', function () {
          that.userTags = _.difference(that._parseSetting(this.value), that.autoTags);
        });
      });
    },

    /**
     * Updates the "Allowed HTML tags" setting and shows an informative message.
     */
    _updateAllowedTags: function () {
      // Update the list of auto-created tags.
      this.autoTags = this._calculateAutoAllowedTags(this.userTags, this.newFeatures);

      // Remove any previous auto-created tag message.
      this.$allowedHTMLDescription.find('.editor-update-message').remove();

      // If any auto-created tags: insert message and update form item.
      if (this.autoTags.length > 0) {
        this.$allowedHTMLDescription.append(Drupal.theme('filterFilterHTMLUpdateMessage', this.autoTags));
        this.$allowedHTMLFormItem.val(this._generateSetting(this.userTags) + ' ' + this._generateSetting(this.autoTags));
      }
      // Restore to original state.
      else {
        this.$allowedHTMLFormItem.val(this._generateSetting(this.userTags));
      }
    },

    /**
     * Calculates which HTML tags the added text editor buttons need to work.
     *
     * The filter_html filter is only concerned with the required tags, not with
     * any properties, nor with each feature's "allowed" tags.
     *
     * @param Array userAllowedTags
     *   The list of user-defined allowed tags.
     * @param Object newFeatures
     *   A list of Drupal.EditorFeature objects' rules, keyed by their name.
     *
     * @return Array
     *   A list of new allowed tags.
     */
    _calculateAutoAllowedTags: function (userAllowedTags, newFeatures) {
      return _
        .chain(newFeatures)
        // Reduce multiple features' rules.
        .reduce(function (memo, featureRules) {
          // Reduce a single features' rules' required tags.
          return _.union(memo, _.reduce(featureRules, function (memo, featureRule) {
            return _.union(memo, featureRule.required.tags);
          }, []));
        }, [])
        // All new features' required tags are "new allowed tags", except
        // for those that are already allowed in the original allowed tags.
        .difference(userAllowedTags)
        .value();
    },

    /**
     * Parses the value of this.$allowedHTMLFormItem.
     *
     * @param String setting
     *   The string representation of the setting. e.g. "<p> <br> <a>"
     *
     * @return Array
     *   The array representation of the setting. e.g. ['p', 'br', 'a']
     */
    _parseSetting: function (setting) {
      return setting.length ? setting.substring(1, setting.length - 1).split('> <') : [];
    },

    /**
     * Generates the value of this.$allowedHTMLFormItem.
     *
     * @param Array setting
     *   The array representation of the setting. e.g. ['p', 'br', 'a']
     *
     * @return Array
     *   The string representation of the setting. e.g. "<p> <br> <a>"
     */
    _generateSetting: function (tags) {
      return tags.length ? '<' + tags.join('> <') + '>' : '';
    }

  };

  /**
   * Theme function for the filter_html update message.
   *
   * @param Array tags
   *   An array of the new tags that are to be allowed.
   * @return
   *   The corresponding HTML.
   */
  Drupal.theme.filterFilterHTMLUpdateMessage = function (tags) {
    var html = '';
    var tagList = '<' + tags.join('> <') + '>';
    html += '<p class="editor-update-message">';
    html += Drupal.t('Based on the text editor configuration, these tags have automatically been added: <strong>@tag-list</strong>.', { '@tag-list': tagList });
    html += '</p>';
    return html;
  };

})(jQuery, _, document, window);
