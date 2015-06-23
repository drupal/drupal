<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\Files.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the files table.
 */
class Files extends DrupalDumpBase {

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
      'mysql_character_set' => 'utf8',
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
#a36145ffe53b2dd78475b37d99e72612
