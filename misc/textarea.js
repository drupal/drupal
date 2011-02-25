
if (isJsEnabled()) {
  addLoadEvent(textAreaAutoAttach);
}

function textAreaAutoAttach(event, parent) {
  if (typeof parent == 'undefined') {
    // Attach to all visible textareas.
    textareas = document.getElementsByTagName('textarea');
  }
  else {
    // Attach to all visible textareas inside parent.
    textareas = parent.getElementsByTagName('textarea');
  }
  var textarea;
  for (var i = 0; textarea = textareas[i]; ++i) {
    if (hasClass(textarea, 'resizable') && !hasClass(textarea.nextSibling, 'grippie')) {
      if (typeof dimensions(textarea).width != 'undefined' && dimensions(textarea).width != 0) {
        new textArea(textarea);
      }
    }
  }
}

function textArea(element) {
  var ta = this;
  this.element = element;
  this.parent = this.element.parentNode;
  this.dimensions = dimensions(element);

  // Prepare wrapper
  this.wrapper = document.createElement('div');
  this.wrapper.className = 'resizable-textarea';
  this.parent.insertBefore(this.wrapper, this.element);

  // Add grippie and measure it
  this.grippie = document.createElement('div');
  this.grippie.className = 'grippie';
  this.wrapper.appendChild(this.grippie);
  this.grippie.dimensions = dimensions(this.grippie);
  this.grippie.onmousedown = function (e) { ta.beginDrag(e); };

  // Set wrapper and textarea dimensions
  this.wrapper.style.height = this.dimensions.height + this.grippie.dimensions.height + 1 +'px';
  this.element.style.marginBottom = '0px';
  this.element.style.width = '100%';
  this.element.style.height = this.dimensions.height +'px';

  // Wrap textarea, working around an IE margin bug.
  removeNode(this.element);
  this.iewrap = document.createElement('span');
  this.iewrap.appendChild(this.element);
  this.wrapper.insertBefore(this.iewrap, this.grippie);

  // Measure difference between desired and actual textarea dimensions to account for padding/borders
  this.widthOffset = dimensions(this.wrapper).width - this.dimensions.width;

  // Make the grippie line up in various browsers
  if (window.opera) {
    // Opera
    this.grippie.style.marginRight = '4px';
  }
  if (document.all && !window.opera) {
    // IE
    this.grippie.style.width = '100%';
    this.grippie.style.paddingLeft = '2px';
  }
  // Mozilla
  this.element.style.MozBoxSizing = 'border-box';

  this.heightOffset = absolutePosition(this.grippie).y - absolutePosition(this.element).y - this.dimensions.height;
}

textArea.prototype.beginDrag = function (event) {
  if (document.isDragging) {
    return;
  }
  document.isDragging = true;

  event = event || window.event;
  // Capture mouse
  var cp = this;
  this.oldMoveHandler = document.onmousemove;
  document.onmousemove = function(e) { cp.handleDrag(e); };
  this.oldUpHandler = document.onmouseup;
  document.onmouseup = function(e) { cp.endDrag(e); };

  // Store drag offset from grippie top
  var pos = absolutePosition(this.grippie);
  this.dragOffset = event.clientY - pos.y;

  // Make transparent
  this.element.style.opacity = 0.5;

  // Process
  this.handleDrag(event);
}

textArea.prototype.handleDrag = function (event) {
  event = event || window.event;
  // Get coordinates relative to text area
  var pos = absolutePosition(this.element);
  var y = event.clientY - pos.y;

  // Set new height
  var height = Math.max(32, y - this.dragOffset - this.heightOffset);
  this.wrapper.style.height = height + this.grippie.dimensions.height + 1 + 'px';
  this.element.style.height = height + 'px';

  // Avoid text selection
  stopEvent(event);
}

textArea.prototype.endDrag = function (event) {
  // Uncapture mouse
  document.onmousemove = this.oldMoveHandler;
  document.onmouseup = this.oldUpHandler;

  // Restore opacity
  this.element.style.opacity = 1.0;
  document.isDragging = false;
}

