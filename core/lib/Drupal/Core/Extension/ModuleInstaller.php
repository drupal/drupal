<?php

/**
 * @file
 * Contains \Drupal\Core\Extension\ModuleInstaller.
 */

namespace Drupal\Core\Extension;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\PreExistingConfigException;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Component\Utility\String;

/**
 * Default implementation of the module installer.
 *
 * It registers the module in config, install its own configuration,
 * installs the schema, updates the Drupal kernel and more.
 */
class ModuleInstaller implements ModuleInstallerInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The drupal kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $kernel;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The uninstall validators.
   *
   * @var \Drupal\Core\Extension\ModuleUninstallValidatorInterface[]
   */
  protected $uninstallValidators;

  /**
   * Constructs a new ModuleInstaller instance.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\DrupalKernelInterface $kernel
   *   The drupal kernel.
   *
   * @see \Drupal\Core\DrupalKernel
   * @see \Drupal\Core\CoreServiceProvider
   */
  public function __construct($root, ModuleHandlerInterface $module_handler, DrupalKernelInterface $kernel) {
    $this->root = $root;
    $this->moduleHandler = $module_handler;
    $this->kernel = $kernel;
  }

  /**
   * {@inheritdoc}
   */
  public function addUninstallValidator(ModuleUninstallValidatorInterface $uninstall_validator) {
    $this->uninstallValidators[] = $uninstall_validator;
  }

  /**
   * {@inheritdoc}
   */
  public function install(array $module_list, $enable_dependencies = TRUE) {
    $extension_config = \Drupal::configFactory()->getEditable('core.extension');
    if ($enable_dependencies) {
      // Get all module data so we can find dependencies and sort.
      $module_data = system_rebuild_module_data();
      $module_list = $module_list ? array_combine($module_list, $module_list) : array();
      if ($missing_modules = array_diff_key($module_list, $module_data)) {
        // One or more of the given modules doesn't exist.
        throw new MissingDependencyException(String::format('Unable to install modules %modules due to missing modules %missing.', array(
          '%modules' => implode(', ', $module_list),
          '%missing' => implode(', ', $missing_modules),
        )));
      }

      // Only process currently uninstalled modules.
      $installed_modules = $extension_config->get('module') ?: array();
      if (!$module_list = array_diff_key($module_list, $installed_modules)) {
        // Nothing to do. All modules already installed.
        return TRUE;
      }

      // Add dependencies to the list. The new modules will be processed as
      // the while loop continues.
      while (list($module) = each($module_list)) {
        foreach (array_keys($module_data[$module]->requires) as $dependency) {
          if (!isset($module_data[$dependency])) {
            // The dependency does not exist.
            throw new MissingDependencyException(String::format('Unable to install modules: module %module is missing its dependency module %dependency.', array(
              '%module' => $module,
              '%dependency' => $dependency,
            )));
          }

          // Skip already installed modules.
          if (!isset($module_list[$dependency]) && !isset($installed_modules[$dependency])) {
            $module_list[$dependency] = $dependency;
          }
        }
      }

      // Set the actual module weights.
      $module_list = array_map(function ($module) use ($module_data) {
        return $module_data[$module]->sort;
      }, $module_list);

      // Sort the module list by their weights (reverse).
      arsort($module_list);
      $module_list = array_keys($module_list);
    }

    // Required for module installation checks.
    include_once $this->root . '/core/includes/install.inc';

    /** @var \Drupal\Core\Config\ConfigInstaller $config_installer */
    $config_installer = \Drupal::service('config.installer');
    $sync_status = $config_installer->isSyncing();
    if ($sync_status) {
      $source_storage = $config_installer->getSourceStorage();
    }
    $modules_installed = array();
    foreach ($module_list as $module) {
      $enabled = $extension_config->get("module.$module") !== NULL;
      if (!$enabled) {
        // Throw an exception if the module name is too long.
        if (strlen($module) > DRUPAL_EXTENSION_NAME_MAX_LENGTH) {
          throw new ExtensionNameLengthException(format_string('Module name %name is over the maximum allowed length of @max characters.', array(
            '%name' => $module,
            '@max' => DRUPAL_EXTENSION_NAME_MAX_LENGTH,
          )));
        }

        // Check the validity of the default configuration. This will throw
        // exceptions if the configuration is not valid.
        $config_installer->checkConfigurationToInstall('module', $module);

        $extension_config
          ->set("module.$module", 0)
          ->set('module', module_config_sort($extension_config->get('module')))
          ->save();

        // Prepare the new module list, sorted by weight, including filenames.
        // This list is used for both the ModuleHandler and DrupalKernel. It
        // needs to be kept in sync between both. A DrupalKernel reboot or
        // rebuild will automatically re-instantiate a new ModuleHandler that
        // uses the new module list of the kernel. However, DrupalKernel does
        // not cause any modules to be loaded.
        // Furthermore, the currently active (fixed) module list can be
        // different from the configured list of enabled modules. For all active
        // modules not contained in the configured enabled modules, we assume a
        // weight of 0.
        $current_module_filenames = $this->moduleHandler->getModuleList();
        $current_modules = array_fill_keys(array_keys($current_module_filenames), 0);
        $current_modules = module_config_sort(array_merge($current_modules, $extension_config->get('module')));
        $module_filenames = array();
        foreach ($current_modules as $name => $weight) {
          if (isset($current_module_filenames[$name])) {
            $module_filenames[$name] = $current_module_filenames[$name];
          }
          else {
            $module_path = drupal_get_path('module', $name);
            $pathname = "$module_path/$name.info.yml";
            $filename = file_exists($module_path . "/$name.module") ? "$name.module" : NULL;
            $module_filenames[$name] = new Extension($this->root, 'module', $pathname, $filename);
          }
        }

        // Update the module handler in order to load the module's code.
        // This allows the module to participate in hooks and its existence to
        // be discovered by other modules.
        // The current ModuleHandler instance is obsolete with the kernel
        // rebuild below.
        $this->moduleHandler->setModuleList($module_filenames);
        $this->moduleHandler->load($module);
        module_load_install($module);

        // Clear the static cache of system_rebuild_module_data() to pick up the
        // new module, since it merges the installation status of modules into
        // its statically cached list.
        drupal_static_reset('system_rebuild_module_data');

        // Update the kernel to include it.
        $this->updateKernel($module_filenames);

        // Refresh the schema to include it.
        drupal_get_schema(NULL, TRUE);

        // Allow modules to react prior to the installation of a module.
        $this->moduleHandler->invokeAll('module_preinstall', array($module));

        // Now install the module's schema if necessary.
        drupal_install_schema($module);

        // Clear plugin manager caches.
        \Drupal::getContainer()->get('plugin.cache_clearer')->clearCachedDefinitions();

        // Set the schema version to the number of the last update provided by
        // the module, or the minimum core schema version.
        $version = \Drupal::CORE_MINIMUM_SCHEMA_VERSION;
        $versions = drupal_get_schema_versions($module);
        if ($versions) {
          $version = max(max($versions), $version);
        }

        // Notify interested components that this module's entity types are new.
        // For example, a SQL-based storage handler can use this as an
        // opportunity to create the necessary database tables.
        // @todo Clean this up in https://www.drupal.org/node/2350111.
        $entity_manager = \Drupal::entityManager();
        foreach ($entity_manager->getDefinitions() as $entity_type) {
          if ($entity_type->getProvider() == $module) {
            $entity_manager->onEntityTypeCreate($entity_type);
          }
        }

        // Install default configuration of the module.
        $config_installer = \Drupal::service('config.installer');
        if ($sync_status) {
          $config_installer
            ->setSyncing(TRUE)
            ->setSourceStorage($source_storage);
        }
        \Drupal::service('config.installer')->installDefaultConfig('module', $module);

        // If the module has no current updates, but has some that were
        // previously removed, set the version to the value of
        // hook_update_last_removed().
        if ($last_removed = $this->moduleHandler->invoke($module, 'update_last_removed')) {
          $version = max($version, $last_removed);
        }
        drupal_set_installed_schema_version($module, $version);

        // Record the fact that it was installed.
        $modules_installed[] = $module;

        // file_get_stream_wrappers() needs to re-register Drupal's stream
        // wrappers in case a module-provided stream wrapper is used later in
        // the same request. In particular, this happens when installing Drupal
        // via Drush, as the 'translations' stream wrapper is provided by
        // Interface Translation module and is later used to import
        // translations.
        \Drupal::service('stream_wrapper_manager')->register();

        // Update the theme registry to include it.
        drupal_theme_rebuild();

        // Modules can alter theme info, so refresh theme data.
        // @todo ThemeHandler cannot be injected into ModuleHandler, since that
        //   causes a circular service dependency.
        // @see https://drupal.org/node/2208429
        \Drupal::service('theme_handler')->refreshInfo();

        // Allow the module to perform install tasks.
        $this->moduleHandler->invoke($module, 'install');

        // Record the fact that it was installed.
        \Drupal::logger('system')->info('%module module installed.', array('%module' => $module));
      }
    }

    // If any modules were newly installed, invoke hook_modules_installed().
    if (!empty($modules_installed)) {
      \Drupal::service('router.builder')->setRebuildNeeded();
      $this->moduleHandler->invokeAll('modules_installed', array($modules_installed));
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(array $module_list, $uninstall_dependents = TRUE) {
    // Get all module data so we can find dependencies and sort.
    $module_data = system_rebuild_module_data();
    $module_list = $module_list ? array_combine($module_list, $module_list) : array();
    if (array_diff_key($module_list, $module_data)) {
      // One or more of the given modules doesn't exist.
      return FALSE;
    }

    $extension_config = \Drupal::configFactory()->getEditable('core.extension');
    $installed_modules = $extension_config->get('module') ?: array();
    if (!$module_list = array_intersect_key($module_list, $installed_modules)) {
      // Nothing to do. All modules already uninstalled.
      return TRUE;
    }

    if ($uninstall_dependents) {
      // Add dependent modules to the list. The new modules will be processed as
      // the while loop continues.
      $profile = drupal_get_profile();
      while (list($module) = each($module_list)) {
        foreach (array_keys($module_data[$module]->required_by) as $dependent) {
          if (!isset($module_data[$dependent])) {
            // The dependent module does not exist.
            return FALSE;
          }

          // Skip already uninstalled modules.
          if (isset($installed_modules[$dependent]) && !isset($module_list[$dependent]) && $dependent != $profile) {
            $module_list[$dependent] = $dependent;
          }
        }
      }
    }

    // Use the validators and throw an exception with the reasons.
    if ($reasons = $this->validateUninstall($module_list)) {
      foreach ($reasons as $reason) {
        $reason_message[] = implode(', ', $reason);
      }
      throw new ModuleUninstallValidatorException(format_string('The following reasons prevents the modules from being uninstalled: @reasons', array(
        '@reasons' => implode(', ', $reason_message),
      )));
    }
    // Set the actual module weights.
    $module_list = array_map(function ($module) use ($module_data) {
      return $module_data[$module]->sort;
    }, $module_list);

    // Sort the module list by their weights.
    asort($module_list);
    $module_list = array_keys($module_list);

    // Only process modules that are enabled. A module is only enabled if it is
    // configured as enabled. Custom or overridden module handlers might contain
    // the module already, which means that it might be loaded, but not
    // necessarily installed.
    foreach ($module_list as $module) {

      // Clean up all entity bundles (including fields) of every entity type
      // provided by the module that is being uninstalled.
      // @todo Clean this up in https://www.drupal.org/node/2350111.
      $entity_manager = \Drupal::entityManager();
      foreach ($entity_manager->getDefinitions() as $entity_type_id => $entity_type) {
        if ($entity_type->getProvider() == $module) {
          foreach (array_keys($entity_manager->getBundleInfo($entity_type_id)) as $bundle) {
            $entity_manager->onBundleDelete($bundle, $entity_type_id);
          }
        }
      }

      // Allow modules to react prior to the uninstallation of a module.
      $this->moduleHandler->invokeAll('module_preuninstall', array($module));

      // Uninstall the module.
      module_load_install($module);
      $this->moduleHandler->invoke($module, 'uninstall');

      // Remove all configuration belonging to the module.
      \Drupal::service('config.manager')->uninstall('module', $module);

      // Notify interested components that this module's entity types are being
      // deleted. For example, a SQL-based storage handler can use this as an
      // opportunity to drop the corresponding database tables.
      // @todo Clean this up in https://www.drupal.org/node/2350111.
      foreach ($entity_manager->getDefinitions() as $entity_type) {
        if ($entity_type->getProvider() == $module) {
          $entity_manager->onEntityTypeDelete($entity_type);
        }
      }

      // Remove the schema.
      drupal_uninstall_schema($module);

      // Remove the module's entry from the config.
      \Drupal::configFactory()->getEditable('core.extension')->clear("module.$module")->save();

      // Update the module handler to remove the module.
      // The current ModuleHandler instance is obsolete with the kernel rebuild
      // below.
      $module_filenames = $this->moduleHandler->getModuleList();
      unset($module_filenames[$module]);
      $this->moduleHandler->setModuleList($module_filenames);

      // Remove any potential cache bins provided by the module.
      $this->removeCacheBins($module);

      // Clear the static cache of system_rebuild_module_data() to pick up the
      // new module, since it merges the installation status of modules into
      // its statically cached list.
      drupal_static_reset('system_rebuild_module_data');

      // Clear plugin manager caches.
      \Drupal::getContainer()->get('plugin.cache_clearer')->clearCachedDefinitions();

      // Update the kernel to exclude the uninstalled modules.
      $this->updateKernel($module_filenames);

      // Update the theme registry to remove the newly uninstalled module.
      drupal_theme_rebuild();

      // Modules can alter theme info, so refresh theme data.
      // @todo ThemeHandler cannot be injected into ModuleHandler, since that
      //   causes a circular service dependency.
      // @see https://drupal.org/node/2208429
      \Drupal::service('theme_handler')->refreshInfo();

      \Drupal::logger('system')->info('%module module uninstalled.', array('%module' => $module));

      $schema_store = \Drupal::keyValue('system.schema');
      $schema_store->delete($module);
    }
    \Drupal::service('router.builder')->setRebuildNeeded();
    drupal_get_installed_schema_version(NULL, TRUE);

    // Let other modules react.
    $this->moduleHandler->invokeAll('modules_uninstalled', array($module_list));

    // Flush all persistent caches.
    // Any cache entry might implicitly depend on the uninstalled modules,
    // so clear all of them explicitly.
    $this->moduleHandler->invokeAll('cache_flush');
    foreach (Cache::getBins() as $service_id => $cache_backend) {
      $cache_backend->deleteAll();
    }

    return TRUE;
  }

  /**
   * Helper method for removing all cache bins registered by a given module.
   *
   * @param string $module
   *   The name of the module for which to remove all registered cache bins.
   */
  protected function removeCacheBins($module) {
    // Remove any cache bins defined by a module.
    $service_yaml_file = drupal_get_path('module', $module) . "/$module.services.yml";
    if (file_exists($service_yaml_file)) {
      $definitions = Yaml::decode(file_get_contents($service_yaml_file));
      if (isset($definitions['services'])) {
        foreach ($definitions['services'] as $id => $definition) {
          if (isset($definition['tags'])) {
            foreach ($definition['tags'] as $tag) {
              // This works for the default cache registration and even in some
              // cases when a non-default "super" factory is used. That should
              // be extremely rare.
              if ($tag['name'] == 'cache.bin' && isset($definition['factory_service']) && isset($definition['factory_method']) && !empty($definition['arguments'])) {
                try {
                  $factory = \Drupal::service($definition['factory_service']);
                  if (method_exists($factory, $definition['factory_method'])) {
                    $backend = call_user_func_array(array($factory, $definition['factory_method']), $definition['arguments']);
                    if ($backend instanceof CacheBackendInterface) {
                      $backend->removeBin();
                    }
                  }
                }
                catch (\Exception $e) {
                  watchdog_exception('system', $e, 'Failed to remove cache bin defined by the service %id.', array('%id' => $id));
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * Updates the kernel module list.
   *
   * @param string $module_filenames
   *   The list of installed modules.
   */
  protected function updateKernel($module_filenames) {
    // This reboots the kernel to register the module's bundle and its services
    // in the service container. The $module_filenames argument is taken over as
    // %container.modules% parameter, which is passed to a fresh ModuleHandler
    // instance upon first retrieval.
    $this->kernel->updateModules($module_filenames, $module_filenames);
    // After rebuilding the container we need to update the injected
    // dependencies.
    $container = $this->kernel->getContainer();
    $this->moduleHandler = $container->get('module_handler');
  }

  /**
   * {@inheritdoc}
   */
  public function validateUninstall(array $module_list) {
    $reasons = array();
    foreach ($module_list as $module) {
      foreach ($this->uninstallValidators as $validator) {
        $validation_reasons = $validator->validate($module);
        if (!empty($validation_reasons)) {
          if (!isset($reasons[$module])) {
            $reasons[$module] = array();
          }
          $reasons[$module] = array_merge($reasons[$module], $validation_reasons);
        }
      }
    }
    return $reasons;
  }

}
