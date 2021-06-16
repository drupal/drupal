<?php

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Entity\RevisionLogInterface;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_mulrev_changed_rev",
 *   label = @Translation("Test entity - revisions log and data table"),
 *   base_table = "entity_test_mulrev_changed_revlog",
 *   revision_table = "entity_test_mulrev_changed_revlog_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "revision" = "revision_id",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message"
 *   },
 * )
 */
class EntityTestMulRevChangedWithRevisionLog extends EntityTestMulRevChanged implements RevisionLogInterface {

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
