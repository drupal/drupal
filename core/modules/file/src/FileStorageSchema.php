<?php

/**
 * @file
 * Contains \Drupal\file\FileStorageSchema.
 */

namespace Drupal\file;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the file schema handler.
 */
class FileStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'file_managed') {
      switch ($field_name) {
        case 'status':
        case 'changed':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;

        case 'uri':
          $this->addSharedTableFieldUniqueKey($storage_definition, $schema, TRUE);
          break;
      }
    }

    return $schema;
  }

}
