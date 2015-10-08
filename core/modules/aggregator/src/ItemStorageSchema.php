<?php

/**
 * @file
 * Contains \Drupal\aggregator\ItemStorageSchema.
 */

namespace Drupal\aggregator;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the item schema handler.
 */
class ItemStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'aggregator_item') {
      switch ($field_name) {
        case 'timestamp':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;

        case 'fid':
          $this->addSharedTableFieldForeignKey($storage_definition, $schema, 'aggregator_feed', 'fid');
          break;
      }
    }

    return $schema;
  }

}
