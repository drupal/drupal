<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\UsersRoles.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the users_roles table.
 */
class UsersRoles extends DrupalDumpBase {

  public function load() {
    $this->createTable("users_roles", array(
      'primary key' => array(
        'uid',
        'rid',
      ),
      'fields' => array(
        'uid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'rid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("users_roles")->fields(array(
      'uid',
      'rid',
    ))
    ->values(array(
      'uid' => '1',
      'rid' => '3',
    ))->execute();
  }

}
#f85dd2dda1a860f0c2a963cbe784458a
