const selectors = {
  expandButton: {
    expanded: '.admin-toolbar__expand-button[aria-expanded=true]',
    collapsed: '.admin-toolbar__expand-button[aria-expanded=false]',
  },
  htmlAttribute: {
    expanded: '[data-admin-toolbar="expanded"]',
    collapsed: '[data-admin-toolbar="collapsed"]',
  },
  clearCacheButton: 'input[data-drupal-selector="edit-clear"]',
};

module.exports = {
  '@tags': ['core', 'navigation'],
  browser(browser) {
    browser
      .drupalInstall()
      .drupalInstallModule('navigation', true)
      .drupalInstallModule('big_pipe');
  },
  after(browser) {
    browser.drupalUninstall();
  },

  'Expand/Collapse - wide viewport': (browser) => {
    browser.drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/admin/config/development/performance')
        .setWindowSize(1220, 800)
        .click(selectors.clearCacheButton)
        .waitForElementPresent(
          '[data-once="admin-toolbar-document-triggers-listener"]',
        )
        // This pause required to wait for first init event.
        .waitForElementNotPresent(selectors.expandButton.expanded)
        .waitForElementPresent(selectors.expandButton.collapsed)
        .waitForElementPresent(selectors.htmlAttribute.collapsed)
        .click(selectors.expandButton.collapsed)
        .waitForElementPresent(selectors.expandButton.expanded)
        .waitForElementPresent(selectors.htmlAttribute.expanded)
        .click(selectors.expandButton.expanded)
        .waitForElementNotPresent(selectors.expandButton.expanded)
        .waitForElementPresent(selectors.expandButton.collapsed)
        .waitForElementPresent(selectors.htmlAttribute.collapsed);
    });
  },

  'Expand/Collapse - narrow viewport': (browser) => {
    browser.drupalLoginAsAdmin(() => {
      const expandButtonSelector = {
        selector:
          '//*[@class="admin-toolbar-control-bar"]//button/*[text()="Expand sidebar"]/ancestor::button',
        locateStrategy: 'xpath',
      };

      const sidebarItemSelector = {
        selector:
          '//*[@id="admin-toolbar"]//button/*[text()="Configuration"]/ancestor::button',
        locateStrategy: 'xpath',
      };

      browser
        .drupalRelativeURL('/admin/config/development/performance')
        .setWindowSize(1000, 800)
        .waitForElementNotVisible(sidebarItemSelector);

      browser
        .click(expandButtonSelector)
        .waitForElementVisible(sidebarItemSelector);

      // eslint-disable-next-line no-unused-expressions
      browser.expect.element(expandButtonSelector).not.to.be.active;

      browser
        .sendKeys('html', browser.Keys.ESCAPE)
        .waitForElementNotVisible(sidebarItemSelector);

      // eslint-disable-next-line no-unused-expressions
      browser.expect.element(expandButtonSelector).to.be.active;
    });
  },
};
