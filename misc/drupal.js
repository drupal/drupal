
/**
 * Only enable Javascript functionality if all required features are supported.
 */
function isJsEnabled() {
  if (typeof document.jsEnabled == 'undefined') {
    // Note: ! casts to boolean implicitly.
    document.jsEnabled = !(
     !document.getElementsByTagName ||
     !document.createElement        ||
     !document.createTextNode       ||
     !document.documentElement      ||
     !document.getElementById);
  }
  return document.jsEnabled;
}

// Global Killswitch on the <html> element
if (isJsEnabled()) {
  document.documentElement.className = 'js';
}

/**
 * Make IE's XMLHTTP object accessible through XMLHttpRequest()
 */
if (typeof XMLHttpRequest == 'undefined') {
  XMLHttpRequest = function () {
    var msxmls = ['MSXML3', 'MSXML2', 'Microsoft']
    for (var i=0; i < msxmls.length; i++) {
      try {
        return new ActiveXObject(msxmls[i]+'.XMLHTTP')
      }
      catch (e) { }
    }
    throw new Error("No XML component installed!");
  }
}

/**
 * Creates an HTTP GET request and sends the response to the callback function.
 *
 * Note that dynamic arguments in the URI should be escaped with encodeURIComponent().
 */
function HTTPGet(uri, callbackFunction, callbackParameter) {
  var xmlHttp = new XMLHttpRequest();
  var bAsync = true;
  if (!callbackFunction) {
    bAsync = false;
  }

  xmlHttp.open('GET', uri, bAsync);
  xmlHttp.send(null);

  if (bAsync) {
    xmlHttp.onreadystatechange = function() {
      if (xmlHttp.readyState == 4) {
        callbackFunction(xmlHttp.responseText, xmlHttp, callbackParameter);
      }
    }
    return xmlHttp;
  }
  else {
    return xmlHttp.responseText;
  }
}

/**
 * Creates an HTTP POST request and sends the response to the callback function
 *
 * Note: passing null or undefined for 'object' makes the request fail in Opera 8.
 *       Pass an empty string instead.
 */
function HTTPPost(uri, callbackFunction, callbackParameter, object) {
  var xmlHttp = new XMLHttpRequest();
  var bAsync = true;
  if (!callbackFunction) {
    bAsync = false;
  }
  xmlHttp.open('POST', uri, bAsync);

  var toSend = '';
  if (typeof object == 'object') {
    xmlHttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    for (var i in object) {
      toSend += (toSend ? '&' : '') + i + '=' + encodeURIComponent(object[i]);
    }
  }
  else {
    toSend = object;
  }
  xmlHttp.send(toSend);

  if (bAsync) {
    xmlHttp.onreadystatechange = function() {
      if (xmlHttp.readyState == 4) {
        callbackFunction(xmlHttp.responseText, xmlHttp, callbackParameter);
      }
    }
    return xmlHttp;
  }
  else {
    return xmlHttp.responseText;
  }
}

/**
 * Redirects a button's form submission to a hidden iframe and displays the result
 * in a given wrapper. The iframe should contain a call to
 * window.parent.iframeHandler() after submission.
 */
function redirectFormButton(uri, button, handler) {
  // (Re)create an iframe to target.
  createIframe();

  // Trap the button
  button.onmouseover = button.onfocus = function() {
    button.onclick = function() {
      // Prepare variables for use in anonymous function.
      var button = this;
      var action = button.form.action;
      var target = button.form.target;

      // Redirect form submission
      this.form.action = uri;
      this.form.target = 'redirect-target';

      handler.onsubmit();

      // Set iframe handler for later
      window.iframeHandler = function () {
        var iframe = $('redirect-target');
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

        $('redirect-target').onload = null;
        $('redirect-target').src = 'about:blank';

        response = parseJson(response);
        // Check response code
        if (response.status == 0) {
          handler.onerror(response.data);
          return;
        }
        handler.oncomplete(response.data);
      }

      return true;
    }
  }
  button.onmouseout = button.onblur = function() {
    button.onclick = null;
  }
}

