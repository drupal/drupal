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
const babel = require('babel-core');
const glob = require('glob');

// Logging human-readable timestamp.
const log = function (message) {
  // eslint-disable-next-line no-console
  console.log(`[${new Date().toTimeString().slice(0, 8)}] ${message}`);
};

function addSourceMappingUrl(code, loc) {
  return code + '\n\n//# sourceMappingURL=' + path.basename(loc);
}

const changedOrAdded = (filePath) => {
  babel.transformFile(filePath, {
    sourceMaps: true,
    comments: false
  }, function (err, result) {
    const fileName = filePath.slice(0, -7);
    // we've requested for a sourcemap to be written to disk
    let mapLoc = `${fileName}.js.map`;

    fs.writeFile(mapLoc, JSON.stringify(result.map));
    fs.writeFile(`${fileName}.js`, addSourceMappingUrl(result.code, mapLoc));

    log(`'${filePath}' is being processed.`);
  });
};

const fileMatch = './**/*.es6.js';
const globOptions = {
  ignore: 'node_modules/**'
};
const processFiles = (error, filePaths) => {
  if (error) {
    process.exitCode = 1;
  }
  filePaths.forEach(changedOrAdded);
};
glob(fileMatch, globOptions, processFiles);
process.exitCode = 0;
