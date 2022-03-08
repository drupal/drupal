/**
 * Process map files.
 *
 * In the `sources` member, remove all "../" values at the start of the file
 * names to avoid virtual files located outside of the library vendor folder.
 *
 * @param {object} data
 *  Object passed to the callback.
 * @param {string} data.contents
 *  Content of the file being processed.
 *
 * @return {Promise<[{contents: string}]>}
 *  Return a Promise that resolves into an array of file and content to create
 *  in the assets/vendor/ directory.
 */
module.exports = ({ contents }) => {
  const json = JSON.parse(contents);
  json.sources = json.sources.map((source) => source.replace(/^(\.\.\/)+/, ''));
  return [{ contents: JSON.stringify(json) }];
};
