// Testing the shimmed jQuery UI :tabbable selector.

// Test confirming the :tabbable shim returns the same values as jQuery UI
// :tabbable.
//
// An array of objects with the following properties:
//   - element: the element to test
//   - tabbable: the number of items the :tabbable selector should return when
//     the element is the only item in the container being queried.
const tabbableTestScenarios = [
  {
    element: '<div>',
    tabbable: 0,
  },
  {
    element: '<div tabindex="0">',
    tabbable: 1,
  },
  {
    element: '<div tabindex="0" hidden>',
    tabbable: 0,
  },
  {
    element: '<div tabindex="0" style="display:none;">',
    tabbable: 0,
  },
  {
    element: '<div href="#">',
    tabbable: 0,
  },
  {
    element: '<a>',
    tabbable: 0,
  },
  {
    element: '<a href="#">',
    tabbable: 1,
  },
  {
    element: '<a tabindex="0">',
    tabbable: 1,
  },
  {
    element: '<a tabindex="-1">',
    tabbable: 0,
  },
  {
    element: '<input type="hidden">',
    tabbable: 0,
  },
  {
    element: '<input type="hidden" tabindex="0">',
    tabbable: 0,
  },
  {
    element: '<input type="hidden" tabindex="1">',
    tabbable: 0,
  },
  {
    element:
      '<details><summary>Summary is now tabbable because IE is not supported anymore</summary>Hooray</details>',
    tabbable: 1,
  },
  {
    element:
      '<details>A details without a summary should be :tabbable</details>',
    tabbable: 1,
  },
  {
    element: '<ul><li>List item</li></ul>',
    tabbable: 0,
  },
  {
    element: '<ul><li tabindex="0">List item</li></ul>',
    tabbable: 1,
  },
];

// Element types to add to the test scenarios.
const elementTypesUsedByTabbableTest = [
  'input-button',
  'input-checkbox',
  'input-color',
  'input-date',
  'input-datetime-local',
  'input-email',
  'input-file',
  'input-image',
  'input-month',
  'input-number',
  'input-password',
  'input-radio',
  'input-range',
  'input-reset',
  'input-search',
  'input-submit',
  'input-tel',
  'input-text',
  'input-time',
  'input-url',
  'input-week',
  'select',
  'button',
  'textarea',
];

// Create multiple test scenarios.

// For each element type being tested, create multiple variations with different
// attributes and store them in the `element:` property. The `tabbable:` property
// is the number of elements in `element:` that would match the :tabbable
// selector.
// Tha variations include:
// - The element with no additional attributes.
// - Separate scenarios for tabindex 0, 1, and -1.
// - With the hidden attribute
// - With `style="display:none;"`
// - With `style="visibility: hidden;"`
elementTypesUsedByTabbableTest.forEach((item) => {
  let elementType = item;
  let selfClose = '';
  let type = '';
  if (item.indexOf('-') > 0) {
    [elementType, type] = item.split('-');
    type = ` type="${type}"`;
    selfClose = ' /';
  }

  tabbableTestScenarios.push({
    element: `<${elementType}${type}${selfClose}>`,
    tabbable: 1,
  });
  tabbableTestScenarios.push({
    element: `<${elementType}${type} tabindex="0"${selfClose}>`,
    tabbable: 1,
  });
  tabbableTestScenarios.push({
    element: `<${elementType}${type} tabindex="1"${selfClose}>`,
    tabbable: 1,
  });
  tabbableTestScenarios.push({
    element: `<${elementType}${type} tabindex="-1"${selfClose}>`,
    tabbable: 0,
  });
  tabbableTestScenarios.push({
    element: `<${elementType}${type} hidden${selfClose}>`,
    tabbable: 0,
  });
  tabbableTestScenarios.push({
    element: `<${elementType}${type} style="display:none;"${selfClose}>`,
    tabbable: 0,
  });
  tabbableTestScenarios.push({
    element: `<${elementType}${type} style="visibility: hidden;"${selfClose}>`,
    tabbable: 0,
  });
});

// The default options for items in dialogIntegrationTestScenarios.
const defaultDialogOptions = {
  buttons: [
    {
      text: 'Ok',
      click: () => {},
    },
  ],
};

