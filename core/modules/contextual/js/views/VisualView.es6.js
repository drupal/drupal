/**
 * @file
 * A Backbone View that provides the visual view of a contextual link.
 */

(function(Drupal, Backbone, Modernizr) {
  Drupal.contextual.VisualView = Backbone.View.extend(
    /** @lends Drupal.contextual.VisualView# */ {
      /**
       * Events for the Backbone view.
       *
       * @return {object}
       *   A mapping of events to be used in the view.
       */
      events() {
        // Prevents delay and simulated mouse events.
        const touchEndToClick = function(event) {
          event.preventDefault();
          event.target.click();
        };
        const mapping = {
          'click .trigger': function() {
            this.model.toggleOpen();
          },
          'touchend .trigger': touchEndToClick,
          'click .contextual-links a': function() {
            this.model.close().blur();
          },
          'touchend .contextual-links a': touchEndToClick,
        };
        // We only want mouse hover events on non-touch.
        if (!Modernizr.touchevents) {
          mapping.mouseenter = function() {
            this.model.focus();
          };
        }
        return mapping;
      },

      /**
       * Renders the visual view of a contextual link. Listens to mouse & touch.
       *
       * @constructs
       *
       * @augments Backbone.View
       */
      initialize() {
        this.listenTo(this.model, 'change', this.render);
      },

      /**
       * @inheritdoc
       *
       * @return {Drupal.contextual.VisualView}
       *   The current contextual visual view.
       */
      render() {
        const isOpen = this.model.get('isOpen');
        // The trigger should be visible when:
        //  - the mouse hovered over the region,
        //  - the trigger is locked,
        //  - and for as long as the contextual menu is open.
        const isVisible =
          this.model.get('isLocked') ||
          this.model.get('regionIsHovered') ||
          isOpen;

        this.$el
          // The open state determines if the links are visible.
          .toggleClass('open', isOpen)
          // Update the visibility of the trigger.
          .find('.trigger')
          .toggleClass('visually-hidden', !isVisible);

        // Nested contextual region handling: hide any nested contextual triggers.
        if ('isOpen' in this.model.changed) {
          this.$el
            .closest('.contextual-region')
            .find('.contextual .trigger:not(:first)')
            .toggle(!isOpen);
        }

        return this;
      },
    },
  );
})(Drupal, Backbone, Modernizr);
