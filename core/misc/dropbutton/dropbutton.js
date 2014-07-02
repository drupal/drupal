(function ($, Drupal) {

  "use strict";

  /**
   * Process elements with the .dropbutton class on page load.
   */
  Drupal.behaviors.dropButton = {
    attach: function (context, settings) {
      var $dropbuttons = $(context).find('.dropbutton-wrapper').once('dropbutton');
      if ($dropbuttons.length) {
        // Adds the delegated handler that will toggle dropdowns on click.
        var $body = $('body').once('dropbutton-click');
        if ($body.length) {
          $body.on('click', '.dropbutton-toggle', dropbuttonClickHandler);
        }
        // Initialize all buttons.
        for (var i = 0, il = $dropbuttons.length; i < il; i++) {
          DropButton.dropbuttons.push(new DropButton($dropbuttons[i], settings.dropbutton));
        }
      }
    }
  };

  /**
   * Delegated callback for opening and closing dropbutton secondary actions.
   */
  function dropbuttonClickHandler(e) {
    e.preventDefault();
    $(e.target).closest('.dropbutton-wrapper').toggleClass('open');
  }

  /**
   * A DropButton presents an HTML list as a button with a primary action.
   *
   * All secondary actions beyond the first in the list are presented in a
   * dropdown list accessible through a toggle arrow associated with the button.
   *
   * @param {jQuery} $dropbutton
   *   A jQuery element.
   *
   * @param {Object} settings
   *   A list of options including:
   *    - {String} title: The text inside the toggle link element. This text is
   *      hidden from visual UAs.
   */
  function DropButton(dropbutton, settings) {
    // Merge defaults with settings.
    var options = $.extend({'title': Drupal.t('List additional actions')}, settings);
    var $dropbutton = $(dropbutton);
    this.$dropbutton = $dropbutton;
    this.$list = $dropbutton.find('.dropbutton');
    // Find actions and mark them.
    this.$actions = this.$list.find('li').addClass('dropbutton-action');

    // Add the special dropdown only if there are hidden actions.
    if (this.$actions.length > 1) {
      // Identify the first element of the collection.
      var $primary = this.$actions.slice(0, 1);
      // Identify the secondary actions.
      var $secondary = this.$actions.slice(1);
      $secondary.addClass('secondary-action');
      // Add toggle link.
      $primary.after(Drupal.theme('dropbuttonToggle', options));
      // Bind mouse events.
      this.$dropbutton
        .addClass('dropbutton-multiple')
        .on({
          /**
           * Adds a timeout to close the dropdown on mouseleave.
           */
          'mouseleave.dropbutton': $.proxy(this.hoverOut, this),
          /**
           * Clears timeout when mouseout of the dropdown.
           */
          'mouseenter.dropbutton': $.proxy(this.hoverIn, this),
          /**
           * Similar to mouseleave/mouseenter, but for keyboard navigation.
           */
          'focusout.dropbutton': $.proxy(this.focusOut, this),
          'focusin.dropbutton': $.proxy(this.focusIn, this)
        });
    }
    else {
      this.$dropbutton.addClass('dropbutton-single');
    }
  }

  /**
   * Extend the DropButton constructor.
   */
  $.extend(DropButton, {
    /**
     * Store all processed DropButtons.
     *
     * @type {Array}
     */
    dropbuttons: []
  });

  /**
   * Extend the DropButton prototype.
   */
  $.extend(DropButton.prototype, {
    /**
     * Toggle the dropbutton open and closed.
     *
     * @param {Boolean} show
     *   (optional) Force the dropbutton to open by passing true or to close by
     *   passing false.
     */
    toggle: function (show) {
      var isBool = typeof show === 'boolean';
      show = isBool ? show : !this.$dropbutton.hasClass('open');
      this.$dropbutton.toggleClass('open', show);
    },

    hoverIn: function () {
      // Clear any previous timer we were using.
      if (this.timerID) {
        window.clearTimeout(this.timerID);
      }
    },

    hoverOut: function () {
      // Wait half a second before closing.
      this.timerID = window.setTimeout($.proxy(this, 'close'), 500);
    },

    open: function () {
      this.toggle(true);
    },

    close: function () {
      this.toggle(false);
    },

    focusOut: function (e) {
      this.hoverOut.call(this, e);
    },

    focusIn: function (e) {
      this.hoverIn.call(this, e);
    }
  });

  $.extend(Drupal.theme, {
    /**
     * A toggle is an interactive element often bound to a click handler.
     *
     * @param {Object} options
     *   - {String} title: (optional) The HTML anchor title attribute and
     *     text for the inner span element.
     *
     * @return {String}
     *   A string representing a DOM fragment.
     */
    dropbuttonToggle: function (options) {
      return '<li class="dropbutton-toggle"><button type="button"><span class="dropbutton-arrow"><span class="visually-hidden">' + options.title + '</span></span></button></li>';
    }
  });

  // Expose constructor in the public space.
  Drupal.DropButton = DropButton;

})(jQuery, Drupal);
