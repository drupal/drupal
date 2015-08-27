<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Base class for D7 source plugins which need to collect field values from
 * the Field API.
 */
abstract class FieldableEntity extends DrupalSqlBase {

  /**
   * Returns all non-deleted field instances attached to a specific entity type.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string|NULL $bundle
   *   (optional) The bundle.
   *
   * @return array[]
   *   The field instances, keyed by field name.
   */
  protected function getFields($entity_type, $bundle = NULL) {
    return $this->select('field_config_instance', 'fci')
      ->fields('fci')
      ->condition('entity_type', $entity_type)
      ->condition('bundle', isset($bundle) ? $bundle : $entity_type)
      ->condition('deleted', 0)
      ->execute()
      ->fetchAllAssoc('field_name');
  }

  /**
   * Retrieves field values for a single field of a single entity.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field
   *   The field name.
   * @param int $entity_id
   *   The entity ID.
   * @param int|null $revision_id
   *   (optional) The entity revision ID.
   *
   * @return array
   *   The raw field values, keyed by delta.
   *
   * @todo Support multilingual field values.
   */
  protected function getFieldValues($entity_type, $field, $entity_id, $revision_id = NULL) {
    $table = (isset($revision_id) ? 'field_revision_' : 'field_data_') . $field;
    $query = $this->select($table, 't')
      ->fields('t')
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $entity_id)
      ->condition('deleted', 0);
    if (isset($revision_id)) {
      $query->condition('revision_id', $revision_id);
    }
    $values = [];
    foreach ($query->execute() as $row) {
      foreach ($row as $key => $value) {
        $delta = $row['delta'];
        if (strpos($key, $field) === 0) {
          $column = substr($key, strlen($field) + 1);
          $values[$delta][$column] = $value;
        }
      }
    }
    return $values;
  }

}
