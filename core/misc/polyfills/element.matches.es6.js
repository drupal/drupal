/**
 * @file
 * Provides a polyfill for Element.prototype.matches().
 *
 * This is needed for Internet Explorer 9+
 *
 * This has been copied from MDN Web Docs code samples. Code samples in the MDN
 * Web Docs are licensed under CC0.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Element/matches#Polyfill
 * @see https://developer.mozilla.org/en-US/docs/MDN/About#Code_samples_and_snippets
 */
if (!Element.prototype.matches) {
  Element.prototype.matches =
    Element.prototype.msMatchesSelector ||
    Element.prototype.webkitMatchesSelector;
}
