// $Id: drupal.js,v 1.35 2007/07/01 15:37:08 dries Exp $

var Drupal = Drupal || { 'settings': {}, 'behaviors': {}, 'themes': {}, 'locale': {} };

/**
 * Set the variable that indicates if JavaScript behaviors should be applied
 */
Drupal.jsEnabled = document.getElementsByTagName && document.createElement && document.createTextNode && document.documentElement && document.getElementById;

/**
 * Extends the current object with the parameter. Works recursively.
 */
Drupal.extend = function(obj) {
  for (var i in obj) {
    if (this[i]) {
      Drupal.extend.apply(this[i], [obj[i]]);
    }
    else {
      this[i] = obj[i];
    }
  }
};

/**
 * Attach all registered behaviors to a page element.
 *
 * Behaviors are event-triggered actions that attach to page elements, enhancing
 * default non-Javascript UIs. Behaviors are registered in the Drupal.behaviors
 * object as follows:
 * @code
 *    Drupal.behaviors.behaviorName = function () {
 *      ...
 *    };
 * @endcode
 *
 * Drupal.attachBehaviors is added below to the jQuery ready event and so
 * runs on initial page load. Developers implementing AHAH/AJAX in their
 * solutions should also call this function after new page content has been
 * loaded, feeding in an element to be processed, in order to attach all
 * behaviors to the new content.
 *
 * Behaviors should use a class in the form behaviorName-processed to ensure
 * the behavior is attached only once to a given element. (Doing so enables
 * the reprocessing of given elements, which may be needed on occasion despite
 * the ability to limit behavior attachment to a particular element.)
 *
 * @param context
 *   An element to attach behaviors to. If none is given, the document element
 *   is used.
 */
Drupal.attachBehaviors = function(context) {
  context = context || document;
  if (Drupal.jsEnabled) {
    // Execute all of them.
    jQuery.each(Drupal.behaviors, function() {
      this(context);
    });
  }
};

/**
 * Encode special characters in a plain-text string for display as HTML.
 */
Drupal.checkPlain = function(str) {
  str = String(str);
  var replace = { '&': '&amp;', '"': '&quot;', '<': '&lt;', '>': '&gt;' };
  for (var character in replace) {
    str = str.replace(character, replace[character]);
  }
  return str;
};

/**
 * Translate strings to the page language or a given language.
 *
 * See the documentation of the server-side t() function for further details.
 *
 * @param str
 *   A string containing the English string to translate.
 * @param args
 *   An object of replacements pairs to make after translation. Incidences
 *   of any key in this array are replaced with the corresponding value.
 *   Based on the first character of the key, the value is escaped and/or themed:
 *    - !variable: inserted as is
 *    - @variable: escape plain text to HTML (Drupal.checkPlain)
 *    - %variable: escape text and theme as a placeholder for user-submitted
 *      content (checkPlain + Drupal.theme('placeholder'))
 * @return
 *   The translated string.
 */
Drupal.t = function(str, args) {
  // Fetch the localized version of the string.
  if (Drupal.locale.strings && Drupal.locale.strings[str]) {
    str = Drupal.locale.strings[str];
  }

  if (args) {
    // Transform arguments before inserting them
    for (var key in args) {
      switch (key.charAt(0)) {
        // Escaped only
        case '@':
          args[key] = Drupal.checkPlain(args[key]);
        break;
        // Pass-through
        case '!':
          break;
        // Escaped and placeholder
        case '%':
        default:
          args[key] = Drupal.theme('placeholder', args[key]);
          break;
      }
      str = str.replace(key, args[key]);
    }
  }
  return str;
};

