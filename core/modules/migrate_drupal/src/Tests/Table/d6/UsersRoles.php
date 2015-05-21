<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\UsersRoles.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

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
      'uid' => '2',
      'rid' => '3',
    ))->values(array(
      'uid' => '15',
      'rid' => '3',
    ))->values(array(
      'uid' => '16',
      'rid' => '3',
    ))->values(array(
      'uid' => '8',
      'rid' => '4',
    ))->values(array(
      'uid' => '15',
      'rid' => '4',
    ))->values(array(
      'uid' => '17',
      'rid' => '4',
    ))->values(array(
      'uid' => '8',
      'rid' => '5',
    ))->values(array(
      'uid' => '15',
      'rid' => '5',
    ))->values(array(
      'uid' => '16',
      'rid' => '5',
    ))->execute();
  }

}
#160988416b8b1403cc0ad78c4c1e944b
