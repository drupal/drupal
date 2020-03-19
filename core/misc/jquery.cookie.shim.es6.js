/**
 * @file
 * Defines a backwards-compatible shim for jquery.cookie.
 */

/**
 * The core/js-cookie library object.
 *
 * @global
 *
 * @var {object} Cookies
 */

(($, Drupal, cookies) => {
  /**
   * Determines if an object is a function.
   *
   * @param {Object} obj
   *   The object to check.
   *
   * @return {boolean}
   *   True if the object is a function.
   */
  const isFunction = obj =>
    Object.prototype.toString.call(obj) === '[object Function]';

  /**
   * Decodes cookie value for compatibility with jquery.cookie.
   *
   * @param {string} value
   *   The cookie value to parse.
   *
   * @return {string}
   *   The cookie value for the reader to return.
   */
  const parseCookieValue = value => {
    if (value.indexOf('"') === 0) {
      value = value
        .slice(1, -1)
        .replace(/\\"/g, '"')
        .replace(/\\\\/g, '\\');
    }
    return decodeURIComponent(value.replace(/\+/g, ' '));
  };

  /**
   * Wraps the cookie value to support unsanitized values.
   *
   * Decoding strings is the job of the converter when using js-cookie, and
   * the shim uses the same decode function as that library when the deprecated
   * raw option is not used.
   *
   * @param {string} cookieValue
   *   The cookie value.
   * @param {string} cookieName
   *   The cookie name.
   * @param {reader~converterCallback} converter
   *   A function that takes the cookie value for further processing.
   * @param {boolean} readUnsanitized
   *   Uses the unsanitized value when set to true.
   *
   * @return {string}
   *   The cookie value that js-cookie will return.
   */
  const reader = (cookieValue, cookieName, converter, readUnsanitized) => {
    const value = readUnsanitized ? cookieValue : parseCookieValue(cookieValue);

    if (converter !== undefined && isFunction(converter)) {
      return converter(value, cookieName);
    }
    return value;
  };

  /**
   * Gets or sets a browser cookie.
   *
   * @example
   * // Returns 'myCookie=myCookieValue'.
   * $.cookie('myCookie', 'myCookieValue');
   * @example
   * // Returns 'myCookieValue'.
   * $.cookie('myCookie');
   *
   * @example
   * // Returns the literal URI-encoded value of {"key": "value"} as the cookie
   * // value along with the path as in the above example.
   * $.cookie('myCookie', { key: 'value' });
   * @example
   * $.cookie.json = true;
   * // Returns { key: 'value' }.
   * $.cookie('myCookie');
   *
   * @param {string} key
   *   The name of the cookie.
   * @param {string|Object|Function|undefined} value
   *   A js-cookie converter callback when used as a getter. This callback must
   *   be a function when using this shim for backwards-compatiblity with
   *   jquery.cookie. When used as a setter, value is the string or JSON object
   *   to be used as the cookie value.
   * @param {Object|undefined} options
   *   Overrides the default options when used as a setter. See the js-cookie
   *   library README.md file for details.
   *
   * @return {string}
   *   Returns the cookie name, value, and other properties based on the
   *   return value of the document.cookie setter.
   *
   * @see https://www.drupal.org/node/3104677
   * @see https://github.com/js-cookie/js-cookie/blob/v2.2.1/README.md
   */
  $.cookie = (key, value = undefined, options = undefined) => {
    if (value !== undefined && !isFunction(value)) {
      // The caller is setting a cookie value and not trying to retrieve the
      // cookie value using a converter callback.
      const attributes = Object.assign({}, $.cookie.defaults, options);

      if (!$.cookie.json) {
        // An object that is passed in must be typecast to a string when the
        // "json" option is not set because js-cookie will always stringify
        // JSON cookie values.
        value = String(value);
      }

      // If the expires value is a non-empty string, it needs to be converted
      // to a Date() object before being sent to js-cookie.
      if (typeof attributes.expires === 'string' && attributes.expires !== '') {
        attributes.expires = new Date(attributes.expires);
      }

      const cookieSetter = cookies.withConverter({
        write: cookieValue => encodeURIComponent(cookieValue),
      });

      return cookieSetter.set(key, value, attributes);
    }

    // Use either js-cookie or pass in a converter to get the raw cookie value,
    // which has security implications, but remains in place for
    // backwards-compatibility.
    const userProvidedConverter = value;
    const cookiesShim = cookies.withConverter((cookieValue, cookieName) =>
      reader(cookieValue, cookieName, userProvidedConverter, $.cookie.raw),
    );

    return $.cookie.json === true
      ? cookiesShim.getJSON(key)
      : cookiesShim.get(key);
  };

  /**
   * @prop {Object} defaults
   *   The default options when setting a cookie.
   * @prop {string} defaults.path
   *   The default path for the cookie is ''.
   * @prop {undefined} defaults.expires
   *   There is no default value for the expires option. The default expiration
   *   is set to an empty string.
   */
  $.cookie.defaults = Object.assign({ path: '' }, cookies.defaults);

  /**
   * @prop {boolean} json
   *   True if the cookie value should be parsed as JSON.
   */
  $.cookie.json = false;

  /**
   * @prop {boolean} json
   *   True if the cookie value should be returned as-is without decoding
   *   URI entities. In jquery.cookie, this also would not encode the cookie
   *   name, but js-cookie does not allow this.
   */
  $.cookie.raw = false;

  /**
   * Removes a browser cookie.
   *
   * @param {string} key
   *   The name of the cookie.
   * @param {Object} options
   *   Optional options. See the js-cookie library README.md for more details.
   *
   * @return {boolean}
   *   Returns true when the cookie is successfully removed.
   *
   * @see https://www.drupal.org/node/3104677
   * @see https://github.com/js-cookie/js-cookie/blob/v2.2.1/README.md
   */
  $.removeCookie = (key, options) => {
    cookies.remove(key, Object.assign({}, $.cookie.defaults, options));
    return !cookies.get(key);
  };
})(jQuery, Drupal, window.Cookies);
