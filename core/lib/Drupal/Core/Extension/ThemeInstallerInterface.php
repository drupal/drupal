<?php

namespace Drupal\Core\Extension;

/**
 * Manages theme installation/uninstallation.
 */
interface ThemeInstallerInterface {

  /**
   * Installs a given list of themes.
   *
   * @param array $theme_list
   *   An array of theme names.
   * @param bool $install_dependencies
   *   (optional) If TRUE, dependencies will automatically be installed in the
   *   correct order. This incurs a significant performance cost, so use FALSE
   *   if you know $theme_list is already complete and in the correct order.
   *
   * @return bool
   *   Whether any of the given themes have been installed.
   *
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException
   *   Thrown when the theme name is to long
   */
  public function install(array $theme_list, $install_dependencies = TRUE);

  /**
   * Uninstalls a given list of themes.
   *
   * Uninstalling a theme removes all related configuration (like blocks) and
   * invokes the 'themes_uninstalled' hook.
   *
   * @param array $theme_list
   *   The themes to uninstall.
   *
   * @throws \InvalidArgumentException
   *   Thrown when you uninstall an not installed theme.
   *
   * @see hook_themes_uninstalled()
   */
  public function uninstall(array $theme_list);

}
