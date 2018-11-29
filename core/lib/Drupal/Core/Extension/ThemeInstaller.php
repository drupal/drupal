<?php

namespace Drupal\Core\Extension;

use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Manages theme installation/uninstallation.
 */
class ThemeInstaller implements ThemeInstallerInterface {

  /**
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Config\ConfigInstallerInterface
   */
  protected $configInstaller;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $cssCollectionOptimizer;

  /**
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new ThemeInstaller.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory to get the installed themes.
   * @param \Drupal\Core\Config\ConfigInstallerInterface $config_installer
   *   (optional) The config installer to install configuration. This optional
   *   to allow the theme handler to work before Drupal is installed and has a
   *   database.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to fire themes_installed/themes_uninstalled hooks.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The config manager used to uninstall a theme.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $css_collection_optimizer
   *   The CSS asset collection optimizer service.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   (optional) The route builder service to rebuild the routes if a theme is
   *   installed.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state store.
   */
  public function __construct(ThemeHandlerInterface $theme_handler, ConfigFactoryInterface $config_factory, ConfigInstallerInterface $config_installer, ModuleHandlerInterface $module_handler, ConfigManagerInterface $config_manager, AssetCollectionOptimizerInterface $css_collection_optimizer, RouteBuilderInterface $route_builder, LoggerInterface $logger, StateInterface $state) {
    $this->themeHandler = $theme_handler;
    $this->configFactory = $config_factory;
    $this->configInstaller = $config_installer;
    $this->moduleHandler = $module_handler;
    $this->configManager = $config_manager;
    $this->cssCollectionOptimizer = $css_collection_optimizer;
    $this->routeBuilder = $route_builder;
    $this->logger = $logger;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function install(array $theme_list, $install_dependencies = TRUE) {
    $extension_config = $this->configFactory->getEditable('core.extension');

    $theme_data = $this->themeHandler->rebuildThemeData();

    if ($install_dependencies) {
      $theme_list = array_combine($theme_list, $theme_list);

      if ($missing = array_diff_key($theme_list, $theme_data)) {
        // One or more of the given themes doesn't exist.
        throw new UnknownExtensionException('Unknown themes: ' . implode(', ', $missing) . '.');
      }

      // Only process themes that are not installed currently.
      $installed_themes = $extension_config->get('theme') ?: [];
      if (!$theme_list = array_diff_key($theme_list, $installed_themes)) {
        // Nothing to do. All themes already installed.
        return TRUE;
      }

      foreach ($theme_list as $theme => $value) {
        // Add dependencies to the list. The new themes will be processed as
        // the parent foreach loop continues.
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
      $installed_themes = $extension_config->get('theme') ?: [];
    }

    $themes_installed = [];
    foreach ($theme_list as $key) {
      // Only process themes that are not already installed.
      $installed = $extension_config->get("theme.$key") !== NULL;
      if ($installed) {
        continue;
      }

      // Throw an exception if the theme name is too long.
      if (strlen($key) > DRUPAL_EXTENSION_NAME_MAX_LENGTH) {
        throw new ExtensionNameLengthException("Theme name $key is over the maximum allowed length of " . DRUPAL_EXTENSION_NAME_MAX_LENGTH . ' characters.');
      }

      // Validate default configuration of the theme. If there is existing
      // configuration then stop installing.
      $this->configInstaller->checkConfigurationToInstall('theme', $key);

      // The value is not used; the weight is ignored for themes currently. Do
      // not check schema when saving the configuration.
      $extension_config
        ->set("theme.$key", 0)
        ->save(TRUE);

      // Reset theme settings.
      $theme_settings = &drupal_static('theme_get_setting');
      unset($theme_settings[$key]);

      // Reset theme listing.
      $this->themeHandler->reset();

      // Only install default configuration if this theme has not been installed
      // already.
      if (!isset($installed_themes[$key])) {
        // Install default configuration of the theme.
        $this->configInstaller->installDefaultConfig('theme', $key);
      }

      $themes_installed[] = $key;

      // Record the fact that it was installed.
      $this->logger->info('%theme theme installed.', ['%theme' => $key]);
    }

    $this->cssCollectionOptimizer->deleteAll();
    $this->resetSystem();

    // Invoke hook_themes_installed() after the themes have been installed.
    $this->moduleHandler->invokeAll('themes_installed', [$themes_installed]);

    return !empty($themes_installed);
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(array $theme_list) {
    $extension_config = $this->configFactory->getEditable('core.extension');
    $theme_config = $this->configFactory->getEditable('system.theme');
    $list = $this->themeHandler->listInfo();
    foreach ($theme_list as $key) {
      if (!isset($list[$key])) {
        throw new UnknownExtensionException("Unknown theme: $key.");
      }
      if ($key === $theme_config->get('default')) {
        throw new \InvalidArgumentException("The current default theme $key cannot be uninstalled.");
      }
      if ($key === $theme_config->get('admin')) {
        throw new \InvalidArgumentException("The current administration theme $key cannot be uninstalled.");
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
    foreach ($theme_list as $key) {
      // The value is not used; the weight is ignored for themes currently.
      $extension_config->clear("theme.$key");

      // Reset theme settings.
      $theme_settings = &drupal_static('theme_get_setting');
      unset($theme_settings[$key]);

      // Remove all configuration belonging to the theme.
      $this->configManager->uninstall('theme', $key);

    }
    // Don't check schema when uninstalling a theme since we are only clearing
    // keys.
    $extension_config->save(TRUE);

    // Refresh theme info.
    $this->resetSystem();
    $this->themeHandler->reset();

    $this->moduleHandler->invokeAll('themes_uninstalled', [$theme_list]);
  }

  /**
   * Resets some other systems like rebuilding the route information or caches.
   */
  protected function resetSystem() {
    if ($this->routeBuilder) {
      $this->routeBuilder->setRebuildNeeded();
    }

    // @todo It feels wrong to have the requirement to clear the local tasks
    //   cache here.
    Cache::invalidateTags(['local_task']);
    $this->themeRegistryRebuild();
  }

  /**
   * Wraps drupal_theme_rebuild().
   */
  protected function themeRegistryRebuild() {
    drupal_theme_rebuild();
  }

}
