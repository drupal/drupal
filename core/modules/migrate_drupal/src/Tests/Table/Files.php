<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Files.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the files table.
 */
class Files extends Drupal6DumpBase {

  public function load() {
    $this->createTable("files", array(
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
        'filepath' => array(
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
    ));
    $this->database->insert("files")->fields(array(
      'fid',
      'uid',
      'filename',
      'filepath',
      'filemime',
      'filesize',
      'status',
      'timestamp',
    ))
    ->values(array(
      'fid' => '1',
      'uid' => '1',
      'filename' => 'Image1.png',
      'filepath' => 'core/modules/simpletest/files/image-1.png',
      'filemime' => 'image/png',
      'filesize' => '39325',
      'status' => '1',
      'timestamp' => '1388880660',
    ))->values(array(
      'fid' => '2',
      'uid' => '1',
      'filename' => 'Image2.jpg',
      'filepath' => 'core/modules/simpletest/files/image-2.jpg',
      'filemime' => 'image/jpeg',
      'filesize' => '1831',
      'status' => '1',
      'timestamp' => '1388880664',
    ))->values(array(
      'fid' => '3',
      'uid' => '1',
      'filename' => 'Image-test.gif',
      'filepath' => 'core/modules/simpletest/files/image-test.gif',
      'filemime' => 'image/jpeg',
      'filesize' => '183',
      'status' => '1',
      'timestamp' => '1388880668',
    ))->values(array(
      'fid' => '5',
      'uid' => '1',
      'filename' => 'html-1.txt',
      'filepath' => 'core/modules/simpletest/files/html-1.txt',
      'filemime' => 'text/plain',
      'filesize' => '24',
      'status' => '1',
      'timestamp' => '1420858106',
    ))->values(array(
      'fid' => '6',
      'uid' => '1',
      'filename' => 'some-temp-file.jpg',
      'filepath' => '/tmp/some-temp-file.jpg',
      'filemime' => 'image/jpeg',
      'filesize' => '24',
      'status' => '0',
      'timestamp' => '1420858106',
    ))->execute();
  }

}
