<?php

/**
 * @file
 * Contains Drupal\Core\Extension\ModuleHandler.
 */

namespace Drupal\Core\Extension;

use Drupal\Component\Graph\Graph;
use Symfony\Component\Yaml\Parser;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class that manages modules in a Drupal installation.
 */
class ModuleHandler implements ModuleHandlerInterface {

  /**
   * List of loaded files.
   *
   * @var array
   *   An associative array whose keys are file paths of loaded files, relative
   *   to the application's root directory.
   */
  protected $loadedFiles;

  /**
   * List of installed modules.
   *
   * @var \Drupal\Core\Extension\Extension[]
   */
  protected $moduleList;

  /**
   * Boolean indicating whether modules have been loaded.
   *
   * @var bool
   */
  protected $loaded = FALSE;

  /**
   * List of hook implementations keyed by hook name.
   *
   * @var array
   */
  protected $implementations;

  /**
   * Information returned by hook_hook_info() implementations.
   *
   * @var array
   */
  protected $hookInfo;

  /**
   * List of alter hook implementations keyed by hook name(s).
   *
   * @var array
   */
  protected $alterFunctions;

  /**
   * Constructs a ModuleHandler object.
   *
   * @param array $module_list
   *   An associative array whose keys are the names of installed modules and
   *   whose values are Extension class parameters. This is normally the
   *   %container.modules% parameter being set up by DrupalKernel.
   *
   * @see \Drupal\Core\DrupalKernel
   * @see \Drupal\Core\CoreServiceProvider
   */
  public function __construct(array $module_list = array()) {
    $this->moduleList = array();
    foreach ($module_list as $name => $module) {
      $this->moduleList[$name] = new Extension($module['type'], $module['pathname'], $module['filename']);
    }
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::load().
   */
  public function load($name) {
    if (isset($this->loadedFiles[$name])) {
      return TRUE;
    }

    if (isset($this->moduleList[$name])) {
      $this->moduleList[$name]->load();
      $this->loadedFiles[$name] = TRUE;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::loadAll().
   */
  public function loadAll() {
    if (!$this->loaded) {
      foreach ($this->moduleList as $name => $module) {
        $this->load($name);
      }
      $this->loaded = TRUE;
    }
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::reload().
   */
  public function reload() {
    $this->loaded = FALSE;
    $this->loadAll();
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::isLoaded().
   */
  public function isLoaded() {
    return $this->loaded;
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::getModuleList().
   */
  public function getModuleList() {
    return $this->moduleList;
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::setModuleList().
   */
  public function setModuleList(array $module_list = array()) {
    $this->moduleList = $module_list;
    // Reset the implementations, so a new call triggers a reloading of the
    // available hooks.
    $this->resetImplementations();
  }

  /**
   * {@inheritdoc}
   */
  public function addModule($name, $path) {
    $this->add('module', $name, $path);
  }

  /**
   * {@inheritdoc}
   */
  public function addProfile($name, $path) {
    $this->add('profile', $name, $path);
  }

  /**
   * Adds a module or profile to the list of currently active modules.
   *
   * @param string $type
   *   The extension type; either 'module' or 'profile'.
   * @param string $name
   *   The module name; e.g., 'node'.
   * @param string $path
   *   The module path; e.g., 'core/modules/node'.
   */
  protected function add($type, $name, $path) {
    $pathname = "$path/$name.info.yml";
    $filename = file_exists("$path/$name.$type") ? "$name.$type" : NULL;
    $this->moduleList[$name] = new Extension($type, $pathname, $filename);
    $this->resetImplementations();
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::buildModuleDependencies().
   */
  public function buildModuleDependencies(array $modules) {
    foreach ($modules as $module) {
      $graph[$module->getName()]['edges'] = array();
      if (isset($module->info['dependencies']) && is_array($module->info['dependencies'])) {
        foreach ($module->info['dependencies'] as $dependency) {
          $dependency_data = static::parseDependency($dependency);
          $graph[$module->getName()]['edges'][$dependency_data['name']] = $dependency_data;
        }
      }
    }
    $graph_object = new Graph($graph);
    $graph = $graph_object->searchAndSort();
    foreach ($graph as $module_name => $data) {
      $modules[$module_name]->required_by = isset($data['reverse_paths']) ? $data['reverse_paths'] : array();
      $modules[$module_name]->requires = isset($data['paths']) ? $data['paths'] : array();
      $modules[$module_name]->sort = $data['weight'];
    }
    return $modules;
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::moduleExists().
   */
  public function moduleExists($module) {
    return isset($this->moduleList[$module]);
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::loadAllIncludes().
   */
  public function loadAllIncludes($type, $name = NULL) {
    foreach ($this->moduleList as $module => $filename) {
      $this->loadInclude($module, $type, $name);
    }
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::loadInclude().
   */
  public function loadInclude($module, $type, $name = NULL) {
    if ($type == 'install') {
      // Make sure the installation API is available
      include_once DRUPAL_ROOT . '/core/includes/install.inc';
    }

    $name = $name ?: $module;
    if (isset($this->moduleList[$module])) {
      $file = DRUPAL_ROOT . '/' . $this->moduleList[$module]->getPath() . "/$name.$type";
      if (is_file($file)) {
        require_once $file;
        return $file;
      }
    }

    return FALSE;
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::getHookInfo().
   */
  public function getHookInfo() {
    if (isset($this->hookInfo)) {
      return $this->hookInfo;
    }
    $this->hookInfo = array();
    // We can't use $this->invokeAll() here or it would cause an infinite
    // loop.
    // Make sure that the modules are loaded before checking.
    $this->reload();
    foreach ($this->moduleList as $module => $filename) {
      $function = $module . '_hook_info';
      if (function_exists($function)) {
        $result = $function();
        if (isset($result) && is_array($result)) {
          $this->hookInfo = NestedArray::mergeDeep($this->hookInfo, $result);
        }
      }
    }
    return $this->hookInfo;
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::getImplementations().
   */
  public function getImplementations($hook) {
    $implementations = $this->getImplementationInfo($hook);
    return array_keys($implementations);
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::resetImplementations().
   */
  public function resetImplementations() {
    $this->implementations = NULL;
    $this->hookInfo = NULL;
    $this->alterFunctions = NULL;
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::implementsHook().
   */
  public function implementsHook($module, $hook) {
    $function = $module . '_' . $hook;
    if (function_exists($function)) {
      return TRUE;
    }
    // If the hook implementation does not exist, check whether it lives in an
    // optional include file registered via hook_hook_info().
    $hook_info = $this->getHookInfo();
    if (isset($hook_info[$hook]['group'])) {
      $this->loadInclude($module, 'inc', $module . '.' . $hook_info[$hook]['group']);
      if (function_exists($function)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::invoke().
   */
  public function invoke($module, $hook, array $args = array()) {
    if (!$this->implementsHook($module, $hook)) {
      return;
    }
    $function = $module . '_' . $hook;
    return call_user_func_array($function, $args);
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::invokeAll().
   */
  public function invokeAll($hook, array $args = array()) {
    $return = array();
    $implementations = $this->getImplementations($hook);
    foreach ($implementations as $module) {
      $function = $module . '_' . $hook;
      if (function_exists($function)) {
        $result = call_user_func_array($function, $args);
        if (isset($result) && is_array($result)) {
          $return = NestedArray::mergeDeep($return, $result);
        }
        elseif (isset($result)) {
          $return[] = $result;
        }
      }
    }

    return $return;
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::alter().
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL) {
    // Most of the time, $type is passed as a string, so for performance,
    // normalize it to that. When passed as an array, usually the first item in
    // the array is a generic type, and additional items in the array are more
    // specific variants of it, as in the case of array('form', 'form_FORM_ID').
    if (is_array($type)) {
      $cid = implode(',', $type);
      $extra_types = $type;
      $type = array_shift($extra_types);
      // Allow if statements in this function to use the faster isset() rather
      // than !empty() both when $type is passed as a string, or as an array with
      // one item.
      if (empty($extra_types)) {
        unset($extra_types);
      }
    }
    else {
      $cid = $type;
    }

    // Some alter hooks are invoked many times per page request, so store the
    // list of functions to call, and on subsequent calls, iterate through them
    // quickly.
    if (!isset($this->alterFunctions[$cid])) {
      $this->alterFunctions[$cid] = array();
      $hook = $type . '_alter';
      $modules = $this->getImplementations($hook);
      if (!isset($extra_types)) {
        // For the more common case of a single hook, we do not need to call
        // function_exists(), since $this->getImplementations() returns only modules with
        // implementations.
        foreach ($modules as $module) {
          $this->alterFunctions[$cid][] = $module . '_' . $hook;
        }
      }
      else {
        // For multiple hooks, we need $modules to contain every module that
        // implements at least one of them.
        $extra_modules = array();
        foreach ($extra_types as $extra_type) {
          $extra_modules = array_merge($extra_modules, $this->getImplementations($extra_type . '_alter'));
        }
        // If any modules implement one of the extra hooks that do not implement
        // the primary hook, we need to add them to the $modules array in their
        // appropriate order. $this->getImplementations() can only return ordered
        // implementations of a single hook. To get the ordered implementations
        // of multiple hooks, we mimic the $this->getImplementations() logic of first
        // ordering by $this->getModuleList(), and then calling
        // $this->alter('module_implements').
        if (array_diff($extra_modules, $modules)) {
          // Merge the arrays and order by getModuleList().
          $modules = array_intersect(array_keys($this->moduleList), array_merge($modules, $extra_modules));
          // Since $this->getImplementations() already took care of loading the necessary
          // include files, we can safely pass FALSE for the array values.
          $implementations = array_fill_keys($modules, FALSE);
          // Let modules adjust the order solely based on the primary hook. This
          // ensures the same module order regardless of whether this if block
          // runs. Calling $this->alter() recursively in this way does not result
          // in an infinite loop, because this call is for a single $type, so we
          // won't end up in this code block again.
          $this->alter('module_implements', $implementations, $hook);
          $modules = array_keys($implementations);
        }
        foreach ($modules as $module) {
          // Since $modules is a merged array, for any given module, we do not
          // know whether it has any particular implementation, so we need a
          // function_exists().
          $function = $module . '_' . $hook;
          if (function_exists($function)) {
            $this->alterFunctions[$cid][] = $function;
          }
          foreach ($extra_types as $extra_type) {
            $function = $module . '_' . $extra_type . '_alter';
            if (function_exists($function)) {
              $this->alterFunctions[$cid][] = $function;
            }
          }
        }
      }
      // Allow the theme to alter variables after the theme system has been
      // initialized.
      global $theme, $base_theme_info;
      if (isset($theme)) {
        $theme_keys = array();
        foreach ($base_theme_info as $base) {
          $theme_keys[] = $base->getName();
        }
        $theme_keys[] = $theme;
        foreach ($theme_keys as $theme_key) {
          $function = $theme_key . '_' . $hook;
          if (function_exists($function)) {
            $this->alterFunctions[$cid][] = $function;
          }
          if (isset($extra_types)) {
            foreach ($extra_types as $extra_type) {
              $function = $theme_key . '_' . $extra_type . '_alter';
              if (function_exists($function)) {
                $this->alterFunctions[$cid][] = $function;
              }
            }
          }
        }
      }
    }

    foreach ($this->alterFunctions[$cid] as $function) {
      $function($data, $context1, $context2);
    }
  }

  /**
   * Provides information about modules' implementations of a hook.
   *
   * @param string $hook
   *   The name of the hook (e.g. "help" or "menu").
   *
   * @return array
   *   An array whose keys are the names of the modules which are implementing
   *   this hook and whose values are either an array of information from
   *   hook_hook_info() or FALSE if the implementation is in the module file.
   */
  protected function getImplementationInfo($hook) {
    if (isset($this->implementations[$hook])) {
      return $this->implementations[$hook];
    }
    $this->implementations[$hook] = array();
    $hook_info = $this->getHookInfo();
    foreach ($this->moduleList as $module => $filename) {
      $include_file = isset($hook_info[$hook]['group']) && $this->loadInclude($module, 'inc', $module . '.' . $hook_info[$hook]['group']);
      // Since $this->implementsHook() may needlessly try to load the include
      // file again, function_exists() is used directly here.
      if (function_exists($module . '_' . $hook)) {
        $this->implementations[$hook][$module] = $include_file ? $hook_info[$hook]['group'] : FALSE;
      }
    }
    // Allow modules to change the weight of specific implementations but avoid
    // an infinite loop.
    if ($hook != 'module_implements_alter') {
      $this->alter('module_implements', $this->implementations[$hook], $hook);
    }
    return $this->implementations[$hook];
  }

  /**
   * Parses a dependency for comparison by drupal_check_incompatibility().
   *
   * @param $dependency
   *   A dependency string, for example 'foo (>=8.x-4.5-beta5, 3.x)'.
   *
   * @return
   *   An associative array with three keys:
   *   - 'name' includes the name of the thing to depend on (e.g. 'foo').
   *   - 'original_version' contains the original version string (which can be
   *     used in the UI for reporting incompatibilities).
   *   - 'versions' is a list of associative arrays, each containing the keys
   *     'op' and 'version'. 'op' can be one of: '=', '==', '!=', '<>', '<',
   *     '<=', '>', or '>='. 'version' is one piece like '4.5-beta3'.
   *   Callers should pass this structure to drupal_check_incompatibility().
   *
   * @see drupal_check_incompatibility()
   */
  public static function parseDependency($dependency) {
    // We use named subpatterns and support every op that version_compare
    // supports. Also, op is optional and defaults to equals.
    $p_op = '(?<operation>!=|==|=|<|<=|>|>=|<>)?';
    // Core version is always optional: 8.x-2.x and 2.x is treated the same.
    $p_core = '(?:' . preg_quote(\Drupal::CORE_COMPATIBILITY) . '-)?';
    $p_major = '(?<major>\d+)';
    // By setting the minor version to x, branches can be matched.
    $p_minor = '(?<minor>(?:\d+|x)(?:-[A-Za-z]+\d+)?)';
    $value = array();
    $parts = explode('(', $dependency, 2);
    $value['name'] = trim($parts[0]);
    if (isset($parts[1])) {
      $value['original_version'] = ' (' . $parts[1];
      foreach (explode(',', $parts[1]) as $version) {
        if (preg_match("/^\s*$p_op\s*$p_core$p_major\.$p_minor/", $version, $matches)) {
          $op = !empty($matches['operation']) ? $matches['operation'] : '=';
          if ($matches['minor'] == 'x') {
            // Drupal considers "2.x" to mean any version that begins with
            // "2" (e.g. 2.0, 2.9 are all "2.x"). PHP's version_compare(),
            // on the other hand, treats "x" as a string; so to
            // version_compare(), "2.x" is considered less than 2.0. This
            // means that >=2.x and <2.x are handled by version_compare()
            // as we need, but > and <= are not.
            if ($op == '>' || $op == '<=') {
              $matches['major']++;
            }
            // Equivalence can be checked by adding two restrictions.
            if ($op == '=' || $op == '==') {
              $value['versions'][] = array('op' => '<', 'version' => ($matches['major'] + 1) . '.x');
              $op = '>=';
            }
          }
          $value['versions'][] = array('op' => $op, 'version' => $matches['major'] . '.' . $matches['minor']);
        }
      }
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function install(array $module_list, $enable_dependencies = TRUE) {
    $module_config = \Drupal::config('system.module');
    if ($enable_dependencies) {
      // Get all module data so we can find dependencies and sort.
      $module_data = system_rebuild_module_data();
      $module_list = $module_list ? array_combine($module_list, $module_list) : array();
      if (array_diff_key($module_list, $module_data)) {
        // One or more of the given modules doesn't exist.
        return FALSE;
      }

      // Only process currently uninstalled modules.
      $installed_modules = $module_config->get('enabled') ?: array();
      if (!$module_list = array_diff_key($module_list, $installed_modules)) {
        // Nothing to do. All modules already installed.
        return TRUE;
      }

      // Conditionally add the dependencies to the list of modules.
      if ($enable_dependencies) {
        // Add dependencies to the list. The new modules will be processed as the
        // while loop continues.
        while (list($module) = each($module_list)) {
          foreach (array_keys($module_data[$module]->requires) as $dependency) {
            if (!isset($module_data[$dependency])) {
              // The dependency does not exist.
              return FALSE;
            }

            // Skip already installed modules.
            if (!isset($module_list[$dependency]) && !isset($installed_modules[$dependency])) {
              $module_list[$dependency] = $dependency;
            }
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
    include_once DRUPAL_ROOT . '/core/includes/install.inc';

    $modules_installed = array();
    foreach ($module_list as $module) {
      $enabled = $module_config->get("enabled.$module") !== NULL;
      if (!$enabled) {
        // Throw an exception if the module name is too long.
        if (strlen($module) > DRUPAL_EXTENSION_NAME_MAX_LENGTH) {
          throw new ExtensionNameLengthException(format_string('Module name %name is over the maximum allowed length of @max characters.', array(
            '%name' => $module,
            '@max' => DRUPAL_EXTENSION_NAME_MAX_LENGTH,
          )));
        }

        $module_config
          ->set("enabled.$module", 0)
          ->set('enabled', module_config_sort($module_config->get('enabled')))
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
        $current_module_filenames = $this->getModuleList();
        $current_modules = array_fill_keys(array_keys($current_module_filenames), 0);
        $current_modules = module_config_sort(array_merge($current_modules, $module_config->get('enabled')));
        $module_filenames = array();
        foreach ($current_modules as $name => $weight) {
          if (isset($current_module_filenames[$name])) {
            $module_filenames[$name] = $current_module_filenames[$name];
          }
          else {
            $module_path = drupal_get_path('module', $name);
            $pathname = "$module_path/$name.info.yml";
            $filename = file_exists($module_path . "/$name.module") ? "$name.module" : NULL;
            $module_filenames[$name] = new Extension('module', $pathname, $filename);
          }
        }

        // Update the module handler in order to load the module's code.
        // This allows the module to participate in hooks and its existence to be
        // discovered by other modules.
        // The current ModuleHandler instance is obsolete with the kernel rebuild
        // below.
        $this->setModuleList($module_filenames);
        $this->load($module);
        module_load_install($module);

        // Clear the static cache of system_rebuild_module_data() to pick up the
        // new module, since it merges the installation status of modules into
        // its statically cached list.
        drupal_static_reset('system_rebuild_module_data');

        // Update the kernel to include it.
        // This reboots the kernel to register the module's bundle and its
        // services in the service container. The $module_filenames argument is
        // taken over as %container.modules% parameter, which is passed to a fresh
        // ModuleHandler instance upon first retrieval.
        // @todo install_begin_request() creates a container without a kernel.
        if ($kernel = \Drupal::service('kernel', ContainerInterface::NULL_ON_INVALID_REFERENCE)) {
          $kernel->updateModules($module_filenames, $module_filenames);
        }

        // Refresh the schema to include it.
        drupal_get_schema(NULL, TRUE);
        // Update the theme registry to include it.
        drupal_theme_rebuild();

        // Allow modules to react prior to the installation of a module.
        $this->invokeAll('module_preinstall', array($module));

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

        // Install default configuration of the module.
        \Drupal::service('config.installer')->installDefaultConfig('module', $module);

        // If the module has no current updates, but has some that were
        // previously removed, set the version to the value of
        // hook_update_last_removed().
        if ($last_removed = $this->invoke($module, 'update_last_removed')) {
          $version = max($version, $last_removed);
        }
        drupal_set_installed_schema_version($module, $version);

        // Record the fact that it was installed.
        $modules_installed[] = $module;

        // Allow the module to perform install tasks.
        $this->invoke($module, 'install');
        // Record the fact that it was installed.
        watchdog('system', '%module module installed.', array('%module' => $module), WATCHDOG_INFO);
      }
    }

    // If any modules were newly installed, invoke hook_modules_installed().
    if (!empty($modules_installed)) {
      $this->invokeAll('modules_installed', array($modules_installed));
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

    // Only process currently installed modules.
    $module_config = \Drupal::config('system.module');
    $installed_modules = $module_config->get('enabled') ?: array();
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
            $module_list[$dependent] = TRUE;
          }
        }
      }
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
    $schema_store = \Drupal::keyValue('system.schema');
    foreach ($module_list as $module) {
      // Allow modules to react prior to the uninstallation of a module.
      $this->invokeAll('module_preuninstall', array($module));

      // Uninstall the module.
      module_load_install($module);
      $this->invoke($module, 'uninstall');
      drupal_uninstall_schema($module);

      // Remove the module's entry from the config.
      $module_config->clear("enabled.$module")->save();

      // Remove all configuration belonging to the module.
      \Drupal::service('config.manager')->uninstall('module', $module);

      // Update the module handler to remove the module.
      // The current ModuleHandler instance is obsolete with the kernel rebuild
      // below.
      $module_filenames = $this->getModuleList();
      unset($module_filenames[$module]);
      $this->setModuleList($module_filenames);

      // Remove any potential cache bins provided by the module.
      $this->removeCacheBins($module);

      // Clear the static cache of system_rebuild_module_data() to pick up the
      // new module, since it merges the installation status of modules into
      // its statically cached list.
      drupal_static_reset('system_rebuild_module_data');

      \Drupal::getContainer()->get('plugin.cache_clearer')->clearCachedDefinitions();

      // Update the kernel to exclude the uninstalled modules.
      \Drupal::service('kernel')->updateModules($module_filenames, $module_filenames);

      // Update the theme registry to remove the newly uninstalled module.
      drupal_theme_rebuild();

      watchdog('system', '%module module uninstalled.', array('%module' => $module), WATCHDOG_INFO);

      $schema_store->delete($module);

      // Make sure any route data is also removed for this module.
      \Drupal::service('router.dumper')->dump(array('provider' => $module));
    }
    drupal_get_installed_schema_version(NULL, TRUE);

    // Let other modules react.
    $this->invokeAll('modules_uninstalled', array($module_list));

    drupal_flush_all_caches();

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
   * {@inheritdoc}
   */
  public function getModuleDirectories() {
    $dirs = array();
    foreach ($this->getModuleList() as $name => $module) {
      $dirs[$name] = DRUPAL_ROOT . '/' . $module->getPath();
    }
    return $dirs;
  }
}
