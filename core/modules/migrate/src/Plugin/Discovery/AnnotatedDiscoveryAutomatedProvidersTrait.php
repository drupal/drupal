<?php

namespace Drupal\migrate\Plugin\Discovery;

use Drupal\Component\Annotation\AnnotationInterface;
use Drupal\Component\Annotation\Doctrine\StaticReflectionParser as BaseStaticReflectionParser;
use Drupal\migrate\Annotation\MultipleProviderAnnotationInterface;

/**
 * Provides method for annotation discovery with multiple providers.
 *
 * @internal
 *   This trait is a temporary solution until annotation discovery is removed.
 *   @see https://www.drupal.org/project/drupal/issues/3521472
 */
trait AnnotatedDiscoveryAutomatedProvidersTrait {

  /**
   * A utility object that can use active autoloaders to find files for classes.
   *
   * @var \Drupal\Component\ClassFinder\ClassFinderInterface
   */
  protected $finder;

  /**
   * Prepares the annotation definition.
   *
   * This is modified from the prepareAnnotationDefinition method from annotated
   * class discovery to account for multiple providers.
   *
   * @param \Drupal\Component\Annotation\AnnotationInterface $annotation
   *   The annotation derived from the plugin.
   * @param class-string $class
   *   The class used for the plugin.
   * @param \Drupal\Component\Annotation\Doctrine\StaticReflectionParser|null $parser
   *   Static reflection parser.
   *
   * @see \Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery::prepareAnnotationDefinition()
   * @see \Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery::prepareAnnotationDefinition()
   */
  protected function prepareAnnotationDefinition(AnnotationInterface $annotation, $class, ?BaseStaticReflectionParser $parser = NULL): void {
    if (!($annotation instanceof MultipleProviderAnnotationInterface)) {
      throw new \LogicException('AnnotatedClassDiscoveryAutomatedProviders annotations must implement ' . MultipleProviderAnnotationInterface::class);
    }
    if (!$parser) {
      throw new \LogicException('Parser argument must be passed for automated providers discovery.');
    }
    if (!method_exists($this, 'getProviderFromNamespace')) {
      throw new \LogicException('Classes using \Drupal\migrate\Plugin\Discovery\AnnotatedDiscoveryAutomatedProvidersTrait must have getProviderFromNamespace() method.');
    }
    // @see \Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery::prepareAnnotationDefinition()
    $annotation->setClass($class);
    $providers = $annotation->getProviders();
    // Loop through all the parent classes and add their providers (which we
    // infer by parsing their namespaces) to the $providers array.
    do {
      $providers[] = $this->getProviderFromNamespace($parser->getNamespaceName());
    } while ($parser = StaticReflectionParser::getParentParser($parser, $this->finder));
    $providers = array_diff(array_unique(array_filter($providers)), ['component']);
    $annotation->setProviders($providers);
  }

}