/**
 * Adds a function to the window onload event
 */
function addLoadEvent(func) {
  var oldOnload = window.onload;
  if (typeof window.onload != 'function') {
    window.onload = func;
  }
  else {
    window.onload = function() {
      oldOnload();
      func();
    }
  }
}

/**
 * Adds a function to a given form's submit event
 */
function addSubmitEvent(form, func) {
  var oldSubmit = form.onsubmit;
  if (typeof oldSubmit != 'function') {
    form.onsubmit = func;
  }
  else {
    form.onsubmit = function() {
      return oldSubmit() && func();
    }
  }
}

/**
 * Retrieves the absolute position of an element on the screen
 */
function absolutePosition(el) {
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
    var tmp = absolutePosition(el.offsetParent);
    r.x += tmp.x;
    r.y += tmp.y;
  }
  return r;
};

function dimensions(el) {
  return { width: el.offsetWidth, height: el.offsetHeight };
}

/**
 * Returns true if an element has a specified class name
 */
function hasClass(node, className) {
  if (node.className == className) {
    return true;
  }
  var reg = new RegExp('(^| )'+ className +'($| )')
  if (reg.test(node.className)) {
    return true;
  }
  return false;
}

/**
 * Adds a class name to an element
 */
function addClass(node, className) {
  if (hasClass(node, className)) {
    return false;
  }
  node.className += ' '+ className;
  return true;
}

/**
 * Removes a class name from an element
 */
function removeClass(node, className) {
  if (!hasClass(node, className)) {
    return false;
  }
  // Replaces words surrounded with whitespace or at a string border with a space. Prevents multiple class names from being glued together.
  node.className = eregReplace('(^|\\s+)'+ className +'($|\\s+)', ' ', node.className);
  return true;
}

/**
 * Toggles a class name on or off for an element
 */
function toggleClass(node, className) {
  if (!removeClass(node, className) && !addClass(node, className)) {
    return false;
  }
  return true;
}

/**
 * Emulate PHP's ereg_replace function in javascript
 */
function eregReplace(search, replace, subject) {
  return subject.replace(new RegExp(search,'g'), replace);
}

/**
 * Removes an element from the page
 */
function removeNode(node) {
  if (typeof node == 'string') {
    node = $(node);
  }
  if (node && node.parentNode) {
    return node.parentNode.removeChild(node);
  }
  else {
    return false;
  }
}

/**
 * Prevents an event from propagating.
 */
function stopEvent(event) {
  if (event.preventDefault) {
    event.preventDefault();
    event.stopPropagation();
  }
  else {
    event.returnValue = false;
    event.cancelBubble = true;
  }
}

/**
 * Parse a JSON response.
 *
 * The result is either the JSON object, or an object with 'status' 0 and 'data' an error message.
 */
function parseJson(data) {
  if (data.substring(0,1) != '{') {
    return { status: 0, data: data.length ? data : 'Unspecified error' };
  }
  return eval('(' + data + ');');
}

/**
 * Create an invisible iframe for form submissions.
 */
function createIframe() {
  // Delete any previous iframe
  deleteIframe();
  // Note: some browsers require the literal name/id attributes on the tag,
  // some want them set through JS. We do both.
  window.iframeHandler = function () {};
  var div = document.createElement('div');
  div.id = 'redirect-holder';
  div.innerHTML = '<iframe name="redirect-target" id="redirect-target" class="redirect" onload="window.iframeHandler();"></iframe>';
  var iframe = div.firstChild;
  with (iframe) {
    name = 'redirect-target';
    setAttribute('name', 'redirect-target');
    id = 'redirect-target';
  }
  with (iframe.style) {
    position = 'absolute';
    height = '1px';
    width = '1px';
    visibility = 'hidden';
  }
  document.body.appendChild(div);
}

/**
 * Delete the invisible iframe for form submissions.
 */
function deleteIframe() {
  var holder = $('redirect-holder');
  if (holder != null) {
    removeNode(holder);
  }
}

/**
 * Wrapper around document.getElementById().
 */
function $(id) {
  return document.getElementById(id);
}
