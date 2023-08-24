/**
 * @file
 * Prevents flicker of the toolbar on page load.
 */

(() => {
  const toolbarState = sessionStorage.getItem('Drupal.toolbar.toolbarState')
    ? JSON.parse(sessionStorage.getItem('Drupal.toolbar.toolbarState'))
    : false;
  // These are classes that toolbar typically adds to <body>, but this code
  // executes before the first paint, when <body> is not yet present. The
  // classes are added to <html> so styling immediately reflects the current
  // toolbar state. The classes are removed after the toolbar completes
  // initialization.
  const classesToAdd = ['toolbar-loading', 'toolbar-anti-flicker'];
  if (toolbarState) {
    const {
      orientation,
      hasActiveTab,
      isFixed,
      activeTray,
      activeTabId,
      isOriented,
      userButtonMinWidth,
    } = toolbarState;

    classesToAdd.push(
      orientation ? `toolbar-${orientation}` : 'toolbar-horizontal',
    );
    if (hasActiveTab !== false) {
      classesToAdd.push('toolbar-tray-open');
    }
    if (isFixed) {
      classesToAdd.push('toolbar-fixed');
    }
    if (isOriented) {
      classesToAdd.push('toolbar-oriented');
    }

    if (activeTray) {
      // These styles are added so the active tab/tray styles are present
      // immediately instead of "flickering" on as the toolbar initializes. In
      // instances where a tray is lazy loaded, these styles facilitate the
      // lazy loaded tray appearing gracefully and without reflow.
      const styleContent = `
      .toolbar-loading #${activeTabId} {
        background-image: linear-gradient(rgba(255, 255, 255, 0.25) 20%, transparent 200%);
      }
      .toolbar-loading #${activeTabId}-tray {
        display: block; box-shadow: -1px 0 5px 2px rgb(0 0 0 / 33%);
        border-right: 1px solid #aaa; background-color: #f5f5f5;
        z-index: 0;
      }
      .toolbar-loading.toolbar-vertical.toolbar-tray-open #${activeTabId}-tray {
        width: 15rem; height: 100vh;
      }
     .toolbar-loading.toolbar-horizontal :not(#${activeTray}) > .toolbar-lining {opacity: 0}`;

      const style = document.createElement('style');
      style.textContent = styleContent;
      style.setAttribute('data-toolbar-anti-flicker-loading', true);
      document.querySelector('head').appendChild(style);
      if (userButtonMinWidth) {
        const userButtonStyle = document.createElement('style');
        userButtonStyle.textContent = `
        #toolbar-item-user {min-width: ${userButtonMinWidth}.px;}`;
        document.querySelector('head').appendChild(userButtonStyle);
      }
    }
  }
  document.querySelector('html').classList.add(...classesToAdd);
})();
