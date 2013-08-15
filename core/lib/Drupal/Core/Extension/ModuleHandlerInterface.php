<?php

/**
 * @file
 * Contains Drupal\Core\Extension\ModuleHandlerInterface.
 */

namespace Drupal\Core\Extension;

/**
 * Interface for classes that manage a set of enabled modules.
 *
 * Classes implementing this interface work with a fixed list of modules and are
 * responsible for loading module files and maintaining information about module
 * dependencies and hook implementations.
 */
interface ModuleHandlerInterface {

  /**
   * Includes a module's .module file.
   *
   * This prevents including a module more than once.
   *
   * @param string $name
   *   The name of the module to load.
   *
   * @return bool
   *   TRUE if the item is loaded or has already been loaded.
   */
  public function load($name);

  /**
   * Loads all enabled modules.
   */
  public function loadAll();

  /**
   * Returns whether all modules have been loaded.
   *
   * @return bool
   *   A Boolean indicating whether all modules have been loaded. This means all
   *   modules; the load status of bootstrap modules cannot be checked.
   */
  public function isLoaded();

  /**
   * Reloads all enabled modules.
   */
  public function reload();

  /**
   * Returns a list of currently active modules.
   *
   * @return array
   *   An associative array whose keys are the names of the modules and whose
   *   values are the module filenames.
   */
  public function getModuleList();

  /**
   * Explicitly sets the moduleList property to the passed in array of modules.
   *
   * @param array $module_list
   *   An associative array whose keys are the names of the modules and whose
   *   values are the module filenames.
   */
  public function setModuleList(array $module_list = array());

  /**
   * Determines which modules require and are required by each module.
   *
   * @param array $modules
   *   An array of module objects keyed by module name. Each object contains
   *   information discovered during a Drupal\Core\SystemListing scan.
   *
   * @return
   *   The same array with the new keys for each module:
   *   - requires: An array with the keys being the modules that this module
   *     requires.
   *   - required_by: An array with the keys being the modules that will not work
   *     without this module.
   *
   * @see \Drupal\Core\SystemListing
   */
  public function buildModuleDependencies(array $modules);

  /**
   * Determines whether a given module is enabled.
   *
   * @param string $module
   *   The name of the module (without the .module extension).
   *
   * @return bool
   *   TRUE if the module is both installed and enabled.
   */
  public function moduleExists($module);

  /**
   * Loads an include file for each enabled module.
   *
   * @param string $type
   *   The include file's type (file extension).
   * @param string $name
   *   (optional) The base file name (without the $type extension). If omitted,
   *   each module's name is used; i.e., "$module.$type" by default.
   */
  public function loadAllIncludes($type, $name = NULL);

  /**
   * Loads a module include file.
   *
   * Examples:
   * @code
   *   // Load node.admin.inc from the node module.
   *   $this->loadInclude('node', 'inc', 'node.admin');
   *   // Load content_types.inc from the node module.
   *   $this->loadInclude('node', 'inc', ''content_types');
   * @endcode
   *
   * @param string $module
   *   The module to which the include file belongs.
   * @param string $type
   *   The include file's type (file extension).
   * @param string $name
   *   (optional) The base file name (without the $type extension). If omitted,
   *   $module is used; i.e., resulting in "$module.$type" by default.
   *
   * @return string|false
   *   The name of the included file, if successful; FALSE otherwise.
   */
  public function loadInclude($module, $type, $name = NULL);

  /**
   * Retrieves a list of hooks that are declared through hook_hook_info().
   *
   * @return array
   *   An associative array whose keys are hook names and whose values are an
   *   associative array containing a group name. The structure of the array
   *   is the same as the return value of hook_hook_info().
   *
   * @see hook_hook_info()
   */
  public function getHookInfo();

  /**
   * Determines which modules are implementing a hook.
   *
   * @param string $hook
   *   The name of the hook (e.g. "help" or "menu").
   *
   * @return array
   *   An array with the names of the modules which are implementing this hook.
   */
  public function getImplementations($hook);

  /**
   * Resets the cached list of hook implementations.
   */
  public function resetImplementations();