/**
 * Format a string containing a count of items.
 *
 * This function ensures that the string is pluralized correctly. Since Drupal.t() is
 * called by this function, make sure not to pass already-localized strings to it.
 *
 * See the documentation of the server-side format_plural() function for further details.
 *
 * @param count
 *   The item count to display.
 * @param singular
 *   The string for the singular case. Please make sure it is clear this is
 *   singular, to ease translation (e.g. use "1 new comment" instead of "1 new").
 *   Do not use @count in the singular string.
 * @param plural
 *   The string for the plural case. Please make sure it is clear this is plural,
 *   to ease translation. Use @count in place of the item count, as in "@count
 *   new comments".
 * @param args
 *   An object of replacements pairs to make after translation. Incidences
 *   of any key in this array are replaced with the corresponding value.
 *   Based on the first character of the key, the value is escaped and/or themed:
 *    - !variable: inserted as is
 *    - @variable: escape plain text to HTML (Drupal.checkPlain)
 *    - %variable: escape text and theme as a placeholder for user-submitted
 *      content (checkPlain + Drupal.theme('placeholder'))
 *   Note that you do not need to include @count in this array.
 *   This replacement is done automatically for the plural case.
 * @return
 *   A translated string.
 */
Drupal.formatPlural = function(count, singular, plural, args) {
  var args = ars || {};
  args['@count'] = count;
  // Determine the index of the plural form.
  var index = Drupal.locale.pluralFormula ? Drupal.locale.pluralFormula(args['@count']) : ((args['@count'] == 1) ? 0 : 1);

  if (index == 0) {
    return Drupal.t(singular, args);
  }
  else if (index == 1) {
    return Drupal.t(plural, args);
  }
  else {
    args['@count['+ index +']'] = args['@count'];
    delete args['@count'];
    return Drupal.t(plural.replace('@count', '@count['+ index +']'));
  }
};

/**
 * Generate the themed representation of a Drupal object.
 *
 * All requests for themed output must go through this function. It examines
 * the request and routes it to the appropriate theme function. If the current
 * theme does not provide an override function, the generic theme function is
 * called.
 *
 * For example, to retrieve the HTML that is output by theme_placeholder(text),
 * call Drupal.theme('placeholder', text).
 *
 * @param func
 *   The name of the theme function to call.
 * @param ...
 *   Additional arguments to pass along to the theme function.
 * @return
 *   Any data the theme function returns. This could be a plain HTML string,
 *   but also a complex object.
 */
Drupal.theme = function(func) {
  for (var i = 1, args = []; i < arguments.length; i++) {
    args.push(arguments[i]);
  }

  return (Drupal.theme[func] || Drupal.theme.prototype[func]).apply(this, args);
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
      };

      return true;
    };
  };
  button.onmouseout = button.onblur = function() {
    button.onclick = null;
  };
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
    return { status: 0, data: data.length ? data : Drupal.t('Unspecified error') };
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
  item = encodeURIComponent(item).replace('%2F', '/');
  return uri.indexOf('?q=') ? item : item.replace('%26', '%2526').replace('%23', '%2523');
};

/**
 * Get the text selection in a textarea.
 */
Drupal.getSelection = function (element) {
  if (typeof(element.selectionStart) != 'number' && document.selection) {
    // The current selection
    var range1 = document.selection.createRange();
    var range2 = range1.duplicate();
    // Select all text.
    range2.moveToElementText(element);
    // Now move 'dummy' end point to end point of original range.
    range2.setEndPoint('EndToEnd', range1);
    // Now we can calculate start and end points.
    var start = range2.text.length - range1.text.length;
    var end = start + range1.text.length;
    return { 'start': start, 'end': end };
  }
  return { 'start': element.selectionStart, 'end': element.selectionEnd };
};

// Global Killswitch on the <html> element
if (Drupal.jsEnabled) {
  // Global Killswitch on the <html> element
  document.documentElement.className = 'js';
  // 'js enabled' cookie
  document.cookie = 'has_js=1';
  // Attach all behaviors.
  $(document).ready(Drupal.attachBehaviors);
}

/**
 * The default themes.
 */
Drupal.theme.prototype = {

  /**
   * Formats text for emphasized display in a placeholder inside a sentence.
   *
   * @param str
   *   The text to format (plain-text).
   * @return
   *   The formatted text (html).
   */
  placeholder: function(str) {
    return '<em>' + Drupal.checkPlain(str) + '</em>';
  }
};
