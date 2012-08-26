(function ($, Drupal) {

"use strict";

/**
 * The collapsible fieldset object represents a single collapsible fieldset.
 */
function CollapsibleFieldset(node, settings) {
  this.$node = $(node);
  this.$node.data('fieldset', this);
  this.settings = $.extend({
      duration:'fast',
      easing:'linear'
    },
    settings
  );
  // Expand fieldset if there are errors inside, or if it contains an
  // element that is targeted by the uri fragment identifier.
  var anchor = location.hash && location.hash !== '#' ? ', ' + location.hash : '';
  if (this.$node.find('.error' + anchor).length) {
    this.$node.removeClass('collapsed');
  }
  // Initialize and setup the summary,
  this.setupSummary();
  // Initialize and setup the legend.
  this.setupLegend();
}

/**
 * Extend CollapsibleFieldset function.
 */
$.extend(CollapsibleFieldset, {
  /**
   * Holds references to instantiated CollapsibleFieldset objects.
   */
  fieldsets: []
});

/**
 * Extend CollapsibleFieldset prototype.
 */
$.extend(CollapsibleFieldset.prototype, {
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
    // Turn the legend into a clickable link, but retain span.fieldset-legend
    // for CSS positioning.
    var $legend = this.$node.find('> legend .fieldset-legend');

    $('<span class="fieldset-legend-prefix element-invisible"></span>')
      .append(this.$node.hasClass('collapsed') ? Drupal.t('Show') : Drupal.t('Hide'))
      .prependTo($legend)
      .after(' ');

    // .wrapInner() does not retain bound events.
    var $link = $('<a class="fieldset-title" href="#"></a>')
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
   * Toggle the visibility of a fieldset using smooth animations.
   */
  toggle: function () {
    // Don't animate multiple times.
    if (this.animating) {
      return;
    }
    if (this.$node.is('.collapsed')) {
      var $content = this.$node.find('> .fieldset-wrapper').hide();
      this.$node
        .removeClass('collapsed')
        .trigger({ type:'collapsed', value:false })
        .find('> legend span.fieldset-legend-prefix').html(Drupal.t('Hide'));
      $content.slideDown(
        $.extend(this.settings, {
          complete:$.proxy(this.onCompleteSlideDown, this)
        })
      );
    }
    else {
      this.$node.trigger({ type:'collapsed', value:true });
      this.$node.find('> .fieldset-wrapper').slideUp(
        $.extend(this.settings, {
          complete:$.proxy(this.onCompleteSlideUp, this)
        })
      );
    }
  },
  /**
   * Completed opening fieldset.
   */
  onCompleteSlideDown: function () {
    this.$node.trigger('completeSlideDown');
    this.animating = false;
  },
  /**
   * Completed closing fieldset.
   */
  onCompleteSlideUp: function () {
    this.$node
      .addClass('collapsed')
      .find('> legend span.fieldset-legend-prefix').html(Drupal.t('Show'));
    this.$node.trigger('completeSlideUp');
    this.animating = false;
  }
});

Drupal.behaviors.collapse = {
  attach: function (context, settings) {
    var $collapsibleFieldsets = $(context).find('fieldset.collapsible').once('collapse');
    if ($collapsibleFieldsets.length) {
      for (var i = 0; i < $collapsibleFieldsets.length; i++) {
        CollapsibleFieldset.fieldsets.push(new CollapsibleFieldset($collapsibleFieldsets[i], settings.collapsibleFieldset));
      }
    }
  }
};

// Expose constructor in the public space.
Drupal.CollapsibleFieldset = CollapsibleFieldset;

})(jQuery, Drupal);
