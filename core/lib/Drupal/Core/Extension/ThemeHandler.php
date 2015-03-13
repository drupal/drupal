<?php

/**
 * @file
 * Contains \Drupal\Core\Extension\ThemeHandler.
 */

namespace Drupal\Core\Extension;

use Drupal\Component\Utility\String;
use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\PreExistingConfigException;
use Drupal\Core\Routing\RouteBuilderIndicatorInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Default theme handler using the config system to store installation statuses.
 */
class ThemeHandler implements ThemeHandlerInterface {

  /**
   * Contains the features enabled for themes by default.
   *
   * @var array
   */
  protected $defaultFeatures = array(
    'logo',
    'favicon',
    'name',
    'slogan',
    'node_user_picture',
    'comment_user_picture',
    'comment_user_verification',
  );

  /**
   * A list of all currently available themes.
   *
   * @var array
   */
  protected $list;

  /**
   * The config factory to get the installed themes.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler to fire themes_installed/themes_uninstalled hooks.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The state backend.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   *  The config installer to install configuration.
   *
   * @var \Drupal\Core\Config\ConfigInstallerInterface
   */
  protected $configInstaller;

  /**
   * The info parser to parse the theme.info.yml files.
   *
   * @var \Drupal\Core\Extension\InfoParserInterface
   */
  protected $infoParser;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The route builder indicator to rebuild the routes if a theme is installed.
   *
   * @var \Drupal\Core\Routing\RouteBuilderIndicatorInterface
   */
  protected $routeBuilderIndicator;

  /**
   * An extension discovery instance.
   *
   * @var \Drupal\Core\Extension\ExtensionDiscovery
   */
  protected $extensionDiscovery;

