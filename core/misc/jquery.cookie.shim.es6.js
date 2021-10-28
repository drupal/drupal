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
  const deprecatedMessageSuffix = `is deprecated in Drupal 9.0.0 and will be removed in Drupal 10.0.0. Use the core/js-cookie library instead. See https://www.drupal.org/node/3104677`;

  /**
   * Determines if an object is a function.
   *
   * @param {Object} obj
   *   The object to check.
   *
   * @return {boolean}
   *   True if the object is a function.
   */
  const isFunction = (obj) =>
    Object.prototype.toString.call(obj) === '[object Function]';

  /**
   * Decodes cookie value for compatibility with jquery.cookie.
   *
   * @param {string} value
   *   The cookie value to parse.
   * @param {boolean} parseJson
   *   Whether cookie value should be parsed from JSON.
   *
   * @return {string}
   *   The cookie value for the reader to return.
   */
  const parseCookieValue = (value, parseJson) => {
    if (value.indexOf('"') === 0) {
      value = value.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
    }

    try {
      value = decodeURIComponent(value.replace(/\+/g, ' '));
      return parseJson ? JSON.parse(value) : value;
    } catch (e) {
      // Exceptions on JSON parsing should be ignored.
    }
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
   * @param {boolean} parseJson
   *   Whether cookie value should be parsed from JSON.
   *
   * @return {string}
   *   The cookie value that js-cookie will return.
   */
  const reader = (
    cookieValue,
    cookieName,
    converter,
    readUnsanitized,
    parseJson,
  ) => {
    const value = readUnsanitized
      ? cookieValue
      : parseCookieValue(cookieValue, parseJson);

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
   *   be a function when using this shim for backwards-compatibility with
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
   * @deprecated in Drupal 9.0.0 and is removed from Drupal 10.0.0.
   *   Use the core/js-cookie library instead.
   *
   * @see https://www.drupal.org/node/3104677
   * @see https://github.com/js-cookie/js-cookie/blob/v3.0.1/README.md
   */
  $.cookie = (key, value = undefined, options = undefined) => {
    Drupal.deprecationError({
      message: `jQuery.cookie() ${deprecatedMessageSuffix}`,
    });

    if (value !== undefined && !isFunction(value)) {
      // The caller is setting a cookie value and not trying to retrieve the
      // cookie value using a converter callback.
      const attributes = { ...$.cookie.defaults, ...options };

      if (typeof attributes.expires === 'string' && attributes.expires !== '') {
        attributes.expires = new Date(attributes.expires);
      }

      const cookieSetter = cookies.withConverter({
        write: (cookieValue) => encodeURIComponent(cookieValue),
      });

      value =
        $.cookie.json && !$.cookie.raw ? JSON.stringify(value) : String(value);

      return cookieSetter.set(key, value, attributes);
    }

    // Use either js-cookie or pass in a converter to get the raw cookie value,
    // which has security implications, but remains in place for
    // backwards-compatibility.
    const userProvidedConverter = value;
    const cookiesShim = cookies.withConverter({
      read: (cookieValue, cookieName) =>
        reader(
          cookieValue,
          cookieName,
          userProvidedConverter,
          $.cookie.raw,
          $.cookie.json,
        ),
    });

    if (key !== undefined) {
      return cookiesShim.get(key);
    }

    const results = cookiesShim.get();
    Object.keys(results).forEach((resultKey) => {
      if (results[resultKey] === undefined) {
        delete results[resultKey];
      }
    });

    return results;
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
  $.cookie.defaults = { path: '', ...cookies.defaults };

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
   * @deprecated in Drupal 9.0.0 and is removed from Drupal 10.0.0.
   *   Use the core/js-cookie library instead.
   *
   * @see https://www.drupal.org/node/3104677
   * @see https://github.com/js-cookie/js-cookie/blob/v3.0.1/README.md
   */
  $.removeCookie = (key, options) => {
    Drupal.deprecationError({
      message: `jQuery.removeCookie() ${deprecatedMessageSuffix}`,
    });
    cookies.remove(key, { ...$.cookie.defaults, ...options });
    return !cookies.get(key);
  };
})(jQuery, Drupal, window.Cookies);
