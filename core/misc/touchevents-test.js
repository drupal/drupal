/**
 * @file
 * A replacement for Modernizr touch events detection.
 */

document.documentElement.classList.add(
  'ontouchstart' in window ||
    (window.DocumentTouch && document instanceof window.DocumentTouch)
    ? 'touchevents'
    : 'no-touchevents',
);
