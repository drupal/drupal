<?php

namespace Drupal\migrate_events_test\Plugin\migrate\destination;

use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Row;

/**
 * Migration dummy destination.
 */
#[MigrateDestination(
  id: 'dummy',
  requirements_met: TRUE
)]
class DummyDestination extends DestinationBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['value']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return ['value' => 'Dummy value'];
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    return ['value' => $row->getDestinationProperty('value')];
  }

}
