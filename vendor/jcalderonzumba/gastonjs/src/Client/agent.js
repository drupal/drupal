var PoltergeistAgent;

PoltergeistAgent = (function () {
  function PoltergeistAgent() {
    this.elements = [];
    this.nodes = {};
  }

  /**
   * Executes an external call done from the web page class
   * @param name
   * @param args
   * @return {*}
   */
  PoltergeistAgent.prototype.externalCall = function (name, args) {
    var error;
    try {
      return {
        value: this[name].apply(this, args)
      };
    } catch (_error) {
      error = _error;
      return {
        error: {
          message: error.toString(),
          stack: error.stack
        }
      };
    }
  };

  /**
   * Object stringifycation
   * @param object
   * @return {*}
   */
  PoltergeistAgent.stringify = function (object) {
    var error;
    try {
      return JSON.stringify(object, function (key, value) {
        if (Array.isArray(this[key])) {
          return this[key];
        } else {
          return value;
        }
      });
    } catch (_error) {
      error = _error;
      if (error instanceof TypeError) {
        return '"(cyclic structure)"';
      } else {
        throw error;
      }
    }
  };

  /**
   * Name speaks for itself
   * @return {string}
   */
  PoltergeistAgent.prototype.currentUrl = function () {
    return encodeURI(decodeURI(window.location.href));
  };

  /**
   *  Given a method of selection (xpath or css), a selector and a possible element to search
   *  tries to find the elements that matches such selection
   * @param method
   * @param selector
   * @param within
   * @return {Array}
   */
  PoltergeistAgent.prototype.find = function (method, selector, within) {
    var elementForXpath, error, i, results, xpath, _i, _len, _results;
    if (within == null) {
      within = document;
    }
    try {
      if (method === "xpath") {
        xpath = document.evaluate(selector, within, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
        results = (function () {
          var _i, _ref, _results;
          _results = [];
          for (i = _i = 0, _ref = xpath.snapshotLength; 0 <= _ref ? _i < _ref : _i > _ref; i = 0 <= _ref ? ++_i : --_i) {
            _results.push(xpath.snapshotItem(i));
          }
          return _results;
        })();
      } else {
        results = within.querySelectorAll(selector);
      }
      _results = [];
      for (_i = 0, _len = results.length; _i < _len; _i++) {
        elementForXpath = results[_i];
        _results.push(this.register(elementForXpath));
      }
      return _results;
    } catch (_error) {
      error = _error;
      if (error.code === DOMException.SYNTAX_ERR || error.code === 51) {
        throw new PoltergeistAgent.InvalidSelector;
      } else {
        throw error;
      }
    }
  };

  /**
   *  Register the element in the agent
   * @param element
   * @return {number}
   */
  PoltergeistAgent.prototype.register = function (element) {
    this.elements.push(element);
    return this.elements.length - 1;
  };

  /**
   *  Gets the size of the document
   * @return {{height: number, width: number}}
   */
  PoltergeistAgent.prototype.documentSize = function () {
    return {
      height: document.documentElement.scrollHeight || document.documentElement.clientHeight,
      width: document.documentElement.scrollWidth || document.documentElement.clientWidth
    };
  };

  /**
   * Gets a Node by a given id
   * @param id
   * @return {PoltergeistAgent.Node}
   */
  PoltergeistAgent.prototype.get = function (id) {
    if (typeof this.nodes[id] == "undefined" || this.nodes[id] === null) {
      //Let's try now the elements approach
      if (typeof this.elements[id] == "undefined" || this.elements[id] === null) {
        throw new PoltergeistAgent.ObsoleteNode;
      }
      return new PoltergeistAgent.Node(this, this.elements[id]);
    }

    return this.nodes[id];
  };

  /**
   * Calls a Node agent function from the Node caller via delegates
   * @param id
   * @param name
   * @param args
   * @return {*}
   */
  PoltergeistAgent.prototype.nodeCall = function (id, name, args) {
    var node;

    node = this.get(id);
    if (node.isObsolete()) {
      throw new PoltergeistAgent.ObsoleteNode;
    }
    //TODO: add some error control here, we might not be able to call name function
    return node[name].apply(node, args);
  };

  PoltergeistAgent.prototype.beforeUpload = function (id) {
    return this.get(id).setAttribute('_poltergeist_selected', '');
  };

  PoltergeistAgent.prototype.afterUpload = function (id) {
    return this.get(id).removeAttribute('_poltergeist_selected');
  };

  PoltergeistAgent.prototype.clearLocalStorage = function () {
    //TODO: WTF where is variable...
    return localStorage.clear();
  };

  return PoltergeistAgent;

})();

PoltergeistAgent.ObsoleteNode = (function () {
  function ObsoleteNode() {
  }

  ObsoleteNode.prototype.toString = function () {
    return "PoltergeistAgent.ObsoleteNode";
  };

  return ObsoleteNode;

})();

PoltergeistAgent.InvalidSelector = (function () {
  function InvalidSelector() {
  }

  InvalidSelector.prototype.toString = function () {
    return "PoltergeistAgent.InvalidSelector";
  };

  return InvalidSelector;

})();

PoltergeistAgent.Node = (function () {

  Node.EVENTS = {
    FOCUS: ['blur', 'focus', 'focusin', 'focusout'],
    MOUSE: ['click', 'dblclick', 'mousedown', 'mouseenter', 'mouseleave', 'mousemove', 'mouseover', 'mouseout', 'mouseup', 'contextmenu'],
    FORM: ['submit']
  };

  function Node(agent, element) {
    this.agent = agent;
    this.element = element;
  }

  /**
   * Give me the node id of the parent of this node
   * @return {number}
   */
  Node.prototype.parentId = function () {
    return this.agent.register(this.element.parentNode);
  };

  /**
   * Returns all the node parents ids up to first child of the dom
   * @return {Array}
   */
  Node.prototype.parentIds = function () {
    var ids, parent;
    ids = [];
    parent = this.element.parentNode;
    while (parent !== document) {
      ids.push(this.agent.register(parent));
      parent = parent.parentNode;
    }
    return ids;
  };

  /**
   * Finds and returns the node ids that matches the selector within this node
   * @param method
   * @param selector
   * @return {Array}
   */
  Node.prototype.find = function (method, selector) {
    return this.agent.find(method, selector, this.element);
  };

  /**
   * Checks whether the node is obsolete or not
   * @return boolean
   */
  Node.prototype.isObsolete = function () {
    var obsolete;

    obsolete = function (element) {
      if (element.parentNode != null) {
        if (element.parentNode === document) {
          return false;
        } else {
          return obsolete(element.parentNode);
        }
      } else {
        return true;
      }
    };

    return obsolete(this.element);
  };

  Node.prototype.changed = function () {
    var event;
    event = document.createEvent('HTMLEvents');
    event.initEvent('change', true, false);
    return this.element.dispatchEvent(event);
  };

  Node.prototype.input = function () {
    var event;
    event = document.createEvent('HTMLEvents');
    event.initEvent('input', true, false);
    return this.element.dispatchEvent(event);
  };

  Node.prototype.keyupdowned = function (eventName, keyCode) {
    var event;
    event = document.createEvent('UIEvents');
    event.initEvent(eventName, true, true);
    event.keyCode = keyCode;
    event.which = keyCode;
    event.charCode = 0;
    return this.element.dispatchEvent(event);
  };

  Node.prototype.keypressed = function (altKey, ctrlKey, shiftKey, metaKey, keyCode, charCode) {
    var event;
    event = document.createEvent('UIEvents');
    event.initEvent('keypress', true, true);
    event.window = this.agent.window;
    event.altKey = altKey;
    event.ctrlKey = ctrlKey;
    event.shiftKey = shiftKey;
    event.metaKey = metaKey;
    event.keyCode = keyCode;
    event.charCode = charCode;
    event.which = keyCode;
    return this.element.dispatchEvent(event);
  };

  /**
   * Tells if the node is inside the body of the document and not somewhere else
   * @return {boolean}
   */
  Node.prototype.insideBody = function () {
    return this.element === document.body || document.evaluate('ancestor::body', this.element, null, XPathResult.BOOLEAN_TYPE, null).booleanValue;
  };

  /**
   * Returns all text visible or not of the node
   * @return {string}
   */
  Node.prototype.allText = function () {
    return this.element.textContent;
  };

  /**
   * Returns the inner html our outer
   * @returns {string}
   */
  Node.prototype.allHTML = function (type) {
    var returnType = type || 'inner';

    if (returnType === "inner") {
      return this.element.innerHTML;
    }

    if (returnType === "outer") {
      if (this.element.outerHTML) {
        return this.element.outerHTML;
      }
      // polyfill:
      var wrapper = document.createElement('div');
      wrapper.appendChild(this.element.cloneNode(true));
      return wrapper.innerHTML;
    }

    return '';
  };

  /**
   * If the element is visible then we return the text
   * @return {string}
   */
  Node.prototype.visibleText = function () {
    if (!this.isVisible(null)) {
      return null;
    }

    if (this.element.nodeName === "TEXTAREA") {
      return this.element.textContent;
    }

    return this.element.innerText;
  };

  /**
   * Deletes the actual text being represented by a selection object from the node's element DOM.
   * @return {*}
   */
  Node.prototype.deleteText = function () {
    var range;
    range = document.createRange();
    range.selectNodeContents(this.element);
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(range);
    return window.getSelection().deleteFromDocument();
  };

  /**
   * Returns all the attributes {name:value} in the element
   * @return {{}}
   */
  Node.prototype.getAttributes = function () {
    var attributes, i, elementAttributes;

    elementAttributes = this.element.attributes;
    attributes = {};
    for (i = 0; i < elementAttributes.length; i++) {
      attributes[elementAttributes[i].name] = elementAttributes[i].value.replace("\n", "\\n");
    }

    return attributes;
  };

  /**
   * Name speaks for it self, returns the value of a given attribute by name
   * @param name
   * @return {string}
   */
  Node.prototype.getAttribute = function (name) {
    if (name === 'checked' || name === 'selected' || name === 'multiple') {
      return this.element[name];
    }
    return this.element.getAttribute(name);
  };

  /**
   * Scrolls the current element into the visible area of the browser window
   * @return {*}
   */
  Node.prototype.scrollIntoView = function () {
    return this.element.scrollIntoViewIfNeeded();
  };

  /**
   *  Returns the element.value property with special treatment if the element is a select
   * @return {*}
   */
  Node.prototype.value = function () {
    var options, i, values;

    if (this.element.tagName.toLowerCase() === 'select' && this.element.multiple) {
      values = [];
      options = this.element.children;
      for (i = 0; i < options.length; i++) {
        if (options[i].selected) {
          values.push(options[i].value);
        }
      }
      return values;
    }

    return this.element.value;
  };

  /**
   * Sets a given value in the element value property by simulation key interaction
   * @param value
   * @return {*}
   */
  Node.prototype.set = function (value) {
    var char, keyCode, i, len;

    if (this.element.readOnly) {
      return null;
    }

    //respect the maxLength property if present
    if (this.element.maxLength >= 0) {
      value = value.substr(0, this.element.maxLength);
    }

    this.element.value = '';
    this.trigger('focus');

    if (this.element.type === 'number') {
      this.element.value = value;
    } else {
      for (i = 0, len = value.length; i < len; i++) {
        char = value[i];
        keyCode = this.characterToKeyCode(char);
        this.keyupdowned('keydown', keyCode);
        this.element.value += char;
        this.keypressed(false, false, false, false, char.charCodeAt(0), char.charCodeAt(0));
        this.keyupdowned('keyup', keyCode);
      }
    }

    this.changed();
    this.input();

    return this.trigger('blur');
  };

  /**
   * Is the node multiple
   * @return {boolean}
   */
  Node.prototype.isMultiple = function () {
    return this.element.multiple;
  };

  /**
   * Sets the value of an attribute given by name
   * @param name
   * @param value
   * @return {boolean}
   */
  Node.prototype.setAttribute = function (name, value) {
    if (value === null) {
      return this.removeAttribute(name);
    }

    this.element.setAttribute(name, value);
    return true;
  };

  /**
   *  Removes and attribute by name
   * @param name
   * @return {boolean}
   */
  Node.prototype.removeAttribute = function (name) {
    this.element.removeAttribute(name);
    return true;
  };

  /**
   *  Selects the current node
   * @param value
   * @return {boolean}
   */
  Node.prototype.select = function (value) {
    if (value === false && !this.element.parentNode.multiple) {
      return false;
    }

    this.element.selected = value;
    this.changed();
    return true;
  };

  /**
   * Selects the radio button that has the defined value
   * @param value
   * @return {boolean}
   */
  Node.prototype.selectRadioValue = function (value) {
    if (this.element.value == value) {
      this.element.checked = true;
      this.trigger('focus');
      this.trigger('click');
      this.changed();
      return true;
    }

    var formElements = this.element.form.elements;
    var name = this.element.getAttribute('name');
    var element, i;

    var deselectAllRadios = function (elements, radioName) {
      var inputRadioElement;

      for (i = 0; i < elements.length; i++) {
        inputRadioElement = elements[i];
        if (inputRadioElement.tagName.toLowerCase() == 'input' && inputRadioElement.type.toLowerCase() == 'radio' && inputRadioElement.name == radioName) {
          inputRadioElement.checked = false;
        }
      }
    };

    var radioChange = function (radioElement) {
      var radioEvent;
      radioEvent = document.createEvent('HTMLEvents');
      radioEvent.initEvent('change', true, false);
      return radioElement.dispatchEvent(radioEvent);
    };

    var radioClickEvent = function (radioElement, name) {
      var radioEvent;
      radioEvent = document.createEvent('MouseEvent');
      radioEvent.initMouseEvent(name, true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
      return radioElement.dispatchEvent(radioEvent);
    };

    if (!name) {
      throw new Poltergeist.BrowserError('The radio button does not have the value "' + value + '"');
    }

    for (i = 0; i < formElements.length; i++) {
      element = formElements[i];
      if (element.tagName.toLowerCase() == 'input' && element.type.toLowerCase() == 'radio' && element.name === name) {
        if (value === element.value) {
          deselectAllRadios(formElements, name);
          element.checked = true;
          radioClickEvent(element, 'click');
          radioChange(element);
          return true;
        }
      }
    }

    throw new Poltergeist.BrowserError('The radio group "' + name + '" does not have an option "' + value + '"');
  };

  /**
   *  Checks or uncheck a radio option
   * @param value
   * @return {boolean}
   */
  Node.prototype.checked = function (value) {
    //TODO: add error control for the checked stuff
    this.element.checked = value;
    return true;
  };

  /**
   * Returns the element tag name as is, no transformations done
   * @return {string}
   */
  Node.prototype.tagName = function () {
    return this.element.tagName;
  };

  /**
   * Checks if the element is visible either by itself of because the parents are visible
   * @param element
   * @return {boolean}
   */
  Node.prototype.isVisible = function (element) {
    var nodeElement = element || this.element;

    if (window.getComputedStyle(nodeElement).display === 'none') {
      return false;
    } else if (nodeElement.parentElement) {
      return this.isVisible(nodeElement.parentElement);
    } else {
      return true;
    }
  };

  /**
   * Is the node disabled for operations with it?
   * @return {boolean}
   */
  Node.prototype.isDisabled = function () {
    return this.element.disabled || this.element.tagName === 'OPTION' && this.element.parentNode.disabled;
  };

  /**
   * Does the node contains the selections
   * @return {boolean}
   */
  Node.prototype.containsSelection = function () {
    var selectedNode;

    selectedNode = document.getSelection().focusNode;
    if (!selectedNode) {
      return false;
    }
    //this magic number is NODE.TEXT_NODE
    if (selectedNode.nodeType === 3) {
      selectedNode = selectedNode.parentNode;
    }

    return this.element.contains(selectedNode);
  };

  /**
   * Returns the offset of the node in relation to the current frame
   * @return {{top: number, left: number}}
   */
  Node.prototype.frameOffset = function () {
    var offset, rect, style, win;
    win = window;
    offset = {
      top: 0,
      left: 0
    };
    while (win.frameElement) {
      rect = win.frameElement.getClientRects()[0];
      style = win.getComputedStyle(win.frameElement);
      win = win.parent;
      offset.top += rect.top + parseInt(style.getPropertyValue("padding-top"), 10);
      offset.left += rect.left + parseInt(style.getPropertyValue("padding-left"), 10);
    }
    return offset;
  };

  /**
   * Returns the object position in relation to the window
   * @return {{top: *, right: *, left: *, bottom: *, width: *, height: *}}
   */
  Node.prototype.position = function () {
    var frameOffset, pos, rect;

    rect = this.element.getClientRects()[0];
    if (!rect) {
      throw new PoltergeistAgent.ObsoleteNode;
    }

    frameOffset = this.frameOffset();
    pos = {
      top: rect.top + frameOffset.top,
      right: rect.right + frameOffset.left,
      left: rect.left + frameOffset.left,
      bottom: rect.bottom + frameOffset.top,
      width: rect.width,
      height: rect.height
    };

    return pos;
  };

  /**
   * Triggers a DOM event related to the node element
   * @param name
   * @return {boolean}
   */
  Node.prototype.trigger = function (name) {
    var event;
    if (Node.EVENTS.MOUSE.indexOf(name) !== -1) {
      event = document.createEvent('MouseEvent');
      event.initMouseEvent(name, true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
    } else if (Node.EVENTS.FOCUS.indexOf(name) !== -1) {
      event = this.obtainEvent(name);
    } else if (Node.EVENTS.FORM.indexOf(name) !== -1) {
      event = this.obtainEvent(name);
    } else {
      throw "Unknown event";
    }
    return this.element.dispatchEvent(event);
  };

  /**
   * Creates a generic HTMLEvent to be use in the node element
   * @param name
   * @return {Event}
   */
  Node.prototype.obtainEvent = function (name) {
    var event;
    event = document.createEvent('HTMLEvents');
    event.initEvent(name, true, true);
    return event;
  };

  /**
   * Does a check to see if the coordinates given
   * match the node element or some of the parents chain
   * @param x
   * @param y
   * @return {*}
   */
  Node.prototype.mouseEventTest = function (x, y) {
    var elementForXpath, frameOffset, origEl;

    frameOffset = this.frameOffset();
    x -= frameOffset.left;
    y -= frameOffset.top;

    elementForXpath = origEl = document.elementFromPoint(x, y);
    while (elementForXpath) {
      if (elementForXpath === this.element) {
        return {
          status: 'success'
        };
      } else {
        elementForXpath = elementForXpath.parentNode;
      }
    }

    return {
      status: 'failure',
      selector: origEl && this.getSelector(origEl)
    };
  };

  /**
   * Returns the node selector in CSS style (NO xpath)
   * @param elementForXpath
   * @return {string}
   */
  Node.prototype.getSelector = function (elementForXpath) {
    var className, selector, i, len, classNames;

    selector = elementForXpath.tagName !== 'HTML' ? this.getSelector(elementForXpath.parentNode) + ' ' : '';
    selector += elementForXpath.tagName.toLowerCase();

    if (elementForXpath.id) {
      selector += "#" + elementForXpath.id;
    }

    classNames = elementForXpath.classList;
    for (i = 0, len = classNames.length; i < len; i++) {
      className = classNames[i];
      selector += "." + className;
    }

    return selector;
  };

  /**
   * Returns the key code that represents the character
   * @param character
   * @return {number}
   */
  Node.prototype.characterToKeyCode = function (character) {
    var code, specialKeys;
    code = character.toUpperCase().charCodeAt(0);
    specialKeys = {
      96: 192,
      45: 189,
      61: 187,
      91: 219,
      93: 221,
      92: 220,
      59: 186,
      39: 222,
      44: 188,
      46: 190,
      47: 191,
      127: 46,
      126: 192,
      33: 49,
      64: 50,
      35: 51,
      36: 52,
      37: 53,
      94: 54,
      38: 55,
      42: 56,
      40: 57,
      41: 48,
      95: 189,
      43: 187,
      123: 219,
      125: 221,
      124: 220,
      58: 186,
      34: 222,
      60: 188,
      62: 190,
      63: 191
    };
    return specialKeys[code] || code;
  };

  /**
   * Checks if one element is equal to other given by its node id
   * @param other_id
   * @return {boolean}
   */
  Node.prototype.isDOMEqual = function (other_id) {
    return this.element === this.agent.get(other_id).element;
  };

  /**
   * The following function allows one to pass an element and an XML document to find a unique string XPath expression leading back to that element.
   * @param element
   * @return {string}
   */
  Node.prototype.getXPathForElement = function (element) {
    var elementForXpath = element || this.element;
    var xpath = '';
    var pos, tempitem2;

    while (elementForXpath !== document.documentElement) {
      pos = 0;
      tempitem2 = elementForXpath;
      while (tempitem2) {
        if (tempitem2.nodeType === 1 && tempitem2.nodeName === elementForXpath.nodeName) { // If it is ELEMENT_NODE of the same name
          pos += 1;
        }
        tempitem2 = tempitem2.previousSibling;
      }

      xpath = "*[name()='" + elementForXpath.nodeName + "' and namespace-uri()='" + (elementForXpath.namespaceURI === null ? '' : elementForXpath.namespaceURI) + "'][" + pos + ']' + '/' + xpath;

      elementForXpath = elementForXpath.parentNode;
    }

    xpath = '/*' + "[name()='" + document.documentElement.nodeName + "' and namespace-uri()='" + (elementForXpath.namespaceURI === null ? '' : elementForXpath.namespaceURI) + "']" + '/' + xpath;
    xpath = xpath.replace(/\/$/, '');
    return xpath;
  };

  /**
   * Deselect all the options for this element
   */
  Node.prototype.deselectAllOptions = function () {
    //TODO: error control when the node is not a select node
    var i, l = this.element.options.length;
    for (i = 0; i < l; i++) {
      this.element.options[i].selected = false;
    }
  };

  return Node;

})();

window.__poltergeist = new PoltergeistAgent;

document.addEventListener('DOMContentLoaded', function () {
  return console.log('__DOMContentLoaded');
});

window.confirm = function (message) {
  return true;
};

window.prompt = function (message, _default) {
  return _default || null;
};
