<?php

namespace Drupal\Core\Entity;

/**
 * Provides a base entity class with extended revision and publishing support.
 *
 * @ingroup entity_api
 */
abstract class EditorialContentEntityBase extends ContentEntityBase implements EntityChangedInterface, EntityPublishedInterface, RevisionLogInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;
  use RevisionLogEntityTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the revision metadata fields.
    $fields += static::revisionLogBaseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    return $fields;
  }

}
