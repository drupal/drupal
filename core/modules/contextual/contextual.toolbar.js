/**
 * @file
 * Attaches behaviors for the Contextual module's edit toolbar tab.
 */

(function ($, Drupal, Backbone) {

"use strict";

var options = {
  strings: {
    tabbingReleased: Drupal.t('Tabbing is no longer constrained by the Contextual module.'),
    tabbingConstrained: Drupal.t('Tabbing is constrained to a set of @contextualsCount and the edit mode toggle.'),
    pressEsc: Drupal.t('Press the esc key to exit.')
  }
};

/**
 * Initializes a contextual link: updates its DOM, sets up model and views
 *
 * @param DOM links
 *   A contextual links DOM element as rendered by the server.
 */
function initContextualToolbar (context) {
  var contextualToolbar = Drupal.contextualToolbar;
  var model = contextualToolbar.model = new contextualToolbar.Model();

  var viewOptions = $.extend({
    el: $('.js .toolbar .bar .contextual-toolbar-tab'),
    model: model
  }, options);
  new contextualToolbar.VisualView(viewOptions);
  new contextualToolbar.AuralView(viewOptions);

  // Update the model based on overlay events.
  $(document).on({
    'drupalOverlayOpen.contextualToolbar': function () {
      model.set('overlayIsOpen', true);
    },
    'drupalOverlayClose.contextualToolbar': function () {
      model.set('overlayIsOpen', false);
    }
  });

  // Show the edit tab while there's >=1 contextual link.
  if (Drupal.contextual && Drupal.contextual.collection) {
    var contextualCollection = Drupal.contextual.collection;
    var trackContextualCount = function () {
      model.set('contextualCount', contextualCollection.length);
    };
    contextualCollection.on('reset remove add', trackContextualCount);
    trackContextualCount();

    // Whenever edit mode is toggled, lock all contextual links.
    model.on('change:isViewing', function() {
      contextualCollection.each(function (contextualModel) {
        contextualModel.set('isLocked', !model.get('isViewing'));
      });
    });
    // When a new contextual link is added and edit mode is enabled, lock it.
    contextualCollection.on('add', function (contextualModel) {
      if (!model.get('isViewing')) {
        contextualModel.set('isLocked', true);
      }
    });
  }

  // Checks whether localStorage indicates we should start in edit mode
  // rather than view mode.
  // @see Drupal.contextualToolbar.VisualView.persist()
  if (localStorage.getItem('Drupal.contextualToolbar.isViewing') === 'false') {
    model.set('isViewing', false);
  }
}

/**
 * Attaches contextual's edit toolbar tab behavior.
 */
Drupal.behaviors.contextualToolbar = {
  attach: function (context) {
    if ($('body').once('contextualToolbar-init').length) {
      initContextualToolbar(context);
    }
  }
};

/**
 * Model and View definitions.
 */
Drupal.contextualToolbar = {
  // The Drupal.contextualToolbar.Model instance.
  model: null,

  /**
   * Models the state of the edit mode toggle.
   */
  Model: Backbone.Model.extend({
    defaults: {
      // Indicates whether the toggle is currently in "view" or "edit" mode.
      isViewing: true,
      // Indicates whether the toggle should be visible or hidden. Automatically
      // calculated, depends on overlayIsOpen and contextualCount.
      isVisible: false,
      // Indicates whether the overlay is open or not.
      overlayIsOpen: false,
      // Tracks how many contextual links exist on the page.
      contextualCount: 0,
      // A TabbingContext object as returned by Drupal.TabbingManager: the set
      // of tabbable elements when edit mode is enabled.
      tabbingContext: null
    },
    initialize: function () {
      this.on('change:overlayIsOpen change:contextualCount', function (model) {
        model.set('isVisible', !model.get('overlayIsOpen') && model.get('contextualCount') > 0);
      });
    }
  }),

  /**
   * Renders the visual view of the edit mode toggle. Listens to mouse & touch.
   *
   * Handles edit mode toggle interactions.
   */
  VisualView: Backbone.View.extend({
    events: function () {
      // Prevents delay and simulated mouse events.
      var touchEndToClick = function (event) {
        event.preventDefault();
        event.target.click();
      };

      return {
        'click': function () {
          this.model.set('isViewing', !this.model.get('isViewing'));
        },
        'touchend': touchEndToClick
      };
    },

    /**
     * {@inheritdoc}
     */
    initialize: function () {
      this.model.on('change', this.render, this);
      this.model.on('change:isViewing', this.persist, this);
    },

    /**
     * {@inheritdoc}
     */
    render: function () {
      // Render the visibility.
      this.$el.toggleClass('element-hidden', !this.model.get('isVisible'));
      // Render the state.
      this.$el.find('button').toggleClass('active', !this.model.get('isViewing'));

      return this;
    },

    /**
     * Model change handler; persists the isViewing value to localStorage.
     *
     * isViewing === true is the default, so only stores in localStorage when
     * it's not the default value (i.e. false).
     *
     * @param Drupal.contextualToolbar.Model model
     *   A Drupal.contextualToolbar.Model model.
     * @param bool isViewing
     *   The value of the isViewing attribute in the model.
     */
    persist: function (model, isViewing) {
      if (!isViewing) {
        localStorage.setItem('Drupal.contextualToolbar.isViewing', 'false');
      }
      else {
        localStorage.removeItem('Drupal.contextualToolbar.isViewing');
      }
    }
  }),

  /**
   * Renders the aural view of the edit mode toggle (i.e.screen reader support).
   */
  AuralView: Backbone.View.extend({
    // Tracks whether the tabbing constraint announcement has been read once yet.
    announcedOnce: false,

    /*
     * {@inheritdoc}
     */
    initialize: function () {
      this.model.on('change', this.render, this);
      this.model.on('change:isViewing', this.manageTabbing, this);

      $(document).on('keyup', _.bind(this.onKeypress, this));
    },

    /**
     * {@inheritdoc}
     */
    render: function () {
      // Render the state.
      this.$el.find('button').attr('aria-pressed', !this.model.get('isViewing'));

      return this;
    },

    /**
     * Limits tabbing to the contextual links and edit mode toolbar tab.
     *
     * @param Drupal.contextualToolbar.Model model
     *   A Drupal.contextualToolbar.Model model.
     * @param bool isViewing
     *   The value of the isViewing attribute in the model.
     */
    manageTabbing: function () {
      var tabbingContext = this.model.get('tabbingContext');
      // Always release an existing tabbing context.
      if (tabbingContext) {
        tabbingContext.release();
        Drupal.announce(this.options.strings.tabbingReleased);
      }
      // Create a new tabbing context when edit mode is enabled.
      if (!this.model.get('isViewing')) {
        tabbingContext = Drupal.tabbingManager.constrain($('.contextual-toolbar-tab, .contextual'));
        this.model.set('tabbingContext', tabbingContext);
        this.announceTabbingConstraint();
        this.announcedOnce = true;
      }
    },

    /**
     * Announces the current tabbing constraint.
     */
    announceTabbingConstraint: function () {
      var strings = this.options.strings;
      Drupal.announce(Drupal.formatString(strings.tabbingConstrained, {
        '@contextualsCount': Drupal.formatPlural(Drupal.contextual.collection.length, '@count contextual link', '@count contextual links')
      }));
      Drupal.announce(strings.pressEsc);
    },

    /**
     * Responds to esc and tab key press events.
     *
     * @param jQuery.Event event
     */
    onKeypress: function (event) {
      // The first tab key press is tracked so that an annoucement about tabbing
      // constraints can be raised if edit mode is enabled when the page is
      // loaded.
      if (!this.announcedOnce && event.keyCode === 9 && !this.model.get('isViewing')) {
        this.announceTabbingConstraint();
        // Set announce to true so that this conditional block won't run again.
        this.announcedOnce = true;
      }
      // Respond to the ESC key. Exit out of edit mode.
      if (event.keyCode === 27) {
        this.model.set('isViewing', true);
      }
    }
  })
};

})(jQuery, Drupal, Backbone);
