/**
 * Guzzle node.js test server to return queued responses to HTTP requests and
 * expose a RESTful API for enqueueing responses and retrieving the requests
 * that have been received.
 *
 * - Delete all requests that have been received:
 *      > DELETE /guzzle-server/requests
 *      > Host: 127.0.0.1:8125
 *
 *  - Enqueue responses
 *      > PUT /guzzle-server/responses
 *      > Host: 127.0.0.1:8125
 *      >
 *      > [{'status': 200, 'reason': 'OK', 'headers': {}, 'body': '' }]
 *
 *  - Get the received requests
 *      > GET /guzzle-server/requests
 *      > Host: 127.0.0.1:8125
 *
 *      < HTTP/1.1 200 OK
 *      <
 *      < [{'http_method': 'GET', 'uri': '/', 'headers': {}, 'body': 'string'}]
 *
 *  - Shutdown the server
 *      > DELETE /guzzle-server
 *      > Host: 127.0.0.1:8125
 *
 * @package Guzzle PHP <http://www.guzzlephp.org>
 * @license See the LICENSE file that was distributed with this source code.
 */

var http = require('http');
var url = require('url');

/**
 * Guzzle node.js server
 * @class
 */
var GuzzleServer = function(port, log) {

    this.port = port;
    this.log = log;
    this.responses = [];
    this.requests = [];
    var that = this;

    var controlRequest = function(request, req, res) {
        if (req.url == '/guzzle-server/perf') {
            res.writeHead(200, 'OK', {'Content-Length': 16});
            res.end('Body of response');
        } else if (req.method == 'DELETE') {
            if (req.url == '/guzzle-server/requests') {
                // Clear the received requests
                that.requests = [];
                res.writeHead(200, 'OK', { 'Content-Length': 0 });
                res.end();
                if (that.log) {
                    console.log('Flushing requests');
                }
            } else if (req.url == '/guzzle-server') {
                // Shutdown the server
                res.writeHead(200, 'OK', { 'Content-Length': 0, 'Connection': 'close' });
                res.end();
                if (that.log) {
                    console.log('Shutting down');
                }
                that.server.close();
            }
        } else if (req.method == 'GET') {
            if (req.url === '/guzzle-server/requests') {
                if (that.log) {
                    console.log('Sending received requests');
                }
                // Get received requests
                var body = JSON.stringify(that.requests);
                res.writeHead(200, 'OK', { 'Content-Length': body.length });
                res.end(body);
            }
        } else if (req.method == 'PUT' && req.url == '/guzzle-server/responses') {
            if (that.log) {
                console.log('Adding responses...');
            }
            if (!request.body) {
                if (that.log) {
                    console.log('No response data was provided');
                }
                res.writeHead(400, 'NO RESPONSES IN REQUEST', { 'Content-Length': 0 });
            } else {
                that.responses = eval('(' + request.body + ')');
                for (var i = 0; i < that.responses.length; i++) {
                    if (that.responses[i].body) {
                        that.responses[i].body = new Buffer(that.responses[i].body, 'base64');
                    }
                }
                if (that.log) {
                    console.log(that.responses);
                }
                res.writeHead(200, 'OK', { 'Content-Length': 0 });
            }
            res.end();
        }
    };

    var receivedRequest = function(request, req, res) {
        if (req.url.indexOf('/guzzle-server') === 0) {
            controlRequest(request, req, res);
        } else if (req.url.indexOf('/guzzle-server') == -1 && !that.responses.length) {
            res.writeHead(500);
            res.end('No responses in queue');
        } else {
            if (that.log) {
                console.log('Returning response from queue and adding request');
            }
            that.requests.push(request);
            var response = that.responses.shift();
            res.writeHead(response.status, response.reason, response.headers);
            res.end(response.body);
        }
    };

    this.start = function() {

        that.server = http.createServer(function(req, res) {

            var parts = url.parse(req.url, false);
            var request = {
                http_method: req.method,
                scheme: parts.scheme,
                uri: parts.pathname,
                query_string: parts.query,
                headers: req.headers,
                version: req.httpVersion,
                body: ''
            };

            // Receive each chunk of the request body
            req.addListener('data', function(chunk) {
                request.body += chunk;
            });

            // Called when the request completes
            req.addListener('end', function() {
                receivedRequest(request, req, res);
            });
        });

        that.server.listen(this.port, '127.0.0.1');

        if (this.log) {
            console.log('Server running at http://127.0.0.1:8125/');
        }
    };
};

// Get the port from the arguments
port = process.argv.length >= 3 ? process.argv[2] : 8125;
log = process.argv.length >= 4 ? process.argv[3] : false;

// Start the server
server = new GuzzleServer(port, log);
server.start();
