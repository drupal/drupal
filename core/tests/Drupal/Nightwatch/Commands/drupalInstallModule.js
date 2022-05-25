/**
 * Install the given module.
 *
 * @param {string} module
 *   The module machine name to enable.
 * @param {function} callback
 *   A callback which will be called, when the module has been enabled.
 * @return {object}
 *   The drupalInstallModule command.
 */
exports.command = function drupalInstallModule(module, callback) {
  const self = this;
  this.drupalLoginAsAdmin(() => {
    this.drupalRelativeURL('/admin/modules')
      // Filter module list to ensure that collapsable <details> elements are expanded.
      .updateValue(
        'form.system-modules [data-drupal-selector="edit-text"]',
        module,
      )
      .waitForElementVisible(
        `form.system-modules [name="modules[${module}][enable]"]`,
        10000,
      )
      .click(`form.system-modules [name="modules[${module}][enable]"]`)
      .submitForm('form.system-modules')
      // Wait for the checkbox for the module to be disabled as a sign that the
      // module has been enabled.
      .waitForElementPresent(
        `form.system-modules [name="modules[${module}][enable]"]:disabled`,
        10000,
      );
  }).perform(() => {
    if (typeof callback === 'function') {
      callback.call(self);
    }
  });

  return this;
};
