/**
 * @file
 * Polyfill for HTML5 details elements.
 */

(function ($, Modernizr, Drupal) {
  /**
   * The collapsible details object represents a single details element.
   *
   * @constructor Drupal.CollapsibleDetails
   *
   * @param {HTMLElement} node
   *   The details element.
   */
  function CollapsibleDetails(node) {
    this.$node = $(node);
    this.$node.data('details', this);
    // Expand details if there are errors inside, or if it contains an
    // element that is targeted by the URI fragment identifier.
    const anchor =
      window.location.hash && window.location.hash !== '#'
        ? `, ${window.location.hash}`
        : '';
    if (this.$node.find(`.error${anchor}`).length) {
      this.$node.attr('open', true);
    }
    // Initialize and set up the summary polyfill.
    this.setupSummaryPolyfill();
  }

  $.extend(
    CollapsibleDetails,
    /** @lends Drupal.CollapsibleDetails */ {
      /**
       * Holds references to instantiated CollapsibleDetails objects.
       *
       * @type {Array.<Drupal.CollapsibleDetails>}
       */
      instances: [],
    },
  );

  $.extend(
    CollapsibleDetails.prototype,
    /** @lends Drupal.CollapsibleDetails# */ {
      /**
       * Initialize and setup summary markup.
       */
      setupSummaryPolyfill() {
        // Turn the summary into a clickable link.
        const $summary = this.$node.find('> summary');

        $('<span class="details-summary-prefix visually-hidden"></span>')
          .append(this.$node.attr('open') ? Drupal.t('Hide') : Drupal.t('Show'))
          .prependTo($summary)
          .after(document.createTextNode(' '));

        // .wrapInner() does not retain bound events.
        $('<a class="details-title"></a>')
          .attr('href', `#${this.$node.attr('id')}`)
          .prepend($summary.contents())
          .appendTo($summary);

        $summary
          .append(this.$summary)
          .on('click', $.proxy(this.onSummaryClick, this));
      },

      /**
       * Handle summary clicks.
       *
       * @param {jQuery.Event} e
       *   The event triggered.
       */
      onSummaryClick(e) {
        this.toggle();
        e.preventDefault();
      },

      /**
       * Toggle the visibility of a details element using smooth animations.
       */
      toggle() {
        const isOpen = !!this.$node.attr('open');
        const $summaryPrefix = this.$node.find(
          '> summary span.details-summary-prefix',
        );
        if (isOpen) {
          $summaryPrefix.html(Drupal.t('Show'));
        } else {
          $summaryPrefix.html(Drupal.t('Hide'));
        }
        // Delay setting the attribute to emulate chrome behavior and make
        // details-aria.js work as expected with this polyfill.
        setTimeout(() => {
          this.$node.attr('open', !isOpen);
        }, 0);
      },
    },
  );

  /**
   * Polyfill HTML5 details element.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior for the details element.
   */
  Drupal.behaviors.collapse = {
    attach(context) {
      if (Modernizr.details) {
        return;
      }
      const $collapsibleDetails = $(context)
        .find('details')
        .once('collapse')
        .addClass('collapse-processed');
      if ($collapsibleDetails.length) {
        for (let i = 0; i < $collapsibleDetails.length; i++) {
          CollapsibleDetails.instances.push(
            new CollapsibleDetails($collapsibleDetails[i]),
          );
        }
      }
    },
  };

  /**
   * Open parent details elements of a targeted page fragment.
   *
   * Opens all (nested) details element on a hash change or fragment link click
   * when the target is a child element, in order to make sure the targeted
   * element is visible. Aria attributes on the summary
   * are set by triggering the click event listener in details-aria.js.
   *
   * @param {jQuery.Event} e
   *   The event triggered.
   * @param {jQuery} $target
   *   The targeted node as a jQuery object.
   */
  const handleFragmentLinkClickOrHashChange = (e, $target) => {
    $target.parents('details').not('[open]').find('> summary').trigger('click');
  };

  /**
   * Binds a listener to handle fragment link clicks and URL hash changes.
   */
  $('body').on(
    'formFragmentLinkClickOrHashChange.details',
    handleFragmentLinkClickOrHashChange,
  );

  // Expose constructor in the public space.
  Drupal.CollapsibleDetails = CollapsibleDetails;
})(jQuery, Modernizr, Drupal);
