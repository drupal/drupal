(function ($, Drupal) {

"use strict";

/**
 * Details feature detection.
 *
 * @todo This is a stop-gap fix only. collapse.js needs to be replaced with a
 *   proper HTML5 details polyfill.
 *
 * @author Mathias Bynens
 * @see http://mathiasbynens.be/notes/html5-details-jquery
 */
var isDetailsSupported = (function (doc) {
  var el = doc.createElement('details'),
      fake,
      root,
      diff;
  if (!('open' in el)) {
    return false;
  }
  root = doc.body || (function () {
    var de = doc.documentElement;
    fake = true;
    return de.insertBefore(doc.createElement('body'), de.firstElementChild || de.firstChild);
  }());
  el.innerHTML = '<summary>a</summary>b';
  el.style.display = 'block';
  root.appendChild(el);
  diff = el.offsetHeight;
  el.open = true;
  diff = diff != el.offsetHeight;
  root.removeChild(el);
  if (fake) {
    root.parentNode.removeChild(root);
  }
  return diff;
}(document));

/**
 * The collapsible details object represents a single collapsible details element.
 */
function CollapsibleDetails(node, settings) {
  this.$node = $(node);
  this.$node.data('details', this);
  this.settings = $.extend({
      duration:'fast',
      easing:'linear'
    },
    settings
  );
  // Expand details if there are errors inside, or if it contains an
  // element that is targeted by the URI fragment identifier.
  var anchor = location.hash && location.hash !== '#' ? ', ' + location.hash : '';
  if (this.$node.find('.error' + anchor).length) {
    this.$node.attr('open', true);
  }
  // Initialize and setup the summary,
  this.setupSummary();
  // Initialize and setup the legend.
  this.setupLegend();
}

/**
 * Extend CollapsibleDetails function.
 */
$.extend(CollapsibleDetails, {
  /**
   * Holds references to instantiated CollapsibleDetails objects.
   */
  instances: []
});

/**
 * Extend CollapsibleDetails prototype.
 */
$.extend(CollapsibleDetails.prototype, {
  /**
   * Flag preventing multiple simultaneous animations.
   */
  animating: false,
  /**
   * Initialize and setup summary events and markup.
   */
  setupSummary: function () {
    this.$summary = $('<span class="summary"></span>');
    this.$node
      .bind('summaryUpdated', $.proxy(this.onSummaryUpdated, this))
      .trigger('summaryUpdated');
  },
  /**
   * Initialize and setup legend markup.
   */
  setupLegend: function () {
    // Turn the summary into a clickable link.
    var $legend = this.$node.find('> summary');

    $('<span class="details-summary-prefix element-invisible"></span>')
      .append(this.$node.attr('open') ? Drupal.t('Hide') : Drupal.t('Show'))
      .prependTo($legend)
      .after(' ');

    // .wrapInner() does not retain bound events.
    var $link = $('<a class="details-title" href="#"></a>')
      .prepend($legend.contents())
      .appendTo($legend)
      .click($.proxy(this.onLegendClick, this));
    $legend.append(this.$summary);
  },
  /**
   * Handle legend clicks
   */
  onLegendClick: function (e) {
    e.preventDefault();
    this.toggle();
  },
  /**
   * Update summary
   */
  onSummaryUpdated: function () {
    var text = $.trim(this.$node.drupalGetSummary());
    this.$summary.html(text ? ' (' + text + ')' : '');
  },
  /**
   * Toggle the visibility of a details element using smooth animations.
   */
  toggle: function () {
    // Don't animate multiple times.
    if (this.animating) {
      return;
    }
    if (!this.$node.attr('open')) {
      var $content = this.$node.find('> .details-wrapper').hide();
      this.$node
        .trigger({ type:'collapsed', value:false })
        .find('> summary span.details-summary-prefix').html(Drupal.t('Hide'));
      $content.slideDown(
        $.extend(this.settings, {
          complete:$.proxy(this.onCompleteSlideDown, this)
        })
      );
    }
    else {
      this.$node.trigger({ type:'collapsed', value:true });
      this.$node.find('> .details-wrapper').slideUp(
        $.extend(this.settings, {
          complete:$.proxy(this.onCompleteSlideUp, this)
        })
      );
    }
  },
  /**
   * Completed opening details element.
   */
  onCompleteSlideDown: function () {
    this.$node.attr('open', true);
    this.$node.trigger('completeSlideDown');
    this.animating = false;
  },
  /**
   * Completed closing details element.
   */
  onCompleteSlideUp: function () {
    this.$node.attr('open', false);
    this.$node
      .find('> summary span.details-summary-prefix').html(Drupal.t('Show'));
    this.$node.trigger('completeSlideUp');
    this.animating = false;
  }
});

Drupal.behaviors.collapse = {
  attach: function (context, settings) {
    if (isDetailsSupported) {
      return;
    }
    var $collapsibleDetails = $(context).find('details.collapsible').once('collapse');
    if ($collapsibleDetails.length) {
      for (var i = 0; i < $collapsibleDetails.length; i++) {
        CollapsibleDetails.instances.push(new CollapsibleDetails($collapsibleDetails[i], settings.collapsibleDetails));
      }
    }
  }
};

// Expose constructor in the public space.
Drupal.CollapsibleDetails = CollapsibleDetails;

})(jQuery, Drupal);
