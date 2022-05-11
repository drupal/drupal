/**
 * @file
 *
 * Provides the build:js command to compile *.es6.js files to ES5.
 *
 * Run build:js with --file to only parse a specific file. Using the --check
 * flag build:js can be run to check if files are compiled correctly.
 * @example <caption>Only process misc/drupal.es6.js and misc/drupal.init.es6.js</caption
 * yarn run build:js -- --file misc/drupal.es6.js --file misc/drupal.init.es6.js
 * @example <caption>Check if all files have been compiled correctly</caption
 * yarn run build:js -- --check
 *
 * @internal This file is part of the core javascript build process and is only
 * meant to be used in that context.
 */

'use strict';

const glob = require('glob');
const argv = require('minimist')(process.argv.slice(2));
const changeOrAdded = require('./changeOrAdded');
const check = require('./check');
const log = require('./log');

console.warn('⚠️  yarn `build:js` command is deprecated in drupal:9.4.0 and will be removed from drupal:10.0.0. This command is no longer needed in Drupal 10.0.0 once https://www.drupal.org/project/drupal/issues/3278415 is committed.️');

// Match only on .es6.js files.
const fileMatch = './**/*.es6.js';
// Ignore everything in node_modules
const globOptions = {
  ignore: './node_modules/**'
};
const processFiles = (error, filePaths) => {
  if (error) {
    process.exitCode = 1;
  }
  // Process all the found files.
  let callback = changeOrAdded;
  if (argv.check) {
    callback = check;
  }
  filePaths.forEach(callback);
};

if (argv.file) {
  processFiles(null, [].concat(argv.file));
}
else {
  glob(fileMatch, globOptions, processFiles);
}
process.exitCode = 0;
