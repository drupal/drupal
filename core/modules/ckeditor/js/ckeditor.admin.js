(function ($, Drupal, drupalSettings, CKEDITOR, _) {

"use strict";

Drupal.ckeditor = Drupal.ckeditor || {};

// Aria-live element for speaking application state.
var $messages;

Drupal.behaviors.ckeditorAdmin = {
  attach: function (context) {
    var $context = $(context);
    var $ckeditorToolbar = $context.find('.ckeditor-toolbar-configuration').once('ckeditor-toolbar');
    var featuresMetadata = {};
    var hiddenCKEditorConfig = drupalSettings.ckeditor.hiddenCKEditorConfig;

    /**
     * Event callback for keypress. Move buttons based on arrow keys.
     */
    function adminToolbarMoveButton (event) {
      var $target = $(event.currentTarget);
      var $button = $target.parent();
      var $currentRow = $button.closest('.ckeditor-buttons');
      var $destinationRow = null;
      var destinationPosition = $button.index();

      switch (event.keyCode) {
        case 37: // Left arrow.
        case 63234: // Safari left arrow.
          $destinationRow = $currentRow;
          destinationPosition -= rtl;
          break;

        case 38: // Up arrow.
        case 63232: // Safari up arrow.
          $destinationRow = $($toolbarRows[$toolbarRows.index($currentRow) - 1]);
          break;

        case 39: // Right arrow.
        case 63235: // Safari right arrow.
          $destinationRow = $currentRow;
          destinationPosition += rtl;
          break;

        case 40: // Down arrow.
        case 63233: // Safari down arrow.
          $destinationRow = $($toolbarRows[$toolbarRows.index($currentRow) + 1]);
      }

      if ($destinationRow && $destinationRow.length) {
        // Detach the button from the DOM so its position doesn't interfere.
        $button.detach();
        // Move the button before the button whose position it should occupy.
        var $targetButton = $destinationRow.children(':eq(' + destinationPosition + ')');
        if ($targetButton.length) {
          $targetButton.before($button);
        }
        else {
          $destinationRow.append($button);
        }
        // Post the update to the aria-live message element.
        $messages.text(Drupal.t('moved to @row, position @position of @totalPositions', {
          '@row': getRowInfo($destinationRow),
          '@position': (destinationPosition + 1),
          '@totalPositions': $destinationRow.children().length
        }));
        // Update the toolbar value field.
        adminToolbarValue(event, { item: $button });
      }
      event.preventDefault();
    }

    /**
     * Event callback for keyup. Move a separator into the active toolbar.
     */
    function adminToolbarMoveSeparator (event) {
      switch (event.keyCode) {
        case 38: // Up arrow.
        case 63232: // Safari up arrow.
          var $button = $(event.currentTarget).parent().clone().appendTo($toolbarRows.eq(-2));
          adminToolbarValue(event, { item: $button });
          event.preventDefault();
      }
    }

    /**
     * Provide help when a button is clicked on.
     */
    function adminToolbarButtonHelp (event) {
      var $link = $(event.currentTarget);
      var $button = $link.parent();
      var $currentRow = $button.closest('.ckeditor-buttons');
      var enabled = $button.closest('.ckeditor-toolbar-active').length > 0;
      var position = $button.index() + 1; // 1-based index for humans.
      var rowNumber = $toolbarRows.index($currentRow) + 1;
      var type = event.data.type;
      var message;

      if (enabled) {
        if (type === 'separator') {
          message = Drupal.t('Separators are used to visually split individual buttons. This @name is currently enabled, in row @row and position @position.', { '@name': $link.attr('aria-label'), '@row': rowNumber, '@position': position }) + "\n\n" + Drupal.t('Drag and drop the separator or use the keyboard arrow keys to change the position of this separator.');
        }
        else {
          message = Drupal.t('The @name button is currently enabled, in row @row and position @position.', { '@name': $link.attr('aria-label'), '@row': rowNumber, '@position': position }) + "\n\n" + Drupal.t('Drag and drop the buttons or use the keyboard arrow keys to change the position of this button.');
        }
      }
      else {
        if (type === 'separator') {
          message = Drupal.t('Separators are used to visually split individual buttons. This @name is currently disabled.', { '@name': $link.attr('aria-label') }) + "\n\n" + Drupal.t('Drag the button or use the up arrow key to move this separator into the active toolbar. You may add multiple separators to each row.');
        }
        else {
          message = Drupal.t('The @name button is currently disabled.', { '@name': $link.attr('aria-label') }) + "\n\n" + Drupal.t('Drag the button or use the up arrow key to move this button into the active toolbar.');
        }
      }
      $messages.text(message);
      $link.focus();
      event.preventDefault();
    }

    /**
     * Add a new row of buttons.
     */
    function adminToolbarAddRow (event) {
      var $this = $(event.currentTarget);
      var $rows = $this.closest('.ckeditor-toolbar-active').find('.ckeditor-buttons');
      var $rowNew = $rows.last().clone().empty().sortable(sortableSettings);
      $rows.last().after($rowNew);
      $toolbarRows = $toolbarAdmin.find('.ckeditor-buttons');
      $this.siblings('a').show();
      redrawToolbarGradient();
      // Post the update to the aria-live message element.
      $messages.text(Drupal.t('row number @count added.', {'@count': ($rows.length + 1)}));
      event.preventDefault();
    }

    /**
     * Remove a row of buttons.
     */
    function adminToolbarRemoveRow (event) {
      var $this = $(event.currentTarget);
      var $rows = $this.closest('.ckeditor-toolbar-active').find('.ckeditor-buttons');
      if ($rows.length === 2) {
        $this.hide();
      }
      if ($rows.length > 1) {
        var $lastRow = $rows.last();
        var $disabledButtons = $ckeditorToolbar.find('.ckeditor-toolbar-disabled .ckeditor-buttons');
        $lastRow.children(':not(.ckeditor-multiple-button)').prependTo($disabledButtons);
        $lastRow.sortable('destroy').remove();
        $toolbarRows = $toolbarAdmin.find('.ckeditor-buttons');
        redrawToolbarGradient();
      }
      // Post the update to the aria-live message element.
      $messages.text(Drupal.formatPlural($rows.length - 1, 'row removed. 1 row remaining.', 'row removed. @count rows remaining.'));
      event.preventDefault();
    }

    /**
     * Browser quirk work-around to redraw CSS3 gradients.
     */
    function redrawToolbarGradient () {
      $ckeditorToolbar.find('.ckeditor-toolbar-active').css('position', 'relative');
      window.setTimeout(function () {
        $ckeditorToolbar.find('.ckeditor-toolbar-active').css('position', '');
      }, 10);
    }

    /**
     * jQuery Sortable stop event. Save updated toolbar positions to the
     * textarea.
     */
    function adminToolbarValue (event, ui) {
      var oldToolbarConfig = JSON.parse($textarea.val());

      // Update the toolbar config after updating a sortable.
      var toolbarConfig = [];
      var $button = ui.item;
      $button.find('a').focus();
      $ckeditorToolbar.find('.ckeditor-toolbar-active ul').each(function () {
        var $rowButtons = $(this).find('li');
        var rowConfig = [];
        if ($rowButtons.length) {
          $rowButtons.each(function () {
            rowConfig.push(this.getAttribute('data-button-name'));
          });
          toolbarConfig.push(rowConfig);
        }
      });
      $textarea.val(JSON.stringify(toolbarConfig, null, '  '));

      if (!ui.silent) {
        // Determine whether we should trigger an event.
        var prev = _.flatten(oldToolbarConfig);
        var next = _.flatten(toolbarConfig);
        if (prev.length !== next.length) {
          $ckeditorToolbar
          .find('.ckeditor-toolbar-active')
          .trigger('CKEditorToolbarChanged', [
            (prev.length < next.length) ? 'added' : 'removed',
            _.difference(_.union(prev, next), _.intersection(prev, next))[0]
          ]);
        }
      }
    }

    /**
     * Asynchronously retrieve the metadata for all available CKEditor features.
     *
     * In order to get a list of all features needed by CKEditor, we create a
     * hidden CKEditor instance, then check the CKEditor's "allowedContent"
     * filter settings. Because creating an instance is expensive, a callback
     * must be provided that will receive a hash of Drupal.EditorFeature
     * features keyed by feature (button) name.
     */
    function getCKEditorFeatures(CKEditorConfig, callback) {
      var getProperties = function (CKEPropertiesList) {
        return (_.isObject(CKEPropertiesList)) ? _.keys(CKEPropertiesList) : [];
      };

      var convertCKERulesToEditorFeature = function (feature, CKEFeatureRules) {
        for (var i = 0; i < CKEFeatureRules.length; i++) {
          var CKERule = CKEFeatureRules[i];
          var rule = new Drupal.EditorFeatureHTMLRule();

          // Tags.
          var tags = getProperties(CKERule.elements);
          rule.required.tags = (CKERule.propertiesOnly) ? [] : tags;
          rule.allowed.tags = tags;
          // Attributes.
          rule.required.attributes = getProperties(CKERule.requiredAttributes);
          rule.allowed.attributes = getProperties(CKERule.attributes);
          // Styles.
          rule.required.styles = getProperties(CKERule.requiredStyles);
          rule.allowed.styles = getProperties(CKERule.styles);
          // Classes.
          rule.required.classes = getProperties(CKERule.requiredClasses);
          rule.allowed.classes = getProperties(CKERule.classes);
          // Raw.
          rule.raw = CKERule;

          feature.addHTMLRule(rule);
        }
      };

      // Create hidden CKEditor with all features enabled, retrieve metadata.
      // @see \Drupal\ckeditor\Plugin\editor\editor\CKEditor::settingsForm.
      var hiddenCKEditorID = 'ckeditor-hidden';
      if (CKEDITOR.instances[hiddenCKEditorID]) {
        CKEDITOR.instances[hiddenCKEditorID].destroy(true);
      }
      // Load external plugins, if any.
      if (hiddenCKEditorConfig.drupalExternalPlugins) {
        var externalPlugins = hiddenCKEditorConfig.drupalExternalPlugins;
        for (var pluginName in externalPlugins) {
          if (externalPlugins.hasOwnProperty(pluginName)) {
            CKEDITOR.plugins.addExternal(pluginName, externalPlugins[pluginName], '');
          }
        }
      }
      CKEDITOR.inline($('#' + hiddenCKEditorID).get(0), CKEditorConfig);

      // Once the instance is ready, retrieve the allowedContent filter rules
      // and convert them to Drupal.EditorFeature objects.
      CKEDITOR.once('instanceReady', function (e) {
        if (e.editor.name === hiddenCKEditorID) {
          // First collect all CKEditor allowedContent rules.
          var CKEFeatureRulesMap = {};
          var rules = e.editor.filter.allowedContent;
          var rule, name;
          for (var i = 0; i < rules.length; i++) {
            rule = rules[i];
            name = rule.featureName || ':(';
            if (!CKEFeatureRulesMap[name]) {
              CKEFeatureRulesMap[name] = [];
            }
            CKEFeatureRulesMap[name].push(rule);
          }

          // Now convert these to Drupal.EditorFeature objects.
          var features = {};
          for (var featureName in CKEFeatureRulesMap) {
            if (CKEFeatureRulesMap.hasOwnProperty(featureName)) {
              var feature = new Drupal.EditorFeature(featureName);
              convertCKERulesToEditorFeature(feature, CKEFeatureRulesMap[featureName]);
              features[featureName] = feature;
            }
          }

          callback(features);
        }
      });
    }

    /**
     * Retrieves the feature for a given button from featuresMetadata. Returns
     * false if the given button is in fact a divider.
     */
    function getFeatureForButton (button) {
      // Return false if the button being added is a divider.
      if (button === '|' || button === '-') {
        return false;
      }

      // Get a Drupal.editorFeature object that contains all metadata for
      // the feature that was just added or removed. Not every feature has
      // such metadata.
      var featureName = button.toLowerCase();
      if (!featuresMetadata[featureName]) {
        featuresMetadata[featureName] = new Drupal.EditorFeature(featureName);
      }
      return featuresMetadata[featureName];
    }

    /**
     * Sets up broadcasting of CKEditor toolbar configuration changes.
     */
    function broadcastConfigurationChanges ($ckeditorToolbar) {
      $ckeditorToolbar
        .find('.ckeditor-toolbar-active')
        // Listen for CKEditor toolbar configuration changes. When a button is
        // added/removed, call an appropriate Drupal.editorConfiguration method.
        .on('CKEditorToolbarChanged.ckeditorAdmin', function (e, action, button) {
          var feature = getFeatureForButton(button);

          // Early-return if the button being added is a divider.
          if (feature === false) {
            return;
          }

          // Trigger a standardized text editor configuration event to indicate
          // whether a feature was added or removed, so that filters can react.
          var event = (action === 'added') ? 'addedFeature' : 'removedFeature';
          Drupal.editorConfiguration[event](feature);
        })
        // Listen for CKEditor plugin settings changes. When a plugin setting is
        // changed, rebuild the CKEditor features metadata.
        .on('CKEditorPluginSettingsChanged.ckeditorAdmin', function (e, settingsChanges) {
          // Update hidden CKEditor configuration.
          for (var key in settingsChanges) {
            if (settingsChanges.hasOwnProperty(key)) {
              hiddenCKEditorConfig[key] = settingsChanges[key];
            }
          }

          // Retrieve features for the updated hidden CKEditor configuration.
          getCKEditorFeatures(hiddenCKEditorConfig, function (features) {
            // Trigger a standardized text editor configuration event for each
            // feature that was modified by the configuration changes.
            for (var name in features) {
              if (features.hasOwnProperty(name)) {
                var feature = features[name];
                if (featuresMetadata.hasOwnProperty(name) && !_.isEqual(featuresMetadata[name], feature)) {
                  Drupal.editorConfiguration.modifiedFeature(feature);
                }
              }
            }
            // Update the CKEditor features metadata.
            featuresMetadata = features;
          });
        });
    }

    if ($ckeditorToolbar.length) {
      var $textareaWrapper = $ckeditorToolbar.find('.form-item-editor-settings-toolbar-buttons').hide();
      var $textarea = $textareaWrapper.find('textarea');
      var $toolbarAdmin = $(drupalSettings.ckeditor.toolbarAdmin);
      var sortableSettings = {
        connectWith: '.ckeditor-buttons',
        placeholder: 'ckeditor-button-placeholder',
        forcePlaceholderSize: true,
        tolerance: 'pointer',
        cursor: 'move',
        stop: adminToolbarValue
      };
      // Add the toolbar to the page.
      $toolbarAdmin.insertAfter($textareaWrapper);

      // Then determine if this is RTL or not.
      var rtl = $toolbarAdmin.css('direction') === 'rtl' ? -1 : 1;
      var $toolbarRows = $toolbarAdmin.find('.ckeditor-buttons');

      // Add the drag and drop functionality.
      $toolbarRows.sortable(sortableSettings);
      $toolbarAdmin.find('.ckeditor-multiple-buttons li').draggable({
        connectToSortable: '.ckeditor-toolbar-active .ckeditor-buttons',
        helper: 'clone'
      });

      // Add keyboard arrow support.
      $toolbarAdmin.on('keyup.ckeditorMoveButton', '.ckeditor-buttons a', adminToolbarMoveButton);
      $toolbarAdmin.on('keyup.ckeditorMoveSeparator', '.ckeditor-multiple-buttons a', adminToolbarMoveSeparator);

      // Add click for help.
      $toolbarAdmin.on('click.ckeditorClickButton', '.ckeditor-buttons a', { type: 'button' }, adminToolbarButtonHelp);
      $toolbarAdmin.on('click.ckeditorClickSeparator', '.ckeditor-multiple-buttons a', { type: 'separator' }, adminToolbarButtonHelp);

      // Add/remove row button functionality.
      $toolbarAdmin.on('click.ckeditorAddRow', 'a.ckeditor-row-add', adminToolbarAddRow);
      $toolbarAdmin.on('click.ckeditorAddRow', 'a.ckeditor-row-remove', adminToolbarRemoveRow);
      if ($toolbarAdmin.find('.ckeditor-toolbar-active ul').length > 1) {
        $toolbarAdmin.find('a.ckeditor-row-remove').hide();
      }

      // Add aural UI focus updates when for individual toolbars.
      $toolbarAdmin.on('focus.ckeditor', '.ckeditor-buttons', grantRowFocus);
      // Identify the aria-live element for interaction updates for screen
      // readers.
      $messages = $('#ckeditor-button-configuration-aria-live');

      getCKEditorFeatures(hiddenCKEditorConfig, function (features) {
        featuresMetadata = features;

        // Ensure that toolbar configuration changes are broadcast.
        broadcastConfigurationChanges($ckeditorToolbar);

        // Initialization: not all of the default toolbar buttons may be allowed
        // by the current filter settings. Remove any of the default toolbar
        // buttons that require more permissive filter settings. The remaining
        // default toolbar buttons are marked as "added".
        var $activeToolbar = $ckeditorToolbar.find('.ckeditor-toolbar-active');
        var existingButtons = _.unique(_.flatten(JSON.parse($textarea.val())));
        for (var i = 0; i < existingButtons.length; i++) {
          var button = existingButtons[i];
          var feature = getFeatureForButton(button);

          // Skip dividers.
          if (feature === false) {
            continue;
          }

          if (Drupal.editorConfiguration.featureIsAllowedByFilters(feature)) {
            // Default toolbar buttons are in fact "added features".
            $activeToolbar.trigger('CKEditorToolbarChanged', ['added', existingButtons[i]]);
          }
          else {
            // Move the button element from the active the active toolbar to the
            // list of available buttons.
            var $button = $('.ckeditor-toolbar-active > ul > li[data-button-name="' + button + '"]')
              .detach()
              .appendTo('.ckeditor-toolbar-disabled > ul');
            // Update the toolbar value field.
            adminToolbarValue({}, { silent: true, item: $button});
          }
        }
      });
    }
  },
  detach: function (context, settings, trigger) {
    // Early-return if the trigger for detachment is something else than unload.
    if (trigger !== 'unload') {
      return;
    }

    // We're detaching because CKEditor as text editor has been disabled; this
    // really means that all CKEditor toolbar buttons have been removed. Hence,
    // all editor features will be removed, so any reactions from filters will
    // be undone.
    var $ckeditorToolbar = $(context).find('.ckeditor-toolbar-configuration.ckeditor-toolbar-processed');
    if ($ckeditorToolbar.length) {
      var value = $ckeditorToolbar
        .find('.form-item-editor-settings-toolbar-buttons')
        .find('textarea')
        .val();
      var $activeToolbar = $ckeditorToolbar.find('.ckeditor-toolbar-active');
      var buttons = _.unique(_.flatten(JSON.parse(value)));
      for (var i = 0; i < buttons.length; i++) {
        $activeToolbar.trigger('CKEditorToolbarChanged', ['removed', buttons[i]]);
      }
    }
  }
};

/**
 * Returns a string describing the type and index of a toolbar row.
 *
 * @param {jQuery} $row
 *   A jQuery object containing a .ckeditor-button row.
 *
 * @return {String}
 *   A string describing the type and index of a toolbar row.
 */
function getRowInfo ($row) {
  var output = '';
  var row;
  // Determine if this is an active row or an available row.
  if ($row.closest('.ckeditor-toolbar-disabled').length > 0) {
    row = $('.ckeditor-toolbar-disabled').find('.ckeditor-buttons').index($row) + 1;
    output += Drupal.t('available button row @row', {'@row': row});
  }
  else {
    row = $('.ckeditor-toolbar-active').find('.ckeditor-buttons').index($row) + 1;
    output += Drupal.t('active button row @row', {'@row': row});
  }
  return output;
}

/**
 * Applies or removes the focused class to a toolbar row.
 *
 * When a button in a toolbar is focused, focus is triggered on the containing
 * toolbar row. When a row is focused, the state change is announced through
 * the aria-live message area.
 *
 * @param {jQuery} event
 *   A jQuery event.
 */
function grantRowFocus (event) {
  var $row = $(event.currentTarget);
  // Remove the focused class from all other toolbars.
  $('.ckeditor-buttons.focused').not($row).removeClass('focused');
  // Post the update to the aria-live message element.
  if (!$row.hasClass('focused')) {
    // Indicate that the current row has focus.
    $row.addClass('focused');
    $messages.text(Drupal.t('@row', {'@row': getRowInfo($row)}));
  }
}

/**
 * Automatically shows/hides settings of buttons-only CKEditor plugins.
 */
Drupal.behaviors.ckeditorAdminButtonPluginSettings = {
  attach: function (context) {
    var $context = $(context);
    var $ckeditorPluginSettings = $context.find('#ckeditor-plugin-settings').once('ckeditor-plugin-settings');
    if ($ckeditorPluginSettings.length) {
      // Hide all button-dependent plugin settings initially.
      $ckeditorPluginSettings.find('[data-ckeditor-plugin-id]').each(function () {
        var $this = $(this);
        if ($this.data('verticalTab')) {
          $this.data('verticalTab').tabHide();
        }
        else {
          // On very narrow viewports, Vertical Tabs are disabled.
          $this.hide();
        }
        $this.data('ckeditorButtonPluginSettingsActiveButtons', []);
      });

      // Whenever a button is added or removed, check if we should show or hide
      // the corresponding plugin settings. (Note that upon initialization, each
      // button that already is part of the toolbar still is considered "added",
      // hence it also works correctly for buttons that were added previously.)
      $context
        .find('.ckeditor-toolbar-active')
        .off('CKEditorToolbarChanged.ckeditorAdminPluginSettings')
        .on('CKEditorToolbarChanged.ckeditorAdminPluginSettings', function (event, action, button) {
          var $pluginSettings = $ckeditorPluginSettings
            .find('[data-ckeditor-buttons~=' + button + ']');

          // No settings for this button.
          if ($pluginSettings.length === 0) {
            return;
          }

          var verticalTab = $pluginSettings.data('verticalTab');
          var activeButtons = $pluginSettings.data('ckeditorButtonPluginSettingsActiveButtons');
          if (action === 'added') {
            activeButtons.push(button);
            // Show this plugin's settings if >=1 of its buttons are active.
            if (verticalTab) {
              verticalTab.tabShow();
            }
            else {
              // On very narrow viewports, Vertical Tabs remain fieldsets.
              $pluginSettings.show();
            }

          }
          else {
            // Remove this button from the list of active buttons.
            activeButtons.splice(activeButtons.indexOf(button), 1);
            // Show this plugin's settings 0 of its buttons are active.
            if (activeButtons.length === 0) {
              if (verticalTab) {
                verticalTab.tabHide();
              }
              else {
                // On very narrow viewports, Vertical Tabs are disabled.
                $pluginSettings.hide();
              }
            }
          }
          $pluginSettings.data('ckeditorButtonPluginSettingsActiveButtons', activeButtons);
        });
    }
  }
};

})(jQuery, Drupal, drupalSettings, CKEDITOR, _);
