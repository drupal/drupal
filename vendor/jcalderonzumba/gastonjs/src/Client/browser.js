var __indexOf = [].indexOf || function (item) {
    for (var i = 0, l = this.length; i < l; i++) {
      if (i in this && this[i] === item) return i;
    }
    return -1;
  };

var xpathStringLiteral = function (s) {
  if (s.indexOf('"') === -1)
    return '"' + s + '"';
  if (s.indexOf("'") === -1)
    return "'" + s + "'";
  return 'concat("' + s.replace(/"/g, '",\'"\',"') + '")';
};

Poltergeist.Browser = (function () {
  /**
   * Creates the "browser" inside phantomjs
   * @param owner
   * @param width
   * @param height
   * @param jsErrors
   * @constructor
   */
  function Browser(owner, width, height, jsErrors) {
    this.owner = owner;
    this.width = width || 1024;
    this.height = height || 768;
    this.pages = [];
    this.js_errors = (typeof jsErrors === 'boolean') ? jsErrors : true;
    this._debug = false;
    this._counter = 0;
    this.resetPage();
  }

  /**
   * Resets the browser to a clean slate
   * @return {Function}
   */
  Browser.prototype.resetPage = function () {
    var _ref;
    var self = this;

    _ref = [0, []];
    this._counter = _ref[0];
    this.pages = _ref[1];

    if (this.page != null) {
      if (!this.page.closed) {
        if (this.page.currentUrl() !== 'about:blank') {
          this.page.clearLocalStorage();
        }
        this.page.release();
      }
      phantom.clearCookies();
    }

    this.page = this.currentPage = new Poltergeist.WebPage;
    this.page.setViewportSize({
      width: this.width,
      height: this.height
    });
    this.page.handle = "" + (this._counter++);
    this.pages.push(this.page);

    return this.page.onPageCreated = function (newPage) {
      var page;
      page = new Poltergeist.WebPage(newPage);
      page.handle = "" + (self._counter++);
      return self.pages.push(page);
    };
  };

  /**
   * Given a page handle id, tries to get it from the browser page list
   * @param handle
   * @return {WebPage}
   */
  Browser.prototype.getPageByHandle = function (handle) {
    var filteredPages;

    //TODO: perhaps we should throw a PageNotFoundByHandle or something like that..
    if (handle === null || typeof handle == "undefined") {
      return null;
    }

    filteredPages = this.pages.filter(function (p) {
      return !p.closed && p.handle === handle;
    });

    if (filteredPages.length === 1) {
      return filteredPages[0];
    }

    return null;
  };

  /**
   * Sends a debug message to the console
   * @param message
   * @return {*}
   */
  Browser.prototype.debug = function (message) {
    if (this._debug) {
      return console.log("poltergeist [" + (new Date().getTime()) + "] " + message);
    }
  };

  /**
   * Given a page_id and id, gets if possible the node in such page
   * @param page_id
   * @param id
   * @return {Poltergeist.Node}
   */
  Browser.prototype.node = function (page_id, id) {
    if (this.currentPage.id === page_id) {
      return this.currentPage.get(id);
    } else {
      throw new Poltergeist.ObsoleteNode;
    }
  };

  /**
   * Returns the frameUrl related to the frame given by name
   * @param frame_name
   * @return {*}
   */
  Browser.prototype.frameUrl = function (frame_name) {
    return this.currentPage.frameUrl(frame_name);
  };

  /**
   * This method defines the rectangular area of the web page to be rasterized when render is invoked.
   * If no clipping rectangle is set, render will process the entire web page.
   * @param full
   * @param selector
   * @return {*}
   */
  Browser.prototype.set_clip_rect = function (full, selector) {
    var dimensions, clipDocument, rect, clipViewport;

    dimensions = this.currentPage.validatedDimensions();
    clipDocument = dimensions.document;
    clipViewport = dimensions.viewport;

    if (full) {
      rect = {
        left: 0,
        top: 0,
        width: clipDocument.width,
        height: clipDocument.height
      };
    } else {
      if (selector != null) {
        rect = this.currentPage.elementBounds(selector);
      } else {
        rect = {
          left: 0,
          top: 0,
          width: clipViewport.width,
          height: clipViewport.height
        };
      }
    }

    this.currentPage.setClipRect(rect);
    return dimensions;
  };

  /**
   * Kill the browser, i.e kill phantomjs current process
   * @return {int}
   */
  Browser.prototype.exit = function () {
    return phantom.exit(0);
  };

  /**
   * Do nothing
   */
  Browser.prototype.noop = function () {
  };

  /**
   * Throws a new Object error
   */
  Browser.prototype.browser_error = function () {
    throw new Error('zomg');
  };

  /**
   *  Visits a page and load its content
   * @param serverResponse
   * @param url
   * @return {*}
   */
  Browser.prototype.visit = function (serverResponse, url) {
    var prevUrl;
    var self = this;
    this.currentPage.state = 'loading';
    prevUrl = this.currentPage.source === null ? 'about:blank' : this.currentPage.currentUrl();
    this.currentPage.open(url);
    if (/#/.test(url) && prevUrl.split('#')[0] === url.split('#')[0]) {
      this.currentPage.state = 'default';
      return this.serverSendResponse({
        status: 'success'
      }, serverResponse);
    } else {
      return this.currentPage.waitState('default', function () {
        if (self.currentPage.statusCode === null && self.currentPage.status === 'fail') {
          return self.owner.serverSendError(new Poltergeist.StatusFailError, serverResponse);
        } else {
          return self.serverSendResponse({
            status: self.currentPage.status
          }, serverResponse);
        }
      });
    }
  };

  /**
   *  Puts the control of the browser inside the IFRAME given by name
   * @param serverResponse
   * @param name
   * @param timeout
   * @return {*}
   */
  Browser.prototype.push_frame = function (serverResponse, name, timeout) {
    var _ref;
    var self = this;

    if (timeout == null) {
      timeout = new Date().getTime() + 2000;
    }

    //TODO: WTF, else if after a if with return COMMON
    if (_ref = this.frameUrl(name), __indexOf.call(this.currentPage.blockedUrls(), _ref) >= 0) {
      return this.serverSendResponse(true, serverResponse);
    } else if (this.currentPage.pushFrame(name)) {
      if (this.currentPage.currentUrl() === 'about:blank') {
        this.currentPage.state = 'awaiting_frame_load';
        return this.currentPage.waitState('default', function () {
          return self.serverSendResponse(true, serverResponse);
        });
      } else {
        return this.serverSendResponse(true, serverResponse);
      }
    } else {
      if (new Date().getTime() < timeout) {
        return setTimeout((function () {
          return self.push_frame(serverResponse, name, timeout);
        }), 50);
      } else {
        return this.owner.serverSendError(new Poltergeist.FrameNotFound(name), serverResponse);
      }
    }
  };

  /**
   *  Injects a javascript into the current page
   * @param serverResponse
   * @param extension
   * @return {*}
   */
  Browser.prototype.add_extension = function (serverResponse, extension) {
    //TODO: error control when the injection was not possible
    this.currentPage.injectExtension(extension);
    return this.serverSendResponse('success', serverResponse);
  };

  /**
   *  Returns the url we are currently in
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.current_url = function (serverResponse) {
    return this.serverSendResponse(this.currentPage.currentUrl(), serverResponse);
  };

  /**
   *  Returns the current page window name
   * @param serverResponse
   * @returns {*}
   */
  Browser.prototype.window_name = function (serverResponse) {
    return this.serverSendResponse(this.currentPage.windowName(), serverResponse);
  };

  /**
   *  Returns the status code associated to the page
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.status_code = function (serverResponse) {
    if (this.currentPage.statusCode === undefined || this.currentPage.statusCode === null) {
      return this.owner.serverSendError(new Poltergeist.StatusFailError("status_code_error"), serverResponse);
    }
    return this.serverSendResponse(this.currentPage.statusCode, serverResponse);
  };

  /**
   *  Returns the source code of the active frame, useful for when inside an IFRAME
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.body = function (serverResponse) {
    return this.serverSendResponse(this.currentPage.content(), serverResponse);
  };

  /**
   * Returns the source code of the page all the html
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.source = function (serverResponse) {
    return this.serverSendResponse(this.currentPage.source, serverResponse);
  };

  /**
   * Returns the current page title
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.title = function (serverResponse) {
    return this.serverSendResponse(this.currentPage.title(), serverResponse);
  };

  /**
   *  Finds the elements that match a method of selection and a selector
   * @param serverResponse
   * @param method
   * @param selector
   * @return {*}
   */
  Browser.prototype.find = function (serverResponse, method, selector) {
    return this.serverSendResponse({
      page_id: this.currentPage.id,
      ids: this.currentPage.find(method, selector)
    }, serverResponse);
  };

  /**
   * Find elements within a given element
   * @param serverResponse
   * @param page_id
   * @param id
   * @param method
   * @param selector
   * @return {*}
   */
  Browser.prototype.find_within = function (serverResponse, page_id, id, method, selector) {
    return this.serverSendResponse(this.node(page_id, id).find(method, selector), serverResponse);
  };

  /**
   * Returns ALL the text, visible and not visible from the given element
   * @param serverResponse
   * @param page_id
   * @param id
   * @return {*}
   */
  Browser.prototype.all_text = function (serverResponse, page_id, id) {
    return this.serverSendResponse(this.node(page_id, id).allText(), serverResponse);
  };

  /**
   * Returns the inner or outer html of a given id
   * @param serverResponse
   * @param page_id
   * @param id
   * @param type
   * @returns Object
   */
  Browser.prototype.all_html = function (serverResponse, page_id, id, type) {
    return this.serverSendResponse(this.node(page_id, id).allHTML(type), serverResponse);
  };

  /**
   *  Returns only the visible text in a given element
   * @param serverResponse
   * @param page_id
   * @param id
   * @return {*}
   */
  Browser.prototype.visible_text = function (serverResponse, page_id, id) {
    return this.serverSendResponse(this.node(page_id, id).visibleText(), serverResponse);
  };

  /**
   * Deletes the text in a given element leaving it empty
   * @param serverResponse
   * @param page_id
   * @param id
   * @return {*}
   */
  Browser.prototype.delete_text = function (serverResponse, page_id, id) {
    return this.serverSendResponse(this.node(page_id, id).deleteText(), serverResponse);
  };

  /**
   *  Gets the value of a given attribute in an element
   * @param serverResponse
   * @param page_id
   * @param id
   * @param name
   * @return {*}
   */
  Browser.prototype.attribute = function (serverResponse, page_id, id, name) {
    return this.serverSendResponse(this.node(page_id, id).getAttribute(name), serverResponse);
  };

  /**
   *  Allows the possibility to set an attribute on a given element
   * @param serverResponse
   * @param page_id
   * @param id
   * @param name
   * @param value
   * @returns {*}
   */
  Browser.prototype.set_attribute = function (serverResponse, page_id, id, name, value) {
    return this.serverSendResponse(this.node(page_id, id).setAttribute(name, value), serverResponse);
  };

  /**
   *  Allows the possibility to remove an attribute on a given element
   * @param serverResponse
   * @param page_id
   * @param id
   * @param name
   * @returns {*}
   */
  Browser.prototype.remove_attribute = function (serverResponse, page_id, id, name) {
    return this.serverSendResponse(this.node(page_id, id).removeAttribute(name), serverResponse);
  };

  /**
   * Returns all the attributes of a given element
   * @param serverResponse
   * @param page_id
   * @param id
   * @param name
   * @return {*}
   */
  Browser.prototype.attributes = function (serverResponse, page_id, id, name) {
    return this.serverSendResponse(this.node(page_id, id).getAttributes(), serverResponse);
  };

  /**
   *  Returns all the way to the document level the parents of a given element
   * @param serverResponse
   * @param page_id
   * @param id
   * @return {*}
   */
  Browser.prototype.parents = function (serverResponse, page_id, id) {
    return this.serverSendResponse(this.node(page_id, id).parentIds(), serverResponse);
  };

  /**
   * Returns the element.value of an element given by its page and id
   * @param serverResponse
   * @param page_id
   * @param id
   * @return {*}
   */
  Browser.prototype.value = function (serverResponse, page_id, id) {
    return this.serverSendResponse(this.node(page_id, id).value(), serverResponse);
  };

  /**
   *  Sets the element.value of an element by the given value
   * @param serverResponse
   * @param page_id
   * @param id
   * @param value
   * @return {*}
   */
  Browser.prototype.set = function (serverResponse, page_id, id, value) {
    this.node(page_id, id).set(value);
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   *  Uploads a file to an input file element
   * @param serverResponse
   * @param page_id
   * @param id
   * @param file_path
   * @return {*}
   */
  Browser.prototype.select_file = function (serverResponse, page_id, id, file_path) {
    var node = this.node(page_id, id);

    this.currentPage.beforeUpload(node.id);
    this.currentPage.uploadFile('[_poltergeist_selected]', file_path);
    this.currentPage.afterUpload(node.id);

    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * Sets a value to the selected element (to be used in select elements)
   * @param serverResponse
   * @param page_id
   * @param id
   * @param value
   * @return {*}
   */
  Browser.prototype.select = function (serverResponse, page_id, id, value) {
    return this.serverSendResponse(this.node(page_id, id).select(value), serverResponse);
  };

  /**
   *  Selects an option with the given value
   * @param serverResponse
   * @param page_id
   * @param id
   * @param value
   * @param multiple
   * @return {*}
   */
  Browser.prototype.select_option = function (serverResponse, page_id, id, value, multiple) {
    return this.serverSendResponse(this.node(page_id, id).select_option(value, multiple), serverResponse);
  };

  /**
   *
   * @param serverResponse
   * @param page_id
   * @param id
   * @return {*}
   */
  Browser.prototype.tag_name = function (serverResponse, page_id, id) {
    return this.serverSendResponse(this.node(page_id, id).tagName(), serverResponse);
  };


  /**
   * Tells if an element is visible or not
   * @param serverResponse
   * @param page_id
   * @param id
   * @return {*}
   */
  Browser.prototype.visible = function (serverResponse, page_id, id) {
    return this.serverSendResponse(this.node(page_id, id).isVisible(), serverResponse);
  };

  /**
   *  Tells if an element is disabled
   * @param serverResponse
   * @param page_id
   * @param id
   * @return {*}
   */
  Browser.prototype.disabled = function (serverResponse, page_id, id) {
    return this.serverSendResponse(this.node(page_id, id).isDisabled(), serverResponse);
  };

  /**
   *  Evaluates a javascript and returns the outcome to the client
   *  This will be JSON response so your script better be returning objects that can be used
   *  in JSON.stringify
   * @param serverResponse
   * @param script
   * @return {*}
   */
  Browser.prototype.evaluate = function (serverResponse, script) {
    return this.serverSendResponse(this.currentPage.evaluate("function() { return " + script + " }"), serverResponse);
  };

  /**
   *  Executes a javascript and goes back to the client with true if there were no errors
   * @param serverResponse
   * @param script
   * @return {*}
   */
  Browser.prototype.execute = function (serverResponse, script) {
    this.currentPage.execute("function() { " + script + " }");
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * If inside a frame then we will go back to the parent
   * Not defined behaviour if you pop and are not inside an iframe
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.pop_frame = function (serverResponse) {
    return this.serverSendResponse(this.currentPage.popFrame(), serverResponse);
  };

  /**
   * Gets the window handle id by a given window name
   * @param serverResponse
   * @param name
   * @return {*}
   */
  Browser.prototype.window_handle = function (serverResponse, name) {
    var handle, pageByWindowName;

    if (name === null || typeof name == "undefined" || name.length === 0) {
      return this.serverSendResponse(this.currentPage.handle, serverResponse);
    }

    handle = null;

    //Lets search the handle by the given window name
    var filteredPages = this.pages.filter(function (p) {
      return !p.closed && p.windowName() === name;
    });

    //A bit of error control is always good
    if (Array.isArray(filteredPages) && filteredPages.length >= 1) {
      pageByWindowName = filteredPages[0];
    } else {
      pageByWindowName = null;
    }

    if (pageByWindowName !== null && typeof pageByWindowName != "undefined") {
      handle = pageByWindowName.handle;
    }

    return this.serverSendResponse(handle, serverResponse);
  };

  /**
   * Returns all the window handles of opened windows
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.window_handles = function (serverResponse) {
    var handles, filteredPages;

    filteredPages = this.pages.filter(function (p) {
      return !p.closed;
    });

    if (filteredPages.length > 0) {
      handles = filteredPages.map(function (p) {
        return p.handle;
      });
      if (handles.length === 0) {
        handles = null;
      }
    } else {
      handles = null;
    }

    return this.serverSendResponse(handles, serverResponse);
  };

  /**
   *  Tries to switch to a window given by the handle id
   * @param serverResponse
   * @param handle
   * @return {*}
   */
  Browser.prototype.switch_to_window = function (serverResponse, handle) {
    var page;
    var self = this;

    page = this.getPageByHandle(handle);
    if (page === null || typeof page == "undefined") {
      throw new Poltergeist.NoSuchWindowError;
    }

    if (page !== this.currentPage) {
      return page.waitState('default', function () {
        self.currentPage = page;
        return self.serverSendResponse(true, serverResponse);
      });
    }

    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * Opens a new window where we can do stuff
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.open_new_window = function (serverResponse) {
    return this.execute(serverResponse, 'window.open()');
  };

  /**
   * Closes the window given by handle name if possible
   * @param serverResponse
   * @param handle
   * @return {*}
   */
  Browser.prototype.close_window = function (serverResponse, handle) {
    var page;

    page = this.getPageByHandle(handle);
    if (page === null || typeof  page == "undefined") {
      //TODO: should we throw error since we actually could not find the window?
      return this.serverSendResponse(false, serverResponse);
    }

    //TODO: we have to add some control here to actually asses that the release has been done
    page.release();
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * Generic mouse event on an element
   * @param serverResponse
   * @param page_id
   * @param id
   * @param name
   * @return {number}
   */
  Browser.prototype.mouse_event = function (serverResponse, page_id, id, name) {
    var node;
    var self = this;
    node = this.node(page_id, id);
    this.currentPage.state = 'mouse_event';
    this.last_mouse_event = node.mouseEvent(name);
    return setTimeout(function () {
      if (self.currentPage.state === 'mouse_event') {
        self.currentPage.state = 'default';
        return self.serverSendResponse({
          position: self.last_mouse_event
        }, serverResponse);
      } else {
        return self.currentPage.waitState('default', function () {
          return self.serverSendResponse({
            position: self.last_mouse_event
          }, serverResponse);
        });
      }
    }, 5);
  };

  /**
   * Simple click on the element
   * @param serverResponse
   * @param page_id
   * @param id
   * @return {*}
   */
  Browser.prototype.click = function (serverResponse, page_id, id) {
    return this.mouse_event(serverResponse, page_id, id, 'click');
  };

  /**
   * Right click on the element
   * @param serverResponse
   * @param page_id
   * @param id
   * @return {*}
   */
  Browser.prototype.right_click = function (serverResponse, page_id, id) {
    return this.mouse_event(serverResponse, page_id, id, 'rightclick');
  };

  /**
   *  Double click on the element given by page and id
   * @param serverResponse
   * @param page_id
   * @param id
   * @return {*}
   */
  Browser.prototype.double_click = function (serverResponse, page_id, id) {
    return this.mouse_event(serverResponse, page_id, id, 'doubleclick');
  };

  /**
   * Executes a mousemove event on the page and given element
   * @param serverResponse
   * @param page_id
   * @param id
   * @return {*}
   */
  Browser.prototype.hover = function (serverResponse, page_id, id) {
    return this.mouse_event(serverResponse, page_id, id, 'mousemove');
  };

  /**
   * Triggers a mouse click event on the given coordinates
   * @param serverResponse
   * @param x
   * @param y
   * @return {*}
   */
  Browser.prototype.click_coordinates = function (serverResponse, x, y) {
    var response;

    this.currentPage.sendEvent('click', x, y);
    response = {
      click: {
        x: x,
        y: y
      }
    };

    return this.serverSendResponse(response, serverResponse);
  };

  /**
   *  Drags one element into another, useful for nice javascript thingies
   * @param serverResponse
   * @param page_id
   * @param id
   * @param other_id
   * @return {*}
   */
  Browser.prototype.drag = function (serverResponse, page_id, id, other_id) {
    this.node(page_id, id).dragTo(this.node(page_id, other_id));
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * Triggers an event on the given page and element
   * @param serverResponse
   * @param page_id
   * @param id
   * @param event
   * @return {*}
   */
  Browser.prototype.trigger = function (serverResponse, page_id, id, event) {
    this.node(page_id, id).trigger(event);
    return this.serverSendResponse(event, serverResponse);
  };

  /**
   * Checks if two elements are equal on a dom level
   * @param serverResponse
   * @param page_id
   * @param id
   * @param other_id
   * @return {*}
   */
  Browser.prototype.equals = function (serverResponse, page_id, id, other_id) {
    return this.serverSendResponse(this.node(page_id, id).isEqual(this.node(page_id, other_id)), serverResponse);
  };

  /**
   * Resets the current page to a clean slate
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.reset = function (serverResponse) {
    this.resetPage();
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * Scrolls to a position given by the left, top coordinates
   * @param serverResponse
   * @param left
   * @param top
   * @return {*}
   */
  Browser.prototype.scroll_to = function (serverResponse, left, top) {
    this.currentPage.setScrollPosition({
      left: left,
      top: top
    });
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * Sends keys to an element simulating as closest as possible what a user would do
   * when typing
   * @param serverResponse
   * @param page_id
   * @param id
   * @param keys
   * @return {*}
   */
  Browser.prototype.send_keys = function (serverResponse, page_id, id, keys) {
    var key, sequence, target, _i, _len;
    target = this.node(page_id, id);
    if (!target.containsSelection()) {
      target.mouseEvent('click');
    }
    for (_i = 0, _len = keys.length; _i < _len; _i++) {
      sequence = keys[_i];
      key = sequence.key != null ? this.currentPage.keyCode(sequence.key) : sequence;
      this.currentPage.sendEvent('keypress', key);
    }
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * Sends a native phantomjs key event to element
   * @param serverResponse
   * @param page_id
   * @param id
   * @param keyEvent
   * @param key
   * @param modifier
   */
  Browser.prototype.key_event = function (serverResponse, page_id, id, keyEvent, key, modifier) {
    var keyEventModifierMap;
    var keyEventModifier;
    var target;

    keyEventModifierMap = {
      'none': 0x0,
      'shift': 0x02000000,
      'ctrl': 0x04000000,
      'alt': 0x08000000,
      'meta': 0x10000000
    };
    keyEventModifier = keyEventModifierMap[modifier];

    target = this.node(page_id, id);
    if (!target.containsSelection()) {
      target.mouseEvent('click');
    }
    target.page.sendEvent(keyEvent, key, null, null, keyEventModifier);

    return this.serverSendResponse(true, serverResponse);
  };

  /**
   *  Sends the rendered page in a base64 encoding
   * @param serverResponse
   * @param format
   * @param full
   * @param selector
   * @return {*}
   */
  Browser.prototype.render_base64 = function (serverResponse, format, full, selector) {
    var encoded_image;
    if (selector == null) {
      selector = null;
    }
    this.set_clip_rect(full, selector);
    encoded_image = this.currentPage.renderBase64(format);
    return this.serverSendResponse(encoded_image, serverResponse);
  };

  /**
   * Renders the current page entirely or a given selection
   * @param serverResponse
   * @param path
   * @param full
   * @param selector
   * @return {*}
   */
  Browser.prototype.render = function (serverResponse, path, full, selector) {
    var dimensions;
    if (selector == null) {
      selector = null;
    }
    dimensions = this.set_clip_rect(full, selector);
    this.currentPage.setScrollPosition({
      left: 0,
      top: 0
    });
    this.currentPage.render(path);
    this.currentPage.setScrollPosition({
      left: dimensions.left,
      top: dimensions.top
    });
    return this.serverSendResponse(true, serverResponse);
  };


  /**
   * Sets the paper size, useful when printing to PDF
   * @param serverResponse
   * @param size
   * @return {*}
   */
  Browser.prototype.set_paper_size = function (serverResponse, size) {
    this.currentPage.setPaperSize(size);
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   *  Sets the zoom factor on the current page
   * @param serverResponse
   * @param zoom_factor
   * @return {*}
   */
  Browser.prototype.set_zoom_factor = function (serverResponse, zoom_factor) {
    this.currentPage.setZoomFactor(zoom_factor);
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * Resizes the browser viewport, useful when testing mobile stuff
   * @param serverResponse
   * @param width
   * @param height
   * @return {*}
   */
  Browser.prototype.resize = function (serverResponse, width, height) {
    this.currentPage.setViewportSize({
      width: width,
      height: height
    });
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * Gets the browser viewport size
   * Because PhantomJS is headless (nothing is shown)
   * viewportSize effectively simulates the size of the window like in a traditional browser.
   * @param serverResponse
   * @param handle
   * @return {*}
   */
  Browser.prototype.window_size = function (serverResponse, handle) {
    //TODO: add support for window handles
    return this.serverSendResponse(this.currentPage.viewportSize(), serverResponse);
  };

  /**
   * Returns the network traffic that the current page has generated
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.network_traffic = function (serverResponse) {
    return this.serverSendResponse(this.currentPage.networkTraffic(), serverResponse);
  };

  /**
   * Clears the accumulated network_traffic in the current page
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.clear_network_traffic = function (serverResponse) {
    this.currentPage.clearNetworkTraffic();
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * Gets the headers of the current page
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.get_headers = function (serverResponse) {
    return this.serverSendResponse(this.currentPage.getCustomHeaders(), serverResponse);
  };

  /**
   * Set headers in the browser
   * @param serverResponse
   * @param headers
   * @return {*}
   */
  Browser.prototype.set_headers = function (serverResponse, headers) {
    if (headers['User-Agent']) {
      this.currentPage.setUserAgent(headers['User-Agent']);
    }
    this.currentPage.setCustomHeaders(headers);
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * Given an array of headers, adds them to the page
   * @param serverResponse
   * @param headers
   * @return {*}
   */
  Browser.prototype.add_headers = function (serverResponse, headers) {
    var allHeaders, name, value;
    allHeaders = this.currentPage.getCustomHeaders();
    for (name in headers) {
      if (headers.hasOwnProperty(name)) {
        value = headers[name];
        allHeaders[name] = value;
      }
    }
    return this.set_headers(serverResponse, allHeaders);
  };

  /**
   * Adds a header to the page temporary or permanently
   * @param serverResponse
   * @param header
   * @param permanent
   * @return {*}
   */
  Browser.prototype.add_header = function (serverResponse, header, permanent) {
    if (!permanent) {
      this.currentPage.addTempHeader(header);
    }
    return this.add_headers(serverResponse, header);
  };


  /**
   * Sends back the client the response headers sent from the browser when making
   * the page request
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.response_headers = function (serverResponse) {
    return this.serverSendResponse(this.currentPage.responseHeaders(), serverResponse);
  };

  /**
   * Returns the cookies of the current page being browsed
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.cookies = function (serverResponse) {
    return this.serverSendResponse(this.currentPage.cookies(), serverResponse);
  };

  /**
   * Sets a cookie in the browser, the format of the cookies has to be the format it says
   * on phantomjs documentation and as such you can set it in other domains, not on the
   * current page
   * @param serverResponse
   * @param cookie
   * @return {*}
   */
  Browser.prototype.set_cookie = function (serverResponse, cookie) {
    return this.serverSendResponse(phantom.addCookie(cookie), serverResponse);
  };

  /**
   * Remove a cookie set on the current page
   * @param serverResponse
   * @param name
   * @return {*}
   */
  Browser.prototype.remove_cookie = function (serverResponse, name) {
    //TODO: add error control to check if the cookie was properly deleted
    this.currentPage.deleteCookie(name);
    phantom.deleteCookie(name);
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * Clear the cookies in the browser
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.clear_cookies = function (serverResponse) {
    phantom.clearCookies();
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * Enables / Disables the cookies on the browser
   * @param serverResponse
   * @param flag
   * @return {*}
   */
  Browser.prototype.cookies_enabled = function (serverResponse, flag) {
    phantom.cookiesEnabled = flag;
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * US19: DONE
   * Sets a basic authentication credential to access a page
   * THIS SHOULD BE USED BEFORE accessing a page
   * @param serverResponse
   * @param user
   * @param password
   * @return {*}
   */
  Browser.prototype.set_http_auth = function (serverResponse, user, password) {
    this.currentPage.setHttpAuth(user, password);
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * Sets the flag whether to fail on javascript errors or not.
   * @param serverResponse
   * @param value
   * @return {*}
   */
  Browser.prototype.set_js_errors = function (serverResponse, value) {
    this.js_errors = value;
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * Sets the debug mode to boolean value
   * @param serverResponse
   * @param value
   * @return {*}
   */
  Browser.prototype.set_debug = function (serverResponse, value) {
    this._debug = value;
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * Goes back in the history when possible
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.go_back = function (serverResponse) {
    var self = this;
    if (this.currentPage.canGoBack()) {
      this.currentPage.state = 'loading';
      this.currentPage.goBack();
      return this.currentPage.waitState('default', function () {
        return self.serverSendResponse(true, serverResponse);
      });
    } else {
      return this.serverSendResponse(false, serverResponse);
    }
  };

  /**
   * Reloads the page if possible
   * @return {*}
   */
  Browser.prototype.reload = function (serverResponse) {
    var self = this;
    this.currentPage.state = 'loading';
    this.currentPage.reload();
    return this.currentPage.waitState('default', function () {
      return self.serverSendResponse(true, serverResponse);
    });
  };

  /**
   * Goes forward in the browser history if possible
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.go_forward = function (serverResponse) {
    var self = this;
    if (this.currentPage.canGoForward()) {
      this.currentPage.state = 'loading';
      this.currentPage.goForward();
      return this.currentPage.waitState('default', function () {
        return self.serverSendResponse(true, serverResponse);
      });
    } else {
      return this.serverSendResponse(false, serverResponse);
    }
  };

  /**
   *  Sets the urlBlacklist for the given urls as parameters
   * @return {boolean}
   */
  Browser.prototype.set_url_blacklist = function (serverResponse, blackList) {
    this.currentPage.urlBlacklist = Array.prototype.slice.call(blackList);
    return this.serverSendResponse(true, serverResponse);
  };

  /**
   * Runs a browser command and returns the response back to the client
   * when the command has finished the execution
   * @param command
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.serverRunCommand = function (command, serverResponse) {
    var commandData;
    var commandArgs;
    var commandName;

    commandName = command.name;
    commandArgs = command.args;
    this.currentPage.state = 'default';
    commandData = [serverResponse].concat(commandArgs);

    if (typeof this[commandName] !== "function") {
      //We can not run such command
      throw new Poltergeist.Error();
    }

    return this[commandName].apply(this, commandData);
  };

  /**
   * Sends a response back to the client who made the request
   * @param response
   * @param serverResponse
   * @return {*}
   */
  Browser.prototype.serverSendResponse = function (response, serverResponse) {
    var errors;
    errors = this.currentPage.errors;
    this.currentPage.clearErrors();
    if (errors.length > 0 && this.js_errors) {
      return this.owner.serverSendError(new Poltergeist.JavascriptError(errors), serverResponse);
    } else {
      return this.owner.serverSendResponse(response, serverResponse);
    }
  };

  return Browser;

})();
