<?php

/**
 * @file
 * API for loading and interacting with Drupal modules.
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Parser;

/**
 * Builds a list of bootstrap modules and enabled modules and themes.
 *
 * @param $type
 *   The type of list to return:
 *   - module_enabled: All enabled modules.
 *   - bootstrap: All enabled modules required for bootstrap.
 *   - theme: All themes.
 *
 * @return
 *   An associative array of modules or themes, keyed by name. For $type
 *   'bootstrap' and 'module_enabled', the array values equal the keys.
 *   For $type 'theme', the array values are objects representing the
 *   respective database row, with the 'info' property already unserialized.
 *
 * @see list_themes()
 *
 * @todo There are too many layers/levels of caching involved for system_list()
 *   data. Consider to add a config($name, $cache = TRUE) argument to allow
 *   callers like system_list() to force-disable a possible configuration
 *   storage controller cache or some other way to circumvent it/take it over.
 */
function system_list($type) {
  $lists = &drupal_static(__FUNCTION__);
  if ($cached = cache('bootstrap')->get('system_list')) {
    $lists = $cached->data;
  }
  else {
    $lists = array(
      'theme' => array(),
      'filepaths' => array(),
    );
    // Build a list of themes.
    $enabled_themes = (array) config('system.theme')->get('enabled');
    // @todo Themes include all themes, including disabled/uninstalled. This
    //   system.theme.data state will go away entirely as soon as themes have
    //   a proper installation status.
    // @see http://drupal.org/node/1067408
    $theme_data = state()->get('system.theme.data');
    if (empty($theme_data)) {
      // @todo: system_list() may be called from _drupal_bootstrap_code(), in
      // which case system.module is not loaded yet.
      // Prevent a filesystem scan in drupal_load() and include it directly.
      // @see http://drupal.org/node/1067408
      require_once DRUPAL_ROOT . '/core/modules/system/system.module';
      $theme_data = system_rebuild_theme_data();
    }
    foreach ($theme_data as $name => $theme) {
      $theme->status = (int) isset($enabled_themes[$name]);
      $lists['theme'][$name] = $theme;
      // Build a list of filenames so drupal_get_filename can use it.
      if (isset($enabled_themes[$name])) {
        $lists['filepaths'][] = array(
          'type' => 'theme',
          'name' => $name,
          'filepath' => $theme->filename,
        );
      }
    }
    // @todo Move into list_themes(). Read info for a particular requested
    //   theme from state instead.
    foreach ($lists['theme'] as $key => $theme) {
      if (!empty($theme->info['base theme'])) {
        // Make a list of the theme's base themes.
        require_once DRUPAL_ROOT . '/core/includes/theme.inc';
        $lists['theme'][$key]->base_themes = drupal_find_base_themes($lists['theme'], $key);
        // Don't proceed if there was a problem with the root base theme.
        if (!current($lists['theme'][$key]->base_themes)) {
          continue;
        }
        // Determine the root base theme.
        $base_key = key($lists['theme'][$key]->base_themes);
        // Add to the list of sub-themes for each of the theme's base themes.
        foreach (array_keys($lists['theme'][$key]->base_themes) as $base_theme) {
          $lists['theme'][$base_theme]->sub_themes[$key] = $lists['theme'][$key]->info['name'];
        }
        // Add the base theme's theme engine info.
        $lists['theme'][$key]->info['engine'] = $lists['theme'][$base_key]->info['engine'];
      }
      else {
        // A plain theme is its own base theme.
        $base_key = $key;
      }
      // Set the theme engine prefix.
      $lists['theme'][$key]->prefix = ($lists['theme'][$key]->info['engine'] == 'theme') ? $base_key : $lists['theme'][$key]->info['engine'];
    }
    cache('bootstrap')->set('system_list', $lists);
  }
  // To avoid a separate database lookup for the filepath, prime the
  // drupal_get_filename() static cache with all enabled modules and themes.
  foreach ($lists['filepaths'] as $item) {
    system_register($item['type'], $item['name'], $item['filepath']);
  }

  return $lists[$type];
}

