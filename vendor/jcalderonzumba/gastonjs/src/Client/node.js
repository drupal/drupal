var __slice = [].slice;

Poltergeist.Node = (function () {
  var name, _fn, _i, _len, _ref;
  var xpathStringLiteral;

  Node.DELEGATES = ['allText', 'visibleText', 'getAttribute', 'value', 'set', 'checked',
    'setAttribute', 'isObsolete', 'removeAttribute', 'isMultiple',
    'select', 'tagName', 'find', 'getAttributes', 'isVisible',
    'position', 'trigger', 'input', 'parentId', 'parentIds', 'mouseEventTest',
    'scrollIntoView', 'isDOMEqual', 'isDisabled', 'deleteText', 'selectRadioValue',
    'containsSelection', 'allHTML', 'changed', 'getXPathForElement', 'deselectAllOptions'];

  function Node(page, id) {
    this.page = page;
    this.id = id;
  }

  /**
   * Returns the parent Node of this Node
   * @return {Poltergeist.Node}
   */
  Node.prototype.parent = function () {
    return new Poltergeist.Node(this.page, this.parentId());
  };

  _ref = Node.DELEGATES;

  _fn = function (name) {
    return Node.prototype[name] = function () {
      var args = [];
      if (arguments.length >= 1) {
        args = __slice.call(arguments, 0)
      }
      return this.page.nodeCall(this.id, name, args);
    };
  };

  //Adding all the delegates from the agent Node to this Node
  for (_i = 0, _len = _ref.length; _i < _len; _i++) {
    name = _ref[_i];
    _fn(name);
  }

  xpathStringLiteral = function (s) {
    if (s.indexOf('"') === -1)
      return '"' + s + '"';
    if (s.indexOf("'") === -1)
      return "'" + s + "'";
    return 'concat("' + s.replace(/"/g, '",\'"\',"') + '")';
  };

  /**
   *  Gets an x,y position tailored for mouse event actions
   * @return {{x, y}}
   */
  Node.prototype.mouseEventPosition = function () {
    var middle, pos, viewport;

    viewport = this.page.viewportSize();
    pos = this.position();
    middle = function (start, end, size) {
      return start + ((Math.min(end, size) - start) / 2);
    };

    return {
      x: middle(pos.left, pos.right, viewport.width),
      y: middle(pos.top, pos.bottom, viewport.height)
    };
  };

  /**
   * Executes a phantomjs native mouse event
   * @param name
   * @return {{x, y}}
   */
  Node.prototype.mouseEvent = function (name) {
    var pos, test;

    this.scrollIntoView();
    pos = this.mouseEventPosition();
    test = this.mouseEventTest(pos.x, pos.y);

    if (test.status === 'success') {
      if (name === 'rightclick') {
        this.page.mouseEvent('click', pos.x, pos.y, 'right');
        this.trigger('contextmenu');
      } else {
        this.page.mouseEvent(name, pos.x, pos.y);
      }
      return pos;
    } else {
      throw new Poltergeist.MouseEventFailed(name, test.selector, pos);
    }
  };

  /**
   * Executes a mouse based drag from one node to another
   * @param other
   * @return {{x, y}}
   */
  Node.prototype.dragTo = function (other) {
    var otherPosition, position;

    this.scrollIntoView();
    position = this.mouseEventPosition();
    otherPosition = other.mouseEventPosition();
    this.page.mouseEvent('mousedown', position.x, position.y);
    return this.page.mouseEvent('mouseup', otherPosition.x, otherPosition.y);
  };

  /**
   * Checks if one node is equal to another
   * @param other
   * @return {boolean}
   */
  Node.prototype.isEqual = function (other) {
    return this.page === other.page && this.isDOMEqual(other.id);
  };


  /**
   * The value to select
   * @param value
   * @param multiple
   */
  Node.prototype.select_option = function (value, multiple) {
    var tagName = this.tagName().toLowerCase();

    if (tagName === "select") {
      var escapedOption = xpathStringLiteral(value);
      // The value of an option is the normalized version of its text when it has no value attribute
      var optionQuery = ".//option[@value = " + escapedOption + " or (not(@value) and normalize-space(.) = " + escapedOption + ")]";
      var ids = this.find("xpath", optionQuery);
      var polterNode = this.page.get(ids[0]);

      if (multiple || !this.getAttribute('multiple')) {
        if (!polterNode.getAttribute('selected')) {
          polterNode.select(value);
          this.trigger('click');
          this.input();
        }
        return true;
      }

      this.deselectAllOptions();
      polterNode.select(value);
      this.trigger('click');
      this.input();
      return true;
    } else if (tagName === "input" && this.getAttribute("type").toLowerCase() === "radio") {
      return this.selectRadioValue(value);
    }

    throw new Poltergeist.BrowserError("The element is not a select or radio input");

  };

  return Node;

}).call(this);
