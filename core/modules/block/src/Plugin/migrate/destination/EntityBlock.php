<?php

namespace Drupal\block\Plugin\migrate\destination;

use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\EntityConfigBase;
use Drupal\migrate\Row;

/**
 * @MigrateDestination(
 *   id = "entity:block"
 * )
 */
class EntityBlock extends EntityConfigBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntityId(Row $row) {
    // Try to find the block by its plugin ID and theme.
    $properties = [
      'plugin' => $row->getDestinationProperty('plugin'),
      'theme' => $row->getDestinationProperty('theme'),
    ];
    $blocks = array_keys($this->storage->loadByProperties($properties));
    return reset($blocks);
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    try {
      $entity_ids = parent::import($row, $old_destination_id_values);
    }
    catch (SchemaIncompleteException $e) {
      throw new MigrateException($e->getMessage());
    }
    return $entity_ids;
  }

}
