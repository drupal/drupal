/**
 * @file
 *
 * Provides the build:css command to compile *.pcss.css files to CSS.
 *
 * Run build:css with --file to only parse a specific file. Using the --check
 * flag build:css can be run to check if files are compiled correctly.
 * @example <caption>Only process misc/drupal.pcss.css and misc/drupal.init.pcss.css</caption>
 * yarn run build:css -- --file misc/drupal.pcss.css --file misc/drupal.init.pcss.css
 * @example <caption>Check if all files have been compiled correctly</caption>
 * yarn run build:css -- --check
 *
 * @internal This file is part of the core CSS build process and is only
 * designed to be used in that context.
 */

'use strict';

const glob = require('glob');
const argv = require('minimist')(process.argv.slice(2));
const changeOrAdded = require('./changeOrAdded');
const check = require('./check');
const log = require('./log');

// Match only on .pcss.css files.
const fileMatch = './**/*.pcss.css';
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
