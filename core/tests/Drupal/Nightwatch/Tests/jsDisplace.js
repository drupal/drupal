const testValues = {
  top: 200,
  right: 110,
  bottom: 145,
  left: 310,
};

const testElements = `
  <div
    data-offset-top
    style="
      background-color: red;
      height: 110px;
      left: 0;
      position: fixed;
      right: 0;
      top: 90px;
      width: 100%;"
  ></div>

  <div
    data-offset-right
    style="
      background-color: blue;
      bottom: 0;
      height: 100%;
      position: fixed;
      right: 10px;
      top: 0;
      width: 100px;"
  ></div>

  <div
    data-offset-bottom
    style="
      background-color: yellow;
      bottom: 45px;
      height: 100px;
      left: 0;
      position: fixed;
      right: 0;
      width: 100%;"
  ></div>

  <div
    data-offset-left
    style="
      background-color: orange;
      bottom: 0;
      height: 100%;
      left: 10px;
      position: fixed;
      top: 0;
      width: 300px;"
  ></div>
`;

module.exports = {
  '@tags': ['core'],
  before(browser) {
    browser.drupalInstall().drupalInstallModule('js_displace');
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Test Drupal.displace() JavaScript API': (browser) => {
    browser
      .drupalRelativeURL('/')
      .waitForElementVisible('body')
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
        function (testValues, testElements) {
          const testElementsContainer = document.createElement('div');

          testElementsContainer.innerHTML = testElements;
          document.body.append(testElementsContainer);

          const displaceOutput = Drupal.displace();
          return (
            displaceOutput.top === testValues.top &&
            displaceOutput.right === testValues.right &&
            displaceOutput.bottom === testValues.bottom &&
            displaceOutput.left === testValues.left
          );
        },
        [testValues, testElements],
        (result) => {
          browser.assert.ok(
            result.value,
            'Drupal.displace() JS returns proper offsets for all edges.',
          );
        },
      )
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
        function (testValues) {
          const rootStyles = getComputedStyle(document.documentElement);
          const topOffsetStyle = rootStyles.getPropertyValue(
            '--drupal-displace-offset-top',
          );
          const rightOffsetStyle = rootStyles.getPropertyValue(
            '--drupal-displace-offset-right',
          );
          const bottomOffsetStyle = rootStyles.getPropertyValue(
            '--drupal-displace-offset-bottom',
          );
          const leftOffsetStyle = rootStyles.getPropertyValue(
            '--drupal-displace-offset-left',
          );
          return (
            topOffsetStyle === `${testValues.top}px` &&
            rightOffsetStyle === `${testValues.right}px` &&
            bottomOffsetStyle === `${testValues.bottom}px` &&
            leftOffsetStyle === `${testValues.left}px`
          );
        },
        [testValues],
        (result) => {
          browser.assert.ok(
            result.value,
            'Drupal.displace() properly sets CSS variables.',
          );
        },
      );
  },
};
