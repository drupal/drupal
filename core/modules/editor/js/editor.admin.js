/**
 * @file
 * Provides a JavaScript API to broadcast text editor configuration changes.
 *
 * Filter implementations may listen to the drupalEditorFeatureAdded,
 * drupalEditorFeatureRemoved, and drupalEditorFeatureRemoved events on document
 * to automatically adjust their settings based on the editor configuration.
 */

(function ($, _, Drupal, document) {

  "use strict";

  Drupal.editorConfiguration = {

    /**
     * Must be called by a specific text editor's configuration whenever a feature
     * is added by the user.
     *
     * Triggers the drupalEditorFeatureAdded event on the document, which receives
     * a Drupal.EditorFeature object.
     *
     * @param Drupal.EditorFeature feature
     *   A text editor feature object.
     */
    addedFeature: function (feature) {
      $(document).trigger('drupalEditorFeatureAdded', feature);
    },

    /**
     * Must be called by a specific text editor's configuration whenever a feature
     * is removed by the user.
     *
     * Triggers the drupalEditorFeatureRemoved event on the document, which
     * receives a Drupal.EditorFeature object.
     *
     * @param Drupal.EditorFeature feature
     *   A text editor feature object.
     */
    removedFeature: function (feature) {
      $(document).trigger('drupalEditorFeatureRemoved', feature);
    },

    /**
     * Must be called by a specific text editor's configuration whenever a feature
     * is modified, i.e. has different rules.
     *
     * For example when the "Bold" button is configured to use the <b> tag instead
     * of the <strong> tag.
     *
     * Triggers the drupalEditorFeatureModified event on the document, which
     * receives a Drupal.EditorFeature object.
     *
     * @param Drupal.EditorFeature feature
     *   A text editor feature object.
     */
    modifiedFeature: function (feature) {
      $(document).trigger('drupalEditorFeatureModified', feature);
    },

    /**
     * May be called by a specific text editor's configuration whenever a feature
     * is being added, to check whether it would require the filter settings to be
     * updated.
     *
     * The canonical use case is when a text editor is being enabled: preferably
     * this would not cause the filter settings to be changed; rather, the default
     * set of buttons (features) for the text editor should adjust itself to not
     * cause filter setting changes.
     *
     * Note: for filters to integrate with this functionality, it is necessary
     * that they implement Drupal.filterSettingsForEditors[filterID].getRules().
     *
     * @param Drupal.EditorFeature feature
     *   A text editor feature object.
     * @return Boolean
     *   Whether the given feature is allowed by the current filters.
     */
    featureIsAllowedByFilters: function (feature) {

      /**
       * Generate the universe U of possible values that can result from the
       * feature's rules' requirements.
       *
       * This generates an object of this form:
       *   var universe = {
     *     a: {
     *       'touchedByAllowedPropertyRule': false,
     *       'tag': false,
     *       'attributes:href': false,
     *       'classes:external': false,
     *     },
     *     strong: {
     *       'touchedByAllowedPropertyRule': false,
     *       'tag': false,
     *     },
     *     img: {
     *       'touchedByAllowedPropertyRule': false,
     *       'tag': false,
     *       'attributes:src': false
     *     }
     *   };
       *
       * In this example, the given text editor feature resulted in the above
       * universe, which shows that it must be allowed to generate the a, strong
       * and img tags. For the a tag, it must be able to set the "href" attribute
       * and the "external" class. For the strong tag, no further properties are
       * required. For the img tag, the "src" attribute is required.
       * The "tag" key is used to track whether that tag was explicitly allowed by
       * one of the filter's rules. The "touchedByAllowedPropertyRule" key is used
       * for state tracking that is essential for filterStatusAllowsFeature() to
       * be able to reason: when all of a filter's rules have been applied, and
       * none of the forbidden rules matched (which would have resulted in early
       * termination) yet the universe has not been made empty (which would be the
       * end result if everything in the universe were explicitly allowed), then
       * this piece of state data enables us to determine whether a tag whose
       * properties were not all explicitly allowed are in fact still allowed,
       * because its tag was explicitly allowed and there were no filter rules
       * applying "allowed tag property value" restrictions for this particular
       * tag.
       *
       * @see findPropertyValueOnTag()
       * @see filterStatusAllowsFeature()
       */
      function generateUniverseFromFeatureRequirements(feature) {
        var properties = ['attributes', 'styles', 'classes'];
        var universe = {};

        for (var r = 0; r < feature.rules.length; r++) {
          var featureRule = feature.rules[r];

          // For each tag required by this feature rule, create a basic entry in
          // the universe.
          var requiredTags = featureRule.required.tags;
          for (var t = 0; t < requiredTags.length; t++) {
            universe[requiredTags[t]] = {
              // Whether this tag was allowed or not.
              tag: false,
              // Whether any filter rule that applies to this tag had an allowed
              // property rule. i.e. will become true if >=1 filter rule has >=1
              // allowed property rule.
              touchedByAllowedPropertyRule: false,
              // Analogous, but for forbidden property rule.
              touchedBytouchedByForbiddenPropertyRule: false
            };
          }

          // If no required properties are defined for this rule, we can move on
          // to the next feature.
          if (emptyProperties(featureRule.required)) {
            continue;
          }

          // Expand the existing universe, assume that each tags' property value
          // is disallowed. If the filter rules allow everything in the feature's
          // universe, then the feature is allowed.
          for (var p = 0; p < properties.length; p++) {
            var property = properties[p];
            for (var pv = 0; pv < featureRule.required[property].length; pv++) {
              var propertyValue = featureRule.required[property];
              universe[requiredTags][property + ':' + propertyValue] = false;
            }
          }
        }

        return universe;
      }

      /**
       * Provided a section of a feature or filter rule, checks if no property
       * values are defined for all properties: attributes, classes and styles.
       */
      function emptyProperties(section) {
        return section.attributes.length === 0 && section.classes.length === 0 && section.styles.length === 0;
      }

      /**
       * Calls findPropertyValueOnTag on the given tag for every property value
       * that is listed in the "propertyValues" parameter. Supports the wildcard
       * tag.
       */
      function findPropertyValuesOnTag(universe, tag, property, propertyValues, allowing) {
        // Detect the wildcard case.
        if (tag === '*') {
          return findPropertyValuesOnAllTags(universe, property, propertyValues, allowing);
        }

        var atLeastOneFound = false;
        _.each(propertyValues, function (propertyValue) {
          if (findPropertyValueOnTag(universe, tag, property, propertyValue, allowing)) {
            atLeastOneFound = true;
          }
        });
        return atLeastOneFound;
      }

      /**
       * Calls findPropertyValuesOnAllTags for all tags in the universe.
       */
      function findPropertyValuesOnAllTags(universe, property, propertyValues, allowing) {
        var atLeastOneFound = false;
        _.each(_.keys(universe), function (tag) {
          if (findPropertyValuesOnTag(universe, tag, property, propertyValues, allowing)) {
            atLeastOneFound = true;
          }
        });
        return atLeastOneFound;
      }

      /**
       * Finds out if a specific property value (potentially containing wildcards)
       * exists on the given tag. When the "allowing" parameter equals true, the
       * universe will be updated if that specific property value exists.
       * Returns true if found, false otherwise.
       */
      function findPropertyValueOnTag(universe, tag, property, propertyValue, allowing) {
        // If the tag does not exist in the universe, then it definitely can't
        // have this specific property value.
        if (!_.has(universe, tag)) {
          return false;
        }

        var key = property + ':' + propertyValue;

        // Track whether a tag was touched by a filter rule that allows specific
        // property values on this particular tag.
        // @see generateUniverseFromFeatureRequirements
        if (allowing) {
          universe[tag].touchedByAllowedPropertyRule = true;
        }

        // The simple case: no wildcard in property value.
        if (_.indexOf(propertyValue, '*') === -1) {
          if (_.has(universe, tag) && _.has(universe[tag], key)) {
            if (allowing) {
              universe[tag][key] = true;
            }
            return true;
          }
          return false;
        }
        // The complex case: wildcard in property value.
        else {
          var atLeastOneFound = false;
          var regex = key.replace(/\*/g, "[^ ]*");
          _.each(_.keys(universe[tag]), function (key) {
            if (key.match(regex)) {
              atLeastOneFound = true;
              if (allowing) {
                universe[tag][key] = true;
              }
            }
          });
          return atLeastOneFound;
        }
      }

      /**
       * Deletes a tag from the universe if the tag itself and each of its
       * properties are marked as allowed.
       */
      function deleteFromUniverseIfAllowed(universe, tag) {
        // Detect the wildcard case.
        if (tag === '*') {
          return deleteAllTagsFromUniverseIfAllowed(universe);
        }
        if (_.has(universe, tag) && _.every(_.omit(universe[tag], 'touchedByAllowedPropertyRule'))) {
          delete universe[tag];
          return true;
        }
        return false;
      }

      /**
       * Calls deleteFromUniverseIfAllowed for all tags in the universe.
       */
      function deleteAllTagsFromUniverseIfAllowed(universe) {
        var atLeastOneDeleted = false;
        _.each(_.keys(universe), function (tag) {
          if (deleteFromUniverseIfAllowed(universe, tag)) {
            atLeastOneDeleted = true;
          }
        });
        return atLeastOneDeleted;
      }

      /**
       * Checks if any filter rule forbids either a tag or a tag property value
       * that exists in the universe.
       */
      function anyForbiddenFilterRuleMatches(universe, filterStatus) {
        var properties = ['attributes', 'styles', 'classes'];

        // Check if a tag in the universe is forbidden.
        var allRequiredTags = _.keys(universe);
        var filterRule, i;
        for (i = 0; i < filterStatus.rules.length; i++) {
          filterRule = filterStatus.rules[i];
          if (filterRule.allow === false) {
            if (_.intersection(allRequiredTags, filterRule.tags).length > 0) {
              return true;
            }
          }
        }

        // Check if a property value of a tag in the universe is forbidden.
        // For all filter rules…
        var j, k;
        for (i = 0; i < filterStatus.rules.length; i++) {
          filterRule = filterStatus.rules[i];
          // … if there are tags with restricted property values …
          if (filterRule.restrictedTags.tags.length && !emptyProperties(filterRule.restrictedTags.forbidden)) {
            // … for all those tags …
            for (j = 0; j < filterRule.restrictedTags.tags.length; j++) {
              var tag = filterRule.restrictedTags.tags[j];
              // … then iterate over all properties …
              for (k = 0; k < properties.length; k++) {
                var property = properties[k];
                // … and return true if just one of the forbidden property values
                // for this tag and property is listed in the universe.
                if (findPropertyValuesOnTag(universe, tag, property, filterRule.restrictedTags.forbidden[property], false)) {
                  return true;
                }
              }
            }
          }
        }

        return false;
      }

      /**
       * Applies every filter rule's explicit allowing of a tag or a tag property
       * value to the universe. Whenever both the tag and all of its required
       * property values are marked as explicitly allowed, they are deleted from
       * the universe.
       */
      function markAllowedTagsAndPropertyValues(universe, filterStatus) {
        var properties = ['attributes', 'styles', 'classes'];

        // Check if a tag in the universe is allowed.
        var filterRule, tag, i, j;
        for (i = 0; !_.isEmpty(universe) && i < filterStatus.rules.length; i++) {
          filterRule = filterStatus.rules[i];
          if (filterRule.allow === true) {
            for (j = 0; !_.isEmpty(universe) && j < filterRule.tags.length; j++) {
              tag = filterRule.tags[j];
              if (_.has(universe, tag)) {
                universe[tag].tag = true;
                deleteFromUniverseIfAllowed(universe, tag);
              }
            }
          }
        }

        // Check if a property value of a tag in the universe is allowed.
        // For all filter rules…
        var k;
        for (i = 0; !_.isEmpty(universe) && i < filterStatus.rules.length; i++) {
          filterRule = filterStatus.rules[i];
          // … if there are tags with restricted property values …
          if (filterRule.restrictedTags.tags.length && !emptyProperties(filterRule.restrictedTags.allowed)) {
            // … for all those tags …
            for (j = 0; !_.isEmpty(universe) && j < filterRule.restrictedTags.tags.length; j++) {
              tag = filterRule.restrictedTags.tags[j];
              // … then iterate over all properties …
              for (k = 0; k < properties.length; k++) {
                var property = properties[k];
                // … and try to delete this tag from the universe if just one of
                // the allowed property values for this tag and property is listed
                // in the universe. (Because everything might be allowed now.)
                if (findPropertyValuesOnTag(universe, tag, property, filterRule.restrictedTags.allowed[property], true)) {
                  deleteFromUniverseIfAllowed(universe, tag);
                }
              }
            }
          }
        }
      }

      /**
       * Checks whether the current status of a filter allows a specific feature
       * by building the universe of potential values from the feature's require-
       * ments and then checking whether anything in the filter prevents that.
       *
       * @see generateUniverseFromFeatureRequirements()
       */
      function filterStatusAllowsFeature(filterStatus, feature) {
        // An inactive filter by definition allows the feature.
        if (!filterStatus.active) {
          return true;
        }

        // A feature that specifies no rules has no HTML requirements and is hence
        // allowed by definition.
        if (feature.rules.length === 0) {
          return true;
        }

        // Analogously for a filter that specifies no rules.
        if (filterStatus.rules.length === 0) {
          return true;
        }

        // Generate the universe U of possible values that can result from the
        // feature's rules' requirements.
        var universe = generateUniverseFromFeatureRequirements(feature);

        // If anything that is in the universe (and is thus required by the
        // feature) is forbidden by any of the filter's rules, then this filter
        // does not allow this feature.
        if (anyForbiddenFilterRuleMatches(universe, filterStatus)) {
          return false;
        }

        // Mark anything in the universe that is allowed by any of the filter's
        // rules as allowed. If everything is explicitly allowed, then the
        // universe will become empty.
        markAllowedTagsAndPropertyValues(universe, filterStatus);

        // If there was at least one filter rule allowing tags, then everything in
        // the universe must be allowed for this feature to be allowed, and thus
        // by now it must be empty.
        // However, it is still possible that the filter allows the feature, due
        // to no rules for allowing tag property values and/or rules for
        // forbidding tag property values. For details: see the comments below.
        //
        // @see generateUniverseFromFeatureRequirements()
        if (_.some(_.pluck(filterStatus.rules, 'allow'))) {
          // If the universe is empty, then everything was explicitly allowed and
          // our job is done: this filter allows this feature!
          if (_.isEmpty(universe)) {
            return true;
          }
          // Otherwise, it is still possible that this feature is allowed.
          else {
            // Every tag must be explicitly allowed if there are filter rules
            // doing tag whitelisting.
            if (!_.every(_.pluck(universe, 'tag'))) {
              return false;
            }
            // Every tag was explicitly allowed, but since the universe is not
            // empty, one or more tag properties are disallowed. However, if only
            // blacklisting of tag properties was applied to these tags, and no
            // whitelisting was ever applied, then it's still fine: since none of
            // the tag properties were blacklisted, we got to this point, and since
            // no whitelisting was applied, it doesn't matter that the properties:
            // this could never have happened anyway.
            // It's only this late that we can know this for certain.
            else {
              var tags = _.keys(universe);
              // Figure out if there was any rule applying whitelisting tag
              // restrictions to each of the remaining tags.
              for (var i = 0; i < tags.length; i++) {
                var tag = tags[i];
                if (_.has(universe, tag)) {
                  if (universe[tag].touchedByAllowedPropertyRule === false) {
                    delete universe[tag];
                  }
                }
              }
              return _.isEmpty(universe);
            }
          }
        }
        // Otherwise, if all filter rules were doing blacklisting, then the sole
        // fact that we got to this point indicates that this filter allows for
        // everything that is required for this feature.
        else {
          return true;
        }
      }

      // If any filter's current status forbids the editor feature, return false.
      Drupal.filterConfiguration.update();
      for (var filterID in Drupal.filterConfiguration.statuses) {
        if (Drupal.filterConfiguration.statuses.hasOwnProperty(filterID)) {
          var filterStatus = Drupal.filterConfiguration.statuses[filterID];
          if (!(filterStatusAllowsFeature(filterStatus, feature))) {
            return false;
          }
        }
      }

      return true;
    }
  };

  /**
   * A text editor feature object. Initialized with the feature name.
   *
   * Contains a set of HTML rules (Drupal.EditorFeatureHTMLRule objects) that
   * describe which HTML tags, attributes, styles and classes are required (i.e.
   * essential for the feature to function at all) and which are allowed (i.e. the
   * feature may generate this, but they're not essential).
   *
   * It is necessary to allow for multiple HTML rules per feature: with just one
   * HTML rule per feature, there is not enough expressiveness to describe certain
   * cases. For example: a "table" feature would probably require the <table> tag,
   * and might allow for e.g. the "summary" attribute on that tag. However, the
   * table feature would also require the <tr> and <td> tags, but it doesn't make
   * sense to allow for a "summary" attribute on these tags. Hence these would
   * need to be split in two separate rules.
   *
   * HTML rules must be added with the addHTMLRule() method. A feature that has
   * zero HTML rules does not create or modify HTML.
   *
   * @param String name
   *   The name of the feature.
   *
   * @see Drupal.EditorFeatureHTMLRule
   */
  Drupal.EditorFeature = function (name) {
    this.name = name;
    this.rules = [];
  };

  /**
   * Adds a HTML rule to the list of HTML rules for this feature.
   *
   * @param Drupal.editorFeatureHTMLRule rule
   *   A text editor feature HTML rule.
   */
  Drupal.EditorFeature.prototype.addHTMLRule = function (rule) {
    this.rules.push(rule);
  };

  /**
   * Constructor for an editor feature HTML rule. Intended to be used in
   * combination with Drupal.EditorFeature.
   *
   * A text editor feature rule object describes both
   *  - required HTML tags, attributes, styles and classes: without these, the
   *    text editor feature is unable to function. It's possible that a
   *  - allowed HTML tags, attributes, styles and classes: these are optional in
   *    the strictest sense, but it is possible that the feature generates them.
   *
   * The structure can be very clearly seen below: there's a "required" and an
   * "allowed" key. For each of those, there are objects with the "tags",
   * "attributes", "styles" and "classes" keys. For all these keys the values are
   * initialized to the empty array. List each possible value as an array value.
   * Besides the "required" and "allowed" keys, there's an optional "raw" key: it
   * allows text editor implementations to optionally pass in their raw
   * representation instead of the Drupal-defined representation for HTML rules.
   *
   * Examples:
   *  - tags: ['<a>']
   *  - attributes: ['href', 'alt']
   *  - styles: ['color', 'text-decoration']
   *  - classes: ['external', 'internal']
   */
  Drupal.EditorFeatureHTMLRule = function () {
    this.required = { tags: [], attributes: [], styles: [], classes: [] };
    this.allowed = { tags: [], attributes: [], styles: [], classes: [] };
    this.raw = null;
  };

  /**
   * Constructor for a text filter status object. Initialized with the filter ID.
   *
   * Indicates whether the text filter is currently active (enabled) or not.
   *
   * Contains a set of HTML rules (Drupal.FilterHTMLRule objects) that describe
   * which HTML tags are allowed or forbidden. They can also describe for a set of
   * tags (or all tags) which attributes, styles and classes are allowed and which
   * are forbidden.
   *
   * It is necessary to allow for multiple HTML rules per feature, for analogous
   * reasons as Drupal.EditorFeature.
   *
   * HTML rules must be added with the addHTMLRule() method. A filter that has
   * zero HTML rules does not disallow any HTML.
   *
   * @param String name
   *   The name of the feature.
   *
   * @see Drupal.FilterHTMLRule
   */
  Drupal.FilterStatus = function (name) {
    this.name = name;
    this.active = false;
    this.rules = [];
  };

  /**
   * Adds a HTML rule to the list of HTML rules for this filter.
   *
   * @param Drupal.FilterHTMLRule rule
   *   A text filter HTML rule.
   */
  Drupal.FilterStatus.prototype.addHTMLRule = function (rule) {
    this.rules.push(rule);
  };

  /**
   * A text filter HTML rule object. Intended to be used in combination with
   * Drupal.FilterStatus.
   *
   * A text filter rule object describes
   *  1. allowed or forbidden tags: (optional) whitelist or blacklist HTML tags
   *  2. restricted tag properties: (optional) whitelist or blacklist attributes,
   *     styles and classes on a set of HTML tags.
   *
   * Typically, each text filter rule object does either 1 or 2, not both.
   *
   * The structure can be very clearly seen below:
   *  1. use the "tags" key to list HTML tags, and set the "allow" key to either
   *     true (to allow these HTML tags) or false (to forbid these HTML tags). If
   *     you leave the "tags" key's default value (the empty array), no
   *     restrictions are applied.
   *  2. all nested within the "restrictedTags" key: use the "tags" subkey to list
   *     HTML tags to which you want to apply property restrictions, then use the
   *     "allowed" subkey to whitelist specific property values, and similarly use
   *     the "forbidden" subkey to blacklist specific property values.
   *
   * Examples:
   *  - Whitelist the "p", "strong" and "a" HTML tags:
   *    {
 *      tags: ['p', 'strong', 'a'],
 *      allow: true,
 *      restrictedTags: {
 *        tags: [],
 *        allowed: { attributes: [], styles: [], classes: [] },
 *        forbidden: { attributes: [], styles: [], classes: [] }
 *      }
 *    }
   *  - For the "a" HTML tag, only allow the "href" attribute and the "external"
   *    class and disallow the "target" attribute.
   *    {
 *      tags: [],
 *      allow: null,
 *      restrictedTags: {
 *        tags: ['a'],
 *        allowed: { attributes: ['href'], styles: [], classes: ['external'] },
 *        forbidden: { attributes: ['target'], styles: [], classes: [] }
 *      }
 *    }
   *  - For all tags, allow the "data-*" attribute (that is, any attribute that
   *    begins with "data-").
   *    {
 *      tags: [],
 *      allow: null,
 *      restrictedTags: {
 *        tags: ['*'],
 *        allowed: { attributes: ['data-*'], styles: [], classes: [] },
 *        forbidden: { attributes: [], styles: [], classes: [] }
 *      }
 *    }
   */
  Drupal.FilterHTMLRule = function () {
    return {
      // Allow or forbid tags.
      tags: [],
      allow: null,
      // Apply restrictions to properties set on tags.
      restrictedTags: {
        tags: [],
        allowed: { attributes: [], styles: [], classes: [] },
        forbidden: { attributes: [], styles: [], classes: [] }
      }
    };
  };

  /**
   * Tracks the configuration of all text filters in Drupal.FilterStatus objects
   * for Drupal.editorConfiguration.featureIsAllowedByFilters().
   */
  Drupal.filterConfiguration = {

    /**
     * Drupal.FilterStatus objects, keyed by filter ID.
     */
    statuses: {},

    /**
     * Live filter setting parsers, keyed by filter ID, for those filters that
     * implement it.
     *
     * Filters should load the implementing JavaScript on the filter configuration
     * form and implement Drupal.filterSettings[filterID].getRules(), which should
     * return an array of Drupal.FilterHTMLRule objects.
     */
    liveSettingParsers: {},

    /**
     * Updates all Drupal.FilterStatus objects to reflect the current state.
     *
     * Automatically checks whether a filter is currently enabled or not. To
     * support more finegrained
     *
     * If a filter implements a live setting parser, then that will be used to
     * keep the HTML rules for the Drupal.FilterStatus object up-to-date.
     */
    update: function () {
      for (var filterID in Drupal.filterConfiguration.statuses) {
        if (Drupal.filterConfiguration.statuses.hasOwnProperty(filterID)) {
          // Update status.
          Drupal.filterConfiguration.statuses[filterID].active = $('[name="filters[' + filterID + '][status]"]').is(':checked');

          // Update current rules.
          if (Drupal.filterConfiguration.liveSettingParsers[filterID]) {
            Drupal.filterConfiguration.statuses[filterID].rules = Drupal.filterConfiguration.liveSettingParsers[filterID].getRules();
          }
        }
      }
    }

  };

  /**
   * Initializes Drupal.filterConfiguration.
   */
  Drupal.behaviors.initializeFilterConfiguration = {
    attach: function (context, settings) {
      var $context = $(context);

      $context.find('#filters-status-wrapper input.form-checkbox').once('filter-editor-status', function () {
        var $checkbox = $(this);
        var nameAttribute = $checkbox.attr('name');

        // The filter's checkbox has a name attribute of the form
        // "filters[<name of filter>][status]", parse "<name of filter>" from it.
        var filterID = nameAttribute.substring(8, nameAttribute.indexOf(']'));

        // Create a Drupal.FilterStatus object to track the state (whether it's
        // active or not and its current settings, if any) of each filter.
        Drupal.filterConfiguration.statuses[filterID] = new Drupal.FilterStatus(filterID);
      });
    }
  };

})(jQuery, _, Drupal, document);
