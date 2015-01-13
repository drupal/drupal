<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Role.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the role table.
 */
class Role extends Drupal6DumpBase {

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
      ),
    ));
    $this->database->insert("role")->fields(array(
      'rid',
      'name',
    ))
    ->values(array(
      'rid' => '1',
      'name' => 'anonymous user',
    ))->values(array(
      'rid' => '2',
      'name' => 'authenticated user',
    ))->values(array(
      'rid' => '3',
      'name' => 'migrate test role 1',
    ))->values(array(
      'rid' => '4',
      'name' => 'migrate test role 2',
    ))->values(array(
      'rid' => '5',
      'name' => 'migrate test role 3 that is longer than thirty two characters',
    ))->execute();
  }

}
