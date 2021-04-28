<?php

namespace Drupal\views\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\EntityConfigBase;
use Drupal\migrate\Row;

/**
 * Drupal 8 views destination.
 *
 * @MigrateDestination(
 *   id = "entity:view"
 * )
 */
class ViewsMigration extends EntityConfigBase {

  /**
   * ViewsMigration import.
   *
   * @param Drupal\migrate\Row $row
   *   The views migration row.
   * @param array $old_destination_id_values
   *   Old destination id values.
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $entity_ids = parent::import($row, $old_destination_id_values);
    return $entity_ids;
  }

}
