<?php

namespace Drupal\Core\Extension;

use Drupal\Component\Graph\Graph;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\Core\Hook\HookCollectorPass;
use Drupal\Core\Hook\OrderOperation\OrderOperation;
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
   * Lists of implementation callables by hook.
   *
   * @var array<string, list<callable>>
   */
  protected array $listenersByHook = [];

  /**
   * Lists of module names by hook.
   *
   * The indices are exactly the same as in $listenersByHook.
   *
   * @var array<string, list<string>>
   */
  protected array $modulesByHook = [];

  /**
   * Hook and module keyed list of listeners.
   *
   * @var array<string, array<string, list<callable>>>
   */
  protected array $invokeMap = [];

  /**
   * Ordering rules by hook name.
   *
   * @var array<string, list<\Drupal\Core\Hook\OrderOperation\OrderOperation>>
   */
  protected array $orderingRules = [];

  /**
   * Constructs a ModuleHandler object.
   *
   * @param string $root
   *   The app root.
   * @param array<string, array{type: string, pathname: string, filename: string}> $module_list
   *   An associative array whose keys are the names of installed modules and
   *   whose values are Extension class parameters. This is normally the
   *   %container.modules% parameter being set up by DrupalKernel.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param array<string, array<class-string, array<string, string>>> $hookImplementationsMap
   *   An array keyed by hook, classname, method and the value is the module.
   * @param array<string, list<string>> $groupIncludes
   *   Lists of *.inc file paths that contain procedural implementations, keyed
   *   by hook name.
   * @param array<string, list<string>> $packedOrderOperations
   *   Ordering rules by hook name, serialized.
   *
   * @see \Drupal\Core\DrupalKernel
   * @see \Drupal\Core\CoreServiceProvider
   */
  public function __construct(
    $root,
    array $module_list,
    protected EventDispatcherInterface $eventDispatcher,
    protected array $hookImplementationsMap,
    protected array $groupIncludes = [],
    protected array $packedOrderOperations = [],
  ) {
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
    @trigger_error(__METHOD__ . '() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no direct replacement. See https://www.drupal.org/node/3491200', E_USER_DEPRECATED);
    $this->add('module', $name, $path);
  }

  /**
   * {@inheritdoc}
   */
  public function addProfile($name, $path) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. There is no direct replacement. See https://www.drupal.org/node/3491200', E_USER_DEPRECATED);
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
   *
   * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0.
   * There is no direct replacement.
   * @see https://www.drupal.org/node/3491200
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
    // phpcs:ignore Drupal.Files.LineLength
    // - $container->register(ProceduralCall::class, ProceduralCall::class)->addArgument($collector->includes);
    // Load all includes so the legacy section of invoke can handle hooks in
    // includes.
    $hook_collector->loadAllIncludes();
    // Register procedural implementations.
    foreach ($hook_collector->getImplementations() as $hook => $moduleImplements) {
      foreach ($moduleImplements as $module => $classImplements) {
        foreach ($classImplements[ProceduralCall::class] ?? [] as $method) {
          $this->listenersByHook[$hook][] = $method;
          $this->modulesByHook[$hook][] = $module;
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
    $this->invokeMap = [];
    $this->listenersByHook = [];
    $this->modulesByHook = [];
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
    foreach ($this->getFlatHookListeners($hook) as $index => $listener) {
      $module = $this->modulesByHook[$hook][$index];
      $callback($listener, $module);
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
    // specific variants of it, as in the case of ['form', 'form_FORM_ID'].
    if (is_array($type)) {
      $cid = implode(',', $type);
    }
    else {
      $cid = $type;
    }

    // Some alter hooks are invoked many times per page request, so store the
    // list of functions to call, and on subsequent calls, iterate through them
    // quickly.
    if (!isset($this->alterEventListeners[$cid])) {
      $hooks = is_array($type)
        ? array_map(static fn (string $type) => $type . '_alter', $type)
        : [$type . '_alter'];
      $this->alterEventListeners[$cid] = $this->getCombinedListeners($hooks);
    }
    foreach ($this->alterEventListeners[$cid] as $listener) {
      $listener($data, $context1, $context2);
    }
  }

  /**
   * Builds a list of listeners for an alter hook.
   *
   * @param list<string> $hooks
   *   The hooks passed to the ->alter() call.
   *
   * @return list<callable>
   *   List of implementation callables.
   */
  protected function getCombinedListeners(array $hooks): array {
    // Get implementation lists for each hook.
    $listener_lists = array_map($this->getFlatHookListeners(...), $hooks);
    // Remove empty lists.
    $listener_lists = array_filter($listener_lists);
    if (!$listener_lists) {
      // No implementations exist.
      return [];
    }
    if (array_keys($listener_lists) === [0]) {
      // Only the first hook has implementations.
      return $listener_lists[0];
    }
    // Collect the lists from each hook and group the listeners by module.
    $listeners_by_identifier = [];
    $modules_by_identifier = [];
    $identifiers_by_module = [];
    foreach ($listener_lists as $i_hook => $listeners) {
      $hook = $hooks[$i_hook];
      foreach ($listeners as $i_listener => $listener) {
        $module = $this->modulesByHook[$hook][$i_listener];
        $identifier = is_array($listener)
          ? get_class($listener[0]) . '::' . $listener[1]
          : ProceduralCall::class . '::' . $listener;
        $other_module = $modules_by_identifier[$identifier] ?? NULL;
        if ($other_module !== NULL) {
          $this->triggerErrorForDuplicateAlterHookListener(
            $hooks,
            $module,
            $other_module,
            $listener,
            $identifier,
          );
          // Don't add the same listener more than once.
          continue;
        }
        $listeners_by_identifier[$identifier] = $listener;
        $modules_by_identifier[$identifier] = $module;
        $identifiers_by_module[$module][] = $identifier;
      }
    }
    // First we get the the modules in moduleList order, this order is module
    // weight then alphabetical. Then we apply legacy ordering using
    // hook_module_implements_alter(). Finally we order using order attributes.
    $modules = array_keys($identifiers_by_module);
    $modules = $this->reOrderModulesForAlter($modules, $hooks[0]);
    // Create a flat list of identifiers, using the new module order.
    $identifiers = array_merge(...array_map(
      fn (string $module) => $identifiers_by_module[$module],
      $modules,
    ));
    foreach ($hooks as $hook) {
      foreach ($this->getHookOrderingRules($hook) as $rule) {
        $rule->apply($identifiers, $modules_by_identifier);
        // Order operations must not:
        // - Insert duplicate keys.
        // - Change the array to be not a list.
        // - Add or remove values.
        assert($identifiers === array_unique($identifiers));
        assert(array_is_list($identifiers));
        assert(!array_diff($identifiers, array_keys($modules_by_identifier)));
        assert(!array_diff(array_keys($modules_by_identifier), $identifiers));
      }
    }
    return array_map(
      static fn (string $identifier) => $listeners_by_identifier[$identifier],
      $identifiers,
    );
  }

  /**
   * Triggers an error on duplicate alter listeners.
   *
   * This is called when the same method is registered for multiple hooks, which
   * are now part of the same alter call.
   *
   * @param list<string> $hooks
   *   Hook names from the ->alter() call.
   * @param string $module
   *   The module name for one of the hook implementations.
   * @param string $other_module
   *   The module name for another hook implementation.
   * @param callable $listener
   *   The hook listener.
   * @param string $identifier
   *   String identifier of the hook listener.
   */
  protected function triggerErrorForDuplicateAlterHookListener(array $hooks, string $module, string $other_module, callable $listener, string $identifier): void {
    $log_message_replacements = [
      '@implementation' => is_array($listener)
        ? ('method ' . $identifier . '()')
        : ('function ' . $listener[1] . '()'),
      '@hooks' => "['" . implode("', '", $hooks) . "']",
    ];
    if ($other_module !== $module) {
      // There is conflicting information about which module this
      // implementation is registered for. At this point we cannot even
      // be sure if the module is the one from the main hook or the extra
      // hook. This means that ordering may not work as expected and it is
      // unclear if the intention is to execute the code multiple times. This
      // can be resolved by using a separate method for alter hooks that
      // implement on behalf of other modules.
      trigger_error((string) new FormattableMarkup(
        'The @implementation is registered for more than one of the alter hooks @hooks from the current ->alter() call, on behalf of different modules @module and @other_module. Only one instance will be part of the implementation list for this hook combination. For the purpose of ordering, the module @module will be used.',
        [
          ...$log_message_replacements,
          '@module' => "'$module'",
          '@other_module' => "'$other_module'",
        ],
      ), E_USER_WARNING);
    }
    else {
      // There is no conflict, but probably one or more redundant #[Hook]
      // attributes should be removed.
      trigger_error((string) new FormattableMarkup(
        'The @implementation is registered for more than one of the alter hooks @hooks from the current ->alter() call. Only one instance will be part of the implementation list for this hook combination.',
        $log_message_replacements,
      ), E_USER_NOTICE);
    }
  }

  /**
   * Gets ordering rules for a hook.
   *
   * @param string $hook
   *   Hook name.
   *
   * @return list<\Drupal\Core\Hook\OrderOperation\OrderOperation>
   *   List of order operations for the hook.
   */
  protected function getHookOrderingRules(string $hook): array {
    return $this->orderingRules[$hook] ??= array_map(
      OrderOperation::unpack(...),
      $this->packedOrderOperations[$hook] ?? [],
    );
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
   * Gets hook listeners by module.
   *
   * @param string $hook
   *   The name of the hook.
   *
   * @return array<string, list<callable>>
   *   A list of event listeners implementing this hook.
   */
  protected function getHookListeners(string $hook): array {
    if (!isset($this->invokeMap[$hook])) {
      $this->invokeMap[$hook] = [];
      foreach ($this->getFlatHookListeners($hook) as $index => $listener) {
        $module = $this->modulesByHook[$hook][$index];
        $this->invokeMap[$hook][$module][] = $listener;
      }
    }

    return $this->invokeMap[$hook] ?? [];
  }

  /**
   * Gets a list of hook listener callbacks.
   *
   * @param string $hook
   *   The hook name.
   *
   * @return list<callable>
   *   A list of hook implementation callables.
   *
   * @internal
   */
  protected function getFlatHookListeners(string $hook): array {
    if (!isset($this->listenersByHook[$hook])) {
      $this->listenersByHook[$hook] = [];
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
            $this->listenersByHook[$hook][] = $callable;
            $this->modulesByHook[$hook][] = $module;
          }
        }
      }
      if (isset($this->groupIncludes[$hook])) {
        foreach ($this->groupIncludes[$hook] as $include) {
          @trigger_error('Autoloading hooks in the file (' . $include . ') is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Move the functions in this file to either the .module file or other appropriate location. See https://www.drupal.org/node/3489765', E_USER_DEPRECATED);
          include_once $include;
        }
      }
    }

    return $this->listenersByHook[$hook];
  }

}
