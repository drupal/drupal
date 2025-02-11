/**
 * @file
 *
 * Watch changes to *.pcss.css files and compile them to CSS during development.
 *
 * @internal This file is part of the core CSS build process and is only
 * designed to be used in that context.
 */

'use strict';

const { watch } = require('chokidar');
const { stat, unlink } = require('node:fs');

const changeOrAdded = require('./changeOrAdded');
const log = require('./log');

// Initialize watcher.
const watcher = watch(['./themes', './modules', './profiles'], {
  ignoreInitial: true,
  ignored: (filePath, stats) =>
    stats?.isFile() && !filePath.endsWith('.pcss.css') || filePath.includes('node_modules'),
  usePolling: true,
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
    const fileName = filePath.slice(0, -9);
    stat(`${fileName}.css`, (err) => {
      if (!err) {
        unlink(`${fileName}.css`, unlinkHandler);
      }
    });
  })
  .on('ready', () => log(`Watching '**/*.pcss.css' for changes.`));