/**
 * Resets all system_list() caches.
 */
function system_list_reset() {
  drupal_static_reset('system_list');
  drupal_static_reset('system_rebuild_module_data');
  drupal_static_reset('list_themes');
  cache('bootstrap')->delete('system_list');
  cache()->delete('system_info');
  // Remove last known theme data state.
  // This causes system_list() to call system_rebuild_theme_data() on its next
  // invocation. When enabling a module that implements hook_system_info_alter()
  // to inject a new (testing) theme or manipulate an existing theme, then that
  // will cause system_list_reset() to be called, but theme data is not
  // necessarily rebuilt afterwards.
  // @todo Obsolete with proper installation status for themes.
  state()->delete('system.theme.data');
}

/**
 * Registers an extension in runtime registries for execution.
 *
 * @param string $type
 *   The extension type; e.g., 'module' or 'theme'.
 * @param string $name
 *   The internal name of the extension; e.g., 'node'.
 * @param string $uri
 *   The relative URI of the primary extension file; e.g.,
 *   'core/modules/node/node.module'.
 */
function system_register($type, $name, $uri) {
  drupal_get_filename($type, $name, $uri);
  drupal_classloader_register($name, dirname($uri));
}

/**
 * Loads a module's installation hooks.
 *
 * @param $module
 *   The name of the module (without the .module extension).
 *
 * @return
 *   The name of the module's install file, if successful; FALSE otherwise.
 */
function module_load_install($module) {
  // Make sure the installation API is available
  include_once DRUPAL_ROOT . '/core/includes/install.inc';

  return module_load_include('install', $module);
}

/**
 * Loads a module include file.
 *
 * Examples:
 * @code
 *   // Load node.admin.inc from the node module.
 *   module_load_include('inc', 'node', 'node.admin');
 *   // Load content_types.inc from the node module.
 *   module_load_include('inc', 'node', 'content_types');
 * @endcode
 *
 * Do not use this function to load an install file, use module_load_install()
 * instead. Do not use this function in a global context since it requires
 * Drupal to be fully bootstrapped, use require_once DRUPAL_ROOT . '/path/file'
 * instead.
 *
 * @param $type
 *   The include file's type (file extension).
 * @param $module
 *   The module to which the include file belongs.
 * @param $name
 *   (optional) The base file name (without the $type extension). If omitted,
 *   $module is used; i.e., resulting in "$module.$type" by default.
 *
 * @return
 *   The name of the included file, if successful; FALSE otherwise.
 *
 * @todo The module_handler service has a loadInclude() method which performs
 *   this same task but only for enabled modules. Figure out a way to move this
 *   functionality entirely into the module_handler while keeping the ability to
 *   load the files of disabled modules.
 */
function module_load_include($type, $module, $name = NULL) {
  if (!isset($name)) {
    $name = $module;
  }

  if (function_exists('drupal_get_path')) {
    $file = DRUPAL_ROOT . '/' . drupal_get_path('module', $module) . "/$name.$type";
    if (is_file($file)) {
      require_once $file;
      return $file;
    }
  }
  return FALSE;
}


/**
 * Enables or installs a given list of modules.
 *
 * Definitions:
 * - "Enabling" is the process of activating a module for use by Drupal.
 * - "Disabling" is the process of deactivating a module.
 * - "Installing" is the process of enabling it for the first time or after it
 *   has been uninstalled.
 * - "Uninstalling" is the process of removing all traces of a module.
 *
 * Order of events:
 * - Gather and add module dependencies to $module_list (if applicable).
 * - For each module that is being enabled:
 *   - Install module schema and update system registries and caches.
 *   - If the module is being enabled for the first time or had been
 *     uninstalled, invoke hook_install() and add it to the list of installed
 *     modules.
 *   - Invoke hook_enable().
 * - Invoke hook_modules_installed().
 * - Invoke hook_modules_enabled().
 *
 * @param $module_list
 *   An array of module names.
 * @param $enable_dependencies
 *   If TRUE, dependencies will automatically be added and enabled in the
 *   correct order. This incurs a significant performance cost, so use FALSE
 *   if you know $module_list is already complete and in the correct order.
 *
 * @return
 *   FALSE if one or more dependencies are missing, TRUE otherwise.
 *
 * @see hook_install()
 * @see hook_enable()
 * @see hook_modules_installed()
 * @see hook_modules_enabled()
 */
