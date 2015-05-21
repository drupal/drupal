<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\CacheConfig.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the cache_config table.
 */
class CacheConfig extends DrupalDumpBase {

  public function load() {
    $this->createTable("cache_config", array(
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
          'type' => 'numeric',
          'not null' => TRUE,
          'precision' => '14',
          'scale' => '3',
          'default' => '0.000',
        ),
        'serialized' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'tags' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'checksum_invalidations' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'checksum_deletions' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("cache_config")->fields(array(
      'cid',
      'data',
      'expire',
      'created',
      'serialized',
      'tags',
      'checksum_invalidations',
      'checksum_deletions',
    ))
    ->execute();
  }

}
#f438fd9400357de86c6fd107fc245404
