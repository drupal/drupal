<?php

declare(strict_types=1);

namespace Drupal\Core\Hook;

use Drupal\Component\Annotation\Doctrine\StaticReflectionParser;
use Drupal\Component\Annotation\Reflection\MockFileFinder;
use Drupal\Core\Extension\ProceduralCall;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\LegacyHook;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects and registers hook implementations.
 *
 * A hook implementation is a class in a Drupal\modulename\Hook namespace
 * where either the class itself or the methods have a #[Hook] attribute.
 * These classes are automatically registered as autowired services.
 *
 * Services for procedural implementation of hooks are also registered
 * using the ProceduralCall class.
 *
 * Finally, a hook_implementations_map container parameter is added. This
 * contains a mapping from [hook,class,method] to the module name.
 */
class HookCollectorPass implements CompilerPassInterface {

  /**
   * An associative array of hook implementations.
   *
   * Keys are hook, class, method. Values are the named parameters of a Hook
   * attribute.
   */
  protected array $implementations = [];

  /**
   * A list of include files.
   *
   * (This is required only for BC.)
   */
  protected array $includes = [];

  /**
   * An array of procedural hook implementations.
   *
   * This is keyed by hook and module name, with the value always FALSE. This
   * corresponds to the $implementations parameter of
   * hook_module_implements_alter().
   *
   * (This is required only for BC.)
   */
  protected array $proceduralHooks = [];

  /**
   * A list of functions implementing hook_module_implements_alter().
   *
   * (This is required only for BC.)
   */
  protected array $moduleImplementsAlters = [];

