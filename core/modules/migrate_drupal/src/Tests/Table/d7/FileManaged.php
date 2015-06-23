<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\FileManaged.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the file_managed table.
 */
class FileManaged extends DrupalDumpBase {

  public function load() {
    $this->createTable("file_managed", array(
      'primary key' => array(
        'fid',
      ),
      'fields' => array(
        'fid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'uid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'filename' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'uri' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'filemime' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'filesize' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'status' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'timestamp' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("file_managed")->fields(array(
      'fid',
      'uid',
      'filename',
      'uri',
      'filemime',
      'filesize',
      'status',
      'timestamp',
    ))
    ->values(array(
      'fid' => '1',
      'uid' => '1',
      'filename' => 'cube.jpeg',
      'uri' => 'public://cube.jpeg',
      'filemime' => 'image/jpeg',
      'filesize' => '3620',
      'status' => '1',
      'timestamp' => '1421727515',
    ))->execute();
  }

}
#56e60ddebedd173968ca05c1c87b0f20
