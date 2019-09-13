<?php

namespace Drupal\Core\Extension;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;

/**
 * Provides a list of available themes.
 *
 * @internal
 *   This class is not yet stable and therefore there are no guarantees that the
 *   internal implementations including constructor signature and protected
 *   properties / methods will not change over time. This will be reviewed after
 *   https://www.drupal.org/project/drupal/issues/2940481
 */
class ThemeExtensionList extends ExtensionList {

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    'engine' => 'twig',
    'regions' => [
      'sidebar_first' => 'Left sidebar',
      'sidebar_second' => 'Right sidebar',
      'content' => 'Content',
      'header' => 'Header',
      'primary_menu' => 'Primary menu',
      'secondary_menu' => 'Secondary menu',
      'footer' => 'Footer',
      'highlighted' => 'Highlighted',
      'help' => 'Help',
      'page_top' => 'Page top',
      'page_bottom' => 'Page bottom',
      'breadcrumb' => 'Breadcrumb',
    ],
    'description' => '',
    // The following array should be kept inline with
    // _system_default_theme_features().
    'features' => [
      'favicon',
      'logo',
      'node_user_picture',
      'comment_user_picture',
      'comment_user_verification',
    ],
    'screenshot' => 'screenshot.png',
    'php' => DRUPAL_MINIMUM_PHP,
    'libraries' => [],
    'libraries_extend' => [],
    'libraries_override' => [],
  ];

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The theme engine list needed by this theme list.
   *
   * @var \Drupal\Core\Extension\ThemeEngineExtensionList
   */
  protected $engineList;

  /**
   * The list of installed themes.
   *
   * @var string[]
   */
  protected $installedThemes;

  /**
   * Constructs a new ThemeExtensionList instance.
   *
   * @param string $root
   *   The app root.
   * @param string $type
   *   The extension type.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info parser.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ThemeEngineExtensionList $engine_list
   *   The theme engine extension listing.
   * @param string $install_profile
   *   The install profile used by the site.
   */
  public function __construct($root, $type, CacheBackendInterface $cache, InfoParserInterface $info_parser, ModuleHandlerInterface $module_handler, StateInterface $state, ConfigFactoryInterface $config_factory, ThemeEngineExtensionList $engine_list, $install_profile) {
    parent::__construct($root, $type, $cache, $info_parser, $module_handler, $state, $install_profile);

    $this->configFactory = $config_factory;
    $this->engineList = $engine_list;
  }

  /**
   * {@inheritdoc}
   */
  protected function doList() {
    // Find themes.
    $themes = parent::doList();

    $engines = $this->engineList->getList();
    // Always get the freshest list of themes (rather than the already cached
    // list in $this->installedThemes) when building the theme listing because a
    // theme could have just been installed or uninstalled.
    $this->installedThemes = $this->configFactory->get('core.extension')->get('theme') ?: [];

    $sub_themes = [];
    // Read info files for each theme.
    foreach ($themes as $name => $theme) {
      // Defaults to 'twig' (see self::defaults above).
      $engine = $theme->info['engine'];
      if (isset($engines[$engine])) {
        $theme->owner = $engines[$engine]->getExtensionPathname();
        $theme->prefix = $engines[$engine]->getName();
      }
      // Add this theme as a sub-theme if it has a base theme.
      if (!empty($theme->info['base theme'])) {
        $sub_themes[] = $name;
      }
      // Add status.
      $theme->status = (int) isset($this->installedThemes[$name]);
    }

    // Build dependencies.
    $themes = $this->moduleHandler->buildModuleDependencies($themes);

    // After establishing the full list of available themes, fill in data for
    // sub-themes.
    $this->fillInSubThemeData($themes, $sub_themes);

    return $themes;
  }

  /**
   * Fills in data for themes that are also sub-themes.
   *
   * @param array $themes
   *   The array of partly processed theme information.
   * @param array $sub_themes
   *   A list of themes from the $theme array that are also sub-themes.
   */
  protected function fillInSubThemeData(array &$themes, array $sub_themes) {
    foreach ($sub_themes as $name) {
      $sub_theme = $themes[$name];
      // The $base_themes property is optional; only set for sub themes.
      // @see ThemeHandlerInterface::listInfo()
      $sub_theme->base_themes = $this->doGetBaseThemes($themes, $name);
      // empty() cannot be used here, since static::doGetBaseThemes() adds
      // the key of a base theme with a value of NULL in case it is not found,
      // in order to prevent needless iterations.
      if (!current($sub_theme->base_themes)) {
        continue;
      }
      // Determine the root base theme.
      $root_key = key($sub_theme->base_themes);
      // Build the list of sub-themes for each of the theme's base themes.
      foreach (array_keys($sub_theme->base_themes) as $base_theme) {
        $themes[$base_theme]->sub_themes[$name] = $sub_theme->info['name'];
      }
      // Add the theme engine info from the root base theme.
      if (isset($themes[$root_key]->owner)) {
        $sub_theme->info['engine'] = $themes[$root_key]->info['engine'];
        $sub_theme->owner = $themes[$root_key]->owner;
        $sub_theme->prefix = $themes[$root_key]->prefix;
      }
    }
  }

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
  public function getBaseThemes(array $themes, $theme) {
    return $this->doGetBaseThemes($themes, $theme);
  }

  /**
   * Finds the base themes for the specific theme.
   *
   * @param array $themes
   *   An array of available themes.
   * @param string $theme
   *   The name of the theme whose base we are looking for.
   * @param array $used_themes
   *   (optional) A recursion parameter preventing endless loops. Defaults to
   *   an empty array.
   *
   * @return array
   *   An array of base themes.
   */
  protected function doGetBaseThemes(array $themes, $theme, array $used_themes = []) {
    if (!isset($themes[$theme]->info['base theme'])) {
      return [];
    }

    $base_key = $themes[$theme]->info['base theme'];
    // Does the base theme exist?
    if (!isset($themes[$base_key])) {
      return [$base_key => NULL];
    }

    $current_base_theme = [$base_key => $themes[$base_key]->info['name']];

    // Is the base theme itself a child of another theme?
    if (isset($themes[$base_key]->info['base theme'])) {
      // Do we already know the base themes of this theme?
      if (isset($themes[$base_key]->base_themes)) {
        return $themes[$base_key]->base_themes + $current_base_theme;
      }
      // Prevent loops.
      if (!empty($used_themes[$base_key])) {
        return [$base_key => NULL];
      }
      $used_themes[$base_key] = TRUE;
      return $this->doGetBaseThemes($themes, $base_key, $used_themes) + $current_base_theme;
    }
    // If we get here, then this is our parent theme.
    return $current_base_theme;
  }

  /**
   * {@inheritdoc}
   */
  protected function createExtensionInfo(Extension $extension) {
    $info = parent::createExtensionInfo($extension);

    // In the past, Drupal used to default to the `stable` theme as the base
    // theme. Explicitly opting out by specifying `base theme: false` was (and
    // still is) possible. However, defaulting to `base theme: stable` prevents
    // automatic updates to the next major version of Drupal, since each major
    // version may have a different version of "the stable theme", for example:
    // - for Drupal 8: `stable`
    // - for Drupal 9: `stable9`
    // - for Drupal 10: `stable10`
    // - et cetera
    // It is impossible to reliably determine which should be used by default,
    // hence we now require the base theme to be explicitly specified.
    if (!isset($info['base theme'])) {
      @trigger_error(sprintf('There is no `base theme` property specified in the %s.info.yml file. The optionality of the `base theme` property is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. All Drupal 8 themes must add `base theme: stable` to their *.info.yml file for them to continue to work as-is in future versions of Drupal. Drupal 9 requires the `base theme` property to be specified. See https://www.drupal.org/node/3066038', $extension->getName()), E_USER_DEPRECATED);
      $info['base theme'] = 'stable';
    }

    // Remove the default Stable base theme when 'base theme: false' is set in
    // a theme .info.yml file.
    if ($info['base theme'] === FALSE) {
      unset($info['base theme']);
    }

    if (!empty($info['base theme'])) {
      // Add the base theme as a proper dependency.
      $info['dependencies'][] = $info['base theme'];
    }

    // Prefix screenshot with theme path.
    if (!empty($info['screenshot'])) {
      $info['screenshot'] = $extension->getPath() . '/' . $info['screenshot'];
    }
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  protected function getInstalledExtensionNames() {
    // Cache the installed themes to avoid multiple calls to the config system.
    if (!isset($this->installedThemes)) {
      $this->installedThemes = $this->configFactory->get('core.extension')->get('theme') ?: [];
    }
    return array_keys($this->installedThemes);
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    parent::reset();
    $this->installedThemes = NULL;
    return $this;
  }

}
