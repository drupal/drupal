<?php

namespace Drupal\block\Plugin\migrate\destination;

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
    $properties = array(
      'plugin' => $row->getDestinationProperty('plugin'),
      'theme' => $row->getDestinationProperty('theme'),
    );
    $blocks = array_keys($this->storage->loadByProperties($properties));
    return reset($blocks);
  }

}
