/**
 * @file
 * A Backbone View that renders the visual view of a contextual region element.
 */

(function (Drupal, Backbone) {
  /**
   * @deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. There is no
   *  replacement.
   */
  Drupal.contextual.RegionView = Backbone.View.extend(
    /** @lends Drupal.contextual.RegionView# */ {
      /**
       * Events for the Backbone view.
       *
       * @return {object}
       *   A mapping of events to be used in the view.
       */
      events() {
        // Used for tracking the presence of touch events. When true, the
        // mousemove and mouseenter event handlers are effectively disabled.
        // This is used instead of preventDefault() on touchstart as some
        // touchstart events are not cancelable.
        let touchStart = false;
        return {
          touchstart() {
            // Set to true so the mouseenter and mouseleave events that follow
            // know to not execute any hover related logic.
            touchStart = true;
          },
          mouseenter() {
            if (!touchStart) {
              this.model.set('regionIsHovered', true);
            }
          },
          mouseleave() {
            if (!touchStart) {
              this.model.close().blur().set('regionIsHovered', false);
            }
          },
          mousemove() {
            // Because there are scenarios where there are both touchscreens
            // and pointer devices, the touchStart flag should be set back to
            // false after mouseenter and mouseleave complete. It will be set to
            // true if another touchstart event occurs.
            touchStart = false;
          },
        };
      },

      /**
       * Renders the visual view of a contextual region element.
       *
       * @constructs
       *
       * @augments Backbone.View
       */
      initialize() {
        this.listenTo(this.model, 'change:hasFocus', this.render);
      },

      /**
       * {@inheritdoc}
       *
       * @return {Drupal.contextual.RegionView}
       *   The current contextual region view.
       */
      render() {
        this.$el.toggleClass('focus', this.model.get('hasFocus'));

        return this;
      },
    },
  );
})(Drupal, Backbone);
