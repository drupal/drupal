<?php

namespace Drupal\Core\Extension;

use Drupal\Component\Graph\Graph;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\Core\Hook\HookCollectorPass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
   * List of events which implement an alter hook keyed by hook name(s).
   *
   * @var array
   */
  protected array $alterEventListeners = [];

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * A list of module include file keys.
   *
   * @var array
   */
  protected $includeFileKeys = [];

  /**
   * Hook and module keyed list of listeners.
   *
   * @var array
   */
  protected array $invokeMap = [];

  /**
   * Constructs a ModuleHandler object.
   *
   * @param string $root
   *   The app root.
   * @param array $module_list
   *   An associative array whose keys are the names of installed modules and
   *   whose values are Extension class parameters. This is normally the
   *   %container.modules% parameter being set up by DrupalKernel.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param array $hookImplementationsMap
   *   An array keyed by hook, classname, method and the value is the module.
   * @param array $groupIncludes
   *   An array of .inc files to get helpers from.
   *
   * @see \Drupal\Core\DrupalKernel
   * @see \Drupal\Core\CoreServiceProvider
   */
  public function __construct($root, array $module_list, protected EventDispatcherInterface $eventDispatcher, protected array $hookImplementationsMap, protected array $groupIncludes = []) {
    $this->root = $root;
    $this->moduleList = [];
    foreach ($module_list as $name => $module) {
      $this->moduleList[$name] = new Extension($this->root, $module['type'], $module['pathname'], $module['filename']);
    }
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function reload() {
    $this->loaded = FALSE;
    $this->loadAll();
  }

  /**
   * {@inheritdoc}
   */
  public function isLoaded() {
    return $this->loaded;
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleList() {
    return $this->moduleList;
  }

  /**
   * {@inheritdoc}
   */
  public function getModule($name) {
    if (isset($this->moduleList[$name])) {
      return $this->moduleList[$name];
    }
    throw new UnknownExtensionException(sprintf('The module %s does not exist.', $name));
  }

  /**
   * {@inheritdoc}
   */
  public function setModuleList(array $module_list = []) {
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
    $php_file_path = $this->root . "/$path/$name.$type";
    $filename = file_exists($php_file_path) ? "$name.$type" : NULL;
    $this->moduleList[$name] = new Extension($this->root, $type, $pathname, $filename);
    $this->resetImplementations();
    $hook_collector = HookCollectorPass::collectAllHookImplementations([$name => ['pathname' => $pathname]]);
    // A module freshly added will not be registered on the container yet.
    // ProceduralCall service does not yet know about it.
    // Note in HookCollectorPass:
    // - $container->register(ProceduralCall::class, ProceduralCall::class)->addArgument($collector->includes);
    // Load all includes so the legacy section of invoke can handle hooks in includes.
    $hook_collector->loadAllIncludes();
    // Register procedural implementations.
    foreach ($hook_collector->getImplementations() as $hook => $moduleImplements) {
      foreach ($moduleImplements as $module => $classImplements) {
        foreach ($classImplements[ProceduralCall::class] ?? [] as $method) {
          $this->invokeMap[$hook][$module][] = $method;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildModuleDependencies(array $modules) {
    foreach ($modules as $module) {
      $graph[$module->getName()]['edges'] = [];
      if (isset($module->info['dependencies']) && is_array($module->info['dependencies'])) {
        foreach ($module->info['dependencies'] as $dependency) {
          $dependency_data = Dependency::createFromString($dependency);
          $graph[$module->getName()]['edges'][$dependency_data->getName()] = $dependency_data;
        }
      }
    }
    $graph_object = new Graph($graph ?? []);
    $graph = $graph_object->searchAndSort();
    foreach ($graph as $module_name => $data) {
      $modules[$module_name]->required_by = $data['reverse_paths'] ?? [];
      $modules[$module_name]->requires = $data['paths'] ?? [];
      $modules[$module_name]->sort = $data['weight'];
    }
    return $modules;
  }

  /**
   * {@inheritdoc}
   */
  public function moduleExists($module) {
    return isset($this->moduleList[$module]);
  }

  /**
   * {@inheritdoc}
   */
  public function loadAllIncludes($type, $name = NULL) {
    foreach ($this->moduleList as $module => $filename) {
      $this->loadInclude($module, $type, $name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadInclude($module, $type, $name = NULL) {
    if ($type == 'install') {
      // Make sure the installation API is available.
      include_once $this->root . '/core/includes/install.inc';
    }

    $name = $name ?: $module;
    $key = $type . ':' . $module . ':' . $name;
    if (isset($this->includeFileKeys[$key])) {
      return $this->includeFileKeys[$key];
    }
    if (isset($this->moduleList[$module])) {
      $file = $this->root . '/' . $this->moduleList[$module]->getPath() . "/$name.$type";
      if (is_file($file)) {
        require_once $file;
        $this->includeFileKeys[$key] = $file;
        return $file;
      }
      else {
        $this->includeFileKeys[$key] = FALSE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getHookInfo() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function resetImplementations() {
    $this->alterEventListeners = [];
  }

  /**
   * {@inheritdoc}
   */
  public function hasImplementations(string $hook, $modules = NULL): bool {
    $implementation_modules = array_keys($this->getHookListeners($hook));
    return (bool) (isset($modules) ? array_intersect($implementation_modules, (array) $modules) : $implementation_modules);
  }

  /**
   * {@inheritdoc}
   */
  public function invokeAllWith(string $hook, callable $callback): void {
    foreach ($this->getHookListeners($hook) as $module => $listeners) {
      foreach ($listeners as $listener) {
        $callback($listener, $module);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invoke($module, $hook, array $args = []) {
    if ($listeners = $this->getHookListeners($hook)[$module] ?? []) {
      if (count($listeners) > 1) {
        throw new \LogicException("Module $module should not implement $hook more than once");
      }
      return reset($listeners)(... $args);
    }

    return $this->legacyInvoke($module, $hook, $args);
  }

  /**
   * Calls a function called $module . '_' . $hook if one exists.
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
  protected function legacyInvoke($module, $hook, array $args = []) {
    $this->load($module);
    $function = $module . '_' . $hook;
    if (function_exists($function) && !(new \ReflectionFunction($function))->getAttributes(LegacyHook::class)) {
      return $function(... $args);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function invokeAll($hook, array $args = []) {
    $return = [];
    $this->invokeAllWith($hook, function (callable $hook, string $module) use ($args, &$return) {
      $result = call_user_func_array($hook, $args);
      if (isset($result) && is_array($result)) {
        $return = NestedArray::mergeDeep($return, $result);
      }
      elseif (isset($result)) {
        $return[] = $result;
      }
    });
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function invokeDeprecated($description, $module, $hook, array $args = []) {
    $result = $this->invoke($module, $hook, $args);
    $this->triggerDeprecationError($description, $hook);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function invokeAllDeprecated($description, $hook, array $args = []) {
    $result = $this->invokeAll($hook, $args);
    $this->triggerDeprecationError($description, $hook);
    return $result;
  }

  /**
   * Triggers an E_USER_DEPRECATED error if any module implements the hook.
   *
   * @param string $description
   *   Helpful text describing what to do instead of implementing this hook.
   * @param string $hook
   *   The name of the hook.
   */
  private function triggerDeprecationError($description, $hook) {
    $modules = array_keys($this->getHookListeners($hook));
    if (!empty($modules)) {
      $message = 'The deprecated hook hook_' . $hook . '() is implemented in these modules: ';
      @trigger_error($message . implode(', ', $modules) . '. ' . $description, E_USER_DEPRECATED);
    }
  }

  /**
   * {@inheritdoc}
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
      // than !empty() both when $type is passed as a string, or as an array
      // with one item.
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
    if (!isset($this->alterEventListeners[$cid])) {
      $this->alterEventListeners[$cid] = [];
      $hook = $type . '_alter';
      $hook_listeners = $this->getHookListeners($hook);
      if (isset($extra_types)) {
        // For multiple hooks, we need $modules to contain every module that
        // implements at least one of them in the correct order.
        foreach ($extra_types as $extra_type) {
          foreach ($this->getHookListeners($extra_type . '_alter') as $module => $listeners) {
            if (isset($hook_listeners[$module])) {
              $hook_listeners[$module] = array_merge($hook_listeners[$module], $listeners);
            }
            else {
              $hook_listeners[$module] = $listeners;
              $extra_modules = TRUE;
            }
          }
        }
      }
      // If any modules implement one of the extra hooks that do not implement
      // the primary hook, we need to add them to the $modules array in their
      // appropriate order.
      $modules = array_keys($hook_listeners);
      if (isset($extra_modules)) {
        $modules = $this->reOrderModulesForAlter($modules, $hook);
      }
      foreach ($modules as $module) {
        foreach ($hook_listeners[$module] ?? [] as $listener) {
          $this->alterEventListeners[$cid][] = $listener;
        }
      }
    }
    foreach ($this->alterEventListeners[$cid] as $listener) {
      $listener($data, $context1, $context2);
    }
  }

  /**
   * Reorder modules for alters.
   *
   * @param array $modules
   *   A list of modules.
   * @param string $hook
   *   The hook being worked on, for example form_alter.
   *
   * @return array
   *   The list, potentially reordered and changed by
   *   hook_module_implements_alter().
   */
  protected function reOrderModulesForAlter(array $modules, string $hook): array {
    // Order by module order first.
    $modules = array_intersect(array_keys($this->moduleList), $modules);
    // Alter expects the module list to be in the keys.
    $implementations = array_fill_keys($modules, FALSE);
    // Let modules adjust the order solely based on the primary hook. This
    // ensures the same module order regardless of whether this block
    // runs. Calling $this->alter() recursively in this way does not
    // result in an infinite loop, because this call is for a single
    // $type, so we won't end up in this method again.
    $this->alter('module_implements', $implementations, $hook);
    return array_keys($implementations);
  }

  /**
   * {@inheritdoc}
   */
  public function alterDeprecated($description, $type, &$data, &$context1 = NULL, &$context2 = NULL) {
    // Invoke the alter hook. This has the side effect of populating
    // $this->alterEventListeners.
    $this->alter($type, $data, $context1, $context2);
    // The $type parameter can be an array. alter() will deal with this
    // internally, but we have to extract the proper $cid in order to discover
    // implementations.
    $cid = $type;
    if (is_array($type)) {
      $cid = implode(',', $type);
      $extra_types = $type;
      $type = array_shift($extra_types);
    }
    if (!empty($this->alterEventListeners[$cid])) {
      $functions = [];
      foreach ($this->alterEventListeners[$cid] as $listener) {
        if (is_string($listener)) {
          $functions[] = substr($listener, 1);
        }
        else {
          $functions[] = get_class($listener[0]) . '::' . $listener[1];
        }
      }
      $message = 'The deprecated alter hook hook_' . $type . '_alter() is implemented in these locations: ' . implode(', ', $functions) . '.';
      @trigger_error($message . ' ' . $description, E_USER_DEPRECATED);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleDirectories() {
    $dirs = [];
    foreach ($this->getModuleList() as $name => $module) {
      $dirs[$name] = $this->root . '/' . $module->getPath();
    }
    return $dirs;
  }

  /**
   * {@inheritdoc}
   */
  public function getName($module) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Extension\ModuleExtensionList::getName($module) instead. See https://www.drupal.org/node/3310017', E_USER_DEPRECATED);
    return \Drupal::service('extension.list.module')->getName($module);
  }

  public function writeCache() {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. There is no need to call this method so there is no replacement. See https://www.drupal.org/node/3442349', E_USER_DEPRECATED);
  }

  /**
   * @param string $hook
   *   The name of the hook.
   *
   * @return array
   *   A list of event listeners implementing this hook.
   */
  protected function getHookListeners(string $hook): array {
    if (!isset($this->invokeMap[$hook])) {
      foreach ($this->eventDispatcher->getListeners("drupal_hook.$hook") as $listener) {
        if (is_array($listener) && is_object($listener[0])) {
          $module = $this->hookImplementationsMap[$hook][get_class($listener[0])][$listener[1]];
          // Inline ProceduralCall::__call() otherwise references get lost.
          if ($listener[0] instanceof ProceduralCall) {
            $listener[0]->loadFile($listener[1]);
            $callable = '\\' . $listener[1];
          }
          else {
            $callable = $listener;
          }
          if (isset($this->moduleList[$module])) {
            $this->invokeMap[$hook][$module][] = $callable;
          }
        }
      }
      if (isset($this->groupIncludes[$hook])) {
        foreach ($this->groupIncludes[$hook] as $include) {
          include_once $include;
        }
      }
    }
    return $this->invokeMap[$hook] ?? [];
  }

}