  /**
   * Returns whether a given module implements a given hook.
   *
   * @param string $module
   *   The name of the module (without the .module extension).
   * @param string $hook
   *   The name of the hook (e.g. "help" or "menu").
   *
   * @return bool
   *   TRUE if the module is both installed and enabled, and the hook is
   *   implemented in that module.
   */
  public function implementsHook($module, $hook);

  /**
   * Invokes a hook in a particular module.
   *
   * @param string $module
   *   The name of the module (without the .module extension).
   * @param string $hook
   *   The name of the hook to invoke.
   * @param ...
   *   Arguments to pass to the hook implementation.
   *
   * @return mixed
   *   The return value of the hook implementation.
   */
  public function invoke($module, $hook, $args = array());

  /**
   * Invokes a hook in all enabled modules that implement it.
   *
   * @param string $hook
   *   The name of the hook to invoke.
   * @param ...
   *   Arguments to pass to the hook.
   *
   * @return array
   *   An array of return values of the hook implementations. If modules return
   *   arrays from their implementations, those are merged into one array.
   */
  public function invokeAll($hook, $args = array());

  /**
   * Passes alterable variables to specific hook_TYPE_alter() implementations.
   *
   * This dispatch function hands off the passed-in variables to type-specific
   * hook_TYPE_alter() implementations in modules. It ensures a consistent
   * interface for all altering operations.
   *
   * A maximum of 2 alterable arguments is supported. In case more arguments need
   * to be passed and alterable, modules provide additional variables assigned by
   * reference in the last $context argument:
   * @code
   *   $context = array(
   *     'alterable' => &$alterable,
   *     'unalterable' => $unalterable,
   *     'foo' => 'bar',
   *   );
   *   $this->alter('mymodule_data', $alterable1, $alterable2, $context);
   * @endcode
   *
   * Note that objects are always passed by reference in PHP5. If it is absolutely
   * required that no implementation alters a passed object in $context, then an
   * object needs to be cloned:
   * @code
   *   $context = array(
   *     'unalterable_object' => clone $object,
   *   );
   *   $this->alter('mymodule_data', $data, $context);
   * @endcode
   *
   * @param string|array $type
   *   A string describing the type of the alterable $data. 'form', 'links',
   *   'node_content', and so on are several examples. Alternatively can be an
   *   array, in which case hook_TYPE_alter() is invoked for each value in the
   *   array, ordered first by module, and then for each module, in the order of
   *   values in $type. For example, when Form API is using $this->alter() to
   *   execute both hook_form_alter() and hook_form_FORM_ID_alter()
   *   implementations, it passes array('form', 'form_' . $form_id) for $type.
   * @param mixed $data
   *   The variable that will be passed to hook_TYPE_alter() implementations to be
   *   altered. The type of this variable depends on the value of the $type
   *   argument. For example, when altering a 'form', $data will be a structured
   *   array. When altering a 'profile', $data will be an object.
   * @param mixed $context1
   *   (optional) An additional variable that is passed by reference.
   * @param mixed $context2
   *   (optional) An additional variable that is passed by reference. If more
   *   context needs to be provided to implementations, then this should be an
   *   associative array as described above.
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL);

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
  public function enable($module_list, $enable_dependencies = TRUE);

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
  public function disable($module_list, $disable_dependents = TRUE);

  /**
   * Uninstalls a given list of disabled modules.
   *
   * @param array $module_list
   *   The modules to uninstall. It is the caller's responsibility to ensure that
   *   all modules in this list have already been disabled before this function
   *   is called.
   * @param bool $uninstall_dependents
   *   (optional) If TRUE, the function will check that all modules which depend
   *   on the passed-in module list either are already uninstalled or contained in
   *   the list, and it will ensure that the modules are uninstalled in the
   *   correct order. This incurs a significant performance cost, so use FALSE if
   *   you know $module_list is already complete and in the correct order.
   *   Defaults to TRUE.
   *
   * @return bool
   *   Returns TRUE if the operation succeeds or FALSE if it aborts due to an
   *   unsafe condition, namely, $uninstall_dependents is TRUE and a module in
   *   $module_list has dependents which are not already uninstalled and not also
   *   included in $module_list).
   */
  public function uninstall($module_list = array(), $uninstall_dependents = TRUE);

  /**
   * Returns an array of directories for all enabled modules. Useful for
   * tasks such as finding a file that exists in all module directories.
   *
   * @return array
   */
  public function getModuleDirectories();

}
