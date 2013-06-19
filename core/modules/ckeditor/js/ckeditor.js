(function (Drupal, CKEDITOR, $) {

"use strict";

Drupal.editors.ckeditor = {

  attach: function (element, format) {
    this._loadExternalPlugins(format);
    this._ACF_HACK_to_support_blacklisted_attributes(element, format);
    return !!CKEDITOR.replace(element, format.editorSettings);
  },

  detach: function (element, format, trigger) {
    var editor = CKEDITOR.dom.element.get(element).getEditor();
    if (editor) {
      if (trigger === 'serialize') {
        editor.updateElement();
      }
      else {
        editor.destroy();
        element.removeAttribute('contentEditable');
      }
    }
    return !!editor;
  },

  onChange: function (element, callback) {
    var editor = CKEDITOR.dom.element.get(element).getEditor();
    if (editor) {
      var changed = function () {
        window.setTimeout(function () {
          callback(editor.getData());
        }, 0);
      };
      // @todo Make this more elegant once http://dev.ckeditor.com/ticket/9794
      // is fixed.
      editor.on('key', changed);
      editor.on('paste', changed);
      editor.on('afterCommandExec', changed);
    }
    return !!editor;
  },

  attachInlineEditor: function (element, format, mainToolbarId, floatedToolbarId) {
    this._loadExternalPlugins(format);
    this._ACF_HACK_to_support_blacklisted_attributes(element, format);

    var settings = $.extend(true, {}, format.editorSettings);

    // If a toolbar is already provided for "true WYSIWYG" (in-place editing),
    // then use that toolbar instead: override the default settings to render
    // CKEditor UI's top toolbar into mainToolbar, and don't render the bottom
    // toolbar at all. (CKEditor doesn't need a floated toolbar.)
    if (mainToolbarId) {
      var settingsOverride = {
        extraPlugins: 'sharedspace',
        removePlugins: 'floatingspace,elementspath',
        sharedSpaces: {
          top: mainToolbarId
        }
      };

      // Find the "Source" button, if any, and replace it with "Sourcedialog".
      // (The 'sourcearea' plugin only works in CKEditor's iframe mode.)
      var sourceButtonFound = false;
      for (var i = 0; !sourceButtonFound && i < settings.toolbar.length; i++) {
        if (settings.toolbar[i] !== '/') {
          for (var j = 0; !sourceButtonFound && j < settings.toolbar[i].items.length; j++) {
            if (settings.toolbar[i].items[j] === 'Source') {
              sourceButtonFound = true;
              // Swap sourcearea's "Source" button for sourcedialog's.
              settings.toolbar[i].items[j] = 'Sourcedialog';
              settingsOverride.extraPlugins += ',sourcedialog';
              settingsOverride.removePlugins += ',sourcearea';
            }
          }
        }
      }

      settings.extraPlugins += ',' + settingsOverride.extraPlugins;
      settings.removePlugins += ',' + settingsOverride.removePlugins;
      settings.sharedSpaces = settingsOverride.sharedSpaces;
    }

    // CKEditor requires an element to already have the contentEditable
    // attribute set to "true", otherwise it won't attach an inline editor.
    element.setAttribute('contentEditable', 'true');

    return !!CKEDITOR.inline(element, settings);
  },

  _loadExternalPlugins: function (format) {
    var externalPlugins = format.editorSettings.drupalExternalPlugins;
    // Register and load additional CKEditor plugins as necessary.
    if (externalPlugins) {
      for (var pluginName in externalPlugins) {
        if (externalPlugins.hasOwnProperty(pluginName)) {
          CKEDITOR.plugins.addExternal(pluginName, externalPlugins[pluginName], '');
        }
      }
      delete format.editorSettings.drupalExternalPlugins;
    }
  },

  /**
   * This is a huge hack to do ONE thing: to allow Drupal to fully mandate what
   * CKEditor should allow by setting CKEditor's allowedContent setting. The
   * problem is that allowedContent only allows for whitelisting, whereas
   * Drupal's default HTML filtering (the filter_html filter) also blacklists
   * the "style" and "on*" ("onClick" etc.) attributes.
   *
   * So this function hacks in explicit support for Drupal's filter_html's need
   * to blacklist specifically those attributes, until ACF supports blacklisting
   * of properties: http://dev.ckeditor.com/ticket/10276.
   *
   * Limitations:
   *   - This does not support blacklisting of other attributes, it's only
   *     intended to implement filter_html's blacklisted attributes.
   *   - This is only a temporary work-around; it assumes the filter_html
   *     filter is being used whenever *any* restriction exists. This is a valid
   *     assumption for the default text formats in Drupal 8 core, but obviously
   *     won't work for release.
   *
   * This is the only way we could get https://drupal.org/node/1936392 committed
   * before Drupal 8 code freeze on July 1, 2013. CKEditor has committed to
   * explicitly supporting this in some way.
   *
   * @todo D8 remove this once http://dev.ckeditor.com/ticket/10276 is done.
   */
  _ACF_HACK_to_support_blacklisted_attributes: function (element, format) {
    function override(rule) {
      var oldValue = rule.attributes;
      function filter_html_override_attributes (attribute) {
        // Disallow the "style" and "on*" attributes on any tag.
        if (attribute === 'style' || attribute.substr(0, 2) === 'on') {
          return false;
        }

        // Ensure the original logic still runs, if any.
        if (typeof oldValue === 'function') {
          return oldValue(attribute);
        }
        else if (typeof oldValue === 'boolean') {
          return oldValue;
        }

        // Otherwise, accept this attribute.
        return true;
      }
      rule.attributes = filter_html_override_attributes;
    }

    CKEDITOR.once('instanceLoaded', function(e) {
      if (e.editor.name === element.id) {
        // If everything is allowed, everything is allowed.
        if (format.editorSettings.allowedContent === true) {
          return;
        }
        // Otherwise, assume Drupal's filter_html filter is being used.
        else {
          // Get the filter object (ACF).
          var filter = e.editor.filter;
          // Find the "config" rule (the one caused by the allowedContent
          // setting) for each HTML tag, and override its "attributes" value.
          for (var el in filter._.rules.elements) {
            if (filter._.rules.elements.hasOwnProperty(el)) {
              for (var i = 0; i < filter._.rules.elements[el].length; i++) {
                if (filter._.rules.elements[el][i].featureName === 'config') {
                  override(filter._.rules.elements[el][i]);
                }
              }
            }
          }
        }
      }
    });
  }
};

})(Drupal, CKEDITOR, jQuery);
