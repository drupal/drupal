// $Id: overlay-child.js,v 1.8 2010/04/24 07:14:29 dries Exp $

(function ($) {

/**
 * Overlay object for child windows.
 */
Drupal.overlayChild = Drupal.overlayChild || { processed: false, behaviors: {} };

/**
 * Attach the child dialog behavior to new content.
 */
Drupal.behaviors.overlayChild = {
  attach: function (context, settings) {
    var self = Drupal.overlayChild;
    var settings = settings.overlayChild || {};

    // Make sure this behavior is not processed more than once.
    if (self.processed) {
      return;
    }
    self.processed = true;

    // If we cannot reach the parent window, then we have nothing else to do
    // here.
    if (!$.isPlainObject(parent.Drupal) || !$.isPlainObject(parent.Drupal.overlay)) {
      return;
    }

    // If a form has been submitted successfully, then the server side script
    // may have decided to tell us the parent window to close the popup dialog.
    if (settings.closeOverlay) {
      parent.Drupal.overlay.bindChild(window, true);
      // Use setTimeout to close the child window from a separate thread,
      // because the current one is busy processing Drupal behaviors.
      setTimeout(function () {
        // We need to store the parent variable locally because it will
        // disappear as soon as we close the iframe.
        var p = parent;
        p.Drupal.overlay.close();
        if (typeof settings.redirect == 'string') {
          p.Drupal.overlay.redirect(settings.redirect);
        }
      }, 1);
      return;
    }

    // If one of the regions displaying outside the overlay needs to be
    // reloaded, let the parent window know.
    if (settings.refreshRegions) {
      parent.Drupal.overlay.refreshRegions(settings.refreshRegions);
    }

    // Ok, now we can tell the parent window we're ready.
    parent.Drupal.overlay.bindChild(window);

    // Attach child related behaviors to the iframe document.
    self.attachBehaviors(context, settings);
  }
};

/**
 * Attach child related behaviors to the iframe document.
 */
Drupal.overlayChild.attachBehaviors = function (context, settings) {
  $.each(this.behaviors, function () {
    this(context, settings);
  });
};

/**
 * Scroll to the top of the page.
 *
 * This makes the overlay visible to users even if it is not as tall as the
 * previously shown overlay was.
 */
Drupal.overlayChild.behaviors.scrollToTop = function (context, settings) {
  window.scrollTo(0, 0);
};

/**
 * Capture and handle clicks.
 *
 * Instead of binding a click event handler to every link we bind one to the
 * document and handle events that bubble up. This also allows other scripts
 * to bind their own handlers to links and also to prevent overlay's handling.
 */
Drupal.overlayChild.behaviors.addClickHandler = function (context, settings) {
  $(document).bind('click.overlay-event', parent.Drupal.overlay.clickHandler);
};

/**
 * Modify forms depending on their relation to the overlay.
 *
 * By default, forms are assumed to keep the flow in the overlay. Thus their
 * action attribute get a ?render=overlay suffix.
 */
Drupal.overlayChild.behaviors.parseForms = function (context, settings) {
  $('form', context).once('overlay', function () {
    // Obtain the action attribute of the form.
    var action = $(this).attr('action');
    // Keep internal forms in the overlay.
    if (action == undefined || (action.indexOf('http') != 0 && action.indexOf('https') != 0)) {
      action += (action.indexOf('?') > -1 ? '&' : '?') + 'render=overlay';
      $(this).attr('action', action);
    }
    // Submit external forms into a new window.
    else {
      $(this).attr('target', '_new');
    }
  });
};

})(jQuery);
