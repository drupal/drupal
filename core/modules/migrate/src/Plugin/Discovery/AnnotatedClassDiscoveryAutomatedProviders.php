<?php

namespace Drupal\migrate\Plugin\Discovery;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Drupal\Component\Annotation\AnnotationInterface;
use Drupal\Component\Annotation\Doctrine\StaticReflectionParser as BaseStaticReflectionParser;
use Drupal\Component\Annotation\Reflection\MockFileFinder;
use Drupal\Component\ClassFinder\ClassFinder;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\migrate\Annotation\MultipleProviderAnnotationInterface;

/**
 * Determines providers based on a class's and its parent's namespaces.
 *
 * @internal
 *   This is a temporary solution to the fact that migration source plugins have
 *   more than one provider. This functionality will be moved to core in
 *   https://www.drupal.org/node/2786355.
 */
class AnnotatedClassDiscoveryAutomatedProviders extends AnnotatedClassDiscovery {

  /**
   * A utility object that can use active autoloaders to find files for classes.
   *
   * @var \Drupal\Component\ClassFinder\ClassFinderInterface
   */
  protected $finder;

  /**
   * Constructs an AnnotatedClassDiscoveryAutomatedProviders object.
   *
   * @param string $subdir
   *   Either the plugin's subdirectory, for example 'Plugin/views/filter', or
   *   empty string if plugins are located at the top level of the namespace.
   * @param \Traversable $root_namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   *   If $subdir is not an empty string, it will be appended to each namespace.
   * @param string $plugin_definition_annotation_name
   *   The name of the annotation that contains the plugin definition.
   *   Defaults to 'Drupal\Component\Annotation\Plugin'.
   * @param string[] $annotation_namespaces
   *   Additional namespaces to scan for annotation definitions.
   */
  public function __construct($subdir, \Traversable $root_namespaces, $plugin_definition_annotation_name = 'Drupal\Component\Annotation\Plugin', array $annotation_namespaces = []) {
    parent::__construct($subdir, $root_namespaces, $plugin_definition_annotation_name, $annotation_namespaces);
    $this->finder = new ClassFinder();
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareAnnotationDefinition(AnnotationInterface $annotation, $class, ?BaseStaticReflectionParser $parser = NULL) {
    if (!($annotation instanceof MultipleProviderAnnotationInterface)) {
      throw new \LogicException('AnnotatedClassDiscoveryAutomatedProviders annotations must implement \Drupal\migrate\Annotation\MultipleProviderAnnotationInterface');
    }
    $annotation->setClass($class);
    $providers = $annotation->getProviders();
    // Loop through all the parent classes and add their providers (which we
    // infer by parsing their namespaces) to the $providers array.
    do {
      $providers[] = $this->getProviderFromNamespace($parser->getNamespaceName());
    } while ($parser = StaticReflectionParser::getParentParser($parser, $this->finder));
    $providers = array_unique(array_filter($providers, function ($provider) {
      return $provider && $provider !== 'component';
    }));
    $annotation->setProviders($providers);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = [];

    $reader = $this->getAnnotationReader();

    // Clear the annotation loaders of any previous annotation classes.
    AnnotationRegistry::reset();
    // Register the namespaces of classes that can be used for annotations.
    AnnotationRegistry::registerLoader('class_exists');

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
                  // Explicitly unserialize this to create a new object instance.
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
              $parser = new BaseStaticReflectionParser($class, $finder, FALSE);

              /** @var \Drupal\Component\Annotation\AnnotationInterface $annotation */
              if ($annotation = $reader->getClassAnnotation($parser->getReflectionClass(), $this->pluginDefinitionAnnotationName)) {
                $this->prepareAnnotationDefinition($annotation, $class, $parser);

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

}
