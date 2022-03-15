/**
 * @file
 *
 * Provides the `check:ckeditor5` command.
 *
 * Check that the plugins are built with the appropriate dependencies. This is
 * only run on DrupalCI.
 *
 * @internal This file is part of the core JavaScript build process and is only
 * meant to be used in that context.
 */

"use strict";

const glob = require("glob");
const log = require("./log");
const fs = require("fs").promises;
const child_process = require("child_process");

async function getContents(files) {
  return Object.fromEntries(
    await Promise.all(
      files.map(async (file) => [file, (await fs.readFile(file)).toString()])
    )
  );
}

(async () => {
  const files = glob.sync("./modules/ckeditor5/js/build/*.js");

  const pluginsBefore = await getContents(files);
  // Execute the plugin build script.
  child_process.execSync("yarn run build:ckeditor5");
  const pluginsAfter = await getContents(files);

  if (JSON.stringify(pluginsBefore) !== JSON.stringify(pluginsAfter)) {
    process.exitCode = 1;
  }
})();
