<?php

/**
 * @file
 * Contains \Drupal\Core\Extension\ThemeHandlerInterface.
 */

namespace Drupal\Core\Extension;

/**
 * Manages the list of available themes as well as enable/disable them.
 */
interface ThemeHandlerInterface {

  /**
   * Enables a given list of themes.
   *
   * @param array $theme_list
   *   An array of theme names.
   *
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException
   *   Thrown when the theme name is to long
   */
  public function enable(array $theme_list);

  /**
   * Disables a given list of themes.
   *
   * @param array $theme_list
   *   An array of theme names.
   */
  public function disable(array $theme_list);

  /**
   * Returns a list of all currently available themes.
   *
   * Retrieved from the database, if available and the site is not in
   * maintenance mode; otherwise compiled freshly from the filesystem.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   An associative array of the currently available themes. The keys are the
   *   themes' machine names and the values are objects having the following
   *   properties:
   *   - filename: The filepath and name of the .info.yml file.
   *   - name: The machine name of the theme.
   *   - status: 1 for enabled, 0 for disabled themes.
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

}
