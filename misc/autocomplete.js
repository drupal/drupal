
// Global Killswitch
if (isJsEnabled()) {
  addLoadEvent(autocompleteAutoAttach);
}

/**
 * Attaches the autocomplete behaviour to all required fields
 */
function autocompleteAutoAttach() {
  var acdb = [];
  var inputs = document.getElementsByTagName('input');
  for (i = 0; input = inputs[i]; i++) {
    if (input && hasClass(input, 'autocomplete')) {
      uri = input.value;
      if (!acdb[uri]) {
        acdb[uri] = new ACDB(uri);
      }
      input = $(input.id.substr(0, input.id.length - 13));
      input.setAttribute('autocomplete', 'OFF');
      addSubmitEvent(input.form, autocompleteSubmit);
      new jsAC(input, acdb[uri]);
    }
  }
}

/**
 * Prevents the form from submitting if the suggestions popup is open
 */
function autocompleteSubmit() {
  var popup = document.getElementById('autocomplete');
  if (popup) {
    popup.owner.hidePopup();
    return false;
  }
  return true;
}


/**
 * An AutoComplete object
 */
function jsAC(input, db) {
  var ac = this;
  this.input = input;
  this.db = db;
  this.input.onkeydown = function (event) { return ac.onkeydown(this, event); };
  this.input.onkeyup = function (event) { ac.onkeyup(this, event) };
  this.input.onblur = function () { ac.hidePopup(); ac.db.cancel(); };
  this.popup = document.createElement('div');
  this.popup.id = 'autocomplete';
  this.popup.owner = this;
};

/**
 * Hides the autocomplete suggestions
 */
jsAC.prototype.hidePopup = function (keycode) {
  if (this.selected && ((keycode && keycode != 46 && keycode != 8 && keycode != 27) || !keycode)) {
    this.input.value = this.selected.autocompleteValue;
  }
  if (this.popup.parentNode && this.popup.parentNode.tagName) {
    removeNode(this.popup);
  }
  this.selected = false;
}


/**
 * Handler for the "keydown" event
 */
jsAC.prototype.onkeydown = function (input, e) {
  if (!e) {
    e = window.event;
  }
  switch (e.keyCode) {
    case 40: // down arrow
      this.selectDown();
      return false;
    case 38: // up arrow
      this.selectUp();
      return false;
    default: // all other keys
      return true;
  }
}

/**
 * Handler for the "keyup" event
 */
jsAC.prototype.onkeyup = function (input, e) {
  if (!e) {
    e = window.event;
  }
  switch (e.keyCode) {
    case 16: // shift
    case 17: // ctrl
    case 18: // alt
    case 20: // caps lock
    case 33: // page up
    case 34: // page down
    case 35: // end
    case 36: // home
    case 37: // left arrow
    case 38: // up arrow
    case 39: // right arrow
    case 40: // down arrow
      return true;

    case 9:  // tab
    case 13: // enter
    case 27: // esc
      this.hidePopup(e.keyCode);
      return true;

    default: // all other keys
      if (input.value.length > 0)
        this.populatePopup();
      else
        this.hidePopup(e.keyCode);
      return true;
  }
}

/**
 * Puts the currently highlighted suggestion into the autocomplete field
 */
jsAC.prototype.select = function (node) {
  this.input.value = node.autocompleteValue;
}

/**
 * Highlights the next suggestion
 */
jsAC.prototype.selectDown = function () {
  if (this.selected && this.selected.nextSibling) {
    this.highlight(this.selected.nextSibling);
  }
  else {
    var lis = this.popup.getElementsByTagName('li');
    if (lis.length > 0) {
      this.highlight(lis[0]);
    }
  }
}

/**
 * Highlights the previous suggestion
 */
jsAC.prototype.selectUp = function () {
  if (this.selected && this.selected.previousSibling) {
    this.highlight(this.selected.previousSibling);
  }
}

/**
 * Highlights a suggestion
 */
jsAC.prototype.highlight = function (node) {
  removeClass(this.selected, 'selected');
  addClass(node, 'selected');
  this.selected = node;
}

/**
 * Unhighlights a suggestion
 */
jsAC.prototype.unhighlight = function (node) {
  removeClass(node, 'selected');
  this.selected = false;
}

/**
 * Positions the suggestions popup and starts a search
 */
jsAC.prototype.populatePopup = function () {
  var ac = this;
  var pos = absolutePosition(this.input);
  this.selected = false;
  this.popup.style.top   = (pos.y + this.input.offsetHeight) +'px';
  this.popup.style.left  = pos.x +'px';
  this.popup.style.width = (this.input.offsetWidth - 4) +'px';
  this.db.owner = this;
  this.db.search(this.input.value);
}

/**
 * Fills the suggestion popup with any matches received
 */
jsAC.prototype.found = function (matches) {
  while (this.popup.hasChildNodes()) {
    this.popup.removeChild(this.popup.childNodes[0]);
  }
  if (!this.popup.parentNode || !this.popup.parentNode.tagName) {
    document.getElementsByTagName('body')[0].appendChild(this.popup);
  }
  var ul = document.createElement('ul');
  var ac = this;

  for (key in matches) {
    var li = document.createElement('li');
    var div = document.createElement('div');
    div.innerHTML = matches[key];
    li.appendChild(div);
    li.autocompleteValue = key;
    li.onmousedown = function() { ac.select(this); };
    li.onmouseover = function() { ac.highlight(this); };
    li.onmouseout  = function() { ac.unhighlight(this); };
    ul.appendChild(li);
  }

  if (ul.childNodes.length > 0) {
    this.popup.appendChild(ul);
  }
  else {
    this.hidePopup();
  }
  removeClass(this.input, 'throbbing');
}

/**
 * An AutoComplete DataBase object
 */
function ACDB(uri) {
  this.uri = uri;
  this.delay = 300;
  this.cache = {};
}

/**
 * Performs a cached and delayed search
 */
ACDB.prototype.search = function(searchString) {
  this.searchString = searchString;
  if (this.cache[searchString]) {
    return this.owner.found(this.cache[searchString]);
  }
  if (this.timer) {
    clearTimeout(this.timer);
  }
  var db = this;
  this.timer = setTimeout(function() {
    addClass(db.owner.input, 'throbbing');
    db.transport = HTTPGet(db.uri +'/'+ encodeURIComponent(searchString), db.receive, db);
  }, this.delay);
}

/**
 * HTTP callback function. Passes suggestions to the autocomplete object
 */
ACDB.prototype.receive = function(string, xmlhttp, acdb) {
  // Note: Safari returns 'undefined' status if the request returns no data.
  if (xmlhttp.status != 200 && typeof xmlhttp.status != 'undefined') {
    removeClass(acdb.owner.input, 'throbbing');
    return alert('An HTTP error '+ xmlhttp.status +' occured.\n'+ acdb.uri);
  }
  // Parse back result
  var matches = parseJson(string);
  if (typeof matches['status'] == 'undefined' || matches['status'] != 0) {
    acdb.cache[acdb.searchString] = matches;
    acdb.owner.found(matches);
  }
}

/**
 * Cancels the current autocomplete request
 */
ACDB.prototype.cancel = function() {
  if (this.owner) removeClass(this.owner.input, 'throbbing');
  if (this.timer) clearTimeout(this.timer);
  if (this.transport) {
    this.transport.onreadystatechange = function() {};
    this.transport.abort();
  }
}
