<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\SearchDataset.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the search_dataset table.
 */
class SearchDataset extends DrupalDumpBase {

  public function load() {
    $this->createTable("search_dataset", array(
      'primary key' => array(
        'sid',
        'type',
      ),
      'fields' => array(
        'sid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '16',
        ),
        'data' => array(
          'type' => 'text',
          'not null' => TRUE,
          'length' => 100,
        ),
        'reindex' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
      ),
    ));
    $this->database->insert("search_dataset")->fields(array(
      'sid',
      'type',
      'data',
      'reindex',
    ))
    ->values(array(
      'sid' => '1',
      'type' => 'node',
      'data' => ' a node 1 default examplecom another examplecom 99999999 monday january 19 2015 2215 monday january 19 2015 2215 prefix value120suffix value abc5xyz click here some more text 9 a comment permalink submitted by admin on mon 1192015 2218 this is a comment log in or register to post comments  ',
      'reindex' => '0',
    ))->execute();
  }

}
#1347bb4f56e9cdf19e54efc2f8837783
