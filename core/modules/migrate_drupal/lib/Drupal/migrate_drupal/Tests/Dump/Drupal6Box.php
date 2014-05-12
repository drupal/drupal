<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6Box.
 */

namespace Drupal\migrate_drupal\Tests\Dump;

class Drupal6Box extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->createTable('boxes', array(
      'description' => 'Stores contents of custom-made blocks.',
      'fields' => array(
        'bid' => array(
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'description' => "The block's {blocks}.bid.",
        ),
        'body' => array(
          'type' => 'text',
          'not null' => FALSE,
          'size' => 'big',
          'description' => 'Block contents.',
        ),
        'info' => array(
          'type' => 'varchar',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Block description.',
        ),
        'format' => array(
          'type' => 'int',
          'size' => 'small',
          'not null' => TRUE,
          'default' => 0,
          'description' => "Block body's {filter_formats}.format; for example, 1 = Filtered HTML.",
        ),
      ),
      'unique keys' => array('info' => array('info')),
      'primary key' => array('bid'),
    ));

    $this->database->insert('boxes')->fields(array(
      'bid',
      'body',
      'info',
      'format',
    ))
    ->values(array(
      'bid' => '1',
      'body' => '<h3>My first custom block body</h3>',
      'info' => 'My block 1',
      'format' => 2,
    ))
    ->values(array(
      'bid' => '2',
      'body' => '<h3>My second custom block body</h3>',
      'info' => 'My block 2',
      'format' => 2,
    ))
    ->execute();
  }
}
