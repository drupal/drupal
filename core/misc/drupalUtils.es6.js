/**
 * @file
 * Defines JavaScript utility functions.
 */

window.drupalUtils = {
  hide(element) {
    element.setAttribute('hidden', '');
  },

  show(element) {
    element.removeAttribute('hidden', '');
  },

  toggle(element, display) {
    if (typeof display === 'boolean') {
      this[display ? 'show' : 'hide'](element);
    } else {
      this[element.hasAttribute('hidden') ? 'show' : 'hide'](element);
    }
  },
};
