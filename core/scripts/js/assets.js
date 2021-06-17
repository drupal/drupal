const path = require('path');
const { copyFile, writeFile, readFile, chmod } = require('fs/promises');

const packageFolder = path.resolve(__dirname, '../../node_modules');
const assetsFolder = path.resolve(__dirname, '../../assets/vendor');

[
  {
    pack: 'backbone',
    files: ['backbone.js', 'backbone-min.js', 'backbone-min.map'],
  },
  {
    pack: 'es6-promise',
    files: [
      { from: 'dist/es6-promise.auto.min.js', to: 'es6-promise.auto.min.js' },
      { from: 'dist/es6-promise.auto.min.map', to: 'es6-promise.auto.min.map' },
    ],
  },
  {
    pack: 'farbtastic',
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
    files: [
      { from: 'dist/jquery.form.min.js', to: 'jquery.form.min.js' },
      { from: 'dist/jquery.form.min.js.map', to: 'jquery.form.min.js.map' },
      { from: 'src/jquery.form.js', to: 'src/jquery.form.js' },
    ],
  },
  {
    pack: 'joyride',
    folder: 'jquery-joyride',
    files: ['jquery.joyride-2.1.js'],
  },
  {
    pack: 'jquery-once',
    files: ['jquery.once.js', 'jquery.once.min.js', 'jquery.once.min.js.map'],
  },
  // { pack: 'js-cookie', files: ['js.cookie.min.js'] },
  {
    pack: 'normalize.css',
    folder: 'normalize-css',
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
].forEach(({ pack, files = [], folder = false }) => {
  const sourceFolder = pack;
  const destFolder = folder || pack;
  files.forEach(async (file) => {
    let source = file;
    let dest = file;
    if (typeof file === 'object') {
      source = file.from;
      dest = file.to;
    }
    // For map files, make sure the sources files don't leak outside the library
    // folder inside assets/vendor.
    if (path.extname(source) === '.map') {
      console.log('Process map file', source);
      const map = await readFile(`${packageFolder}/${sourceFolder}/${source}`);
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
      // Fix some permissions
      if (['jquery.joyride-2.1.js', 'marker.png'].includes(dest)) {
        await chmod(`${assetsFolder}/${destFolder}/${dest}`, 0o644);
      }
    }
  });
});
