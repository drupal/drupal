/**
 * Creates role with given permissions.
 *
 * @param {object} settings
 *   Settings object
 * @param {array} settings.permissions
 *   The list of roles granted for the user.
 * @param {string} [settings.name=null]
 *   The role name.
 * @param {function} callback
 *   A callback which will be called, when creating the role is finished.
 * @return {object}
 *   The drupalCreateRole command.
 */
exports.command = function drupalCreateRole(
  { permissions, name = null },
  callback,
) {
  const self = this;
  const roleName = name || Math.random().toString(36).substring(2, 15);

  let machineName;
  this.drupalLoginAsAdmin(async () => {
    this.drupalRelativeURL('/admin/people/roles/add');
    this.setValue('input[name="label"]', roleName);

    this.execute(() => {
      jQuery('input[name="label"]').trigger('formUpdated');
    });
    // Wait for the machine name to appear so that it can be used later to
    // select the permissions from the permission page.
    this.expect
      .element('.user-role-form .machine-name-value')
      .to.be.visible.before(2000);

    machineName = await this.getText('.user-role-form .machine-name-value');
    this.submitForm('#user-role-form').assert.textContains(
      '[data-drupal-messages]',
      `Role ${roleName} has been added.`,
    );

    this.drupalRelativeURL('/admin/people/permissions').waitForElementVisible(
      'table.permissions',
    );

    await Promise.all(
      permissions.map(async (permission) =>
        this.click(`input[name="${machineName}[${permission}]"]`),
      ),
    );

    this.submitForm('#user-admin-permissions').assert.textContains(
      '[data-drupal-messages]',
      'The changes have been saved.',
    );
  }).perform(() => {
    if (typeof callback === 'function') {
      callback.call(self, machineName);
    }
  });

  return this;
};
