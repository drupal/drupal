/**
 * @file
 *
 * Watch changes to *.es6.js files and compile them to ES5 during development.
 *
 * @internal This file is part of the core javascript build process and is only
 * meant to be used in that context.
 */

'use strict';

const fs = require('fs');
const path = require('path');
const chokidar = require('chokidar');

const changeOrAdded = require('./changeOrAdded');
const log = require('./log');

// Match only on .es6.js files.
const fileMatch = './**/*.es6.js';
// Ignore everything in node_modules
const watcher = chokidar.watch(fileMatch, {
  ignoreInitial: true,
  ignored: './node_modules/**'
});

const unlinkHandler = (err) => {
  if (err) {
    log(err);
  }
};

// Watch for filesystem changes.
watcher
  .on('add', changeOrAdded)
  .on('change', changeOrAdded)
  .on('unlink', (filePath) => {
    const fileName = filePath.slice(0, -7);
    fs.stat(`${fileName}.js`, () => {
      fs.unlink(`${fileName}.js`, unlinkHandler);
    });
    fs.stat(`${fileName}.js.map`, () => {
      fs.unlink(`${fileName}.js.map`, unlinkHandler);
    });
  })
  .on('ready', () => log(`Watching '${fileMatch}' for changes.`));
