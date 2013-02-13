/**
 * @file
 * Attaches behaviors for the Contextual module.
 */

(function ($, Drupal) {

"use strict";

var contextuals = [];

/**
 * Attaches outline behavior for regions associated with contextual links.
 */
Drupal.behaviors.contextual = {
  attach: function (context) {
    $('ul.contextual-links', context).once('contextual', function () {
      var $this = $(this);
      var contextual = new Drupal.contextual($this, $this.closest('.contextual-region'));
      contextuals.push(contextual);
      $this.data('drupal-contextual', contextual);
    });

    // Bind to edit mode changes.
    $('body').once('contextual', function () {
      $(document).on('drupalEditModeChanged.contextual', toggleEditMode);
    });
  }
};

/**
 * Contextual links object.
 */
Drupal.contextual = function($links, $region) {
  this.$links = $links;
  this.$region = $region;

  this.init();
};

/**
 * Initiates a contextual links object.
 */
Drupal.contextual.prototype.init = function() {
  // Wrap the links to provide positioning and behavior attachment context.
  this.$wrapper = $(Drupal.theme.contextualWrapper())
    .insertBefore(this.$links)
    .append(this.$links);

  // Mark the links as hidden. Use aria-role form so that the number of items
  // in the list is spoken.
  this.$links
    .attr({
      'hidden': 'hidden',
      'role': 'form'
    });

  // Create and append the contextual links trigger.
  var action = Drupal.t('Open');
  this.$trigger = $(Drupal.theme.contextualTrigger())
    .text(Drupal.t('@action configuration options', {'@action': action}))
    // Set the aria-pressed state.
    .attr('aria-pressed', false)
    .prependTo(this.$wrapper);

  // The trigger behaviors are never detached or mutated.
  this.$region
    .on('click.contextual', '.contextual .trigger', $.proxy(this.triggerClickHandler, this))
    .on('mouseleave.contextual', '.contextual', {show: false}, $.proxy(this.triggerLeaveHandler, this));
  // Attach highlight behaviors.
  this.attachHighlightBehaviors();
};

/**
 * Attaches highlight-on-mouseenter behaviors.
 */
Drupal.contextual.prototype.attachHighlightBehaviors = function () {
  // Bind behaviors through delegation.
  var highlightRegion = $.proxy(this.highlightRegion, this);
  this.$region
    .on('mouseenter.contextual.highlight', {highlight: true}, highlightRegion)
    .on('mouseleave.contextual.highlight', {highlight: false}, highlightRegion)
    .on('click.contextual.highlight', '.contextual-links a', {highlight: false}, highlightRegion)
    .on('focus.contextual.highlight', '.contextual-links a, .contextual .trigger', {highlight: true}, highlightRegion)
    .on('blur.contextual.highlight', '.contextual-links a, .contextual .trigger', {highlight: false}, highlightRegion);
};

/**
 * Detaches unhighlight-on-mouseleave behaviors.
 */
Drupal.contextual.prototype.detachHighlightBehaviors = function () {
  this.$region.off('.contextual.highlight');
};

/**
 * Toggles the highlighting of a contextual region.
 *
 * @param {Object} event
 *   jQuery Event object.
 */
Drupal.contextual.prototype.highlightRegion = function(event) {
  // Set up a timeout to delay the dismissal of the region highlight state.
  if (!event.data.highlight && this.timer === undefined) {
    return this.timer = window.setTimeout($.proxy($.fn.trigger, $(event.target), 'mouseleave.contextual'), 100);
  }
  // Clear the timeout to prevent an infinite loop of mouseleave being
  // triggered.
  if (this.timer) {
    window.clearTimeout(this.timer);
    delete this.timer;
  }
  // Toggle active state of the contextual region based on the highlight value.
  this.$region.toggleClass('contextual-region-active', event.data.highlight);
  // Hide the links if the contextual region is inactive.
  var state = this.$region.hasClass('contextual-region-active');
  if (!state) {
    this.showLinks(state);
  }
};

/**
 * Handles click on the contextual links trigger.
 *
 * @param {Object} event
 *   jQuery Event object.
 */
Drupal.contextual.prototype.triggerClickHandler = function (event) {
  event.preventDefault();
  this.showLinks();
};

/**
 * Handles mouseleave on the contextual links trigger.
 *
 * @param {Object} event
 *   jQuery Event object.
 */
Drupal.contextual.prototype.triggerLeaveHandler = function (event) {
  var show = event && event.data && event.data.show;
  this.showLinks(show);
};

/**
 * Toggles the active state of the contextual links.
 *
 * @param {Boolean} show
 *   (optional) True if the links should be shown. False is the links should be
 *   hidden.
 */
Drupal.contextual.prototype.showLinks = function(show) {
  this.$wrapper.toggleClass('contextual-links-active', show);
  var isOpen = this.$wrapper.hasClass('contextual-links-active');
  var action = (isOpen) ? Drupal.t('Close') : Drupal.t('Open');
  this.$trigger
    .text(Drupal.t('@action configuration options', {'@action': action}))
    // Set the aria-pressed state.
    .attr('aria-pressed', isOpen);
  // Mark the links as hidden if they are.
  if (isOpen) {
    this.$links.removeAttr('hidden');
  }
  else {
    this.$links.attr('hidden', 'hidden');
  }

};

/**
 * Shows or hides all pencil icons and corresponding contextual regions.
 */
function toggleEditMode (event, data) {
  for (var i = contextuals.length - 1; i >= 0; i--) {
    contextuals[i][(data.status) ? 'detachHighlightBehaviors' : 'attachHighlightBehaviors']();
    contextuals[i].$region.toggleClass('contextual-region-active', data.status);
  }
}

/**
 * Wraps contextual links.
 *
 * @return {String}
 *   A string representing a DOM fragment.
 */
Drupal.theme.contextualWrapper = function () {
  return '<div class="contextual" />';
};

/**
 * A trigger is an interactive element often bound to a click handler.
 *
 * @return {String}
 *   A string representing a DOM fragment.
 */
Drupal.theme.contextualTrigger = function () {
  return '<button class="trigger element-invisible element-focusable" type="button"></button>';
};

})(jQuery, Drupal);
