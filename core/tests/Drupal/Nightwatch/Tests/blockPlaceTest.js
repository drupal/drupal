module.exports = {
  '@tags': ['block'],
  before(browser) {
    browser.drupalInstall().drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/admin/modules')
        .setValue('input[type="search"]', 'Block')
        .waitForElementVisible('input[name="modules[block][enable]"]', 1000)
        .click('input[name="modules[block][enable]"]')
        .click('input[type="submit"]') // Submit module form.
        .drupalCreateUser({
          name: 'user',
          password: '123',
          permissions: ['administer blocks'],
        });
    });
  },
  beforeEach(browser) {
    browser.drupalLogin({ name: 'user', password: '123' });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Test once on block filter': (browser) => {
    browser
      .drupalRelativeURL('/admin/structure/block')
      .click('#edit-blocks-region-header-title')
      .waitForElementVisible('#drupal-modal');
    browser.expect
      .elements('[data-once="block-filter-text"]')
      .count.to.equal(1);
    browser.executeAsync(
      function (done) {
        done(once('block-filter-text', 'input.block-filter-text'));
      },
      [],
      (result) => {
        browser.assert.equal(
          result.value.length,
          0,
          'once returned no elements',
        );
      },
    );
    browser.executeAsync(
      function (done) {
        Drupal.attachBehaviors();
        done(once('block-filter-text', 'input.block-filter-text'));
      },
      [],
      (result) => {
        browser.assert.equal(
          result.value.length,
          0,
          'once returned no elements after second attachBehaviors',
        );
      },
    );
  },
  'Test block highlight on placement': (browser) => {
    browser
      // Set the height smaller, for testing scroll on placed.
      .resizeWindow(1000, 400)
      .drupalRelativeURL('/admin/structure/block')
      .click('#edit-blocks-region-header-title')
      .waitForElementVisible('#drupal-modal')
      // @todo Cannot actually click on the button for visible row.
      //   Always defaults to the first row, even if hidden.
      .setValue('input.block-filter-text', 'Tabs')
      .click('.block-add-table tbody tr:first-child a')
      .waitForElementVisible('.ui-dialog-buttonpane button.button--primary')
      .click('.ui-dialog-buttonpane button.button--primary');
    browser.expect.url().to.endWith('?block-placement=pagetitle');
    // Pause for animation.
    // @todo run execute, wait for jQuery:animate, then assert?
    browser.pause(500);
    browser.executeAsync(
      function (done) {
        done(
          once(
            'block-highlight',
            '[data-drupal-selector="edit-blocks"]',
            document,
          ),
        );
      },
      [],
      (result) => {
        browser.assert.equal(
          result.value.length,
          0,
          'once returned no elements',
        );
      },
    );
    browser.executeAsync(
      function (done) {
        done({
          windowOffset: window.pageYOffset,
          elementOffset: document.querySelector('.js-block-placed').offsetTop,
        });
      },
      [],
      (result) =>
        browser.assert.equal(
          result.value.windowOffset,
          result.value.elementOffset,
          'window was scrolled to the placed block',
        ),
    );
  },
};
