<?php

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
    $raw_fields = $this->getAllFieldNames($entity_type, $bundle);
    return new CountableResourceType(
      $entity_type->id(),
      $bundle,
      $entity_type->getClass(),
      $entity_type->isInternal(),
      static::isLocatableResourceType($entity_type, $bundle),
      static::isMutableResourceType($entity_type, $bundle),
      static::getFieldMapping($raw_fields, $entity_type, $bundle)
    );
  }

}
