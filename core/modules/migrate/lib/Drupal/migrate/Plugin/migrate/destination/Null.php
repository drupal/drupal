<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\Null.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Row;

/**
 * @MigrateDestination(
 *   id = "null",
 *   requirements_met = false
 * )
 */
class Null extends DestinationBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
  }

}
