<?php

namespace Drupal\comment;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\RequiredFieldStorageDefinitionInterface;

/**
 * Defines the comment schema handler.
 */
class CommentStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    if ($data_table = $this->storage->getDataTable()) {
      $schema[$data_table]['indexes'] += [
        'comment__status_pid' => ['pid', 'status'],
        'comment__num_new' => [
          'entity_id',
          'entity_type',
          'comment_type',
          'status',
          'created',
          'cid',
          'thread',
        ],
        'comment__entity_langcode' => [
          'entity_id',
          'entity_type',
          'comment_type',
          'default_langcode',
        ],
      ];
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'comment_field_data') {
      // Remove unneeded indexes.
      unset($schema['indexes']['comment_field__pid__target_id']);
      unset($schema['indexes']['comment_field__entity_id__target_id']);

      switch ($field_name) {
        case 'thread':
          // Improves the performance of the comment__num_new index defined
          // in getEntitySchema().
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;

        case 'entity_type':
        case 'field_name':
          assert($storage_definition instanceof RequiredFieldStorageDefinitionInterface);
          if ($storage_definition->isStorageRequired()) {
            // The 'entity_type' and 'field_name' are required so they also need
            // to be marked as NOT NULL.
            $schema['fields'][$field_name]['not null'] = TRUE;
          }
          break;

        case 'created':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;

        case 'uid':
          $this->addSharedTableFieldForeignKey($storage_definition, $schema, 'users', 'uid');
      }
    }

    return $schema;
  }

}
