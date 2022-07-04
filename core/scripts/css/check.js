const fs = require('fs');
const log = require('./log');
const compile = require('./compile');

module.exports = (filePath) => {
  log(`'${filePath}' is being checked.`);
  // Transform the file.
  compile(filePath, function check(code) {
    const fileName = filePath.slice(0, -9);
    fs.readFile(`${fileName}.css`, function read(err, data) {
      if (err) {
        log(err);
        process.exitCode = 1;
        return;
      }
      if (code !== data.toString()) {
        log(`'${filePath}' is not updated.`);
        process.exitCode = 1;
      }
    });
  });
};
