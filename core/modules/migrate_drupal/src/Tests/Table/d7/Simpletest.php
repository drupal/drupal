<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Simpletest.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the simpletest table.
 */
class Simpletest extends DrupalDumpBase {

  public function load() {
    $this->createTable("simpletest", array(
      'primary key' => array(
        'message_id',
      ),
      'fields' => array(
        'message_id' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'test_id' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'test_class' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'status' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '9',
          'default' => '',
        ),
        'message' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
        'message_group' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'function' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'line' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'file' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("simpletest")->fields(array(
      'message_id',
      'test_id',
      'test_class',
      'status',
      'message',
      'message_group',
      'function',
      'line',
      'file',
    ))
    ->execute();
  }

}
#88369dad7154203ce0fa1eee7f392942
