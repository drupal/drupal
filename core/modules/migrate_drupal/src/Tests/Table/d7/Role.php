<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Role.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the role table.
 */
class Role extends DrupalDumpBase {

  public function load() {
    $this->createTable("role", array(
      'primary key' => array(
        'rid',
      ),
      'fields' => array(
        'rid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
          'default' => '',
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("role")->fields(array(
      'rid',
      'name',
      'weight',
    ))
    ->values(array(
      'rid' => '1',
      'name' => 'anonymous user',
      'weight' => '0',
    ))->values(array(
      'rid' => '2',
      'name' => 'authenticated user',
      'weight' => '1',
    ))->values(array(
      'rid' => '3',
      'name' => 'administrator',
      'weight' => '2',
    ))->execute();
  }

}
#d09ac267302832e11a9b70c9d03dc112
