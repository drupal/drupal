
if (isJsEnabled()) {
  addLoadEvent(collapseAutoAttach);
}

function collapseAutoAttach() {
  var fieldsets = document.getElementsByTagName('fieldset');
  var legend, fieldset;
  for (var i = 0; fieldset = fieldsets[i]; i++) {
    if (!hasClass(fieldset, 'collapsible')) {
      continue;
    }
    legend = fieldset.getElementsByTagName('legend');
    if (legend.length == 0) {
      continue;
    }
    legend = legend[0];
    var a = document.createElement('a');
    a.href = '#';
    a.onclick = function() {
      toggleClass(this.parentNode.parentNode, 'collapsed');
      if (!hasClass(this.parentNode.parentNode, 'collapsed')) {
        collapseScrollIntoView(this.parentNode.parentNode);
        if (typeof textAreaAutoAttach != 'undefined') {
          // Add the grippie to a textarea in a collapsed fieldset.
          textAreaAutoAttach(null, this.parentNode.parentNode);
        }
      }
      this.blur();
      return false;
    };
    a.innerHTML = legend.innerHTML;
    while (legend.hasChildNodes()) {
      removeNode(legend.childNodes[0]);
    }
    legend.appendChild(a);
    collapseEnsureErrorsVisible(fieldset);
  }
}

function collapseEnsureErrorsVisible(fieldset) {
  if (!hasClass(fieldset, 'collapsed')) {
    return;
  }
  var inputs = [];
  inputs = inputs.concat(fieldset.getElementsByTagName('input'));
  inputs = inputs.concat(fieldset.getElementsByTagName('textarea'));
  inputs = inputs.concat(fieldset.getElementsByTagName('select'));
  for (var j = 0; j<3; j++) {
    for (var i = 0; i < inputs[j].length; i++) {
      if (hasClass(inputs[j][i], 'error')) {
        return removeClass(fieldset, 'collapsed');
      }
    }
  }
}

function collapseScrollIntoView(node) {
  var h = self.innerHeight || document.documentElement.clientHeight || document.body.clientHeight || 0;
  var offset = self.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
  var pos = absolutePosition(node);
  if (pos.y + node.scrollHeight > h + offset) {
    if (node.scrollHeight > h) {
      window.scrollTo(0, pos.y);
    } else {
      window.scrollTo(0, pos.y + node.scrollHeight - h);
    }
  }
}
