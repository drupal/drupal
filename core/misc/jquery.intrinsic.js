/**
 * @file
 * Measure an element’s intrinsic width or height when neither constrained by
 * a container nor forced full width as in 'display: block'.
 */
(function ($) {
  'use strict';

  // Style block applied momentarily in order to measure the element.
  //
  // 1. Shrink-wrap the element. Block display would give us the width of the
  //    container, not the element’s intrinsic width.
  // 2. Preventative measure. The styles should be reverted before the browser’s
  //    UI thread updates.
  //
  // We avoid 'position: absolute' because this causes the element to wrap if
  // it’s wider than the viewport, regardless of the width of <body> and <html>.
  //
  var tempElementCSS = {
    display: 'table', /* 1 */
    visibility: 'hidden', /* 2 */
    width: 'auto',
    height: 'auto',
    maxWidth: 'none',
    maxHeight: 'none'
  };

  // Style block applied momentarily to the body in order to ensure the
  // element’s layout area isn’t constrained.
  var tempBodyCSS = {
    width: '999em',
    height: '999em'
  };

  $.fn.intrinsic = function (dimension) {

    // The measured element may be a plain object or jQuery.
    var element = this instanceof jQuery ? this[0] : this;
    var measurement;

    // Use jQuery’s internal swap() method to temporarily apply the styles, then
    // measure the element’s width() or height().
    $.swap(document.body, tempBodyCSS, function () {
      $.swap(element, tempElementCSS, function () {
        measurement = $(element)[dimension]();
      });
    });

    return measurement;
  };
})(jQuery);
