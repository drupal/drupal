/**
 * @file
 * Defines a backwards-compatible shim for jQuery .show, .hide, .toggle.
 */

(($) => {
  const originalHide = $.fn.hide;
  $.fn.hide = function (...args) {
    if (args.length) {
      Drupal.deprecationError({
        message: `hide() no longer accepts arguments`,
      });
      return originalHide.apply(this, args);
    }

    this.each((index, item) => {
      if (item instanceof Element) {
        item.setAttribute('hidden', '');
      }
    });
    return this;
  };

  const originalShow = $.fn.show;
  $.fn.show = function (...args) {
    if (args.length) {
      Drupal.deprecationError({
        message: `show() no longer accepts arguments`,
      });
      return originalShow.apply(this, args);
    }

    this.each((index, item) => {
      if (item instanceof Element) {
        item.removeAttribute('hidden');
      }
    });
    return this;
  };

  const originalToggle = $.fn.toggle;
  $.fn.toggle = function (...args) {
    if (
      args.length === 0 ||
      (args.length === 1 && typeof args[0] === 'boolean')
    ) {
      this.each((index, item) => {
        if (item instanceof Element) {
          if (args.length === 0) {
            item.hasAttribute('hidden')
              ? item.removeAttribute('hidden')
              : item.setAttribute('hidden', '');
          } else {
            $args[0]
              ? item.removeAttribute('hidden')
              : item.setAttribute('hidden', '');
          }
        }
      });
      return this;
    }

    Drupal.deprecationError({
      message: `toggle() only accepts a single boolean argument`,
    });
    return originalToggle.apply(this, args);
  };

  const originalFadeIn = $.fn.fadeIn;
  $.fn.fadeIn = function (...args) {
    this.css('display', 'none');
    this.removeAttr('hidden');

    return originalFadeIn.apply(this, args);
  };
})(jQuery);
