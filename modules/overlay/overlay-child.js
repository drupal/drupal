// $Id$

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
    if (!$.isObject(parent.Drupal) || !$.isObject(parent.Drupal.overlay)) {
      return;
    }

    // If a form has been submitted successfully, then the server side script
    // may have decided to tell us the parent window to close the popup dialog.
    if (settings.closeOverlay) {
      parent.Drupal.overlay.bindChild(window, true);
      // Close the child window from a separate thread because the current
      // one is busy processing Drupal behaviors.
      setTimeout(function () {
        // We need to store the parent variable locally because it will
        // disappear as soon as we close the iframe.
        var p = parent;
        p.Drupal.overlay.close(settings.args, settings.statusMessages);
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

    // Install onBeforeUnload callback, if module is present.
    if ($.isObject(Drupal.onBeforeUnload) && !Drupal.onBeforeUnload.callbackExists('overlayChild')) {
      Drupal.onBeforeUnload.addCallback('overlayChild', function () {
        // Tell the parent window we're unloading.
        parent.Drupal.overlay.unbindChild(window);
      });
    }

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
 * Modify links and forms depending on their relation to the overlay.
 *
 * By default, forms and links are assumed to keep the flow in the overlay.
 * Thus their action and href attributes respectively get a ?render=overlay
 * suffix. Non-administrative links should however close the overlay and
 * redirect the parent page to the given link. This would include links in a
 * content listing, where administration options are mixed with links to the
 * actual content to be shown on the site out of the overlay.
 *
 * @see Drupal.overlay.isAdminLink()
 */
Drupal.overlayChild.behaviors.parseLinks = function (context, settings) {
  var closeAndRedirectOnClick = function (event) {
    // We need to store the parent variable locally because it will
    // disappear as soon as we close the iframe.
    var parentWindow = parent;
    if (parentWindow.Drupal.overlay.close(false)) {
      parentWindow.Drupal.overlay.redirect($(this).attr('href'));
    }
    return false;
  };
  var redirectOnClick = function (event) {
    parent.Drupal.overlay.redirect($(this).attr('href'));
    return false;
  };

  $('a:not(.overlay-exclude)', context).once('overlay', function () {
    var href = $(this).attr('href');
    // Skip links that don't have an href attribute.
    if (href == undefined) {
      return;
    }
    // Non-admin links should close the overlay and open in the main window.
    else if (!parent.Drupal.overlay.isAdminLink(href)) {
      $(this).click(closeAndRedirectOnClick);
    }
    // Open external links in a new window.
    else if (href.indexOf('http') > 0 || href.indexOf('https') > 0) {
      $(this).attr('target', '_new');
    }
    // Open admin links in the overlay.
    else {
      $(this)
        .attr('href', parent.Drupal.overlay.fragmentizeLink(this))
        .click(redirectOnClick);
    }
  });

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
