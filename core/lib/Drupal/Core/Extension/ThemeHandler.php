<?php

namespace Drupal\Core\Extension;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\Exception\UninstalledExtensionException;
use Drupal\Core\Extension\Exception\UnknownExtensionException;

/**
 * Default theme handler using the config system to store installation statuses.
 */
class ThemeHandler implements ThemeHandlerInterface {

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
   * An extension discovery instance.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeList;

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
   * @param \Drupal\Core\Extension\ThemeExtensionList $theme_list
   *   A extension discovery instance.
   */
  public function __construct($root, ConfigFactoryInterface $config_factory, ThemeExtensionList $theme_list) {
    $this->root = $root;
    $this->configFactory = $config_factory;
    $this->themeList = $theme_list;
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
      throw new UninstalledExtensionException("$name theme is not installed.");
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
      $this->list = [];
      $installed_themes = $this->configFactory->get('core.extension')->get('theme');
      if (!empty($installed_themes)) {
        $installed_themes = array_intersect_key($this->themeList->getList(), $installed_themes);
        array_map([$this, 'addTheme'], $installed_themes);
      }
    }
    return $this->list;
  }

  /**
   * {@inheritdoc}
   */
  public function addTheme(Extension $theme) {
    // Register the namespaces of installed themes.
    // @todo Implement proper theme registration
    // https://www.drupal.org/project/drupal/issues/2941757
    \Drupal::service('class_loader')->addPsr4('Drupal\\' . $theme->getName() . '\\', $this->root . '/' . $theme->getPath() . '/src');

    if (!empty($theme->info['libraries'])) {
      foreach ($theme->info['libraries'] as $library => $name) {
        $theme->libraries[$library] = $name;
      }
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
    $installed = $this->configFactory->get('core.extension')->get('theme');
    // Only refresh the info if a theme has been installed. Modules are
    // installed before themes by the installer and this method is called during
    // module installation.
    if (empty($installed) && empty($this->list)) {
      return;
    }
    $this->reset();
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->themeList->reset();
    $this->list = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildThemeData() {
    return $this->themeList->reset()->getList();
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseThemes(array $themes, $theme) {
    return $this->themeList->getBaseThemes($themes, $theme);
  }

  /**
   * {@inheritdoc}
   */
  public function getName($theme) {
    return $this->themeList->getName($theme);
  }

  /**
   * {@inheritdoc}
   */
  public function getThemeDirectories() {
    $dirs = [];
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
    throw new UnknownExtensionException(sprintf('The theme %s does not exist.', $name));
  }

  /**
   * {@inheritdoc}
   */
  public function hasUi($name) {
    $themes = $this->listInfo();
    if (isset($themes[$name])) {
      if (!empty($themes[$name]->info['hidden'])) {
        $theme_config = $this->configFactory->get('system.theme');
        return $name == $theme_config->get('default') || $name == $theme_config->get('admin');
      }
      return TRUE;
    }
    return FALSE;
  }

}
