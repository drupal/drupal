const preloadFontPaths = [
  'core/themes/olivero/fonts/metropolis/Metropolis-Regular.woff2',
  'core/themes/olivero/fonts/metropolis/Metropolis-SemiBold.woff2',
  'core/themes/olivero/fonts/metropolis/Metropolis-Bold.woff2',
  'core/themes/olivero/fonts/lora/lora-v14-latin-regular.woff2',
];

module.exports = {
  '@tags': ['core', 'olivero'],
  before(browser) {
    browser.drupalInstall({
      setupFile:
        'core/tests/Drupal/TestSite/TestSiteOliveroInstallTestScript.php',
      installProfile: 'minimal',
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Verify font loading': (browser) => {
    browser
      .drupalRelativeURL('/')
      .waitForElementVisible('body')
      // Check that <link rel="preload"> tags properly reference font.
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
        function (preloadFontPaths) {
          const basePath = drupalSettings.path.baseUrl;
          let selectorsExist = true;
          preloadFontPaths.forEach((path) => {
            if (!document.head.querySelector(`[href="${basePath + path}"]`)) {
              selectorsExist = false;
            }
          });

          return selectorsExist;
        },
        [preloadFontPaths],
        (result) => {
          browser.assert.ok(
            result.value,
            'Check that <link rel="preload"> tags properly reference font.',
          );
        },
      )
      // Check that the CSS @font-face declaration has loaded the font.
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
        function () {
          document.fonts.load('16px metropolis');
          document.fonts.load('16px Lora');
          return (
            document.fonts.check('16px metropolis') &&
            document.fonts.check('16px Lora')
          );
        },
        [],
        (result) => {
          browser.assert.ok(
            result.value,
            'Check that the CSS @font-face declaration has loaded the font.',
          );
        },
      );
  },
};
