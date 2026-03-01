/* eslint-disable func-names, no-mutable-exports, comma-dangle, strict */

((Drupal, once) => {
  Drupal.behaviors.ginCoreNavigation = {
    attach: (context) => {
      Drupal.ginCoreNavigation.initKeyboardShortcut(context);
    },
  };

  Drupal.ginCoreNavigation = {
    initKeyboardShortcut: function (context) {
      once('ginToolbarKeyboardShortcut', '.admin-toolbar__expand-button', context).forEach(() => {
        // Show toolbar navigation with shortcut:
        // OPTION + T (Mac) / ALT + T (Windows)
        document.addEventListener('keydown', e => {
          if (e.altKey === true && e.code === 'KeyT') {
            this.toggleToolbar();
          }
        });
      });

      once('ginToolbarClickHandler', '.top-bar__burger, .admin-toolbar__expand-button', context).forEach(button => {
        button.addEventListener('click', () => {
          if (window.innerWidth < 1280 && button.getAttribute('aria-expanded', 'false')) {
            Drupal.ginSidebar?.collapseSidebar();
          }
        });
      });
    },

    toggleToolbar() {
      let toolbarTrigger = document.querySelector('.admin-toolbar__expand-button');

      // Core navigation.
      if (toolbarTrigger) {
        toolbarTrigger.click();
        return;
      }
    },

    collapseToolbar: function () {
      document.querySelectorAll('.top-bar__burger, .admin-toolbar__expand-button').forEach(button => {
        button.setAttribute('aria-expanded', 'false');
      });
      document.documentElement.setAttribute('data-admin-toolbar', 'collapsed');
      Drupal.displace(true);
    },

  };

})(Drupal, once);
