<?php

namespace Drupal\Component\Annotation\Plugin\Discovery;

use Drupal\Component\Annotation\AnnotationInterface;
use Drupal\Component\Annotation\Doctrine\AnnotationRegistry;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Annotation\Doctrine\SimpleAnnotationReader;
use Drupal\Component\Annotation\Doctrine\StaticReflectionParser;
use Drupal\Component\Annotation\Reflection\MockFileFinder;
use Drupal\Component\Plugin\Discovery\DiscoveryTrait;
use Drupal\Component\Utility\Crypt;

/**
 * Defines a discovery mechanism to find annotated plugins in PSR-4 namespaces.
 */
class AnnotatedClassDiscovery implements DiscoveryInterface {

  use DiscoveryTrait;

  /**
   * The doctrine annotation reader.
   *
   * @var \Doctrine\Common\Annotations\Reader
   */
  protected $annotationReader;

  /**
   * The file cache object.
   *
   * @var \Drupal\Component\FileCache\FileCacheInterface
   */
  protected $fileCache;

  /**
   * Constructs a new instance.
   *
   * @param array<string, list<string>> $pluginNamespaces
   *   (optional) An array of namespace that may contain plugin implementations.
   *   Defaults to an empty array.
   * @param string $pluginDefinitionAnnotationName
   *   (optional) The name of the annotation that contains the plugin
   *   definition. Defaults to 'Drupal\Component\Annotation\Plugin'.
   * @param string[] $annotationNamespaces
   *   (optional) Additional namespaces to be scanned for annotation classes.
   */
  public function __construct(
    protected $pluginNamespaces = [],
    protected $pluginDefinitionAnnotationName = 'Drupal\Component\Annotation\Plugin',
    protected array $annotationNamespaces = [],
  ) {
    $file_cache_suffix = str_replace('\\', '_', $this->pluginDefinitionAnnotationName);
    $file_cache_suffix .= ':' . Crypt::hashBase64(serialize($this->annotationNamespaces));
    $this->fileCache = FileCacheFactory::get('annotation_discovery:' . $file_cache_suffix);
  }

  /**
   * Gets the used doctrine annotation reader.
   *
   * @return \Doctrine\Common\Annotations\Reader
   *   The annotation reader.
   */
  protected function getAnnotationReader() {
    if (!isset($this->annotationReader)) {
      $this->annotationReader = new SimpleAnnotationReader();

      // Add the namespaces from the main plugin annotation, like @EntityType.
      $namespace = substr($this->pluginDefinitionAnnotationName, 0, strrpos($this->pluginDefinitionAnnotationName, '\\'));
      $this->annotationReader->addNamespace($namespace);

      // Register additional namespaces to be scanned for annotations.
      foreach ($this->annotationNamespaces as $namespace) {
        $this->annotationReader->addNamespace($namespace);
      }
    }
    return $this->annotationReader;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = [];

    $reader = $this->getAnnotationReader();

    // Clear the annotation loaders of any previous annotation classes.
    AnnotationRegistry::reset();

    // Search for classes within all PSR-4 namespace locations.
    foreach ($this->getPluginNamespaces() as $namespace => $dirs) {
      foreach ($dirs as $dir) {
        if (file_exists($dir)) {
          $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
          );
          foreach ($iterator as $fileinfo) {
            if ($fileinfo->getExtension() == 'php') {
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

              // The filename is already known, so there is no need to find the
              // file. However, StaticReflectionParser needs a finder, so use a
              // mock version.
              $finder = MockFileFinder::create($fileinfo->getPathName());
              $parser = new StaticReflectionParser($class, $finder, TRUE);

              /** @var \Drupal\Component\Annotation\AnnotationInterface $annotation */
              if ($annotation = $reader->getClassAnnotation($parser->getReflectionClass(), $this->pluginDefinitionAnnotationName)) {
                $this->prepareAnnotationDefinition($annotation, $class);

                $id = $annotation->getId();
                $content = $annotation->get();
                $definitions[$id] = $content;
                // Explicitly serialize this to create a new object instance.
                $this->fileCache->set($fileinfo->getPathName(), ['id' => $id, 'content' => serialize($content)]);
              }
              else {
                // Store a NULL object, so the file is not parsed again.
                $this->fileCache->set($fileinfo->getPathName(), [NULL]);
              }
            }
          }
        }
      }
    }

    // Don't let annotation loaders pile up.
    AnnotationRegistry::reset();

    return $definitions;
  }

  /**
   * Prepares the annotation definition.
   *
   * @param \Drupal\Component\Annotation\AnnotationInterface $annotation
   *   The annotation derived from the plugin.
   * @param string $class
   *   The class used for the plugin.
   */
  protected function prepareAnnotationDefinition(AnnotationInterface $annotation, $class) {
    $annotation->setClass($class);
  }

  /**
   * Gets an array of PSR-4 namespaces to search for plugin classes.
   *
   * @return array<string, list<string>>
   *   The PSR-4 namespaces for the plugin class.
   */
  protected function getPluginNamespaces() {
    return $this->pluginNamespaces;
  }

}
