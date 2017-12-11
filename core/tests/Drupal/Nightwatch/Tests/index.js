module.exports = {
  'Demo Drupal.org': (browser) => {
    browser
      .relativeURL('/community')
      .waitForElementVisible('body', 1000)
      .assert.containsText('body', 'Where is the Drupal Community?')
      .end();
  },
};
