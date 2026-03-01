/* To inject this as early as possible
 * we use native JS instead of Drupal's behaviors.
*/

// Legacy Check: Transform old localStorage items to newer ones.
function checkLegacy() {
  if (localStorage.getItem('GinDarkMode')) {
    localStorage.removeItem('GinDarkMode');
  }
  if (localStorage.getItem('Drupal.gin.dark_mode')) {
    localStorage.removeItem('Drupal.gin.dark_mode');
  }
  if (localStorage.getItem('GinSidebarOpen')) {
    localStorage.setItem('Drupal.gin.toolbarExpanded', localStorage.getItem('GinSidebarOpen'));
    localStorage.removeItem('GinSidebarOpen');
  }
}

checkLegacy();

// Dark mode Check.
function ginInitDarkMode() {
  const darkModeClass = 'gin--dark-mode';

  const darkModeSetting = document.getElementById('gin-setting-dark_mode')?.textContent;
  // Set window variable.
  window.ginDarkMode = darkModeSetting ? JSON.parse(darkModeSetting)?.ginDarkMode : 'auto';

  if (
    window.ginDarkMode == 1 ||
    window.ginDarkMode === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches
  ) {
    document.documentElement.classList.add(darkModeClass);
  } else {
    document.documentElement.classList.contains(darkModeClass) === true && document.documentElement.classList.remove(darkModeClass);
  }
}

ginInitDarkMode();

// Sidebar checks.
if (localStorage.getItem('Drupal.gin.sidebarWidth')) {
  const sidebarWidth = localStorage.getItem('Drupal.gin.sidebarWidth');
  document.documentElement.style.setProperty('--gin-sidebar-width', sidebarWidth);
}

if (localStorage.getItem('Drupal.gin.sidebarExpanded.desktop')) {
  const style = document.createElement('style');
  const className = 'gin-sidebar-inline-styles';
  style.className = className;

  if (window.innerWidth < 1024 || localStorage.getItem('Drupal.gin.sidebarExpanded.desktop') === 'false') {
    style.innerHTML = `
    body {
      --gin-sidebar-offset: 0px;
      padding-inline-end: 0;
      transition: none;
    }

    .layout-region--secondary {
      transform: translateX(var(--gin-sidebar-width, 360px));
      transition: none;
    }

    .meta-sidebar__overlay {
      display: none;
    }
    `;

    const scriptTag = document.querySelector('script');
    scriptTag.parentNode.insertBefore(style, scriptTag);
  } else if (document.getElementsByClassName(className).length > 0) {
    document.getElementsByClassName(className)[0].remove();
  }
}
