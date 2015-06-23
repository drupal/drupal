<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\CacheMenu.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the cache_menu table.
 */
class CacheMenu extends DrupalDumpBase {

  public function load() {
    $this->createTable("cache_menu", array(
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
        'headers' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
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
    $this->database->insert("cache_menu")->fields(array(
      'cid',
      'data',
      'expire',
      'created',
      'headers',
      'serialized',
    ))
    ->execute();
  }

}
#8619c81798239c1dacaaef75f55ee4be
