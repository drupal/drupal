<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\CtoolsObjectCache.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the ctools_object_cache table.
 */
class CtoolsObjectCache extends DrupalDumpBase {

  public function load() {
    $this->createTable("ctools_object_cache", array(
      'primary key' => array(
        'sid',
        'name',
        'obj',
      ),
      'fields' => array(
        'sid' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
        ),
        'obj' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '32',
        ),
        'updated' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'data' => array(
          'type' => 'blob',
          'not null' => FALSE,
          'length' => 100,
        ),
      ),
    ));
    $this->database->insert("ctools_object_cache")->fields(array(
      'sid',
      'name',
      'obj',
      'updated',
      'data',
    ))
    ->execute();
  }

}
#ffb2022818224704fc2119666a2f0646
