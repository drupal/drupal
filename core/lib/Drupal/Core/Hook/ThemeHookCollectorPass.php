<?php

declare(strict_types=1);

namespace Drupal\Core\Hook;

use Drupal\Component\Annotation\Doctrine\StaticReflectionParser;
use Drupal\Component\Annotation\Reflection\MockFileFinder;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\HookAttributeInterface;
use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\Core\Hook\Attribute\RemoveHook;
use Drupal\Core\Hook\Attribute\ProceduralHookScanStop;
use Drupal\Core\Hook\Attribute\ReorderHook;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects and registers hook implementations.
 *
 * A hook implementation is a class in a Drupal\themename\Hook namespace
 * where either the class itself or the methods have a #[Hook] attribute.
 * These classes are automatically registered as autowired services.
 *
 * Finally, a temporary .theme_hook_data container parameter is added. This
 * contains:
 *  - theme_hook_list a mapping from theme to [hook,class,method].
 *  - theme_preprocess_for_suggestions preprocess hooks with double underscores.
 *
 * The parameter theme_hook_data is processed in HookCollectorKeyValueWritePass
 * and removed automatically.
 *
 * @internal
 */
class ThemeHookCollectorPass implements CompilerPassInterface {

  /**
   * OOP implementation theme names keyed by hook name and "$class::$method".
   *
   * @var array<string, array<string, string>>
   */
  protected array $oopImplementations = [];

  /**
   * Procedural implementation extension names by hook name.
   *
   * @var array<string, list<string>>
   */
  protected array $proceduralImplementations = [];

  /**
   * Preprocess suggestions discovered in extensions.
   *
   * These are stored to prevent adding preprocess suggestions to the invoke map
   * that are not discovered in extensions.
   *
   * @var array<string, true>
   */
  protected array $preprocessForSuggestions;

  /**
   * Constructor.
   *
   * @param list<string> $themes
   *   Names of installed themes.
   *   When used as a compiler pass, this parameter should be omitted.
   */
  public function __construct(
    protected readonly array $themes = [],
  ) {}

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    $collectorThemes = static::collectAllHookImplementations($container);