  /**
   * The priority of the eventual event listener.
   *
   * This ensures the module order is kept.
   */
  protected int $priority = 0;

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    $collector = static::collectAllHookImplementations($container->getParameter('container.modules'));
    $map = [];
    $container->register(ProceduralCall::class, ProceduralCall::class)
      ->addArgument($collector->includes);
    foreach ($collector->implementations as $hook => $class_implementations) {
      foreach ($class_implementations as $class => $method_hooks) {
        if ($container->has($class)) {
          $definition = $container->findDefinition($class);
        }
        else {
          $definition = $container
            ->register($class, $class)
            ->setAutowired(TRUE);
        }
        foreach ($method_hooks as $method => $hook_data) {
          $map[$hook][$class][$method] = $hook_data['module'];
          $definition->addTag('kernel.event_listener', [
            'event' => "drupal_hook.$hook",
            'method' => $method,
            'priority' => $hook_data['priority'],
          ]);
        }
      }
    }
    $container->setParameter('hook_implementations_map', $map);
  }

  /**
   * Collects all hook implementations.
   *
   * @param array $module_filenames
   *   An associative array. Keys are the module names, values are relevant
   *   info yml file path.
   *
   * @return \Drupal\Core\Extension\HookCollectorPass
   *   A HookCollectorPass instance holding all hook implementations and
   *   include file information.
   *
   * @internal
   *   This method is only used by ModuleHandler.
   */
  public static function collectAllHookImplementations(array $module_filenames): static {
    $modules = array_map(fn ($x) => preg_quote($x, '/'), array_keys($module_filenames));
    // Longer modules first.
    usort($modules, fn($a, $b) => strlen($b) - strlen($a));
    $module_preg = '/^(?<function>(?<module>' . implode('|', $modules) . ')_(?!preprocess_)(?!update_\d)(?<hook>[a-zA-Z0-9_\x80-\xff]+$))/';
    $collector = new static();
    foreach ($module_filenames as $module => $info) {
      $collector->collectModuleHookImplementations(dirname($info['pathname']), $module, $module_preg);
    }
    return $collector->convertProceduralToImplementations();
  }

  /**
   * Collects procedural and Attribute hook implementations.
   *
   * @param $dir
   *   The directory in which the module resides.
   * @param $module
   *   The name of the module.
   * @param $module_preg
   *   A regular expression matching every module, longer module names are
   *   matched first.
   *
   * @return void
   */
  protected function collectModuleHookImplementations($dir, $module, $module_preg): void {
    $iterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);
    $iterator = new \RecursiveCallbackFilterIterator($iterator, static::filterIterator(...));
    $iterator = new \RecursiveIteratorIterator($iterator);
    /** @var \RecursiveDirectoryIterator | \RecursiveIteratorIterator $iterator*/
    foreach ($iterator as $fileinfo) {
      assert($fileinfo instanceof \SplFileInfo);
      $extension = $fileinfo->getExtension();
      if ($extension === 'module' && !$iterator->getDepth()) {
        // There is an expectation for all modules to be loaded. However,
        // .module files are not supposed to be in subdirectories.
        include_once $fileinfo->getPathname();
      }
      if ($extension === 'php') {
        $namespace = preg_replace('#^src/#', "Drupal/$module/", $iterator->getSubPath());
        $class = $namespace . '/' . basename($fileinfo->getFilename(), '.php');
        $class = str_replace('/', '\\', $class);
        foreach (static::getHookAttributesInClass($class) as $attribute) {
          $this->addFromAttribute($attribute, $class, $module);
        }
      }
      else {
        $finder = MockFileFinder::create($fileinfo->getPathName());
        $parser = new StaticReflectionParser('', $finder);
        foreach ($parser->getMethodAttributes() as $function => $attributes) {
          if (!StaticReflectionParser::hasAttribute($attributes, LegacyHook::class) && preg_match($module_preg, $function, $matches)) {
            $this->addProceduralImplementation($fileinfo, $matches['hook'], $matches['module'], $matches['function']);
          }
        }
      }
    }
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
      return !in_array($fileInfo->getFilename(), ['tests', 'js', 'css']) && !array_filter(scandir($key), fn ($filename) => str_ends_with($filename, '.info.yml'));
    }
    return in_array($extension, ['inc', 'module', 'profile', 'install']);
  }

  /**
   * An array of Hook attributes on this class with $method set.
   *
   * @param string $class
   *   The class.
   *
   * @return \Drupal\Core\Hook\Attribute\Hook[]
   *   An array of Hook attributes on this class. The $method property is guaranteed to be set.
   */
  protected static function getHookAttributesInClass(string $class): array {
    if (!class_exists($class)) {
      return [];
    }
    $reflection_class = new \ReflectionClass($class);
    $class_implementations = [];
    // Check for #[Hook] on the class itself.
    foreach ($reflection_class->getAttributes(Hook::class, \ReflectionAttribute::IS_INSTANCEOF) as $reflection_attribute) {
      $hook = $reflection_attribute->newInstance();
      assert($hook instanceof Hook);
      self::checkForProceduralOnlyHooks($hook, $class);
      if (!$hook->method) {
        if (method_exists($class, '__invoke')) {
          $hook->setMethod('__invoke');
        }
        else {
          throw new \LogicException("The Hook attribute for hook $hook->hook on class $class must specify a method.");
        }
      }
      $class_implementations[] = $hook;
    }
    // Check for #[Hook] on methods.
    foreach ($reflection_class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method_reflection) {
      foreach ($method_reflection->getAttributes(Hook::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute_reflection) {
        $hook = $attribute_reflection->newInstance();
        assert($hook instanceof Hook);
        self::checkForProceduralOnlyHooks($hook, $class);
        $class_implementations[] = $hook->setMethod($method_reflection->getName());
      }
    }
    return $class_implementations;
  }

  /**
   * Adds a Hook attribute implementation.
   *
   * @param \Drupal\Core\Hook\Attribute\Hook $hook
   *   A hook attribute.
   * @param $class
   *   The class in which said attribute resides in.
   * @param $module
   *   The module in which the class resides in.
   *
   * @return void
   */
  protected function addFromAttribute(Hook $hook, $class, $module) {
    $this->implementations[$hook->hook][$class][$hook->method] = [
      'priority' => $hook->priority ?? $this->priority--,
      'module' => $hook->module ?? $module,
    ];
  }

  /**
   * Adds a procedural hook implementation.
   *
   * @param \SplFileInfo $fileinfo
   *   The file this procedural implementation is in. (You don't say)
   * @param string $hook
   *   The name of the hook. (Huh, right?)
   * @param string $module
   *   The name of the module. (Truly shocking!)
   * @param string $function
   *   The name of function implementing the hook. (Wow!)
   *
   * @return void
   */
  protected function addProceduralImplementation(\SplFileInfo $fileinfo, string $hook, string $module, string $function) {
    $this->proceduralHooks[$hook][$module] = FALSE;
    if ($hook === 'module_implements_alter') {
      $this->moduleImplementsAlters[] = $function;
    }
    if ($fileinfo->getExtension() !== 'module') {
      $this->includes[$function] = $fileinfo->getPathname();
    }
  }

  /**
   * Converts procedural hooks to attribute based hooks.
   *
   * @return $this
   */
  protected function convertProceduralToImplementations(): static {
    foreach ($this->proceduralHooks as $hook => $hook_implementations) {
      // A hook can be all numbers and because it was put into an array index
      // it might get cast into a number which might fail a
      // hook_module_implements_alter() and is guaranteed to fail the Hook
      // attribute constructor.
      $hook = (string) $hook;
      if ($hook !== 'module_implements_alter') {
        foreach ($this->moduleImplementsAlters as $alter) {
          $alter($hook_implementations, $hook);
        }
      }
      foreach ($hook_implementations as $module => $group) {
        $this->addFromAttribute(new Hook($hook, $module . '_' . $hook), ProceduralCall::class, $module);
      }
    }
    return $this;
  }

  /**
   * This method is only to be used by ModuleHandler.
   *
   * @internal
   */
  public function loadAllIncludes(): void {
    foreach ($this->includes as $include) {
      include_once $include;
    }
  }

  /**
   * This method is only to be used by ModuleHandler.
   *
   * @internal
   */
  public function getImplementations(): array {
    return $this->implementations;
  }

  /**
   * Checks for hooks which can't be supported in classes.
   *
   * @param \Drupal\Core\Hook\Attribute\Hook $hook
   *   The hook to check.
   * @param string $class
   *   The class the hook is implemented on.
   *
   * @return void
   */
  public static function checkForProceduralOnlyHooks(Hook $hook, string $class): void {
    $staticDenyHooks = [
      'install',
      'module_preinstall',
      'module_preuninstall',
      'modules_installed',
      'modules_uninstalled',
      'requirements',
      'schema',
      'uninstall',
      'update_last_removed',
    ];

    if (in_array($hook->hook, $staticDenyHooks) || preg_match('/^(post_update_|preprocess_|process_|update_\d+$)/', $hook->hook)) {
      throw new \LogicException("The hook $hook->hook on class $class does not support attributes and must remain procedural.");
    }
  }

}
