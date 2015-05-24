<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\FileUsage.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the file_usage table.
 */
class FileUsage extends DrupalDumpBase {

  public function load() {
    $this->createTable("file_usage", array(
      'primary key' => array(
        'fid',
        'module',
        'type',
        'id',
      ),
      'fields' => array(
        'fid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'module' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
          'default' => '',
        ),
        'id' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'count' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
      ),
    ));
    $this->database->insert("file_usage")->fields(array(
      'fid',
      'module',
      'type',
      'id',
      'count',
    ))
    ->values(array(
      'fid' => '1',
      'module' => 'file',
      'type' => 'node',
      'id' => '1',
      'count' => '1',
    ))->execute();
  }

}
#9487016b893e8c923d60d751a1875230
