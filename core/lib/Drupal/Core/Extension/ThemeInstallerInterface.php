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
   *   Thrown when a theme's name is longer than
   *   DRUPAL_EXTENSION_NAME_MAX_LENGTH.
   *
   * @throws \Drupal\Core\Extension\ExtensionNameReservedException
   *   Thrown when a theme's name is already used by an installed module.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   Thrown when the theme does not exist.
   *
   * @throws \Drupal\Core\Extension\MissingDependencyException
   *   Thrown when a requested dependency can't be found.
   */
  public function install(array $theme_list, $install_dependencies = TRUE);

  /**
   * Uninstalls a given list of themes.
   *
   * Uninstalling a theme removes all related configuration (like blocks) and
   * invokes the 'themes_uninstalled' hook.
   *
   * Themes are allowed to be uninstalled even when their code has been removed
   * from the filesystem, this is because themes do not allow uninstall hooks to
   * be defined.
   *
   * @param array $theme_list
   *   The themes to uninstall.
   *
   * @throws \InvalidArgumentException
   *   Thrown when trying to uninstall the admin theme, the default theme or
   *   a theme that another theme depends on.
   *
   * @see hook_themes_uninstalled()
   */
  public function uninstall(array $theme_list);

}
