<?php

namespace Drupal\Component\Plugin\Discovery;

use Drupal\Component\Discovery\MissingClassDetectionClassLoader;
use Drupal\Component\Plugin\Attribute\AttributeInterface;
use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\FileCache\FileCacheInterface;

/**
 * Defines a discovery mechanism to find plugins with attributes.
 */
class AttributeClassDiscovery implements DiscoveryInterface {

  use DiscoveryTrait;

  /**
   * The file cache object.
   */
  protected FileCacheInterface $fileCache;

  /**
   * An array of classes to skip.
   *
   * This must be static because once a class has been autoloaded by PHP, it
   * cannot be unregistered again.
   */
  protected static array $skipClasses = [];

  /**
   * Constructs a new instance.
   *
   * @param string[] $pluginNamespaces
   *   (optional) An array of namespace that may contain plugin implementations.
   *   Defaults to an empty array.
   * @param string $pluginDefinitionAttributeName
   *   (optional) The name of the attribute that contains the plugin definition.
   *   Defaults to 'Drupal\Component\Plugin\Attribute\Plugin'.
   */
  public function __construct(
    protected readonly array $pluginNamespaces = [],
    protected readonly string $pluginDefinitionAttributeName = Plugin::class,
  ) {
    $file_cache_suffix = str_replace('\\', '_', $this->pluginDefinitionAttributeName);
    $this->fileCache = FileCacheFactory::get('attribute_discovery:' . $this->getFileCacheSuffix($file_cache_suffix));
  }

  /**
   * Gets the file cache suffix.
   *
   * This method allows classes that extend this class to add additional
   * information to the file cache collection name.
   *
   * @param string $default_suffix
   *   The default file cache suffix.
   *
   * @return string
   *   The file cache suffix.
   */
  protected function getFileCacheSuffix(string $default_suffix): string {
    return $default_suffix;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = [];

    $autoloader = new MissingClassDetectionClassLoader();
    spl_autoload_register([$autoloader, 'loadClass']);

    // Search for classes within all PSR-4 namespace locations.
    foreach ($this->getPluginNamespaces() as $namespace => $dirs) {
      foreach ($dirs as $dir) {
        if (file_exists($dir)) {
          $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
          );
          foreach ($iterator as $fileinfo) {
            assert($fileinfo instanceof \SplFileInfo);
            if ($fileinfo->getExtension() === 'php') {
              if ($cached = $this->fileCache->get($fileinfo->getPathName())) {
                if (isset($cached['id'])) {
                  // Explicitly unserialize this to create a new object
                  // instance.
                  $definitions[$cached['id']] = unserialize($cached['content']);
                }
                continue;
              }

              $sub_path = $iterator->getSubIterator()->getSubPath();
              $sub_path = $sub_path ? str_replace(DIRECTORY_SEPARATOR, '\\', $sub_path) . '\\' : '';
              $class = $namespace . '\\' . $sub_path . $fileinfo->getBasename('.php');
              // Plugins may rely on Attribute classes defined by modules that
              // are not installed. In such a case, a 'class not found' error
              // may be thrown from reflection. However, this is an unavoidable
              // situation with optional dependencies and plugins. Therefore,
              // silently skip over this class and avoid writing to the cache,
              // so that it is scanned each time. This ensures that the plugin
              // definition will be found if the module it requires is
              // enabled.
              // PHP handles missing traits as an unrecoverable error.
              // Register a special classloader that prevents a missing
              // trait from causing an error. When it encounters a missing
              // trait it stores that it was unable to find the trait.
              // Because the classloader will result in the class being
              // autoloaded we store an array of classes to skip if this
              // method is called again.
              // If discovery runs twice in a single request, first without
              // the module that defines the missing trait, and second after it
              // has been installed, we want the plugin to be discovered in the
              // second case. Therefore, if a module has been added to skipped
              // classes, check if the trait's namespace is available.
              // If it is available, allow discovery.
              // @todo a fix for this has been committed to PHP. Once that is
              // available, attempt to make the class loader registration
              // conditional on PHP version, then remove the logic entirely once
              // Drupal requires PHP 8.5.
              // @see https://github.com/php/php-src/issues/17959
              // @see https://github.com/php/php-src/commit/8731c95b35f6838bacd12a07c50886e020aad5a6
              if (array_key_exists($class, self::$skipClasses)) {
                $missing_classes = self::$skipClasses[$class];
                foreach ($missing_classes as $missing_class) {
                  $missing_class_namespace = implode('\\', array_slice(explode('\\', $missing_class), 0, 2));

                  // If we arrive here a second time, and the namespace is still
                  // unavailable, ensure discovery is skipped. Without this
                  // explicit check for already checked classes, an invalid
                  // class would be discovered, because once we've detected a
                  // a missing trait and aliased the stub instead, this can't
                  // happen again, so the class appears valid. However, if the
                  // namespace has become available in the meantime, assume that
                  // the class actually should be discovered since this probably
                  // means the optional module it depends on has been enabled.
                  if (!isset($this->getPluginNamespaces()[$missing_class_namespace])) {
                    $autoloader->reset();
                    continue 2;
                  }
                }
              }
              try {
                $class_exists = class_exists($class, TRUE);
                if (!$class_exists || \count($autoloader->getMissingTraits()) > 0) {
                  // @todo remove this workaround once PHP treats missing traits
                  // as catchable fatal errors.
                  if (\count($autoloader->getMissingTraits()) > 0) {
                    self::$skipClasses[$class] = $autoloader->getMissingTraits();
                  }
                  $autoloader->reset();
                  continue;
                }
              }
              catch (\Error $e) {
                if (!$autoloader->hasMissingClass()) {
                  // @todo Add test coverage for unexpected Error exceptions in
                  // https://www.drupal.org/project/drupal/issues/3520811.
                  $autoloader->reset();
                  spl_autoload_unregister([$autoloader, 'loadClass']);
                  throw $e;
                }
                $autoloader->reset();
                continue;
              }
              ['id' => $id, 'content' => $content] = $this->parseClass($class, $fileinfo);
              if ($id) {
                $definitions[$id] = $content;
                // Explicitly serialize this to create a new object instance.
                if (!isset(self::$skipClasses[$class])) {
                  $this->fileCache->set($fileinfo->getPathName(), ['id' => $id, 'content' => serialize($content)]);
                }
              }
              else {
                // Store a NULL object, so that the file is not parsed again.
                $this->fileCache->set($fileinfo->getPathName(), [NULL]);
              }
            }
          }
        }
      }
    }
    spl_autoload_unregister([$autoloader, 'loadClass']);