function module_enable($module_list, $enable_dependencies = TRUE) {
  if ($enable_dependencies) {
    // Get all module data so we can find dependencies and sort.
    $module_data = system_rebuild_module_data();
    // Create an associative array with weights as values.
    $module_list = array_flip(array_values($module_list));

    while (list($module) = each($module_list)) {
      if (!isset($module_data[$module])) {
        // This module is not found in the filesystem, abort.
        return FALSE;
      }
      if ($module_data[$module]->status) {
        // Skip already enabled modules.
        unset($module_list[$module]);
        continue;
      }
      $module_list[$module] = $module_data[$module]->sort;

      // Add dependencies to the list, with a placeholder weight.
      // The new modules will be processed as the while loop continues.
      foreach (array_keys($module_data[$module]->requires) as $dependency) {
        if (!isset($module_list[$dependency])) {
          $module_list[$dependency] = 0;
        }
      }
    }

    if (!$module_list) {
      // Nothing to do. All modules already enabled.
      return TRUE;
    }

    // Sort the module list by pre-calculated weights.
    arsort($module_list);
    $module_list = array_keys($module_list);
  }

  // Required for module installation checks.
  include_once DRUPAL_ROOT . '/core/includes/install.inc';

  $modules_installed = array();
  $modules_enabled = array();
  $schema_store = Drupal::keyValue('system.schema');
  $module_config = config('system.module');
  $disabled_config = config('system.module.disabled');
  $module_handler = drupal_container()->get('module_handler');
  foreach ($module_list as $module) {
    // Only process modules that are not already enabled.
    // A module is only enabled if it is configured as enabled. Custom or
    // overridden module handlers might contain the module already, which means
    // that it might be loaded, but not necessarily installed or enabled.
    $enabled = $module_config->get("enabled.$module") !== NULL;
    if (!$enabled) {
      $weight = $disabled_config->get($module);
      if ($weight === NULL) {
        $weight = 0;
      }
      $module_config
        ->set("enabled.$module", $weight)
        ->set('enabled', module_config_sort($module_config->get('enabled')))
        ->save();
      $disabled_config
        ->clear($module)
        ->save();

      // Prepare the new module list, sorted by weight, including filenames.
      // This list is used for both the ModuleHandler and DrupalKernel. It needs
      // to be kept in sync between both. A DrupalKernel reboot or rebuild will
      // automatically re-instantiate a new ModuleHandler that uses the new
      // module list of the kernel. However, DrupalKernel does not cause any
      // modules to be loaded.
      // Furthermore, the currently active (fixed) module list can be different
      // from the configured list of enabled modules. For all active modules not
      // contained in the configured enabled modules, we assume a weight of 0.
      $current_module_filenames = $module_handler->getModuleList();
      $current_modules = array_fill_keys(array_keys($current_module_filenames), 0);
      $current_modules = module_config_sort(array_merge($current_modules, $module_config->get('enabled')));
      $module_filenames = array();
      foreach ($current_modules as $name => $weight) {
        if (isset($current_module_filenames[$name])) {
          $filename = $current_module_filenames[$name];
        }
        else {
          $filename = drupal_get_filename('module', $name);
        }
        $module_filenames[$name] = $filename;
      }

      // Update the module handler in order to load the module's code.
      // This allows the module to participate in hooks and its existence to be
      // discovered by other modules.
      // The current ModuleHandler instance is obsolete with the kernel rebuild
      // below.
      $module_handler->setModuleList($module_filenames);
      $module_handler->load($module);
      module_load_install($module);

      // Flush theme info caches, since (testing) modules can implement
      // hook_system_theme_info() to register additional themes.
      system_list_reset();
      // Refresh the list of modules that implement bootstrap hooks.
      // @see bootstrap_hooks()
      _system_update_bootstrap_status();

      // Update the kernel to include it.
      // This reboots the kernel to register the module's bundle and its
      // services in the service container. The $module_filenames argument is
      // taken over as %container.modules% parameter, which is passed to a fresh
      // ModuleHandler instance upon first retrieval.
      // @todo install_begin_request() creates a container without a kernel.
      if ($kernel = drupal_container()->get('kernel', ContainerInterface::NULL_ON_INVALID_REFERENCE)) {
        $kernel->updateModules($module_filenames, $module_filenames);
      }

      // Refresh the schema to include it.
      drupal_get_schema(NULL, TRUE);
      // Update the theme registry to include it.
      drupal_theme_rebuild();

      // Allow modules to react prior to the installation of a module.
      module_invoke_all('modules_preinstall', array($module));

      // Clear the entity info cache before importing new configuration.
      entity_info_cache_clear();

      // Now install the module if necessary.
      if (drupal_get_installed_schema_version($module, TRUE) == SCHEMA_UNINSTALLED) {
        drupal_install_schema($module);

        // Set the schema version to the number of the last update provided
        // by the module.
        $versions = drupal_get_schema_versions($module);
        $version = $versions ? max($versions) : SCHEMA_INSTALLED;

        // Install default configuration of the module.
        config_install_default_config('module', $module);

        // If the module has no current updates, but has some that were
        // previously removed, set the version to the value of
        // hook_update_last_removed().
        if ($last_removed = module_invoke($module, 'update_last_removed')) {
          $version = max($version, $last_removed);
        }
        drupal_set_installed_schema_version($module, $version);
        // Allow the module to perform install tasks.
        module_invoke($module, 'install');
        // Record the fact that it was installed.
        $modules_installed[] = $module;
        watchdog('system', '%module module installed.', array('%module' => $module), WATCHDOG_INFO);
      }

      // Allow modules to react prior to the enabling of a module.
      entity_info_cache_clear();
      module_invoke_all('modules_preenable', array($module));

      // Enable the module.
      module_invoke($module, 'enable');

      // Record the fact that it was enabled.
      $modules_enabled[] = $module;
      watchdog('system', '%module module enabled.', array('%module' => $module), WATCHDOG_INFO);
    }
  }

  // If any modules were newly installed, invoke hook_modules_installed().
  if (!empty($modules_installed)) {
    module_invoke_all('modules_installed', $modules_installed);
  }

  // If any modules were newly enabled, invoke hook_modules_enabled().
  if (!empty($modules_enabled)) {
    module_invoke_all('modules_enabled', $modules_enabled);
  }

  return TRUE;
}

