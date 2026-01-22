<?php

declare(strict_types=1);

namespace Drupal\Core\Hook;

use Drupal\Component\Annotation\Doctrine\StaticReflectionParser;
use Drupal\Component\Annotation\Reflection\MockFileFinder;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\Extension\ProceduralCall;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\HookAttributeInterface;
use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\Core\Hook\Attribute\LegacyModuleImplementsAlter;
use Drupal\Core\Hook\Attribute\ProceduralHookScanStop;
use Drupal\Core\Hook\Attribute\LegacyRequirementsHook;
use Drupal\Core\Hook\Attribute\RemoveHook;
use Drupal\Core\Hook\Attribute\ReorderHook;
use Drupal\Core\Hook\OrderOperation\OrderOperation;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects and registers hook implementations.
 *
 * A hook implementation is a class in a Drupal\modulename\Hook namespace
 * where either the class itself or the methods have a #[Hook] attribute.
 * These classes are automatically registered as autowired services.
 *
 * Services for procedural implementation of hooks are also registered.
 *
 * Finally, a temporary .hook_data container parameter is added. This
 * contains:
 *  - hook_list a mapping from [hook,class,method] to the module name.
 *  - preprocess_for_suggestions preprocess hooks with double underscores.
 *  - includes files that contain hooks that are not defined by hook_hook_info
 *    or in .module files
 *  - group_includes files identified by hook_hook_info
 *  - packed_order_operations ordering rules for runtime evaluation
 *
 * The parameter hook_data is processed in HookCollectorKeyValueWritePass and
 * removed automatically.
 *
 * @internal
 */
class HookCollectorPass implements CompilerPassInterface {

  /**
   * OOP implementation module names keyed by hook name and "$class::$method".
   *
   * @var array<string, array<string, string>>
   */
  protected array $oopImplementations = [];

  /**
   * Procedural implementation module names by hook name.
   *
   * @var array<string, list<string>>
   */
  protected array $proceduralImplementations = [];

  /**
   * Order operations grouped by hook name and weight.
   *
   * Operations with higher weight are applied last, which means they can
   * override the changes from previous operations.
   *
   * @var array<string, array<int, list<\Drupal\Core\Hook\OrderOperation\OrderOperation>>>
   *
   * @todo Review how to combine operations from different hooks.
   */
  protected array $orderOperations = [];

  /**
   * Lists of implementation identifiers to remove, keyed by hook name.
   *
   * An identifier can be a function name or a "$class::$method" string.
   *
   * @var array<string, list<string>>
   */
  protected array $removeHookIdentifiers = [];

  /**
   * A map of include files by function name.
   *
   * (This is required only for BC.)
   *
   * @var array<callable-string, string>
   */
  protected array $includes = [];

  /**
   * A list of functions implementing hook_module_implements_alter().
   *
   * (This is required only for BC.)
   *
   * @var list<callable-string>
   */
  protected array $moduleImplementsAlters = [];

  /**
   * A list of functions implementing hook_hook_info().
   *
   * (This is required only for BC.)
   *
   * @var list<callable-string>
   */
  private array $hookInfo = [];

  /**
   * Preprocess suggestions discovered in modules.
   *
   * These are stored to prevent adding preprocess suggestions to the invoke map
   * that are not discovered in modules.
   *
   * @var array<string, true>
   */
  protected array $preprocessForSuggestions;

  /**
   * Include files, keyed by the $group part of "/$module.$group.inc".
   *
   * @var array<string, list<string>>
   */
  private array $groupIncludes = [];

  /**
   * Constructor.
   *
   * @param list<string> $modules
   *   Names of installed modules.
   *   When used as a compiler pass, this parameter should be omitted.
   */
  public function __construct(
    protected readonly array $modules = [],
  ) {}

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    $module_list = $container->getParameter('container.modules');
    $parameters = $container->getParameterBag()->all();
    $skip_procedural_modules = array_filter(
      array_keys($module_list),
      static fn (string $module) => !empty($parameters["$module.skip_procedural_hook_scan"]),
    );
    $collector = static::collectAllHookImplementations($module_list, $skip_procedural_modules);

