/**
 * @file
 * Provides a polyfill for String.includes().
 *
 * This is needed for Internet Explorer 11 and Opera Mini.
 *
 * This has been copied from MDN Web Docs code samples. Code samples in the MDN
 * Web Docs are licensed under CC0.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/includes#Polyfill
 * @see https://developer.mozilla.org/en-US/docs/MDN/About#Code_samples_and_snippets
 */
if (!String.prototype.includes) {
  String.prototype.includes = function (search, start) {
    'use strict';
    if (search instanceof RegExp) {
      throw TypeError('first argument must not be a RegExp');
    }

    if (typeof start !== 'number') {
      start = 0;
    }

    if (start + search.length > this.length) {
      return false;
    }

    return this.indexOf(search, start) !== -1;
  };
}
