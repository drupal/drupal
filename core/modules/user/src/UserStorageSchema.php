<?php

/**
 * @file
 * Contains \Drupal\user\UserStorageSchema.
 */

namespace Drupal\user;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the user schema handler.
 */
class UserStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['users_field_data']['unique keys'] += array(
      'user__name' => array('name', 'langcode'),
    );

    $schema['users_roles'] = array(
      'description' => 'Maps users to roles.',
      'fields' => array(
        'uid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Primary Key: {users}.uid for user.',
        ),
        'rid' => array(
          'type' => 'varchar',
          'length' => 64,
          'not null' => TRUE,
          'description' => 'Primary Key: ID for the role.',
        ),
      ),
      'primary key' => array('uid', 'rid'),
      'indexes' => array(
        'rid' => array('rid'),
      ),
      'foreign keys' => array(
        'user' => array(
          'table' => 'users',
          'columns' => array('uid' => 'uid'),
        ),
      ),
    );

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function processIdentifierSchema(&$schema, $key) {
    // The "users" table does not use serial identifiers.
    if ($key != $this->entityType->getKey('id')) {
      parent::processIdentifierSchema($schema, $key);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'users_field_data') {
      switch ($field_name) {
        case 'name':
          // Improves the performance of the user__name index defined
          // in getEntitySchema().
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;

        case 'mail':
          $this->addSharedTableFieldIndex($storage_definition, $schema);
          break;

        case 'access':
        case 'created':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;
      }
    }

    return $schema;
  }

}
