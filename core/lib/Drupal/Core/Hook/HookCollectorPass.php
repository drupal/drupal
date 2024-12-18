<?php

declare(strict_types=1);

namespace Drupal\Core\Hook;

use Drupal\Component\Annotation\Doctrine\StaticReflectionParser;
use Drupal\Component\Annotation\Reflection\MockFileFinder;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\Extension\ProceduralCall;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\Core\Hook\Attribute\StopProceduralHookScan;
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
   * Keys are hook, module, class. Values are a list of methods.
   */
  protected array $implementations = [];

  /**
   * An associative array of hook implementations.
   *
   * Keys are hook, module and an empty string value.
   *
   * @see hook_module_implements_alter()
   */
  protected array $moduleImplements = [];

  /**
   * A list of include files.
   *
   * (This is required only for BC.)
   */
  protected array $includes = [];

  /**
   * A list of functions implementing hook_module_implements_alter().
   *
   * (This is required only for BC.)
   */
  protected array $moduleImplementsAlters = [];

  /**
   * A list of functions implementing hook_hook_info().
   *
   * (This is required only for BC.)
   */
  private array $hookInfo = [];

  /**
   * A list of .inc files.
   */
  private array $groupIncludes = [];

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    $collector = static::collectAllHookImplementations($container->getParameter('container.modules'), $container);
    $map = [];
    $container->register(ProceduralCall::class, ProceduralCall::class)
      ->addArgument($collector->includes);
    $groupIncludes = [];
    foreach ($collector->hookInfo as $function) {
      foreach ($function() as $hook => $info) {
        if (isset($collector->groupIncludes[$info['group']])) {
          $groupIncludes[$hook] = $collector->groupIncludes[$info['group']];
        }
      }
    }
    $definition = $container->getDefinition('module_handler');
    $definition->setArgument('$groupIncludes', $groupIncludes);
    foreach ($collector->moduleImplements as $hook => $moduleImplements) {
      foreach ($collector->moduleImplementsAlters as $alter) {
        $alter($moduleImplements, $hook);
      }
      $priority = 0;
      foreach ($moduleImplements as $module => $v) {
        foreach ($collector->implementations[$hook][$module] as $class => $method_hooks) {
          if ($container->has($class)) {
            $definition = $container->findDefinition($class);
          }
          else {
            $definition = $container
              ->register($class, $class)
              ->setAutowired(TRUE);
          }
          foreach ($method_hooks as $method) {
            $map[$hook][$class][$method] = $module;
            $definition->addTag('kernel.event_listener', [
              'event' => "drupal_hook.$hook",
              'method' => $method,
              'priority' => $priority--,
            ]);
          }
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
   * @param Symfony\Component\DependencyInjection\ContainerBuilder|null $container
   *   The container.
   *
   * @return static
   *   A HookCollectorPass instance holding all hook implementations and
   *   include file information.
   *
   * @internal
   *   This method is only used by ModuleHandler.
   *
   * * @todo Pass only $container when ModuleHandler->add is removed https://www.drupal.org/project/drupal/issues/3481778
   */
  public static function collectAllHookImplementations(array $module_filenames, ?ContainerBuilder $container = NULL): static {
    $modules = array_map(fn ($x) => preg_quote($x, '/'), array_keys($module_filenames));
    // Longer modules first.
    usort($modules, fn($a, $b) => strlen($b) - strlen($a));
    $module_preg = '/^(?<function>(?<module>' . implode('|', $modules) . ')_(?!preprocess_)(?!update_\d)(?<hook>[a-zA-Z0-9_\x80-\xff]+$))/';
    $collector = new static();
    foreach ($module_filenames as $module => $info) {
      $skip_procedural = isset($container) ? $container->hasParameter("$module.hooks_converted") : FALSE;
      $collector->collectModuleHookImplementations(dirname($info['pathname']), $module, $module_preg, $skip_procedural);
    }
    return $collector;
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
   * @param $skip_procedural
   *   Skip the procedural check for the current module.
   *
   * @return void
   */
  protected function collectModuleHookImplementations($dir, $module, $module_preg, bool $skip_procedural): void {
    $hook_file_cache = FileCacheFactory::get('hook_implementations');
    $procedural_hook_file_cache = FileCacheFactory::get('procedural_hook_implementations:' . $module_preg);

    $iterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS | \FilesystemIterator::FOLLOW_SYMLINKS);
    $iterator = new \RecursiveCallbackFilterIterator($iterator, static::filterIterator(...));
    $iterator = new \RecursiveIteratorIterator($iterator);
    /** @var \RecursiveDirectoryIterator | \RecursiveIteratorIterator $iterator*/
    foreach ($iterator as $fileinfo) {
      assert($fileinfo instanceof \SplFileInfo);
      $extension = $fileinfo->getExtension();
      $filename = $fileinfo->getPathname();

      if (($extension === 'module' || $extension === 'profile') && !$iterator->getDepth() && !$skip_procedural) {
        // There is an expectation for all modules and profiles to be loaded.
        // .module and .profile files are not supposed to be in subdirectories.
        // These need to be loaded even if the module has no procedural hooks.
        include_once $filename;
      }
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
          if (class_exists($class)) {
            $attributes = static::getHookAttributesInClass($class);
            $hook_file_cache->set($filename, ['class' => $class, 'attributes' => $attributes]);
          }
          else {
            $attributes = [];
          }
        }
        foreach ($attributes as $attribute) {
          $this->addFromAttribute($attribute, $class, $module);
        }
      }
      elseif (!$skip_procedural) {
        $implementations = $procedural_hook_file_cache->get($filename);
        if ($implementations === NULL) {
          $finder = MockFileFinder::create($filename);
          $parser = new StaticReflectionParser('', $finder);
          $implementations = [];
          foreach ($parser->getMethodAttributes() as $function => $attributes) {
            if (StaticReflectionParser::hasAttribute($attributes, StopProceduralHookScan::class)) {
              break;
            }
            if (!StaticReflectionParser::hasAttribute($attributes, LegacyHook::class) && preg_match($module_preg, $function, $matches)) {
              $implementations[] = ['function' => $function, 'module' => $matches['module'], 'hook' => $matches['hook']];
            }
          }
          $procedural_hook_file_cache->set($filename, $implementations);
        }
        foreach ($implementations as $implementation) {
          $this->addProceduralImplementation($fileinfo, $implementation['hook'], $implementation['module'], $implementation['function']);
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
    if ($hook->module) {
      $module = $hook->module;
    }
    $this->moduleImplements[$hook->hook][$module] = '';
    $this->implementations[$hook->hook][$module][$class][] = $hook->method;
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
    $this->addFromAttribute(new Hook($hook, $module . '_' . $hook), ProceduralCall::class, $module);
    if ($hook === 'hook_info') {
      $this->hookInfo[] = $function;
    }
    if ($hook === 'module_implements_alter') {
      $this->moduleImplementsAlters[] = $function;
    }
    if ($fileinfo->getExtension() !== 'module') {
      $this->includes[$function] = $fileinfo->getPathname();
    }
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
      'hook_info',
      'install',
      'module_implements_alter',
      'requirements',
      'schema',
      'uninstall',
      'update_last_removed',
      'hook_install_tasks',
      'hook_install_tasks_alter',
    ];

    if (in_array($hook->hook, $staticDenyHooks) || preg_match('/^(post_update_|preprocess_|update_\d+$)/', $hook->hook)) {
      throw new \LogicException("The hook $hook->hook on class $class does not support attributes and must remain procedural.");
    }
  }

}
