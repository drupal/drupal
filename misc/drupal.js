
var Drupal = Drupal || {};

/**
 * Set the variable that indicates if JavaScript behaviors should be applied
 */
Drupal.jsEnabled = document.getElementsByTagName && document.createElement && document.createTextNode && document.documentElement && document.getElementById;

/**
 * Extends the current object with the parameter. Works recursively.
 */
Drupal.extend = function(obj) {
  for (var i in obj) {
    if (this[i] && (typeof(this[i]) == 'function' || typeof(this[i]) == 'object')) {
   	  Drupal.extend.apply(this[i], [obj[i]]);
    }
    else {
      this[i] = obj[i];
    }
  }
};

/**
 * Redirects a button's form submission to a hidden iframe and displays the result
 * in a given wrapper. The iframe should contain a call to
 * window.parent.iframeHandler() after submission.
 */
Drupal.redirectFormButton = function (uri, button, handler) {
  // Trap the button
  button.onmouseover = button.onfocus = function() {
    button.onclick = function() {
      // Create target iframe
      Drupal.createIframe();

      // Prepare variables for use in anonymous function.
      var button = this;
      var action = button.form.action;
      var target = button.form.target;

      // Redirect form submission to iframe
      this.form.action = uri;
      this.form.target = 'redirect-target';

      handler.onsubmit();

      // Set iframe handler for later
      window.iframeHandler = function () {
        var iframe = $('#redirect-target').get(0);
        // Restore form submission
        button.form.action = action;
        button.form.target = target;

        // Get response from iframe body
        try {
          response = (iframe.contentWindow || iframe.contentDocument || iframe).document.body.innerHTML;
          // Firefox 1.0.x hack: Remove (corrupted) control characters
          response = response.replace(/[\f\n\r\t]/g, ' ');
          if (window.opera) {
            // Opera-hack: it returns innerHTML sanitized.
            response = response.replace(/&quot;/g, '"');
          }
        }
        catch (e) {
          response = null;
        }

        response = Drupal.parseJson(response);
        // Check response code
        if (response.status == 0) {
          handler.onerror(response.data);
          return;
        }
        handler.oncomplete(response.data);

        return true;
      }

      return true;
    }
  }
  button.onmouseout = button.onblur = function() {
    button.onclick = null;
  }
};

/**
 * Retrieves the absolute position of an element on the screen
 */
Drupal.absolutePosition = function (el) {
  var sLeft = 0, sTop = 0;
  var isDiv = /^div$/i.test(el.tagName);
  if (isDiv && el.scrollLeft) {
    sLeft = el.scrollLeft;
  }
  if (isDiv && el.scrollTop) {
    sTop = el.scrollTop;
  }
  var r = { x: el.offsetLeft - sLeft, y: el.offsetTop - sTop };
  if (el.offsetParent) {
    var tmp = Drupal.absolutePosition(el.offsetParent);
    r.x += tmp.x;
    r.y += tmp.y;
  }
  return r;
};

/**
 * Return the dimensions of an element on the screen
 */
Drupal.dimensions = function (el) {
  return { width: el.offsetWidth, height: el.offsetHeight };
};

/**
 *  Returns the position of the mouse cursor based on the event object passed
 */
Drupal.mousePosition = function(e) {
  return { x: e.clientX + document.documentElement.scrollLeft, y: e.clientY + document.documentElement.scrollTop };
};

/**
 * Parse a JSON response.
 *
 * The result is either the JSON object, or an object with 'status' 0 and 'data' an error message.
 */
Drupal.parseJson = function (data) {
  if ((data.substring(0, 1) != '{') && (data.substring(0, 1) != '[')) {
    return { status: 0, data: data.length ? data : 'Unspecified error' };
  }
  return eval('(' + data + ');');
};

/**
 * Create an invisible iframe for form submissions.
 */
Drupal.createIframe = function () {
  if ($('#redirect-holder').size()) {
    return;
  }
  // Note: some browsers require the literal name/id attributes on the tag,
  // some want them set through JS. We do both.
  window.iframeHandler = function () {};
  var div = document.createElement('div');
  div.id = 'redirect-holder';
  $(div).html('<iframe name="redirect-target" id="redirect-target" class="redirect" onload="window.iframeHandler();"></iframe>');
  var iframe = div.firstChild;
  $(iframe)
    .attr({
      name: 'redirect-target',
      id: 'redirect-target'
    })
    .css({
      position: 'absolute',
      height: '1px',
      width: '1px',
      visibility: 'hidden'
    });
  $('body').append(div);
};

/**
 * Delete the invisible iframe
 */
Drupal.deleteIframe = function () {
  $('#redirect-holder').remove();
};

/**
 * Freeze the current body height (as minimum height). Used to prevent
 * unnecessary upwards scrolling when doing DOM manipulations.
 */
Drupal.freezeHeight = function () {
  Drupal.unfreezeHeight();
  var div = document.createElement('div');
  $(div).css({
    position: 'absolute',
    top: '0px',
    left: '0px',
    width: '1px',
    height: $('body').css('height')
  }).attr('id', 'freeze-height');
  $('body').append(div);
};

/**
 * Unfreeze the body height
 */
Drupal.unfreezeHeight = function () {
  $('#freeze-height').remove();
};

/**
 * Wrapper to address the mod_rewrite url encoding bug
 * (equivalent of drupal_urlencode() in PHP).
 */
Drupal.encodeURIComponent = function (item, uri) {
  uri = uri || location.href;
  item = encodeURIComponent(item).replace(/%2F/g, '/');
  return (uri.indexOf('?q=') != -1) ? item : item.replace(/%26/g, '%2526').replace(/%23/g, '%2523').replace(/\/\//g, '/%252F');
};

// Global Killswitch on the <html> element
if (Drupal.jsEnabled) {
  $(document.documentElement).addClass('js');
}
