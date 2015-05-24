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
#a02e273463ff074f34cd9819f90a8332