/**
 * Disables a given set of modules.
 *
 * @param $module_list
 *   An array of module names.
 * @param $disable_dependents
 *   If TRUE, dependent modules will automatically be added and disabled in the
 *   correct order. This incurs a significant performance cost, so use FALSE
 *   if you know $module_list is already complete and in the correct order.
 */
function module_disable($module_list, $disable_dependents = TRUE) {
  if ($disable_dependents) {
    // Get all module data so we can find dependents and sort.
    $module_data = system_rebuild_module_data();
    // Create an associative array with weights as values.
    $module_list = array_flip(array_values($module_list));

    $profile = drupal_get_profile();
    while (list($module) = each($module_list)) {
      if (!isset($module_data[$module]) || !$module_data[$module]->status) {
        // This module doesn't exist or is already disabled, skip it.
        unset($module_list[$module]);
        continue;
      }
      $module_list[$module] = $module_data[$module]->sort;

      // Add dependent modules to the list, with a placeholder weight.
      // The new modules will be processed as the while loop continues.
      foreach ($module_data[$module]->required_by as $dependent => $dependent_data) {
        if (!isset($module_list[$dependent]) && $dependent != $profile) {
          $module_list[$dependent] = 0;
        }
      }
    }

    // Sort the module list by pre-calculated weights.
    asort($module_list);
    $module_list = array_keys($module_list);
  }

  $invoke_modules = array();

  $module_config = config('system.module');
  $disabled_config = config('system.module.disabled');
  $module_handler = drupal_container()->get('module_handler');
  foreach ($module_list as $module) {
    // Only process modules that are enabled.
    // A module is only enabled if it is configured as enabled. Custom or
    // overridden module handlers might contain the module already, which means
    // that it might be loaded, but not necessarily installed or enabled.
    $enabled = $module_config->get("enabled.$module") !== NULL;
    if ($enabled) {
      module_load_install($module);
      module_invoke($module, 'disable');

      $disabled_config
        ->set($module, $module_config->get($module))
        ->save();
      $module_config
        ->clear("enabled.$module")
        ->save();

      // Update the module handler to remove the module.
      // The current ModuleHandler instance is obsolete with the kernel rebuild
      // below.
      $module_filenames = $module_handler->getModuleList();
      unset($module_filenames[$module]);
      $module_handler->setModuleList($module_filenames);

      // Record the fact that it was disabled.
      $invoke_modules[] = $module;
      watchdog('system', '%module module disabled.', array('%module' => $module), WATCHDOG_INFO);
    }
  }

  if (!empty($invoke_modules)) {
    // @todo Most of the following should happen in above loop already.

    // Refresh the system list to exclude the disabled modules.
    // @todo Only needed to rebuild theme info.
    // @see system_list_reset()
    system_list_reset();

    entity_info_cache_clear();

    // Invoke hook_modules_disabled before disabling modules,
    // so we can still call module hooks to get information.
    module_invoke_all('modules_disabled', $invoke_modules);
    _system_update_bootstrap_status();

    // Update the kernel to exclude the disabled modules.
    $enabled = $module_handler->getModuleList();
    drupal_container()->get('kernel')->updateModules($enabled, $enabled);

    // Update the theme registry to remove the newly-disabled module.
    drupal_theme_rebuild();
  }
}

