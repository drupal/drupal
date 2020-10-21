/**
 * @file
 * Provides additional Modernizr tests.
 */
((Modernizr) => {
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
  // @todo find alternative to Modernizr's deprecated touchevent test in
  //   http://drupal.org/node/3101922
  // @see https://github.com/Modernizr/Modernizr/blob/v3.3.1/feature-detects/touchevents.js
  Modernizr.addTest('touchevents', () => {
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
