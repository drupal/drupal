/**
 * @file
 * Attaches behaviors for the Contextual module.
 */

(function ($, Drupal) {

"use strict";

/**
 * Attaches outline behavior for regions associated with contextual links.
 */
Drupal.behaviors.contextual = {
  attach: function (context) {
    $('ul.contextual-links', context).once('contextual', function () {
      var $this = $(this);
      $this.data('drupal-contextual', new Drupal.contextual($this, $this.closest('.contextual-region')));
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

  // Bind behaviors through delegation.
  var highlightRegion = $.proxy(this.highlightRegion, this);
  this.$region
    .on('click.contextual', '.contextual .trigger', $.proxy(this.triggerClickHandler, this))
    .on('mouseenter.contextual', {highlight: true}, highlightRegion)
    .on('mouseleave.contextual', {highlight: false}, highlightRegion)
    .on('mouseleave.contextual', '.contextual', {show: false}, $.proxy(this.triggerLeaveHandler, this))
    .on('focus.contextual', '.contextual-links a, .contextual .trigger', {highlight: true}, highlightRegion)
    .on('blur.contextual', '.contextual-links a, .contextual .trigger', {highlight: false}, highlightRegion);
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
