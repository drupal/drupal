const fs = require('node:fs');
const log = require('./log');
const compile = require('./compile');

module.exports = (filePath) => {
  log(`'${filePath}' is being processed.`);
  // Transform the file.
  compile(filePath, function write(code) {
    const fileName = filePath.slice(0, -9);
    // Write the result to the filesystem.
    fs.writeFile(`${fileName}.css`, code, () => {
      log(`'${filePath}' is finished.`);
    });
  });
};
