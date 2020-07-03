<?php

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\migrate\Row;

/**
 * Provides null destination plugin.
 *
 * @MigrateDestination(
 *   id = "null",
 *   requirements_met = false
 * )
 */
class NullDestination extends DestinationBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
  }

}
