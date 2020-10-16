/* eslint-disable */
/**
 * From https://developer.mozilla.org/en-US/docs/Web/API/NodeList/forEach#Polyfill
 * Licensed public domain (CC0).
 * @see https://creativecommons.org/publicdomain/zero/1.0/
 * @see https://developer.mozilla.org/en-US/docs/MDN/About
 *
 * @todo Remove when https://www.drupal.org/node/3143465 is fixed.
 */
if (window.NodeList && !NodeList.prototype.forEach) {
  NodeList.prototype.forEach = function (callback, thisArg) {
    thisArg = thisArg || window;
    for (var i = 0; i < this.length; i++) {
      callback.call(thisArg, this[i], i, this);
    }
  };
}

/**
 * From https://developer.mozilla.org/en-US/docs/Web/API/Element/matches#Polyfill
 * Licensed public domain (CC0).
 * @see https://creativecommons.org/publicdomain/zero/1.0/
 * @see https://developer.mozilla.org/en-US/docs/MDN/About
 */
if (!Element.prototype.matches) {
  Element.prototype.matches =
    Element.prototype.msMatchesSelector ||
    Element.prototype.webkitMatchesSelector;
}
