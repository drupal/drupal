<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\EntityCommentType.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\migrate\Row;

/**
 * @MigrateDestination(
 *   id = "entity:comment_type"
 * )
 */
class EntityCommentType extends EntityConfigBase {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    $entity_ids = parent::import($row, $old_destination_id_values);
    \Drupal::service('comment.manager')->addBodyField(reset($entity_ids));
    return $entity_ids;
  }

}
