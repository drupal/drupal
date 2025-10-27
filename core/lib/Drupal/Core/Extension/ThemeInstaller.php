<?php

namespace Drupal\Core\Extension;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\Registry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Manages theme installation/uninstallation.
 */
class ThemeInstaller implements ThemeInstallerInterface {

  use ModuleDependencyMessageTrait;
  use StringTranslationTrait;

  public function __construct(
    protected ThemeHandlerInterface $themeHandler,
    protected ConfigFactoryInterface $configFactory,
    protected ConfigInstallerInterface $configInstaller,
    protected ModuleHandlerInterface $moduleHandler,
    protected ConfigManagerInterface $configManager,
    #[Autowire(service: 'asset.css.collection_optimizer')]
    protected AssetCollectionOptimizerInterface $cssCollectionOptimizer,
    protected RouteBuilderInterface $routeBuilder,
    #[Autowire(service: 'logger.channel.default')]
    protected LoggerInterface $logger,
    protected StateInterface $state,
    protected ModuleExtensionList $moduleExtensionList,
    protected Registry $themeRegistry,
    protected ThemeExtensionList $themeExtensionList,
    #[Autowire(service: 'kernel')]
    protected DrupalKernelInterface|CachedDiscoveryInterface|null $kernel = NULL,
  ) {
    if (!$this->kernel instanceof DrupalKernelInterface) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $kernel argument is deprecated in drupal:11.3.0 and it will be required in drupal:12.0.0. See https://www.drupal.org/node/3551652', E_USER_DEPRECATED);
      $this->kernel = \Drupal::service('kernel');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function install(array $theme_list, $install_dependencies = TRUE) {
    $extension_config = $this->configFactory->getEditable('core.extension');

    $theme_data = $this->themeExtensionList->reset()->getList();
    $installed_themes = $extension_config->get('theme') ?: [];
    $installed_modules = $extension_config->get('module') ?: [];

    if ($install_dependencies) {
      $theme_list = array_combine($theme_list, $theme_list);

      if ($missing = array_diff_key($theme_list, $theme_data)) {
        // One or more of the given themes doesn't exist.
        throw new UnknownExtensionException('Unknown themes: ' . implode(', ', $missing) . '.');
      }

      // Only process themes that are not installed currently.
      if (!$theme_list = array_diff_key($theme_list, $installed_themes)) {
        // Nothing to do. All themes already installed.
        return TRUE;
      }

      $module_list = $this->moduleExtensionList->getList();
      foreach ($theme_list as $theme => $value) {
        $module_dependencies = $theme_data[$theme]->module_dependencies;
        // $theme_data[$theme]->requires contains both theme and module
        // dependencies keyed by the extension machine names.
        // $theme_data[$theme]->module_dependencies contains only the module
        // dependencies keyed by the module extension machine name. Therefore,
        // we can find the theme dependencies by finding array keys for
        // 'requires' that are not in $module_dependencies.
        $theme_dependencies = array_diff_key($theme_data[$theme]->requires, $module_dependencies);
        // We can find the unmet module dependencies by finding the module
        // machine names keys that are not in $installed_modules keys.
        $unmet_module_dependencies = array_diff_key($module_dependencies, $installed_modules);

        if ($theme_data[$theme]->info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER] === ExtensionLifecycle::DEPRECATED) {
          // phpcs:ignore Drupal.Semantics.FunctionTriggerError
          @trigger_error("The theme '$theme' is deprecated. See " . $theme_data[$theme]->info['lifecycle_link'], E_USER_DEPRECATED);
        }

        // Prevent themes with unmet module dependencies from being installed.
        if (!empty($unmet_module_dependencies)) {
          $unmet_module_dependencies_list = implode(', ', array_keys($unmet_module_dependencies));
          throw new MissingDependencyException("Unable to install theme: '$theme' due to unmet module dependencies: '$unmet_module_dependencies_list'.");
        }

        foreach ($module_dependencies as $dependency => $dependency_object) {
          if ($incompatible = $this->checkDependencyMessage($module_list, $dependency, $dependency_object)) {
            $sanitized_message = Html::decodeEntities(strip_tags($incompatible));
            throw new MissingDependencyException("Unable to install theme: $sanitized_message");
          }
        }

        // Add dependencies to the list of themes to install. The new themes
        // will be processed as the parent foreach loop continues.
        foreach (array_keys($theme_dependencies) as $dependency) {
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

      // Throw an exception if a module with the same name is enabled.
      $installed_modules = $extension_config->get('module') ?: [];
      if (isset($installed_modules[$key])) {
        throw new ExtensionNameReservedException("Theme name $key is already in use by an installed module.");
      }

      // Validate default configuration of the theme. If there is existing
      // configuration then stop installing.
      $this->configInstaller->checkConfigurationToInstall('theme', $key);

      // The value is not used; the weight is ignored for themes currently. Do
      // not check schema when saving the configuration.
      $extension_config
        ->set("theme.$key", 0)
        ->save(TRUE);

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
    // Add new themes to the list of installed themes.
    $register_themes = array_merge(array_keys($installed_themes), $themes_installed);
    // Get list of extensions for the new list of themes.
    $register_themes = array_intersect_key($theme_data, array_flip($register_themes));
    $this->resetSystem($register_themes);

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
    $installed_themes = $extension_config->get('theme') ?: [];
    $theme_data = $this->themeExtensionList->reset()->getList();
    foreach ($theme_list as $key) {
      if ($extension_config->get("theme.$key") === NULL) {
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
      if (isset($list[$key]) && !empty($list[$key]->sub_themes)) {
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

      // Remove all configuration belonging to the theme.
      $this->configManager->uninstall('theme', $key);
    }
    // Don't check schema when uninstalling a theme since we are only clearing
    // keys.
    $extension_config->save(TRUE);

    // Refresh theme info.
    $this->themeHandler->reset();
    // Remove themes that were uninstalled from the list.
    $register_themes = array_diff(array_keys($installed_themes), $theme_list);
    // Get list of extensions for the new list of themes.
    $register_themes = array_intersect_key($theme_data, array_flip($register_themes));
    $this->resetSystem($register_themes);

    $this->moduleHandler->invokeAll('themes_uninstalled', [$theme_list]);
  }

  /**
   * Resets some other systems like rebuilding the route information or caches.
   *
   * @param array<string, \Drupal\Core\Extension\Extension> $register_themes
   *   Extension data for themes that should be registered, keyed by name.
   */
  protected function resetSystem(array $register_themes) {
    $this->themeRegistry->reset();
    $this->kernel->updateThemes($register_themes);
    $container = $this->kernel->getContainer();
    $this->themeHandler = $container->get('theme_handler');
    $this->configFactory = $container->get('config.factory');
    $this->configInstaller = $container->get('config.installer');
    $this->moduleHandler = $container->get('module_handler');
    $this->configManager = $container->get('config.manager');
    $this->cssCollectionOptimizer = $container->get('asset.css.collection_optimizer');
    $this->routeBuilder = $container->get('router.builder');
    $this->logger = $container->get('logger.channel.default');
    $this->state = $container->get('state');
    $this->moduleExtensionList = $container->get('extension.list.module');
    $this->themeRegistry = $container->get('theme.registry');
    $this->themeExtensionList = $container->get('extension.list.theme');

    $container->get('stream_wrapper_manager')->register();
    // Clear all plugin caches.
    $container->get('plugin.cache_clearer')->clearCachedDefinitions();
    $this->routeBuilder->setRebuildNeeded();
  }

}
