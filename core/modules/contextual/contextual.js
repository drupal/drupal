/**
 * @file
 * Attaches behaviors for the Contextual module.
 */

(function ($, Drupal, drupalSettings, Backbone, Modernizr) {

"use strict";

var options = $.extend({
  strings: {
    open: Drupal.t('open'),
    close: Drupal.t('close')
  }
}, drupalSettings.contextual);

/**
 * Initializes a contextual link: updates its DOM, sets up model and views
 *
 * @param jQuery $contextual
 *   A contextual links placeholder DOM element, containing the actual
 *   contextual links as rendered by the server.
 */
function initContextual ($contextual) {
  var $region = $contextual.closest('.contextual-region');
  var contextual = Drupal.contextual;

  $contextual
    // Use the placeholder as a wrapper with a specific class to provide
    // positioning and behavior attachment context.
    .addClass('contextual')
    // Ensure a trigger element exists before the actual contextual links.
    .prepend(Drupal.theme('contextualTrigger'));

  // Create a model and the appropriate views.
  var model = new contextual.Model({
    title: $region.find('h2:first').text().trim()
  });
  var viewOptions = $.extend({ el: $contextual, model: model }, options);
  contextual.views.push({
    visual: new contextual.VisualView(viewOptions),
    aural: new contextual.AuralView(viewOptions),
    keyboard: new contextual.KeyboardView(viewOptions)
  });
  contextual.regionViews.push(new contextual.RegionView(
    $.extend({ el: $region, model: model }, options))
  );

  // Add the model to the collection. This must happen after the views have been
  // associated with it, otherwise collection change event handlers can't
  // trigger the model change event handler in its views.
  contextual.collection.add(model);

  // Let other JavaScript react to the adding of a new contextual link.
  $(document).trigger('drupalContextualLinkAdded', {
    $el: $contextual,
    $region: $region,
    model: model
  });

  // Fix visual collisions between contextual link triggers.
  adjustIfNestedAndOverlapping($contextual);
}

/**
 * Determines if a contextual link is nested & overlapping, if so: adjusts it.
 *
 * This only deals with two levels of nesting; deeper levels are not touched.
 *
 * @param jQuery $contextual
 *   A contextual links placeholder DOM element, containing the actual
 *   contextual links as rendered by the server.
 */
function adjustIfNestedAndOverlapping ($contextual) {
  var $contextuals = $contextual
    // @todo confirm that .closest() is not sufficient
    .parents('.contextual-region').eq(-1)
    .find('.contextual');

  // Early-return when there's no nesting.
  if ($contextuals.length === 1) {
    return;
  }

  // If the two contextual links overlap, then we move the second one.
  var firstTop = $contextuals.eq(0).offset().top;
  var secondTop = $contextuals.eq(1).offset().top;
  if (firstTop === secondTop) {
    var $nestedContextual = $contextuals.eq(1);

    // Retrieve height of nested contextual link.
    var height = 0;
    var $trigger = $nestedContextual.find('.trigger');
    // Elements with the .visually-hidden class have no dimensions, so this
    // class must be temporarily removed to the calculate the height.
    $trigger.removeClass('visually-hidden');
    height = $nestedContextual.height();
    $trigger.addClass('visually-hidden');

    // Adjust nested contextual link's position.
    $nestedContextual.css({ top: $nestedContextual.position().top + height });
  }
}

/**
 * Attaches outline behavior for regions associated with contextual links.
 *
 * Events
 *   Contextual triggers an event that can be used by other scripts.
 *   - drupalContextualLinkAdded: Triggered when a contextual link is added.
 */
Drupal.behaviors.contextual = {
  attach: function (context) {
    var $context = $(context);

    // Find all contextual links placeholders, if any.
    var $placeholders = $context.find('[data-contextual-id]').once('contextual-render');
    if ($placeholders.length === 0) {
      return;
    }

    // Collect the IDs for all contextual links placeholders.
    var ids = [];
    $placeholders.each(function () {
      ids.push($(this).attr('data-contextual-id'));
    });

    // Perform an AJAX request to let the server render the contextual links for
    // each of the placeholders.
    $.ajax({
      url: Drupal.url('contextual/render') + '?destination=' + Drupal.encodePath(drupalSettings.currentPath),
      type: 'POST',
      data: { 'ids[]' : ids },
      dataType: 'json',
      success: function (results) {
        for (var id in results) {
          if (results.hasOwnProperty(id)) {
            // Update the placeholder to contain its rendered contextual links.
            var $placeholder = $context.find('[data-contextual-id="' + id + '"]')
              .html(results[id]);

            // Initialize the contextual link.
            initContextual($placeholder);
          }
        }
      }
     });
  }
};

/**
 * Model and View definitions.
 */
Drupal.contextual = {
  // The Drupal.contextual.View instances associated with each list element of
  // contextual links.
  views: [],

  // The Drupal.contextual.RegionView instances associated with each contextual
  // region element.
  regionViews: [],

  /**
   * Models the state of a contextual link's trigger and list.
   */
  Model: Backbone.Model.extend({
    defaults: {
      // The title of the entity to which these contextual links apply.
      title: '',
      // Represents if the contextual region is being hovered.
      regionIsHovered: false,
      // Represents if the contextual trigger or options have focus.
      hasFocus: false,
      // Represents if the contextual options for an entity are available to
      // be selected.
      isOpen: false,
      // When the model is locked, the trigger remains active.
      isLocked: false
    },

    /**
     * Opens or closes the contextual link.
     *
     * If it is opened, then also give focus.
     */
    toggleOpen: function () {
      var newIsOpen = !this.get('isOpen');
      this.set('isOpen', newIsOpen);
      if (newIsOpen) {
        this.focus();
      }
      return this;
    },

    /**
     * Closes this contextual link.
     *
     * Does not call blur() because we want to allow a contextual link to have
     * focus, yet be closed for example when hovering.
     */
    close: function () {
      this.set('isOpen', false);
      return this;
    },

    /**
     * Gives focus to this contextual link.
     *
     * Also closes + removes focus from every other contextual link.
     */
    focus: function () {
      this.set('hasFocus', true);
      var cid = this.cid;
      this.collection.each(function (model) {
        if (model.cid !== cid) {
          model.close().blur();
        }
      });
      return this;
    },

    /**
     * Removes focus from this contextual link, unless it is open.
     */
    blur: function () {
      if (!this.get('isOpen')) {
        this.set('hasFocus', false);
      }
      return this;
    }
  }),

  /**
   * Renders the visual view of a contextual link. Listens to mouse & touch.
   */
  VisualView: Backbone.View.extend({
    events: function () {
      // Prevents delay and simulated mouse events.
      var touchEndToClick = function (event) {
        event.preventDefault();
        event.target.click();
      };
      var mapping = {
        'click .trigger': function () { this.model.toggleOpen(); },
        'touchend .trigger': touchEndToClick,
        'click .contextual-links a': function () { this.model.close().blur(); },
        'touchend .contextual-links a': touchEndToClick
      };
      // We only want mouse hover events on non-touch.
      if (!Modernizr.touch) {
        mapping.mouseenter =  function () { this.model.focus(); };
      }
      return mapping;
    },

    /**
     * {@inheritdoc}
     */
    initialize: function () {
      this.model.on('change', this.render, this);
    },

    /**
     * {@inheritdoc}
     */
    render: function () {
      var isOpen = this.model.get('isOpen');
      // The trigger should be visible when:
      //  - the mouse hovered over the region,
      //  - the trigger is locked,
      //  - and for as long as the contextual menu is open.
      var isVisible = this.model.get('isLocked') || this.model.get('regionIsHovered') || isOpen;

      this.$el
        // The open state determines if the links are visible.
        .toggleClass('open', isOpen)
        // Update the visibility of the trigger.
        .find('.trigger').toggleClass('visually-hidden', !isVisible);

      // Nested contextual region handling: hide any nested contextual triggers.
      if ('isOpen' in this.model.changed) {
        this.$el.closest('.contextual-region')
          .find('.contextual .trigger:not(:first)')
          .toggle(!isOpen);
      }

      return this;
    }
  }),

  /**
   * Renders the aural view of a contextual link (i.e. screen reader support).
   */
  AuralView: Backbone.View.extend({
    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      this.model.on('change', this.render, this);

      // Use aria-role form so that the number of items in the list is spoken.
      this.$el.attr('role', 'form');

      // Initial render.
      this.render();
    },

    /**
     * {@inheritdoc}
     */
    render: function () {
      var isOpen = this.model.get('isOpen');

      // Set the hidden property of the links.
      this.$el.find('.contextual-links')
        .prop('hidden', !isOpen);

      // Update the view of the trigger.
      this.$el.find('.trigger')
        .text(Drupal.t('@action @title configuration options', {
          '@action': (!isOpen) ? this.options.strings.open : this.options.strings.close,
          '@title': this.model.get('title')
        }))
        .attr('aria-pressed', isOpen);
    }
  }),

  /**
   * Listens to keyboard.
   */
  KeyboardView: Backbone.View.extend({
    events: {
      'focus .trigger': 'focus',
      'focus .contextual-links a': 'focus',
      'blur .trigger': function () { this.model.blur(); },
      'blur .contextual-links a': function () {
        // Set up a timeout to allow a user to tab between the trigger and the
        // contextual links without the menu dismissing.
        var that = this;
        this.timer = window.setTimeout(function () {
          that.model.close().blur();
        }, 150);
      }
    },

    /**
     * {@inheritdoc}
     */
    initialize: function () {
      // The timer is used to create a delay before dismissing the contextual
      // links on blur. This is only necessary when keyboard users tab into
      // contextual links without edit mode (i.e. without TabbingManager).
      // That means that if we decide to disable tabbing of contextual links
      // without edit mode, all this timer logic can go away.
      this.timer = NaN;
    },

    /**
     * Sets focus on the model; Clears the timer that dismisses the links.
     */
    focus: function () {
      // Clear the timeout that might have been set by blurring a link.
      window.clearTimeout(this.timer);
      this.model.focus();
    }
  }),

  /**
   * Renders the visual view of a contextual region element.
   */
  RegionView: Backbone.View.extend({
    events: function () {
      var mapping = {
        mouseenter: function () { this.model.set('regionIsHovered', true); },
        mouseleave: function () {
          this.model.close().blur().set('regionIsHovered', false);
        }
      };
      // We don't want mouse hover events on touch.
      if (Modernizr.touch) {
        mapping = {};
      }
      return mapping;
    },

    /**
     * {@inheritdoc}
     */
    initialize: function () {
      this.model.on('change:hasFocus', this.render, this);
    },

    /**
     * {@inheritdoc}
     */
    render: function () {
      this.$el.toggleClass('focus', this.model.get('hasFocus'));

      return this;
    }
  })
};

// A Backbone.Collection of Drupal.contextual.Model instances.
Drupal.contextual.collection = new Backbone.Collection([], { model: Drupal.contextual.Model });

/**
 * A trigger is an interactive element often bound to a click handler.
 *
 * @return String
 *   A string representing a DOM fragment.
 */
Drupal.theme.contextualTrigger = function () {
  return '<button class="trigger visually-hidden focusable" type="button"></button>';
};

})(jQuery, Drupal, drupalSettings, Backbone, Modernizr);
