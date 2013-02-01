/**
 * @file toolbar.js
 *
 * Defines the behavior of the Drupal administration toolbar.
 */
(function ($, Drupal, drupalSettings) {

"use strict";

Drupal.toolbar = Drupal.toolbar || {};

/**
 * Store the state of the active tab so it will remain active across page loads.
 */
var activeTab = JSON.parse(localStorage.getItem('Drupal.toolbar.activeTab'));

/**
 * Store the state of the trays to maintain them across page loads.
 */
var locked = JSON.parse(localStorage.getItem('Drupal.toolbar.trayVerticalLocked')) || false;
var orientation = (locked) ? 'vertical' : 'horizontal';

/**
 * Holds the jQuery objects of the toolbar DOM element, the trays and messages.
 */
var $toolbar;
var $trays;
var $messages;

/**
 * Holds the mediaQueryList object.
 */
var mql = {
  standard: null,
  wide: null
};

/**
 * Register tabs with the toolbar.
 *
 * The Drupal toolbar allows modules to register top-level tabs. These may point
 * directly to a resource or toggle the visibility of a tray.
 *
 * Modules register tabs with hook_toolbar().
 */
Drupal.behaviors.toolbar = {
  attach: function(context) {
    var options = $.extend(this.options, drupalSettings.toolbar);
    var $toolbarOnce = $(context).find('#toolbar-administration').once('toolbar');
    if ($toolbarOnce.length) {
      // Assign the $toolbar variable in the closure.
      $toolbar = $toolbarOnce;
      // Add subtrees.
      // @todo Optimize this to delay adding each subtree to the DOM until it is
      //   needed; however, take into account screen readers for determining
      //   when the DOM elements are needed.
      if (Drupal.toolbar.subtrees) {
        for (var id in Drupal.toolbar.subtrees) {
          $('#toolbar-link-' + id).after(Drupal.toolbar.subtrees[id]);
        }
      }
      // Append a messages element for appending interaction updates for screen
      // readers.
      $messages = $(Drupal.theme('toolbarMessageBox')).appendTo($toolbar);
      // Store the trays in a scoped variable.
      $trays = $toolbar.find('.tray');
      $trays
        // Add the tray orientation toggles.
        .find('.lining')
        .append(Drupal.theme('toolbarOrientationToggle'));
      // Store media queries.
      mql.standard = window.matchMedia(options.breakpoints['module.toolbar.standard']);
      // Set up switching between the vertical and horizontal presentation
      // of the toolbar trays based on a breakpoint.
      mql.wide = window.matchMedia(options.breakpoints['module.toolbar.wide']);
      mql.wide.addListener(Drupal.toolbar.mediaQueryChangeHandler);
      // Set the orientation of the tray.
      // If the tray is set to vertical in localStorage, persist the vertical
      // presentation. If the tray is not locked to vertical, let the media
      // query application decide the orientation.
      changeOrientation((locked) ? 'vertical' : ((mql.wide.matches) ? 'horizontal' : 'vertical'), locked);
      // Render the main menu as a nested, collapsible accordion.
      $toolbar.find('.toolbar-menu-administration > .menu').toolbarMenu();
      // Call setHeight on screen resize. Wrap it in debounce to prevent
      // setHeight from being called too frequently.
      var setHeight = Drupal.debounce(Drupal.toolbar.setHeight, 200);
      // Attach behavior to the window.
      $(window)
        .on('resize.toolbar', setHeight);
      // Attach behaviors to the toolbar.
      $toolbar
        .on('click.toolbar', '.bar a', Drupal.toolbar.toggleTray)
        .on('click.toolbar', '.toggle-orientation button', Drupal.toolbar.orientationChangeHandler);
      // Restore the open tab. Only open the tab on wide screens.
      if (activeTab && window.matchMedia(options.breakpoints['module.toolbar.standard']).matches) {
        $toolbar.find('[data-toolbar-tray="' + activeTab + '"]').trigger('click.toolbar');
      }
      else {
        // Update the page and toolbar dimension indicators.
        updatePeripherals();
      }
    }
  },
  // Default options.
  options: {
    breakpoints: {
      'module.toolbar.standard': '',
      'module.toolbar.wide': ''
    }
  }
};

/**
 * Set subtrees.
 *
 * JSONP callback.
 * @see toolbar_subtrees_jsonp().
 */
Drupal.toolbar.setSubtrees = function(subtrees) {
  Drupal.toolbar.subtrees = subtrees;
};

/**
 * Toggle a toolbar tab and the associated tray.
 */
Drupal.toolbar.toggleTray = function (event) {
  var strings = {
    opened: Drupal.t('opened'),
    closed: Drupal.t('closed')
  };
  var $tab = $(event.target);
  var name = $tab.attr('data-toolbar-tray');
  // Activate the selected tab and associated tray.
  var $activateTray = $toolbar.find('[data-toolbar-tray="' + name + '"].tray').toggleClass('active');
  if ($activateTray.length) {
    event.preventDefault();
    event.stopPropagation();
    $tab.toggleClass('active');
    // Toggle aria-pressed.
    var value = $tab.attr('aria-pressed');
    $tab.attr('aria-pressed', (value === 'false') ? 'true' : 'false');
    // Append a message that a tray has been opened.
    setMessage(Drupal.t('@tray tray @state.', {
      '@tray': name,
      '@state': (value === 'true') ? strings.closed : strings.opened
    }));
    // Store the active tab name or remove the setting.
    if ($tab.hasClass('active')) {
      localStorage.setItem('Drupal.toolbar.activeTab', JSON.stringify(name));
    }
    else {
      localStorage.removeItem('Drupal.toolbar.activeTab');
    }
    // Disable non-selected tabs and trays.
    $toolbar.find('.bar .trigger')
      .not($tab)
      .removeClass('active')
      // Set aria-pressed to false.
      .attr('aria-pressed', 'false');
    $toolbar.find('.tray').not($activateTray).removeClass('active');
    // Update the page and toolbar dimension indicators.
    updatePeripherals();
  }
};

/**
 * The height of the toolbar offsets the top of the page content.
 *
 * Page components can register with the offsettopchange event to know when
 * the height of the toolbar changes.
 */
Drupal.toolbar.setHeight = function () {
  // Set the top of the all the trays to the height of the bar.
  var barHeight = $toolbar.find('.bar').outerHeight();
  var height = barHeight;
  var bhpx =  barHeight + 'px';
  var tray;
  for (var i = 0, il = $trays.length; i < il; i++) {
    tray = $trays[i];
    if (!tray.style.top.length || (tray.style.top !== bhpx)) {
      tray.style.top = bhpx;
    }
  }
  /**
   * Get the height of the active tray and include it in the total
   * height of the toolbar.
   */
  height += $trays.filter('.active.horizontal').outerHeight() || 0;
  // Indicate the height of the toolbar in the attribute data-offset-top.
  var offset = parseInt($toolbar.attr('data-offset-top'), 10);
  if (offset !== height) {
    $toolbar.attr('data-offset-top', height);
    // Alter the padding on the top of the body element.
    $('body').css('padding-top', height);
    $(document).trigger('offsettopchange', height);
    $(window).trigger('resize');
  }
};

/**
 * Respond to configured media query applicability changes.
 */
Drupal.toolbar.mediaQueryChangeHandler = function (mql) {
  var orientation = (mql.matches) ? 'horizontal' : 'vertical';
  changeOrientation(orientation);
  // Update the page and toolbar dimension indicators.
  updatePeripherals();
};

/**
 * Respond to the toggling of the tray orientation.
 */
Drupal.toolbar.orientationChangeHandler = function (event) {
  event.preventDefault();
  event.stopPropagation();
  var $button = $(event.target);
  var orientation = event.target.value;
  var $tray = $button.closest('.tray');
  changeOrientation(orientation, true);
  // Update the page and toolbar dimension indicators.
  updatePeripherals();
};

/**
 * Change the orientation of the tray between vertical and horizontal.
 *
 * @param {String} newOrientation
 *   Either 'vertical' or 'horizontal'. The orientation to change the tray to.
 *
 * @param {Boolean} isLock
 *   Whether the orientation of the tray should be locked if it is being toggled
 *   to vertical.
 */
function changeOrientation (newOrientation, isLock) {
  var oldOrientation = orientation;
  if (isLock) {
    locked = (newOrientation === 'vertical');
    if (locked) {
      localStorage.setItem('Drupal.toolbar.trayVerticalLocked', JSON.stringify(locked));
    }
    else {
      localStorage.removeItem('Drupal.toolbar.trayVerticalLocked');
    }
  }
  if ((!locked && newOrientation === 'horizontal') || newOrientation === 'vertical') {
    $trays
      .removeClass('horizontal vertical')
      .addClass(newOrientation);
    orientation = newOrientation;
    toggleOrientationToggle((newOrientation === 'vertical') ? 'horizontal' : 'vertical');
  }
}

/**
 * Mark up the body tag to reflect the current state of the toolbar.
 */
function setBodyState () {
  var $activeTray = $trays.filter('.active');
  $('body')
    .toggleClass('toolbar-tray-open', !!$activeTray.length)
    .toggleClass('toolbar-vertical', (!!$activeTray.length && orientation === 'vertical'))
    .toggleClass('toolbar-horizontal', (!!$activeTray.length && orientation === 'horizontal'));
}

/**
 * Change the orientation toggle active state.
 */
function toggleOrientationToggle (orientation) {
  var strings = {
    horizontal: Drupal.t('Horizontal orientation'),
    vertical: Drupal.t('Vertical orientation')
  };
  var antiOrientation = (orientation === 'vertical') ? 'horizontal' : 'vertical';
  var iconClass = 'icon-toggle-' + orientation;
  var iconAntiClass = 'icon-toggle-' + antiOrientation;
  // Append a message that the tray orientation has been changed.
  setMessage(Drupal.t('Tray orientation changed to @orientation.', {
    '@orientation': antiOrientation
  }));
  // Change the tray orientation.
  $trays.find('.toggle-orientation button')
    .val(orientation)
    .text(strings[orientation])
    .removeClass(iconAntiClass)
    .addClass(iconClass);
}

/**
 * Updates elements peripheral to the toolbar.
 *
 * When the dimensions and orientation of the toolbar change, elements on the
 * page must either be changed or informed of the changes.
 */
function updatePeripherals () {
  // Adjust the body to accommodate trays.
  setBodyState();
  // Adjust the height of the toolbar.
  Drupal.toolbar.setHeight();
}

/**
 * Places the message in the toolbar's ARIA live message area.
 *
 * The message will be read by speaking User Agents.
 *
 * @param {String} message
 *   A string to be inserted into the message area.
 */
function setMessage (message) {
  $messages.html(Drupal.theme('toolbarTrayMessage', message));
}

/**
 * A toggle is an interactive element often bound to a click handler.
 *
 * @return {String}
 *   A string representing a DOM fragment.
 */
Drupal.theme.toolbarOrientationToggle = function () {
  return '<div class="toggle-orientation"><div class="lining">' +
    '<button class="icon" type="button"></button>' +
    '</div></div>';
};

/**
 * A region to post messages that a screen reading UA will announce.
 *
 * @return {String}
 *   A string representing a DOM fragment.
 */
Drupal.theme.toolbarMessageBox = function () {
  return '<div id="toolbar-messages" class="element-invisible" role="region" aria-live="polite"></div>';
};

/**
 * Wrap a message string in a p tag.
 *
 * @return {String}
 *   A string representing a DOM fragment.
 */
Drupal.theme.toolbarTrayMessage = function (message) {
  return '<p>' + message + '</p>';
};

}(jQuery, Drupal, drupalSettings));
