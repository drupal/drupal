const fs = require('fs');
const log = require('./log');
const Terser = require('terser');

module.exports = (filePath) => {
  log(`'${filePath}' is being processed.`);
  // Transform the file.
  const file = fs.readFileSync(filePath, 'utf-8');
  const result = Terser.minify(file);
  fs.writeFile(`${filePath.slice(0, -3)}-min.js`, result.code, () => {
    log(`'${filePath}' is finished.`);
  });
};
