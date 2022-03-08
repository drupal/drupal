const Terser = require('terser');
const path = require('path');

/**
 * Process jQuery UI source files.
 *
 * Each file being processed creates 3 files under assets/vendor/jquery.ui/:
 *  - The original source for audit purposes, with a `.js` suffix.
 *  - The minified version for production use, with a `-min.js` suffix.
 *  - The source map for debugging purposes, with a `-min.js.map` suffix.
 *
 * @param {object} data
 *  Object passed to the callback.
 * @param {object} data.file
 *  Normalized file information object.
 * @param {string} data.file.from
 *  Path of the file in node_modules/ directory.
 * @param {string} data.file.to
 *  Path of the file in core assets/vendor/ directory.
 * @param {string} data.contents
 *  Content of the file being processed.
 *
 * @return {Promise<[{filename: string, contents: string}]>}
 *  Return a Promise that resolves into an array of file and content to create
 *  in the assets/vendor/ directory.
 */
module.exports = async ({ file: { from, to }, contents }) => {
  const filename = `${to.slice(0, -3)}-min.js`;
  const sourcemap = `${filename}.map`;

  const { code, map } = await Terser.minify(
    { [path.basename(from)]: contents }, {
    sourceMap: {
      filename: path.basename(filename),
      url: path.basename(sourcemap),
    },
  });

  return [
    // Original file.
    { filename: to, contents },
    // Minified file.
    { filename, contents: code },
    // Sourcemap file.
    { filename: sourcemap, contents: map },
  ];
};
