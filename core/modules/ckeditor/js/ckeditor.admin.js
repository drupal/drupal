/**
 * @file
 * CKEditor button and group configuration user interface.
 */
(function ($, Drupal, _, CKEDITOR) {

  "use strict";

  Drupal.ckeditor = Drupal.ckeditor || {};

  Drupal.behaviors.ckeditorAdmin = {
    attach: function (context) {
      // Process the CKEditor configuration fragment once.
      var $configurationForm = $(context).find('.ckeditor-toolbar-configuration');
      if ($configurationForm.once('ckeditor-configuration').length) {
        var $textarea = $configurationForm
          // Hide the textarea that contains the serialized representation of the
          // CKEditor configuration.
          .find('.form-item-editor-settings-toolbar-button-groups')
          .hide()
          // Return the textarea child node from this expression.
          .find('textarea');

        // The HTML for the CKEditor configuration is assembled on the server and
        // and sent to the client as a serialized DOM fragment.
        $configurationForm.append(drupalSettings.ckeditor.toolbarAdmin);

        // Create a configuration model.
        var model = Drupal.ckeditor.models.Model = new Drupal.ckeditor.Model({
          $textarea: $textarea,
          activeEditorConfig: JSON.parse($textarea.val()),
          hiddenEditorConfig: drupalSettings.ckeditor.hiddenCKEditorConfig
        });

        // Create the configuration Views.
        var viewDefaults = {
          model: model,
          el: $('.ckeditor-toolbar-configuration')
        };
        Drupal.ckeditor.views = {
          controller: new Drupal.ckeditor.ControllerView(viewDefaults),
          visualView: new Drupal.ckeditor.VisualView(viewDefaults),
          keyboardView: new Drupal.ckeditor.KeyboardView(viewDefaults),
          auralView: new Drupal.ckeditor.AuralView(viewDefaults)
        };
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
      var $configurationForm = $(context).find('.ckeditor-toolbar-configuration.ckeditor-configuration-processed');
      if ($configurationForm.length && Drupal.ckeditor.models && Drupal.ckeditor.models.Model) {
        var config = Drupal.ckeditor.models.Model.toJSON().activeEditorConfig;
        var buttons = Drupal.ckeditor.views.controller.getButtonList(config);
        var $activeToolbar = $('.ckeditor-toolbar-configuration').find('.ckeditor-toolbar-active');
        for (var i = 0; i < buttons.length; i++) {
          $activeToolbar.trigger('CKEditorToolbarChanged', ['removed', buttons[i]]);
        }
      }
    }
  };

  /**
   * CKEditor configuration UI methods of Backbone objects.
   */
  Drupal.ckeditor = {

    // A hash of View instances.
    views: {},

    // A hash of Model instances.
    models: {},

    /**
     * Translates a change in CKEditor config DOM structure into the config model.
     *
     * If the button is moved within an existing group, the DOM structure is simply
     * translated to a configuration model. If the button is moved into a new group
     * placeholder, then a process is launched to name that group before the button
     * move is translated into configuration.
     *
     * @param Backbone.View view
     *   The Backbone View that invoked this function.
     * @param jQuery $button
     *   A jQuery set that contains an li element that wraps a button element.
     * @param function callback
     *   A callback to invoke after the button group naming modal dialog has been
     *   closed.
     */
    registerButtonMove: function (view, $button, callback) {
      var $group = $button.closest('.ckeditor-toolbar-group');

      // If dropped in a placeholder button group, the user must name it.
      if ($group.hasClass('placeholder')) {

          if (view.isProcessing) {
              return;
          }
          view.isProcessing = true;

          Drupal.ckeditor.openGroupNameDialog(view, $group, callback);
      }
      else {
          view.model.set('isDirty', true);
          callback(true);
      }
    },

    /**
     * Translates a change in CKEditor config DOM structure into the config model.
     *
     * Each row has a placeholder group at the end of the row. A user may not move
     * an existing button group past the placeholder group at the end of a row.
     *
     * @param Backbone.View view
     *   The Backbone View that invoked this function.
     * @param jQuery $group
     *   A jQuery set that contains an li element that wraps a group of buttons.
     */
    registerGroupMove: function (view, $group) {
      // Remove placeholder classes if necessary.
      var $row = $group.closest('.ckeditor-row');
      if ($row.hasClass('placeholder')) {
        $row.removeClass('placeholder');
      }
      // If there are any rows with just a placeholder group, mark the row as a
      // placeholder.
      $row.parent().children().each(function () {
        $row = $(this);
        if ($row.find('.ckeditor-toolbar-group').not('.placeholder').length === 0) {
          $row.addClass('placeholder');
        }
      });
      view.model.set('isDirty', true);
    },

    /**
     * Opens a Drupal dialog with a form for changing the title of a button group.
     *
     * @param Backbone.View view
     *   The Backbone View that invoked this function.
     * @param jQuery $group
     *   A jQuery set that contains an li element that wraps a group of buttons.
     * @param function callback
     *   A callback to invoke after the button group naming modal dialog has been
     *   closed.
     */
    openGroupNameDialog: function (view, $group, callback) {
      callback = callback || function () {};

      /**
       * Validates the string provided as a button group title.
       *
       * @param DOM form
       *   The form DOM element that contains the input with the new button group
       *   title string.
       * @return Boolean
       *   Returns true when an error exists, otherwise returns false.
       */
      function validateForm(form) {
        if (form.elements[0].value.length === 0) {
          var $form = $(form);
          if (!$form.hasClass('errors')) {
            $form
              .addClass('errors')
              .find('input')
              .addClass('error')
              .attr('aria-invalid', 'true');
            $('<div class=\"description\" >' + Drupal.t('Please provide a name for the button group.') + '</div>').insertAfter(form.elements[0]);
          }
          return true;
        }
        return false;
      }

      /**
       * Attempts to close the dialog; Validates user input.
       *
       * @param String action
       *   The dialog action chosen by the user: 'apply' or 'cancel'.
       * @param DOM form
       *   The form DOM element that contains the input with the new button group
       *   title string.
       */
      function closeDialog(action, form) {

        /**
         * Closes the dialog when the user cancels or supplies valid data.
         */
        function shutdown() {
          dialog.close(action);

          // The processing marker can be deleted since the dialog has been closed.
          delete view.isProcessing;
        }

        /**
         * Applies a string as the name of a CKEditor button group.
         *
         * @param jQuery $group
         *   A jQuery set that contains an li element that wraps a group of buttons.
         * @param String name
         *   The new name of the CKEditor button group.
         */
        function namePlaceholderGroup($group, name) {
          // If it's currently still a placeholder, then that means we're creating
          // a new group, and we must do some extra work.
          if ($group.hasClass('placeholder')) {
            // Remove all whitespace from the name, lowercase it and ensure
            // HTML-safe encoding, then use this as the group ID for CKEditor
            // configuration UI accessibility purposes only.
            var groupID = 'ckeditor-toolbar-group-aria-label-for-' + Drupal.checkPlain(name.toLowerCase().replace(/\s/g, '-'));
            $group
              // Update the group container.
              .removeAttr('aria-label')
              .attr('data-drupal-ckeditor-type', 'group')
              .attr('tabindex', 0)
              // Update the group heading.
              .children('.ckeditor-toolbar-group-name')
              .attr('id', groupID)
              .end()
              // Update the group items.
              .children('.ckeditor-toolbar-group-buttons')
              .attr('aria-labelledby', groupID);
          }

          $group
            .attr('data-drupal-ckeditor-toolbar-group-name', name)
            .children('.ckeditor-toolbar-group-name')
            .text(name);
        }

        // Invoke a user-provided callback and indicate failure.
        if (action === 'cancel') {
          shutdown();
          callback(false, $group);
          return;
        }

        // Validate that a group name was provided.
        if (form && validateForm(form)) {
          return;
        }

        // React to application of a valid group name.
        if (action === 'apply') {
          shutdown();
          // Apply the provided name to the button group label.
          namePlaceholderGroup($group, Drupal.checkPlain(form.elements[0].value));
          // Remove placeholder classes so that new placeholders will be
          // inserted.
          $group.closest('.ckeditor-row.placeholder').addBack().removeClass('placeholder');

          // Invoke a user-provided callback and indicate success.
          callback(true, $group);

          // Signal that the active toolbar DOM structure has changed.
          view.model.set('isDirty', true);
        }
      }

      // Create a Drupal dialog that will get a button group name from the user.
      var $ckeditorButtonGroupNameForm = $(Drupal.theme('ckeditorButtonGroupNameForm'));
      var dialog = Drupal.dialog($ckeditorButtonGroupNameForm.get(0), {
        title: Drupal.t('Button group name'),
        dialogClass: 'ckeditor-name-toolbar-group',
        resizable: false,
        buttons: [
          {
            text: Drupal.t('Apply'),
            click: function () {
              closeDialog('apply', this);
            },
            primary: true
          },
          {
            text: Drupal.t('Cancel'),
            click: function () {
              closeDialog('cancel');
            }
          }
        ],
        open: function () {
          var form = this;
          var $form = $(this);
          var $widget = $form.parent();
          $widget.find('.ui-dialog-titlebar-close').remove();
          // Set a click handler on the input and button in the form.
          $widget.on('keypress.ckeditor', 'input, button', function (event) {
            // React to enter key press.
            if (event.keyCode === 13) {
              var $target = $(event.currentTarget);
              var data = $target.data('ui-button');
              var action = 'apply';
              // Assume 'apply', but take into account that the user might have
              // pressed the enter key on the dialog buttons.
              if (data && data.options && data.options.label) {
                action = data.options.label.toLowerCase();
              }
              closeDialog(action, form);
              event.stopPropagation();
              event.stopImmediatePropagation();
              event.preventDefault();
            }
          });
          // Announce to the user that a modal dialog is open.
          var text = Drupal.t('Editing the name of the new button group in a dialog.');
          if (typeof $group.attr('data-drupal-ckeditor-toolbar-group-name') !== 'undefined') {
            text = Drupal.t('Editing the name of the "@groupName" button group in a dialog.', {
              '@groupName': $group.attr('data-drupal-ckeditor-toolbar-group-name')
            });
          }
          Drupal.announce(text);
        },
        close: function (event) {
          // Automatically destroy the DOM element that was used for the dialog.
          $(event.target).remove();
        }
      });
      // A modal dialog is used because the user must provide a button group name
      // or cancel the button placement before taking any other action.
      dialog.showModal();

      $(document.querySelector('.ckeditor-name-toolbar-group').querySelector('input'))
        // When editing, set the "group name" input in the form to the current value.
        .attr('value', $group.attr('data-drupal-ckeditor-toolbar-group-name'))
        // Focus on the "group name" input in the form.
        .trigger('focus');
    }

  };


  /**
   * Automatically shows/hides settings of buttons-only CKEditor plugins.
   */
  Drupal.behaviors.ckeditorAdminButtonPluginSettings = {
    attach: function (context) {
      var $context = $(context);
      var $ckeditorPluginSettings = $context.find('#ckeditor-plugin-settings').once('ckeditor-plugin-settings');
      if ($ckeditorPluginSettings.length) {
        // Hide all button-dependent plugin settings initially.
        $ckeditorPluginSettings.find('[data-ckeditor-buttons]').each(function () {
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

  /**
   * Themes a blank CKEditor row.
   *
   * @return String
   */
  Drupal.theme.ckeditorRow = function () {
    return '<li class="ckeditor-row placeholder" role="group"><ul class="ckeditor-toolbar-groups clearfix"></ul></li>';
  };

  /**
   * Themes a blank CKEditor button group.
   *
   * @return String
   */
  Drupal.theme.ckeditorToolbarGroup = function () {
    var group = '';
    group += '<li class="ckeditor-toolbar-group placeholder" role="presentation" aria-label="' + Drupal.t('Place a button to create a new button group.') + '">';
    group += '<h3 class="ckeditor-toolbar-group-name">' + Drupal.t('New group') + '</h3>';
    group += '<ul class="ckeditor-buttons ckeditor-toolbar-group-buttons" role="toolbar" data-drupal-ckeditor-button-sorting="target"></ul>';
    group += '</li>';
    return group;
  };

  /**
   * Themes a form for changing the title of a CKEditor button group.
   *
   * @return String
   */
  Drupal.theme.ckeditorButtonGroupNameForm = function () {
    return '<form><input name="group-name" required="required"></form>';
  };

  /**
   * Themes a button that will toggle the button group names in active config.
   *
   * @return String
   */
  Drupal.theme.ckeditorButtonGroupNamesToggle = function () {
    return '<a class="ckeditor-groupnames-toggle" role="button" aria-pressed="false"></a>';
  };

  /**
   * Themes a button that will prompt the user to name a new button group.
   *
   * @return String
   */
  Drupal.theme.ckeditorNewButtonGroup = function () {
    return '<li class="ckeditor-add-new-group"><button role="button" aria-label="' + Drupal.t('Add a CKEditor button group to the end of this row.') + '">' + Drupal.t('Add group') + '</button></li>';
  };

})(jQuery, Drupal, _, CKEDITOR);