  /**
   * The CSS asset collection optimizer service.
   *
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $cssCollectionOptimizer;

  /**
   * The config manager used to uninstall a theme.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * Constructs a new ThemeHandler.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory to get the installed themes.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to fire themes_installed/themes_uninstalled hooks.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state store.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info parser to parse the theme.info.yml files.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $css_collection_optimizer
   *   The CSS asset collection optimizer service.
   * @param \Drupal\Core\Config\ConfigInstallerInterface $config_installer
   *   (optional) The config installer to install configuration. This optional
   *   to allow the theme handler to work before Drupal is installed and has a
   *   database.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The config manager used to uninstall a theme.
   * @param \Drupal\Core\Routing\RouteBuilderIndicatorInterface $route_builder_indicator
   *   (optional) The route builder indicator service to rebuild the routes if a
   *   theme is installed.
   * @param \Drupal\Core\Extension\ExtensionDiscovery $extension_discovery
   *   (optional) A extension discovery instance (for unit tests).
   */
  public function __construct($root, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, StateInterface $state, InfoParserInterface $info_parser,LoggerInterface $logger, AssetCollectionOptimizerInterface $css_collection_optimizer = NULL, ConfigInstallerInterface $config_installer = NULL, ConfigManagerInterface $config_manager = NULL, RouteBuilderIndicatorInterface $route_builder_indicator = NULL, ExtensionDiscovery $extension_discovery = NULL) {
    $this->root = $root;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->state = $state;
    $this->infoParser = $info_parser;
    $this->logger = $logger;
    $this->cssCollectionOptimizer = $css_collection_optimizer;
    $this->configInstaller = $config_installer;
    $this->configManager = $config_manager;
    $this->routeBuilderIndicator = $route_builder_indicator;
    $this->extensionDiscovery = $extension_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefault() {
    return $this->configFactory->get('system.theme')->get('default');
  }

  /**
   * {@inheritdoc}
   */
  public function setDefault($name) {
    $list = $this->listInfo();
    if (!isset($list[$name])) {
      throw new \InvalidArgumentException("$name theme is not installed.");
    }
    $this->configFactory->getEditable('system.theme')
      ->set('default', $name)
      ->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function install(array $theme_list, $install_dependencies = TRUE) {
    $extension_config = $this->configFactory->getEditable('core.extension');

    $theme_data = $this->rebuildThemeData();

    if ($install_dependencies) {
      $theme_list = array_combine($theme_list, $theme_list);

      if ($missing = array_diff_key($theme_list, $theme_data)) {
        // One or more of the given themes doesn't exist.
        throw new \InvalidArgumentException(String::format('Unknown themes: !themes.', array(
          '!themes' => implode(', ', $missing),
        )));
      }

      // Only process themes that are not installed currently.
      $installed_themes = $extension_config->get('theme') ?: array();
      if (!$theme_list = array_diff_key($theme_list, $installed_themes)) {
        // Nothing to do. All themes already installed.
        return TRUE;
      }

      while (list($theme) = each($theme_list)) {
        // Add dependencies to the list. The new themes will be processed as
        // the while loop continues.
        foreach (array_keys($theme_data[$theme]->requires) as $dependency) {
          if (!isset($theme_data[$dependency])) {
            // The dependency does not exist.
            return FALSE;
          }

          // Skip already installed themes.
          if (!isset($theme_list[$dependency]) && !isset($installed_themes[$dependency])) {
            $theme_list[$dependency] = $dependency;
          }
        }
      }

      // Set the actual theme weights.
      $theme_list = array_map(function ($theme) use ($theme_data) {
        return $theme_data[$theme]->sort;
      }, $theme_list);

      // Sort the theme list by their weights (reverse).
      arsort($theme_list);
      $theme_list = array_keys($theme_list);
    }
    else {
      $installed_themes = $extension_config->get('theme') ?: array();
    }

    $themes_installed = array();
    foreach ($theme_list as $key) {
      // Only process themes that are not already installed.
      $installed = $extension_config->get("theme.$key") !== NULL;
      if ($installed) {
        continue;
      }

      // Throw an exception if the theme name is too long.
      if (strlen($key) > DRUPAL_EXTENSION_NAME_MAX_LENGTH) {
        throw new ExtensionNameLengthException(String::format('Theme name %name is over the maximum allowed length of @max characters.', array(
          '%name' => $key,
          '@max' => DRUPAL_EXTENSION_NAME_MAX_LENGTH,
        )));
      }

      // Validate default configuration of the theme. If there is existing
      // configuration then stop installing.
      $existing_configuration = $this->configInstaller->findPreExistingConfiguration('theme', $key);
      if (!empty($existing_configuration)) {
        throw PreExistingConfigException::create($key, $existing_configuration);
      }

      // The value is not used; the weight is ignored for themes currently.
      $extension_config
        ->set("theme.$key", 0)
        ->save();

      // Add the theme to the current list.
      // @todo Remove all code that relies on $status property.
      $theme_data[$key]->status = 1;
      $this->addTheme($theme_data[$key]);

      // Update the current theme data accordingly.
      $current_theme_data = $this->state->get('system.theme.data', array());
      $current_theme_data[$key] = $theme_data[$key];
      $this->state->set('system.theme.data', $current_theme_data);

      // Reset theme settings.
      $theme_settings = &drupal_static('theme_get_setting');
      unset($theme_settings[$key]);

      // @todo Remove system_list().
      $this->systemListReset();

      // Only install default configuration if this theme has not been installed
      // already.
      if (!isset($installed_themes[$key])) {
        // The default config installation storage only knows about the
        // currently installed list of themes, so it has to be reset in order to
        // pick up the default config of the newly installed theme. However, do
        // not reset the source storage when synchronizing configuration, since
        // that would needlessly trigger a reload of the whole configuration to
        // be imported.
        if (!$this->configInstaller->isSyncing()) {
          $this->configInstaller->resetSourceStorage();
        }

        // Install default configuration of the theme.
        $this->configInstaller->installDefaultConfig('theme', $key);
      }

      $themes_installed[] = $key;

      // Record the fact that it was installed.
      $this->logger->info('%theme theme installed.', array('%theme' => $key));
    }

    $this->cssCollectionOptimizer->deleteAll();
    $this->resetSystem();

    // Invoke hook_themes_installed() after the themes have been installed.
    $this->moduleHandler->invokeAll('themes_installed', array($themes_installed));

    return !empty($themes_installed);
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(array $theme_list) {
    $extension_config = $this->configFactory->getEditable('core.extension');
    $theme_config = $this->configFactory->getEditable('system.theme');
    $list = $this->listInfo();
    foreach ($theme_list as $key) {
      if (!isset($list[$key])) {
        throw new \InvalidArgumentException("Unknown theme: $key.");
      }
      if ($key === $theme_config->get('default')) {
        throw new \InvalidArgumentException("The current default theme $key cannot be uninstalled.");
      }
      if ($key === $theme_config->get('admin')) {
        throw new \InvalidArgumentException("The current admin theme $key cannot be uninstalled.");
      }
      // Base themes cannot be uninstalled if sub themes are installed, and if
      // they are not uninstalled at the same time.
      // @todo https://www.drupal.org/node/474684 and
      //   https://www.drupal.org/node/1297856 themes should leverage the module
      //   dependency system.
      if (!empty($list[$key]->sub_themes)) {
        foreach ($list[$key]->sub_themes as $sub_key => $sub_label) {
          if (isset($list[$sub_key]) && !in_array($sub_key, $theme_list, TRUE)) {
            throw new \InvalidArgumentException("The base theme $key cannot be uninstalled, because theme $sub_key depends on it.");
          }
        }
      }
    }

    $this->cssCollectionOptimizer->deleteAll();
    $current_theme_data = $this->state->get('system.theme.data', array());
    foreach ($theme_list as $key) {
      // The value is not used; the weight is ignored for themes currently.
      $extension_config->clear("theme.$key");

      // Remove the theme from the current list.
      unset($this->list[$key]);

      // Update the current theme data accordingly.
      unset($current_theme_data[$key]);

      // Reset theme settings.
      $theme_settings = &drupal_static('theme_get_setting');
      unset($theme_settings[$key]);

      // @todo Remove system_list().
      $this->systemListReset();

      // Remove all configuration belonging to the theme.
      $this->configManager->uninstall('theme', $key);

    }
    $extension_config->save();
    $this->state->set('system.theme.data', $current_theme_data);

    $this->resetSystem();

    $this->moduleHandler->invokeAll('themes_uninstalled', [$theme_list]);
  }

  /**
   * {@inheritdoc}
   */
  public function listInfo() {
    if (!isset($this->list)) {
      $this->list = array();
      $themes = $this->systemThemeList();
      // @todo Ensure that systemThemeList() does not contain an empty list
      //   during the batch installer, see https://www.drupal.org/node/2322619.
      if (empty($themes)) {
        $this->refreshInfo();
        $this->list = $this->list ?: array();
        $themes = \Drupal::state()->get('system.theme.data', array());
      }
      foreach ($themes as $theme) {
        $this->addTheme($theme);
      }
    }
    return $this->list;
  }

  /**
   * {@inheritdoc}
   */
  public function addTheme(Extension $theme) {
    foreach ($theme->info['libraries'] as $library => $name) {
      $theme->libraries[$library] = $name;
    }
    if (isset($theme->info['engine'])) {
      $theme->engine = $theme->info['engine'];
    }
    if (isset($theme->info['base theme'])) {
      $theme->base_theme = $theme->info['base theme'];
    }
    $this->list[$theme->getName()] = $theme;
  }

  /**
   * {@inheritdoc}
   */
  public function refreshInfo() {
    $this->reset();
    $extension_config = $this->configFactory->get('core.extension');
    $installed = $extension_config->get('theme');

    // @todo Avoid re-scanning all themes by retaining the original (unaltered)
    //   theme info somewhere.
    $list = $this->rebuildThemeData();
    foreach ($list as $name => $theme) {
      if (isset($installed[$name])) {
        $this->addTheme($theme);
      }
    }
    $this->state->set('system.theme.data', $this->list);
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->systemListReset();
    $this->list = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildThemeData() {
    $listing = $this->getExtensionDiscovery();
    $themes = $listing->scan('theme');
    $engines = $listing->scan('theme_engine');
    $extension_config = $this->configFactory->get('core.extension');
    $installed = $extension_config->get('theme') ?: array();

    // Set defaults for theme info.
    $defaults = array(
      'engine' => 'twig',
      'regions' => array(
        'sidebar_first' => 'Left sidebar',
        'sidebar_second' => 'Right sidebar',
        'content' => 'Content',
        'header' => 'Header',
        'primary_menu' => 'Primary menu',
        'secondary_menu' => 'Secondary menu',
        'footer' => 'Footer',
        'highlighted' => 'Highlighted',
        'messages' => 'Messages',
        'help' => 'Help',
        'page_top' => 'Page top',
        'page_bottom' => 'Page bottom',
        'breadcrumb' => 'Breadcrumb',
      ),
      'description' => '',
      'features' => $this->defaultFeatures,
      'screenshot' => 'screenshot.png',
      'php' => DRUPAL_MINIMUM_PHP,
      'libraries' => array(),
    );

    $sub_themes = array();
    $files = array();
    // Read info files for each theme.
    foreach ($themes as $key => $theme) {
      // @todo Remove all code that relies on the $status property.
      $theme->status = (int) isset($installed[$key]);

      $theme->info = $this->infoParser->parse($theme->getPathname()) + $defaults;

      // Add the info file modification time, so it becomes available for
      // contributed modules to use for ordering theme lists.
      $theme->info['mtime'] = $theme->getMTime();

      // Invoke hook_system_info_alter() to give installed modules a chance to
      // modify the data in the .info.yml files if necessary.
      // @todo Remove $type argument, obsolete with $theme->getType().
      $type = 'theme';
      $this->moduleHandler->alter('system_info', $theme->info, $theme, $type);

      if (!empty($theme->info['base theme'])) {
        $sub_themes[] = $key;
        // Add the base theme as a proper dependency.
        $themes[$key]->info['dependencies'][] = $themes[$key]->info['base theme'];
      }

      // Defaults to 'twig' (see $defaults above).
      $engine = $theme->info['engine'];
      if (isset($engines[$engine])) {
        $theme->owner = $engines[$engine]->getExtensionPathname();
        $theme->prefix = $engines[$engine]->getName();
      }

      // Prefix screenshot with theme path.
      if (!empty($theme->info['screenshot'])) {
        $theme->info['screenshot'] = $theme->getPath() . '/' . $theme->info['screenshot'];
      }

      $files[$key] = $theme->getPathname();
    }
    // Build dependencies.
    // @todo Move into a generic ExtensionHandler base class.
    // @see https://drupal.org/node/2208429
    $themes = $this->moduleHandler->buildModuleDependencies($themes);

    // Store filenames to allow system_list() and drupal_get_filename() to
    // retrieve them without having to scan the filesystem.
    $this->state->set('system.theme.files', $files);

    // After establishing the full list of available themes, fill in data for
    // sub-themes.
    foreach ($sub_themes as $key) {
      $sub_theme = $themes[$key];
      // The $base_themes property is optional; only set for sub themes.
      // @see ThemeHandlerInterface::listInfo()
      $sub_theme->base_themes = $this->getBaseThemes($themes, $key);
      // empty() cannot be used here, since ThemeHandler::doGetBaseThemes() adds
      // the key of a base theme with a value of NULL in case it is not found,
      // in order to prevent needless iterations.
      if (!current($sub_theme->base_themes)) {
        continue;
      }
      // Determine the root base theme.
      $root_key = key($sub_theme->base_themes);
      // Build the list of sub-themes for each of the theme's base themes.
      foreach (array_keys($sub_theme->base_themes) as $base_theme) {
        $themes[$base_theme]->sub_themes[$key] = $sub_theme->info['name'];
      }
      // Add the theme engine info from the root base theme.
      if (isset($themes[$root_key]->owner)) {
        $sub_theme->info['engine'] = $themes[$root_key]->info['engine'];
        $sub_theme->owner = $themes[$root_key]->owner;
        $sub_theme->prefix = $themes[$root_key]->prefix;
      }
    }

    return $themes;
  }

  /**
   * {@inheritdoc}
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
  protected function doGetBaseThemes(array $themes, $theme, $used_themes = array()) {
    if (!isset($themes[$theme]->info['base theme'])) {
      return array();
    }

    $base_key = $themes[$theme]->info['base theme'];
    // Does the base theme exist?
    if (!isset($themes[$base_key])) {
      return array($base_key => NULL);
    }

    $current_base_theme = array($base_key => $themes[$base_key]->info['name']);

    // Is the base theme itself a child of another theme?
    if (isset($themes[$base_key]->info['base theme'])) {
      // Do we already know the base themes of this theme?
      if (isset($themes[$base_key]->base_themes)) {
        return $themes[$base_key]->base_themes + $current_base_theme;
      }
      // Prevent loops.
      if (!empty($used_themes[$base_key])) {
        return array($base_key => NULL);
      }
      $used_themes[$base_key] = TRUE;
      return $this->doGetBaseThemes($themes, $base_key, $used_themes) + $current_base_theme;
    }
    // If we get here, then this is our parent theme.
    return $current_base_theme;
  }

  /**
   * Returns an extension discovery object.
   *
   * @return \Drupal\Core\Extension\ExtensionDiscovery
   *   The extension discovery object.
   */
  protected function getExtensionDiscovery() {
    if (!isset($this->extensionDiscovery)) {
      $this->extensionDiscovery = new ExtensionDiscovery($this->root);
    }
    return $this->extensionDiscovery;
  }

  /**
   * Resets some other systems like rebuilding the route information or caches.
   */
  protected function resetSystem() {
    if ($this->routeBuilderIndicator) {
      $this->routeBuilderIndicator->setRebuildNeeded();
    }
    $this->systemListReset();

    // @todo It feels wrong to have the requirement to clear the local tasks
    //   cache here.
    Cache::invalidateTags(array('local_task'));
    $this->themeRegistryRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function getName($theme) {
    $themes = $this->listInfo();
    if (!isset($themes[$theme])) {
      throw new \InvalidArgumentException(String::format('Requested the name of a non-existing theme @theme', array('@theme' => $theme)));
    }
    return String::checkPlain($themes[$theme]->info['name']);
  }

  /**
   * Wraps system_list_reset().
   */
  protected function systemListReset() {
    system_list_reset();
  }

  /**
   * Wraps drupal_theme_rebuild().
   */
  protected function themeRegistryRebuild() {
    drupal_theme_rebuild();
  }

  /**
   * Wraps system_list().
   *
   * @return array
   *   A list of themes keyed by name.
   */
  protected function systemThemeList() {
    return system_list('theme');
  }

  /**
   * {@inheritdoc}
   */
  public function getThemeDirectories() {
    $dirs = array();
    foreach ($this->listInfo() as $name => $theme) {
      $dirs[$name] = $this->root . '/' . $theme->getPath();
    }
    return $dirs;
  }

  /**
   * {@inheritdoc}
   */
  public function themeExists($theme) {
    $themes = $this->listInfo();
    return isset($themes[$theme]);
  }

  /**
   * {@inheritdoc}
   */
  public function getTheme($name) {
    $themes = $this->listInfo();
    if (isset($themes[$name])) {
      return $themes[$name];
    }
    throw new \InvalidArgumentException(sprintf('The theme %s does not exist.', $name));
  }

}
