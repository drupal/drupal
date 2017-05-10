/**
 * @file
 *
 * Compile *.es6.js files to ES5.
 *
 * @internal This file is part of the core javascript build process and is only
 * meant to be used in that context.
 */

'use strict';

const fs = require('fs');
const path = require('path');
const glob = require('glob');

const changeOrAdded = require('./changeOrAdded');
const log = require('./log');

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
  filePaths.forEach(changeOrAdded);
};

// Run build:js with some special arguments to only parse specific files.
// npm run build:js -- --files misc/drupal.es6.js misc/drupal.init.es6.js
// Only misc/drupal.es6.js misc/drupal.init.es6.js will be processed.
if (process.argv.length > 2 && process.argv[2] === '--files') {
  processFiles(null, process.argv.splice(3, process.argv.length));
}
else {
  glob(fileMatch, globOptions, processFiles);
}
process.exitCode = 0;
