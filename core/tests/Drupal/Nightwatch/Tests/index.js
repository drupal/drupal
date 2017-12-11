module.exports = {
  'Demo Drupal.org': (browser) => {
    browser
      .drupalURL('/community')
      .waitForElementVisible('body', 1000)
      .assert.containsText('body', 'Where is the Drupal Community?')
      .end();
  },
};
