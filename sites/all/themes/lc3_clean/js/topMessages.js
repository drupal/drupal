/* vim: set ts=2 sw=2 sts=2 et: */

/**
 * Top message controller
 *
 * @author    Creative Development LLC <info@cdev.ru>
 * @copyright Copyright (c) 2010 Creative Development LLC <info@cdev.ru>. All rights reserved
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @version   SVN: $Id: topMessages.js 4779 2010-12-24 07:07:29Z xplorer $
 * @link      http://www.litecommerce.com/
 * @since     3.0.0
 */
var MESSAGE_INFO    = 'status';
var MESSAGE_WARNING = 'warning';
var MESSAGE_ERROR   = 'error';

/**
 * Controller
 */

// Constructor
function TopMessages(container) {
  if (!container) {
    return false;
  }

  this.container = jQuery(container).eq(0);
  if (!this.container.length) {
    return false;
  }

  // Add listeners
  var o = this;

  // Close button
  jQuery('a.close', this.container).click(
    function(event) {
      event.stopPropagation();
      o.clearRecords();

      return false;
    }
  ).hover(
    function() {
      jQuery(this).addClass('close-hover');
    },
    function() {
      jQuery(this).removeClass('close-hover');
    }
  );

  // Global event
  if ('undefined' != typeof(window.core)) {
    core.bind(
      'message',
      function(event, data) {
        return o.messageHandler(data.message, data.type);
      }
    );
  }

  // Remove dump items (W3C compatibility)
  jQuery('li.dump', this.container).remove();

  // Fix position: fixed
  this.msie6 = jQuery.browser.msie && parseInt(jQuery.browser.version) < 7;
  if (this.msie6) {
    this.container.css('position', 'absolute');
    this.container.css('border-style', 'solid');
    jQuery('ul', this.container).css('border-style', 'solid');
  }

  // Initial show
  if (!this.isVisible() && jQuery('li', this.container).length) {
    setTimeout(
      function() {
        o.show();

        // Set initial timers
        jQuery('li.status', o.container).each(
          function() {
            o.setTimer(this);
          }
        );
      },
      1000
    );

  } else {

    // Set initial timers
    jQuery('li.status', this.container).each(
      function() {
        o.setTimer(this);
      }
    );
  }
}

/**
 * Properties
 */
TopMessages.prototype.container = null;
TopMessages.prototype.to = null;

TopMessages.prototype.ttl = 10000;

/**
 * Methods
 */

// Check visibility
TopMessages.prototype.isVisible = function()
{
  return this.container.css('display') != 'none';
}

// Show widget
TopMessages.prototype.show = function()
{
  this.container.slideDown();
}

// Hide widget
TopMessages.prototype.hide = function()
{
  this.container.slideUp();
}

// Add record
TopMessages.prototype.addRecord = function(text, type)
{
  if (
    !type
    || (MESSAGE_INFO != type && MESSAGE_WARNING != type && MESSAGE_ERROR != type)
  ) {
    type = MESSAGE_INFO; 
  }

  var li = document.createElement('LI');
  li.innerHTML = text;
  li.className = type;
  li.style.display = 'none';

  jQuery('ul', this.container).append(li);

  if (
    jQuery('li', this.container).length
    && !this.isVisible()
  ) {
    this.show();
  }

  jQuery(li).slideDown('fast');

  if (type == MESSAGE_INFO) {
    this.setTimer(li);
  }
}

// Clear record
TopMessages.prototype.hideRecord = function(li)
{
  if (jQuery('li:not(.remove)', this.container).length == 1) {
    this.clearRecords();

  } else {
    jQuery(li).addClass('remove').slideUp(
      'fast',
      function() {
        jQuery(this).remove();
      }
    );
  }
}

// Clear all records
TopMessages.prototype.clearRecords = function()
{
  this.hide();
  jQuery('li', this.container).remove();
}

// Set record timer
TopMessages.prototype.setTimer = function(li)
{
  li = jQuery(li).get(0);

  if (li.timer) {
    clearTimeout(li.timer);
    li.timer = false;
  }

  var o = this;
  li.timer = setTimeout(
    function() {
      o.hideRecord(li);
    },
    this.ttl
  );
}

// onmessage event handler
TopMessages.prototype.messageHandler = function(text, type)
{
  this.addRecord(text, type);
}

jQuery(document).ready(function () {
  new TopMessages(jQuery('#status-messages'));
});
