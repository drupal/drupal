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

console.warn('⚠️  yarn `watch:js` command is deprecated in drupal:9.4.0 and will be removed from drupal:10.0.0. This command is no longer needed in Drupal 10.0.0 once https://www.drupal.org/project/drupal/issues/3278415 is committed.️');

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
  })
  .on('ready', () => log(`Watching '${fileMatch}' for changes.`));
