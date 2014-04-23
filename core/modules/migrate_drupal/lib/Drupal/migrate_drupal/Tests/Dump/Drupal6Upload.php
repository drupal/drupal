<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6Upload.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

class Drupal6Upload extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->setModuleVersion('upload', 6000);
    $this->createTable('upload', array(
      'fields' => array(
        'fid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Primary Key: The {files}.fid.',
        ),
        'nid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The {node}.nid associated with the uploaded file.',
        ),
        'vid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Primary Key: The {node}.vid associated with the uploaded file.',
        ),
        'description' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Description of the uploaded file.',
        ),
        'list' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
          'description' => 'Whether the file should be visibly listed on the node: yes(1) or no(0).',
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'tiny',
          'description' => 'Weight of this upload in relation to other uploads in this node.',
        ),
      ),
      'primary key' => array('vid', 'fid'),
      'indexes' => array(
        'fid' => array('fid'),
        'nid' => array('nid'),
      ),
    ));
    $this->database->insert('upload')->fields(array(
      'nid',
      'vid',
      'fid',
      'description',
      'list',
      'weight',
    ))
    ->values(array(
      'nid' => 1,
      'vid' => 1,
      'fid' => 1,
      'description' => 'file 1-1-1',
      'list' => 0,
      'weight' => 5,
    ))
    ->values(array(
      'nid' => 1,
      'vid' => 2,
      'fid' => 2,
      'description' => 'file 1-2-2',
      'list' => 1,
      'weight' => 4,
    ))
    ->values(array(
      'nid' => 1,
      'vid' => 2,
      'fid' => 3,
      'description' => 'file 1-2-3',
      'list' => 0,
      'weight' => 3,
    ))
    ->values(array(
      'nid' => 2,
      'vid' => 3,
      'fid' => 2,
      'description' => 'file 2-3-2',
      'list' => 1,
      'weight' => 2,
    ))
    ->values(array(
      'nid' => 2,
      'vid' => 3,
      'fid' => 3,
      'description' => 'file 2-3-3',
      'list' => 0,
      'weight' => 1,
    ))
    ->execute();
  }

}
