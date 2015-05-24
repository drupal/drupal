<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Authmap.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the authmap table.
 */
class Authmap extends DrupalDumpBase {

  public function load() {
    $this->createTable("authmap", array(
      'primary key' => array(
        'aid',
      ),
      'fields' => array(
        'aid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'uid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'authname' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'module' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
      ),
    ));
    $this->database->insert("authmap")->fields(array(
      'aid',
      'uid',
      'authname',
      'module',
    ))
    ->execute();
  }

}
#d3b53fbf5d22670b0038998db6d6d13e
