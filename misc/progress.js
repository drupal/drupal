
/**
 * A progressbar object. Initialized with the given id. Must be inserted into
 * the DOM afterwards through progressBar.element.
 *
 * method is the function which will perform the HTTP request to get the
 * progress bar state. Either HTTPGet or HTTPPost.
 *
 * e.g. pb = new progressBar('myProgressBar');
 *      some_element.appendChild(pb.element);
 */
function progressBar(id, updateCallback, method, errorCallback) {
  var pb = this;
  this.id = id;
  this.method = method ? method : HTTPGet;
  this.updateCallback = updateCallback;
  this.errorCallback = errorCallback;

  this.element = document.createElement('div');
  this.element.id = id;
  this.element.className = 'progress';
  this.element.innerHTML = '<div class="percentage"></div>'+
                           '<div class="message">&nbsp;</div>'+
                           '<div class="bar"><div class="filled"></div></div>';
}

/**
 * Set the percentage and status message for the progressbar.
 */
progressBar.prototype.setProgress = function (percentage, message) {
  var divs = this.element.getElementsByTagName('div');
  var div;
  for (var i = 0; div = divs[i]; ++i) {
    if (percentage >= 0) {
      if (hasClass(divs[i], 'filled')) {
        divs[i].style.width = percentage + '%';
      }
      if (hasClass(divs[i], 'percentage')) {
        divs[i].innerHTML = percentage + '%';
      }
    }
    if (hasClass(divs[i], 'message')) {
      divs[i].innerHTML = message;
    }
  }
  if (this.updateCallback) {
    this.updateCallback(percentage, message, this);
  }
}

/**
 * Start monitoring progress via Ajax.
 */
progressBar.prototype.startMonitoring = function (uri, delay) {
  this.delay = delay;
  this.uri = uri;
  this.sendPing();
}

/**
 * Stop monitoring progress via Ajax.
 */
progressBar.prototype.stopMonitoring = function () {
  clearTimeout(this.timer);
  // This allows monitoring to be stopped from within the callback
  this.uri = null;
}

/**
 * Request progress data from server.
 */
progressBar.prototype.sendPing = function () {
  if (this.timer) {
    clearTimeout(this.timer);
  }
  if (this.uri) {
    this.method(this.uri, this.receivePing, this, '');
  }
}

/**
 * HTTP callback function. Passes data back to the progressbar and sets a new
 * timer for the next ping.
 */
progressBar.prototype.receivePing = function (string, xmlhttp, pb) {
  if (xmlhttp.status != 200) {
    return pb.displayError('An HTTP error '+ xmlhttp.status +' occured.\n'+ pb.uri);
  }
  // Parse response
  var progress = parseJson(string);
  // Display errors
  if (progress.status == 0) {
    pb.displayError(progress.data);
    return;
  }

  // Update display
  pb.setProgress(progress.percentage, progress.message);
  // Schedule next timer
  pb.timer = setTimeout(function() { pb.sendPing(); }, pb.delay);
}

/**
 * Display errors on the page.
 */
progressBar.prototype.displayError = function (string) {
  var error = document.createElement('div');
  error.className = 'error';
  error.innerHTML = string;

  this.element.style.display = 'none';
  this.element.parentNode.insertBefore(error, this.element);

  if (this.errorCallback) {
    this.errorCallback(this);
  }
}
