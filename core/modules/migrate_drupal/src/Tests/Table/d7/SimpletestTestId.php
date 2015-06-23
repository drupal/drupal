<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\SimpletestTestId.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the simpletest_test_id table.
 */
class SimpletestTestId extends DrupalDumpBase {

  public function load() {
    $this->createTable("simpletest_test_id", array(
      'primary key' => array(
        'test_id',
      ),
      'fields' => array(
        'test_id' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'last_prefix' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '60',
          'default' => '',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("simpletest_test_id")->fields(array(
      'test_id',
      'last_prefix',
    ))
    ->execute();
  }

}
#8ee6808713104a181d069645521d0ca5
