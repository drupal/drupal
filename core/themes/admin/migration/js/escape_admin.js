/* eslint-disable func-names, no-mutable-exports, comma-dangle, strict */

((Drupal, drupalSettings, once) => {
  /**
   * Replaces the "Home" link with "Back to site" link.
   *
   * Back to site link points to the last non-administrative page the user
   * visited within the same browser tab.
   */
  Drupal.behaviors.ginEscapeAdmin = {
    attach: (context) => {
      once('ginEscapeAdmin', '[data-gin-toolbar-escape-admin]', context).forEach(el => {
        const escapeAdminPath = sessionStorage.getItem('escapeAdminPath');

        if (drupalSettings.path.currentPathIsAdmin && escapeAdminPath !== null) {
          el.setAttribute('href', escapeAdminPath);
        }
      });
    },
  };

})(Drupal, drupalSettings, once);
