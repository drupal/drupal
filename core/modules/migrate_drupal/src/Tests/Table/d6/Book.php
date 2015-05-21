<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\Book.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the book table.
 */
class Book extends DrupalDumpBase {

  public function load() {
    $this->createTable("book", array(
      'primary key' => array(
        'mlid',
      ),
      'fields' => array(
        'mlid' => array(
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
        'bid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
      ),
    ));
    $this->database->insert("book")->fields(array(
      'mlid',
      'nid',
      'bid',
    ))
    ->values(array(
      'mlid' => '1',
      'nid' => '4',
      'bid' => '4',
    ))->values(array(
      'mlid' => '2',
      'nid' => '5',
      'bid' => '4',
    ))->values(array(
      'mlid' => '3',
      'nid' => '6',
      'bid' => '4',
    ))->values(array(
      'mlid' => '4',
      'nid' => '7',
      'bid' => '4',
    ))->values(array(
      'mlid' => '5',
      'nid' => '8',
      'bid' => '8',
    ))->execute();
  }

}
#a91ece4a284d1ba43d247d623edd7a74
