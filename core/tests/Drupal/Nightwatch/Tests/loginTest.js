module.exports = {
  '@tags': ['core'],

  before(browser) {
    browser.drupalInstall();
  },
  after(browser) {
    browser.drupalUninstall();
  },

  'Test login': browser => {
    browser
      .drupalCreateUser({
        name: 'user',
        password: '123',
        permissions: ['access site reports'],
      })
      .drupalLogin({ name: 'user', password: '123' })
      .drupalRelativeURL('/admin/reports')
      .expect.element('h1.page-title')
      .text.to.contain('Reports');
  },
};
