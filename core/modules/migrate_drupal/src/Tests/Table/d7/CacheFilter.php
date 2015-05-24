<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\CacheFilter.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the cache_filter table.
 */
class CacheFilter extends DrupalDumpBase {

  public function load() {
    $this->createTable("cache_filter", array(
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
          'length' => '6',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("cache_filter")->fields(array(
      'cid',
      'data',
      'expire',
      'created',
      'serialized',
    ))
    ->execute();
  }

}
#fbe86d7806627ea6c2017f5ea88ba7b9
