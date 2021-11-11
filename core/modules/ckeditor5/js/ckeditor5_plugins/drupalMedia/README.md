# ckeditor5-drupal-media

Drupal media plugin for CKEditor5

Provides required attributes `data-entity-uuid` and `data-entity-type`.

## Usage

This project is for creating minified js for [Drupal CKEditor5](http://drupal.org/project/ckeditor5)

When using this plugin, you must provide the following config in `drupalMedia`:

1. `libraryURL`: The URL at route media_library.ui to open a browse dialog
2. `previewURL`: The URL at route media.filter.preview to render a media preview
3. `openDialog`: The function to open a Drupal dialog
4. `themeError`: The markup to display if the preview fails

Example config:

```
ClassicEditor
  .create(editorElement, {
    drupalMedia: {
      libraryURL: '/media-library?...',
      previewURL: '/media/{filter_format}/preview',
      openDialog: Drupal.ckeditor5.openDialog,
      themeError: Drupal.theme('mediaEmbedPreviewError'),
    },
 });
  .then( ... )
  .catch( ... );
```

## Build

Run `npm run dll:build` to create the minified js.

Check the file path in `webpack.config.js` to ensure it points to the dll
manifest in your copy of `ckeditor5`.
