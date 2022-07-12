/**
 * @file
 * Provides a polyfill for String.includes().
 *
 * This is needed for Internet Explorer 11 and Opera Mini.
 *
 * This has been copied from MDN Web Docs code samples. Code samples in the MDN
 * Web Docs are licensed under CC0.
 *
 * @see https://web.archive.org/web/20210916035058/https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/includes
 * @see https://developer.mozilla.org/en-US/docs/MDN/About#Code_samples_and_snippets
 */
/* eslint-disable strict, lines-around-directive, no-extend-native */
if (!String.prototype.includes) {
  String.prototype.includes = function (search, start) {
    'use strict';

    if (search instanceof RegExp) {
      throw TypeError('first argument must not be a RegExp');
    }
    if (start === undefined) {
      start = 0;
    }
    return this.indexOf(search, start) !== -1;
  };
}
