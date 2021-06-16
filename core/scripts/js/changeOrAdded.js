const fs = require('fs');
const log = require('./log');
const compile = require('./compile');

module.exports = (filePath) => {
  log(`'${filePath}' is being processed.`);
  // Transform the file.
  compile(filePath, function write(code) {
    const fileName = filePath.slice(0, -7);
    // Write the result to the filesystem.
    fs.writeFile(`${fileName}.js`, code, () => {
      log(`'${filePath}' is finished.`);
    });
  });
}
