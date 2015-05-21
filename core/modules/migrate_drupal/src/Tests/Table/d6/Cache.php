<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\Cache.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the cache table.
 */
class Cache extends DrupalDumpBase {

  public function load() {
    $this->createTable("cache", array(
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
    ));
    $this->database->insert("cache")->fields(array(
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
#a2937f7bc1d1a464b3497945341caba4
