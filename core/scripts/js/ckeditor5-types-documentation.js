/**
 * @file
 *
 * Provides the `build:ckeditor5-types` command.
 *
 * This command is used for generating documentation for mapping CKEditor 5
 * types so that they can be parsed by IDEs.
 *
 * @internal This file is part of the core javascript build process and is only
 * meant to be used in that context.
 */

'use strict';

const glob = require('glob');
const log = require('./log');
const fs = require('fs');

/**
 * A list of regex used to alias CKEditor 5 types.
 *
 * @type {RegExp[]}
 */
const regexList = [
  // Makes sure that `export default class` code can be referenced with the
  // class name and not the module name only.
  / * @module \b(.*)\b[\s\S]*?export default(?: class| function)? \b(\w+)\b/g,

  // Pick up CKEditor 5 own aliases to alias them too.
  / * @module \b(.*)\b[\s\S]*?@(?:typedef|interface) (?:.*~)?(\w+)/g,
];

const globOptions = {
  // Search within the ckeditor npm namespace.
  cwd: process.cwd() + '/node_modules/@ckeditor/',
  absolute: true,
};

/**
 * Template for the generated typedef comment.
 *
 * @param {string} file
 *  The path to the file containing the type definition.
 * @param {string} module
 *  The module name as defined by the @module jsdoc comment.
 * @param {string} name
 *  The name of the class being exported
 *
 * @return {string}
 *  The comment aliasing the module name to the specific named exports.
 */
function generateTypeDef(file, module, name) {
  const cleanModule = module.replace('module:', '');
  return `/**
 * Declared in file @ckeditor/${file.replace(globOptions.cwd, '')}
 *
 * @typedef {module:${cleanModule}} module:${cleanModule}~${name}
 */
`;
}


/**
 * Helper to get the file contents as a string.
 *
 * @param {string} filePath
 *  Absolute path to the file.
 *
 * @return {string}
 */
function getFile(filePath) {
  try {
    return fs.readFileSync(filePath, 'utf8');
  } catch (err) {
    return '';
  }
}

/**
 * Returns a callback function.
 *
 * @param {string} filePath
 *  The CKEditor 5 source file to inspect for exports or type definitions.
 *
 * @return {function}
 *  The aliased typedef string.
 *
 * @see generateTypeDef
 */
function processFile(filePath) {
  const fileData = getFile(filePath);
  // Use a for loop to be able to return early.
  for (const regex of regexList) {
    // Reset the match index of the Regex to make sure we search from the
    // beginning of the file every time.
    regex.lastIndex = 0;
    const m = regex.exec(fileData);
    if (m) {
      return generateTypeDef(filePath, m[1], m[2]);
    }
  }
  return false;
}

const definitions = glob.sync('./ckeditor5*/src/**/*.+(js|jsdoc)', globOptions).map(processFile);
// Filter definitions that do not match any regex.
const existingDefinitions = definitions.filter((e) => !!e);

// Write the file in the ckeditor module, use the JSDoc extension to make sure
// the JSDoc extension is associated with the JavaScript file type and it
// prevents core JavaScript lint rules to be run. Add it to the build folder to
// prevent cspell checks on this file.
fs.writeFile(`./modules/ckeditor5/js/build/ckeditor5.types.jsdoc`, existingDefinitions.join('\n'), () => {
  log(`CKEditor 5 types have been generated: ${existingDefinitions.length} declarations aliased, ${definitions.length - existingDefinitions.length} files ignored`);
});

process.exitCode = 0;
