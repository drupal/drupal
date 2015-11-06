<?php

/**
 * @file
 * Contains \Drupal\Core\Extension\ThemeHandlerInterface.
 */

namespace Drupal\Core\Extension;

/**
 * Manages the list of available themes.
 */
interface ThemeHandlerInterface {

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
   *
   * @deprecated in Drupal 8.0.x-dev and will be removed before Drupal 9.0.0.
   *   Use the theme_installer service instead.
   *
   * @see \Drupal\Core\Extension\ThemeInstallerInterface::install
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
   *
   * @deprecated in Drupal 8.0.x-dev and will be removed before Drupal 9.0.0.
   *   Use the theme_installer service instead.
   *
   * @see \Drupal\Core\Extension\ThemeInstallerInterface::uninstall
   */
  public function uninstall(array $theme_list);

  /**
   * Returns a list of currently installed themes.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   An associative array of the currently installed themes. The keys are the
   *   themes' machine names and the values are objects having the following
   *   properties:
   *   - filename: The filepath and name of the .info.yml file.
   *   - name: The machine name of the theme.
   *   - status: 1 for installed, 0 for uninstalled themes.
   *   - info: The contents of the .info.yml file.
   *   - stylesheets: A two dimensional array, using the first key for the
   *     media attribute (e.g. 'all'), the second for the name of the file
   *     (e.g. style.css). The value is a complete filepath (e.g.
   *     themes/bartik/style.css). Not set if no stylesheets are defined in the
   *     .info.yml file.
   *   - scripts: An associative array of JavaScripts, using the filename as key
   *     and the complete filepath as value. Not set if no scripts are defined
   *     in the .info.yml file.
   *   - prefix: The base theme engine prefix.
   *   - engine: The machine name of the theme engine.
   *   - base_theme: If this is a sub-theme, the machine name of the base theme
   *     defined in the .info.yml file. Otherwise, the element is not set.
   *   - base_themes: If this is a sub-theme, an associative array of the
   *     base-theme ancestors of this theme, starting with this theme's base
   *     theme, then the base theme's own base theme, etc. Each entry has an
   *     array key equal to the theme's machine name, and a value equal to the
   *     human-readable theme name; if a theme with matching machine name does
   *     not exist in the system, the value will instead be NULL (and since the
   *     system would not know whether that theme itself has a base theme, that
   *     will end the array of base themes). This is not set if the theme is not
   *     a sub-theme.
   *   - sub_themes: An associative array of themes on the system that are
   *     either direct sub-themes (that is, they declare this theme to be
   *     their base theme), direct sub-themes of sub-themes, etc. The keys are
   *     the themes' machine names, and the values are the themes'
   *     human-readable names. This element is not set if there are no themes on
   *     the system that declare this theme as their base theme.
   */
  public function listInfo();


  /**
   * Adds a theme extension to the internal listing.
   *
   * @param \Drupal\Core\Extension\Extension $theme
   *   The theme extension.
   */
  public function addTheme(Extension $theme);

  /**
   * Refreshes the theme info data of currently installed themes.
   *
   * Modules can alter theme info, so this is typically called after a module
   * has been installed or uninstalled.
   */
  public function refreshInfo();

  /**
   * Resets the internal state of the theme handler.
   */
  public function reset();

  /**
   * Scans and collects theme extension data and their engines.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   An associative array of theme extensions.
   */
  public function rebuildThemeData();

  /**
   * Finds all the base themes for the specified theme.
   *
   * Themes can inherit templates and function implementations from earlier
   * themes.
   *
   * @param \Drupal\Core\Extension\Extension[] $themes
   *   An array of available themes.
   * @param string $theme
   *   The name of the theme whose base we are looking for.
   *
   * @return array
   *   Returns an array of all of the theme's ancestors; the first element's
   *   value will be NULL if an error occurred.
   */
  public function getBaseThemes(array $themes, $theme);

  /**
   * Gets the human readable name of a given theme.
   *
   * @param string $theme
   *   The machine name of the theme which title should be shown.
   *
   * @return string
   *   Returns the human readable name of the theme.
   */
  public function getName($theme);

  /**
   * Returns the default theme.
   *
   * @return string
   *   The default theme.
   */
  public function getDefault();

  /**
   * Sets a new default theme.
   *
   * @param string $theme
   *   The new default theme.
   *
   * @return $this
   */
  public function setDefault($theme);

  /**
   * Returns an array of directories for all installed themes.
   *
   * Useful for tasks such as finding a file that exists in all theme
   * directories.
   *
   * @return array
   */
  public function getThemeDirectories();

  /**
   * Determines whether a given theme is installed.
   *
   * @param string $theme
   *   The name of the theme (without the .theme extension).
   *
   * @return bool
   *   TRUE if the theme is installed.
   */
  public function themeExists($theme);

  /**
   * Returns a theme extension object from the currently active theme list.
   *
   * @param string $name
   *   The name of the theme to return.
   *
   * @return \Drupal\Core\Extension\Extension
   *   An extension object.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the requested theme does not exist.
   */
  public function getTheme($name);

  /**
   * Determines if a theme should be shown in the user interface.
   *
   * To be shown in the UI the theme has to be installed. If the theme is hidden
   * it will not be shown unless it is the default or admin theme.
   *
   * @param string $name
   *   The name of the theme to check.
   *
   * @return bool
   *   TRUE if the theme should be shown in the UI, FALSE if not.
   */
  public function hasUi($name);

}
