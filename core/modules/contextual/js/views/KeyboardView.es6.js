/**
 * @file
 * A Backbone View that provides keyboard interaction for a contextual link.
 */

(function (Drupal, Backbone) {
  Drupal.contextual.KeyboardView = Backbone.View.extend(/** @lends Drupal.contextual.KeyboardView# */{

    /**
     * @type {object}
     */
    events: {
      'focus .trigger': 'focus',
      'focus .contextual-links a': 'focus',
      'blur .trigger': function () {
        this.model.blur();
      },
      'blur .contextual-links a': function () {
        // Set up a timeout to allow a user to tab between the trigger and the
        // contextual links without the menu dismissing.
        const that = this;
        this.timer = window.setTimeout(() => {
          that.model.close().blur();
        }, 150);
      },
    },

    /**
     * Provides keyboard interaction for a contextual link.
     *
     * @constructs
     *
     * @augments Backbone.View
     */
    initialize() {
      /**
       * The timer is used to create a delay before dismissing the contextual
       * links on blur. This is only necessary when keyboard users tab into
       * contextual links without edit mode (i.e. without TabbingManager).
       * That means that if we decide to disable tabbing of contextual links
       * without edit mode, all this timer logic can go away.
       *
       * @type {NaN|number}
       */
      this.timer = NaN;
    },

    /**
     * Sets focus on the model; Clears the timer that dismisses the links.
     */
    focus() {
      // Clear the timeout that might have been set by blurring a link.
      window.clearTimeout(this.timer);
      this.model.focus();
    },

  });
}(Drupal, Backbone));
