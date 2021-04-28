/**
 * @file
 * Provides a polyfill for Element.prototype.closest().
 *
 * This is needed for Internet Explorer 11 and Opera Mini.
 *
 * This has been copied from MDN Web Docs code samples. Code samples in the MDN
 * Web Docs are licensed under CC0.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Element/closest#polyfill
 * @see https://developer.mozilla.org/en-US/docs/MDN/About#Code_samples_and_snippets
 */
if (!Element.prototype.closest) {
  Element.prototype.closest = function (s) {
    var el = this;

    do {
      if (Element.prototype.matches.call(el, s)) return el;
      el = el.parentElement || el.parentNode;
    } while (el !== null && el.nodeType === 1);
    return null;
  };
}
