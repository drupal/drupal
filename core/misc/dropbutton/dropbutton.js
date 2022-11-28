/**
 * @file
 * Dropbutton feature.
 */

(function ($, Drupal) {
  /**
   * A DropButton presents an HTML list as a button with a primary action.
   *
   * All secondary actions beyond the first in the list are presented in a
   * dropdown list accessible through a toggle arrow associated with the button.
   *
   * @constructor Drupal.DropButton
   *
   * @param {HTMLElement} dropbutton
   *   A DOM element.
   * @param {object} settings
   *   A list of options including:
   * @param {string} settings.title
   *   The text inside the toggle link element. This text is hidden
   *   from visual UAs.
   */
  function DropButton(dropbutton, settings) {
    // Merge defaults with settings.
    const options = $.extend(
      { title: Drupal.t('List additional actions') },
      settings,
    );
    const $dropbutton = $(dropbutton);

    /**
     * @type {jQuery}
     */
    this.$dropbutton = $dropbutton;

    /**
     * @type {jQuery}
     */
    this.$list = $dropbutton.find('.dropbutton');

    /**
     * Find actions and mark them.
     *
     * @type {jQuery}
     */
    this.$actions = this.$list.find('li').addClass('dropbutton-action');

    // Add the special dropdown only if there are hidden actions.
    if (this.$actions.length > 1) {
      // Identify the first element of the collection.
      const $primary = this.$actions.slice(0, 1);
      // Identify the secondary actions.
      const $secondary = this.$actions.slice(1);
      $secondary.addClass('secondary-action');
      // Add toggle link.
      $primary.after(Drupal.theme('dropbuttonToggle', options));
      // Bind mouse events.
      this.$dropbutton.addClass('dropbutton-multiple').on({
        /**
         * Adds a timeout to close the dropdown on mouseleave.
         *
         * @ignore
         */
        'mouseleave.dropbutton': $.proxy(this.hoverOut, this),

        /**
         * Clears timeout when mouseout of the dropdown.
         *
         * @ignore
         */
        'mouseenter.dropbutton': $.proxy(this.hoverIn, this),

        /**
         * Similar to mouseleave/mouseenter, but for keyboard navigation.
         *
         * @ignore
         */
        'focusout.dropbutton': $.proxy(this.focusOut, this),

        /**
         * @ignore
         */
        'focusin.dropbutton': $.proxy(this.focusIn, this),
      });
    } else {
      this.$dropbutton.addClass('dropbutton-single');
    }
  }

  /**
   * Delegated callback for opening and closing dropbutton secondary actions.
   *
   * @function Drupal.DropButton~dropbuttonClickHandler
   *
   * @param {jQuery.Event} e
   *   The event triggered.
   */
  function dropbuttonClickHandler(e) {
    e.preventDefault();
    $(e.target).closest('.dropbutton-wrapper').toggleClass('open');
  }

  /**
   * Process elements with the .dropbutton class on page load.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches dropButton behaviors.
   */
  Drupal.behaviors.dropButton = {
    attach(context, settings) {
      const dropbuttons = once('dropbutton', '.dropbutton-wrapper', context);
      if (dropbuttons.length) {
        // Adds the delegated handler that will toggle dropdowns on click.
        const body = once('dropbutton-click', 'body');
        if (body.length) {
          $(body).on('click', '.dropbutton-toggle', dropbuttonClickHandler);
        }
        // Initialize all buttons.
        dropbuttons.forEach((dropbutton) => {
          DropButton.dropbuttons.push(
            new DropButton(dropbutton, settings.dropbutton),
          );
        });
      }
    },
  };

  /**
   * Extend the DropButton constructor.
   */
  $.extend(
    DropButton,
    /** @lends Drupal.DropButton */ {
      /**
       * Store all processed DropButtons.
       *
       * @type {Array.<Drupal.DropButton>}
       */
      dropbuttons: [],
    },
  );

  /**
   * Extend the DropButton prototype.
   */
  $.extend(
    DropButton.prototype,
    /** @lends Drupal.DropButton# */ {
      /**
       * Toggle the dropbutton open and closed.
       *
       * @param {boolean} [show]
       *   Force the dropbutton to open by passing true or to close by
       *   passing false.
       */
      toggle(show) {
        const isBool = typeof show === 'boolean';
        show = isBool ? show : !this.$dropbutton.hasClass('open');
        this.$dropbutton.toggleClass('open', show);
      },

      /**
       * @method
       */
      hoverIn() {
        // Clear any previous timer we were using.
        if (this.timerID) {
          window.clearTimeout(this.timerID);
        }
      },

      /**
       * @method
       */
      hoverOut() {
        // Wait half a second before closing.
        this.timerID = window.setTimeout($.proxy(this, 'close'), 500);
      },

      /**
       * @method
       */
      open() {
        this.toggle(true);
      },

      /**
       * @method
       */
      close() {
        this.toggle(false);
      },

      /**
       * @param {jQuery.Event} e
       *   The event triggered.
       */
      focusOut(e) {
        this.hoverOut.call(this, e);
      },

      /**
       * @param {jQuery.Event} e
       *   The event triggered.
       */
      focusIn(e) {
        this.hoverIn.call(this, e);
      },
    },
  );

  $.extend(
    Drupal.theme,
    /** @lends Drupal.theme */ {
      /**
       * A toggle is an interactive element often bound to a click handler.
       *
       * @param {object} options
       *   Options object.
       * @param {string} [options.title]
       *   The button text.
       *
       * @return {string}
       *   A string representing a DOM fragment.
       */
      dropbuttonToggle(options) {
        return `<li class="dropbutton-toggle"><button type="button"><span class="dropbutton-arrow"><span class="visually-hidden">${options.title}</span></span></button></li>`;
      },
    },
  );

  // Expose constructor in the public space.
  Drupal.DropButton = DropButton;
})(jQuery, Drupal);
