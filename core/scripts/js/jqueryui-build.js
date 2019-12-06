/**
 * @file
 *
 * Provides the build:jqueryui command to minify *.js source files.
 *
 * Run build:jqueryui with --file to only parse a specific file.
 * @example <caption>Only process assets/vendor/jquery.ui/ui/widget.js and
 * assets/vendor/jquery.ui/ui/plugin.js</caption
 * yarn run build:jqueryui --file assets/vendor/jquery.ui/ui/widget.js --file
 * assets/vendor/jquery.ui/ui/plugin.js
 * @example <caption>Check if all files have been compiled correctly</caption>
 * yarn run build:jqueryui --check
 *
 * @internal This file is part of the core javascript build process and is only
 * meant to be used in that context.
 */

'use strict';

const glob = require('glob');
const argv = require('minimist')(process.argv.slice(2));
const check = require('./jqueryui-check');
const minify = require('./jqueryui-terser');
const log = require('./log');

// Match only on jQuery UI .js files.
const fileMatch = './assets/vendor/jquery.ui/**/!(*-min).js';
const processFiles = (error, filePaths) => {
  if (error) {
    process.exitCode = 1;
  }
  // Process all the found files.
  let callback = minify;
  if (argv.check) {
    callback = check;
  }
  filePaths.forEach(callback);
};

if (argv.file) {
  processFiles(null, [].concat(argv.file));
}
else {
  glob(fileMatch, {}, processFiles);
}
process.exitCode = 0;
