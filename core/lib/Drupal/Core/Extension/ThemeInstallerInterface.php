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
   * Determines the full set of themes corresponding to the given list.
   *
   * @param string[] $theme_list
   *   An array of theme machine names.
   * @param bool $install_dependencies
   *   (optional) If TRUE, dependencies are resolved and added to the returned
   *   list in the correct install order. If FALSE, the given list is returned
   *   unchanged.
   *
   * @return string[]
   *   The machine names of the themes to install, including any resolved theme
   *   dependencies, ordered the way ::install() would install them.
   *   Already installed themes are omitted.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   Thrown when a requested theme, or a theme dependency of one, does not
   *   exist.
   *
   * @throws \Drupal\Core\Extension\MissingDependencyException
   *   Thrown when a theme has an unmet or incompatible module dependency.
   */
  public function getThemesToInstall(array $theme_list, bool $install_dependencies = TRUE): array;

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

  /**
   * Determines the full set of themes to uninstall for the given list.
   *
   * @param string[] $theme_list
   *   An array of theme machine names.
   * @param bool $uninstall_dependents
   *   (optional) If TRUE, installed sub-themes of the given themes are added to
   *   the returned list. If FALSE, an exception is thrown when a given base
   *   theme has an installed sub-theme that is not also in the list.
   *
   * @return string[]
   *   The machine names of the themes to uninstall, including any added
   *   sub-themes.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   Thrown when a requested theme is not installed.
   *
   * @throws \InvalidArgumentException
   *   Thrown when trying to uninstall the admin theme, the default theme or,
   *   when $uninstall_dependents is FALSE, a theme that another theme depends
   *   on.
   */
  public function getThemesToUninstall(array $theme_list, bool $uninstall_dependents = TRUE): array;

}
