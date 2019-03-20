<?php

namespace Drupal\jsonapi_test_field_aliasing\ResourceType;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;

/**
 * Provides a repository of JSON:API resource types with aliasable field names.
 */
class AliasingResourceTypeRepository extends ResourceTypeRepository {

  /**
   * {@inheritdoc}
   */
  protected static function getFieldMapping(array $field_names, EntityTypeInterface $entity_type, $bundle) {
    $mapping = parent::getFieldMapping($field_names, $entity_type, $bundle);
    foreach ($field_names as $field_name) {
      if (strpos($field_name, 'field_test_alias_') === 0) {
        $mapping[$field_name] = 'field_test_alias';
      }
    }
    return $mapping;
  }

}
