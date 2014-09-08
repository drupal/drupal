<?php

/**
 * @file
 * Contains \Drupal\aggregator\ItemStorageSchema.
 */

namespace Drupal\aggregator;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the item schema handler.
 */
class ItemStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['aggregator_item']['fields']['timestamp']['not null'] = TRUE;

    $schema['aggregator_item']['indexes'] += array(
      'aggregator_item__timestamp' => array('timestamp'),
    );
    $schema['aggregator_item']['foreign keys'] += array(
      'aggregator_item__aggregator_feed' => array(
        'table' => 'aggregator_feed',
        'columns' => array('fid' => 'fid'),
      ),
    );

    return $schema;
  }

}
