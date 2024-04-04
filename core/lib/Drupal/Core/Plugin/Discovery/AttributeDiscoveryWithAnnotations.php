<?php

namespace Drupal\Core\Plugin\Discovery;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Drupal\Component\Annotation\AnnotationInterface;
use Drupal\Component\Annotation\Doctrine\SimpleAnnotationReader;
use Drupal\Component\Annotation\Doctrine\StaticReflectionParser;
use Drupal\Component\Annotation\Reflection\MockFileFinder;
use Drupal\Component\Utility\Crypt;

/**
 * Enables both attribute and annotation discovery for plugin definitions.
 */
class AttributeDiscoveryWithAnnotations extends AttributeClassDiscovery {

  /**
   * The doctrine annotation reader.
   *
   * @var \Doctrine\Common\Annotations\Reader
   */
  protected $annotationReader;

  /**
   * Constructs an AttributeDiscoveryWithAnnotations object.
   *
   * @param string $subdir
   *   Either the plugin's subdirectory, for example 'Plugin/views/filter', or
   *   empty string if plugins are located at the top level of the namespace.
   * @param \Traversable $rootNamespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   *   If $subdir is not an empty string, it will be appended to each namespace.
   * @param string $pluginDefinitionAttributeName
   *   (optional) The name of the attribute that contains the plugin definition.
   *   Defaults to 'Drupal\Component\Plugin\Attribute\Plugin'.
   * @param string $pluginDefinitionAnnotationName
   *   (optional) The name of the attribute that contains the plugin definition.
   *   Defaults to 'Drupal\Component\Annotation\Plugin'.
   * @param string[] $additionalNamespaces
   *   (optional) Additional namespaces to scan for attribute definitions.
   */
  public function __construct(
    string $subdir,
    \Traversable $rootNamespaces,
    string $pluginDefinitionAttributeName = 'Drupal\Component\Plugin\Attribute\Plugin',
    protected readonly string $pluginDefinitionAnnotationName = 'Drupal\Component\Annotation\Plugin',
    protected readonly array $additionalNamespaces = [],
  ) {
    parent::__construct($subdir, $rootNamespaces, $pluginDefinitionAttributeName);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFileCacheSuffix(string $default_suffix):string {
    return $default_suffix . ':' . Crypt::hashBase64(serialize($this->additionalNamespaces)) . ':' . str_replace('\\', '_', $this->pluginDefinitionAnnotationName);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    // Clear the annotation loaders of any previous annotation classes.
    AnnotationRegistry::reset();
    // Register the namespaces of classes that can be used for annotations.
    // @phpstan-ignore-next-line
    AnnotationRegistry::registerLoader('class_exists');

    $definitions = parent::getDefinitions();

    $this->annotationReader = NULL;

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  protected function parseClass(string $class, \SplFileInfo $fileinfo): array {
    // The filename is already known, so there is no need to find the
    // file. However, StaticReflectionParser needs a finder, so use a
    // mock version.
    $finder = MockFileFinder::create($fileinfo->getPathName());
    $parser = new StaticReflectionParser($class, $finder, TRUE);

    $reflection_class = $parser->getReflectionClass();
    // @todo Handle deprecating definitions discovery via annotations in
    // https://www.drupal.org/project/drupal/issues/3265945.
    /** @var \Drupal\Component\Annotation\AnnotationInterface $annotation */
    if ($annotation = $this->getAnnotationReader()->getClassAnnotation($reflection_class, $this->pluginDefinitionAnnotationName)) {
      $this->prepareAnnotationDefinition($annotation, $class);
      return ['id' => $annotation->getId(), 'content' => $annotation->get()];
    }

    // Annotations use static reflection and are able to analyze a class that
    // extends classes or uses traits that do not exist. Attribute discovery
    // will trigger a fatal error with such classes, so only call it if the
    // class has a class attribute.
    if ($reflection_class->hasClassAttribute($this->pluginDefinitionAttributeName)) {
      return parent::parseClass($class, $fileinfo);
    }
    return ['id' => NULL, 'content' => NULL];
  }

  /**
   * Prepares the annotation definition.
   *
   * This is a copy of the prepareAnnotationDefinition method from annotated
   * class discovery.
   *
   * @param \Drupal\Component\Annotation\AnnotationInterface $annotation
   *   The annotation derived from the plugin.
   * @param class-string $class
   *   The class used for the plugin.
   *
   * @see \Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery::prepareAnnotationDefinition()
   * @see \Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery::prepareAnnotationDefinition()
   */
  private function prepareAnnotationDefinition(AnnotationInterface $annotation, string $class): void {
    $annotation->setClass($class);
    if (!$annotation->getProvider()) {
      $annotation->setProvider($this->getProviderFromNamespace($class));
    }
  }

  /**
   * Gets the used doctrine annotation reader.
   *
   * This is a copy of the getAnnotationReader method from annotated class
   * discovery.
   *
   * @return \Drupal\Component\Annotation\Doctrine\SimpleAnnotationReader
   *   The annotation reader.
   *
   * @see \Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery::getAnnotationReader()
   * @see \Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery::getAnnotationReader()
   */
  private function getAnnotationReader() : SimpleAnnotationReader {
    if (!isset($this->annotationReader)) {
      $this->annotationReader = new SimpleAnnotationReader();

      // Add the namespaces from the main plugin annotation, like @EntityType.
      $namespace = substr($this->pluginDefinitionAnnotationName, 0, strrpos($this->pluginDefinitionAnnotationName, '\\'));
      $this->annotationReader->addNamespace($namespace);

      // Register additional namespaces to be scanned for annotations.
      foreach ($this->additionalNamespaces as $namespace) {
        $this->annotationReader->addNamespace($namespace);
      }

      // Add the Core annotation classes like @Translation.
      $this->annotationReader->addNamespace('Drupal\Core\Annotation');
    }
    return $this->annotationReader;
  }

}
