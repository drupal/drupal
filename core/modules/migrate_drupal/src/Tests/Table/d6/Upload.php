<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\Upload.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the upload table.
 */
class Upload extends DrupalDumpBase {

  public function load() {
    $this->createTable("upload", array(
      'primary key' => array(
        'fid',
        'vid',
      ),
      'fields' => array(
        'fid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'nid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'vid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'description' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'list' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("upload")->fields(array(
      'fid',
      'nid',
      'vid',
      'description',
      'list',
      'weight',
    ))
    ->values(array(
      'fid' => '1',
      'nid' => '1',
      'vid' => '1',
      'description' => 'file 1-1-1',
      'list' => '0',
      'weight' => '-1',
    ))->values(array(
      'fid' => '2',
      'nid' => '1',
      'vid' => '2',
      'description' => 'file 1-2-2',
      'list' => '1',
      'weight' => '4',
    ))->values(array(
      'fid' => '2',
      'nid' => '2',
      'vid' => '3',
      'description' => 'file 2-3-2',
      'list' => '1',
      'weight' => '2',
    ))->values(array(
      'fid' => '3',
      'nid' => '1',
      'vid' => '2',
      'description' => 'file 1-2-3',
      'list' => '0',
      'weight' => '3',
    ))->values(array(
      'fid' => '3',
      'nid' => '2',
      'vid' => '3',
      'description' => 'file 2-3-3',
      'list' => '0',
      'weight' => '1',
    ))->execute();
  }

}
#adb3ab1babf69826197c48bfaa0804ab
