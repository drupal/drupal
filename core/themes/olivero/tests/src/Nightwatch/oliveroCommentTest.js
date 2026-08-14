const commentTitleSelector = 'h2.comments__title';
const commentCountSelector = 'h2.comments__title .comments__count';

module.exports = {
  '@tags': ['core', 'olivero'],
  before(browser) {
    browser
      .drupalInstall({
        setupFile:
          'core/tests/Drupal/TestSite/TestSiteOliveroInstallTestScript.php',
        installProfile: 'minimal',
      })
      .drupalCreateUser({
        name: 'user',
        password: '123',
        permissions: [
          'access comments',
          'post comments',
          'skip comment approval',
        ],
      })
      .drupalLogin({ name: 'user', password: '123' });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Article without comments should not display count': (browser) => {
    browser
      .drupalRelativeURL('/node/1')
      .assert.textContains('body', 'Article without comments')
      .assert.not.elementPresent(commentCountSelector);
  },
  'Article with comments should display count': (browser) => {
    browser
      .drupalRelativeURL('/node/2')
      .assert.textContains('body', 'Article with comments')
      .assert.elementPresent(commentTitleSelector)
      .assert.elementPresent(commentCountSelector)
      .assert.textContains(commentCountSelector, '2');
  },
};
