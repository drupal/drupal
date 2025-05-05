<?php

declare(strict_types=1);

namespace Drupal\migrate\Plugin\Discovery;

use Drupal\Component\Annotation\Doctrine\StaticReflectionParser as BaseStaticReflectionParser;
use Drupal\Component\Annotation\Reflection\MockFileFinder;
use Drupal\Component\ClassFinder\ClassFinder;
use Drupal\Component\Plugin\Attribute\AttributeInterface;
use Drupal\Core\Plugin\Discovery\AttributeDiscoveryWithAnnotations;

/**
 * Enables both attribute and annotation discovery for plugin definitions.
 *
 * @internal
 *   This is a temporary solution to the fact that migration source plugins have
 *   more than one provider. This functionality will be moved to core in
 *   https://www.drupal.org/node/2786355.
 */
class AttributeDiscoveryWithAnnotationsAutomatedProviders extends AttributeDiscoveryWithAnnotations {

  use AnnotatedDiscoveryAutomatedProvidersTrait;

  /**
   * Instance of attribute class discovery with automatic providers.
   *
   * Since there isn't multiple inheritance, instantiate the attribute only
   * discovery for code reuse.
   *
   * @var \Drupal\migrate\Plugin\Discovery\AttributeClassDiscoveryAutomatedProviders
   */
  private AttributeClassDiscoveryAutomatedProviders $attributeDiscovery;

  public function __construct(
    string $subdir,
    \Traversable $rootNamespaces,
    string $pluginDefinitionAttributeName = 'Drupal\Component\Plugin\Attribute\Plugin',
    string $pluginDefinitionAnnotationName = 'Drupal\Component\Annotation\Plugin',
    array $additionalNamespaces = [],
  ) {
    parent::__construct($subdir, $rootNamespaces, $pluginDefinitionAttributeName, $pluginDefinitionAnnotationName, $additionalNamespaces);
    $this->finder = new ClassFinder();
    $this->attributeDiscovery = new AttributeClassDiscoveryAutomatedProviders($subdir, $rootNamespaces, $pluginDefinitionAttributeName);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareAttributeDefinition(AttributeInterface $attribute, string $class): void {
    $this->attributeDiscovery->prepareAttributeDefinition($attribute, $class);
  }

  /**
   * {@inheritdoc}
   */
  protected function parseClass(string $class, \SplFileInfo $fileinfo): array {
    // Parse using attributes first.
    $definition = $this->attributeDiscovery->parseClass($class, $fileinfo);
    if (isset($definition['id'])) {
      return $definition;
    }

    // The filename is already known, so there is no need to find the
    // file. However, StaticReflectionParser needs a finder, so use a
    // mock version.
    $finder = MockFileFinder::create($fileinfo->getPathName());
    // The parser is instantiated here with FALSE as the last parameter. This is
    // needed so that the parser includes the 'extends' declaration and extracts
    // providers from ancestor classes.
    $parser = new BaseStaticReflectionParser($class, $finder, FALSE);

    $reflection_class = $parser->getReflectionClass();
    // @todo Handle deprecating definitions discovery via annotations in
    // https://www.drupal.org/project/drupal/issues/3522409.
    /** @var \Drupal\Component\Annotation\AnnotationInterface $annotation */
    if ($annotation = $this->getAnnotationReader()->getClassAnnotation($reflection_class, $this->pluginDefinitionAnnotationName)) {
      $this->prepareAnnotationDefinition($annotation, $class, $parser);
      return ['id' => $annotation->getId(), 'content' => $annotation->get()];
    }

    return ['id' => NULL, 'content' => NULL];
  }

}
