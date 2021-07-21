/**
 * @file
 * Provides a polyfill for Element.prototype.toggleAttribute().
 *
 * This is needed for Internet Explorer 9+
 *
 * This has been copied from MDN Web Docs code samples. Code samples in the MDN
 * Web Docs are licensed under CC0.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Element/toggleAttribute#polyfill
 * @see https://developer.mozilla.org/en-US/docs/MDN/About#Code_samples_and_snippets
 */

if (!Element.prototype.toggleAttribute) {
  Element.prototype.toggleAttribute = function (name, force) {
    // eslint-disable-next-line no-void
    if (force !== void 0) force = !!force;

    if (this.hasAttribute(name)) {
      if (force) return true;

      this.removeAttribute(name);
      return false;
    }
    if (force === false) return false;

    this.setAttribute(name, '');
    return true;
  };
}
