<?php

namespace Drupal\Core\Entity;

/**
 * Provides helper methods to detect changes in an entity object.
 *
 * @internal This may be replaced by a proper entity comparison handler.
 */
trait EntityChangesDetectionTrait {

  /**
   * Returns an array of field names to skip when checking for changes.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   A content entity object.
   *
   * @return string[]
   *   An array of field names.
   */
  protected function getFieldsToSkipFromTranslationChangesCheck(ContentEntityInterface $entity) {
    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
    $entity_type = $entity->getEntityType();

    // A list of known revision metadata fields which should be skipped from
    // the comparison.
    $fields = [
      $entity_type->getKey('revision'),
      $entity_type->getKey('revision_translation_affected'),
    ];
    $fields = array_merge($fields, array_values($entity_type->getRevisionMetadataKeys()));

    // Computed fields should be skipped by the check for translation changes.
    foreach (array_diff_key($entity->getFieldDefinitions(), array_flip($fields)) as $field_name => $field_definition) {
      if ($field_definition->isComputed()) {
        $fields[] = $field_name;
      }
    }

    return $fields;
  }

}
