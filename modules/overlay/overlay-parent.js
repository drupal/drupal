// $Id$

(function ($) {

/**
 * Open the overlay, or load content into it, when an admin link is clicked.
 */
Drupal.behaviors.overlayParent = {
  attach: function (context, settings) {
    var $window = $(window);

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
        if (Drupal.overlay.isOpen) {
          Drupal.overlay.outerResize();
        }
      }, 10);
    });

    // Make sure the onhashchange handling below is only processed once.
    if (this.processed) {
      return;
    }
    this.processed = true;

    // When the hash (URL fragment) changes, open the overlay if needed.
    $window.bind('hashchange', function (e) {
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
    $window.trigger('hashchange');
  }
};

/**
 * Overlay object for parent windows.
 */
Drupal.overlay = Drupal.overlay || {
  options: {},
  isOpen: false,
  isOpening: false,
  isClosing: false,
  isLoading: false,

  onOverlayCloseArgs: null,
  onOverlayCloseStatusMessages: null,

  resizeTimeoutID: null,
  lastHeight: 0,

  $wrapper: null,
  $dialog: null,
  $dialogTitlebar: null,
  $container: null,
  $iframe: null,

  $iframeWindow: null,
  $iframeDocument: null,
  $iframeBody: null
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
  if (self.isOpen || self.isOpening) {
    return false;
  }
  self.isOpening = true;

  var defaultOptions = {
    url: options.url,
    width: options.width,
    height: options.height,
    autoFit: (options.autoFit == undefined || options.autoFit),
    onOverlayOpen: options.onOverlayOpen,
    onOverlayCanClose: options.onOverlayCanClose,
    onOverlayClose: options.onOverlayClose,
    customDialogOptions: options.customDialogOptions || {}
  };

  self.options = $.extend(defaultOptions, options);

  // Create the dialog and related DOM elements.
  self.create();

  // Open the dialog.
  self.$container.dialog('open');

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
  var $window = $(window);
  var $body = $('body');

  var delayedOuterResize = function() {
    setTimeout(self.outerResize, 1);
  };

  // Open callback for jQuery UI Dialog.
  var dialogOpen = function () {
    // Unbind the keypress handler installed by ui.dialog itself.
    // IE does not fire keypress events for some non-alphanumeric keys
    // such as the tab character. http://www.quirksmode.org/js/keys.html
    // Also, this is not necessary here because we need to deal with an
    // iframe element that contains a separate window.
    // We'll try to provide our own behavior from bindChild() method.
    self.$dialog.unbind('keypress.ui-dialog');

    // Add title to close button features for accessibility.
    self.$dialogTitlebar.find('.ui-dialog-titlebar-close').attr('title', Drupal.t('Close'));

    // Replace the title span element with an h1 element for accessibility.
    var $dialogTitle = self.$dialogTitlebar.find('.ui-dialog-title');
    $dialogTitle.replaceWith(Drupal.theme('overlayTitleHeader', $dialogTitle.html()));

    // Wrap the dialog into a div so we can center it using CSS.
    self.$dialog.wrap(Drupal.theme('overlayWrapper'));
    self.$wrapper = self.$dialog.parent();

    self.$dialog.css({
      // Remove some CSS properties added by ui.dialog itself.
      position: '', left: '', top: '', height: ''
    });

    // Add a class to the body to indicate the overlay is open.
    $body.addClass('overlay-open');

    // Adjust overlay size when window is resized.
    $window.bind('resize', delayedOuterResize);

    if (self.options.autoFit) {
      $body.addClass('overlay-autofit');
    }
    else {
      // Add scrollbar to the iframe when autoFit is disabled.
      self.$iframe.css('overflow', 'auto').attr('scrolling', 'yes');
    }

    // Compute initial dialog size.
    self.outerResize();

    // Load the document on hidden iframe (see bindChild method).
    self.load(self.options.url);

    if ($.isFunction(self.options.onOverlayOpen)) {
      self.options.onOverlayOpen(self);
    }

    self.isOpen = true;
    self.isOpening = false;
  };

  // Before close callback for jQuery UI Dialog.
  var dialogBeforeClose = function () {
    // Prevent double execution when close is requested more than once.
    if (!self.isOpen || self.isClosing) {
      return false;
    }

    // Allow external scripts decide if the overlay can be closed.
    // The external script should call Drupal.overlay.close() again when it is ready for closing.
    if ($.isFunction(self.options.onOverlayCanClose) && self.options.onOverlayCanClose(self) === false) {
      return false;
    }

    self.isClosing = true;

    // Stop all animations.
    $window.unbind('resize', delayedOuterResize);
    clearTimeout(self.resizeTimeoutID);
  };

  // Close callback for jQuery UI Dialog.
  var dialogClose = function () {
    $(document).unbind('keydown.overlay-event');

    $body.removeClass('overlay-open').removeClass('overlay-autofit');

    // When the iframe is still loading don't destroy it immediately but after
    // the content is loaded (see self.load).
    if (!self.isLoading) {
      self.$iframe.unbind('load');
      self.destroy();
    }

    self.isOpen = false;
    self.isClosing = false;

    self.lastHeight = 0;

    if ($.isFunction(self.options.onOverlayClose)) {
      self.options.onOverlayClose(self.onOverlayCloseArgs, self.onOverlayCloseStatusMessages);
    }
    self.onOverlayCloseArgs = null;
    self.onOverlayCloseStatusMessages = null;
  };

  // Default jQuery UI Dialog options.
  var dialogOptions = {
    autoOpen: false,
    closeOnEscape: true,
    dialogClass: 'overlay',
    draggable: false,
    modal: true,
    resizable: false,
    title: Drupal.t('Loading...'),
    zIndex: 500,

    // When not set use a empty string so it is not applied and CSS can handle it.
    width: self.options.width || '',
    height: self.options.height,

    open: dialogOpen,
    beforeclose: dialogBeforeClose,
    close: dialogClose
  };

  // Create the overlay container and iframe.
  self.$iframe = $(Drupal.theme('overlayElement'));
  self.$container = $(Drupal.theme('overlayContainer')).append(self.$iframe);

  // Allow external script override default jQuery UI Dialog options.
  $.extend(dialogOptions, self.options.customDialogOptions);

  // Create the jQuery UI Dialog.
  self.$container.dialog(dialogOptions);
  // Cache dialog selector.
  self.$dialog = self.$container.parents('.' + dialogOptions.dialogClass);
  self.$dialogTitlebar = self.$dialog.find('.ui-dialog-titlebar');
};

