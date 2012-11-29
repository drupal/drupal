//     Create.js - On-site web editing interface
//     (c) 2011-2012 Henri Bergius, IKS Consortium
//     Create may be freely distributed under the MIT license.
//     For all details and documentation:
//     http://createjs.org/
(function (jQuery, undefined) {
  // Run JavaScript in strict mode
  /*global jQuery:false _:false window:false console:false */
  'use strict';

  // # Widget for adding items to a collection
  jQuery.widget('Midgard.midgardCollectionAdd', {
    options: {
      editingWidgets: null,
      collection: null,
      model: null,
      definition: null,
      view: null,
      disabled: false,
      vie: null,
      editableOptions: null,
      templates: {
        button: '<button class="btn"><i class="icon-<%= icon %>"></i> <%= label %></button>'
      }
    },

    _create: function () {
      this.addButtons = [];
      var widget = this;
      if (!widget.options.collection.localStorage) {
        try {
          widget.options.collection.url = widget.options.model.url();
        } catch (e) {
          if (window.console) {
            console.log(e);
          }
        }
      }

      widget.options.collection.bind('add', function (model) {
        model.primaryCollection = widget.options.collection;
        widget.options.vie.entities.add(model);
        model.collection = widget.options.collection;
      });

      // Re-check collection constraints
      widget.options.collection.bind('add remove reset', widget.checkCollectionConstraints, widget);

      widget._bindCollectionView(widget.options.view);
    },

    _bindCollectionView: function (view) {
      var widget = this;
      view.bind('add', function (itemView) {
        itemView.$el.effect('slide', function () {
          widget._makeEditable(itemView);
        });
      });
    },

    _makeEditable: function (itemView) {
      this.options.editableOptions.disabled = this.options.disabled;
      this.options.editableOptions.model = itemView.model;
      itemView.$el.midgardEditable(this.options.editableOptions);
    },

    _init: function () {
      if (this.options.disabled) {
        this.disable();
        return;
      }
      this.enable();
    },

    hideButtons: function () {
      _.each(this.addButtons, function (button) {
        button.hide();
      });
    },

    showButtons: function () {
      _.each(this.addButtons, function (button) {
        button.show();
      });
    },

    checkCollectionConstraints: function () {
      if (this.options.disabled) {
        return;
      }

      if (!this.options.view.canAdd()) {
        this.hideButtons();
        return;
      }

      if (!this.options.definition) {
        // We have now information on the constraints applying to this collection
        this.showButtons();
        return;
      }

      if (!this.options.definition.max || this.options.definition.max === -1) {
        // No maximum constraint
        this.showButtons();
        return;
      }

      if (this.options.collection.length < this.options.definition.max) {
        this.showButtons();
        return;
      }
      // Collection is already full by its definition
      this.hideButtons();
    },

    enable: function () {
      var widget = this;

      var addButton = jQuery(_.template(this.options.templates.button, {
        icon: 'plus',
        label: this.options.editableOptions.localize('Add', this.options.editableOptions.language)
      })).button();
      addButton.addClass('midgard-create-add');
      addButton.click(function () {
        widget.addItem(addButton);
      });
      jQuery(widget.options.view.el).after(addButton);

      widget.addButtons.push(addButton);
      widget.checkCollectionConstraints();
    },

    disable: function () {
      _.each(this.addButtons, function (button) {
        button.remove();
      });
      this.addButtons = [];
    },

    _getTypeActions: function (options) {
      var widget = this;
      var actions = [];
      _.each(this.options.definition.range, function (type) {
        var nsType = widget.options.collection.vie.namespaces.uri(type);
        if (!widget.options.view.canAdd(nsType)) {
          return;
        }
        actions.push({
          name: type,
          label: type,
          cb: function () {
            widget.options.collection.add({
              '@type': type
            }, options);
          },
          className: 'create-ui-btn'
        });
      });
      return actions;
    },

    addItem: function (button, options) {
      if (options === undefined) {
          options = {};
      }
      var addOptions = _.extend({}, options, { validate: false });

      var itemData = {};
      if (this.options.definition && this.options.definition.range) {
        if (this.options.definition.range.length === 1) {
          // Items can be of single type, add that
          itemData['@type'] = this.options.definition.range[0];
        } else {
          // Ask user which type to add
          jQuery('body').midgardNotifications('create', {
            bindTo: button,
            gravity: 'L',
            body: this.options.editableOptions.localize('Choose type to add', this.options.editableOptions.language),
            timeout: 0,
            actions: this._getTypeActions(addOptions)
          });
          return;
        }
      } else {
        // Check the view templates for possible non-Thing type to use
        var keys = _.keys(this.options.view.templates);
        if (keys.length == 2) {
          itemData['@type'] = keys[0];
        }
      }
      this.options.collection.add(itemData, addOptions);
    }
  });
})(jQuery);
//     Create.js - On-site web editing interface
//     (c) 2011-2012 Henri Bergius, IKS Consortium
//     Create may be freely distributed under the MIT license.
//     For all details and documentation:
//     http://createjs.org/
(function (jQuery, undefined) {
  // Run JavaScript in strict mode
  /*global jQuery:false _:false window:false console:false */
  'use strict';

  // # Widget for adding items anywhere inside a collection
  jQuery.widget('Midgard.midgardCollectionAddBetween', jQuery.Midgard.midgardCollectionAdd, {
    _bindCollectionView: function (view) {
      var widget = this;
      view.bind('add', function (itemView) {
        //itemView.el.effect('slide');
        widget._makeEditable(itemView);
        widget._refreshButtons();
      });
      view.bind('remove', function () {
        widget._refreshButtons();
      });
    },

    _refreshButtons: function () {
      var widget = this;
      window.setTimeout(function () {
        widget.disable();
        widget.enable();
      }, 1);
    },

    prepareButton: function (index) {
      var widget = this;
      var addButton = jQuery(_.template(this.options.templates.button, {
        icon: 'plus',
        label: ''
      })).button();
      addButton.addClass('midgard-create-add');
      addButton.click(function () {
        widget.addItem(addButton, {
          at: index
        });
      });
      return addButton;
    },

    enable: function () {
      var widget = this;

      var firstAddButton = widget.prepareButton(0);
      jQuery(widget.options.view.el).prepend(firstAddButton);
      widget.addButtons.push(firstAddButton);
      jQuery.each(widget.options.view.entityViews, function (cid, view) {
        var index = widget.options.collection.indexOf(view.model);
        var addButton = widget.prepareButton(index + 1);
        jQuery(view.el).append(addButton);
        widget.addButtons.push(addButton);
      });

      this.checkCollectionConstraints();
    },

    disable: function () {
      var widget = this;
      jQuery.each(widget.addButtons, function (idx, button) {
        button.remove();
      });
      widget.addButtons = [];
    }
  });
})(jQuery);
//     Create.js - On-site web editing interface
//     (c) 2011-2012 Henri Bergius, IKS Consortium
//     Create may be freely distributed under the MIT license.
//     For all details and documentation:
//     http://createjs.org/
(function (jQuery, undefined) {
  // Run JavaScript in strict mode
  /*global jQuery:false _:false window:false VIE:false */
  'use strict';

  // Define Create's EditableEntity widget.
  jQuery.widget('Midgard.midgardEditable', {
    options: {
      propertyEditors: {},
      collections: [],
      model: null,
      // the configuration (mapping and options) of property editor widgets
      propertyEditorWidgetsConfiguration: {
        hallo: {
          widget: 'halloWidget',
          options: {}
        }
      },
      // the available property editor widgets by data type
      propertyEditorWidgets: {
        'default': 'hallo'
      },
      collectionWidgets: {
        'default': 'midgardCollectionAdd'
      },
      toolbarState: 'full',
      vie: null,
      domService: 'rdfa',
      predicateSelector: '[property]',
      disabled: false,
      localize: function (id, language) {
        return window.midgardCreate.localize(id, language);
      },
      language: null,
      // Current state of the Editable
      state: null,
      // Callback function for validating changes between states. Receives the previous state, new state, possibly property, and a callback
      acceptStateChange: true,
      // Callback function for listening (and reacting) to state changes.
      stateChange: null,
      // Callback function for decorating the full editable. Will be called on instantiation
      decorateEditableEntity: null,
      // Callback function for decorating a single property editor widget. Will
      // be called on editing widget instantiation.
      decoratePropertyEditor: null,

      // Deprecated.
      editables: [], // Now `propertyEditors`.
      editors: {}, // Now `propertyEditorWidgetsConfiguration`.
      widgets: {} // Now `propertyEditorW
    },

    // Aids in consistently passing parameters to events and callbacks.
    _params: function(predicate, extended) {
      var entityParams = {
        entity: this.options.model,
        editableEntity: this,
        entityElement: this.element,

        // Deprecated.
        editable: this,
        element: this.element,
        instance: this.options.model
      };

      var propertyParams = (predicate) ? {
        predicate: predicate,
        propertyEditor: this.options.propertyEditors[predicate],
        propertyElement: this.options.propertyEditors[predicate].element,

        // Deprecated.
        property: predicate,
        element: this.options.propertyEditors[predicate].element
      } : {};

      return _.extend(entityParams, propertyParams, extended);
    },

    _create: function () {
      // Backwards compatibility:
      // - this.options.propertyEditorWidgets used to be this.options.widgets
      // - this.options.propertyEditorWidgetsConfiguration used to be
      //   this.options.editors
      if (this.options.widgets) {
        this.options.propertyEditorWidgets = _.extend(this.options.propertyEditorWidgets, this.options.widgets);
      }
      if (this.options.editors) {
        this.options.propertyEditorWidgetsConfiguration = _.extend(this.options.propertyEditorWidgetsConfiguration, this.options.editors);
      }

      this.vie = this.options.vie;
      this.domService = this.vie.service(this.options.domService);
      if (!this.options.model) {
        var widget = this;
        this.vie.load({
          element: this.element
        }).from(this.options.domService).execute().done(function (entities) {
          widget.options.model = entities[0];
        });
      }
      if (_.isFunction(this.options.decorateEditableEntity)) {
        this.options.decorateEditableEntity(this._params());
      }
    },

    _init: function () {
      // Backwards compatibility:
      // - this.options.propertyEditorWidgets used to be this.options.widgets
      // - this.options.propertyEditorWidgetsConfiguration used to be
      //   this.options.editors
      if (this.options.widgets) {
        this.options.propertyEditorWidgets = _.extend(this.options.propertyEditorWidgets, this.options.widgets);
      }
      if (this.options.editors) {
        this.options.propertyEditorWidgetsConfiguration = _.extend(this.options.propertyEditorWidgetsConfiguration, this.options.editors);
      }

      // Old way of setting the widget inactive
      if (this.options.disabled === true) {
        this.setState('inactive');
        return;
      }

      if (this.options.disabled === false && this.options.state === 'inactive') {
        this.setState('candidate');
        return;
      }
      this.options.disabled = false;

      if (this.options.state) {
        this.setState(this.options.state);
        return;
      }
      this.setState('candidate');
    },

    // Method used for cycling between the different states of the Editable widget:
    //
    // * Inactive: editable is loaded but disabled
    // * Candidate: editable is enabled but not activated
    // * Highlight: user is hovering over the editable (not set by Editable widget directly)
    // * Activating: an editor widget is being activated for user to edit with it (skipped for editors that activate instantly)
    // * Active: user is actually editing something inside the editable
    // * Changed: user has made changes to the editable
    // * Invalid: the contents of the editable have validation errors
    //
    // In situations where state changes are triggered for a particular property editor, the `predicate`
    // argument will provide the name of that property.
    //
    // State changes may carry optional context information in a JavaScript object. The payload of these context objects is not
    // standardized, and is meant to be set and used by the application controller
    //
    // The callback parameter is optional and will be invoked after a state change has been accepted (after the 'statechange'
    // event) or rejected.
    setState: function (state, predicate, context, callback) {
      var previous = this.options.state;
      var current = state;
      if (current === previous) {
        return;
      }

      if (this.options.acceptStateChange === undefined || !_.isFunction(this.options.acceptStateChange)) {
        // Skip state transition validation
        this._doSetState(previous, current, predicate, context);
        if (_.isFunction(callback)) {
          callback(true);
        }
        return;
      }

      var widget = this;
      this.options.acceptStateChange(previous, current, predicate, context, function (accepted) {
        if (accepted) {
          widget._doSetState(previous, current, predicate, context);
        }
        if (_.isFunction(callback)) {
          callback(accepted);
        }
        return;
      });
    },

    getState: function () {
      return this.options.state;
    },

    _doSetState: function (previous, current, predicate, context) {
      this.options.state = current;
      if (current === 'inactive') {
        this.disable();
      } else if ((previous === null || previous === 'inactive') && current !== 'inactive') {
        this.enable();
      }

      this._trigger('statechange', null, this._params(predicate, {
        previous: previous,
        current: current,
        context: context
      }));
    },

    findEditablePredicateElements: function (callback) {
      this.domService.findPredicateElements(this.options.model.id, jQuery(this.options.predicateSelector, this.element), false).each(callback);
    },

    getElementPredicate: function (element) {
      return this.domService.getElementPredicate(element);
    },

    enable: function () {
      var editableEntity = this;
      if (!this.options.model) {
        return;
      }

      this.findEditablePredicateElements(function () {
        editableEntity._enablePropertyEditor(jQuery(this));
      });

      this._trigger('enable', null, this._params());

      _.each(this.domService.views, function (view) {
        if (view instanceof this.vie.view.Collection && this.options.model === view.owner) {
          var predicate = view.collection.predicate;
          var editableOptions = _.clone(this.options);
          editableOptions.state = null;
          var collection = this.enableCollection({
            model: this.options.model,
            collection: view.collection,
            property: predicate,
            definition: this.getAttributeDefinition(predicate),
            view: view,
            element: view.el,
            vie: editableEntity.vie,
            editableOptions: editableOptions
          });
          editableEntity.options.collections.push(collection);
        }
      }, this);
    },

    disable: function () {
      _.each(this.options.propertyEditors, function (editable) {
        this.disableEditor({
          widget: this,
          editable: editable,
          entity: this.options.model,
          element: jQuery(editable)
        });
      }, this);
      this.options.propertyEditors = {};

      // Deprecated.
      this.options.editables = [];

      _.each(this.options.collections, function (collectionWidget) {
        var editableOptions = _.clone(this.options);
        editableOptions.state = 'inactive';
        this.disableCollection({
          widget: this,
          model: this.options.model,
          element: collectionWidget,
          vie: this.vie,
          editableOptions: editableOptions
        });
      }, this);
      this.options.collections = [];

      this._trigger('disable', null, this._params());
    },

    _enablePropertyEditor: function (element) {
      var widget = this;
      var predicate = this.getElementPredicate(element);
      if (!predicate) {
        return true;
      }
      if (this.options.model.get(predicate) instanceof Array) {
        // For now we don't deal with multivalued properties in the editable
        return true;
      }

      var propertyElement = this.enablePropertyEditor({
        widget: this,
        element: element,
        entity: this.options.model,
        property: predicate,
        vie: this.vie,
        decorate: this.options.decoratePropertyEditor,
        decorateParams: _.bind(this._params, this),
        changed: function (content) {
          widget.setState('changed', predicate);

          var changedProperties = {};
          changedProperties[predicate] = content;
          widget.options.model.set(changedProperties, {
            silent: true
          });

          widget._trigger('changed', null, widget._params(predicate));
        },
        activating: function () {
          widget.setState('activating', predicate);
        },
        activated: function () {
          widget.setState('active', predicate);
          widget._trigger('activated', null, widget._params(predicate));
        },
        deactivated: function () {
          widget.setState('candidate', predicate);
          widget._trigger('deactivated', null, widget._params(predicate));
        }
      });

      if (!propertyElement) {
        return;
      }
      var widgetType = propertyElement.data('createWidgetName');
      this.options.propertyEditors[predicate] = propertyElement.data(widgetType);

      // Deprecated.
      this.options.editables.push(propertyElement);

      this._trigger('enableproperty', null, this._params(predicate));
    },

    // returns the name of the property editor widget to use for the given property
    _propertyEditorName: function (data) {
      if (this.options.propertyEditorWidgets[data.property] !== undefined) {
        // Property editor widget configuration set for specific RDF predicate
        return this.options.propertyEditorWidgets[data.property];
      }

      // Load the property editor widget configuration for the data type
      var propertyType = 'default';
      var attributeDefinition = this.getAttributeDefinition(data.property);
      if (attributeDefinition) {
        propertyType = attributeDefinition.range[0];
      }
      if (this.options.propertyEditorWidgets[propertyType] !== undefined) {
        return this.options.propertyEditorWidgets[propertyType];
      }
      return this.options.propertyEditorWidgets['default'];
    },

    _propertyEditorWidget: function (editor) {
      return this.options.propertyEditorWidgetsConfiguration[editor].widget;
    },

    _propertyEditorOptions: function (editor) {
      return this.options.propertyEditorWidgetsConfiguration[editor].options;
    },

    getAttributeDefinition: function (property) {
      var type = this.options.model.get('@type');
      if (!type) {
        return;
      }
      if (!type.attributes) {
        return;
      }
      return type.attributes.get(property);
    },

    // Deprecated.
    enableEditor: function (data) {
      return this.enablePropertyEditor(data);
    },

    enablePropertyEditor: function (data) {
      var editorName = this._propertyEditorName(data);
      if (editorName === null) {
        return;
      }

      var editorWidget = this._propertyEditorWidget(editorName);

      data.editorOptions = this._propertyEditorOptions(editorName);
      data.toolbarState = this.options.toolbarState;
      data.disabled = false;
      // Pass metadata that could be useful for some implementations.
      data.editorName = editorName;
      data.editorWidget = editorWidget;

      if (typeof jQuery(data.element)[editorWidget] !== 'function') {
        throw new Error(editorWidget + ' widget is not available');
      }

      jQuery(data.element)[editorWidget](data);
      jQuery(data.element).data('createWidgetName', editorWidget);
      return jQuery(data.element);
    },

    // Deprecated.
    disableEditor: function (data) {
      return this.disablePropertyEditor(data);
    },

    disablePropertyEditor: function (data) {
      var widgetName = jQuery(data.element).data('createWidgetName');

      data.disabled = true;

      if (widgetName) {
        // only if there has been an editing widget registered
        jQuery(data.element)[widgetName](data);
        jQuery(data.element).removeClass('ui-state-disabled');

        if (data.element.is(':focus')) {
          data.element.blur();
        }
      }
    },

    collectionWidgetName: function (data) {
      if (this.options.collectionWidgets[data.property] !== undefined) {
        // Widget configuration set for specific RDF predicate
        return this.options.collectionWidgets[data.property];
      }

      var propertyType = 'default';
      var attributeDefinition = this.getAttributeDefinition(data.property);
      if (attributeDefinition) {
        propertyType = attributeDefinition.range[0];
      }
      if (this.options.collectionWidgets[propertyType] !== undefined) {
        return this.options.collectionWidgets[propertyType];
      }
      return this.options.collectionWidgets['default'];
    },

    enableCollection: function (data) {
      var widgetName = this.collectionWidgetName(data);
      if (widgetName === null) {
        return;
      }
      data.disabled = false;
      if (typeof jQuery(data.element)[widgetName] !== 'function') {
        throw new Error(widgetName + ' widget is not available');
      }
      jQuery(data.element)[widgetName](data);
      jQuery(data.element).data('createCollectionWidgetName', widgetName);
      return jQuery(data.element);
    },

    disableCollection: function (data) {
      var widgetName = jQuery(data.element).data('createCollectionWidgetName');
      if (widgetName === null) {
        return;
      }
      data.disabled = true;
      if (widgetName) {
        // only if there has been an editing widget registered
        jQuery(data.element)[widgetName](data);
        jQuery(data.element).removeClass('ui-state-disabled');
      }
    }
  });
})(jQuery);
//     Create.js - On-site web editing interface
//     (c) 2012 Tobias Herrmann, IKS Consortium
//     Create may be freely distributed under the MIT license.
//     For all details and documentation:
//     http://createjs.org/
(function (jQuery, undefined) {
  // Run JavaScript in strict mode
  /*global jQuery:false _:false document:false */
  'use strict';

  // # Base property editor widget
  //
  // This property editor widget provides a very simplistic `contentEditable`
  // property editor that can be used as standalone, but should more usually be
  // used as the base class for other property editor widgets.
  // This property editor widget is only useful for textual properties!
  //
  // Subclassing this base property editor widget is easy:
  //
  //     jQuery.widget('Namespace.MyWidget', jQuery.Create.editWidget, {
  //       // override any properties
  //     });
  jQuery.widget('Create.editWidget', {
    options: {
      disabled: false,
      vie: null
    },
    // override to enable the widget
    enable: function () {
      this.element.attr('contenteditable', 'true');
    },
    // override to disable the widget
    disable: function (disable) {
      this.element.attr('contenteditable', 'false');
    },
    // called by the jQuery UI plugin factory when creating the property editor
    // widget instance
    _create: function () {
      this._registerWidget();
      this._initialize();

      if (_.isFunction(this.options.decorate) && _.isFunction(this.options.decorateParams)) {
        // TRICKY: we can't use this.options.decorateParams()'s 'propertyName'
        // parameter just yet, because it will only be available after this
        // object has been created, but we're currently in the constructor!
        // Hence we have to duplicate part of its logic here.
        this.options.decorate(this.options.decorateParams(null, {
          propertyName: this.options.property,
          propertyEditor: this,
          propertyElement: this.element,
          // Deprecated.
          editor: this,
          predicate: this.options.property,
          element: this.element
        }));
      }
    },
    // called every time the property editor widget is called
    _init: function () {
      if (this.options.disabled) {
        this.disable();
        return;
      }
      this.enable();
    },
    // override this function to initialize the property editor widget functions
    _initialize: function () {
      var self = this;
      this.element.bind('focus', function () {
        if (self.options.disabled) {
          return;
        }
        self.options.activated();
      });
      this.element.bind('blur', function () {
        if (self.options.disabled) {
          return;
        }
        self.options.deactivated();
      });
      var before = this.element.html();
      this.element.bind('keyup paste', function (event) {
        if (self.options.disabled) {
          return;
        }
        var current = jQuery(this).html();
        if (before !== current) {
          before = current;
          self.options.changed(current);
        }
      });
    },
    // used to register the property editor widget name with the DOM element
    _registerWidget: function () {
      this.element.data("createWidgetName", this.widgetName);
    }
  });
})(jQuery);
//     Create.js - On-site web editing interface
//     (c) 2012 Tobias Herrmann, IKS Consortium
//     (c) 2011 Rene Kapusta, Evo42
//     Create may be freely distributed under the MIT license.
//     For all details and documentation:
//     http://createjs.org/
(function (jQuery, undefined) {
  // Run JavaScript in strict mode
  /*global jQuery:false _:false document:false Aloha:false */
  'use strict';

  // # Aloha editing widget
  //
  // This widget allows editing textual contents using the
  // [Aloha](http://aloha-editor.org) rich text editor.
  //
  // Due to licensing incompatibilities, Aloha Editor needs to be installed
  // and configured separately.
  jQuery.widget('Create.alohaWidget', jQuery.Create.editWidget, {
    _initialize: function () {},
    enable: function () {
      var options = this.options;
      var editable;
      var currentElement = Aloha.jQuery(options.element.get(0)).aloha();
      _.each(Aloha.editables, function (aloha) {
        // Find the actual editable instance so we can hook to the events
        // correctly
        if (aloha.obj.get(0) === currentElement.get(0)) {
          editable = aloha;
        }
      });
      if (!editable) {
        return;
      }
      editable.vieEntity = options.entity;

      // Subscribe to activation and deactivation events
      Aloha.bind('aloha-editable-activated', function (event, data) {
        if (data.editable !== editable) {
          return;
        }
        options.activated();
      });
      Aloha.bind('aloha-editable-deactivated', function (event, data) {
        if (data.editable !== editable) {
          return;
        }
        options.deactivated();
      });

      Aloha.bind('aloha-smart-content-changed', function (event, data) {
        if (data.editable !== editable) {
          return;
        }
        if (!data.editable.isModified()) {
          return true;
        }
        options.changed(data.editable.getContents());
        data.editable.setUnmodified();
      });
      this.options.disabled = false;
    },
    disable: function () {
      Aloha.jQuery(this.options.element.get(0)).mahalo();
      this.options.disabled = true;
    }
  });
})(jQuery);
//     Create.js - On-site web editing interface
//     (c) 2012 Tobias Herrmann, IKS Consortium
//     Create may be freely distributed under the MIT license.
//     For all details and documentation:
(function (jQuery, undefined) {
  // Run JavaScript in strict mode
  /*global jQuery:false _:false document:false CKEDITOR:false */
  'use strict';

  // # CKEditor editing widget
  //
  // This widget allows editing textual content areas with the
  // [CKEditor](http://ckeditor.com/) rich text editor.
  jQuery.widget('Create.ckeditorWidget', jQuery.Create.editWidget, {
    enable: function () {
      this.element.attr('contentEditable', 'true');
      this.editor = CKEDITOR.inline(this.element.get(0));
      this.options.disabled = false;

      var widget = this;
      this.editor.on('focus', function () {
        widget.options.activated();
      });
      this.editor.on('blur', function () {
        widget.options.activated();
      });
      this.editor.on('key', function () {
        widget.options.changed(widget.editor.getData());
      });
      this.editor.on('paste', function () {
        widget.options.changed(widget.editor.getData());
      });
      this.editor.on('afterCommandExec', function () {
        widget.options.changed(widget.editor.getData());
      });
    },

    disable: function () {
      if (!this.editor) {
        return;
      }
      this.element.attr('contentEditable', 'false');
      this.editor.destroy();
      this.editor = null;
    },

    _initialize: function () {
      CKEDITOR.disableAutoInline = true;
    }
  });
})(jQuery);
//     Create.js - On-site web editing interface
//     (c) 2012 Tobias Herrmann, IKS Consortium
//     Create may be freely distributed under the MIT license.
//     For all details and documentation:
//     http://createjs.org/
(function (jQuery, undefined) {
  // Run JavaScript in strict mode
  /*global jQuery:false _:false document:false */
  'use strict';

  // # Hallo editing widget
  //
  // This widget allows editing textual content areas with the
  // [Hallo](http://hallojs.org) rich text editor.
  jQuery.widget('Create.halloWidget', jQuery.Create.editWidget, {
    options: {
      editorOptions: {},
      disabled: true,
      toolbarState: 'full',
      vie: null,
      entity: null
    },
    enable: function () {
      jQuery(this.element).hallo({
        editable: true
      });
      this.options.disabled = false;
    },

    disable: function () {
      jQuery(this.element).hallo({
        editable: false
      });
      this.options.disabled = true;
    },

    _initialize: function () {
      jQuery(this.element).hallo(this.getHalloOptions());
      var self = this;
      jQuery(this.element).bind('halloactivated', function (event, data) {
        self.options.activated();
      });
      jQuery(this.element).bind('hallodeactivated', function (event, data) {
        self.options.deactivated();
      });
      jQuery(this.element).bind('hallomodified', function (event, data) {
        self.options.changed(data.content);
        data.editable.setUnmodified();
      });

      jQuery(document).bind('midgardtoolbarstatechange', function(event, data) {
        // Switch between Hallo configurations when toolbar state changes
        if (data.display === self.options.toolbarState) {
          return;
        }
        self.options.toolbarState = data.display;
        var newOptions = self.getHalloOptions();
        self.element.hallo('changeToolbar', newOptions.parentElement, newOptions.toolbar, true);
      });
    },

    getHalloOptions: function() {
      var defaults = {
        plugins: {
          halloformat: {},
          halloblock: {},
          hallolists: {},
          hallolink: {},
          halloimage: {
            entity: this.options.entity
          }
        },
        buttonCssClass: 'create-ui-btn-small',
        placeholder: '[' + this.options.property + ']'
      };

      if (typeof this.element.annotate === 'function' && this.options.vie.services.stanbol) {
        // Enable Hallo Annotate plugin by default if user has annotate.js
        // loaded and VIE has Stanbol enabled
        defaults.plugins.halloannotate = {
            vie: this.options.vie
        };
      }

      if (this.options.toolbarState === 'full') {
        // Use fixed toolbar in the Create tools area
        defaults.parentElement = jQuery('.create-ui-toolbar-dynamictoolarea .create-ui-tool-freearea');
        defaults.toolbar = 'halloToolbarFixed';
      } else {
        // Tools area minimized, use floating toolbar
        defaults.parentElement = 'body';
        defaults.toolbar = 'halloToolbarContextual';
      }
      return _.extend(defaults, this.options.editorOptions);
    }
  });
})(jQuery);
//     Create.js - On-site web editing interface
//     (c) 2012 Henri Bergius, IKS Consortium
//     Create may be freely distributed under the MIT license.
//     For all details and documentation:
//     http://createjs.org/
(function (jQuery, undefined) {
  // Run JavaScript in strict mode
  /*global jQuery:false _:false document:false */
  'use strict';

  // # Redactor editing widget
  //
  // This widget allows editing textual content areas with the
  // [Redactor](http://redactorjs.com/) rich text editor.
  jQuery.widget('Create.redactorWidget', jQuery.Create.editWidget, {
    editor: null,

    options: {
      editorOptions: {},
      disabled: true
    },

    enable: function () {
      jQuery(this.element).redactor(this.getRedactorOptions());
      this.options.disabled = false;
    },

    disable: function () {
      jQuery(this.element).destroyEditor();
      this.options.disabled = true;
    },

    _initialize: function () {
      var self = this;
      jQuery(this.element).bind('focus', function (event) {
        self.options.activated();
      });
      /*
      jQuery(this.element).bind('blur', function (event) {
        self.options.deactivated();
      });
      */
    },

    getRedactorOptions: function () {
      var self = this;
      var overrides = {
        keyupCallback: function (obj, event) {
          self.options.changed(jQuery(self.element).getCode());
        },
        execCommandCallback: function (obj, command) {
          self.options.changed(jQuery(self.element).getCode());
        }
      };

      return _.extend(self.options.editorOptions, overrides);
    }
  });
})(jQuery);
//     Create.js - On-site web editing interface
//     (c) 2011-2012 Henri Bergius, IKS Consortium
//     Create may be freely distributed under the MIT license.
//     For all details and documentation:
//     http://createjs.org/
(function (jQuery, undefined) {
  // Run JavaScript in strict mode
  /*global jQuery:false _:false window:false */
  'use strict';

  jQuery.widget('Midgard.midgardStorage', {
    saveEnabled: true,
    options: {
      // Whether to use localstorage
      localStorage: false,
      removeLocalstorageOnIgnore: true,
      // VIE instance to use for storage handling
      vie: null,
      // URL callback for Backbone.sync
      url: '',
      // Whether to enable automatic saving
      autoSave: false,
      // How often to autosave in milliseconds
      autoSaveInterval: 5000,
      // Whether to save entities that are referenced by entities
      // we're saving to the server.
      saveReferencedNew: false,
      saveReferencedChanged: false,
      // Namespace used for events from midgardEditable-derived widget
      editableNs: 'midgardeditable',
      // CSS selector for the Edit button, leave to null to not bind
      // notifications to any element
      editSelector: '#midgardcreate-edit a',
      localize: function (id, language) {
        return window.midgardCreate.localize(id, language);
      },
      language: null
    },

    _create: function () {
      var widget = this;
      this.changedModels = [];

      if (window.localStorage) {
        this.options.localStorage = true;
      }

      this.vie = this.options.vie;

      this.vie.entities.bind('add', function (model) {
        // Add the back-end URL used by Backbone.sync
        model.url = widget.options.url;
        model.toJSON = model.toJSONLD;
      });

      widget._bindEditables();
      if (widget.options.autoSave) {
        widget._autoSave();
      }
    },

    _autoSave: function () {
      var widget = this;
      widget.saveEnabled = true;

      var doAutoSave = function () {
        if (!widget.saveEnabled) {
          return;
        }

        if (widget.changedModels.length === 0) {
          return;
        }

        widget.saveRemoteAll({
          // We make autosaves silent so that potential changes from server
          // don't disrupt user while writing.
          silent: true
        });
      };

      var timeout = window.setInterval(doAutoSave, widget.options.autoSaveInterval);

      this.element.bind('startPreventSave', function () {
        if (timeout) {
          window.clearInterval(timeout);
          timeout = null;
        }
        widget.disableAutoSave();
      });
      this.element.bind('stopPreventSave', function () {
        if (!timeout) {
          timeout = window.setInterval(doAutoSave, widget.options.autoSaveInterval);
        }
        widget.enableAutoSave();
      });

    },

    enableAutoSave: function () {
      this.saveEnabled = true;
    },

    disableAutoSave: function () {
      this.saveEnabled = false;
    },

    _bindEditables: function () {
      var widget = this;
      this.restorables = [];
      var restorer;

      widget.element.bind(widget.options.editableNs + 'changed', function (event, options) {
        if (_.indexOf(widget.changedModels, options.instance) === -1) {
          widget.changedModels.push(options.instance);
        }
        widget._saveLocal(options.instance);
      });

      widget.element.bind(widget.options.editableNs + 'disable', function (event, options) {
        widget._restoreLocal(options.instance);
      });

      widget.element.bind(widget.options.editableNs + 'enable', function (event, options) {
        if (!options.instance._originalAttributes) {
          options.instance._originalAttributes = _.clone(options.instance.attributes);
        }

        if (!options.instance.isNew() && widget._checkLocal(options.instance)) {
          // We have locally-stored modifications, user needs to be asked
          widget.restorables.push(options.instance);
        }

        /*_.each(options.instance.attributes, function (attributeValue, property) {
          if (attributeValue instanceof widget.vie.Collection) {
            widget._readLocalReferences(options.instance, property, attributeValue);
          }
        });*/
      });

      widget.element.bind('midgardcreatestatechange', function (event, options) {
        if (options.state === 'browse' || widget.restorables.length === 0) {
          widget.restorables = [];
          if (restorer) {
            restorer.close();
          }
          return;
        }
        restorer = widget.checkRestore();
      });

      widget.element.bind('midgardstorageloaded', function (event, options) {
        if (_.indexOf(widget.changedModels, options.instance) === -1) {
          widget.changedModels.push(options.instance);
        }
      });
    },

    checkRestore: function () {
      var widget = this;
      if (widget.restorables.length === 0) {
        return;
      }

      var message;
      var restorer;
      if (widget.restorables.length === 1) {
        message = _.template(widget.options.localize('localModification', widget.options.language), {
          label: widget.restorables[0].getSubjectUri()
        });
      } else {
        message = _.template(widget.options.localize('localModifications', widget.options.language), {
          number: widget.restorables.length
        });
      }

      var doRestore = function (event, notification) {
        widget.restoreLocal();
        restorer.close();
      };

      var doIgnore = function (event, notification) {
        widget.ignoreLocal();
        restorer.close();
      };

      restorer = jQuery('body').midgardNotifications('create', {
        bindTo: widget.options.editSelector,
        gravity: 'TR',
        body: message,
        timeout: 0,
        actions: [
          {
            name: 'restore',
            label: widget.options.localize('Restore', widget.options.language),
            cb: doRestore,
            className: 'create-ui-btn'
          },
          {
            name: 'ignore',
            label: widget.options.localize('Ignore', widget.options.language),
            cb: doIgnore,
            className: 'create-ui-btn'
          }
        ],
        callbacks: {
          beforeShow: function () {
            if (!window.Mousetrap) {
              return;
            }
            window.Mousetrap.bind(['command+shift+r', 'ctrl+shift+r'], function (event) {
              event.preventDefault();
              doRestore();
            });
            window.Mousetrap.bind(['command+shift+i', 'ctrl+shift+i'], function (event) {
              event.preventDefault();
              doIgnore();
            });
          },
          afterClose: function () {
            if (!window.Mousetrap) {
              return;
            }
            window.Mousetrap.unbind(['command+shift+r', 'ctrl+shift+r']);
            window.Mousetrap.unbind(['command+shift+i', 'ctrl+shift+i']);
          }
        }
      });
      return restorer;
    },

    restoreLocal: function () {
      _.each(this.restorables, function (instance) {
        this.readLocal(instance);
      }, this);
      this.restorables = [];
    },

    ignoreLocal: function () {
      if (this.options.removeLocalstorageOnIgnore) {
        _.each(this.restorables, function (instance) {
          this._removeLocal(instance);
        }, this);
      }
      this.restorables = [];
    },

    saveReferences: function (model) {
      _.each(model.attributes, function (value, property) {
        if (!value || !value.isCollection) {
          return;
        }

        value.each(function (referencedModel) {
          if (this.changedModels.indexOf(referencedModel) !== -1) {
            // The referenced model is already in the save queue
            return;
          }

          if (referencedModel.isNew() && this.options.saveReferencedNew) {
            return referencedModel.save();
          }

          if (referencedModel.hasChanged() && this.options.saveReferencedChanged) {
            return referencedModel.save();
          }
        }, this);
      }, this);
    },

    saveRemote: function (model, options) {
      // Optionally handle entities referenced in this model first
      this.saveReferences(model);

      this._trigger('saveentity', null, {
        entity: model,
        options: options
      });

      var widget = this;
      model.save(null, _.extend({}, options, {
        success: function (m, response) {
          // From now on we're going with the values we have on server
          model._originalAttributes = _.clone(model.attributes);
          widget._removeLocal(model);
          window.setTimeout(function () {
            // Remove the model from the list of changed models after saving
            widget.changedModels.splice(widget.changedModels.indexOf(model), 1);
          }, 0);
          if (_.isFunction(options.success)) {
            options.success(m, response);
          }
          widget._trigger('savedentity', null, {
            entity: model,
            options: options
          });
        },
        error: function (m, response) {
          if (_.isFunction(options.error)) {
            options.error(m, response);
          }
        }
      }));
    },

    saveRemoteAll: function (options) {
      var widget = this;
      if (widget.changedModels.length === 0) {
        return;
      }

      widget._trigger('save', null, {
        entities: widget.changedModels,
        options: options,
        // Deprecated
        models: widget.changedModels
      });

      var notification_msg;
      var needed = widget.changedModels.length;
      if (needed > 1) {
        notification_msg = _.template(widget.options.localize('saveSuccessMultiple', widget.options.language), {
          number: needed
        });
      } else {
        notification_msg = _.template(widget.options.localize('saveSuccess', widget.options.language), {
          label: widget.changedModels[0].getSubjectUri()
        });
      }

      widget.disableAutoSave();
      _.each(widget.changedModels, function (model) {
        this.saveRemote(model, {
          success: function (m, response) {
            needed--;
            if (needed <= 0) {
              // All models were happily saved
              widget._trigger('saved', null, {
                options: options
              });
              if (options && _.isFunction(options.success)) {
                options.success(m, response);
              }
              jQuery('body').midgardNotifications('create', {
                body: notification_msg
              });
              widget.enableAutoSave();
            }
          },
          error: function (m, err) {
            if (options && _.isFunction(options.error)) {
              options.error(m, err);
            }
            jQuery('body').midgardNotifications('create', {
              body: _.template(widget.options.localize('saveError', widget.options.language), {
                error: err.responseText || ''
              }),
              timeout: 0
            });

            widget._trigger('error', null, {
              instance: model
            });
          }
        });
      }, this);
    },

    _saveLocal: function (model) {
      if (!this.options.localStorage) {
        return;
      }

      if (model.isNew()) {
        // Anonymous object, save as refs instead
        if (!model.primaryCollection) {
          return;
        }
        return this._saveLocalReferences(model.primaryCollection.subject, model.primaryCollection.predicate, model);
      }
      window.localStorage.setItem(model.getSubjectUri(), JSON.stringify(model.toJSONLD()));
    },

    _getReferenceId: function (model, property) {
      return model.id + ':' + property;
    },

    _saveLocalReferences: function (subject, predicate, model) {
      if (!this.options.localStorage) {
        return;
      }

      if (!subject || !predicate) {
        return;
      }

      var widget = this;
      var identifier = subject + ':' + predicate;
      var json = model.toJSONLD();
      if (window.localStorage.getItem(identifier)) {
        var referenceList = JSON.parse(window.localStorage.getItem(identifier));
        var index = _.pluck(referenceList, '@').indexOf(json['@']);
        if (index !== -1) {
          referenceList[index] = json;
        } else {
          referenceList.push(json);
        }
        window.localStorage.setItem(identifier, JSON.stringify(referenceList));
        return;
      }
      window.localStorage.setItem(identifier, JSON.stringify([json]));
    },

    _checkLocal: function (model) {
      if (!this.options.localStorage) {
        return false;
      }

      var local = window.localStorage.getItem(model.getSubjectUri());
      if (!local) {
        return false;
      }

      return true;
    },

    hasLocal: function (model) {
      if (!this.options.localStorage) {
        return false;
      }

      if (!window.localStorage.getItem(model.getSubjectUri())) {
        return false;
      }
      return true;
    },

    readLocal: function (model) {
      if (!this.options.localStorage) {
        return;
      }

      var local = window.localStorage.getItem(model.getSubjectUri());
      if (!local) {
        return;
      }
      if (!model._originalAttributes) {
        model._originalAttributes = _.clone(model.attributes);
      }
      var parsed = JSON.parse(local);
      var entity = this.vie.entities.addOrUpdate(parsed, {
        overrideAttributes: true
      });

      this._trigger('loaded', null, {
        instance: entity
      });
    },

    _readLocalReferences: function (model, property, collection) {
      if (!this.options.localStorage) {
        return;
      }

      var identifier = this._getReferenceId(model, property);
      var local = window.localStorage.getItem(identifier);
      if (!local) {
        return;
      }
      collection.add(JSON.parse(local));
    },

    _restoreLocal: function (model) {
      var widget = this;

      // Remove unsaved collection members
      if (!model) { return; }
      _.each(model.attributes, function (attributeValue, property) {
        if (attributeValue instanceof widget.vie.Collection) {
          var removables = [];
          attributeValue.forEach(function (model) {
            if (model.isNew()) {
              removables.push(model);
            }
          });
          attributeValue.remove(removables);
        }
      });

      // Restore original object properties
      if (!model.changedAttributes()) {
        if (model._originalAttributes) {
          model.set(model._originalAttributes);
        }
        return;
      }

      model.set(model.previousAttributes());
    },

    _removeLocal: function (model) {
      if (!this.options.localStorage) {
        return;
      }

      window.localStorage.removeItem(model.getSubjectUri());
    }
  });
})(jQuery);
if (window.midgardCreate === undefined) {
  window.midgardCreate = {};
}
if (window.midgardCreate.locale === undefined) {
  window.midgardCreate.locale = {};
}

window.midgardCreate.locale.en = {
  // Session-state buttons for the main toolbar
  'Save': 'Save',
  'Saving': 'Saving',
  'Cancel': 'Cancel',
  'Edit': 'Edit',
  // Storage status messages
  'localModification': 'Item "<%= label %>" has local modifications',
  'localModifications': '<%= number %> items on this page have local modifications',
  'Restore': 'Restore',
  'Ignore': 'Ignore',
  'saveSuccess': 'Item "<%= label %>" saved successfully',
  'saveSuccessMultiple': '<%= number %> items saved successfully',
  'saveError': 'Error occurred while saving<br /><%= error %>',
  // Tagging
  'Item tags': 'Item tags',
  'Suggested tags': 'Suggested tags',
  'Tags': 'Tags',
  'add a tag': 'add a tag',
  // Collection widgets
  'Add': 'Add',
  'Choose type to add': 'Choose type to add'
};
