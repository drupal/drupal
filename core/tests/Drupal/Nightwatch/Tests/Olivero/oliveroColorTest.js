const selectors = {
  schemePicker: '[data-drupal-selector="edit-color-scheme"]',
  primaryColor: {
    text: 'input[type="text"][name="base_primary_color"]',
    color: 'input[type="color"][name="base_primary_color_visual"]',
  },
  submit: '[data-drupal-selector="edit-submit"]',
  siteHeader: '.site-header__initial',
};

const colorSchemes = {
  default: {
    base_primary_color: '#1b9ae4',
  },
  firehouse: {
    base_primary_color: '#a30f0f',
  },
  ice: {
    base_primary_color: '#57919e',
  },
  plum: {
    base_primary_color: '#7a4587',
  },
  slate: {
    base_primary_color: '#47625b',
  },
};

module.exports = {
  '@tags': ['core', 'olivero'],
  before(browser) {
    browser
      .drupalInstall({
        setupFile:
          'core/tests/Drupal/TestSite/TestSiteOliveroInstallTestScript.php',
        installProfile: 'minimal',
      })
      // Create user that can search.
      .drupalCreateUser({
        name: 'user',
        password: '123',
        permissions: ['administer themes', 'view the administration theme'],
      })
      .drupalLogin({ name: 'user', password: '123' });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Olivero Settings - color schemes update individual values': (browser) => {
    browser
      .drupalRelativeURL('/admin/appearance/settings/olivero')
      .waitForElementVisible(selectors.schemePicker)
      .click(`${selectors.schemePicker} option[value="firehouse"]`)
      .assert.valueEquals(
        selectors.primaryColor.text,
        colorSchemes.firehouse.base_primary_color,
      )
      .assert.valueEquals(
        selectors.primaryColor.color,
        colorSchemes.firehouse.base_primary_color,
      )
      .click(`${selectors.schemePicker} option[value="ice"]`)
      .assert.valueEquals(
        selectors.primaryColor.text,
        colorSchemes.ice.base_primary_color,
      )
      .assert.valueEquals(
        selectors.primaryColor.color,
        colorSchemes.ice.base_primary_color,
      )
      .click(`${selectors.schemePicker} option[value="plum"]`)
      .assert.valueEquals(
        selectors.primaryColor.text,
        colorSchemes.plum.base_primary_color,
      )
      .assert.valueEquals(
        selectors.primaryColor.color,
        colorSchemes.plum.base_primary_color,
      )
      .click(`${selectors.schemePicker} option[value="slate"]`)
      .assert.valueEquals(
        selectors.primaryColor.text,
        colorSchemes.slate.base_primary_color,
      )
      .assert.valueEquals(
        selectors.primaryColor.color,
        colorSchemes.slate.base_primary_color,
      )
      .click(`${selectors.schemePicker} option[value="default"]`)
      .assert.valueEquals(
        selectors.primaryColor.text,
        colorSchemes.default.base_primary_color,
      )
      .assert.valueEquals(
        selectors.primaryColor.color,
        colorSchemes.default.base_primary_color,
      );
  },
  'Olivero Settings - color inputs stay synchronized': (browser) => {
    browser
      .drupalRelativeURL('/admin/appearance/settings/olivero')
      .waitForElementVisible(selectors.primaryColor.text)
      .waitForElementVisible(selectors.primaryColor.color)
      .updateValue(selectors.primaryColor.text, '#ff0000')
      .assert.valueEquals(selectors.primaryColor.color, '#ff0000')
      .updateValue(selectors.primaryColor.text, '#00ff00')
      .assert.valueEquals(selectors.primaryColor.color, '#00ff00')
      .updateValue(selectors.primaryColor.text, '#0000ff')
      .assert.valueEquals(selectors.primaryColor.color, '#0000ff');
  },
  'Olivero Settings - color selections impact olivero theme': (browser) => {
    browser
      .drupalRelativeURL('/admin/appearance/settings/olivero')
      .waitForElementVisible(selectors.primaryColor.color)
      .updateValue(selectors.primaryColor.text, '#ff0000') // hsl(0, 100%, 50%)
      .click(selectors.submit)
      .waitForElementVisible(selectors.primaryColor.color)
      .drupalRelativeURL('/')
      .waitForElementVisible(selectors.siteHeader)
      .expect.element(selectors.siteHeader)
      .to.have.css('backgroundColor', 'rgb(255, 0, 0)');

    browser
      .drupalRelativeURL('/admin/appearance/settings/olivero')
      .waitForElementVisible(selectors.primaryColor.color)
      .updateValue(selectors.primaryColor.text, '#7a4587') // hsl(0, 100%, 50%)
      .click(selectors.submit)
      .waitForElementVisible(selectors.primaryColor.color)
      .drupalRelativeURL('/')
      .waitForElementVisible(selectors.siteHeader)
      .expect.element(selectors.siteHeader)
      .to.have.css('backgroundColor', 'rgb(122, 69, 135)');
  },
};