/**
 * Uninstalls a given list of modules.
 *
 * @param $module_list
 *   The modules to uninstall.
 * @param $uninstall_dependents
 *   If TRUE, the function will check that all modules which depend on the
 *   passed-in module list either are already uninstalled or contained in the
 *   list, and it will ensure that the modules are uninstalled in the correct
 *   order. This incurs a significant performance cost, so use FALSE if you
 *   know $module_list is already complete and in the correct order.
 *
 * @return
 *   FALSE if one or more dependent modules are missing from the list, TRUE
 *   otherwise.
 */
function module_uninstall($module_list = array(), $uninstall_dependents = TRUE) {
  if ($uninstall_dependents) {
    // Get all module data so we can find dependents and sort.
    $module_data = system_rebuild_module_data();
    // Create an associative array with weights as values.
    $module_list = array_flip(array_values($module_list));

    $profile = drupal_get_profile();
    while (list($module) = each($module_list)) {
      if (!isset($module_data[$module]) || drupal_get_installed_schema_version($module) == SCHEMA_UNINSTALLED) {
        // This module doesn't exist or is already uninstalled, skip it.
        unset($module_list[$module]);
        continue;
      }
      $module_list[$module] = $module_data[$module]->sort;

      // If the module has any dependents which are not already uninstalled and
      // not included in the passed-in list, abort. It is not safe to uninstall
      // them automatically because uninstalling a module is a destructive
      // operation.
      foreach (array_keys($module_data[$module]->required_by) as $dependent) {
        if (!isset($module_list[$dependent]) && drupal_get_installed_schema_version($dependent) != SCHEMA_UNINSTALLED && $dependent != $profile) {
          return FALSE;
        }
      }
    }

    // Sort the module list by pre-calculated weights.
    asort($module_list);
    $module_list = array_keys($module_list);
  }

  $schema_store = Drupal::keyValue('system.schema');
  $disabled_config = config('system.module.disabled');
  foreach ($module_list as $module) {
    // Uninstall the module.
    module_load_install($module);
    module_invoke($module, 'uninstall');
    drupal_uninstall_schema($module);

    // Remove all configuration belonging to the module.
    config_uninstall_default_config('module', $module);

    // Remove any cache bins defined by the module.
    $service_yaml_file = drupal_get_path('module', $module) . "/$module.services.yml";
    if (file_exists($service_yaml_file)) {
      $parser = new Parser;
      $definitions = $parser->parse(file_get_contents($service_yaml_file));
      if (isset($definitions['services'])) {
        foreach ($definitions['services'] as $id => $definition) {
          if (isset($definition['tags'])) {
            foreach ($definition['tags'] as $tag) {
              // This works for the default cache registration and even in some
              // cases when a non-default "super" factory is used. That should
              // be extremely rare.
              if ($tag['name'] == 'cache.bin' && isset($definition['factory_service']) && isset($definition['factory_method']) && !empty($definition['arguments'])) {
                try {
                  $factory = Drupal::service($definition['factory_service']);
                  if (method_exists($factory, $definition['factory_method'])) {
                    $backend = call_user_func_array(array($factory, $definition['factory_method']), $definition['arguments']);
                    if ($backend instanceof CacheBackendInterface) {
                      $backend->removeBin();
                    }
                  }
                }
                catch (Exception $e) {
                  watchdog_exception('system', $e, 'Failed to remove cache bin defined by the service %id.', array('%id' => $id));
                }
              }
            }
          }
        }
      }
    }

    watchdog('system', '%module module uninstalled.', array('%module' => $module), WATCHDOG_INFO);
    $schema_store->delete($module);
    $disabled_config->clear($module);
  }
  $disabled_config->save();
  drupal_get_installed_schema_version(NULL, TRUE);

  if (!empty($module_list)) {
    // Call hook_module_uninstall to let other modules act
    module_invoke_all('modules_uninstalled', $module_list);
  }

  return TRUE;
}

