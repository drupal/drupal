module.exports = {
  'Demo Drupal.org': (browser) => {
    browser
      .relativeURL('/user/login')
      .waitForElementVisible('body', 1000)
      .assert.containsText('body', 'Powered by Drupal')
      .end();
  },
};
