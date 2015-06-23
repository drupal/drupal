<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\Boxes.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the boxes table.
 */
class Boxes extends DrupalDumpBase {

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
          'length' => '11',
          'default' => '0',
        ),
      ),
      'mysql_character_set' => 'utf8',
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
#2210f6e6a50ddd9c00900cc7e54a5b43