/**
 * @defgroup hooks Hooks
 * @{
 * Allow modules to interact with the Drupal core.
 *
 * Drupal's module system is based on the concept of "hooks". A hook is a PHP
 * function that is named foo_bar(), where "foo" is the name of the module
 * (whose filename is thus foo.module) and "bar" is the name of the hook. Each
 * hook has a defined set of parameters and a specified result type.
 *
 * To extend Drupal, a module need simply implement a hook. When Drupal wishes
 * to allow intervention from modules, it determines which modules implement a
 * hook and calls that hook in all enabled modules that implement it.
 *
 * The available hooks to implement are explained here in the Hooks section of
 * the developer documentation. The string "hook" is used as a placeholder for
 * the module name in the hook definitions. For example, if the module file is
 * called example.module, then hook_help() as implemented by that module would
 * be defined as example_help().
 *
 * The example functions included are not part of the Drupal core, they are
 * just models that you can modify. Only the hooks implemented within modules
 * are executed when running Drupal.
 *
 * @see themeable
 * @see callbacks
 *
 * @} End of "defgroup hooks".
 */

 /**
  * @defgroup callbacks Callbacks
  * @{
  * Callback function signatures.
  *
  * Drupal's API sometimes uses callback functions to allow you to define how
  * some type of processing happens. A callback is a function with a defined
  * signature, which you define in a module. Then you pass the function name as
  * a parameter to a Drupal API function or return it as part of a hook
  * implementation return value, and your function is called at an appropriate
  * time. For instance, when setting up batch processing you might need to
  * provide a callback function for each processing step and/or a callback for
  * when processing is finished; you would do that by defining these functions
  * and passing their names into the batch setup function.
  *
  * Callback function signatures, like hook definitions, are described by
  * creating and documenting dummy functions in a *.api.php file; normally, the
  * dummy callback function's name should start with "callback_", and you should
  * document the parameters and return value and provide a sample function body.
  * Then your API documentation can refer to this callback function in its
  * documentation. A user of your API can usually name their callback function
  * anything they want, although a standard name would be to replace "callback_"
  * with the module name.
  *
  * @see hooks
  * @see themeable
  *
  * @} End of "defgroup callbacks".
  */

