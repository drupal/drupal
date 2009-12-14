// $Id$

(function ($) {

/**
 * Open the overlay, or load content into it, when an admin link is clicked.
 */
Drupal.behaviors.overlayParent = {
  attach: function (context, settings) {
    // Alter all admin links so that they will open in the overlay.
    $('a', context).filter(function () {
      return Drupal.overlay.isAdminLink(this.href);
    })
    .once('overlay')
    .each(function () {
      // Move the link destination to a URL fragment.
      this.href = Drupal.overlay.fragmentizeLink(this);
    });

    // Simulate the native click event for all links that appear outside the
    // overlay. jQuery UI Dialog prevents all clicks outside a modal dialog.
    $('.overlay-displace-top a:not(.overlay-displace-no-click)', context)
    .add('.overlay-displace-bottom a:not(.overlay-displace-no-click)', context)
    .click(function () {
      window.location.href = this.href;
    });

    // Resize the overlay when the toolbar drawer is toggled.
    $('#toolbar a.toggle', context).once('overlay').click(function () {
      setTimeout(function () {
        // Resize the overlay, if it's open.
        if (Drupal.overlay.iframe.documentSize) {
          Drupal.overlay.resize(Drupal.overlay.iframe.documentSize);
        }
      }, 150);
    });

    // Make sure the onhashchange handling below is only processed once.
    if (this.processed) {
      return;
    }
    this.processed = true;

    // When the hash (URL fragment) changes, open the overlay if needed.
    $(window).bind('hashchange', function (e) {
      // If we changed the hash to reflect an internal redirect in the overlay,
      // its location has already been changed, so don't do anything.
      if ($.data(window.location, window.location.href) === 'redirect') {
        $.data(window.location, window.location.href, null);
      }
      // Otherwise, change the contents of the overlay to reflect the new hash.
      else {
        Drupal.overlay.trigger();
      }
    });

    // Trigger the hashchange event once, after the page is loaded, so that
    // permalinks open the overlay.
    $(window).trigger('hashchange');
  }
};

/**
 * Overlay object for parent windows.
 */
Drupal.overlay = Drupal.overlay || {
  options: {},
  iframe: { $container: null, $element: null },
  isOpen: false
};

/**
 * Open an overlay.
 *
 * Ensure that only one overlay is opened ever. Use Drupal.overlay.load() if
 * the overlay is already open but a new page needs to be opened.
 *
 * @param options
 *   Properties of the overlay to open:
 *   - url: the URL of the page to open in the overlay.
 *   - width: width of the overlay in pixels.
 *   - height: height of the overlay in pixels.
 *   - autoFit: boolean indicating whether the overlay should be resized to
 *     fit the contents of the document loaded.
 *   - onOverlayOpen: callback to invoke when the overlay is opened.
 *   - onOverlayCanClose: callback to allow external scripts decide if the
 *     overlay can be closed.
 *   - onOverlayClose: callback to invoke when the overlay is closed.
 *   - customDialogOptions: an object with custom jQuery UI Dialog options.
 *
 * @return
 *   If the overlay was opened true, otherwise false.
 */
Drupal.overlay.open = function (options) {
  var self = this;

  // Just one overlay is allowed.
  if (self.isOpen || $('#overlay-container').size()) {
    return false;
  }

  var defaultOptions = {
    url: options.url,
    width: options.width,
    height: options.height,
    autoFit: (options.autoFit == undefined || options.autoFit),
    onOverlayOpen: options.onOverlayOpen,
    onOverlayCanClose: options.onOverlayCanClose,
    onOverlayClose: options.onOverlayClose,
    customDialogOptions: options.customDialogOptions || {}
  }

  self.options = $.extend(defaultOptions, options);

  // Create the dialog and related DOM elements.
  self.create();

  // Open the dialog offscreen where we can set its size, etc.
  var temp = self.iframe.$container.dialog('option', { position: ['-999em', '-999em'] }).dialog('open');;

  return true;
};

/**
 * Create the underlying markup and behaviors for the overlay.
 *
 * Reuses jQuery UI's dialog component to construct the overlay markup and
 * behaviors, sanitizing the options previously set in self.options.
 */
Drupal.overlay.create = function () {
  var self = this;

  self.iframe.$element = $(Drupal.theme('overlayElement'));
  self.iframe.$container = $(Drupal.theme('overlayContainer')).append(self.iframe.$element);

  $('body').append(self.iframe.$container);

  // Open callback for jQuery UI Dialog.
  var dialogOpen = function () {
    // Unbind the keypress handler installed by ui.dialog itself.
    // IE does not fire keypress events for some non-alphanumeric keys
    // such as the tab character. http://www.quirksmode.org/js/keys.html
    // Also, this is not necessary here because we need to deal with an
    // iframe element that contains a separate window.
    // We'll try to provide our own behavior from bindChild() method.
    $('.overlay').unbind('keypress.ui-dialog');

    // Adjust close button features.
    $('.overlay .ui-dialog-titlebar-close:not(.overlay-processed)').addClass('overlay-processed')
      .attr('href', '#')
      .attr('title', Drupal.t('Close'))
      .unbind('click')
      .bind('click', function () {
        try { self.close(); } catch(e) {}
        // Allow the click event to propagate, to clear the hash state.
        return true;
      });

    // Replace the title span element with an h1 element for accessibility.
    $('.overlay .ui-dialog-title').replaceWith(Drupal.theme('overlayTitleHeader', $('.overlay .ui-dialog-title').html()));

    // Compute initial dialog size.
    var dialogSize = self.sanitizeSize({width: self.options.width, height: self.options.height});

    // Compute frame size and dialog position based on dialog size.
    var frameSize = $.extend({}, dialogSize);
    frameSize.height -= $('.overlay .ui-dialog-titlebar').outerHeight(true);
    var dialogPosition = self.computePosition($('.overlay'), dialogSize);

    // Adjust size of the iframe element and container.
    $('.overlay').width(dialogSize.width).height(dialogSize.height);
    self.iframe.$container.width(frameSize.width).height(frameSize.height);
    self.iframe.$element.width(frameSize.width).height(frameSize.height);

    // Update the dialog size so that UI internals are aware of the change.
    self.iframe.$container.dialog('option', { width: dialogSize.width, height: dialogSize.height });

    // Hide the dialog, position it on the viewport and then fade it in with
    // the frame hidden until the child document is loaded.
    self.iframe.$element.hide();
    $('.overlay').hide().css({top: dialogPosition.top, left: dialogPosition.left});
    $('.overlay').fadeIn('fast', function () {
      // Load the document on hidden iframe (see bindChild method).
      self.load(self.options.url);
    });

    if ($.isFunction(self.options.onOverlayOpen)) {
      self.options.onOverlayOpen(self);
    }

    self.isOpen = true;
  };

  // Before close callback for jQuery UI Dialog.
  var dialogBeforeClose = function () {
    if (self.beforeCloseEnabled) {
      return true;
    }
    if (!self.beforeCloseIsBusy) {
      self.beforeCloseIsBusy = true;
      setTimeout(function () { self.close(); }, 1);
    }
    return false;
  };

  // Close callback for jQuery UI Dialog.
  var dialogClose = function () {
    $(document).unbind('keydown.overlay-event');
    $('.overlay .ui-dialog-titlebar-close').unbind('keydown.overlay-event');
    try {
      self.iframe.$element.remove();
      self.iframe.$container.dialog('destroy').remove();
    } catch(e) {};
    delete self.iframe.documentSize;
    delete self.iframe.Drupal;
    delete self.iframe.$element;
    delete self.iframe.$container;
    if (self.beforeCloseEnabled) {
      delete self.beforeCloseEnabled;
    }
    if (self.beforeCloseIsBusy) {
      delete self.beforeCloseIsBusy;
    }
    self.isOpen = false;
  };

  // Default jQuery UI Dialog options.
  var dialogOptions = {
    modal: true,
    autoOpen: false,
    closeOnEscape: true,
    resizable: false,
    title: Drupal.t('Loading...'),
    dialogClass: 'overlay',
    zIndex: 500,
    open: dialogOpen,
    beforeclose: dialogBeforeClose,
    close: dialogClose
  };

  // Allow external script override default jQuery UI Dialog options.
  $.extend(dialogOptions, self.options.customDialogOptions);

  // Create the jQuery UI Dialog.
  self.iframe.$container.dialog(dialogOptions);
};

/**
 * Load the given URL into the overlay iframe.
 *
 * Use this method to change the URL being loaded in the overlay if it is
 * already open.
 */
Drupal.overlay.load = function (url) {
  var self = this;
  var iframe = self.iframe.$element.get(0);
  // Get the document object of the iframe window.
  // @see http://xkr.us/articles/dom/iframe-document/
  var doc = (iframe.contentWindow || iframe.contentDocument);
  if (doc.document) {
    doc = doc.document;
  }
  // location.replace doesn't create a history entry. location.href does.
  // In this case, we want location.replace, as we're creating the history
  // entry using URL fragments.
  doc.location.replace(url);
};

/**
 * Check if the dialog can be closed.
 */
Drupal.overlay.canClose = function () {
  var self = this;
  if (!self.isOpen) {
    return false;
  }
  // Allow external scripts decide if the overlay can be closed.
  if ($.isFunction(self.options.onOverlayCanClose)) {
    if (!self.options.onOverlayCanClose(self)) {
      return false;
    }
  }
  return true;
};

/**
 * Close the overlay and remove markup related to it from the document.
 */
Drupal.overlay.close = function (args, statusMessages) {
  var self = this;

  // Offer the user a chance to change their mind if there is a form on the
  // page, which may have unsaved work on it.
  var iframeElement = self.iframe.$element.get(0);
  var iframeDocument = (iframeElement.contentWindow || iframeElement.contentDocument);
  if (iframeDocument.document) {
    iframeDocument = iframeDocument.document;
  }

  // Check if the dialog can be closed.
  if (!self.canClose()) {
    delete self.beforeCloseIsBusy;
    return false;
  }

  // Hide and destroy the dialog.
  function closeDialog() {
    // Prevent double execution when close is requested more than once.
    if (!$.isObject(self.iframe.$container)) {
      return;
    }
    self.beforeCloseEnabled = true;
    self.iframe.$container.dialog('close');
    if ($.isFunction(self.options.onOverlayClose)) {
      self.options.onOverlayClose(args, statusMessages);
    }
  }
  if (!$.isObject(self.iframe.$element) || !self.iframe.$element.size() || !self.iframe.$element.is(':visible')) {
    closeDialog();
  }
  else {
    self.iframe.$container.animate({height: 'hide'}, { duration: 'fast', 'queue': false });
    $('.overlay').animate({opacity: 'hide'}, closeDialog);
  }
  return true;
};

/**
 * Redirect the overlay parent window to the given URL.
 *
 * @param link
 *   Can be an absolute URL or a relative link to the domain root.
 */
Drupal.overlay.redirect = function (link) {
  if (link.indexOf('http') != 0 && link.indexOf('https') != 0) {
    var absolute = location.href.match(/https?:\/\/[^\/]*/)[0];
    link = absolute + link;
  }
  location.href = link;
  return true;
}

/**
 * Bind the child window.
 *
 * Add tabs on the overlay, keyboard actions and display animation.
 */
Drupal.overlay.bindChild = function (iFrameWindow, isClosing) {
  var self = this;
  var $iFrameWindow = iFrameWindow.jQuery;
  var $iFrameDocument = $iFrameWindow(iFrameWindow.document);
  var autoResizing = false;
  self.iframe.Drupal = iFrameWindow.Drupal;

  // We are done if the child window is closing.
  if (isClosing) {
    return;
  }

  // Make sure the parent window URL matches the child window URL.
  self.syncChildLocation($iFrameDocument[0].location);
  // Update the dialog title with the child window title.
  $('.overlay .ui-dialog-title').html($iFrameDocument.attr('title')).focus();
  // Add a title attribute to the iframe for accessibility.
  self.iframe.$element.attr('title', Drupal.t('@title dialog', { '@title': $iFrameDocument.attr('title') }));

  // If the shortcut add/delete button exists, move it to the dialog title.
  var addToShortcuts = $('.add-or-remove-shortcuts', $iFrameDocument);
  if (addToShortcuts.length) {
    // Remove any existing shortcut button markup in the title section.
    $('.ui-dialog-titlebar .add-or-remove-shortcuts').remove();
    // Make the link overlay-friendly.
    var $link = $('a', addToShortcuts);
    $link.attr('href', Drupal.overlay.fragmentizeLink($link.get(0)));
    // Move the button markup to the title section. We need to copy markup
    // instead of moving the DOM element, because Webkit browsers will not
    // move DOM elements between two DOM documents.
    var shortcutsMarkup = '<div class="' + $(addToShortcuts).attr('class') + '">' + $(addToShortcuts).html() + '</div>';
    $('.overlay .ui-dialog-title').after(shortcutsMarkup);
    $('.add-or-remove-shortcuts', $iFrameDocument).remove();
  }

  // Remove any existing tabs.
  $('.overlay .ui-dialog-titlebar ul').remove();

  // Setting tabIndex makes the div focusable.
  $iFrameDocument.attr('tabindex', -1);

  $('.ui-dialog-titlebar-close-bg').animate({opacity: 0.9999}, 'fast');

  // Perform animation to show the iframe element.
  self.iframe.$element.fadeIn('fast', function () {
    // @todo: Watch for experience in the way we compute the size of the
    // iframed document. There are many ways to do it, and none of them
    // seem to be perfect. Note though, that the size of the iframe itself
    // may affect the size of the child document, especially on fluid layouts.
    self.iframe.documentSize = { width: $iFrameDocument.width(), height: $iFrameWindow('body').height() + 25 };

    // Adjust overlay to fit the iframe content?
    if (self.options.autoFit) {
      self.resize(self.iframe.documentSize);
    }

    // Try to enhance keyboard based navigation of the overlay.
    // Logic inspired by the open() method in ui.dialog.js, and
    // http://wiki.codetalks.org/wiki/index.php/Docs/Keyboard_navigable_JS_widgets

    // Get a reference to the close button.
    var $closeButton = $('.overlay .ui-dialog-titlebar-close');

    // Search tabbable elements on the iframed document to speed up related
    // keyboard events.
    // @todo: Do we need to provide a method to update these references when
    // AJAX requests update the DOM on the child document?
    var $iFrameTabbables = $iFrameWindow(':tabbable:not(form)');
    var $firstTabbable = $iFrameTabbables.filter(':first');
    var $lastTabbable = $iFrameTabbables.filter(':last');

    // Unbind keyboard event handlers that may have been enabled previously.
    $(document).unbind('keydown.overlay-event');
    $closeButton.unbind('keydown.overlay-event');

    // When the focus leaves the close button, then we want to jump to the
    // first/last inner tabbable element of the child window.
    $closeButton.bind('keydown.overlay-event', function (event) {
      if (event.keyCode && event.keyCode == $.ui.keyCode.TAB) {
        var $target = (event.shiftKey ? $lastTabbable : $firstTabbable);
        if (!$target.size()) {
          $target = $iFrameDocument;
        }
        setTimeout(function () { $target.focus(); }, 10);
        return false;
      }
    });

    // When the focus leaves the child window, then drive the focus to the
    // close button of the dialog.
    $iFrameDocument.bind('keydown.overlay-event', function (event) {
      if (event.keyCode) {
        if (event.keyCode == $.ui.keyCode.TAB) {
          if (event.shiftKey && event.target == $firstTabbable.get(0)) {
            setTimeout(function () { $closeButton.focus(); }, 10);
            return false;
          }
          else if (!event.shiftKey && event.target == $lastTabbable.get(0)) {
            setTimeout(function () { $closeButton.focus(); }, 10);
            return false;
          }
        }
        else if (event.keyCode == $.ui.keyCode.ESCAPE) {
          setTimeout(function () { self.close(); }, 10);
          return false;
        }
      }
    });

    var autoResize = function () {
      if (typeof self.iframe.$element == 'undefined') {
        autoResizing = false;
        $(window).unbind('resize', windowResize);
        return;
      }
      var iframeElement = self.iframe.$element.get(0);
      var iframeDocument = (iframeElement.contentWindow || iframeElement.contentDocument);
      if (iframeDocument.document) {
        iframeDocument = iframeDocument.document;
      }
      // Use outerHeight() because otherwise the calculation will be off
      // because of padding and/or border added by the theme.
      var height = $(iframeDocument).find('body').outerHeight() + 25;
      self.iframe.$element.css('height', height);
      self.iframe.$container.css('height', height);
      self.iframe.$container.parent().css('height', height + 45);
      // Don't allow the shadow background to shrink so it's not enough to hide
      // the whole page. Take the existing document height (with overlay) and
      // the body height itself for our base calculation.
      var docHeight = Math.min($(document).find('body').outerHeight(), $(document).height());
      $('.ui-widget-overlay').height(Math.max(docHeight, $(window).height(), height + 145));
      setTimeout(autoResize, 150);
    };

    var windowResize = function () {
      var width = $(window).width()
      var change = lastWidth - width;
      var currentWidth = self.iframe.$element.width();
      var newWidth = lastFrameWidth - change;
      lastWidth = width;
      lastFrameWidth = newWidth;

      if (newWidth >= 300) {
        self.iframe.$element.css('width', newWidth);
        self.iframe.$container.css('width', newWidth);
        self.iframe.$container.parent().css('width', newWidth);
        widthBelowMin = false;
      }
      else {
        widthBelowMin = true;
      }
    }

    if (!autoResizing) {
      autoResizing = true;
      autoResize();
      var lastFrameWidth = self.iframe.$element.width();
      var lastWidth = $(window).width();
      $(window).resize(windowResize);
    }

    // When the focus is captured by the parent document, then try
    // to drive the focus back to the first tabbable element, or the
    // close button of the dialog (default).
    $(document).bind('keydown.overlay-event', function (event) {
      if (event.keyCode && event.keyCode == $.ui.keyCode.TAB) {
        setTimeout(function () {
          if (!$iFrameWindow(':tabbable:not(form):first').focus().size()) {
            $closeButton.focus();
          }
        }, 10);
        return false;
      }
    });

    // If there are tabs in the page, move them to the titlebar.
    var tabs = $iFrameDocument.find('ul.primary').get(0);

    // This breaks in anything less than IE 7. Prevent it from running.
    if (typeof tabs != 'undefined' && (!$.browser.msie || parseInt($.browser.version) >= 7)) {
      $('.ui-dialog-titlebar').append($(tabs).remove().get(0));
      if ($(tabs).is('.primary')) {
        $(tabs).find('a').removeClass('overlay-processed');
        Drupal.attachBehaviors($(tabs));
      }
      // Remove any classes from the list element to avoid theme styles
      // clashing with our styling.
      $(tabs).removeAttr('class');
    }
  });
};

/**
 * Unbind the child window.
 *
 * Remove keyboard event handlers, reset title and hide the iframe.
 */
Drupal.overlay.unbindChild = function (iFrameWindow) {
  var self = this;

  // Prevent memory leaks by explicitly unbinding keyboard event handler
  // on the child document.
  iFrameWindow.jQuery(iFrameWindow.document).unbind('keydown.overlay-event');

  // Change the overlay title.
  $('.overlay .ui-dialog-title').html(Drupal.t('Please wait...'));

  // Hide the iframe element.
  self.iframe.$element.fadeOut('fast');
};

/**
 * Check if the given link is in the administrative section of the site.
 *
 * @param url
 *   The url to be tested.
 * @return boolean
 *   TRUE if the URL represents an administrative link, FALSE otherwise.
 */
Drupal.overlay.isAdminLink = function (url) {
  var self = this;
  // Create a native Link object, so we can use its object methods.
  var link = $(url.link(url)).get(0);
  var path = link.pathname;
  // Ensure a leading slash on the path, omitted in some browsers.
  if (path.substr(0, 1) != '/') {
    path = '/' + path;
  }
  path = path.replace(new RegExp(Drupal.settings.basePath), '');
  if (path == '') {
    // If the path appears empty, it might mean the path is represented in the
    // query string (clean URLs are not used).
    var match = new RegExp("(\\?|&)q=(.+)(&|$)").exec(link.search);
    if (match && match.length == 4) {
      path = match[2];
    }
  }

  // Turn the list of administrative paths into a regular expression.
  if (!self.adminPathRegExp) {
    var adminPaths = '^(' + Drupal.settings.overlay.paths.admin.replace(/\s+/g, ')$|^(') + ')$';
    var nonAdminPaths = '^(' + Drupal.settings.overlay.paths.non_admin.replace(/\s+/g, ')$|^(') + ')$';
    adminPaths = adminPaths.replace(/\*/g, '.*');
    nonAdminPaths = nonAdminPaths.replace(/\*/g, '.*');
    self.adminPathRegExp = new RegExp(adminPaths);
    self.nonAdminPathRegExp = new RegExp(nonAdminPaths);
  }

  return self.adminPathRegExp.exec(path) && !self.nonAdminPathRegExp.exec(path);
}

/**
 * Sanitize dialog size.
 *
 * Do not let the overlay go over the 0.78x of the width of the screen and set
 * minimal height. The height is not limited due to how we rely on the parent
 * window to provide scrolling instead of scrolling in scrolling with the
 * overlay.
 *
 * @param size
 *   Contains 'width' and 'height' items as numbers.
 * @return
 *   The same structure with sanitized number values.
 */
Drupal.overlay.sanitizeSize = function (size) {
  var width, height;
  var $window = $(window);

  // Use 300px as the minimum width but at most expand to 78% of the window.
  // Ensures that users see that there is an actual website in the background.
  var minWidth = 300, maxWidth = parseInt($window.width() * .78);
  if (typeof size.width != 'number') {
    width = maxWidth;
  }
  // Set to at least minWidth but at most maxWidth.
  else if (size.width < minWidth || size.width > maxWidth) {
    width = Math.min(maxWidth, Math.max(minWidth, size.width));
  }
  else {
    width = size.width;
  }

  // Use 100px as the minimum height. Expand to 92% of the window if height
  // was invalid, to ensure that we have a reasonable chance to show content.
  var minHeight = 100, maxHeight = parseInt($window.height() * .92);
  if (typeof size.height != 'number') {
    height = maxHeight;
  }
  else if (size.height < minHeight) {
    // Do not consider maxHeight as the actual maximum height, since we rely on
    // the parent window scroll bar to scroll the window. Only set up to be at
    // least the minimal height.
    height = Math.max(minHeight, size.height);
  }
  else {
    height = size.height;
  }
  return { width: width, height: height };
};

/**
 * Compute position to center horizontally and on viewport top vertically.
 */
Drupal.overlay.computePosition = function ($element, elementSize) {
  var $window = $(window);
  // Consider any region that should be visible above the overlay (such as
  // an admin toolbar).
  var $toolbar = $('.overlay-displace-top');
  var toolbarHeight = 0;
  $toolbar.each(function () {
    toolbarHeight += $toolbar.height();
  });
  var position = {
    left: Math.max(0, parseInt(($window.width() - elementSize.width) / 2)),
    top: toolbarHeight + 20
  };

  // Reset the scroll to the top of the window so that the overlay is visible again.
  window.scrollTo(0, 0);
  return position;
};

/**
 * Resize overlay to the given size.
 *
 * @param size
 *   Contains 'width' and 'height' items as numbers.
 */
Drupal.overlay.resize = function (size) {
  var self = this;

  // Compute frame and dialog size based on requested document size.
  var titleBarHeight = $('.overlay .ui-dialog-titlebar').outerHeight(true);
  var frameSize = self.sanitizeSize(size);
  var dialogSize = $.extend({}, frameSize);
  dialogSize.height += titleBarHeight + 15;

  // Compute position on viewport.
  var dialogPosition = self.computePosition($('.overlay'), dialogSize);

  var animationOptions = $.extend(dialogSize, dialogPosition);

  // Perform the resize animation.
  $('.overlay').animate(animationOptions, 'fast', function () {
    // Proceed only if the dialog still exists.
    if ($.isObject(self.iframe.$element) && $.isObject(self.iframe.$container)) {
      // Resize the iframe element and container.
      $('.overlay').width(dialogSize.width).height(dialogSize.height);
      self.iframe.$container.width(frameSize.width).height(frameSize.height);
      self.iframe.$element.width(frameSize.width).height(frameSize.height);

      // Update the dialog size so that UI internals are aware of the change.
      self.iframe.$container.dialog('option', { width: dialogSize.width, height: dialogSize.height });

      // Keep the dim background grow or shrink with the dialog.
      $('.ui-widget-overlay').height($(document).height());

      // Animate body opacity, so we fade in the page as it loads in.
      $(self.iframe.$element.get(0)).contents().find('body.overlay').animate({opacity: 0.9999}, 'slow');
    }
  });
};

/**
 * Add overlay rendering GET parameter to the given href.
 */
Drupal.overlay.addOverlayParam = function (href) {
  return $.param.querystring(href, {'render': 'overlay'});
  // Do not process links with an empty href, or that only have the fragment or
  // which are external links.
  if (href.length > 0 && href.charAt(0) != '#' && href.indexOf('http') != 0 && href.indexOf('https') != 0) {
    var fragmentIndex = href.indexOf('#');
    var fragment = '';
    if (fragmentIndex != -1) {
      fragment = href.substr(fragmentIndex);
      href = href.substr(0, fragmentIndex);
    }
    href += (href.indexOf('?') > -1 ? '&' : '?') + 'render=overlay' + fragment;
  }
  return href;
};

/**
 * Open, reload, or close the overlay, based on the current URL fragment.
 */
Drupal.overlay.trigger = function () {
  // Get the overlay URL from the current URL fragment.
  var state = $.bbq.getState('overlay');
  if (state) {
    // Append render variable, so the server side can choose the right
    // rendering and add child modal frame code to the page if needed.
    var linkURL = Drupal.overlay.addOverlayParam(Drupal.settings.basePath + state);

    // If the modal frame is already open, replace the loaded document with
    // this new one.
    if (Drupal.overlay.isOpen) {
      Drupal.overlay.load(linkURL);
    }
    else {
      // There is not an overlay opened yet; we should open a new one.
      var overlayOptions = {
        url: linkURL,
        onOverlayClose: function () {
          // Clear the overlay URL fragment.
          $.bbq.pushState();
          // Remove active class from all header buttons.
          $('a.overlay-processed').each(function () {
            $(this).removeClass('active');
          });
        },
        draggable: false
      };
      Drupal.overlay.open(overlayOptions);
    }
  }
  else {
    // If there is no overlay URL in the fragment, close the overlay.
    try {
      Drupal.overlay.close();
    }
    catch(e) {
      // The close attempt may have failed because the overlay isn't open.
      // If so, no special handling is needed here.
    }
  }
};

/**
 * Make a regular admin link into a URL that will trigger the overlay to open.
 *
 * @param link
 *   A Javascript Link object (i.e. an <a> element).
 * @return
 *   A URL that will trigger the overlay (in the form
 *   /node/1#overlay=admin/config).
 */
Drupal.overlay.fragmentizeLink = function (link) {
  // Don't operate on links that are already overlay-ready.
  var params = $.deparam.fragment(link.href);
  if (params.overlay) {
    return link.href;
  }

  // Determine the link's original destination, and make it relative to the
  // Drupal site.
  var fullpath = link.pathname;
  var re = new RegExp('^' + Drupal.settings.basePath);
  var path = fullpath.replace(re, '');
  // Preserve existing query and fragment parameters in the URL.
  var fragment = link.hash;
  var querystring = link.search;
  // If the query includes ?render=overlay, leave it out.
  if (querystring.indexOf('render=overlay') !== -1) {
    querystring = querystring.replace(/render=overlay/, '');
    if (querystring === '?') {
      querystring = '';
    }
  }

  var destination = path + querystring + fragment;

  // Assemble the overlay-ready link.
  var base = window.location.href;
  return $.param.fragment(base, {'overlay':destination});
}

/**
 * Make sure the internal overlay URL is reflected in the parent URL fragment.
 *
 * Normally the parent URL fragment determines the overlay location. However, if
 * the overlay redirects internally, the parent doesn't get informed, and the
 * parent URL fragment will be out of date. This is a sanity check to make
 * sure we're in the right place.
 *
 * @param childLocation
 *   The child window's location object.
 */
Drupal.overlay.syncChildLocation = function (childLocation) {
  var expected = $.bbq.getState('overlay');
  // This is just a sanity check, so we're comparing paths, not query strings.
  expected = Drupal.settings.basePath + expected.replace(/\?.+/, '');
  var actual = childLocation.pathname;
  if (expected !== actual) {
    // There may have been a redirect inside the child overlay window that the
    // parent wasn't aware of. Update the parent URL fragment appropriately.
    var newLocation = Drupal.overlay.fragmentizeLink(childLocation);
    // Set a 'redirect' flag on the new location so the hashchange event handler
    // knows not to change the overlay's content.
    $.data(window.location, newLocation, 'redirect');
    window.location.href = newLocation;
  }
};

/**
 * Refresh any regions of the page that are displayed outside the overlay.
 *
 * @param data
 *   An array of objects with information on the page regions to be refreshed.
 *   For each object, the key is a CSS class identifying the region to be
 *   refreshed, and the value represents the section of the Drupal $page array
 *   corresponding to this region.
 */
Drupal.overlay.refreshRegions = function (data) {
  $.each(data, function () {
    var region_info = this;
    $.each(region_info, function (regionClass) {
      var regionName = region_info[regionClass];
      var regionSelector = '.' + regionClass;
      $.get(Drupal.settings.basePath + Drupal.settings.overlay.ajaxCallback + '/' + regionName, function (newElement) {
        $(regionSelector).replaceWith($(newElement));
        Drupal.attachBehaviors($(regionSelector), Drupal.settings);
      });
    });
  });
};

/**
 * Theme function to create the overlay iframe element.
 */
Drupal.theme.prototype.overlayElement = function () {
  // Note: We use scrolling="yes" for IE as a workaround to yet another IE bug
  // where the horizontal scrollbar is always rendered no matter how wide the
  // iframe element is defined.
  return '<iframe id="overlay-element" frameborder="0" name="overlay-element"'+ ($.browser.msie ? ' scrolling="yes"' : '') +'/>';
};

/**
 * Theme function to create a container for the overlay iframe element.
 */
Drupal.theme.prototype.overlayContainer = function () {
  return '<div id="overlay-container"/>';
}

/**
 * Theme function for the overlay title markup.
 */
Drupal.theme.prototype.overlayTitleHeader = function (text) {
  return '<h1 id="ui-dialog-title-overlay-container" class="ui-dialog-title" tabindex="-1" unselectable="on">' + text + '</h1>';
};

})(jQuery);