/**
 * Load the given URL into the overlay iframe.
 *
 * Use this method to change the URL being loaded in the overlay if it is
 * already open.
 */
Drupal.overlay.load = function (url) {
  var self = this;
  var iframeElement = self.$iframe.get(0);

  self.isLoading = true;

  self.$iframeWindow = null;
  self.$iframeDocument = null;
  self.$iframeBody = null;

  // No need to resize when loading.
  clearTimeout(self.resizeTimeoutID);

  // Change the overlay title.
  self.$container.dialog('option', 'title', Drupal.t('Loading...'));

  // When a new overlay is opened and loaded, we add a loaded class to
  // the dialog. The loaded class is not removed and added back again
  // while switching between pages with the overlay already open,
  // due to performance issues.

  //self.$dialog.removeClass('overlay-loaded');
  self.$iframe
    .css('opacity', 0.2)
    .load(function () {
      self.isLoading = false;

      // Only continue when overlay is still open and not closing.
      if (self.isOpen && !self.isClosing) {
        self.$iframe.css('opacity', 1);
        self.$dialog.addClass('overlay-loaded');
      }
      else {
        self.destroy();
      }
  });

  // Get the document object of the iframe window.
  // @see http://xkr.us/articles/dom/iframe-document/
  var iframeDocument = (iframeElement.contentWindow || iframeElement.contentDocument);
  if (iframeDocument.document) {
    iframeDocument = iframeDocument.document;
  }

  // location.replace doesn't create a history entry. location.href does.
  // In this case, we want location.replace, as we're creating the history
  // entry using URL fragments.
  iframeDocument.location.replace(url);
};

/**
 * Close the overlay and remove markup related to it from the document.
 */
Drupal.overlay.close = function (args, statusMessages) {
  var self = this;

  self.onOverlayCloseArgs = args;
  self.onOverlayCloseStatusMessages = statusMessages;

  return self.$container.dialog('close');
};

/**
 * Destroy the overlay.
 */
Drupal.overlay.destroy = function () {
  var self = this;

  self.$container.dialog('destroy').remove();
  self.$wrapper.remove();

  self.$wrapper = null;
  self.$dialog = null;
  self.$dialogTitlebar = null;
  self.$container = null;
  self.$iframe = null;

  self.$iframeWindow = null;
  self.$iframeDocument = null;
  self.$iframeBody = null;
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
};

/**
 * Bind the child window.
 *
 * Add tabs on the overlay, keyboard actions and display animation.
 */
