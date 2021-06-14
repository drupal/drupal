/**
 * Ends the browser session and logs the console log if there were any errors.
 * See globals.js.
 *
 * @param {Object}
 *   (optional) Settings object
 *   @param onlyOnError
 *     (optional) Only writes out the console log file if the test failed.
 * @param {function} callback
 *   A callback which will be called.
 * @return {object}
 *   The 'browser' object.
 */
exports.command = function drupalLogAndEnd({ onlyOnError = true }, callback) {
  const self = this;
  this.drupalLogConsole = true;
  this.drupalLogConsoleOnlyOnError = onlyOnError;

  // Nightwatch doesn't like it when no actions are added in a command file.
  // https://github.com/nightwatchjs/nightwatch/issues/1792
  this.pause(1);

  if (typeof callback === 'function') {
    callback.call(self);
  }
  return this;
};
