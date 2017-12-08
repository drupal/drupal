module.exports = {
  'Demo Drupal.org' : function (browser) {
    browser
      .url('https://www.drupal.org/')
      .waitForElementVisible('body', 1000)
      .assert.containsText('body', 'Launch, manage, and scale ambitious digital experiences—with the flexibility to build great websites or push beyond the browser. Proudly open source.')
      .end();
  }
};