    $collectorThemes->writeToContainer($container);
  }

  /**
   * Writes collected definitions to the container builder.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   Container builder.
   */
  protected function writeToContainer(ContainerBuilder $container): void {
    $implementationsByHook = $this->calculateImplementations();

    static::registerHookServices($container, $implementationsByHook);

    // Write aggregated data about hooks into a temporary parameter.
    // We use a dot prefixed parameter so it will automatically get cleaned up.
    $container->setParameter('.theme_hook_data', [
      'theme_hook_list' => $this->sortByTheme($implementationsByHook),
      'theme_preprocess_for_suggestions' => $this->preprocessForSuggestions ?? [],
    ]);
  }

  /**
   * Sort by theme.
   *
   * @param array<string, array<string, string>> $implementationsByHook
   *   Implementations, as theme names keyed by hook name and
   *   "$class::$method" identifier.
   *
   * @return array<string, array<string, list>>
   *   Implementations, as theme names keyed by theme, hook name and
   *   "$class::$method" identifier.
   */
  protected function sortByTheme(array $implementationsByHook) {
    $implementationsByTheme = [];
    foreach ($implementationsByHook as $hook => $identifiers) {
      foreach ($identifiers as $identifier => $theme) {
        $implementationsByTheme[$theme][$hook][] = $identifier;
      }
    }
    return $implementationsByTheme;
  }

  /**
   * Calculates the ordered implementations.
   *
   * @return array<string, array<string, string>>
   *   Implementations, as theme names keyed by hook name and
   *   "$class::$method" identifier.
   */
  protected function calculateImplementations(): array {
    $implementationsByHookOrig = $this->getFilteredImplementations();

    // Store preprocess implementations for themes.
    foreach ($implementationsByHookOrig as $hook => $hookImplementations) {
      if (is_string($hook) && str_starts_with($hook, 'preprocess_') && str_contains($hook, '__')) {
        foreach ($hookImplementations as $theme) {
          $this->preprocessForSuggestions[$theme . '_' . $hook] = TRUE;
        }
      }
    }

    return $implementationsByHookOrig;
  }

  /**
   * Collects all hook implementations.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The container.
   *
   * @return static
   *   A ThemeHookCollectorPass instance holding all hook implementations and
   *   include file information.
   *
   * @internal
   */
  protected static function collectAllHookImplementations(ContainerBuilder $container): static {
    $parameters = $container->getParameterBag()->all();
    $themeList = $parameters['container.themes'];

    $skipProcedural = array_filter(
      array_keys($themeList),
      static fn(string $theme) => !empty($parameters["$theme.skip_procedural_hook_scan"]),
    );
    $themes = array_keys($themeList);
    $allThemesPreg = static::getThemeListPattern($themes);
    $collector = new static($themes);
    foreach ($themeList as $theme => $info) {
      $shouldSkipProceduralScan = in_array($theme, $skipProcedural);
      $currentThemePreg = static::getThemeListPattern([$theme]);
      $collector->collectThemeHookImplementations(dirname($info['pathname']), $theme, $currentThemePreg, $allThemesPreg, $shouldSkipProceduralScan);
    }
    return $collector;
  }

  /**
   * Get a pattern used to match hooks for the given theme list.
   *
   * The supplied theme list will be sorted by length in descending order so
   * that longer names are matched first.
   *
   * @param list<string> $themeList
   *   A list of theme names.
   *
   * @return string
   *   The pattern used to match hooks for the given theme list.
   */
  protected static function getThemeListPattern(array $themeList): string {
    usort($themeList, static fn($a, $b) => strlen($b) - strlen($a));
    $themePattern = implode('|', array_map(
      static fn($x) => preg_quote($x, '/'),
      $themeList,
    ));
    return '/^(?<function>(?<theme>' . $themePattern . ')_(?!update_\d)(?<hook>[a-zA-Z0-9_\x80-\xff]+$))/';
  }

  /**
   * Collects procedural and Attribute hook implementations.
   *
   * @param string $dir
   *   The directory in which the theme resides.
   * @param string $theme
   *   The name of the theme.
   * @param string $currentThemePreg
   *   A regular expression matching only the theme being scanned.
   * @param string $allThemesPreg
   *   A regular expression matching every theme, longer theme names are
   *   matched first.
   * @param bool $shouldSkipProceduralScan
   *   Skip the procedural check for the current theme.
   */
  protected function collectThemeHookImplementations($dir, $theme, $currentThemePreg, $allThemesPreg, bool $shouldSkipProceduralScan): void {
    $hookFileCache = FileCacheFactory::get('theme_hook_implementations');
    $proceduralHookFileCache = FileCacheFactory::get('theme_procedural_hook_implementations:' . $allThemesPreg);

    $iterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS | \FilesystemIterator::FOLLOW_SYMLINKS);
    $iterator = new \RecursiveCallbackFilterIterator($iterator, static::filterIterator(...));
    $iterator = new \RecursiveIteratorIterator($iterator);
    /** @var \RecursiveDirectoryIterator | \RecursiveIteratorIterator $iterator*/
    foreach ($iterator as $fileinfo) {
      assert($fileinfo instanceof \SplFileInfo);
      $fileExtension = $fileinfo->getExtension();
      $filename = $fileinfo->getPathname();

      $isThemeSettings = str_ends_with($filename, 'theme-settings.php');

      if ($fileExtension === 'php' && !$isThemeSettings) {
        $cached = $hookFileCache->get($filename);
        if ($cached) {
          $class = $cached['class'];
          $attributes = $cached['attributes'];
        }
        else {
          $namespace = preg_replace('#^src/#', "Drupal/$theme/", $iterator->getSubPath());
          $class = $namespace . '/' . $fileinfo->getBasename('.php');
          $class = str_replace('/', '\\', $class);
          $attributes = [];
          if (class_exists($class)) {
            $reflectionClass = new \ReflectionClass($class);
            $attributes = self::getAttributeInstances($reflectionClass);
            $hookFileCache->set($filename, ['class' => $class, 'attributes' => $attributes]);
          }
        }
        foreach ($attributes as $method => $methodAttributes) {
          foreach ($methodAttributes as $attribute) {
            if ($attribute instanceof Hook) {
              self::checkInvalidHookParametersInThemes($attribute, $class);
              $this->oopImplementations[$attribute->hook][$class . '::' . ($attribute->method ?: $method)] = $theme;
            }
            elseif ($attribute instanceof RemoveHook) {
              throw new \LogicException("The #[RemoveHook] attribute is not allowed in themes. Found in $class.");
            }
            elseif ($attribute instanceof ReorderHook) {
              throw new \LogicException("The #[ReorderHook] attribute is not allowed in themes. Found in $class.");
            }
          }
        }
      }
      elseif (!$shouldSkipProceduralScan) {
        $implementations = $proceduralHookFileCache->get($filename);
        if ($implementations === NULL) {
          $finder = MockFileFinder::create($filename);
          $parser = new StaticReflectionParser('', $finder);
          $implementations = [];
          foreach ($parser->getMethodAttributes() as $function => $attributes) {
            if (StaticReflectionParser::hasAttribute($attributes, ProceduralHookScanStop::class)) {
              break;
            }
            if (!StaticReflectionParser::hasAttribute($attributes, LegacyHook::class) && (preg_match($currentThemePreg, $function, $matches) || preg_match($allThemesPreg, $function, $matches))) {
              assert($function === $matches['theme'] . '_' . $matches['hook']);
              $implementations[] = ['theme' => $matches['theme'], 'hook' => $matches['hook']];
            }
          }
          $proceduralHookFileCache->set($filename, $implementations);
        }
        foreach ($implementations as $implementation) {
          $this->proceduralImplementations[$implementation['hook']][] = $implementation['theme'];
        }
      }
    }
  }

  /**
   * Gets implementation lists with removals already applied.
   *
   * @return array<string, list<string>>
   *   Implementations, as extension names keyed by hook name and
   *   "$class::$method".
   */
  protected function getFilteredImplementations(): array {
    $implementationsByHook = [];
    foreach ($this->proceduralImplementations as $hook => $proceduralThemes) {
      foreach ($proceduralThemes as $theme) {
        $implementationsByHook[$hook][$theme . '_' . $hook] = $theme;
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

    return $implementationsByHook;
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
   * Filter iterator callback. Allows include files and .php files in src/Hook.
   */
  protected static function filterIterator(\SplFileInfo $fileInfo, $key, \RecursiveDirectoryIterator $iterator): bool {
    $subPathName = $iterator->getSubPathname();
    $extension = $fileInfo->getExtension();
    if (str_starts_with($subPathName, 'src/Hook/')) {
      return $iterator->isDir() || $extension === 'php';
    }
    if ($iterator->isDir()) {
      if ($subPathName === 'src' || $subPathName === 'src/Hook') {
        return TRUE;
      }
      // glob() doesn't support streams but scandir() does.
      return !in_array($fileInfo->getFilename(), ['tests', 'js', 'css', 'templates']) && !array_filter(scandir($key), static fn($filename) => str_ends_with($filename, '.info.yml'));
    }
    if ($fileInfo->getFilename() === 'theme-settings.php') {
      return TRUE;
    }
    return in_array($extension, ['inc', 'theme']);
  }

  /**
   * Checks for hooks which can't be supported in theme classes.
   *
   * @param \Drupal\Core\Hook\Attribute\Hook $hookAttribute
   *   The hook to check.
   * @param class-string $class
   *   The class the hook is implemented on.
   */
  public static function checkInvalidHookParametersInThemes(Hook $hookAttribute, string $class): void {
    // A theme cannot implement a hook on behalf of a module or other theme.
    if ($hookAttribute->module !== NULL) {
      throw new \LogicException("The 'module' parameter on the #[Hook] attribute is not allowed in themes. Found in $class.");
    }
    // A theme cannot alter the order of hook implementations.
    if ($hookAttribute->order !== NULL) {
      throw new \LogicException("The 'order' parameter on the #[Hook] attribute is not allowed in themes. Found in $class.");
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
        $attributes[$method] = array_map(static fn(\ReflectionAttribute $ra) => $ra->newInstance(), $reflectionAttributes);
      }
    }
    return $attributes;
  }

}
