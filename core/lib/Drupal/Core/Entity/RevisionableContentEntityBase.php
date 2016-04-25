<?php

namespace Drupal\Core\Entity;

/**
 * Provides a content entity with extended support for revisions.
 *
 * In addition to the parent entity class, base fields and methods for
 * accessing the revision log message, revision owner and the revision creation
 * time are provided.
 *
 * @ingroup entity_api
 */
abstract class RevisionableContentEntityBase extends ContentEntityBase implements RevisionLogInterface {

  use RevisionLogEntityTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields += static::revisionLogBaseFieldDefinitions($entity_type);
    return $fields;
  }

}
