var Poltergeist, system, _ref, _ref1, _ref2;

//Inheritance tool
phantom.injectJs("" + phantom.libraryPath + "/Tools/inherit.js");

//Poltergeist main object
phantom.injectJs("" + phantom.libraryPath + "/poltergeist.js");

//Errors that are controller in the poltergeist code
phantom.injectJs("" + phantom.libraryPath + "/Errors/error.js");
phantom.injectJs("" + phantom.libraryPath + "/Errors/obsolete_node.js");
phantom.injectJs("" + phantom.libraryPath + "/Errors/invalid_selector.js");
phantom.injectJs("" + phantom.libraryPath + "/Errors/frame_not_found.js");
phantom.injectJs("" + phantom.libraryPath + "/Errors/mouse_event_failed.js");
phantom.injectJs("" + phantom.libraryPath + "/Errors/javascript_error.js");
phantom.injectJs("" + phantom.libraryPath + "/Errors/browser_error.js");
phantom.injectJs("" + phantom.libraryPath + "/Errors/status_fail_error.js");
phantom.injectJs("" + phantom.libraryPath + "/Errors/no_such_window_error.js");

//web server to control the commands
phantom.injectJs("" + phantom.libraryPath + "/Server/server.js");

phantom.injectJs("" + phantom.libraryPath + "/web_page.js");
phantom.injectJs("" + phantom.libraryPath + "/node.js");
phantom.injectJs("" + phantom.libraryPath + "/browser.js");

system = require('system');

new Poltergeist(system.args[1], system.args[2], system.args[3], system.args[4] === 'false' ? false : true);