/**
 * Returns an array of modules required by core.
 */
function drupal_required_modules() {
  $files = drupal_system_listing('/^' . DRUPAL_PHP_FUNCTION_PATTERN . '\.info.yml$/', 'modules');
  $required = array();

  // An installation profile is required and one must always be loaded.
  $required[] = drupal_get_profile();

  foreach ($files as $name => $file) {
    $info = drupal_parse_info_file($file->uri);
    if (!empty($info) && !empty($info['required']) && $info['required']) {
      $required[] = $name;
    }
  }

  return $required;
}

/**
 * Sets weight of a particular module.
 *
 * The weight of uninstalled modules cannot be changed.
 *
 * @param string $module
 *   The name of the module (without the .module extension).
 * @param int $weight
 *   An integer representing the weight of the module.
 */
function module_set_weight($module, $weight) {
  // Update the module weight in the config file that contains it.
  $module_config = config('system.module');
  if ($module_config->get("enabled.$module") !== NULL) {
    $module_config
      ->set("enabled.$module", $weight)
      ->set('enabled', module_config_sort($module_config->get('enabled')))
      ->save();

    // Prepare the new module list, sorted by weight, including filenames.
    // @see module_enable()
    $module_handler = drupal_container()->get('module_handler');
    $current_module_filenames = $module_handler->getModuleList();
    $current_modules = array_fill_keys(array_keys($current_module_filenames), 0);
    $current_modules = module_config_sort(array_merge($current_modules, $module_config->get('enabled')));
    $module_filenames = array();
    foreach ($current_modules as $name => $weight) {
      $module_filenames[$name] = $current_module_filenames[$name];
    }
    // Update the module list in the extension handler.
    $module_handler->setModuleList($module_filenames);
    return;
  }
  $disabled_config = config('system.module.disabled');
  if ($disabled_config->get($module) !== NULL) {
    $disabled_config
      ->set($module, $weight)
      ->save();
    return;
  }
}

/**
 * Sorts the configured list of enabled modules.
 *
 * The list of enabled modules is expected to be ordered by weight and name.
 * The list is always sorted on write to avoid the overhead on read.
 *
 * @param array $data
 *   An array of module configuration data.
 *
 * @return array
 *   An array of module configuration data sorted by weight and name.
 */
function module_config_sort($data) {
  // PHP array sorting functions such as uasort() do not work with both keys and
  // values at the same time, so we achieve weight and name sorting by computing
  // strings with both information concatenated (weight first, name second) and
  // use that as a regular string sort reference list via array_multisort(),
  // compound of "[sign-as-integer][padded-integer-weight][name]"; e.g., given
  // two modules and weights (spaces added for clarity):
  // - Block with weight -5: 0 0000000000000000005 block
  // - Node  with weight  0: 1 0000000000000000000 node
  $sort = array();
  foreach ($data as $name => $weight) {
    // Prefix negative weights with 0, positive weights with 1.
    // +/- signs cannot be used, since + (ASCII 43) is before - (ASCII 45).
    $prefix = (int) ($weight >= 0);
    // The maximum weight is PHP_INT_MAX, so pad all weights to 19 digits.
    $sort[] = $prefix . sprintf('%019d', abs($weight)) . $name;
  }
  array_multisort($sort, SORT_STRING, $data);
  return $data;
}
