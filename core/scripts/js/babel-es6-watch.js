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
const log = function (message) {
  // eslint-disable-next-line no-console
  console.log(`[${new Date().toTimeString().slice(0, 8)}] ${message}`);
};

function addSourceMappingUrl(code, loc) {
  return code + '\n\n//# sourceMappingURL=' + path.basename(loc);
}

const fileMatch = './**/*.es6.js';
const watcher = chokidar.watch(fileMatch, {
  ignoreInitial: true,
  ignored: 'node_modules/**'
});

const babelOptions = {
  sourceMaps: true,
  comments: false
};

const changedOrAdded = (filePath) => {
  babel.transformFile(filePath, babelOptions, function (err, result) {
    const fileName = filePath.slice(0, -7);

    // we've requested for a sourcemap to be written to disk
    let mapLoc = `${fileName}.js.map`;
    result.code = addSourceMappingUrl(result.code, mapLoc);
    fs.writeFileSync(mapLoc, JSON.stringify(result.map));

    fs.writeFileSync(`${fileName}.js`, result.code);

    log(`'${filePath}' has been changed.`);
  });
};

const unlinkHandler = (err) => {
  if (err) {
    return log(err);
  }
};

watcher
  .on('add', filePath => changedOrAdded(filePath))
  .on('change', filePath => changedOrAdded(filePath))
  .on('unlink', filePath => {
    const fileName = filePath.slice(0, -7);
    fs.stat(`${fileName}.js`, function () {
      fs.unlink(`${fileName}.js`, unlinkHandler);
    });
    fs.stat(`${fileName}.js.map`, function () {
      fs.unlink(`${fileName}.js.map`, unlinkHandler);
    });
  })
  .on('ready', () => log(`Watching '${fileMatch}' for changes.`));
