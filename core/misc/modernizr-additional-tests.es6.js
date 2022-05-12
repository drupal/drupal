/**
 * @file
 * Provides additional Modernizr tests.
 */
((Modernizr) => {
  /**
   * Triggers deprecation error.
   *
   * Deprecation errors are only triggered if deprecation errors haven't
   * been suppressed.
   *
   * For performance concerns this method is inlined here to avoid adding a
   * dependency to core/drupal that would force drupal.js to be loaded in the
   * header like this script is.
   *
   * @param {Object} deprecation
   *   The deprecation options.
   * @param {string} deprecation.message
   *   The deprecation message.
   *
   * @see https://www.drupal.org/core/deprecation#javascript
   */
  const _deprecationErrorModernizrCopy = ({ message }) => {
    if (typeof console !== 'undefined' && console.warn) {
      console.warn(`[Deprecation] ${message}`);
    }
  };

  /**
   * Triggers deprecation error when object property is being used.
   *
   * @param {Object} deprecation
   *   The deprecation options.
   * @param {Object} deprecation.target
   *   The targeted object.
   * @param {string} deprecation.deprecatedProperty
   *   A key of the deprecated property.
   * @param {string} deprecation.message
   *   The deprecation message.
   *
   * @return {Object}
   *
   * @see https://www.drupal.org/core/deprecation#javascript
   */
  const _deprecatedPropertyModernizrCopy = ({
    target,
    deprecatedProperty,
    message,
  }) => {
    // Proxy and Reflect are not supported by all browsers. Unsupported browsers
    // are ignored since this is a development feature.
    if (!Proxy || !Reflect) {
      return target;
    }

    return new Proxy(target, {
      // eslint-disable-next-line no-shadow
      get: (target, key, ...rest) => {
        if (key === deprecatedProperty) {
          _deprecationErrorModernizrCopy({ message });
        }
        return Reflect.get(target, key, ...rest);
      },
    });
  };

  window.Modernizr = _deprecatedPropertyModernizrCopy({
    target: Modernizr,
    deprecatedProperty: 'touchevents',
    message:
      'The touchevents property of Modernizr has been deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. There will be no replacement for this feature. See https://www.drupal.org/node/3277381.',
  });

  if (
    document.documentElement.classList.contains('touchevents') ||
    document.documentElement.classList.contains('no-touchevents')
  ) {
    return;
  }

  // This is a copy of Modernizr's touchevents test from version 3.3.1. Drupal
  // core has updated Modernizr to a version newer than 3.3.1, but this newer
  // version does not include the touchevents test in its build. Modernizr's
  // touchevents test is deprecated, and newer versions of this test do not work
  // properly with Drupal as it significantly changes the criteria used for
  // determining if touchevents are supported.
  // The most recent known-to-work version, 3.3.1 is provided here. The only
  // changes are refactoring the code to meet Drupal's JavaScript coding
  // standards and calling prefixes and testStyles() via the Modernizr object
  // as they are not in scope when adding a test via Modernizr.addTest();
  // @see https://github.com/Modernizr/Modernizr/blob/v3.3.1/feature-detects/touchevents.js
  Modernizr.addTest('touchevents', () => {
    _deprecationErrorModernizrCopy({
      message:
        'The Modernizr touch events test is deprecated in Drupal 9.4.0 and will be removed in Drupal 10.0.0. See https://www.drupal.org/node/3277381 for information on its replacement and how it should be used.',
    });
    let bool;

    if (
      'ontouchstart' in window ||
      (window.DocumentTouch && document instanceof window.DocumentTouch)
    ) {
      bool = true;
    } else {
      // include the 'heartz' as a way to have a non matching MQ to help
      // terminate the join https://git.io/vznFH
      const query = [
        '@media (',
        Modernizr._prefixes.join('touch-enabled),('),
        'heartz',
        ')',
        '{#modernizr{top:9px;position:absolute}}',
      ].join('');
      Modernizr.testStyles(query, (node) => {
        bool = node.offsetTop === 9;
      });
    }
    return bool;
  });
})(Modernizr);
