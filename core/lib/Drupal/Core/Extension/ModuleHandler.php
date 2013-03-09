<?php

/**
 * @file
 * Contains Drupal\Core\Extension\ModuleHandler.
 */

namespace Drupal\Core\Extension;

use Drupal\Component\Graph\Graph;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;

/**
 * Class that manages enabled modules in a Drupal installation.
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
   * List of enabled bootstrap modules.
   *
   * @var array
   */
  protected $bootstrapModules;

  /**
   * List of enabled modules.
   *
   * @var array
   *   An associative array whose keys are the names of the modules and whose
   *   values are the module filenames.
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
   *   An associative array whose keys are the names of enabled modules and
   *   whose values are the module filenames. This is normally the
   *   %container.modules% parameter being set up by DrupalKernel.
   *
   * @see \Drupal\Core\DrupalKernel
   * @see \Drupal\Core\CoreBundle
   */
  public function __construct(array $module_list = array()) {
    $this->moduleList = $module_list;
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::load().
   */
  public function load($name) {
    if (isset($this->loadedFiles[$name])) {
      return TRUE;
    }

    if (isset($this->moduleList[$name])) {
      $filename = $this->moduleList[$name];
      include_once DRUPAL_ROOT . '/' . $filename;
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
      foreach ($this->moduleList as $module => $filename) {
        $this->load($module);
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
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::loadBootstrapModules().
   */
  public function loadBootstrapModules() {
    if (!$this->loaded) {
      foreach ($this->getBootstrapModules() as $module) {
        $this->load($module);
      }
    }
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
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::getBootstrapModules().
   */
  public function getBootstrapModules() {
    // The basic module handler does not know anything about how to retrieve a
    // list of bootstrap modules.
    return array();
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::buildModuleDependencies().
   */
  public function buildModuleDependencies(array $modules) {
    foreach ($modules as $name => $module) {
      $graph[$module->name]['edges'] = array();
      if (isset($module->info['dependencies']) && is_array($module->info['dependencies'])) {
        foreach ($module->info['dependencies'] as $dependency) {
          $dependency_data = $this->parseDependency($dependency);
          $graph[$module->name]['edges'][$dependency_data['name']] = $dependency_data;
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
    $file = DRUPAL_ROOT . '/' . dirname($this->moduleList[$module]) . "/$name.$type";
    if (is_file($file)) {
      require_once $file;
      return $file;
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
  public function invoke($module, $hook, $args = array()) {
    if (!$this->implementsHook($module, $hook)) {
      return;
    }
    $function = $module . '_' . $hook;
    return call_user_func_array($function, $args);
  }

  /**
   * Implements \Drupal\Core\Extension\ModuleHandlerInterface::invokeAll().
   */
  public function invokeAll($hook, $args = array()) {
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
          $theme_keys[] = $base->name;
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
      // Since $this->hookImplements() may needlessly try to load the include
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
  protected function parseDependency($dependency) {
    // We use named subpatterns and support every op that version_compare
    // supports. Also, op is optional and defaults to equals.
    $p_op = '(?P<operation>!=|==|=|<|<=|>|>=|<>)?';
    // Core version is always optional: 8.x-2.x and 2.x is treated the same.
    $p_core = '(?:' . preg_quote(DRUPAL_CORE_COMPATIBILITY) . '-)?';
    $p_major = '(?P<major>\d+)';
    // By setting the minor version to x, branches can be matched.
    $p_minor = '(?P<minor>(?:\d+|x)(?:-[A-Za-z]+\d+)?)';
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
}
