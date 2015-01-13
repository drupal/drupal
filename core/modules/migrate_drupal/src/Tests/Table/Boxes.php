<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Boxes.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see cores/scripts/dump-database-d6.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table;

use Drupal\migrate_drupal\Tests\Dump\Drupal6DumpBase;

/**
 * Generated file to represent the boxes table.
 */
class Boxes extends Drupal6DumpBase {

  public function load() {
    $this->createTable("boxes", array(
      'primary key' => array(
        'bid',
      ),
      'fields' => array(
        'bid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'body' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'info' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'format' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '6',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("boxes")->fields(array(
      'bid',
      'body',
      'info',
      'format',
    ))
    ->values(array(
      'bid' => '1',
      'body' => '<h3>My first custom block body</h3>',
      'info' => 'My block 1',
      'format' => '2',
    ))->values(array(
      'bid' => '2',
      'body' => '<h3>My second custom block body</h3>',
      'info' => 'My block 2',
      'format' => '2',
    ))->execute();
  }

}
