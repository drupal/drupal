<?php

namespace Drupal\serialization\EntityResolver;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Resolver delegating the entity resolution to a chain of resolvers.
 */
class ChainEntityResolver implements ChainEntityResolverInterface {

  /**
   * Constructs a ChainEntityResolver object.
   *
   * @param \Drupal\serialization\EntityResolver\EntityResolverInterface[] $resolvers
   *   The array of concrete resolvers.
   */
  public function __construct(
    protected array $resolvers = [],
  ) {
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
