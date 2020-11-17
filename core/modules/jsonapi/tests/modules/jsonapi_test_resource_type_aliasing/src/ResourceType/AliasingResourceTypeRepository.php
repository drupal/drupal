<?php

namespace Drupal\jsonapi_test_resource_type_aliasing\ResourceType;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository as ResourceTypeRepositoryBase;

/**
 * Provides JSON:API resource types with a different type name pattern.
 *
 * @todo remove this class in https://www.drupal.org/project/drupal/issues/3105318
 */
class AliasingResourceTypeRepository extends ResourceTypeRepositoryBase {

  /**
   * {@inheritdoc}
   */
  protected function createResourceType(EntityTypeInterface $entity_type, $bundle) {
    $alias = sprintf('%s==%s', $entity_type->id(), $bundle);
    $base_resource_type = parent::createResourceType($entity_type, $bundle);
    return new AliasedResourceType($base_resource_type, $alias);
  }

}
