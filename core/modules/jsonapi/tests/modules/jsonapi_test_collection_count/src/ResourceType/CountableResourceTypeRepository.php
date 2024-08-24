<?php

declare(strict_types=1);

namespace Drupal\jsonapi_test_collection_count\ResourceType;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;

/**
 * Provides a repository of JSON:API configurable resource types.
 */
class CountableResourceTypeRepository extends ResourceTypeRepository {

  /**
   * {@inheritdoc}
   */
  protected function createResourceType(EntityTypeInterface $entity_type, $bundle) {
    $resource_type = parent::createResourceType($entity_type, $bundle);
    return new CountableResourceType(
      $resource_type->getEntityTypeId(),
      $resource_type->getBundle(),
      $resource_type->getDeserializationTargetClass(),
      $resource_type->isInternal(),
      $resource_type->isLocatable(),
      $resource_type->isMutable(),
      $resource_type->isVersionable(),
      $resource_type->getFields(),
      $resource_type->getTypeName()
    );
  }

}
