const fs = require('fs');
const babel = require('babel-core');

const log = require('./log');

module.exports = (filePath) => {
  const moduleName = filePath.slice(0, -7);
  log(`'${filePath}' is being processed.`);
  // Transform the file.
  // Check process.env.NODE_ENV to see if we should create sourcemaps.
  babel.transformFile(
    filePath,
    {
      sourceMaps: process.env.NODE_ENV === 'development' ? 'inline' : false,
      comments: false
    },
    (err, result) => {
      if (err) {
        throw new Error(err);
      }
      const fileName = filePath.slice(0, -7);
      // Write the result to the filesystem.
      fs.writeFile(`${fileName}.js`, result.code, () => {
        log(`'${filePath}' is finished.`);
      });
    }
  );
}
