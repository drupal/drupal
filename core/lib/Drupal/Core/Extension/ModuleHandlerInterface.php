<?php

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
   * Returns the list of currently active modules.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   An associative array whose keys are the names of the modules and whose
   *   values are Extension objects.
   */
  public function getModuleList();

  /**
   * Returns a module extension object from the currently active modules list.
   *
   * @param string $name
   *   The name of the module to return.
   *
   * @return \Drupal\Core\Extension\Extension
   *   An extension object.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   Thrown when the requested module does not exist.
   */
  public function getModule($name);

  /**
   * Sets an explicit list of currently active modules.
   *
   * @param \Drupal\Core\Extension\Extension[] $module_list
   *   An associative array whose keys are the names of the modules and whose
   *   values are Extension objects.
   */
  public function setModuleList(array $module_list = []);

  /**
   * Adds a module to the list of currently active modules.
   *
   * @param string $name
   *   The module name; e.g., 'node'.
   * @param string $path
   *   The module path; e.g., 'core/modules/node'.
   */
  public function addModule($name, $path);

  /**
   * Adds an installation profile to the list of currently active modules.
   *
   * @param string $name
   *   The profile name; e.g., 'standard'.
   * @param string $path
   *   The profile path; e.g., 'core/profiles/standard'.
   */
  public function addProfile($name, $path);

  /**
   * Determines which modules require and are required by each module.
   *
   * @param array $modules
   *   An array of module objects keyed by module name. Each object contains
   *   information discovered during a Drupal\Core\Extension\ExtensionDiscovery
   *   scan.
   *
   * @return
   *   The same array with the new keys for each module:
   *   - requires: An array with the keys being the modules that this module
   *     requires.
   *   - required_by: An array with the keys being the modules that will not work
   *     without this module.
   *
   * @see \Drupal\Core\Extension\ExtensionDiscovery
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
   *   $this->loadInclude('node', 'inc', 'content_types');
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
   * Write the hook implementation info to the cache.
   */
  public function writeCache();

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
   * @param array $args
   *   Arguments to pass to the hook implementation.
   *
   * @return mixed
   *   The return value of the hook implementation.
   */
  public function invoke($module, $hook, array $args = []);

  /**
   * Invokes a hook in all enabled modules that implement it.
   *
   * @param string $hook
   *   The name of the hook to invoke.
   * @param array $args
   *   Arguments to pass to the hook.
   *
   * @return array
   *   An array of return values of the hook implementations. If modules return
   *   arrays from their implementations, those are merged into one array
   *   recursively. Note: integer keys in arrays will be lost, as the merge is
   *   done using Drupal\Component\Utility\NestedArray::mergeDeepArray().
   */
  public function invokeAll($hook, array $args = []);

  /**
   * Invokes a deprecated hook in a particular module.
   *
   * Invoking a deprecated hook adds the behavior of triggering an
   * E_USER_DEPRECATED error if any implementations are found.
   *
   * API maintainers should use this method instead of invoke() when their hook
   * is deprecated. This method does not detect when a hook is deprecated.
   *
   * @param string $description
   *   Helpful text describing what to do instead of implementing this hook.
   * @param string $module
   *   The name of the module (without the .module extension).
   * @param string $hook
   *   The name of the hook to invoke.
   * @param array $args
   *   Arguments to pass to the hook implementation.
   *
   * @return mixed
   *   The return value of the hook implementation.
   *
   * @see \Drupal\Core\Extension\ModuleHandlerInterface::invoke()
   * @see https://www.drupal.org/core/deprecation#how-hook
   */
  public function invokeDeprecated($description, $module, $hook, array $args = []);

  /**
   * Invokes a deprecated hook in all enabled modules that implement it.
   *
   * Invoking a deprecated hook adds the behavior of triggering an
   * E_USER_DEPRECATED error if any implementations are found.
   *
   * API maintainers should use this method instead of invokeAll() when their
   * hook is deprecated. This method does not detect when a hook is deprecated.
   *
   * @param string $description
   *   Helpful text describing what to do instead of implementing this hook.
   * @param string $hook
   *   The name of the hook to invoke.
   * @param array $args
   *   Arguments to pass to the hook.
   *
   * @return array
   *   An array of return values of the hook implementations. If modules return
   *   arrays from their implementations, those are merged into one array
   *   recursively. Note: integer keys in arrays will be lost, as the merge is
   *   done using Drupal\Component\Utility\NestedArray::mergeDeepArray().
   *
   * @see \Drupal\Core\Extension\ModuleHandlerInterface::invokeAll()
   * @see https://www.drupal.org/core/deprecation#how-hook
   */
  public function invokeAllDeprecated($description, $hook, array $args = []);

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
   * Note that objects are always passed by reference. If it is absolutely
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
   * Passes alterable variables to deprecated hook_TYPE_alter() implementations.
   *
   * This method triggers an E_USER_DEPRECATED error if any implementations of
   * the alter hook are found. It is otherwise identical to alter().
   *
   * See the documentation for alter() for more details.
   *
   * @param string $description
   *   Helpful text describing what to do instead of implementing this alter
   *   hook.
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
   *
   * @see \Drupal\Core\Extension\ModuleHandlerInterface::alter()
   * @see https://www.drupal.org/core/deprecation#how-hook
   */
  public function alterDeprecated($description, $type, &$data, &$context1 = NULL, &$context2 = NULL);

  /**
   * Returns an array of directories for all enabled modules. Useful for
   * tasks such as finding a file that exists in all module directories.
   *
   * @return array
   */
  public function getModuleDirectories();

  /**
   * Gets the human readable name of a given module.
   *
   * @param string $module
   *   The machine name of the module which title should be shown.
   *
   * @return string
   *   Returns the human readable name of the module or the machine name passed
   *   in if no matching module is found.
   */
  public function getName($module);

}
