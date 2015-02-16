<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Config.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the config table.
 */
class Config extends DrupalDumpBase {

  public function load() {
    $this->createTable("config", array(
      'primary key' => array(
        'collection',
        'name',
      ),
      'fields' => array(
        'collection' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'name' => array(
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
      ),
    ));
    $this->database->insert("config")->fields(array(
      'collection',
      'name',
      'data',
    ))
    ->values(array(
      'collection' => '',
      'name' => 'system.file',
      'data' => 'a:1:{s:4:"path";a:1:{s:9:"temporary";s:4:"/tmp";}}',
    ))->execute();
  }

}