Drupal.overlay.bindChild = function (iframeWindow, isClosing) {
  var self = this;
  self.$iframeWindow = iframeWindow.jQuery;
  self.$iframeDocument = self.$iframeWindow(iframeWindow.document);
  self.$iframeBody = self.$iframeWindow('body');

  // We are done if the child window is closing.
  if (isClosing || self.isClosing || !self.isOpen) {
    return;
  }

  // Make sure the parent window URL matches the child window URL.
  self.syncChildLocation(iframeWindow.document.location);

  // Reset the scroll to the top of the window so that the overlay is visible again.
  window.scrollTo(0, 0);

  var iframeTitle = self.$iframeDocument.attr('title');

  // Update the dialog title with the child window title.
  self.$container.dialog('option', 'title', iframeTitle);
  self.$dialogTitlebar.find('.ui-dialog-title').focus();
  // Add a title attribute to the iframe for accessibility.
  self.$iframe.attr('title', Drupal.t('@title dialog', { '@title': iframeTitle }));

  // Remove any existing shortcut button markup in the title section.
  self.$dialogTitlebar.find('.add-or-remove-shortcuts').remove();
  // If the shortcut add/delete button exists, move it to the dialog title.
  var $addToShortcuts = self.$iframeWindow('.add-or-remove-shortcuts');
  if ($addToShortcuts.length) {
    // Make the link overlay-friendly.
    var $link = $('a', $addToShortcuts);
    $link.attr('href', Drupal.overlay.fragmentizeLink($link.get(0)));
    // Move the button markup to the title section. We need to copy markup
    // instead of moving the DOM element, because Webkit and IE browsers will
    // not move DOM elements between two DOM documents.
    var shortcutsMarkup = '<div class="' + $($addToShortcuts).attr('class') + '">' + $($addToShortcuts).html() + '</div>';
    self.$dialogTitlebar.find('.ui-dialog-title').after(shortcutsMarkup);
    self.$iframeWindow('.add-or-remove-shortcuts').remove();
  }

  // Remove any existing tabs in the title section.
  self.$dialogTitlebar.find('ul').remove();
  // If there are tabs in the page, move them to the titlebar.
  var $tabs = self.$iframeWindow('ul.primary');
  if ($tabs.length) {
    // Move the tabs markup to the title section. We need to copy markup
    // instead of moving the DOM element, because Webkit and IE browsers will
    // not move DOM elements between two DOM documents.
    $tabs = $(self.$iframeWindow('<div>').append($tabs.clone()).remove().html());

    self.$dialogTitlebar.append($tabs);
    if ($tabs.is('.primary')) {
      $tabs.find('a').removeClass('overlay-processed');
      Drupal.attachBehaviors($tabs);
    }
    // Remove any classes from the list element to avoid theme styles
    // clashing with our styling.
    $tabs.removeAttr('class');
  }

  // Try to enhance keyboard based navigation of the overlay.
  // Logic inspired by the open() method in ui.dialog.js, and
  // http://wiki.codetalks.org/wiki/index.php/Docs/Keyboard_navigable_JS_widgets

  // Get a reference to the close button.
  var $closeButton = self.$dialogTitlebar.find('.ui-dialog-titlebar-close');

  // Search tabbable elements on the iframed document to speed up related
  // keyboard events.
  // @todo: Do we need to provide a method to update these references when
  // AJAX requests update the DOM on the child document?
  var $iframeTabbables = self.$iframeWindow(':tabbable:not(form)');
  var $firstTabbable = $iframeTabbables.filter(':first');
  var $lastTabbable = $iframeTabbables.filter(':last');

  // Unbind keyboard event handlers that may have been enabled previously.
  $(document).unbind('keydown.overlay-event');
  $closeButton.unbind('keydown.overlay-event');

  // When the focus leaves the close button, then we want to jump to the
  // first/last inner tabbable element of the child window.
  $closeButton.bind('keydown.overlay-event', function (event) {
    if (event.keyCode && event.keyCode == $.ui.keyCode.TAB) {
      var $target = (event.shiftKey ? $lastTabbable : $firstTabbable);
      if (!$target.size()) {
        $target = self.$iframeDocument;
      }
      setTimeout(function () { $target.focus(); }, 10);
      return false;
    }
  });

  // When the focus leaves the child window, then drive the focus to the
  // close button of the dialog.
  self.$iframeDocument.bind('keydown.overlay-event', function (event) {
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

  // When the focus is captured by the parent document, then try
  // to drive the focus back to the first tabbable element, or the
  // close button of the dialog (default).
  $(document).bind('keydown.overlay-event', function (event) {
    if (event.keyCode && event.keyCode == $.ui.keyCode.TAB) {
      setTimeout(function () {
        if (!self.$iframeWindow(':tabbable:not(form):first').focus().size()) {
          $closeButton.focus();
        }
      }, 10);
      return false;
    }
  });

  // Adjust overlay to fit the iframe content?
  if (self.options.autoFit) {
    self.innerResize();

    var delayedResize = function() {
      if (!self.isOpen) {
        clearTimeout(self.resizeTimeoutID);
        return;
      }

      self.innerResize();
      iframeWindow.scrollTo(0, 0);
      self.resizeTimeoutID = setTimeout(delayedResize, 150);
    };

    clearTimeout(self.resizeTimeoutID);
    self.resizeTimeoutID = setTimeout(delayedResize, 150);
  }
};

/**
 * Unbind the child window.
 *
 * Remove keyboard event handlers, reset title and hide the iframe.
 */
Drupal.overlay.unbindChild = function (iframeWindow) {
  var self = this;
  var $iframeDocument = iframeWindow.jQuery(iframeWindow.document);

  // Prevent memory leaks by explicitly unbinding keyboard event handler
  // on the child document.
  $iframeDocument.unbind('keydown.overlay-event');

  // Change the overlay title.
  self.$container.dialog('option', 'title', Drupal.t('Please wait...'));

  // Hide the iframe element.
  self.$iframe.fadeOut('fast');
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
};

/**
 * Resize overlay according to the size of its content.
 *
 * @todo: Watch for experience in the way we compute the size of the
 * iframed document. There are many ways to do it, and none of them
 * seem to be perfect. Note though, that the size of the iframe itself
 * may affect the size of the child document, especially on fluid layouts.
 */
Drupal.overlay.innerResize = function () {
  var self = Drupal.overlay;
  // Proceed only if the dialog still exists.
  if (!(self.isOpen || self.isOpening) || self.isClosing) {
    return;
  }

  var height;
  // Only set height when iframe content is loaded.
  if ($.isObject(self.$iframeBody)) {
    height = self.$iframeBody.outerHeight() + 25;

    // Only resize when height actually is changed.
    if (height != self.lastHeight) {

      // Resize the container.
      self.$container.height(height);
      // Keep the dim background grow or shrink with the dialog.
      $.ui.dialog.overlay.resize();
    }
    self.lastHeight = height;
  }
};

/**
 * Resize overlay according to the size of the parent window.
 */
Drupal.overlay.outerResize = function () {
  var self = Drupal.overlay;
  // Proceed only if the dialog still exists.
  if (!(self.isOpen || self.isOpening) || self.isClosing) {
    return;
  }

  // Consider any region that should be visible above the overlay (such as
  // an admin toolbar).
  var $displaceTop = $('.overlay-displace-top');
  var displaceTopHeight = 0;
  $displaceTop.each(function () {
    displaceTopHeight += $(this).height();
  });

  self.$wrapper.css('top', displaceTopHeight);

  // When the overlay has no height yet make it fit exactly in the window,
  // or the configured height when autoFit is disabled.
  if (!self.lastHeight) {
    var titleBarHeight = self.$dialogTitlebar.outerHeight(true);

    if (self.options.autoFit || self.options.height == undefined ||!isNan(self.options.height)) {
      self.lastHeight = parseInt($(window).height() - displaceTopHeight - titleBarHeight - 45);
    }
    else {
      self.lastHeight = self.options.height;
    }

    self.$container.height(self.lastHeight);
  }

  if (self.options.autoFit) {
    self.innerResize();
  }

  // Make the dim background grow or shrink with the dialog.
  $.ui.dialog.overlay.resize();
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
        }
      };
      Drupal.overlay.open(overlayOptions);
    }
  }
  // If there is no overlay URL in the fragment and the overlay is (still)
  // open, close the overlay.
  else if (Drupal.overlay.isOpen && !Drupal.overlay.isClosing) {
    Drupal.overlay.close();
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
  var path = link.pathname;
  // Ensure a leading slash on the path, omitted in some browsers.
  if (path.substr(0, 1) != '/') {
    path = '/' + path;
  }
  path = path.replace(new RegExp(Drupal.settings.basePath), '');
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
};

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
  return '<iframe id="overlay-element" frameborder="0" name="overlay-element" scrolling="no" allowtransparency="true"/>';
};

/**
 * Theme function to create a container for the overlay iframe element.
 */
Drupal.theme.prototype.overlayContainer = function () {
  return '<div id="overlay-container"/>';
};

/**
 * Theme function for the overlay title markup.
 */
Drupal.theme.prototype.overlayTitleHeader = function (text) {
  return '<h1 id="ui-dialog-title-overlay-container" class="ui-dialog-title" tabindex="-1" unselectable="on">' + text + '</h1>';
};

/**
 * Theme function to create a wrapper for the jquery UI dialog.
 */
Drupal.theme.prototype.overlayWrapper = function () {
  return '<div id="overlay-wrapper"/>';
};

})(jQuery);
