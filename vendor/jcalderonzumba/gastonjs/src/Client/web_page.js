var __slice = [].slice;
var __indexOf = [].indexOf || function (item) {
    for (var i = 0, l = this.length; i < l; i++) {
      if (i in this && this[i] === item) return i;
    }
    return -1;
  };

Poltergeist.WebPage = (function () {
  var command, delegate, commandFunctionBind, delegateFunctionBind, i, j, commandsLength, delegatesRefLength, commandsRef, delegatesRef,
    _this = this;

  //Native or not webpage callbacks
  WebPage.CALLBACKS = ['onAlert', 'onConsoleMessage', 'onLoadFinished', 'onInitialized', 'onLoadStarted', 'onResourceRequested',
    'onResourceReceived', 'onError', 'onNavigationRequested', 'onUrlChanged', 'onPageCreated', 'onClosing'];

  // Delegates the execution to the phantomjs page native functions but directly available in the WebPage object
  WebPage.DELEGATES = ['open', 'sendEvent', 'uploadFile', 'release', 'render', 'renderBase64', 'goBack', 'goForward', 'reload'];

  //Commands to execute on behalf of the browser but on the current page
  WebPage.COMMANDS = ['currentUrl', 'find', 'nodeCall', 'documentSize', 'beforeUpload', 'afterUpload', 'clearLocalStorage'];

  WebPage.EXTENSIONS = [];

  function WebPage(nativeWebPage) {
    var callback, i, callBacksLength, callBacksRef;

    //Lets create the native phantomjs webpage
    if (nativeWebPage === null || typeof nativeWebPage == "undefined") {
      this._native = require('webpage').create();
    } else {
      this._native = nativeWebPage;
    }

    this.id = 0;
    this.source = null;
    this.closed = false;
    this.state = 'default';
    this.urlBlacklist = [];
    this.frames = [];
    this.errors = [];
    this._networkTraffic = {};
    this._tempHeaders = {};
    this._blockedUrls = [];

    callBacksRef = WebPage.CALLBACKS;
    for (i = 0, callBacksLength = callBacksRef.length; i < callBacksLength; i++) {
      callback = callBacksRef[i];
      this.bindCallback(callback);
    }
  }

  //Bind the commands we can run from the browser to the current page
  commandsRef = WebPage.COMMANDS;
  commandFunctionBind = function (command) {
    return WebPage.prototype[command] = function () {
      var args;
      args = 1 <= arguments.length ? __slice.call(arguments, 0) : [];
      return this.runCommand(command, args);
    };
  };
  for (i = 0, commandsLength = commandsRef.length; i < commandsLength; i++) {
    command = commandsRef[i];
    commandFunctionBind(command);
  }

  //Delegates bind applications
  delegatesRef = WebPage.DELEGATES;
  delegateFunctionBind = function (delegate) {
    return WebPage.prototype[delegate] = function () {
      return this._native[delegate].apply(this._native, arguments);
    };
  };
  for (j = 0, delegatesRefLength = delegatesRef.length; j < delegatesRefLength; j++) {
    delegate = delegatesRef[j];
    delegateFunctionBind(delegate);
  }

  /**
   * This callback is invoked after the web page is created but before a URL is loaded.
   * The callback may be used to change global objects.
   * @return {*}
   */
  WebPage.prototype.onInitializedNative = function () {
    this.id += 1;
    this.source = null;
    this.injectAgent();
    this.removeTempHeaders();
    return this.setScrollPosition({
      left: 0,
      top: 0
    });
  };

  /**
   * This callback is invoked when the WebPage object is being closed,
   * either via page.close in the PhantomJS outer space or via window.close in the page's client-side.
   * @return {boolean}
   */
  WebPage.prototype.onClosingNative = function () {
    this.handle = null;
    return this.closed = true;
  };

  /**
   * This callback is invoked when there is a JavaScript console message on the web page.
   * The callback may accept up to three arguments: the string for the message, the line number, and the source identifier.
   * @param message
   * @param line
   * @param sourceId
   * @return {boolean}
   */
  WebPage.prototype.onConsoleMessageNative = function (message, line, sourceId) {
    if (message === '__DOMContentLoaded') {
      this.source = this._native.content;
      return false;
    }
    console.log(message);
    return true;
  };

  /**
   * This callback is invoked when the page starts the loading. There is no argument passed to the callback.
   * @return {number}
   */
  WebPage.prototype.onLoadStartedNative = function () {
    this.state = 'loading';
    return this.requestId = this.lastRequestId;
  };

  /**
   * This callback is invoked when the page finishes the loading.
   * It may accept a single argument indicating the page's status: 'success' if no network errors occurred, otherwise 'fail'.
   * @param status
   * @return {string}
   */
  WebPage.prototype.onLoadFinishedNative = function (status) {
    this.status = status;
    this.state = 'default';

    if (this.source === null || typeof this.source == "undefined") {
      this.source = this._native.content;
    } else {
      this.source = this._native.content;
    }

    return this.source;
  };

  /**
   * This callback is invoked when there is a JavaScript execution error.
   * It is a good way to catch problems when evaluating a script in the web page context.
   * The arguments passed to the callback are the error message and the stack trace [as an Array].
   * @param message
   * @param stack
   * @return {Number}
   */
  WebPage.prototype.onErrorNative = function (message, stack) {
    var stackString;

    stackString = message;
    stack.forEach(function (frame) {
      stackString += "\n";
      stackString += "    at " + frame.file + ":" + frame.line;
      if (frame["function"] && frame["function"] !== '') {
        return stackString += " in " + frame["function"];
      }
    });

    return this.errors.push({
      message: message,
      stack: stackString
    });
  };

  /**
   * This callback is invoked when the page requests a resource.
   * The first argument to the callback is the requestData metadata object.
   * The second argument is the networkRequest object itself.
   * @param requestData
   * @param networkRequest
   * @return {*}
   */
  WebPage.prototype.onResourceRequestedNative = function (requestData, networkRequest) {
    var abort;

    abort = this.urlBlacklist.some(function (blacklistedUrl) {
      return requestData.url.indexOf(blacklistedUrl) !== -1;
    });

    if (abort) {
      if (this._blockedUrls.indexOf(requestData.url) === -1) {
        this._blockedUrls.push(requestData.url);
      }
      //TODO: check this, as it raises onResourceError
      return networkRequest.abort();
    }

    this.lastRequestId = requestData.id;
    if (requestData.url === this.redirectURL) {
      this.redirectURL = null;
      this.requestId = requestData.id;
    }

    return this._networkTraffic[requestData.id] = {
      request: requestData,
      responseParts: []
    };
  };

  /**
   * This callback is invoked when a resource requested by the page is received.
   * The only argument to the callback is the response metadata object.
   * @param response
   * @return {*}
   */
  WebPage.prototype.onResourceReceivedNative = function (response) {
    var networkTrafficElement;

    if ((networkTrafficElement = this._networkTraffic[response.id]) != null) {
      networkTrafficElement.responseParts.push(response);
    }

    if (this.requestId === response.id) {
      if (response.redirectURL) {
        return this.redirectURL = response.redirectURL;
      }

      this.statusCode = response.status;
      return this._responseHeaders = response.headers;
    }
  };

  /**
   * Inject the poltergeist agent into the webpage
   * @return {Array}
   */
  WebPage.prototype.injectAgent = function () {
    var extension, isAgentInjected, i, extensionsRefLength, extensionsRef, injectionResults;

    isAgentInjected = this["native"]().evaluate(function () {
      return typeof window.__poltergeist;
    });

    if (isAgentInjected === "undefined") {
      this["native"]().injectJs("" + phantom.libraryPath + "/agent.js");
      extensionsRef = WebPage.EXTENSIONS;
      injectionResults = [];
      for (i = 0, extensionsRefLength = extensionsRef.length; i < extensionsRefLength; i++) {
        extension = extensionsRef[i];
        injectionResults.push(this["native"]().injectJs(extension));
      }
      return injectionResults;
    }
  };

  /**
   * Injects a Javascript file extension into the
   * @param file
   * @return {*}
   */
  WebPage.prototype.injectExtension = function (file) {
    //TODO: add error control, for example, check if file already in the extensions array, check if the file exists, etc.
    WebPage.EXTENSIONS.push(file);
    return this["native"]().injectJs(file);
  };

  /**
   * Returns the native phantomjs webpage object
   * @return {*}
   */
  WebPage.prototype["native"] = function () {
    if (this.closed) {
      throw new Poltergeist.NoSuchWindowError;
    }

    return this._native;
  };

  /**
   * Returns the current page window name
   * @return {*}
   */
  WebPage.prototype.windowName = function () {
    return this["native"]().windowName;
  };

  /**
   * Returns the keyCode of a given key as set in the phantomjs values
   * @param name
   * @return {number}
   */
  WebPage.prototype.keyCode = function (name) {
    return this["native"]().event.key[name];
  };

  /**
   * Waits for the page to reach a certain state
   * @param state
   * @param callback
   * @return {*}
   */
  WebPage.prototype.waitState = function (state, callback) {
    var self = this;
    if (this.state === state) {
      return callback.call();
    } else {
      return setTimeout((function () {
        return self.waitState(state, callback);
      }), 100);
    }
  };

  /**
   * Sets the browser header related to basic authentication protocol
   * @param user
   * @param password
   * @return {boolean}
   */
  WebPage.prototype.setHttpAuth = function (user, password) {
    var allHeaders = this.getCustomHeaders();

    if (user === false || password === false) {
      if (allHeaders.hasOwnProperty("Authorization")) {
        delete allHeaders["Authorization"];
      }
      this.setCustomHeaders(allHeaders);
      return true;
    }

    var userName = user || "";
    var userPassword = password || "";

    allHeaders["Authorization"] = "Basic " + btoa(userName + ":" + userPassword);
    this.setCustomHeaders(allHeaders);
    return true;
  };

  /**
   * Returns all the network traffic associated to the rendering of this page
   * @return {{}}
   */
  WebPage.prototype.networkTraffic = function () {
    return this._networkTraffic;
  };

  /**
   * Clears all the recorded network traffic related to the current page
   * @return {{}}
   */
  WebPage.prototype.clearNetworkTraffic = function () {
    return this._networkTraffic = {};
  };

  /**
   * Returns the blocked urls that the page will not load
   * @return {Array}
   */
  WebPage.prototype.blockedUrls = function () {
    return this._blockedUrls;
  };

  /**
   * Clean all the urls that should not be loaded
   * @return {Array}
   */
  WebPage.prototype.clearBlockedUrls = function () {
    return this._blockedUrls = [];
  };

  /**
   * This property stores the content of the web page's currently active frame
   * (which may or may not be the main frame), enclosed in an HTML/XML element.
   * @return {string}
   */
  WebPage.prototype.content = function () {
    return this["native"]().frameContent;
  };

  /**
   * Returns the current active frame title
   * @return {string}
   */
  WebPage.prototype.title = function () {
    return this["native"]().frameTitle;
  };

  /**
   * Returns if possible the frame url of the frame given by name
   * @param frameName
   * @return {string}
   */
  WebPage.prototype.frameUrl = function (frameName) {
    var query;

    query = function (frameName) {
      var iframeReference;
      if ((iframeReference = document.querySelector("iframe[name='" + frameName + "']")) != null) {
        return iframeReference.src;
      }
      return void 0;
    };

    return this.evaluate(query, frameName);
  };

  /**
   * Remove the errors caught on the page
   * @return {Array}
   */
  WebPage.prototype.clearErrors = function () {
    return this.errors = [];
  };

  /**
   * Returns the response headers associated to this page
   * @return {{}}
   */
  WebPage.prototype.responseHeaders = function () {
    var headers;
    headers = {};
    this._responseHeaders.forEach(function (item) {
      return headers[item.name] = item.value;
    });
    return headers;
  };

  /**
   * Get Cookies visible to the current URL (though, for setting, use of page.addCookie is preferred).
   * This array will be pre-populated by any existing Cookie data visible to this URL that is stored in the CookieJar, if any.
   * @return {*}
   */
  WebPage.prototype.cookies = function () {
    return this["native"]().cookies;
  };

  /**
   * Delete any Cookies visible to the current URL with a 'name' property matching cookieName.
   * Returns true if successfully deleted, otherwise false.
   * @param name
   * @return {*}
   */
  WebPage.prototype.deleteCookie = function (name) {
    return this["native"]().deleteCookie(name);
  };

  /**
   * This property gets the size of the viewport for the layout process.
   * @return {*}
   */
  WebPage.prototype.viewportSize = function () {
    return this["native"]().viewportSize;
  };

  /**
   * This property sets the size of the viewport for the layout process.
   * @param size
   * @return {*}
   */
  WebPage.prototype.setViewportSize = function (size) {
    return this["native"]().viewportSize = size;
  };

  /**
   * This property specifies the scaling factor for the page.render and page.renderBase64 functions.
   * @param zoomFactor
   * @return {*}
   */
  WebPage.prototype.setZoomFactor = function (zoomFactor) {
    return this["native"]().zoomFactor = zoomFactor;
  };

  /**
   * This property defines the size of the web page when rendered as a PDF.
   * See: http://phantomjs.org/api/webpage/property/paper-size.html
   * @param size
   * @return {*}
   */
  WebPage.prototype.setPaperSize = function (size) {
    return this["native"]().paperSize = size;
  };

  /**
   * This property gets the scroll position of the web page.
   * @return {*}
   */
  WebPage.prototype.scrollPosition = function () {
    return this["native"]().scrollPosition;
  };

  /**
   * This property defines the scroll position of the web page.
   * @param pos
   * @return {*}
   */
  WebPage.prototype.setScrollPosition = function (pos) {
    return this["native"]().scrollPosition = pos;
  };


  /**
   * This property defines the rectangular area of the web page to be rasterized when page.render is invoked.
   * If no clipping rectangle is set, page.render will process the entire web page.
   * @return {*}
   */
  WebPage.prototype.clipRect = function () {
    return this["native"]().clipRect;
  };

  /**
   * This property defines the rectangular area of the web page to be rasterized when page.render is invoked.
   * If no clipping rectangle is set, page.render will process the entire web page.
   * @param rect
   * @return {*}
   */
  WebPage.prototype.setClipRect = function (rect) {
    return this["native"]().clipRect = rect;
  };

  /**
   * Returns the size of an element given by a selector and its position relative to the viewport.
   * @param selector
   * @return {Object}
   */
  WebPage.prototype.elementBounds = function (selector) {
    return this["native"]().evaluate(function (selector) {
      return document.querySelector(selector).getBoundingClientRect();
    }, selector);
  };

  /**
   * Defines the user agent sent to server when the web page requests resources.
   * @param userAgent
   * @return {*}
   */
  WebPage.prototype.setUserAgent = function (userAgent) {
    return this["native"]().settings.userAgent = userAgent;
  };

  /**
   * Returns the additional HTTP request headers that will be sent to the server for EVERY request.
   * @return {{}}
   */
  WebPage.prototype.getCustomHeaders = function () {
    return this["native"]().customHeaders;
  };

  /**
   * Gets the additional HTTP request headers that will be sent to the server for EVERY request.
   * @param headers
   * @return {*}
   */
  WebPage.prototype.setCustomHeaders = function (headers) {
    return this["native"]().customHeaders = headers;
  };

  /**
   * Adds a one time only request header, after being used it will be deleted
   * @param header
   * @return {Array}
   */
  WebPage.prototype.addTempHeader = function (header) {
    var name, value, tempHeaderResult;
    tempHeaderResult = [];
    for (name in header) {
      if (header.hasOwnProperty(name)) {
        value = header[name];
        tempHeaderResult.push(this._tempHeaders[name] = value);
      }
    }
    return tempHeaderResult;
  };

  /**
   * Remove the temporary headers we have set via addTempHeader
   * @return {*}
   */
  WebPage.prototype.removeTempHeaders = function () {
    var allHeaders, name, value, tempHeadersRef;
    allHeaders = this.getCustomHeaders();
    tempHeadersRef = this._tempHeaders;
    for (name in tempHeadersRef) {
      if (tempHeadersRef.hasOwnProperty(name)) {
        value = tempHeadersRef[name];
        delete allHeaders[name];
      }
    }

    return this.setCustomHeaders(allHeaders);
  };

  /**
   * If possible switch to the frame given by name
   * @param name
   * @return {boolean}
   */
  WebPage.prototype.pushFrame = function (name) {
    if (this["native"]().switchToFrame(name)) {
      this.frames.push(name);
      return true;
    }
    return false;
  };

  /**
   * Switch to parent frame, use with caution:
   * popFrame assumes you are in frame, pop frame not being in a frame
   * leaves unexpected behaviour
   * @return {*}
   */
  WebPage.prototype.popFrame = function () {
    //TODO: add some error control here, some way to check we are in a frame or not
    this.frames.pop();
    return this["native"]().switchToParentFrame();
  };

  /**
   * Returns the webpage dimensions
   * @return {{top: *, bottom: *, left: *, right: *, viewport: *, document: {height: number, width: number}}}
   */
  WebPage.prototype.dimensions = function () {
    var scroll, viewport;
    scroll = this.scrollPosition();
    viewport = this.viewportSize();
    return {
      top: scroll.top,
      bottom: scroll.top + viewport.height,
      left: scroll.left,
      right: scroll.left + viewport.width,
      viewport: viewport,
      document: this.documentSize()
    };
  };

  /**
   * Returns webpage dimensions that are valid
   * @return {{top: *, bottom: *, left: *, right: *, viewport: *, document: {height: number, width: number}}}
   */
  WebPage.prototype.validatedDimensions = function () {
    var dimensions, documentDimensions;

    dimensions = this.dimensions();
    documentDimensions = dimensions.document;

    if (dimensions.right > documentDimensions.width) {
      dimensions.left = Math.max(0, dimensions.left - (dimensions.right - documentDimensions.width));
      dimensions.right = documentDimensions.width;
    }

    if (dimensions.bottom > documentDimensions.height) {
      dimensions.top = Math.max(0, dimensions.top - (dimensions.bottom - documentDimensions.height));
      dimensions.bottom = documentDimensions.height;
    }

    this.setScrollPosition({
      left: dimensions.left,
      top: dimensions.top
    });

    return dimensions;
  };

  /**
   * Returns a Poltergeist.Node given by an id
   * @param id
   * @return {Poltergeist.Node}
   */
  WebPage.prototype.get = function (id) {
    return new Poltergeist.Node(this, id);
  };

  /**
   * Executes a phantomjs mouse event, for more info check: http://phantomjs.org/api/webpage/method/send-event.html
   * @param name
   * @param x
   * @param y
   * @param button
   * @return {*}
   */
  WebPage.prototype.mouseEvent = function (name, x, y, button) {
    if (button == null) {
      button = 'left';
    }
    this.sendEvent('mousemove', x, y);
    return this.sendEvent(name, x, y, button);
  };

  /**
   * Evaluates a javascript and returns the evaluation of such script
   * @return {*}
   */
  WebPage.prototype.evaluate = function () {
    var args, fn;
    fn = arguments[0];
    args = [];

    if (2 <= arguments.length) {
      args = __slice.call(arguments, 1);
    }

    this.injectAgent();
    return JSON.parse(this.sanitize(this["native"]().evaluate("function() { return PoltergeistAgent.stringify(" + (this.stringifyCall(fn, args)) + ") }")));
  };

  /**
   * Does some string sanitation prior parsing
   * @param potentialString
   * @return {*}
   */
  WebPage.prototype.sanitize = function (potentialString) {
    if (typeof potentialString === "string") {
      return potentialString.replace("\n", "\\n").replace("\r", "\\r");
    }

    return potentialString;
  };

  /**
   * Executes a script into the current page scope
   * @param script
   * @return {*}
   */
  WebPage.prototype.executeScript = function (script) {
    return this["native"]().evaluateJavaScript(script);
  };

  /**
   * Executes a script via phantomjs evaluation
   * @return {*}
   */
  WebPage.prototype.execute = function () {
    var args, fn;

    fn = arguments[0];
    args = [];

    if (2 <= arguments.length) {
      args = __slice.call(arguments, 1);
    }

    return this["native"]().evaluate("function() { " + (this.stringifyCall(fn, args)) + " }");
  };

  /**
   * Helper methods to do script evaluation and execution
   * @param fn
   * @param args
   * @return {string}
   */
  WebPage.prototype.stringifyCall = function (fn, args) {
    if (args.length === 0) {
      return "(" + (fn.toString()) + ")()";
    }

    return "(" + (fn.toString()) + ").apply(this, JSON.parse(" + (JSON.stringify(JSON.stringify(args))) + "))";
  };

  /**
   * Binds callbacks to their respective Native implementations
   * @param name
   * @return {Function}
   */
  WebPage.prototype.bindCallback = function (name) {
    var self;
    self = this;

    return this["native"]()[name] = function () {
      var result;
      if (self[name + 'Native'] != null) {
        result = self[name + 'Native'].apply(self, arguments);
      }
      if (result !== false && (self[name] != null)) {
        return self[name].apply(self, arguments);
      }
    };
  };

  /**
   * Runs a command delegating to the PoltergeistAgent
   * @param name
   * @param args
   * @return {*}
   */
  WebPage.prototype.runCommand = function (name, args) {
    var method, result, selector;

    result = this.evaluate(function (name, args) {
      return window.__poltergeist.externalCall(name, args);
    }, name, args);

    if (result !== null) {
      if (result.error != null) {
        switch (result.error.message) {
          case 'PoltergeistAgent.ObsoleteNode':
            throw new Poltergeist.ObsoleteNode;
            break;
          case 'PoltergeistAgent.InvalidSelector':
            method = args[0];
            selector = args[1];
            throw new Poltergeist.InvalidSelector(method, selector);
            break;
          default:
            throw new Poltergeist.BrowserError(result.error.message, result.error.stack);
        }
      } else {
        return result.value;
      }
    }
  };

  /**
   * Tells if we can go back or not
   * @return {boolean}
   */
  WebPage.prototype.canGoBack = function () {
    return this["native"]().canGoBack;
  };

  /**
   * Tells if we can go forward or not in the browser history
   * @return {boolean}
   */
  WebPage.prototype.canGoForward = function () {
    return this["native"]().canGoForward;
  };

  return WebPage;

}).call(this);
