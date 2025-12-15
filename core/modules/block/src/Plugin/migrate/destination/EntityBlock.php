<?php

namespace Drupal\block\Plugin\migrate\destination;

use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\EntityConfigBase;
use Drupal\migrate\Row;

/**
 * Migrate destination for block entity.
 */
#[MigrateDestination('entity:block')]
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
   *
   * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3533565
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533565', E_USER_DEPRECATED);
    try {
      $entity_ids = parent::import($row, $old_destination_id_values);
    }
    catch (SchemaIncompleteException $e) {
      throw new MigrateException($e->getMessage());
    }
    return $entity_ids;
  }

}
