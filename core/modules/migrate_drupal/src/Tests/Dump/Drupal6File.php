<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6File.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

/**
 * Database dump for testing file migrations.
 */
class Drupal6File extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {

    $this->createTable('files', array(
      'fields' => array(
        'fid' => array(
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
        'uid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'filename' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'filepath' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'filemime' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'filesize' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'status' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'timestamp' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
      'indexes' => array(
        'uid' => array(
          'uid',
        ),
        'status' => array(
          'status',
        ),
        'timestamp' => array(
          'timestamp',
        ),
      ),
      'primary key' => array(
        'fid',
      ),
      'module' => 'system',
      'name' => 'files',
    ));
    $this->database->insert('files')->fields(array(
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
    ))
    ->values(array(
      'fid' => '2',
      'uid' => '1',
      'filename' => 'Image2.jpg',
      'filepath' => 'core/modules/simpletest/files/image-2.jpg',
      'filemime' => 'image/jpeg',
      'filesize' => '1831',
      'status' => '1',
      'timestamp' => '1388880664',
    ))
    ->values(array(
      'fid' => '3',
      'uid' => '1',
      'filename' => 'Image-test.gif',
      'filepath' => 'core/modules/simpletest/files/image-test.gif',
      'filemime' => 'image/jpeg',
      'filesize' => '183',
      'status' => '1',
      'timestamp' => '1388880668',
    ))
    ->execute();
  }

}
