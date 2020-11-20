Poltergeist = (function () {

  /**
   * The MAIN class of the project
   * @param port
   * @param width
   * @param height
   * @param jsErrors
   * @constructor
   */
  function Poltergeist(port, width, height, jsErrors) {
    var self;
    this.browser = new Poltergeist.Browser(this, width, height, jsErrors);

    this.commandServer = new Poltergeist.Server(this, port);
    this.commandServer.start();

    self = this;

    phantom.onError = function (message, stack) {
      return self.onError(message, stack);
    };

    this.running = false;
  }

  /**
   * Tries to execute a command send by a client and returns the command response
   * or error if something happened
   * @param command
   * @param serverResponse
   * @return {boolean}
   */
  Poltergeist.prototype.serverRunCommand = function (command, serverResponse) {
    var error;
    this.running = true;
    try {
      return this.browser.serverRunCommand(command, serverResponse);
    } catch (_error) {
      error = _error;
      if (error instanceof Poltergeist.Error) {
        return this.serverSendError(error, serverResponse);
      }
      return this.serverSendError(new Poltergeist.BrowserError(error.toString(), error.stack), serverResponse);
    }
  };

  /**
   * Sends error back to the client
   * @param error
   * @param serverResponse
   * @return {boolean}
   */
  Poltergeist.prototype.serverSendError = function (error, serverResponse) {
    var errorObject;
    errorObject = {
      error: {
        name: error.name || 'Generic',
        args: error.args && error.args() || [error.toString()]
      }
    };
    return this.commandServer.sendError(serverResponse, 500, errorObject);
  };

  /**
   * Send the response back to the client
   * @param response        Data to send to the client
   * @param serverResponse  Phantomjs response object associated to the client request
   * @return {boolean}
   */
  Poltergeist.prototype.serverSendResponse = function (response, serverResponse) {
    return this.commandServer.send(serverResponse, {response: response});
  };

  return Poltergeist;
})();

window.Poltergeist = Poltergeist;
