<?php

namespace Drupal\Core\Extension;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * Default implementation of the module installer.
 *
 * It registers the module in config, installs its own configuration,
 * installs the schema, updates the Drupal kernel and more.
 *
 * We don't inject dependencies yet, as we would need to reload them after
 * each installation or uninstallation of a module.
 * https://www.drupal.org/project/drupal/issues/2350111 for example tries to
 * solve this dilemma.
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
    // Get all module data so we can find dependencies and sort and find the
    // core requirements. The module list needs to be reset so that it can
    // re-scan and include any new modules that may have been added directly
    // into the filesystem.
    $module_data = \Drupal::service('extension.list.module')->reset()->getList();
    foreach ($module_list as $module) {
      if (!empty($module_data[$module]->info['core_incompatible'])) {
        throw new MissingDependencyException("Unable to install modules: module '$module' is incompatible with this version of Drupal core.");
      }
    }
    if ($enable_dependencies) {
      $module_list = $module_list ? array_combine($module_list, $module_list) : [];
      if ($missing_modules = array_diff_key($module_list, $module_data)) {
        // One or more of the given modules doesn't exist.
        throw new MissingDependencyException(sprintf('Unable to install modules %s due to missing modules %s.', implode(', ', $module_list), implode(', ', $missing_modules)));
      }

      // Only process currently uninstalled modules.
      $installed_modules = $extension_config->get('module') ?: [];
      if (!$module_list = array_diff_key($module_list, $installed_modules)) {
        // Nothing to do. All modules already installed.
        return TRUE;
      }

      // Add dependencies to the list. The new modules will be processed as
      // the foreach loop continues.
      foreach ($module_list as $module => $value) {
        foreach (array_keys($module_data[$module]->requires) as $dependency) {
          if (!isset($module_data[$dependency])) {
            // The dependency does not exist.
            throw new MissingDependencyException("Unable to install modules: module '$module' is missing its dependency module $dependency.");
          }

          // Skip already installed modules.
          if (!isset($module_list[$dependency]) && !isset($installed_modules[$dependency])) {
            if ($module_data[$dependency]->info['core_incompatible']) {
              throw new MissingDependencyException("Unable to install modules: module '$module'. Its dependency module '$dependency' is incompatible with this version of Drupal core.");
            }
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
    $modules_installed = [];
    foreach ($module_list as $module) {
      $enabled = $extension_config->get("module.$module") !== NULL;
      if (!$enabled) {
        // Throw an exception if the module name is too long.
        if (strlen($module) > DRUPAL_EXTENSION_NAME_MAX_LENGTH) {
          throw new ExtensionNameLengthException("Module name '$module' is over the maximum allowed length of " . DRUPAL_EXTENSION_NAME_MAX_LENGTH . ' characters');
        }

        // Load a new config object for each iteration, otherwise changes made
        // in hook_install() are not reflected in $extension_config.
        $extension_config = \Drupal::configFactory()->getEditable('core.extension');

        // Check the validity of the default configuration. This will throw
        // exceptions if the configuration is not valid.
        $config_installer->checkConfigurationToInstall('module', $module);

        // Save this data without checking schema. This is a performance
        // improvement for module installation.
        $extension_config
          ->set("module.$module", 0)
          ->set('module', module_config_sort($extension_config->get('module')))
          ->save(TRUE);

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
        $module_filenames = [];
        foreach ($current_modules as $name => $weight) {
          if (isset($current_module_filenames[$name])) {
            $module_filenames[$name] = $current_module_filenames[$name];
          }
          else {
            $module_path = \Drupal::service('extension.list.module')->getPath($name);
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

        // Clear the static cache of the "extension.list.module" service to pick
        // up the new module, since it merges the installation status of modules
        // into its statically cached list.
        \Drupal::service('extension.list.module')->reset();

        // Update the kernel to include it.
        $this->updateKernel($module_filenames);

        // Replace the route provider service with a version that will rebuild
        // if routes used during installation. This ensures that a module's
        // routes are available during installation. This has to occur before
        // any services that depend on it are instantiated otherwise those
        // services will have the old route provider injected. Note that, since
        // the container is rebuilt by updating the kernel, the route provider
        // service is the regular one even though we are in a loop and might
        // have replaced it before.
        \Drupal::getContainer()->set('router.route_provider.old', \Drupal::service('router.route_provider'));
        \Drupal::getContainer()->set('router.route_provider', \Drupal::service('router.route_provider.lazy_builder'));

        // Allow modules to react prior to the installation of a module.
        $this->moduleHandler->invokeAll('module_preinstall', [$module]);

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

        // Notify interested components that this module's entity types and
        // field storage definitions are new. For example, a SQL-based storage
        // handler can use this as an opportunity to create the necessary
        // database tables.
        // @todo Clean this up in https://www.drupal.org/node/2350111.
        $entity_type_manager = \Drupal::entityTypeManager();
        $update_manager = \Drupal::entityDefinitionUpdateManager();
        /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
        $entity_field_manager = \Drupal::service('entity_field.manager');
        foreach ($entity_type_manager->getDefinitions() as $entity_type) {
          if ($entity_type->getProvider() == $module) {
            $update_manager->installEntityType($entity_type);
          }
          elseif ($entity_type->entityClassImplements(FieldableEntityInterface::CLASS)) {
            // The module being installed may be adding new fields to existing
            // entity types. Field definitions for any entity type defined by
            // the module are handled in the if branch.
            foreach ($entity_field_manager->getFieldStorageDefinitions($entity_type->id()) as $storage_definition) {
              if ($storage_definition->getProvider() == $module) {
                // If the module being installed is also defining a storage key
                // for the entity type, the entity schema may not exist yet. It
                // will be created later in that case.
                try {
                  $update_manager->installFieldStorageDefinition($storage_definition->getName(), $entity_type->id(), $module, $storage_definition);
                }
                catch (EntityStorageException $e) {
                  watchdog_exception('system', $e, 'An error occurred while notifying the creation of the @name field storage definition: "!message" in %function (line %line of %file).', ['@name' => $storage_definition->getName()]);
                }
              }
            }
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

        // Ensure that all post_update functions are registered already.
        /** @var \Drupal\Core\Update\UpdateRegistry $post_update_registry */
        $post_update_registry = \Drupal::service('update.post_update_registry');
        $post_update_registry->registerInvokedUpdates($post_update_registry->getModuleUpdateFunctions($module));

        // Record the fact that it was installed.
        $modules_installed[] = $module;

        // Drupal's stream wrappers needs to be re-registered in case a
        // module-provided stream wrapper is used later in the same request. In
        // particular, this happens when installing Drupal via Drush, as the
        // 'translations' stream wrapper is provided by Interface Translation
        // module and is later used to import translations.
        \Drupal::service('stream_wrapper_manager')->register();

        // Update the theme registry to include it.
        drupal_theme_rebuild();

        // Modules can alter theme info, so refresh theme data.
        // @todo ThemeHandler cannot be injected into ModuleHandler, since that
        //   causes a circular service dependency.
        // @see https://www.drupal.org/node/2208429
        \Drupal::service('theme_handler')->refreshInfo();

        // Allow the module to perform install tasks.
        $this->moduleHandler->invoke($module, 'install');

        // Record the fact that it was installed.
        \Drupal::logger('system')->info('%module module installed.', ['%module' => $module]);
      }
    }

    // If any modules were newly installed, invoke hook_modules_installed().
    if (!empty($modules_installed)) {
      // If the container was rebuilt during hook_install() it might not have
      // the 'router.route_provider.old' service.
      if (\Drupal::hasService('router.route_provider.old')) {
        \Drupal::getContainer()->set('router.route_provider', \Drupal::service('router.route_provider.old'));
      }
      if (!\Drupal::service('router.route_provider.lazy_builder')->hasRebuilt()) {
        // Rebuild routes after installing module. This is done here on top of
        // \Drupal\Core\Routing\RouteBuilder::destruct to not run into errors on
        // fastCGI which executes ::destruct() after the module installation
        // page was sent already.
        \Drupal::service('router.builder')->rebuild();
      }

      $this->moduleHandler->invokeAll('modules_installed', [$modules_installed]);
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall(array $module_list, $uninstall_dependents = TRUE) {
    // Get all module data so we can find dependencies and sort.
    $module_data = \Drupal::service('extension.list.module')->getList();
    $module_list = $module_list ? array_combine($module_list, $module_list) : [];
    if (array_diff_key($module_list, $module_data)) {
      // One or more of the given modules doesn't exist.
      return FALSE;
    }

    $extension_config = \Drupal::configFactory()->getEditable('core.extension');
    $installed_modules = $extension_config->get('module') ?: [];
    if (!$module_list = array_intersect_key($module_list, $installed_modules)) {
      // Nothing to do. All modules already uninstalled.
      return TRUE;
    }

    if ($uninstall_dependents) {
      // Add dependent modules to the list. The new modules will be processed as
      // the foreach loop continues.
      foreach ($module_list as $module => $value) {
        foreach (array_keys($module_data[$module]->required_by) as $dependent) {
          if (!isset($module_data[$dependent])) {
            // The dependent module does not exist.
            return FALSE;
          }

          // Skip already uninstalled modules.
          if (isset($installed_modules[$dependent]) && !isset($module_list[$dependent])) {
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
      throw new ModuleUninstallValidatorException('The following reasons prevent the modules from being uninstalled: ' . implode('; ', $reason_message));
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
      $entity_type_manager = \Drupal::entityTypeManager();
      $entity_type_bundle_info = \Drupal::service('entity_type.bundle.info');
      foreach ($entity_type_manager->getDefinitions() as $entity_type_id => $entity_type) {
        if ($entity_type->getProvider() == $module) {
          foreach (array_keys($entity_type_bundle_info->getBundleInfo($entity_type_id)) as $bundle) {
            \Drupal::service('entity_bundle.listener')->onBundleDelete($bundle, $entity_type_id);
          }
        }
      }

      // Allow modules to react prior to the uninstallation of a module.
      $this->moduleHandler->invokeAll('module_preuninstall', [$module]);

      // Uninstall the module.
      module_load_install($module);
      $this->moduleHandler->invoke($module, 'uninstall');

      // Remove all configuration belonging to the module.
      \Drupal::service('config.manager')->uninstall('module', $module);

      // In order to make uninstalling transactional if anything uses routes.
      \Drupal::getContainer()->set('router.route_provider.old', \Drupal::service('router.route_provider'));
      \Drupal::getContainer()->set('router.route_provider', \Drupal::service('router.route_provider.lazy_builder'));

      // Notify interested components that this module's entity types are being
      // deleted. For example, a SQL-based storage handler can use this as an
      // opportunity to drop the corresponding database tables.
      // @todo Clean this up in https://www.drupal.org/node/2350111.
      $update_manager = \Drupal::entityDefinitionUpdateManager();
      /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
      $entity_field_manager = \Drupal::service('entity_field.manager');
      foreach ($entity_type_manager->getDefinitions() as $entity_type) {
        if ($entity_type->getProvider() == $module) {
          $update_manager->uninstallEntityType($entity_type);
        }
        elseif ($entity_type->entityClassImplements(FieldableEntityInterface::CLASS)) {
          // The module being uninstalled might have added new fields to
          // existing entity types. This will add them to the deleted fields
          // repository so their data will be purged on cron.
          foreach ($entity_field_manager->getFieldStorageDefinitions($entity_type->id()) as $storage_definition) {
            if ($storage_definition->getProvider() == $module) {
              $update_manager->uninstallFieldStorageDefinition($storage_definition);
            }
          }
        }
      }

      // Remove the schema.
      drupal_uninstall_schema($module);

      // Remove the module's entry from the config. Don't check schema when
      // uninstalling a module since we are only clearing a key.
      \Drupal::configFactory()->getEditable('core.extension')->clear("module.$module")->save(TRUE);

      // Update the module handler to remove the module.
      // The current ModuleHandler instance is obsolete with the kernel rebuild
      // below.
      $module_filenames = $this->moduleHandler->getModuleList();
      unset($module_filenames[$module]);
      $this->moduleHandler->setModuleList($module_filenames);

      // Remove any potential cache bins provided by the module.
      $this->removeCacheBins($module);

      // Clear the static cache of the "extension.list.module" service to pick
      // up the new module, since it merges the installation status of modules
      // into its statically cached list.
      \Drupal::service('extension.list.module')->reset();

      // Clear plugin manager caches.
      \Drupal::getContainer()->get('plugin.cache_clearer')->clearCachedDefinitions();

      // Update the kernel to exclude the uninstalled modules.
      $this->updateKernel($module_filenames);

      // Update the theme registry to remove the newly uninstalled module.
      drupal_theme_rebuild();

      // Modules can alter theme info, so refresh theme data.
      // @todo ThemeHandler cannot be injected into ModuleHandler, since that
      //   causes a circular service dependency.
      // @see https://www.drupal.org/node/2208429
      \Drupal::service('theme_handler')->refreshInfo();

      \Drupal::logger('system')->info('%module module uninstalled.', ['%module' => $module]);

      $schema_store = \Drupal::keyValue('system.schema');
      $schema_store->delete($module);

      /** @var \Drupal\Core\Update\UpdateRegistry $post_update_registry */
      $post_update_registry = \Drupal::service('update.post_update_registry');
      $post_update_registry->filterOutInvokedUpdatesByModule($module);
    }
    // Rebuild routes after installing module. This is done here on top of
    // \Drupal\Core\Routing\RouteBuilder::destruct to not run into errors on
    // fastCGI which executes ::destruct() after the Module uninstallation page
    // was sent already.
    \Drupal::service('router.builder')->rebuild();
    drupal_get_installed_schema_version(NULL, TRUE);

    // Let other modules react.
    $this->moduleHandler->invokeAll('modules_uninstalled', [$module_list]);

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
    $service_yaml_file = drupal_get_path('module', $module) . "/$module.services.yml";
    if (!file_exists($service_yaml_file)) {
      return;
    }

    $definitions = Yaml::decode(file_get_contents($service_yaml_file));

    $cache_bin_services = array_filter(
      isset($definitions['services']) ? $definitions['services'] : [],
      function ($definition) {
        $tags = isset($definition['tags']) ? $definition['tags'] : [];
        foreach ($tags as $tag) {
          if (isset($tag['name']) && ($tag['name'] == 'cache.bin')) {
            return TRUE;
          }
        }
        return FALSE;
      }
    );

    foreach (array_keys($cache_bin_services) as $service_id) {
      $backend = $this->kernel->getContainer()->get($service_id);
      if ($backend instanceof CacheBackendInterface) {
        $backend->removeBin();
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
    $reasons = [];
    foreach ($module_list as $module) {
      foreach ($this->uninstallValidators as $validator) {
        $validation_reasons = $validator->validate($module);
        if (!empty($validation_reasons)) {
          if (!isset($reasons[$module])) {
            $reasons[$module] = [];
          }
          $reasons[$module] = array_merge($reasons[$module], $validation_reasons);
        }
      }
    }
    return $reasons;
  }

}
