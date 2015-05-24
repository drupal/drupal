<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Book.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

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
    ->execute();
  }

}
#1beb26f2dacfd20f487eff98597bd70e
