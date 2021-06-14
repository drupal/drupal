<?php

namespace Drupal\serialization\EntityResolver;

/**
 * An interface for delegating an entity resolution to a chain of resolvers.
 */
interface ChainEntityResolverInterface extends EntityResolverInterface {

  /**
   * Adds an entity resolver.
   *
   * @param \Drupal\serialization\EntityResolver\EntityResolverInterface $resolver
   *   The entity resolver to add.
   */
  public function addResolver(EntityResolverInterface $resolver);

}
