
// Global killswitch
if (isJsEnabled()) {
  addLoadEvent(uploadAutoAttach);
}

/**
 * Attaches the upload behaviour to the upload form.
 */
function uploadAutoAttach() {
  var inputs = document.getElementsByTagName('input');
  for (i = 0; input = inputs[i]; i++) {
    if (input && hasClass(input, 'upload')) {
      var uri = input.value;
      // Extract the button ID based on a substring of the input name: edit[foo][bar] -> foo-bar
      var button = input.name.substr(5, input.name.length - 6).replace('][', '-');
      var wrapper = button + '-wrapper';
      var hide = button + '-hide';
      var upload = new jsUpload(uri, button, wrapper, hide);
    }
  }
}

/**
 * JS upload object.
 */
function jsUpload(uri, button, wrapper, hide) {
  this.button = button;
  this.wrapper = wrapper;
  this.hide = hide;
  redirectFormButton(uri, $(button), this);
}

/**
 * Handler for the form redirection submission.
 */
jsUpload.prototype.onsubmit = function () {
  var hide = $(this.hide);
  // Insert progressbar and stretch to take the same space.
  this.progress = new progressBar('uploadprogress');
  this.progress.setProgress(-1, 'Uploading file');
  this.progress.element.style.width = '28em';
  this.progress.element.style.height = hide.offsetHeight +'px';
  hide.parentNode.insertBefore(this.progress.element, hide);
  // Hide file form (cannot use display: none, this mysteriously aborts form
  // submission in Konqueror)
  hide.style.position = 'absolute';
  hide.style.left = '-2000px';
}

/**
 * Handler for the form redirection completion.
 */
jsUpload.prototype.oncomplete = function (data) {
  // Remove progressbar
  removeNode(this.progress.element);
  this.progress = null;
  // Replace form and re-attach behaviour
  $(this.wrapper).innerHTML = data;
  uploadAutoAttach();
}

/**
 * Handler for the form redirection error.
 */
jsUpload.prototype.onerror = function (error) {
  alert('An error occurred:\n\n'+ error);
  // Remove progressbar
  removeNode(this.progress.element);
  this.progress = null;
  // Undo hide
  $(this.hide).style.position = 'static';
  $(this.hide).style.left = '0px';
}
