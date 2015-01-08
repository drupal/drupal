<?php

/**
 * @file
 * Contains \Drupal\serialization\EntityResolver\ChainEntityResolver
 */

namespace Drupal\serialization\EntityResolver;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Resolver delegating the entity resolution to a chain of resolvers.
 */
class ChainEntityResolver implements ChainEntityResolverInterface {

  /**
   * The concrete resolvers.
   *
   * @var \Drupal\serialization\EntityResolver\EntityResolverInterface[]
   */
  protected $resolvers = array();

  /**
   * Constructs a ChainEntityResolver object.
   *
   * @param \Drupal\serialization\EntityResolver\EntityResolverInterface[] $resolvers
   *   The array of concrete resolvers.
   */
  public function __construct(array $resolvers = array()) {
    $this->resolvers = $resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function addResolver(EntityResolverInterface $resolver) {
    $this->resolvers[] = $resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(NormalizerInterface $normalizer, $data, $entity_type) {
    foreach ($this->resolvers as $resolver) {
      $resolved = $resolver->resolve($normalizer, $data, $entity_type);
      if (isset($resolved)) {
        return $resolved;
      }
    }
  }

}
