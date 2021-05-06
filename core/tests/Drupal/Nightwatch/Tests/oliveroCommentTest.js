module.exports = {
  '@tags': ['core', 'olivero'],
  before(browser) {
    browser
      .drupalInstall({
        setupFile:
          'core/tests/Drupal/TestSite/TestSiteOliveroInstallTestScript.php',
        installProfile: 'minimal',
      })
      .drupalLoginAsAdmin(() => {
        browser
          .drupalRelativeURL('/admin/modules')
          .setValue('input[type="search"]', 'Comment')
          .waitForElementVisible('input[name="modules[comment][enable]"]', 1000)
          .click('input[name="modules[comment][enable]"]')
          .clearValue('input[type="search"]')
          .setValue('input[type="search"]', 'Field UI')
          .waitForElementVisible(
            'input[name="modules[field_ui][enable]"]',
            1000,
          )
          .click('input[name="modules[field_ui][enable]"]')
          .click('input[type="submit"]'); // Submit module form.
        browser
          .drupalRelativeURL('/admin/structure/comment/types/add')
          .setValue('input[name="label"]', 'Default')
          .waitForElementVisible('span.machine-name-label', 1000)
          .click('select[name="target_entity_type_id"] option[value="node"]')
          .click('input[type="submit"]');
        browser
          .drupalRelativeURL('/admin/structure/types/add')
          .setValue('input[name="name"]', 'Article')
          .waitForElementVisible('span.machine-name-label', 1000)
          .click('input[type="submit"]');
        browser
          .drupalRelativeURL(
            '/admin/structure/types/manage/article/fields/add-field',
          )
          .setValue('select[name="new_storage_type"]', 'comment')
          .setValue('input[name="label"]', 'Comments')
          .waitForElementVisible('span.machine-name-label', 1000)
          .click('input[type="submit"]');
        browser
          .drupalRelativeURL('/admin/config/development/performance')
          .click('input#edit-clear');
        browser
          .drupalRelativeURL('/node/add/article')
          .setValue('input[name="title[0][value]"]', 'Test article')
          .setValue(
            'textarea[name="body[0][value]"]',
            'Body for the test article',
          )
          .click('input[type="submit"]');
      })
      .drupalCreateUser({
        name: 'user',
        password: '123',
        permissions: [
          'access comments',
          'post comments',
          'skip comment approval',
        ],
      })
      .drupalLogin({ name: 'user', password: '123' });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'create comment and comment counts': (browser) => {
    browser
      .drupalRelativeURL('/node/1')
      .setValue('input[name="subject[0][value]"]', 'A test comment!')
      .click('input[type="submit"]')
      .assert.containsText('h2.comments__title', 'Comments')
      .assert.containsText('h2.comments__title .comments__count', '1');
  },
};
