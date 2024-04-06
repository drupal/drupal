<?php

namespace Drupal\comment\Plugin\migrate\destination;

use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\migrate\Plugin\migrate\destination\EntityConfigBase;
use Drupal\migrate\Row;

/**
 * Comment type destination.
 */
#[MigrateDestination('entity:comment_type')]
class EntityCommentType extends EntityConfigBase {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $entity_ids = parent::import($row, $old_destination_id_values);
    \Drupal::service('comment.manager')->addBodyField(reset($entity_ids));
    return $entity_ids;
  }

}
