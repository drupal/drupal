/**
 * @file
 * Callback returning the list of files to copy to the assets/vendor directory.
 */
const glob = require('glob');
// There are a lot of CKEditor 5 packages, generate the list dynamically.
// Drupal-specific mapping between CKEditor 5 name and Drupal library name.
const ckeditor5PluginMapping = {
  'block-quote': 'blockquote',
  essentials: 'internal',
  'basic-styles': 'basic',
};

/**
 * Build the list of assets to be copied based on what exists in the filesystem.
 *
 * @param {string} packageFolder
 *   The path to node_modules folder.
 *
 * @return {DrupalLibraryAsset[]}
 *  List of libraries and files to process.
 */
module.exports = (packageFolder) => {
  const fileList = [];
  // Get all the CKEditor 5 packages.
  const ckeditor5Dirs = glob.sync(`{${packageFolder}/@ckeditor/ckeditor5*,${packageFolder}/ckeditor5}`);
  for (const ckeditor5package of ckeditor5Dirs) {
    // Add all the files in the build/ directory to the process array for
    // copying.
    const buildFiles = glob.sync(`${ckeditor5package}/build/**/*.js`, {
      nodir: true,
    });
    if (buildFiles.length) {
      // Clean up the path to get the original package name.
      const pack = ckeditor5package.replace(`${packageFolder}/`, '');
      // Use the package name to generate the plugin name. There are some
      // exceptions that needs to be handled. Ideally remove the special cases.
      let pluginName = pack.replace('@ckeditor/ckeditor5-', '');
      // Target folder in the vendor/assets folder.
      let folder = `ckeditor5/${pluginName.replace('@ckeditor/ckeditor5-', '')}`;
      // Transform kebab-case to CamelCase.
      let library = pluginName.replace(/-./g, (match) => match[1].toUpperCase());
      // Special case for Drupal implementation.
      if (ckeditor5PluginMapping.hasOwnProperty(pluginName)) {
        library = ckeditor5PluginMapping[pluginName];
      }
      if (library === 'ckeditor5') {
        folder = 'ckeditor5/ckeditor5-dll';
      } else {
        library = `ckeditor5.${library}`;
      }
      fileList.push({
        pack,
        library,
        folder,
        files: buildFiles.map((absolutePath) => ({
          from: absolutePath.replace(`${ckeditor5package}/`, ''),
          to: absolutePath.replace(`${ckeditor5package}/build/`, ''),
        })),
      });
    }
  }

  return fileList;
};
