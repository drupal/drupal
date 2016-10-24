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
const babel = require('babel-core');
const chokidar = require('chokidar');

// Logging human-readable timestamp.
const log = function log(message) {
  // eslint-disable-next-line no-console
  console.log(`[${new Date().toTimeString().slice(0, 8)}] ${message}`);
};

function addSourceMappingUrl(code, loc) {
  return `${code}\n\n//# sourceMappingURL=${path.basename(loc)}`;
}

const fileMatch = './**/*.es6.js';
const watcher = chokidar.watch(fileMatch, {
  ignoreInitial: true,
  ignored: 'node_modules/**'
});

const changedOrAdded = (filePath) => {
  babel.transformFile(filePath, {
    sourceMaps: true,
    comments: false
  }, (err, result) => {
    const fileName = filePath.slice(0, -7);
    // we've requested for a sourcemap to be written to disk
    const mapLoc = `${fileName}.js.map`;

    fs.writeFileSync(mapLoc, JSON.stringify(result.map));
    fs.writeFileSync(`${fileName}.js`, addSourceMappingUrl(result.code, mapLoc));

    log(`'${filePath}' has been changed.`);
  });
};

const unlinkHandler = (err) => {
  if (err) {
    log(err);
  }
};

watcher
  .on('add', filePath => changedOrAdded(filePath))
  .on('change', filePath => changedOrAdded(filePath))
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