    $collector->writeToContainer($container);
  }

  /**
   * Writes collected definitions to the container builder.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   Container builder.
   */
  protected function writeToContainer(ContainerBuilder $container): void {
    // Gather includes for each hook_hook_info group. Store this in
    // $groupIncludes so the module handler includes the files at runtime when
    // the hooks are invoked.
    $groupIncludes = [];
    foreach ($this->hookInfo as $function) {
      foreach ($function() as $hook => $info) {
        if (isset($this->groupIncludes[$info['group']])) {
          $groupIncludes[$hook] = $this->groupIncludes[$info['group']];
        }
      }
    }

    $implementationsByHook = $this->calculateImplementations();

    static::registerHookServices($container, $implementationsByHook);

    $includes = $this->collectIncludesPerHook($implementationsByHook, $groupIncludes);

    $packed_order_operations = [];
    $order_operations = $this->getOrderOperations();
    foreach (preg_grep('@_alter$@', array_keys($order_operations)) as $alter_hook) {
      $packed_order_operations[$alter_hook] = array_map(
        fn (OrderOperation $operation) => $operation->pack(),
        $order_operations[$alter_hook],
      );
    }

    // Write aggregated data about hooks into a temporary parameter.
    // We use a dot prefixed parameter so it will automatically get cleaned up.
    // This will be stored in a keyvalue store in
    // \Drupal\Core\Hook\HookCollectorKeyValueWritePass.
    $container->setParameter('.hook_data', [
      'hook_list' => $implementationsByHook,
      'preprocess_for_suggestions' => $this->preprocessForSuggestions ?? [],
      'includes' => $includes,
      'group_includes' => $groupIncludes,
      'packed_order_operations' => $packed_order_operations,
    ]);
  }

  /**
   * Collects include files by hook name.
   *
   * @param array<string, array<string, string>> $implementationsByHook
   *   Implementations by hook.
   * @param array<string, list<string>> $groupIncludes
   *   Explicitly defined group includes to filter out.
   *
   * @return array<string, list<string>>
   *   Lists of include files by hook name.
   */
  protected function collectIncludesPerHook(array $implementationsByHook, array $groupIncludes): array {
    $includesMap = [];
    foreach ($implementationsByHook as $hook => $hookImplementations) {
      foreach ($hookImplementations as $identifier => $module) {
        if (str_contains($identifier, '::')) {
          continue;
        }
        $include = $this->includes[$identifier] ?? NULL;
        if ($include !== NULL) {
          // Do not add includes that are already in group includes.
          if (isset($groupIncludes[$hook]) && in_array($include, $groupIncludes[$hook])) {
            continue;
          }
          $includesMap[$hook][$include] = TRUE;
        }
      }
    }
    return array_map(array_keys(...), $includesMap);
  }

  /**
   * Gets implementation lists with removals already applied.
   *
   * @return array<string, list<string>>
   *   Implementations, as module names keyed by hook name and
   *   "$class::$method".
   */
  protected function getFilteredImplementations(): array {
    $implementationsByHook = [];
    foreach ($this->proceduralImplementations as $hook => $procedural_modules) {
      foreach ($procedural_modules as $module) {
        $implementationsByHook[$hook][$module . '_' . $hook] = $module;
      }
    }
    foreach ($this->oopImplementations as $hook => $oopImplementations) {
      if (!isset($implementationsByHook[$hook])) {
        $implementationsByHook[$hook] = $oopImplementations;
      }
      else {
        $implementationsByHook[$hook] += $oopImplementations;
      }
    }
    foreach ($this->removeHookIdentifiers as $hook => $identifiers_to_remove) {
      foreach ($identifiers_to_remove as $identifier_to_remove) {
        unset($implementationsByHook[$hook][$identifier_to_remove]);
      }
      if (empty($implementationsByHook[$hook])) {
        unset($implementationsByHook[$hook]);
      }
    }
    return $implementationsByHook;
  }

  /**
   * Calculates the ordered implementations.
   *
   * @return array<string, array<string, string>>
   *   Implementations, as module names keyed by hook name and "$class::$method"
   *   identifier.
   */
  protected function calculateImplementations(): array {
    $implementationsByHookOrig = $this->getFilteredImplementations();

    // List of hooks and modules formatted for hook_module_implements_alter().
    $moduleImplementsMap = [];
    foreach ($implementationsByHookOrig as $hook => $hookImplementations) {
      foreach (array_intersect($this->modules, $hookImplementations) as $module) {
        $moduleImplementsMap[$hook][$module] = '';
      }
    }

    $implementationsByHook = [];
    foreach ($moduleImplementsMap as $hook => $moduleImplements) {
      // Process all hook_module_implements_alter() for build time ordering.
      foreach ($this->moduleImplementsAlters as $alter) {
        $alter($moduleImplements, $hook);
      }
      foreach ($moduleImplements as $module => $v) {
        if (is_string($hook) && str_starts_with($hook, 'preprocess_') && str_contains($hook, '__')) {
          $this->preprocessForSuggestions[$module . '_' . $hook] = 'module';
        }
        foreach (array_keys($implementationsByHookOrig[$hook], $module, TRUE) as $identifier) {
          $implementationsByHook[$hook][$identifier] = $module;
        }
      }
    }

    foreach ($this->getOrderOperations() as $hook => $order_operations) {
      self::applyOrderOperations($implementationsByHook[$hook], $order_operations);
    }

    return $implementationsByHook;
  }

  /**
   * Gets order operations by hook.
   *
   * @return array<string, list<\Drupal\Core\Hook\OrderOperation\OrderOperation>>
   *   Order operations by hook name.
   */
  protected function getOrderOperations(): array {
    $implementationsByHook = $this->getFilteredImplementations();
    $operations_by_hook = [];
    foreach ($this->orderOperations as $hook => $order_operations_by_weight) {
      ksort($order_operations_by_weight);
      $order_operations = array_merge(...$order_operations_by_weight);
      foreach ($order_operations as $key => $operation) {
        if (!isset($implementationsByHook[$hook][$operation->identify()])) {
          unset($order_operations[$key]);
        }
      }
      $operations_by_hook[$hook] = array_values($order_operations);

    }
    return $operations_by_hook;
  }

  /**
   * Applies order operations to a hook implementation list.
   *
   * @param array<string, string> $implementation_list
   *   Implementation list for one hook, as module names keyed by
   *   "$class::$method" identifiers.
   * @param list<\Drupal\Core\Hook\OrderOperation\OrderOperation> $order_operations
   *   A list of order operations for one hook.
   */
  protected static function applyOrderOperations(array &$implementation_list, array $order_operations): void {
    $module_finder = $implementation_list;
    $identifiers = array_keys($module_finder);
    foreach ($order_operations as $order_operation) {
      $order_operation->apply($identifiers, $module_finder);
      assert($identifiers === array_unique($identifiers));
      assert(array_is_list($identifiers));
      assert(!array_diff($identifiers, array_keys($module_finder)));
      assert(!array_diff(array_keys($module_finder), $identifiers));
    }
    // Rebuild the identifier -> module array with the new order.
    $identifiers = array_combine($identifiers, $identifiers);
    $identifiers = array_intersect_key($identifiers, $module_finder);
    $implementation_list = array_replace($identifiers, $module_finder);
  }

  /**
   * Registers the hook implementation services.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The container builder.
   * @param array<string, array<string, string>> $implementationsByHook
   *   Implementations, as module names keyed by hook name and "$class::$method"
   *   or $function identifier.
   */
  protected static function registerHookServices(
    ContainerBuilder $container,
    array $implementationsByHook,
  ): void {
    $classesMap = [];
    foreach ($implementationsByHook as $hookImplementations) {
      foreach (array_keys($hookImplementations) as $identifier) {
        $parts = explode('::', $identifier, 2);
        if (isset($parts[1])) {
          $classesMap[$parts[0]] = TRUE;
        }
      }
    }

    foreach (array_keys($classesMap) as $class) {
      if (!$container->hasDefinition($class)) {
        $container
          ->register($class, $class)
          ->setAutowired(TRUE);
      }
    }
  }

  /**
   * Collects all hook implementations.
   *
   * @param array<string, array{pathname: string}> $module_list
   *   An associative array. Keys are the module names, values are relevant
   *   info yml file path.
   * @param list<string> $skipProceduralModules
   *   Module names that are known to not have procedural hook implementations.
   *
   * @return static
   *   A HookCollectorPass instance holding all hook implementations and
   *   include file information.
   *
   * @internal
   *   This method is only used by ModuleHandler.
   *
   * @todo Pass only $container and make protected when ModuleHandler::add() is
   *   removed in Drupal 12.0.0.
   */
  public static function collectAllHookImplementations(array $module_list, array $skipProceduralModules = []): static {
    $modules = array_keys($module_list);
    $all_modules_preg = static::getModuleListPattern($modules);
    $collector = new static($modules);
    foreach ($module_list as $module => $info) {
      $skip_procedural = in_array($module, $skipProceduralModules);
      $current_module_preg = static::getModuleListPattern([$module]);
      $collector->collectModuleHookImplementations(dirname($info['pathname']), $module, $current_module_preg, $all_modules_preg, $skip_procedural);
    }
    return $collector;
  }

  /**
   * Get a pattern used to match hooks for the given module list.
   *
   * The supplied module list will be sorted by length in descending order so
   * that longer names are matched first.
   *
   * @param list<string> $module_list
   *   A list of module names.
   *
   * @return string
   *   The pattern used to match hooks for the given module list.
   */
  protected static function getModuleListPattern(array $module_list): string {
    usort($module_list, static fn ($a, $b) => strlen($b) - strlen($a));
    $module_pattern = implode('|', array_map(
      static fn ($x) => preg_quote($x, '/'),
      $module_list,
    ));
    return '/^(?<function>(?<module>' . $module_pattern . ')_(?!update_\d)(?<hook>[a-zA-Z0-9_\x80-\xff]+$))/';
  }

  /**
   * Collects procedural and Attribute hook implementations.
   *
   * @param string $dir
   *   The directory in which the module resides.
   * @param string $module
   *   The name of the module.
   * @param string $current_module_preg
   *   A regular expression matching only the module being scanned.
   * @param string $all_modules_preg
   *   A regular expression matching every module, longer module names are
   *   matched first.
   * @param bool $skip_procedural
   *   Skip the procedural check for the current module.
   */
  protected function collectModuleHookImplementations($dir, $module, $current_module_preg, $all_modules_preg, bool $skip_procedural): void {
    $hook_file_cache = FileCacheFactory::get('hook_implementations');
    // List of modules must be included in the cache key to ensure rediscovery
    // takes place when a new module is installed. Some modules define hooks
    // on behalf of other modules and those hooks need to be found.
    // Hash to prevent massive key sizes.
    $procedural_hook_file_cache = FileCacheFactory::get('procedural_hook_implementations:' . hash('xxh3', $all_modules_preg));

    $iterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS | \FilesystemIterator::FOLLOW_SYMLINKS);
    $iterator = new \RecursiveCallbackFilterIterator($iterator, static::filterIterator(...));
    $iterator = new \RecursiveIteratorIterator($iterator);
    /** @var \RecursiveDirectoryIterator | \RecursiveIteratorIterator $iterator*/
    foreach ($iterator as $fileinfo) {
      assert($fileinfo instanceof \SplFileInfo);
      $extension = $fileinfo->getExtension();
      $filename = $fileinfo->getPathname();

      if ($extension === 'php') {
        $cached = $hook_file_cache->get($filename);
        if ($cached) {
          $class = $cached['class'];
          $attributes = $cached['attributes'];
        }
        else {
          $namespace = preg_replace('#^src/#', "Drupal/$module/", $iterator->getSubPath());
          $class = $namespace . '/' . $fileinfo->getBasename('.php');
          $class = str_replace('/', '\\', $class);
          $attributes = [];
          if (class_exists($class)) {
            $reflectionClass = new \ReflectionClass($class);
            $attributes = self::getAttributeInstances($reflectionClass);
            $hook_file_cache->set($filename, ['class' => $class, 'attributes' => $attributes]);
          }
        }
        foreach ($attributes as $method => $methodAttributes) {
          foreach ($methodAttributes as $attribute) {
            if ($attribute instanceof Hook) {
              self::checkForProceduralOnlyHooks($attribute, $class);
              $this->oopImplementations[$attribute->hook][$class . '::' . ($attribute->method ?: $method)] = $attribute->module ?? $module;
              if ($attribute->order !== NULL) {
                // Use a lower weight for order operations that are declared
                // together with the hook listener they apply to.
                $this->orderOperations[$attribute->hook][0][] = $attribute->order->getOperation("$class::$method");
              }
            }
            elseif ($attribute instanceof ReorderHook) {
              $identifier = $attribute->class === ProceduralCall::class
                ? $attribute->method
                : $attribute->class . '::' . $attribute->method;
              // Use a higher weight for order operations that target other hook
              // listeners.
              $this->orderOperations[$attribute->hook][1][] = $attribute->order->getOperation($identifier);
            }
            elseif ($attribute instanceof RemoveHook) {
              $identifier = $attribute->class === ProceduralCall::class
                ? $attribute->method
                : $attribute->class . '::' . $attribute->method;
              $this->removeHookIdentifiers[$attribute->hook][] = $identifier;
            }
          }
        }
      }
      elseif (!$skip_procedural) {
        $implementations = $procedural_hook_file_cache->get($filename);
        if ($implementations === NULL) {
          $finder = MockFileFinder::create($filename);
          $parser = new StaticReflectionParser('', $finder);
          $implementations = [];
          foreach ($parser->getMethodAttributes() as $function => $attributes) {
            if (StaticReflectionParser::hasAttribute($attributes, ProceduralHookScanStop::class)) {
              break;
            }

            $legacy_attributes = [LegacyHook::class, LegacyModuleImplementsAlter::class, LegacyRequirementsHook::class];
            if (!static::hasAnyAttribute($attributes, $legacy_attributes) && (preg_match($current_module_preg, $function, $matches) || preg_match($all_modules_preg, $function, $matches))) {
              // Skip hooks that are not supported by the new hook system, they
              // do not need to be added to the BC layer. Note that is different
              // from static::checkForProceduralOnlyHooks(). hook_requirements
              // is allowed and only update hooks are considered as dynamic
              // exclusion, since post updates are not expected to be parsed.
              // Also, update hooks are checked as ends with, since the regular
              // expression sometimes attributes them to the wrong module,
              // resulting in a prefix.
              $staticDenyHooks = [
                'install',
                'install_tasks',
                'install_tasks_alter',
                'schema',
                'uninstall',
                'update_last_removed',
                'update_dependencies',
              ];
              if (in_array($matches['hook'], $staticDenyHooks) || preg_match('/update_\d+$/', $function)) {
                continue;
              }

              assert($function === $matches['module'] . '_' . $matches['hook']);
              $implementations[] = ['module' => $matches['module'], 'hook' => $matches['hook']];
            }
          }
          $procedural_hook_file_cache->set($filename, $implementations);
        }
        foreach ($implementations as $implementation) {
          $this->addProceduralImplementation($fileinfo, $implementation['hook'], $implementation['module']);
        }
      }
      if ($extension === 'inc') {
        $parts = explode('.', $fileinfo->getFilename());
        if (count($parts) === 3 && $parts[0] === $module) {
          $this->groupIncludes[$parts[1]][] = $filename;
        }
      }
    }
  }

  /**
   * Returns whether the existing attributes match any of the expected ones.
   *
   * @param array $existingAttributes
   *   List of attribute classes.
   * @param array $attributesLookingFor
   *   List of expected attribute classes.
   *
   * @return bool
   *   Whether an expected attribute class exists.
   */
  public static function hasAnyAttribute(array $existingAttributes, array $attributesLookingFor): bool {
    foreach ($existingAttributes as $existingAttribute) {
      foreach ($attributesLookingFor as $attributeLookingFor) {
        if (is_a($existingAttribute, $attributeLookingFor, TRUE)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Filter iterator callback. Allows include files and .php files in src/Hook.
   */
  protected static function filterIterator(\SplFileInfo $fileInfo, $key, \RecursiveDirectoryIterator $iterator): bool {
    $sub_path_name = $iterator->getSubPathname();
    $extension = $fileInfo->getExtension();
    if (str_starts_with($sub_path_name, 'src/Hook/')) {
      return $iterator->isDir() || $extension === 'php';
    }
    if ($iterator->isDir()) {
      if ($sub_path_name === 'src' || $sub_path_name === 'src/Hook') {
        return TRUE;
      }
      // glob() doesn't support streams but scandir() does.
      return !in_array($fileInfo->getFilename(), ['tests', 'js', 'css']) && !array_filter(scandir($key), static fn ($filename) => str_ends_with($filename, '.info.yml'));
    }
    return in_array($extension, ['inc', 'module', 'profile', 'install']);
  }

  /**
   * Adds a procedural hook implementation.
   *
   * @param \SplFileInfo $fileinfo
   *   The file this procedural implementation is in.
   * @param string $hook
   *   The name of the hook.
   * @param string $module
   *   The module implementing the hook, or on behalf of which the hook is
   *   implemented.
   */
  protected function addProceduralImplementation(\SplFileInfo $fileinfo, string $hook, string $module): void {
    $function = $module . '_' . $hook;
    if ($hook === 'hook_info') {
      $this->hookInfo[] = $function;
      include_once $fileinfo->getPathname();
    }
    elseif ($hook === 'module_implements_alter') {
      $message = "$function without a #[LegacyModuleImplementsAlter] attribute is deprecated in drupal:11.2.0 and removed in drupal:12.0.0. See https://www.drupal.org/node/3496788";
      @trigger_error($message, E_USER_DEPRECATED);
      $this->moduleImplementsAlters[] = $function;
      include_once $fileinfo->getPathname();
    }
    elseif (in_array($hook, ['requirements', 'requirements_alter'])) {
      $message = "$function without a #[LegacyRequirementsHook] attribute is deprecated in drupal:11.3.0 and removed in drupal:13.0.0. See https://www.drupal.org/node/3549685";
      @trigger_error($message, E_USER_DEPRECATED);
    }
    $this->proceduralImplementations[$hook][] = $module;
    if ($fileinfo->getExtension() !== 'module') {
      $this->includes[$function] = $fileinfo->getPathname();
    }
  }

  /**
   * This method is only to be used by ModuleHandler.
   *
   * @todo Remove when ModuleHandler::add() is removed in Drupal 12.0.0.
   *
   * @internal
   */
  public function loadAllIncludes(): void {
    foreach ($this->includes as $include) {
      include_once $include;
    }
  }

  /**
   * Checks for hooks which can't be supported in classes.
   *
   * @param \Drupal\Core\Hook\Attribute\Hook $hookAttribute
   *   The hook to check.
   * @param class-string $class
   *   The class the hook is implemented on.
   */
  public static function checkForProceduralOnlyHooks(Hook $hookAttribute, string $class): void {
    $staticDenyHooks = [
      'hook_info',
      'install',
      'install_tasks',
      'install_tasks_alter',
      'module_implements_alter',
      'removed_post_updates',
      'requirements',
      'schema',
      'uninstall',
      'update_dependencies',
      'update_last_removed',
    ];

    if (in_array($hookAttribute->hook, $staticDenyHooks) || preg_match('/^(post_update_|update_\d+$)/', $hookAttribute->hook)) {
      throw new \LogicException("The hook $hookAttribute->hook on class $class does not support attributes and must remain procedural.");
    }
  }

  /**
   * Get attribute instances from class and method reflections.
   *
   * @param \ReflectionClass $reflectionClass
   *   A reflected class.
   *
   * @return array<string, list<\Drupal\Core\Hook\Attribute\HookAttributeInterface>>
   *   Lists of Hook attribute instances by method name.
   */
  protected static function getAttributeInstances(\ReflectionClass $reflectionClass): array {
    $attributes = [];
    $reflections = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
    $reflections[] = $reflectionClass;
    foreach ($reflections as $reflection) {
      if ($reflectionAttributes = $reflection->getAttributes(HookAttributeInterface::class, \ReflectionAttribute::IS_INSTANCEOF)) {
        $method = $reflection instanceof \ReflectionMethod ? $reflection->getName() : '__invoke';
        $attributes[$method] = array_map(static fn (\ReflectionAttribute $ra) => $ra->newInstance(), $reflectionAttributes);
      }
    }
    return $attributes;
  }

}
