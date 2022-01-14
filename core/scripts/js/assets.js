/**
 * @file
 * Copy files for JS vendor dependencies from node_modules to the assets/vendor
 * folder.
 *
 * This script handles all dependencies except CKEditor, Modernizr,
 * and jQuery.ui which require a custom build step.
 */

const path = require('path');
const { copyFile, writeFile, readFile, chmod, rmdir, mkdir, readdir, appendFile } = require('fs').promises;

const coreFolder = path.resolve(__dirname, '../../');
const packageFolder = `${coreFolder}/node_modules`;
const assetsFolder = `${coreFolder}/assets/vendor`;

(async () => {
  const librariesPath = `${coreFolder}/core.libraries.yml`;
  // Open the core.libraries.yml file to update version information
  // automatically.
  const libraries = (await readFile(librariesPath, 'utf-8')).split('\n\n');

  function updateLibraryVersion(libraryName, { version }) {
    const libraryIndex = libraries.findIndex((lib) =>
      lib.startsWith(libraryName),
    );
    if (libraryIndex > 0) {
      const libraryDeclaration = libraries[libraryIndex];
      // Get the previous package version.
      const currentVersion = libraryDeclaration.match(/version: "(.*)"\n/)[1];
      // Replace the version value and the version in the license URL.
      libraries[libraryIndex] = libraryDeclaration.replace(
        new RegExp(currentVersion, 'g'),
        version,
      );
    }
  }

  // CKEditor 5 translation files need some special handling. Start by ensuring
  // that an empty /translations directory exists in the
  // /core/assets/vendor/ckeditor5 directory.
  const ckeditor5Path = `${assetsFolder}/ckeditor5`;
  await rmdir(`${ckeditor5Path}/translations`, { recursive: true })
    .catch(() => {
      // Nothing to do if the directory doesn't exist.
    });
  await mkdir(`${ckeditor5Path}/translations`);

  /**
   * Declare the array that defines what needs to be copied over.
   *
   * @prop {string} pack
   *   The name of the npm package (used to get the name of the folder where
   *   the files are situated inside of the node_modules folder). Note that we
   *   use `pack` and not `package` because `package` is a future reserved word.
   * @prop {string} [folder]
   *   The folder under `assets/vendor/` where the files will be copied. If
   *   this
   *   is not defined the value of `pack` is used.
   * @prop {string} [library]
   *   The key under which the library is declared in core.libraries.yml.
   * @prop {Array} files
   *   An array of files to be copied over.
   *     - A string if the file has the same name and is at the same level in
   *   the source and target folder.
   *     - An object with a `from` and `to` property if the source and target
   *   have a different name or if the folder nesting is different.
   */
  const process = [
    {
      pack: 'backbone',
      files: ['backbone.js', 'backbone-min.js', 'backbone-min.map'],
    },
    {
      pack: 'css.escape',
      folder: 'css-escape',
      library: 'css.escape',
      files: ['css.escape.js'],
    },
    {
      pack: 'es6-promise',
      files: [
        { from: 'dist/es6-promise.auto.min.js', to: 'es6-promise.auto.min.js' },
        {
          from: 'dist/es6-promise.auto.min.map',
          to: 'es6-promise.auto.min.map',
        },
      ],
    },
    {
      pack: 'farbtastic',
      library: 'jquery.farbtastic',
      files: [
        'marker.png',
        'mask.png',
        'wheel.png',
        'farbtastic.css',
        { from: 'farbtastic.min.js', to: 'farbtastic.js' },
      ],
    },
    {
      pack: 'jquery',
      files: [
        { from: 'dist/jquery.js', to: 'jquery.js' },
        { from: 'dist/jquery.min.js', to: 'jquery.min.js' },
        { from: 'dist/jquery.min.map', to: 'jquery.min.map' },
      ],
    },
    {
      pack: 'jquery-form',
      library: 'jquery.form',
      files: [
        { from: 'dist/jquery.form.min.js', to: 'jquery.form.min.js' },
        { from: 'dist/jquery.form.min.js.map', to: 'jquery.form.min.js.map' },
        { from: 'src/jquery.form.js', to: 'src/jquery.form.js' },
      ],
    },
    {
      pack: 'joyride',
      folder: 'jquery-joyride',
      library: 'jquery.joyride',
      files: ['jquery.joyride-2.1.js'],
    },
    {
      pack: 'jquery-once',
      library: 'jquery.once',
      files: ['jquery.once.js', 'jquery.once.min.js', 'jquery.once.min.js.map'],
    },
    {
      pack: 'js-cookie',
      files: [{ from: 'dist/js.cookie.min.js', to: 'js.cookie.min.js' }],
    },
    {
      pack: 'normalize.css',
      folder: 'normalize-css',
      library: 'normalize',
      files: ['normalize.css'],
    },
    {
      pack: '@drupal/once',
      folder: 'once',
      files: [
        { from: 'dist/once.js', to: 'once.js' },
        { from: 'dist/once.min.js', to: 'once.min.js' },
        { from: 'dist/once.min.js.map', to: 'once.min.js.map' },
      ],
    },
    {
      pack: 'picturefill',
      files: [{ from: 'dist/picturefill.min.js', to: 'picturefill.min.js' }],
    },
    {
      pack: '@popperjs/core',
      folder: 'popperjs',
      files: [
        { from: 'dist/umd/popper.min.js', to: 'popper.min.js' },
        { from: 'dist/umd/popper.min.js.map', to: 'popper.min.js.map' },
      ],
    },
    {
      pack: 'shepherd.js',
      folder: 'shepherd',
      files: [
        { from: 'dist/js/shepherd.min.js', to: 'shepherd.min.js' },
        { from: 'dist/js/shepherd.min.js.map', to: 'shepherd.min.js.map' },
      ],
    },
    { pack: 'sortablejs', folder: 'sortable', files: ['Sortable.min.js'] },
    {
      pack: 'tabbable',
      files: [
        { from: 'dist/index.umd.min.js', to: 'index.umd.min.js' },
        { from: 'dist/index.umd.min.js.map', to: 'index.umd.min.js.map' },
      ],
    },
    {
      pack: 'underscore',
      files: ['underscore-min.js', 'underscore-min.js.map'],
    },
    {
      pack: 'loadjs',
      files: [{ from: 'dist/loadjs.min.js', to: 'loadjs.min.js' }],
    },
    {
      pack: '@ckeditor/ckeditor5-alignment',
      folder: 'ckeditor5',
      files: [
        { from: 'build/alignment.js', to: 'alignment.js' }
      ],
      library: 'ckeditor5.alignment',
    },
    {
      pack: '@ckeditor/ckeditor5-basic-styles',
      folder: 'ckeditor5',
      files: [
        { from: 'build/basic-styles.js', to: 'basic-styles.js' }
      ],
      library: 'ckeditor5.basic',
    },
    {
      pack: '@ckeditor/ckeditor5-block-quote',
      folder: 'ckeditor5',
      files: [
        { from: 'build/block-quote.js', to: 'block-quote.js' }
      ],
      library: 'ckeditor5.blockquote',
    },
    {
      pack: '@ckeditor/ckeditor5-editor-classic',
      folder: 'ckeditor5',
      files: [
        { from: 'build/editor-classic.js', to: 'editor-classic.js' }
      ],
      library: 'ckeditor5.editorClassic',
    },
    {
      pack: '@ckeditor/ckeditor5-editor-decoupled',
      folder: 'ckeditor5',
      files: [
        { from: 'build/editor-decoupled.js', to: 'editor-decoupled.js' }
      ],
      library: 'ckeditor5.editorDecoupled',
    },
    {
      pack: '@ckeditor/ckeditor5-essentials',
      folder: 'ckeditor5',
      files: [
        { from: 'build/essentials.js', to: 'essentials.js' }
      ],
      library: 'ckeditor5.internal',
    },
    {
      pack: '@ckeditor/ckeditor5-heading',
      folder: 'ckeditor5',
      files: [
        { from: 'build/heading.js', to: 'heading.js' }
      ],
      library: 'ckeditor5.internal',
    },
    {
      pack: '@ckeditor/ckeditor5-horizontal-line',
      folder: 'ckeditor5',
      files: [
        { from: 'build/horizontal-line.js', to: 'horizontal-line.js' }
      ],
      library: 'ckeditor5.horizontalLine',
    },
    {
      pack: '@ckeditor/ckeditor5-image',
      folder: 'ckeditor5',
      files: [
        { from: 'build/image.js', to: 'image.js' }
      ],
      library: 'ckeditor5.image',
    },
    {
      pack: '@ckeditor/ckeditor5-indent',
      folder: 'ckeditor5',
      files: [
        { from: 'build/indent.js', to: 'indent.js' }
      ],
      library: 'ckeditor5.indent',
    },
    {
      pack: '@ckeditor/ckeditor5-language',
      folder: 'ckeditor5',
      files: [
        { from: 'build/language.js', to: 'language.js' },
      ],
      library: 'ckeditor5.language',
    },
    {
      pack: '@ckeditor/ckeditor5-link',
      folder: 'ckeditor5',
      files: [
        { from: 'build/link.js', to: 'link.js' }
      ],
      library: 'ckeditor5.link',
    },
    {
      pack: '@ckeditor/ckeditor5-list',
      folder: 'ckeditor5',
      files: [
        { from: 'build/list.js', to: 'list.js' }
      ],
      library: 'ckeditor5.list',
    },
    {
      pack: '@ckeditor/ckeditor5-paste-from-office',
      folder: 'ckeditor5',
      files: [
        { from: 'build/paste-from-office.js', to: 'paste-from-office.js' }
      ],
      library: 'ckeditor5.pasteFromOffice',
    },
    {
      pack: '@ckeditor/ckeditor5-remove-format',
      folder: 'ckeditor5',
      files: [
        { from: 'build/remove-format.js', to: 'remove-format.js' }
      ],
      library: 'ckeditor5.removeFormat',
    },
    {
      pack: '@ckeditor/ckeditor5-source-editing',
      folder: 'ckeditor5',
      files: [
        { from: 'build/source-editing.js', to: 'source-editing.js' }
      ],
      library: 'ckeditor5.sourceEditing',
    },
    {
      pack: '@ckeditor/ckeditor5-table',
      folder: 'ckeditor5',
      files: [
        { from: 'build/table.js', to: 'table.js' }
      ],
      library: 'ckeditor5.table',
    },
    {
      pack: '@ckeditor/ckeditor5-html-support',
      folder: 'ckeditor5',
      files: [
        { from: 'build/html-support.js', to: 'html-support.js' }
      ],
      library: 'ckeditor5.htmlSupport',
    },
    {
      pack: '@ckeditor/ckeditor5-special-characters',
      folder: 'ckeditor5',
      files: [
        { from: 'build/special-characters.js', to: 'special-characters.js' }
      ],
      library: 'ckeditor5.specialCharacters',
    },
    {
      pack: 'ckeditor5',
      files: [
        { from: 'build/ckeditor5-dll.js', to: 'ckeditor5-dll.js' }
      ],
    }
  ];

  // Use Array.reduce for sequential processing to avoid corrupting the
  // contents of the concatenated CKEditor 5 translation files.
  await process.reduce(async (previous, { pack, files = [], folder = false, library = false }) => {
    return previous.then(async () => {
      const sourceFolder = pack;
      const libraryName = library || folder || pack;
      const destFolder = folder || pack;

      let packageInfo;
      // Take the version info from the package.json file.
      if (!['joyride', 'farbtastic'].includes(pack)) {
        packageInfo = JSON.parse(
          await readFile(`${packageFolder}/${sourceFolder}/package.json`),
        );
      }
      if (packageInfo) {
        updateLibraryVersion(libraryName, packageInfo);
      }

      // CKEditor 5 packages ship with translation files.
      if (pack.startsWith('@ckeditor') || pack === 'ckeditor5') {
        const packageTranslationPath = `${packageFolder}/${sourceFolder}/build/translations`;
        await readdir(packageTranslationPath, { withFileTypes: true }).then(async (translationFiles) => {
          return translationFiles.map(async (translationFile) => {
            if (!translationFile.isDirectory()) {
              // Translation files are concatenated to a single translation
              // file to avoid having to make multiple network requests to
              // various translation files. As a trade off, this leads into
              // some redundant translations depending on configuration.
              await readFile(`${packageTranslationPath}/${translationFile.name}`).then(async (contents) => {
                return appendFile(`${assetsFolder}/${destFolder}/translations/${translationFile.name}`, contents);
              });
            }
          }, Promise.resolve());
        }).catch(() => {
          // Do nothing as it's expected that not all packages ship translations.
        });
      }

      return files.forEach(async (file) => {
        let source = file;
        let dest = file;
        if (typeof file === 'object') {
          source = file.from;
          dest = file.to;
        }
        // For map files, make sure the sources files don't leak outside the
        // library folder. In the `sources` member, remove all "../" values at
        // the start of the files names to avoid having the virtual files outside
        // of the library vendor folder in dev tools.
        if (path.extname(source) === '.map') {
          console.log('Process map file', source);
          const map = await readFile(
            `${packageFolder}/${sourceFolder}/${source}`,
          );
          const json = JSON.parse(map);
          json.sources = json.sources.map((source) =>
            source.replace(/^(\.\.\/)+/, ''),
          );
          await writeFile(
            `${assetsFolder}/${destFolder}/${dest}`,
            JSON.stringify(json),
          );
        } else {
          console.log(
            'Copy',
            `${sourceFolder}/${source}`,
            'to',
            `${destFolder}/${dest}`,
          );
          await copyFile(
            `${packageFolder}/${sourceFolder}/${source}`,
            `${assetsFolder}/${destFolder}/${dest}`,
          );
          // These 2 files come from a zip file that hasn't been updated in years
          // hardcode the permission fix to pass the commit checks.
          if (['jquery.joyride-2.1.js', 'marker.png'].includes(dest)) {
            await chmod(`${assetsFolder}/${destFolder}/${dest}`, 0o644);
          }
        }
      });
    });
  }, Promise.resolve());

  await writeFile(librariesPath, libraries.join('\n\n'));
})();
