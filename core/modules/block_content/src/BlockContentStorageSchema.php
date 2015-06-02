<?php

/**
 * @file
 * Contains \Drupal\block_content\BlockContentStorageSchema.
 */

namespace Drupal\block_content;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the block content schema handler.
 */
class BlockContentStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['block_content_field_data']['unique keys'] += array(
      'block_content__info' => array('info', 'langcode'),
    );

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'block_content_field_data') {
      switch ($field_name) {
        case 'info':
          // Improves the performance of the block_content__info index defined
          // in getEntitySchema().
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;
      }
    }

    return $schema;
  }

}
