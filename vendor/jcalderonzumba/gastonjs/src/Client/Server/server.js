Poltergeist.Server = (function () {

  /**
   * Server constructor
   * @param owner
   * @param port
   * @constructor
   */
  function Server(owner, port) {
    this.server = require('webserver').create();
    this.port = port;
    this.owner = owner;
    this.webServer = null;
  }

  /**
   * Starts the web server
   */
  Server.prototype.start = function () {
    var self = this;
    this.webServer = this.server.listen(this.port, function (request, response) {
      self.handleRequest(request, response);
    });
  };

  /**
   * Send error back with code and message
   * @param response
   * @param code
   * @param message
   * @return {boolean}
   */
  Server.prototype.sendError = function (response, code, message) {
    response.statusCode = code;
    response.setHeader('Content-Type', 'application/json');
    response.write(JSON.stringify(message, null, 4));
    response.close();
    return true;
  };


  /**
   * Send response back to the client
   * @param response
   * @param data
   * @return {boolean}
   */
  Server.prototype.send = function (response, data) {
    console.log("RESPONSE: " + JSON.stringify(data, null, 4).substr(0, 200));

    response.statusCode = 200;
    response.setHeader('Content-Type', 'application/json');
    response.write(JSON.stringify(data, null, 4));
    response.close();
    return true;
  };

  /**
   * Handles a request to the server
   * @param request
   * @param response
   * @return {boolean}
   */
  Server.prototype.handleRequest = function (request, response) {
    var commandData;
    if (request.method !== "POST") {
      return this.sendError(response, 405, "Only POST method is allowed in the service");
    }
    console.log("REQUEST: " + request.post + "\n");
    try {
      commandData = JSON.parse(request.post);
    } catch (parseError) {
      return this.sendError(response, 400, "JSON data invalid error: " + parseError.message);
    }

    return this.owner.serverRunCommand(commandData, response);
  };

  return Server;
})();
