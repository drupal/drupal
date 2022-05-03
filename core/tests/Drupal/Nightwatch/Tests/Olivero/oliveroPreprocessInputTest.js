const checkboxSelector = '#edit-form-checkboxes-title-attribute';

const inputTypes = [
  {
    selector: '#edit-form-textfield-test-title-and-required',
    type: 'text',
    api: 'textfield',
  },
  {
    selector: '#edit-form-email-title-no-xss',
    type: 'email',
    api: 'email',
  },
  {
    selector: '#edit-form-tel-title-no-xss',
    type: 'tel',
    api: 'tel',
  },
  {
    selector: '#edit-form-number-title-no-xss',
    type: 'number',
    api: 'number',
  },
  {
    selector: '#edit-form-search-title-no-xss',
    type: 'search',
    api: 'search',
  },
  {
    selector: '#edit-form-password-title-no-xss',
    type: 'password',
    api: 'password',
  },
  {
    selector: '#edit-form-date-title-no-xss',
    type: 'date',
    api: 'date',
  },
  {
    selector: '#edit-form-datetime-title-no-xss-time',
    type: 'time',
    api: 'date',
  },
  {
    selector: '#edit-form-file-title-no-xss',
    type: 'file',
    api: 'file',
  },
  {
    selector: '#edit-form-color-title-no-xss',
    type: 'color',
    api: 'color',
  },
  {
    selector: '#edit-form-url-title-no-xss',
    type: 'url',
    api: 'url',
  },
  // TODO - Cover datetime-local, month, week - no test form input examples are easily available
];

const booleanInputTypes = [
  {
    selector: '#edit-form-checkboxes-test-first-checkbox',
    type: 'checkbox',
  },
  {
    selector: '#edit-form-radios-title-attribute-first-radio',
    type: 'radio',
  },
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
  'Confirm that title attribute exists if set to display': (browser) => {
    browser
      .setWindowSize(1400, 800)
      .drupalRelativeURL('/form_test/form-labels')
      .waitForElementVisible(checkboxSelector, 1000)
      .assert.attributeEquals(
        checkboxSelector,
        'title',
        'Checkboxes test (Required)',
      );
  },
  'Check form element classes by type': (browser) => {
    browser.drupalRelativeURL('/form_test/form-labels');
    inputTypes.forEach((inputType) => {
      browser.assert.hasClass(inputType.selector, [
        'form-element',
        `form-element--type-${inputType.type}`,
        `form-element--api-${inputType.api}`,
      ]);
    });
    booleanInputTypes.forEach((booleanInputType) => {
      browser.assert.hasClass(booleanInputType.selector, [
        'form-boolean',
        `form-boolean--type-${booleanInputType.type}`,
      ]);
    });
  },
};
