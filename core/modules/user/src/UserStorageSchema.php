<?php

/**
 * @file
 * Contains \Drupal\user\UserStorageSchema.
 */

namespace Drupal\user;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the user schema handler.
 */
class UserStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // The "users" table does not use serial identifiers.
    $schema['users']['fields']['uid']['type'] = 'int';

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['users_field_data']['fields']['access']['not null'] = TRUE;
    $schema['users_field_data']['fields']['created']['not null'] = TRUE;
    $schema['users_field_data']['fields']['name']['not null'] = TRUE;

    $schema['users_field_data']['indexes'] += array(
      'user__access' => array('access'),
      'user__created' => array('created'),
      'user__mail' => array('mail'),
    );
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

}
