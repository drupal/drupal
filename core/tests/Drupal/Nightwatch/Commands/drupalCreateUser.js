/**
 * Logs into Drupal as the given user.
 *
 * @param {object} settings
 *   Settings object
 * @param {string} settings.name
 *   The user name.
 * @param {string} settings.password
 *   The user password.
 * @param {array} [settings.permissions=[]]
 *   The list of permissions granted for the user.
 * @param {function} callback
 *   A callback which will be called when creating the user is finished.
 * @return {object}
 *   The drupalCreateUser command.
 */
exports.command = function drupalCreateUser(
  { name, password, permissions = [] },
  callback,
) {
  const self = this;

  // Define the name here because the callback from drupalCreateRole can be
  // undefined in some cases.
  const roleName = Math.random()
    .toString(36)
    .replace(/[^\w\d]/g, '')
    .substring(2, 15);
  this.perform((client, done) => {
    if (permissions.length) {
      this.drupalCreateRole({ permissions, name: roleName }, done);
    }
  }).drupalLoginAsAdmin(async () => {
    this.drupalRelativeURL('/admin/people/create')
      .setValue('input[name="name"]', name)
      .setValue('input[name="pass[pass1]"]', password)
      .setValue('input[name="pass[pass2]"]', password)
      .perform((client, done) => {
        if (permissions.length) {
          client.click(`input[name="roles[${roleName}]`, () => {
            done();
          });
        } else {
          done();
        }
      })
      .submitForm('#user-register-form')
      .assert.textContains(
        '[data-drupal-messages]',
        'Created a new user account',
        `User "${name}" was created successfully.`,
      );
  });

  if (typeof callback === 'function') {
    callback.call(self);
  }

  return this;
};
