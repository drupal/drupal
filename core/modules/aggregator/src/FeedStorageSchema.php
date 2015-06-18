<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedStorageSchema.
 */

namespace Drupal\aggregator;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the feed schema handler.
 */
class FeedStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'aggregator_feed') {
      switch ($field_name) {
        case 'url':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE, 255);
          break;

        case 'queued':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;

        case 'title':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;
      }
    }

    return $schema;
  }

}
