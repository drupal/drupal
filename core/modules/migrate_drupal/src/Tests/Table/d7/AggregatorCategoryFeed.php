<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\AggregatorCategoryFeed.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the aggregator_category_feed table.
 */
class AggregatorCategoryFeed extends DrupalDumpBase {

  public function load() {
    $this->createTable("aggregator_category_feed", array(
      'primary key' => array(
        'fid',
        'cid',
      ),
      'fields' => array(
        'fid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'cid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("aggregator_category_feed")->fields(array(
      'fid',
      'cid',
    ))
    ->execute();
  }

}
#b1438209e6c5437928ec638c06bea9f1
