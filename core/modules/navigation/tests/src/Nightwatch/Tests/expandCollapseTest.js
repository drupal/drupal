const selectors = {
  expandButton: {
    expanded: '.admin-toolbar__expand-button[aria-expanded=true]',
    collapsed: '.admin-toolbar__expand-button[aria-expanded=false]',
  },
  htmlAttribute: {
    expanded: '[data-admin-toolbar="expanded"]',
    collapsed: '[data-admin-toolbar="collapsed"]',
  },
};

module.exports = {
  '@tags': ['core', 'navigation'],
  browser(browser) {
    browser
      .drupalInstall()
      .drupalInstallModule('navigation', true)
      .setWindowSize(1220, 800);
  },
  after(browser) {
    browser.drupalUninstall();
  },

  'Expand/Collapse': (browser) => {
    browser.drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/')
        .waitForElementPresent(
          '[data-once="admin-toolbar-document-triggers-listener"][data-admin-toolbar="expanded"]',
        )
        // This pause required to wait for first init event.
        .waitForElementVisible(selectors.expandButton.expanded)
        .click(selectors.expandButton.expanded)
        .waitForElementNotPresent(selectors.expandButton.expanded)
        .waitForElementPresent(selectors.expandButton.collapsed)
        .waitForElementPresent(selectors.htmlAttribute.collapsed)
        .click(selectors.expandButton.collapsed)
        .waitForElementPresent(selectors.expandButton.expanded)
        .waitForElementPresent(selectors.htmlAttribute.expanded);
    });
  },
};
