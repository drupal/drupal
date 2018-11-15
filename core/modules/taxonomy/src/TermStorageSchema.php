<?php

namespace Drupal\taxonomy;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the term schema handler.
 */
class TermStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    if ($data_table = $this->storage->getDataTable()) {
      $schema[$data_table]['indexes'] += [
        'taxonomy_term__tree' => ['vid', 'weight', 'name'],
        'taxonomy_term__vid_name' => ['vid', 'name'],
      ];
    }

    $schema['taxonomy_index'] = [
      'description' => 'Maintains denormalized information about node/term relationships.',
      'fields' => [
        'nid' => [
          'description' => 'The {node}.nid this record tracks.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
        'tid' => [
          'description' => 'The term ID.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
        'status' => [
          'description' => 'Boolean indicating whether the node is published (visible to non-administrators).',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 1,
        ],
        'sticky' => [
          'description' => 'Boolean indicating whether the node is sticky.',
          'type' => 'int',
          'not null' => FALSE,
          'default' => 0,
          'size' => 'tiny',
        ],
        'created' => [
          'description' => 'The Unix timestamp when the node was created.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
      ],
      'primary key' => ['nid', 'tid'],
      'indexes' => [
        'term_node' => ['tid', 'status', 'sticky', 'created'],
      ],
      'foreign keys' => [
        'tracked_node' => [
          'table' => 'node',
          'columns' => ['nid' => 'nid'],
        ],
        'term' => [
          'table' => 'taxonomy_term_data',
          'columns' => ['tid' => 'tid'],
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'taxonomy_term_field_data') {
      // Remove unneeded indexes.
      unset($schema['indexes']['taxonomy_term_field__vid__target_id']);
      unset($schema['indexes']['taxonomy_term_field__description__format']);

      switch ($field_name) {
        case 'weight':
          // Improves the performance of the taxonomy_term__tree index defined
          // in getEntitySchema().
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;

        case 'name':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;
      }
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDedicatedTableSchema(FieldStorageDefinitionInterface $storage_definition, ContentEntityTypeInterface $entity_type = NULL) {
    $dedicated_table_schema = parent::getDedicatedTableSchema($storage_definition, $entity_type);

    // Add an index on 'bundle', 'delta' and 'parent_target_id' columns to
    // increase the performance of the query from
    // \Drupal\taxonomy\TermStorage::getVocabularyHierarchyType().
    if ($storage_definition->getName() === 'parent') {
      /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
      $table_mapping = $this->storage->getTableMapping();
      $dedicated_table_name = $table_mapping->getDedicatedDataTableName($storage_definition);

      unset($dedicated_table_schema[$dedicated_table_name]['indexes']['bundle']);
      $dedicated_table_schema[$dedicated_table_name]['indexes']['bundle_delta_target_id'] = [
        'bundle',
        'delta',
        $table_mapping->getFieldColumnName($storage_definition, 'target_id'),
      ];
    }

    return $dedicated_table_schema;
  }

}
