const path = require('path');
const { copyFile } = require('fs/promises');

const packageFolder = path.resolve(__dirname, '../../node_modules');
const assetsFolder = path.resolve(__dirname, '../../assets/vendor');

[
  {
    module: 'backbone',
    files: ['backbone.js', 'backbone-min.js', 'backbone-min.map'],
  },
  {
    module: 'es6-promise',
    files: [
      { from: 'dist/es6-promise.auto.min.js', to: 'es6-promise.auto.min.js' },
      { from: 'dist/es6-promise.auto.min.map', to: 'es6-promise.auto.min.map' },
    ],
  },
  {
    module: 'farbtastic',
    files: [
      'marker.png',
      'mask.png',
      'wheel.png',
      'farbtastic.css',
      { from: 'farbtastic.min.js', to: 'farbtastic.js' },
    ],
  },
  {
    module: 'jquery',
    files: [
      { from: 'dist/jquery.js', to: 'jquery.js' },
      { from: 'dist/jquery.min.js', to: 'jquery.min.js' },
      { from: 'dist/jquery.min.map', to: 'jquery.min.map' },
    ],
  },
  {
    module: 'jquery-form',
    files: [
      { from: 'dist/jquery.form.min.js', to: 'jquery.form.min.js' },
      { from: 'dist/jquery.form.min.js.map', to: 'jquery.form.min.js.map' },
    ],
  },
  {
    module: 'joyride',
    folder: 'jquery-joyride',
    files: ['jquery.joyride-2.1.js'],
  },
  {
    module: 'jquery-once',
    files: ['jquery.once.js', 'jquery.once.min.js', 'jquery.once.min.js.map'],
  },
  // { module: 'js-cookie', files: ['js.cookie.min.js'] },
  {
    module: 'normalize.css',
    folder: 'normalize-css',
    files: ['normalize.css'],
  },
  {
    module: '@drupal/once',
    folder: 'once',
    files: [
      { from: 'dist/once.js', to: 'once.js' },
      { from: 'dist/once.min.js', to: 'once.min.js' },
      { from: 'dist/once.min.js.map', to: 'once.min.js.map' },
    ],
  },
  {
    module: 'picturefill',
    files: [{ from: 'dist/picturefill.min.js', to: 'picturefill.min.js' }],
  },
  {
    module: '@popperjs/core',
    folder: 'popperjs',
    files: [
      { from: 'dist/umd/popper.min.js', to: 'popper.min.js' },
      { from: 'dist/umd/popper.min.js.map', to: 'popper.min.js.map' },
    ],
  },
  {
    module: 'shepherd.js',
    folder: 'shepherd',
    files: [
      { from: 'dist/js/shepherd.min.js', to: 'shepherd.min.js' },
      { from: 'dist/js/shepherd.min.js.map', to: 'shepherd.min.js.map' },
    ],
  },
  { module: 'sortablejs', folder: 'sortable', files: ['Sortable.min.js'] },
  {
    module: 'tabbable',
    files: [
      { from: 'dist/index.umd.min.js', to: 'index.umd.min.js' },
      { from: 'dist/index.umd.min.js.map', to: 'index.umd.min.js.map' },
    ],
  },
  {
    module: 'underscore',
    files: ['underscore-min.js', 'underscore-min.js.map'],
  },
].forEach(({ module, files = [], folder = false }) => {
  const sourceFolder = module;
  const destFolder = folder || module;
  files.forEach(async (file) => {
    let source = file;
    let dest = file;
    if (typeof file === 'object') {
      source = file.from;
      dest = file.to;
    }
    await copyFile(
      `${packageFolder}/${sourceFolder}/${source}`,
      `${assetsFolder}/${destFolder}/${dest}`,
    );
  });
});
