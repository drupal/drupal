/**
 * Enable a given theme.
 *
 * @param themeMachineName
 *   The theme machine name to enable
 * @param adminTheme
 *   If true, install the theme as the admin theme instead of default.
 * @return {object}
 *   The drupalEnableTheme command.
 */
exports.command = function drupalEnableTheme(
  themeMachineName,
  adminTheme = false,
) {
  this.drupalLoginAsAdmin(() => {
    const path = adminTheme
      ? '/admin/theme/install_admin/'
      : '/admin/theme/install_default/';
    this.drupalRelativeURL(`${path}${themeMachineName}`).waitForElementPresent(
      '#theme-installed',
      10000,
    );
  });
  return this;
};
