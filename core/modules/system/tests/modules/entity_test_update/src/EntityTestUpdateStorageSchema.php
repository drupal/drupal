<?php

namespace Drupal\entity_test_update;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the entity_test_update storage_schema handler.
 */
class EntityTestUpdateStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    if ($entity_type->id() == 'entity_test_update') {
      $schema[$entity_type->getBaseTable()]['indexes'] += \Drupal::state()->get('entity_test_update.additional_entity_indexes', []);
    }
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);

    if (\Drupal::state()->get('entity_test_update.additional_field_index.' . $table_name . '.' . $storage_definition->getName())) {
      $this->addSharedTableFieldIndex($storage_definition, $schema);
    }

    return $schema;
  }

}