    // Plugin discovery is a memory expensive process due to reflection and the
    // number of files involved. Collect cycles at the end of discovery to be as
    // efficient as possible.
    gc_collect_cycles();
    return $definitions;
  }

  /**
   * Parses attributes from a class.
   *
   * @param class-string $class
   *   The class to parse.
   * @param \SplFileInfo $fileinfo
   *   The SPL file information for the class.
   *
   * @return array
   *   An array with the keys 'id' and 'content'. The 'id' is the plugin ID and
   *   'content' is the plugin definition.
   *
   * @throws \ReflectionException
   * @throws \Error
   */
  protected function parseClass(string $class, \SplFileInfo $fileinfo): array {
    // @todo Consider performance improvements over using reflection.
    // @see https://www.drupal.org/project/drupal/issues/3395260.
    $reflection_class = new \ReflectionClass($class);

    $id = $content = NULL;
    if ($attributes = $reflection_class->getAttributes($this->pluginDefinitionAttributeName, \ReflectionAttribute::IS_INSTANCEOF)) {
      /** @var \Drupal\Component\Plugin\Attribute\AttributeInterface $attribute */
      $attribute = $attributes[0]->newInstance();
      $this->prepareAttributeDefinition($attribute, $class);

      $id = $attribute->getId();
      $content = $attribute->get();
    }
    return ['id' => $id, 'content' => $content];
  }

  /**
   * Prepares the attribute definition.
   *
   * @param \Drupal\Component\Plugin\Attribute\AttributeInterface $attribute
   *   The attribute derived from the plugin.
   * @param string $class
   *   The class used for the plugin.
   */
  protected function prepareAttributeDefinition(AttributeInterface $attribute, string $class): void {
    $attribute->setClass($class);
  }

  /**
   * Gets an array of PSR-4 namespaces to search for plugin classes.
   *
   * @return string[][]
   *   An array of namespaces to search.
   */
  protected function getPluginNamespaces(): array {
    return $this->pluginNamespaces;
  }

}
