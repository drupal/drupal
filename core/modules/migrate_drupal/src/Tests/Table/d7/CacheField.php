<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\CacheField.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the cache_field table.
 */
class CacheField extends DrupalDumpBase {

  public function load() {
    $this->createTable("cache_field", array(
      'primary key' => array(
        'cid',
      ),
      'fields' => array(
        'cid' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'data' => array(
          'type' => 'blob',
          'not null' => FALSE,
          'length' => 100,
        ),
        'expire' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'created' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'serialized' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("cache_field")->fields(array(
      'cid',
      'data',
      'expire',
      'created',
      'serialized',
    ))
    ->execute();
  }

}
#1d540d71bfaac8c084b0ba9f8ef2fdee
