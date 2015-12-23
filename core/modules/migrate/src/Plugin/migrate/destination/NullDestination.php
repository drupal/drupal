<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\NullDestination.
 */

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\migrate\Entity\MigrationInterface;
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
