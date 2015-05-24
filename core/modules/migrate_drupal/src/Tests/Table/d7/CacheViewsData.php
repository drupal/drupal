<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\CacheViewsData.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the cache_views_data table.
 */
class CacheViewsData extends DrupalDumpBase {

  public function load() {
    $this->createTable("cache_views_data", array(
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
          'default' => '1',
        ),
      ),
    ));
    $this->database->insert("cache_views_data")->fields(array(
      'cid',
      'data',
      'expire',
      'created',
      'serialized',
    ))
    ->execute();
  }

}
#f77e8f16bdd13efe2d0c6169b6021e2a
