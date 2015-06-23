<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Accesslog.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the accesslog table.
 */
class Accesslog extends DrupalDumpBase {

  public function load() {
    $this->createTable("accesslog", array(
      'primary key' => array(
        'aid',
      ),
      'fields' => array(
        'aid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'sid' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'title' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ),
        'path' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ),
        'url' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'hostname' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '128',
        ),
        'uid' => array(
          'type' => 'int',
          'not null' => FALSE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'timer' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
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
    $this->database->insert("accesslog")->fields(array(
      'aid',
      'sid',
      'title',
      'path',
      'url',
      'hostname',
      'uid',
      'timer',
      'timestamp',
    ))
    ->values(array(
      'aid' => '89',
      'sid' => 's12XgEKLqGr998aW8pzRB4dCX5SkOVoDm4YO76PPv7g',
      'title' => 'Appearance',
      'path' => 'admin/appearance',
      'url' => 'http://localhost/sandbox7/node/1',
      'hostname' => '::1',
      'uid' => '1',
      'timer' => '922',
      'timestamp' => '1421843462',
    ))->values(array(
      'aid' => '90',
      'sid' => 's12XgEKLqGr998aW8pzRB4dCX5SkOVoDm4YO76PPv7g',
      'title' => 'Modules',
      'path' => 'admin/modules',
      'url' => 'http://localhost/sandbox7/admin/appearance',
      'hostname' => '::1',
      'uid' => '1',
      'timer' => '492',
      'timestamp' => '1421843474',
    ))->values(array(
      'aid' => '91',
      'sid' => 's12XgEKLqGr998aW8pzRB4dCX5SkOVoDm4YO76PPv7g',
      'title' => 'Configuration',
      'path' => 'admin/config',
      'url' => 'http://localhost/sandbox7/admin/modules',
      'hostname' => '::1',
      'uid' => '1',
      'timer' => '180',
      'timestamp' => '1421843480',
    ))->execute();
  }

}
#1aaa5fa899c08cfc6607be2b0599ef46
