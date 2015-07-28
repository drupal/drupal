<?php

/**
 * @file
 * Contains \Drupal\Core\Extension\ThemeHandler.
 */

namespace Drupal\Core\Extension;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;

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
   * The route builder to rebuild the routes if a theme is installed.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

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
   * @param \Drupal\Core\Extension\ExtensionDiscovery $extension_discovery
   *   (optional) A extension discovery instance (for unit tests).
   */
  public function __construct($root, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, StateInterface $state, InfoParserInterface $info_parser, ExtensionDiscovery $extension_discovery = NULL) {
    $this->root = $root;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->state = $state;
    $this->infoParser = $info_parser;
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
    // We keep the old install() method as BC layer but redirect directly to the
    // theme installer.
    return \Drupal::service('theme_installer')->install($theme_list, $install_dependencies);
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(array $theme_list) {
    // We keep the old uninstall() method as BC layer but redirect directly to
    // the theme installer.
    \Drupal::service('theme_installer')->uninstall($theme_list);
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
    $files_theme = array();
    $files_theme_engine = array();
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
        $files_theme_engine[$engine] = $engines[$engine]->getPathname();
      }

      // Prefix screenshot with theme path.
      if (!empty($theme->info['screenshot'])) {
        $theme->info['screenshot'] = $theme->getPath() . '/' . $theme->info['screenshot'];
      }

      $files_theme[$key] = $theme->getPathname();
    }
    // Build dependencies.
    // @todo Move into a generic ExtensionHandler base class.
    // @see https://www.drupal.org/node/2208429
    $themes = $this->moduleHandler->buildModuleDependencies($themes);

    // Store filenames to allow system_list() and drupal_get_filename() to
    // retrieve them for themes and theme engines without having to scan the
    // filesystem.
    $this->state->set('system.theme.files', $files_theme);
    $this->state->set('system.theme_engine.files', $files_theme_engine);

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
   * {@inheritdoc}
   */
  public function getName($theme) {
    $themes = $this->listInfo();
    if (!isset($themes[$theme])) {
      throw new \InvalidArgumentException("Requested the name of a non-existing theme $theme");
    }
    return SafeMarkup::checkPlain($themes[$theme]->info['name']);
  }

  /**
   * Wraps system_list_reset().
   */
  protected function systemListReset() {
    system_list_reset();
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
