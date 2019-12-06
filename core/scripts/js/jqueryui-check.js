const chalk = require('chalk');
const fs = require('fs');
const log = require('./log');
const Terser = require('terser');

module.exports = filePath => {
  log(`'${filePath}' is being checked.`);
  // Transform the file.
  const file = fs.readFileSync(filePath, 'utf-8');
  const result = Terser.minify(file);
  const fileName = filePath.slice(0, -3);
  fs.readFile(`${fileName}-min.js`, function read(err, data) {
    if (err) {
      log(chalk.red(err));
      process.exitCode = 1;
      return;
    }
    if (result.code !== data.toString()) {
      log(chalk.red(`'${filePath}' is not updated.`));
      process.exitCode = 1;
    }
  });
};
