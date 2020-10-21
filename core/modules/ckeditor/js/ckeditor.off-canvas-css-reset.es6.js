/**
 * @file
 * Provides styles for CKEditor inside off-canvas dialogs.
 */

(($, CKEDITOR) => {
  /**
   * Takes a string of CKEditor CSS and modifies it for use in off-canvas.
   *
   * @param {string} originalCss
   *   The CSS rules from CKEditor.
   * @return {string}
   *   The rules from originalCss with extra specificity for off-canvas.
   */
  const convertToOffCanvasCss = (originalCss) => {
    const selectorPrefix = '#drupal-off-canvas ';
    const skinPath = `${CKEDITOR.basePath}${CKEDITOR.skinName}/`;
    const css = originalCss
      .substring(originalCss.indexOf('*/') + 2)
      .trim()
      .replace(/}/g, `}${selectorPrefix}`)
      .replace(/,/g, `,${selectorPrefix}`)
      .replace(/url\(/g, skinPath);
    return `${selectorPrefix}${css}`;
  };

  /**
   * Inserts CSS rules into DOM.
   *
   * @param {string} cssToInsert
   *   CSS rules to be inserted
   */
  const insertCss = (cssToInsert) => {
    const offCanvasCss = document.createElement('style');
    offCanvasCss.innerHTML = cssToInsert;
    offCanvasCss.setAttribute('id', 'ckeditor-off-canvas-reset');
    document.body.appendChild(offCanvasCss);
  };

  /**
   * Adds CSS so CKEditor is styled properly in off-canvas.
   */
  const addCkeditorOffCanvasCss = () => {
    // If #ckeditor-off-canvas-reset exists, this has already run.
    if (document.getElementById('ckeditor-off-canvas-reset')) {
      return;
    }
    // CKEDITOR.skin.getPath() requires the CKEDITOR.skinName property.
    // @see https://stackoverflow.com/a/17336982
    CKEDITOR.skinName = CKEDITOR.skin.name;

    // Get the paths to the css CKEditor is using.
    const editorCssPath = CKEDITOR.skin.getPath('editor');
    const dialogCssPath = CKEDITOR.skin.getPath('dialog');

    // The key for cached CSS in localStorage is based on the CSS paths.
    const storedOffCanvasCss = window.localStorage.getItem(
      `Drupal.off-canvas.css.${editorCssPath}${dialogCssPath}`,
    );

    // See if CSS is cached in localStorage, and use that when available.
    if (storedOffCanvasCss) {
      insertCss(storedOffCanvasCss);
      return;
    }

    // If CSS unavailable in localStorage, get the files via AJAX and parse.
    $.when($.get(editorCssPath), $.get(dialogCssPath)).done(
      (editorCss, dialogCss) => {
        const offCanvasEditorCss = convertToOffCanvasCss(editorCss[0]);
        const offCanvasDialogCss = convertToOffCanvasCss(dialogCss[0]);
        const cssToInsert = `#drupal-off-canvas .cke_inner * {background: transparent;}
          ${offCanvasEditorCss}
          ${offCanvasDialogCss}`;
        insertCss(cssToInsert);

        // The localStorage key for accessing the cached CSS is based on the
        // paths of the CKEditor CSS files. This prevents localStorage from
        // providing outdated CSS. If new files are used due to using a new
        // skin, a new localStorage key is created.
        //
        // The CSS paths also include the cache-busting query string that is
        // stored in state and CKEDITOR.timestamp. This query string changes on
        // update and cache clear  and prevents localStorage from providing
        // stale CKEditor CSS.
        //
        // Before adding the CSS rules to localStorage, there is a check that
        // confirms the cache-busting query (CKEDITOR.timestamp) is in the CSS
        // paths. This prevents localStorage from caching something unbustable.
        //
        // @see ckeditor_library_info_alter()
        if (
          CKEDITOR.timestamp &&
          editorCssPath.indexOf(CKEDITOR.timestamp) !== -1 &&
          dialogCssPath.indexOf(CKEDITOR.timestamp) !== -1
        ) {
          Object.keys(window.localStorage).forEach((key) => {
            if (key.indexOf('Drupal.off-canvas.css.') === 0) {
              window.localStorage.removeItem(key);
            }
          });
          window.localStorage.setItem(
            `Drupal.off-canvas.css.${editorCssPath}${dialogCssPath}`,
            cssToInsert,
          );
        }
      },
    );
  };

  addCkeditorOffCanvasCss();
})(jQuery, CKEDITOR);
