/**
 * @file
 * VIE DOM parsing service for Edit.
 */
(function(jQuery, _, VIE, Drupal, drupalSettings) {

"use strict";

  VIE.prototype.EditService = function (options) {
    var defaults = {
      name: 'edit',
      subjectSelector: '.edit-field.edit-allowed'
    };
    this.options = _.extend({}, defaults, options);

    this.views = [];
    this.vie = null;
    this.name = this.options.name;
  };

  VIE.prototype.EditService.prototype = {
    load: function (loadable) {
      var correct = loadable instanceof this.vie.Loadable;
      if (!correct) {
        throw new Error('Invalid Loadable passed');
      }

      var element;
      if (!loadable.options.element) {
        if (typeof document === 'undefined') {
          return loadable.resolve([]);
        } else {
          element = drupalSettings.edit.context;
        }
      } else {
        element = loadable.options.element;
      }

      var entities = this.readEntities(element);
      loadable.resolve(entities);
    },

    _getViewForElement:function (element, collectionView) {
      var viewInstance;

      jQuery.each(this.views, function () {
        if (jQuery(this.el).get(0) === element.get(0)) {
          if (collectionView && !this.template) {
            return true;
          }
          viewInstance = this;
          return false;
        }
      });
      return viewInstance;
    },

    _registerEntityView:function (entity, element, isNew) {
      if (!element.length) {
        return;
      }

      // Let's only have this overhead for direct types. Form-based editors are
      // handled in backbone.drupalform.js and the PropertyEditor instance.
      if (!jQuery(element).hasClass('edit-type-direct')) {
        return;
      }

      var service = this;
      var viewInstance = this._getViewForElement(element);
      if (viewInstance) {
        return viewInstance;
      }

      viewInstance = new this.vie.view.Entity({
        model:entity,
        el:element,
        tagName:element.get(0).nodeName,
        vie:this.vie,
        service:this.name
      });

      this.views.push(viewInstance);

      return viewInstance;
    },

    save: function(saveable) {
      var correct = saveable instanceof this.vie.Savable;
      if (!correct) {
        throw "Invalid Savable passed";
      }

      if (!saveable.options.element) {
        // FIXME: we could find element based on subject
        throw "Unable to write entity to edit.module-markup, no element given";
      }

      if (!saveable.options.entity) {
        throw "Unable to write to edit.module-markup, no entity given";
      }

      var $element = jQuery(saveable.options.element);
      this._writeEntity(saveable.options.entity, saveable.options.element);
      saveable.resolve();
    },

    _writeEntity:function (entity, element) {
      var service = this;
      this.findPredicateElements(this.getElementSubject(element), element, true).each(function () {
        var predicateElement = jQuery(this);
        var predicate = service.getElementPredicate(predicateElement);
        if (!entity.has(predicate)) {
          return true;
        }

        var value = entity.get(predicate);
        if (value && value.isCollection) {
          // Handled by CollectionViews separately
          return true;
        }
        if (value === service.readElementValue(predicate, predicateElement)) {
          return true;
        }
        // Unlike in the VIE's RdfaService no (re-)mapping needed here.
        predicateElement.html(value);
      });
      return true;
    },

    // The edit-id data attribute contains the full identifier of
    // each entity element in the format
    // `<entity type>:<id>:<field name>:<language code>:<view mode>`.
    _getID: function (element) {
      var id = jQuery(element).attr('data-edit-id');
      if (!id) {
        id = jQuery(element).closest('[data-edit-id]').attr('data-edit-id');
      }
      return id;
    },

    // Returns the "URI" of an entity of an element in format
    // `<entity type>/<id>`.
    getElementSubject: function (element) {
      return this._getID(element).split(':').slice(0, 2).join('/');
    },

    // Returns the field name for an element in format
    // `<field name>/<language code>/<view mode>`.
    // (Slashes instead of colons because the field name is no namespace.)
    getElementPredicate: function (element) {
      if (!this._getID(element)) {
        throw new Error('Could not find predicate for element');
      }
      return this._getID(element).split(':').slice(2, 5).join('/');
    },

    getElementType: function (element) {
      return this._getID(element).split(':').slice(0, 1)[0];
    },

    // Reads all editable entities (currently each Drupal field is considered an
    // entity, in the future Drupal entities should be mapped to VIE entities)
    // from DOM and returns the VIE enties it found.
    readEntities: function (element) {
      var service = this;
      var entities = [];
      var entityElements = jQuery(this.options.subjectSelector, element);
      entityElements = entityElements.add(jQuery(element).filter(this.options.subjectSelector));
      entityElements.each(function () {
        var entity = service._readEntity(jQuery(this));
        if (entity) {
          entities.push(entity);
        }
      });
      return entities;
    },

    // Returns a filled VIE Entity instance for a DOM element. The Entity
    // is also registered in the VIE entities collection.
    _readEntity: function (element) {
      var subject = this.getElementSubject(element);
      var type = this.getElementType(element);
      var entity = this._readEntityPredicates(subject, element, false);
      if (jQuery.isEmptyObject(entity)) {
        return null;
      }
      entity['@subject'] = subject;
      if (type) {
        entity['@type'] = this._registerType(type, element);
      }

      var entityInstance = new this.vie.Entity(entity);
      entityInstance = this.vie.entities.addOrUpdate(entityInstance, {
        updateOptions: {
          silent: true,
          ignoreChanges: true
        }
      });

      this._registerEntityView(entityInstance, element);
      return entityInstance;
    },

    _registerType: function (typeId, element) {
      typeId = '<http://viejs.org/ns/' + typeId + '>';
      var type = this.vie.types.get(typeId);
      if (!type) {
        this.vie.types.add(typeId, []);
        type = this.vie.types.get(typeId);
      }

      var predicate = this.getElementPredicate(element);
      if (type.attributes.get(predicate)) {
        return type;
      }

      var label = element.data('edit-field-label');
      var range = 'Form';
      if (element.hasClass('edit-type-direct')) {
        range = 'Direct';
      }
      if (element.hasClass('edit-type-direct-with-wysiwyg')) {
        range = 'Wysiwyg';
      }
      type.attributes.add(predicate, [range], 0, 1, {
        label: element.data('edit-field-label')
      });

      return type;
    },

    _readEntityPredicates: function (subject, element, emptyValues) {
      var entityPredicates = {};
      var service = this;
      this.findPredicateElements(subject, element, true).each(function () {
        var predicateElement = jQuery(this);
        var predicate = service.getElementPredicate(predicateElement);
        if (!predicate) {
          return;
        }
        var value = service.readElementValue(predicate, predicateElement);
        if (value === null && !emptyValues) {
          return;
        }

        entityPredicates[predicate] = value;
        entityPredicates[predicate + '/rendered'] = predicateElement[0].outerHTML;
      });
      return entityPredicates;
    },

    readElementValue : function(predicate, element) {
      // Unlike in RdfaService there is parsing needed here.
      if (element.hasClass('edit-type-form')) {
        return undefined;
      }
      else {
        return jQuery.trim(element.html());
      }
    },

    // Subject elements are the DOM elements containing a single or multiple
    // editable fields.
    findSubjectElements: function (element) {
      if (!element) {
        element = drupalSettings.edit.context;
      }
      return jQuery(this.options.subjectSelector, element);
    },

    // Predicate Elements are the actual DOM elements that users will be able
    // to edit.
    findPredicateElements: function (subject, element, allowNestedPredicates, stop) {
      var predicates = jQuery();
      // Make sure that element is wrapped by jQuery.
      var $element = jQuery(element);

      // Form-type predicates
      predicates = predicates.add($element.filter('.edit-type-form'));

      // Direct-type predicates
      var direct = $element.filter('.edit-type-direct');
      predicates = predicates.add(direct.find('.field-item'));

      if (!predicates.length && !stop) {
        var parentElement = $element.parent(this.options.subjectSelector);
        if (parentElement.length) {
          return this.findPredicateElements(subject, parentElement, allowNestedPredicates, true);
        }
      }

      return predicates;
    }
  };

})(jQuery, _, VIE, Drupal, drupalSettings);
