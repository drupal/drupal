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

const { globSync } = require('glob');
const log = require('./log');
const fs = require('node:fs');

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
 * @param {string[]} names
 *  The names of the classes/functions being exported.
 *
 * @return {string}
 *  The comment aliasing the module name to the specific named exports.
 */
function generateTypeDefs(file, module, names) {
  const cleanModule = module.replace('module:', '');
  return `/**
 * Declared in file @ckeditor/${file.replace(globOptions.cwd, '')}
 *${names.map(n => `
 * @typedef {module:${cleanModule}} module:${cleanModule}~${n}`).join('')}
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
  const moduleRegex = / * @module \b(.*)\b/
  const exportsRegex = /export(?: default)?(?: class| function) \b(\w+)\b/g

  const fileData = getFile(filePath);
  const module = moduleRegex.exec(fileData)
  const exports = fileData.matchAll(exportsRegex).map(e => e[1]).toArray()
  if (module && exports.length > 0) {
    return generateTypeDefs(filePath, module[1], exports)
  }
  return false;
}

const definitions = globSync('./ckeditor5*/src/**/*.+(js|jsdoc)', globOptions).sort().map(processFile);
// Filter definitions that do not match any regex.
const existingDefinitions = definitions.filter((e) => !!e);

// Write the file in the ckeditor module, use the JSDoc extension to make sure
// the JSDoc extension is associated with the JavaScript file type and it
// prevents core JavaScript lint rules to be run. Add it to the build folder to
// prevent cspell checks on this file.
fs.writeFile(`./modules/ckeditor5/js/build/ckeditor5.types.jsdoc`, existingDefinitions.join('\n'), () => {
  log(`CKEditor 5 types have been generated: ${existingDefinitions.length} files aliased, ${definitions.length - existingDefinitions.length} files ignored`);
});

process.exitCode = 0;
