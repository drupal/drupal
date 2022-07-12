/**
 * @file
 * Provides a polyfill for CustomEvent.
 *
 * This is needed for Internet Explorer 11.
 *
 * This has been copied from MDN Web Docs code samples. Code samples in the MDN
 * Web Docs are licensed under CC0.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent/CustomEvent#Polyfill
 * @see https://developer.mozilla.org/en-US/docs/MDN/About#Code_samples_and_snippets
 */
// eslint-disable-next-line func-names
(function () {
  if (typeof window.CustomEvent === 'function') return false;

  function CustomEvent(event, params) {
    params = params || { bubbles: false, cancelable: false, detail: null };
    const evt = document.createEvent('CustomEvent');
    evt.initCustomEvent(
      event,
      params.bubbles,
      params.cancelable,
      params.detail,
    );
    return evt;
  }

  window.CustomEvent = CustomEvent;
})();
