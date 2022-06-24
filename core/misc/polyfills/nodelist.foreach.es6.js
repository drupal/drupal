/**
 * @file
 * Provides a polyfill for NodeList.forEach().
 *
 * This is needed for Internet Explorer 11 and Opera Mini.
 *
 * This has been copied from MDN Web Docs code samples. Code samples in the MDN
 * Web Docs are licensed under CC0.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/NodeList/forEach#Polyfill
 * @see https://developer.mozilla.org/en-US/docs/MDN/About#Code_samples_and_snippets
 */
if (window.NodeList && !NodeList.prototype.forEach) {
  NodeList.prototype.forEach = Array.prototype.forEach;
}