// Contains scenarios for testing dialog's use of the :tabbable selector.
// These are based on the "focus tabbable" tests within jQuery UI
// @see
//   https://github.com/jquery/jquery-ui/blob/1.12.1/tests/unit/dialog/core.js
const dialogIntegrationTestScenarios = [
  {
    info: 'An element that was focused previously.',
    markup: '<div><input><input></div>',
    options: {},
    // eslint-disable-next-line object-shorthand, func-names
    testActions: function ($element) {
      const $input = $element
        .find('input:last')
        .trigger('focus')
        .trigger('blur');
      $element.dialog('instance')._focusTabbable();
      return $input[0];
    },
  },
  {
    info: 'First element inside the dialog matching [autofocus]',
    markup: '<div><input><input autofocus></div>',
    options: defaultDialogOptions,
    // eslint-disable-next-line object-shorthand, func-names
    testActions: function ($element) {
      return $element.find('input')[1];
    },
  },
  {
    info: 'Tabbable element inside the content element',
    markup: '<div><input><input></div>',
    options: defaultDialogOptions,
    // eslint-disable-next-line object-shorthand, func-names
    testActions: function ($element) {
      return $element.find('input')[0];
    },
  },
  {
    info: 'Tabbable element inside the buttonpane',
    markup: '<div>text</div>',
    options: defaultDialogOptions,
    // eslint-disable-next-line object-shorthand, func-names
    testActions: function ($element) {
      return $element.dialog('widget').find('.ui-dialog-buttonpane button')[0];
    },
  },
  {
    info: 'The close button',
    markup: '<div>text</div>',
    options: {},
    // eslint-disable-next-line object-shorthand, func-names
    testActions: function ($element) {
      return $element
        .dialog('widget')
        .find('.ui-dialog-titlebar .ui-dialog-titlebar-close')[0];
    },
  },
  {
    info: 'The dialog itself',
    markup: '<div>text</div>',
    options: { autoOpen: false },
    // eslint-disable-next-line object-shorthand, func-names
    testActions: function ($element) {
      $element.dialog('widget').find('.ui-dialog-titlebar-close').hide();
      $element.dialog('open');
      return $element.parent()[0];
    },
  },
  {
    info: 'Focus starts on second input',
    markup: '<div><input><input autofocus></div>',
    options: {
      // eslint-disable-next-line object-shorthand, func-names
      open: function () {
        const inputs = jQuery(this).find('input');
        inputs.last().on('keydown', function (event) {
          event.preventDefault();
          inputs.first().trigger('focus');
        });
      },
    },
    // eslint-disable-next-line object-shorthand, func-names
    testActions: function ($element) {
      const inputs = $element.find('input');
      return inputs[1];
    },
  },
];

module.exports = {
  '@tags': ['core'],
  before(browser) {
    browser.drupalInstall().drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/admin/modules')
        .setValue('input[type="search"]', 'Tabbable Shim Test')
        .waitForElementVisible(
          'input[name="modules[tabbable_shim_test][enable]"]',
          1000,
        )
        .click('input[name="modules[tabbable_shim_test][enable]"]')
        .click('input[type="submit"]');
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'test tabbable': (browser) => {
    browser
      .drupalRelativeURL('/tabbable-shim-test')
      .waitForElementPresent('#tabbable-test-container', 1000);

    tabbableTestScenarios.forEach((iteration) => {
      browser.execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (scenario) {
          const $container = jQuery('#tabbable-test-container');
          $container.empty();
          $container.append(jQuery(scenario.element));

          return {
            expected: scenario.tabbable,
            actual: $container.find(':tabbable').length,
            element: scenario.element,
          };
        },
        [iteration],
        (result) => {
          browser.assert.ok(typeof result.value.actual === 'number');
          browser.assert.ok(typeof result.value.expected === 'number');
          browser.assert.equal(
            result.value.actual,
            result.value.expected,
            `Expected :tabbable to return ${result.value.expected} for element ${result.value.element}`,
          );
        },
      );
    });
    browser.assert.deprecationErrorExists(
      'The :tabbable selector is deprecated in Drupal 9.2.0 and will be removed in Drupal 11.0.0. Use the core/tabbable library instead. See https://www.drupal.org/node/3183730',
    );
    browser.drupalLogAndEnd({ onlyOnError: false });
  },
  'test tabbable dialog integration': (browser) => {
    browser
      .drupalRelativeURL('/tabbable-shim-dialog-integration-test')
      .waitForElementPresent('#tabbable-dialog-test-container', 1000);

    dialogIntegrationTestScenarios.forEach((iteration) => {
      browser.execute(
        // eslint-disable-next-line func-names
        function (scenario, testActions) {
          // Create the jQuery element that will be used in the test steps.
          const $element = jQuery(scenario.markup).dialog(scenario.options);

          // Convert the testActions string into a function. testActions is a
          // string due to functions being removed from objects passed to
          // browser.execute().
          // The expectedActiveElement function performs steps specific to a test
          // iteration, then returns the element expected to be active after
          // those steps.
          // eslint-disable-next-line no-new-func
          const expectedActiveElement = new Function(`return ${testActions}`)();
          return expectedActiveElement($element) === document.activeElement;
        },
        [iteration, iteration.testActions.toString()],
        (result) => {
          browser.assert.equal(result.value, true, iteration.info);
        },
      );
    });
    browser.assert.deprecationErrorExists(
      'The :tabbable selector is deprecated in Drupal 9.2.0 and will be removed in Drupal 11.0.0. Use the core/tabbable library instead. See https://www.drupal.org/node/3183730',
    );
    browser.drupalLogAndEnd({ onlyOnError: false });
  },
};
