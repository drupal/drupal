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
      .updateValue('[data-drupal-selector="edit-text"]', module)
      .waitForElementVisible(`[name="modules[${module}][enable]"]`, 10000)
      .click(`[name="modules[${module}][enable]"]`)
      .click('input[data-drupal-selector="edit-submit"]')
      // Wait for the install message to show up.
      .waitForElementVisible('.system-modules', 10000);
  }).perform(() => {
    if (typeof callback === 'function') {
      callback.call(self);
    }
  });

  return this;
};
