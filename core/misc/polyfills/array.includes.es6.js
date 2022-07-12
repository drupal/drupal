/**
 * @file
 * Provides a polyfill for Array.includes().
 *
 * This is needed for Internet Explorer 11 and Opera Mini.
 *
 * This has based on MDN Web Docs code samples. Code samples in the MDN Web Docs
 * are licensed under CC0.
 *
 * @see https://web.archive.org/web/20161012020930/https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/includes
 * @see https://developer.mozilla.org/en-US/docs/MDN/About#Code_samples_and_snippets
 */
if (!Array.prototype.includes) {
  // eslint-disable-next-line no-extend-native
  Array.prototype.includes = function (searchElement) {
    if (this == null) {
      throw new TypeError(
        'Array.prototype.includes called on null or undefined',
      );
    }

    const O = Object(this);
    const len = parseInt(O.length, 10) || 0;
    if (len === 0) {
      return false;
    }
    // eslint-disable-next-line prefer-rest-params
    const n = parseInt(arguments[1], 10) || 0;
    let k;
    if (n >= 0) {
      k = n;
    } else {
      k = len + n;
      if (k < 0) {
        k = 0;
      }
    }
    let currentElement;
    while (k < len) {
      currentElement = O[k];
      if (
        searchElement === currentElement ||
        // eslint-disable-next-line no-self-compare
        (searchElement !== searchElement && currentElement !== currentElement)
      ) {
        // NaN !== NaN
        return true;
      }
      k += 1;
    }
    return false;
  };
}
